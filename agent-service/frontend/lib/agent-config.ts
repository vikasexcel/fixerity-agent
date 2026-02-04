/**
 * Agent Configuration Storage Utilities
 * Manages seller agent configuration (provider details used for job matching)
 * Now uses database storage via API instead of localStorage
 */

import { getProviderServiceData, updateAgentConfig as updateAgentConfigApi, type ProviderServiceData } from './provider-api';
import { getAccessToken } from './auth-context';

export interface AgentConfig {
  average_rating: number;
  total_completed_order: number;
  num_of_rating: number;
  licensed: boolean;
  package_list: Array<{
    package_id?: number;
    package_name?: string;
    package_description?: string;
    package_price?: number;
    max_book_quantity?: number;
  }>;
}

/**
 * Get agent configuration for a provider from database via API
 */
export async function getAgentConfig(
  providerId: number,
  accessToken: string,
  serviceCategoryId: number
): Promise<AgentConfig | null> {
  try {
    const serviceData = await getProviderServiceData(providerId, accessToken, serviceCategoryId);
    
    // Convert database format to AgentConfig format
    return {
      average_rating: serviceData.average_rating ?? 0,
      total_completed_order: serviceData.total_completed_order ?? 0,
      num_of_rating: serviceData.num_of_rating ?? 0,
      licensed: serviceData.licensed ?? false,
      package_list: serviceData.package_list ?? [],
    };
  } catch (err) {
    console.error('Failed to get agent config from database:', err);
    return null;
  }
}

/**
 * Save agent configuration for a provider to database via API
 */
export async function saveAgentConfig(
  providerId: number,
  accessToken: string,
  serviceCategoryId: number,
  config: AgentConfig
): Promise<void> {
  try {
    await updateAgentConfigApi({
      provider_id: providerId,
      access_token: accessToken,
      service_category_id: serviceCategoryId,
      average_rating: config.average_rating,
      total_completed_order: config.total_completed_order,
      num_of_rating: config.num_of_rating,
      licensed: config.licensed,
      package_list: config.package_list,
    });
  } catch (err) {
    console.error('Failed to save agent config to database:', err);
    throw err;
  }
}

/**
 * Get agent configuration in format expected by seller agent
 * Matches the structure used in sellerMatchAgent.js lines 412-418
 * Note: provider_name should be fetched from provider data, not agent config
 * This is now async and requires API call
 */
export async function getAgentConfigForAgent(
  providerId: number,
  accessToken: string,
  serviceCategoryId: number,
  providerName?: string
): Promise<{
  provider_id: number;
  provider_name: string;
  average_rating: number;
  total_completed_order: number;
  num_of_rating: number;
  package_list: unknown[];
  licensed: boolean;
} | null> {
  const config = await getAgentConfig(providerId, accessToken, serviceCategoryId);
  if (!config) return null;

  return {
    provider_id: providerId,
    provider_name: providerName || 'Provider',
    average_rating: config.average_rating,
    total_completed_order: config.total_completed_order,
    num_of_rating: config.num_of_rating,
    package_list: config.package_list || [],
    licensed: config.licensed,
  };
}

/**
 * Delete agent configuration for a provider (reset to defaults)
 * Sets all agent config fields to null/0
 */
export async function deleteAgentConfig(
  providerId: number,
  accessToken: string,
  serviceCategoryId: number
): Promise<void> {
  try {
    await updateAgentConfigApi({
      provider_id: providerId,
      access_token: accessToken,
      service_category_id: serviceCategoryId,
      average_rating: null,
      total_completed_order: 0,
      num_of_rating: 0,
      licensed: false,
      package_list: [],
    });
  } catch (err) {
    console.error('Failed to delete agent config:', err);
    throw err;
  }
}
