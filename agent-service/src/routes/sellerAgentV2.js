import express from "express";
import { v4 as uuidv4 } from "uuid";
import { HumanMessage } from "@langchain/core/messages";
import { buildSellerGraph } from "../graph/sellerGraph.js";
import { PROFILE_MARKER } from "../agents/sellerAgent.js";
import {
  upsertConversation,
  addMessage,
  getConversationByThreadId,
} from "../db/conversationRepository.js";

const router = express.Router();

const graph = buildSellerGraph();

const LOG_TAG = "[SellerRoute]";

/**
 * Get current graph state for a thread.
 */
async function getGraphState(threadId) {
  return graph.getState({ configurable: { thread_id: threadId } });
}

/**
 * Clean the AI response for the API consumer.
 * Removes the internal marker so the client gets clean text.
 */
function cleanMessage(content) {
  return content.replace(PROFILE_MARKER, "").trim();
}

/**
 * Serialize the agent state for DB storage.
 * Converts LangChain message objects to plain {role, content} objects.
 */
function serializeState(state) {
  if (!state) return null;
  const { messages, ...rest } = state;
  return {
    ...rest,
    messages: (messages || []).map((m) => ({
      role: m._getType() === "human" ? "user" : "assistant",
      content: cleanMessage(m.content),
    })),
  };
}

/**
 * POST /seller-agentv2/start
 * Start a new conversation. The seller provides their initial description of what they offer.
 * Body: { message } (optional — if omitted, a generic prompt is used)
 * Returns { threadId, message, status }
 */
router.post("/start", async (req, res) => {
  try {
    const threadId = uuidv4();
    const userMessage =
      req.body.message ||
      "I want to create a seller profile. Help me get started.";

    console.log(LOG_TAG, "start", { threadId });

    const result = await graph.invoke(
      {
        messages: [new HumanMessage(userMessage)],
      },
      { configurable: { thread_id: threadId } }
    );

    const lastMessage = result.messages[result.messages.length - 1];
    const assistantContent = cleanMessage(lastMessage.content);

    console.log(LOG_TAG, "start result", { threadId, status: result.status });

    // Persist conversation + both messages to DB
    try {
      const conv = await upsertConversation({
        threadId,
        agentType: "seller",
        title: userMessage.slice(0, 60),
        status: result.status,
        stateSnapshot: serializeState(result),
      });
      await addMessage(conv.id, "user", userMessage);
      await addMessage(conv.id, "assistant", assistantContent);
    } catch (dbErr) {
      console.error(LOG_TAG, "DB persist error on start:", dbErr);
    }

    res.json({
      threadId,
      message: assistantContent,
      status: result.status,
    });
  } catch (error) {
    console.error(LOG_TAG, "Error starting seller conversation:", error);
    res.status(500).json({ error: "Failed to start conversation" });
  }
});

/**
 * POST /seller-agentv2/chat
 * Continue a conversation.
 * Body: { threadId, message }
 * Returns { threadId, message, status, sellerProfile, placeholders }
 */
router.post("/chat", async (req, res) => {
  try {
    const { threadId, message } = req.body;

    if (!threadId) {
      return res.status(400).json({ error: "threadId is required" });
    }
    if (!message) {
      return res.status(400).json({ error: "message is required" });
    }

    const currentState = await getGraphState(threadId);
    const currentStatus = currentState?.values?.status;

    console.log(LOG_TAG, "chat", { threadId, statusBefore: currentStatus });

    let result;

    if (currentStatus === "reviewing") {
      console.log(LOG_TAG, "chat resuming from interrupt (reviewing)");
      await graph.updateState(
        { configurable: { thread_id: threadId } },
        { messages: [new HumanMessage(message)] }
      );
      result = await graph.invoke(null, {
        configurable: { thread_id: threadId },
      });
    } else {
      result = await graph.invoke(
        {
          messages: [new HumanMessage(message)],
        },
        { configurable: { thread_id: threadId } }
      );
    }

    const lastMessage = result.messages[result.messages.length - 1];
    const assistantContent = cleanMessage(lastMessage.content);

    console.log(LOG_TAG, "chat result", { threadId, statusAfter: result.status });

    // Persist to DB
    try {
      const conv = await upsertConversation({
        threadId,
        agentType: "seller",
        status: result.status,
        stateSnapshot: serializeState(result),
      });
      await addMessage(conv.id, "user", message);
      await addMessage(conv.id, "assistant", assistantContent);
    } catch (dbErr) {
      console.error(LOG_TAG, "DB persist error on chat:", dbErr);
    }

    const response = {
      threadId,
      message: assistantContent,
      status: result.status,
    };

    if (result.sellerProfile) {
      response.sellerProfile = result.sellerProfile;
      response.placeholders = result.placeholders;
    }
    if (result.matchedJobs != null) {
      response.matchedJobs = result.matchedJobs;
    }
    if (result.jobMatchingStatus != null) {
      response.jobMatchingStatus = result.jobMatchingStatus;
    }
    if (result.embeddingId != null) {
      response.embeddingId = result.embeddingId;
    }

    res.json(response);
  } catch (error) {
    console.error(LOG_TAG, "Error in seller chat:", error);
    res.status(500).json({ error: "Failed to process message" });
  }
});

/**
 * GET /seller-agentv2/state/:threadId
 * Get the full state of a conversation.
 * First tries the live LangGraph in-memory state; falls back to the DB snapshot.
 */
router.get("/state/:threadId", async (req, res) => {
  try {
    const { threadId } = req.params;

    // Try live graph state first
    const state = await graph.getState({
      configurable: { thread_id: threadId },
    });
    const hasLiveState = state?.values?.messages?.length > 0;

    if (hasLiveState) {
      const messages = state.values.messages.map((m) => ({
        role: m._getType() === "human" ? "user" : "assistant",
        content: cleanMessage(m.content),
      }));

      const response = {
        threadId,
        messages,
        status: state.values.status,
        questionCount: state.values.questionCount,
        profileAnswers: state.values.profileAnswers ?? {},
        domainPhaseComplete: state.values.domainPhaseComplete === true,
      };

      if (state.values.sellerProfile) {
        response.sellerProfile = state.values.sellerProfile;
        response.placeholders = state.values.placeholders;
      }
      if (state.values.matchedJobs != null) {
        response.matchedJobs = state.values.matchedJobs;
      }
      if (state.values.jobMatchingStatus != null) {
        response.jobMatchingStatus = state.values.jobMatchingStatus;
      }
      if (state.values.embeddingId != null) {
        response.embeddingId = state.values.embeddingId;
      }
      if (state.values.profileMetadata != null) {
        response.profileMetadata = state.values.profileMetadata;
      }

      return res.json(response);
    }

    // Fallback: load from DB snapshot (server restarted / in-memory state lost)
    const conv = await getConversationByThreadId(threadId);
    if (!conv) {
      return res.status(404).json({ error: "Thread not found" });
    }

    const snapshot = conv.stateSnapshot || {};
    const response = {
      threadId,
      messages: conv.messages.map((m) => ({ role: m.role, content: m.content })),
      status: conv.status,
      questionCount: snapshot.questionCount ?? 0,
      profileAnswers: snapshot.profileAnswers ?? {},
      domainPhaseComplete: snapshot.domainPhaseComplete === true,
    };

    if (snapshot.sellerProfile) {
      response.sellerProfile = snapshot.sellerProfile;
      response.placeholders = snapshot.placeholders;
    }
    if (snapshot.matchedJobs != null) {
      response.matchedJobs = snapshot.matchedJobs;
    }
    if (snapshot.jobMatchingStatus != null) {
      response.jobMatchingStatus = snapshot.jobMatchingStatus;
    }
    if (snapshot.embeddingId != null) {
      response.embeddingId = snapshot.embeddingId;
    }
    if (snapshot.profileMetadata != null) {
      response.profileMetadata = snapshot.profileMetadata;
    }

    return res.json(response);
  } catch (error) {
    console.error(LOG_TAG, "Error getting seller state:", error);
    res.status(500).json({ error: "Failed to get conversation state" });
  }
});

export default router;
