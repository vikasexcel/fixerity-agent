import { sessionRepository } from "../../prisma/repositories/sessionRepository.js";
import { messageRepository } from "../../prisma/repositories/messageRepository.js";

export const sessionService = {
  /**
   * Create or resume session for user
   * - If forceNew is true: always create new session (for explicit "new chat")
   * - If active session exists and forceNew is false: return it
   * - Otherwise: create new session
   */
  async getOrCreateSession({ userId, userType, accessToken, forceNew = false }) {
    console.log('[SessionService] getOrCreateSession userId=', userId, 'userType=', userType, 'forceNew=', forceNew);
    // When user explicitly starts a new chat, always create
    if (!forceNew) {
      const existingSession = await sessionRepository.findMostRecentActive(userId, userType);
      if (existingSession) {
        console.log('[SessionService] getOrCreateSession resuming existing session id=', existingSession.id, 'phase=', existingSession.phase);
        return {
          session: existingSession,
          isNew: false,
          resumedFrom: existingSession.phase,
        };
      }
      console.log('[SessionService] getOrCreateSession no active session found');
    }

    // Create new session
    const initialState = this._getInitialState(userType);
    if (forceNew && userType === 'seller') {
      initialState.profileSessionScoped = true;
      console.log('[SessionService] getOrCreateSession seller forceNew set profileSessionScoped');
    }
    const newSession = await sessionRepository.create({
      userId,
      userType,
      accessToken,
      phase: userType === 'buyer' ? 'conversation' : 'profile_check',
      state: initialState,
    });

    console.log('[SessionService] getOrCreateSession created new session id=', newSession.id, 'phase=', newSession.phase);

    return {
      session: newSession,
      isNew: true,
    };
  },

  /**
   * Get initial state based on user type
   */
  _getInitialState(userType) {
    console.log('[SessionService] _getInitialState userType=', userType);
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
          slots: {
            intent_summary: null,
            service: null,
            scope: null,
            location: null,
            timeline: null,
            budget: null,
            deliverables: null,
            constraints: null,
          },
          assumptions: [],
          questionCount: 0,
          completion: {
            ready: false,
            confidence: 0,
            missingCritical: ['service', 'scope'],
            assumptions: [],
          },
        },
        // Deprecated compatibility fields kept for one release.
        requiredMissing: ['service_category', 'scope'],
        optionalMissing: ['budget_max', 'start_date', 'location', 'end_date'],
        jobReadiness: 'incomplete',
        completion: {
          ready: false,
          confidence: 0,
          missingCritical: ['service', 'scope'],
          assumptions: [],
        },
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
    console.log('[SessionService] updatePhase sessionId=', sessionId, 'newPhase=', newPhase);
    const session = await sessionRepository.findById(sessionId);
    if (!session) {
      console.log('[SessionService] updatePhase error session not found sessionId=', sessionId);
      throw new Error(`Session ${sessionId} not found`);
    }

    const oldPhase = session.phase;
    console.log('[SessionService] updatePhase oldPhase=', oldPhase, '-> newPhase=', newPhase);

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

    console.log('[SessionService] updatePhase done sessionId=', sessionId);

    return { oldPhase, newPhase };
  },

  /**
   * Update session state (merge with existing)
   */
  async updateState(sessionId, stateUpdates) {
    console.log('[SessionService] updateState sessionId=', sessionId, 'updateKeys=', Object.keys(stateUpdates || {}).join(', '));
    const session = await sessionRepository.findById(sessionId);
    if (!session) {
      console.log('[SessionService] updateState error session not found sessionId=', sessionId);
      throw new Error(`Session ${sessionId} not found`);
    }

    const currentState = session.state || {};
    const newState = this._deepMerge(currentState, stateUpdates);

    await sessionRepository.updateState(sessionId, newState);

    console.log('[SessionService] updateState done sessionId=', sessionId);

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
    console.log('[SessionService] getSessionWithContext sessionId=', sessionId, 'messageLimit=', messageLimit);
    const session = await sessionRepository.findById(sessionId);
    if (!session) {
      console.log('[SessionService] getSessionWithContext error session not found sessionId=', sessionId);
      throw new Error(`Session ${sessionId} not found`);
    }

    const messages = await messageRepository.getLastN(sessionId, messageLimit);
    console.log('[SessionService] getSessionWithContext done phase=', session.phase, 'messagesCount=', messages?.length ?? 0);

    return {
      ...session,
      messages,
    };
  },

  /**
   * Mark session as complete (set job reference, mark inactive)
   */
  async completeSession(sessionId, jobId = null) {
    console.log('[SessionService] completeSession sessionId=', sessionId, 'jobId=', jobId ?? '—');
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

    console.log('[SessionService] completeSession done sessionId=', sessionId);
  },

  /**
   * Restart session (mark old as inactive, create new)
   */
  async restartSession(oldSessionId, { userId, userType, accessToken }) {
    console.log('[SessionService] restartSession oldSessionId=', oldSessionId, 'userId=', userId, 'userType=', userType);
    // Mark old session as inactive
    await sessionRepository.markInactive(oldSessionId);
    console.log('[SessionService] restartSession old session marked inactive');

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

    console.log('[SessionService] restartSession done newSessionId=', newSession.id);

    return newSession;
  },

  /**
   * Get user's session history
   */
  async getUserSessions(userId, userType, options = {}) {
    console.log('[SessionService] getUserSessions userId=', userId, 'userType=', userType);
    const sessions = await sessionRepository.findByUser(userId, userType, {
      activeOnly: options.activeOnly || false,
      limit: options.limit || 10,
    });
    console.log('[SessionService] getUserSessions result count=', sessions?.length ?? 0);
    return sessions;
  },

  /**
   * Get session analytics
   */
  async getAnalytics(userType = null) {
    console.log('[SessionService] getAnalytics userType=', userType ?? 'all');
    return await sessionRepository.getAnalytics(userType);
  },

  /**
   * Check if session is valid and active
   */
  async validateSession(sessionId) {
    console.log('[SessionService] validateSession sessionId=', sessionId);
    const session = await sessionRepository.findById(sessionId);

    if (!session) {
      console.log('[SessionService] validateSession result valid=false reason=session_not_found');
      return { valid: false, reason: 'session_not_found' };
    }

    if (!session.isActive) {
      console.log('[SessionService] validateSession result valid=false reason=session_inactive');
      return { valid: false, reason: 'session_inactive' };
    }

    console.log('[SessionService] validateSession result valid=true');
    return { valid: true, session };
  },
};
