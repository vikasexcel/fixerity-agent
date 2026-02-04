/**
 * Seller Agent: LangGraph React agent with Redis conversation memory (per-order).
 * runSellerAgent(providerId, message, accessToken, opts?) → { reply }
 * Conversation history is stored in Redis; memory is separated per order.
 */

import { ChatOpenAI } from '@langchain/openai';
import { createReactAgent } from '@langchain/langgraph/prebuilt';
import { HumanMessage, AIMessage } from '@langchain/core/messages';
import { createSellerTools } from '../tools/seller/index.js';
import * as redisChat from '../memory/redisChatStore.js';
import { OPENAI_API_KEY } from '../config/index.js';

const BASE_SYSTEM_PROMPT = `You are a helpful seller assistant for the Fixerity Fox Handyman marketplace. You help service providers (sellers) manage their business: view and update packages/pricing, manage orders (view details, update status, track work progress), manage availability (open time slots), and view customer feedback. Use the provided tools to call the Laravel API; you have the provider's auth context. Be concise and helpful. If you don't have enough information to call a tool (e.g. order_id for order details), ask the user.`;

/**
 * Build system prompt with optional order context.
 * @param {{ orderId?: string; orderTitle?: string }} [orderContext] - Order context for scoped conversations.
 * @returns {string}
 */
function buildSystemPrompt(orderContext = {}) {
  let prompt = BASE_SYSTEM_PROMPT;
  if (orderContext.orderId || orderContext.orderTitle) {
    const orderDesc = orderContext.orderTitle
      ? `Current conversation is about order: "${orderContext.orderTitle}" (ID: ${orderContext.orderId || 'unknown'}).`
      : `Current conversation is about order ID: ${orderContext.orderId}.`;
    prompt += `\n\n${orderDesc} Keep answers focused on this order when relevant.`;
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
 * Run the Seller Agent: load conversation from Redis, run agent, persist turn to Redis.
 * Falls back to opts.conversationHistory when Redis is unavailable.
 * @param {number|string} providerId - Provider id.
 * @param {string} message - User message.
 * @param {string} accessToken - Provider access_token for Laravel API.
 * @param {{ conversationHistory?: Array<{ role: 'user' | 'assistant'; content: string }>; orderId?: string; orderTitle?: string }} [opts] - Optional fallback conversation history and order context.
 * @returns {Promise<{ reply: string }>}
 */
export async function runSellerAgent(providerId, message, accessToken, opts = {}) {
  const { conversationHistory = [], orderId, orderTitle } = opts;
  const sessionId = redisChat.sellerSessionId(providerId, orderId);
  const systemPrompt = buildSystemPrompt({ orderId, orderTitle });

  let historyMessages = [];
  try {
    historyMessages = await redisChat.getHistory(sessionId);
  } catch (_) {
    historyMessages = [];
  }

  if (historyMessages.length === 0 && Array.isArray(conversationHistory) && conversationHistory.length > 0) {
    console.log('[SellerAgent] Redis unavailable, using request conversation_history');
    historyMessages = conversationHistory.map((m) =>
      m.role === 'user' ? new HumanMessage(m.content) : new AIMessage(m.content)
    );
  }

  console.log(`[SellerAgent] sessionId=${sessionId} loaded ${historyMessages.length} messages, invoking agent`);

  const tools = createSellerTools({ providerId, accessToken });
  const llm = new ChatOpenAI({
    model: 'gpt-4o-mini',
    temperature: 0,
    openAIApiKey: OPENAI_API_KEY,
  }).bindTools(tools);

  const agent = createReactAgent({
    llm,
    tools,
    prompt: systemPrompt,
    recursionLimit: 25,
  });

  const inputMessages = [...historyMessages, new HumanMessage(message)];
  const result = await agent.invoke({ messages: inputMessages });

  const reply = getFinalReply(result.messages ?? []);

  try {
    await redisChat.addTurn(sessionId, new HumanMessage(message), new AIMessage(reply));
    console.log(`[SellerAgent] sessionId=${sessionId} reply length=${reply.length} turn persisted`);
  } catch (_) {
    // Non-fatal: reply still returned
  }

  return { reply: reply || 'I couldn\'t generate a reply. Please try again.' };
}
