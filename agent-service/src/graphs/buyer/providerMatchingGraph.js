import { StateGraph, START, END, MessagesAnnotation } from '@langchain/langgraph';
import { ToolNode, toolsCondition } from '@langchain/langgraph/prebuilt';
import { ChatOpenAI } from '@langchain/openai';
import { HumanMessage, SystemMessage } from '@langchain/core/messages';
import { OPENAI_API_KEY } from '../../config/index.js';
import prisma from '../../prisma/client.js';
import { buyerTools } from './buyerTools.js';
import { buildOptimizedQueryForJob } from '../../services/jobQueryService.js';
import { searchSellersByQuery } from '../../services/sellerEmbeddingService.js';
import { rerankCandidatesForJob } from '../../services/rerankService.js';

/* ================================================================================
   PROVIDER MATCHING - Semantic pipeline: query -> top 40 -> rerank 15 -> final LLM rank.
   Fallback: when semantic search returns 0, use tool-based graph (list_seller_profiles_for_job).
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
  return parseRankedSellerIdsFromContent(content);
}

/**
 * Parse ranked_seller_ids from a string (LLM content).
 */
function parseRankedSellerIdsFromContent(content) {
  if (typeof content !== 'string') return null;
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

const FINAL_RANK_SYSTEM = `You are selecting the most accurate service providers (sellers) for a buyer's job. You will receive the job details and a short list of candidate providers with their profile summary. Return the seller IDs in order of best match (most accurate first). Output ONLY a JSON object with this exact key: "ranked_seller_ids" (array of seller_id strings). No other text.`;

/**
 * Final LLM: given job + list of candidates (seller_id + searchable_text), return ranked_seller_ids (most accurate first).
 * @param {object} job - Job summary
 * @param {Array<{ seller_id: string, searchable_text: string }>} candidates - Up to 15 candidates with summaries
 * @returns {Promise<string[]>}
 */
async function finalRankProvidersForJob(job, candidates) {
  if (!candidates || candidates.length === 0) return [];
  const candidateSet = new Set(candidates.map((c) => c.seller_id).filter(Boolean));
  if (candidateSet.size === 0) return [];

  console.log('\n  [Final LLM] Input: job + ' + candidates.length + ' candidates (seller_id + searchable_text snippet each).');

  if (!OPENAI_API_KEY || !OPENAI_API_KEY.trim()) {
    return candidates.map((c) => c.seller_id);
  }
  const jobSummary = [
    job?.title && `Title: ${job.title}`,
    job?.description && `Description: ${job.description}`,
    job?.service_category_name && `Service: ${job.service_category_name}`,
    job?.budget && typeof job.budget === 'object' && `Budget: $${job.budget.min ?? '?'}-$${job.budget.max ?? '?'}`,
    job?.priorities && `Priorities: ${typeof job.priorities === 'string' ? job.priorities : JSON.stringify(job.priorities)}`,
  ].filter(Boolean).join('\n');
  const candidateList = candidates.map((c, i) => `${i + 1}. seller_id: ${c.seller_id} | ${(c.searchable_text || '').slice(0, 400)}`).join('\n');
  const llm = new ChatOpenAI({ model: 'gpt-4o-mini', temperature: 0.2, openAIApiKey: OPENAI_API_KEY });
  const response = await llm.invoke([
    new SystemMessage(FINAL_RANK_SYSTEM),
    new HumanMessage(`Job:\n${jobSummary}\n\nCandidates:\n${candidateList}\n\nReturn JSON: {"ranked_seller_ids": ["id1", "id2", ...]} with the most accurate providers first.`),
  ]);
  const content = response?.content;
  const ranked = parseRankedSellerIdsFromContent(typeof content === 'string' ? content : '');
  const valid = ranked && ranked.length > 0 ? ranked.filter((id) => candidateSet.has(id)) : [];
  const result = valid.length ? valid : candidates.map((c) => c.seller_id);
  console.log('  [Final LLM] Output: ' + result.length + ' ranked seller IDs (most accurate first).\n');
  return result;
}

/**
 * Run fallback matching (tool-based graph) when semantic search returns no sellers.
 */
async function runFallbackProviderMatching(job) {
  const userMessage = buildUserMessage(job);
  const initialState = { messages: [new HumanMessage(userMessage)] };
  const finalState = await graph.invoke(initialState);
  const messages = finalState?.messages ?? [];
  let rankedIds = parseRankedSellerIds(messages);
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
  return rankedIds || [];
}

/**
 * Run the matching pipeline and return providers in the shape expected by the orchestrator.
 * Pipeline: build query -> semantic search top 40 -> rerank to 15 -> final LLM rank.
 * Fallback: when semantic search returns 0, use tool-based graph (list_seller_profiles_for_job).
 * @param {object} job - Job with id, title, service_category_name, budget, dates, priorities, etc.
 * @returns {{ providers: Array, error?: string }}
 */
export async function runProviderMatching(job) {
  const serviceName = job?.service_category_name ?? '';
  if (!serviceName.trim()) {
    return { providers: [], error: 'Job must have service_category_name for matching.' };
  }

  const LOG_HEADER = '[ProviderMatching]';

  try {
    console.log('\n' + '#'.repeat(60));
    console.log(LOG_HEADER + ' Pipeline start (job id: ' + (job?.id ?? '—') + ')');
    console.log('#'.repeat(60) + '\n');

    // Step A: build optimized retrieval query from job (logged in jobQueryService)
    const query = await buildOptimizedQueryForJob(job);
    console.log(LOG_HEADER + ' Step A done. Query length: ' + (query?.length ?? 0) + ' chars\n');

    // Step 2: semantic search over seller embeddings -> top 40 (logged in sellerEmbeddingService)
    const top40 = await searchSellersByQuery(query, 40);
    console.log(LOG_HEADER + ' Step 2 done. Semantic search returned ' + top40.length + ' sellers (top 40)\n');

    let rankedIds;
    if (top40.length === 0) {
      console.log(LOG_HEADER + ' Semantic search returned 0; using fallback (list_seller_profiles_for_job).\n');
      rankedIds = await runFallbackProviderMatching(job);
      console.log(LOG_HEADER + ' Fallback returned ' + (rankedIds?.length ?? 0) + ' seller IDs\n');
    } else {
      // Step 3: rerank 40 -> top 15 (logged in rerankService)
      const top15Ids = await rerankCandidatesForJob(job, top40, 15);
      console.log(LOG_HEADER + ' Step 3 done. Reranked to top ' + top15Ids.length + ' seller IDs\n');

      const top15Candidates = top15Ids
        .map((id) => top40.find((c) => c.seller_id === id))
        .filter(Boolean);

      // Step 4: final LLM -> most accurate providers (ranked list)
      rankedIds = await finalRankProvidersForJob(job, top15Candidates);

      console.log('\n' + '='.repeat(60));
      console.log(LOG_HEADER + ' Step 4 — Final ranked providers (most accurate first)');
      console.log('='.repeat(60));
      console.log('\n  Ranked seller IDs (full list):');
      console.log('  ' + '-'.repeat(56));
      (rankedIds || []).forEach((id, i) => console.log('  [' + (i + 1) + '] ' + id));
      console.log('  ' + '-'.repeat(56));
      console.log('  Total: ' + (rankedIds?.length ?? 0) + ' providers');
      console.log('='.repeat(60) + '\n');
    }

    if (!rankedIds || rankedIds.length === 0) {
      console.log(LOG_HEADER + ' No providers to return.\n');
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

    console.log(LOG_HEADER + ' Pipeline complete. Returning ' + providers.length + ' providers.');
    console.log('#'.repeat(60) + '\n');
    return { providers };
  } catch (error) {
    console.error('[ProviderMatching] Error:', error.message);
    return { providers: [], error: error.message };
  }
}
