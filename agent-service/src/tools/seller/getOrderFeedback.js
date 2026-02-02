/**
 * Seller tool: Get order feedback for the provider.
 *
 * Laravel: POST /api/on-demand/order-feedback
 * Required (from context): provider_id, access_token. No extra body params.
 *
 * Use when the seller asks for feedback or reviews they received.
 */

import { tool } from '@langchain/core/tools';
import { z } from 'zod';

const path = 'on-demand/order-feedback';

const schema = z.object({});

/**
 * @param {typeof import('../../lib/laravelClient.js').post} laravelClient
 * @param {number} [providerId]
 * @param {string} [accessToken]
 * @returns {import('@langchain/core/tools').StructuredToolInterface}
 */
export function createGetOrderFeedbackTool(laravelClient, providerId, accessToken) {
  return tool(
    async () => {
      try {
        const data = await laravelClient(path, {}, { providerId, accessToken });
        return JSON.stringify(data);
      } catch (err) {
        return `Failed to get order feedback: ${err.message}`;
      }
    },
    {
      name: 'getOrderFeedback',
      description: 'Get the provider\'s order feedback and reviews. Use when the seller asks for feedback or reviews they received. Requires authenticated provider.',
      schema,
    }
  );
}
