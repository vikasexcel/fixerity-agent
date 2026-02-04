/**
 * HTTP client for Laravel customer/on-demand APIs.
 * POSTs to LARAVEL_API_BASE_URL with optional user_id, provider_id, and access_token in the body.
 */

import { LARAVEL_API_BASE_URL } from '../config/index.js';

/**
 * POST to a Laravel customer or provider API path.
 * @param {string} path - Path relative to base URL (e.g. 'customer/on-demand/provider-list' or 'on-demand/package-list').
 * @param {Record<string, unknown>} body - Request body (will be JSON).
 * @param {{ userId?: number; providerId?: number; accessToken?: string }} [auth] - Optional user_id (customer), provider_id (seller), and access_token to merge into body.
 *   When providerId is set, user_id is never sent so Laravel uses provider auth (avoids "App User Not Found").
 * @returns {Promise<Record<string, unknown>>} Parsed JSON response.
 * @throws {Error} On HTTP error or when response.status === 0 or status === 5 with a message.
 */
export async function post(path, body, auth = {}) {
  const url = `${LARAVEL_API_BASE_URL}/${path.replace(/^\//, '')}`;
  // When authenticating as provider, never send user_id so Laravel validates provider_id only
  const useProviderAuth = auth.providerId != null;
  const payload = {
    ...body,
    ...(!useProviderAuth && auth.userId != null && { user_id: auth.userId }),
    ...(auth.providerId != null && { provider_id: auth.providerId }),
    ...(auth.accessToken != null && { access_token: auth.accessToken }),
  };

  const response = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  });

  const data = await response.json().catch(() => ({}));

  if (!response.ok) {
    const message = data?.message ?? response.statusText ?? 'Request failed';
    throw new Error(message);
  }

  // status 0 = validation/general error; status 5 = user/provider not found or auth failed
  if ((data?.status === 0 || data?.status === 5) && data?.message) {
    throw new Error(data.message);
  }

  return data;
}
