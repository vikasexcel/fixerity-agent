import { HumanMessage, SystemMessage } from '@langchain/core/messages';
import { sessionService, messageService } from '../../services/index.js';
import { createProviderAgentTools } from './providerAgentTools.js';
import { createProviderAgentGraph } from './providerAgentGraph.js';
import { serviceCategoryManager } from '../../services/serviceCategoryManager.js';
import { logProviderConversation } from '../../utils/providerProfileLogger.js';

/* ================================================================================
   PROVIDER CONVERSATION GRAPH - Profile Creation Through Conversational Agent
   No predefined fields. AI asks domain-specific questions based on provider's specialty.
   ================================================================================ */

function getLastAIMessageText(messages) {
  for (let i = messages.length - 1; i >= 0; i--) {
    const m = messages[i];
    const type = m._getType?.() ?? m.constructor?.name ?? '';
    if (type === 'ai' || type === 'AIMessage') {
      const content = m.content;
      if (typeof content === 'string') return content;
      if (Array.isArray(content) && content.length > 0) {
        const first = content[0];
        if (first?.type === 'text') return first.text ?? '';
      }
      return '';
    }
  }
  return '';
}

function extractProfileFromToolMessages(messages) {
  for (let i = messages.length - 1; i >= 0; i--) {
    const m = messages[i];
    const type = m._getType?.() ?? m.constructor?.name ?? '';
    if (type === 'tool' || type === 'ToolMessage') {
      const content = m.content;
      const str = Array.isArray(content) ? content.map(c => c?.content ?? '').join('') : String(content ?? '');
      try {
        const parsed = JSON.parse(str);
        if (parsed?.success === true && parsed?.profile) return parsed.profile;
      } catch {
        // not JSON or parse failed
      }
    }
  }
  return null;
}

function formatProfileCreatedResponse(profile) {
  const marketplace = profile.preferences?.marketplace_profile && typeof profile.preferences.marketplace_profile === 'object'
    ? profile.preferences.marketplace_profile
    : {};
  const parts = [
    "Your profile has been successfully created! Here are the details:\n",
  ];
  const fullName = [profile.first_name, profile.last_name].filter(Boolean).join(' ').trim();
  if (fullName) {
    parts.push(`**Name:** ${fullName}\n`);
  }
  if (profile.contact_number || profile.email) {
    parts.push(`**Contact:** ${[profile.contact_number, profile.email].filter(Boolean).join(' | ')}\n`);
  }
  if (marketplace.service_title) {
    parts.push(`**Service title:** ${marketplace.service_title}\n`);
  }
  if (marketplace.tagline) {
    parts.push(`**Tagline:** ${marketplace.tagline}\n`);
  }
  parts.push(
    `**Services:** ${(profile.service_category_names || []).join(', ') || 'Not specified'}\n`,
  );
  if (marketplace.short_description) {
    parts.push(`\n**Short description:** ${marketplace.short_description}\n`);
  }
  if (marketplace.long_description) {
    parts.push(`\n**Detailed description:** ${marketplace.long_description}\n`);
  }
  if (profile.bio) {
    parts.push(`\n**Bio:** ${profile.bio}\n`);
  }
  const area = profile.service_area;
  if (area && (typeof area === 'object' ? area.location : area)) {
    parts.push(`\n**Service area:** ${typeof area === 'object' ? area.location : area}\n`);
  }
  const languages = Array.isArray(marketplace.languages_spoken) ? marketplace.languages_spoken.filter(Boolean) : [];
  if (languages.length > 0) {
    parts.push(`\n**Languages spoken:** ${languages.join(', ')}\n`);
  }
  if (marketplace.delivery_or_completion_time) {
    parts.push(`\n**Delivery / completion time:** ${marketplace.delivery_or_completion_time}\n`);
  }

  // Availability
  const avail = profile.availability;
  if (avail && typeof avail === 'object') {
    const ap = [];
    if (avail.schedule) ap.push(avail.schedule);
    if (avail.weekdays) ap.push(`Weekdays: ${avail.weekdays}`);
    if (avail.weekends && String(avail.weekends).toLowerCase().trim() !== 'not available') ap.push(`Weekends: ${avail.weekends}`);
    if (avail.emergency) ap.push('Emergency availability');
    if (ap.length > 0) parts.push(`\n**Availability:** ${ap.join('. ')}\n`);
  }

  // Credentials
  const cred = profile.credentials;
  if (cred && typeof cred === 'object') {
    const cp = [];
    if (cred.licensed === true) cp.push('Licensed');
    if (cred.insured === true) cp.push('Insured');
    if (cred.years_experience != null) cp.push(`${cred.years_experience} years experience`);
    if (cred.certifications?.length) cp.push(`Certifications: ${cred.certifications.join(', ')}`);
    if (cred.certifications_not_held?.length) cp.push(`Not certified: ${cred.certifications_not_held.join(', ')}`);
    if (cred.references_available === true) cp.push('References available');
    if (cp.length > 0) parts.push(`\n**Credentials:** ${cp.join('. ')}\n`);
  }

  // Conversation-derived details (equipment, materials, project types, etc.)
  const conv = profile.preferences?.conversation_profile;
  if (conv && typeof conv === 'object' && Object.keys(conv).length > 0) {
    const cp = [];
    if (conv.equipment?.length) cp.push(`**Equipment:** ${conv.equipment.join(', ')}`);
    if (conv.materials?.length) cp.push(`**Materials:** ${conv.materials.join(', ')}`);
    if (conv.project_focus) cp.push(`**Project focus:** ${conv.project_focus}`);
    if (conv.project_sizes_sqft) {
      const ps = conv.project_sizes_sqft;
      if (typeof ps === 'object' && (ps.min != null || ps.max != null)) {
        cp.push(`**Project sizes:** ${ps.min ?? '?'}–${ps.max ?? '?'} sq ft`);
      } else if (typeof ps === 'string' && ps.trim()) {
        cp.push(`**Project sizes:** ${ps.trim()}`);
      }
    }
    if (conv.additional_services?.length) cp.push(`**Also offers:** ${conv.additional_services.join(', ')}`);
    Object.entries(conv).forEach(([k, v]) => {
      if (['equipment', 'materials', 'project_focus', 'project_sizes_sqft', 'additional_services', 'location', 'availability', 'service_area', 'languages_spoken'].includes(k)) return;
      if (Array.isArray(v) && v.length > 0) cp.push(`**${k.replace(/_/g, ' ')}:** ${v.join(', ')}`);
      else if (typeof v === 'string' && v.trim()) cp.push(`**${k.replace(/_/g, ' ')}:** ${v.trim()}`);
      else if (typeof v === 'boolean' && v) cp.push(`**${k.replace(/_/g, ' ')}:** Yes`);
    });
    if (cp.length > 0) parts.push('\n' + cp.join('\n') + '\n');
  }

  // Pricing
  const pricing = profile.pricing;
  if (pricing && (pricing.hourly_rate_max != null || (pricing.fixed_prices && Object.keys(pricing.fixed_prices).length > 0))) {
    const min = pricing.hourly_rate_min ?? '?';
    const max = pricing.hourly_rate_max ?? '?';
    parts.push(`\n**Pricing:** $${min}-$${max}/hr`);
    if (pricing.fixed_prices && Object.keys(pricing.fixed_prices).length > 0) {
      const fixed = Object.entries(pricing.fixed_prices).map(([k, v]) => `${k}: $${v}`).join(', ');
      parts.push(` (Fixed: ${fixed})`);
    }
    if (pricing.pricing_notes && String(pricing.pricing_notes).trim()) {
      parts.push(` (${String(pricing.pricing_notes).trim()})`);
    }
    parts.push('\n');
  }
  const packages = Array.isArray(pricing?.packages) ? pricing.packages.filter(Boolean) : [];
  if (packages.length > 0) {
    const pkgText = packages.map((p) => (typeof p === 'string' ? p : JSON.stringify(p))).join('; ');
    parts.push(`\n**Packages:** ${pkgText}\n`);
  }

  parts.push("You're all set! I'll find jobs that match your skills.");
  return parts.join('');
}

function profileToCollected(profile) {
  if (!profile) return {};
  const area = profile.service_area;
  const location = typeof area === 'object' && area?.location ? area.location : (area ?? null);
  return {
    first_name: profile.first_name ?? null,
    last_name: profile.last_name ?? null,
    email: profile.email ?? null,
    contact_number: profile.contact_number ?? null,
    service_category_names: profile.service_category_names ?? [],
    service_area: location,
    availability: profile.availability,
    credentials: profile.credentials ?? {},
    pricing: profile.pricing ?? {},
    preferences: profile.preferences ?? {},
    marketplace_profile: profile.preferences?.marketplace_profile ?? {},
    bio: profile.bio ?? null,
  };
}

function detectDomainHint(message) {
  const text = String(message || '').toLowerCase();
  if (text.includes('plumb')) return 'plumbing';
  if (text.includes('electric') || text.includes('ev charger') || text.includes('solar')) return 'electrical';
  if (text.includes('concrete') || text.includes('foundation') || text.includes('rebar')) return 'concrete/foundation';
  if (text.includes('clean')) return 'home cleaning';
  if (text.includes('contractor') || text.includes('remodel') || text.includes('renov')) return 'general contracting';
  if (text.includes('paint')) return 'painting';
  if (text.includes('roof')) return 'roofing';
  if (text.includes('hvac') || text.includes('heating') || text.includes('air condition')) return 'HVAC';
  return null;
}

function buildFirstTurnReminder(userMessage) {
  const domainHint = detectDomainHint(userMessage);
  const domainText = domainHint ? `Detected specialty: ${domainHint}.` : 'Detected specialty from user message.';
  return new SystemMessage(
    `CRITICAL - This is the user's FIRST message. ${domainText} Ask exactly ONE domain-specific question first (tools/equipment, license/certification, project type, materials, method, compliance). Do NOT ask about service area, location, availability, schedule, or pricing yet. Avoid form-filling style.`
  );
}

function buildDomainDepthGuard(domainQuestionsAsked) {
  const asked = Number.isFinite(domainQuestionsAsked) ? domainQuestionsAsked : 0;
  const remaining = Math.max(0, 2 - asked);
  return new SystemMessage(
    `Progress guard: domain_questions_asked=${asked}. Before asking service area/location/availability/pricing, you must ask ${remaining} more domain-specific question(s). Ask only one focused question this turn.`
  );
}

function classifyAssistantQuestionTopic(text) {
  const lower = String(text || '').toLowerCase();
  if (!lower.includes('?')) return 'none';
  if (/(service area|location|where do you serve|where are you based|city|state|zip|radius|coverage area)/.test(lower)) return 'location';
  if (/(availability|available|schedule|weekend|weekday|hours|time slot|when can you|emergency)/.test(lower)) return 'availability';
  if (/(price|pricing|hourly|rate|budget|quote|cost|charge)/.test(lower)) return 'pricing';
  if (/(equipment|tools|material|license|licen[cs]e|certif|specialt|project|install|repair|permit|scope|experience|method|workflow)/.test(lower)) return 'domain';
  return 'other';
}

/* -------------------- RUNNER FUNCTION -------------------- */

export async function runProviderProfileConversation(input) {
  const { sessionId, sellerId, accessToken, message } = input;

  logProviderConversation('runProviderProfileConversation_start', {
    sessionId,
    sellerId,
    messageLength: message?.length ?? 0,
    messagePreview: message ? message.slice(0, 100) + (message.length > 100 ? '...' : '') : null,
  });

  const sessionData = await sessionService.getSessionWithContext(sessionId, 50);
  logProviderConversation('session_loaded', {
    sessionId,
    phase: sessionData?.phase ?? null,
    hasStateProfile: !!(sessionData?.state?.profile),
  });

  const conversationMessages = (sessionData?.messages || []).filter((m) => m.role === 'user' || m.role === 'assistant');
  const profileFlowState = sessionData?.state?.provider_profile_state ?? {};
  const domainQuestionsAsked = Number.isFinite(Number(profileFlowState.domain_questions_asked))
    ? Number(profileFlowState.domain_questions_asked)
    : 0;
  let inputMessages = [new HumanMessage(message)];

  const isFirstMessage = conversationMessages.length === 0;
  logProviderConversation('conversation_history', {
    sessionId,
    historyCount: conversationMessages.length,
    historyRoles: conversationMessages.map((m) => m.role),
    isFirstMessage,
    domainQuestionsAsked,
  });

  if (domainQuestionsAsked < 2) {
    inputMessages = [buildDomainDepthGuard(domainQuestionsAsked), ...inputMessages];
  }

  // When it's the first message (user describing their specialty), inject a strong reminder
  // so the model asks domain-specific questions first—not service area.
  if (isFirstMessage) {
    logProviderConversation('first_message_reminder_injected', { sessionId });
    const firstMsgReminder = buildFirstTurnReminder(message);
    inputMessages = [firstMsgReminder, ...inputMessages];
  }

  const tools = createProviderAgentTools({
    sellerId,
    accessToken,
    serviceCategoryManager,
  });

  const graph = createProviderAgentGraph(tools);

  const config = {
    configurable: {
      thread_id: sessionId,
    },
  };

  logProviderConversation('graph_invoke', {
    sessionId,
    inputMessageCount: inputMessages.length,
    inputMessageTypes: inputMessages.map((m) => m._getType?.() ?? m.constructor?.name ?? 'unknown'),
  });

  const result = await graph.invoke({ messages: inputMessages }, config);

  const messages = result?.messages ?? [];
  const profile = extractProfileFromToolMessages(messages);
  const responseText = profile
    ? formatProfileCreatedResponse(profile)
    : getLastAIMessageText(messages) || "I'm here to help you create your profile!";
  const questionTopic = classifyAssistantQuestionTopic(responseText);
  const nextDomainQuestionsAsked = questionTopic === 'domain'
    ? domainQuestionsAsked + 1
    : domainQuestionsAsked;

  const phase = profile ? 'complete' : 'profile_creation';

  logProviderConversation('graph_result', {
    sessionId,
    totalMessagesFromGraph: messages.length,
    profileExtracted: !!profile,
    phase,
    questionTopic,
    domainQuestionsAsked,
    nextDomainQuestionsAsked,
    responseLength: responseText?.length ?? 0,
    responsePreview: responseText ? responseText.slice(0, 150) + (responseText.length > 150 ? '...' : '') : null,
  });

  await messageService.addUserMessage(sessionId, message);
  await messageService.addAssistantMessage(sessionId, responseText, {
    action: profile ? 'profile_created' : 'conversing',
  });

  await sessionService.updateState(sessionId, {
    profile: profile ?? sessionData.state?.profile ?? null,
    provider_profile_state: {
      domain_questions_asked: nextDomainQuestionsAsked,
      last_ai_question_topic: questionTopic,
    },
  });

  if (phase !== sessionData.phase) {
    await sessionService.updatePhase(sessionId, phase);
    logProviderConversation('phase_updated', { sessionId, from: sessionData.phase, to: phase });
  }

  const collected = profile ? profileToCollected(profile) : {};

  logProviderConversation('runProviderProfileConversation_end', {
    sessionId,
    phase,
    action: profile ? 'profile_created' : 'conversing',
    profileId: profile?.id ?? null,
    collectedKeys: Object.keys(collected),
  });

  return {
    sessionId,
    phase,
    response: responseText,
    action: profile ? 'profile_created' : 'conversing',
    collected,
    requiredMissing: [],
    optionalMissing: [],
    profileReadiness: profile ? 'complete' : 'incomplete',
    profile,
  };
}
