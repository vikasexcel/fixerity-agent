/**
 * Buyer tool: Search for service providers by category and location.
 *
 * Laravel: POST /api/customer/on-demand/provider-list
 * Required: service_category_id, sub_category_id, lat, long
 * Optional (from context): user_id, access_token
 *
 * Use when the user wants to find providers (e.g. plumbers, electricians) near a location.
 */

import { tool } from '@langchain/core/tools';
import { z } from 'zod';
import { post } from '../../lib/laravelClient.js';

const path = 'customer/on-demand/provider-list';

const schema = z.object({
  service_category_id: z.number().describe('Service category ID'),
  sub_category_id: z.number().describe('Sub-category ID'),
  lat: z.number().describe('Latitude for search location'),
  long: z.number().describe('Longitude for search location'),
});

/**
 * @param {typeof post} laravelClient
 * @param {number} [userId]
 * @param {string} [accessToken]
 * @returns {import('@langchain/core/tools').StructuredToolInterface}
 */
export function createSearchProvidersTool(laravelClient, userId, accessToken) {
  return tool(
    async (input) => {
      try {
        const data = await laravelClient(path, input, { userId, accessToken });
        return JSON.stringify(data);
      } catch (err) {
        return `Provider search failed: ${err.message}`;
      }
    },
    {
      name: 'searchProviders',
      description: 'Search for service providers by category and location (latitude/longitude). Use when the user wants to find providers such as plumbers or handymen near an area.',
      schema,
    }
  );
}
