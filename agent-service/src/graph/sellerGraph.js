import { StateGraph, START, END, MemorySaver } from "@langchain/langgraph";
import { SellerAgentState } from "./sellerState.js";
import {
  gatherSellerInfoNode,
  routeAfterGather,
  reviewSellerProfileNode,
  routeAfterReview,
  createProfileNode,
} from "../agents/sellerAgent.js";

/**
 * Build the Seller Agent V2 graph with human-in-the-loop and memory checkpointing.
 *
 * Graph flow:
 *   START -> gatherSellerInfo -> (conditional) -> reviewSellerProfile [INTERRUPT] -> (conditional) -> createProfile -> END
 *                                \-> END (still gathering)                    \-> gatherSellerInfo (seller wants more details)
 *
 * When the profile is generated, the graph pauses BEFORE reviewSellerProfile.
 * The seller reviews and sends a message (confirm or add more details); the route
 * adds that message to state and resumes, so reviewSellerProfileNode runs with it.
 * On confirm, createProfile embeds the profile in Pinecone (namespace seller-profile).
 */
function buildSellerGraph() {
  const checkpointer = new MemorySaver();

  const graph = new StateGraph(SellerAgentState)
    .addNode("gatherSellerInfo", gatherSellerInfoNode)
    .addNode("reviewSellerProfile", reviewSellerProfileNode)
    .addNode("createProfile", createProfileNode)
    .addEdge(START, "gatherSellerInfo")
    .addConditionalEdges("gatherSellerInfo", routeAfterGather, {
      reviewSellerProfile: "reviewSellerProfile",
      __end__: END,
    })
    .addConditionalEdges("reviewSellerProfile", routeAfterReview, {
      createProfile: "createProfile",
      gatherSellerInfo: "gatherSellerInfo",
    })
    .addEdge("createProfile", END);

  const compiledGraph = graph.compile({
    checkpointer,
    interruptBefore: ["reviewSellerProfile"],
  });

  return compiledGraph;
}

export { buildSellerGraph };
