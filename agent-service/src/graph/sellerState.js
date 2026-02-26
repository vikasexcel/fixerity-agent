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
  // Current phase: "gathering" | "done"
  status: Annotation({
    reducer: (_prev, next) => next,
    default: () => "gathering",
  }),
  // How many questions the AI has asked
  questionCount: Annotation({
    reducer: (_prev, next) => next,
    default: () => 0,
  }),
});

export { SellerAgentState };
