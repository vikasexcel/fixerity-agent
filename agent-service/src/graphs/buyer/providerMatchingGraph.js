/**
 * Provider Matching Graph — Upwork-style semantic pipeline.
 *
 * Pipeline:
 *   1. Build a buyer-facing query from the job
 *   2. Semantic search seller_embeddings (top 40) with normalised category filter
 *      → automatic fallback to widened search if 0 results
 *   3. Rerank top 40 → top 15 using rerankService
 *   4. Final LLM ranking → most accurate providers first
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

const LOG_PREFIX   = '[ProviderMatching]';
const DIVIDER      = '═'.repeat(70);
const SUB_DIVIDER  = '─'.repeat(70);

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
  const spaces = ' '.repeat(indent);
  console.log(`${spaces}${key}: ${value === null || value === undefined ? '—' : value}`);
}

function logJson(obj, indent = 4) {
  const spaces = ' '.repeat(indent);
  JSON.stringify(obj, null, 2).split('\n').forEach((l) => console.log(`${spaces}${l}`));
}

/* ─────────────────────────────────────────────────────────────
   HELPERS
───────────────────────────────────────────────────────────── */

const OPENAI_API_KEY = process.env.OPENAI_API_KEY;

function resolveLocation(location) {
  if (!location) return null;
  if (typeof location === 'string') return location.trim();
  if (typeof location === 'object') {
    return [location.address, location.city, location.state]
      .filter(Boolean).map((s) => String(s).trim()).join(', ') || null;
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
   QUERY BUILDER — buyer-facing language
───────────────────────────────────────────────────────────── */

async function buildBuyerFacingQuery(job) {
  const llmQuery = await buildOptimizedQueryForJob(job);
  const parts    = [];

  const location = resolveLocation(job?.location);
  if (location) parts.push(`Located in or serving ${location}.`);

  const budget = resolveBudget(job?.budget);
  if (budget) parts.push(`Budget: ${budget}.`);

  if (job?.startDate) parts.push(`Available to start on or before ${job.startDate}.`);

  if (llmQuery && llmQuery.trim()) parts.push(llmQuery.trim());

  const priorities = job?.priorities;
  if (priorities && typeof priorities === 'object') {
    const must = priorities.must_have;
    if (must && typeof must === 'object' && Object.keys(must).length > 0) {
      parts.push(`Must have: ${Object.keys(must).join(', ')}.`);
    }
  }

  return parts.join(' ').trim();
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
   - List seller IDs in order of best match first.
   - If no sellers are suitable, use: {"ranked_seller_ids": []}

Always call list_seller_profiles_for_job first with the service category from the job.`;

function buildFallbackUserMessage(job) {
  const budget = job.budget && typeof job.budget === 'object'
    ? `$${job.budget.min ?? '?'} - $${job.budget.max ?? '?'}`
    : String(job.budget ?? 'Not specified');

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
- priorities: ${job.priorities ? JSON.stringify(job.priorities) : 'None'}

Use the tools to get sellers for this service, then rank them and reply with the JSON block ranked_seller_ids.`;
}

async function agentNode(state) {
  const llm = new ChatOpenAI({
    model: 'gpt-4o-mini',
    temperature: 0.2,
    openAIApiKey: OPENAI_API_KEY,
  });
  const response = await llm.bindTools(buyerTools).invoke([
    new SystemMessage(MATCH_SYSTEM_PROMPT),
    ...state.messages,
  ]);
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
        return JSON.parse('[' + match[1] + ']').filter((id) => id != null && String(id).trim());
      } catch { return null; }
    }
  }
  return null;
}

function parseRankedSellerIds(messages) {
  const aiMessages = messages.filter(
    (m) => m._getType?.() === 'ai' || m.constructor?.name === 'AIMessage',
  );
  const last = aiMessages[aiMessages.length - 1];
  return typeof last?.content === 'string'
    ? parseRankedSellerIdsFromContent(last.content)
    : null;
}

/* ─────────────────────────────────────────────────────────────
   FALLBACK RUNNER
───────────────────────────────────────────────────────────── */

async function runFallbackProviderMatching(job) {
  logSection('Fallback: Tool-Based Graph Execution');
  const userMessage = buildFallbackUserMessage(job);
  console.log('\n  ⏳ Invoking tool-based matching graph...');

  const finalState = await graph.invoke({ messages: [new HumanMessage(userMessage)] });
  const messages   = finalState?.messages ?? [];

  console.log(`\n  ✅ Graph completed with ${messages.length} messages`);

  let rankedIds = parseRankedSellerIds(messages);

  if (!rankedIds || rankedIds.length === 0) {
    const toolMessages = messages.filter((m) => m.tool_calls?.length || m.name);
    for (const m of toolMessages) {
      if (m.content && typeof m.content === 'string') {
        try {
          const data    = JSON.parse(m.content);
          const sellers = data?.sellers ?? data?.results;
          if (Array.isArray(sellers) && sellers.length > 0) {
            rankedIds = sellers.map((s) => s.seller_id ?? s.id).filter(Boolean);
            break;
          }
        } catch { /* ignore */ }
      }
    }
  }

  return rankedIds || [];
}

/* ─────────────────────────────────────────────────────────────
   SELLER DETAIL BUILDER
   Builds comprehensive seller description from SellerProfile record
───────────────────────────────────────────────────────────── */

function buildSellerDetail(profile) {
  const parts = [];

  // Services
  const services = Array.isArray(profile.serviceCategoryNames)
    ? profile.serviceCategoryNames.filter(Boolean)
    : [];
  if (services.length > 0) {
    parts.push(`Services: ${services.join(', ')}`);
  }

  // Location
  const area = profile.serviceArea;
  if (area && typeof area === 'object') {
    const loc = area.location || area.city || area.address;
    if (loc) parts.push(`Service area: ${String(loc)}`);
  } else if (typeof area === 'string' && area.trim()) {
    parts.push(`Service area: ${area.trim()}`);
  }

  // Pricing
  const pricing = profile.pricing;
  if (pricing && typeof pricing === 'object') {
    if (pricing.hourly_rate != null) {
      parts.push(`Pricing: $${pricing.hourly_rate}/hr`);
    } else if (pricing.hourly_rate_min != null || pricing.hourly_rate_max != null) {
      parts.push(`Pricing: $${pricing.hourly_rate_min ?? '?'}–$${pricing.hourly_rate_max ?? '?'}/hr`);
    }
  }

  // Availability
  const avail = profile.availability;
  if (avail && typeof avail === 'object') {
    const ap = [];
    if (avail.schedule) ap.push(String(avail.schedule));
    if (avail.weekdays) ap.push(`weekdays: ${avail.weekdays}`);
    if (avail.weekends && String(avail.weekends).toLowerCase().trim() !== 'not available') {
      ap.push(`weekends: ${avail.weekends}`);
    }
    if (avail.emergency) ap.push('emergency available');
    if (ap.length > 0) parts.push(`Availability: ${ap.join(', ')}`);
  }

  // Credentials
  const cred = profile.credentials;
  if (cred && typeof cred === 'object') {
    const cp = [];
    if (cred.licensed === true) cp.push('licensed');
    if (cred.license && String(cred.license).trim()) cp.push(String(cred.license).trim());
    if (cred.references_available === true) cp.push('references available');
    if (cred.years_experience != null) cp.push(`${cred.years_experience} years experience`);
    if (cp.length > 0) parts.push(`Credentials: ${cp.join(', ')}`);
  }

  // Bio
  if (profile.bio && String(profile.bio).trim()) {
    parts.push(`Bio: ${String(profile.bio).trim()}`);
  }

  // Conversation-derived profile
  const conv = profile.preferences?.conversation_profile;
  if (conv && typeof conv === 'object') {
    const cp = [];
    if (conv.equipment?.length) cp.push(`Equipment: ${conv.equipment.join(', ')}`);
    if (conv.materials?.length) cp.push(`Materials: ${conv.materials.join(', ')}`);
    if (conv.project_focus) cp.push(`Focus: ${conv.project_focus}`);
    if (conv.additional_services?.length) cp.push(`Also offers: ${conv.additional_services.join(', ')}`);
    if (cp.length > 0) parts.push(cp.join('; '));
  }

  // Track record
  if (profile.totalBidsAccepted != null && profile.totalBidsAccepted > 0) {
    parts.push(`Completed jobs: ${profile.totalBidsAccepted}`);
  }

  return parts.join(' | ');
}

/* ─────────────────────────────────────────────────────────────
   FINAL LLM RANKER
───────────────────────────────────────────────────────────── */

const FINAL_RANK_SYSTEM = `You are acting as a smart recruiter doing a final selection of service providers (sellers) for a buyer's job.

Your job has TWO steps:

STEP 1 — FILTER: Decide if each seller CAN actually do the required job based on their skills and services. Use real-world trade knowledge:
- A "concrete work" provider CAN do "foundation repair", "slab work", "concrete pouring"
- A "concrete work" provider CANNOT do "home cleaning", "painting", "electrical work"
- A "plumber" CAN do "pipe repair", "drain cleaning", "water heater installation"
- A "home cleaning" provider CANNOT do "roofing" or "carpentry"
Use common sense about which trades overlap and which do not.

STEP 2 — RANK: From the sellers who CAN do the job, rank them by best overall fit:
- How closely their specific skills match the job requirements
- Location compatibility with the job
- Budget compatibility (their pricing vs job budget)
- Credentials (licensed, references, years of experience)
- Track record (completed jobs)
- Availability vs job timeline

OUTPUT RULES:
- Return ONLY a JSON object: {"ranked_seller_ids": ["id1", "id2", ...]}
- Only include sellers who CAN do this job
- If NO sellers are suitable, return: {"ranked_seller_ids": []}
- No explanation, no other text, just the JSON`;

async function finalRankProvidersForJob(job, candidates) {
  logSection('Final LLM Ranking');

  if (!candidates || candidates.length === 0) return [];
  const candidateSet = new Set(candidates.map((c) => c.seller_id).filter(Boolean));
  if (candidateSet.size === 0) return [];

  console.log(`  🤖 Ranking ${candidates.length} candidates...`);

  if (!OPENAI_API_KEY || !OPENAI_API_KEY.trim()) {
    return candidates.map((c) => c.seller_id);
  }

  const jobSummary = [
    job?.title                 && `Title: ${job.title}`,
    job?.description           && `Description: ${job.description}`,
    job?.service_category_name && `Service: ${job.service_category_name}`,
    resolveBudget(job?.budget) && `Budget: ${resolveBudget(job.budget)}`,
    resolveLocation(job?.location) && `Location: ${resolveLocation(job.location)}`,
    job?.startDate             && `Start: ${job.startDate}`,
    job?.priorities            && `Priorities: ${JSON.stringify(job.priorities)}`,
  ].filter(Boolean).join('\n');

  // Fetch full seller profiles from database
  const sellerIds = candidates.map((c) => c.seller_id).filter(Boolean);
  let sellerRecords = [];
  
  try {
    sellerRecords = await prisma.sellerProfile.findMany({
      where: { id: { in: sellerIds } },
    });
    console.log(`\n  ✅ Fetched ${sellerRecords.length} full seller profiles from database`);
  } catch (err) {
    console.error(`${LOG_PREFIX} Error fetching sellers from DB:`, err.message);
    // Fallback to searchable_text if DB fetch fails
    const candidateList = candidates
      .map((c, i) => `${i + 1}. seller_id: ${c.seller_id} | ${c.searchable_text || 'No summary'}`)
      .join('\n');

    console.log('\n  Job context:');
    console.log('  ' + '-'.repeat(56));
    jobSummary.split('\n').forEach((l) => console.log('  ' + l));
    console.log('  ' + '-'.repeat(56));
    console.log(`\n  Candidates: ${candidates.length} sellers (using cached text)`);

    const userPrompt =
      `Job:\n${jobSummary}\n\n` +
      `Seller candidates:\n${candidateList}\n\n` +
      `Return JSON with ranked_seller_ids — only sellers who CAN do this job, best fit first. ` +
      `If none are suitable return {"ranked_seller_ids": []}`;

    const llm = new ChatOpenAI({ model: 'gpt-4o-mini', temperature: 0.1, openAIApiKey: OPENAI_API_KEY });

    let content;
    try {
      const response = await llm.invoke([
        new SystemMessage(FINAL_RANK_SYSTEM),
        new HumanMessage(userPrompt),
      ]);
      content = response?.content;
    } catch (err2) {
      console.error(`${LOG_PREFIX} LLM error in finalRankProvidersForJob:`, err2.message);
      return candidates.map((c) => c.seller_id);
    }

    console.log('\n  LLM response:');
    if (typeof content === 'string') content.split('\n').forEach((l) => console.log('    ' + l));

    const ranked = parseRankedSellerIdsFromContent(typeof content === 'string' ? content : '');

    if (ranked && Array.isArray(ranked) && ranked.length === 0) {
      console.log('\n  ⚠️  LLM determined: no suitable providers for this job');
      return [];
    }

    const valid = ranked ? ranked.filter((id) => candidateSet.has(id)) : [];
    const result = valid.length > 0 ? valid : candidates.map((c) => c.seller_id);

    console.log(`\n  ✅ Final ranked: ${result.length} providers`);
    result.forEach((id, i) => console.log(`    [${i + 1}] ${id}`));

    return result;
  }

  // Build seller summaries from database records
  const sellerMap = new Map(sellerRecords.map((s) => [s.id, s]));
  const candidateList = sellerIds.map((sellerId, i) => {
    const seller = sellerMap.get(sellerId);
    if (!seller) {
      // Fallback to cached text if seller not found
      const candidate = candidates.find((c) => c.seller_id === sellerId);
      const fallbackText = candidate?.searchable_text || 'Seller not found';
      return `${i + 1}. seller_id: ${sellerId} | ${fallbackText}`;
    }
    const summary = buildSellerDetail(seller);
    return `${i + 1}. seller_id: ${sellerId} | ${summary}`;
  }).join('\n');

  console.log('\n  Job context:');
  console.log('  ' + '-'.repeat(56));
  jobSummary.split('\n').forEach((l) => console.log('  ' + l));
  console.log('  ' + '-'.repeat(56));
  console.log(`\n  Candidates: ${sellerRecords.length} sellers (with full details from DB)`);

  const userPrompt =
    `Job:\n${jobSummary}\n\n` +
    `Seller candidates:\n${candidateList}\n\n` +
    `Return JSON with ranked_seller_ids — only sellers who CAN do this job, best fit first. ` +
    `If none are suitable return {"ranked_seller_ids": []}`;

  const llm = new ChatOpenAI({ model: 'gpt-4o-mini', temperature: 0.1, openAIApiKey: OPENAI_API_KEY });

  let content;
  try {
    const response = await llm.invoke([
      new SystemMessage(FINAL_RANK_SYSTEM),
      new HumanMessage(userPrompt),
    ]);
    content = response?.content;
  } catch (err) {
    console.error(`${LOG_PREFIX} LLM error in finalRankProvidersForJob:`, err.message);
    return candidates.map((c) => c.seller_id);
  }

  console.log('\n  LLM response:');
  if (typeof content === 'string') content.split('\n').forEach((l) => console.log('    ' + l));

  const ranked = parseRankedSellerIdsFromContent(typeof content === 'string' ? content : '');

  // LLM explicitly returned empty — no suitable sellers, respect that decision
  if (ranked && Array.isArray(ranked) && ranked.length === 0) {
    console.log('\n  ⚠️  LLM determined: no suitable providers for this job');
    return [];
  }

  // Filter to only valid IDs from our candidate set
  const valid = ranked ? ranked.filter((id) => candidateSet.has(id)) : [];

  // If LLM response was unparseable, fall back to original order
  const result = valid.length > 0 ? valid : candidates.map((c) => c.seller_id);

  console.log(`\n  ✅ Final ranked: ${result.length} providers`);
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
    provider_id:           profile?.id ?? profile?.seller_id,
    id:                    profile?.id ?? profile?.seller_id,
    average_rating:        0,
    total_completed_order: profile?.totalBidsAccepted ?? 0,
    jobsCompleted:         profile?.totalBidsAccepted ?? 0,
    licensed:              cred.licensed === true || !!(cred.license),
    referencesAvailable:   cred.references_available === true,
    num_of_rating:         cred.references_available ? 1 : 0,
    deadline_in_days:      3,
    ...profile,
  };
}

/* ─────────────────────────────────────────────────────────────
   MAIN PIPELINE
───────────────────────────────────────────────────────────── */

export async function runProviderMatching(job) {
  const startTime   = Date.now();

  console.log('\n' + '▓'.repeat(70));
  console.log(`${LOG_PREFIX} 🚀 PROVIDER MATCHING PIPELINE STARTED`);
  console.log('▓'.repeat(70));

  try {
    logHeader('INPUT: Job Details');
    logKeyValue('Job ID',           job?.id,                    4);
    logKeyValue('Title',            job?.title,                 4);
    logKeyValue('Budget',           resolveBudget(job?.budget), 4);
    logKeyValue('Location',         resolveLocation(job?.location), 4);
    logKeyValue('Start Date',       job?.startDate,             4);

    // ── Step 1: Build buyer-facing query ──────────────────────────────────
    logHeader('STEP 1: Build Search Query');
    const query = await buildBuyerFacingQuery(job);
    console.log(`\n  ✅ Query: ${query}`);
    console.log(`  📏 Length: ${query.length} characters`);

    // ── Step 2: Pure semantic search ───────────────────────────────────────
    logHeader('STEP 2: Semantic Search (Embeddings)');
    console.log(`  🔍 Searching for best matches based on job description`);

    const top40 = await searchSellersByQuery(query, 40);

    console.log(`\n  ✅ Embedding Search: ${top40.length} sellers found`);
    if (top40.length > 0) {
      top40.slice(0, 5).forEach((s, i) => {
        const score = s.similarity_score != null ? (s.similarity_score * 100).toFixed(2) : '—';
        console.log(`    [${i + 1}] ${s.seller_id}  similarity: ${score}%`);
      });
    }

    let rankedIds;

    if (top40.length === 0) {
      logHeader('FALLBACK: Tool-Based Matching');
      console.log('  ⚠️  Semantic search returned 0 results (even after widening)');
      rankedIds = await runFallbackProviderMatching(job);
      console.log(`\n  ✅ Fallback returned ${rankedIds?.length ?? 0} seller IDs`);
    } else {
      // ── Step 3: Rerank ─────────────────────────────────────────────────
      logHeader('STEP 3: Rerank with LLM');
      console.log(`  🤖 Reranking ${top40.length} candidates → top 15...`);

      const top15Ids        = await rerankCandidatesForJob(job, top40, 15);
      const top15Candidates = top15Ids.map((id) => top40.find((c) => c.seller_id === id)).filter(Boolean);

      console.log(`\n  ✅ Reranked to ${top15Ids.length} candidates`);

      // ── Step 4: Final LLM Ranking ──────────────────────────────────────
      logHeader('STEP 4: Final LLM Ranking');
      rankedIds = await finalRankProvidersForJob(job, top15Candidates);
    }

    if (!rankedIds || rankedIds.length === 0) {
      const duration = Date.now() - startTime;
      console.log(`\n  ❌ No providers found. Duration: ${duration}ms`);
      console.log('▓'.repeat(70) + '\n');
      return { providers: [] };
    }

    // ── Step 5: Fetch full provider details ────────────────────────────────
    logHeader('STEP 5: Fetch Provider Details');
    const profiles  = await prisma.sellerProfile.findMany({
      where: { id: { in: rankedIds }, active: true },
    });
    const byId      = new Map(profiles.map((p) => [p.id, p]));
    const providers = rankedIds
      .map((id) => byId.get(id))
      .filter(Boolean)
      .map((p) => sellerProfileToProvider(p));

    const duration = Date.now() - startTime;

    console.log('\n' + '▓'.repeat(70));
    console.log(`${LOG_PREFIX} 🏁 PIPELINE COMPLETED`);
    console.log('▓'.repeat(70));
    logKeyValue('Service Category', serviceName,      4);
    logKeyValue('Providers Found',  providers.length, 4);
    logKeyValue('Duration',         `${duration}ms`,  4);

    console.log('\n  🏆 Matched Providers (Ranked):');
    providers.forEach((p, i) => {
      console.log(`\n    [${i + 1}] ${p.provider_id || p.id}`);
      if (p.serviceCategoryNames) {
        logKeyValue('Services', Array.isArray(p.serviceCategoryNames)
          ? p.serviceCategoryNames.join(', ') : p.serviceCategoryNames, 6);
      }
      logKeyValue('Licensed',    p.licensed ? 'Yes' : 'No', 6);
      logKeyValue('Jobs Done',   p.jobsCompleted ?? 0,      6);
    });

    console.log('▓'.repeat(70) + '\n');
    return { providers };

  } catch (error) {
    const duration = Date.now() - startTime;
    console.error(`\n  ❌ Error: ${error.message}`);
    console.error(`  Stack: ${error.stack}`);
    console.log(`  ⏱️  Duration: ${duration}ms`);
    console.log('▓'.repeat(70) + '\n');
    return { providers: [], error: error.message };
  }
}