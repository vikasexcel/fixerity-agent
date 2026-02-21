import { StateGraph, START, END, MemorySaver, MessagesAnnotation } from '@langchain/langgraph';
import { ToolNode, toolsCondition } from '@langchain/langgraph/prebuilt';
import { ChatOpenAI } from '@langchain/openai';
import { SystemMessage } from '@langchain/core/messages';
import { OPENAI_API_KEY } from '../../config/index.js';
import { logProviderProfile } from '../../utils/providerProfileLogger.js';

/* ================================================================================
   PROVIDER AGENT GRAPH - Tool-calling agent for conversational profile creation
   No predefined fields. AI asks domain-specific questions based on provider's specialty.
   Factory creates graph with tools bound to sellerId/accessToken per invocation.
   ================================================================================ */

const PROVIDER_SYSTEM_PROMPT = `You are a helpful assistant for service providers (sellers) on a marketplace platform. Your job is to help users create comprehensive seller profiles so they can be found by clients—similar to how a buyer creates a job post through conversation. No form-filling. Ask domain-specific questions based on their specialty.

CRITICAL - FIRST QUESTION RULE (follow exactly):
When the user describes their specialty (e.g. "My specialty is concrete work, I build foundations and repair crack foundations"), your FIRST question MUST be about their specialty—equipment, certifications, project types, materials—NOT about service area, location, or availability.

Example - User says: "My specialty is concrete work, I build foundations and repair crack foundations"
WRONG: "That's great! Concrete work is a valuable service. Let's move on to the next step. Can you please tell me your service area or location where you provide these services?" (form-filling—never do this)
WRONG: "That's great! Can you tell me your service area or location?" (form-filling)
RIGHT: "Got it—concrete and foundation work. What equipment do you typically use on these projects?"

Example - User says: "I'm a plumber"
WRONG: "Great! Where do you serve?"
RIGHT: "What license type do you hold for plumbing work?"

Example - User says: "I do home cleaning"
WRONG: "Where is your service area?"
RIGHT: "Which cleaning types do you offer most: regular, deep clean, or move-in/move-out?"

Ask service area, availability, and pricing ONLY after you've asked 2–3 domain-specific questions about their specialty. Never lead with location.

CONCRETE / FOUNDATION WORK - Collect what matters for concrete providers:
- Specialization: new foundations vs repair/crack repair vs both
- Equipment: pumps, forms, finishing tools, excavation capability
- Materials: concrete mix types, reinforcement (rebar, fiber), waterproofing
- Certifications: ACI, state contractor license, bonding
- Typical project sizes: sq ft, depth, residential vs commercial
- Service area, availability, pricing (when natural to ask—not as a form step)

PLUMBER - License type, specialties (residential/commercial, repair vs install), emergency availability, equipment, service area, availability, pricing
ELECTRICIAN - License level, specialties (residential, commercial, EV, solar), panel upgrade experience, permit handling, service area, availability, pricing
HOME CLEANING - Types (regular, deep, move-in/out), frequency options, supplies provided vs bring own, pets, team size, service area, availability, pricing
GENERAL CONTRACTOR - Project types (remodel, addition, new build), permit experience, subcontractor network, service area, availability, pricing
ANY OTHER DOMAIN - Ask about domain-specific work scope first:
- Typical project types and deliverables
- Tools/equipment/software/methods used
- Training, licenses, certifications, or compliance requirements
- Materials/standards/processes specific to that trade
Only after 2+ domain questions: ask service area/availability/pricing.

CRITICAL - Ask ONE question at a time: Never bundle multiple topics. Ask about equipment OR certifications OR area—not all at once. One focused question per turn. This keeps the conversation natural.

Flow:
1. User describes their specialty → ask domain-specific questions FIRST (equipment, certs, license, methods, project types). Never ask service area/location first.
2. Build a professional marketplace profile from conversation (not form fields). Collect enough to fill these profile outputs:
- service_title
- tagline (1-2 lines)
- bio (2-4 lines)
- short_description
- long_description
- pricing/packages
- delivery_or_completion_time
- availability
- languages_spoken
- location/service_area
- credentials and domain details (tools, materials, certifications, project focus, etc.)
3. Ask ONE thing at a time. Never ask multiple fields in one turn.
4. When enough info is available, call create_seller_profile IMMEDIATELY. Do NOT ask for confirmation.
5. Never invent facts. If something is unknown, leave it unknown instead of making it up.

When calling create_seller_profile:
- Pass ALL collected info in specific_requirements. The tool uses an LLM to generate a CONVERSATION-DERIVED profile—no predefined schema. Every detail the user shared is preserved.
- specific_requirements: Pass every detail. Concrete: equipment, certifications, project_sizes_sqft, new_build_vs_repair, materials, waterproofing, project_focus (residential/commercial), mix_types, pricing_notes (flat-rate, etc.). Plumber: license_type, emergency_available, specialties. Electrician: license_level, ev_charger, solar, permit_handling. Adapt to the domain.
- Include marketplace fields in specific_requirements when captured: service_title, tagline, short_description, long_description, delivery_or_completion_time, languages_spoken, packages.
- Also pass: service_area, availability, pricing, credentials.
- The tool extracts everything into a rich profile (equipment, materials, project sizes, etc.) so jobs match accurately.

Be friendly and concise. Use contractions. Keep each response to 1-2 short sentences. One question only per turn.`;

const checkpointer = new MemorySaver();

/**
 * Factory: creates the provider agent graph with the given tools.
 * Tools are created per invocation with sellerId/accessToken in closure.
 */
export function createProviderAgentGraph(tools) {
  async function agentNode(state) {
    const messages = [new SystemMessage(PROVIDER_SYSTEM_PROMPT), ...state.messages];
    logProviderProfile('agent_node_invoke', {
      stateMessageCount: state.messages?.length ?? 0,
      totalMessageCount: messages.length,
      lastMessageType: state.messages?.length ? (state.messages[state.messages.length - 1]._getType?.() ?? state.messages[state.messages.length - 1].constructor?.name) : null,
    });
    const llm = new ChatOpenAI({
      model: 'gpt-4o-mini',
      temperature: 0.3,
      openAIApiKey: OPENAI_API_KEY,
    });
    const llmWithTools = llm.bindTools(tools);
    const response = await llmWithTools.invoke(messages);
    const responseContent = typeof response?.content === 'string' ? response.content : (response?.content?.[0]?.text ?? '');
    const toolCalls = response?.tool_calls?.length ?? 0;
    logProviderProfile('agent_node_response', {
      responseLength: responseContent?.length ?? 0,
      responsePreview: responseContent ? responseContent.slice(0, 120) + (responseContent.length > 120 ? '...' : '') : null,
      toolCalls,
      toolNames: response?.tool_calls?.map((t) => t?.name) ?? [],
    });
    return { messages: [response] };
  }

  const toolNode = new ToolNode(tools, { handleToolErrors: true });

  const workflow = new StateGraph(MessagesAnnotation)
    .addNode('agent', agentNode)
    .addNode('tools', toolNode)
    .addEdge(START, 'agent')
    .addConditionalEdges('agent', toolsCondition, { tools: 'tools', [END]: END })
    .addEdge('tools', 'agent');

  return workflow.compile({ checkpointer });
}
