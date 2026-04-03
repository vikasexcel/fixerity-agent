import { prisma } from "./prisma.js";

/**
 * Upsert a conversation record.
 * Creates a new record if none exists for threadId; updates title/status/stateSnapshot otherwise.
 *
 * @param {object} params
 * @param {string} params.threadId
 * @param {"buyer"|"seller"} params.agentType
 * @param {string} [params.title]
 * @param {string} [params.status]
 * @param {object|null} [params.stateSnapshot]
 * @returns {Promise<object>}
 */
export async function upsertConversation({
  threadId,
  agentType,
  title,
  status,
  stateSnapshot = null,
}) {
  return prisma.conversation.upsert({
    where: { threadId },
    create: {
      threadId,
      agentType,
      title: title || "New conversation",
      status: status || "gathering",
      stateSnapshot,
    },
    update: {
      ...(title != null ? { title } : {}),
      ...(status != null ? { status } : {}),
      stateSnapshot,
    },
  });
}

/**
 * Append a message to a conversation.
 *
 * @param {string} conversationId  - Prisma conversation PK
 * @param {"user"|"assistant"} role
 * @param {string} content
 * @returns {Promise<object>}
 */
export async function addMessage(conversationId, role, content) {
  return prisma.conversationMessage.create({
    data: { conversationId, role, content },
  });
}

/**
 * List all conversations for a given agent type, ordered newest-first.
 *
 * @param {"buyer"|"seller"} agentType
 * @returns {Promise<Array>}
 */
export async function listConversations(agentType) {
  return prisma.conversation.findMany({
    where: { agentType },
    orderBy: { updatedAt: "desc" },
    select: {
      id: true,
      threadId: true,
      title: true,
      status: true,
      agentType: true,
      createdAt: true,
      updatedAt: true,
    },
  });
}

/**
 * Update the title of a conversation.
 * Throws if no conversation exists for the given threadId.
 *
 * @param {string} threadId
 * @param {string} title
 * @returns {Promise<object>}
 */
export async function updateConversationTitle(threadId, title) {
  return prisma.conversation.update({
    where: { threadId },
    data: { title },
  });
}

/**
 * Get a single conversation with its messages and state snapshot.
 *
 * @param {string} threadId
 * @returns {Promise<object|null>}
 */
export async function getConversationByThreadId(threadId) {
  return prisma.conversation.findUnique({
    where: { threadId },
    include: {
      messages: {
        orderBy: { createdAt: "asc" },
        select: { role: true, content: true, createdAt: true },
      },
    },
  });
}
