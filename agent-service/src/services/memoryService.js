import { memoryRepository } from "../../prisma/repositories/memoryRepository.js";

export const memoryService = {
  /**
   * Store buyer's negotiation pattern
   */
  async storeBuyerNegotiation({ buyerId, jobId, negotiationData }) {
    if (!negotiationData || typeof negotiationData !== 'object') {
      console.warn('[MemoryService] storeBuyerNegotiation skipped: negotiationData required');
      return null;
    }
    const { job, quote, providerId, outcome } = negotiationData;

    const content = `Buyer posted ${job.title} job with budget $${job.budget?.min || '?'}-$${job.budget?.max || '?'}. Provider ${providerId} quoted $${quote?.price || '?'} for ${quote?.days || '?'} days. Outcome: ${outcome}.`;

    const memory = await memoryRepository.create({
      userId: buyerId,
      userType: 'buyer',
      memoryType: 'negotiation',
      category: String(job.service_category_id),
      content,
      metadata: {
        job_id: jobId,
        provider_id: providerId,
        service_category: job.service_category_id,
        budget_min: job.budget?.min,
        budget_max: job.budget?.max,
        quoted_price: quote?.price,
        quoted_days: quote?.days,
        outcome,
        can_meet_dates: quote?.can_meet_dates,
        licensed: quote?.licensed,
        timestamp: Date.now(),
      },
      relevanceScore: outcome === 'accepted' ? 1.0 : 0.7,
    });

    console.log(`[MemoryService] Stored buyer negotiation memory for buyer ${buyerId}`);

    return memory;
  },

  /**
   * Store provider's negotiation pattern
   */
  async storeProviderNegotiation({ providerId, jobId, negotiationData }) {
    if (!negotiationData || typeof negotiationData !== 'object') {
      console.warn('[MemoryService] storeProviderNegotiation skipped: negotiationData required');
      return null;
    }
    const { job, quote, buyerId, outcome } = negotiationData;

    const content = `Provider quoted $${quote?.price || '?'} for ${job.title}. Job budget was $${job.budget?.max || '?'}. Timeline: ${job.startDate} to ${job.endDate}. Outcome: ${outcome}.`;

    const memory = await memoryRepository.create({
      userId: providerId,
      userType: 'provider',
      memoryType: 'negotiation',
      category: String(job.service_category_id),
      content,
      metadata: {
        job_id: jobId,
        buyer_id: buyerId,
        service_category: job.service_category_id,
        budget_offered: job.budget?.max,
        quoted_price: quote?.price,
        quoted_days: quote?.days,
        price_vs_budget_ratio: quote?.price / (job.budget?.max || 1),
        outcome,
        timestamp: Date.now(),
      },
      relevanceScore: outcome === 'accepted' ? 1.0 : 0.6,
    });

    console.log(`[MemoryService] Stored provider negotiation memory for provider ${providerId}`);

    return memory;
  },

  /**
   * Get buyer preferences for a service category
   */
  async getBuyerPreferences(buyerId, serviceCategory = null) {
    const memories = await memoryRepository.findByUser(buyerId, 'buyer', {
      memoryType: 'negotiation',
      category: serviceCategory,
      limit: 10,
    });

    if (memories.length === 0) {
      console.log(`[MemoryService] No memories found for buyer ${buyerId}`);
      return null;
    }

    const summary = this._summarizeBuyerPreferences(memories);

    console.log(`[MemoryService] Retrieved ${memories.length} buyer preferences`);

    return {
      memories,
      summary,
    };
  },

  /**
   * Get provider pricing patterns
   */
  async getProviderPattern(providerId, serviceCategory = null) {
    const memories = await memoryRepository.findByUser(providerId, 'provider', {
      memoryType: 'negotiation',
      category: serviceCategory,
      limit: 10,
    });

    if (memories.length === 0) {
      console.log(`[MemoryService] No memories found for provider ${providerId}`);
      return null;
    }

    const summary = this._summarizeProviderPattern(memories);

    console.log(`[MemoryService] Retrieved ${memories.length} provider patterns`);

    return {
      memories,
      summary,
    };
  },

  /**
   * Summarize buyer preferences from memories
   */
  _summarizeBuyerPreferences(memories) {
    const acceptedMemories = memories.filter(m => m.metadata.outcome === 'accepted');

    if (acceptedMemories.length === 0) {
      return {
        totalNegotiations: memories.length,
        acceptedNegotiations: 0,
        avgBudget: null,
        preferredQualities: [],
      };
    }

    const budgets = acceptedMemories
      .map(m => m.metadata.budget_max)
      .filter(b => b != null);

    const avgBudget = budgets.length > 0
      ? budgets.reduce((a, b) => a + b, 0) / budgets.length
      : null;

    const licensedCount = acceptedMemories.filter(m => m.metadata.licensed === true).length;
    const canMeetDatesCount = acceptedMemories.filter(m => m.metadata.can_meet_dates === true).length;

    const preferredQualities = [];
    if (licensedCount / acceptedMemories.length > 0.7) {
      preferredQualities.push('licensed');
    }
    if (canMeetDatesCount / acceptedMemories.length > 0.8) {
      preferredQualities.push('meets_dates');
    }

    return {
      totalNegotiations: memories.length,
      acceptedNegotiations: acceptedMemories.length,
      avgBudget: avgBudget ? Math.round(avgBudget) : null,
      preferredQualities,
    };
  },

  /**
   * Summarize provider pricing patterns
   */
  _summarizeProviderPattern(memories) {
    const quotedMemories = memories.filter(m => m.metadata.quoted_price != null);

    if (quotedMemories.length === 0) {
      return {
        totalQuotes: memories.length,
        avgQuoteRatio: null,
        acceptanceRate: 0,
      };
    }

    const ratios = quotedMemories
      .map(m => m.metadata.price_vs_budget_ratio)
      .filter(r => r != null && r > 0 && r < 2); // Filter outliers

    const avgQuoteRatio = ratios.length > 0
      ? ratios.reduce((a, b) => a + b, 0) / ratios.length
      : null;

    const acceptedCount = memories.filter(m => m.metadata.outcome === 'accepted').length;
    const acceptanceRate = acceptedCount / memories.length;

    return {
      totalQuotes: memories.length,
      avgQuoteRatio: avgQuoteRatio ? avgQuoteRatio.toFixed(2) : null,
      acceptanceRate: acceptanceRate.toFixed(2),
    };
  },

  /**
   * Get job recommendations based on past negotiations
   */
  async getJobRecommendations(buyerId, job) {
    const memories = await memoryRepository.findByUser(buyerId, 'buyer', {
      category: String(job.service_category_id),
      limit: 15,
    });

    if (memories.length === 0) {
      return null;
    }

    const recommendations = this._extractRecommendations(memories, job);

    console.log(`[MemoryService] Generated recommendations for buyer ${buyerId}`);

    return {
      memories,
      recommendations,
      confidence: memories.length > 5 ? 'high' : memories.length > 2 ? 'medium' : 'low',
    };
  },

  /**
   * Extract recommendations from memories
   */
  _extractRecommendations(memories, job) {
    const insights = [];

    // Budget insights
    const budgets = memories
      .map(m => m.metadata.budget_max)
      .filter(b => b != null);

    if (budgets.length > 0) {
      const avgBudget = budgets.reduce((a, b) => a + b, 0) / budgets.length;
      insights.push({
        type: 'budget',
        insight: `Based on past jobs, your typical budget is around $${Math.round(avgBudget)}`,
        relevance: 0.9,
      });
    }

    // Quality preferences
    const licensedPreference = memories.filter(m =>
      m.metadata.outcome === 'accepted' && m.metadata.licensed === true
    ).length / memories.length;

    if (licensedPreference > 0.7) {
      insights.push({
        type: 'quality',
        insight: 'You typically prefer licensed providers',
        relevance: 0.8,
      });
    }

    return {
      based_on_negotiations: memories.length,
      key_insights: insights,
    };
  },

  /**
   * Update outcome after buyer accepts/rejects
   */
  async updateNegotiationOutcome(buyerId, jobId, providerId, outcome) {
    // Find the memory
    const memories = await memoryRepository.search(buyerId, 'buyer', jobId, 5);

    for (const memory of memories) {
      if (memory.metadata.job_id === jobId && memory.metadata.provider_id === providerId) {
        // Update metadata with final outcome
        const updatedMetadata = {
          ...memory.metadata,
          final_outcome: outcome,
          outcome_updated_at: Date.now(),
        };

        // Update relevance score
        const newRelevanceScore = outcome === 'accepted' ? 1.0 : 0.5;

        await memoryRepository.update(memory.id, {
          metadata: updatedMetadata,
          relevanceScore: newRelevanceScore,
        });

        console.log(`[MemoryService] Updated outcome for memory ${memory.id}: ${outcome}`);
        break;
      }
    }
  },

  /**
   * Get memory statistics
   */
  async getMemoryStats(userId, userType) {
    return await memoryRepository.getStatistics(userId, userType);
  },

  /**
   * Clean up low-relevance memories (optional)
   */
  async cleanupLowRelevance(threshold = 0.3, olderThanDays = 180) {
    const result = await memoryRepository.cleanupLowRelevance(threshold, olderThanDays);
    console.log(`[MemoryService] Cleaned up ${result.count} low-relevance memories`);
    return result;
  },
};