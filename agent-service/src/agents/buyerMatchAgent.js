/**
 * Buyer Match Agent: LangGraph React agent for job-to-provider matching.
 * Fetches providers via on-demand/public/provider-service-by-category (job's service_category_id
 * and user's access_token), then sends job + providers to the LLM for dynamic matching (no hardcoded rules).
 */

import { ChatOpenAI } from '@langchain/openai';
import { createReactAgent } from '@langchain/langgraph/prebuilt';
import { HumanMessage } from '@langchain/core/messages';
import { tool } from '@langchain/core/tools';
import { z } from 'zod';
import { post } from '../lib/laravelClient.js';
import * as mem0 from '../memory/mem0Client.js';
import { OPENAI_API_KEY } from '../config/index.js';

const PROVIDER_BY_CATEGORY_PATH = 'on-demand/public/provider-service-by-category';

/**
 * Fetch providers for a service category (user-authenticated).
 * @param {string} accessToken - User's access token
 * @param {number} service_category_id - From job
 * @returns {Promise<{ providers: Array, error?: string }>}
 */
async function fetchProvidersByCategory(accessToken, service_category_id) {
  const payload = {
    access_token: accessToken,
    service_category_id: Number(service_category_id) || 0,
  };
  try {
    const data = await post(PROVIDER_BY_CATEGORY_PATH, payload);
    if (data.status !== 1 || !Array.isArray(data.data)) {
      return { providers: [], error: data?.message || 'No providers found for this category.' };
    }
    return { providers: data.data };
  } catch (err) {
    const isNoData = err.message === 'Data Not Found' || /not found|no provider/i.test(err.message);
    return {
      providers: [],
      error: err.message,
      ...(isNoData && { message: 'No providers found for this category.' }),
    };
  }
}

/**
 * Create the tool that fetches providers by category (job's service_category_id + user access_token).
 */
function createGetProvidersByCategoryTool(accessToken) {
  return tool(
    async (input) => {
      const { service_category_id } = input;
      const { providers, error, message } = await fetchProvidersByCategory(accessToken, service_category_id);
      return JSON.stringify({
        providers,
        ...(error && { error }),
        ...(message && { message }),
      });
    },
    {
      name: 'getProvidersByCategory',
      description:
        'Fetch the list of providers (and their data) for a given service category. Use the job\'s service_category_id and the user\'s access_token. Returns providers array or error.',
      schema: z.object({
        service_category_id: z.number().describe('Service category ID from the job'),
      }),
    }
  );
}

/**
 * Extract deals from agent execution: from tool result or from LLM message JSON (deals array).
 * @param {Object} result - Agent invoke result
 * @param {Object} [job] - Job object for normalizing deal.job
 */
function extractDealsFromResult(result, job = null) {
  const messages = result?.messages ?? [];
  for (let i = messages.length - 1; i >= 0; i--) {
    const msg = messages[i];
    const content = msg?.content;
    if (typeof content === 'string') {
      const trimmed = content.trim();
      if (trimmed.startsWith('{')) {
        try {
          const parsed = JSON.parse(content);
          if (parsed.deals && Array.isArray(parsed.deals)) return normalizeDeals(parsed.deals, job);
        } catch {}
      }
      const jsonMatch = trimmed.match(/\{[\s\S]*"deals"[\s\S]*\}/);
      if (jsonMatch) {
        try {
          const parsed = JSON.parse(jsonMatch[0]);
          if (parsed.deals && Array.isArray(parsed.deals)) return normalizeDeals(parsed.deals, job);
        } catch {}
      }
    }
    if (Array.isArray(content)) {
      for (const block of content) {
        const str = typeof block === 'string' ? block : block?.content ?? block?.text;
        if (str && typeof str === 'string' && str.trim().startsWith('{')) {
          try {
            const parsed = JSON.parse(str);
            if (parsed.deals && Array.isArray(parsed.deals)) return normalizeDeals(parsed.deals, job);
          } catch {}
        }
      }
    }
  }
  return [];
}

/**
 * Ensure each deal has id, createdAt, job snippet for consistency.
 */
function normalizeDeals(deals, job = null) {
  const jobSnippet = job
    ? {
        id: job.id,
        title: job.title ?? 'Job',
        description: job.description ?? '',
        budget: job.budget ?? { min: 0, max: 0 },
        startDate: job.startDate ?? '',
        endDate: job.endDate ?? '',
        priorities: job.priorities ?? [],
      }
    : null;
  return deals.map((d, idx) => ({
    ...d,
    id: d.id ?? `deal_${d.jobId ?? 'unknown'}_${idx}`,
    createdAt: d.createdAt ?? new Date().toISOString().split('T')[0],
    job: d.job ?? jobSnippet,
  }));
}

/**
 * Ask LLM to return a deals array from job + providers (no tools). Used for fallback or direct matching.
 */
async function llmMatchProvidersToJob(job, providers) {
  const llm = new ChatOpenAI({
    model: 'gpt-4o-mini',
    temperature: 0,
    openAIApiKey: OPENAI_API_KEY,
  });
  const prompt = `You are a buyer matching assistant. Given the job and the list of providers below, dynamically match and rank the best providers. Use your judgment only; there are no fixed rules.

Job (JSON):
${JSON.stringify(job, null, 2)}

Providers (JSON array):
${JSON.stringify(providers, null, 2)}

Reply with ONLY a single valid JSON object (no markdown, no code fence) with this shape:
{
  "deals": [
    {
      "jobId": <job id>,
      "sellerId": "<provider_id from provider>",
      "sellerAgent": { "id", "userId", "name", "rating", "hourlyRate", "jobsCompleted", "licensed", ... },
      "matchScore": <0-100>,
      "matchReasons": ["reason1", "reason2"],
      "status": "proposed"
    }
  ]
}
Return at most 5 deals, ordered by best match first.`;
  const response = await llm.invoke([new HumanMessage(prompt)]);
  const text = typeof response.content === 'string' ? response.content : String(response.content ?? '');
  const jsonMatch = text.match(/\{[\s\S]*"deals"[\s\S]*\}/);
  if (jsonMatch) {
    try {
      const parsed = JSON.parse(jsonMatch[0]);
      if (parsed.deals && Array.isArray(parsed.deals)) return parsed.deals;
    } catch {}
  }
  try {
    const parsed = JSON.parse(text);
    if (parsed.deals && Array.isArray(parsed.deals)) return parsed.deals;
  } catch {}
  return [];
}

/**
 * Run the Buyer Match Agent.
 * Fetches providers via provider-service-by-category (job.service_category_id + user access_token),
 * then sends job + providers to the LLM for dynamic matching (no hardcoded rules).
 * @param {number|string} userId
 * @param {string} accessToken
 * @param {Object} job - Job with id, title, description, budget, priorities, service_category_id (required for API)
 * @returns {Promise<{ deals: Array }>}
 */
export async function runBuyerMatchAgent(userId, accessToken, job) {
  const service_category_id = Number(job.service_category_id) || 0;
  if (!service_category_id) {
    return { deals: [], message: 'Job must have service_category_id to match providers.' };
  }

  const jobSummary = `Job: ${job.title}. Budget: $${job.budget?.min ?? 0}-${job.budget?.max ?? 0}. Priorities: ${JSON.stringify(job.priorities || [])}`;
  await mem0.search(userId, jobSummary, { limit: 5 });

  const jobForContext = {
    id: job.id,
    title: job.title,
    description: job.description,
    budget: job.budget || { min: 0, max: 999999 },
    priorities: job.priorities || [],
    startDate: job.startDate,
    endDate: job.endDate,
    service_category_id,
  };

  const getProvidersTool = createGetProvidersByCategoryTool(accessToken);
  const systemPrompt = `You are a buyer matching assistant. Your task is to match a job to service providers.

1. Call the getProvidersByCategory tool ONCE with the job's service_category_id to fetch the provider list.
2. Using ONLY the job data and the providers returned by the tool, dynamically match and rank the best providers. Use your own judgment; there are no hardcoded rules.
3. Reply with a single JSON object containing a "deals" array. Each deal must include: jobId, sellerId (use provider_id from the provider), sellerAgent (summary object with id, userId, name, rating, etc.), matchScore (0-100), matchReasons (array of short strings), status ("proposed"). Return at most 5 deals, best match first. Do not use markdown or code fences—output only the JSON object.`;

  const llm = new ChatOpenAI({
    model: 'gpt-4o-mini',
    temperature: 0,
    openAIApiKey: OPENAI_API_KEY,
  }).bindTools([getProvidersTool]);

  const agent = createReactAgent({
    llm,
    tools: [getProvidersTool],
    prompt: systemPrompt,
    recursionLimit: 10,
  });

  const userMessage = `Match this job to providers. Job: ${JSON.stringify(jobForContext)}. Use service_category_id=${service_category_id} when calling the tool.`;
  const result = await agent.invoke({ messages: [new HumanMessage(userMessage)] });

  let deals = extractDealsFromResult(result, job);
  if (deals.length === 0) {
    const { providers } = await fetchProvidersByCategory(accessToken, service_category_id);
    if (providers.length > 0) {
      deals = await llmMatchProvidersToJob(jobForContext, providers);
      deals = normalizeDeals(deals, job);
    }
  }

  const matchesSummary = `Matched ${deals.length} providers for job ${job.title}`;
  await mem0.add(userId, [
    { role: 'user', content: jobSummary },
    { role: 'assistant', content: matchesSummary },
  ]);

  return { deals };
}
