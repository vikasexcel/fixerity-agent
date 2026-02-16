/**
 * Provider basic details service.
 * Fetches first_name, last_name, email, gender, contact_number from
 * Laravel API (on-demand/provider-basic-details) and caches per provider.
 * Use this when creating seller profiles - fetch once per provider, use for all profiles.
 */

import { LARAVEL_API_BASE_URL } from '../config/index.js';
import { cacheService } from './cacheService.js';

/**
 * Fetch provider basic details from Laravel API.
 * @param {number} providerId - External provider ID
 * @returns {Promise<{ firstName?: string; lastName?: string; email?: string; gender?: number; contactNumber?: string } | null>}
 */
async function fetchProviderBasicDetailsFromAPI(providerId) {
  try {
    const response = await fetch(`${LARAVEL_API_BASE_URL}/on-demand/provider-basic-details`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ provider_id: providerId }),
    });

    const data = await response.json();

    if (data?.status !== 1 || !data?.data) {
      console.warn(`[ProviderDetails] API returned status=${data?.status}, message=${data?.message}`);
      return null;
    }

    const d = data.data;
    return {
      firstName: d.first_name ?? '',
      lastName: d.last_name ?? '',
      email: d.email ?? '',
      gender: d.gender ?? null,
      contactNumber: d.contact_number ?? '',
    };
  } catch (error) {
    console.error(`[ProviderDetails] Fetch error for provider ${providerId}:`, error.message);
    return null;
  }
}

/**
 * Get provider basic details - uses cache, fetches once per provider.
 * Fetches from API: POST /api/on-demand/provider-basic-details with { provider_id }
 * Response: { status: 1, data: { first_name, last_name, email, contact_number, gender } }
 * @param {number} providerId - External provider ID
 * @returns {Promise<{ firstName?: string; lastName?: string; email?: string; gender?: number; contactNumber?: string } | null>}
 */
export async function getProviderBasicDetails(providerId) {
  const pid = Number(providerId);
  if (!pid) {
    console.warn('[ProviderDetails] Invalid providerId:', providerId);
    return null;
  }

  return cacheService.getProviderBasic(
    pid,
    async () => await fetchProviderBasicDetailsFromAPI(pid)
  );
}
