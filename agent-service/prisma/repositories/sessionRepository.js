import { prisma } from "../../src/primsadb.js";

export const sessionRepository = {
  /**
   * Create a new conversation session
   */
  async create({ userId, userType, accessToken, phase = 'conversation', state = {} }) {
    return await prisma.conversationSession.create({
      data: {
        userId,
        userType,
        accessToken,
        phase,
        state,
        isActive: true,
      },
    });
  },

  /**
   * Find session by ID
   */
  async findById(sessionId) {
    return await prisma.conversationSession.findUnique({
      where: { id: sessionId },
      include: {
        messages: {
          orderBy: { createdAt: 'desc' },
          take: 50, // Last 50 messages
        },
      },
    });
  },

  /**
   * Find all sessions by user (for resume functionality)
   */
  async findByUser(userId, userType, options = {}) {
    const where = {
      userId,
      userType,
    };

    if (options.activeOnly) {
      where.isActive = true;
    }

    return await prisma.conversationSession.findMany({
      where,
      orderBy: { updatedAt: 'desc' },
      take: options.limit || 10,
      include: {
        messages: {
          orderBy: { createdAt: 'desc' },
          take: 5, // Preview last 5 messages
        },
      },
    });
  },

  /**
   * Find most recent active session by user
   */
  async findMostRecentActive(userId, userType) {
    return await prisma.conversationSession.findFirst({
      where: {
        userId,
        userType,
        isActive: true,
      },
      orderBy: { updatedAt: 'desc' },
      include: {
        messages: {
          orderBy: { createdAt: 'desc' },
          take: 50,
        },
      },
    });
  },

  /**
   * Update session state
   */
  async update(sessionId, data) {
    return await prisma.conversationSession.update({
      where: { id: sessionId },
      data: {
        ...data,
        updatedAt: new Date(),
      },
    });
  },

  /**
   * Update phase
   */
  async updatePhase(sessionId, phase) {
    return await this.update(sessionId, { phase });
  },

  /**
   * Update state (merge with existing)
   */
  async updateState(sessionId, stateUpdates) {
    const session = await this.findById(sessionId);
    if (!session) throw new Error('Session not found');

    const newState = {
      ...session.state,
      ...stateUpdates,
    };

    return await this.update(sessionId, { state: newState });
  },

  /**
   * Mark session as inactive (soft delete)
   */
  async markInactive(sessionId) {
    return await this.update(sessionId, { isActive: false });
  },

  /**
   * Reactivate session
   */
  async reactivate(sessionId) {
    return await this.update(sessionId, { isActive: true });
  },

  /**
   * Delete session (hard delete - use carefully)
   */
  async delete(sessionId) {
    // Cascade delete will handle messages
    return await prisma.conversationSession.delete({
      where: { id: sessionId },
    });
  },

  /**
   * Get session analytics
   */
  async getAnalytics(userType = null) {
    const where = userType ? { userType } : {};

    const [total, active, byPhase, avgDuration] = await Promise.all([
      // Total sessions
      prisma.conversationSession.count({ where }),

      // Active sessions
      prisma.conversationSession.count({ 
        where: { ...where, isActive: true } 
      }),

      // Sessions by phase
      prisma.conversationSession.groupBy({
        by: ['phase'],
        where,
        _count: true,
      }),

      // Average session duration
      prisma.$queryRaw`
        SELECT AVG(EXTRACT(EPOCH FROM (updated_at - created_at))) as avg_seconds
        FROM conversation_sessions
        ${userType ? prisma.Prisma.sql`WHERE user_type = ${userType}` : prisma.Prisma.empty}
      `,
    ]);

    return {
      total,
      active,
      inactive: total - active,
      byPhase,
      avgDurationSeconds: avgDuration[0]?.avg_seconds || 0,
    };
  },

  /**
   * Find inactive sessions older than N days (for optional cleanup)
   */
  async findInactiveOlderThan(days) {
    const cutoffDate = new Date();
    cutoffDate.setDate(cutoffDate.getDate() - days);

    return await prisma.conversationSession.findMany({
      where: {
        isActive: false,
        updatedAt: { lt: cutoffDate },
      },
    });
  },

  /**
   * Delete inactive sessions older than N days (optional cleanup)
   */
  async deleteInactiveOlderThan(days) {
    const cutoffDate = new Date();
    cutoffDate.setDate(cutoffDate.getDate() - days);

    return await prisma.conversationSession.deleteMany({
      where: {
        isActive: false,
        updatedAt: { lt: cutoffDate },
      },
    });
  },
};