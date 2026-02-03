/**
 * Auth API: customer and provider (on-demand) login/register against Laravel backend.
 */

import { apiPost } from './api';

const defaults = {
  device_token: 'web',
  select_language: 'en',
  select_country_code: '+1',
  select_currency: 'USD',
  login_device: 1,
} as const;

export type AuthLoginPayload = {
  email: string;
  password: string;
  select_country_code?: string;
  select_currency?: string;
  select_language?: string;
  device_token?: string;
  login_device?: number;
};

export type CustomerRegisterPayload = {
  email: string;
  password: string;
  full_name: string;
  contact_number: string;
  select_country_code?: string;
  select_currency?: string;
  select_language?: string;
  device_token?: string;
  login_device?: number;
  refer_code?: string;
};

export type ProviderRegisterPayload = {
  email: string;
  password: string;
  full_name: string;
  contact_number: string;
  gender: 1 | 2;
  select_country_code?: string;
  select_currency?: string;
  select_language?: string;
  device_token?: string;
  login_device?: number;
};

export type CustomerAuthResult = {
  success: true;
  access_token: string;
  user_id: number;
  user_name: string;
  email: string;
  [key: string]: unknown;
};

export type ProviderAuthResult = {
  success: true;
  access_token: string;
  provider_id: number;
  provider_name: string;
  email: string;
  [key: string]: unknown;
};

type ApiAuthResponse = {
  status: number;
  message?: string;
  message_code?: number;
  access_token?: string;
  user_id?: number;
  user_name?: string;
  provider_id?: number;
  provider_name?: string;
  email?: string;
  [key: string]: unknown;
};

function checkStatus(data: ApiAuthResponse): void {
  if (data.status !== 1) {
    throw new Error(data.message ?? 'Request failed');
  }
}

export async function customerLogin(payload: AuthLoginPayload): Promise<CustomerAuthResult> {
  const body = {
    login_type: 'email',
    email: payload.email,
    password: payload.password,
    device_token: payload.device_token ?? defaults.device_token,
    select_language: payload.select_language ?? defaults.select_language,
    select_country_code: payload.select_country_code ?? defaults.select_country_code,
    select_currency: payload.select_currency ?? defaults.select_currency,
    login_device: payload.login_device ?? defaults.login_device,
  };
  const data = await apiPost<ApiAuthResponse>('customer/login', body);
  checkStatus(data);
  return {
    success: true,
    access_token: data.access_token!,
    user_id: data.user_id!,
    user_name: data.user_name ?? '',
    email: data.email ?? payload.email,
    ...data,
  };
}

export async function customerRegister(payload: CustomerRegisterPayload): Promise<CustomerAuthResult> {
  const body = {
    email: payload.email,
    password: payload.password,
    full_name: payload.full_name,
    contact_number: payload.contact_number,
    device_token: payload.device_token ?? defaults.device_token,
    select_language: payload.select_language ?? defaults.select_language,
    select_country_code: payload.select_country_code ?? defaults.select_country_code,
    select_currency: payload.select_currency ?? defaults.select_currency,
    login_device: payload.login_device ?? defaults.login_device,
    ...(payload.refer_code != null && payload.refer_code !== '' && { refer_code: payload.refer_code }),
  };
  const data = await apiPost<ApiAuthResponse>('customer/register', body);
  checkStatus(data);
  return {
    success: true,
    access_token: data.access_token!,
    user_id: data.user_id!,
    user_name: data.user_name ?? '',
    email: data.email ?? payload.email,
    ...data,
  };
}

export async function providerLogin(payload: AuthLoginPayload): Promise<ProviderAuthResult> {
  const body = {
    login_type: 'email',
    email: payload.email,
    password: payload.password,
    device_token: payload.device_token ?? defaults.device_token,
    select_language: payload.select_language ?? defaults.select_language,
    select_country_code: payload.select_country_code ?? defaults.select_country_code,
    select_currency: payload.select_currency ?? defaults.select_currency,
    login_device: payload.login_device ?? defaults.login_device,
  };
  const data = await apiPost<ApiAuthResponse>('on-demand/login', body);
  checkStatus(data);
  return {
    success: true,
    access_token: data.access_token!,
    provider_id: data.provider_id!,
    provider_name: data.provider_name ?? '',
    email: data.email ?? payload.email,
    ...data,
  };
}

export async function providerRegister(payload: ProviderRegisterPayload): Promise<ProviderAuthResult> {
  const body = {
    email: payload.email,
    password: payload.password,
    full_name: payload.full_name,
    contact_number: payload.contact_number,
    gender: payload.gender,
    device_token: payload.device_token ?? defaults.device_token,
    select_language: payload.select_language ?? defaults.select_language,
    select_country_code: payload.select_country_code ?? defaults.select_country_code,
    select_currency: payload.select_currency ?? defaults.select_currency,
    login_device: payload.login_device ?? defaults.login_device,
  };
  const data = await apiPost<ApiAuthResponse>('on-demand/register', body);
  checkStatus(data);
  return {
    success: true,
    access_token: data.access_token!,
    provider_id: data.provider_id!,
    provider_name: data.provider_name ?? '',
    email: data.email ?? payload.email,
    ...data,
  };
}
