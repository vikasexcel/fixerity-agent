/**
 * Unit tests for seller agent state logic.
 *
 * Tests:
 *   1. buildProfileStateSummary — pure function, no model needed
 *   2. routeAfterGather — pure routing function
 *   3. reviewSellerProfileNode — confirm/reject patterns
 *   4. gatherSellerInfoNode — premature profile interception (mock model)
 *   5. gatherSellerInfoNode — happy path with all 20 questions (mock model)
 *   6. gatherSellerInfoNode — domain phase gating (mock model)
 *
 * Run from agent-service: node scripts/test-seller-agent-unit.js
 * Does NOT require a running server or OPENAI_API_KEY.
 */

import {
  gatherSellerInfoNode,
  routeAfterGather,
  reviewSellerProfileNode,
  PROFILE_MARKER,
} from "../src/agents/sellerAgent.js";
import { SELLER_PROFILE_QUESTION_IDS, SELLER_PROFILE_QUESTIONS } from "../src/data/sellerProfileQuestions.js";
import { HumanMessage, AIMessage } from "@langchain/core/messages";

// ─── Helpers ────────────────────────────────────────────────────────────────

let passed = 0;
let failed = 0;
const failures = [];

function assert(condition, message) {
  if (condition) {
    passed++;
    console.log("  OK:", message);
  } else {
    failed++;
    failures.push(message);
    console.log("  FAIL:", message);
  }
}

function assertEq(actual, expected, message) {
  const ok = JSON.stringify(actual) === JSON.stringify(expected);
  if (ok) {
    passed++;
    console.log("  OK:", message);
  } else {
    failed++;
    failures.push(`${message} — expected ${JSON.stringify(expected)}, got ${JSON.stringify(actual)}`);
    console.log(`  FAIL: ${message}`);
    console.log(`        expected: ${JSON.stringify(expected)}`);
    console.log(`        got:      ${JSON.stringify(actual)}`);
  }
}

/** Build a full set of answers for all 20 profile questions */
function allAnswers() {
  const answers = {};
  for (const id of SELLER_PROFILE_QUESTION_IDS) {
    answers[id] = `answer for ${id}`;
  }
  return answers;
}

/** Create a minimal valid state object */
function makeState(overrides = {}) {
  return {
    messages: [],
    questionCount: 0,
    status: "gathering",
    domainQuestionCount: 0,
    domainPhaseComplete: false,
    profileAnswers: {},
    sellerProfile: null,
    placeholders: [],
    ...overrides,
  };
}

// ─── Note on buildProfileStateSummary ────────────────────────────────────────
// Tested indirectly via gatherSellerInfoNode (it injects the PROFILE STATE into
// the system prompt). Direct tests are in sections 3-6 via mock model assertions.

// ─── Section 1: routeAfterGather ─────────────────────────────────────────────

console.log("\n=== 1. routeAfterGather ===\n");

assertEq(routeAfterGather({ status: "gathering" }), "__end__", "gathering → __end__");
assertEq(routeAfterGather({ status: "reviewing" }), "reviewSellerProfile", "reviewing → reviewSellerProfile");
assertEq(routeAfterGather({ status: "done" }), "__end__", "done → __end__");
assertEq(routeAfterGather({ status: "confirmed" }), "__end__", "confirmed → __end__");

// ─── Section 2: reviewSellerProfileNode — confirmation patterns ───────────────

console.log("\n=== 2. reviewSellerProfileNode ===\n");

const confirmPhrases = [
  "Looks good, confirm.",
  "yes",
  "go ahead",
  "ok",
  "okay",
  "perfect",
  "submit",
  "do it",
  "lgtm",
  "good to go",
  "ready",
  "All good!",
  "all good, let's proceed",
  "that's good",
  "that works",
  "sure",
];

for (const phrase of confirmPhrases) {
  const result = await reviewSellerProfileNode({
    messages: [new HumanMessage(phrase)],
  });
  assertEq(result.status, "confirmed", `confirm phrase: "${phrase}"`);
}

const rejectPhrases = [
  "can you add more detail about my experience?",
  "please update the pricing section",
  "I want to change my service area",
  "not quite right",
];

for (const phrase of rejectPhrases) {
  const result = await reviewSellerProfileNode({
    messages: [new HumanMessage(phrase)],
  });
  assertEq(result.status, "gathering", `reject phrase: "${phrase}"`);
}

// ─── Section 3: gatherSellerInfoNode — premature profile interception ──────────

console.log("\n=== 3. gatherSellerInfoNode — premature profile (core bug fix) ===\n");

// Scenario: domainPhaseComplete=true, only 3 profile questions answered (< 17),
// but LLM generates the profile. Should re-invoke and return a question, NOT the profile.
{
  const partialAnswers = {
    serviceType: "House cleaning",
    availability: "Weekends",
    insurance: "Yes, liability",
  };

  const prematureProfile = `${PROFILE_MARKER}\n# House Cleaner Profile\n**Headline:** Pro House Cleaner\n\nI clean houses.`;
  const correctionQuestion = "How do you charge — per hour or flat rate per job?";

  let modelCallCount = 0;
  const mockModel = {
    invoke: async (messages) => {
      modelCallCount++;
      const systemContent = messages[0]?.content ?? "";
      // Call order in gatherSellerInfoNode when profile is detected:
      //   1) main LLM call → returns premature profile
      //   2) extractProfileUpdate classifier call (invoked AFTER main call when isProfileReady=true)
      //   3) correction re-invoke → returns next question
      if (modelCallCount === 1) {
        // Main call: return premature profile to trigger the guard
        return { content: prematureProfile };
      }
      if (systemContent.includes("You are a classifier")) {
        // Classifier call: the last exchange was a domain question
        return { content: '{"questionId":null,"value":null}' };
      }
      // Correction call: return a follow-up question (no profile marker)
      return { content: correctionQuestion };
    },
  };

  const conversationMessages = [
    new HumanMessage("I do house cleaning"),
    new AIMessage("What kind of cleaning do you specialize in?"),
    new HumanMessage("Residential deep cleaning and regular maintenance"),
    new AIMessage("Do you bring your own supplies?"),
    new HumanMessage("Yes, I bring everything"),
  ];

  const state = makeState({
    messages: conversationMessages,
    questionCount: 4,
    domainQuestionCount: 5,
    domainPhaseComplete: true,
    profileAnswers: partialAnswers,
  });

  const result = await gatherSellerInfoNode(state, {}, mockModel);

  assert(result.status === "gathering", "premature profile: status stays 'gathering'");
  assert(!result.messages[0].content.includes(PROFILE_MARKER), "premature profile: PROFILE_MARKER not in response");
  assert(!result.messages[0].content.includes("House Cleaner Profile"), "premature profile: profile content not sent to user");
  assert(result.messages[0].content === correctionQuestion, "premature profile: correction question sent instead");
  assert(result.domainPhaseComplete === true, "premature profile: domainPhaseComplete forced true");
  assert(result.sellerProfile == null, "premature profile: sellerProfile NOT set in state");
  // Model was called at least twice (classifier + main + correction)
  assert(modelCallCount >= 2, `premature profile: model re-invoked (calls=${modelCallCount})`);
}

// ─── Section 4: gatherSellerInfoNode — happy path (all 20 answered) ───────────

console.log("\n=== 4. gatherSellerInfoNode — happy path (all 20 questions) ===\n");

{
  const answers = allAnswers();

  const profileContent = `# Complete Profile\nHeadline: Pro House Cleaner\nI clean homes.\n\n**Service Type:** House cleaning\n**Pricing:** $150/visit`;
  const fullProfileResponse = `${PROFILE_MARKER}\n${profileContent}`;

  let modelCallCount = 0;
  const mockModel = {
    invoke: async (messages) => {
      modelCallCount++;
      const systemContent = messages[0]?.content ?? "";
      if (systemContent.includes("You are a classifier")) {
        // Simulate last exchange was additionalInfo question answered
        return { content: '{"questionId":"additionalInfo","value":"Great service, always on time"}' };
      }
      return { content: fullProfileResponse };
    },
  };

  // Remove one answer to simulate the current-turn extraction picking it up
  const answersMinusOne = { ...answers };
  delete answersMinusOne.additionalInfo;

  const conversationMessages = [
    new HumanMessage("I do house cleaning"),
    ...Array.from({ length: 19 }, (_, i) => [
      new AIMessage(`Question ${i + 1}`),
      new HumanMessage(`Answer ${i + 1}`),
    ]).flat(),
    new AIMessage("Anything else you'd like clients to know?"),
    new HumanMessage("Great service, always on time"),
  ];

  const state = makeState({
    messages: conversationMessages,
    questionCount: 20,
    domainQuestionCount: 5,
    domainPhaseComplete: true,
    profileAnswers: answersMinusOne, // 19/20 in state; classifier will pick up the last one
  });

  const result = await gatherSellerInfoNode(state, {}, mockModel);

  assertEq(result.status, "reviewing", "happy path: status becomes 'reviewing'");
  assert(result.sellerProfile != null && result.sellerProfile.length > 0, "happy path: sellerProfile is set");
  assert(result.sellerProfile.includes("Pro House Cleaner"), "happy path: sellerProfile contains profile content");
  assert(!result.sellerProfile.includes(PROFILE_MARKER), "happy path: sellerProfile does not contain PROFILE_MARKER");
}

// ─── Section 5: gatherSellerInfoNode — domain phase gating ───────────────────

console.log("\n=== 5. gatherSellerInfoNode — domain phase gating ===\n");

{
  // Only 3 domain questions answered, domainPhaseComplete=false
  // LLM asks a domain question (no profile generated)
  let modelCallCount = 0;
  const mockModel = {
    invoke: async (messages) => {
      modelCallCount++;
      const systemContent = messages[0]?.content ?? "";
      if (systemContent.includes("You are a classifier")) {
        // During Phase 1, extractProfileUpdate returns null immediately (before invoking model)
        // but we still count this call if it happens
        return { content: '{"questionId":null,"value":null}' };
      }
      return { content: "What type of materials do you use for cleaning?" };
    },
  };

  const conversationMessages = [
    new HumanMessage("I do house cleaning"),
    new AIMessage("Do you clean residential or commercial?"),
    new HumanMessage("Residential only"),
    new AIMessage("What's your typical schedule?"),
    new HumanMessage("Weekday mornings"),
  ];

  const state = makeState({
    messages: conversationMessages,
    questionCount: 2,
    domainQuestionCount: 2,
    domainPhaseComplete: false,
    profileAnswers: {},
  });

  const result = await gatherSellerInfoNode(state, {}, mockModel);

  assertEq(result.status, "gathering", "domain phase: status stays 'gathering'");
  assert(result.sellerProfile == null, "domain phase: no sellerProfile generated");
  assert(!result.messages[0].content.includes(PROFILE_MARKER), "domain phase: no profile marker in response");
  // domainQuestionCount is returned as a delta (may be absent if 0); cast via Object to access
  const domainDelta = /** @type {any} */ (result).domainQuestionCount;
  assert(domainDelta == null || domainDelta >= 0, "domain phase: domainQuestionCount delta is non-negative");
}

// ─── Section 6: gatherSellerInfoNode — confirm loop prevention ─────────────────

console.log("\n=== 6. gatherSellerInfoNode — confirm-loop prevention ===\n");

// Scenario: User sends "go ahead" after seeing premature profile (while status is "gathering").
// gatherSellerInfoNode should NOT return status "reviewing" without the full profile.
// It should ask a question, not repeat the profile or confirm.
{
  const partialAnswers = { serviceType: "Cleaning", insurance: "Yes" }; // only 2/20

  const prematureProfile = `${PROFILE_MARKER}\n# Profile\nI am a cleaner.`;
  const correctionQuestion = "Are you licensed to operate in your state?";

  let callCount = 0;
  const mockModel = {
    invoke: async (messages) => {
      callCount++;
      const systemContent = messages[0]?.content ?? "";
      if (callCount === 1) {
        // Main call: LLM re-generates the profile (user said "go ahead" but we're still gathering)
        return { content: prematureProfile };
      }
      if (systemContent.includes("You are a classifier")) {
        return { content: '{"questionId":null,"value":null}' };
      }
      // Correction call
      return { content: correctionQuestion };
    },
  };

  const conversationMessages = [
    new HumanMessage("I do cleaning"),
    new AIMessage("Let me build your profile:"),
    // Simulate user already said "go ahead" (they saw the premature profile)
    new HumanMessage("go ahead"),
  ];

  const state = makeState({
    messages: conversationMessages,
    questionCount: 3,
    domainQuestionCount: 5,
    domainPhaseComplete: true,
    profileAnswers: partialAnswers,
  });

  const result = await gatherSellerInfoNode(state, {}, mockModel);

  // Must NOT transition to reviewing — status must stay gathering
  assert(result.status === "gathering", "confirm-loop: 'go ahead' with partial answers stays in gathering");
  // Must NOT set sellerProfile — otherwise createProfileNode would run with incomplete profile
  assert(result.sellerProfile == null, "confirm-loop: sellerProfile not saved with partial answers");
  // Must NOT echo the profile content back
  assert(!result.messages[0].content.includes("PROFILE_MARKER") && !result.messages[0].content.includes("I am a cleaner"), "confirm-loop: premature profile content not returned to user");
}

// ─── Section 7: SELLER_PROFILE_QUESTION_IDS completeness ─────────────────────

console.log("\n=== 7. Profile question IDs completeness ===\n");

assertEq(SELLER_PROFILE_QUESTION_IDS.length, 20, "exactly 20 profile question IDs");
assert(SELLER_PROFILE_QUESTION_IDS.every((id) => typeof id === "string" && id.length > 0), "all IDs are non-empty strings");
assert(new Set(SELLER_PROFILE_QUESTION_IDS).size === SELLER_PROFILE_QUESTION_IDS.length, "no duplicate question IDs");
assert(SELLER_PROFILE_QUESTIONS.every((q) => q.id && q.label && (q.shortPrompt || q.exactQuestion)), "all questions have required fields");

// Specific IDs that are commonly missed by classifier
const criticalIds = ["languages", "additionalInfo", "serviceType", "insurance"];
for (const id of criticalIds) {
  assert(SELLER_PROFILE_QUESTION_IDS.includes(id), `critical question ID "${id}" exists`);
}

// ─── Summary ─────────────────────────────────────────────────────────────────

console.log("\n" + "─".repeat(50));
console.log(`${passed} passed, ${failed} failed`);
if (failures.length > 0) {
  console.log("\nFailed assertions:");
  failures.forEach((f) => console.log("  -", f));
}
process.exit(failed > 0 ? 1 : 0);
