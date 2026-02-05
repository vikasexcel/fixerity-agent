/**
 * Negotiation subgraph: buyer and seller nodes take turns negotiating price and
 * completion time for a single job-provider pair. Exits on accept or timeout/round limit.
 */
import { Annotation, StateGraph, START, END } from '@langchain/langgraph';
import { ChatOpenAI } from '@langchain/openai';
import { HumanMessage, SystemMessage } from '@langchain/core/messages';
import { OPENAI_API_KEY } from '../config/index.js';
// --- State schema ---
const NegotiationState = Annotation.Root({
  job: Annotation(),
  providerId: Annotation(),
  providerServiceData: Annotation(),
  round: Annotation(),
  maxRounds: Annotation(),
  deadline_ts: Annotation(),

  // 🧠 LLM memory
  conversation: Annotation({
    reducer: (a, b) => a.concat(b),
    default: () => [],
  }),

  status: Annotation(), // negotiating | accepted | timeout
  finalDeal: Annotation(), // { price, days }
});


function getInitialState(input) {
  return {
    job: input.job,
    providerId: input.providerId,
    providerServiceData: input.providerServiceData ?? {},
    round: 0,
    maxRounds: input.maxRounds ?? 8,
    deadline_ts: input.deadline_ts ?? Date.now() + 120_000,
    conversation: [],
    status: "negotiating",
    finalDeal: null,
  };
}

function parseOfferResponse(text) {
  const str = typeof text === 'string' ? text : String(text ?? '');
  const jsonMatch = str.match(/\{[\s\S]*\}/);
  if (jsonMatch) {
    try {
      const parsed = JSON.parse(jsonMatch[0]);
      const action = parsed.action === 'accept' || parsed.action === 'counter' ? parsed.action : 'counter';
      return {
        action,
        price: typeof parsed.price === 'number' ? parsed.price : undefined,
        completionDays: typeof parsed.completionDays === 'number' ? parsed.completionDays : undefined,
      };
    } catch (_) {}
  }
  return { action: 'counter', price: undefined, completionDays: undefined };
}

function getPriceFromOffer(offer) {
  if (offer && typeof offer.price === 'number') return offer.price;
  return null;
}

function getCompletionDaysFromOffer(offer) {
  if (offer && typeof offer.completionDays === 'number') return offer.completionDays;
  return null;
}
// 🧠 Converts conversation memory into readable dialogue for the LLM (includes offer when present)
function formatConversation(conversation = []) {
  if (!conversation.length) return "No messages yet.";
  return conversation
    .map((m) => {
      const offer = m.offer;
      const price = offer && typeof offer.price === "number" ? offer.price : null;
      const days = offer && (typeof offer.days === "number" ? offer.days : typeof offer.completionDays === "number" ? offer.completionDays : null);
      const offerStr = price != null && days != null ? ` [offer: $${price}, ${days} days]` : "";
      return `${m.role.toUpperCase()}${offerStr}: ${m.message}`;
    })
    .join("\n");
}

/** Buyer node: makes first offer or counters seller's offer. */
async function buyerNode(state) {
  if (state.status !== "negotiating") return state;

  const llm = new ChatOpenAI({
    model: "gpt-4o-mini",
    temperature: 0.7,
    openAIApiKey: OPENAI_API_KEY,
  });

  const prompt = `
You are the BUYER negotiating with a single provider. This is a natural back-and-forth conversation.

Job:
${JSON.stringify(state.job, null, 2)}

Provider profile:
${JSON.stringify(state.providerServiceData, null, 2)}

Conversation so far (read it and respond to the LATEST message from the provider; do not repeat yourself):
${formatConversation(state.conversation)}

Rules:
- If the provider just sent a message, respond directly to what they said (e.g. acknowledge their price or timeline, then make your counter or accept).
- If this is your first message, introduce your budget and timeline in one short message and give your opening offer.
- Never copy-paste or repeat a previous message. Each reply must move the conversation forward.
- Prefer short, natural sentences. You may accept, or counter with a new price and/or days.

Reply ONLY with valid JSON, no other text:
{
  "message": "your next message to the provider (one short paragraph, respond to their last message)",
  "action": "continue" or "accept",
  "offer": { "price": number, "days": number } or null (required when countering; use your proposed price and completion days)
}
`;

  const res = await llm.invoke([new SystemMessage("Only output valid JSON"), new HumanMessage(prompt)]);
  const data = JSON.parse(res.content);

  const message = {
    role: "buyer",
    message: data.message,
    offer: data.offer,
  };

  // Return only the new message; reducer will append to state.conversation (avoids duplication)
  return {
    conversation: [message],
    round: state.round + 1,
    status: data.action === "accept" ? "accepted" : "negotiating",
    finalDeal: data.action === "accept" ? data.offer : state.finalDeal,
  };
}


/** Seller node: counters buyer's offer. */
async function sellerNode(state) {
  if (state.status !== "negotiating") return state;

  const llm = new ChatOpenAI({
    model: "gpt-4o-mini",
    temperature: 0.7,
    openAIApiKey: OPENAI_API_KEY,
  });

  const prompt = `
You are the SERVICE PROVIDER in a negotiation with a buyer. This is a natural back-and-forth conversation.

Your business info (min/max price, typical timeline):
${JSON.stringify(state.providerServiceData, null, 2)}

Job:
${JSON.stringify(state.job, null, 2)}

Conversation so far (read it and respond to the LATEST message from the buyer; do not repeat yourself):
${formatConversation(state.conversation)}

Rules:
- If the buyer just sent a message, respond directly to what they said (e.g. acknowledge their offer, then accept or counter with your terms).
- If this is your first message, state your minimum price and realistic timeline in one short message and give your opening offer.
- Never copy-paste or repeat a previous message. Each reply must move the conversation forward.
- Prefer short, natural sentences. You may accept their offer, or counter with your price and/or days.

Reply ONLY with valid JSON, no other text:
{
  "message": "your next message to the buyer (one short paragraph, respond to their last message)",
  "action": "continue" or "accept",
  "offer": { "price": number, "days": number } or null (required when countering; use your proposed price and completion days)
}
`;

  const res = await llm.invoke([new SystemMessage("Only output valid JSON"), new HumanMessage(prompt)]);
  const data = JSON.parse(res.content);

  const message = {
    role: "seller",
    message: data.message,
    offer: data.offer,
  };

  // Return only the new message; reducer will append to state.conversation (avoids duplication)
  return {
    conversation: [message],
    round: state.round + 1,
    status: data.action === "accept" ? "accepted" : "negotiating",
    finalDeal: data.action === "accept" ? data.offer : state.finalDeal,
  };
}

/** Resolve timeout: set final negotiated values from last offer. */
function resolveTimeoutNode(state) {
  return {
    status: "timeout",
    finalDeal: state.conversation.at(-1)?.offer ?? null
  };
}


function routeAfterBuyer(state) {
  if (state.status === "accepted") return "end";
  if (Date.now() > state.deadline_ts || state.round >= state.maxRounds) return "timeout";
  return "seller";
}

function routeAfterSeller(state) {
  if (state.status === "accepted") return "end";
  if (Date.now() > state.deadline_ts || state.round >= state.maxRounds) return "timeout";
  return "buyer";
}


function ensureNegotiatedOnAccept(state) {
  if (state.status !== 'accepted') return state;
  const last = state.lastOffer;
  return {
    negotiatedPrice: state.negotiatedPrice ?? getPriceFromOffer(last) ?? 0,
    negotiatedCompletionDays: state.negotiatedCompletionDays ?? getCompletionDaysFromOffer(last) ?? 7,
  };
}

const workflow = new StateGraph(NegotiationState)
  .addNode('buyer', buyerNode)
  .addNode('seller', sellerNode)
  .addNode('resolveTimeout', resolveTimeoutNode)
  .addEdge(START, 'buyer')
  .addConditionalEdges('buyer', routeAfterBuyer, { end: END, timeout: 'resolveTimeout', seller: 'seller' })
  .addConditionalEdges('seller', routeAfterSeller, { end: END, timeout: 'resolveTimeout', buyer: 'buyer' })
  .addEdge('resolveTimeout', END);

const compiledGraph = workflow.compile();

/**
 * Run negotiation for one job-provider pair.
 * @param {Object} input - { job, providerId, providerServiceData?, maxRounds?, deadline_ts? }
 * @returns {Promise<{ negotiatedPrice: number, negotiatedCompletionDays: number, status: string, providerId, job }>}
 */
export async function runNegotiation(input) {
  const result = await compiledGraph.invoke(getInitialState(input));

  return {
    job: result.job,
    providerId: result.providerId,
    status: result.status,
    negotiatedPrice: result.finalDeal?.price ?? null,
    negotiatedCompletionDays: result.finalDeal?.days ?? null,
    transcript: result.conversation
  };
}


/**
 * Run negotiation and stream each offer step via onStep(step).
 * step: { role: 'buyer'|'seller', round: number, action: 'counter'|'accept', price: number, completionDays: number }
 * @param {Object} input - same as runNegotiation
 * @param {function(object): void} onStep - called after each buyer/seller offer
 * @returns {Promise<{ negotiatedPrice, negotiatedCompletionDays, status, providerId, job }>}
 */
export async function runNegotiationStream(input, onStep) {
  const initialState = getInitialState(input);
  if (typeof onStep !== 'function') {
    return runNegotiation(input);
  }
  let lastResult = null;
  const stream = await compiledGraph.stream(initialState, { streamMode: 'values' });
  let emittedCount = 0;
  for await (const chunk of stream) {
    const state = Array.isArray(chunk) ? (chunk[1] ?? chunk[0]) : chunk;
    if (state && Array.isArray(state.conversation)) {
      while (emittedCount < state.conversation.length) {
        const msg = state.conversation[emittedCount];
        emittedCount += 1;
        const offer = msg.offer ?? {};
        const isLast = emittedCount === state.conversation.length;
        onStep({
          role: msg.role,
          round: state.round ?? emittedCount,
          action: state.status === 'accepted' && isLast ? 'accept' : 'counter',
          message: typeof msg.message === 'string' ? msg.message : '',
          price: typeof offer.price === 'number' ? offer.price : undefined,
          completionDays: typeof offer.days === 'number' ? offer.days : (typeof offer.completionDays === 'number' ? offer.completionDays : undefined),
        });
      }
    }
    lastResult = state;
  }
  const result = lastResult ?? (await compiledGraph.invoke(initialState));
  const resolved = result.status === 'timeout' ? resolveTimeoutNode(result) : ensureNegotiatedOnAccept(result);
  return {
    job: result.job,
    providerId: result.providerId,
    status: result.status,
    negotiatedPrice: resolved.negotiatedPrice ?? result.negotiatedPrice ?? 0,
    negotiatedCompletionDays: resolved.negotiatedCompletionDays ?? result.negotiatedCompletionDays ?? 7,
  };
}

export { compiledGraph as negotiationGraph, NegotiationState };
