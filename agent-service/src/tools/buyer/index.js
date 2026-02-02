/**
 * Buyer Agent tools factory.
 * Returns an array of LangChain tools with auth (userId, accessToken) injected
 * for use with createReactAgent({ llm, tools }) or a custom LangGraph tool node.
 */

import { post } from '../../lib/laravelClient.js';
import { createSearchProvidersTool } from './searchProviders.js';
import { createGetProviderDetailsTool } from './getProviderDetails.js';
import { createGetProviderPackagesTool } from './getProviderPackages.js';
import { createGetProviderTimeSlotsTool } from './getProviderTimeSlots.js';
import { createGetOrderPreviewTool } from './getOrderPreview.js';
import { createPlaceOrderTool } from './placeOrder.js';
import { createGetOrderHistoryTool } from './getOrderHistory.js';
import { createGetOrderDetailsTool } from './getOrderDetails.js';
import { createGetAddressListTool } from './getAddressList.js';
import { createGetWalletBalanceTool } from './getWalletBalance.js';
import { createGetProviderReviewsTool } from './getProviderReviews.js';

/**
 * Create all Buyer Agent tools with auth context.
 * @param {{ userId?: number; accessToken?: string }} auth - Customer user_id and access_token for Laravel API.
 * @returns {import('@langchain/core/tools').StructuredToolInterface[]} Array of 11 tools for the Buyer Agent.
 */
export function createBuyerTools(auth = {}) {
  const { userId, accessToken } = auth;
  const client = post;

  return [
    createSearchProvidersTool(client, userId, accessToken),
    createGetProviderDetailsTool(client, userId, accessToken),
    createGetProviderPackagesTool(client, userId, accessToken),
    createGetProviderTimeSlotsTool(client, userId, accessToken),
    createGetOrderPreviewTool(client, userId, accessToken),
    createPlaceOrderTool(client, userId, accessToken),
    createGetOrderHistoryTool(client, userId, accessToken),
    createGetOrderDetailsTool(client, userId, accessToken),
    createGetAddressListTool(client, userId, accessToken),
    createGetWalletBalanceTool(client, userId, accessToken),
    createGetProviderReviewsTool(client, userId, accessToken),
  ];
}
