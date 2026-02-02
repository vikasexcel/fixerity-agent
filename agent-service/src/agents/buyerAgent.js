/**
 * Buyer Agent: LangGraph React agent with Mem0 user-scoped memory.
 * runBuyerAgent(userId, message, accessToken) → { reply }
 */

import { ChatOpenAI } from '@langchain/openai';
import { createReactAgent } from '@langchain/langgraph/prebuilt';
import { HumanMessage, AIMessage } from '@langchain/core/messages';
import { createBuyerTools } from '../tools/buyer/index.js';
import * as mem0 from '../memory/mem0Client.js';
import { OPENAI_API_KEY } from '../config/index.js';

const BASE_SYSTEM_PROMPT = `You are a helpful buyer assistant for the Fixerity Fox Handyman marketplace. You help customers find service providers (e.g. plumbers, electricians), view provider details and packages, check availability, get order previews, place orders, and view order history. Use the provided tools to call the Laravel API; you have the customer's auth context. Be concise and helpful. If you don't have enough information to call a tool (e.g. category or location for provider search), ask the user.`;

/**
 * Build system prompt with optional Mem0 context.
 * @param {string} [memoryContext] - Formatted string of relevant memories.
 * @returns {string}
 */
function buildSystemPrompt(memoryContext) {
  if (!memoryContext || memoryContext.trim() === '') {
    return BASE_SYSTEM_PROMPT;
  }
  return `${BASE_SYSTEM_PROMPT}

Relevant context from past conversations:
${memoryContext}`;
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
 * @returns {Promise<{ reply: string }>}
 */
export async function runBuyerAgent(userId, message, accessToken) {
  const memoryContext = await mem0.search(userId, message, { limit: 10 });
  const systemPrompt = buildSystemPrompt(memoryContext);

  const tools = createBuyerTools({ userId, accessToken });
  const llm = new ChatOpenAI({
    model: 'gpt-4o-mini',
    temperature: 0,
    openAIApiKey: OPENAI_API_KEY,
  }).bindTools(tools);

  const agent = createReactAgent({ llm, tools, prompt: systemPrompt });
  const inputMessages = [new HumanMessage(message)];
  const result = await agent.invoke({ messages: inputMessages });

  const reply = getFinalReply(result.messages ?? []);
  await mem0.add(userId, [{ role: 'user', content: message }, { role: 'assistant', content: reply }]);

  return { reply: reply || 'I couldn\'t generate a reply. Please try again.' };
}
