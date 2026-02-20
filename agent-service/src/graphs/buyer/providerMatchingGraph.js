/**
 * Provider Matching Graph — Upwork-style semantic pipeline.
 *
 * Pipeline:
 *   1. Build a buyer-facing query from the job (reads like "I need a provider who...")
 *   2. Semantic search seller_embeddings (top 40) with normalised category filter
 *      → automatic fallback to widened search if 0 results
 *   3. Rerank top 40 → top 15 using rerankService
 *   4. Final LLM ranking: most accurate providers first
 *   5. Fetch full SellerProfile rows and return
 *
 * Fallback (when semantic search returns 0 even after widening):
 *   → Tool-based graph using list_seller_profiles_for_job
 */

import { StateGraph, START, END, MessagesAnnotation } from '@langchain/langgraph';
import { ToolNode, toolsCondition } from '@langchain/langgraph/prebuilt';
import { ChatOpenAI } from '@langchain/openai';
import { HumanMessage, SystemMessage } from '@langchain/core/messages';
import prisma from '../../prisma/client.js';
import { buyerTools } from './buyerTools.js';
import { buildOptimizedQueryForJob } from '../../services/jobQueryService.js';
import { searchSellersByQuery } from '../../services/sellerEmbeddingService.js';
import { rerankCandidatesForJob } from '../../services/rerankService.js';

/* ─────────────────────────────────────────────────────────────
   LOGGING HELPERS
───────────────────────────────────────────────────────────── */

const LOG_PREFIX = '[ProviderMatching]';
const DIVIDER     = '═'.repeat(70);
const SUB_DIVIDER = '─'.repeat(70);

function logHeader(title) {
  console.log('\n' + DIVIDER);
  console.log(`${LOG_PREFIX} ${title}`);
  console.log(DIVIDER);
}

function logSection(title) {
  console.log('\n' + SUB_DIVIDER);
  console.log(`  📌 ${title}`);
  console.log(SUB_DIVIDER);
}

function logKeyValue(key, value, indent = 2) {
  const spaces      = ' '.repeat(indent);
  const displayValue = value === null || value === undefined ? '—' : value;
  console.log(`${spaces}${key}: ${displayValue}`);
}

function logJson(obj, indent = 4) {
  const spaces = ' '.repeat(indent);
  JSON.stringify(obj, null, 2).split('\n').forEach((l) => console.log(`${spaces}${l}`));
}

/* ─────────────────────────────────────────────────────────────
   CONSTANTS
───────────────────────────────────────────────────────────── */

const OPENAI_API_KEY = process.env.OPENAI_API_KEY;

/**
 * Minimum similarity score (0-1) for a seller to be considered a match.
 * Sellers below this threshold are soft-filtered before reranking.
 * Set to 0 to disable — the reranker will handle all filtering.
 */
const MIN_SIMILARITY_SCORE = 0.0;

/* ─────────────────────────────────────────────────────────────
   QUERY BUILDER — buyer-facing language
   
   The query is written as if the buyer is describing what they need,
   NOT as a database search string. This ensures the query lives in
   the same semantic space as the seller searchable_text.
───────────────────────────────────────────────────────────── */

/**
 * Build a buyer-facing natural language query from the job.
 * Falls back to LLM-generated query via buildOptimizedQueryForJob.
 *
 * Example output:
 *   "I need a licensed home cleaning provider in San Jose, California.
 *    Budget around $6500. Available to start February 28, 2026.
 *    Looking for someone with references and experience in residential cleaning."
 *
 * @param {object} job
 * @returns {Promise<string>}
 */
async function buildBuyerFacingQuery(job) {
  // First, use the LLM to generate the optimised query
  const llmQuery = await buildOptimizedQueryForJob(job);

  // Then prepend structured signals so the embedding captures them strongly
  const parts = [];

  const service = job?.service_category_name ?? job?.serviceCategoryName ?? '';
  if (service) {
    parts.push(`I need a ${service} provider.`);
  }

  const location = resolveLocation(job?.location);
  if (location) {
    parts.push(`Located in or serving ${location}.`);
  }

  const budget = resolveBudget(job?.budget);
  if (budget) {
    parts.push(`Budget: ${budget}.`);
  }

  if (job?.startDate) {
    parts.push(`Available to start on or before ${job.startDate}.`);
  }

  // Append LLM query for additional semantic richness
  if (llmQuery && llmQuery.trim()) {
    parts.push(llmQuery.trim());
  }

  // Append priority signals
  const priorities = job?.priorities;
  if (priorities && typeof priorities === 'object') {
    const must = priorities.must_have;
    if (must && typeof must === 'object' && Object.keys(must).length > 0) {
      parts.push(`Must have: ${Object.keys(must).join(', ')}.`);
    }
    const nice = priorities.nice_to_have;
    if (nice && typeof nice === 'object' && Object.keys(nice).length > 0) {
      parts.push(`Nice to have: ${Object.keys(nice).join(', ')}.`);
    }
  }

  return parts.join(' ').trim();
}

function resolveLocation(location) {
  if (!location) return null;
  if (typeof location === 'string') return location.trim();
  if (typeof location === 'object') {
    const parts = [location.address, location.city, location.state]
      .filter(Boolean)
      .map((s) => String(s).trim());
    return parts.join(', ') || null;
  }
  return null;
}

function resolveBudget(budget) {
  if (!budget) return null;
  if (typeof budget === 'object') {
    const min = budget.min ?? budget.Min;
    const max = budget.max ?? budget.Max;
    if (min != null && max != null) return `$${min}–$${max}`;
    if (max != null) return `up to $${max}`;
    if (min != null) return `from $${min}`;
  }
  if (typeof budget === 'string') return budget;
  return null;
}

/* ─────────────────────────────────────────────────────────────
   TOOL-BASED FALLBACK GRAPH
───────────────────────────────────────────────────────────── */

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

function buildFallbackUserMessage(job) {
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
- location: ${resolveLocation(job.location) ?? 'Not set'}
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

/* ─────────────────────────────────────────────────────────────
   PARSE HELPERS
───────────────────────────────────────────────────────────── */

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

function parseRankedSellerIds(messages) {
  const aiMessages = messages.filter(
    (m) => m._getType?.() === 'ai' || m.constructor?.name === 'AIMessage',
  );
  const last    = aiMessages[aiMessages.length - 1];
  const content = last?.content;
  if (typeof content !== 'string') return null;
  return parseRankedSellerIdsFromContent(content);
}

/* ─────────────────────────────────────────────────────────────
   FALLBACK RUNNER
───────────────────────────────────────────────────────────── */

async function runFallbackProviderMatching(job) {
  logSection('Fallback: Tool-Based Graph Execution');
  const userMessage = buildFallbackUserMessage(job);

  console.log('\n  📝 User Message (fallback):');
  console.log('  ' + '─'.repeat(50));
  userMessage.split('\n').forEach((l) => console.log(`    ${l}`));
  console.log('  ' + '─'.repeat(50));
  console.log('\n  ⏳ Invoking tool-based matching graph...');

  const finalState = await graph.invoke({ messages: [new HumanMessage(userMessage)] });
  const messages   = finalState?.messages ?? [];

  console.log(`\n  ✅ Graph completed with ${messages.length} messages`);

  let rankedIds = parseRankedSellerIds(messages);

  if (!rankedIds || rankedIds.length === 0) {
    console.log('  ⚠️  Could not parse ranked_seller_ids, checking tool result messages...');
    const toolMessages = messages.filter((m) => m.tool_calls?.length || m.name);
    for (const m of toolMessages) {
      if (m.content && typeof m.content === 'string') {
        try {
          const data    = JSON.parse(m.content);
          const sellers = data?.sellers ?? data?.results;
          if (Array.isArray(sellers) && sellers.length > 0) {
            rankedIds = sellers.map((s) => s.seller_id ?? s.id).filter(Boolean);
            console.log(`  ✅ Extracted ${rankedIds.length} seller IDs from tool results`);
            break;
          }
        } catch {
          // ignore parse errors
        }
      }
    }
  } else {
    console.log(`  ✅ Parsed ${rankedIds.length} ranked seller IDs from LLM response`);
  }

  return rankedIds || [];
}

/* ─────────────────────────────────────────────────────────────
   FINAL LLM RANKER
───────────────────────────────────────────────────────────── */

const FINAL_RANK_SYSTEM = `You are selecting the most accurate service providers (sellers) for a buyer's job.
You will receive the job details and a short list of candidate providers with their profile summary.
Return the seller IDs in order of best match (most accurate / most relevant first).
Consider: service match, location match, budget compatibility, credentials, and availability.
Output ONLY a JSON object: {"ranked_seller_ids": ["id1", "id2", ...]}
If a candidate is clearly unsuitable for the job, omit them from the list.
No other text — just the JSON.`;

async function finalRankProvidersForJob(job, candidates) {
  logSection('Final LLM Ranking');

  if (!candidates || candidates.length === 0) {
    console.log('  ❌ No candidates for final ranking');
    return [];
  }

  const candidateSet = new Set(candidates.map((c) => c.seller_id).filter(Boolean));
  if (candidateSet.size === 0) return [];

  console.log(`  🤖 Sending ${candidates.length} candidates to LLM for final ranking...`);

  if (!OPENAI_API_KEY || !OPENAI_API_KEY.trim()) {
    console.log('  ⚠️  No OpenAI API key — returning candidates in original order');
    return candidates.map((c) => c.seller_id);
  }

  const jobSummary = [
    job?.title           && `Title: ${job.title}`,
    job?.description     && `Description: ${job.description}`,
    job?.service_category_name && `Service: ${job.service_category_name}`,
    job?.budget && typeof job.budget === 'object'
      && `Budget: $${job.budget.min ?? '?'}–$${job.budget.max ?? '?'}`,
    resolveLocation(job?.location) && `Location: ${resolveLocation(job?.location)}`,
    job?.startDate       && `Start date: ${job.startDate}`,
    job?.priorities      && `Priorities: ${JSON.stringify(job.priorities)}`,
  ].filter(Boolean).join('\n');

  const candidateList = candidates
    .map((c, i) => `${i + 1}. seller_id: ${c.seller_id} | ${(c.searchable_text || '').slice(0, 400)}`)
    .join('\n');

  const llm = new ChatOpenAI({
    model: 'gpt-4o-mini',
    temperature: 0.1,
    openAIApiKey: OPENAI_API_KEY,
  });

  const response = await llm.invoke([
    new SystemMessage(FINAL_RANK_SYSTEM),
    new HumanMessage(
      `Job:\n${jobSummary}\n\nCandidates:\n${candidateList}\n\n` +
      `Return JSON: {"ranked_seller_ids": ["id1", "id2", ...]} — most accurate providers first.`,
    ),
  ]);

  const content = response?.content;
  console.log('\n  🤖 LLM Response:');
  console.log('  ' + '─'.repeat(50));
  if (typeof content === 'string') content.split('\n').forEach((l) => console.log(`    ${l}`));
  console.log('  ' + '─'.repeat(50));

  const ranked = parseRankedSellerIdsFromContent(typeof content === 'string' ? content : '');

  // LLM explicitly returned empty → no suitable providers
  if (ranked && Array.isArray(ranked) && ranked.length === 0) {
    console.log('  ⚠️  LLM returned no suitable providers');
    return [];
  }

  const valid  = ranked ? ranked.filter((id) => candidateSet.has(id)) : [];
  const result = valid.length ? valid : candidates.map((c) => c.seller_id);

  console.log(`\n  ✅ Final Ranked Seller IDs (${result.length}):`);
  result.forEach((id, i) => console.log(`    [${i + 1}] ${id}`));

  return result;
}

/* ─────────────────────────────────────────────────────────────
   PROFILE MAPPER
───────────────────────────────────────────────────────────── */

export function sellerProfileToProvider(profile) {
  const cred = profile?.credentials && typeof profile.credentials === 'object'
    ? profile.credentials
    : {};
  return {
    provider_id:         profile?.id ?? profile?.seller_id,
    id:                  profile?.id ?? profile?.seller_id,
    average_rating:      0,
    total_completed_order: profile?.totalBidsAccepted ?? 0,
    jobsCompleted:       profile?.totalBidsAccepted ?? 0,
    licensed:            cred.licensed === true,
    referencesAvailable: cred.references_available === true,
    num_of_rating:       cred.references_available ? 1 : 0,
    deadline_in_days:    3,
    ...profile,
  };
}

/* ─────────────────────────────────────────────────────────────
   MAIN PIPELINE
───────────────────────────────────────────────────────────── */

/**
 * Run the provider matching pipeline and return providers ranked by best match.
 *
 * Pipeline:
 *   Step 1 — Build a buyer-facing search query (richer than before)
 *   Step 2 — Semantic search (top 40) with normalised category filter + auto-widen fallback
 *   Step 3 — Rerank 40 → 15
 *   Step 4 — Final LLM rank 15 → ordered list
 *   Step 5 — Fetch full SellerProfile rows
 *
 * @param {object} job
 * @returns {Promise<{ providers: Array, error?: string }>}
 */
export async function runProviderMatching(job) {
  const startTime   = Date.now();
  const serviceName = (job?.service_category_name ?? '').trim();

  console.log('\n' + '▓'.repeat(70));
  console.log(`${LOG_PREFIX} 🚀 PROVIDER MATCHING PIPELINE STARTED`);
  console.log('▓'.repeat(70));

  if (!serviceName) {
    console.log(`\n  ❌ Error: Job must have service_category_name for matching.`);
    console.log('▓'.repeat(70) + '\n');
    return { providers: [], error: 'Job must have service_category_name for matching.' };
  }

  try {
    // ── Log job details ────────────────────────────────────────────────────
    logHeader('INPUT: Job Details');
    logKeyValue('Job ID',           job?.id,           4);
    logKeyValue('Title',            job?.title,        4);
    logKeyValue('Service Category', serviceName,       4);
    logKeyValue('Description',      job?.description ? job.description.substring(0, 150) + '...' : '—', 4);
    logKeyValue('Budget',           resolveBudget(job?.budget), 4);
    logKeyValue('Start Date',       job?.startDate,    4);
    logKeyValue('End Date',         job?.endDate,      4);
    logKeyValue('Location',         resolveLocation(job?.location), 4);
    if (job?.priorities) { console.log('    Priorities:'); logJson(job.priorities, 6); }

    // ── Step 1: Build buyer-facing query ──────────────────────────────────
    logHeader('STEP 1: Build Search Query');
    console.log('  🤖 Building buyer-facing search query...');

    const query = await buildBuyerFacingQuery(job);

    console.log(`\n  ✅ Generated Search Query:`);
    console.log('  ' + SUB_DIVIDER);
    query.split('\n').forEach((l) => console.log(`    ${l}`));
    console.log('  ' + SUB_DIVIDER);
    console.log(`  📏 Query length: ${query.length} characters`);

    // ── Step 2: Semantic search ────────────────────────────────────────────
    logHeader('STEP 2: Semantic Search (Embeddings)');
    console.log('  🔍 Searching SellerEmbedding table for top 40 matches...');
    console.log(`  📌 Service category filter: "${serviceName}" (normalised internally)`);

    const top40 = await searchSellersByQuery(query, 40, serviceName);

    console.log(`\n  ✅ Embedding Search Results: ${top40.length} sellers found`);

    if (top40.length > 0) {
      console.log('  ' + SUB_DIVIDER);
      top40.slice(0, 10).forEach((s, i) => {
        const score   = s.similarity_score != null ? (s.similarity_score * 100).toFixed(2) : '—';
        const preview = (s.searchable_text ?? '').substring(0, 80);
        console.log(`    [${i + 1}] Seller ID: ${s.seller_id}  Similarity: ${score}%`);
        console.log(`        ${preview}...`);
      });
      if (top40.length > 10) console.log(`    ... and ${top40.length - 10} more sellers`);
      console.log('  ' + SUB_DIVIDER);
    }

    let rankedIds;

    if (top40.length === 0) {
      // ── Fallback: tool-based graph ─────────────────────────────────────
      logHeader('FALLBACK: Tool-Based Matching');
      console.log('  ⚠️  Semantic search returned 0 results (even after widening)');
      console.log('  🔄 Falling back to list_seller_profiles_for_job tool...');

      rankedIds = await runFallbackProviderMatching(job);

      console.log(`\n  ✅ Fallback returned ${rankedIds?.length ?? 0} seller IDs`);
      if (rankedIds?.length > 0) {
        rankedIds.forEach((id, i) => console.log(`    [${i + 1}] ${id}`));
      }
    } else {
      // ── Step 3: Rerank ─────────────────────────────────────────────────
      logHeader('STEP 3: Rerank with LLM');

      // Optional: apply a minimum similarity threshold before reranking
      const filtered = MIN_SIMILARITY_SCORE > 0
        ? top40.filter((s) => (s.similarity_score ?? 0) >= MIN_SIMILARITY_SCORE)
        : top40;

      console.log(
        `  🤖 Reranking ${filtered.length} candidates (${top40.length - filtered.length} below threshold) → top 15...`,
      );

      const rerankedCandidates = filtered.length > 0 ? filtered : top40;
      const top15Ids           = await rerankCandidatesForJob(job, rerankedCandidates, 15);

      console.log(`\n  ✅ Reranked Seller IDs (top ${top15Ids.length}):`);
      console.log('  ' + SUB_DIVIDER);
      top15Ids.forEach((id, i) => console.log(`    [${i + 1}] ${id}`));
      console.log('  ' + SUB_DIVIDER);

      const top15Candidates = top15Ids
        .map((id) => top40.find((c) => c.seller_id === id))
        .filter(Boolean);

      // ── Step 4: Final LLM Ranking ──────────────────────────────────────
      logHeader('STEP 4: Final LLM Ranking');
      rankedIds = await finalRankProvidersForJob(job, top15Candidates);
    }

    if (!rankedIds || rankedIds.length === 0) {
      const duration = Date.now() - startTime;
      console.log(`\n  ❌ No providers found for this job`);
      console.log(`  ⏱️  Duration: ${duration}ms`);
      console.log('▓'.repeat(70) + '\n');
      return { providers: [] };
    }

    // ── Step 5: Fetch full provider details ────────────────────────────────
    logHeader('STEP 5: Fetch Provider Details');
    console.log(`  📥 Loading complete profile data for ${rankedIds.length} providers...`);

    const profiles = await prisma.sellerProfile.findMany({
      where: { id: { in: rankedIds }, active: true },
    });
    const byId    = new Map(profiles.map((p) => [p.id, p]));
    const providers = rankedIds
      .map((id) => byId.get(id))
      .filter(Boolean)
      .map((p) => sellerProfileToProvider(p));

    console.log(`  ✅ Retrieved ${providers.length} active provider profiles`);

    // ── Summary ────────────────────────────────────────────────────────────
    const duration = Date.now() - startTime;
    console.log('\n' + '▓'.repeat(70));
    console.log(`${LOG_PREFIX} 🏁 PIPELINE COMPLETED`);
    console.log('▓'.repeat(70));
    logKeyValue('Job ID',           job?.id,              4);
    logKeyValue('Service Category', serviceName,          4);
    logKeyValue('Providers Found',  providers.length,     4);
    logKeyValue('Duration',         `${duration}ms`,      4);

    console.log('\n  🏆 Matched Providers (Ranked):');
    console.log('  ' + DIVIDER);
    providers.forEach((p, i) => {
      console.log(`\n    [${i + 1}] Provider ID: ${p.provider_id || p.id}`);
      console.log('    ' + '─'.repeat(40));
      if (p.serviceCategoryNames) {
        logKeyValue('Services', Array.isArray(p.serviceCategoryNames)
          ? p.serviceCategoryNames.join(', ')
          : p.serviceCategoryNames, 6);
      }
      logKeyValue('Service Area',         resolveLocation(p.serviceArea), 6);
      logKeyValue('Jobs Completed',       p.jobsCompleted ?? p.totalBidsAccepted ?? 0, 6);
      logKeyValue('Licensed',             p.licensed ? 'Yes' : 'No', 6);
      logKeyValue('References Available', p.referencesAvailable ? 'Yes' : 'No', 6);
      if (p.bio) logKeyValue('Bio', p.bio.substring(0, 100) + '...', 6);
    });

    console.log('\n  ' + DIVIDER);
    console.log(`\n  ✅ Returning ${providers.length} providers`);
    console.log('▓'.repeat(70) + '\n');

    return { providers };
  } catch (error) {
    const duration = Date.now() - startTime;
    console.error(`\n  ❌ Error: ${error.message}`);
    console.error(`  Stack: ${error.stack}`);
    logKeyValue('Duration', `${duration}ms`, 2);
    console.log('▓'.repeat(70) + '\n');
    return { providers: [], error: error.message };
  }
}