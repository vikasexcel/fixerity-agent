import { Annotation, StateGraph, START, END, Command } from '@langchain/langgraph';
import { ChatOpenAI } from '@langchain/openai';
import { HumanMessage, SystemMessage } from '@langchain/core/messages';
import { OPENAI_API_KEY } from '../config/index.js';
import { redisClient } from '../config/redis.js';
import { MemorySaver } from '@langchain/langgraph';

/* ================================================================================
   CONVERSATION GRAPH - Job Creation Through Natural Conversation
   ================================================================================
   Updated with Human-in-the-Loop using interrupt() for job confirmation
   ================================================================================ */

/* -------------------- REDIS CONVERSATION STORE -------------------- */

class ConversationSessionStore {
  constructor(redis) {
    this.redis = redis;
    this.TTL = 7200; // 2 hours
  }

  async saveSession(sessionId, state) {
    const key = `conversation:${sessionId}:state`;
    await this.redis.setEx(
      key,
      this.TTL,
      JSON.stringify({
        ...state,
        updated_at: Date.now(),
      })
    );
  }

  async getSession(sessionId) {
    const key = `conversation:${sessionId}:state`;
    const data = await this.redis.get(key);
    return data ? JSON.parse(data) : null;
  }

  async appendMessage(sessionId, message) {
    const key = `conversation:${sessionId}:messages`;
    await this.redis.rPush(key, JSON.stringify({
      ...message,
      timestamp: Date.now(),
    }));
    await this.redis.expire(key, this.TTL);
    await this.redis.lTrim(key, -50, -1);
  }

  async getMessages(sessionId) {
    const key = `conversation:${sessionId}:messages`;
    const messages = await this.redis.lRange(key, 0, -1);
    return messages.map(m => JSON.parse(m));
  }

  async setPhase(sessionId, phase) {
    const key = `conversation:${sessionId}:phase`;
    await this.redis.setEx(key, this.TTL, phase);
  }

  async getPhase(sessionId) {
    const key = `conversation:${sessionId}:phase`;
    return await this.redis.get(key) || 'conversation';
  }

  async cleanup(sessionId) {
    const pattern = `conversation:${sessionId}:*`;
    const keys = await this.redis.keys(pattern);
    if (keys.length > 0) {
      await this.redis.del(...keys);
    }
  }
}

export const conversationStore = new ConversationSessionStore(redisClient);

/* -------------------- SERVICE CATEGORIES CACHE & TOOL -------------------- */

class ServiceCategoryManager {
  constructor(redis) {
    this.redis = redis;
    this.TTL = 86400; // 24 hours
    this.CACHE_KEY = 'service_categories:all';
  }

  async fetchFromAPI(userId, accessToken) {
    try {
      const response = await fetch('http://localhost:8000/api/customer/home', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          user_id: userId,
          access_token: accessToken,
          app_version: '1.0'
        })
      });

      const data = await response.json();
      if (data.status === 1 && data.services) {
        await this.cacheCategories(data.services);
        return data.services;
      }
      return null;
    } catch (error) {
      console.error('[ServiceCategory] API fetch error:', error.message);
      return null;
    }
  }

  async cacheCategories(services) {
    await this.redis.setEx(
      this.CACHE_KEY,
      this.TTL,
      JSON.stringify(services)
    );
    console.log(`[Redis] Cached ${services.length} service categories`);
  }

  async getCategories() {
    const cached = await this.redis.get(this.CACHE_KEY);
    return cached ? JSON.parse(cached) : null;
  }

  async getCategoriesOrFetch(userId, accessToken) {
    let categories = await this.getCategories();
    if (!categories) {
      categories = await this.fetchFromAPI(userId, accessToken);
    }
    return categories || [];
  }

  async findCategory(userInput, categories, llm) {
    if (!categories || categories.length === 0) {
      return null;
    }

    const categoryList = categories.map(c => 
      `- ID: ${c.service_category_id}, Name: "${c.service_category_name}"`
    ).join('\n');

    const prompt = `
You are a service category matcher. Given a user's request, find the BEST matching service category.

Available categories:
${categoryList}

User's request: "${userInput}"

Instructions:
1. Find the category that BEST matches what the user is looking for
2. Consider synonyms and related terms (e.g., "house cleaning" = "Home Cleaning", "plumber" = "Plumbers")
3. If no category matches well, return null

Reply ONLY with JSON:
{
  "matched": true/false,
  "category_id": <number or null>,
  "category_name": "<string or null>",
  "confidence": "<high/medium/low>",
  "reason": "<brief explanation>"
}
`;

    try {
      const res = await llm.invoke([
        new SystemMessage('Only output valid JSON. Be accurate in matching.'),
        new HumanMessage(prompt),
      ]);

      let content = res.content.trim();
      content = content.replace(/```json\n?/g, '').replace(/```\n?/g, '');
      return JSON.parse(content);
    } catch (error) {
      console.error('[ServiceCategory] LLM matching error:', error.message);
      return null;
    }
  }
}

export const serviceCategoryManager = new ServiceCategoryManager(redisClient);

/* -------------------- STATE DEFINITION -------------------- */

const ConversationState = Annotation.Root({
  sessionId: Annotation(),
  buyerId: Annotation(),
  accessToken: Annotation(),
  
  phase: Annotation(), // 'conversation' | 'confirmation' | 'complete'
  
  currentMessage: Annotation(),
  
  messages: Annotation({
    reducer: (a, b) => {
      const combined = a.concat(b);
      return combined.slice(-30);
    },
    default: () => [],
  }),
  
  collected: Annotation({
    reducer: (existing, updates) => ({
      ...existing,
      ...updates,
    }),
    default: () => ({
      service_category_id: null,
      service_category_name: null,
      title: null,
      description: null,
      budget: { min: null, max: null },
      startDate: null,
      endDate: null,
      priorities: [],
      location: null,
    }),
  }),
  
  // Categorize missing fields
  requiredMissing: Annotation({
    reducer: (_, b) => b,
    default: () => [],
  }),
  
  optionalMissing: Annotation({
    reducer: (_, b) => b,
    default: () => [],
  }),
  
  intent: Annotation(),
  extraction: Annotation(),
  response: Annotation(),
  
  // New: Job readiness levels
  jobReadiness: Annotation(), // 'incomplete' | 'minimum' | 'complete'
  
  job: Annotation(),
  serviceCategories: Annotation(),
  error: Annotation(),
});

/* -------------------- INTENT DETECTION NODE -------------------- */

async function detectIntentNode(state) {
  const llm = new ChatOpenAI({
    model: 'gpt-4o-mini',
    temperature: 0,
    openAIApiKey: OPENAI_API_KEY,
  });

  const recentMessages = state.messages.slice(-5).map(m => 
    `${m.role.toUpperCase()}: ${m.content}`
  ).join('\n');

  const prompt = `
You are an intent classifier for a service marketplace app.

Conversation context:
${recentMessages || 'No previous messages'}

Current user message: "${state.currentMessage}"

Current collected data:
- Service: ${state.collected.service_category_name || 'not set'}
- Budget: ${state.collected.budget?.max ? '$' + state.collected.budget.max : 'not set'}
- Start date: ${state.collected.startDate || 'not set'}
- End date: ${state.collected.endDate || 'not set'}
- Title: ${state.collected.title || 'not set'}
- Description: ${state.collected.description || 'not set'}
- Location: ${state.collected.location || 'not set'}

Classify the intent as ONE of:
1. "create_job" - User wants to find a service provider / create a job request
2. "provide_info" - User is answering a question / providing job details
3. "ask_question" - User is asking about something (services, how it works, etc.)
4. "modify_info" - User wants to change previously provided information
5. "confirm" - User is confirming/agreeing to proceed (yes, looks good, proceed, find providers)
6. "add_more_info" - User wants to add more details before proceeding
7. "cancel" - User wants to cancel or start over
8. "other" - Doesn't fit any category

Also extract any service-related keywords from the message.

Reply ONLY with JSON:
{
  "intent": "<one of the intents above>",
  "service_keywords": ["<any service-related words mentioned>"],
  "has_budget_mention": true/false,
  "has_date_mention": true/false,
  "has_location_mention": true/false,
  "sentiment": "positive/neutral/negative",
  "confidence": "high/medium/low"
}
`;

  try {
    const res = await llm.invoke([
      new SystemMessage('Only output valid JSON.'),
      new HumanMessage(prompt),
    ]);

    let content = res.content.trim();
    content = content.replace(/```json\n?/g, '').replace(/```\n?/g, '');
    const intent = JSON.parse(content);

    console.log(`[Intent] Detected: ${intent.intent} (${intent.confidence})`);
    return { intent };
  } catch (error) {
    console.error('[Intent] Detection error:', error.message);
    return { 
      intent: { 
        intent: 'provide_info', 
        confidence: 'low',
        service_keywords: [],
        has_budget_mention: false,
        has_date_mention: false,
        has_location_mention: false,
      } 
    };
  }
}

/* -------------------- INFORMATION EXTRACTION NODE -------------------- */

async function extractInfoNode(state) {
  const llm = new ChatOpenAI({
    model: 'gpt-4o-mini',
    temperature: 0,
    openAIApiKey: OPENAI_API_KEY,
  });

  let categories = state.serviceCategories;
  if (!categories || categories.length === 0) {
    categories = await serviceCategoryManager.getCategoriesOrFetch(
      state.buyerId,
      state.accessToken
    );
  }

  const categoryList = categories?.map(c => c.service_category_name).join(', ') || 'Loading...';

  const prompt = `
You are an information extractor for a service marketplace.

User message: "${state.currentMessage}"

Currently collected:
${JSON.stringify(state.collected, null, 2)}

Available service categories: ${categoryList}

Extract ANY new information from the user's message. Be thorough but accurate.

Instructions:
1. For service type: Match to one of the available categories if possible
2. For title: Extract if user mentions a specific job title
3. For description: Extract any detailed description of the work needed
4. For budget: Extract numbers mentioned (e.g., "around 200" → max: 200, "100-200" → min: 100, max: 200)
5. For dates: Parse natural language (e.g., "this Saturday", "next week", "ASAP", "within 3 days")
6. For location: Extract any address or location mentioned
7. For preferences: Extract any mentioned requirements (licensed, references, rating, etc.)

Today's date: ${new Date().toISOString().split('T')[0]}

Reply ONLY with JSON:
{
  "extracted": {
    "service_category_name": "<matched category name or null>",
    "title": "<job title if mentioned or null>",
    "description": "<any description details or null>",
    "budget": {
      "min": <number or null>,
      "max": <number or null>
    },
    "startDate": "<YYYY-MM-DD or 'ASAP' or null>",
    "endDate": "<YYYY-MM-DD or 'flexible' or null>",
    "location": "<location/address if mentioned or null>",
    "priorities": [
      {
        "type": "<price|rating|licensed|references|startDate|endDate>",
        "level": "<must_have|nice_to_have|bonus>",
        "value": "<extracted value>"
      }
    ]
  },
  "found_new_info": true/false,
  "needs_clarification": "<any unclear parts that need clarification or null>"
}
`;

  try {
    const res = await llm.invoke([
      new SystemMessage('Only output valid JSON. Extract accurately.'),
      new HumanMessage(prompt),
    ]);

    let content = res.content.trim();
    content = content.replace(/```json\n?/g, '').replace(/```\n?/g, '');
    const extraction = JSON.parse(content);

    console.log(`[Extraction] Found new info: ${extraction.found_new_info}`);

    if (extraction.extracted.service_category_name && categories) {
      const matchResult = await serviceCategoryManager.findCategory(
        extraction.extracted.service_category_name,
        categories,
        llm
      );
      
      if (matchResult?.matched && matchResult.category_id) {
        extraction.extracted.service_category_id = matchResult.category_id;
        extraction.extracted.service_category_name = matchResult.category_name;
        console.log(`[Extraction] Matched category: ${matchResult.category_name} (ID: ${matchResult.category_id})`);
      }
    }

    return { 
      extraction,
      serviceCategories: categories,
    };
  } catch (error) {
    console.error('[Extraction] Error:', error.message);
    return { 
      extraction: { 
        extracted: {}, 
        found_new_info: false,
        needs_clarification: null,
      } 
    };
  }
}

/* -------------------- UPDATE COLLECTED DATA NODE -------------------- */

function updateCollectedNode(state) {
  if (!state.extraction?.found_new_info) {
    return {};
  }

  const extracted = state.extraction.extracted;
  const updates = {};

  if (extracted.service_category_id) {
    updates.service_category_id = extracted.service_category_id;
  }
  if (extracted.service_category_name) {
    updates.service_category_name = extracted.service_category_name;
  }
  if (extracted.title) {
    updates.title = extracted.title;
  }
  if (extracted.description) {
    updates.description = extracted.description;
  }
  if (extracted.budget?.min !== null || extracted.budget?.max !== null) {
    updates.budget = {
      min: extracted.budget?.min ?? state.collected.budget?.min,
      max: extracted.budget?.max ?? state.collected.budget?.max,
    };
  }
  if (extracted.startDate) {
    updates.startDate = extracted.startDate;
  }
  if (extracted.endDate) {
    updates.endDate = extracted.endDate;
  }
  if (extracted.location) {
    updates.location = extracted.location;
  }
  if (extracted.priorities?.length > 0) {
    const existingPriorities = state.collected.priorities || [];
    const newPriorities = [...existingPriorities];
    
    for (const p of extracted.priorities) {
      const existingIndex = newPriorities.findIndex(ep => ep.type === p.type);
      if (existingIndex >= 0) {
        newPriorities[existingIndex] = p;
      } else {
        newPriorities.push(p);
      }
    }
    updates.priorities = newPriorities;
  }

  return { collected: updates };
}

/* -------------------- CHECK COMPLETENESS NODE (UPDATED) -------------------- */

function checkCompletenessNode(state) {
  const collected = state.collected;
  const required = [];
  const optional = [];

  // REQUIRED FIELDS - Must have these to search
  if (!collected.service_category_id) {
    required.push('service_category');
  }
  if (!collected.budget?.max) {
    required.push('budget_max');
  }
  if (!collected.startDate) {
    required.push('start_date');
  }
  if (!collected.location) {
    required.push('location');
  }

  // OPTIONAL FIELDS - Nice to have for better matching
  if (!collected.title) {
    optional.push('title');
  }
  if (!collected.description) {
    optional.push('description');
  }
  if (!collected.budget?.min) {
    optional.push('budget_min');
  }
  if (!collected.endDate) {
    optional.push('end_date');
  }

  // Determine readiness level
  let jobReadiness = 'incomplete';
  
  if (required.length === 0 && optional.length > 0) {
    jobReadiness = 'minimum'; // Has all required, missing some optional
  } else if (required.length === 0 && optional.length === 0) {
    jobReadiness = 'complete'; // Has everything
  }

  console.log(`[Completeness] Readiness: ${jobReadiness}`);
  console.log(`[Completeness] Required missing: ${required.join(', ') || 'none'}`);
  console.log(`[Completeness] Optional missing: ${optional.join(', ') || 'none'}`);

  return { 
    requiredMissing: required,
    optionalMissing: optional,
    jobReadiness,
  };
}

/* -------------------- GENERATE RESPONSE NODE (UPDATED) -------------------- */

async function generateResponseNode(state) {
  const llm = new ChatOpenAI({
    model: 'gpt-4o-mini',
    temperature: 0.7,
    openAIApiKey: OPENAI_API_KEY,
  });

  const recentMessages = state.messages.slice(-5).map(m => 
    `${m.role.toUpperCase()}: ${m.content}`
  ).join('\n');

  let responseGoal = '';
  
  if (state.intent?.intent === 'cancel') {
    responseGoal = 'Acknowledge cancellation and offer to start fresh';
  } else if (state.intent?.intent === 'ask_question') {
    responseGoal = 'Answer their question helpfully, then guide back to job creation if appropriate';
  } else if (state.jobReadiness === 'minimum' || state.jobReadiness === 'complete') {
    // Job has minimum requirements, prepare for confirmation
    responseGoal = 'Present a summary of what will be searched and ask if they want to add more details or proceed now';
  } else if (state.requiredMissing.includes('service_category')) {
    responseGoal = 'Ask what type of service they need (be helpful, suggest categories if they seem unsure)';
  } else if (state.requiredMissing.includes('budget_max')) {
    responseGoal = 'Ask about their budget for this service';
  } else if (state.requiredMissing.includes('start_date')) {
    responseGoal = 'Ask when they need this service to start';
  } else if (state.requiredMissing.includes('location')) {
    responseGoal = 'Ask for the location/address where the service is needed';
  } else {
    responseGoal = 'Ask if they have any other details to add';
  }

  const availableCategories = state.serviceCategories?.slice(0, 10)
    .map(c => c.service_category_name).join(', ') || '';

  const prompt = `
You are a friendly assistant helping someone find a service provider.

Conversation so far:
${recentMessages || 'This is the start of the conversation'}

User's latest message: "${state.currentMessage}"

What we know so far:
- Service: ${state.collected.service_category_name || 'not specified yet'}
- Budget: ${state.collected.budget?.max ? '$' + (state.collected.budget.min ? state.collected.budget.min + '-' : '') + state.collected.budget.max : 'not specified yet'}
- Start date: ${state.collected.startDate || 'not specified yet'}
- End date: ${state.collected.endDate || 'not specified'}
- Location: ${state.collected.location || 'not specified yet'}
- Title: ${state.collected.title || 'not specified'}
- Description: ${state.collected.description || 'not specified'}

Job readiness: ${state.jobReadiness}
Required fields still needed: ${state.requiredMissing.join(', ') || 'none'}
Optional fields missing: ${state.optionalMissing.join(', ') || 'none'}

Your goal: ${responseGoal}

Some available services: ${availableCategories}...

Instructions:
- Be conversational and friendly (like texting a helpful friend)
- Keep responses SHORT (2-3 sentences max)
- Don't be robotic or formal
- Use contractions (I'm, you're, what's)
- Only ask ONE question at a time
- If job readiness is 'minimum' or 'complete', summarize what you'll search for and ask if they want to proceed or add more details

Reply ONLY with JSON:
{
  "message": "<your friendly response>",
  "action": "<asking_service|asking_budget|asking_dates|asking_location|asking_optional|ready_for_confirmation|answering>"
}
`;

  try {
    const res = await llm.invoke([
      new SystemMessage('Only output valid JSON. Be conversational and brief.'),
      new HumanMessage(prompt),
    ]);

    let content = res.content.trim();
    content = content.replace(/```json\n?/g, '').replace(/```\n?/g, '');
    const response = JSON.parse(content);

    console.log(`[Response] Action: ${response.action}`);
    return { response };
  } catch (error) {
    console.error('[Response] Generation error:', error.message);
    return { 
      response: { 
        message: "I'd love to help you find a service provider! What type of service are you looking for?",
        action: 'asking_service'
      } 
    };
  }
}

/* -------------------- CONFIRMATION NODE (NEW - HUMAN-IN-THE-LOOP) -------------------- */

async function confirmationNode(state, config) {
  // This node uses interrupt() to pause and wait for human confirmation
  const collected = state.collected;
  
  // Build a preview summary
  const summary = {
    service: collected.service_category_name,
    budget: collected.budget?.max 
      ? `$${collected.budget.min || '?'}-$${collected.budget.max}`
      : 'Not specified',
    startDate: collected.startDate || 'ASAP',
    endDate: collected.endDate || 'Flexible',
    location: collected.location || 'Not specified',
    title: collected.title || `${collected.service_category_name} Service`,
    description: collected.description || 'No detailed description',
  };

  console.log('[Confirmation] Showing job preview to user');
  
  // Generate confirmation message
  const llm = new ChatOpenAI({
    model: 'gpt-4o-mini',
    temperature: 0.7,
    openAIApiKey: OPENAI_API_KEY,
  });

  const prompt = `
You are helping a user confirm their job details before searching for providers.

Job Summary:
${JSON.stringify(summary, null, 2)}

Optional fields that could be added:
${state.optionalMissing.join(', ') || 'All fields are complete!'}

Generate a friendly confirmation message that:
1. Summarizes what they're looking for
2. Lists the key details
3. Mentions any optional fields they could add
4. Asks if they want to proceed, add more details, or make changes

Keep it conversational and friendly. Use 3-4 sentences max.

Reply ONLY with JSON:
{
  "message": "<your friendly confirmation message>"
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

    // Store the confirmation message
    return {
      response: {
        message: response.message,
        action: 'awaiting_confirmation',
      },
      phase: 'confirmation',
    };
  } catch (error) {
    console.error('[Confirmation] Error generating message:', error.message);
    return {
      response: {
        message: `Great! I've got your details for ${summary.service} with a budget of ${summary.budget}, starting ${summary.startDate} at ${summary.location}. Ready to find providers, or want to add more details?`,
        action: 'awaiting_confirmation',
      },
      phase: 'confirmation',
    };
  }
}

/* -------------------- BUILD JOB NODE (UPDATED) -------------------- */

function buildJobNode(state) {
  const collected = state.collected;
  
  const jobId = `job_${state.buyerId}_${Date.now()}`;

  const job = {
    id: jobId,
    buyer_id: state.buyerId,
    title: collected.title || `${collected.service_category_name} Service`,
    description: collected.description || `Looking for ${collected.service_category_name} service`,
    service_category_id: collected.service_category_id,
    budget: {
      min: collected.budget?.min || Math.floor((collected.budget?.max || 100) * 0.5),
      max: collected.budget?.max,
    },
    startDate: collected.startDate || 'ASAP',
    endDate: collected.endDate || 'flexible',
    location: collected.location || null,
    priorities: collected.priorities || [],
    created_at: Date.now(),
  };

  console.log(`[BuildJob] Created job: ${jobId}`);
  return { 
    job,
    phase: 'complete', // Mark as complete, ready for negotiation
  };
}

/* -------------------- ROUTING FUNCTIONS (UPDATED) -------------------- */

function routeAfterIntent(state) {
  const intent = state.intent?.intent;
  
  if (intent === 'cancel') {
    return 'generate_response';
  }
  
  if (intent === 'ask_question' && !state.intent?.service_keywords?.length) {
    return 'generate_response';
  }
  
  // For confirm intent and we're in confirmation phase, build the job
  if (intent === 'confirm' && state.phase === 'confirmation') {
    return 'build_job';
  }
  
  // For add_more_info or modify_info during confirmation, go back to extraction
  if ((intent === 'add_more_info' || intent === 'modify_info') && state.phase === 'confirmation') {
    return 'extract_info';
  }
  
  return 'extract_info';
}

function routeAfterCompleteness(state) {
  // If minimum requirements met, go to confirmation
  if (state.jobReadiness === 'minimum' || state.jobReadiness === 'complete') {
    return 'confirmation';
  }
  
  // Otherwise, ask for more info
  return 'generate_response';
}

/* -------------------- GRAPH DEFINITION (UPDATED) -------------------- */

const workflow = new StateGraph(ConversationState)
  .addNode('detect_intent', detectIntentNode)
  .addNode('extract_info', extractInfoNode)
  .addNode('update_collected', updateCollectedNode)
  .addNode('check_completeness', checkCompletenessNode)
  .addNode('generate_response', generateResponseNode)
  .addNode('confirmation', confirmationNode)
  .addNode('build_job', buildJobNode)
  
  .addEdge(START, 'detect_intent')
  
  .addConditionalEdges('detect_intent', routeAfterIntent, {
    'extract_info': 'extract_info',
    'generate_response': 'generate_response',
    'build_job': 'build_job',
  })
  
  .addEdge('extract_info', 'update_collected')
  .addEdge('update_collected', 'check_completeness')
  
  .addConditionalEdges('check_completeness', routeAfterCompleteness, {
    'confirmation': 'confirmation',
    'generate_response': 'generate_response',
  })
  
  .addEdge('confirmation', 'generate_response')
  .addEdge('build_job', 'generate_response')
  .addEdge('generate_response', END);

// Use MemorySaver for checkpointing (required for human-in-the-loop)
const checkpointer = new MemorySaver();
export const conversationGraph = workflow.compile({ checkpointer });

/* -------------------- RUNNER FUNCTION (UPDATED) -------------------- */

export async function runConversation(input) {
  const { sessionId, buyerId, accessToken, message } = input;

  const existingSession = await conversationStore.getSession(sessionId);
  
  const initialState = {
    sessionId,
    buyerId,
    accessToken,
    phase: existingSession?.phase || 'conversation',
    currentMessage: message,
    messages: existingSession?.messages || [],
    collected: existingSession?.collected || {
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
    requiredMissing: existingSession?.requiredMissing || ['service_category_id', 'budget_max', 'start_date', 'location'],
    optionalMissing: existingSession?.optionalMissing || ['title', 'description', 'budget_min', 'end_date'],
    serviceCategories: existingSession?.serviceCategories || null,
    jobReadiness: existingSession?.jobReadiness || 'incomplete',
    job: existingSession?.job || null,
  };

  initialState.messages = initialState.messages.concat([{
    role: 'user',
    content: message,
    timestamp: Date.now(),
  }]);

  // Configure with thread_id for checkpointing
  const config = {
    configurable: {
      thread_id: sessionId,
    },
  };

  const result = await conversationGraph.invoke(initialState, config);

  const assistantMessage = {
    role: 'assistant',
    content: result.response?.message || "I'm here to help!",
    timestamp: Date.now(),
  };

  const updatedMessages = result.messages.concat([assistantMessage]);

  await conversationStore.saveSession(sessionId, {
    phase: result.phase || 'conversation',
    messages: updatedMessages,
    collected: result.collected,
    requiredMissing: result.requiredMissing,
    optionalMissing: result.optionalMissing,
    serviceCategories: result.serviceCategories,
    jobReadiness: result.jobReadiness,
    job: result.job,
  });

  await conversationStore.appendMessage(sessionId, { role: 'user', content: message });
  await conversationStore.appendMessage(sessionId, { role: 'assistant', content: assistantMessage.content });
  await conversationStore.setPhase(sessionId, result.phase || 'conversation');

  return {
    sessionId,
    phase: result.phase || 'conversation',
    response: result.response?.message || "I'm here to help!",
    action: result.response?.action,
    collected: result.collected,
    requiredMissing: result.requiredMissing,
    optionalMissing: result.optionalMissing,
    jobReadiness: result.jobReadiness,
    job: result.job,
  };
}