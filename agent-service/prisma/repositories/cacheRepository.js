import { prisma } from "../../src/primsadb.js";

// Cache KEEPS expiry for performance optimization
export const cacheRepository = {
  /**
   * Get cached value
   */
  async get(key) {
    const entry = await prisma.cacheEntry.findUnique({
      where: { key },
    });

    if (!entry) return null;

    // Check if expired
    if (entry.expiresAt < new Date()) {
      await this.delete(key);
      return null;
    }

    return entry.value;
  },

  /**
   * Set cached value
   */
  async set(key, value, ttlSeconds = 86400) {
    const expiresAt = new Date(Date.now() + ttlSeconds * 1000);

    return await prisma.cacheEntry.upsert({
      where: { key },
      create: {
        key,
        value,
        expiresAt,
      },
      update: {
        value,
        expiresAt,
        updatedAt: new Date(),
      },
    });
  },

  /**
   * Delete cached value
   */
  async delete(key) {
    return await prisma.cacheEntry.delete({
      where: { key },
    }).catch(() => null); // Ignore if not found
  },

  /**
   * Delete expired cache entries (run as cron job)
   */
  async deleteExpired() {
    return await prisma.cacheEntry.deleteMany({
      where: {
        expiresAt: { lt: new Date() },
      },
    });
  },

  /**
   * Clear all cache
   */
  async clear() {
    return await prisma.cacheEntry.deleteMany();
  },

  /**
   * Get cache statistics
   */
  async getStatistics() {
    const [total, expired] = await Promise.all([
      prisma.cacheEntry.count(),
      prisma.cacheEntry.count({
        where: { expiresAt: { lt: new Date() } },
      }),
    ]);

    return {
      total,
      active: total - expired,
      expired,
    };
  },
};