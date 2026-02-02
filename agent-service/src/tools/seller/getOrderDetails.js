/**
 * Seller tool: Get details for a single order (provider view).
 *
 * Laravel: POST /api/on-demand/order-details
 * Required: provider_id, access_token, order_id
 *
 * Use when the seller asks about a specific order or booking.
 */

import { tool } from '@langchain/core/tools';
import { z } from 'zod';

const path = 'on-demand/order-details';

const schema = z.object({
  order_id: z.number().describe('Order ID'),
});

/**
 * @param {typeof import('../../lib/laravelClient.js').post} laravelClient
 * @param {number} [providerId]
 * @param {string} [accessToken]
 * @returns {import('@langchain/core/tools').StructuredToolInterface}
 */
export function createGetOrderDetailsTool(laravelClient, providerId, accessToken) {
  return tool(
    async (input) => {
      try {
        const data = await laravelClient(path, input, { providerId, accessToken });
        return JSON.stringify(data);
      } catch (err) {
        return `Failed to get order details: ${err.message}`;
      }
    },
    {
      name: 'getOrderDetails',
      description: 'Get full details for a specific order (status, items, customer, address). Requires order_id. Use when the seller asks about a particular booking or order.',
      schema,
    }
  );
}
