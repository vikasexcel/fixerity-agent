/**
 * Seller tool: Search for available/open jobs in the marketplace.
 *
 * Laravel: POST /api/customer/on-demand/job/list
 * Note: Current endpoint filters by user_id. For seller job search, may need backend changes
 * to allow searching all open jobs by category/location, or use provider_id instead.
 * For now, this tool searches jobs that match the provider's service category and location.
 *
 * Required: provider_id, access_token
 * Optional: service_category_id, sub_category_id, lat, long, status (default: 'open')
 *
 * Use when the seller wants to find available jobs that match their profile.
 */

import { tool } from '@langchain/core/tools';
import { z } from 'zod';

const path = 'customer/on-demand/job/list';

const schema = z.object({
  service_category_id: z.number().optional().describe('Service category ID to filter jobs'),
  sub_category_id: z.number().optional().describe('Sub-category ID to filter jobs'),
  lat: z.number().optional().describe('Latitude for location-based job search'),
  long: z.number().optional().describe('Longitude for location-based job search'),
  status: z.enum(['open', 'matched', 'completed', 'all']).optional().default('open').describe('Job status filter (default: open)'),
});

/**
 * @param {typeof import('../../lib/laravelClient.js').post} laravelClient
 * @param {number} [providerId]
 * @param {string} [accessToken]
 * @returns {import('@langchain/core/tools').StructuredToolInterface}
 */
export function createSearchAvailableJobsTool(laravelClient, providerId, accessToken) {
  return tool(
    async (input) => {
      try {
        // Note: Current Laravel endpoint requires user_id, but we're searching as provider
        // This may require backend changes. For now, we'll attempt the call.
        // Backend should ideally accept provider_id and return open jobs matching category/location
        const payload = {
          status: input.status || 'open',
          ...(input.service_category_id && { service_category_id: input.service_category_id }),
          ...(input.sub_category_id && { sub_category_id: input.sub_category_id }),
          ...(input.lat != null && { lat: input.lat }),
          ...(input.long != null && { long: input.long }),
        };
        
        const data = await laravelClient(path, payload, { providerId, accessToken });
        return JSON.stringify(data);
      } catch (err) {
        return `Failed to search available jobs: ${err.message}`;
      }
    },
    {
      name: 'searchAvailableJobs',
      description: 'Search for available/open jobs in the marketplace that match the provider\'s service category and location. Use when the seller wants to discover job opportunities. Note: May require backend endpoint changes to support provider-based job search.',
      schema,
    }
  );
}
