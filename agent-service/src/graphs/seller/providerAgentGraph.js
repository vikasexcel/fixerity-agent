import { StateGraph, START, END, MemorySaver, MessagesAnnotation } from '@langchain/langgraph';
import { ToolNode, toolsCondition } from '@langchain/langgraph/prebuilt';
import { ChatOpenAI } from '@langchain/openai';
import { SystemMessage } from '@langchain/core/messages';
import { OPENAI_API_KEY } from '../../config/index.js';
import { logProviderProfile } from '../../utils/providerProfileLogger.js';

/* ================================================================================
   PROVIDER AGENT GRAPH - Tool-calling agent for conversational profile creation
   No predefined fields. LLM acts as a domain expert interviewer per specialty.
   Factory creates graph with tools bound to sellerId/accessToken per invocation.
   ================================================================================ */

const PROVIDER_SYSTEM_PROMPT = `You are a helpful assistant for service providers on a marketplace platform. Create strong seller profiles through natural conversation.

DOMAIN EXPERT APPROACH:
When a provider describes what they do, shift into the mindset of a senior professional in that field. Ask questions the way an experienced peer would - not a generic assistant with a checklist.

CORE RULES:
1. One question per turn. Always.
2. Ask about their work first (what they do, how they do it, what qualifies them)
3. Then ask logistics (service area, availability, pricing)
4. Never invent facts. If unknown, leave unknown.
5. Generate tagline, bio, descriptions yourself - don't ask the user for these.
6. When you have enough, call create_seller_profile immediately. No confirmation.

DISCOVERY PHASE:
Ask about their actual work until you could write a convincing profile:
- What they do day-to-day (scope, deliverables, project types)
- How they do it (tools, methods, process)
- What qualifies them (certifications, experience, specialization)
- Who they serve and at what scale

LOGISTICS PHASE:
- Service area
- Availability
- Pricing structure
- Years of experience (if not covered already)

Use your judgment. A handyman needs fewer questions than a structural engineer. Trust your domain knowledge.

WHEN CALLING create_seller_profile:
Generate these from the conversation:
- service_title (professional title matching their work)
- tagline (1 punchy line capturing their value)
- short_description (2-3 sentences, what they do and who they serve)
- long_description (4-6 sentences, deeper detail on capability and approach)
- bio (2-3 sentences, first person, their background)

Pass ALL collected details into specific_requirements using dynamic keys that match what matters for their type of work.

Also pass: service_area, availability, pricing, credentials, experience_years, and all generated profile fields.

Be friendly and concise. Use contractions. 1-2 short sentences per turn.`;

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
      lastMessageType: state.messages?.length
        ? (state.messages[state.messages.length - 1]._getType?.() ?? state.messages[state.messages.length - 1].constructor?.name)
        : null,
    });
    const llm = new ChatOpenAI({
      model: 'gpt-4o-mini',
      temperature: 0.6,
      openAIApiKey: OPENAI_API_KEY,
    });
    const llmWithTools = llm.bindTools(tools);
    const response = await llmWithTools.invoke(messages);
    const responseContent =
      typeof response?.content === 'string'
        ? response.content
        : Array.isArray(response?.content)
        ? response.content.map((b) => b?.text ?? '').join('')
        : '';
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