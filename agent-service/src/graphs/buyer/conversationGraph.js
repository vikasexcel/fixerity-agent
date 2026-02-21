import { Annotation, StateGraph, START, END } from '@langchain/langgraph';
import { ChatOpenAI } from '@langchain/openai';
import { HumanMessage, SystemMessage } from '@langchain/core/messages';
import { OPENAI_API_KEY, LARAVEL_API_BASE_URL } from '../../config/index.js';
import { MemorySaver } from '@langchain/langgraph';
import { sessionService, messageService, cacheService, getCustomerUserDetails, upsertJobEmbedding } from '../../services/index.js';
import prisma from '../../prisma/client.js';

/* ================================================================================
   CONVERSATION GRAPH - Dynamic Job Creation Through Natural Conversation
   ================================================================================ */

const MAX_FOLLOW_UP_QUESTIONS = 8;
const COMPLETION_THRESHOLD = 0.75;

/** Log helper: format object as readable key=value lines (no JSON) */
function logObj(prefix, obj, keys) {
  if (!obj || !keys?.length) return;
  const parts = keys.map((k) => {
    const v = obj[k];
    if (v === undefined || v === null) return `${k}=—`;
    if (typeof v === 'object' && !Array.isArray(v)) return `${k}=${Object.keys(v).join(',')}`;
    return `${k}=${v}`;
  });
  console.log(`[ConversationGraph] ${prefix} ${parts.join(' | ')}`);
}

/* -------------------- SERVICE CATEGORIES MANAGER (Using Cache Service) -------------------- */

class ServiceCategoryManager {
  async fetchFromAPI(userId, accessToken) {
    console.log('[ConversationGraph] ServiceCategoryManager.fetchFromAPI called | userId=', userId, '| baseUrl=', LARAVEL_API_BASE_URL);
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
      console.log('[ConversationGraph] customer/home response | status=', data.status, '| servicesCount=', data.services?.length ?? 0);
      if (data.status === 1 && data.services) {
        return data.services;
      }
      console.log('[ConversationGraph] fetchFromAPI returning null (no services)');
      return null;
    } catch (error) {
      console.error('[ConversationGraph] ServiceCategoryManager API fetch error:', error.message);
      return null;
    }
  }

  async fetchProviderFromAPI(providerId, accessToken) {
    console.log('[ConversationGraph] ServiceCategoryManager.fetchProviderFromAPI | providerId=', providerId);
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
      console.log('[ConversationGraph] get-service-list response | status=', data.status, '| listLength=', data.service_category_list?.length ?? 0);
      if (data.status === 1 && data.service_category_list) {
        return data.service_category_list.map((s) => ({
          service_category_id: s.service_cat_id ?? s.service_category_id,
          service_category_name: s.service_cat_name ?? s.service_category_name,
        }));
      }
      return null;
    } catch (error) {
      console.error('[ConversationGraph] ServiceCategoryManager Provider API fetch error:', error.message);
      return null;
    }
  }

  async getCategoriesOrFetch(userId, accessToken) {
    return await cacheService.getServiceCategories(
      async () => await this.fetchFromAPI(userId, accessToken),
      accessToken
    );
  }

  async getProviderCategoriesOrFetch(providerId, accessToken) {
    return await cacheService.getOrFetch(
      'service_categories:provider',
      async () => await this.fetchProviderFromAPI(providerId, accessToken),
      86400
    );
  }

  async findCategory(userInput, categories, llm) {
    console.log('[ConversationGraph] findCategory | userInput=', (userInput || '').slice(0, 80), '| categoriesCount=', categories?.length ?? 0);
    if (!categories || categories.length === 0) {
      console.log('[ConversationGraph] findCategory returning null (no categories)');
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

      const parsed = tryParseJsonResponse(res.content);
      console.log('[ConversationGraph] findCategory result | matched=', parsed?.matched, '| category_id=', parsed?.category_id, '| confidence=', parsed?.confidence);
      return parsed;
    } catch (error) {
      console.error('[ConversationGraph] ServiceCategoryManager LLM matching error:', error.message);
      return null;
    }
  }
}

export const serviceCategoryManager = new ServiceCategoryManager();

function initialCollected() {
  return {
    service_category_id: null,
    service_category_name: null,
    title: null,
    description: null,
    budget: { min: null, max: null },
    startDate: null,
    endDate: null,
    priorities: [],
    location: null,
    slots: {
      intent_summary: null,
      service: null,
      scope: null,
      location: null,
      timeline: null,
      budget: null,
      deliverables: null,
      constraints: null,
    },
    assumptions: [],
    questionCount: 0,
    completion: {
      ready: false,
      confidence: 0,
      missingCritical: ['service', 'scope'],
      assumptions: [],
    },
  };
}

function mergeCollected(existing, updates) {
  const next = {
    ...existing,
    ...updates,
    budget: {
      min: updates?.budget?.min ?? existing?.budget?.min ?? null,
      max: updates?.budget?.max ?? existing?.budget?.max ?? null,
    },
    slots: {
      ...(existing?.slots || {}),
      ...(updates?.slots || {}),
    },
  };

  if (Array.isArray(updates?.assumptions)) {
    const prior = Array.isArray(existing?.assumptions) ? existing.assumptions : [];
    const map = new Map(prior.map((a) => [a.key, a]));
    for (const a of updates.assumptions) {
      if (!a?.key) continue;
      map.set(a.key, a);
    }
    next.assumptions = Array.from(map.values());
  }

  return next;
}

function tryParseJsonResponse(content) {
  let text = typeof content === 'string' ? content.trim() : String(content || '').trim();
  text = text.replace(/```json\n?/g, '').replace(/```\n?/g, '');
  try {
    return JSON.parse(text);
  } catch {
    return null;
  }
}

function toLegacyMissing(completion, collected) {
  const required = [];
  const optional = [];

  if (!collected?.service_category_name) required.push('service_category');
  if (!collected?.description && !collected?.title) optional.push('scope');

  if (!collected?.budget?.max) optional.push('budget_max');
  if (!collected?.startDate) optional.push('start_date');
  if (!collected?.location) optional.push('location');
  if (!collected?.endDate) optional.push('end_date');

  let jobReadiness = 'incomplete';
  if (completion?.ready) {
    jobReadiness = optional.length === 0 ? 'complete' : 'minimum';
  }

  return { required, optional, jobReadiness };
}

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
    reducer: (existing, updates) => mergeCollected(existing, updates),
    default: () => initialCollected(),
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
  planner: Annotation(),
  response: Annotation(),

  jobReadiness: Annotation(),
  completion: Annotation(),

  job: Annotation(),
  serviceCategories: Annotation(),
  error: Annotation(),
});

/* -------------------- INTENT DETECTION NODE -------------------- */

async function detectIntentNode(state) {
  console.log('[ConversationGraph] --- NODE: detect_intent ---');
  console.log('[ConversationGraph] currentMessage=', (state.currentMessage || '').slice(0, 120), '| messagesCount=', state.messages?.length ?? 0);
  const llm = new ChatOpenAI({
    model: 'gpt-4o-mini',
    temperature: 0,
    openAIApiKey: OPENAI_API_KEY,
  });

  const recentMessages = state.messages.slice(-6).map(m =>
    `${m.role.toUpperCase()}: ${m.content}`
  ).join('\n');

  const prompt = `
You classify buyer intent in a service marketplace chat.

Conversation context:
${recentMessages || 'No previous messages'}

Current user message: "${state.currentMessage}"

Classify into exactly one:
- "confirm"
- "decline_confirmation"
- "cancel"
- "ask_question"
- "provide_info"
- "modify_info"
- "other"

Also detect if user allows assumptions.

Return JSON only:
{
  "intent": "<one label>",
  "confidence": "high|medium|low",
  "allow_assumptions": true/false,
  "reasoning": "<brief>"
}
`;

  try {
    const res = await llm.invoke([
      new SystemMessage('Only output valid JSON.'),
      new HumanMessage(prompt),
    ]);

    const intent = tryParseJsonResponse(res.content);
    if (intent?.intent) {
      console.log('[ConversationGraph] detect_intent result | intent=', intent.intent, '| confidence=', intent.confidence, '| allow_assumptions=', intent.allow_assumptions);
      return { intent };
    }
  } catch (error) {
    console.error('[ConversationGraph] detect_intent error:', error.message);
  }

  console.log('[ConversationGraph] detect_intent fallback | intent=provide_info');
  return {
    intent: {
      intent: 'provide_info',
      confidence: 'low',
      allow_assumptions: false,
      reasoning: 'Fallback intent',
    }
  };
}

/* -------------------- INFORMATION EXTRACTION NODE -------------------- */

async function extractInfoNode(state) {
  console.log('[ConversationGraph] --- NODE: extract_info ---');
  console.log('[ConversationGraph] currentMessage=', (state.currentMessage || '').slice(0, 120));
  logObj('collected (in)', state.collected, ['service_category_name', 'title', 'description', 'startDate', 'endDate', 'location']);
  const llm = new ChatOpenAI({
    model: 'gpt-4o-mini',
    temperature: 0,
    openAIApiKey: OPENAI_API_KEY,
  });

  const prompt = `
Extract buyer job information from this message.

User message: "${state.currentMessage}"
Known data:
${JSON.stringify(state.collected, null, 2)}

Today: ${new Date().toISOString().split('T')[0]}

Rules:
- Extract only what is stated or strongly implied.
- If buyer says AI should decide (e.g. "you decide", "not sure", "use standard"), set allow_assumptions true.
- Keep output concise and structured.

Return JSON only:
{
  "found_new_info": true/false,
  "allow_assumptions": true/false,
  "extracted": {
    "service_category_name": "<string|null>",
    "title": "<string|null>",
    "description": "<string|null>",
    "budget": { "min": <number|null>, "max": <number|null> },
    "startDate": "<YYYY-MM-DD|ASAP|null>",
    "endDate": "<YYYY-MM-DD|flexible|null>",
    "location": "<string|null>",
    "priorities": [{ "type": "<price|rating|licensed|references|startDate|endDate>", "level": "<must_have|nice_to_have|bonus>", "value": "<value>" }],
    "slots": {
      "intent_summary": "<string|null>",
      "service": "<string|null>",
      "scope": "<string|null>",
      "location": "<string|null>",
      "timeline": "<string|null>",
      "budget": "<string|null>",
      "deliverables": "<string|null>",
      "constraints": "<string|null>"
    }
  }
}
`;

  try {
    const res = await llm.invoke([
      new SystemMessage('Only output valid JSON. Extract accurately.'),
      new HumanMessage(prompt),
    ]);

    const extraction = tryParseJsonResponse(res.content);
    if (extraction?.extracted) {
      console.log('[ConversationGraph] extract_info result | found_new_info=', extraction.found_new_info, '| allow_assumptions=', extraction.allow_assumptions);
      const e = extraction.extracted;
      if (e) logObj('extracted fields', e, ['service_category_name', 'title', 'description', 'startDate', 'endDate', 'location']);
      return { extraction };
    }
  } catch (error) {
    console.error('[ConversationGraph] extract_info error:', error.message);
  }

  console.log('[ConversationGraph] extract_info fallback (no new info)');
  return {
    extraction: {
      found_new_info: false,
      allow_assumptions: false,
      extracted: {},
    }
  };
}

/* -------------------- UPDATE COLLECTED DATA NODE -------------------- */

function updateCollectedNode(state) {
  console.log('[ConversationGraph] --- NODE: update_collected ---');
  const extraction = state.extraction || {};
  const extracted = extraction.extracted || {};
  const updates = {};

  if (extracted.service_category_name) {
    updates.service_category_name = String(extracted.service_category_name).trim();
    updates.slots = {
      ...(updates.slots || {}),
      service: String(extracted.service_category_name).trim(),
    };
  }
  if (extracted.title) updates.title = extracted.title;
  if (extracted.description) {
    updates.description = extracted.description;
    updates.slots = {
      ...(updates.slots || {}),
      scope: extracted.description,
    };
  }

  if (extracted.budget?.min != null || extracted.budget?.max != null) {
    updates.budget = {
      min: extracted.budget?.min ?? state.collected.budget?.min,
      max: extracted.budget?.max ?? state.collected.budget?.max,
    };
    updates.slots = {
      ...(updates.slots || {}),
      budget: `$${updates.budget.min ?? '?'}-$${updates.budget.max ?? '?'}`,
    };
  }

  if (extracted.startDate) updates.startDate = extracted.startDate;
  if (extracted.endDate) updates.endDate = extracted.endDate;
  if (extracted.startDate || extracted.endDate) {
    const start = extracted.startDate ?? state.collected.startDate ?? 'flexible';
    const end = extracted.endDate ?? state.collected.endDate ?? 'flexible';
    updates.slots = {
      ...(updates.slots || {}),
      timeline: `${start} to ${end}`,
    };
  }

  if (extracted.location) {
    updates.location = extracted.location;
    updates.slots = {
      ...(updates.slots || {}),
      location: extracted.location,
    };
  }

  if (Array.isArray(extracted.priorities) && extracted.priorities.length > 0) {
    const existingPriorities = Array.isArray(state.collected.priorities) ? state.collected.priorities : [];
    const next = [...existingPriorities];
    for (const p of extracted.priorities) {
      if (!p?.type) continue;
      const idx = next.findIndex((x) => x.type === p.type);
      if (idx >= 0) next[idx] = p;
      else next.push(p);
    }
    updates.priorities = next;
  }

  if (extracted.slots && typeof extracted.slots === 'object') {
    updates.slots = {
      ...(updates.slots || {}),
      ...extracted.slots,
    };
  }

  const updateKeys = Object.keys(updates).filter((k) => k !== 'slots');
  console.log('[ConversationGraph] update_collected applying keys:', updateKeys.length ? updateKeys.join(', ') : 'none');
  if (updates.service_category_name) console.log('[ConversationGraph]   service_category_name=', updates.service_category_name);
  if (updates.title) console.log('[ConversationGraph]   title=', updates.title);
  if (updates.budget?.min != null || updates.budget?.max != null) console.log('[ConversationGraph]   budget min=', updates.budget?.min, 'max=', updates.budget?.max);
  if (updates.startDate) console.log('[ConversationGraph]   startDate=', updates.startDate);
  if (updates.endDate) console.log('[ConversationGraph]   endDate=', updates.endDate);
  if (updates.location) console.log('[ConversationGraph]   location=', updates.location);
  return { collected: updates };
}

/* -------------------- COMPLETENESS + ASSUMPTIONS NODE -------------------- */

function applyAssumption(collected, key, value, reason) {
  const assumptions = Array.isArray(collected.assumptions) ? [...collected.assumptions] : [];
  const idx = assumptions.findIndex((a) => a.key === key);
  const next = { key, value, reason };
  if (idx >= 0) assumptions[idx] = next;
  else assumptions.push(next);
  return assumptions;
}

function checkCompletenessNode(state) {
  console.log('[ConversationGraph] --- NODE: check_completeness ---');
  const collected = { ...state.collected };
  const allowAssumptions = true;

  const hasService = !!(collected.service_category_name || collected.slots?.service);
  const hasScope = !!(collected.description || collected.title || collected.slots?.scope || state.currentMessage);
  console.log('[ConversationGraph] check_completeness | hasService=', hasService, '| hasScope=', hasScope, '| questionCount=', collected.questionCount ?? 0);

  if (allowAssumptions) {
    if (!collected.budget?.max) {
      collected.budget = { min: collected.budget?.min ?? 100, max: 500 };
      collected.assumptions = applyAssumption(collected, 'budget', '$100-$500 placeholder', 'Buyer asked AI to decide budget details.');
      collected.slots = { ...(collected.slots || {}), budget: '$100-$500 placeholder' };
    }
    if (!collected.startDate) {
      collected.startDate = 'ASAP';
      collected.assumptions = applyAssumption(collected, 'timeline_start', 'ASAP', 'Buyer asked AI to decide timeline.');
    }
    if (!collected.endDate) {
      collected.endDate = 'flexible';
      collected.assumptions = applyAssumption(collected, 'timeline_end', 'flexible', 'Buyer asked AI to decide timeline.');
      collected.slots = { ...(collected.slots || {}), timeline: 'ASAP to flexible' };
    }
    if (!collected.location) {
      collected.location = 'To be confirmed after provider shortlist';
      collected.assumptions = applyAssumption(collected, 'location', 'to be confirmed', 'Buyer asked AI to decide location details for now.');
      collected.slots = { ...(collected.slots || {}), location: 'To be confirmed after provider shortlist' };
    }
  }

  if (!collected.description && typeof state.currentMessage === 'string' && state.currentMessage.trim().length > 10) {
    collected.description = state.currentMessage.trim();
    collected.slots = { ...(collected.slots || {}), scope: collected.description };
  }

  const missingCritical = [];
  if (!hasService) missingCritical.push('service');

  let confidence = 0;
  if (hasService) confidence += 0.50;
  if (hasScope) confidence += 0.25;
  if (collected.budget?.max) confidence += 0.10;
  if (collected.startDate || collected.endDate) confidence += 0.08;
  if (collected.location) confidence += 0.07;

  const ready = missingCritical.length === 0 &&
    (confidence >= COMPLETION_THRESHOLD || (collected.questionCount || 0) >= MAX_FOLLOW_UP_QUESTIONS);

  const completion = {
    ready,
    confidence: Number(confidence.toFixed(2)),
    missingCritical,
    assumptions: Array.isArray(collected.assumptions) ? collected.assumptions : [],
  };

  const legacy = toLegacyMissing(completion, collected);

  console.log('[ConversationGraph] check_completeness result | ready=', completion.ready, '| confidence=', completion.confidence, '| missingCritical=', (completion.missingCritical || []).join(',') || 'none');
  console.log('[ConversationGraph]   jobReadiness=', legacy.jobReadiness, '| requiredMissing=', legacy.required?.join(', ') || '—', '| optionalMissing=', legacy.optional?.join(', ') || '—');

  return {
    collected,
    completion,
    requiredMissing: legacy.required,
    optionalMissing: legacy.optional,
    jobReadiness: legacy.jobReadiness,
  };
}

/* -------------------- DYNAMIC PLANNER NODE -------------------- */

async function planNextNode(state) {
  console.log('[ConversationGraph] --- NODE: plan_next ---');
  if (state.error) {
    console.log('[ConversationGraph] plan_next | routing=error | question=', (state.error || '').slice(0, 60));
    return {
      planner: {
        type: 'question',
        question: state.error,
        action: 'error',
      }
    };
  }

  if (state.intent?.intent === 'cancel') {
    console.log('[ConversationGraph] plan_next | routing=cancel (cancelled)');
    return {
      planner: {
        type: 'question',
        question: "No problem. If you want to start over, tell me what you need and I'll build the job post with you.",
        action: 'cancelled',
      }
    };
  }

  if (state.intent?.intent === 'decline_confirmation' && state.phase === 'confirmation') {
    console.log('[ConversationGraph] plan_next | routing=decline_confirmation (asking_dynamic)');
    return {
      planner: {
        type: 'question',
        question: 'Got it. What should I change before I post it?',
        action: 'asking_dynamic',
      }
    };
  }

  if (state.completion?.ready) {
    console.log('[ConversationGraph] plan_next | routing=ready_for_confirmation');
    return {
      planner: {
        type: 'ready_for_confirmation',
        action: 'ready_for_confirmation',
      }
    };
  }

  const llm = new ChatOpenAI({
    model: 'gpt-4o-mini',
    temperature: 0.4,
    openAIApiKey: OPENAI_API_KEY,
  });

  const recentMessages = state.messages.slice(-6).map(m => `${m.role.toUpperCase()}: ${m.content}`).join('\n');
  const prompt = `
You are a buyer-job interview planner.

Context:
${recentMessages || 'No previous messages'}

Known collected data:
${JSON.stringify(state.collected, null, 2)}

Completion:
${JSON.stringify(state.completion, null, 2)}

Rules:
- No mention of forms or standard fields.
- Ask exactly one high-impact question.
- Keep question short and conversational.
- Prioritize missing critical items (service, scope) before optional details.
- If you judge details are sufficient, set type=ready_for_confirmation.

Return JSON only:
{
  "type": "question|ready_for_confirmation",
  "question": "<string|null>",
  "action": "asking_dynamic|ready_for_confirmation"
}
`;

  try {
    const res = await llm.invoke([
      new SystemMessage('Only output valid JSON.'),
      new HumanMessage(prompt),
    ]);

    const planner = tryParseJsonResponse(res.content);
    if (planner?.type === 'question' && planner?.question) {
      console.log('[ConversationGraph] plan_next LLM result | type=question | question=', (planner.question || '').slice(0, 80));
      return { planner };
    }
    if (planner?.type === 'ready_for_confirmation') {
      console.log('[ConversationGraph] plan_next LLM result | type=ready_for_confirmation');
      return { planner };
    }
  } catch (error) {
    console.error('[ConversationGraph] plan_next error:', error.message);
  }

  console.log('[ConversationGraph] plan_next fallback | default question');
  return {
    planner: {
      type: 'question',
      question: 'Can you briefly describe the service outcome you want?',
      action: 'asking_dynamic',
    }
  };
}

/* -------------------- CONFIRMATION NODE -------------------- */

async function confirmationNode(state) {
  console.log('[ConversationGraph] --- NODE: confirmation ---');
  const collected = state.collected || {};
  const assumptions = Array.isArray(collected.assumptions) ? collected.assumptions : [];
  const serviceName = collected.service_category_name || collected.slots?.service || 'Service Professional';
  console.log('[ConversationGraph] confirmation | service=', serviceName, '| assumptionsCount=', assumptions.length);
  const scope = collected.description || collected.title || collected.slots?.scope || `Need a ${serviceName} for a new project.`;
  const budgetText = collected.budget?.max ? `$${collected.budget?.min ?? '?'}-$${collected.budget.max}` : 'To be finalized with shortlisted providers';
  const timelineText = `${collected.startDate || 'ASAP'} to ${collected.endDate || 'flexible'}`;
  const locationText = collected.location || 'To be shared with shortlisted providers';

  const llm = new ChatOpenAI({
    model: 'gpt-4o-mini',
    temperature: 0.3,
    openAIApiKey: OPENAI_API_KEY,
  });

  const prompt = `
You are an expert marketplace buyer agent.

Create a short, publish-ready job post in ONE response.
Do not ask clarifying questions.
Use smart assumptions where needed.
Keep it concise and professional.

Input:
- Service: ${serviceName}
- Scope: ${scope}
- Budget: ${budgetText}
- Timeline: ${timelineText}
- Location: ${locationText}
- Assumptions: ${assumptions.length ? assumptions.map((a) => `${a.key}: ${a.value}`).join('; ') : 'none'}

Output structure (plain text):
1) A strong job title line
2) Short project overview (2-3 lines)
3) Responsibilities (4-6 bullets)
4) Requirements (4-6 bullets)
5) Deliverables (3-5 bullets)
6) Project details line: budget, timeline, location

Return JSON only:
{ "draft": "<the full job post text>" }
`;

  try {
    const res = await llm.invoke([
      new SystemMessage('Only output valid JSON.'),
      new HumanMessage(prompt),
    ]);
    const parsed = tryParseJsonResponse(res.content);
    if (parsed?.draft) {
      console.log('[ConversationGraph] confirmation | draft generated (LLM) | length=', parsed.draft?.length ?? 0);
      return {
        response: {
          message: `${parsed.draft}\n\nProceed to post this now? (yes/no)`,
          action: 'awaiting_confirmation',
        },
        phase: 'confirmation',
      };
    }
  } catch (error) {
    console.error('[ConversationGraph] confirmation draft generation error:', error.message);
  }

  console.log('[ConversationGraph] confirmation | using fallback draft');
  const fallbackDraft = `${serviceName} Needed\n\nProject: ${scope}\n\nBudget: ${budgetText}\nTimeline: ${timelineText}\nLocation: ${locationText}`;
  return {
    response: {
      message: `${fallbackDraft}\n\nProceed to post this now? (yes/no)`,
      action: 'awaiting_confirmation',
    },
    phase: 'confirmation',
  };
}

/* -------------------- GENERATE RESPONSE NODE -------------------- */

function generateResponseNode(state) {
  console.log('[ConversationGraph] --- NODE: generate_response ---');
  if (state.response?.message) {
    console.log('[ConversationGraph] generate_response | already have response, skip | length=', state.response.message?.length ?? 0);
    return {};
  }

  const planner = state.planner || {};
  if (planner.type === 'question' && planner.question) {
    console.log('[ConversationGraph] generate_response | using planner question | action=', planner.action);
    const currentCount = Number(state.collected?.questionCount || 0);
    return {
      response: {
        message: planner.question,
        action: planner.action || 'asking_dynamic',
      },
      collected: {
        questionCount: currentCount + 1,
      },
    };
  }

  console.log('[ConversationGraph] generate_response | fallback default question');
  return {
    response: {
      message: 'Can you briefly describe the service outcome you want?',
      action: 'asking_dynamic',
    },
    collected: {
      questionCount: Number(state.collected?.questionCount || 0) + 1,
    },
  };
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

function assumptionsToRequirements(assumptions) {
  if (!Array.isArray(assumptions) || assumptions.length === 0) return null;
  return {
    assumptions: assumptions.map((a) => ({
      key: a.key,
      value: a.value,
      reason: a.reason || null,
    })),
  };
}

async function buildJobNode(state) {
  console.log('[ConversationGraph] --- NODE: build_job ---');
  const collected = state.collected;
  const serviceName = collected.service_category_name || collected.slots?.service;
  console.log('[ConversationGraph] build_job | serviceName=', serviceName, '| buyerId=', state.buyerId);

  if (!serviceName) {
    console.log('[ConversationGraph] build_job | abort: no service name, asking for service');
    return {
      response: {
        message: 'Before I post this, what exact service do you want to hire for?',
        action: 'asking_dynamic',
      },
      phase: 'conversation',
      completion: {
        ...(state.completion || {}),
        ready: false,
      },
    };
  }

  const jobId = `job_${state.buyerId}_${Date.now()}`;
  console.log('[ConversationGraph] build_job | jobId=', jobId);

  const budgetMax = collected.budget?.max ?? null;
  const budgetMin = collected.budget?.min ?? (budgetMax != null ? Math.floor(budgetMax * 0.5) : 100);
  console.log('[ConversationGraph] build_job | budget min=', budgetMin, 'max=', budgetMax);

  const buyerDetails = await getCustomerUserDetails(state.buyerId);
  console.log('[ConversationGraph] build_job | buyerDetails found=', !!buyerDetails);

  const generatedScope = collected.description || collected.slots?.scope || `Looking for ${serviceName}`;
  const generatedTitle = collected.title || `${serviceName} - ${generatedScope.slice(0, 60)}`;

  const assumptions = Array.isArray(collected.assumptions) ? collected.assumptions : [];
  const assumptionText = assumptions.length
    ? `\n\nAssumptions used: ${assumptions.map((a) => `${a.key}: ${a.value}`).join('; ')}`
    : '';

  const payload = {
    id: jobId,
    buyerId: state.buyerId,
    firstName: buyerDetails?.firstName ?? null,
    lastName: buyerDetails?.lastName ?? null,
    email: buyerDetails?.email ?? null,
    contactNumber: buyerDetails?.contactNumber ?? null,
    serviceCategoryId: collected.service_category_id ?? null,
    serviceCategoryName: serviceName,
    title: generatedTitle,
    description: `${generatedScope}${assumptionText}`,
    budget: { min: budgetMin, max: budgetMax },
    startDate: collected.startDate || 'ASAP',
    endDate: collected.endDate || 'flexible',
    location: normalizeLocation(collected.location),
    priorities: collected.priorities?.length ? collected.priorities : null,
    specificRequirements: assumptionsToRequirements(assumptions),
    status: 'open',
  };

  try {
    console.log('[ConversationGraph] build_job | creating JobListing in DB');
    const created = await prisma.jobListing.create({
      data: payload,
    });
    console.log('[ConversationGraph] build_job | JobListing created | id=', created.id);

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

    upsertJobEmbedding(created.id, created).catch((err) => {
      console.error('[ConversationGraph] build_job job embedding failed:', err.message);
    });

    console.log('[ConversationGraph] build_job | success | phase=complete');
    return { job, phase: 'complete' };
  } catch (error) {
    console.error('[ConversationGraph] build_job JobListing.create failed:', error.message);
    return {
      job: null,
      phase: 'conversation',
      error: 'Failed to create job. Please try again.',
      response: {
        message: 'I could not create the job right now. Please try again in a moment.',
        action: 'error',
      },
    };
  }
}

/* -------------------- ROUTING FUNCTIONS -------------------- */

function routeAfterIntent(state) {
  const intent = state.intent?.intent;
  const phase = state.phase;
  let route = 'extract_info';
  if (intent === 'confirm' && phase === 'confirmation') route = 'build_job';
  else if ((intent === 'decline_confirmation' || intent === 'modify_info') && phase === 'confirmation') route = 'extract_info';
  console.log('[ConversationGraph] ROUTE after intent | intent=', intent, '| phase=', phase, '| next=', route);
  return route;
}

function routeAfterPlanning(state) {
  const next = state.planner?.type === 'ready_for_confirmation' ? 'confirmation' : 'generate_response';
  console.log('[ConversationGraph] ROUTE after planning | planner.type=', state.planner?.type, '| next=', next);
  return next;
}

/* -------------------- GRAPH DEFINITION -------------------- */

const workflow = new StateGraph(ConversationState)
  .addNode('detect_intent', detectIntentNode)
  .addNode('extract_info', extractInfoNode)
  .addNode('update_collected', updateCollectedNode)
  .addNode('check_completeness', checkCompletenessNode)
  .addNode('plan_next', planNextNode)
  .addNode('confirmation', confirmationNode)
  .addNode('generate_response', generateResponseNode)
  .addNode('build_job', buildJobNode)

  .addEdge(START, 'detect_intent')

  .addConditionalEdges('detect_intent', routeAfterIntent, {
    'extract_info': 'extract_info',
    'build_job': 'build_job',
  })

  .addEdge('extract_info', 'update_collected')
  .addEdge('update_collected', 'check_completeness')
  .addEdge('check_completeness', 'plan_next')

  .addConditionalEdges('plan_next', routeAfterPlanning, {
    'confirmation': 'confirmation',
    'generate_response': 'generate_response',
  })

  .addEdge('confirmation', 'generate_response')
  .addEdge('build_job', 'generate_response')
  .addEdge('generate_response', END);

const checkpointer = new MemorySaver();
export const conversationGraph = workflow.compile({ checkpointer });

/* -------------------- RUNNER FUNCTION -------------------- */

function migrateCollectedState(raw) {
  console.log('[ConversationGraph] migrateCollectedState | hasRaw=', !!raw);
  const base = initialCollected();
  if (!raw || typeof raw !== 'object') return base;

  const merged = mergeCollected(base, raw);

  if (!merged.slots?.service && merged.service_category_name) {
    merged.slots = { ...(merged.slots || {}), service: merged.service_category_name };
  }

  if (!merged.slots?.scope && (merged.description || merged.title)) {
    merged.slots = { ...(merged.slots || {}), scope: merged.description || merged.title };
  }

  if (!Array.isArray(merged.assumptions)) merged.assumptions = [];
  if (!Number.isFinite(merged.questionCount)) merged.questionCount = 0;
  if (!merged.completion || typeof merged.completion !== 'object') {
    merged.completion = {
      ready: false,
      confidence: 0,
      missingCritical: ['service', 'scope'],
      assumptions: merged.assumptions,
    };
  }

  return merged;
}

export async function runConversation(input) {
  const { sessionId, buyerId, accessToken, message } = input;
  console.log('[ConversationGraph] ========== runConversation START ==========');
  console.log('[ConversationGraph] input | sessionId=', sessionId, '| buyerId=', buyerId, '| messageLength=', (message || '').length);

  const sessionData = await sessionService.getSessionWithContext(sessionId, 50);
  console.log('[ConversationGraph] session loaded | phase=', sessionData.phase, '| messagesCount=', sessionData.messages?.length ?? 0);

  const messages = sessionData.messages.map((m) => ({
    role: m.role,
    content: m.content,
    timestamp: m.createdAt,
  }));

  const migratedCollected = migrateCollectedState(sessionData.state?.collected);
  const completion = sessionData.state?.completion || migratedCollected.completion;
  const legacy = toLegacyMissing(completion, migratedCollected);

  const initialState = {
    sessionId,
    buyerId,
    accessToken,
    phase: sessionData.phase || 'conversation',
    currentMessage: message,
    messages,
    collected: migratedCollected,
    requiredMissing: sessionData.state?.requiredMissing || legacy.required,
    optionalMissing: sessionData.state?.optionalMissing || legacy.optional,
    serviceCategories: sessionData.state?.serviceCategories || null,
    jobReadiness: sessionData.state?.jobReadiness || legacy.jobReadiness,
    completion,
    job: sessionData.state?.job || null,
  };

  const config = {
    configurable: {
      thread_id: sessionId,
    },
  };

  console.log('[ConversationGraph] invoking graph | phase=', initialState.phase, '| messagesCount=', initialState.messages?.length ?? 0);
  const result = await conversationGraph.invoke(initialState, config);
  console.log('[ConversationGraph] graph finished | result.phase=', result.phase, '| hasResponse=', !!result.response?.message, '| hasJob=', !!result.job);

  await messageService.addUserMessage(sessionId, message);

  await messageService.addAssistantMessage(
    sessionId,
    result.response?.message || "I'm here to help!",
    {
      action: result.response?.action,
      intent: result.intent,
      completion: result.completion,
    }
  );

  console.log('[ConversationGraph] persisting: user message, assistant message, session state');
  await sessionService.updateState(sessionId, {
    collected: result.collected,
    requiredMissing: result.requiredMissing,
    optionalMissing: result.optionalMissing,
    jobReadiness: result.jobReadiness,
    completion: result.completion,
    job: result.job,
  });

  if (result.phase && result.phase !== sessionData.phase) {
    console.log('[ConversationGraph] phase change |', sessionData.phase, '->', result.phase);
    await sessionService.updatePhase(sessionId, result.phase);
  }

  console.log('[ConversationGraph] ========== runConversation END ========== phase=', result.phase || sessionData.phase);
  return {
    sessionId,
    phase: result.phase || sessionData.phase,
    response: result.response?.message || "I'm here to help!",
    action: result.response?.action,
    collected: result.collected,
    requiredMissing: result.requiredMissing,
    optionalMissing: result.optionalMissing,
    jobReadiness: result.jobReadiness,
    completion: result.completion,
    job: result.job,
  };
}
