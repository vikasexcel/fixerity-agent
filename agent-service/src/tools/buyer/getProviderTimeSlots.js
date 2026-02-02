/**
 * Buyer tool: Get available time slots for a provider on a date.
 *
 * Laravel: POST /api/customer/on-demand/provider-time-list
 * Required: user_id, access_token, provider_id, select_date
 * Optional: timezone
 *
 * Use when the user wants to book or see available times for a provider.
 */

import { tool } from '@langchain/core/tools';
import { z } from 'zod';
import { post } from '../../lib/laravelClient.js';

const path = 'customer/on-demand/provider-time-list';

const schema = z.object({
  provider_id: z.number().describe('Provider ID'),
  select_date: z.string().describe('Date for availability (e.g. YYYY-MM-DD)'),
  timezone: z.string().optional().describe('Timezone (e.g. Asia/Kolkata)'),
});

/**
 * @param {typeof post} laravelClient
 * @param {number} [userId]
 * @param {string} [accessToken]
 * @returns {import('@langchain/core/tools').StructuredToolInterface}
 */
export function createGetProviderTimeSlotsTool(laravelClient, userId, accessToken) {
  return tool(
    async (input) => {
      try {
        const data = await laravelClient(path, input, { userId, accessToken });
        return JSON.stringify(data);
      } catch (err) {
        return `Failed to get time slots: ${err.message}`;
      }
    },
    {
      name: 'getProviderTimeSlots',
      description: 'Get available time slots for a provider on a given date. Use when the user wants to book or see when a provider is free.',
      schema,
    }
  );
}
