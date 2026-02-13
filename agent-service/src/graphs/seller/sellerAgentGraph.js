import { StateGraph, START, END, MemorySaver, MessagesAnnotation, Command } from '@langchain/langgraph';
import { ToolNode, toolsCondition } from '@langchain/langgraph/prebuilt';
import { ChatOpenAI } from '@langchain/openai';
import { HumanMessage, AIMessage, SystemMessage } from '@langchain/core/messages';
import { OPENAI_API_KEY } from '../../config/index.js';
import { messageService } from '../../services/index.js';
import { sellerTools } from './sellerTools.js';

/* ================================================================================
   SELLER AGENT GRAPH - Tool-calling agent with human-in-the-loop
   State: messages only. Thread identity: thread_id = sessionId. sellerId in config.
   ================================================================================ */

const checkpointer = new MemorySaver();

function buildSystemPrompt(sellerId) {
  return `You are a helpful assistant for service providers (sellers) on a marketplace platform.
Your capabilities: view and update seller profile, browse matched jobs, get job details, list and manage bids, submit bids, withdraw bids, and view the dashboard.

Important: The current seller's ID is "${sellerId}". When calling any tool that requires sellerId, always use this exact value: "${sellerId}".

Use the provided tools to fulfill the user's requests. Be concise and friendly. After using a tool, summarize the result for the user in a natural way.`;
}

async function agentNode(state, config) {
  const sellerId = config?.configurable?.sellerId;
  const llm = new ChatOpenAI({
    model: 'gpt-4o-mini',
    temperature: 0.3,
    openAIApiKey: OPENAI_API_KEY,
  });
  const llmWithTools = llm.bindTools(sellerTools);
  const systemPrompt = buildSystemPrompt(sellerId || '');
  const messages = [new SystemMessage(systemPrompt), ...state.messages];
  const response = await llmWithTools.invoke(messages);
  return { messages: [response] };
}

const toolNode = new ToolNode(sellerTools, { handleToolErrors: true });

const workflow = new StateGraph(MessagesAnnotation)
  .addNode('agent', agentNode)
  .addNode('tools', toolNode)
  .addEdge(START, 'agent')
  .addConditionalEdges('agent', toolsCondition, { tools: 'tools', [END]: END })
  .addEdge('tools', 'agent');

export const sellerAgentGraph = workflow.compile({ checkpointer });

/**
 * Convert DB message format to LangChain BaseMessage.
 */
function toLangChainMessage(m) {
  const content = m.content || '';
  if (m.role === 'user') return new HumanMessage(content);
  if (m.role === 'assistant') return new AIMessage(content);
  if (m.role === 'system') return new SystemMessage(content);
  return new HumanMessage(content);
}

/**
 * Load conversation history from session and append new user message if provided.
 */
async function getMessagesForInvoke(sessionId, newMessageText = null) {
  const history = await messageService.getConversationHistory(sessionId, { limit: 50, includeSystem: false });
  const messages = history.map(toLangChainMessage);
  if (newMessageText != null && newMessageText !== '') {
    messages.push(new HumanMessage(newMessageText));
  }
  return messages;
}

/**
 * Invoke the seller agent. Returns { result, __interrupt__ }.
 * - If the graph hits an interrupt (e.g. in submit_bid or withdraw_bid), result will have __interrupt__.
 * - config must include configurable: { thread_id: sessionId, sellerId }.
 */
export async function invokeSellerAgent(input, config) {
  const { messages: inputMessages, sellerId, sessionId, newMessage } = input;
  let messages = inputMessages;
  if (messages == null || messages.length === 0) {
    messages = await getMessagesForInvoke(sessionId, newMessage);
  } else if (newMessage != null && newMessage !== '') {
    messages = [...messages, new HumanMessage(newMessage)];
  }

  const result = await sellerAgentGraph.invoke({ messages }, config);
  return result;
}

/**
 * Resume the seller agent after an interrupt. Pass Command({ resume }) as input.
 */
export async function resumeSellerAgent(config, resumeValue) {
  const result = await sellerAgentGraph.invoke(new Command({ resume: resumeValue }), config);
  return result;
}
