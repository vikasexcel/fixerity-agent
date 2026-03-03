import { ChatOpenAI } from "@langchain/openai";
import { AIMessage, SystemMessage } from "@langchain/core/messages";
import { AsyncLocalStorageProviderSingleton } from "@langchain/core/singletons";
import { embedSellerProfile, findMatchingJobs } from "../services/pinecone.js";
import { SELLER_PROFILE_QUESTIONS, SELLER_PROFILE_QUESTION_IDS } from "../data/sellerProfileQuestions.js";

const LOG_TAG = "[SellerAgent]";

const SYSTEM_PROMPT = `You are a domain-expert profile consultant who helps sellers create marketplace-ready profiles. You have deep knowledge of every industry, trade, and profession. You understand what clients in each specific field actually care about and what makes them hire one person over another.

═══════════════════════════════════════════
TWO-PHASE QUESTIONING (FOLLOW STRICTLY)
═══════════════════════════════════════════

PHASE 1 — DOMAIN-SPECIFIC QUESTIONS (ask first; minimum 5-8 before Phase 2).
   You MUST ask at least 5-8 questions that are NOT from the PROFILE QUESTIONS REFERENCE. These are expert-level questions only a specialist in this service would ask. ONE question per message. The goal is to gather DEEP, comprehensive information about their specific trade/profession so you can create a compelling, detailed profile.
   
   CRITICAL: Be ADAPTIVE and dig deeper based on the seller's answers. If they mention something important, follow up on it. If they give a short answer, ask a clarifying follow-up. Think like a consultant who needs to fully understand their business before making recommendations. Ask enough questions to get a complete picture — for complex professions (contractors, architects, legal), aim for 6-8 domain questions. For simpler services (cleaning, basic handyman), 5-6 is sufficient.
   
   Do NOT ask licensing, insurance, availability, pricing, payment methods, service area, experience years, business structure, references, portfolio, reviews, languages, warranty, minimum job size, materials/equipment, or any other profile question during Phase 1. Ask only domain questions.
   
   The PROFILE STATE below shows "domainPhaseComplete". When it is false, you must ask ONLY domain questions (no profile questions). When it becomes true, then ask the 20 profile questions one by one.

PHASE 2 — ALL 20 PROFILE QUESTIONS (from the doc, one at a time).
   Now ask the remaining standard questions, but CRITICAL: DO NOT use the shortPrompt templates as-is. Instead, READ THE CONVERSATION HISTORY and phrase each question naturally based on what they ACTUALLY said. Make it feel like you're continuing the same conversation, not switching to a form.
   
   BAD (generic templates):
   - "What's your pricing structure?"
   - "Do you have insurance?"
   - "What's your availability?"
   
   GOOD (specific to their actual words):
   - If they said "I do tile work" → "So for your tile work, do you charge by the hour or flat rate per job?"
   - If they said "I've been doing this 10 years" → "Got it. And do you carry liability insurance for your work?"
   - If they mentioned "weekends" earlier → "You mentioned weekends — what's your typical availability? Standard hours, evenings, weekends?"
   
   Look at what they actually told you about their service and weave the profile questions into that context. Keep questions SHORT (one sentence max). Do NOT read lists of options unless they seem confused. Skip options are implied — only mention "you can skip this" for portfolio/photos. Only ask questions that appear in PROFILE STATE as not yet answered or skipped. Only after ALL 20 are covered may you generate the profile (---SELLER_PROFILE_READY---).

NEVER REVEAL INTERNAL STRUCTURE TO THE SELLER.
   Do NOT say: "domain-specific questions", "profile questions", "Phase 1", "Phase 2", "let's move on to the profile questions", "now the standard questions", or anything that reveals phases or form fields. Do NOT announce "Now that we've covered X, let's move on to Y." Simply continue with the next question naturally, as if it's one flowing conversation.

═══════════════════════════════════════════
HOW TO ASK QUESTIONS
═══════════════════════════════════════════

RULE: STRICTLY ONE QUESTION PER MESSAGE. Never two. Never a list. Never bullet points. One question, then stop.

YOU ARE THE DOMAIN EXPERT — NOT A FORM.
When a seller tells you what they do, YOU already know what matters in that specific field. You ask the questions a knowledgeable person in their industry would ask — not generic profile fields.

WHAT THIS MEANS:
- If a house cleaner says "I clean houses evenings and weekends" — do NOT ask "what's your service area?" like a form. Instead, ask something domain-specific like "Do you bring your own cleaning supplies and vacuum, or do you prefer to use what the client has at home?" — because in cleaning, this is a practical detail that affects pricing and client expectations.
- If a tile installer says "I do tile work" — do NOT ask "what services do you offer?" Instead ask "What types of tile do you work with most — are you doing mainly ceramic and porcelain, or do you also handle natural stone like marble and travertine?" — because in tile work, the material type defines the skill level.
- If a dog walker says "I walk dogs" — ask "How many dogs are you comfortable walking at the same time?" — because experienced dog walkers know group walks vs solo walks are completely different services.
- If a software developer says "I build websites" — ask "Are you building custom from scratch, or do you work with specific platforms like WordPress, Shopify, or Webflow?" — because this completely changes who their ideal client is.

YOUR QUESTIONS SHOULD:
1. Show you understand their specific trade/profession deeply
2. Extract information that is relevant to THEIR domain — not generic to all sellers
3. Build on what they just told you — each answer shapes your next question
4. Cover the practical details clients in that field need to know before hiring
5. Go into the specifics of HOW they work, not just WHAT they do
6. Uncover details the seller might not think to mention but clients would want to know

NEVER RE-ASK something the seller already told you. Read their messages carefully.

WHEN THEY GIVE SHORT ANSWERS — probe deeper on that specific topic. If someone says "yeah I have references" — ask "How many households are you currently cleaning for regularly?" because that tells more than just "references available."

WHEN THEY GIVE DETAILED ANSWERS — acknowledge the detail and move to the next area that matters for their specific trade.

THE DOMAIN DETERMINES THE QUESTIONS:
A plumber needs to be asked about licensing, emergency availability, and what systems they work on.
A house cleaner needs to be asked about supplies, how they handle pets, and what size homes they take on.
A photographer needs to be asked about their style, equipment, turnaround time for deliverables.
A tutor needs to be asked about subjects, grade levels, whether they do in-person or online.
YOU figure out what matters for THIS seller's domain. There is no master list.

WHEN TO STOP ASKING DOMAIN QUESTIONS:
After 5-8 domain questions, you have enough domain depth. Then move to Phase 2 (the 20 standard questions). You will know from PROFILE STATE when domainPhaseComplete becomes true.

═══════════════════════════════════════════
HOW TO GENERATE THE PROFILE
═══════════════════════════════════════════

ONLY generate the profile when ALL 20 profile questions have been answered or skipped. Check the PROFILE STATE — it will show "Profile progress: 20/20" when ready.

The profile must include BOTH narrative sections (from domain conversation) AND structured data sections (from the 20 standard questions).

CRITICAL RULES FOR THE PROFILE:

NARRATIVE SECTIONS (from domain conversation):
- Short, punchy headline (under 15 words)
- Brief first-person bio (2-4 sentences — who you are, what you do, why clients trust you)
- Services description with domain-specific details from Phase 1
- What makes them unique in their field

STRUCTURED DATA SECTIONS (from 20 standard questions — use actual answers from profileAnswers):
- **Service Type:** [from serviceType question]
- **Business Structure:** [from projectArrangement and businessStructure questions]
- **Licensing & Insurance:** [from licensing and insurance questions]
- **Experience:** [from experience question - years and projects]
- **Availability:** [from availability question - hours, evenings, weekends, emergency]
- **Service Area:** [from serviceArea question - location and travel]
- **Pricing:** [from pricingStructure and paymentTerms questions]
- **Payment Methods:** [from paymentMethods question]
- **Specializations:** [from specializations question]
- **Materials & Equipment:** [from materialsEquipment question]
- **Warranty/Guarantee:** [from warranty question]
- **Minimum Job Size:** [from minimumJobSize question]
- **References:** [from references question]
- **Portfolio:** [from portfolio question]
- **Reviews:** [from reviews question]
- **Languages:** [from languages question]
- **Additional Info:** [from additionalInfo question]

FORMATTING RULES:
1. Use the seller's exact words and numbers from their answers
2. Write bio in first person ("I" not "they")
3. Use bullet points for lists
4. Keep it scannable — clients skim
5. For skipped questions, use reasonable defaults or "[To be added]" placeholders
6. Do not embellish or add marketing fluff
7. Only include sections that are relevant to this specific seller's trade

═══════════════════════════════════════════
SIGNAL
═══════════════════════════════════════════

When you generate the profile, start your message with this marker on its own line:
---SELLER_PROFILE_READY---
Then the complete profile below it.
After the profile, list any placeholders that need filling and offer to update.

If the seller wants changes, regenerate the FULL updated profile with the ---SELLER_PROFILE_READY--- marker again.

The PROFILE QUESTIONS REFERENCE and PROFILE STATE for this turn are provided below. Do not ask a profile question that is already answered or skipped. Only generate the profile when all 20 profile questions are covered (answered or skipped).`;

function createModel() {
  return new ChatOpenAI({
    modelName: process.env.OPENAI_MODEL || "gpt-4o-mini",
    temperature: 0.7,
    openAIApiKey: process.env.OPENAI_API_KEY,
  });
}

const PROFILE_MARKER = "---SELLER_PROFILE_READY---";

/**
 * Build the reference text of 20 standard questions for the seller.
 * @returns {string}
 */
function buildSellerQuestionsReference() {
  return (
    "PROFILE QUESTIONS REFERENCE (ask one at a time; use the short natural phrasing below — do not read long lists to the seller):\n" +
    SELLER_PROFILE_QUESTIONS.map(
      (q) => `- ${q.label} (id: ${q.id}): ${q.shortPrompt ?? q.exactQuestion}`
    ).join("\n")
  );
}

/**
 * Build a short summary of profile answers for the system prompt.
 * @param {Record<string, string | "skip">} profileAnswers
 * @param {boolean} domainPhaseComplete
 * @param {number} domainQuestionCount
 * @returns {string}
 */
function buildProfileStateSummary(profileAnswers, domainPhaseComplete = false, domainQuestionCount = 0) {
  const lines = [];
  if (domainPhaseComplete) {
    lines.push("Domain phase complete: Yes. You may now ask the 20 profile questions from the reference below, one at a time.");
  } else {
    lines.push("Domain phase complete: No. You MUST ask domain-specific questions only (minimum 5-8 total for a comprehensive understanding). Do NOT ask any profile question (no licensing, insurance, availability, pricing, payment methods, service area, experience, business structure, references, portfolio, reviews, languages, warranty, minimum job size, materials/equipment, or specializations) until domain phase is complete. Domain questions so far: " + domainQuestionCount + ". Continue asking domain questions to gather complete details about their trade/profession.");
  }
  if (!profileAnswers || Object.keys(profileAnswers).length === 0) {
    if (domainPhaseComplete) lines.push("Profile: None answered or skipped yet. Ask profile questions one at a time from the reference.");
    return "PROFILE STATE:\n" + lines.join("\n");
  }
  const answered = [];
  const skipped = [];
  for (const id of SELLER_PROFILE_QUESTION_IDS) {
    const v = profileAnswers[id];
    if (v === "skip") skipped.push(id);
    else if (v != null && String(v).trim() !== "") answered.push(`${id}=${String(v).trim().slice(0, 80)}`);
  }
  if (answered.length) lines.push("Answered: " + answered.join("; "));
  if (skipped.length) lines.push("Skipped (do not ask again): " + skipped.join(", "));
  const notYet = SELLER_PROFILE_QUESTION_IDS.filter((id) => profileAnswers[id] == null);
  if (notYet.length) lines.push("Not yet asked: " + notYet.join(", "));
  const totalCovered = answered.length + skipped.length;
  lines.push("Profile progress: " + totalCovered + "/20. Only generate the profile when all 20 are answered or skipped.");
  return "PROFILE STATE:\n" + lines.join("\n");
}

/**
 * Build a question-intent reference for the classifier so it can match natural phrasings
 * to the correct profile question ID (fixes under-recognition of "languages" and "additionalInfo").
 * @returns {string}
 */
function buildClassifierQuestionIntents() {
  const lines = SELLER_PROFILE_QUESTIONS.map((q) => {
    const intent = [q.label, q.shortPrompt].filter(Boolean).join(" — ");
    return `- ${q.id}: ${intent}`;
  });
  // Explicit intent hints for the two most commonly missed questions (natural phrasings)
  const hints = `
SPECIAL: Map to "languages" if the AI asked about languages spoken, communication languages, speaking other languages, serving non-English-speaking clients, or what languages the seller uses. Map to "additionalInfo" if the AI asked a catch-all like "anything else?", "what else should clients know?", "one last thing?", "what sets you apart?", or "any final thoughts for clients?".`;
  return "Profile questions (match the AI's question to exactly one ID by topic):\n" + lines.join("\n") + hints;
}

/**
 * Extract which profile question was just answered or skipped from the last exchange.
 * @param {ReturnType<typeof createModel>} model
 * @param {string} lastAiContent
 * @param {string} lastUserContent
 * @param {boolean} domainPhaseComplete
 * @returns {Promise<{ questionId: string, value: string | "skip" } | null>}
 */
async function extractProfileUpdate(model, lastAiContent, lastUserContent, domainPhaseComplete) {
  if (!lastAiContent?.trim() || !lastUserContent?.trim()) return null;
  // During Phase 1, do NOT classify as profile — always return null so we count domain questions
  if (!domainPhaseComplete) {
    console.log(LOG_TAG, "extractProfileUpdate: Phase 1 active, not classifying as profile");
    return null;
  }
  const questionIntents = buildClassifierQuestionIntents();
  const prompt = `You are a classifier. Given the last AI message (a single question to the user) and the user's reply, determine:
1. Was the AI asking one of these profile questions? Use the intents below to match natural phrasings to the correct ID.
${questionIntents}

2. If yes, which ID best matches the topic of the AI's question? And did the user answer (value = their answer in a short phrase) or skip (value = "skip")? Treat responses like "skip", "pass", "don't know", "not sure", "next", "I'll skip", "no preference" as skip.
3. If the AI was asking a domain-specific question (not one of the profile topics above), return null.

Reply with ONLY a single JSON object, no other text. Examples:
{"questionId":"licensing","value":"Yes, licensed electrician #12345"}
{"questionId":"portfolio","value":"skip"}
{"questionId":"languages","value":"English and Spanish"}
{"questionId":"additionalInfo","value":"We're reliable and insured."}
{"questionId":null,"value":null}

Last AI message:\n${lastAiContent.slice(0, 1500)}\n\nUser reply:\n${lastUserContent.slice(0, 800)}`;
  try {
    const response = await model.invoke([new SystemMessage(prompt)]);
    const text = (response.content ?? "").trim();
    const match = text.match(/\{[\s\S]*\}/);
    if (!match) return null;
    const parsed = JSON.parse(match[0]);
    if (parsed.questionId == null || parsed.value == null) return null;
    if (!SELLER_PROFILE_QUESTION_IDS.includes(parsed.questionId)) return null;
    const value = parsed.value === "skip" ? "skip" : String(parsed.value).trim();
    return { questionId: parsed.questionId, value };
  } catch (err) {
    console.error(LOG_TAG, "extractProfileUpdate error:", err.message);
    return null;
  }
}

async function gatherSellerInfoNode(state) {
  console.log(LOG_TAG, "gatherSellerInfoNode entry", {
    questionCount: state.questionCount,
    status: state.status,
    domainQuestionCount: state.domainQuestionCount || 0,
    domainPhaseComplete: state.domainPhaseComplete || false,
  });

  const model = createModel();

  // Build profile questions reference and state summary
  const profileQuestionsRef = buildSellerQuestionsReference();
  const currentProfileAnswers = state.profileAnswers || {};
  const currentDomainCount = state.domainQuestionCount || 0;
  const currentDomainPhaseComplete = state.domainPhaseComplete || false;
  const profileStateSummary = buildProfileStateSummary(
    currentProfileAnswers,
    currentDomainPhaseComplete,
    currentDomainCount
  );

  const messagesForLLM = [
    new SystemMessage(
      SYSTEM_PROMPT + "\n\n---\n" + profileQuestionsRef + "\n\n" + profileStateSummary
    ),
    ...state.messages,
  ];

  const response = await model.invoke(messagesForLLM);
  const content = response.content;

  const isProfileReady = content.includes(PROFILE_MARKER);

  if (isProfileReady) {
    const parts = content.split(PROFILE_MARKER);
    const profileContent = parts[1] ? parts[1].trim() : content;

    // Include the current turn's Q&A in the count (e.g. AI asked "languages", user answered — we haven't merged it yet)
    const messages = state.messages || [];
    let effectiveProfileAnswers = { ...currentProfileAnswers };
    if (messages.length >= 2 && currentDomainPhaseComplete) {
      const lastUser = messages[messages.length - 1];
      const lastAi = messages[messages.length - 2];
      const lastUserContent = typeof lastUser?.content === "string" ? lastUser.content : "";
      const lastAiContent = typeof lastAi?.content === "string" ? lastAi.content : "";
      const extracted = await extractProfileUpdate(model, lastAiContent, lastUserContent, currentDomainPhaseComplete);
      if (extracted && effectiveProfileAnswers[extracted.questionId] == null) {
        effectiveProfileAnswers[extracted.questionId] = extracted.value;
        console.log(LOG_TAG, "gatherSellerInfoNode profile-ready: included current turn", {
          questionId: extracted.questionId,
          value: extracted.value === "skip" ? "skip" : String(extracted.value).slice(0, 60),
        });
      }
    }

    const answeredOrSkipped = Object.keys(effectiveProfileAnswers).length;
    const allCovered = answeredOrSkipped >= SELLER_PROFILE_QUESTION_IDS.length;

    if (!allCovered) {
      console.log(LOG_TAG, "gatherSellerInfoNode profile generated too early", {
        answeredOrSkipped,
        required: SELLER_PROFILE_QUESTION_IDS.length,
        message: "Profile generated before all questions answered - continuing to gather",
      });
      // Merge the current-turn extraction into state so we don't lose it, then continue gathering
      const profileAnswersUpdate =
        Object.keys(effectiveProfileAnswers).length > Object.keys(currentProfileAnswers).length
          ? Object.fromEntries(
              Object.entries(effectiveProfileAnswers).filter(([id]) => currentProfileAnswers[id] == null)
            )
          : null;
      return {
        messages: [new AIMessage(content)],
        status: "gathering",
        questionCount: state.questionCount + 1,
        ...(profileAnswersUpdate && Object.keys(profileAnswersUpdate).length > 0 ? { profileAnswers: profileAnswersUpdate } : {}),
      };
    }

    const placeholderRegex = /\[([A-Z][A-Z\s/\-_]*(?:\:.*?)?)\]/g;
    const placeholders = [];
    let match;
    while ((match = placeholderRegex.exec(profileContent)) !== null) {
      placeholders.push(match[0]);
    }

    console.log(LOG_TAG, "gatherSellerInfoNode profile ready", {
      status: "reviewing",
      placeholdersCount: placeholders.length,
      profileAnswersCovered: answeredOrSkipped,
    });

    // Persist any current-turn extraction so state has 20/20
    const profileAnswersUpdate =
      Object.keys(effectiveProfileAnswers).length > Object.keys(currentProfileAnswers).length
        ? Object.fromEntries(
            Object.entries(effectiveProfileAnswers).filter(([id]) => currentProfileAnswers[id] == null)
          )
        : null;

    return {
      messages: [new AIMessage(content)],
      sellerProfile: profileContent,
      placeholders: placeholders,
      status: "reviewing",
      questionCount: state.questionCount,
      ...(profileAnswersUpdate && Object.keys(profileAnswersUpdate).length > 0 ? { profileAnswers: profileAnswersUpdate } : {}),
    };
  }

  // Extract profile update from last exchange (if in Phase 2)
  let profileAnswersUpdate = null;
  let domainQuestionCountIncrement = 0;
  let setDomainPhaseComplete = currentDomainPhaseComplete;

  const messages = state.messages || [];
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
        console.log(LOG_TAG, "gatherSellerInfoNode profile update", extracted);
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
  console.log(LOG_TAG, "gatherSellerInfoNode still gathering", {
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
  if (setDomainPhaseComplete !== currentDomainPhaseComplete) out.domainPhaseComplete = setDomainPhaseComplete;

  return out;
}

/**
 * Routing: after gatherSellerInfo
 * If profile ready (status reviewing) → reviewSellerProfile
 * Else → END (still gathering)
 */
function routeAfterGather(state) {
  if (state.status === "reviewing") {
    console.log(LOG_TAG, "routeAfterGather → reviewSellerProfile");
    return "reviewSellerProfile";
  }
  console.log(LOG_TAG, "routeAfterGather → END");
  return "__end__";
}

/**
 * Node: reviewSellerProfile
 * Human-in-the-loop: seller can confirm or request more details.
 */
async function reviewSellerProfileNode(state) {
  const lastMessage = state.messages[state.messages.length - 1];
  const sellerResponse = (lastMessage?.content ?? "").toLowerCase().trim();

  console.log(LOG_TAG, "reviewSellerProfileNode entry", {
    responseLength: sellerResponse.length,
  });

  const confirmPatterns = [
    "confirm",
    "yes",
    "looks good",
    "approve",
    "create",
    "go ahead",
    "perfect",
    "submit",
    "that's good",
    "that works",
    "ok",
    "okay",
    "sure",
    "do it",
    "lgtm",
    "good to go",
    "ready",
    "publish",
    "post it",
  ];

  const isConfirmed = confirmPatterns.some((p) => sellerResponse.includes(p));

  if (isConfirmed) {
    console.log(LOG_TAG, "reviewSellerProfileNode confirmed");
    return { status: "confirmed" };
  }

  console.log(LOG_TAG, "reviewSellerProfileNode wants more details → gathering");
  return { status: "gathering" };
}

/**
 * Routing: after reviewSellerProfile
 * If confirmed → createProfile; else → gatherSellerInfo
 */
function routeAfterReview(state) {
  if (state.status === "confirmed") {
    console.log(LOG_TAG, "routeAfterReview → createProfile");
    return "createProfile";
  }
  console.log(LOG_TAG, "routeAfterReview → gatherSellerInfo");
  return "gatherSellerInfo";
}

/**
 * Node: createProfile
 * Embeds the seller profile in Pinecone (namespace seller-profile) and marks done.
 */
async function createProfileNode(state, config) {
  console.log(LOG_TAG, "createProfileNode entry");

  const runnableConfig = AsyncLocalStorageProviderSingleton.getRunnableConfig?.();
  const threadId =
    config?.configurable?.thread_id ??
    runnableConfig?.configurable?.thread_id ??
    state.messages?.[0]?.id ??
    "unknown";

  let embeddingId = null;
  let profileMetadata = null;

  try {
    const result = await embedSellerProfile(state.sellerProfile, threadId);
    embeddingId = result.embeddingId;
    profileMetadata = result.profileMetadata;
    console.log(LOG_TAG, "createProfileNode embed success", {
      embeddingId,
      chunkCount: result.chunkCount,
    });
  } catch (err) {
    console.error(LOG_TAG, "createProfileNode embed failed (non-blocking):", err.message);
  }

  let matchedJobs = null;
  let jobMatchingStatus = null;
  if (state.sellerProfile && state.sellerProfile.trim().length > 0) {
    try {
      matchedJobs = await findMatchingJobs(state.sellerProfile);
      jobMatchingStatus = "found";
      console.log(LOG_TAG, "createProfileNode job matching complete", {
        jobMatchingStatus,
        matchedCount: matchedJobs?.length ?? 0,
      });
    } catch (err) {
      console.error(LOG_TAG, "createProfileNode findMatchingJobs failed (non-blocking):", err.message);
      jobMatchingStatus = "error";
    }
  }

  const model = createModel();
  const confirmPrompt = `The seller has confirmed and their profile has been created successfully. Generate a brief, friendly confirmation message. Tell them their profile is now live and clients will be able to find them. Keep it short and warm (2-3 sentences max). Do NOT include the full profile again.`;
  const response = await model.invoke([new SystemMessage(confirmPrompt)]);

  console.log(LOG_TAG, "createProfileNode done", { status: "done" });

  return {
    messages: [new AIMessage(response.content)],
    status: "done",
    embeddingId,
    profileMetadata,
    matchedJobs,
    jobMatchingStatus,
  };
}

export {
  gatherSellerInfoNode,
  routeAfterGather,
  reviewSellerProfileNode,
  routeAfterReview,
  createProfileNode,
  PROFILE_MARKER,
  buildClassifierQuestionIntents,
  extractProfileUpdate,
  createModel,
};
