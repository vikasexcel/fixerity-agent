import { v4 as uuidv4 } from 'uuid';
import { redisClient } from '../config/redis.js';
import { runConversation, conversationStore, serviceCategoryManager } from './conversationGraph.js';
import { runNegotiationAndMatchStream, updateNegotiationOutcome } from './negotiationOrchestrator.js';
import { runSellerProfileConversation, sellerProfileStore } from './seller/sellerProfileGraph.js';
import { findJobsForSeller } from './seller/jobMatchingGraph.js';
import { submitSellerBid } from './seller/sellerBiddingGraph.js';
import { getSellerDashboard } from './seller/sellerOrchestrator.js';
import { SellerProfile } from '../models/SellerProfile.js'
import { ChatOpenAI } from '@langchain/openai';
import { HumanMessage, SystemMessage } from '@langchain/core/messages';
import { OPENAI_API_KEY } from '../config/index.js';

/* ================================================================================
   UNIFIED AGENT - Supports Both Buyer and Seller
   ================================================================================ */

/* -------------------- BUYER SESSION MANAGER -------------------- */

class UnifiedSessionManager {
  constructor(redis) {
    this.redis = redis;
    this.TTL = 7200;
  }

  async createSession(buyerId, accessToken) {
    const sessionId = `session_${buyerId}_${Date.now()}_${uuidv4().slice(0, 8)}`;
    
    const session = {
      sessionId,
      buyerId,
      accessToken,
      phase: 'conversation',
      created_at: Date.now(),
      updated_at: Date.now(),
    };

    await this.saveSession(sessionId, session);
    console.log(`[BuyerSession] Created new session: ${sessionId}`);
    
    return session;
  }

  async saveSession(sessionId, data) {
    const key = `unified:${sessionId}`;
    await this.redis.setEx(key, this.TTL, JSON.stringify({
      ...data,
      updated_at: Date.now(),
    }));
  }

  async getSession(sessionId) {
    const key = `unified:${sessionId}`;
    const data = await this.redis.get(key);
    return data ? JSON.parse(data) : null;
  }

  async updatePhase(sessionId, phase) {
    const session = await this.getSession(sessionId);
    if (session) {
      session.phase = phase;
      await this.saveSession(sessionId, session);
    }
  }

  async setJob(sessionId, job) {
    const session = await this.getSession(sessionId);
    if (session) {
      session.job = job;
      await this.saveSession(sessionId, session);
    }
  }

  async setDeals(sessionId, deals) {
    const session = await this.getSession(sessionId);
    if (session) {
      session.deals = deals;
      await this.saveSession(sessionId, session);
    }
  }

  async cleanup(sessionId) {
    const key = `unified:${sessionId}`;
    await this.redis.del(key);
    await conversationStore.cleanup(sessionId);
    console.log(`[BuyerSession] Cleaned up session: ${sessionId}`);
  }
}

export const buyerSessionManager = new UnifiedSessionManager(redisClient);

/* -------------------- SELLER SESSION MANAGER -------------------- */

class SellerSessionManager {
  constructor(redis) {
    this.redis = redis;
    this.TTL = 7200;
  }

  async createSession(sellerId, accessToken) {
    const sessionId = `seller_session_${sellerId}_${Date.now()}_${uuidv4().slice(0, 8)}`;
    
    const session = {
      sessionId,
      sellerId,
      accessToken,
      phase: 'profile_check', // profile_check | profile_creation | job_browsing | bidding
      created_at: Date.now(),
      updated_at: Date.now(),
    };

    await this.saveSession(sessionId, session);
    console.log(`[SellerSession] Created new session: ${sessionId}`);
    
    return session;
  }

  async saveSession(sessionId, data) {
    const key = `seller_unified:${sessionId}`;
    await this.redis.setEx(key, this.TTL, JSON.stringify({
      ...data,
      updated_at: Date.now(),
    }));
  }

  async getSession(sessionId) {
    const key = `seller_unified:${sessionId}`;
    const data = await this.redis.get(key);
    return data ? JSON.parse(data) : null;
  }

  async updatePhase(sessionId, phase) {
    const session = await this.getSession(sessionId);
    if (session) {
      session.phase = phase;
      await this.saveSession(sessionId, session);
    }
  }

  async setProfile(sessionId, profile) {
    const session = await this.getSession(sessionId);
    if (session) {
      session.profile = profile;
      await this.saveSession(sessionId, session);
    }
  }

  async setMatchedJobs(sessionId, jobs) {
    const session = await this.getSession(sessionId);
    if (session) {
      session.matchedJobs = jobs;
      await this.saveSession(sessionId, session);
    }
  }

  async cleanup(sessionId) {
    const key = `seller_unified:${sessionId}`;
    await this.redis.del(key);
    await sellerProfileStore.cleanup(sessionId);
    console.log(`[SellerSession] Cleaned up session: ${sessionId}`);
  }
}

export const sellerSessionManager = new SellerSessionManager(redisClient);

/* -------------------- MAIN ROUTER -------------------- */

export async function handleAgentChat(input, send) {
  const { userType } = input; // 'buyer' or 'seller'

  if (userType === 'seller') {
    return handleSellerAgent(input, send);
  } else {
    return handleBuyerAgent(input, send);
  }
}

/* ================================================================================
   BUYER AGENT (EXISTING CODE)
   ================================================================================ */

async function handleBuyerAgent(input, send) {
  const { buyerId, accessToken, message } = input;
  let { sessionId } = input;

  if (!buyerId || !accessToken) {
    send({ type: 'error', error: 'Missing buyerId or accessToken' });
    return;
  }

  if (!message || typeof message !== 'string') {
    send({ type: 'error', error: 'Message is required' });
    return;
  }

  try {
    let session;
    
    if (sessionId) {
      session = await buyerSessionManager.getSession(sessionId);
      if (!session) {
        console.log(`[BuyerAgent] Session ${sessionId} not found, creating new`);
        session = await buyerSessionManager.createSession(buyerId, accessToken);
        sessionId = session.sessionId;
      }
    } else {
      session = await buyerSessionManager.createSession(buyerId, accessToken);
      sessionId = session.sessionId;
    }

    send({ 
      type: 'session', 
      sessionId: session.sessionId,
      phase: session.phase,
      userType: 'buyer',
    });

    serviceCategoryManager.getCategoriesOrFetch(buyerId, accessToken).catch(console.error);

    // Use LLM-based intent detection
    const intent = await intelligentBuyerIntentCheck(message, session);
    console.log(`[BuyerAgent] Phase: ${session.phase}, Intent: ${intent}`);

    switch (intent) {
      case 'restart':
        await handleBuyerRestart(session, send);
        break;
      case 'confirm_and_proceed':
        await handleConfirmAndProceed(session, message, send);
        break;
      case 'modify_before_confirm':
        await handleModifyBeforeConfirm(session, message, send);
        break;
      case 'select_provider':
        await handleProviderSelection(session, message, send);
        break;
      case 'filter_results':
        await handleFilterResults(session, message, send);
        break;
      case 'continue_conversation':
        await handleConversation(session, message, send);
        break;
      case 'refinement_question':
        await handleRefinement(session, message, send);
        break;
      case 'ask_question':
        await handleGeneralQuestion(session, message, send);
        break;
      default:
        await handleConversation(session, message, send);
    }

  } catch (error) {
    console.error('[BuyerAgent] Error:', error);
    send({ type: 'error', error: error.message || 'An unexpected error occurred' });
  }
}

/* -------------------- BUYER INTENT DETECTION -------------------- */

async function intelligentBuyerIntentCheck(message, session) {
  const llm = new ChatOpenAI({
    model: 'gpt-4o-mini',
    temperature: 0,
    openAIApiKey: OPENAI_API_KEY,
  });

  const { phase, job, deals } = session;
  const hasDeals = deals && deals.length > 0;
  
  const conversationHistory = await conversationStore.getMessages(session.sessionId);
  const recentMessages = conversationHistory
    .slice(-5)
    .map(m => `${m.role.toUpperCase()}: ${m.content}`)
    .join('\n');

  const jobContext = job ? `
Current Job:
- Title: ${job.title}
- Service: ${job.service_category_id}
- Budget: $${job.budget.min}-$${job.budget.max}
- Start: ${job.startDate}
- End: ${job.endDate}
- Location: ${job.location || 'Not specified'}
` : 'No job created yet.';

  const dealsContext = hasDeals ? `
Available Providers: ${deals.length} providers found
Top provider: ${deals[0].sellerName} at $${deals[0].quote.price}
` : 'No providers found yet.';

  const prompt = `
You are an intent classifier for a conversational service marketplace agent (BUYER side).

Current Phase: ${phase}
(Phases: conversation = collecting job info, confirmation = reviewing job before search, negotiation = finding providers, refinement = discussing results)

Recent Conversation:
${recentMessages || 'No previous messages'}

${jobContext}

${dealsContext}

User's Current Message: "${message}"

Classify the user's intent into ONE of these categories:

1. **restart** - User wants to start over, cancel, or create a new/different job
2. **confirm_and_proceed** - User explicitly confirms to proceed (only in confirmation phase)
3. **modify_before_confirm** - User wants to change/add details before confirming (in confirmation phase)
4. **continue_conversation** - User is providing job information or answering questions (in conversation phase)
5. **refinement_question** - User is asking about job/provider details (in refinement phase)
6. **select_provider** - User wants to select/book a specific provider (in refinement phase)
7. **filter_results** - User wants to filter/sort providers (in refinement phase)
8. **ask_question** - User is asking a general question not related to current phase

Reply ONLY with JSON:
{
  "intent": "<one of the 8 intents above>",
  "confidence": "<high|medium|low>",
  "reasoning": "<brief explanation>"
}
`;

  try {
    const res = await llm.invoke([
      new SystemMessage('Only output valid JSON. Be accurate in intent classification.'),
      new HumanMessage(prompt),
    ]);

    let content = res.content.trim();
    content = content.replace(/```json\n?/g, '').replace(/```\n?/g, '');
    const result = JSON.parse(content);

    console.log(`[BuyerIntent] Detected: ${result.intent} (${result.confidence}) - ${result.reasoning}`);
    
    return result.intent;
  } catch (error) {
    console.error('[BuyerIntent] LLM classification error:', error.message);
    
    if (phase === 'conversation') return 'continue_conversation';
    if (phase === 'confirmation') return 'confirm_and_proceed';
    if (phase === 'refinement') return 'refinement_question';
    
    return 'continue_conversation';
  }
}

/* -------------------- BUYER HANDLERS (EXISTING) -------------------- */

async function handleConversation(session, message, send) {
  const { sessionId, buyerId, accessToken } = session;

  send({ type: 'phase', phase: 'conversation' });

  const result = await runConversation({
    sessionId,
    buyerId,
    accessToken,
    message,
  });

  send({ 
    type: 'message', 
    text: result.response,
    action: result.action,
  });

  send({ 
    type: 'collected', 
    data: result.collected,
    requiredMissing: result.requiredMissing,
    optionalMissing: result.optionalMissing,
    jobReadiness: result.jobReadiness,
  });

  if (result.phase === 'confirmation') {
    console.log(`[BuyerAgent] Job ready for confirmation`);
    
    await buyerSessionManager.updatePhase(sessionId, 'confirmation');
    
    await conversationStore.appendMessage(sessionId, {
      role: 'system',
      content: `Job preview ready: ${JSON.stringify(result.collected)}`
    });
    
    send({ 
      type: 'phase_transition', 
      from: 'conversation',
      to: 'confirmation',
      jobPreview: result.collected,
    });

    send({ type: 'phase', phase: 'confirmation' });
  }
  
  if (result.phase === 'complete' && result.job) {
    console.log(`[BuyerAgent] Job confirmed, starting negotiation for job ${result.job.id}`);
    
    await buyerSessionManager.setJob(sessionId, result.job);
    await buyerSessionManager.updatePhase(sessionId, 'negotiation');

    await conversationStore.appendMessage(sessionId, {
      role: 'system',
      content: `Job created: ${result.job.title} (ID: ${result.job.id})`
    });

    send({ 
      type: 'phase_transition', 
      from: 'confirmation',
      to: 'negotiation',
      job: result.job,
    });

    send({ 
      type: 'message', 
      text: `Perfect! Now searching for the best ${result.collected.service_category_name} providers in your area...`,
    });

    await handleNegotiation(session, result.job, send);
  }
}

async function handleConfirmAndProceed(session, message, send) {
  const { sessionId, buyerId, accessToken } = session;

  console.log('[BuyerAgent] User confirmed, building job and proceeding to negotiation');

  send({ type: 'phase', phase: 'confirmation' });
  
  const result = await runConversation({
    sessionId,
    buyerId,
    accessToken,
    message: message,
  });

  if (result.phase === 'complete' && result.job) {
    await buyerSessionManager.setJob(sessionId, result.job);
    await buyerSessionManager.updatePhase(sessionId, 'negotiation');

    send({ 
      type: 'phase_transition', 
      from: 'confirmation',
      to: 'negotiation',
      job: result.job,
    });

    send({ 
      type: 'message', 
      text: `Excellent! Searching for the best ${result.collected.service_category_name} providers for you now...`,
    });

    await handleNegotiation(session, result.job, send);
  } else {
    send({
      type: 'message',
      text: result.response || "Let me know when you're ready to proceed!",
    });
  }
}

async function handleModifyBeforeConfirm(session, message, send) {
  const { sessionId, buyerId, accessToken } = session;

  console.log('[BuyerAgent] User wants to modify details before confirming');

  await buyerSessionManager.updatePhase(sessionId, 'conversation');

  send({ 
    type: 'phase_transition', 
    from: 'confirmation',
    to: 'conversation',
  });

  send({ type: 'phase', phase: 'conversation' });

  const result = await runConversation({
    sessionId,
    buyerId,
    accessToken,
    message,
  });

  send({ 
    type: 'message', 
    text: result.response,
    action: result.action,
  });

  send({ 
    type: 'collected', 
    data: result.collected,
    requiredMissing: result.requiredMissing,
    optionalMissing: result.optionalMissing,
    jobReadiness: result.jobReadiness,
  });

  if (result.phase === 'confirmation') {
    await buyerSessionManager.updatePhase(sessionId, 'confirmation');
    
    send({ 
      type: 'phase_transition', 
      from: 'conversation',
      to: 'confirmation',
      jobPreview: result.collected,
    });

    send({ type: 'phase', phase: 'confirmation' });
  }
}

async function handleNegotiation(session, job, send) {
  const { sessionId, buyerId, accessToken } = session;

  send({ type: 'phase', phase: 'negotiation' });

  await buyerSessionManager.setJob(sessionId, job);

  const result = await runNegotiationAndMatchStream(
    job,
    accessToken,
    {
      buyerId,
      useMem0: true,
      providerLimit: 10,
      maxRounds: 1,
    },
    send
  );

  if (result?.deals) {
    await buyerSessionManager.setDeals(sessionId, result.deals);
  }

  await buyerSessionManager.updatePhase(sessionId, 'refinement');

  send({ 
    type: 'phase_transition', 
    from: 'negotiation',
    to: 'refinement',
  });

  if (result?.deals && result.deals.length > 0) {
    const topDeal = result.deals[0];
    
    await conversationStore.appendMessage(sessionId, {
      role: 'system',
      content: `Search completed. Found ${result.deals.length} providers for ${job.title}. Top match: ${topDeal.sellerName} at $${topDeal.quote.price}.`
    });
    
    send({ 
      type: 'message', 
      text: `Great news! I found ${result.deals.length} providers for your ${job.title} job. The best match is ${topDeal.sellerName} at $${topDeal.quote.price} (${topDeal.quote.days} days). Would you like to book them, or see more details?`,
    });
  } else {
    send({ 
      type: 'message', 
      text: "I couldn't find any providers matching your requirements right now. Would you like to adjust your criteria and try again?",
    });
  }

  send({ type: 'phase', phase: 'refinement' });
}

async function handleRefinement(session, message, send) {
  const { deals, job, sessionId } = session;

  send({ type: 'phase', phase: 'refinement' });

  const conversationHistory = await conversationStore.getMessages(sessionId);
  
  const llm = new ChatOpenAI({
    model: 'gpt-4o-mini',
    temperature: 0.7,
    openAIApiKey: OPENAI_API_KEY,
  });

  const recentMessages = conversationHistory
    .slice(-10)
    .map(m => `${m.role.toUpperCase()}: ${m.content}`)
    .join('\n');

  const jobContext = job ? `
Job Details:
- Title: ${job.title}
- Service Category: ${job.service_category_id}
- Budget: $${job.budget.min}-$${job.budget.max}
- Start Date: ${job.startDate}
- End Date: ${job.endDate}
- Location: ${job.location || 'Not specified'}
- Description: ${job.description || 'No detailed description'}
` : 'No job details available.';

  const dealsContext = deals && deals.length > 0 ? `
Available Providers (${deals.length}):
${deals.map((d, i) => `
${i + 1}. ${d.sellerName}
   - Price: $${d.quote.price}
   - Completion: ${d.quote.days} days
   - Payment: ${d.quote.paymentSchedule}
   - Licensed: ${d.quote.licensed ? 'Yes' : 'No'}
   - References: ${d.quote.referencesAvailable ? 'Yes' : 'No'}
   - Can Meet Dates: ${d.quote.can_meet_dates !== false ? 'Yes' : 'No'}
   - Match Score: ${d.matchScore}/100
   ${d.negotiationMessage ? '   - Provider Message: ' + d.negotiationMessage : ''}
`).join('\n')}
` : 'No providers found yet.';

  const prompt = `
You are a helpful assistant in the refinement phase after finding service providers.

Conversation History:
${recentMessages}

${jobContext}

${dealsContext}

User's Question: "${message}"

Instructions:
- Answer the user's question naturally and conversationally using the context above
- If they ask about the job (title, budget, dates, location, description), use the job details
- If they ask about providers (prices, timelines, qualifications), use the provider details
- If they want to compare providers, present the comparison clearly
- Be specific and use actual numbers/names from the context
- Keep responses friendly and conversational (2-4 sentences)
- Don't repeat what was already said unless specifically asked
- If information is not available, acknowledge it honestly

Reply ONLY with JSON:
{
  "message": "<your natural, helpful response>",
  "intent": "<info_request|provider_selection|filter_request|new_search|comparison|other>",
  "requires_action": true/false
}
`;

  try {
    const res = await llm.invoke([
      new SystemMessage('Only output valid JSON. Be conversational and helpful.'),
      new HumanMessage(prompt),
    ]);

    let content = res.content.trim();
    content = content.replace(/```json\n?/g, '').replace(/```\n?/g, '');
    const response = JSON.parse(content);

    console.log(`[BuyerRefinement] Response intent: ${response.intent}`);

    send({
      type: 'message',
      text: response.message,
    });

    await conversationStore.appendMessage(sessionId, { 
      role: 'user', 
      content: message 
    });
    await conversationStore.appendMessage(sessionId, { 
      role: 'assistant', 
      content: response.message 
    });

    if (response.intent === 'info_request') {
      const lowerMessage = message.toLowerCase();
      
      if (lowerMessage.includes('all providers') || 
          lowerMessage.includes('all details') || 
          lowerMessage.includes('show all') ||
          lowerMessage.includes('complete details')) {
        send({
          type: 'deals_detail',
          deals: deals?.map(d => ({
            id: d.id,
            name: d.sellerName,
            price: d.quote.price,
            days: d.quote.days,
            paymentSchedule: d.quote.paymentSchedule,
            licensed: d.quote.licensed,
            referencesAvailable: d.quote.referencesAvailable,
            can_meet_dates: d.quote.can_meet_dates,
            matchScore: d.matchScore,
            message: d.negotiationMessage,
          })) || [],
        });
      }
    }

  } catch (error) {
    console.error('[BuyerRefinement] Error:', error.message);
    
    const fallbackResponse = job 
      ? `Your job "${job.title}" has a budget of $${job.budget.min}-$${job.budget.max} and is scheduled to start ${job.startDate}. I found ${deals?.length || 0} providers. What would you like to know?`
      : "I'm here to help! You can ask about the job details, providers, or start a new search.";
    
    send({
      type: 'message',
      text: fallbackResponse,
    });
  }
}

async function handleGeneralQuestion(session, message, send) {
  const llm = new ChatOpenAI({
    model: 'gpt-4o-mini',
    temperature: 0.7,
    openAIApiKey: OPENAI_API_KEY,
  });

  const prompt = `
You are a helpful assistant for a service marketplace platform (buyer side).

User's Question: "${message}"

This is a general question about how the platform works, not about a specific job or provider.

Provide a helpful, friendly answer. Keep it brief (2-3 sentences).

Reply ONLY with JSON:
{
  "message": "<your helpful response>"
}
`;

  try {
    const res = await llm.invoke([
      new SystemMessage('Only output valid JSON.'),
      new HumanMessage(prompt),
    ]);

    let content = res.content.trim();
    content = content.replace(/```json\n?/g, '').replace(/```\n?/g, '');
    const response = JSON.parse(content);

    send({
      type: 'message',
      text: response.message,
    });

    await conversationStore.appendMessage(session.sessionId, { 
      role: 'user', 
      content: message 
    });
    await conversationStore.appendMessage(session.sessionId, { 
      role: 'assistant', 
      content: response.message 
    });

  } catch (error) {
    console.error('[BuyerGeneralQuestion] Error:', error.message);
    send({
      type: 'message',
      text: "I'm here to help you find service providers! You can describe what service you need, and I'll guide you through the process.",
    });
  }
}

async function handleBuyerRestart(session, send) {
  const { sessionId, buyerId, accessToken } = session;

  await conversationStore.cleanup(sessionId);

  const newSession = {
    sessionId,
    buyerId,
    accessToken,
    phase: 'conversation',
    created_at: session.created_at,
    updated_at: Date.now(),
  };
  
  await buyerSessionManager.saveSession(sessionId, newSession);

  send({ type: 'phase', phase: 'conversation' });
  send({ 
    type: 'message', 
    text: "No problem, let's start fresh! What kind of service are you looking for today?",
  });
  send({ 
    type: 'collected', 
    data: {
      service_category_id: null,
      service_category_name: null,
      title: null,
      description: null,
      budget: { min: null, max: null },
      startDate: null,
      endDate: null,
      priorities: [],
      location: null,
    },
    requiredMissing: ['service_category_id', 'budget_max', 'start_date', 'location'],
    optionalMissing: ['title', 'description', 'budget_min', 'end_date'],
    jobReadiness: 'incomplete',
  });
}

async function handleProviderSelection(session, message, send) {
  const { sessionId, buyerId, deals, job } = session;

  if (!deals || deals.length === 0) {
    send({
      type: 'message',
      text: "I don't have any providers to select from. Would you like to search for providers first?",
    });
    return;
  }

  const lowerMessage = message.toLowerCase();
  let selectedDeal = null;

  if (lowerMessage.includes('first') || lowerMessage.includes('best') || lowerMessage.includes('top')) {
    selectedDeal = deals[0];
  } else {
    for (const deal of deals) {
      if (deal.sellerName && lowerMessage.includes(deal.sellerName.toLowerCase())) {
        selectedDeal = deal;
        break;
      }
    }
  }
  
  if (!selectedDeal) {
    const numberMatch = message.match(/\d+/);
    if (numberMatch) {
      const index = parseInt(numberMatch[0]) - 1;
      if (index >= 0 && index < deals.length) {
        selectedDeal = deals[index];
      }
    }
  }

  if (selectedDeal) {
    if (job?.id) {
      await updateNegotiationOutcome(buyerId, job.id, selectedDeal.sellerId, 'accepted');
    }

    send({
      type: 'provider_selected',
      deal: {
        id: selectedDeal.id,
        sellerId: selectedDeal.sellerId,
        sellerName: selectedDeal.sellerName,
        price: selectedDeal.quote.price,
        days: selectedDeal.quote.days,
        paymentSchedule: selectedDeal.quote.paymentSchedule,
      },
    });

    send({
      type: 'message',
      text: `Excellent choice! I've selected ${selectedDeal.sellerName} for $${selectedDeal.quote.price}. They'll complete the job in ${selectedDeal.quote.days} day(s). You can now proceed to confirm the booking!`,
    });

    await buyerSessionManager.updatePhase(sessionId, 'complete');
    send({ type: 'phase', phase: 'complete' });
  } else {
    const providerList = deals.map((d, i) => `${i + 1}. ${d.sellerName} - $${d.quote.price}`).join('\n');
    send({
      type: 'message',
      text: `Which provider would you like to select?\n\n${providerList}\n\nJust say "select provider 1" or mention their name!`,
    });
  }
}

async function handleFilterResults(session, message, send) {
  const { deals } = session;

  if (!deals || deals.length === 0) {
    send({
      type: 'message',
      text: "I don't have any results to filter. Would you like to search for providers?",
    });
    return;
  }

  const lowerMessage = message.toLowerCase();
  let filteredDeals = [...deals];

  if (lowerMessage.includes('licensed')) {
    filteredDeals = filteredDeals.filter(d => d.quote.licensed === true);
  }
  if (lowerMessage.includes('reference')) {
    filteredDeals = filteredDeals.filter(d => d.quote.referencesAvailable === true);
  }
  if (lowerMessage.includes('cheaper') || lowerMessage.includes('lowest price')) {
    filteredDeals = filteredDeals.sort((a, b) => a.quote.price - b.quote.price);
  }
  if (lowerMessage.includes('highest rated') || lowerMessage.includes('best rated')) {
    filteredDeals = filteredDeals.sort((a, b) => b.matchScore - a.matchScore);
  }
  if (lowerMessage.includes('fastest') || lowerMessage.includes('quickest')) {
    filteredDeals = filteredDeals.sort((a, b) => a.quote.days - b.quote.days);
  }

  if (filteredDeals.length === 0) {
    send({
      type: 'message',
      text: "No providers match that filter. Would you like to try a different filter or see all results?",
    });
  } else {
    send({
      type: 'filtered_deals',
      deals: filteredDeals.map(d => ({
        id: d.id,
        name: d.sellerName,
        price: d.quote.price,
        days: d.quote.days,
        licensed: d.quote.licensed,
        referencesAvailable: d.quote.referencesAvailable,
        matchScore: d.matchScore,
      })),
      count: filteredDeals.length,
    });
    
    send({
      type: 'message',
      text: `Found ${filteredDeals.length} provider(s) matching your criteria. The top option is ${filteredDeals[0].sellerName} at $${filteredDeals[0].quote.price}. Would you like to select one?`,
    });
  }
}

/* ================================================================================
   SELLER AGENT (NEW)
   ================================================================================ */

async function handleSellerAgent(input, send) {
  const { sellerId, accessToken, message } = input;
  let { sessionId } = input;

  if (!sellerId || !accessToken) {
    send({ type: 'error', error: 'Missing sellerId or accessToken' });
    return;
  }

  if (!message || typeof message !== 'string') {
    send({ type: 'error', error: 'Message is required' });
    return;
  }

  try {
    let session;
    
    if (sessionId) {
      session = await sellerSessionManager.getSession(sessionId);
      if (!session) {
        console.log(`[SellerAgent] Session ${sessionId} not found, creating new`);
        session = await sellerSessionManager.createSession(sellerId, accessToken);
        sessionId = session.sessionId;
      }
    } else {
      session = await sellerSessionManager.createSession(sellerId, accessToken);
      sessionId = session.sessionId;
    }

    send({ 
      type: 'session', 
      sessionId: session.sessionId,
      phase: session.phase,
      userType: 'seller',
    });

    // Check if profile exists
    const hasProfile = await SellerProfile.findOne({
      where: { seller_id: sellerId, active: true }
    });

    if (!hasProfile && session.phase === 'profile_check') {
      send({
        type: 'message',
        text: "Welcome! Let's create your service provider profile so clients can find you. What services do you offer?",
      });
      
      await sellerSessionManager.updatePhase(sessionId, 'profile_creation');
      session.phase = 'profile_creation';
      
      send({ type: 'phase', phase: 'profile_creation' });
    }

    // Route based on phase and intent
    const intent = await intelligentSellerIntent(message, session);
    console.log(`[SellerAgent] Phase: ${session.phase}, Intent: ${intent}`);

    switch (intent) {
      case 'create_profile':
      case 'provide_profile_info':
        await handleSellerProfileCreation(session, message, send);
        break;
      case 'browse_jobs':
        await handleJobBrowsing(session, message, send);
        break;
      case 'bid_on_job':
        await handleBidding(session, message, send);
        break;
      case 'check_dashboard':
        await handleDashboard(session, send);
        break;
      case 'ask_question':
        await handleSellerQuestion(session, message, send);
        break;
      case 'restart':
        await handleSellerRestart(session, send);
        break;
      default:
        await handleSellerProfileCreation(session, message, send);
    }

  } catch (error) {
    console.error('[SellerAgent] Error:', error);
    send({ type: 'error', error: error.message || 'An unexpected error occurred' });
  }
}

/* -------------------- SELLER INTENT DETECTION -------------------- */
/**
 * Use LLM to understand which job the user wants to bid on
 */
async function intelligentJobSelection(message, matchedJobs, session) {
  const llm = new ChatOpenAI({
    model: 'gpt-4o-mini',
    temperature: 0,
    openAIApiKey: OPENAI_API_KEY,
  });

  // Get recent conversation for context
  const conversationHistory = await sellerProfileStore.getMessages(session.sessionId);
  const recentMessages = conversationHistory
    .slice(-3)
    .map(m => `${m.role.toUpperCase()}: ${m.content}`)
    .join('\n');

  const jobListForLLM = matchedJobs.map((j, i) => `
Job ${i + 1}:
- ID: ${j.job_id}
- Title: "${j.title}"
- Description: ${j.description || 'No description'}
- Budget: $${j.budget.min}-$${j.budget.max}
- Location: ${j.location?.address || 'Not specified'}
- Start Date: ${j.start_date}
- Match Score: ${j.matchScore}/100
`).join('\n');

  const prompt = `
You are helping a service provider select which job they want to bid on.

Recent Conversation:
${recentMessages || 'No previous messages'}

Available Jobs:
${jobListForLLM}

User's Message: "${message}"

Task: Determine which job (if any) the user wants to bid on.

Rules:
1. If user says "yes", "sure", "okay", "go ahead" without specifying which job, AND there was a specific job mentioned in the recent conversation, select that job
2. If user says "first", "top", "best", "1st", "1" → Select Job 1
3. If user says "second", "2nd", "2" → Select Job 2
4. If user mentions part of a job title (e.g., "the cleaning one", "kitchen cleaner") → Match to that job
5. If user asks for more details about a job → DO NOT select, return null
6. If user's intent is unclear → return null

Reply ONLY with JSON:
{
  "job_selected": true/false,
  "job_index": <0-based index number or null>,
  "job_id": "<job_id or null>",
  "confidence": "high/medium/low",
  "reasoning": "<brief explanation of why this job was selected>"
}
`;

  try {
    const res = await llm.invoke([
      new SystemMessage('Only output valid JSON. Be accurate in job matching.'),
      new HumanMessage(prompt),
    ]);

    let content = res.content.trim();
    content = content.replace(/```json\n?/g, '').replace(/```\n?/g, '');
    const result = JSON.parse(content);

    console.log(`[JobSelection] Selected: ${result.job_selected}, Job: ${result.job_id}, Confidence: ${result.confidence}`);
    console.log(`[JobSelection] Reasoning: ${result.reasoning}`);

    if (result.job_selected && result.job_index !== null) {
      return matchedJobs[result.job_index] || null;
    }

    if (result.job_selected && result.job_id) {
      return matchedJobs.find(j => j.job_id === result.job_id) || null;
    }

    return null;
  } catch (error) {
    console.error('[JobSelection] LLM error:', error.message);
    
    // Fallback: Check for very obvious patterns
    const lowerMessage = message.toLowerCase();
    if ((lowerMessage.includes('yes') || lowerMessage.includes('first') || lowerMessage === '1') && matchedJobs.length > 0) {
      console.log('[JobSelection] Fallback: Selecting first job');
      return matchedJobs[0];
    }
    
    return null;
  }
}

/**
 * Use LLM to extract custom bidding details (price, message)
 */
async function intelligentBiddingDetails(message, selectedJob, session) {
  const llm = new ChatOpenAI({
    model: 'gpt-4o-mini',
    temperature: 0,
    openAIApiKey: OPENAI_API_KEY,
  });

  const prompt = `
You are helping a service provider customize their bid for a job.

Job Details:
- Title: "${selectedJob.title}"
- Budget: $${selectedJob.budget.min}-$${selectedJob.budget.max}

User's Message: "${message}"

Task: Extract any custom pricing or special message the user wants to include in their bid.

Rules:
1. If user mentions a specific price (e.g., "I want to quote $175", "bid $200"), extract it
2. If user says they want to include a message (e.g., "tell them I have 10 years experience"), extract it
3. If user just says "yes", "bid on this", "go ahead" → No custom details
4. Price should be a number, not a range

Examples:
- "Yes, bid on this" → custom_price: null, custom_message: null
- "I want to quote $850" → custom_price: 850, custom_message: null
- "Bid $175 and tell them I'm available immediately" → custom_price: 175, custom_message: "I'm available immediately"
- "Quote $200" → custom_price: 200, custom_message: null

Reply ONLY with JSON:
{
  "has_custom_details": true/false,
  "custom_price": <number or null>,
  "custom_message": "<string or null>",
  "reasoning": "<brief explanation>"
}
`;

  try {
    const res = await llm.invoke([
      new SystemMessage('Only output valid JSON. Extract pricing and messages accurately.'),
      new HumanMessage(prompt),
    ]);

    let content = res.content.trim();
    content = content.replace(/```json\n?/g, '').replace(/```\n?/g, '');
    const result = JSON.parse(content);

    console.log(`[BiddingDetails] Custom Price: ${result.custom_price}, Custom Message: ${result.custom_message}`);
    console.log(`[BiddingDetails] Reasoning: ${result.reasoning}`);

    return {
      customPrice: result.custom_price,
      customMessage: result.custom_message,
    };
  } catch (error) {
    console.error('[BiddingDetails] LLM error:', error.message);
    return {
      customPrice: null,
      customMessage: null,
    };
  }
}
async function intelligentSellerIntent(message, session) {
  const llm = new ChatOpenAI({
    model: 'gpt-4o-mini',
    temperature: 0,
    openAIApiKey: OPENAI_API_KEY,
  });

  // Reload session to get latest data
  const freshSession = await sellerSessionManager.getSession(session.sessionId);
  const { phase, profile, matchedJobs } = freshSession || session;

  // Get conversation context
  const conversationHistory = await sellerProfileStore.getMessages(session.sessionId);
  const recentMessages = conversationHistory
    .slice(-5)
    .map(m => `${m.role.toUpperCase()}: ${m.content}`)
    .join('\n');

  const jobContext = matchedJobs && matchedJobs.length > 0 ? `
Available Jobs: ${matchedJobs.length} job(s)
Top Job: "${matchedJobs[0].title}" - Budget: $${matchedJobs[0].budget.min}-$${matchedJobs[0].budget.max}
` : 'No jobs available yet';

  const prompt = `
You are an intent classifier for a service provider (seller) agent.

Current Phase: ${phase}
(Phases: profile_check, profile_creation, job_browsing, bidding)

Recent Conversation:
${recentMessages || 'No previous messages'}

Has Profile: ${profile ? 'Yes' : 'No'}
${jobContext}

User's Message: "${message}"

Classify the intent into ONE of these:

1. **create_profile** - User wants to create their provider profile
2. **provide_profile_info** - User is providing profile details (services, pricing, availability)
3. **browse_jobs** - User wants to see available jobs or more job details
4. **bid_on_job** - User wants to bid on a job
   - Triggers: "yes" (after job offer), "sure", "okay", "bid on...", "I want to bid", mentions job number/title
   - Context: Should have jobs available
5. **check_dashboard** - User wants to see their bids/active jobs
6. **ask_question** - General question about how it works
7. **modify_profile** - User wants to change their profile
8. **restart** - User wants to start over

Classification Rules:
- If recent conversation shows a job was offered and user says "yes", "sure", "okay", "go ahead" → "bid_on_job"
- If user mentions job number (1st, first, second) or job title → "bid_on_job"
- If no jobs available yet → NEVER "bid_on_job"
- If user asks "tell me more about..." or "what is..." → "browse_jobs" (not bidding yet)
- If phase is "profile_creation" and user is answering questions → "provide_profile_info"

Reply ONLY with JSON:
{
  "intent": "<one of the intents above>",
  "confidence": "<high|medium|low>",
  "reasoning": "<brief explanation of why this intent was chosen>"
}
`;

  try {
    const res = await llm.invoke([
      new SystemMessage('Only output valid JSON. Consider conversation context carefully.'),
      new HumanMessage(prompt),
    ]);

    let content = res.content.trim();
    content = content.replace(/```json\n?/g, '').replace(/```\n?/g, '');
    const result = JSON.parse(content);

    console.log(`[SellerIntent] Detected: ${result.intent} (${result.confidence})`);
    console.log(`[SellerIntent] Reasoning: ${result.reasoning}`);
    
    return result.intent;
  } catch (error) {
    console.error('[SellerIntent] Error:', error.message);
    
    // Fallback logic
    if (phase === 'profile_creation') return 'provide_profile_info';
    if (phase === 'job_browsing' && matchedJobs?.length > 0) {
      const lower = message.toLowerCase();
      if (lower.includes('yes') || lower.includes('bid') || lower.includes('first')) {
        return 'bid_on_job';
      }
    }
    return 'browse_jobs';
  }
}
/* -------------------- SELLER HANDLERS -------------------- */

async function handleSellerProfileCreation(session, message, send) {
  const { sessionId, sellerId, accessToken } = session;

  send({ type: 'phase', phase: 'profile_creation' });

  const result = await runSellerProfileConversation({
    sessionId,
    sellerId,
    accessToken,
    message,
  });

  send({ 
    type: 'message', 
    text: result.response,
    action: result.action,
  });

  send({ 
    type: 'profile_collected', 
    data: result.collected,
    requiredMissing: result.requiredMissing,
    optionalMissing: result.optionalMissing,
    profileReadiness: result.profileReadiness,
  });

  if (result.phase === 'complete' && result.profile) {
    await sellerSessionManager.setProfile(sessionId, result.profile);
    await sellerSessionManager.updatePhase(sessionId, 'job_browsing');

    send({ 
      type: 'phase_transition', 
      from: 'profile_creation',
      to: 'job_browsing',
      profile: result.profile,
    });

    send({
      type: 'message',
      text: "Excellent! Your profile is now live. Let me find jobs that match your skills...",
    });

    await handleJobBrowsing(session, "show me jobs", send);
  }
}

async function handleJobBrowsing(session, message, send) {
  const { sessionId, sellerId } = session;

  send({ type: 'phase', phase: 'job_browsing' });

  const result = await findJobsForSeller(sellerId);

  await sellerSessionManager.setMatchedJobs(sessionId, result.jobs);

  if (result.jobs && result.jobs.length > 0) {
    const topJobs = result.jobs.slice(0, 5);
    
    send({
      type: 'matched_jobs',
      jobs: topJobs.map(j => ({
        job_id: j.job_id,
        title: j.title,
        budget: j.budget,
        location: j.location?.address,
        start_date: j.start_date,
        matchScore: j.matchScore,
      })),
      count: result.jobs.length,
    });

    const topJob = topJobs[0];
    
    // ✅ Store in conversation history for LLM context
    await sellerProfileStore.appendMessage(sessionId, {
      role: 'system',
      content: `Jobs found: ${result.jobs.length}. Top match: "${topJob.title}" (ID: ${topJob.job_id}) - Budget: $${topJob.budget.min}-$${topJob.budget.max}, Match Score: ${topJob.matchScore}/100`
    });
    
    send({
      type: 'message',
      text: `Great news! I found ${result.jobs.length} job(s) that match your profile. The best match is "${topJob.title}" with a budget of $${topJob.budget.min}-$${topJob.budget.max} (Match Score: ${topJob.matchScore}/100). Would you like to bid on this job?`,
    });
  } else {
    send({
      type: 'message',
      text: "I couldn't find any jobs matching your profile right now. I'll notify you when new jobs become available!",
    });
  }
}

async function handleBidding(session, message, send) {
  const { sellerId, matchedJobs, sessionId } = session;

  // Check if jobs are available
  if (!matchedJobs || matchedJobs.length === 0) {
    send({
      type: 'message',
      text: "Let me first find some jobs for you...",
    });
    await handleJobBrowsing(session, "show jobs", send);
    return;
  }

  // ✅ USE LLM TO UNDERSTAND WHICH JOB USER WANTS
  const selectedJob = await intelligentJobSelection(message, matchedJobs, session);

  if (!selectedJob) {
    // LLM couldn't determine which job - ask for clarification
    await sellerSessionManager.updatePhase(sessionId, 'job_browsing');
    
    const jobList = matchedJobs.slice(0, 5).map((j, i) => 
      `${i + 1}. "${j.title}" - Budget: $${j.budget.min}-$${j.budget.max} (Match: ${j.matchScore}/100)`
    ).join('\n');
    
    send({
      type: 'message',
      text: `I have ${matchedJobs.length} job(s) available:\n\n${jobList}\n\nWhich one would you like to bid on?`,
    });
    return;
  }

  // ✅ JOB SELECTED - CHECK IF USER WANTS CUSTOM PRICING
  const biddingDetails = await intelligentBiddingDetails(message, selectedJob, session);

  // Update phase
  await sellerSessionManager.updatePhase(sessionId, 'bidding');
  
  send({ type: 'phase', phase: 'bidding' });
  send({
    type: 'message',
    text: `Preparing your bid for "${selectedJob.title}"...`,
  });

  // Submit bid with custom details if provided
  const result = await submitSellerBid({
    sellerId,
    jobId: selectedJob.job_id,
    customMessage: biddingDetails.customMessage || null,
    customPrice: biddingDetails.customPrice || null,
  });

  if (result.bid) {
    send({
      type: 'bid_submitted',
      bid: {
        bid_id: result.bid.bid_id,
        job_id: result.bid.job_id,
        quoted_price: result.bid.quoted_price,
        quoted_timeline: result.bid.quoted_timeline,
        message: result.bid.message,
      }
    });

    send({
      type: 'message',
      text: `Perfect! I've submitted your bid for $${result.bid.quoted_price} with ${result.bid.quoted_completion_days} day(s) completion time. The buyer will review your bid and get back to you.`,
    });

    await sellerSessionManager.updatePhase(sessionId, 'job_browsing');
  } else {
    send({
      type: 'message',
      text: `Sorry, I couldn't submit the bid. ${result.error || 'Please try again.'}`,
    });
  }
}

async function handleDashboard(session, send) {
  const { sellerId } = session;

  const dashboard = await getSellerDashboard(sellerId);

  send({
    type: 'dashboard',
    data: {
      pending_bids: dashboard.stats.pending_bids,
      active_jobs: dashboard.stats.active_jobs,
      new_matches: dashboard.stats.new_matches,
    },
    activeBids: dashboard.activeBids.slice(0, 5),
    activeJobs: dashboard.activeJobs.slice(0, 5),
  });

  send({
    type: 'message',
    text: `Here's your dashboard: You have ${dashboard.stats.pending_bids} pending bid(s), ${dashboard.stats.active_jobs} active job(s), and ${dashboard.stats.new_matches} new job match(es). Would you like to see details?`,
  });
}

async function handleSellerQuestion(session, message, send) {
  const llm = new ChatOpenAI({
    model: 'gpt-4o-mini',
    temperature: 0.7,
    openAIApiKey: OPENAI_API_KEY,
  });

  const prompt = `
You are a helpful assistant for service providers on a marketplace platform.

User's Question: "${message}"

Provide a helpful answer about:
- How to create a profile
- How to find jobs
- How bidding works
- How payments work
- Tips for getting more jobs

Keep it brief (2-3 sentences).

Reply ONLY with JSON:
{
  "message": "<your helpful response>"
}
`;

  try {
    const res = await llm.invoke([
      new SystemMessage('Only output valid JSON.'),
      new HumanMessage(prompt),
    ]);

    let content = res.content.trim();
    content = content.replace(/```json\n?/g, '').replace(/```\n?/g, '');
    const response = JSON.parse(content);

    send({
      type: 'message',
      text: response.message,
    });
  } catch (error) {
    send({
      type: 'message',
      text: "I'm here to help you find jobs and grow your business! You can create your profile, browse jobs, and submit bids - all through this chat.",
    });
  }
}

async function handleSellerRestart(session, send) {
  const { sessionId, sellerId, accessToken } = session;

  await sellerProfileStore.cleanup(sessionId);

  const newSession = {
    sessionId,
    sellerId,
    accessToken,
    phase: 'profile_check',
    created_at: session.created_at,
    updated_at: Date.now(),
  };
  
  await sellerSessionManager.saveSession(sessionId, newSession);

  send({ type: 'phase', phase: 'profile_check' });
  send({ 
    type: 'message', 
    text: "No problem, let's start fresh! What would you like to do?",
  });
}

/* ================================================================================
   EXPORTS
   ================================================================================ */

export { 
  buyerSessionManager as sessionManager, 
  intelligentBuyerIntentCheck as intelligentIntentCheck,
  intelligentBuyerIntentCheck as quickIntentCheck
};