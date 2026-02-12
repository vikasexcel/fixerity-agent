import { sessionRepository } from "../../prisma/repositories/sessionRepository.js";
import { messageRepository } from "../../prisma/repositories/messageRepository.js";

export const sessionService = {
  /**
   * Create or resume session for user
   * - If active session exists, return it
   * - Otherwise, create new session
   */
  async getOrCreateSession({ userId, userType, accessToken }) {
    // Check for existing active session
    const existingSession = await sessionRepository.findMostRecentActive(userId, userType);

    if (existingSession) {
      console.log(`[SessionService] Resuming session ${existingSession.id} for ${userType} ${userId}`);
      return {
        session: existingSession,
        isNew: false,
        resumedFrom: existingSession.phase,
      };
    }

    // Create new session
    const newSession = await sessionRepository.create({
      userId,
      userType,
      accessToken,
      phase: userType === 'buyer' ? 'conversation' : 'profile_check',
      state: this._getInitialState(userType),
    });

    console.log(`[SessionService] Created new session ${newSession.id} for ${userType} ${userId}`);

    return {
      session: newSession,
      isNew: true,
    };
  },

  /**
   * Get initial state based on user type
   */
  _getInitialState(userType) {
    if (userType === 'buyer') {
      return {
        collected: {
          service_category_id: null,
          service_category_name: null,
          title: null,
          description: null,
          budget: { min: null, max: null },
          startDate: null,
          endDate: null,
          priorities: [],
          location: null,
        },
        requiredMissing: ['service_category', 'budget_max', 'start_date', 'location'],
        optionalMissing: ['title', 'description', 'budget_min', 'end_date'],
        jobReadiness: 'incomplete',
      };
    } else {
      // Seller
      return {
        collected: {
          service_categories: [],
          service_area: null,
          availability: null,
          credentials: {
            licensed: null,
            insured: null,
            years_experience: null,
            references_available: null,
            certifications: [],
          },
          pricing: {
            hourly_rate_min: null,
            hourly_rate_max: null,
            fixed_prices: {},
          },
          preferences: {
            min_job_size_hours: null,
            max_travel_distance: null,
            provides_materials: null,
            preferred_payment: [],
          },
          bio: null,
        },
        requiredMissing: ['service_categories', 'service_area', 'availability', 'pricing'],
        optionalMissing: ['years_experience', 'licensed', 'references', 'bio', 'min_job_size'],
        profileReadiness: 'incomplete',
      };
    }
  },

  /**
   * Update session phase with transition logging
   */
  async updatePhase(sessionId, newPhase, metadata = {}) {
    const session = await sessionRepository.findById(sessionId);
    if (!session) {
      throw new Error(`Session ${sessionId} not found`);
    }

    const oldPhase = session.phase;

    await sessionRepository.updatePhase(sessionId, newPhase);

    // Log phase transition
    await messageRepository.create({
      sessionId,
      role: 'system',
      content: `Phase transition: ${oldPhase} → ${newPhase}`,
      metadata: {
        type: 'phase_transition',
        from: oldPhase,
        to: newPhase,
        ...metadata,
      },
    });

    console.log(`[SessionService] Session ${sessionId}: ${oldPhase} → ${newPhase}`);

    return { oldPhase, newPhase };
  },

  /**
   * Update session state (merge with existing)
   */
  async updateState(sessionId, stateUpdates) {
    const session = await sessionRepository.findById(sessionId);
    if (!session) {
      throw new Error(`Session ${sessionId} not found`);
    }

    const currentState = session.state || {};
    const newState = this._deepMerge(currentState, stateUpdates);

    await sessionRepository.updateState(sessionId, newState);

    console.log(`[SessionService] Updated state for session ${sessionId}`);

    return newState;
  },

  /**
   * Deep merge two objects
   */
  _deepMerge(target, source) {
    const result = { ...target };

    for (const key in source) {
      if (source[key] && typeof source[key] === 'object' && !Array.isArray(source[key])) {
        result[key] = this._deepMerge(target[key] || {}, source[key]);
      } else {
        result[key] = source[key];
      }
    }

    return result;
  },

  /**
   * Get session with full context (session + recent messages)
   */
  async getSessionWithContext(sessionId, messageLimit = 50) {
    const session = await sessionRepository.findById(sessionId);
    if (!session) {
      throw new Error(`Session ${sessionId} not found`);
    }

    const messages = await messageRepository.getLastN(sessionId, messageLimit);

    return {
      ...session,
      messages,
    };
  },

  /**
   * Mark session as complete (set job reference, mark inactive)
   */
  async completeSession(sessionId, jobId = null) {
    await sessionRepository.update(sessionId, {
      jobId,
      phase: 'complete',
      isActive: false,
    });

    await messageRepository.create({
      sessionId,
      role: 'system',
      content: `Session completed${jobId ? ` - Job created: ${jobId}` : ''}`,
      metadata: {
        type: 'session_complete',
        jobId,
      },
    });

    console.log(`[SessionService] Session ${sessionId} marked as complete`);
  },

  /**
   * Restart session (mark old as inactive, create new)
   */
  async restartSession(oldSessionId, { userId, userType, accessToken }) {
    // Mark old session as inactive
    await sessionRepository.markInactive(oldSessionId);

    await messageRepository.create({
      sessionId: oldSessionId,
      role: 'system',
      content: 'Session restarted by user',
      metadata: { type: 'session_restart' },
    });

    // Create new session
    const newSession = await sessionRepository.create({
      userId,
      userType,
      accessToken,
      phase: userType === 'buyer' ? 'conversation' : 'profile_check',
      state: this._getInitialState(userType),
    });

    console.log(`[SessionService] Restarted session: ${oldSessionId} → ${newSession.id}`);

    return newSession;
  },

  /**
   * Get user's session history
   */
  async getUserSessions(userId, userType, options = {}) {
    return await sessionRepository.findByUser(userId, userType, {
      activeOnly: options.activeOnly || false,
      limit: options.limit || 10,
    });
  },

  /**
   * Get session analytics
   */
  async getAnalytics(userType = null) {
    return await sessionRepository.getAnalytics(userType);
  },

  /**
   * Check if session is valid and active
   */
  async validateSession(sessionId) {
    const session = await sessionRepository.findById(sessionId);

    if (!session) {
      return { valid: false, reason: 'session_not_found' };
    }

    if (!session.isActive) {
      return { valid: false, reason: 'session_inactive' };
    }

    return { valid: true, session };
  },
};