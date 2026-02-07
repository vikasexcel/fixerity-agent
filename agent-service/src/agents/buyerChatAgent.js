/**
 * Buyer Chat Agent: answers follow-up questions about a job, matched providers,
 * provider details, payments, and timelines using stored deals (from negotiate-and-match).
 */

import { ChatOpenAI } from '@langchain/openai';
import { HumanMessage, AIMessage, SystemMessage } from '@langchain/core/messages';
import { OPENAI_API_KEY } from '../config/index.js';
import * as redisChat from '../memory/redisChatStore.js';
import { getDealsForJob } from '../lib/jobMatchQuoteStore.js';

/**
 * Answer a follow-up question about the job and matched providers (deals).
 * Uses job summary + deals (provider names, quotes, payment schedule, licensed, references, timeline).
 *
 * @param {Object} context - { jobTitle?: string, jobId?: string, deals: Array }
 * @param {import('@langchain/core/messages').BaseMessage[]} historyMessages
 * @param {string} userMessage
 * @returns {Promise<string>}
 */
async function answerFollowUp(context, historyMessages, userMessage) {
  const llm = new ChatOpenAI({
    model: 'gpt-4o-mini',
    temperature: 0,
    openAIApiKey: OPENAI_API_KEY,
  });

  const hasDeals = Array.isArray(context.deals) && context.deals.length > 0;
  const systemPrompt = `You are a helpful assistant for a buyer discussing a job and its provider quotes. Answer their follow-up questions using ONLY the provided job and matched providers (deals) data. Be concise and accurate.

You can answer about:
- The job (title, id)
- Matched providers: names, match scores, quotes (price, completion days)
- Provider details: licensing status, references, payment schedule (e.g. % upfront)
- Payments: quote price, payment terms per provider
- Timeline: proposed completion days per provider
- Who is the best match and why (based on match score and quote)

Do not make up data. If the data does not contain what they ask, say so briefly and point to what is available.${!hasDeals ? '\n\nNo provider quotes are stored for this job yet. Politely suggest they complete the negotiation/match step above first, then they can ask about providers, payments, and timeline.' : ''}`;

  const contextBlock = [
    'Job:',
    `  Title: ${context.jobTitle ?? 'Unknown'}`,
    `  ID: ${context.jobId ?? 'N/A'}`,
    '',
    'Matched providers (deals) — use this to answer questions about providers, payments, and timeline:',
    JSON.stringify(context.deals, null, 2),
    '',
    `User question: ${userMessage}`,
  ].join('\n');

  const historyAsMessages = (historyMessages || []).map((m) => {
    const content = typeof m.content === 'string' ? m.content : String(m.content ?? '');
    return m.constructor.name === 'HumanMessage' ? new HumanMessage(content) : new AIMessage(content);
  });

  const messages = [
    new SystemMessage(systemPrompt),
    ...historyAsMessages,
    new HumanMessage(contextBlock),
  ];

  const response = await llm.invoke(messages);
  return typeof response.content === 'string' ? response.content : String(response.content ?? '');
}

/**
 * Run the Buyer Chat Agent (follow-up Q&A for job chat).
 * Loads stored deals for this user+job from DB (saved after negotiate-and-match stream).
 * Uses Redis for conversation history when available.
 *
 * @param {number|string} userId
 * @param {string} message - User's follow-up question
 * @param {string} accessToken
 * @param {{ conversationHistory?: Array<{role,content}>, jobId?: string, jobTitle?: string }} [opts]
 * @returns {Promise<{ reply: string }>}
 */
export async function runBuyerAgent(userId, message, accessToken, opts = {}) {
  const jobId = opts.jobId && String(opts.jobId).trim() ? String(opts.jobId).trim() : null;
  const jobTitle = opts.jobTitle && String(opts.jobTitle).trim() ? String(opts.jobTitle).trim() : 'This job';

  const sessionId = redisChat.buyerSessionId(userId, jobId);
  let historyMessages = [];

  if (redisChat.getHistory) {
    try {
      historyMessages = await redisChat.getHistory(sessionId);
    } catch (_) {
      historyMessages = [];
    }
  }

  // If frontend sent conversation history, use it to build recent context (last N turns)
  const conversationHistory = opts.conversationHistory || [];
  if (conversationHistory.length > 0) {
    const maxTurns = 10;
    const recent = conversationHistory.slice(-maxTurns * 2);
    historyMessages = recent.map((m) =>
      m.role === 'user' ? new HumanMessage(m.content) : new AIMessage(m.content)
    );
  }

  let deals = [];
  if (jobId && userId != null) {
    try {
      deals = await getDealsForJob(Number(userId), jobId);
    } catch (_) {
      deals = [];
    }
  }

  const context = {
    jobId: jobId ?? undefined,
    jobTitle,
    deals,
  };

  const reply = await answerFollowUp(context, historyMessages, message.trim());

  if (sessionId && redisChat.addTurn) {
    try {
      await redisChat.addTurn(
        sessionId,
        new HumanMessage(message.trim()),
        new AIMessage(reply || 'No reply.')
      );
    } catch (_) {}
  }

  return { reply: reply || "I couldn't generate a response. Please try rephrasing your question." };
}
