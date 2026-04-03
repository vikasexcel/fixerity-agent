import express from "express";
import {
  listConversations,
  getConversationByThreadId,
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

export default router;
