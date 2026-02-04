/**
 * Agent Configuration Storage Utilities
 * Manages seller agent configuration (provider details used for job matching)
 */

export interface AgentConfig {
  provider_name: string;
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
 * Get agent configuration for a provider from localStorage
 */
export function getAgentConfig(providerId: number): AgentConfig | null {
  if (typeof window === 'undefined') return null;
  try {
    const key = `agent_config_${providerId}`;
    const stored = window.localStorage.getItem(key);
    if (!stored) return null;
    return JSON.parse(stored) as AgentConfig;
  } catch {
    return null;
  }
}

/**
 * Save agent configuration for a provider to localStorage
 */
export function saveAgentConfig(providerId: number, config: AgentConfig): void {
  if (typeof window === 'undefined') return;
  try {
    const key = `agent_config_${providerId}`;
    window.localStorage.setItem(key, JSON.stringify(config));
  } catch (err) {
    console.error('Failed to save agent config:', err);
  }
}

/**
 * Get agent configuration in format expected by seller agent
 * Matches the structure used in sellerMatchAgent.js lines 412-418
 */
export function getAgentConfigForAgent(providerId: number): {
  provider_id: number;
  provider_name: string;
  average_rating: number;
  total_completed_order: number;
  num_of_rating: number;
  package_list: unknown[];
  licensed: boolean;
} | null {
  const config = getAgentConfig(providerId);
  if (!config) return null;

  return {
    provider_id: providerId,
    provider_name: config.provider_name,
    average_rating: config.average_rating,
    total_completed_order: config.total_completed_order,
    num_of_rating: config.num_of_rating,
    package_list: config.package_list || [],
    licensed: config.licensed,
  };
}

/**
 * Delete agent configuration for a provider
 */
export function deleteAgentConfig(providerId: number): void {
  if (typeof window === 'undefined') return;
  try {
    const key = `agent_config_${providerId}`;
    window.localStorage.removeItem(key);
  } catch (err) {
    console.error('Failed to delete agent config:', err);
  }
}
