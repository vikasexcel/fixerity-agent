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
 * @param {number|string} userId - Customer user id.
 * @returns {string}
 */
function buyerEntityId(userId) {
  return `buyer_${userId}`;
}

/**
 * Add a conversation turn to memory for a buyer.
 * @param {number|string} userId - Customer user id.
 * @param {Array<{ role: 'user' | 'assistant'; content: string }>} messages - One turn: user message + assistant reply.
 * @param {{ type?: 'order' | 'search' | 'feedback' }} [metadata] - Optional metadata for filtering.
 * @returns {Promise<void>}
 */
export async function add(userId, messages, metadata = {}) {
  const client = getClient();
  if (!client) return;
  const user_id = buyerEntityId(userId);
  try {
    await client.add(messages, {
      user_id,
      metadata: Object.keys(metadata).length ? metadata : undefined,
    });
  } catch (err) {
    console.error('[Mem0] add failed:', err.message);
  }
}

/**
 * Search memories for a buyer (user-scoped).
 * @param {number|string} userId - Customer user id.
 * @param {string} query - Natural-language query (e.g. current user message or summary).
 * @param {{ limit?: number }} [options] - Optional; limit defaults to 10.
 * @returns {Promise<string>} Formatted string of relevant memories for injection into system prompt, or empty string.
 */
export async function search(userId, query, options = {}) {
  const client = getClient();
  if (!client) return '';
  const limit = options.limit ?? 10;
  const user_id = buyerEntityId(userId);
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
