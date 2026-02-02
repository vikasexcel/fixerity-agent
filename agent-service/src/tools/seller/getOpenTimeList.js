/**
 * Seller tool: Get open/available time slots for a day.
 *
 * Laravel: POST /api/on-demand/open-time-list
 * Required: provider_id, access_token, day
 *
 * Use when the seller asks for their availability or open slots for a day.
 */

import { tool } from '@langchain/core/tools';
import { z } from 'zod';

const path = 'on-demand/open-time-list';

const schema = z.object({
  day: z.string().describe('Day (e.g. MONDAY, TUESDAY or uppercase day name)'),
});

/**
 * @param {typeof import('../../lib/laravelClient.js').post} laravelClient
 * @param {number} [providerId]
 * @param {string} [accessToken]
 * @returns {import('@langchain/core/tools').StructuredToolInterface}
 */
export function createGetOpenTimeListTool(laravelClient, providerId, accessToken) {
  return tool(
    async (input) => {
      try {
        const data = await laravelClient(path, input, { providerId, accessToken });
        return JSON.stringify(data);
      } catch (err) {
        return `Failed to get open time list: ${err.message}`;
      }
    },
    {
      name: 'getOpenTimeList',
      description: 'Get the provider\'s open/available time slots for a given day. Requires day (e.g. MONDAY). Use when the seller asks for their availability or open slots.',
      schema,
    }
  );
}
