/**
 * Seller tool: Add or update a service package.
 *
 * Laravel: POST /api/on-demand/add-update-package
 * Required: provider_id, access_token, provider_service_id, category_id, package_name,
 *           package_description, package_price, max_book_quantity
 * Optional: package_id (omit for add, include for update)
 *
 * Use when the seller wants to create or edit a package.
 */

import { tool } from '@langchain/core/tools';
import { z } from 'zod';

const path = 'on-demand/add-update-package';

const schema = z.object({
  provider_service_id: z.number().describe('Provider service ID'),
  category_id: z.number().describe('Category ID'),
  package_name: z.string().describe('Package name'),
  package_description: z.string().describe('Package description'),
  package_price: z.number().describe('Package price'),
  max_book_quantity: z.number().int().min(1).describe('Maximum bookable quantity per order'),
  package_id: z.number().optional().describe('Package ID; omit for new package, include for update'),
});

/**
 * @param {typeof import('../../lib/laravelClient.js').post} laravelClient
 * @param {number} [providerId]
 * @param {string} [accessToken]
 * @returns {import('@langchain/core/tools').StructuredToolInterface}
 */
export function createAddOrUpdatePackageTool(laravelClient, providerId, accessToken) {
  return tool(
    async (input) => {
      try {
        const data = await laravelClient(path, input, { providerId, accessToken });
        return JSON.stringify(data);
      } catch (err) {
        return `Failed to add or update package: ${err.message}`;
      }
    },
    {
      name: 'addOrUpdatePackage',
      description: 'Add a new package or update an existing one. Requires provider_service_id, category_id, package_name, package_description, package_price, max_book_quantity. Use package_id only when updating. Use when the seller wants to create or edit a package.',
      schema,
    }
  );
}
