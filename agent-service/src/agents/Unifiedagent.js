import { v4 as uuidv4 } from 'uuid';
import { redisClient } from '../config/redis.js';
import { runConversation, conversationStore, serviceCategoryManager } from './conversationGraph.js';
import { runNegotiationAndMatchStream, updateNegotiationOutcome } from './negotiationOrchestrator.js';
import { ChatOpenAI } from '@langchain/openai';
import { HumanMessage, SystemMessage } from '@langchain/core/messages';
import { OPENAI_API_KEY } from '../config/index.js';

/* ================================================================================
   UNIFIED AGENT - Updated with LLM-Based Intent Detection
   ================================================================================ */

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
    console.log(`[Session] Created new session: ${sessionId}`);
    
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
    console.log(`[Session] Cleaned up session: ${sessionId}`);
  }
}

const sessionManager = new UnifiedSessionManager(redisClient);

/* -------------------- LLM-BASED INTENT ROUTER (NEW) -------------------- */

async function intelligentIntentCheck(message, session) {
  const llm = new ChatOpenAI({
    model: 'gpt-4o-mini',
    temperature: 0,
    openAIApiKey: OPENAI_API_KEY,
  });

  const { phase, job, deals } = session;
  const hasDeals = deals && deals.length > 0;
  
  // Get conversation history for context
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
You are an intent classifier for a conversational service marketplace agent.

Current Phase: ${phase}
(Phases: conversation = collecting job info, confirmation = reviewing job before search, negotiation = finding providers, refinement = discussing results)

Recent Conversation:
${recentMessages || 'No previous messages'}

${jobContext}

${dealsContext}

User's Current Message: "${message}"

Classify the user's intent into ONE of these categories:

1. **restart** - User wants to start over, cancel, or create a new/different job
   Examples: "start over", "new job", "cancel", "different service"

2. **confirm_and_proceed** - User explicitly confirms to proceed (only in confirmation phase)
   Examples: "yes", "proceed", "go ahead", "looks good", "find providers", "confirm"

3. **modify_before_confirm** - User wants to change/add details before confirming (in confirmation phase)
   Examples: "change budget", "add description", "modify", "update location"

4. **continue_conversation** - User is providing job information or answering questions (in conversation phase)
   Examples: "I need cleaning", "my budget is $500", "next week", answering any collection question

5. **refinement_question** - User is asking about job/provider details (in refinement phase)
   Examples: "what's the job title?", "tell me about providers", "how much?", "who is licensed?"

6. **select_provider** - User wants to select/book a specific provider (in refinement phase)
   Examples: "select first", "book Aayush", "choose provider 2", "go with them"

7. **filter_results** - User wants to filter/sort providers (in refinement phase)
   Examples: "show only licensed", "cheapest option", "highest rated", "fastest"

8. **ask_question** - User is asking a general question not related to current phase
   Examples: "how does this work?", "what services do you offer?"

Classification Rules:
- In "conversation" phase → mostly "continue_conversation" unless asking general questions
- In "confirmation" phase → "confirm_and_proceed" or "modify_before_confirm"
- In "refinement" phase → "refinement_question", "select_provider", or "filter_results"
- "restart" can happen in any phase
- Consider conversation context and user's natural language

Reply ONLY with JSON:
{
  "intent": "<one of the 8 intents above>",
  "confidence": "<high|medium|low>",
  "reasoning": "<brief explanation of why this intent was chosen>"
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

    console.log(`[Intent] Detected: ${result.intent} (${result.confidence}) - ${result.reasoning}`);
    
    return result.intent;
  } catch (error) {
    console.error('[Intent] LLM classification error:', error.message);
    
    // Fallback to simple phase-based routing
    if (phase === 'conversation') return 'continue_conversation';
    if (phase === 'confirmation') return 'confirm_and_proceed';
    if (phase === 'refinement') return 'refinement_question';
    
    return 'continue_conversation';
  }
}

/* -------------------- MAIN HANDLER (UPDATED) -------------------- */

export async function handleAgentChat(input, send) {
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
      session = await sessionManager.getSession(sessionId);
      if (!session) {
        console.log(`[Agent] Session ${sessionId} not found, creating new`);
        session = await sessionManager.createSession(buyerId, accessToken);
        sessionId = session.sessionId;
      }
    } else {
      session = await sessionManager.createSession(buyerId, accessToken);
      sessionId = session.sessionId;
    }

    send({ 
      type: 'session', 
      sessionId: session.sessionId,
      phase: session.phase,
    });

    serviceCategoryManager.getCategoriesOrFetch(buyerId, accessToken).catch(console.error);

    // Use LLM-based intent detection
    const intent = await intelligentIntentCheck(message, session);
    console.log(`[Agent] Phase: ${session.phase}, Intent: ${intent}`);

    switch (intent) {
      case 'restart':
        await handleRestart(session, send);
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
    console.error('[Agent] Error:', error);
    send({ type: 'error', error: error.message || 'An unexpected error occurred' });
  }
}

/* -------------------- CONVERSATION HANDLER (UPDATED) -------------------- */

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

  // Check if moved to confirmation phase
  if (result.phase === 'confirmation') {
    console.log(`[Agent] Job ready for confirmation`);
    
    await sessionManager.updatePhase(sessionId, 'confirmation');
    
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
  
  // Check if job is complete and confirmed
  if (result.phase === 'complete' && result.job) {
    console.log(`[Agent] Job confirmed, starting negotiation for job ${result.job.id}`);
    
    await sessionManager.setJob(sessionId, result.job);
    await sessionManager.updatePhase(sessionId, 'negotiation');

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

/* -------------------- CONFIRM AND PROCEED HANDLER -------------------- */

async function handleConfirmAndProceed(session, message, send) {
  const { sessionId, buyerId, accessToken } = session;

  console.log('[Agent] User confirmed, building job and proceeding to negotiation');

  send({ type: 'phase', phase: 'confirmation' });
  
  const result = await runConversation({
    sessionId,
    buyerId,
    accessToken,
    message: message,
  });

  if (result.phase === 'complete' && result.job) {
    await sessionManager.setJob(sessionId, result.job);
    await sessionManager.updatePhase(sessionId, 'negotiation');

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

/* -------------------- MODIFY BEFORE CONFIRM HANDLER -------------------- */

async function handleModifyBeforeConfirm(session, message, send) {
  const { sessionId, buyerId, accessToken } = session;

  console.log('[Agent] User wants to modify details before confirming');

  await sessionManager.updatePhase(sessionId, 'conversation');

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
    await sessionManager.updatePhase(sessionId, 'confirmation');
    
    send({ 
      type: 'phase_transition', 
      from: 'conversation',
      to: 'confirmation',
      jobPreview: result.collected,
    });

    send({ type: 'phase', phase: 'confirmation' });
  }
}

/* -------------------- NEGOTIATION HANDLER -------------------- */

async function handleNegotiation(session, job, send) {
  const { sessionId, buyerId, accessToken } = session;

  send({ type: 'phase', phase: 'negotiation' });

  await sessionManager.setJob(sessionId, job);

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
    await sessionManager.setDeals(sessionId, result.deals);
  }

  await sessionManager.updatePhase(sessionId, 'refinement');

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

/* -------------------- REFINEMENT HANDLER (WITH FULL CONTEXT) -------------------- */

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

    console.log(`[Refinement] Response intent: ${response.intent}`);

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

    // Handle additional actions based on intent
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
    console.error('[Refinement] Error:', error.message);
    
    const fallbackResponse = job 
      ? `Your job "${job.title}" has a budget of $${job.budget.min}-$${job.budget.max} and is scheduled to start ${job.startDate}. I found ${deals?.length || 0} providers. What would you like to know?`
      : "I'm here to help! You can ask about the job details, providers, or start a new search.";
    
    send({
      type: 'message',
      text: fallbackResponse,
    });
  }
}

/* -------------------- GENERAL QUESTION HANDLER (NEW) -------------------- */

async function handleGeneralQuestion(session, message, send) {
  const llm = new ChatOpenAI({
    model: 'gpt-4o-mini',
    temperature: 0.7,
    openAIApiKey: OPENAI_API_KEY,
  });

  const prompt = `
You are a helpful assistant for a service marketplace platform.

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
    console.error('[GeneralQuestion] Error:', error.message);
    send({
      type: 'message',
      text: "I'm here to help you find service providers! You can describe what service you need, and I'll guide you through the process.",
    });
  }
}

/* -------------------- RESTART HANDLER -------------------- */

async function handleRestart(session, send) {
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
  
  await sessionManager.saveSession(sessionId, newSession);

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

/* -------------------- PROVIDER SELECTION HANDLER -------------------- */

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

    await sessionManager.updatePhase(sessionId, 'complete');
    send({ type: 'phase', phase: 'complete' });
  } else {
    const providerList = deals.map((d, i) => `${i + 1}. ${d.sellerName} - $${d.quote.price}`).join('\n');
    send({
      type: 'message',
      text: `Which provider would you like to select?\n\n${providerList}\n\nJust say "select provider 1" or mention their name!`,
    });
  }
}

/* -------------------- FILTER RESULTS HANDLER -------------------- */

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

export { sessionManager, intelligentIntentCheck, intelligentIntentCheck as quickIntentCheck };