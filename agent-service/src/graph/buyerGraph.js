import { StateGraph, START, END, MemorySaver } from "@langchain/langgraph";
import { BuyerAgentState } from "./state.js";
import { gatherInfoNode, routeAfterGather } from "../agents/buyerAgent.js";

/**
 * Build the Buyer Agent V2 graph with memory checkpointing.
 *
 * Graph flow:
 *   START -> gatherInfo -> (conditional) -> END
 *
 * The graph is invoked once per user message. The MemorySaver checkpointer
 * persists state across invocations using thread_id, so conversation history,
 * collected info, and job post state all survive between API calls.
 */
function buildBuyerGraph() {
  const checkpointer = new MemorySaver();

  const graph = new StateGraph(BuyerAgentState)
    .addNode("gatherInfo", gatherInfoNode)
    .addEdge(START, "gatherInfo")
    .addConditionalEdges("gatherInfo", routeAfterGather, {
      __end__: END,
    });

  const compiledGraph = graph.compile({ checkpointer });

  return compiledGraph;
}

export { buildBuyerGraph };
