/**
 * Buyer tool: Get customer wallet balance.
 *
 * Laravel: POST /api/customer/get-wallet-balance
 * Optional (from context): user_id, access_token. No extra body params.
 *
 * Use when the user asks about their wallet or balance.
 */

import { tool } from '@langchain/core/tools';
import { z } from 'zod';
import { post } from '../../lib/laravelClient.js';

const path = 'customer/get-wallet-balance';

const schema = z.object({});

/**
 * @param {typeof post} laravelClient
 * @param {number} [userId]
 * @param {string} [accessToken]
 * @returns {import('@langchain/core/tools').StructuredToolInterface}
 */
export function createGetWalletBalanceTool(laravelClient, userId, accessToken) {
  return tool(
    async () => {
      try {
        const data = await laravelClient(path, {}, { userId, accessToken });
        return JSON.stringify(data);
      } catch (err) {
        return `Failed to get wallet balance: ${err.message}`;
      }
    },
    {
      name: 'getWalletBalance',
      description: 'Get the customer\'s wallet balance. Use when the user asks how much is in their wallet or their balance.',
      schema,
    }
  );
}
