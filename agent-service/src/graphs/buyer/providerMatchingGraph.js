import { StateGraph, START, END, MessagesAnnotation } from '@langchain/langgraph';
import { ToolNode, toolsCondition } from '@langchain/langgraph/prebuilt';
import { ChatOpenAI } from '@langchain/openai';
import { HumanMessage, SystemMessage } from '@langchain/core/messages';
import { OPENAI_API_KEY } from '../../config/index.js';
import prisma from '../../prisma/client.js';
import { buyerTools } from './buyerTools.js';

/* ================================================================================
   PROVIDER MATCHING GRAPH - LLM uses tools to get sellers from SellerProfile and rank them
   ================================================================================ */

const MATCH_SYSTEM_PROMPT = `You are matching a buyer's job to the best available service providers (sellers).

Your task:
1. Call the tool list_seller_profiles_for_job with the job's service_category_name to get candidate sellers.
2. Optionally call get_seller_profile for one or more seller_ids if you need more detail to compare.
3. Compare candidates to the job (budget, dates, priorities, credentials like licensed/references).
4. Output your final answer as follows:
   - In your final message, end with a single JSON block (no other text after it) with this exact structure:
     {"ranked_seller_ids": ["seller_id_1", "seller_id_2", ...]}
   - List seller IDs in order of best match first. Include only sellers that are suitable for the job.
   - If no sellers are suitable, use: {"ranked_seller_ids": []}

Always call list_seller_profiles_for_job first with the service category from the job.`;

/**
 * Map a Prisma SellerProfile to the provider shape expected by getProviderServiceData and cache.
 */
export function sellerProfileToProvider(profile) {
  const cred = profile?.credentials && typeof profile.credentials === 'object' ? profile.credentials : {};
  return {
    provider_id: profile?.id ?? profile?.seller_id,
    id: profile?.id ?? profile?.seller_id,
    average_rating: 0,
    total_completed_order: profile?.totalBidsAccepted ?? 0,
    jobsCompleted: profile?.totalBidsAccepted ?? 0,
    licensed: cred.licensed === true,
    referencesAvailable: cred.references_available === true,
    num_of_rating: cred.references_available ? 1 : 0,
    deadline_in_days: 3,
    ...profile,
  };
}

function buildUserMessage(job) {
  const budget = job.budget && typeof job.budget === 'object'
    ? `$${job.budget.min ?? '?'} - $${job.budget.max ?? '?'}`
    : String(job.budget ?? 'Not specified');
  const priorities = job.priorities
    ? (typeof job.priorities === 'string' ? job.priorities : JSON.stringify(job.priorities))
    : 'None';
  return `Match this job to the best providers.

Job:
- id: ${job.id ?? 'unknown'}
- title: ${job.title ?? 'No title'}
- description: ${job.description ?? 'None'}
- service_category_name: ${job.service_category_name ?? 'Not set'}
- budget: ${budget}
- startDate: ${job.startDate ?? 'Not set'}
- endDate: ${job.endDate ?? 'Not set'}
- priorities: ${priorities}

Use the tools to get sellers for this service, then rank them and reply with the JSON block ranked_seller_ids.`;
}

async function agentNode(state) {
  const llm = new ChatOpenAI({
    model: 'gpt-4o-mini',
    temperature: 0.2,
    openAIApiKey: OPENAI_API_KEY,
  });
  const llmWithTools = llm.bindTools(buyerTools);
  const messages = [new SystemMessage(MATCH_SYSTEM_PROMPT), ...state.messages];
  const response = await llmWithTools.invoke(messages);
  return { messages: [response] };
}

const toolNode = new ToolNode(buyerTools, { handleToolErrors: true });

const workflow = new StateGraph(MessagesAnnotation)
  .addNode('agent', agentNode)
  .addNode('tools', toolNode)
  .addEdge(START, 'agent')
  .addConditionalEdges('agent', toolsCondition, { tools: 'tools', [END]: END })
  .addEdge('tools', 'agent');

const graph = workflow.compile();

/**
 * Parse ranked_seller_ids from the last assistant message (JSON block or inline).
 */
function parseRankedSellerIds(messages) {
  const aiMessages = messages.filter((m) => m._getType?.() === 'ai' || m.constructor?.name === 'AIMessage');
  const last = aiMessages[aiMessages.length - 1];
  const content = last?.content;
  if (typeof content !== 'string') return null;
  // Try ```json ... ``` block first
  const jsonBlock = content.match(/```(?:json)?\s*(\{[\s\S]*?\})\s*```/)?.[1];
  const str = jsonBlock ?? content;
  try {
    const obj = JSON.parse(str);
    const ids = obj?.ranked_seller_ids;
    return Array.isArray(ids) ? ids.filter((id) => id != null && String(id).trim()) : null;
  } catch {
    const match = content.match(/"ranked_seller_ids"\s*:\s*\[([^\]]*)\]/);
    if (match) {
      try {
        const arr = JSON.parse('[' + match[1] + ']');
        return arr.filter((id) => id != null && String(id).trim());
      } catch {
        return null;
      }
    }
  }
  return null;
}

/**
 * Run the matching graph and return providers in the shape expected by the orchestrator.
 * @param {object} job - Job with id, title, service_category_name, budget, dates, priorities, etc.
 * @returns {{ providers: Array, error?: string }}
 */
export async function runProviderMatching(job) {
  const serviceName = job?.service_category_name ?? '';
  if (!serviceName.trim()) {
    return { providers: [], error: 'Job must have service_category_name for matching.' };
  }

  const userMessage = buildUserMessage(job);
  const initialState = { messages: [new HumanMessage(userMessage)] };

  try {
    const finalState = await graph.invoke(initialState);
    const messages = finalState?.messages ?? [];
    let rankedIds = parseRankedSellerIds(messages);

    // Fallback: use order from first list_seller_profiles_for_job tool result
    if (!rankedIds || rankedIds.length === 0) {
      const toolMessages = messages.filter((m) => m.tool_calls?.length || m.name);
      for (const m of toolMessages) {
        if (m.content && typeof m.content === 'string') {
          try {
            const data = JSON.parse(m.content);
            const sellers = data?.sellers ?? data?.results;
            if (Array.isArray(sellers) && sellers.length > 0) {
              rankedIds = sellers.map((s) => s.seller_id ?? s.id).filter(Boolean);
              console.log('[ProviderMatching] Fallback: using tool result order for', rankedIds.length, 'sellers');
              break;
            }
          } catch {
            // ignore
          }
        }
      }
    }

    if (!rankedIds || rankedIds.length === 0) {
      return { providers: [] };
    }

    const profiles = await prisma.sellerProfile.findMany({
      where: { id: { in: rankedIds }, active: true },
    });
    const byId = new Map(profiles.map((p) => [p.id, p]));
    const providers = rankedIds
      .map((id) => byId.get(id))
      .filter(Boolean)
      .map((p) => sellerProfileToProvider(p));

    console.log('[ProviderMatching] Returning', providers.length, 'providers');
    return { providers };
  } catch (error) {
    console.error('[ProviderMatching] Error:', error.message);
    return { providers: [], error: error.message };
  }
}
