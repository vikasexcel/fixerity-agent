/**
 * Buyer Direct Chat Agent: simulates a provider's responses when a buyer
 * initiates direct chat with a matched provider. Uses the provider's quote,
 * profile, and job context to generate in-character responses.
 */

import { ChatOpenAI } from '@langchain/openai';
import { HumanMessage, AIMessage, SystemMessage } from '@langchain/core/messages';
import { OPENAI_API_KEY } from '../config/index.js';
import * as redisChat from '../memory/redisChatStore.js';

/**
 * Generate a provider-simulated response for direct buyer-provider chat.
 *
 * @param {Object} context - { jobTitle, jobId, providerName, providerId, price, days, paymentSchedule, rating, jobsCompleted, conversationHistory }
 * @param {import('@langchain/core/messages').BaseMessage[]} historyMessages
 * @param {string} userMessage
 * @returns {Promise<string>}
 */
async function generateProviderResponse(context, historyMessages, userMessage) {
  const llm = new ChatOpenAI({
    model: 'gpt-4o-mini',
    temperature: 0.7,
    openAIApiKey: OPENAI_API_KEY,
  });

  const {
    jobTitle,
    jobId,
    providerName,
    price,
    days,
    paymentSchedule,
    rating,
    jobsCompleted,
  } = context;

  const quoteLine =
    price != null && days != null
      ? `$${price} for ${days} day${days !== 1 ? 's' : ''}`
      : 'a quoted price and timeline';
  const extra =
    [paymentSchedule && `Payment: ${paymentSchedule}`, rating != null && `${rating}/5 rating`, jobsCompleted != null && `${jobsCompleted} jobs completed`]
      .filter(Boolean)
      .join('. ') || '';

  const systemPrompt = `You are ${providerName || 'a handyman'}, a professional service provider. A buyer is messaging you directly about a job they posted.

Context:
- Job: ${jobTitle ?? 'Unknown'} (ID: ${jobId ?? 'N/A'})
- Your agreed quote: ${quoteLine}${extra ? `. ${extra}` : ''}

Respond naturally and conversationally as the provider. Stay in character. Be helpful, professional, and friendly. Keep responses concise (2-4 sentences typically). You can:
- Confirm the quote and discuss details
- Answer questions about availability, timeline, or payment
- Discuss next steps (scheduling, contract, etc.)
- Be warm but professional

Do not break character. Do not mention you are an AI.`;

  const historyAsMessages = (historyMessages || []).map((m) => {
    const content = typeof m.content === 'string' ? m.content : String(m.content ?? '');
    return m.constructor.name === 'HumanMessage' ? new HumanMessage(content) : new AIMessage(content);
  });

  const messages = [
    new SystemMessage(systemPrompt),
    ...historyAsMessages,
    new HumanMessage(userMessage),
  ];

  const response = await llm.invoke(messages);
  return typeof response.content === 'string' ? response.content : String(response.content ?? '');
}

/**
 * Run the Buyer Direct Chat Agent (provider-simulated responses).
 *
 * @param {number|string} userId
 * @param {string} accessToken - unused for now but kept for future auth
 * @param {string} message - Buyer's message
 * @param {Object} opts
 * @param {string} opts.jobId
 * @param {string} [opts.jobTitle]
 * @param {number|string} opts.providerId
 * @param {string} [opts.providerName]
 * @param {number} [opts.price]
 * @param {number} [opts.days]
 * @param {string} [opts.paymentSchedule]
 * @param {number} [opts.rating]
 * @param {number} [opts.jobsCompleted]
 * @param {Array<{role: 'user'|'assistant', content: string}>} [opts.conversationHistory]
 * @returns {Promise<{ reply: string }>}
 */
export async function runBuyerDirectChatAgent(userId, accessToken, message, opts = {}) {
  const jobId = opts.jobId && String(opts.jobId).trim() ? String(opts.jobId).trim() : '';
  const providerId = opts.providerId != null ? String(opts.providerId) : '';
  const sessionId = redisChat.buyerDirectChatSessionId(userId, jobId, providerId);

  let historyMessages = [];

  if (redisChat.getHistory) {
    try {
      historyMessages = await redisChat.getHistory(sessionId);
    } catch (_) {
      historyMessages = [];
    }
  }

  const conversationHistory = opts.conversationHistory || [];
  if (conversationHistory.length > 0) {
    const maxTurns = 10;
    const recent = conversationHistory.slice(-maxTurns * 2);
    historyMessages = recent.map((m) =>
      m.role === 'user' ? new HumanMessage(m.content) : new AIMessage(m.content)
    );
  }

  const context = {
    jobTitle: opts.jobTitle,
    jobId,
    providerName: opts.providerName ?? 'Provider',
    providerId,
    price: opts.price,
    days: opts.days,
    paymentSchedule: opts.paymentSchedule,
    rating: opts.rating,
    jobsCompleted: opts.jobsCompleted,
  };

  const reply = await generateProviderResponse(context, historyMessages, message.trim());

  if (sessionId && redisChat.addTurn) {
    try {
      await redisChat.addTurn(
        sessionId,
        new HumanMessage(message.trim()),
        new AIMessage(reply || 'No reply.')
      );
    } catch (_) {}
  }

  return { reply: reply || "I couldn't generate a response. Please try again." };
}
