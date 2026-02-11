import { post } from "../../lib/laravelClient.js";

const PROVIDER_BY_CATEGORY_PATH = 'on-demand/public/provider-service-by-category';
const PROVIDER_BASIC_DETAILS_PATH = 'on-demand/provider-basic-details';

/**
 * Fetch provider basic details by provider_id.
 * @param {number} providerId - Provider ID
 * @returns {Promise<{ first_name: string, last_name: string, email: string, contact_number: string, gender: number } | null>}
 */
export async function fetchProviderBasicDetails(providerId) {
  const id = Number(providerId);
  if (!id) return null;
  try {
    const data = await post(PROVIDER_BASIC_DETAILS_PATH, { provider_id: id });
    if (data.status !== 1 || !data.data) return null;
    return data.data;
  } catch {
    return null;
  }
}

/**
 * Fetch providers for a service category (user-authenticated).
 * @param {string} accessToken - User's access token
 * @param {number} service_category_id - From job
 * @returns {Promise<{ providers: Array, error?: string }>}
 */
export async function fetchProvidersByCategory(accessToken, service_category_id) {
  const payload = {
    access_token: accessToken,
    service_category_id: Number(service_category_id) || 0,
  };
  try {
    const data = await post(PROVIDER_BY_CATEGORY_PATH, payload);
    if (data.status !== 1 || !Array.isArray(data.data)) {
      return { providers: [], error: data?.message || 'No providers found for this category.' };
    }
    return { providers: data.data };
  } catch (err) {
    const isNoData = err.message === 'Data Not Found' || /not found|no provider/i.test(err.message);
    return {
      providers: [],
      error: err.message,
      ...(isNoData && { message: 'No providers found for this category.' }),
    };
  }
}
