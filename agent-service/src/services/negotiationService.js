import { negotiationRepository } from "../../prisma/repositories/negotiationRepository.js";

export const negotiationService = {
  /**
   * Start negotiation between buyer and provider
   */
  async startNegotiation({ jobId, providerId, buyerId, initialState = {} }) {
    // Check if negotiation already exists
    const existing = await negotiationRepository.findByJobAndProvider(jobId, providerId);

    if (existing) {
      console.log(`[NegotiationService] Resuming existing negotiation for job ${jobId}, provider ${providerId}`);
      return {
        negotiation: existing,
        isNew: false,
      };
    }

    // Create new negotiation
    const negotiation = await negotiationRepository.create({
      jobId,
      providerId,
      buyerId,
      state: {
        round: 0,
        maxRounds: initialState.maxRounds || 1,
        deadline_ts: initialState.deadline_ts || Date.now() + 3600000, // 1 hour default
        ...initialState,
      },
    });

    console.log(`[NegotiationService] Started negotiation ${negotiation.id} for job ${jobId}`);

    return {
      negotiation,
      isNew: true,
    };
  },

  /**
   * Add message to negotiation
   */
  async addMessage(negotiationId, role, message) {
    await negotiationRepository.addMessage(negotiationId, role, message);

    // Update negotiation updated_at timestamp
    await negotiationRepository.update(negotiationId, {});

    console.log(`[NegotiationService] ${role} message added to negotiation ${negotiationId}`);
  },

  /**
   * Update negotiation state
   */
  async updateState(negotiationId, stateUpdates) {
    const negotiation = await negotiationRepository.findByJobAndProvider(
      stateUpdates.jobId,
      stateUpdates.providerId
    );

    if (!negotiation) {
      throw new Error(`Negotiation ${negotiationId} not found`);
    }

    const newState = {
      ...negotiation.state,
      ...stateUpdates,
    };

    await negotiationRepository.update(negotiationId, { state: newState });

    console.log(`[NegotiationService] State updated for negotiation ${negotiationId}`);

    return newState;
  },

  /**
   * Save final quote and mark as done
   */
  async saveQuote(negotiationId, quote) {
    await negotiationRepository.saveQuote(negotiationId, quote);

    console.log(`[NegotiationService] Quote saved for negotiation ${negotiationId}: $${quote.price}`);

    return quote;
  },

  /**
   * Mark negotiation as timeout
   */
  async markAsTimeout(negotiationId) {
    await negotiationRepository.updateStatus(negotiationId, 'timeout');

    await negotiationRepository.addMessage(
      negotiationId,
      'system',
      'Negotiation timed out'
    );

    console.log(`[NegotiationService] Negotiation ${negotiationId} marked as timeout`);
  },

  /**
   * Get negotiation with full context
   */
  async getNegotiationWithContext(jobId, providerId) {
    const negotiation = await negotiationRepository.findByJobAndProvider(jobId, providerId);

    if (!negotiation) {
      return null;
    }

    return {
      ...negotiation,
      transcript: negotiation.messages.map(m => ({
        role: m.role,
        message: m.message,
        timestamp: m.createdAt,
      })),
    };
  },

  /**
   * Get all negotiations for a job
   */
  async getNegotiationsByJob(jobId) {
    const negotiations = await negotiationRepository.findByJob(jobId);

    return negotiations.map(n => ({
      id: n.id,
      providerId: n.providerId,
      status: n.status,
      quote: n.quote,
      messageCount: n.messages.length,
      createdAt: n.createdAt,
      updatedAt: n.updatedAt,
    }));
  },

  /**
   * Get negotiation summary for provider
   */
  async getProviderNegotiations(providerId) {
    const negotiations = await negotiationRepository.findByProvider(providerId);

    return negotiations.map(n => ({
      id: n.id,
      jobId: n.jobId,
      buyerId: n.buyerId,
      status: n.status,
      quote: n.quote,
      messageCount: n.messages.length,
      createdAt: n.createdAt,
    }));
  },

  /**
   * Get negotiation statistics for buyer
   */
  async getBuyerNegotiationStats(buyerId) {
    const stats = await negotiationRepository.getStatistics(buyerId);

    return {
      total: stats.total,
      byStatus: stats.byStatus.reduce((acc, item) => {
        acc[item.status] = item._count;
        return acc;
      }, {}),
    };
  },

  /**
   * Check if negotiation is expired (for cleanup)
   */
  async isExpired(negotiationId, jobId, providerId) {
    const negotiation = await negotiationRepository.findByJobAndProvider(jobId, providerId);

    if (!negotiation) return true;

    const deadlineTs = negotiation.state?.deadline_ts;
    if (!deadlineTs) return false;

    return Date.now() > deadlineTs;
  },

  /**
   * Increment negotiation round
   */
  async incrementRound(negotiationId, jobId, providerId) {
    const negotiation = await negotiationRepository.findByJobAndProvider(jobId, providerId);

    if (!negotiation) {
      throw new Error('Negotiation not found');
    }

    const newState = {
      ...negotiation.state,
      round: (negotiation.state.round || 0) + 1,
    };

    await negotiationRepository.update(negotiationId, { state: newState });

    return newState.round;
  },
};