/**
 * Buyer tool: List customer saved addresses.
 *
 * Laravel: POST /api/customer/address-list
 * Required (from context): user_id, access_token. No extra body params.
 *
 * Use when the user wants to see their addresses or choose one for booking.
 */

import { tool } from '@langchain/core/tools';
import { z } from 'zod';
import { post } from '../../lib/laravelClient.js';

const path = 'customer/address-list';

const schema = z.object({});

/**
 * @param {typeof post} laravelClient
 * @param {number} [userId]
 * @param {string} [accessToken]
 * @returns {import('@langchain/core/tools').StructuredToolInterface}
 */
export function createGetAddressListTool(laravelClient, userId, accessToken) {
  return tool(
    async () => {
      try {
        const data = await laravelClient(path, {}, { userId, accessToken });
        return JSON.stringify(data);
      } catch (err) {
        return `Failed to get address list: ${err.message}`;
      }
    },
    {
      name: 'getAddressList',
      description: 'Get the customer\'s saved addresses. Use when the user wants to see their addresses or pick one for a booking. Requires authenticated customer.',
      schema,
    }
  );
}
