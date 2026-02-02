/**
 * Buyer tool: List packages offered by a provider.
 *
 * Laravel: POST /api/customer/on-demand/provider-package-list
 * Required: provider_id, service_category_id
 * Optional (from context): user_id, access_token
 *
 * Use when the user wants to see pricing or package options for a provider.
 */

import { tool } from '@langchain/core/tools';
import { z } from 'zod';
import { post } from '../../lib/laravelClient.js';

const path = 'customer/on-demand/provider-package-list';

const schema = z.object({
  provider_id: z.number().describe('Provider ID'),
  service_category_id: z.number().describe('Service category ID'),
});

/**
 * @param {typeof post} laravelClient
 * @param {number} [userId]
 * @param {string} [accessToken]
 * @returns {import('@langchain/core/tools').StructuredToolInterface}
 */
export function createGetProviderPackagesTool(laravelClient, userId, accessToken) {
  return tool(
    async (input) => {
      try {
        const data = await laravelClient(path, input, { userId, accessToken });
        return JSON.stringify(data);
      } catch (err) {
        return `Failed to get provider packages: ${err.message}`;
      }
    },
    {
      name: 'getProviderPackages',
      description: 'List service packages and pricing for a specific provider. Use when the user asks about packages, prices, or options for a provider.',
      schema,
    }
  );
}
