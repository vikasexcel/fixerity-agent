import { Annotation, StateGraph, START, END } from '@langchain/langgraph';
import { ChatOpenAI } from '@langchain/openai';
import { HumanMessage, SystemMessage } from '@langchain/core/messages';
import { OPENAI_API_KEY } from '../config/index.js';
import { redisClient } from '../config/redis.js'; 
import memoryClient from '../memory/mem0.js';

/* -------------------- REDIS SESSION STORE (Short-term: Active Negotiations) -------------------- */

class NegotiationSessionStore {
  constructor(redis) {
    this.redis = redis;
    this.TTL = 3600; // 1 hour
  }

  async saveState(jobId, providerId, state) {
    const key = `negotiation:${jobId}:${providerId}:state`;
    await this.redis.setEx(
      key,
      this.TTL,
      JSON.stringify({
        ...state,
        updated_at: Date.now(),
      })
    );
  }

  async getState(jobId, providerId) {
    const key = `negotiation:${jobId}:${providerId}:state`;
    const data = await this.redis.get(key);
    return data ? JSON.parse(data) : null;
  }

  async appendMessage(jobId, providerId, message) {
    const key = `negotiation:${jobId}:${providerId}:messages`;
    await this.redis.lPush(key, JSON.stringify({
      ...message,
      timestamp: Date.now(),
    }));
    await this.redis.expire(key, this.TTL);
    
    // Keep only last 20 messages
    await this.redis.lTrim(key, 0, 19);
  }

  async getAllMessages(jobId, providerId) {
    const key = `negotiation:${jobId}:${providerId}:messages`;
    const messages = await this.redis.lRange(key, 0, -1);
    return messages.map(m => JSON.parse(m)).reverse();
  }

  async saveQuote(jobId, providerId, quote) {
    const key = `negotiation:${jobId}:${providerId}:quote`;
    await this.redis.setEx(key, this.TTL, JSON.stringify(quote));
  }

  async getQuote(jobId, providerId) {
    const key = `negotiation:${jobId}:${providerId}:quote`;
    const data = await this.redis.get(key);
    return data ? JSON.parse(data) : null;
  }

  async cleanup(jobId, providerId) {
    const pattern = `negotiation:${jobId}:${providerId}*`;
    const keys = await this.redis.keys(pattern);
    if (keys.length > 0) {
      await this.redis.del(...keys);
    }
  }

  async setStatus(jobId, providerId, status) {
    const key = `negotiation:${jobId}:${providerId}:status`;
    await this.redis.setEx(key, this.TTL, status);
  }

  async getStatus(jobId, providerId) {
    const key = `negotiation:${jobId}:${providerId}:status`;
    return await this.redis.get(key);
  }
}

/* -------------------- MEM0 SEMANTIC MEMORY (Long-term: Learning & Patterns) -------------------- */

class SemanticMemoryManager {
  constructor(mem0Client) {
    this.memory = mem0Client;
  }

  /**
   * 🧠 STORE: Buyer's negotiation for learning
   */
  async storeBuyerNegotiation(buyerId, jobId, negotiationData) {
    const { job, quote, providerId, conversation, outcome } = negotiationData;

    try {
      await this.memory.add({
        messages: [
          {
            role: "user",
            content: `I posted a ${job.title} job with budget $${job.budget?.min || '?'}-$${job.budget?.max || '?'}. 
                     Start: ${job.startDate || 'ASAP'}, End: ${job.endDate || 'flexible'}. 
                     Service category: ${job.service_category_id}.`
          },
          {
            role: "assistant",
            content: `Provider ${providerId} quoted $${quote?.price || '?'} for ${quote?.days || '?'} days. 
                     Payment: ${quote?.paymentSchedule || 'not specified'}. 
                     Can meet dates: ${quote?.can_meet_dates ? 'Yes' : 'No'}. 
                     Licensed: ${quote?.licensed ? 'Yes' : 'No'}. 
                     References: ${quote?.referencesAvailable ? 'Yes' : 'No'}.
                     Outcome: ${outcome}.`
          }
        ],
        user_id: `buyer_${buyerId}`,
        metadata: {
          type: 'negotiation',
          job_id: jobId,
          provider_id: providerId,
          service_category: job.service_category_id,
          final_price: quote?.price,
          quoted_days: quote?.days,
          outcome: outcome, // 'accepted', 'rejected', 'timeout', 'presented'
          timestamp: Date.now(),
          budget_min: job.budget?.min,
          budget_max: job.budget?.max,
          can_meet_dates: quote?.can_meet_dates,
          licensed: quote?.licensed,
        }
      });

      console.log(`[Mem0] ✅ Stored buyer ${buyerId} negotiation for job ${jobId}`);
    } catch (error) {
      console.error(`[Mem0] ❌ Error storing buyer memory:`, error.message);
    }
  }

  /**
   * 🧠 STORE: Provider's negotiation behavior
   */
  async storeProviderNegotiation(providerId, jobId, negotiationData) {
    const { job, quote, outcome, buyerId } = negotiationData;

    try {
      await this.memory.add({
        messages: [
          {
            role: "user",
            content: `Job request: ${job.title}. Budget offered: $${job.budget?.max || '?'}. 
                     Timeline: ${job.startDate || 'ASAP'} to ${job.endDate || 'flexible'}. 
                     Service category: ${job.service_category_id}.`
          },
          {
            role: "assistant",
            content: `I quoted $${quote?.price || '?'} for ${quote?.days || '?'} days. 
                     Payment terms: ${quote?.paymentSchedule || 'standard'}. 
                     Outcome: ${outcome}.`
          }
        ],
        user_id: `provider_${providerId}`,
        metadata: {
          type: 'negotiation',
          job_id: jobId,
          buyer_id: buyerId,
          service_category: job.service_category_id,
          quoted_price: quote?.price,
          quoted_days: quote?.days,
          budget_offered: job.budget?.max,
          outcome: outcome,
          timestamp: Date.now(),
          price_vs_budget_ratio: quote?.price / (job.budget?.max || 1),
        }
      });

      console.log(`[Mem0] ✅ Stored provider ${providerId} negotiation for job ${jobId}`);
    } catch (error) {
      console.error(`[Mem0] ❌ Error storing provider memory:`, error.message);
    }
  }

  /**
   * 🔍 RETRIEVE: Buyer's preferences and patterns
   */
  async getBuyerPreferences(buyerId, serviceCategory = null) {
    try {
      const query = serviceCategory 
        ? `What are buyer ${buyerId}'s preferences for service category ${serviceCategory}? 
           Include typical budget range, preferred provider qualities (rating, licensing), 
           timeline preferences, and payment terms they accept.`
        : `What are buyer ${buyerId}'s negotiation preferences and patterns across all services?`;

      const memories = await this.memory.search({
        query: query,
        user_id: `buyer_${buyerId}`,
        limit: 10
      });

      if (!memories || memories.length === 0) {
        console.log(`[Mem0] No buyer preferences found for ${buyerId}`);
        return null;
      }

      console.log(`[Mem0] Retrieved ${memories.length} buyer preferences`);
      return {
        memories: memories,
        summary: this.summarizeBuyerPreferences(memories)
      };
    } catch (error) {
      console.error(`[Mem0] Error fetching buyer preferences:`, error.message);
      return null;
    }
  }

  /**
   * 🔍 RETRIEVE: Provider's pricing patterns
   */
  async getProviderPattern(providerId, serviceCategory = null) {
    try {
      const query = serviceCategory
        ? `What are provider ${providerId}'s typical quotes, timelines, and acceptance rates 
           for service category ${serviceCategory}?`
        : `What are provider ${providerId}'s negotiation patterns, typical quotes, and success rates?`;

      const memories = await this.memory.search({
        query: query,
        user_id: `provider_${providerId}`,
        limit: 10
      });

      if (!memories || memories.length === 0) {
        console.log(`[Mem0] No provider patterns found for ${providerId}`);
        return null;
      }

      console.log(`[Mem0] Retrieved ${memories.length} provider patterns`);
      return {
        memories: memories,
        summary: this.summarizeProviderPattern(memories)
      };
    } catch (error) {
      console.error(`[Mem0] Error fetching provider pattern:`, error.message);
      return null;
    }
  }

  /**
   * 🎯 RETRIEVE: Smart recommendations for current job
   */
  async getJobRecommendations(buyerId, job) {
    try {
      const memories = await this.memory.search({
        query: `Based on buyer ${buyerId}'s past negotiations for ${job.title} 
                (service category ${job.service_category_id}), what budget range, 
                timeline flexibility, and provider qualities should they prioritize? 
                What typically leads to successful outcomes?`,
        user_id: `buyer_${buyerId}`,
        limit: 15
      });

      if (!memories || memories.length === 0) {
        console.log(`[Mem0] No recommendations found for buyer ${buyerId}`);
        return null;
      }

      console.log(`[Mem0] Retrieved ${memories.length} recommendations`);
      return {
        memories: memories,
        recommendations: this.extractRecommendations(memories, job)
      };
    } catch (error) {
      console.error(`[Mem0] Error getting recommendations:`, error.message);
      return null;
    }
  }

  /* -------------------- PARSING HELPERS -------------------- */

  summarizeBuyerPreferences(memories) {
    // Extract common patterns from memories
    // Mem0 already does semantic analysis, so we just structure it
    return {
      total_negotiations: memories.length,
      top_insights: memories.slice(0, 3).map(m => ({
        text: m.memory || m.text || m.content,
        relevance: m.score || 0
      }))
    };
  }

  summarizeProviderPattern(memories) {
    return {
      total_quotes: memories.length,
      top_patterns: memories.slice(0, 3).map(m => ({
        text: m.memory || m.text || m.content,
        relevance: m.score || 0
      }))
    };
  }

  extractRecommendations(memories, job) {
    return {
      based_on_negotiations: memories.length,
      confidence: memories.length > 5 ? 'high' : memories.length > 2 ? 'medium' : 'low',
      key_insights: memories.slice(0, 5).map(m => ({
        insight: m.memory || m.text || m.content,
        relevance: m.score || 0
      }))
    };
  }
}

// Initialize stores
const sessionStore = new NegotiationSessionStore(redisClient);
const semanticMemory = new SemanticMemoryManager(memoryClient);

/* -------------------- STATE -------------------- */

const MatchingState = Annotation.Root({
  job: Annotation(),
  providerId: Annotation(),
  providerServiceData: Annotation(),
  round: Annotation(),
  maxRounds: Annotation(),
  deadline_ts: Annotation(),
  buyerId: Annotation(), // ✅ For Mem0 learning
  conversation: Annotation({
    reducer: (a, b) => {
      const combined = a.concat(b);
      return combined.slice(-20); // Limit to prevent memory bloat
    },
    default: () => [],
  }),
  status: Annotation(), // collecting | done | timeout
  collectedQuote: Annotation(),
  // ✅ Mem0 learned data (optional, for context)
  buyerPreferences: Annotation(),
  providerPattern: Annotation(),
});

async function getInitialState(input) {
  const jobId = input.job.id;
  const providerId = input.providerId;
  const buyerId = input.buyerId;

  // Try to restore from Redis if exists
  const cached = await sessionStore.getState(jobId, providerId);
  if (cached) {
    console.log(`[Redis] Restored state for job ${jobId}, provider ${providerId}`);
    return cached;
  }

  // 🧠 OPTIONAL: Retrieve learned patterns (for context in prompts)
  let buyerPreferences = null;
  let providerPattern = null;

  if (buyerId && input.useMem0Learning !== false) {
    buyerPreferences = await semanticMemory.getBuyerPreferences(
      buyerId,
      input.job.service_category_id
    );
    
    providerPattern = await semanticMemory.getProviderPattern(
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
  };

  // Save to Redis
  await sessionStore.saveState(jobId, providerId, initialState);
  console.log(`[Redis] Created new state for job ${jobId}, provider ${providerId}`);

  return initialState;
}

/* -------------------- HELPERS -------------------- */

function formatConversation(conversation = []) {
  if (!conversation.length) return 'No messages yet.';
  return conversation
    .map((m) => `${m.role.toUpperCase()}: ${m.message}`)
    .join('\n');
}

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
    await sessionStore.setStatus(state.job.id, state.providerId, 'done');
    return { status: 'done' };
  }

  // Check if buyer message already sent (idempotency via Redis)
  const existingMessages = await sessionStore.getAllMessages(state.job.id, state.providerId);
  if (existingMessages.some(m => m.role === 'buyer')) {
    console.log(`[Redis] Buyer message already exists, skipping`);
    return state;
  }

  const llm = new ChatOpenAI({
    model: 'gpt-4o-mini',
    temperature: 0.7,
    openAIApiKey: OPENAI_API_KEY,
  });

  const startDate = state.job.startDate || 'ASAP';
  const endDate = state.job.endDate || 'flexible';
  const jobTitle = state.job.title || 'service job';

  // 🧠 OPTIONAL: Use buyer preferences in prompt (if available)
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

  const message = {
    role: 'buyer',
    message: data.message,
  };

  // Save to Redis
  await sessionStore.appendMessage(state.job.id, state.providerId, message);

  // Update state in Redis
  const updatedState = {
    ...state,
    conversation: state.conversation.concat([message]),
    round: state.round + 1,
  };
  await sessionStore.saveState(state.job.id, state.providerId, updatedState);

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

  // 🧠 OPTIONAL: Use provider patterns in prompt (if available)
  let patternContext = '';
  if (state.providerPattern?.summary?.top_patterns) {
    const patterns = state.providerPattern.summary.top_patterns
      .map(p => p.text)
      .join('; ');
    patternContext = `\n\nYour Past Behavior: ${patterns}`;
  }

  const prompt = `
You are a PROFESSIONAL SERVICE PROVIDER responding to a quote request.

Your Background:
- Rating: ${rating}/5 (from ${jobsCompleted} completed jobs)
- Licensed: ${isLicensed ? 'Yes' : 'No'}
- References: ${hasReferences ? 'Available' : 'Not available'}
- Your typical completion time for this service: ${providerDays} day(s)
${patternContext}

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

  // Save to Redis
  await sessionStore.appendMessage(state.job.id, state.providerId, message);
  await sessionStore.saveQuote(state.job.id, state.providerId, data.quote);
  await sessionStore.setStatus(state.job.id, state.providerId, 'done');

  // Update state in Redis
  const updatedState = {
    ...state,
    conversation: state.conversation.concat([message]),
    collectedQuote: data.quote,
    status: 'done',
  };
  await sessionStore.saveState(state.job.id, state.providerId, updatedState);

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
  await sessionStore.setStatus(state.job.id, state.providerId, 'timeout');
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
  
  // Retrieve from Redis for complete data
  const jobId = input.job.id;
  const providerId = input.providerId;
  
  const allMessages = await sessionStore.getAllMessages(jobId, providerId);
  const savedQuote = await sessionStore.getQuote(jobId, providerId);
  const status = await sessionStore.getStatus(jobId, providerId);
  
  return {
    status: status || finalState.status,
    quote: savedQuote || finalState.collectedQuote,
    transcript: allMessages.length > 0 ? allMessages : finalState.conversation,
    buyerId: input.buyerId, // Pass through for Mem0 storage
  };
}

export const matchingGraph = compiled;
export { sessionStore, semanticMemory };