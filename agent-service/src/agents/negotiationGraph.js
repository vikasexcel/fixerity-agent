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
  lastOffer: Annotation(),
  offerHistory: Annotation({
    reducer: (a, b) => (Array.isArray(b) ? a.concat(b) : a.concat([b])),
    default: () => [],
  }),
  status: Annotation(),
  negotiatedPrice: Annotation(),
  negotiatedCompletionDays: Annotation(),
});

function getInitialState(input) {
  return {
    job: input.job,
    providerId: input.providerId,
    providerServiceData: input.providerServiceData ?? {},
    round: 0,
    maxRounds: input.maxRounds ?? 5,
    deadline_ts: input.deadline_ts ?? Date.now() + 60_000,
    lastOffer: null,
    offerHistory: [],
    status: 'negotiating',
    negotiatedPrice: null,
    negotiatedCompletionDays: null,
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

/** Buyer node: makes first offer or counters seller's offer. */
async function buyerNode(state) {
  const { job, providerServiceData, lastOffer, round, maxRounds, deadline_ts } = state;
  if (state.status !== 'negotiating') return state;

  const llm = new ChatOpenAI({
    model: 'gpt-4o-mini',
    temperature: 0.2,
    openAIApiKey: OPENAI_API_KEY,
  });

  const budget = job?.budget ?? { min: 0, max: 999999 };
  const budgetMin = Number(budget.min) ?? 0;
  const budgetMax = Number(budget.max) ?? 999999;
  const midBudget = Math.round((budgetMin + budgetMax) / 2);
  const providerMin = Number(providerServiceData?.min_price) ?? 0;
  const providerMax = Number(providerServiceData?.max_price) ?? 999999;
  const providerDays = Number(providerServiceData?.deadline_in_days) ?? 7;

  const isFirstTurn = !lastOffer || round === 0;
  let prompt;
  if (isFirstTurn) {
    prompt = `You are the buyer (job poster) negotiating with a provider for this job:
${JSON.stringify({ title: job?.title, budget: job?.budget, startDate: job?.startDate, endDate: job?.endDate }, null, 2)}

Provider's range: min_price ${providerMin}, max_price ${providerMax}, deadline_in_days ${providerDays}.
Make your first offer. Reply with ONLY a JSON object: { "action": "counter" or "accept", "price": number, "completionDays": number }.
Price must be between ${budgetMin} and ${budgetMax}. If the provider's range is acceptable to you, you may "accept" with a price and completionDays within range.`;
  } else if (lastOffer.role === 'seller') {
    const sellerPrice = lastOffer.price;
    const sellerDays = lastOffer.completionDays;
    prompt = `You are the buyer. The seller countered with price ${sellerPrice} and completionDays ${sellerDays}.
Your job budget: ${budgetMin}-${budgetMax}. Provider accepts ${providerMin}-${providerMax} and up to ${providerDays} days.
Reply with ONLY a JSON object: { "action": "counter" or "accept", "price": number, "completionDays": number }. If you accept the seller's terms, use "action": "accept" and their price and completionDays.`;
  } else {
    return { ...state, round: state.round + 1, lastOffer: state.lastOffer, offerHistory: state.offerHistory };
  }

  const response = await llm.invoke([
    new SystemMessage('You output only valid JSON with keys action, price, completionDays. No markdown.'),
    new HumanMessage(prompt),
  ]);
  const content = response?.content;
  const parsed = parseOfferResponse(content);
  const price = parsed.price ?? (isFirstTurn ? midBudget : getPriceFromOffer(lastOffer));
  const completionDays = parsed.completionDays ?? (isFirstTurn ? Math.min(7, providerDays) : getCompletionDaysFromOffer(lastOffer));

  const newOffer = {
    role: 'buyer',
    action: parsed.action,
    price: Number(price),
    completionDays: Number(completionDays),
  };
  const status = parsed.action === 'accept' ? 'accepted' : state.status;
  const negotiatedPrice = parsed.action === 'accept' ? Number(price) : state.negotiatedPrice;
  const negotiatedCompletionDays = parsed.action === 'accept' ? Number(completionDays) : state.negotiatedCompletionDays;

  return {
    round: state.round + 1,
    lastOffer: newOffer,
    offerHistory: [...(state.offerHistory || []), newOffer],
    status,
    ...(negotiatedPrice != null && { negotiatedPrice }),
    ...(negotiatedCompletionDays != null && { negotiatedCompletionDays }),
  };
}

/** Seller node: counters buyer's offer. */
async function sellerNode(state) {
  const { job, providerServiceData, lastOffer, round, maxRounds, deadline_ts } = state;
  if (state.status !== 'negotiating') return state;
  if (!lastOffer || lastOffer.role !== 'buyer') return state;

  const llm = new ChatOpenAI({
    model: 'gpt-4o-mini',
    temperature: 0.2,
    openAIApiKey: OPENAI_API_KEY,
  });

  const budget = job?.budget ?? { min: 0, max: 999999 };
  const providerMin = Number(providerServiceData?.min_price) ?? 0;
  const providerMax = Number(providerServiceData?.max_price) ?? 999999;
  const providerDays = Number(providerServiceData?.deadline_in_days) ?? 7;
  const buyerPrice = lastOffer.price;
  const buyerDays = lastOffer.completionDays;

  const prompt = `You are the provider (seller). The buyer offered price ${buyerPrice} and completionDays ${buyerDays}.
Your acceptable range: min_price ${providerMin}, max_price ${providerMax}, deadline_in_days ${providerDays}.
Job budget: ${JSON.stringify(budget)}.
Reply with ONLY a JSON object: { "action": "counter" or "accept", "price": number, "completionDays": number }. If you accept the buyer's terms, use "action": "accept" and their price and completionDays.`;

  const response = await llm.invoke([
    new SystemMessage('You output only valid JSON with keys action, price, completionDays. No markdown.'),
    new HumanMessage(prompt),
  ]);
  const parsed = parseOfferResponse(response?.content);
  const price = parsed.price ?? buyerPrice;
  const completionDays = parsed.completionDays ?? buyerDays;

  const newOffer = {
    role: 'seller',
    action: parsed.action,
    price: Number(price),
    completionDays: Number(completionDays),
  };
  const status = parsed.action === 'accept' ? 'accepted' : state.status;
  const negotiatedPrice = parsed.action === 'accept' ? Number(price) : state.negotiatedPrice;
  const negotiatedCompletionDays = parsed.action === 'accept' ? Number(completionDays) : state.negotiatedCompletionDays;

  return {
    round: state.round + 1,
    lastOffer: newOffer,
    offerHistory: [...(state.offerHistory || []), newOffer],
    status,
    ...(negotiatedPrice != null && { negotiatedPrice }),
    ...(negotiatedCompletionDays != null && { negotiatedCompletionDays }),
  };
}

/** Resolve timeout: set final negotiated values from last offer. */
function resolveTimeoutNode(state) {
  const last = state.lastOffer;
  const price = getPriceFromOffer(last);
  const days = getCompletionDaysFromOffer(last);
  return {
    status: 'timeout',
    negotiatedPrice: price ?? state.negotiatedPrice ?? 0,
    negotiatedCompletionDays: days ?? state.negotiatedCompletionDays ?? 7,
  };
}

function routeAfterBuyer(state) {
  if (state.status === 'accepted') return 'end';
  const now = Date.now();
  if (now >= state.deadline_ts || state.round >= state.maxRounds) return 'timeout';
  return 'seller';
}

function routeAfterSeller(state) {
  if (state.status === 'accepted') return 'end';
  const now = Date.now();
  if (now >= state.deadline_ts || state.round >= state.maxRounds) return 'timeout';
  return 'buyer';
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
  const initialState = getInitialState(input);
  const result = await compiledGraph.invoke(initialState);
  const accepted = result.status === 'accepted';
  const resolved = result.status === 'timeout' ? resolveTimeoutNode(result) : ensureNegotiatedOnAccept(result);
  return {
    job: result.job,
    providerId: result.providerId,
    status: result.status,
    negotiatedPrice: resolved.negotiatedPrice ?? result.negotiatedPrice ?? 0,
    negotiatedCompletionDays: resolved.negotiatedCompletionDays ?? result.negotiatedCompletionDays ?? 7,
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
  let lastEmittedKey = null;
  for await (const chunk of stream) {
    const state = Array.isArray(chunk) ? (chunk[1] ?? chunk[0]) : chunk;
    if (state && state.lastOffer) {
      const key = `${state.round}-${state.lastOffer.role}`;
      if (key !== lastEmittedKey) {
        lastEmittedKey = key;
        const offer = state.lastOffer;
        onStep({
          role: offer.role,
          round: state.round ?? 0,
          action: offer.action ?? 'counter',
          price: offer.price,
          completionDays: offer.completionDays,
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
