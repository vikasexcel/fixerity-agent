/**
 * Customer user details service.
 * Fetches first_name, last_name, email, contact_number from
 * Laravel API (customer/user-details) for buyers. Uses cache - fetch once per buyer, use for all jobs.
 */

import { LARAVEL_API_BASE_URL } from '../config/index.js';
import { cacheService } from './cacheService.js';

/**
 * Fetch customer user details from Laravel API.
 * @param {number} userId - Buyer user ID
 * @returns {Promise<{ firstName?: string; lastName?: string; email?: string; contactNumber?: string } | null>}
 */
async function fetchCustomerUserDetailsFromAPI(userId) {
  try {
    const response = await fetch(`${LARAVEL_API_BASE_URL}/customer/user-details`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ user_id: userId }),
    });

    const data = await response.json();

    if (data?.status !== 1 || !data?.data) {
      console.warn(`[CustomerUserDetails] API returned status=${data?.status}, message=${data?.message}`);
      return null;
    }

    const d = data.data;
    return {
      firstName: d.first_name ?? '',
      lastName: d.last_name ?? '',
      email: d.email ?? '',
      contactNumber: d.contact_number ?? '',
    };
  } catch (error) {
    console.error(`[CustomerUserDetails] Fetch error for user ${userId}:`, error.message);
    return null;
  }
}

/**
 * Get customer user details - uses cache, fetches once per buyer.
 * API: POST /api/customer/user-details with { user_id }
 * Response: { status: 1, data: { first_name, last_name, email, contact_number } }
 * @param {string|number} buyerId - Buyer user ID (buyer_id from job)
 * @returns {Promise<{ firstName?: string; lastName?: string; email?: string; contactNumber?: string } | null>}
 */
export async function getCustomerUserDetails(buyerId) {
  const uid = parseInt(buyerId, 10);
  if (!uid || !Number.isFinite(uid)) {
    console.warn('[CustomerUserDetails] Invalid buyerId:', buyerId);
    return null;
  }

  return cacheService.getOrFetch(
    `customer:user:${uid}`,
    async () => await fetchCustomerUserDetailsFromAPI(uid),
    86400 // 24 hours - static value, fetch once per buyer
  );
}
