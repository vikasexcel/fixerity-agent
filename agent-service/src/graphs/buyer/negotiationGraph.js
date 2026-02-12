import { Annotation, StateGraph, START, END } from '@langchain/langgraph';
import { ChatOpenAI } from '@langchain/openai';
import { HumanMessage, SystemMessage } from '@langchain/core/messages';
import { OPENAI_API_KEY } from '../../config/index.js';
import { negotiationService, memoryService } from '../../services/index.js';

/* ================================================================================
   NEGOTIATION GRAPH - Provider Quote Collection
   ================================================================================ */

/* -------------------- STATE -------------------- */

const MatchingState = Annotation.Root({
  job: Annotation(),
  providerId: Annotation(),
  providerServiceData: Annotation(),
  round: Annotation(),
  maxRounds: Annotation(),
  deadline_ts: Annotation(),
  buyerId: Annotation(),
  conversation: Annotation({
    reducer: (a, b) => {
      const combined = a.concat(b);
      return combined.slice(-20);
    },
    default: () => [],
  }),
  status: Annotation(),
  collectedQuote: Annotation(),
  buyerPreferences: Annotation(),
  providerPattern: Annotation(),
  streamCallback: Annotation(),
});

async function getInitialState(input) {
  const jobId = input.job.id;
  const providerId = input.providerId;
  const buyerId = input.buyerId;

  // Try to restore from database
  const existing = await negotiationService.getNegotiationWithContext(jobId, providerId);
  
  if (existing) {
    console.log(`[Negotiation] Restored state for job ${jobId}, provider ${providerId}`);
    return {
      ...existing.state,
      conversation: existing.transcript.map(t => ({ role: t.role, message: t.message })),
      streamCallback: input.streamCallback,
    };
  }

  // Get buyer preferences and provider patterns from memory
  let buyerPreferences = null;
  let providerPattern = null;

  if (buyerId && input.useMem0Learning !== false) {
    buyerPreferences = await memoryService.getBuyerPreferences(
      buyerId,
      input.job.service_category_id
    );
    
    providerPattern = await memoryService.getProviderPattern(
      providerId,
      input.job.service_category_id
    );
  }

  // Create new state
  const initialState = {
    job: input.job,
    providerId: input.providerId,
    providerServiceData: input.providerServiceData,
    buyerId: buyerId,
    round: 0,
    maxRounds: input.maxRounds ?? 1,
    deadline_ts: input.deadline_ts,
    conversation: [],
    status: 'collecting',
    collectedQuote: null,
    buyerPreferences: buyerPreferences,
    providerPattern: providerPattern,
    streamCallback: input.streamCallback,
  };

  // Create negotiation in database
  await negotiationService.startNegotiation({
    jobId,
    providerId,
    buyerId,
    initialState,
  });

  console.log(`[Negotiation] Created new state for job ${jobId}, provider ${providerId}`);

  return initialState;
}

/* -------------------- HELPERS -------------------- */

function checkDateAvailability(startDate, endDate, providerDays) {
  if (!startDate || !endDate || startDate === 'ASAP' || endDate === 'flexible') {
    return { canMeet: true, reason: '' };
  }

  try {
    const start = new Date(startDate);
    const end = new Date(endDate);
    const availableDays = Math.ceil((end - start) / (1000 * 60 * 60 * 24));

    if (providerDays > availableDays) {
      return {
        canMeet: false,
        reason: `I need ${providerDays} days but you only have ${availableDays} days available between ${startDate} and ${endDate}.`
      };
    }

    return { canMeet: true, reason: '' };
  } catch (e) {
    console.error('Date parsing error:', e);
    return { canMeet: true, reason: '' };
  }
}

/* -------------------- BUYER (INFO REQUEST) -------------------- */

async function buyerNode(state) {
  if (state.status !== 'collecting') return state;
  if (state.round >= state.maxRounds) {
    await negotiationService.updateState(state.job.id, {
      jobId: state.job.id,
      providerId: state.providerId,
      status: 'done',
    });
    return { status: 'done' };
  }

  const llm = new ChatOpenAI({
    model: 'gpt-4o-mini',
    temperature: 0.7,
    openAIApiKey: OPENAI_API_KEY,
  });

  const startDate = state.job.startDate || 'ASAP';
  const endDate = state.job.endDate || 'flexible';
  const jobTitle = state.job.title || 'service job';

  let preferenceContext = '';
  if (state.buyerPreferences?.summary?.top_insights) {
    const insights = state.buyerPreferences.summary.top_insights
      .map(i => i.text)
      .join('; ');
    preferenceContext = `\n\nContext: Based on past interactions, ${insights}`;
  }

  const prompt = `
You are a friendly BUYER reaching out to a service provider about a job.

Job Details:
- Title: ${jobTitle}
- Start: ${startDate}
- End: ${endDate}
- Budget: $${state.job.budget?.min || '?'} - $${state.job.budget?.max || '?'}
${preferenceContext}

Write a NATURAL, CONVERSATIONAL message asking for:
1. Their price quote
2. If they can start by ${startDate} and finish by ${endDate}
3. How long it'll take (in days)
4. Payment terms they offer (upfront % vs on completion)
5. If they're licensed
6. If they have references

Tone:
- Friendly and conversational (like texting a professional)
- NOT formal or robotic
- Keep it brief but warm
- Use contractions (I'm, you're, can't)
- Ask naturally, don't list numbered items

Reply ONLY with JSON:
{
  "message": "your natural, friendly message"
}
`;

  const res = await llm.invoke([
    new SystemMessage('Only output valid JSON. Write conversationally, not formally.'),
    new HumanMessage(prompt),
  ]);

  let content = res.content.trim();
  content = content.replace(/```json\n?/g, '').replace(/```\n?/g, '');
  
  const data = JSON.parse(content);

  const message = {
    role: 'buyer',
    message: data.message,
  };

  // Save to database
  const negotiation = await negotiationService.getNegotiationWithContext(state.job.id, state.providerId);
  if (negotiation) {
    await negotiationService.addMessage(negotiation.id, 'buyer', data.message);
  }

  // Stream callback if provided
  if (typeof state.streamCallback === 'function') {
    state.streamCallback({
      type: 'negotiation_step',
      providerId: state.providerId,
      step: {
        role: 'buyer',
        round: state.round + 1,
        action: 'request',
        message: data.message,
      }
    });
  }

  return {
    conversation: [message],
    round: state.round + 1,
  };
}

/* -------------------- SELLER (QUOTE RESPONSE) -------------------- */

async function sellerNode(state) {
  if (state.status !== 'collecting') return state;

  const llm = new ChatOpenAI({
    model: 'gpt-4o-mini',
    temperature: 0.7,
    openAIApiKey: OPENAI_API_KEY,
  });

  const providerDays = state.providerServiceData.deadline_in_days || 
                       state.providerServiceData.service_deadline_days || 
                       state.providerServiceData.completionDays || 
                       3;

  const startDate = state.job.startDate || 'ASAP';
  const endDate = state.job.endDate || 'flexible';
  const jobTitle = state.job.title || 'the job';
  const rating = state.providerServiceData.average_rating || 0;
  const jobsCompleted = state.providerServiceData.total_completed_order || 0;
  const isLicensed = state.providerServiceData.licensed !== false;
  const hasReferences = state.providerServiceData.referencesAvailable !== false;

  const availability = checkDateAvailability(startDate, endDate, providerDays);

  let patternContext = '';
  if (state.providerPattern?.summary?.top_patterns) {
    const patterns = state.providerPattern.summary.top_patterns
      .map(p => p.text)
      .join('; ');
    patternContext = `\n\nYour Past Behavior: ${patterns}`;
  }

  const conversationContext = state.conversation
    .map(m => `${m.role.toUpperCase()}: ${m.message}`)
    .join('\n');

  const prompt = `
You are a PROFESSIONAL SERVICE PROVIDER responding to a quote request.

Your Background:
- Rating: ${rating}/5 (from ${jobsCompleted} completed jobs)
- Licensed: ${isLicensed ? 'Yes' : 'No'}
- References: ${hasReferences ? 'Available' : 'Not available'}
- Your typical completion time for this service: ${providerDays} day(s)
${patternContext}

Conversation so far:
${conversationContext}

Job Requirements:
- Title: ${jobTitle}
- Client needs to start: ${startDate}
- Client needs to finish: ${endDate}
- Budget range: $${state.job.budget?.min || '?'} - $${state.job.budget?.max || '?'}

CRITICAL - Date Availability:
${availability.canMeet 
  ? `✅ YOU CAN meet these dates (you need ${providerDays} days, they have enough time)`
  : `❌ YOU CANNOT meet these dates. ${availability.reason}`
}

Instructions:
Write a NATURAL, PROFESSIONAL response that includes:
1. A friendly greeting
2. Your price quote (be competitive but fair based on your experience)
3. ${availability.canMeet 
     ? `Confirm you CAN start on ${startDate} and finish by ${endDate}` 
     : `Politely explain you CANNOT meet the dates and suggest alternative timeline`}
4. State that you need ${providerDays} day(s) to complete the job
5. Your payment terms (e.g., "I typically do 20% upfront, 80% when done")
6. Mention your license status naturally
7. Mention references if available

Tone:
- Professional but friendly (like a text from a skilled contractor)
- Confident but not pushy
- Use contractions and natural language
- NO numbered lists
- NO formal salutations

Pricing Strategy:
- If budget max is given, quote 90-95% of it
- Higher rating (4+) = can charge more
- Be realistic and competitive

Reply ONLY with JSON:
{
  "message": "your natural, professional response",
  "quote": {
    "price": <number - your quote in dollars>,
    "days": ${providerDays},
    "can_meet_dates": ${availability.canMeet},
    "paymentSchedule": "<natural description like '20% upfront, rest when done'>",
    "licensed": ${isLicensed},
    "referencesAvailable": ${hasReferences}
  }
}
`;

  const res = await llm.invoke([
    new SystemMessage('Only output valid JSON. Write like a real service provider texting, not a robot.'),
    new HumanMessage(prompt),
  ]);

  let content = res.content.trim();
  content = content.replace(/```json\n?/g, '').replace(/```\n?/g, '');
  
  const data = JSON.parse(content);

  if (!data.quote || typeof data.quote.price !== 'number') {
    console.warn('Invalid quote structure from LLM, using fallback');
    const budgetMax = state.job.budget?.max || 1000;
    const basePrice = budgetMax * 0.92;
    
    data.quote = {
      price: Math.round(basePrice),
      days: providerDays,
      can_meet_dates: availability.canMeet,
      paymentSchedule: '20% upfront, 80% on completion',
      licensed: isLicensed,
      referencesAvailable: hasReferences,
    };
  }

  // Force correct values
  data.quote.days = providerDays;
  data.quote.can_meet_dates = availability.canMeet;
  data.quote.licensed = isLicensed;
  data.quote.referencesAvailable = hasReferences;

  const message = {
    role: 'seller',
    message: data.message,
  };

  // Save to database
  const negotiation = await negotiationService.getNegotiationWithContext(state.job.id, state.providerId);
  if (negotiation) {
    await negotiationService.addMessage(negotiation.id, 'seller', data.message);
    await negotiationService.saveQuote(negotiation.id, data.quote);
  }

  // Stream callback if provided
  if (typeof state.streamCallback === 'function') {
    state.streamCallback({
      type: 'negotiation_step',
      providerId: state.providerId,
      step: {
        role: 'seller',
        round: state.round,
        action: 'quote',
        message: data.message,
        price: data.quote.price,
        completionDays: data.quote.days,
        paymentSchedule: data.quote.paymentSchedule,
        can_meet_dates: data.quote.can_meet_dates,
        licensed: data.quote.licensed,
        referencesAvailable: data.quote.referencesAvailable,
      }
    });
  }

  return {
    conversation: [message],
    collectedQuote: data.quote,
    status: 'done',
  };
}

/* -------------------- ROUTING -------------------- */

function routeAfterBuyer(state) {
  if (Date.now() > state.deadline_ts) return 'timeout';
  return 'seller';
}

function routeAfterSeller() {
  return 'end';
}

async function resolveTimeoutNode(state) {
  const negotiation = await negotiationService.getNegotiationWithContext(state.job.id, state.providerId);
  if (negotiation) {
    await negotiationService.markAsTimeout(negotiation.id);
  }
  
  if (typeof state.streamCallback === 'function') {
    state.streamCallback({
      type: 'negotiation_timeout',
      providerId: state.providerId,
    });
  }
  
  return { status: 'timeout' };
}

/* -------------------- GRAPH -------------------- */

const workflow = new StateGraph(MatchingState)
  .addNode('buyer', buyerNode)
  .addNode('seller', sellerNode)
  .addNode('timeout', resolveTimeoutNode)
  .addEdge(START, 'buyer')
  .addConditionalEdges('buyer', routeAfterBuyer, {
    seller: 'seller',
    timeout: 'timeout',
  })
  .addConditionalEdges('seller', routeAfterSeller, {
    end: END,
  })
  .addEdge('timeout', END);

const compiled = workflow.compile();

/* -------------------- RUNNERS -------------------- */

export async function runMatching(input) {
  const initialState = await getInitialState(input);
  const finalState = await compiled.invoke(initialState);
  
  // Retrieve from database for complete data
  const jobId = input.job.id;
  const providerId = input.providerId;
  
  const negotiation = await negotiationService.getNegotiationWithContext(jobId, providerId);
  
  return {
    status: negotiation?.status || finalState.status,
    quote: negotiation?.quote || finalState.collectedQuote,
    transcript: negotiation?.transcript || finalState.conversation,
    buyerId: input.buyerId,
  };
}

export const matchingGraph = compiled;
export { negotiationService as sessionStore, memoryService as semanticMemory };