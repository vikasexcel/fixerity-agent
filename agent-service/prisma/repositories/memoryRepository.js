import { prisma } from "../../src/primsadb.js";

export const memoryRepository = {
  /**
   * Store user memory/pattern (permanent)
   */
  async create({ userId, userType, memoryType, category, content, metadata, relevanceScore = 1.0 }) {
    return await prisma.userMemory.create({
      data: {
        userId,
        userType,
        memoryType,
        category,
        content,
        metadata,
        relevanceScore,
      },
    });
  },

  /**
   * Find memories by user
   */
  async findByUser(userId, userType, options = {}) {
    const where = {
      userId,
      userType,
    };

    if (options.memoryType) {
      where.memoryType = options.memoryType;
    }

    if (options.category) {
      where.category = options.category;
    }

    return await prisma.userMemory.findMany({
      where,
      orderBy: { relevanceScore: 'desc' },
      take: options.limit || 10,
    });
  },

  /**
   * Search memories by content
   */
  async search(userId, userType, searchTerm, limit = 10) {
    return await prisma.userMemory.findMany({
      where: {
        userId,
        userType,
        content: {
          contains: searchTerm,
          mode: 'insensitive',
        },
      },
      orderBy: { relevanceScore: 'desc' },
      take: limit,
    });
  },

  /**
   * Update memory relevance score
   */
  async updateRelevance(id, relevanceScore) {
    return await prisma.userMemory.update({
      where: { id },
      data: { 
        relevanceScore,
        updatedAt: new Date(),
      },
    });
  },

  /**
   * Update memory content
   */
  async update(id, data) {
    return await prisma.userMemory.update({
      where: { id },
      data: {
        ...data,
        updatedAt: new Date(),
      },
    });
  },

  /**
   * Delete memory
   */
  async delete(id) {
    return await prisma.userMemory.delete({
      where: { id },
    });
  },

  /**
   * Get memory statistics
   */
  async getStatistics(userId, userType) {
    const [total, byType, byCategory, avgRelevance] = await Promise.all([
      prisma.userMemory.count({
        where: { userId, userType },
      }),
      prisma.userMemory.groupBy({
        by: ['memoryType'],
        where: { userId, userType },
        _count: true,
      }),
      prisma.userMemory.groupBy({
        by: ['category'],
        where: { userId, userType, category: { not: null } },
        _count: true,
      }),
      prisma.userMemory.aggregate({
        where: { userId, userType },
        _avg: { relevanceScore: true },
      }),
    ]);

    return {
      total,
      byType,
      byCategory,
      avgRelevanceScore: avgRelevance._avg.relevanceScore || 0,
    };
  },

  /**
   * Optional: Cleanup low-relevance memories older than N days
   * (only if you want to prune very old, irrelevant data)
   */
  async cleanupLowRelevance(threshold = 0.3, olderThanDays = 180) {
    const cutoffDate = new Date();
    cutoffDate.setDate(cutoffDate.getDate() - olderThanDays);

    return await prisma.userMemory.deleteMany({
      where: {
        relevanceScore: { lt: threshold },
        createdAt: { lt: cutoffDate },
      },
    });
  },
};