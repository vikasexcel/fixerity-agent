import { ChatOpenAI } from "@langchain/openai";
import { AIMessage, SystemMessage } from "@langchain/core/messages";
import { AsyncLocalStorageProviderSingleton } from "@langchain/core/singletons";
import { embedJobPost } from "../services/pinecone.js";

const LOG_TAG = "[BuyerAgent]";

const SYSTEM_PROMPT = `You are a world-class job post creator and domain expert across every industry and trade. Your job is to help a buyer create the most detailed, professional, and complete job post possible — one so thorough that service providers can respond with accurate pricing and timelines without needing to ask follow-up questions.

CRITICAL RULES:

1. THERE ARE NO CATEGORIES, TEMPLATES, OR PREDEFINED FIELDS.
   Every job is unique. A request for an architect is completely different from a request for a dog walker, a software developer, a muralist, a wedding planner, or a plumber. YOU must figure out what information matters for THIS specific job.

2. YOU ARE THE DOMAIN EXPERT.
   When someone says "I need an architect for a new house" — YOU know that architects need to know: lot size, zoning, topography, utilities, target square footage, number of stories, room program (bedrooms, bathrooms, office, etc.), style preferences, site constraints (easements, setbacks, HOA, hillside ordinances), budget range, level of finish, timeline, scope of services needed (schematic design, construction docs, permit support, 3D renderings, construction administration), deliverables expected, and decision timeline.
   When someone says "I need someone to walk my dog" — YOU know to ask about: breed/size/temperament, how many dogs, schedule (days/times), duration per walk, location/neighborhood, leash behavior, special needs (medication, anxiety), whether they need to come inside or meet at a location, etc.
   YOU decide the questions. Not us. Not a template.

3. STRICTLY ONE QUESTION PER MESSAGE — THIS IS THE MOST IMPORTANT RULE.
   You MUST ask exactly ONE question per message. Never two. Never three. Never a list of questions. Never bullet points of things to answer. ONE single question, then STOP and WAIT for the answer.
   - WRONG: "What's the lot size? Also, do you know the zoning?"
   - WRONG: "Tell me about: 1) lot size 2) zoning 3) topography"
   - WRONG: "What style do you prefer, and what's your budget?"
   - RIGHT: "What's the approximate lot size of your property?"
   Then wait. After they answer, ask the NEXT single question.
   Each question should be specific, relevant, and dig deep into the domain. Do NOT ask vague questions like "tell me more" or "anything else?". Ask precise, expert-level questions that a professional in that field would need answered.

4. ASK IN-DEPTH DOMAIN-SPECIFIC QUESTIONS.
   Go deep. For an architect job you might ask 8-12+ questions covering project overview, site info, home program, style, services needed, budget, timeline, and deliverables. For simpler jobs you might need 4-6 questions. The number depends on the complexity — YOU judge when you have enough.
   Ask them ONE AT A TIME. Each message = one question. No exceptions.

5. BE CONVERSATIONAL AND FRIENDLY.
   Talk like a knowledgeable consultant helping the buyer think through everything. If they mention something that implies a follow-up, ask it. If they skip something critical, circle back.

6. WHEN YOU HAVE ENOUGH INFORMATION — GENERATE THE JOB POST.
   When you have gathered enough detail to create a comprehensive post, generate it. The post should be structured in whatever way makes sense for THAT specific domain:
   - An architect RFP might have sections like: Project Title, Project Location, Project Overview, Scope of Services, Proposed Home Program, Style Preferences, Site Information, Budget Guidance, Deliverables Requested, Decision Timeline, Contact Info.
   - A dog walker post might have: Service Needed, Schedule, Dog Details, Location, Special Requirements, Compensation.
   - A software project might have: Project Overview, Technical Requirements, Tech Stack, Timeline & Milestones, Budget, Team/Collaboration Expectations.
   YOU decide the sections. YOU decide the structure. There is NO fixed format.

7. PLACEHOLDERS FOR MISSING INFO.
   If the buyer didn't provide something that a provider would want to know, include it as a placeholder like [YOUR CITY/STATE] or [BUDGET RANGE] or [PREFERRED START DATE]. This way the post is complete and the buyer just fills in the blanks.

8. SIGNAL WHEN THE JOB POST IS READY.
   When you generate the final job post, start your message with exactly this marker on its own line:
   ---JOB_POST_READY---
   Then provide the complete job post below it.
   After the job post, ask the buyer to review it and let them know they can request changes or confirm it to publish.

9. AFTER THE JOB POST IS GENERATED.
   If the buyer wants to change anything, update the post and show the full updated version (again with the ---JOB_POST_READY--- marker). If they provide missing placeholder info, fill it in and regenerate.

FINAL REMINDER: You are not a form. You are an expert consultant. Every question you ask should demonstrate that you deeply understand the domain of the job being posted. And you MUST only ask ONE question per message — no exceptions, no lists, no compound questions.`;

function createModel() {
  return new ChatOpenAI({
    modelName: process.env.OPENAI_MODEL || "gpt-4o-mini",
    temperature: 0.7,
    openAIApiKey: process.env.OPENAI_API_KEY,
  });
}

const JOB_POST_MARKER = "---JOB_POST_READY---";

/**
 * Node: gatherInfo
 * Asks domain-specific questions and generates the job post draft.
 * When the job post is ready, sets status to "reviewing" for human approval.
 */
async function gatherInfoNode(state) {
  console.log(LOG_TAG, "gatherInfoNode entry", {
    questionCount: state.questionCount,
    status: state.status,
  });

  const model = createModel();

  const messagesForLLM = [new SystemMessage(SYSTEM_PROMPT), ...state.messages];

  const response = await model.invoke(messagesForLLM);
  const content = response.content;

  const isJobPostReady = content.includes(JOB_POST_MARKER);

  if (isJobPostReady) {
    const parts = content.split(JOB_POST_MARKER);
    const jobPostContent = parts[1] ? parts[1].trim() : content;

    const placeholderRegex = /\[([A-Z][A-Z\s/\-_]*(?:\:.*?)?)\]/g;
    const placeholders = [];
    let match;
    while ((match = placeholderRegex.exec(jobPostContent)) !== null) {
      placeholders.push(match[0]);
    }

    console.log(LOG_TAG, "gatherInfoNode job post ready", {
      status: "reviewing",
      placeholdersCount: placeholders.length,
    });

    return {
      messages: [new AIMessage(content)],
      jobPost: jobPostContent,
      placeholders: placeholders,
      status: "reviewing",
      questionCount: state.questionCount,
    };
  }

  console.log(LOG_TAG, "gatherInfoNode still gathering", {
    questionCount: state.questionCount + 1,
  });

  return {
    messages: [new AIMessage(content)],
    status: "gathering",
    questionCount: state.questionCount + 1,
  };
}

/**
 * Routing: after gatherInfo
 * If the job post is ready → go to reviewJobPost node (human interrupt)
 * Otherwise → END (wait for next user message)
 */
function routeAfterGather(state) {
  if (state.status === "reviewing") {
    console.log(LOG_TAG, "routeAfterGather → reviewJobPost");
    return "reviewJobPost";
  }
  console.log(LOG_TAG, "routeAfterGather → END");
  return "__end__";
}

/**
 * Node: reviewJobPost
 * This is the human-in-the-loop interrupt point.
 * The graph will pause here and wait for the buyer's response.
 * The buyer can either:
 *   - Confirm → proceed to createJob
 *   - Request changes → go back to gatherInfo
 */
async function reviewJobPostNode(state) {
  const lastMessage = state.messages[state.messages.length - 1];
  const buyerResponse = (lastMessage?.content ?? "").toLowerCase().trim();

  console.log(LOG_TAG, "reviewJobPostNode entry", {
    responseLength: buyerResponse.length,
  });

  const confirmPatterns = [
    "confirm",
    "yes",
    "looks good",
    "approve",
    "publish",
    "create",
    "go ahead",
    "perfect",
    "submit",
    "post it",
    "that's good",
    "that works",
    "ok",
    "okay",
    "sure",
    "do it",
    "lgtm",
    "good to go",
    "ready",
  ];

  const isConfirmed = confirmPatterns.some((p) => buyerResponse.includes(p));

  if (isConfirmed) {
    console.log(LOG_TAG, "reviewJobPostNode confirmed");
    return { status: "confirmed" };
  }

  console.log(LOG_TAG, "reviewJobPostNode wants changes → gathering");
  return { status: "gathering" };
}

/**
 * Routing: after reviewJobPost
 * If confirmed → createJob
 * If wants changes → gatherInfo (to regenerate the post)
 */
function routeAfterReview(state) {
  if (state.status === "confirmed") {
    console.log(LOG_TAG, "routeAfterReview → createJob");
    return "createJob";
  }
  console.log(LOG_TAG, "routeAfterReview → gatherInfo");
  return "gatherInfo";
}

/**
 * Node: createJob
 * Embeds the finalized job post in Pinecone and marks the job as done.
 * The buyer sees a confirmation message — they don't know about the embedding.
 * @param {object} state - Graph state
 * @param {object} [config] - Run config (from graph.invoke); holds configurable.thread_id
 */
async function createJobNode(state, config) {
  console.log(LOG_TAG, "createJobNode entry");

  const model = createModel();

  const runnableConfig = AsyncLocalStorageProviderSingleton.getRunnableConfig?.();
  const threadId =
    config?.configurable?.thread_id ??
    runnableConfig?.configurable?.thread_id ??
    state.messages?.[0]?.id ??
    "unknown";

  let embeddingId = null;
  let jobMetadata = null;

  try {
    const result = await embedJobPost(state.jobPost, threadId);
    embeddingId = result.embeddingId;
    jobMetadata = result.jobMetadata;
    console.log(LOG_TAG, "createJobNode embed success", {
      embeddingId,
      chunkCount: result.chunkCount,
    });
  } catch (err) {
    console.error(LOG_TAG, "createJobNode embed failed (non-blocking):", err.message);
  }

  const confirmPrompt = `The buyer has confirmed and the job post has been created successfully. Generate a brief, friendly confirmation message. Tell them their job post is now live and service providers will be able to see it. Keep it short and warm (2-3 sentences max). Do NOT include the job post again.`;

  const response = await model.invoke([
    new SystemMessage(confirmPrompt),
  ]);

  console.log(LOG_TAG, "createJobNode done", { status: "done" });

  return {
    messages: [new AIMessage(response.content)],
    status: "done",
    embeddingId: embeddingId,
    jobMetadata: jobMetadata,
  };
}

export {
  gatherInfoNode,
  routeAfterGather,
  reviewJobPostNode,
  routeAfterReview,
  createJobNode,
  JOB_POST_MARKER,
};
