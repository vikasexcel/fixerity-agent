import { StateGraph, START, END, MemorySaver } from "@langchain/langgraph";
import { SellerAgentState } from "./sellerState.js";
import {
  gatherSellerInfoNode,
  routeAfterGather,
} from "../agents/sellerAgent.js";

/**
 * Build the Seller Agent V2 graph with memory checkpointing.
 *
 * Graph flow:
 *   START -> gatherSellerInfo -> (conditional) -> END
 *
 * The graph is invoked once per user message. The MemorySaver checkpointer
 * persists state across invocations using thread_id, so conversation history,
 * collected info, and seller profile state all survive between API calls.
 */
function buildSellerGraph() {
  const checkpointer = new MemorySaver();

  const graph = new StateGraph(SellerAgentState)
    .addNode("gatherSellerInfo", gatherSellerInfoNode)
    .addEdge(START, "gatherSellerInfo")
    .addConditionalEdges("gatherSellerInfo", routeAfterGather, {
      __end__: END,
    });

  const compiledGraph = graph.compile({ checkpointer });

  return compiledGraph;
}

export { buildSellerGraph };
