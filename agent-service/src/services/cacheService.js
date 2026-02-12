import { cacheRepository } from "../../prisma/repositories/cacheRepository.js";

export const cacheService = {
  /**
   * Get or fetch service categories
   */
  async getServiceCategories(fetchFn, accessToken) {
    const cacheKey = 'service_categories:all';

    // Try cache first
    const cached = await cacheRepository.get(cacheKey);
    if (cached) {
      console.log('[CacheService] Service categories cache hit');
      return cached;
    }

    // Fetch from API
    console.log('[CacheService] Service categories cache miss, fetching...');
    const categories = await fetchFn(accessToken);

    if (categories) {
      // Cache for 24 hours
      await cacheRepository.set(cacheKey, categories, 86400);
    }

    return categories;
  },

  /**
   * Get or fetch provider details
   */
  async getProviderDetails(providerId, fetchFn) {
    const cacheKey = `provider:${providerId}:details`;

    const cached = await cacheRepository.get(cacheKey);
    if (cached) {
      console.log(`[CacheService] Provider ${providerId} cache hit`);
      return cached;
    }

    console.log(`[CacheService] Provider ${providerId} cache miss, fetching...`);
    const details = await fetchFn(providerId);

    if (details) {
      // Cache for 24 hours
      await cacheRepository.set(cacheKey, details, 86400);
    }

    return details;
  },

  /**
   * Get or fetch provider basic info
   */
  async getProviderBasic(providerId, fetchFn) {
    const cacheKey = `provider:${providerId}:basic`;

    const cached = await cacheRepository.get(cacheKey);
    if (cached) {
      console.log(`[CacheService] Provider ${providerId} basic cache hit`);
      return cached;
    }

    console.log(`[CacheService] Provider ${providerId} basic cache miss, fetching...`);
    const basic = await fetchFn(providerId);

    if (basic) {
      await cacheRepository.set(cacheKey, basic, 86400);
    }

    return basic;
  },

  /**
   * Batch cache providers
   */
  async batchCacheProviders(providers) {
    const promises = providers.map(provider => {
      const providerId = provider.provider_id || provider.id;
      const cacheKey = `provider:${providerId}:details`;
      return cacheRepository.set(cacheKey, provider, 86400);
    });

    await Promise.all(promises);
    console.log(`[CacheService] Cached ${providers.length} providers`);
  },

  /**
   * Invalidate cache by key
   */
  async invalidate(key) {
    await cacheRepository.delete(key);
    console.log(`[CacheService] Invalidated cache: ${key}`);
  },

  /**
   * Invalidate provider cache
   */
  async invalidateProvider(providerId) {
    await this.invalidate(`provider:${providerId}:details`);
    await this.invalidate(`provider:${providerId}:basic`);
  },

  /**
   * Invalidate service categories
   */
  async invalidateServiceCategories() {
    await this.invalidate('service_categories:all');
  },

  /**
   * Get cache statistics
   */
  async getStats() {
    return await cacheRepository.getStatistics();
  },

  /**
   * Clear all cache
   */
  async clearAll() {
    await cacheRepository.clear();
    console.log('[CacheService] Cleared all cache');
  },

  /**
   * Clean up expired cache entries
   */
  async cleanupExpired() {
    const result = await cacheRepository.deleteExpired();
    console.log(`[CacheService] Cleaned up ${result.count} expired cache entries`);
    return result;
  },

  /**
   * Generic get-or-fetch pattern
   */
  async getOrFetch(key, fetchFn, ttlSeconds = 86400) {
    const cached = await cacheRepository.get(key);
    if (cached) {
      console.log(`[CacheService] Cache hit: ${key}`);
      return cached;
    }

    console.log(`[CacheService] Cache miss: ${key}, fetching...`);
    const data = await fetchFn();

    if (data) {
      await cacheRepository.set(key, data, ttlSeconds);
    }

    return data;
  },
};