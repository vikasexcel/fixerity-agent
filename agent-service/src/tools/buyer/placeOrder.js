/**
 * Buyer tool: Place an order.
 *
 * Laravel: POST /api/customer/on-demand/place-order
 * Required: user_id, access_token, service_category_id, provider_id, address, package_id_list,
 *           package_quantity_list, payment_type (1|2|3), select_time, select_date
 * Optional: remark, select_provider_location, promo_code
 *
 * Use when the user confirms they want to book and pay.
 */

import { tool } from '@langchain/core/tools';
import { z } from 'zod';
import { post } from '../../lib/laravelClient.js';

const path = 'customer/on-demand/place-order';

const schema = z.object({
  service_category_id: z.number().describe('Service category ID'),
  provider_id: z.number().describe('Provider ID'),
  address: z.number().describe('Address ID'),
  package_id_list: z.array(z.number()).describe('List of package IDs'),
  package_quantity_list: z.array(z.number()).describe('Quantity for each package'),
  payment_type: z.union([z.literal(1), z.literal(2), z.literal(3)]).describe('Payment type: 1, 2, or 3'),
  select_time: z.string().describe('Selected time slot'),
  select_date: z.string().describe('Selected date (e.g. YYYY-MM-DD)'),
  remark: z.string().optional().describe('Order note or remark'),
  select_provider_location: z.union([z.literal(0), z.literal(1)]).optional().describe('Use provider location (0 or 1)'),
  promo_code: z.string().optional().describe('Promo code'),
});

/**
 * @param {typeof post} laravelClient
 * @param {number} [userId]
 * @param {string} [accessToken]
 * @returns {import('@langchain/core/tools').StructuredToolInterface}
 */
export function createPlaceOrderTool(laravelClient, userId, accessToken) {
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
        return `Place order failed: ${err.message}`;
      }
    },
    {
      name: 'placeOrder',
      description: 'Place a booking order with a provider. Requires provider, address, packages, payment type (1, 2, or 3), and selected date and time. Use when the user confirms they want to book.',
      schema,
    }
  );
}
