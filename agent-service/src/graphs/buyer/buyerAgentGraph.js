import { StateGraph, START, END, MemorySaver, MessagesAnnotation } from '@langchain/langgraph';
import { ToolNode, toolsCondition } from '@langchain/langgraph/prebuilt';
import { ChatOpenAI } from '@langchain/openai';
import { SystemMessage } from '@langchain/core/messages';
import { OPENAI_API_KEY } from '../../config/index.js';

/* ================================================================================
   BUYER AGENT GRAPH - Tool-calling agent for conversational job creation
   No predefined fields. AI asks questions based on job type and marketplace knowledge.
   Factory creates graph with tools bound to buyerId/accessToken per invocation.
   ================================================================================ */

const BUYER_SYSTEM_PROMPT = `You are a helpful assistant for buyers on a service marketplace. Help users create comprehensive job posts so service providers can give accurate bids.

Your job is to understand what the user needs through natural conversation. Use your knowledge about different types of work to ask the RIGHT questions for their specific situation.

CORE PRINCIPLES:
- Ask ONE question at a time. Never bundle multiple topics.
- Be conversational and natural - no forms, no checklists.
- The more detail you collect, the better the bids will be.
- When you have enough for providers to bid accurately, call create_job IMMEDIATELY.
- Do NOT ask for confirmation - just create the job.

WHAT TO COLLECT (adapt to the job):
- What needs to be done and why
- Location and site details (if relevant)
- Timeline and urgency
- Budget (can be flexible/reasonable)
- Any specific requirements, constraints, or preferences

Trust your judgment on what's relevant. "Walk my dog each morning" needs different questions than "design my house" or "paint a wall mural of me."

When calling create_job:
- Pass ALL collected info in specific_requirements as structured key-value pairs that match what matters for this type of work.
- Also pass: budget_min, budget_max (use reasonable defaults if user said "flexible"), start_date, end_date, location.
- The tool uses an LLM to generate a professional job post from your collected data.

Be friendly and concise. Use contractions. Keep responses to 1-2 sentences.`;

const checkpointer = new MemorySaver();

/**
 * Factory: creates the buyer agent graph with the given tools.
 * Tools are created per invocation with buyerId/accessToken in closure.
 */
export function createBuyerAgentGraph(tools) {
  async function agentNode(state) {
    const llm = new ChatOpenAI({
      model: 'gpt-4o-mini',
      temperature: 0.7,
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

  return workflow.compile({ checkpointer });
}
