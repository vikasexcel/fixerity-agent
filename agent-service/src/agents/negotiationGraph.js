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
  collectedQuote: Annotation(), // { price, days, paymentTerms, notes }
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

/* -------------------- BUYER (INFO REQUEST) -------------------- */

async function buyerNode(state) {
  if (state.status !== 'collecting') return state;
  if (state.round >= state.maxRounds) {
    return { status: 'done' };
  }

  const llm = new ChatOpenAI({
    model: 'gpt-4o-mini',
    temperature: 0.4, // more deterministic
    openAIApiKey: OPENAI_API_KEY,
  });

  const prompt = `
You are a BUYER AGENT collecting information.
You MUST NOT negotiate or accept deals.

Job:
${JSON.stringify(state.job, null, 2)}

You need to ask the provider for:
- price quote
- start & end timeline (days)
- payment schedule (e.g. % upfront)
- license / references confirmation (yes/no)

Rules:
- Ask everything in ONE concise message.
- Do NOT counter prices.
- Do NOT negotiate.
- Be polite and professional.

Reply ONLY with JSON:
{
  "message": "your question to the provider"
}
`;

  const res = await llm.invoke([
    new SystemMessage('Only output valid JSON'),
    new HumanMessage(prompt),
  ]);

  const data = JSON.parse(res.content);

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
    temperature: 0.4,
    openAIApiKey: OPENAI_API_KEY,
  });

  const prompt = `
You are a SERVICE PROVIDER responding to a quote request.

Your business info:
${JSON.stringify(state.providerServiceData, null, 2)}

Conversation so far:
${formatConversation(state.conversation)}

Rules:
- Provide a clear price and timeline.
- Include payment schedule.
- Answer license / reference questions honestly.
- Do NOT negotiate.

Reply ONLY with JSON:
{
  "message": "your response",
  "quote": {
    "price": number,
    "days": number,
    "paymentSchedule": string,
    "licensed": boolean,
    "referencesAvailable": boolean
  }
}
`;

  const res = await llm.invoke([
    new SystemMessage('Only output valid JSON'),
    new HumanMessage(prompt),
  ]);

  const data = JSON.parse(res.content);

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
