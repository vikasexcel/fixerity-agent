/**
 * Buyer Agent: LangGraph React agent with Mem0 user-scoped memory.
 * runBuyerAgent(userId, message, accessToken, opts?) → { reply }
 * Supports conversation_history and job context for job-scoped chats.
 */

import { ChatOpenAI } from '@langchain/openai';
import { createReactAgent } from '@langchain/langgraph/prebuilt';
import { HumanMessage, AIMessage } from '@langchain/core/messages';
import { createBuyerTools } from '../tools/buyer/index.js';
import * as mem0 from '../memory/mem0Client.js';
import { OPENAI_API_KEY } from '../config/index.js';

const BASE_SYSTEM_PROMPT = `You are a helpful buyer assistant for the Fixerity Fox Handyman marketplace. You help customers find service providers (e.g. plumbers, electricians), view provider details and packages, check availability, get order previews, place orders, and view order history. Use the provided tools to call the Laravel API; you have the customer's auth context. Be concise and helpful. If you don't have enough information to call a tool (e.g. category or location for provider search), ask the user.`;

/**
 * Build system prompt with optional Mem0 context and job context.
 * @param {string} [memoryContext] - Formatted string of relevant memories.
 * @param {{ jobId?: string; jobTitle?: string }} [jobContext] - Job context for scoped conversations.
 * @returns {string}
 */
function buildSystemPrompt(memoryContext, jobContext = {}) {
  let prompt = BASE_SYSTEM_PROMPT;
  if (jobContext.jobId || jobContext.jobTitle) {
    const jobDesc = jobContext.jobTitle
      ? `Current conversation is about job: "${jobContext.jobTitle}" (ID: ${jobContext.jobId || 'unknown'}).`
      : `Current conversation is about job ID: ${jobContext.jobId}.`;
    prompt += `\n\n${jobDesc} Keep answers focused on this job and any matched providers when relevant.`;
  }
  if (memoryContext && memoryContext.trim() !== '') {
    prompt += `

Relevant context from past conversations:
${memoryContext}`;
  }
  return prompt;
}

/**
 * Extract final assistant reply text from agent state messages.
 * @param {import('@langchain/core/messages').BaseMessage[]} messages
 * @returns {string}
 */
function getFinalReply(messages) {
  for (let i = messages.length - 1; i >= 0; i--) {
    const msg = messages[i];
    if (msg instanceof AIMessage && !msg.tool_calls?.length) {
      const content = msg.content;
      if (typeof content === 'string') return content;
      if (Array.isArray(content)) {
        const text = content.map((c) => (typeof c === 'string' ? c : c?.text ?? '')).join('');
        if (text) return text;
      }
    }
  }
  return '';
}

/**
 * Run the Buyer Agent: retrieve Mem0 context, run LangGraph React agent, store turn in Mem0, return reply.
 * @param {number|string} userId - Customer user id.
 * @param {string} message - User message.
 * @param {string} accessToken - Customer access_token for Laravel API.
 * @param {{ conversationHistory?: Array<{ role: 'user' | 'assistant'; content: string }>; jobId?: string; jobTitle?: string }} [opts] - Optional conversation history and job context.
 * @returns {Promise<{ reply: string }>}
 */
export async function runBuyerAgent(userId, message, accessToken, opts = {}) {
  const { conversationHistory = [], jobId, jobTitle } = opts;
  const memoryContext = await mem0.search(userId, message, { limit: 10, jobId });
  const systemPrompt = buildSystemPrompt(memoryContext, { jobId, jobTitle });

  const tools = createBuyerTools({ userId, accessToken });
  const llm = new ChatOpenAI({
    model: 'gpt-4o-mini',
    temperature: 0,
    openAIApiKey: OPENAI_API_KEY,
  }).bindTools(tools);

  const agent = createReactAgent({ 
    llm, 
    tools, 
    prompt: systemPrompt,
    recursionLimit: 25, // Prevent infinite loops
  });

  const historyMessages = conversationHistory.map((m) =>
    m.role === 'user' ? new HumanMessage(m.content) : new AIMessage(m.content)
  );
  const inputMessages = [...historyMessages, new HumanMessage(message)];
  const result = await agent.invoke({ messages: inputMessages });

  const reply = getFinalReply(result.messages ?? []);
  await mem0.add(userId, [{ role: 'user', content: message }, { role: 'assistant', content: reply }], { jobId });

  return { reply: reply || 'I couldn\'t generate a reply. Please try again.' };
}
