/**
 * Seller tool: Update provider work status (available / busy).
 *
 * Laravel: POST /api/on-demand/update-work-status
 * Required: provider_id, access_token, status (0 or 1)
 *
 * Use when the seller wants to set themselves as available or busy.
 */

import { tool } from '@langchain/core/tools';
import { z } from 'zod';

const path = 'on-demand/update-work-status';

const schema = z.object({
  status: z.union([z.literal(0), z.literal(1)]).describe('Work status: 0 = busy/unavailable, 1 = available'),
});

/**
 * @param {typeof import('../../lib/laravelClient.js').post} laravelClient
 * @param {number} [providerId]
 * @param {string} [accessToken]
 * @returns {import('@langchain/core/tools').StructuredToolInterface}
 */
export function createUpdateWorkStatusTool(laravelClient, providerId, accessToken) {
  return tool(
    async (input) => {
      try {
        const data = await laravelClient(path, input, { providerId, accessToken });
        return JSON.stringify(data);
      } catch (err) {
        return `Failed to update work status: ${err.message}`;
      }
    },
    {
      name: 'updateWorkStatus',
      description: 'Update the provider\'s work status. Requires status: 0 = busy/unavailable, 1 = available. Use when the seller wants to set themselves as available or busy.',
      schema,
    }
  );
}
