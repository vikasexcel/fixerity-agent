import { HumanMessage, AIMessage } from "@langchain/core/messages";
import { prisma } from "../lib/prisma.js";

const LOG_TAG = "[SellerThreadState]";

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

export async function saveSellerThreadState(threadId, graph) {
  try {
    const state = await graph.getState({ configurable: { thread_id: threadId } });
    if (!state?.values) {
      console.warn(LOG_TAG, "saveSellerThreadState: no state values to persist", { threadId });
      return;
    }

    const values = state.values;

    if (!prisma || !prisma.sellerThreadState || typeof prisma.sellerThreadState.upsert !== "function") {
      console.error(LOG_TAG, "saveSellerThreadState: prisma sellerThreadState delegate missing or invalid", {
        threadId,
        hasPrisma: !!prisma,
        hasSellerThreadState: !!prisma?.sellerThreadState,
        sellerThreadStateType: typeof prisma?.sellerThreadState,
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
      sellerProfile: typeof values.sellerProfile === "string" ? values.sellerProfile : null,
      placeholders: values.placeholders ?? [],
      matchedJobs: Array.isArray(values.matchedJobs) ? values.matchedJobs : null,
      matchingStatus: values.jobMatchingStatus ?? null,
    };

    await prisma.sellerThreadState.upsert({
      where: { threadId },
      update: payload,
      create: {
        id: undefined,
        ...payload,
      },
    });

    console.log(LOG_TAG, "saveSellerThreadState: persisted state", {
      threadId,
      status: payload.status,
      messagesCount: payload.messages.length,
    });
  } catch (err) {
    console.error(LOG_TAG, "saveSellerThreadState: failed to persist state", {
      threadId,
      error: err instanceof Error ? err.message : String(err),
    });
  }
}

export async function loadSellerThreadStateIntoGraph(threadId, graph) {
  try {
    console.log(LOG_TAG, "loadSellerThreadStateIntoGraph: loading from DB", { threadId });

    const record = await prisma.sellerThreadState.findUnique({
      where: { threadId },
    });

    if (!record) {
      console.log(LOG_TAG, "loadSellerThreadStateIntoGraph: no record found", { threadId });
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
        sellerProfile: record.sellerProfile ?? null,
        placeholders: record.placeholders ?? [],
        matchedJobs: Array.isArray(record.matchedJobs) ? record.matchedJobs : null,
        jobMatchingStatus: record.matchingStatus ?? null,
      }
    );

    console.log(LOG_TAG, "loadSellerThreadStateIntoGraph: restored into graph", {
      threadId,
      status: record.status,
      messagesCount: messages.length,
    });

    return true;
  } catch (err) {
    console.error(LOG_TAG, "loadSellerThreadStateIntoGraph: failed to restore", {
      threadId,
      error: err instanceof Error ? err.message : String(err),
    });
    return false;
  }
}

