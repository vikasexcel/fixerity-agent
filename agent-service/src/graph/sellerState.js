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
  // Used to avoid re-asking and to fill skipped items when generating the profile
  profileAnswers: Annotation({
    reducer: (prev, next) => (next ? { ...prev, ...next } : prev),
    default: () => ({}),
  }),
  // Matched jobs for seller (after profile confirmed), from scoreJobsForSeller
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
