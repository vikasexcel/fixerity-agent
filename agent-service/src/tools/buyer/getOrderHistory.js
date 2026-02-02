/**
 * Buyer tool: List customer order history.
 *
 * Laravel: POST /api/customer/on-demand/order-history
 * Required: timezone
 * Optional: user_id, access_token, filter_type (0-5)
 *
 * Use when the user asks for their orders, past bookings, or order history.
 */

import { tool } from '@langchain/core/tools';
import { z } from 'zod';
import { post } from '../../lib/laravelClient.js';

const path = 'customer/on-demand/order-history';

const schema = z.object({
  timezone: z.string().describe('User timezone (e.g. Asia/Kolkata, America/New_York)'),
  filter_type: z.number().min(0).max(5).optional().describe('Filter: 0-5 for order status filter'),
});

/**
 * @param {typeof post} laravelClient
 * @param {number} [userId]
 * @param {string} [accessToken]
 * @returns {import('@langchain/core/tools').StructuredToolInterface}
 */
export function createGetOrderHistoryTool(laravelClient, userId, accessToken) {
  return tool(
    async (input) => {
      try {
        const data = await laravelClient(path, input, { userId, accessToken });
        return JSON.stringify(data);
      } catch (err) {
        return `Failed to get order history: ${err.message}`;
      }
    },
    {
      name: 'getOrderHistory',
      description: 'Get the customer\'s order history (past bookings). Requires timezone; optional filter_type 0-5 for status. Use when the user asks for their orders or booking history.',
      schema,
    }
  );
}
