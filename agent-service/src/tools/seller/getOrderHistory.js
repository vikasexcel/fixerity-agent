/**
 * Seller tool: List provider order history.
 *
 * Laravel: POST /api/on-demand/order-history
 * Required: provider_id, access_token, timezone
 * Optional: filter_type (0–5)
 *
 * Use when the seller asks for their orders, past bookings, or order history.
 */

import { tool } from '@langchain/core/tools';
import { z } from 'zod';

const path = 'on-demand/order-history';

const schema = z.object({
  timezone: z.string().describe('Provider timezone (e.g. Asia/Kolkata, America/New_York)'),
  filter_type: z.number().min(0).max(5).optional().describe('Filter: 0–5 for order status filter'),
});

/**
 * @param {typeof import('../../lib/laravelClient.js').post} laravelClient
 * @param {number} [providerId]
 * @param {string} [accessToken]
 * @returns {import('@langchain/core/tools').StructuredToolInterface}
 */
export function createGetOrderHistoryTool(laravelClient, providerId, accessToken) {
  return tool(
    async (input) => {
      try {
        const data = await laravelClient(path, input, { providerId, accessToken });
        return JSON.stringify(data);
      } catch (err) {
        return `Failed to get order history: ${err.message}`;
      }
    },
    {
      name: 'getOrderHistory',
      description: 'Get the provider\'s order history (past bookings). Requires timezone; optional filter_type 0–5 for status. Use when the seller asks for their orders or booking history.',
      schema,
    }
  );
}
