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
