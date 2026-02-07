/**
 * Laravel services and addresses API for CreateJobModal.
 */

import { apiPost } from './api';

export type ServiceCategory = {
  service_category_id: number;
  service_category_name: string;
  service_category_icon?: string;
};

export type AddressItem = {
  address_id: number;
  type: string;
  address: string;
  lat: string;
  long: string;
  flat_no?: string;
  landmark?: string;
};

export async function fetchServiceCategories(
  userId: number,
  accessToken: string
): Promise<ServiceCategory[]> {
  const data = await apiPost<{ status: number; services?: ServiceCategory[] }>('customer/home', {
    user_id: userId,
    access_token: accessToken,
    app_version: '1.0',
  });
  if (data.status !== 1 || !data.services) {
    return [];
  }
  return data.services;
}

export async function fetchSubCategories(
  userId: number,
  accessToken: string,
  serviceCategoryId: number
): Promise<Array<{ id: number; name: string }>> {
  const data = await apiPost<{
    status: number;
    category_list?: Array<{ category_id: number; category_name: string }>;
  }>('customer/on-demand/category-list', {
    user_id: userId,
    access_token: accessToken,
    service_category_id: serviceCategoryId,
  });
  if (data.status !== 1 || !data.category_list) {
    return [];
  }
  return data.category_list.map((c) => ({ id: c.category_id, name: c.category_name }));
}

/**
 * Fetch subcategories for a service category (provider auth).
 * Uses on-demand/get-subcategory-list - does not require provider_service_id.
 * Laravel: POST /api/on-demand/get-subcategory-list
 */
export async function fetchProviderSubCategories(
  providerId: number,
  accessToken: string,
  serviceCategoryId: number
): Promise<Array<{ id: number; name: string }>> {
  const data = await apiPost<{
    status: number;
    category_list?: Array<{ category_id: number; category_name: string }>;
  }>('on-demand/get-subcategory-list', {
    provider_id: providerId,
    access_token: accessToken,
    service_category_id: serviceCategoryId,
  });
  if (data.status !== 1 || !data.category_list) {
    return [];
  }
  return data.category_list.map((c) => ({ id: c.category_id, name: c.category_name }));
}

/**
 * Fetch service categories for sellers/providers.
 * Uses on-demand/get-service-list API - returns categories the provider can add.
 * Laravel: POST /api/on-demand/get-service-list
 */
export async function fetchProviderServiceCategories(
  providerId: number,
  accessToken: string
): Promise<ServiceCategory[]> {
  const data = await apiPost<{
    status: number;
    service_category_list?: Array<{ service_cat_id: number; service_cat_name: string }>;
  }>('on-demand/get-service-list', {
    provider_id: providerId,
    access_token: accessToken,
  });
  if (data.status !== 1 || !data.service_category_list) {
    return [];
  }
  return data.service_category_list.map((s) => ({
    service_category_id: s.service_cat_id,
    service_category_name: s.service_cat_name,
    service_category_icon: undefined,
  }));
}

export async function fetchAddressList(
  userId: number,
  accessToken: string
): Promise<AddressItem[]> {
  const data = await apiPost<{ status: number; address_list?: AddressItem[] }>(
    'customer/address-list',
    { user_id: userId, access_token: accessToken }
  );
  if (data.status !== 1 || !data.address_list) {
    return [];
  }
  return data.address_list;
}
