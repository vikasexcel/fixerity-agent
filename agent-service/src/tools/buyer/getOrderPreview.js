/**
 * Buyer tool: Preview order totals before placing.
 *
 * Laravel: POST /api/customer/on-demand/order-preview
 * Required: user_id, access_token, service_category_id, provider_id, address, package_id_list, package_quantity_list
 * Optional: payment_type, promo_code
 *
 * Use when the user wants to see order summary, total, or apply a promo before placing.
 */

import { tool } from '@langchain/core/tools';
import { z } from 'zod';
import { post } from '../../lib/laravelClient.js';

const path = 'customer/on-demand/order-preview';

const schema = z.object({
  service_category_id: z.number().describe('Service category ID'),
  provider_id: z.number().describe('Provider ID'),
  address: z.number().describe('Address ID'),
  package_id_list: z.array(z.number()).describe('List of package IDs'),
  package_quantity_list: z.array(z.number()).describe('Quantity for each package (same order as package_id_list)'),
  payment_type: z.number().optional().describe('Payment type (1, 2, or 3)'),
  promo_code: z.string().optional().describe('Promo code'),
});

/**
 * @param {typeof post} laravelClient
 * @param {number} [userId]
 * @param {string} [accessToken]
 * @returns {import('@langchain/core/tools').StructuredToolInterface}
 */
export function createGetOrderPreviewTool(laravelClient, userId, accessToken) {
  return tool(
    async (input) => {
      try {
        const body = {
          ...input,
          package_id_list: Array.isArray(input.package_id_list) ? input.package_id_list : [input.package_id_list],
          package_quantity_list: Array.isArray(input.package_quantity_list) ? input.package_quantity_list : [input.package_quantity_list],
        };
        const data = await laravelClient(path, body, { userId, accessToken });
        return JSON.stringify(data);
      } catch (err) {
        return `Order preview failed: ${err.message}`;
      }
    },
    {
      name: 'getOrderPreview',
      description: 'Preview an order (totals, discounts) before placing. Requires provider, address, and package IDs with quantities. Use when the user wants to see order summary or apply a promo.',
      schema,
    }
  );
}
