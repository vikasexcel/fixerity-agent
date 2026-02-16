/**
 * Job query service.
 * Builds an optimized retrieval query string from a job using an LLM, for semantic search over seller embeddings.
 */
import 'dotenv/config';
import { ChatOpenAI } from '@langchain/openai';
import { HumanMessage, SystemMessage } from '@langchain/core/messages';
const OPENAI_API_KEY = process.env.OPENAI_API_KEY;
const SYSTEM_PROMPT = `You are building a search query to find service providers (sellers) that match a buyer's job.

Given the job details, output a single, concise search query string that would best match seller profiles in a semantic search.
The query should capture: service type, skills needed, budget/price context, location or area, timeline, and any key requirements (e.g. licensed, references).
Use natural language. Output ONLY the query string, no JSON, no explanation, no prefix.`;

/**
 * Build job summary text for the LLM.
 * @param {object} job - Job object (id, title, description, budget, startDate, endDate, priorities, service_category_name, location, specificRequirements)
 * @returns {string}
 */
function jobToPromptText(job) {
  const parts = [];
  if (job.title) parts.push(`Title: ${job.title}`);
  if (job.description) parts.push(`Description: ${job.description}`);
  if (job.service_category_name) parts.push(`Service: ${job.service_category_name}`);
  if (job.budget && typeof job.budget === 'object') {
    parts.push(`Budget: $${job.budget.min ?? '?'} - $${job.budget.max ?? '?'}`);
  } else if (job.budget) parts.push(`Budget: ${job.budget}`);
  if (job.startDate) parts.push(`Start: ${job.startDate}`);
  if (job.endDate) parts.push(`End: ${job.endDate}`);
  if (job.location) {
    const loc = typeof job.location === 'object' ? job.location?.address || JSON.stringify(job.location) : job.location;
    if (loc) parts.push(`Location: ${loc}`);
  }
  if (job.priorities) {
    const p = typeof job.priorities === 'string' ? job.priorities : JSON.stringify(job.priorities);
    parts.push(`Priorities: ${p}`);
  }
  if (job.specificRequirements) {
    const sr = typeof job.specificRequirements === 'string'
      ? job.specificRequirements
      : JSON.stringify(job.specificRequirements);
    parts.push(`Requirements: ${sr}`);
  }
  return parts.join('\n') || 'No job details';
}

/**
 * Build an optimized retrieval query for the given job using an LLM.
 * @param {object} job - Full job object (title, description, budget, dates, priorities, service_category_name, location, etc.)
 * @returns {Promise<string>} Single query string for embedding and semantic search
 */
export async function buildOptimizedQueryForJob(job) {
  if (!job || (typeof job === 'object' && !job.title && !job.description && !job.service_category_name)) {
    return (job?.service_category_name || 'service provider') + ' needed';
  }

  if (!OPENAI_API_KEY || !OPENAI_API_KEY.trim()) {
    const fallback = job.service_category_name
      ? `${job.service_category_name} ${job.title || ''} ${job.description || ''}`.trim()
      : (job.title || job.description || 'service provider');
    const query = fallback.slice(0, 500);
    console.log('\n[JobQueryService] No OPENAI_API_KEY — using fallback query:', query + '\n');
    return query;
  }

  const llm = new ChatOpenAI({
    model: 'gpt-4o-mini',
    temperature: 0.2,
    openAIApiKey: OPENAI_API_KEY,
  });

  const userText = jobToPromptText(job);

  console.log('\n' + '='.repeat(60));
  console.log('[JobQueryService] Step A — Embed Job (Query): building retrieval query');
  console.log('='.repeat(60));
  console.log('\n  Job input (full details):');
  console.log('  ' + '-'.repeat(56));
  console.log(userText.split('\n').map((line) => '  ' + line).join('\n'));
  console.log('  ' + '-'.repeat(56) + '\n');

  const response = await llm.invoke([
    new SystemMessage(SYSTEM_PROMPT),
    new HumanMessage(`Job:\n${userText}\n\nOutput only the single search query string:`),
  ]);

  const content = response?.content;
  let query;
  if (typeof content !== 'string' || !content.trim()) {
    query = (job.service_category_name || 'service provider') + ' ' + (job.title || '').trim();
  } else {
    query = content.trim().slice(0, 1000);
  }

  console.log('  Built retrieval query (optimized for semantic search):');
  console.log('  ' + '-'.repeat(56));
  console.log('  ' + query);
  console.log('  ' + '-'.repeat(56));
  console.log('='.repeat(60) + '\n');

  return query;
}
