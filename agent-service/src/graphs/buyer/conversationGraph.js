import { HumanMessage, AIMessage, SystemMessage } from '@langchain/core/messages';
import { sessionService, messageService } from '../../services/index.js';
import { createBuyerAgentTools } from './buyerAgentTools.js';
import { createBuyerAgentGraph } from './buyerAgentGraph.js';

function toLangChainMessage(m) {
  const content = m.content || '';
  if (m.role === 'user') return new HumanMessage(content);
  if (m.role === 'assistant') return new AIMessage(content);
  if (m.role === 'system') return new SystemMessage(content);
  return new HumanMessage(content);
}

/* ================================================================================
   CONVERSATION GRAPH - Job Creation Through Conversational Agent
   No predefined fields. AI asks questions based on job type and marketplace knowledge.
   ================================================================================ */

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
    parts.push(`${job.description}\n`);
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

  const history = await messageService.getConversationHistory(sessionId, { limit: 50, includeSystem: false });
  const historyMessages = history.map(toLangChainMessage);
  const inputMessages = [...historyMessages, new HumanMessage(message)];

  const tools = createBuyerAgentTools({
    buyerId,
    accessToken,
  });

  const graph = createBuyerAgentGraph(tools);

  const result = await graph.invoke({ messages: inputMessages });

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
