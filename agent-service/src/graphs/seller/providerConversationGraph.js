import { HumanMessage, AIMessage, SystemMessage } from '@langchain/core/messages';
import { sessionService, messageService } from '../../services/index.js';
import { createProviderAgentTools } from './providerAgentTools.js';
import { createProviderAgentGraph } from './providerAgentGraph.js';
import { serviceCategoryManager } from '../../services/serviceCategoryManager.js';
import { logProviderConversation } from '../../utils/providerProfileLogger.js';

function toLangChainMessage(m) {
  const content = m.content || '';
  if (m.role === 'user') return new HumanMessage(content);
  if (m.role === 'assistant') return new AIMessage(content);
  if (m.role === 'system') return new SystemMessage(content);
  return new HumanMessage(content);
}

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
  const parts = [
    "Your profile has been successfully created! Here are the details:\n",
    `**Services:** ${(profile.service_category_names || []).join(', ') || 'Not specified'}\n`,
  ];
  if (profile.bio) {
    parts.push(`\n**About you:** ${profile.bio}\n`);
  }
  const area = profile.service_area;
  if (area && (typeof area === 'object' ? area.location : area)) {
    parts.push(`\n**Service area:** ${typeof area === 'object' ? area.location : area}\n`);
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
      if (['equipment', 'materials', 'project_focus', 'project_sizes_sqft', 'additional_services'].includes(k)) return;
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

  parts.push("You're all set! I'll find jobs that match your skills.");
  return parts.join('');
}

function profileToCollected(profile) {
  if (!profile) return {};
  const area = profile.service_area;
  const location = typeof area === 'object' && area?.location ? area.location : (area ?? null);
  return {
    service_category_names: profile.service_category_names ?? [],
    service_area: location,
    availability: profile.availability,
    credentials: profile.credentials ?? {},
    pricing: profile.pricing ?? {},
    preferences: profile.preferences ?? {},
    bio: profile.bio ?? null,
  };
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

  const history = await messageService.getConversationHistory(sessionId, { limit: 50, includeSystem: false });
  const historyMessages = history.map(toLangChainMessage);
  let inputMessages = [...historyMessages, new HumanMessage(message)];

  const isFirstMessage = historyMessages.length === 0;
  logProviderConversation('conversation_history', {
    sessionId,
    historyCount: history.length,
    historyRoles: history.map((m) => m.role),
    isFirstMessage,
  });

  // When it's the first message (user describing their specialty), inject a strong reminder
  // so the model asks domain-specific questions first—not service area.
  if (isFirstMessage) {
    logProviderConversation('first_message_reminder_injected', { sessionId });
    const firstMsgReminder = new SystemMessage(
      `CRITICAL - This is the user's FIRST message. They described their specialty. Your response MUST ask about equipment, certifications, or project types—NEVER about service area, location, or availability. For concrete/foundation: ask "What equipment do you use—pumps, forms, finishing tools? Do you have ACI certification or a state license?" Do NOT say "Can you tell me your service area" or "Where do you provide these services."`
    );
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

  const phase = profile ? 'complete' : 'profile_creation';

  logProviderConversation('graph_result', {
    sessionId,
    totalMessagesFromGraph: messages.length,
    profileExtracted: !!profile,
    phase,
    responseLength: responseText?.length ?? 0,
    responsePreview: responseText ? responseText.slice(0, 150) + (responseText.length > 150 ? '...' : '') : null,
  });

  await messageService.addUserMessage(sessionId, message);
  await messageService.addAssistantMessage(sessionId, responseText, {
    action: profile ? 'profile_created' : 'conversing',
  });

  await sessionService.updateState(sessionId, {
    profile: profile ?? sessionData.state?.profile ?? null,
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
