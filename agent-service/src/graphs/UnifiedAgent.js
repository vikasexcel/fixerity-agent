import { runConversation } from './buyer/conversationGraph.js';
import { runNegotiationAndMatchStream, updateNegotiationOutcome } from './buyer/negotiationOrchestrator.js';
import { runSellerProfileConversation } from './seller/sellerProfileGraph.js';
import { findJobsForSeller } from './seller/jobMatchingGraph.js';
import { submitSellerBid } from './seller/sellerBiddingGraph.js';
import { getSellerDashboard } from './seller/sellerOrchestrator.js';
import { invokeSellerAgent, resumeSellerAgent } from './seller/sellerAgentGraph.js';
import { sessionService, messageService } from '../services/index.js';
import { sessionRepository } from '../../prisma/repositories/sessionRepository.js';
import { prisma } from '../primsadb.js';
import { ChatOpenAI } from '@langchain/openai';
import { HumanMessage, SystemMessage } from '@langchain/core/messages';
import { OPENAI_API_KEY } from '../config/index.js';

/* UNIFIED AGENT - Supports Both Buyer and Seller (No Redis, Services-Only) */

async function getMessagesForSession(sessionId, limit = 50) {
  const history = await messageService.getConversationHistory(sessionId, { limit, includeSystem: true });
  return history.map(m => ({ role: m.role, content: m.content }));
}

async function appendMessage(sessionId, { role, content }) {
  if (role === 'user') await messageService.addUserMessage(sessionId, content);
  else if (role === 'assistant') await messageService.addAssistantMessage(sessionId, content);
  else if (role === 'system') await messageService.addSystemMessage(sessionId, content);
}

async function cleanupMessages(sessionId) {
  await messageService.clearHistory(sessionId);
}

function buildBuyerSessionFromDb(dbSession) {
  if (!dbSession) return null;
  const state = dbSession.state || {};
  return {
    sessionId: dbSession.id,
    buyerId: dbSession.userId,
    accessToken: dbSession.accessToken,
    phase: dbSession.phase,
    job: state.job ?? null,
    deals: state.deals ?? null,
    created_at: dbSession.createdAt?.getTime?.() ?? dbSession.createdAt,
    updated_at: dbSession.updatedAt?.getTime?.() ?? dbSession.updatedAt,
  };
}

function buildSellerSessionFromDb(dbSession) {
  if (!dbSession) return null;
  const state = dbSession.state || {};
  return {
    sessionId: dbSession.id,
    sellerId: dbSession.userId,
    accessToken: dbSession.accessToken,
    phase: dbSession.phase,
    profile: state.profile ?? null,
    matchedJobs: state.matchedJobs ?? null,
    created_at: dbSession.createdAt?.getTime?.() ?? dbSession.createdAt,
    updated_at: dbSession.updatedAt?.getTime?.() ?? dbSession.updatedAt,
  };
}

export const sessionManager = {
  async cleanup(sessionId) {
    await sessionRepository.markInactive(sessionId);
    await cleanupMessages(sessionId).catch(() => {});
    console.log(`[SessionManager] Cleaned up session: ${sessionId}`);
  },
};

export const sellerSessionManager = {
  async cleanup(sessionId) {
    await sessionRepository.markInactive(sessionId);
    await cleanupMessages(sessionId).catch(() => {});
    console.log(`[SellerSessionManager] Cleaned up session: ${sessionId}`);
  },
};

export async function handleAgentChat(input, send) {
  if (input.userType === 'seller') return handleSellerAgent(input, send);
  return handleBuyerAgent(input, send);
}

/* ---------- BUYER AGENT ---------- */

async function handleBuyerAgent(input, send) {
  const { buyerId, accessToken, message, forceNewSession } = input;
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
    let dbSession;
    if (sessionId) {
      dbSession = await sessionRepository.findById(sessionId);
      if (!dbSession || !dbSession.isActive) {
        const { session } = await sessionService.getOrCreateSession({ userId: buyerId, userType: 'buyer', accessToken, forceNew: !!forceNewSession });
        dbSession = session;
        sessionId = dbSession.id;
      } else {
        sessionId = dbSession.id;
      }
    } else {
      const { session } = await sessionService.getOrCreateSession({ userId: buyerId, userType: 'buyer', accessToken, forceNew: !!forceNewSession });
      dbSession = session;
      sessionId = dbSession.id;
    }
    const session = buildBuyerSessionFromDb(dbSession);
    send({ type: 'session', sessionId: session.sessionId, phase: session.phase, userType: 'buyer' });
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

async function intelligentBuyerIntentCheck(message, session) {
  const llm = new ChatOpenAI({ model: 'gpt-4o-mini', temperature: 0, openAIApiKey: OPENAI_API_KEY });
  const { phase, job, deals } = session;
  const hasDeals = deals && deals.length > 0;
  const conversationHistory = await getMessagesForSession(session.sessionId, 50);
  const recentMessages = conversationHistory.slice(-5).map(m => `${m.role.toUpperCase()}: ${m.content}`).join('\n');
  const jobContext = job ? `Current Job:\n- Title: ${job.title}\n- Service: ${job.service_category_id}\n- Budget: $${job.budget.min}-$${job.budget.max}\n- Start: ${job.startDate}\n- End: ${job.endDate}\n- Location: ${job.location || 'Not specified'}` : 'No job created yet.';
  const dealsContext = hasDeals ? `Available Providers: ${deals.length} providers found\nTop provider: ${deals[0].sellerName} at $${deals[0].quote.price}` : 'No providers found yet.';
  const prompt = `You are an intent classifier for a conversational service marketplace agent (BUYER side).
Current Phase: ${phase}
(Phases: conversation = collecting job info, confirmation = reviewing job before search, negotiation = finding providers, refinement = discussing results)
Recent Conversation:\n${recentMessages || 'No previous messages'}\n${jobContext}\n${dealsContext}
User's Current Message: "${message}"

Classify the user's intent into ONE of these categories:

1. **restart** - ONLY when the user EXPLICITLY wants to cancel, stop, or abandon the current flow. Examples: "never mind", "cancel", "start over", "let's do something else", "forget it", "I want to start fresh". Do NOT use restart when the user is describing what they need (e.g. a detailed job description). Describing a new job = continue_conversation.

2. **confirm_and_proceed** - User explicitly confirms to proceed (only in confirmation phase). E.g. "yes", "looks good", "find providers", "proceed".

3. **modify_before_confirm** - User wants to change or add details before confirming (in confirmation phase).

4. **continue_conversation** - User is providing job information, answering questions, OR giving a full description of what they need. Use this when: the user describes a service with details (scope, budget, dates, address); the first message is a long description of their request; they are answering "what service", "what budget", etc. Example: "I need a full roof replacement. Here are the details: 2-story house, ~2000 sq ft, full tear-off, budget $15000, by end of March, address 123 Main St" -> continue_conversation.

5. **refinement_question** - User is asking about job/provider details (in refinement phase).

6. **select_provider** - User wants to select/book a specific provider (in refinement phase).

7. **filter_results** - User wants to filter/sort providers (in refinement phase).

8. **ask_question** - User is asking a general question not related to current phase.

Reply ONLY with JSON:\n{ "intent": "<one of the 8 intents above>", "confidence": "<high|medium|low>", "reasoning": "<brief explanation>" }`;
  try {
    const res = await llm.invoke([new SystemMessage('Only output valid JSON. Be accurate in intent classification.'), new HumanMessage(prompt)]);
    let content = res.content.trim().replace(/```json\n?/g, '').replace(/```\n?/g, '');
    const result = JSON.parse(content);
    console.log(`[BuyerIntent] Detected: ${result.intent} (${result.confidence})`);
    const intent = result.intent;
    if (phase === 'conversation' && !recentMessages?.trim() && intent === 'restart') {
      const msg = (message || '').trim();
      if (msg.length > 80 || /\d{4,}|\$\d+|budget|address|sq ft|replacement|repair|install/.test(msg)) {
        console.log('[BuyerIntent] Override: first message with job-like details treated as continue_conversation');
        return 'continue_conversation';
      }
    }
    return intent;
  } catch (error) {
    console.error('[BuyerIntent] LLM classification error:', error.message);
    if (phase === 'conversation') return 'continue_conversation';
    if (phase === 'confirmation') return 'confirm_and_proceed';
    if (phase === 'refinement') return 'refinement_question';
    return 'continue_conversation';
  }
}

async function handleConversation(session, message, send) {
  const { sessionId, buyerId, accessToken } = session;
  send({ type: 'phase', phase: 'conversation' });
  const result = await runConversation({ sessionId, buyerId, accessToken, message });
  send({ type: 'message', text: result.response, action: result.action });
  send({ type: 'collected', data: result.collected, requiredMissing: result.requiredMissing, optionalMissing: result.optionalMissing, jobReadiness: result.jobReadiness });
  if (result.phase === 'confirmation') {
    await sessionService.updatePhase(sessionId, 'confirmation');
    await appendMessage(sessionId, { role: 'system', content: `Job preview ready: ${JSON.stringify(result.collected)}` });
    send({ type: 'phase_transition', from: 'conversation', to: 'confirmation', jobPreview: result.collected });
    send({ type: 'phase', phase: 'confirmation' });
  }
  if (result.phase === 'complete' && result.job) {
    await sessionService.updateState(sessionId, { job: result.job });
    await sessionService.updatePhase(sessionId, 'negotiation');
    await appendMessage(sessionId, { role: 'system', content: `Job created: ${result.job.title} (ID: ${result.job.id})` });
    send({ type: 'phase_transition', from: 'confirmation', to: 'negotiation', job: result.job });
    send({ type: 'message', text: `Perfect! Now searching for the best ${result.collected.service_category_name} providers in your area...` });
    await handleNegotiation(session, result.job, send);
  }
}

async function handleConfirmAndProceed(session, message, send) {
  const { sessionId, buyerId, accessToken } = session;
  send({ type: 'phase', phase: 'confirmation' });
  const result = await runConversation({ sessionId, buyerId, accessToken, message: message });
  if (result.phase === 'complete' && result.job) {
    await sessionService.updateState(sessionId, { job: result.job });
    await sessionService.updatePhase(sessionId, 'negotiation');
    send({ type: 'phase_transition', from: 'confirmation', to: 'negotiation', job: result.job });
    send({ type: 'message', text: `Excellent! Searching for the best ${result.collected.service_category_name} providers for you now...` });
    await handleNegotiation(session, result.job, send);
  } else {
    send({ type: 'message', text: result.response || "Let me know when you're ready to proceed!" });
  }
}

async function handleModifyBeforeConfirm(session, message, send) {
  const { sessionId, buyerId, accessToken } = session;
  await sessionService.updatePhase(sessionId, 'conversation');
  send({ type: 'phase_transition', from: 'confirmation', to: 'conversation' });
  send({ type: 'phase', phase: 'conversation' });
  const result = await runConversation({ sessionId, buyerId, accessToken, message });
  send({ type: 'message', text: result.response, action: result.action });
  send({ type: 'collected', data: result.collected, requiredMissing: result.requiredMissing, optionalMissing: result.optionalMissing, jobReadiness: result.jobReadiness });
  if (result.phase === 'confirmation') {
    await sessionService.updatePhase(sessionId, 'confirmation');
    send({ type: 'phase_transition', from: 'conversation', to: 'confirmation', jobPreview: result.collected });
    send({ type: 'phase', phase: 'confirmation' });
  }
}

async function handleNegotiation(session, job, send) {
  const { sessionId, buyerId, accessToken } = session;
  send({ type: 'phase', phase: 'negotiation' });
  await sessionService.updateState(sessionId, { job });
  const result = await runNegotiationAndMatchStream(job, accessToken, { buyerId, useMem0: true, providerLimit: 10, maxRounds: 1 }, send);
  if (result?.deals) await sessionService.updateState(sessionId, { deals: result.deals });
  await sessionService.updatePhase(sessionId, 'refinement');
  send({ type: 'phase_transition', from: 'negotiation', to: 'refinement' });
  if (result?.deals && result.deals.length > 0) {
    const topDeal = result.deals[0];
    await appendMessage(sessionId, { role: 'system', content: `Search completed. Found ${result.deals.length} providers for ${job.title}. Top match: ${topDeal.sellerName} at $${topDeal.quote.price}.` });
    send({ type: 'message', text: `Great news! I found ${result.deals.length} providers for your ${job.title} job. The best match is ${topDeal.sellerName} at $${topDeal.quote.price} (${topDeal.quote.days} days). Would you like to book them, or see more details?` });
  } else {
    send({ type: 'message', text: "I couldn't find any providers matching your requirements right now. Would you like to adjust your criteria and try again?" });
  }
  send({ type: 'phase', phase: 'refinement' });
}

async function handleRefinement(session, message, send) {
  const { deals, job, sessionId } = session;
  send({ type: 'phase', phase: 'refinement' });
  const conversationHistory = await getMessagesForSession(sessionId, 50);
  const llm = new ChatOpenAI({ model: 'gpt-4o-mini', temperature: 0.7, openAIApiKey: OPENAI_API_KEY });
  const recentMessages = conversationHistory.slice(-10).map(m => `${m.role.toUpperCase()}: ${m.content}`).join('\n');
  const jobContext = job ? `Job Details:\n- Title: ${job.title}\n- Service Category: ${job.service_category_id}\n- Budget: $${job.budget.min}-$${job.budget.max}\n- Start: ${job.startDate}\n- End: ${job.endDate}\n- Location: ${job.location || 'Not specified'}\n- Description: ${job.description || 'No detailed description'}` : 'No job details available.';
  const dealsContext = deals && deals.length > 0 ? `Available Providers (${deals.length}):\n${deals.map((d, i) => `${i + 1}. ${d.sellerName}\n   - Price: $${d.quote.price}\n   - Completion: ${d.quote.days} days\n   - Match Score: ${d.matchScore}/100`).join('\n')}` : 'No providers found yet.';
  const prompt = `You are a helpful assistant in the refinement phase after finding service providers.\nConversation History:\n${recentMessages}\n${jobContext}\n${dealsContext}\nUser's Question: "${message}"\nAnswer naturally. Reply ONLY with JSON: { "message": "<response as a string only, never an object>", "intent": "<info_request|provider_selection|filter_request|new_search|comparison|other>", "requires_action": true/false }. When the user asks for job details, summarize the job in a short friendly message using the job context above; do not return raw data.`;
  try {
    const res = await llm.invoke([new SystemMessage('Only output valid JSON. The "message" value must always be a string.'), new HumanMessage(prompt)]);
    let content = res.content.trim().replace(/```json\n?/g, '').replace(/```\n?/g, '');
    const response = JSON.parse(content);
    let messageText = response.message;
    if (messageText != null && typeof messageText !== 'string') {
      if (job && typeof messageText === 'object' && (messageText.title || messageText.description != null)) {
        messageText = `Here are your job details:\n\nTitle: ${job.title || 'N/A'}\nService: ${job.service_category_name ?? job.service_category_id ?? 'N/A'}\nBudget: $${job.budget?.min ?? '?'}-$${job.budget?.max ?? '?'}\nStart: ${job.startDate ?? 'N/A'}\nEnd: ${job.endDate ?? 'N/A'}\nLocation: ${typeof job.location === 'object' ? job.location?.address : job.location ?? 'N/A'}\nDescription: ${job.description || 'None'}`;
      } else {
        messageText = JSON.stringify(messageText);
      }
    }
    messageText = String(messageText ?? '');
    send({ type: 'message', text: messageText });
    await appendMessage(sessionId, { role: 'user', content: message });
    await appendMessage(sessionId, { role: 'assistant', content: messageText });
    if (response.intent === 'info_request') {
      const lowerMessage = message.toLowerCase();
      if (lowerMessage.includes('all providers') || lowerMessage.includes('all details') || lowerMessage.includes('show all') || lowerMessage.includes('complete details')) {
        send({ type: 'deals_detail', deals: deals?.map(d => ({ id: d.id, sellerId: d.sellerId, name: d.sellerName, email: d.sellerEmail, contactNumber: d.sellerContactNumber, price: d.quote.price, days: d.quote.days, paymentSchedule: d.quote.paymentSchedule, licensed: d.quote.licensed, referencesAvailable: d.quote.referencesAvailable, can_meet_dates: d.quote.can_meet_dates, matchScore: d.matchScore })) || [] });
      }
    }
  } catch (error) {
    const fallbackResponse = job ? `Your job "${job.title}" has a budget of $${job.budget.min}-$${job.budget.max}. I found ${deals?.length || 0} providers. What would you like to know?` : "I'm here to help! You can ask about the job details, providers, or start a new search.";
    send({ type: 'message', text: fallbackResponse });
  }
}

async function handleGeneralQuestion(session, message, send) {
  const llm = new ChatOpenAI({ model: 'gpt-4o-mini', temperature: 0.7, openAIApiKey: OPENAI_API_KEY });
  const prompt = `You are a helpful assistant for a service marketplace platform (buyer side).\nUser's Question: "${message}"\nThis is a general question. Reply ONLY with JSON: { "message": "<your helpful response as a string only>" }`;
  try {
    const res = await llm.invoke([new SystemMessage('Only output valid JSON. The "message" value must be a string.'), new HumanMessage(prompt)]);
    let content = res.content.trim().replace(/```json\n?/g, '').replace(/```\n?/g, '');
    const response = JSON.parse(content);
    let messageText = response.message;
    if (messageText != null && typeof messageText !== 'string') {
      messageText = typeof messageText === 'object' ? JSON.stringify(messageText) : String(messageText);
    }
    messageText = String(messageText ?? '');
    send({ type: 'message', text: messageText });
    await appendMessage(session.sessionId, { role: 'user', content: message });
    await appendMessage(session.sessionId, { role: 'assistant', content: messageText });
  } catch (error) {
    send({ type: 'message', text: "I'm here to help you find service providers! You can describe what service you need, and I'll guide you through the process." });
  }
}

async function handleBuyerRestart(session, send) {
  const { sessionId, buyerId, accessToken } = session;
  const newSession = await sessionService.restartSession(sessionId, { userId: buyerId, userType: 'buyer', accessToken });
  const sessionObj = buildBuyerSessionFromDb(newSession);
  send({ type: 'session', sessionId: sessionObj.sessionId, phase: 'conversation', userType: 'buyer' });
  send({ type: 'phase', phase: 'conversation' });
  send({ type: 'message', text: "No problem, let's start fresh! What kind of service are you looking for today?" });
  send({ type: 'collected', data: {}, requiredMissing: [], optionalMissing: [], jobReadiness: 'incomplete' });
}

async function handleProviderSelection(session, message, send) {
  const { sessionId, buyerId, deals, job } = session;
  if (!deals || deals.length === 0) {
    send({ type: 'message', text: "I don't have any providers to select from. Would you like to search for providers first?" });
    return;
  }
  const lowerMessage = message.toLowerCase();
  let selectedDeal = null;
  if (lowerMessage.includes('first') || lowerMessage.includes('best') || lowerMessage.includes('top')) selectedDeal = deals[0];
  else {
    for (const deal of deals) {
      if (deal.sellerName && lowerMessage.includes(deal.sellerName.toLowerCase())) { selectedDeal = deal; break; }
    }
  }
  if (!selectedDeal) {
    const numberMatch = message.match(/\d+/);
    if (numberMatch) {
      const index = parseInt(numberMatch[0]) - 1;
      if (index >= 0 && index < deals.length) selectedDeal = deals[index];
    }
  }
  if (selectedDeal) {
    if (job?.id) await updateNegotiationOutcome(buyerId, job.id, selectedDeal.sellerId, 'accepted');
    send({ type: 'provider_selected', deal: { id: selectedDeal.id, sellerId: selectedDeal.sellerId, sellerName: selectedDeal.sellerName, price: selectedDeal.quote.price, days: selectedDeal.quote.days, paymentSchedule: selectedDeal.quote.paymentSchedule } });
    send({ type: 'message', text: `Excellent choice! I've selected ${selectedDeal.sellerName} for $${selectedDeal.quote.price}. They'll complete the job in ${selectedDeal.quote.days} day(s). You can now proceed to confirm the booking!` });
    await sessionService.updatePhase(sessionId, 'complete');
    send({ type: 'phase', phase: 'complete' });
  } else {
    const providerList = deals.map((d, i) => `${i + 1}. ${d.sellerName} - $${d.quote.price}`).join('\n');
    send({ type: 'message', text: `Which provider would you like to select?\n\n${providerList}\n\nJust say "select provider 1" or mention their name!` });
  }
}

async function handleFilterResults(session, message, send) {
  const { deals } = session;
  if (!deals || deals.length === 0) {
    send({ type: 'message', text: "I don't have any results to filter. Would you like to search for providers?" });
    return;
  }
  const lowerMessage = message.toLowerCase();
  let filteredDeals = [...deals];
  if (lowerMessage.includes('licensed')) filteredDeals = filteredDeals.filter(d => d.quote.licensed === true);
  if (lowerMessage.includes('reference')) filteredDeals = filteredDeals.filter(d => d.quote.referencesAvailable === true);
  if (lowerMessage.includes('cheaper') || lowerMessage.includes('lowest price')) filteredDeals = filteredDeals.sort((a, b) => a.quote.price - b.quote.price);
  if (lowerMessage.includes('highest rated') || lowerMessage.includes('best rated')) filteredDeals = filteredDeals.sort((a, b) => b.matchScore - a.matchScore);
  if (lowerMessage.includes('fastest') || lowerMessage.includes('quickest')) filteredDeals = filteredDeals.sort((a, b) => a.quote.days - b.quote.days);
  if (filteredDeals.length === 0) {
    send({ type: 'message', text: "No providers match that filter. Would you like to try a different filter or see all results?" });
  } else {
    send({ type: 'filtered_deals', deals: filteredDeals.map(d => ({ id: d.id, name: d.sellerName, price: d.quote.price, days: d.quote.days, licensed: d.quote.licensed, referencesAvailable: d.quote.referencesAvailable, matchScore: d.matchScore })), count: filteredDeals.length });
    send({ type: 'message', text: `Found ${filteredDeals.length} provider(s) matching your criteria. The top option is ${filteredDeals[0].sellerName} at $${filteredDeals[0].quote.price}. Would you like to select one?` });
  }
}

/* ---------- SELLER AGENT (Tool-calling + HITL) ---------- */

async function handleSellerAgent(input, send) {
  const { sellerId, accessToken, message, resume, forceNewSession } = input;
  let { sessionId } = input;
  if (!sellerId || !accessToken) {
    send({ type: 'error', error: 'Missing sellerId or accessToken' });
    return;
  }
  const sellerIdStr = String(sellerId);
  try {
    let dbSession;
    if (sessionId) {
      dbSession = await sessionRepository.findById(sessionId);
      if (!dbSession || !dbSession.isActive) {
        const { session } = await sessionService.getOrCreateSession({ userId: sellerIdStr, userType: 'seller', accessToken, forceNew: !!forceNewSession });
        dbSession = session;
        sessionId = dbSession.id;
      } else {
        sessionId = dbSession.id;
      }
    } else {
      const { session } = await sessionService.getOrCreateSession({ userId: sellerIdStr, userType: 'seller', accessToken, forceNew: !!forceNewSession });
      dbSession = session;
      sessionId = dbSession.id;
    }
    const profileSessionScoped = !!(forceNewSession || dbSession?.state?.profileSessionScoped);
    const config = { configurable: { thread_id: sessionId, sellerId: sellerIdStr, profileSessionScoped } };
    send({ type: 'session', sessionId, phase: dbSession.phase || 'conversation', userType: 'seller' });

    if (resume !== undefined && resume !== null) {
      const result = await resumeSellerAgent(config, resume);
      if (result && result.__interrupt__) {
        send({ type: 'interrupt', value: result.__interrupt__ });
        return;
      }
      const lastMsg = result?.messages?.[result.messages.length - 1];
      const text = lastMsg?.content && typeof lastMsg.content === 'string' ? lastMsg.content : (lastMsg?.content?.[0]?.text ?? 'Done.');
      send({ type: 'message', text });
      if (lastMsg && lastMsg.getType?.() === 'ai') {
        await messageService.addAssistantMessage(sessionId, text).catch(() => {});
      }
      return;
    }

    if (!message || typeof message !== 'string') {
      send({ type: 'error', error: 'Message is required' });
      return;
    }
    await messageService.addUserMessage(sessionId, message).catch(() => {});

    const result = await invokeSellerAgent(
      { sellerId: sellerIdStr, sessionId, newMessage: message },
      config
    );

    if (result && result.__interrupt__) {
      send({ type: 'interrupt', value: result.__interrupt__ });
      return;
    }

    const lastMsg = result?.messages?.[result.messages.length - 1];
    const text = lastMsg?.content && typeof lastMsg.content === 'string' ? lastMsg.content : (lastMsg?.content?.[0]?.text ?? "I'm here to help. What would you like to do?");
    send({ type: 'message', text });
    if (lastMsg && lastMsg.getType?.() === 'ai') {
      await messageService.addAssistantMessage(sessionId, text).catch(() => {});
    }
  } catch (error) {
    console.error('[SellerAgent] Error:', error);
    send({ type: 'error', error: error.message || 'An unexpected error occurred' });
  }
}

async function intelligentJobSelection(message, matchedJobs, session) {
  const llm = new ChatOpenAI({ model: 'gpt-4o-mini', temperature: 0, openAIApiKey: OPENAI_API_KEY });
  const conversationHistory = await getMessagesForSession(session.sessionId, 20);
  const recentMessages = conversationHistory.slice(-3).map(m => `${m.role.toUpperCase()}: ${m.content}`).join('\n');
  const jobListForLLM = matchedJobs.map((j, i) => `Job ${i + 1}:\n- ID: ${j.job_id}\n- Title: "${j.title}"\n- Budget: $${j.budget.min}-$${j.budget.max}\n- Match Score: ${j.matchScore}/100`).join('\n');
  const prompt = `You are helping a service provider select which job they want to bid on.\nRecent Conversation:\n${recentMessages || 'No previous messages'}\nAvailable Jobs:\n${jobListForLLM}\nUser's Message: "${message}"\nDetermine which job (if any). Reply ONLY with JSON: { "job_selected": true/false, "job_index": <0-based index or null>, "job_id": "<id or null>", "confidence": "high/medium/low", "reasoning": "<brief>" }`;
  try {
    const res = await llm.invoke([new SystemMessage('Only output valid JSON.'), new HumanMessage(prompt)]);
    let content = res.content.trim().replace(/```json\n?/g, '').replace(/```\n?/g, '');
    const result = JSON.parse(content);
    if (result.job_selected && result.job_index !== null) return matchedJobs[result.job_index] || null;
    if (result.job_selected && result.job_id) return matchedJobs.find(j => j.job_id === result.job_id) || null;
    return null;
  } catch (error) {
    const lowerMessage = message.toLowerCase();
    if ((lowerMessage.includes('yes') || lowerMessage.includes('first') || lowerMessage === '1') && matchedJobs.length > 0) return matchedJobs[0];
    return null;
  }
}

async function intelligentBiddingDetails(message, selectedJob, session) {
  const llm = new ChatOpenAI({ model: 'gpt-4o-mini', temperature: 0, openAIApiKey: OPENAI_API_KEY });
  const prompt = `Job: "${selectedJob.title}" Budget: $${selectedJob.budget.min}-$${selectedJob.budget.max}\nUser's Message: "${message}"\nExtract custom_price (number or null) and custom_message (string or null). Reply ONLY with JSON: { "has_custom_details": true/false, "custom_price": <number or null>, "custom_message": "<string or null>", "reasoning": "<brief>" }`;
  try {
    const res = await llm.invoke([new SystemMessage('Only output valid JSON.'), new HumanMessage(prompt)]);
    let content = res.content.trim().replace(/```json\n?/g, '').replace(/```\n?/g, '');
    const result = JSON.parse(content);
    return { customPrice: result.custom_price, customMessage: result.custom_message };
  } catch (error) {
    return { customPrice: null, customMessage: null };
  }
}

async function intelligentSellerIntent(message, session) {
  const llm = new ChatOpenAI({ model: 'gpt-4o-mini', temperature: 0, openAIApiKey: OPENAI_API_KEY });
  const dbSession = await sessionRepository.findById(session.sessionId);
  const freshSession = buildSellerSessionFromDb(dbSession) || session;
  const { phase, profile, matchedJobs } = freshSession;
  const conversationHistory = await getMessagesForSession(session.sessionId, 50);
  const recentMessages = conversationHistory.slice(-5).map(m => `${m.role.toUpperCase()}: ${m.content}`).join('\n');
  const jobContext = matchedJobs?.length > 0 ? `Available Jobs: ${matchedJobs.length}. Top: "${matchedJobs[0].title}" - $${matchedJobs[0].budget.min}-$${matchedJobs[0].budget.max}` : 'No jobs available yet';
  const prompt = `Intent classifier for service provider (seller).\nPhase: ${phase}. Has Profile: ${profile ? 'Yes' : 'No'}. ${jobContext}\nRecent:\n${recentMessages || 'None'}\nUser: "${message}"\nClassify into ONE: create_profile, provide_profile_info, browse_jobs, bid_on_job, check_dashboard, ask_question, modify_profile, restart.\nReply ONLY with JSON: { "intent": "<one above>", "confidence": "high|medium|low", "reasoning": "<brief>" }`;
  try {
    const res = await llm.invoke([new SystemMessage('Only output valid JSON.'), new HumanMessage(prompt)]);
    let content = res.content.trim().replace(/```json\n?/g, '').replace(/```\n?/g, '');
    const result = JSON.parse(content);
    return result.intent;
  } catch (error) {
    if (phase === 'profile_creation') return 'provide_profile_info';
    if (phase === 'job_browsing' && matchedJobs?.length > 0) {
      const lower = message.toLowerCase();
      if (lower.includes('yes') || lower.includes('bid') || lower.includes('first')) return 'bid_on_job';
    }
    return 'browse_jobs';
  }
}

async function handleSellerProfileCreation(session, message, send) {
  const { sessionId, sellerId, accessToken } = session;
  send({ type: 'phase', phase: 'profile_creation' });
  const result = await runSellerProfileConversation({ sessionId, sellerId, accessToken, message });
  send({ type: 'message', text: result.response, action: result.action });
  send({ type: 'profile_collected', data: result.collected, requiredMissing: result.requiredMissing, optionalMissing: result.optionalMissing, profileReadiness: result.profileReadiness });
  if (result.phase === 'complete' && result.profile) {
    await sessionService.updateState(sessionId, { profile: result.profile });
    await sessionService.updatePhase(sessionId, 'job_browsing');
    send({ type: 'phase_transition', from: 'profile_creation', to: 'job_browsing', profile: result.profile });
    send({ type: 'message', text: "Excellent! Your profile is now live. Let me find jobs that match your skills..." });
    await handleJobBrowsing(session, "show me jobs", send);
  }
}

async function handleJobBrowsing(session, message, send) {
  const { sessionId, sellerId } = session;
  send({ type: 'phase', phase: 'job_browsing' });
  const result = await findJobsForSeller(sellerId);
  await sessionService.updateState(sessionId, { matchedJobs: result.jobs });
  if (result.jobs?.length > 0) {
    const topJobs = result.jobs.slice(0, 5);
    send({ type: 'matched_jobs', jobs: topJobs.map(j => ({ job_id: j.job_id, title: j.title, budget: j.budget, location: j.location?.address, start_date: j.start_date, matchScore: j.matchScore })), count: result.jobs.length });
    const topJob = topJobs[0];
    await appendMessage(sessionId, { role: 'system', content: `Jobs found: ${result.jobs.length}. Top: "${topJob.title}" (ID: ${topJob.job_id}) - $${topJob.budget.min}-$${topJob.budget.max}, Match: ${topJob.matchScore}/100` });
    send({ type: 'message', text: `Great news! I found ${result.jobs.length} job(s). Best match: "${topJob.title}" - $${topJob.budget.min}-$${topJob.budget.max} (Match: ${topJob.matchScore}/100). Would you like to bid on this job?` });
  } else {
    send({ type: 'message', text: "I couldn't find any jobs matching your profile right now. I'll notify you when new jobs become available!" });
  }
}

async function handleBidding(session, message, send) {
  const { sellerId, matchedJobs, sessionId } = session;
  if (!matchedJobs?.length) {
    send({ type: 'message', text: "Let me first find some jobs for you..." });
    await handleJobBrowsing(session, "show jobs", send);
    return;
  }
  const selectedJob = await intelligentJobSelection(message, matchedJobs, session);
  if (!selectedJob) {
    await sessionService.updatePhase(sessionId, 'job_browsing');
    const jobList = matchedJobs.slice(0, 5).map((j, i) => `${i + 1}. "${j.title}" - $${j.budget.min}-$${j.budget.max} (Match: ${j.matchScore}/100)`).join('\n');
    send({ type: 'message', text: `I have ${matchedJobs.length} job(s):\n\n${jobList}\n\nWhich one would you like to bid on?` });
    return;
  }
  const biddingDetails = await intelligentBiddingDetails(message, selectedJob, session);
  await sessionService.updatePhase(sessionId, 'bidding');
  send({ type: 'phase', phase: 'bidding' });
  send({ type: 'message', text: `Preparing your bid for "${selectedJob.title}"...` });
  const result = await submitSellerBid({ sellerId, jobId: selectedJob.job_id, customMessage: biddingDetails.customMessage || null, customPrice: biddingDetails.customPrice || null });
  if (result.bid) {
    send({ type: 'bid_submitted', bid: { bid_id: result.bid.bid_id, job_id: result.bid.job_id, quoted_price: result.bid.quoted_price, quoted_timeline: result.bid.quoted_timeline, message: result.bid.message } });
    send({ type: 'message', text: `Perfect! I've submitted your bid for $${result.bid.quoted_price} with ${result.bid.quoted_completion_days} day(s) completion time. The buyer will review your bid.` });
    await sessionService.updatePhase(sessionId, 'job_browsing');
  } else {
    send({ type: 'message', text: `Sorry, I couldn't submit the bid. ${result.error || 'Please try again.'}` });
  }
}

async function handleDashboard(session, send) {
  const { sellerId } = session;
  const dashboard = await getSellerDashboard(sellerId);
  send({ type: 'dashboard', data: { pending_bids: dashboard.stats.pending_bids, active_jobs: dashboard.stats.active_jobs, new_matches: dashboard.stats.new_matches }, activeBids: dashboard.activeBids.slice(0, 5), activeJobs: dashboard.activeJobs.slice(0, 5) });
  send({ type: 'message', text: `Here's your dashboard: ${dashboard.stats.pending_bids} pending bid(s), ${dashboard.stats.active_jobs} active job(s), ${dashboard.stats.new_matches} new match(es). Would you like to see details?` });
}

async function handleSellerQuestion(session, message, send) {
  const llm = new ChatOpenAI({ model: 'gpt-4o-mini', temperature: 0.7, openAIApiKey: OPENAI_API_KEY });
  const prompt = `Helpful assistant for service providers.\nUser: "${message}"\nReply ONLY with JSON: { "message": "<helpful response>" }`;
  try {
    const res = await llm.invoke([new SystemMessage('Only output valid JSON.'), new HumanMessage(prompt)]);
    let content = res.content.trim().replace(/```json\n?/g, '').replace(/```\n?/g, '');
    const response = JSON.parse(content);
    send({ type: 'message', text: response.message });
  } catch (error) {
    send({ type: 'message', text: "I'm here to help you find jobs and grow your business! You can create your profile, browse jobs, and submit bids - all through this chat." });
  }
}

async function handleSellerRestart(session, send) {
  const { sessionId, sellerId, accessToken } = session;
  const newSession = await sessionService.restartSession(sessionId, { userId: sellerId, userType: 'seller', accessToken });
  const sessionObj = buildSellerSessionFromDb(newSession);
  send({ type: 'session', sessionId: sessionObj.sessionId, phase: 'profile_check', userType: 'seller' });
  send({ type: 'phase', phase: 'profile_check' });
  send({ type: 'message', text: "No problem, let's start fresh! What would you like to do?" });
}

export { intelligentBuyerIntentCheck as intelligentIntentCheck, intelligentBuyerIntentCheck as quickIntentCheck };
