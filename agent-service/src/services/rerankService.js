/**
 * Rerank service: use LLM to reduce candidates (e.g. 40) to top N (e.g. 15) for a job.
 */
import 'dotenv/config';
import { ChatOpenAI } from '@langchain/openai';
import { HumanMessage, SystemMessage } from '@langchain/core/messages';

const DEFAULT_TOP_N = 15;
const SELLER_TOP_JOBS = 10;
const LOG_PREFIX = '[RerankService]';
const OPENAI_API_KEY = process.env.OPENAI_API_KEY;

function parseRankedJobIds(content) {
  if (typeof content !== 'string' || !content.trim()) return null;
  const jsonBlock = content.match(/```(?:json)?\s*(\{[\s\S]*?\})\s*```/)?.[1];
  const str = jsonBlock ?? content;
  try {
    const obj = JSON.parse(str);
    const ids = obj?.ranked_job_ids;
    return Array.isArray(ids) ? ids.filter((id) => id != null && String(id).trim()) : null;
  } catch {
    const match = content.match(/"ranked_job_ids"\s*:\s*\[([^\]]*)\]/);
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

function parseRankedSellerIds(content) {
  if (typeof content !== 'string' || !content.trim()) return null;
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

export async function rerankCandidatesForJob(job, candidates, topN = DEFAULT_TOP_N) {
  const cap = Math.min(Math.max(Number(topN) || DEFAULT_TOP_N, 1), 50);

  console.log('\n' + '='.repeat(60));
  console.log(LOG_PREFIX + ' Rerank: ' + (candidates?.length ?? 0) + ' candidates -> top ' + cap);
  console.log('='.repeat(60));

  if (!candidates || candidates.length === 0) {
    console.log('  No candidates to rerank.');
  } else {
    console.log('\n  Job context:');
    console.log('  ' + '-'.repeat(56));
    if (job?.title) console.log('  Title: ' + job.title);
    if (job?.description) console.log('  Description: ' + (String(job.description).slice(0, 120) + (String(job.description).length > 120 ? '...' : '')));
    if (job?.service_category_name) console.log('  Service: ' + job.service_category_name);
    if (job?.budget && typeof job.budget === 'object') {
      console.log('  Budget: $' + (job.budget.min ?? '?') + ' - $' + (job.budget.max ?? '?'));
    }
    console.log('  ' + '-'.repeat(56));
    console.log('\n  Candidates in (seller_id only):');
    console.log('  ' + '-'.repeat(56));
    candidates.slice(0, 50).forEach((c, i) => {
      console.log('  [' + (i + 1) + '] ' + (c.seller_id ?? ''));
    });
    console.log('  ' + '-'.repeat(56) + '\n');
  }

  if (!candidates || candidates.length === 0) {
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
    console.log('  No OPENAI_API_KEY — using order as-is. Top ' + cap + ': ' + fallback.join(', '));
    console.log('='.repeat(60) + '\n');
    return fallback;
  }

  const jobParts = [];
  if (job?.title) jobParts.push('Title: ' + job.title);
  if (job?.description) jobParts.push('Description: ' + job.description);
  if (job?.service_category_name) jobParts.push('Service: ' + job.service_category_name);
  if (job?.budget && typeof job.budget === 'object') {
    jobParts.push('Budget: $' + (job.budget.min ?? '?') + '-$' + (job.budget.max ?? '?'));
  }
  if (job?.priorities) {
    jobParts.push('Priorities: ' + (typeof job.priorities === 'string' ? job.priorities : JSON.stringify(job.priorities)));
  }
  const jobSummary = jobParts.join('\n');
  const candidateList = candidates.slice(0, 50).map((c, i) => {
    const snippet = (c.searchable_text || '').slice(0, 300);
    return (i + 1) + '. seller_id: ' + c.seller_id + ' | ' + (snippet || 'No summary');
  }).join('\n');
  const systemPrompt = 'You are reranking service providers for a buyer job. Return the best ' + cap + ' seller IDs in order. Output ONLY JSON: {"ranked_seller_ids": ["id1", "id2", ...]}. No other text.';
  const userPrompt = 'Job:\n' + jobSummary + '\n\nCandidates:\n' + candidateList + '\n\nReturn JSON with ranked_seller_ids (best ' + cap + ' in order).';
  const llm = new ChatOpenAI({ model: 'gpt-4o-mini', temperature: 0.2, openAIApiKey: OPENAI_API_KEY });
  const response = await llm.invoke([new SystemMessage(systemPrompt), new HumanMessage(userPrompt)]);
  const content = response?.content;
  const ranked = parseRankedSellerIds(typeof content === 'string' ? content : '');
  const validRanked = (ranked && ranked.length > 0 ? ranked.filter((id) => candidateSet.has(id)) : candidates.slice(0, cap).map((c) => c.seller_id)).slice(0, cap);

  console.log('  Reranked top ' + cap + ' (seller_id in order):');
  console.log('  ' + '-'.repeat(56));
  validRanked.forEach((id, i) => console.log('  [' + (i + 1) + '] ' + id));
  console.log('  ' + '-'.repeat(56));
  console.log('='.repeat(60) + '\n');

  return validRanked;
}

/**
 * Rerank job candidates for a seller profile: use LLM to pick the best top N jobs (default 10).
 * @param {object} sellerProfile - Seller profile (service_category_names, service_area, availability, pricing, credentials, bio)
 * @param {Array<{ job_id: string, searchable_text?: string, title?: string, budget?: object }>} candidates - Job candidates from semantic search
 * @param {number} [topN=10] - Max number of jobs to return
 * @returns {Promise<string[]>} Ordered list of job_id (best first)
 */
export async function rerankJobsForSeller(sellerProfile, candidates, topN = SELLER_TOP_JOBS) {
  const cap = Math.min(Math.max(Number(topN) || SELLER_TOP_JOBS, 1), 50);

  console.log('\n' + '='.repeat(60));
  console.log(LOG_PREFIX + ' Rerank jobs for seller: ' + (candidates?.length ?? 0) + ' candidates -> top ' + cap);
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
    console.log('  No OPENAI_API_KEY — using order as-is. Top ' + cap + ' job_ids.');
    console.log('='.repeat(60) + '\n');
    return fallback;
  }

  const profileParts = [];
  if (Array.isArray(sellerProfile?.service_category_names) && sellerProfile.service_category_names.length > 0) {
    profileParts.push('Services: ' + sellerProfile.service_category_names.join(', '));
  }
  if (sellerProfile?.service_area?.location) {
    profileParts.push('Service area: ' + sellerProfile.service_area.location);
  }
  if (sellerProfile?.pricing && typeof sellerProfile.pricing === 'object') {
    const p = sellerProfile.pricing;
    profileParts.push('Pricing: $' + (p.hourly_rate_min ?? '?') + '-' + (p.hourly_rate_max ?? '?') + '/hr');
  }
  if (sellerProfile?.credentials?.licensed === true) profileParts.push('Licensed');
  if (sellerProfile?.credentials?.references_available === true) profileParts.push('References available');
  if (sellerProfile?.bio) profileParts.push('Bio: ' + String(sellerProfile.bio).slice(0, 200));
  const profileSummary = profileParts.join('\n') || 'Service provider';

  const candidateList = candidates.slice(0, 50).map((c, i) => {
    const snippet = (c.searchable_text || c.title || '').slice(0, 300);
    return (i + 1) + '. job_id: ' + c.job_id + ' | ' + (snippet || 'No summary');
  }).join('\n');

  const systemPrompt = 'You are reranking job listings for a service provider (seller). Return the best ' + cap + ' job_ids in order of fit for this provider. Output ONLY JSON: {"ranked_job_ids": ["job_id_1", "job_id_2", ...]}. No other text.';
  const userPrompt = 'Seller profile:\n' + profileSummary + '\n\nJob candidates:\n' + candidateList + '\n\nReturn JSON with ranked_job_ids (best ' + cap + ' in order).';
  const llm = new ChatOpenAI({ model: 'gpt-4o-mini', temperature: 0.2, openAIApiKey: OPENAI_API_KEY });
  const response = await llm.invoke([new SystemMessage(systemPrompt), new HumanMessage(userPrompt)]);
  const content = response?.content;
  const ranked = parseRankedJobIds(typeof content === 'string' ? content : '');
  const validRanked = (ranked && ranked.length > 0 ? ranked.filter((id) => candidateSet.has(id)) : candidates.slice(0, cap).map((c) => c.job_id)).slice(0, cap);

  console.log('  Reranked top ' + cap + ' (job_id in order):');
  validRanked.forEach((id, i) => console.log('  [' + (i + 1) + '] ' + id));
  console.log('='.repeat(60) + '\n');

  return validRanked;
}
