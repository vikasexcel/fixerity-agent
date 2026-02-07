/**
 * Redis-backed chat message store using the official redis package.
 * Stores conversation history per session (per-job for buyer, per-order for seller).
 * Messages are serialized as { type: 'human'|'ai', content } and mapped to HumanMessage/AIMessage.
 */

import { createClient } from 'redis';
import { HumanMessage, AIMessage } from '@langchain/core/messages';
import { REDIS_URL } from '../config/index.js';

const LOG_PREFIX = '[RedisChat]';
const KEY_PREFIX = 'chat:';
const SESSION_TTL_SECONDS = 86400 * 7; // 7 days

let clientInstance = null;

/**
 * Get or create the Redis client. Lazy connect on first use.
 * @returns {import('redis').RedisClientType | null}
 */
function getClient() {
  if (!REDIS_URL || REDIS_URL.trim() === '') {
    return null;
  }
  if (!clientInstance) {
    clientInstance = createClient({
      url: REDIS_URL,
      socket: { connectTimeout: 5000 },
    });
    clientInstance.on('error', (err) => {
      console.error(`${LOG_PREFIX} Redis client error:`, err.message);
    });
  }
  return clientInstance;
}

/**
 * Ensure the Redis client is connected. Call before operations.
 * @returns {Promise<boolean>} true if connected, false if unavailable
 */
async function ensureConnected() {
  const client = getClient();
  if (!client) return false;
  try {
    if (!client.isOpen) {
      await client.connect();
    }
    return true;
  } catch (err) {
    console.error(`${LOG_PREFIX} connection failed:`, err.message);
    return false;
  }
}

/**
 * Redis key for a session (e.g. chat:buyer:123:job:456).
 * @param {string} sessionId
 * @returns {string}
 */
function redisKey(sessionId) {
  return KEY_PREFIX + sessionId;
}

/**
 * Redis key for stored match result (deals) for a buyer job session.
 * @param {string} sessionId - e.g. buyer:123:job:456
 * @returns {string}
 */
function matchResultKey(sessionId) {
  return KEY_PREFIX + sessionId + ':deals';
}

/**
 * Serialize a message to JSON for storage.
 * @param {HumanMessage | AIMessage} message
 * @returns {string}
 */
function serializeMessage(message) {
  const content = typeof message.content === 'string' ? message.content : String(message.content ?? '');
  const type = message instanceof HumanMessage ? 'human' : 'ai';
  return JSON.stringify({ type, content });
}

/**
 * Deserialize a stored JSON string to HumanMessage or AIMessage.
 * @param {string} raw
 * @returns {HumanMessage | AIMessage | null}
 */
function deserializeMessage(raw) {
  try {
    const parsed = JSON.parse(raw);
    if (parsed.type === 'human' && typeof parsed.content === 'string') {
      return new HumanMessage(parsed.content);
    }
    if (parsed.type === 'ai' && typeof parsed.content === 'string') {
      return new AIMessage(parsed.content);
    }
  } catch (_) {}
  return null;
}

/**
 * Get conversation history for a session from Redis.
 * @param {string} sessionId - e.g. buyer:123:job:456 or seller:789:order:abc
 * @returns {Promise<import('@langchain/core/messages').BaseMessage[]>} HumanMessage/AIMessage array; empty on error or missing
 */
export async function getHistory(sessionId) {
  const connected = await ensureConnected();
  if (!connected) return [];
  const client = getClient();
  const key = redisKey(sessionId);
  try {
    const rawList = await client.lRange(key, 0, -1);
    return rawList.map(deserializeMessage).filter(Boolean);
  } catch (err) {
    console.error(`${LOG_PREFIX} load sessionId=${sessionId} failed:`, err.message);
    return [];
  }
}

/**
 * Append one conversation turn (human + AI) to the session in Redis.
 * @param {string} sessionId
 * @param {HumanMessage} humanMessage
 * @param {AIMessage} aiMessage
 * @returns {Promise<void>}
 */
export async function addTurn(sessionId, humanMessage, aiMessage) {
  const connected = await ensureConnected();
  if (!connected) return;
  const client = getClient();
  const key = redisKey(sessionId);
  try {
    await client.rPush(key, serializeMessage(humanMessage), serializeMessage(aiMessage));
    await client.expire(key, SESSION_TTL_SECONDS);
  } catch (err) {
    console.error(`${LOG_PREFIX} save sessionId=${sessionId} failed:`, err.message);
  }
}

/**
 * Store the matched providers (deals) for a buyer job session so follow-up questions can use them.
 * @param {string} sessionId - e.g. from buyerSessionId(userId, jobId)
 * @param {Array<object>} deals - Array of deal objects (provider info, matchScore, etc.)
 * @returns {Promise<void>}
 */
export async function setMatchResult(sessionId, deals) {
  const connected = await ensureConnected();
  if (!connected || !Array.isArray(deals)) return;
  const client = getClient();
  const key = matchResultKey(sessionId);
  try {
    await client.set(key, JSON.stringify(deals), { EX: SESSION_TTL_SECONDS });
  } catch (err) {
    console.error(`${LOG_PREFIX} setMatchResult sessionId=${sessionId} failed:`, err.message);
  }
}

/**
 * Get the stored matched providers (deals) for a buyer job session.
 * @param {string} sessionId - e.g. from buyerSessionId(userId, jobId)
 * @returns {Promise<Array<object>>} Deals array or [] if missing/error
 */
export async function getMatchResult(sessionId) {
  const connected = await ensureConnected();
  if (!connected) return [];
  const client = getClient();
  const key = matchResultKey(sessionId);
  try {
    const raw = await client.get(key);
    if (!raw) return [];
    const deals = JSON.parse(raw);
    return Array.isArray(deals) ? deals : [];
  } catch (err) {
    console.error(`${LOG_PREFIX} getMatchResult sessionId=${sessionId} failed:`, err.message);
    return [];
  }
}

/**
 * Build session ID for buyer: per-job when jobId provided, else per-user.
 * @param {number|string} userId
 * @param {string} [jobId]
 * @returns {string}
 */
export function buyerSessionId(userId, jobId) {
  if (jobId && String(jobId).trim()) {
    return `buyer:${userId}:job:${String(jobId).trim()}`;
  }
  return `buyer:${userId}`;
}

/**
 * Build session ID for seller: per-order when orderId provided, else per-provider.
 * @param {number|string} providerId
 * @param {string} [orderId]
 * @returns {string}
 */
export function sellerSessionId(providerId, orderId) {
  if (orderId && String(orderId).trim()) {
    return `seller:${providerId}:order:${String(orderId).trim()}`;
  }
  return `seller:${providerId}`;
}

/**
 * Build session ID for buyer direct chat with a specific provider (per job).
 * @param {number|string} userId
 * @param {string} jobId
 * @param {number|string} providerId
 * @returns {string}
 */
export function buyerDirectChatSessionId(userId, jobId, providerId) {
  return `buyer:direct:${userId}:job:${String(jobId || '').trim()}:provider:${providerId}`;
}
