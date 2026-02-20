/**
 * Rerank service — fully LLM-based relevance judgment.
 *
 * Key changes over v1:
 *
 *  rerankJobsForSeller:
 *    - System prompt now explicitly instructs the LLM to EXCLUDE jobs the
 *      seller cannot do (e.g. "home cleaning" for a "concrete work" seller)
 *      AND rank the remaining ones. No hardcoded category strings, no
 *      similarity thresholds — the LLM reasons about it like a human recruiter.
 *    - Profile summary now correctly reads { hourly_rate: 60 } pricing shape.
 *    - Profile summary passes availability correctly so the LLM can judge fit.
 *
 *  rerankCandidatesForJob:
 *    - System prompt now explicitly instructs the LLM to EXCLUDE sellers who
 *      do not offer the required service, even if their profile is otherwise
 *      strong. Same philosophy — LLM as the relevance gate, not string logic.
 */

import 'dotenv/config';
import { ChatOpenAI } from '@langchain/openai';
import { HumanMessage, SystemMessage } from '@langchain/core/messages';

const DEFAULT_TOP_N  = 15;
const SELLER_TOP_JOBS = 10;
const LOG_PREFIX      = '[RerankService]';
const OPENAI_API_KEY  = process.env.OPENAI_API_KEY;

/* ─────────────────────────────────────────────────────────────
   PARSE HELPERS
───────────────────────────────────────────────────────────── */

function parseRankedIds(content, key) {
  if (typeof content !== 'string' || !content.trim()) return null;
  const jsonBlock = content.match(/```(?:json)?\s*(\{[\s\S]*?\})\s*```/)?.[1];
  const str = jsonBlock ?? content;
  try {
    const obj = JSON.parse(str);
    const ids = obj?.[key];
    return Array.isArray(ids) ? ids.filter((id) => id != null && String(id).trim()) : null;
  } catch {
    const match = content.match(new RegExp(`"${key}"\\s*:\\s*\\[([^\\]]*)\\]`));
    if (match) {
      try {
        return JSON.parse('[' + match[1] + ']').filter((id) => id != null && String(id).trim());
      } catch { return null; }
    }
  }
  return null;
}

/* ─────────────────────────────────────────────────────────────
   PROFILE SUMMARY BUILDER
   Handles all known DB field shapes — no silent drops.
───────────────────────────────────────────────────────────── */

function buildProfileSummary(sellerProfile) {
  const parts = [];

  // Services
  const services = Array.isArray(sellerProfile?.service_category_names)
    ? sellerProfile.service_category_names.filter(Boolean)
    : [];
  if (services.length > 0) {
    parts.push(`Services offered: ${services.join(', ')}`);
  }

  // Location
  const area = sellerProfile?.service_area;
  if (area && typeof area === 'object') {
    const loc = area.location || area.city || area.address;
    if (loc) parts.push(`Service area: ${String(loc)}`);
  } else if (typeof area === 'string' && area.trim()) {
    parts.push(`Service area: ${area.trim()}`);
  }

  // Pricing — handle { hourly_rate: 60 } AND { hourly_rate_min, hourly_rate_max }
  const pricing = sellerProfile?.pricing;
  if (pricing && typeof pricing === 'object') {
    if (pricing.hourly_rate != null) {
      parts.push(`Pricing: $${pricing.hourly_rate} per hour`);
    } else if (pricing.hourly_rate_min != null || pricing.hourly_rate_max != null) {
      parts.push(`Pricing: $${pricing.hourly_rate_min ?? '?'}–$${pricing.hourly_rate_max ?? '?'} per hour`);
    }
  }

  // Availability — handle { weekdays: "8 AM - 6 PM", weekends: "not available" }
  // and boolean flag shapes
  const avail = sellerProfile?.availability;
  if (avail && typeof avail === 'object') {
    const ap = [];
    if (avail.schedule)  ap.push(String(avail.schedule));
    if (avail.weekdays)  ap.push(`weekdays: ${avail.weekdays}`);
    if (avail.weekends) {
      const wknd = String(avail.weekends).toLowerCase().trim();
      if (wknd === 'not available' || wknd === 'unavailable' || wknd === 'no') {
        ap.push('not available on weekends');
      } else {
        ap.push(`weekends: ${avail.weekends}`);
      }
    }
    if (avail.weekday_evenings) ap.push('weekday evenings');
    if (avail.same_day)         ap.push('same-day available');
    if (ap.length > 0) parts.push(`Availability: ${ap.join(', ')}`);
  }

  // Credentials — handle { license: "general license" } AND { licensed: true }
  const cred = sellerProfile?.credentials;
  if (cred && typeof cred === 'object') {
    const cp = [];
    if (cred.licensed === true)                        cp.push('licensed');
    if (cred.license && String(cred.license).trim())   cp.push(String(cred.license).trim());
    if (cred.references_available === true)            cp.push('references available');
    if (cred.years_experience != null)                 cp.push(`${cred.years_experience} years experience`);
    if (cp.length > 0) parts.push(`Credentials: ${cp.join(', ')}`);
  }

  // Bio
  if (sellerProfile?.bio && String(sellerProfile.bio).trim()) {
    parts.push(`Bio: ${String(sellerProfile.bio).slice(0, 200)}`);
  }

  return parts.join('\n') || 'Service provider (no profile details)';
}

/* ─────────────────────────────────────────────────────────────
   RERANK JOBS FOR SELLER
   
   The LLM acts as a human recruiter:
   1. First it decides if the seller CAN do each job (relevance gate)
   2. Then it ranks the ones they can do by best fit
   3. If none are suitable, it returns empty array
   
   No hardcoded category strings. No similarity thresholds.
   The LLM reasons about semantic trade relationships.
───────────────────────────────────────────────────────────── */

const RERANK_JOBS_SYSTEM = `You are acting as a smart recruiter matching a service provider (seller) to job listings.

Your job has TWO steps:

STEP 1 — FILTER: Go through each job candidate and decide if this seller CAN do that job based on their skills and services. Use real-world trade knowledge — for example:
- A "concrete work" provider CAN do "foundation repair", "concrete pouring", "slab work"
- A "concrete work" provider CANNOT do "home cleaning", "painting", "electrical work"
- A "plumber" CAN do "pipe repair", "drain cleaning", "water heater installation"
- An "electrician" CANNOT do "landscaping" or "roof repair"
Use common sense about which trades overlap and which do not.

STEP 2 — RANK: From the jobs the seller CAN do, rank them by best fit considering:
- How well the job matches their specific skills
- Location compatibility
- Budget vs their pricing
- Timeline vs their availability

OUTPUT RULES:
- Return ONLY a JSON object: {"ranked_job_ids": ["job_id_1", "job_id_2", ...]}
- Only include jobs the seller CAN do
- If the seller cannot do ANY of the jobs, return: {"ranked_job_ids": []}
- No explanation, no other text, just the JSON`;

export async function rerankJobsForSeller(sellerProfile, candidates, topN = SELLER_TOP_JOBS) {
  const cap = Math.min(Math.max(Number(topN) || SELLER_TOP_JOBS, 1), 50);

  console.log('\n' + '='.repeat(60));
  console.log(`${LOG_PREFIX} Rerank jobs for seller: ${candidates?.length ?? 0} candidates -> top ${cap}`);
  console.log('='.repeat(60));

  if (!candidates || candidates.length === 0) {
    console.log('  No job candidates to rerank.');
    console.log('='.repeat(60) + '\n');
    return [];
  }

  const candidateSet = new Set(candidates.map((c) => c.job_id).filter(Boolean));
  if (candidateSet.size === 0) {
    console.log('='.repeat(60) + '\n');
    return [];
  }

  if (!OPENAI_API_KEY || !OPENAI_API_KEY.trim()) {
    const fallback = candidates.slice(0, cap).map((c) => c.job_id);
    console.log('  No OPENAI_API_KEY — returning candidates in original order');
    console.log('='.repeat(60) + '\n');
    return fallback;
  }

  const profileSummary = buildProfileSummary(sellerProfile);

  const candidateList = candidates.slice(0, 50).map((c, i) => {
    const snippet = (c.searchable_text || '').slice(0, 400);
    return `${i + 1}. job_id: ${c.job_id} | ${snippet || 'No summary'}`;
  }).join('\n');

  console.log('\n  Seller profile summary:');
  console.log('  ' + '-'.repeat(56));
  profileSummary.split('\n').forEach((l) => console.log('  ' + l));
  console.log('  ' + '-'.repeat(56));
  console.log(`\n  Candidates: ${candidates.length} jobs`);

  const userPrompt =
    `Seller profile:\n${profileSummary}\n\n` +
    `Job candidates:\n${candidateList}\n\n` +
    `Return JSON with ranked_job_ids — only jobs this seller CAN do, best fit first. ` +
    `If none are suitable return {"ranked_job_ids": []}`;

  const llm = new ChatOpenAI({
    model: 'gpt-4o-mini',
    temperature: 0.1,
    openAIApiKey: OPENAI_API_KEY,
  });

  let content;
  try {
    const response = await llm.invoke([
      new SystemMessage(RERANK_JOBS_SYSTEM),
      new HumanMessage(userPrompt),
    ]);
    content = response?.content;
  } catch (err) {
    console.error(`${LOG_PREFIX} LLM error:`, err.message);
    return candidates.slice(0, cap).map((c) => c.job_id);
  }

  console.log('\n  LLM response:');
  if (typeof content === 'string') content.split('\n').forEach((l) => console.log('  ' + l));

  const ranked = parseRankedIds(typeof content === 'string' ? content : '', 'ranked_job_ids');

  // LLM explicitly returned empty — means no suitable jobs
  if (ranked && Array.isArray(ranked) && ranked.length === 0) {
    console.log('\n  ⚠️  LLM determined: no suitable jobs for this seller');
    console.log('='.repeat(60) + '\n');
    return [];
  }

  // Filter to only valid IDs from our candidate set
  const valid = ranked ? ranked.filter((id) => candidateSet.has(id)) : [];

  // If LLM response was unparseable, fall back to original order
  const result = valid.length > 0 ? valid.slice(0, cap) : candidates.slice(0, cap).map((c) => c.job_id);

  console.log(`\n  Reranked top ${result.length} (job_id in order):`);
  result.forEach((id, i) => console.log(`  [${i + 1}] ${id}`));
  console.log('='.repeat(60) + '\n');

  return result;
}

/* ─────────────────────────────────────────────────────────────
   RERANK CANDIDATES FOR JOB
   
   Same philosophy — LLM filters out sellers who don't offer
   the required service, then ranks remaining by best fit.
───────────────────────────────────────────────────────────── */

const RERANK_SELLERS_SYSTEM = `You are acting as a smart recruiter matching service providers (sellers) to a buyer's job.

Your job has TWO steps:

STEP 1 — FILTER: Go through each seller candidate and decide if this seller CAN do the required job based on their skills/services. Use real-world trade knowledge:
- A "concrete work" provider CAN do "foundation repair" jobs
- A "home cleaning" provider CANNOT do "electrical work" jobs
- A "plumber" CAN do "pipe repair" jobs
- Use common sense about which trades overlap and which do not

STEP 2 — RANK: From the sellers who CAN do the job, rank them by best fit considering:
- How well their specific skills match the job
- Location match
- Budget compatibility with their pricing
- Availability vs job timeline
- Credentials (licensed, references, experience)
- Track record (completed jobs)

OUTPUT RULES:
- Return ONLY a JSON object: {"ranked_seller_ids": ["id1", "id2", ...]}
- Only include sellers who CAN do this job
- If no sellers are suitable, return: {"ranked_seller_ids": []}
- No explanation, no other text, just the JSON`;

export async function rerankCandidatesForJob(job, candidates, topN = DEFAULT_TOP_N) {
  const cap = Math.min(Math.max(Number(topN) || DEFAULT_TOP_N, 1), 50);

  console.log('\n' + '='.repeat(60));
  console.log(`${LOG_PREFIX} Rerank sellers for job: ${candidates?.length ?? 0} candidates -> top ${cap}`);
  console.log('='.repeat(60));

  if (!candidates || candidates.length === 0) {
    console.log('  No candidates to rerank.');
    console.log('='.repeat(60) + '\n');
    return [];
  }

  const candidateSet = new Set(candidates.map((c) => c.seller_id).filter(Boolean));
  if (candidateSet.size === 0) {
    console.log('='.repeat(60) + '\n');
    return [];
  }

  if (!OPENAI_API_KEY || !OPENAI_API_KEY.trim()) {
    const fallback = candidates.slice(0, cap).map((c) => c.seller_id);
    console.log('  No OPENAI_API_KEY — returning candidates in original order');
    console.log('='.repeat(60) + '\n');
    return fallback;
  }

  // Build job summary
  const jobParts = [];
  if (job?.title)                 jobParts.push(`Title: ${job.title}`);
  if (job?.description)           jobParts.push(`Description: ${job.description}`);
  if (job?.service_category_name) jobParts.push(`Service required: ${job.service_category_name}`);
  if (job?.budget && typeof job.budget === 'object') {
    jobParts.push(`Budget: $${job.budget.min ?? '?'}–$${job.budget.max ?? '?'}`);
  }
  if (job?.location) {
    const loc = typeof job.location === 'object'
      ? (job.location.address || job.location.city || JSON.stringify(job.location))
      : String(job.location);
    jobParts.push(`Location: ${loc}`);
  }
  if (job?.startDate) jobParts.push(`Start: ${job.startDate}`);
  if (job?.priorities && typeof job.priorities === 'object' && Object.keys(job.priorities).length > 0) {
    jobParts.push(`Priorities: ${JSON.stringify(job.priorities)}`);
  }
  const jobSummary = jobParts.join('\n') || 'No job details';

  const candidateList = candidates.slice(0, 50).map((c, i) => {
    const snippet = (c.searchable_text || '').slice(0, 400);
    return `${i + 1}. seller_id: ${c.seller_id} | ${snippet || 'No summary'}`;
  }).join('\n');

  console.log('\n  Job context:');
  console.log('  ' + '-'.repeat(56));
  jobParts.forEach((l) => console.log('  ' + l));
  console.log('  ' + '-'.repeat(56));
  console.log(`\n  Candidates: ${candidates.length} sellers`);

  const userPrompt =
    `Job:\n${jobSummary}\n\n` +
    `Seller candidates:\n${candidateList}\n\n` +
    `Return JSON with ranked_seller_ids — only sellers who CAN do this job, best fit first. ` +
    `If none are suitable return {"ranked_seller_ids": []}`;

  const llm = new ChatOpenAI({
    model: 'gpt-4o-mini',
    temperature: 0.1,
    openAIApiKey: OPENAI_API_KEY,
  });

  let content;
  try {
    const response = await llm.invoke([
      new SystemMessage(RERANK_SELLERS_SYSTEM),
      new HumanMessage(userPrompt),
    ]);
    content = response?.content;
  } catch (err) {
    console.error(`${LOG_PREFIX} LLM error:`, err.message);
    return candidates.slice(0, cap).map((c) => c.seller_id);
  }

  console.log('\n  LLM response:');
  if (typeof content === 'string') content.split('\n').forEach((l) => console.log('  ' + l));

  const ranked = parseRankedIds(typeof content === 'string' ? content : '', 'ranked_seller_ids');

  // LLM explicitly returned empty — means no suitable sellers
  if (ranked && Array.isArray(ranked) && ranked.length === 0) {
    console.log('\n  ⚠️  LLM determined: no suitable sellers for this job');
    console.log('='.repeat(60) + '\n');
    return [];
  }

  const valid  = ranked ? ranked.filter((id) => candidateSet.has(id)) : [];
  const result = valid.length > 0 ? valid.slice(0, cap) : candidates.slice(0, cap).map((c) => c.seller_id);

  console.log(`\n  Reranked top ${result.length} (seller_id in order):`);
  result.forEach((id, i) => console.log(`  [${i + 1}] ${id}`));
  console.log('='.repeat(60) + '\n');

  return result;
}