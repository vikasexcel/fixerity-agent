/**
 * Buyer tool: Get reviews for a provider.
 *
 * Laravel: POST /api/customer/on-demand/provider-review
 * Required: provider_id, service_category_id, page
 * Optional: user_id, access_token, per_page
 *
 * Use when the user wants to see reviews or ratings for a provider.
 */

import { tool } from '@langchain/core/tools';
import { z } from 'zod';
import { post } from '../../lib/laravelClient.js';

const path = 'customer/on-demand/provider-review';

const schema = z.object({
  provider_id: z.number().describe('Provider ID'),
  service_category_id: z.number().describe('Service category ID'),
  page: z.number().describe('Page number for pagination'),
  per_page: z.number().optional().describe('Items per page'),
});

/**
 * @param {typeof post} laravelClient
 * @param {number} [userId]
 * @param {string} [accessToken]
 * @returns {import('@langchain/core/tools').StructuredToolInterface}
 */
export function createGetProviderReviewsTool(laravelClient, userId, accessToken) {
  return tool(
    async (input) => {
      try {
        const data = await laravelClient(path, input, { userId, accessToken });
        return JSON.stringify(data);
      } catch (err) {
        return `Failed to get provider reviews: ${err.message}`;
      }
    },
    {
      name: 'getProviderReviews',
      description: 'Get reviews and ratings for a provider. Requires provider_id, service_category_id, and page. Use when the user wants to see what others said about a provider.',
      schema,
    }
  );
}
