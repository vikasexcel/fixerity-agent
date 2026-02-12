import { prisma } from "../../src/primsadb.js";

export const messageRepository = {
  /**
   * Add message to session
   */
  async create({ sessionId, role, content, metadata = null }) {
    return await prisma.conversationMessage.create({
      data: {
        sessionId,
        role,
        content,
        metadata,
      },
    });
  },

  /**
   * Get all messages for session
   */
  async findBySession(sessionId, options = {}) {
    return await prisma.conversationMessage.findMany({
      where: { sessionId },
      orderBy: { createdAt: options.ascending ? 'asc' : 'desc' },
      take: options.limit,
      skip: options.skip || 0,
    });
  },

  /**
   * Get last N messages (in chronological order)
   */
  async getLastN(sessionId, n = 10) {
    const messages = await prisma.conversationMessage.findMany({
      where: { sessionId },
      orderBy: { createdAt: 'desc' },
      take: n,
    });
    return messages.reverse(); // Return in chronological order
  },

  /**
   * Get messages with pagination
   */
  async getPaginated(sessionId, page = 1, pageSize = 50) {
    const skip = (page - 1) * pageSize;

    const [messages, total] = await Promise.all([
      prisma.conversationMessage.findMany({
        where: { sessionId },
        orderBy: { createdAt: 'asc' },
        skip,
        take: pageSize,
      }),
      prisma.conversationMessage.count({
        where: { sessionId },
      }),
    ]);

    return {
      messages,
      pagination: {
        page,
        pageSize,
        total,
        totalPages: Math.ceil(total / pageSize),
        hasMore: skip + messages.length < total,
      },
    };
  },

  /**
   * Count messages in session
   */
  async count(sessionId) {
    return await prisma.conversationMessage.count({
      where: { sessionId },
    });
  },

  /**
   * Delete all messages for session
   */
  async deleteBySession(sessionId) {
    return await prisma.conversationMessage.deleteMany({
      where: { sessionId },
    });
  },

  /**
   * Search messages
   */
  async search(sessionId, searchTerm, options = {}) {
    return await prisma.conversationMessage.findMany({
      where: {
        sessionId,
        content: {
          contains: searchTerm,
          mode: 'insensitive',
        },
      },
      orderBy: { createdAt: options.ascending ? 'asc' : 'desc' },
      take: options.limit || 50,
    });
  },

  /**
   * Get messages by role
   */
  async findByRole(sessionId, role) {
    return await prisma.conversationMessage.findMany({
      where: {
        sessionId,
        role,
      },
      orderBy: { createdAt: 'asc' },
    });
  },

  /**
   * Get conversation context (recent messages formatted)
   */
  async getContext(sessionId, limit = 10) {
    const messages = await this.getLastN(sessionId, limit);
    
    return messages.map(m => ({
      role: m.role,
      content: m.content,
      timestamp: m.createdAt,
    }));
  },
};