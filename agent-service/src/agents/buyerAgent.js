import { ChatOpenAI } from "@langchain/openai";
import { AIMessage, SystemMessage } from "@langchain/core/messages";
import { AsyncLocalStorageProviderSingleton } from "@langchain/core/singletons";
import { embedJobPost, findMatchingSellers } from "../services/pinecone.js";
import { PROFILE_QUESTIONS, PROFILE_QUESTION_IDS } from "../data/profileQuestions.js";

const LOG_TAG = "[BuyerAgent]";

const SYSTEM_PROMPT = `You are a world-class job post creator and domain expert across every industry and trade. Your job is to help a buyer create the most detailed, professional, and complete job post possible — one so thorough that service providers can respond with accurate pricing and timelines without needing to ask follow-up questions.

TWO-PHASE QUESTIONING (FOLLOW STRICTLY):

PHASE 1 — DOMAIN-SPECIFIC QUESTIONS (ask first; minimum 5-8 before Phase 2).
   You MUST ask at least 5-8 questions that are NOT from the PROFILE QUESTIONS REFERENCE. These are expert-level questions only a specialist in this service would ask. ONE question per message. The goal is to gather DEEP, comprehensive information about the specific job so providers can give accurate quotes without follow-ups.
   
   CRITICAL: Be ADAPTIVE and dig deeper based on the buyer's answers. If they mention something important, follow up on it. If they give a short answer, ask a clarifying follow-up. Think like a consultant who needs to fully understand the job before making recommendations. Ask enough questions to get a complete picture — for complex jobs (repairs, construction, professional services), aim for 6-8 domain questions. For simpler jobs (routine cleaning, basic tasks), 5-6 is sufficient.
   
   Do NOT ask urgency, scope, location, budget, timeline, availability, photos, licensing, decision timeline, references, or any other profile question during Phase 1. Ask only domain questions.
   
   Examples by domain (adapt these to the specific buyer request and follow up based on their answers):
   - House cleaning: square footage or number of bedrooms/bathrooms, one-time or recurring, who provides supplies, any areas needing extra attention (carpets, windows), pets or allergies, access (key, code). If they say "eco-friendly products", ask which products or certifications matter. If they mention pets, ask about allergies or special handling.
   - Laptop/electronics repair: device model and year, warranty/AppleCare, symptoms and when they started, troubleshooting already tried, in-person vs mail-in. If they say "screen flickering", ask when it happens (always, only when hot, certain apps). If they mention battery issues, ask about charging patterns or error messages.
   - Baby/childcare: child's age, any special needs or medical conditions, daily routine (naps, meals, activities), caregiver qualifications needed (CPR, experience with age group), where care will be provided (your home, caregiver's home), number of children, whether caregiver will drive child anywhere. If they mention "developmental activities", ask what types they want (music, reading, outdoor play). If child has allergies, ask about emergency protocols.
   - Architect: lot size, zoning, square footage, room program, style, site constraints, scope of services. If they say "modern style", ask for examples or specific features. If they mention zoning, ask if permits are already handled.
   - Dog walker: breed/size, schedule, duration, leash behavior, special needs. If they mention "reactive to other dogs", ask about training already done or specific triggers.
   
   The PROFILE STATE below shows "domainPhaseComplete". When it is false, you must ask ONLY domain questions (no profile questions). When it becomes true, then ask the 15 profile questions one by one.

PHASE 2 — ALL 15 PROFILE QUESTIONS (from the doc, one at a time).
   Now ask the remaining standard questions, but CRITICAL: DO NOT use the shortPrompt templates as-is. Instead, READ THE CONVERSATION HISTORY and phrase each question naturally based on what they ACTUALLY said. Make it feel like you're continuing the same conversation, not switching to a form.
   
   BAD (generic templates):
   - "Based on what you've described, how would you categorize this?"
   - "Timing-wise, how urgent is this?"
   - "For this kind of job, what's your budget?"
   
   GOOD (specific to their actual words):
   - If they said "I need my MacBook fixed" → "So for this MacBook repair, what's your budget looking like?"
   - If they said "my baby is 8 months old" → "Got it. And where are you located — city, state, ZIP?"
   - If they mentioned "ASAP" or "urgent" earlier → "You mentioned this is urgent — are we talking within 24 hours, or within the week?"
   - If they said "house cleaning" → "For the cleaning, do you need them to be licensed and insured, or is that not a concern?"
   
   Look at what they actually told you about their job and weave the profile questions into that context. If they already mentioned something (like "at my home" or "in Brooklyn"), reference it: "You mentioned Brooklyn earlier — what's the full location?" Accept whatever detail they give (just city name is fine).
   
   Keep questions SHORT (one sentence max). Do NOT read lists of options unless they seem confused. Skip options are implied — only mention "you can skip this" for budget/photos. Only ask questions that appear in PROFILE STATE as not yet answered or skipped. Only after ALL 15 are covered may you generate the job post (---JOB_POST_READY---).

NEVER REVEAL INTERNAL STRUCTURE TO THE BUYER.
   Do NOT say: "domain-specific questions", "profile questions", "Phase 1", "Phase 2", "let's move on to the profile questions", "now the standard questions", or anything that reveals phases or form fields. Do NOT announce "Now that we've covered X, let's move on to Y." Simply continue with the next question naturally, as if it's one flowing conversation.

NEVER ASK FOR CONTACT INFORMATION.
   Do NOT ask for the buyer's name, phone number, email address, or any personal contact information. The buyer is already logged in and we have their contact details. Only ask about the job itself — what they need, where, when, and job-specific requirements.

CRITICAL RULES:

1. STRICTLY ONE QUESTION PER MESSAGE.
   Never two questions, never a list, never bullet points. ONE question, then STOP and WAIT.

2. BE CONVERSATIONAL AND FRIENDLY.
   Talk like a knowledgeable consultant. If they mention something that implies a follow-up, ask it. Reference their previous answers when relevant (e.g. "You mentioned eco-friendly products — are there specific brands or certifications you prefer?" or "Since your baby is 8 months old, will the caregiver need to handle solid foods or just bottles?").

3. WHEN GENERATING THE JOB POST — USE THE MANDATORY STRUCTURE BELOW.
   The final job post MUST include every section in this exact order. For any field the buyer skipped or did not answer, use the suggested default text (never leave blank or use placeholders):

MANDATORY JOB POST STRUCTURE (include every section):
**Job Title:** [Generated from service type]
**Service Category Needed:** [From profile answer]
**Specific Service Type:** [From profile answer or generated from context]
**Project Overview:** [From detailed description]
**Issues/Scope:** [From answers]
**Project Urgency:** [From profile answer]
**Project Scope/Complexity:** [From profile answer]
**Location:** [Whatever location detail the buyer provided — city, state, ZIP, or just city name; accept any level of detail; include whether location is important and Remote/Virtual acceptable if mentioned]
**Budget Range:** [From profile answer OR infer a reasonable range from your knowledge of this service type if skipped — e.g. typical cost for similar jobs]
**Timeline/Schedule:** [From profile answer OR "To be determined" if skipped]
**Availability for Consultation/Work:** [From profile answer]
**Photos/Documentation:** [Status from answer OR "No photos provided at this time" if skipped]
**Special Requirements:** [From profile answer]
**Licensing/Credentials Required:** [From profile answer OR "Prefer licensed and insured" if skipped]
**Decision Timeline:** [From profile answer OR "Within 2 weeks" if skipped]
**References/Reviews Important?:** [From profile answer OR "Preferred" if skipped]
**Additional Comments:** [From profile answer OR "None" if skipped]

4. SIGNAL WHEN THE JOB POST IS READY.
   On its own line, start with exactly: ---JOB_POST_READY---
   Then the complete job post, then ask the buyer to review and confirm or request changes.

5. AFTER THE JOB POST IS GENERATED.
   If the buyer wants changes, update the post and show the full version again with ---JOB_POST_READY---.

The PROFILE QUESTIONS REFERENCE and PROFILE STATE for this turn are provided below. Do not ask a profile question that is already answered or skipped. Only generate the job post when all 15 profile questions are covered (answered or skipped).`;

function createModel() {
  return new ChatOpenAI({
    modelName: process.env.OPENAI_MODEL || "gpt-4o-mini",
    temperature: 0.7,
    openAIApiKey: process.env.OPENAI_API_KEY,
  });
}

const JOB_POST_MARKER = "---JOB_POST_READY---";

/**
 * Return profile field IDs whose label (or a recognizable part) does not appear in the job post.
 * @param {string} jobPostContent
 * @returns {string[]} missing field IDs
 */
function getMissingJobPostFields(jobPostContent) {
  if (!jobPostContent || !jobPostContent.trim()) return [...PROFILE_QUESTION_IDS];
  const lower = jobPostContent.toLowerCase();
  const missing = [];
  for (const q of PROFILE_QUESTIONS) {
    const labelLower = q.label.toLowerCase();
    const searchLen = Math.min(14, labelLower.length);
    const search = labelLower.slice(0, searchLen).trim();
    if (search.length < 5) continue;
    const found = lower.includes(search);
    if (!found) missing.push(q.id);
  }
  return missing;
}

/**
 * Build the reference text of exact profile questions (same as in the doc, same style).
 * @returns {string}
 */
function buildProfileQuestionsReference() {
  return (
    "PROFILE QUESTIONS REFERENCE (ask one at a time; use the short natural phrasing below — do not read long lists to the buyer):\n" +
    PROFILE_QUESTIONS.map(
      (q) => `- ${q.label} (id: ${q.id}): ${q.shortPrompt ?? q.exactQuestion}`
    ).join("\n")
  );
}

/**
 * Build a short summary of profile answers for the system prompt.
 * @param {Record<string, string | "skip">} profileAnswers
 * @returns {string}
 */
function buildProfileStateSummary(profileAnswers, domainPhaseComplete = false, domainQuestionCount = 0) {
  const lines = [];
  if (domainPhaseComplete) {
    lines.push("Domain phase complete: Yes. You may now ask the 15 profile questions from the reference below, one at a time.");
  } else {
    lines.push("Domain phase complete: No. You MUST ask domain-specific questions only (minimum 5-8 total for a comprehensive understanding). Do NOT ask any profile question (no urgency, scope, location, budget, timeline, availability, photos, licensing, decision timeline, references, or additional comments) until domain phase is complete. Domain questions so far: " + domainQuestionCount + ". Continue asking domain questions to gather complete job details.");
  }
  if (!profileAnswers || Object.keys(profileAnswers).length === 0) {
    if (domainPhaseComplete) lines.push("Profile: None answered or skipped yet. Ask profile questions one at a time from the reference.");
    return "PROFILE STATE:\n" + lines.join("\n");
  }
  const answered = [];
  const skipped = [];
  for (const id of PROFILE_QUESTION_IDS) {
    const v = profileAnswers[id];
    if (v === "skip") skipped.push(id);
    else if (v != null && String(v).trim() !== "") answered.push(`${id}=${String(v).trim().slice(0, 80)}`);
  }
  if (answered.length) lines.push("Answered: " + answered.join("; "));
  if (skipped.length) lines.push("Skipped (do not ask again): " + skipped.join(", "));
  const notYet = PROFILE_QUESTION_IDS.filter((id) => profileAnswers[id] == null);
  if (notYet.length) lines.push("Not yet asked: " + notYet.join(", "));
  const totalCovered = answered.length + skipped.length;
  lines.push("Profile progress: " + totalCovered + "/15. Only generate the job post when all 15 are answered or skipped.");
  return "PROFILE STATE:\n" + lines.join("\n");
}

/**
 * Extract which profile question was just answered or skipped from the last exchange.
 * @param {ReturnType<typeof createModel>} model
 * @param {string} lastAiContent
 * @param {string} lastUserContent
 * @returns {Promise<{ questionId: string, value: string | "skip" } | null>}
 */
async function extractProfileUpdate(model, lastAiContent, lastUserContent, domainPhaseComplete) {
  if (!lastAiContent?.trim() || !lastUserContent?.trim()) return null;
  // During Phase 1, do NOT classify as profile — always return null so we count domain questions
  if (!domainPhaseComplete) {
    console.log(LOG_TAG, "extractProfileUpdate: Phase 1 active, not classifying as profile");
    return null;
  }
  const prompt = `You are a classifier. Given the last AI message (a single question to the user) and the user's reply, determine:
1. Was the AI asking one of these profile questions? Profile question IDs: ${PROFILE_QUESTION_IDS.join(", ")}.
2. If yes, which ID best matches the topic of the AI's question? And did the user answer (value = their answer in a short phrase) or skip (value = "skip")? Treat responses like "skip", "pass", "don't know", "not sure", "next", "I'll skip", "no preference" as skip.
3. If the AI was asking a domain-specific question (not one of the profile topics above), return null.

Reply with ONLY a single JSON object, no other text. Examples:
{"questionId":"urgency","value":"Soon (2-4 weeks)"}
{"questionId":"budgetRange","value":"skip"}
{"questionId":null,"value":null}

Last AI message:\n${lastAiContent.slice(0, 1500)}\n\nUser reply:\n${lastUserContent.slice(0, 800)}`;
  try {
    const response = await model.invoke([new SystemMessage(prompt)]);
    const text = (response.content ?? "").trim();
    const match = text.match(/\{[\s\S]*\}/);
    if (!match) return null;
    const parsed = JSON.parse(match[0]);
    if (parsed.questionId == null || parsed.value == null) return null;
    if (!PROFILE_QUESTION_IDS.includes(parsed.questionId)) return null;
    const value = parsed.value === "skip" ? "skip" : String(parsed.value).trim();
    return { questionId: parsed.questionId, value: value || "skip" };
  } catch (err) {
    console.warn(LOG_TAG, "extractProfileUpdate failed", err.message);
    return null;
  }
}

/**
 * Node: gatherInfo
 * Asks domain-specific questions and profile questions (with skip support), then generates the job post.
 * When the job post is ready, sets status to "reviewing" for human approval.
 */
async function gatherInfoNode(state) {
  const enteringPhase = state.domainPhaseComplete === true ? 2 : 1;
  console.log(LOG_TAG, "gatherInfoNode entry", {
    phase: enteringPhase,
    questionCount: state.questionCount,
    status: state.status,
    domainQuestionCount: state.domainQuestionCount ?? 0,
    domainPhaseComplete: state.domainPhaseComplete === true,
  });

  const model = createModel();
  const profileQuestionsRef = buildProfileQuestionsReference();
  const domainPhaseComplete = state.domainPhaseComplete === true;
  const domainQuestionCount = state.domainQuestionCount ?? 0;
  const profileStateSummary = buildProfileStateSummary(state.profileAnswers ?? {}, domainPhaseComplete, domainQuestionCount);
  const systemContent =
    SYSTEM_PROMPT + "\n\n---\n" + profileQuestionsRef + "\n\n" + profileStateSummary;
  const messagesForLLM = [new SystemMessage(systemContent), ...state.messages];

  const response = await model.invoke(messagesForLLM);
  const content = response.content;

  const isJobPostReady = content.includes(JOB_POST_MARKER);

  if (isJobPostReady) {
    // Use the exact job post from the assistant message — do not repair or rewrite (so "Job post" card matches chat).
    const jobPostContent = content.split(JOB_POST_MARKER)[1] ? content.split(JOB_POST_MARKER)[1].trim() : content;

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

  // Still gathering: try to extract profile answer/skip from last exchange
  let profileAnswersUpdate = null;
  let domainQuestionCountIncrement = 0;
  let setDomainPhaseComplete = false;
  const messages = state.messages ?? [];
  const currentProfileAnswers = state.profileAnswers ?? {};
  const currentDomainCount = state.domainQuestionCount ?? 0;
  const currentDomainPhaseComplete = state.domainPhaseComplete === true;
  if (messages.length >= 2) {
    const lastUser = messages[messages.length - 1];
    const lastAi = messages[messages.length - 2];
    const lastUserContent = typeof lastUser?.content === "string" ? lastUser.content : "";
    const lastAiContent = typeof lastAi?.content === "string" ? lastAi.content : "";
    const extracted = await extractProfileUpdate(model, lastAiContent, lastUserContent, currentDomainPhaseComplete);
    if (extracted) {
      console.log(LOG_TAG, "last exchange: profile question", {
        questionId: extracted.questionId,
        value: extracted.value === "skip" ? "skip" : String(extracted.value).slice(0, 60),
      });
      // Prevent duplicate: do not overwrite if this question was already answered or skipped
      if (currentProfileAnswers[extracted.questionId] == null) {
        profileAnswersUpdate = { [extracted.questionId]: extracted.value };
        console.log(LOG_TAG, "gatherInfoNode profile update", extracted);
      }
    } else {
      console.log(LOG_TAG, "last exchange: domain question", {
        counted: true,
        domainQuestionCountAfter: currentDomainCount + 1,
      });
      // Last exchange was a domain question (no profile match) — count it
      domainQuestionCountIncrement = 1;
    }
  }
  const newDomainCount = currentDomainCount + domainQuestionCountIncrement;
  // Require 5 domain questions minimum before allowing profile questions
  if (newDomainCount >= 5) setDomainPhaseComplete = true;

  const phase = setDomainPhaseComplete ? 2 : 1;
  console.log(LOG_TAG, "gatherInfoNode still gathering", {
    phase,
    phaseLabel: phase === 1 ? "Phase 1 (domain questions)" : "Phase 2 (profile questions)",
    questionCount: state.questionCount + 1,
    domainQuestionCount: newDomainCount,
    domainPhaseComplete: setDomainPhaseComplete,
  });

  const out = {
    messages: [new AIMessage(content)],
    status: "gathering",
    questionCount: state.questionCount + 1,
  };
  if (profileAnswersUpdate != null) out.profileAnswers = profileAnswersUpdate;
  if (domainQuestionCountIncrement > 0) out.domainQuestionCount = domainQuestionCountIncrement;
  if (setDomainPhaseComplete) out.domainPhaseComplete = true;
  return out;
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

  let matchedSellers = null;
  let matchingStatus = null;
  if (state.jobPost && state.jobPost.trim().length > 0) {
    console.log(LOG_TAG, "createJobNode starting seller matching", {
      jobPostLength: state.jobPost.trim().length,
      threadId,
    });
    try {
      matchedSellers = await findMatchingSellers(state.jobPost);
      matchingStatus = matchedSellers.length > 0 ? "found" : "found";
      console.log(LOG_TAG, "createJobNode seller matching complete", {
        matchingStatus,
        matchedCount: matchedSellers?.length ?? 0,
      });
    } catch (err) {
      console.error(LOG_TAG, "createJobNode findMatchingSellers failed (non-blocking):", err.message);
      matchingStatus = "error";
    }
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
    matchedSellers,
    matchingStatus,
  };
}

export {
  gatherInfoNode,
  routeAfterGather,
  reviewJobPostNode,
  routeAfterReview,
  createJobNode,
  JOB_POST_MARKER,
  getMissingJobPostFields,
};
