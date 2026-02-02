/**
 * HTTP client for Laravel customer/on-demand APIs.
 * POSTs to LARAVEL_API_BASE_URL with optional user_id and access_token in the body.
 */

import { LARAVEL_API_BASE_URL } from '../config/index.js';

/**
 * POST to a Laravel customer API path.
 * @param {string} path - Path relative to base URL (e.g. 'customer/on-demand/provider-list').
 * @param {Record<string, unknown>} body - Request body (will be JSON).
 * @param {{ userId?: number; accessToken?: string }} [auth] - Optional user_id and access_token to merge into body.
 * @returns {Promise<Record<string, unknown>>} Parsed JSON response.
 * @throws {Error} On HTTP error or when response.status === 0 with a message.
 */
export async function post(path, body, auth = {}) {
  const url = `${LARAVEL_API_BASE_URL}/${path.replace(/^\//, '')}`;
  const payload = {
    ...body,
    ...(auth.userId != null && { user_id: auth.userId }),
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

  if (data?.status === 0 && data?.message) {
    throw new Error(data.message);
  }

  return data;
}
