/**
 * Rerank service: use LLM to reduce candidates (e.g. 40) to top N (e.g. 15) for a job.
 */
import 'dotenv/config';
import { ChatOpenAI } from '@langchain/openai';
import { HumanMessage, SystemMessage } from '@langchain/core/messages';

const DEFAULT_TOP_N = 15;
const LOG_PREFIX = '[RerankService]';
const OPENAI_API_KEY = process.env.OPENAI_API_KEY;

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
