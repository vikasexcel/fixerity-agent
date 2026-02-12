import { prisma } from "../../src/primsadb.js";

export const negotiationRepository = {
  /**
   * Create negotiation session (no expiry)
   */
  async create({ jobId, providerId, buyerId, state = {} }) {
    return await prisma.negotiationSession.create({
      data: {
        jobId,
        providerId,
        buyerId,
        state,
        status: 'collecting',
      },
    });
  },

  /**
   * Find by job and provider
   */
  async findByJobAndProvider(jobId, providerId) {
    return await prisma.negotiationSession.findUnique({
      where: {
        jobId_providerId: {
          jobId,
          providerId,
        },
      },
      include: {
        messages: {
          orderBy: { createdAt: 'asc' },
        },
      },
    });
  },

  /**
   * Find or create negotiation
   */
  async findOrCreate({ jobId, providerId, buyerId, state = {} }) {
    let negotiation = await this.findByJobAndProvider(jobId, providerId);
    
    if (!negotiation) {
      negotiation = await this.create({ jobId, providerId, buyerId, state });
    }
    
    return negotiation;
  },

  /**
   * Update negotiation state
   */
  async update(id, data) {
    return await prisma.negotiationSession.update({
      where: { id },
      data: {
        ...data,
        updatedAt: new Date(),
      },
    });
  },

  /**
   * Update status
   */
  async updateStatus(id, status) {
    return await this.update(id, { status });
  },

  /**
   * Save quote
   */
  async saveQuote(id, quote) {
    return await this.update(id, { quote, status: 'done' });
  },

  /**
   * Add message
   */
  async addMessage(negotiationId, role, message) {
    return await prisma.negotiationMessage.create({
      data: {
        negotiationId,
        role,
        message,
      },
    });
  },

  /**
   * Get messages
   */
  async getMessages(negotiationId) {
    return await prisma.negotiationMessage.findMany({
      where: { negotiationId },
      orderBy: { createdAt: 'asc' },
    });
  },

  /**
   * Find all negotiations by job
   */
  async findByJob(jobId) {
    return await prisma.negotiationSession.findMany({
      where: { jobId },
      include: {
        messages: {
          orderBy: { createdAt: 'asc' },
        },
      },
      orderBy: { createdAt: 'desc' },
    });
  },

  /**
   * Find negotiations by provider
   */
  async findByProvider(providerId) {
    return await prisma.negotiationSession.findMany({
      where: { providerId },
      include: {
        messages: {
          orderBy: { createdAt: 'asc' },
        },
      },
      orderBy: { createdAt: 'desc' },
    });
  },

  /**
   * Find negotiations by buyer
   */
  async findByBuyer(buyerId) {
    return await prisma.negotiationSession.findMany({
      where: { buyerId },
      include: {
        messages: {
          orderBy: { createdAt: 'asc' },
        },
      },
      orderBy: { createdAt: 'desc' },
    });
  },

  /**
   * Delete negotiation
   */
  async delete(id) {
    return await prisma.negotiationSession.delete({
      where: { id },
    });
  },

  /**
   * Get negotiation statistics
   */
  async getStatistics(buyerId = null) {
    const where = buyerId ? { buyerId } : {};

    const [total, byStatus] = await Promise.all([
      prisma.negotiationSession.count({ where }),
      prisma.negotiationSession.groupBy({
        by: ['status'],
        where,
        _count: true,
      }),
    ]);

    return {
      total,
      byStatus,
    };
  },
};