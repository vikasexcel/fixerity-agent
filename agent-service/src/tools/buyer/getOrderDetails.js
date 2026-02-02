/**
 * Buyer tool: Get details for a single order.
 *
 * Laravel: POST /api/customer/on-demand/order-details
 * Required: user_id, access_token, order_id
 *
 * Use when the user asks about a specific order or booking.
 */

import { tool } from '@langchain/core/tools';
import { z } from 'zod';
import { post } from '../../lib/laravelClient.js';

const path = 'customer/on-demand/order-details';

const schema = z.object({
  order_id: z.number().describe('Order ID'),
});

/**
 * @param {typeof post} laravelClient
 * @param {number} [userId]
 * @param {string} [accessToken]
 * @returns {import('@langchain/core/tools').StructuredToolInterface}
 */
export function createGetOrderDetailsTool(laravelClient, userId, accessToken) {
  return tool(
    async (input) => {
      try {
        const data = await laravelClient(path, input, { userId, accessToken });
        return JSON.stringify(data);
      } catch (err) {
        return `Failed to get order details: ${err.message}`;
      }
    },
    {
      name: 'getOrderDetails',
      description: 'Get full details for a specific order (status, items, provider, address). Requires order_id. Use when the user asks about a particular booking or order.',
      schema,
    }
  );
}
