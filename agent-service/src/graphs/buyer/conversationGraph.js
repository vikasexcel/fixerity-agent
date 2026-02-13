import { Annotation, StateGraph, START, END } from '@langchain/langgraph';
import { ChatOpenAI } from '@langchain/openai';
import { HumanMessage, SystemMessage } from '@langchain/core/messages';
import { OPENAI_API_KEY, LARAVEL_API_BASE_URL } from '../../config/index.js';
import { MemorySaver } from '@langchain/langgraph';
import { sessionService, messageService, cacheService } from '../../services/index.js';
import prisma from '../../prisma/client.js';

/* ================================================================================
   CONVERSATION GRAPH - Job Creation Through Natural Conversation
   ================================================================================ */

/* -------------------- SERVICE CATEGORIES MANAGER (Using Cache Service) -------------------- */

class ServiceCategoryManager {
  async fetchFromAPI(userId, accessToken) {
    try {
      const response = await fetch(`${LARAVEL_API_BASE_URL}/customer/home`, {
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
        return data.services;
      }
      return null;
    } catch (error) {
      console.error('[ServiceCategory] API fetch error:', error.message);
      return null;
    }
  }

  /**
   * Fetch service categories for sellers/providers (on-demand/get-service-list).
   * Returns same shape as customer API: { service_category_id, service_category_name }.
   */
  async fetchProviderFromAPI(providerId, accessToken) {
    try {
      const response = await fetch(`${LARAVEL_API_BASE_URL}/on-demand/get-service-list`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          provider_id: providerId,
          access_token: accessToken,
        })
      });

      const data = await response.json();
      if (data.status === 1 && data.service_category_list) {
        return data.service_category_list.map((s) => ({
          service_category_id: s.service_cat_id ?? s.service_category_id,
          service_category_name: s.service_cat_name ?? s.service_category_name,
        }));
      }
      return null;
    } catch (error) {
      console.error('[ServiceCategory] Provider API fetch error:', error.message);
      return null;
    }
  }

  async getCategoriesOrFetch(userId, accessToken) {
    return await cacheService.getServiceCategories(
      async () => await this.fetchFromAPI(userId, accessToken),
      accessToken
    );
  }

  /**
   * Get provider/seller service categories (for seller profile flow).
   * Uses on-demand/get-service-list and caches under service_categories:provider.
   */
  async getProviderCategoriesOrFetch(providerId, accessToken) {
    return await cacheService.getOrFetch(
      'service_categories:provider',
      async () => await this.fetchProviderFromAPI(providerId, accessToken),
      86400
    );
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

export const serviceCategoryManager = new ServiceCategoryManager();

/* -------------------- STATE DEFINITION -------------------- */

const ConversationState = Annotation.Root({
  sessionId: Annotation(),
  buyerId: Annotation(),
  accessToken: Annotation(),
  
  phase: Annotation(),
  
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
  
  jobReadiness: Annotation(),
  
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

  const prompt = `
You are an information extractor for a service marketplace.

User message: "${state.currentMessage}"

Currently collected:
${JSON.stringify(state.collected, null, 2)}

Extract ANY new information from the user's message. Do NOT use any external category list—use only what the user explicitly said or implied.

Instructions:
1. For service type: Extract the type of service they need from their words (e.g. "cleaning", "home cleaning", "plumbing", "deep clean"). Use a clear, normalised phrase as service_category_name. If they confirm or correct (e.g. "yes", "actually I need X"), use that.
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
    "service_category_name": "<service they need, e.g. home cleaning, plumbing, or null>",
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
    if (extraction.extracted.service_category_name) {
      console.log(`[Extraction] Service from conversation: ${extraction.extracted.service_category_name}`);
    }

    return { extraction };
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

  if (extracted.service_category_name) {
    updates.service_category_name = extracted.service_category_name.trim();
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

/* -------------------- CHECK COMPLETENESS NODE -------------------- */

function checkCompletenessNode(state) {
  const collected = state.collected;
  const required = [];
  const optional = [];

  // REQUIRED FIELDS (service is explicit from conversation, no API)
  if (!collected.service_category_name) {
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

  // OPTIONAL FIELDS
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

  let jobReadiness = 'incomplete';
  
  if (required.length === 0 && optional.length > 0) {
    jobReadiness = 'minimum';
  } else if (required.length === 0 && optional.length === 0) {
    jobReadiness = 'complete';
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

/* -------------------- GENERATE RESPONSE NODE -------------------- */

async function generateResponseNode(state) {
  if (state.error) {
    return { response: { message: state.error } };
  }

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
    responseGoal = 'Present a summary of what will be searched and ask if they want to add more details or proceed now';
  } else if (state.requiredMissing.includes('service_category')) {
    responseGoal = 'Ask what type of service they need. If they already mentioned something, confirm: "Is this the service you need? If different, tell me."';
  } else if (state.requiredMissing.includes('budget_max')) {
    responseGoal = 'Ask about their budget for this service';
  } else if (state.requiredMissing.includes('start_date')) {
    responseGoal = 'Ask when they need this service to start';
  } else if (state.requiredMissing.includes('location')) {
    responseGoal = 'Ask for the location/address where the service is needed';
  } else {
    responseGoal = 'Ask if they have any other details to add';
  }

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

${state.collected.service_category_name ? `Current service they said: ${state.collected.service_category_name}. When confirming, ask: "Is this the service you need? If different, tell me."` : ''}

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

/* -------------------- CONFIRMATION NODE -------------------- */

async function confirmationNode(state) {
  const collected = state.collected;
  
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

/* -------------------- BUILD JOB NODE -------------------- */

function normalizeLocation(location) {
  if (location == null) return null;
  if (typeof location === 'string') return { address: location };
  if (typeof location === 'object' && (location.address !== undefined || location.lat !== undefined)) {
    return { address: location.address ?? '', lat: location.lat ?? null, lng: location.lng ?? null };
  }
  return null;
}

async function buildJobNode(state) {
  const collected = state.collected;
  const jobId = `job_${state.buyerId}_${Date.now()}`;

  const budgetMax = collected.budget?.max;
  const budgetMin = collected.budget?.min ?? (budgetMax != null ? Math.floor(budgetMax * 0.5) : 100);

  const payload = {
    id: jobId,
    buyerId: state.buyerId,
    serviceCategoryId: collected.service_category_id ?? null,
    serviceCategoryName: collected.service_category_name || null,
    title: collected.title || `${collected.service_category_name || 'Service'} request`,
    description: collected.description || (collected.service_category_name ? `Looking for ${collected.service_category_name}` : 'Service request'),
    budget: { min: budgetMin, max: budgetMax },
    startDate: collected.startDate || 'ASAP',
    endDate: collected.endDate || 'flexible',
    location: normalizeLocation(collected.location),
    priorities: collected.priorities?.length ? collected.priorities : null,
    specificRequirements: null,
    status: 'open',
  };

  try {
    const created = await prisma.jobListing.create({
      data: payload,
    });

    const job = {
      id: created.id,
      buyer_id: created.buyerId,
      title: created.title,
      description: created.description,
      service_category_id: created.serviceCategoryId,
      service_category_name: created.serviceCategoryName,
      budget: created.budget,
      startDate: created.startDate,
      endDate: created.endDate,
      location: created.location,
      priorities: created.priorities || [],
      created_at: created.createdAt,
    };

    console.log(`[BuildJob] Created job: ${jobId}`);
    return { job, phase: 'complete' };
  } catch (error) {
    console.error('[BuildJob] JobListing.create failed:', error.message);
    return {
      job: null,
      phase: 'conversation',
      error: 'Failed to create job. Please try again.',
    };
  }
}

/* -------------------- ROUTING FUNCTIONS -------------------- */

function routeAfterIntent(state) {
  const intent = state.intent?.intent;
  
  if (intent === 'cancel') {
    return 'generate_response';
  }
  
  if (intent === 'ask_question' && !state.intent?.service_keywords?.length) {
    return 'generate_response';
  }
  
  if (intent === 'confirm' && state.phase === 'confirmation') {
    return 'build_job';
  }
  
  if ((intent === 'add_more_info' || intent === 'modify_info') && state.phase === 'confirmation') {
    return 'extract_info';
  }
  
  return 'extract_info';
}

function routeAfterCompleteness(state) {
  if (state.jobReadiness === 'minimum' || state.jobReadiness === 'complete') {
    return 'confirmation';
  }
  
  return 'generate_response';
}

/* -------------------- GRAPH DEFINITION -------------------- */

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

const checkpointer = new MemorySaver();
export const conversationGraph = workflow.compile({ checkpointer });

/* -------------------- RUNNER FUNCTION -------------------- */

export async function runConversation(input) {
  const { sessionId, buyerId, accessToken, message } = input;

  // Get session from database
  const sessionData = await sessionService.getSessionWithContext(sessionId, 50);

  // Format messages for LangGraph state
  const messages = sessionData.messages.map(m => ({
    role: m.role,
    content: m.content,
    timestamp: m.createdAt,
  }));

  const initialState = {
    sessionId,
    buyerId,
    accessToken,
    phase: sessionData.phase || 'conversation',
    currentMessage: message,
    messages: messages,
    collected: sessionData.state?.collected || {
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
    requiredMissing: sessionData.state?.requiredMissing || ['service_category', 'budget_max', 'start_date', 'location'],
    optionalMissing: sessionData.state?.optionalMissing || ['title', 'description', 'budget_min', 'end_date'],
    serviceCategories: sessionData.state?.serviceCategories || null,
    jobReadiness: sessionData.state?.jobReadiness || 'incomplete',
    job: sessionData.state?.job || null,
  };

  const config = {
    configurable: {
      thread_id: sessionId,
    },
  };

  const result = await conversationGraph.invoke(initialState, config);

  // Save user message
  await messageService.addUserMessage(sessionId, message);

  // Save assistant response
  await messageService.addAssistantMessage(
    sessionId,
    result.response?.message || "I'm here to help!",
    {
      action: result.response?.action,
      intent: result.intent,
    }
  );

  // Update session state
  await sessionService.updateState(sessionId, {
    collected: result.collected,
    requiredMissing: result.requiredMissing,
    optionalMissing: result.optionalMissing,
    jobReadiness: result.jobReadiness,
    job: result.job,
  });

  // Update phase if changed
  if (result.phase && result.phase !== sessionData.phase) {
    await sessionService.updatePhase(sessionId, result.phase);
  }

  return {
    sessionId,
    phase: result.phase || sessionData.phase,
    response: result.response?.message || "I'm here to help!",
    action: result.response?.action,
    collected: result.collected,
    requiredMissing: result.requiredMissing,
    optionalMissing: result.optionalMissing,
    jobReadiness: result.jobReadiness,
    job: result.job,
  };
}