/**
 * Buyer Match Agent: LangGraph React agent for job-to-provider matching.
 * Uses Mem0 for context, searchProviders + getProviderDetails tools, and
 * server-side evaluation to return ranked deals.
 */

import { ChatOpenAI } from '@langchain/openai';
import { createReactAgent } from '@langchain/langgraph/prebuilt';
import { HumanMessage } from '@langchain/core/messages';
import { tool } from '@langchain/core/tools';
import { z } from 'zod';
import { post } from '../lib/laravelClient.js';
import * as mem0 from '../memory/mem0Client.js';
import { OPENAI_API_KEY } from '../config/index.js';

/**
 * Evaluate and rank providers against job priorities (ported from frontend agent-engine logic).
 * @param {Array} providers - Provider data from Laravel
 * @param {Object} job - Job with priorities, budget
 * @returns {Array} Top 5 deals with matchScore and matchReasons
 */
function evaluateAndRank(providers, job) {
  const jobPriorities = job.priorities || [];
  const maxBudget = job.budget?.max ?? 999999;

  const scored = providers.map((p) => {
    let baseScore = 100;
    const reasons = [];

    const agent = {
      id: `agent_${p.provider_id}`,
      userId: String(p.provider_id),
      name: p.provider_name || 'Provider',
      type: 'seller',
      rating: parseFloat(p.average_rating) || 0,
      jobsCompleted: parseInt(p.total_completed_order, 10) || 0,
      licensed: true, // Laravel may not expose; assume true
      references: (parseInt(p.num_of_rating, 10) || 0) > 0,
      bio: '',
      hourlyRate: p.package_list?.[0]?.package_price
        ? parseFloat(p.package_list[0].package_price)
        : 0,
      createdAt: new Date().toISOString().split('T')[0],
    };

    for (const priority of jobPriorities) {
      let matched = false;
      let reason = '';

      switch (priority.type) {
        case 'price': {
          const agentMonthlyRate = (agent.hourlyRate || 0) * 160;
          matched = agentMonthlyRate <= maxBudget;
          reason = matched
            ? `Price within budget ($${maxBudget})`
            : `Rate exceeds budget ($${agentMonthlyRate.toFixed(0)} estimated)`;
          break;
        }
        case 'startDate':
        case 'endDate':
          matched = true;
          reason = `Available for dates`;
          break;
        case 'rating': {
          const required = Number(priority.value) || 4;
          matched = agent.rating >= required;
          reason = matched
            ? `Rating ${agent.rating}★ meets ${required}★`
            : `Rating ${agent.rating}★ below ${required}★`;
          break;
        }
        case 'jobsCompleted': {
          const required = Number(priority.value) || 10;
          matched = agent.jobsCompleted >= required;
          reason = matched
            ? `${agent.jobsCompleted} jobs exceeds ${required}`
            : `${agent.jobsCompleted} jobs below ${required}`;
          break;
        }
        case 'licensed':
          matched = agent.licensed;
          reason = agent.licensed ? 'Licensed' : 'Not licensed';
          break;
        case 'references':
          matched = agent.references;
          reason = agent.references ? 'References available' : 'No references';
          break;
        default:
          matched = false;
          reason = 'Unknown requirement';
      }

      reasons.push(matched ? `✓ ${reason}` : `✗ ${reason}`);
      if (priority.level === 'bonus' && matched) baseScore += 5;
      if (priority.level === 'must_have' && !matched) baseScore -= 25;
      if (priority.level === 'nice_to_have' && !matched) baseScore -= 10;
    }

    const finalScore = Math.max(0, Math.min(100, baseScore));
    return { agent, score: finalScore, reasons };
  });

  return scored
    .sort((a, b) => b.score - a.score)
    .slice(0, 5)
    .map((s, idx) => ({
      id: `deal_${job.id}_${idx}`,
      jobId: job.id,
      sellerId: s.agent.userId,
      sellerAgent: s.agent,
      matchScore: Math.round(s.score),
      matchReasons: s.reasons,
      status: 'proposed',
      createdAt: new Date().toISOString().split('T')[0],
      job: {
        id: job.id,
        title: job.title ?? 'Job',
        description: job.description ?? '',
        budget: job.budget ?? { min: 0, max: 0 },
        startDate: job.startDate ?? '',
        endDate: job.endDate ?? '',
        priorities: job.priorities ?? [],
      },
    }));
}

/**
 * Create the match tool that searches providers and returns ranked deals.
 */
function createMatchJobTool(userId, accessToken) {
  return tool(
    async (input) => {
      const { service_category_id, sub_category_id, lat, long, job_json } = input;
      const job = typeof job_json === 'string' ? JSON.parse(job_json) : job_json;

      const path = 'customer/on-demand/provider-list';
      const payload = {
        service_category_id: Number(service_category_id) || 1,
        sub_category_id: Number(sub_category_id) || 1,
        lat: Number(lat) || 0,
        long: Number(long) || 0,
      };

      try {
        const data = await post(path, payload, { userId, accessToken });
        if (data.status !== 1 || !data.provider_list || data.provider_list.length === 0) {
          return JSON.stringify({
            deals: [],
            message: data?.message || 'No providers found for this category and location.',
          });
        }
        const deals = evaluateAndRank(data.provider_list, job);
        return JSON.stringify({ deals });
      } catch (err) {
        const isNoData = err.message === 'Data Not Found' || /not found|no provider/i.test(err.message);
        return JSON.stringify({
          deals: [],
          error: err.message,
          ...(isNoData && { message: 'No providers found for this category and location.' }),
        });
      }
    },
    {
      name: 'matchJobToProviders',
      description:
        'Search for providers by service category and location, then evaluate and rank them against the job priorities. Returns top 5 matches as deals. Use this when you have a job with priorities and search params (service_category_id, sub_category_id, lat, long).',
      schema: z.object({
        service_category_id: z.number().describe('Service category ID'),
        sub_category_id: z.number().describe('Sub-category ID'),
        lat: z.number().describe('Latitude'),
        long: z.number().describe('Longitude'),
        job_json: z.string().describe('JSON string of the job object with id, budget, priorities'),
      }),
    }
  );
}

/**
 * Extract deals from agent execution (last tool result).
 * ToolMessage or any message with JSON content containing deals.
 */
function extractDealsFromResult(result) {
  const messages = result?.messages ?? [];
  for (let i = messages.length - 1; i >= 0; i--) {
    const msg = messages[i];
    const content = msg?.content;
    if (typeof content === 'string' && content.trim().startsWith('{')) {
      try {
        const parsed = JSON.parse(content);
        if (parsed.deals && Array.isArray(parsed.deals)) return parsed.deals;
      } catch {}
    }
    if (Array.isArray(content)) {
      for (const block of content) {
        const str = typeof block === 'string' ? block : block?.content ?? block?.text;
        if (str && typeof str === 'string' && str.trim().startsWith('{')) {
          try {
            const parsed = JSON.parse(str);
            if (parsed.deals && Array.isArray(parsed.deals)) return parsed.deals;
          } catch {}
        }
      }
    }
  }
  return [];
}

/**
 * Run the Buyer Match Agent.
 * @param {number|string} userId
 * @param {string} accessToken
 * @param {Object} job - Job with id, title, description, budget, priorities, service_category_id, sub_category_id, lat, long
 * @returns {Promise<{ deals: Array }>}
 */
export async function runBuyerMatchAgent(userId, accessToken, job) {
  const jobSummary = `Job: ${job.title}. Budget: $${job.budget?.min ?? 0}-${job.budget?.max ?? 0}. Priorities: ${JSON.stringify(job.priorities || [])}`;
  const memoryContext = await mem0.search(userId, jobSummary, { limit: 5 });
  const systemPrompt = `You are a buyer matching assistant. Your task is to match a job to service providers.
Use the matchJobToProviders tool ONCE with the job (as JSON string), service_category_id, sub_category_id, lat, and long.
The job will have: id, title, budget (min, max), priorities (array of {type, level, value, description}).
After calling the tool and receiving the results, STOP. Do not call the tool again. The tool result contains the final matches.`;

  const matchTool = createMatchJobTool(userId, accessToken);
  const llm = new ChatOpenAI({
    model: 'gpt-4o-mini',
    temperature: 0,
    openAIApiKey: OPENAI_API_KEY,
  }).bindTools([matchTool]);

  const agent = createReactAgent({ 
    llm, 
    tools: [matchTool], 
    prompt: systemPrompt,
    recursionLimit: 10, // Limit to prevent infinite loops
  });

  const jobForTool = {
    id: job.id,
    title: job.title,
    description: job.description,
    budget: job.budget || { min: 0, max: 999999 },
    priorities: job.priorities || [],
    startDate: job.startDate,
    endDate: job.endDate,
  };

  const userMessage = `Match this job to providers. Job: ${JSON.stringify(jobForTool)}. Use service_category_id=${job.service_category_id ?? 1}, sub_category_id=${job.sub_category_id ?? 1}, lat=${job.lat ?? 0}, long=${job.long ?? 0}.`;
  const result = await agent.invoke({ messages: [new HumanMessage(userMessage)] });

  let deals = extractDealsFromResult(result);
  if (deals.length === 0) {
    // Fallback: call tool directly
    const toolResult = await matchTool.invoke({
      service_category_id: job.service_category_id ?? 1,
      sub_category_id: job.sub_category_id ?? 1,
      lat: job.lat ?? 0,
      long: job.long ?? 0,
      job_json: JSON.stringify(jobForTool),
    });
    const parsed = JSON.parse(toolResult);
    deals = parsed.deals || [];
  }

  const matchesSummary = `Matched ${deals.length} providers for job ${job.title}`;
  await mem0.add(userId, [
    { role: 'user', content: jobSummary },
    { role: 'assistant', content: matchesSummary },
  ]);

  return { deals };
}
