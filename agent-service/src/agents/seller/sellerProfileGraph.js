import { Annotation, StateGraph, START, END } from '@langchain/langgraph';
import { ChatOpenAI } from '@langchain/openai';
import { HumanMessage, SystemMessage } from '@langchain/core/messages';
import { OPENAI_API_KEY } from '../../config/index.js';
import { redisClient } from '../../config/redis.js';
import { MemorySaver } from '@langchain/langgraph';
import { SellerProfile } from '../../models/SellerProfile.js';
import { serviceCategoryManager } from '../conversationGraph.js';

/* ================================================================================
   SELLER PROFILE GRAPH - Profile Creation Through Natural Conversation
   ================================================================================ */

/* -------------------- REDIS SESSION STORE -------------------- */

class SellerProfileSessionStore {
  constructor(redis) {
    this.redis = redis;
    this.TTL = 7200; // 2 hours
  }

  async saveSession(sessionId, state) {
    const key = `seller_profile:${sessionId}:state`;
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
    const key = `seller_profile:${sessionId}:state`;
    const data = await this.redis.get(key);
    return data ? JSON.parse(data) : null;
  }

  async appendMessage(sessionId, message) {
    const key = `seller_profile:${sessionId}:messages`;
    await this.redis.rPush(key, JSON.stringify({
      ...message,
      timestamp: Date.now(),
    }));
    await this.redis.expire(key, this.TTL);
    await this.redis.lTrim(key, -50, -1);
  }

  async getMessages(sessionId) {
    const key = `seller_profile:${sessionId}:messages`;
    const messages = await this.redis.lRange(key, 0, -1);
    return messages.map(m => JSON.parse(m));
  }

  async setPhase(sessionId, phase) {
    const key = `seller_profile:${sessionId}:phase`;
    await this.redis.setEx(key, this.TTL, phase);
  }

  async getPhase(sessionId) {
    const key = `seller_profile:${sessionId}:phase`;
    return await this.redis.get(key) || 'collection';
  }

  async cleanup(sessionId) {
    const pattern = `seller_profile:${sessionId}:*`;
    const keys = await this.redis.keys(pattern);
    if (keys.length > 0) {
      await this.redis.del(...keys);
    }
  }
}

export const sellerProfileStore = new SellerProfileSessionStore(redisClient);

/* -------------------- STATE DEFINITION -------------------- */

const SellerProfileState = Annotation.Root({
  sessionId: Annotation(),
  sellerId: Annotation(),
  accessToken: Annotation(),
  
  phase: Annotation(), // 'collection' | 'confirmation' | 'complete'
  
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
      service_categories: [], // [id1, id2, ...]
      service_area: null,
      availability: null,
      credentials: {
        licensed: null,
        insured: null,
        years_experience: null,
        references_available: null,
        certifications: [],
      },
      pricing: {
        hourly_rate_min: null,
        hourly_rate_max: null,
        fixed_prices: {},
      },
      preferences: {
        min_job_size_hours: null,
        max_travel_distance: null,
        provides_materials: null,
        preferred_payment: [],
      },
      bio: null,
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
  
  profileReadiness: Annotation(), // 'incomplete' | 'minimum' | 'complete'
  
  profile: Annotation(),
  serviceCategories: Annotation(),
  error: Annotation(),
});

/** Format pricing for display so the model sees both hourly and fixed prices (avoids re-asking). */
function formatPricingDisplay(pricing) {
  if (!pricing) return 'not set';
  const parts = [];
  if (pricing.hourly_rate_max != null) {
    parts.push(`$${pricing.hourly_rate_min ?? '?'}-$${pricing.hourly_rate_max}/hr`);
  }
  const fp = pricing.fixed_prices && Object.keys(pricing.fixed_prices).length > 0
    ? pricing.fixed_prices
    : null;
  if (fp) {
    const fixedStr = Object.entries(fp).map(([k, v]) => `${k}: $${v}`).join(', ');
    parts.push(`Fixed: ${fixedStr}`);
  }
  return parts.length ? parts.join('; ') : 'not set';
}

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
You are an intent classifier for a service provider profile creation.

Conversation context:
${recentMessages || 'No previous messages'}

Current user message: "${state.currentMessage}"

Current collected data:
- Services: ${state.collected.service_categories.length > 0 ? state.collected.service_categories.join(', ') : 'not set'}
- Service area: ${state.collected.service_area || 'not set'}
- Availability: ${state.collected.availability || 'not set'}
- Pricing: ${formatPricingDisplay(state.collected.pricing)}
- Experience: ${state.collected.credentials?.years_experience ? state.collected.credentials.years_experience + ' years' : 'not set'}
- Licensed: ${state.collected.credentials?.licensed ?? 'not set'}

Classify the intent as ONE of:
1. "create_profile" - User wants to create a service provider profile
2. "provide_info" - User is answering a question / providing profile details
3. "ask_question" - User is asking about something
4. "modify_info" - User wants to change previously provided information
5. "confirm" - User is confirming/agreeing to finalize profile
6. "add_more_info" - User wants to add more details before finalizing
7. "cancel" - User wants to cancel or start over
8. "other" - Doesn't fit any category

Reply ONLY with JSON:
{
  "intent": "<one of the intents above>",
  "service_keywords": ["<any service-related words mentioned>"],
  "has_pricing_mention": true/false,
  "has_availability_mention": true/false,
  "has_location_mention": true/false,
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

    console.log(`[SellerIntent] Detected: ${intent.intent} (${intent.confidence})`);
    return { intent };
  } catch (error) {
    console.error('[SellerIntent] Detection error:', error.message);
    return { 
      intent: { 
        intent: 'provide_info', 
        confidence: 'low',
        service_keywords: [],
        has_pricing_mention: false,
        has_availability_mention: false,
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
      state.sellerId,
      state.accessToken
    );
  }

  const categoryList = categories?.map(c => c.service_category_name).join(', ') || 'Loading...';

  const prompt = `
You are an information extractor for a service provider profile creation.

User message: "${state.currentMessage}"

Currently collected:
${JSON.stringify(state.collected, null, 2)}

Available service categories: ${categoryList}

Extract ANY new information from the user's message about their service provider profile.

Instructions:
1. For services: Extract all services they offer (can be multiple). Match to available categories.
2. For service area: Extract city, neighborhood, or "radius from location"
3. For availability: Extract days/times (e.g., "evenings 5-9PM", "weekends", "Mon-Fri mornings")
4. For pricing: Extract hourly rates or fixed prices per service type. Use a consistent key for the service (e.g. "house cleaning"). Do NOT return empty "pricing" or "fixed_prices" if the user only confirms existing info (e.g. "fixed price", "that's right")—only add new numbers or leave pricing out of "extracted" to avoid overwriting.
5. For credentials: Extract experience years, licensed status, insurance, references
6. For preferences: Extract job size preferences (e.g. "no minimum" or "no" → min_job_size_hours: 0), travel distance, materials, payment methods
7. For bio: Extract any personal introduction or background story
8. Set found_new_info to true only when you actually extract NEW data; if the user only confirms or repeats what we already have, set found_new_info to false.

Today's date: ${new Date().toISOString().split('T')[0]}

Reply ONLY with JSON:
{
  "extracted": {
    "service_categories": ["<matched category names>"],
    "service_area": {
      "location": "<city/neighborhood or null>",
      "radius_miles": <number or null>
    },
    "availability": {
      "schedule": "<natural language schedule or null>",
      "weekday_evenings": <true/false/null>,
      "weekends": <true/false/null>
    },
    "credentials": {
      "licensed": <true/false/null>,
      "insured": <true/false/null>,
      "years_experience": <number or null>,
      "references_available": <true/false/null>,
      "certifications": ["<any certifications mentioned>"]
    },
    "pricing": {
      "hourly_rate_min": <number or null>,
      "hourly_rate_max": <number or null>,
      "fixed_prices": {
        "<service_type>": <number>
      }
    },
    "preferences": {
      "min_job_size_hours": <number or null (use 0 for "no minimum")>,
      "max_travel_distance": <number or null>,
      "provides_materials": <true/false/null>,
      "preferred_payment": ["<payment methods>"]
    },
    "bio": "<any introduction text or null>"
  },
  "found_new_info": true/false,
  "needs_clarification": "<any unclear parts or null>"
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

    console.log(`[SellerExtraction] Found new info: ${extraction.found_new_info}`);

    // Match service categories
    if (extraction.extracted.service_categories && extraction.extracted.service_categories.length > 0 && categories) {
      const matchedIds = [];
      for (const serviceName of extraction.extracted.service_categories) {
        const matchResult = await serviceCategoryManager.findCategory(
          serviceName,
          categories,
          llm
        );
        
        if (matchResult?.matched && matchResult.category_id) {
          matchedIds.push(matchResult.category_id);
          console.log(`[SellerExtraction] Matched: ${matchResult.category_name} (ID: ${matchResult.category_id})`);
        }
      }
      extraction.extracted.service_category_ids = matchedIds;
    }

    return { 
      extraction,
      serviceCategories: categories,
    };
  } catch (error) {
    console.error('[SellerExtraction] Error:', error.message);
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

  if (extracted.service_category_ids && extracted.service_category_ids.length > 0) {
    updates.service_categories = [...new Set([
      ...state.collected.service_categories,
      ...extracted.service_category_ids
    ])];
  }

  if (extracted.service_area) {
    updates.service_area = {
      ...state.collected.service_area,
      ...extracted.service_area,
    };
  }

  if (extracted.availability) {
    updates.availability = {
      ...state.collected.availability,
      ...extracted.availability,
    };
  }

  if (extracted.credentials) {
    updates.credentials = {
      ...state.collected.credentials,
      ...extracted.credentials,
    };
  }

  if (extracted.pricing) {
    const existingFixed = state.collected.pricing?.fixed_prices || {};
    const newFixed = extracted.pricing.fixed_prices || {};
    updates.pricing = {
      ...state.collected.pricing,
      ...extracted.pricing,
      // Deep-merge fixed_prices so later messages don't overwrite already extracted prices
      fixed_prices: { ...existingFixed, ...newFixed },
    };
  }

  if (extracted.preferences) {
    updates.preferences = {
      ...state.collected.preferences,
      ...extracted.preferences,
    };
  }

  if (extracted.bio) {
    updates.bio = extracted.bio;
  }

  return { collected: updates };
}

/* -------------------- CHECK COMPLETENESS NODE -------------------- */

function checkCompletenessNode(state) {
  const collected = state.collected;
  const required = [];
  const optional = [];

  // REQUIRED FIELDS
  if (!collected.service_categories || collected.service_categories.length === 0) {
    required.push('service_categories');
  }
  if (!collected.service_area?.location) {
    required.push('service_area');
  }
  if (!collected.availability?.schedule) {
    required.push('availability');
  }
  if (!collected.pricing?.hourly_rate_max && Object.keys(collected.pricing?.fixed_prices || {}).length === 0) {
    required.push('pricing');
  }

  // OPTIONAL FIELDS
  if (!collected.credentials?.years_experience) {
    optional.push('years_experience');
  }
  if (collected.credentials?.licensed === null) {
    optional.push('licensed');
  }
  if (collected.credentials?.references_available === null) {
    optional.push('references');
  }
  if (!collected.bio) {
    optional.push('bio');
  }
  if (collected.preferences?.min_job_size_hours == null) {
    optional.push('min_job_size');
  }

  let profileReadiness = 'incomplete';
  
  if (required.length === 0 && optional.length > 0) {
    profileReadiness = 'minimum';
  } else if (required.length === 0 && optional.length === 0) {
    profileReadiness = 'complete';
  }

  console.log(`[SellerCompleteness] Readiness: ${profileReadiness}`);
  console.log(`[SellerCompleteness] Required missing: ${required.join(', ') || 'none'}`);
  console.log(`[SellerCompleteness] Optional missing: ${optional.join(', ') || 'none'}`);

  return { 
    requiredMissing: required,
    optionalMissing: optional,
    profileReadiness,
  };
}

/* -------------------- GENERATE RESPONSE NODE -------------------- */

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
    responseGoal = 'Answer their question helpfully';
  } else if (state.profileReadiness === 'minimum' || state.profileReadiness === 'complete') {
    responseGoal = 'Present a summary of their profile and ask if they want to add more details or finalize';
  } else if (state.requiredMissing.includes('service_categories')) {
    responseGoal = 'Ask what services they offer';
  } else if (state.requiredMissing.includes('service_area')) {
    responseGoal = 'Ask what area they serve';
  } else if (state.requiredMissing.includes('availability')) {
    responseGoal = 'Ask about their availability (days/times)';
  } else if (state.requiredMissing.includes('pricing')) {
    responseGoal = 'Ask about their pricing (hourly rate or fixed prices)';
  } else {
    responseGoal = 'Ask if they have any other details to add';
  }

  const availableCategories = state.serviceCategories?.slice(0, 10)
    .map(c => c.service_category_name).join(', ') || '';

  const prompt = `
You are a friendly assistant helping a service provider create their profile.

Conversation so far:
${recentMessages || 'This is the start of the conversation'}

User's latest message: "${state.currentMessage}"

What we know so far:
- Services: ${state.collected.service_categories.length > 0 ? state.collected.service_categories.join(', ') : 'not specified yet'}
- Service area: ${state.collected.service_area?.location || 'not specified yet'}
- Availability: ${state.collected.availability?.schedule || 'not specified yet'}
- Pricing: ${formatPricingDisplay(state.collected.pricing).replace('not set', 'not specified yet')}
- Experience: ${state.collected.credentials?.years_experience ? state.collected.credentials.years_experience + ' years' : 'not specified'}
- Licensed: ${state.collected.credentials?.licensed === true ? 'Yes' : state.collected.credentials?.licensed === false ? 'No' : 'not specified'}
- Bio: ${state.collected.bio || 'not added'}

Profile readiness: ${state.profileReadiness}
Required fields still needed: ${state.requiredMissing.join(', ') || 'none'}
Optional fields missing: ${state.optionalMissing.join(', ') || 'none'}

Your goal: ${responseGoal}

Some available services: ${availableCategories}...

Instructions:
- Be conversational and friendly (like helping a friend)
- Keep responses SHORT (2-3 sentences max)
- Don't be robotic or formal
- Use contractions (I'm, you're, what's)
- Only ask ONE question at a time
- NEVER ask again for something already in "What we know so far" (e.g. if Pricing is set, do not ask about pricing again; if they said no minimum job size, do not ask again). Move to the next missing topic or to confirmation.
- If profile readiness is 'minimum' or 'complete', summarize their profile and ask if they want to finalize

Reply ONLY with JSON:
{
  "message": "<your friendly response>",
  "action": "<asking_services|asking_area|asking_availability|asking_pricing|asking_optional|ready_for_confirmation|answering>"
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

    console.log(`[SellerResponse] Action: ${response.action}`);
    return { response };
  } catch (error) {
    console.error('[SellerResponse] Generation error:', error.message);
    return { 
      response: { 
        message: "I'd love to help you create your service provider profile! What services do you offer?",
        action: 'asking_services'
      } 
    };
  }
}

/* -------------------- CONFIRMATION NODE -------------------- */

async function confirmationNode(state) {
  const collected = state.collected;
  
  const summary = {
    services: collected.service_categories.join(', ') || 'Not specified',
    service_area: collected.service_area?.location || 'Not specified',
    availability: collected.availability?.schedule || 'Not specified',
    pricing: formatPricingDisplay(collected.pricing).replace('not set', 'Custom pricing'),
    experience: collected.credentials?.years_experience 
      ? `${collected.credentials.years_experience} years` 
      : 'Not specified',
    licensed: collected.credentials?.licensed === true ? 'Yes' : collected.credentials?.licensed === false ? 'No' : 'Not specified',
    bio: collected.bio || 'No bio added',
  };

  console.log('[SellerConfirmation] Showing profile preview');
  
  const llm = new ChatOpenAI({
    model: 'gpt-4o-mini',
    temperature: 0.7,
    openAIApiKey: OPENAI_API_KEY,
  });

  const prompt = `
You are helping a service provider confirm their profile before going live.

Profile Summary:
${JSON.stringify(summary, null, 2)}

Optional fields that could be added:
${state.optionalMissing.join(', ') || 'All fields are complete!'}

Generate a friendly confirmation message that:
1. Summarizes their profile
2. Lists the key details
3. Mentions any optional fields they could add
4. Asks if they want to finalize, add more details, or make changes

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
    console.error('[SellerConfirmation] Error:', error.message);
    return {
      response: {
        message: `Great! Your profile looks good: ${summary.services} in ${summary.service_area}, available ${summary.availability} at ${summary.pricing}. Ready to go live?`,
        action: 'awaiting_confirmation',
      },
      phase: 'confirmation',
    };
  }
}

/* -------------------- BUILD PROFILE NODE -------------------- */

async function buildProfileNode(state) {
  const collected = state.collected;
  
  try {
    // Calculate completeness score
    let score = 0;
    if (collected.service_categories.length > 0) score += 20;
    if (collected.service_area?.location) score += 15;
    if (collected.availability?.schedule) score += 15;
    if (collected.pricing?.hourly_rate_max || Object.keys(collected.pricing?.fixed_prices || {}).length > 0) score += 15;
    if (collected.credentials?.years_experience) score += 10;
    if (collected.credentials?.licensed !== null) score += 5;
    if (collected.credentials?.references_available) score += 5;
    if (collected.bio) score += 10;
    if (collected.preferences?.min_job_size_hours) score += 5;

    // Create or update profile in database
    const [profile, created] = await SellerProfile.upsert({
      seller_id: state.sellerId,
      service_categories: collected.service_categories,
      service_area: collected.service_area,
      availability: collected.availability,
      credentials: collected.credentials,
      pricing: collected.pricing,
      preferences: collected.preferences,
      bio: collected.bio,
      profile_completeness_score: score,
      active: true,
    });

    console.log(`[BuildProfile] ${created ? 'Created' : 'Updated'} profile for seller ${state.sellerId}`);
    
    return { 
      profile: profile.toJSON(),
      phase: 'complete',
    };
  } catch (error) {
    console.error('[BuildProfile] Error:', error.message);
    return {
      error: 'Failed to save profile',
      phase: 'collection',
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
    return 'build_profile';
  }
  
  if ((intent === 'add_more_info' || intent === 'modify_info') && state.phase === 'confirmation') {
    return 'extract_info';
  }
  
  return 'extract_info';
}

function routeAfterCompleteness(state) {
  if (state.profileReadiness === 'minimum' || state.profileReadiness === 'complete') {
    return 'confirmation';
  }
  
  return 'generate_response';
}

/* -------------------- GRAPH DEFINITION -------------------- */

const workflow = new StateGraph(SellerProfileState)
  .addNode('detect_intent', detectIntentNode)
  .addNode('extract_info', extractInfoNode)
  .addNode('update_collected', updateCollectedNode)
  .addNode('check_completeness', checkCompletenessNode)
  .addNode('generate_response', generateResponseNode)
  .addNode('confirmation', confirmationNode)
  .addNode('build_profile', buildProfileNode)
  
  .addEdge(START, 'detect_intent')
  
  .addConditionalEdges('detect_intent', routeAfterIntent, {
    'extract_info': 'extract_info',
    'generate_response': 'generate_response',
    'build_profile': 'build_profile',
  })
  
  .addEdge('extract_info', 'update_collected')
  .addEdge('update_collected', 'check_completeness')
  
  .addConditionalEdges('check_completeness', routeAfterCompleteness, {
    'confirmation': 'confirmation',
    'generate_response': 'generate_response',
  })
  
  .addEdge('confirmation', 'generate_response')
  .addEdge('build_profile', 'generate_response')
  .addEdge('generate_response', END);

const checkpointer = new MemorySaver();
export const sellerProfileGraph = workflow.compile({ checkpointer });

/* -------------------- RUNNER FUNCTION -------------------- */

export async function runSellerProfileConversation(input) {
  const { sessionId, sellerId, accessToken, message } = input;

  const existingSession = await sellerProfileStore.getSession(sessionId);
  
  const initialState = {
    sessionId,
    sellerId,
    accessToken,
    phase: existingSession?.phase || 'collection',
    currentMessage: message,
    messages: existingSession?.messages || [],
    collected: existingSession?.collected || {
      service_categories: [],
      service_area: null,
      availability: null,
      credentials: {
        licensed: null,
        insured: null,
        years_experience: null,
        references_available: null,
        certifications: [],
      },
      pricing: {
        hourly_rate_min: null,
        hourly_rate_max: null,
        fixed_prices: {},
      },
      preferences: {
        min_job_size_hours: null,
        max_travel_distance: null,
        provides_materials: null,
        preferred_payment: [],
      },
      bio: null,
    },
    requiredMissing: existingSession?.requiredMissing || ['service_categories', 'service_area', 'availability', 'pricing'],
    optionalMissing: existingSession?.optionalMissing || ['years_experience', 'licensed', 'references', 'bio', 'min_job_size'],
    serviceCategories: existingSession?.serviceCategories || null,
    profileReadiness: existingSession?.profileReadiness || 'incomplete',
    profile: existingSession?.profile || null,
  };

  initialState.messages = initialState.messages.concat([{
    role: 'user',
    content: message,
    timestamp: Date.now(),
  }]);

  const config = {
    configurable: {
      thread_id: sessionId,
    },
  };

  const result = await sellerProfileGraph.invoke(initialState, config);

  const assistantMessage = {
    role: 'assistant',
    content: result.response?.message || "I'm here to help you create your profile!",
    timestamp: Date.now(),
  };

  const updatedMessages = result.messages.concat([assistantMessage]);

  await sellerProfileStore.saveSession(sessionId, {
    phase: result.phase || 'collection',
    messages: updatedMessages,
    collected: result.collected,
    requiredMissing: result.requiredMissing,
    optionalMissing: result.optionalMissing,
    serviceCategories: result.serviceCategories,
    profileReadiness: result.profileReadiness,
    profile: result.profile,
  });

  await sellerProfileStore.appendMessage(sessionId, { role: 'user', content: message });
  await sellerProfileStore.appendMessage(sessionId, { role: 'assistant', content: assistantMessage.content });
  await sellerProfileStore.setPhase(sessionId, result.phase || 'collection');

  return {
    sessionId,
    phase: result.phase || 'collection',
    response: result.response?.message || "I'm here to help!",
    action: result.response?.action,
    collected: result.collected,
    requiredMissing: result.requiredMissing,
    optionalMissing: result.optionalMissing,
    profileReadiness: result.profileReadiness,
    profile: result.profile,
  };
}