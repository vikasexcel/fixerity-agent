/**
 * Seller Agent tools factory.
 * Returns an array of LangChain tools with auth (providerId, accessToken) injected
 * for use with createReactAgent({ llm, tools }) or a custom LangGraph tool node.
 */

import { post } from '../../lib/laravelClient.js';
import { createGetPackageListTool } from './getPackageList.js';
import { createAddOrUpdatePackageTool } from './addOrUpdatePackage.js';
import { createGetOrderDetailsTool } from './getOrderDetails.js';
import { createUpdateOrderStatusTool } from './updateOrderStatus.js';
import { createGetOrderHistoryTool } from './getOrderHistory.js';
import { createGetOpenTimeListTool } from './getOpenTimeList.js';
import { createUpdateWorkStatusTool } from './updateWorkStatus.js';
import { createGetOrderFeedbackTool } from './getOrderFeedback.js';

/**
 * Create all Seller Agent tools with auth context.
 * @param {{ providerId?: number; accessToken?: string }} auth - Provider provider_id and access_token for Laravel API.
 * @returns {import('@langchain/core/tools').StructuredToolInterface[]} Array of 8 tools for the Seller Agent.
 */
export function createSellerTools(auth = {}) {
  const { providerId, accessToken } = auth;
  const client = post;

  return [
    createGetPackageListTool(client, providerId, accessToken),
    createAddOrUpdatePackageTool(client, providerId, accessToken),
    createGetOrderDetailsTool(client, providerId, accessToken),
    createUpdateOrderStatusTool(client, providerId, accessToken),
    createGetOrderHistoryTool(client, providerId, accessToken),
    createGetOpenTimeListTool(client, providerId, accessToken),
    createUpdateWorkStatusTool(client, providerId, accessToken),
    createGetOrderFeedbackTool(client, providerId, accessToken),
  ];
}
