/**
 * Seller tool: List provider packages for a service.
 *
 * Laravel: POST /api/on-demand/package-list
 * Required: provider_id, access_token, provider_service_id
 *
 * Use when the seller asks for their packages or service offerings.
 */

import { tool } from '@langchain/core/tools';
import { z } from 'zod';

const path = 'on-demand/package-list';

const schema = z.object({
  provider_service_id: z.number().describe('Provider service ID for the category/service'),
});

/**
 * @param {typeof import('../../lib/laravelClient.js').post} laravelClient
 * @param {number} [providerId]
 * @param {string} [accessToken]
 * @returns {import('@langchain/core/tools').StructuredToolInterface}
 */
export function createGetPackageListTool(laravelClient, providerId, accessToken) {
  return tool(
    async (input) => {
      try {
        const data = await laravelClient(path, input, { providerId, accessToken });
        return JSON.stringify(data);
      } catch (err) {
        return `Failed to get package list: ${err.message}`;
      }
    },
    {
      name: 'getPackageList',
      description: 'Get the provider\'s package list for a service. Requires provider_service_id. Use when the seller asks for their packages or service offerings.',
      schema,
    }
  );
}
