import { Annotation } from "@langchain/langgraph";

const BuyerAgentState = Annotation.Root({
  // Full conversation history (LangChain message objects)
  messages: Annotation({
    reducer: (prev, next) => [...prev, ...next],
    default: () => [],
  }),
  // The generated job post as free-form text (null until created)
  jobPost: Annotation({
    reducer: (_prev, next) => next,
    default: () => null,
  }),
  // List of placeholder strings found in the job post
  placeholders: Annotation({
    reducer: (_prev, next) => next,
    default: () => [],
  }),
  // Current phase: "gathering" | "reviewing" | "confirmed" | "done"
  // "reviewing" = job post generated, waiting for buyer confirmation
  // "confirmed" = buyer confirmed, job created & embedded
  status: Annotation({
    reducer: (_prev, next) => next,
    default: () => "gathering",
  }),
  // How many questions the AI has asked
  questionCount: Annotation({
    reducer: (_prev, next) => next,
    default: () => 0,
  }),
  // Number of exchanges classified as domain (no profile question matched)
  domainQuestionCount: Annotation({
    reducer: (prev, next) => (next != null ? (prev ?? 0) + next : prev ?? 0),
    default: () => 0,
  }),
  // True once we have asked enough domain questions (Phase 2 started)
  domainPhaseComplete: Annotation({
    reducer: (_prev, next) => next,
    default: () => false,
  }),
  // Profile question answers/skips: { [questionId]: string | "skip" }
  // Used to avoid re-asking and to fill skipped items when generating the job post
  profileAnswers: Annotation({
    reducer: (prev, next) => (next ? { ...prev, ...next } : prev),
    default: () => ({}),
  }),
  // Structured metadata extracted from the job post for embedding
  jobMetadata: Annotation({
    reducer: (_prev, next) => next,
    default: () => null,
  }),
  // Pinecone vector ID after embedding (null until embedded)
  embeddingId: Annotation({
    reducer: (_prev, next) => next,
    default: () => null,
  }),
  // Matched seller profiles after job is published (search → rerank → LLM score)
  matchedSellers: Annotation({
    reducer: (_prev, next) => next,
    default: () => null,
  }),
  // "found" | "error" (frontend uses loading state for "searching")
  matchingStatus: Annotation({
    reducer: (_prev, next) => next,
    default: () => null,
  }),
  // Buyer decisions per profile: { [profileId]: "approved" | "rejected" | "contacted" }
  sellerDecisions: Annotation({
    reducer: (prev, next) => (next ? { ...prev, ...next } : prev),
    default: () => ({}),
  }),
});

export { BuyerAgentState };
