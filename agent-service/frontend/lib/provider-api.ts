/**
 * Provider API client for fetching provider details from Laravel.
 */

import { apiPost } from './api';

export interface ProviderProfile {
  provider_id: number;
  provider_name: string;
  provider_last_name?: string;
  provider_profile_image?: string;
  provider_gender?: number;
  average_rating: number;
  total_completed_order: number;
  service_category_name?: string;
  service_category_icon?: string;
  distance?: number;
  provider_service_radius?: string;
  provider_services_list?: Array<{
    service_cat_id: number;
    service_cat_name: string;
  }>;
  current_status?: number;
  pending_orders?: unknown[];
  accepted_orders?: unknown[];
  processing_order?: unknown[];
  completed_order?: unknown[];
}

export interface ProviderService {
  service_cat_id: number;
  service_cat_name: string;
}

export interface ProviderHomeResponse {
  status: number;
  message: string;
  message_code: number;
  provider_name: string;
  provider_status: number;
  provider_profile_image?: string;
  provider_service_radius?: string;
  /** API returns comma-separated string; normalize to array for display */
  provider_services_list?: ProviderService[] | string;
  average_rating?: number;
  total_completed_order?: number;
  current_status: number;
  pending_orders: unknown[];
  accepted_orders: unknown[];
  processing_order: unknown[];
  completed_order: unknown[];
  is_auto_settle?: number;
}

/**
 * Get provider home/dashboard data.
 * Laravel: POST /api/on-demand/home
 */
export async function getProviderHome(
  providerId: number,
  accessToken: string,
  appVersion: string = '1.0.0'
): Promise<ProviderHomeResponse> {
  return apiPost<ProviderHomeResponse>('on-demand/home', {
    provider_id: providerId,
    access_token: accessToken,
    app_version: appVersion,
  });
}

/**
 * Get provider details for a specific service category.
 * Laravel: POST /api/customer/on-demand/provider-details
 */
export async function getProviderDetails(
  providerId: number,
  serviceCategoryId: number,
  lat: number,
  long: number,
  accessToken?: string
): Promise<ProviderProfile> {
  const body: Record<string, unknown> = {
    provider_id: providerId,
    service_category_id: serviceCategoryId,
    lat,
    long,
  };
  if (accessToken) {
    body.access_token = accessToken;
  }
  const data = await apiPost<{ provider_details: ProviderProfile } & { status: number; message: string }>(
    'customer/on-demand/provider-details',
    body
  );
  if (data.status !== 1 || !data.provider_details) {
    throw new Error(data.message || 'Provider details not found');
  }
  return data.provider_details;
}

export interface UpdateProviderProfilePayload {
  provider_id: number;
  access_token: string;
  full_name: string;
  last_name?: string;
  email: string;
  gender: number;
  contact_number: string;
  address: string;
  lat: number;
  long: number;
  landmark?: string;
  service_radius: number;
  select_country_code: string;
  profile_image?: File | string;
}

/**
 * Update provider profile.
 * Laravel: POST /api/on-demand/edit-profile
 */
export async function updateProviderProfile(
  payload: UpdateProviderProfilePayload
): Promise<{ status: number; message: string; message_code: number }> {
  const formData = new FormData();
  Object.entries(payload).forEach(([key, value]) => {
    if (value !== undefined && value !== null) {
      if (key === 'profile_image' && value instanceof File) {
        formData.append(key, value);
      } else {
        formData.append(key, String(value));
      }
    }
  });

  const base = getApiBaseUrl();
  const url = `${base}/api/on-demand/edit-profile`;
  const timeZone = typeof Intl !== 'undefined' && Intl.DateTimeFormat
    ? Intl.DateTimeFormat().resolvedOptions().timeZone
    : 'UTC';
  
  const res = await fetch(url, {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      'select-time-zone': timeZone,
    },
    body: formData,
  });

  const data = (await res.json().catch(() => ({}))) as { status: number; message: string; message_code: number };
  if (!res.ok) {
    throw new Error(data.message ?? res.statusText ?? 'Failed to update profile');
  }
  return data;
}

export interface ProviderServiceInfo {
  provider_service_id: number;
  service_cat_id: number;
  service_cat_name: string;
  status: number;
  current_status: number;
  subcategories?: Array<{ category_id: number; category_name: string }>;
  packages?: Array<{
    package_id: number;
    package_name: string;
    package_description?: string;
    package_price: number;
    max_book_quantity: number;
  }>;
}

/**
 * Get provider services list.
 * Laravel: POST /api/on-demand/provider-service-list
 */
export async function getProviderServices(
  providerId: number,
  accessToken: string
): Promise<ProviderServiceInfo[]> {
  const data = await apiPost<{ status: number; provider_service_list?: ProviderServiceInfo[] }>(
    'on-demand/provider-service-list',
    {
      provider_id: providerId,
      access_token: accessToken,
    }
  );
  if (data.status !== 1 || !data.provider_service_list) {
    return [];
  }
  return data.provider_service_list;
}

export interface CreateProviderServicePayload {
  provider_id: number;
  access_token: string;
  service_category_id: number;
}

/**
 * Create a new provider service.
 * Laravel: POST /api/on-demand/provider-service-register-step (step=2)
 */
export async function createProviderService(
  payload: CreateProviderServicePayload
): Promise<{ status: number; message: string; message_code: number }> {
  return apiPost<{ status: number; message: string; message_code: number }>(
    'on-demand/provider-service-register-step',
    {
      ...payload,
      step: 2,
    }
  );
}
