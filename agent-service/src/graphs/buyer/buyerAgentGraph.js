import { StateGraph, START, END, MessagesAnnotation } from '@langchain/langgraph';
import { ToolNode, toolsCondition } from '@langchain/langgraph/prebuilt';
import { ChatOpenAI } from '@langchain/openai';
import { SystemMessage } from '@langchain/core/messages';
import { OPENAI_API_KEY } from '../../config/index.js';

/* ================================================================================
   BUYER AGENT GRAPH - Tool-calling agent for conversational job creation
   No predefined fields. AI asks questions based on job type and marketplace knowledge.
   Factory creates graph with tools bound to buyerId/accessToken per invocation.
   ================================================================================ */

const BUYER_SYSTEM_PROMPT = `You are an expert assistant helping users find service providers. Have a natural, flowing conversation - ask questions the way the actual service provider would ask them when understanding the job.

Ask ONE question at a time, building naturally on their answers. Focus on work-specific details:

Examples:
- Dog walking: Start with breed and size, then temperament, then walking routine preferences
- Wall mural: Start with where the wall is (indoor/outdoor), then size, then artistic vision
- Laptop repair: Start with what's wrong, then model, then what happened before it broke
- House design: Start with what spaces need designing, then style preferences, then scope

Never ask about budget, deadline, or location upfront - focus on understanding the WORK itself first.

When you have thorough work details, call create_job with everything you learned.`;

/**
 * Factory: creates the buyer agent graph with the given tools.
 * Tools are created per invocation with buyerId/accessToken in closure.
 */
export function createBuyerAgentGraph(tools) {
  async function agentNode(state) {
    const llm = new ChatOpenAI({
      model: 'gpt-4o',
      openAIApiKey: OPENAI_API_KEY,
    });
    const llmWithTools = llm.bindTools(tools);
    const messages = [new SystemMessage(BUYER_SYSTEM_PROMPT), ...state.messages];
    const response = await llmWithTools.invoke(messages);
    return { messages: [response] };
  }

  const toolNode = new ToolNode(tools, { handleToolErrors: true });

  const workflow = new StateGraph(MessagesAnnotation)
    .addNode('agent', agentNode)
    .addNode('tools', toolNode)
    .addEdge(START, 'agent')
    .addConditionalEdges('agent', toolsCondition, { tools: 'tools', [END]: END })
    .addEdge('tools', 'agent');

  return workflow.compile();
}
