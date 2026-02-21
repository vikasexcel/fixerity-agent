import { ChatOpenAI } from '@langchain/openai';
import { HumanMessage, SystemMessage } from '@langchain/core/messages';
import { OPENAI_API_KEY, LARAVEL_API_BASE_URL } from '../../config/index.js';
import { sessionService, messageService, cacheService } from '../../services/index.js';
import { createBuyerAgentTools } from './buyerAgentTools.js';
import { createBuyerAgentGraph } from './buyerAgentGraph.js';

/* ================================================================================
   CONVERSATION GRAPH - Job Creation Through Conversational Agent
   No predefined fields. AI asks questions based on job type and marketplace knowledge.
   ================================================================================ */

/* -------------------- SERVICE CATEGORIES MANAGER (Using Cache Service) -------------------- */

class ServiceCategoryManager {
  async fetchFromAPI(userId, accessToken) {
    try {
      const response = await fetch(`${LARAVEL_API_BASE_URL}/customer/home`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          user_id: userId,
          access_token: accessToken,
          app_version: '1.0'
        })
      });

      const data = await response.json();
      if (data.status === 1 && data.services) {
        return data.services;
      }
      return null;
    } catch (error) {
      console.error('[ServiceCategory] API fetch error:', error.message);
      return null;
    }
  }

  /**
   * Fetch service categories for sellers/providers (on-demand/get-service-list).
   * Returns same shape as customer API: { service_category_id, service_category_name }.
   */
  async fetchProviderFromAPI(providerId, accessToken) {
    try {
      const response = await fetch(`${LARAVEL_API_BASE_URL}/on-demand/get-service-list`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          provider_id: providerId,
          access_token: accessToken,
        })
      });

      const data = await response.json();
      if (data.status === 1 && data.service_category_list) {
        return data.service_category_list.map((s) => ({
          service_category_id: s.service_cat_id ?? s.service_category_id,
          service_category_name: s.service_cat_name ?? s.service_category_name,
        }));
      }
      return null;
    } catch (error) {
      console.error('[ServiceCategory] Provider API fetch error:', error.message);
      return null;
    }
  }

  async getCategoriesOrFetch(userId, accessToken) {
    return await cacheService.getServiceCategories(
      async () => await this.fetchFromAPI(userId, accessToken),
      accessToken
    );
  }

  /**
   * Get provider/seller service categories (for seller profile flow).
   * Uses on-demand/get-service-list and caches under service_categories:provider.
   */
  async getProviderCategoriesOrFetch(providerId, accessToken) {
    return await cacheService.getOrFetch(
      'service_categories:provider',
      async () => await this.fetchProviderFromAPI(providerId, accessToken),
      86400
    );
  }

  async findCategory(userInput, categories, llm) {
    if (!categories || categories.length === 0) {
      return null;
    }

    const categoryList = categories.map(c => 
      `- ID: ${c.service_category_id}, Name: "${c.service_category_name}"`
    ).join('\n');

    const prompt = `
You are a service category matcher. Given a user's request, find the BEST matching service category.

Available categories:
${categoryList}

User's request: "${userInput}"

Instructions:
1. Find the category that BEST matches what the user is looking for
2. Consider synonyms and related terms (e.g., "house cleaning" = "Home Cleaning", "plumber" = "Plumbers")
3. If no category matches well, return null

Reply ONLY with JSON:
{
  "matched": true/false,
  "category_id": <number or null>,
  "category_name": "<string or null>",
  "confidence": "<high/medium/low>",
  "reason": "<brief explanation>"
}
`;

    try {
      const res = await llm.invoke([
        new SystemMessage('Only output valid JSON. Be accurate in matching.'),
        new HumanMessage(prompt),
      ]);

      let content = res.content.trim();
      content = content.replace(/```json\n?/g, '').replace(/```\n?/g, '');
      return JSON.parse(content);
    } catch (error) {
      console.error('[ServiceCategory] LLM matching error:', error.message);
      return null;
    }
  }
}

export const serviceCategoryManager = new ServiceCategoryManager();

/* -------------------- HELPERS -------------------- */

function getLastAIMessageText(messages) {
  for (let i = messages.length - 1; i >= 0; i--) {
    const m = messages[i];
    const type = m._getType?.() ?? m.constructor?.name ?? '';
    if (type === 'ai' || type === 'AIMessage') {
      const content = m.content;
      if (typeof content === 'string') return content;
      if (Array.isArray(content) && content.length > 0) {
        const first = content[0];
        if (first?.type === 'text') return first.text ?? '';
      }
      return '';
    }
  }
  return '';
}

function extractJobFromToolMessages(messages) {
  for (let i = messages.length - 1; i >= 0; i--) {
    const m = messages[i];
    const type = m._getType?.() ?? m.constructor?.name ?? '';
    if (type === 'tool' || type === 'ToolMessage') {
      const content = m.content;
      const str = Array.isArray(content) ? content.map(c => c?.content ?? '').join('') : String(content ?? '');
      try {
        const parsed = JSON.parse(str);
        if (parsed?.success === true && parsed?.job) return parsed.job;
      } catch {
        // not JSON or parse failed
      }
    }
  }
  return null;
}

/**
 * Build the chat response when a job is created. Shows the full LLM-generated
 * job post (title + full description with sections) instead of a summary.
 */
function formatJobCreatedResponse(job) {
  const parts = [
    "Your job post has been successfully created! Here are the full details:\n",
    `**Title:** ${job.title || 'Job Listing'}\n`,
  ];
  if (job.description) {
    parts.push(`**Description:**\n\n${job.description}\n`);
  }
  const budget = job.budget;
  if (budget && (budget.min != null || budget.max != null)) {
    const min = Number(budget.min ?? 0);
    const max = Number(budget.max ?? 0);
    parts.push(`\n**Budget:** $${min.toLocaleString()} – $${max.toLocaleString()}`);
  }
  if (job.startDate) {
    parts.push(`\n**Start Date:** ${job.startDate}`);
  }
  if (job.endDate) {
    parts.push(`\n**End Date:** ${job.endDate}`);
  }
  const loc = job.location;
  const address = typeof loc === 'object' && loc?.address ? loc.address : (loc ?? null);
  if (address) {
    parts.push(`\n**Location:** ${address}`);
  }
  parts.push("\n\nIf you need any changes or have questions, just let me know!");
  return parts.join('');
}

function jobToCollected(job) {
  if (!job) return {};
  const loc = job.location;
  const location = typeof loc === 'object' && loc?.address ? loc.address : (loc ?? null);
  return {
    service_category_id: job.service_category_id,
    service_category_name: job.service_category_name,
    title: job.title,
    description: job.description,
    budget: job.budget ? { min: job.budget.min, max: job.budget.max } : { min: null, max: null },
    startDate: job.startDate ?? null,
    endDate: job.endDate ?? null,
    priorities: job.priorities ?? [],
    location,
  };
}

/* -------------------- RUNNER FUNCTION -------------------- */

export async function runConversation(input) {
  const { sessionId, buyerId, accessToken, message } = input;

  const sessionData = await sessionService.getSessionWithContext(sessionId, 50);

  const tools = createBuyerAgentTools({
    buyerId,
    accessToken,
    serviceCategoryManager,
  });

  const graph = createBuyerAgentGraph(tools);

  const config = {
    configurable: {
      thread_id: sessionId,
    },
  };

  const inputMessages = [new HumanMessage(message)];
  const result = await graph.invoke({ messages: inputMessages }, config);

  const messages = result?.messages ?? [];
  const job = extractJobFromToolMessages(messages);
  const responseText = job
    ? formatJobCreatedResponse(job)
    : getLastAIMessageText(messages) || "I'm here to help!";

  const phase = job ? 'complete' : 'conversation';

  await messageService.addUserMessage(sessionId, message);
  await messageService.addAssistantMessage(sessionId, responseText, {
    action: job ? 'job_created' : 'conversing',
  });

  await sessionService.updateState(sessionId, {
    job: job ?? sessionData.state?.job ?? null,
  });

  if (phase !== sessionData.phase) {
    await sessionService.updatePhase(sessionId, phase);
  }

  const collected = job ? jobToCollected(job) : {};

  return {
    sessionId,
    phase,
    response: responseText,
    action: job ? 'job_created' : 'conversing',
    collected,
    requiredMissing: [],
    optionalMissing: [],
    jobReadiness: job ? 'complete' : 'incomplete',
    job,
  };
}
