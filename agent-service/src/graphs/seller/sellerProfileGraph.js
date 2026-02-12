import { Annotation, StateGraph, START, END } from '@langchain/langgraph';
import { ChatOpenAI } from '@langchain/openai';
import { HumanMessage, SystemMessage } from '@langchain/core/messages';
import { OPENAI_API_KEY } from '../../config/index.js';
import { MemorySaver } from '@langchain/langgraph';
import { sessionService, messageService, cacheService } from '../../services/index.js';
import { serviceCategoryManager } from '../buyer/conversationGraph.js';
import prisma from '../../prisma/client.js';

/* ================================================================================
   SELLER PROFILE GRAPH - Profile Creation Through Natural Conversation
   ================================================================================ */

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

/** Format pricing for display */
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

/* -------------------- CHECK COMPLETENESS NODE (LLM-based, dynamic) -------------------- */

async function checkCompletenessNode(state) {
  const llm = new ChatOpenAI({
    model: 'gpt-4o-mini',
    temperature: 0,
    openAIApiKey: OPENAI_API_KEY,
  });

  const recentMessages = state.messages.slice(-6).map(m =>
    `${m.role.toUpperCase()}: ${m.content}`
  ).join('\n');

  const collected = state.collected;
  const hasServiceCategories = collected.service_categories?.length > 0;
  const hasServiceArea = !!collected.service_area?.location;

  const prompt = `You are judging whether a service provider's profile has ENOUGH information to be usable (so we can stop asking and offer to finalize).

Conversation (recent):
${recentMessages || 'No messages yet'}

Current collected data (raw):
${JSON.stringify(collected, null, 2)}

User's latest message: "${state.currentMessage}"

Guidelines:
1. **Sufficient service info**: If the user clearly stated what they offer (e.g. "home cleaning", "deep cleaning", "carpet cleaning", "cleaning services"), that counts as having service info even if it's one category or one type. Do NOT require an exhaustive list of every sub-service.
2. **Required for "minimum" (ready to show summary and ask to finalize)**: We need at least (a) what services they offer, and (b) where they serve (location/area). If we have both, consider profile_readiness "minimum" and we can ask for availability/pricing as optional or in confirmation.
3. **Required for "complete"**: Same as minimum, plus either availability OR pricing (or both). Prefer to move to "minimum" once we have services + area so we stop drilling into more service subtypes.
4. **required_missing**: Only list fields that are TRULY essential and still empty. E.g. if they said "Noida", service_area is NOT missing. If they said "home cleaning" or "deep cleaning" and we have at least one service category or clear intent, do NOT list service_categories.
5. **optional_missing**: List nice-to-have fields that are still empty (years_experience, licensed, bio, etc.). Keep this list short; don't list everything under the sun.

Output JSON only:
{
  "profile_readiness": "incomplete" | "minimum" | "complete",
  "required_missing": ["list", "of", "field", "names"],
  "optional_missing": ["list", "of", "optional", "field", "names"],
  "reasoning": "one sentence why this readiness"
}

Use "minimum" when we have enough to create a usable profile (e.g. services + location). Use "complete" when we also have availability or pricing. Use "incomplete" only when we're missing something essential like no service type at all or no location.`;

  try {
    const res = await llm.invoke([
      new SystemMessage('You output only valid JSON. Be generous: if the user has stated their service (e.g. home cleaning, deep cleaning) and location, treat as minimum ready.'),
      new HumanMessage(prompt),
    ]);

    let content = res.content.trim();
    content = content.replace(/```json\n?/g, '').replace(/```\n?/g, '');
    const result = JSON.parse(content);

    const profileReadiness = result.profile_readiness === 'complete' ? 'complete'
      : result.profile_readiness === 'minimum' ? 'minimum'
      : 'incomplete';
    const requiredMissing = Array.isArray(result.required_missing) ? result.required_missing : [];
    const optionalMissing = Array.isArray(result.optional_missing) ? result.optional_missing : [];

    console.log(`[SellerCompleteness] Readiness: ${profileReadiness} (LLM: ${result.reasoning || 'n/a'})`);
    console.log(`[SellerCompleteness] Required missing: ${requiredMissing.join(', ') || 'none'}`);
    console.log(`[SellerCompleteness] Optional missing: ${optionalMissing.join(', ') || 'none'}`);

    return {
      requiredMissing,
      optionalMissing,
      profileReadiness,
    };
  } catch (error) {
    console.error('[SellerCompleteness] LLM error:', error.message);
    // Fallback: simple heuristic so we don't block
    const required = [];
    if (!hasServiceCategories) required.push('service_categories');
    if (!hasServiceArea) required.push('service_area');
    const optional = [];
    if (!collected.credentials?.years_experience) optional.push('years_experience');
    if (collected.credentials?.licensed === null) optional.push('licensed');
    if (!collected.bio) optional.push('bio');
    let profileReadiness = 'incomplete';
    if (required.length === 0) profileReadiness = (optional.length > 0 ? 'minimum' : 'complete');
    return {
      requiredMissing: required,
      optionalMissing: optional,
      profileReadiness,
    };
  }
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
- Do NOT ask for "more specific services", "what other cleaning services", or drill into sub-types if the user has already stated their service (e.g. home cleaning, deep cleaning, carpet cleaning). One clear service type is enough—move on to area, availability, pricing, or confirmation.
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
    if (collected.preferences?.min_job_size_hours != null) score += 5;

    // Create or update profile in database
    const profile = await prisma.sellerProfile.upsert({
      where: { id: state.sellerId },
      create: {
        id: state.sellerId,
        serviceCategories: collected.service_categories,
        serviceArea: collected.service_area,
        availability: collected.availability,
        credentials: collected.credentials,
        pricing: collected.pricing,
        preferences: collected.preferences,
        bio: collected.bio,
        profileCompletenessScore: score,
        active: true,
      },
      update: {
        serviceCategories: collected.service_categories,
        serviceArea: collected.service_area,
        availability: collected.availability,
        credentials: collected.credentials,
        pricing: collected.pricing,
        preferences: collected.preferences,
        bio: collected.bio,
        profileCompletenessScore: score,
        active: true,
      },
    });

    console.log(`[BuildProfile] Upserted profile for seller ${state.sellerId}`);
    
    return { 
      profile: {
        seller_id: profile.id,
        service_categories: profile.serviceCategories,
        service_area: profile.serviceArea,
        availability: profile.availability,
        credentials: profile.credentials,
        pricing: profile.pricing,
        preferences: profile.preferences,
        bio: profile.bio,
        profile_completeness_score: profile.profileCompletenessScore,
        active: profile.active,
      },
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
    sellerId,
    accessToken,
    phase: sessionData.phase || 'collection',
    currentMessage: message,
    messages: messages,
    collected: sessionData.state?.collected || {
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
    requiredMissing: sessionData.state?.requiredMissing || ['service_categories', 'service_area', 'availability', 'pricing'],
    optionalMissing: sessionData.state?.optionalMissing || ['years_experience', 'licensed', 'references', 'bio', 'min_job_size'],
    serviceCategories: sessionData.state?.serviceCategories || null,
    profileReadiness: sessionData.state?.profileReadiness || 'incomplete',
    profile: sessionData.state?.profile || null,
  };

  const config = {
    configurable: {
      thread_id: sessionId,
    },
  };

  const result = await sellerProfileGraph.invoke(initialState, config);

  // Save user message
  await messageService.addUserMessage(sessionId, message);

  // Save assistant response
  await messageService.addAssistantMessage(
    sessionId,
    result.response?.message || "I'm here to help you create your profile!",
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
    serviceCategories: result.serviceCategories,
    profileReadiness: result.profileReadiness,
    profile: result.profile,
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
    profileReadiness: result.profileReadiness,
    profile: result.profile,
  };
}