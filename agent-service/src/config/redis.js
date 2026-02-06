/**
 * Shared Redis client for negotiation session store and provider cache.
 */

import { createClient } from 'redis';
import { REDIS_URL } from './index.js';

const url = REDIS_URL?.trim() || 'redis://localhost:6379';
const redisClient = createClient({
  url,
  socket: { connectTimeout: 5000 },
});

redisClient.on('error', (err) => {
  console.error('[Redis] client error:', err.message);
});

export { redisClient };
