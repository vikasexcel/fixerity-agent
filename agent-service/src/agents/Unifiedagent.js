import { v4 as uuidv4 } from 'uuid';
import { redisClient } from '../config/redis.js';
import { runConversation, conversationStore, serviceCategoryManager } from './conversationGraph.js';
import { runNegotiationAndMatchStream, updateNegotiationOutcome } from './negotiationOrchestrator.js';

/* ================================================================================
   UNIFIED AGENT - Updated with Human-in-the-Loop Confirmation
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

/* -------------------- INTENT ROUTER (UPDATED) -------------------- */

async function quickIntentCheck(message, currentPhase, hasDeals, jobReadiness) {
  const lowerMessage = message.toLowerCase();
  
  // Check for start over
  if (lowerMessage.includes('start over') || 
      lowerMessage.includes('new job') || 
      lowerMessage.includes('different service') ||
      lowerMessage.includes('cancel')) {
    return 'restart';
  }
  
  // In confirmation phase, check for explicit confirmation
  if (currentPhase === 'confirmation') {
    if (lowerMessage.includes('yes') ||
        lowerMessage.includes('proceed') ||
        lowerMessage.includes('find providers') ||
        lowerMessage.includes('go ahead') ||
        lowerMessage.includes('looks good') ||
        lowerMessage.includes('confirm')) {
      return 'confirm_and_proceed';
    }
    
    if (lowerMessage.includes('add') ||
        lowerMessage.includes('change') ||
        lowerMessage.includes('modify') ||
        lowerMessage.includes('update')) {
      return 'modify_before_confirm';
    }
  }
  
  // Check for provider selection
  if (hasDeals && (
      lowerMessage.includes('select') ||
      lowerMessage.includes('choose') ||
      lowerMessage.includes('pick') ||
      lowerMessage.includes('go with') ||
      lowerMessage.includes('book'))) {
    return 'select_provider';
  }
  
  // Check for filtering
  if (hasDeals && (
      lowerMessage.includes('only licensed') ||
      lowerMessage.includes('filter') ||
      lowerMessage.includes('show me') ||
      lowerMessage.includes('cheaper') ||
      lowerMessage.includes('highest rated'))) {
    return 'filter_results';
  }
  
  // Default based on phase
  if (currentPhase === 'conversation') {
    return 'continue_conversation';
  } else if (currentPhase === 'refinement') {
    return 'refinement_question';
  }
  
  return 'continue_conversation';
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

    const currentPhase = session.phase || 'conversation';
    const hasDeals = session.deals && session.deals.length > 0;
    
    const intent = await quickIntentCheck(message, currentPhase, hasDeals);
    console.log(`[Agent] Phase: ${currentPhase}, Intent: ${intent}`);

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
      default:
        await handleConversation(session, message, send);
    }

  } catch (error) {
    console.error('[Agent] Error:', error);
    send({ type: 'error', error: error.message || 'An unexpected error occurred' });
  }
}

/* -------------------- CONVERSATION HANDLER -------------------- */

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

/* -------------------- CONFIRM AND PROCEED HANDLER (NEW) -------------------- */

async function handleConfirmAndProceed(session, message, send) {
  const { sessionId, buyerId, accessToken } = session;

  console.log('[Agent] User confirmed, building job and proceeding to negotiation');

  send({ type: 'phase', phase: 'confirmation' });
  
  // User confirmed, so we pass a "confirm" intent message
  const result = await runConversation({
    sessionId,
    buyerId,
    accessToken,
    message: message, // The confirmation message
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
    // Shouldn't happen, but handle gracefully
    send({
      type: 'message',
      text: result.response || "Let me know when you're ready to proceed!",
    });
  }
}

/* -------------------- MODIFY BEFORE CONFIRM HANDLER (NEW) -------------------- */

async function handleModifyBeforeConfirm(session, message, send) {
  const { sessionId, buyerId, accessToken } = session;

  console.log('[Agent] User wants to modify details before confirming');

  // Reset to conversation phase
  await sessionManager.updatePhase(sessionId, 'conversation');

  send({ 
    type: 'phase_transition', 
    from: 'confirmation',
    to: 'conversation',
  });

  send({ type: 'phase', phase: 'conversation' });

  // Process the modification request
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

  // Check if back to confirmation
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
    send({ 
      type: 'message', 
      text: `Great news! I found ${result.deals.length} providers for you. The best match is ${topDeal.sellerName} at $${topDeal.quote.price} (${topDeal.quote.days} days). Would you like to book them, or see more details?`,
    });
  } else {
    send({ 
      type: 'message', 
      text: "I couldn't find any providers matching your requirements right now. Would you like to adjust your criteria and try again?",
    });
  }

  send({ type: 'phase', phase: 'refinement' });
}

/* -------------------- OTHER HANDLERS (UNCHANGED) -------------------- */

async function handleRefinement(session, message, send) {
  const { deals } = session;

  send({ type: 'phase', phase: 'refinement' });

  const lowerMessage = message.toLowerCase();

  if (lowerMessage.includes('more detail') || lowerMessage.includes('tell me more')) {
    if (deals && deals.length > 0) {
      send({
        type: 'deals_detail',
        deals: deals.map(d => ({
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
        })),
      });
      send({
        type: 'message',
        text: "Here are the details for all providers. Let me know if you'd like to select one!",
      });
    } else {
      send({
        type: 'message',
        text: "I don't have any provider results saved. Would you like to search again?",
      });
    }
  } else {
    send({
      type: 'message',
      text: "I'm here to help! You can ask me to show more details, select a provider, filter by criteria, or start a new search.",
    });
  }
}

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

export { sessionManager, quickIntentCheck };