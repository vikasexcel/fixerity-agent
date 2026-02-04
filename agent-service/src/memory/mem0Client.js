/**
 * Mem0 Platform client for user-scoped agent memory.
 * Policy: one entity id per buyer (buyer_${userId}) so memory is strictly user-scoped.
 * Store last turns / key decisions; no summarization in v1.
 */

import { MemoryClient } from 'mem0ai';
import { MEM0_API_KEY } from '../config/index.js';

let clientInstance = null;

/**
 * Get or create the Mem0 MemoryClient (Platform API).
 * @returns {MemoryClient | null} Client instance, or null if MEM0_API_KEY is missing.
 */
function getClient() {
  if (!MEM0_API_KEY || MEM0_API_KEY.trim() === '') {
    return null;
  }
  if (!clientInstance) {
    clientInstance = new MemoryClient({ apiKey: MEM0_API_KEY });
  }
  return clientInstance;
}

/**
 * Entity id for buyer-scoped memory (avoids collision with seller/provider ids).
 * When jobId is provided, memory is scoped to that job's conversation.
 * @param {number|string} userId - Customer user id.
 * @param {string} [jobId] - Optional job id for job-scoped memory.
 * @returns {string}
 */
function buyerEntityId(userId, jobId) {
  if (jobId) {
    return `buyer_${userId}_job_${jobId}`;
  }
  return `buyer_${userId}`;
}

/**
 * Add a conversation turn to memory for a buyer.
 * @param {number|string} userId - Customer user id.
 * @param {Array<{ role: 'user' | 'assistant'; content: string }>} messages - One turn: user message + assistant reply.
 * @param {{ type?: 'order' | 'search' | 'feedback'; jobId?: string }} [metadata] - Optional metadata; jobId for job-scoped memory.
 * @returns {Promise<void>}
 */
export async function add(userId, messages, metadata = {}) {
  const client = getClient();
  if (!client) return;
  const { jobId, ...restMeta } = metadata;
  const user_id = buyerEntityId(userId, jobId);
  try {
    await client.add(messages, {
      user_id,
      metadata: Object.keys(restMeta).length ? restMeta : undefined,
    });
  } catch (err) {
    console.error('[Mem0] add failed:', err.message);
  }
}

/**
 * Search memories for a buyer (user-scoped or job-scoped).
 * @param {number|string} userId - Customer user id.
 * @param {string} query - Natural-language query (e.g. current user message or summary).
 * @param {{ limit?: number; jobId?: string }} [options] - Optional; limit defaults to 10; jobId for job-scoped search.
 * @returns {Promise<string>} Formatted string of relevant memories for injection into system prompt, or empty string.
 */
export async function search(userId, query, options = {}) {
  const client = getClient();
  if (!client) return '';
  const limit = options.limit ?? 10;
  const user_id = buyerEntityId(userId, options.jobId);
  try {
    const results = await client.search(query, {
      filters: { user_id },
      top_k: limit,
    });
    const list = Array.isArray(results) ? results : (results?.results ?? []);
    const memories = list
      .map((m) => (typeof m === 'string' ? m : m?.memory ?? m?.data?.memory))
      .filter(Boolean);
    if (memories.length === 0) return '';
    return memories.join('\n');
  } catch (err) {
    console.error('[Mem0] search failed:', err.message);
    return '';
  }
}

/**
 * Entity id for provider-scoped memory (avoids collision with buyer ids).
 * When orderId is provided, memory is scoped to that order's conversation.
 * @param {number|string} providerId - Provider id.
 * @param {string} [orderId] - Optional order id for order-scoped memory.
 * @returns {string}
 */
function providerEntityId(providerId, orderId) {
  if (orderId) {
    return `provider_${providerId}_order_${orderId}`;
  }
  return `provider_${providerId}`;
}

/**
 * Add a conversation turn to memory for a provider (seller).
 * @param {number|string} providerId - Provider id.
 * @param {Array<{ role: 'user' | 'assistant'; content: string }>} messages - One turn: user message + assistant reply.
 * @param {{ orderId?: string }} [metadata] - Optional; orderId for order-scoped memory.
 * @returns {Promise<void>}
 */
export async function addForProvider(providerId, messages, metadata = {}) {
  const client = getClient();
  if (!client) return;
  const { orderId, ...restMeta } = metadata;
  const user_id = providerEntityId(providerId, orderId);
  try {
    await client.add(messages, {
      user_id,
      metadata: Object.keys(restMeta).length ? restMeta : undefined,
    });
  } catch (err) {
    console.error('[Mem0] addForProvider failed:', err.message);
  }
}

/**
 * Search memories for a provider (seller) (provider-scoped or order-scoped).
 * @param {number|string} providerId - Provider id.
 * @param {string} query - Natural-language query (e.g. current user message or summary).
 * @param {{ limit?: number; orderId?: string }} [options] - Optional; limit defaults to 10; orderId for order-scoped search.
 * @returns {Promise<string>} Formatted string of relevant memories for injection into system prompt, or empty string.
 */
export async function searchForProvider(providerId, query, options = {}) {
  const client = getClient();
  if (!client) return '';
  const limit = options.limit ?? 10;
  const user_id = providerEntityId(providerId, options.orderId);
  try {
    const results = await client.search(query, {
      filters: { user_id },
      top_k: limit,
    });
    const list = Array.isArray(results) ? results : (results?.results ?? []);
    const memories = list
      .map((m) => (typeof m === 'string' ? m : m?.memory ?? m?.data?.memory))
      .filter(Boolean);
    if (memories.length === 0) return '';
    return memories.join('\n');
  } catch (err) {
    console.error('[Mem0] searchForProvider failed:', err.message);
    return '';
  }
}
