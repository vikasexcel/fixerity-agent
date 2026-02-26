import express from "express";
import { v4 as uuidv4 } from "uuid";
import { HumanMessage } from "@langchain/core/messages";
import { buildBuyerGraph } from "../graph/buyerGraph.js";

const router = express.Router();

const graph = buildBuyerGraph();

const JOB_POST_MARKER = "---JOB_POST_READY---";

/**
 * Clean the AI response for the API consumer.
 * Removes the internal marker so the client gets clean text.
 */
function cleanMessage(content) {
  return content.replace(JOB_POST_MARKER, "").trim();
}

/**
 * Helper to get the current graph state for a thread.
 */
async function getGraphState(threadId) {
  return graph.getState({ configurable: { thread_id: threadId } });
}

/**
 * POST /buyer-agentv2/start
 * Start a new conversation. The buyer provides their initial description of what they need.
 * Body: { message } (optional — if omitted, a generic prompt is used)
 * Returns { threadId, message, status }
 */
router.post("/start", async (req, res) => {
  try {
    const threadId = uuidv4();
    const userMessage =
      req.body.message ||
      "I want to create a job post. Help me get started.";

    const result = await graph.invoke(
      {
        messages: [new HumanMessage(userMessage)],
      },
      { configurable: { thread_id: threadId } }
    );

    const lastMessage = result.messages[result.messages.length - 1];

    res.json({
      threadId,
      message: cleanMessage(lastMessage.content),
      status: result.status,
    });
  } catch (error) {
    console.error("Error starting conversation:", error);
    res.status(500).json({ error: "Failed to start conversation" });
  }
});

/**
 * POST /buyer-agentv2/chat
 * Continue a conversation.
 * Handles both normal messages and human-in-the-loop resume.
 *
 * When status is "reviewing" (graph is interrupted before reviewJobPost):
 *   - Adds buyer's message to state
 *   - Resumes the graph by invoking with null (continues from interrupt)
 *
 * When status is "gathering" (normal flow):
 *   - Sends message through the graph as usual
 *
 * Body: { threadId, message }
 * Returns { threadId, message, status, jobPost?, placeholders? }
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

    // Check current state to see if we're at an interrupt point
    const currentState = await getGraphState(threadId);
    const currentStatus = currentState?.values?.status;

    let result;

    if (currentStatus === "reviewing") {
      // Graph is interrupted before reviewJobPost — resume with buyer's input.
      // First, add the buyer's message to the state so reviewJobPostNode can see it
      await graph.updateState(
        { configurable: { thread_id: threadId } },
        { messages: [new HumanMessage(message)] }
      );

      // Resume the graph from the interrupt point (invoke with null)
      result = await graph.invoke(null, {
        configurable: { thread_id: threadId },
      });
    } else {
      // Normal flow — send the message through the graph
      result = await graph.invoke(
        {
          messages: [new HumanMessage(message)],
        },
        { configurable: { thread_id: threadId } }
      );
    }

    const lastMessage = result.messages[result.messages.length - 1];

    const response = {
      threadId,
      message: cleanMessage(lastMessage.content),
      status: result.status,
    };

    // Include jobPost fields when a post has been generated
    if (result.jobPost) {
      response.jobPost = result.jobPost;
      response.placeholders = result.placeholders;
    }

    res.json(response);
  } catch (error) {
    console.error("Error in chat:", error);
    res.status(500).json({ error: "Failed to process message" });
  }
});

/**
 * GET /buyer-agentv2/state/:threadId
 * Get the full state of a conversation.
 */
router.get("/state/:threadId", async (req, res) => {
  try {
    const { threadId } = req.params;

    const state = await getGraphState(threadId);

    if (!state || !state.values || !state.values.messages) {
      return res.status(404).json({ error: "Thread not found" });
    }

    const messages = state.values.messages.map((m) => ({
      role: m._getType() === "human" ? "user" : "assistant",
      content: cleanMessage(m.content),
    }));

    const response = {
      threadId,
      messages,
      status: state.values.status,
      questionCount: state.values.questionCount,
    };

    if (state.values.jobPost) {
      response.jobPost = state.values.jobPost;
      response.placeholders = state.values.placeholders;
    }

    res.json(response);
  } catch (error) {
    console.error("Error getting state:", error);
    res.status(500).json({ error: "Failed to get conversation state" });
  }
});

export default router;
