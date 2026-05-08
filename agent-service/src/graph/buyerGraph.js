import { StateGraph, START, END, MemorySaver } from "@langchain/langgraph";
import { BuyerAgentState } from "./state.js";
import {
  gatherInfoNode,
  routeAfterGather,
  reviewJobPostNode,
  routeAfterReview,
  createJobNode,
} from "../agents/buyerAgent.js";

/**
 * Build the Buyer Agent V2 graph with human-in-the-loop and memory checkpointing.
 *
 * Graph flow:
 *   START -> gatherInfo -> (conditional) -> reviewJobPost [INTERRUPT] -> (conditional) -> createJob -> END
 *                        \-> END (still gathering)            \-> gatherInfo (buyer wants changes)
 *
 * The "reviewJobPost" node uses LangGraph's interrupt mechanism:
 *   - When the job post is generated, the graph pauses BEFORE reviewJobPost
 *   - The buyer reviews the post and sends their response (confirm / request changes)
 *   - The graph resumes from reviewJobPost with the buyer's input
 *
 * After confirmation, createJob embeds the post in Pinecone (invisible to the buyer)
 * and sends a confirmation message.
 */
function buildBuyerGraph() {
  const checkpointer = new MemorySaver();

  const graph = new StateGraph(BuyerAgentState)
    .addNode("gatherInfo", gatherInfoNode)
    .addNode("reviewJobPost", reviewJobPostNode)
    .addNode("createJob", createJobNode)
    // START → gatherInfo
    .addEdge(START, "gatherInfo")
    // gatherInfo → reviewJobPost (if job post ready) or END (still asking questions)
    .addConditionalEdges("gatherInfo", routeAfterGather, {
      reviewJobPost: "reviewJobPost",
      __end__: END,
    })
    // reviewJobPost → createJob (confirmed) or gatherInfo (wants changes)
    .addConditionalEdges("reviewJobPost", routeAfterReview, {
      createJob: "createJob",
      gatherInfo: "gatherInfo",
    })
    // createJob → END
    .addEdge("createJob", END);

  // Compile with interrupt BEFORE reviewJobPost — this is the human-in-the-loop point
  const compiledGraph = graph.compile({
    checkpointer,
    interruptBefore: ["reviewJobPost"],
  });

  return compiledGraph;
}

export { buildBuyerGraph };
