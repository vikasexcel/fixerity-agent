import { Annotation, StateGraph, START, END } from '@langchain/langgraph';
import { ChatOpenAI } from '@langchain/openai';
import { HumanMessage, SystemMessage } from '@langchain/core/messages';
import { OPENAI_API_KEY } from '../config/index.js';

/* -------------------- STATE -------------------- */

const MatchingState = Annotation.Root({
  job: Annotation(),
  providerId: Annotation(),
  providerServiceData: Annotation(),
  round: Annotation(),
  maxRounds: Annotation(),
  deadline_ts: Annotation(),
  conversation: Annotation({
    reducer: (a, b) => a.concat(b),
    default: () => [],
  }),
  status: Annotation(), // collecting | done | timeout
  collectedQuote: Annotation(), // { price, days, can_meet_dates, paymentSchedule, licensed, referencesAvailable }
});

function getInitialState(input) {
  return {
    job: input.job,
    providerId: input.providerId,
    providerServiceData: input.providerServiceData,
    round: 0,
    maxRounds: input.maxRounds ?? 1, // 🔒 HARD DEFAULT
    deadline_ts: input.deadline_ts,
    conversation: [],
    status: 'collecting',
    collectedQuote: null,
  };
}

/* -------------------- HELPERS -------------------- */

function formatConversation(conversation = []) {
  if (!conversation.length) return 'No messages yet.';
  return conversation
    .map((m) => `${m.role.toUpperCase()}: ${m.message}`)
    .join('\n');
}

/**
 * Check if provider can meet the job's date requirements
 */
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

  const prompt = `
You are a friendly BUYER reaching out to a service provider about a job.

Job Details:
- Title: ${jobTitle}
- Start: ${startDate}
- End: ${endDate}
- Budget: $${state.job.budget?.min || '?'} - $${state.job.budget?.max || '?'}

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

BAD Example: "Dear Service Provider, I am gathering information for..."
GOOD Example: "Hi! I'm looking for a quote on ${jobTitle}. Can you..."

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

  return {
    conversation: [
      {
        role: 'buyer',
        message: data.message,
      },
    ],
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

  // ✅ Extract provider's actual service timeline
  const providerDays = state.providerServiceData.deadline_in_days || 
                       state.providerServiceData.service_deadline_days || 
                       state.providerServiceData.completionDays || 
                       3; // fallback only if no data

  const startDate = state.job.startDate || 'ASAP';
  const endDate = state.job.endDate || 'flexible';
  const jobTitle = state.job.title || 'the job';
  const rating = state.providerServiceData.average_rating || 0;
  const jobsCompleted = state.providerServiceData.total_completed_order || 0;
  const isLicensed = state.providerServiceData.licensed !== false;
  const hasReferences = state.providerServiceData.referencesAvailable !== false;

  // ✅ Check if provider can meet the dates
  const availability = checkDateAvailability(startDate, endDate, providerDays);

  const prompt = `
You are a PROFESSIONAL SERVICE PROVIDER responding to a quote request.

Your Background:
- Rating: ${rating}/5 (from ${jobsCompleted} completed jobs)
- Licensed: ${isLicensed ? 'Yes' : 'No'}
- References: ${hasReferences ? 'Available' : 'Not available'}
- Your typical completion time for this service: ${providerDays} day(s)

Job Request:
${formatConversation(state.conversation)}

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

  // ✅ Validate quote structure
  if (!data.quote || typeof data.quote.price !== 'number') {
    console.warn('Invalid quote structure from LLM, using fallback');
    const budgetMax = state.job.budget?.max || 1000;
    const basePrice = budgetMax * 0.92;
    
    data.quote = {
      price: Math.round(basePrice),
      days: providerDays, // ✅ Use provider's actual timeline
      can_meet_dates: availability.canMeet,
      paymentSchedule: '20% upfront, 80% on completion',
      licensed: isLicensed,
      referencesAvailable: hasReferences,
    };
  }

  // ✅ FORCE the correct values
  data.quote.days = providerDays; // Always use provider's actual timeline
  data.quote.can_meet_dates = availability.canMeet; // Always use calculated availability
  data.quote.licensed = isLicensed;
  data.quote.referencesAvailable = hasReferences;

  return {
    conversation: [
      {
        role: 'seller',
        message: data.message,
      },
    ],
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

function resolveTimeoutNode() {
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
  const finalState = await compiled.invoke(getInitialState(input));
  return {
    status: finalState.status,
    quote: finalState.collectedQuote,
    transcript: finalState.conversation,
  };
}

export const matchingGraph = compiled;