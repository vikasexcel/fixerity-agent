import { HumanMessage, AIMessage } from "@langchain/core/messages";
import { prisma } from "../lib/prisma.js";

const LOG_TAG = "[BuyerThreadState]";

function serializeMessages(messages) {
  if (!Array.isArray(messages)) return [];
  return messages.map((m) => ({
    type: m?._getType?.() || "unknown",
    content: typeof m?.content === "string" ? m.content : "",
  }));
}

function deserializeMessages(serialized) {
  if (!Array.isArray(serialized)) return [];
  return serialized.map((m) => {
    const content = typeof m?.content === "string" ? m.content : "";
    if (m.type === "human") return new HumanMessage(content);
    if (m.type === "ai") return new AIMessage(content);
    // Fallback: treat unknown as AI message so the conversation still makes sense
    return new AIMessage(content);
  });
}

export async function saveBuyerThreadState(threadId, graph) {
  try {
    const state = await graph.getState({ configurable: { thread_id: threadId } });
    if (!state?.values) {
      console.warn(LOG_TAG, "saveBuyerThreadState: no state values to persist", { threadId });
      return;
    }

    const values = state.values;

    if (!prisma || !prisma.buyerThreadState || typeof prisma.buyerThreadState.upsert !== "function") {
      console.error(LOG_TAG, "saveBuyerThreadState: prisma buyerThreadState delegate missing or invalid", {
        threadId,
        hasPrisma: !!prisma,
        hasBuyerThreadState: !!prisma?.buyerThreadState,
        buyerThreadStateType: typeof prisma?.buyerThreadState,
      });
      return;
    }
    const payload = {
      threadId,
      status: values.status ?? "gathering",
      messages: serializeMessages(values.messages ?? []),
      questionCount: values.questionCount ?? 0,
      domainQuestionCount: values.domainQuestionCount ?? 0,
      domainPhaseComplete: values.domainPhaseComplete === true,
      profileAnswers: values.profileAnswers ?? {},
      jobPost: typeof values.jobPost === "string" ? values.jobPost : null,
      placeholders: values.placeholders ?? [],
      sellerDecisions: values.sellerDecisions ?? {},
    matchedSellers: values.matchedSellers ?? null,
    matchingStatus: values.matchingStatus ?? null,
    };

    await prisma.buyerThreadState.upsert({
      where: { threadId },
      update: payload,
      create: {
        id: undefined,
        ...payload,
      },
    });

    console.log(LOG_TAG, "saveBuyerThreadState: persisted state", {
      threadId,
      status: payload.status,
      messagesCount: payload.messages.length,
    });
  } catch (err) {
    console.error(
      LOG_TAG,
      "saveBuyerThreadState: failed to persist state",
      {
        threadId,
        error: err instanceof Error ? err.message : String(err),
      }
    );
  }
}

export async function loadBuyerThreadStateIntoGraph(threadId, graph) {
  try {
    console.log(LOG_TAG, "loadBuyerThreadStateIntoGraph: loading from DB", { threadId });

    const record = await prisma.buyerThreadState.findUnique({
      where: { threadId },
    });

    if (!record) {
      console.log(LOG_TAG, "loadBuyerThreadStateIntoGraph: no record found", { threadId });
      return false;
    }

    const messages = deserializeMessages(record.messages ?? []);

    await graph.updateState(
      { configurable: { thread_id: threadId } },
      {
        messages,
        status: record.status,
        questionCount: record.questionCount,
        domainQuestionCount: record.domainQuestionCount,
        domainPhaseComplete: record.domainPhaseComplete,
        profileAnswers: record.profileAnswers ?? {},
        jobPost: record.jobPost ?? null,
        placeholders: record.placeholders ?? [],
        sellerDecisions: record.sellerDecisions ?? {},
        matchedSellers: record.matchedSellers ?? null,
        matchingStatus: record.matchingStatus ?? null,
      }
    );

    console.log(LOG_TAG, "loadBuyerThreadStateIntoGraph: restored into graph", {
      threadId,
      status: record.status,
      messagesCount: messages.length,
    });

    return true;
  } catch (err) {
    console.error(LOG_TAG, "loadBuyerThreadStateIntoGraph: failed to restore", {
      threadId,
      error: err instanceof Error ? err.message : String(err),
    });
    return false;
  }
}

