import { Annotation } from "@langchain/langgraph";

const SellerAgentState = Annotation.Root({
  // Full conversation history (LangChain message objects)
  messages: Annotation({
    reducer: (prev, next) => [...prev, ...next],
    default: () => [],
  }),
  // The generated seller profile as free-form text (null until created)
  sellerProfile: Annotation({
    reducer: (_prev, next) => next,
    default: () => null,
  }),
  // List of placeholder strings found in the profile
  placeholders: Annotation({
    reducer: (_prev, next) => next,
    default: () => [],
  }),
  // Current phase: "gathering" | "reviewing" | "confirmed" | "done"
  status: Annotation({
    reducer: (_prev, next) => next,
    default: () => "gathering",
  }),
  // How many questions the AI has asked
  questionCount: Annotation({
    reducer: (_prev, next) => next,
    default: () => 0,
  }),
  // Pinecone vector ID after embedding (null until embedded)
  embeddingId: Annotation({
    reducer: (_prev, next) => next,
    default: () => null,
  }),
  // Structured metadata for the embedded profile (for embedding)
  profileMetadata: Annotation({
    reducer: (_prev, next) => next,
    default: () => null,
  }),
  // Matched jobs for seller (after profile confirmed)
  matchedJobs: Annotation({
    reducer: (_prev, next) => next,
    default: () => null,
  }),
  // "found" | "error" | null
  jobMatchingStatus: Annotation({
    reducer: (_prev, next) => next,
    default: () => null,
  }),
});

export { SellerAgentState };
