/**
 * Buyer tool: Get details for a single provider.
 *
 * Laravel: POST /api/customer/on-demand/provider-details
 * Required: provider_id, service_category_id, lat, long
 * Optional (from context): user_id, access_token
 *
 * Use when the user wants to see a provider's profile, ratings, or service info.
 */

import { tool } from '@langchain/core/tools';
import { z } from 'zod';
import { post } from '../../lib/laravelClient.js';

const path = 'customer/on-demand/provider-details';

const schema = z.object({
  provider_id: z.number().describe('Provider ID'),
  service_category_id: z.number().describe('Service category ID'),
  lat: z.number().describe('Latitude for distance/availability'),
  long: z.number().describe('Longitude for distance/availability'),
});

/**
 * @param {typeof post} laravelClient
 * @param {number} [userId]
 * @param {string} [accessToken]
 * @returns {import('@langchain/core/tools').StructuredToolInterface}
 */
export function createGetProviderDetailsTool(laravelClient, userId, accessToken) {
  return tool(
    async (input) => {
      try {
        const data = await laravelClient(path, input, { userId, accessToken });
        return JSON.stringify(data);
      } catch (err) {
        return `Failed to get provider details: ${err.message}`;
      }
    },
    {
      name: 'getProviderDetails',
      description: 'Get full details for a specific service provider (profile, ratings, services). Requires provider ID, service category, and location (lat/long).',
      schema,
    }
  );
}
