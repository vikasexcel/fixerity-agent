import { messageRepository } from "../../prisma/repositories/messageRepository.js";
import { sessionRepository } from "../../prisma/repositories/sessionRepository.js";

export const messageService = {
  /**
   * Add user message to session
   */
  async addUserMessage(sessionId, content, metadata = null) {
    // Validate session exists
    const session = await sessionRepository.findById(sessionId);
    if (!session) {
      throw new Error(`Session ${sessionId} not found`);
    }

    const message = await messageRepository.create({
      sessionId,
      role: 'user',
      content,
      metadata,
    });

    console.log(`[MessageService] User message added to session ${sessionId}`);

    return message;
  },

  /**
   * Add assistant message to session
   */
  async addAssistantMessage(sessionId, content, metadata = null) {
    const session = await sessionRepository.findById(sessionId);
    if (!session) {
      throw new Error(`Session ${sessionId} not found`);
    }

    const message = await messageRepository.create({
      sessionId,
      role: 'assistant',
      content,
      metadata,
    });

    console.log(`[MessageService] Assistant message added to session ${sessionId}`);

    return message;
  },

  /**
   * Add system message to session
   */
  async addSystemMessage(sessionId, content, metadata = null) {
    const message = await messageRepository.create({
      sessionId,
      role: 'system',
      content,
      metadata,
    });

    console.log(`[MessageService] System message added to session ${sessionId}`);

    return message;
  },

  /**
   * Get conversation context for LLM
   * Returns last N messages formatted for LLM consumption
   */
  async getConversationContext(sessionId, limit = 10) {
    const messages = await messageRepository.getLastN(sessionId, limit);

    return messages
      .filter(m => m.role !== 'system') // Exclude system messages from LLM context
      .map(m => ({
        role: m.role === 'user' ? 'user' : 'assistant',
        content: m.content,
      }));
  },

  /**
   * Get formatted conversation history for display
   */
  async getConversationHistory(sessionId, options = {}) {
    const limit = options.limit || 50;
    const includeSystem = options.includeSystem || false;

    let messages = await messageRepository.getLastN(sessionId, limit);

    if (!includeSystem) {
      messages = messages.filter(m => m.role !== 'system');
    }

    return messages.map(m => ({
      id: m.id,
      role: m.role,
      content: m.content,
      timestamp: m.createdAt,
      metadata: m.metadata,
    }));
  },

  /**
   * Get paginated conversation history
   */
  async getPaginatedHistory(sessionId, page = 1, pageSize = 50) {
    return await messageRepository.getPaginated(sessionId, page, pageSize);
  },

  /**
   * Search conversation for specific content
   */
  async searchConversation(sessionId, searchTerm, options = {}) {
    const messages = await messageRepository.search(sessionId, searchTerm, options);

    return messages.map(m => ({
      id: m.id,
      role: m.role,
      content: m.content,
      timestamp: m.createdAt,
      // Highlight search term (optional)
      highlighted: this._highlightSearchTerm(m.content, searchTerm),
    }));
  },

  /**
   * Highlight search term in content
   */
  _highlightSearchTerm(content, searchTerm) {
    const regex = new RegExp(`(${searchTerm})`, 'gi');
    return content.replace(regex, '**$1**');
  },

  /**
   * Get conversation statistics
   */
  async getConversationStats(sessionId) {
    const [total, byRole] = await Promise.all([
      messageRepository.count(sessionId),
      messageRepository.findByRole(sessionId, 'user'),
      messageRepository.findByRole(sessionId, 'assistant'),
      messageRepository.findByRole(sessionId, 'system'),
    ]);

    return {
      total,
      userMessages: byRole[0]?.length || 0,
      assistantMessages: byRole[1]?.length || 0,
      systemMessages: byRole[2]?.length || 0,
    };
  },

  /**
   * Export conversation as text
   */
  async exportConversation(sessionId, format = 'text') {
    const messages = await messageRepository.findBySession(sessionId, { ascending: true });

    if (format === 'json') {
      return JSON.stringify(messages, null, 2);
    }

    // Text format
    return messages
      .map(m => {
        const timestamp = new Date(m.createdAt).toLocaleString();
        return `[${timestamp}] ${m.role.toUpperCase()}: ${m.content}`;
      })
      .join('\n\n');
  },

  /**
   * Clear conversation history (soft delete via system message)
   */
  async clearHistory(sessionId) {
    await this.addSystemMessage(sessionId, 'Conversation history cleared by user', {
      type: 'history_clear',
    });

    // Note: We don't actually delete messages, just mark the clear event
    // If you want hard delete, use: await messageRepository.deleteBySession(sessionId);

    console.log(`[MessageService] Conversation history cleared for session ${sessionId}`);
  },

  /**
   * Get last message by role
   */
  async getLastMessageByRole(sessionId, role) {
    const messages = await messageRepository.findByRole(sessionId, role);
    return messages[messages.length - 1] || null;
  },

  /**
   * Count messages since last system message
   */
  async countMessagesSinceLastSystem(sessionId) {
    const messages = await messageRepository.findBySession(sessionId, { ascending: false });

    let count = 0;
    for (const msg of messages) {
      if (msg.role === 'system') break;
      count++;
    }

    return count;
  },
};