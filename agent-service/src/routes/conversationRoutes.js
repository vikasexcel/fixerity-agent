import express from "express";
import {
  listConversations,
  getConversationByThreadId,
  updateConversationTitle,
} from "../db/conversationRepository.js";

const router = express.Router();

/**
 * GET /conversations?agentType=buyer|seller
 * List all conversations for the given agent type, newest first.
 * Returns: [{ id, threadId, title, status, agentType, createdAt, updatedAt }]
 */
router.get("/", async (req, res) => {
  try {
    const { agentType } = req.query;
    if (!agentType || !["buyer", "seller"].includes(agentType)) {
      return res
        .status(400)
        .json({ error: "agentType query param must be 'buyer' or 'seller'" });
    }
    const conversations = await listConversations(agentType);
    res.json({ conversations });
  } catch (error) {
    console.error("[ConversationRoutes] Error listing conversations:", error);
    res.status(500).json({ error: "Failed to list conversations" });
  }
});

/**
 * GET /conversations/:threadId
 * Get a single conversation with its full message history and state snapshot.
 * Returns: { id, threadId, title, status, agentType, messages[], stateSnapshot, ... }
 */
router.get("/:threadId", async (req, res) => {
  try {
    const { threadId } = req.params;
    const conversation = await getConversationByThreadId(threadId);
    if (!conversation) {
      return res.status(404).json({ error: "Conversation not found" });
    }
    res.json({ conversation });
  } catch (error) {
    console.error("[ConversationRoutes] Error getting conversation:", error);
    res.status(500).json({ error: "Failed to get conversation" });
  }
});

/**
 * PATCH /conversations/:threadId/title
 * Set a custom title for a conversation. The title will not be overwritten by
 * subsequent chat messages.
 * Body: { title: string }
 * Returns: { threadId, title }
 */
router.patch("/:threadId/title", async (req, res) => {
  try {
    const { threadId } = req.params;
    const { title } = req.body;

    if (!title || typeof title !== "string" || title.trim().length === 0) {
      return res.status(400).json({ error: "title must be a non-empty string" });
    }

    const conversation = await updateConversationTitle(threadId, title.trim());
    res.json({ threadId: conversation.threadId, title: conversation.title });
  } catch (error) {
    if (error.code === "P2025") {
      return res.status(404).json({ error: "Conversation not found" });
    }
    console.error("[ConversationRoutes] Error updating title:", error);
    res.status(500).json({ error: "Failed to update title" });
  }
});

export default router;
