/**
 * Seller tool: Update order status.
 *
 * Laravel: POST /api/on-demand/update-order-status
 * Required: provider_id, access_token, order_id, update_status (2–9)
 *
 * Use when the seller wants to change an order status (e.g. accept, start, complete).
 */

import { tool } from '@langchain/core/tools';
import { z } from 'zod';

const path = 'on-demand/update-order-status';

const schema = z.object({
  order_id: z.number().describe('Order ID'),
  update_status: z
    .number()
    .int()
    .min(2)
    .max(9)
    .describe('New status: 2–9 (e.g. accepted, in progress, completed)'),
});

/**
 * @param {typeof import('../../lib/laravelClient.js').post} laravelClient
 * @param {number} [providerId]
 * @param {string} [accessToken]
 * @returns {import('@langchain/core/tools').StructuredToolInterface}
 */
export function createUpdateOrderStatusTool(laravelClient, providerId, accessToken) {
  return tool(
    async (input) => {
      try {
        const data = await laravelClient(path, input, { providerId, accessToken });
        return JSON.stringify(data);
      } catch (err) {
        return `Failed to update order status: ${err.message}`;
      }
    },
    {
      name: 'updateOrderStatus',
      description: 'Update an order\'s status. Requires order_id and update_status (2–9). Use when the seller wants to accept, start, complete, or otherwise change an order status.',
      schema,
    }
  );
}
