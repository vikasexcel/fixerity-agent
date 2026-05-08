/**
 * Comprehensive unit tests for the buyer agent.
 *
 * Tests (no real OpenAI / DB / Pinecone needed):
 *   1. Profile question data integrity — 15 IDs, no duplicates, required fields
 *   2. getMissingJobPostFields — empty, full, partial
 *   3. buildProfileStateSummary — Phase 1 gate, Phase 2 progress, answered/skipped
 *   4. routeAfterGather — all status transitions
 *   5. reviewJobPostNode — confirm and change-request patterns
 *   6. gatherInfoNode — Phase 1 domain questions (no job post, counts domain)
 *   7. gatherInfoNode — Phase 1→2 transition at 5 domain questions
 *   8. gatherInfoNode — Phase 2 profile answer extraction
 *   9. gatherInfoNode — duplicate profile answer prevention
 *  10. gatherInfoNode — premature job post guard (re-invoke to ask next question)
 *  11. gatherInfoNode — happy path: all 15 answers → job post ready
 *  12. gatherInfoNode — skip handling: buyer skips optional fields
 *  13. gatherInfoNode — state preserved: domainPhaseComplete stays true in Phase 2
 *  14. gatherInfoNode — job post with all 15 sections → 0 missing fields
 *  15. End-to-end flow: confirm → status "confirmed"
 *  16. End-to-end flow: request changes → status back to "gathering"
 *  17. Edge cases: empty messages, null profileAnswers
 *
 * Run from agent-service: tsx scripts/test-buyer-agent-unit.js
 * Does NOT require a running server, OPENAI_API_KEY, or database.
 */

import {
  gatherInfoNode,
  routeAfterGather,
  reviewJobPostNode,
  routeAfterReview,
  JOB_POST_MARKER,
  PROFILE_BACKFILL_FROM_JOB_POST,
  getMissingJobPostFields,
  buildProfileStateSummary,
  buildProfileQuestionsReference,
} from "../src/agents/buyerAgent.js";
import { PROFILE_QUESTIONS, PROFILE_QUESTION_IDS } from "../src/data/profileQuestions.js";
import { HumanMessage, AIMessage } from "@langchain/core/messages";

// ─── Test harness ─────────────────────────────────────────────────────────────

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
    const msg = `${message} — expected ${JSON.stringify(expected)}, got ${JSON.stringify(actual)}`;
    failures.push(msg);
    console.log("  FAIL:", message);
    console.log("        expected:", JSON.stringify(expected));
    console.log("        got:     ", JSON.stringify(actual));
  }
}

function assertContains(str, substring, message) {
  const ok = typeof str === "string" && str.includes(substring);
  if (ok) {
    passed++;
    console.log("  OK:", message);
  } else {
    failed++;
    const msg = `${message} — "${substring}" not found in "${String(str).slice(0, 120)}"`;
    failures.push(msg);
    console.log("  FAIL:", message);
    console.log("        substring not found:", substring);
  }
}

function assertNotContains(str, substring, message) {
  const ok = typeof str === "string" && !str.includes(substring);
  if (ok) {
    passed++;
    console.log("  OK:", message);
  } else {
    failed++;
    const msg = `${message} — "${substring}" should NOT be in response`;
    failures.push(msg);
    console.log("  FAIL:", message);
  }
}

// ─── State helpers ────────────────────────────────────────────────────────────

function makeState(overrides = {}) {
  return {
    messages: [],
    questionCount: 0,
    status: "gathering",
    domainQuestionCount: 0,
    domainPhaseComplete: false,
    profileAnswers: {},
    jobPost: null,
    placeholders: [],
    ...overrides,
  };
}

/** Build a full set of answers for all 15 profile questions */
function allAnswers() {
  const answers = {};
  for (const id of PROFILE_QUESTION_IDS) {
    answers[id] = `answer for ${id}`;
  }
  return answers;
}

/** Build all answers except the given IDs */
function answersExcept(...excludeIds) {
  const answers = {};
  for (const id of PROFILE_QUESTION_IDS) {
    if (!excludeIds.includes(id)) {
      answers[id] = `answer for ${id}`;
    }
  }
  return answers;
}

// ─── Mock model factory ───────────────────────────────────────────────────────

/**
 * Creates a mock LLM model.
 * - mainResponse: what the main LLM call returns
 * - classifierResponses: map of partial-text-match → JSON string for classifier calls
 * - correctionResponse: what the correction call returns (when premature job post detected)
 */
function makeMockModel({ mainResponse, classifierResponse = '{"questionId":null,"value":null}', correctionResponse = "What is your budget?" } = {}) {
  let callCount = 0;
  const calls = [];
  return {
    _calls: calls,
    _getCallCount: () => callCount,
    invoke: async (messages) => {
      callCount++;
      const systemContent = (messages[0]?.content ?? "").slice(0, 300);
      calls.push({ callNumber: callCount, isClassifier: systemContent.includes("You are a classifier") });
      if (systemContent.includes("You are a classifier")) {
        return { content: typeof classifierResponse === "function" ? classifierResponse(callCount) : classifierResponse };
      }
      if (callCount > 1 && !mainResponse.includes(JOB_POST_MARKER)) {
        // Correction re-invoke
        return { content: correctionResponse };
      }
      return { content: mainResponse };
    },
  };
}

// ─── Section 1: Profile question data integrity ───────────────────────────────

console.log("\n=== 1. Profile question data integrity ===\n");

assertEq(PROFILE_QUESTION_IDS.length, 15, "exactly 15 profile question IDs");
assertEq(PROFILE_QUESTIONS.length, 15, "exactly 15 profile questions");
assert(
  new Set(PROFILE_QUESTION_IDS).size === 15,
  "no duplicate question IDs"
);
assert(
  PROFILE_QUESTIONS.every((q) => q.id && q.label && (q.shortPrompt || q.exactQuestion)),
  "all questions have required fields (id, label, shortPrompt or exactQuestion)"
);

const criticalIds = ["urgency", "budgetRange", "location", "timelineSchedule", "availability", "licensingCredentials", "decisionTimeline"];
for (const id of criticalIds) {
  assert(PROFILE_QUESTION_IDS.includes(id), `critical question ID "${id}" exists`);
}

// All IDs must be valid non-empty strings
assert(
  PROFILE_QUESTION_IDS.every((id) => typeof id === "string" && id.length > 0),
  "all question IDs are non-empty strings"
);

// ─── Section 2: getMissingJobPostFields ───────────────────────────────────────

console.log("\n=== 2. getMissingJobPostFields ===\n");

const missingEmpty = getMissingJobPostFields("");
assertEq(missingEmpty.length, 15, "empty job post → 15 missing fields");

const missingNull = getMissingJobPostFields(null);
assertEq(missingNull.length, 15, "null job post → 15 missing fields");

const minimalPost = PROFILE_QUESTIONS.map((q) => `**${q.label}:** (filled in)`).join("\n");
const missingFull = getMissingJobPostFields(minimalPost);
assertEq(missingFull.length, 0, "job post with all 15 labels → 0 missing fields");

const partialPost = "**Service Category Needed:** Repair\n**Location:** Brooklyn";
const missingPartial = getMissingJobPostFields(partialPost);
assert(missingPartial.length > 0 && missingPartial.length < 15, "partial post → some missing fields (not 0, not 15)");
assert(!missingPartial.includes("serviceCategory"), "serviceCategory present → not in missing list");
assert(!missingPartial.includes("location"), "location present → not in missing list");

// All 15 specific label checks (must include every label the getMissingJobPostFields checker scans)
const fullJobPost = [
  "**Job Title:** MacBook Repair",
  "**Service Category Needed:** Electronics",
  "**Specific Service Type:** Laptop Repair",
  "**Project Overview:** Screen flickering issue",
  "**Issues/Scope:** Screen flickers on startup",
  "**Project Urgency:** Soon (2-4 weeks)",
  "**Project Scope/Complexity:** Medium",
  "**Detailed Description:** The screen flickers intermittently on battery power.",
  "**Location:** Brooklyn, NY",
  "**Budget Range:** $200-$500",
  "**Timeline/Schedule:** Start next week",
  "**Availability for Consultation/Work:** Weekdays afternoon",
  "**Photos/Documentation:** No photos provided at this time",
  "**Special Requirements:** None",
  "**Licensing/Credentials Required:** Prefer licensed and insured",
  "**Decision Timeline:** Within 2 weeks",
  "**References/Reviews Important?:** Preferred",
  "**Additional Comments:** None",
].join("\n");

const missingFullJobPost = getMissingJobPostFields(fullJobPost);
assertEq(missingFullJobPost.length, 0, "complete job post with all sections → 0 missing fields");

const withoutDetailedHeading = fullJobPost
  .split("\n")
  .filter((line) => !/\*\*Detailed Description:\*\*/i.test(line))
  .join("\n");
assert(
  !withoutDetailedHeading.toLowerCase().includes("detailed descr"),
  "sanity: stripped template has no Detailed Description line"
);
assertEq(
  getMissingJobPostFields(withoutDetailedHeading).length,
  0,
  "mandatory-template post (Project Overview + Issues/Scope) satisfies detailedDescription check"
);

// ─── Section 3: buildProfileStateSummary ─────────────────────────────────────

console.log("\n=== 3. buildProfileStateSummary ===\n");

const summaryPhase1 = buildProfileStateSummary({}, false, 2);
assertContains(summaryPhase1, "Domain phase complete: No", "Phase 1: shows domain phase incomplete");
assertContains(summaryPhase1, "Domain questions so far: 2", "Phase 1: shows domain question count");
assertNotContains(summaryPhase1, "Domain phase complete: Yes", "Phase 1: does not say domain phase complete");

const summaryPhase2Empty = buildProfileStateSummary({}, true, 5);
assertContains(summaryPhase2Empty, "Domain phase complete: Yes", "Phase 2 empty: shows domain phase complete");
assertContains(summaryPhase2Empty, "None answered or skipped yet", "Phase 2 empty: shows no answers yet");

const summaryWithAnswers = buildProfileStateSummary(
  { urgency: "Soon (2-4 weeks)", budgetRange: "skip", location: "Brooklyn" },
  true,
  5
);
assertContains(summaryWithAnswers, "urgency=", "Phase 2 with answers: shows urgency");
assertContains(summaryWithAnswers, "Skipped", "Phase 2 with skips: shows skipped budgetRange");
assertContains(summaryWithAnswers, "budgetRange", "Phase 2 with skips: budgetRange in skipped list");
assertContains(summaryWithAnswers, "3/15", "Phase 2 with 3 covered: shows 3/15 progress");

const summaryAllAnswered = buildProfileStateSummary(allAnswers(), true, 5);
assertContains(summaryAllAnswered, "15/15", "Phase 2 all answered: shows 15/15 progress");
assertNotContains(summaryAllAnswered, "Not yet asked", "Phase 2 all answered: no 'not yet asked' section");

// ─── Section 4: routeAfterGather ─────────────────────────────────────────────

console.log("\n=== 4. routeAfterGather ===\n");

assertEq(routeAfterGather({ status: "gathering" }), "__end__", "gathering → __end__");
assertEq(routeAfterGather({ status: "reviewing" }), "reviewJobPost", "reviewing → reviewJobPost");
assertEq(routeAfterGather({ status: "confirmed" }), "__end__", "confirmed → __end__");
assertEq(routeAfterGather({ status: "done" }), "__end__", "done → __end__");

// ─── Section 5: reviewJobPostNode — confirm patterns ─────────────────────────

console.log("\n=== 5. reviewJobPostNode — confirm and change-request patterns ===\n");

const confirmPhrases = [
  "yes",
  "all good",
  "looks good",
  "approve",
  "publish",
  "ok",
  "okay",
  "perfect",
  "confirm",
  "go ahead",
  "submit",
  "post it",
  "do it",
  "lgtm",
  "good to go",
  "ready",
  "sure",
  "that's good",
  "that works",
  "create",
];

for (const phrase of confirmPhrases) {
  const result = await reviewJobPostNode({
    messages: [new HumanMessage(phrase)],
  });
  assertEq(result.status, "confirmed", `confirm phrase: "${phrase}"`);
}

const changePhrases = [
  "can you update the budget section?",
  "please fix the location",
  "I want to change something",
  "not quite right",
  "update the timeline",
  "the description needs more detail",
];

for (const phrase of changePhrases) {
  const result = await reviewJobPostNode({
    messages: [new HumanMessage(phrase)],
  });
  assertEq(result.status, "gathering", `change-request phrase: "${phrase}"`);
}

// ─── Section 6: routeAfterReview ─────────────────────────────────────────────

console.log("\n=== 6. routeAfterReview ===\n");

assertEq(routeAfterReview({ status: "confirmed" }), "createJob", "confirmed → createJob");
assertEq(routeAfterReview({ status: "gathering" }), "gatherInfo", "gathering → gatherInfo (wants changes)");

// ─── Section 7: gatherInfoNode — Phase 1 domain questions ────────────────────

console.log("\n=== 7. gatherInfoNode — Phase 1 domain questions ===\n");

{
  const domainQuestion = "What type of laptop do you have and what year was it made?";
  const mockModel = makeMockModel({ mainResponse: domainQuestion });

  const state = makeState({
    messages: [
      new HumanMessage("I need my laptop screen fixed"),
    ],
    questionCount: 0,
    domainQuestionCount: 0,
    domainPhaseComplete: false,
  });

  const result = await gatherInfoNode(state, null, mockModel);

  assertEq(result.status, "gathering", "Phase 1: status stays 'gathering'");
  assertEq(result.messages[0].content, domainQuestion, "Phase 1: domain question returned to user");
  assert(result.jobPost == null, "Phase 1: no job post generated");
  assert(result.domainPhaseComplete == null || result.domainPhaseComplete === false, "Phase 1 with 0 domain: domainPhaseComplete not set yet");
}

// ─── Section 8: gatherInfoNode — Phase 1→2 transition at 5 domain questions ──

console.log("\n=== 8. gatherInfoNode — Phase 1→2 transition at 5 domain questions ===\n");

{
  const nextDomainQuestion = "What symptoms did you notice first?";
  // Classifier says domain (null) — so domain count increments
  const mockModel = makeMockModel({
    mainResponse: nextDomainQuestion,
    classifierResponse: '{"questionId":null,"value":null}',
  });

  // 4 domain questions already done; this exchange will bring it to 5 → trigger transition
  const state = makeState({
    messages: [
      new HumanMessage("My MacBook screen flickers"),
      new AIMessage("What model is it?"),
      new HumanMessage("MacBook Pro 2021"),
      new AIMessage("Is it under warranty?"),
      new HumanMessage("No, out of warranty"),
      new AIMessage("Have you tried an external display?"),
      new HumanMessage("Yes, external is fine"),
      new AIMessage("When does the flickering happen?"),
      new HumanMessage("Only when on battery"),
    ],
    questionCount: 4,
    domainQuestionCount: 4,
    domainPhaseComplete: false,
  });

  const result = await gatherInfoNode(state, null, mockModel);

  assertEq(result.status, "gathering", "Phase 1→2 transition: status stays 'gathering'");
  assertEq(result.domainPhaseComplete, true, "Phase 1→2: domainPhaseComplete set to true when domain count reaches 5");
  assert(result.domainQuestionCount == null || result.domainQuestionCount >= 1, "Phase 1→2: domain count incremented");
}

// ─── Section 9: gatherInfoNode — Phase 2 profile answer extraction ────────────

console.log("\n=== 9. gatherInfoNode — Phase 2 profile answer extraction ===\n");

{
  const nextProfileQuestion = "For the MacBook repair, what's your budget?";
  const mockModel = makeMockModel({
    mainResponse: nextProfileQuestion,
    classifierResponse: '{"questionId":"urgency","value":"Soon (2-4 weeks)"}',
  });

  const state = makeState({
    messages: [
      new HumanMessage("My MacBook needs repair"),
      new AIMessage("How urgent is this?"),
      new HumanMessage("Within a few weeks is fine"),
    ],
    questionCount: 6,
    domainQuestionCount: 5,
    domainPhaseComplete: true,
    profileAnswers: {},
  });

  const result = await gatherInfoNode(state, null, mockModel);

  assertEq(result.status, "gathering", "Phase 2 extraction: status stays 'gathering'");
  assert(result.profileAnswers != null, "Phase 2 extraction: profileAnswers update returned");
  assertEq(result.profileAnswers?.urgency, "Soon (2-4 weeks)", "Phase 2 extraction: urgency answer captured");
}

// ─── Section 10: gatherInfoNode — duplicate answer prevention ─────────────────

console.log("\n=== 10. gatherInfoNode — duplicate answer prevention ===\n");

{
  const nextQ = "What's your preferred schedule?";
  const mockModel = makeMockModel({
    mainResponse: nextQ,
    classifierResponse: '{"questionId":"urgency","value":"Emergency"}',
  });

  // urgency is already answered — should NOT overwrite
  const state = makeState({
    messages: [
      new HumanMessage("I need this ASAP"),
      new AIMessage("How urgent is this?"),
      new HumanMessage("Emergency, today if possible"),
    ],
    questionCount: 6,
    domainQuestionCount: 5,
    domainPhaseComplete: true,
    profileAnswers: { urgency: "Emergency (within 24 hours)" }, // already set
  });

  const result = await gatherInfoNode(state, null, mockModel);

  // The returned profileAnswers update should be null (no overwrite)
  assert(
    result.profileAnswers == null || result.profileAnswers?.urgency == null,
    "Duplicate prevention: existing urgency answer not overwritten"
  );
}

// ─── Section 11: gatherInfoNode — premature job post guard ───────────────────

console.log("\n=== 11. gatherInfoNode — premature job post guard ===\n");

{
  // Only 5/15 profile questions answered — LLM tries to generate job post prematurely
  const prematureJobPost = `${JOB_POST_MARKER}
**Job Title:** MacBook Screen Repair
**Service Category Needed:** Electronics Repair
**Specific Service Type:** Laptop Screen Repair
**Project Overview:** MacBook screen flickers
**Issues/Scope:** Screen flickering on battery
**Project Urgency:** Soon
**Project Scope/Complexity:** Medium
**Location:** Brooklyn
**Budget Range:** $200-$400
**Timeline/Schedule:** Within 2 weeks
**Availability for Consultation/Work:** Weekdays
**Photos/Documentation:** No photos
**Special Requirements:** None
**Licensing/Credentials Required:** Prefer licensed
**Decision Timeline:** Within 2 weeks
**References/Reviews Important?:** Preferred
**Additional Comments:** None`;

  const correctionQ = "What's your preferred schedule — weekdays, weekends, or either?";

  let callCount = 0;
  let correctionCallMade = false;
  const mockModel = {
    _calls: [],
    invoke: async (messages) => {
      callCount++;
      const systemContent = messages[0]?.content ?? "";
      const isClassifier = systemContent.slice(0, 300).includes("You are a classifier");
      mockModel._calls.push({ callCount, isClassifier });

      if (isClassifier) {
        // Classifier: this turn was domain question (null), so no profile update from this exchange
        return { content: '{"questionId":null,"value":null}' };
      }
      if (callCount === 1) {
        // First (main) call: return premature job post
        return { content: prematureJobPost };
      }
      // Any subsequent non-classifier call is the correction re-invoke
      correctionCallMade = true;
      return { content: correctionQ };
    },
  };

  const state = makeState({
    messages: [
      new HumanMessage("I need MacBook repair"),
      new AIMessage("What model?"),
      new HumanMessage("MacBook Pro 2021"),
      new AIMessage("Any warranty?"),
      new HumanMessage("No warranty"),
    ],
    questionCount: 5,
    domainQuestionCount: 5,
    domainPhaseComplete: true,
    // Only 5 of 15 profile answers tracked — not enough
    profileAnswers: {
      urgency: "Soon",
      location: "Brooklyn",
      budgetRange: "skip",
      serviceCategory: "Electronics Repair",
      specificServiceType: "Laptop Screen Repair",
    },
  });

  const result = await gatherInfoNode(state, null, mockModel);

  assertEq(result.status, "gathering", "premature job post guard: status stays 'gathering'");
  assert(result.jobPost == null, "premature job post guard: jobPost NOT set in state");
  assertNotContains(result.messages[0].content, JOB_POST_MARKER, "premature job post guard: marker not sent to user");
  assertNotContains(result.messages[0].content, "MacBook Screen Repair", "premature job post guard: job post content not sent to user");
  assertEq(result.messages[0].content, correctionQ, "premature job post guard: correction question sent instead");
  assertEq(result.domainPhaseComplete, true, "premature job post guard: domainPhaseComplete forced true");
  assert(correctionCallMade, "premature job post guard: correction re-invoke made");
}

// ─── Section 11b: structural backfill — complete post in body, extraction missed some IDs ─

console.log("\n=== 11b. gatherInfoNode — structural backfill (complete post, partial tracking) ===\n");

{
  const completePost = `${JOB_POST_MARKER}
${fullJobPost}

Please review this job post.`;

  const mockModel = makeMockModel({
    mainResponse: completePost,
    classifierResponse: '{"questionId":null,"value":null}',
  });

  const tenAnswers = answersExcept(
    "serviceCategory",
    "specificServiceType",
    "scopeComplexity",
    "detailedDescription",
    "decisionTimeline"
  );

  const state = makeState({
    messages: [
      new HumanMessage("I need help"),
      new AIMessage("Tell me more"),
      new HumanMessage("here are the details"),
    ],
    questionCount: 20,
    domainQuestionCount: 5,
    domainPhaseComplete: true,
    profileAnswers: tenAnswers,
  });

  const result = await gatherInfoNode(state, null, mockModel);

  assertEq(result.status, "reviewing", "structural backfill: reaches reviewing");
  assert(result.jobPost != null && result.jobPost.length > 0, "structural backfill: jobPost set");
  assertEq(result.profileAnswers?.serviceCategory, PROFILE_BACKFILL_FROM_JOB_POST, "backfill: serviceCategory");
  assertEq(result.profileAnswers?.decisionTimeline, PROFILE_BACKFILL_FROM_JOB_POST, "backfill: decisionTimeline");
}

// ─── Section 12: gatherInfoNode — happy path: all 15 answers → job post ready ─

console.log("\n=== 12. gatherInfoNode — happy path: all 15 answered → job post ready ===\n");

{
  const lastAnswer = "No special requirements";
  const jobPostContent = `${JOB_POST_MARKER}
**Job Title:** MacBook Pro Screen Repair
**Service Category Needed:** Electronics Repair
**Specific Service Type:** Laptop Screen Repair
**Project Overview:** MacBook Pro 2021 screen flickering on battery power
**Issues/Scope:** Screen flickers when running on battery
**Project Urgency:** Soon (2-4 weeks)
**Project Scope/Complexity:** Medium
**Detailed Description:** MacBook Pro 2021 screen flickers intermittently when running on battery power. It works fine with an external display. No warranty. Tried resetting SMC but issue persists.
**Location:** Brooklyn, NY 11201
**Budget Range:** $200-$500
**Timeline/Schedule:** Start within 1 week
**Availability for Consultation/Work:** Weekdays, afternoons preferred
**Photos/Documentation:** No photos provided at this time
**Special Requirements:** None
**Licensing/Credentials Required:** Prefer licensed and insured
**Decision Timeline:** Within 2 weeks
**References/Reviews Important?:** Preferred
**Additional Comments:** None`;

  const mockModel = makeMockModel({
    mainResponse: jobPostContent,
    classifierResponse: '{"questionId":"specialRequirements","value":"None"}',
  });

  // 14 of 15 answered — classifier will pick up specialRequirements this turn → 15/15
  const answers14 = answersExcept("specialRequirements");

  const state = makeState({
    messages: [
      new HumanMessage("I need MacBook repair"),
      ...Array.from({ length: 14 }, (_, i) => [
        new AIMessage(`Profile question ${i + 1}?`),
        new HumanMessage(`Answer ${i + 1}`),
      ]).flat(),
      new AIMessage("Any special requirements?"),
      new HumanMessage(lastAnswer),
    ],
    questionCount: 15,
    domainQuestionCount: 5,
    domainPhaseComplete: true,
    profileAnswers: answers14,
  });

  const result = await gatherInfoNode(state, null, mockModel);

  assertEq(result.status, "reviewing", "happy path: status becomes 'reviewing'");
  assert(result.jobPost != null && result.jobPost.length > 0, "happy path: jobPost is set in state");
  assertContains(result.jobPost, "MacBook Pro Screen Repair", "happy path: jobPost contains job title");
  assertNotContains(result.jobPost, JOB_POST_MARKER, "happy path: jobPost does not contain the marker");
  assert(result.profileAnswers?.specialRequirements === "None", "happy path: last profile answer persisted in state");
  const missing = getMissingJobPostFields(result.jobPost);
  assertEq(missing.length, 0, "happy path: generated job post has 0 missing fields");
}

// ─── Section 13: gatherInfoNode — skip handling ──────────────────────────────

console.log("\n=== 13. gatherInfoNode — skip handling ===\n");

{
  // Buyer explicitly skips budget
  const mockModel = makeMockModel({
    mainResponse: "What's your preferred timeline?",
    classifierResponse: '{"questionId":"budgetRange","value":"skip"}',
  });

  const state = makeState({
    messages: [
      new HumanMessage("I need cleaning services"),
      new AIMessage("What's your budget?"),
      new HumanMessage("I'd rather just get estimates"),
    ],
    questionCount: 6,
    domainQuestionCount: 5,
    domainPhaseComplete: true,
    profileAnswers: {},
  });

  const result = await gatherInfoNode(state, null, mockModel);

  assertEq(result.profileAnswers?.budgetRange, "skip", "skip handling: 'skip' value stored for budgetRange");
  assertEq(result.status, "gathering", "skip handling: status stays 'gathering'");
}

{
  // Variety of skip phrases
  const skipPhrases = ["skip", "pass", "don't know", "not sure", "next", "I'll skip", "no preference"];
  for (const phrase of skipPhrases) {
    const mockModel = makeMockModel({
      mainResponse: "What's your preferred timeline?",
      classifierResponse: '{"questionId":"availability","value":"skip"}',
    });

    const state = makeState({
      messages: [
        new HumanMessage("I need cleaning"),
        new AIMessage("What times work for you?"),
        new HumanMessage(phrase),
      ],
      questionCount: 7,
      domainQuestionCount: 5,
      domainPhaseComplete: true,
      profileAnswers: {},
    });

    const result = await gatherInfoNode(state, null, mockModel);
    assertEq(result.profileAnswers?.availability, "skip", `skip phrase "${phrase}" → stores "skip"`);
  }
}

// ─── Section 14: gatherInfoNode — domainPhaseComplete preserved in Phase 2 ───

console.log("\n=== 14. gatherInfoNode — domainPhaseComplete preserved in Phase 2 ===\n");

{
  // In Phase 2, LLM asks profile question; classifier matches it → domainQuestionCountIncrement = 0
  // newDomainCount = 5 (still >= 5) → setDomainPhaseComplete = true → returned
  const mockModel = makeMockModel({
    mainResponse: "When do you need this done?",
    classifierResponse: '{"questionId":"timelineSchedule","value":"Within 2 weeks"}',
  });

  const state = makeState({
    messages: [
      new HumanMessage("I need cleaning"),
      new AIMessage("How urgent is this?"),
      new HumanMessage("Within 2 weeks"),
    ],
    questionCount: 6,
    domainQuestionCount: 5,
    domainPhaseComplete: true, // already true
    profileAnswers: { urgency: "Soon" },
  });

  const result = await gatherInfoNode(state, null, mockModel);

  assertEq(result.status, "gathering", "domainPhaseComplete preserved: status is gathering");
  // domainPhaseComplete should be true in return (either explicitly set or LangGraph keeps old value)
  // Since newDomainCount=5 >= 5, setDomainPhaseComplete=true → returned in out
  assertEq(result.domainPhaseComplete, true, "domainPhaseComplete preserved: stays true after Phase 2 profile Q");
}

// ─── Section 15: gatherInfoNode — Phase 1 gate (domain < 5, no profile Qs) ──

console.log("\n=== 15. gatherInfoNode — Phase 1 gate enforced (domain < 5) ===\n");

{
  const domainQ = "What's the age and breed of your dog?";
  const mockModel = makeMockModel({
    mainResponse: domainQ,
    classifierResponse: '{"questionId":null,"value":null}',
  });

  const state = makeState({
    messages: [
      new HumanMessage("I need a dog walker"),
      new AIMessage("How many dogs do you have?"),
      new HumanMessage("Just one"),
    ],
    questionCount: 1,
    domainQuestionCount: 1,
    domainPhaseComplete: false,
  });

  const result = await gatherInfoNode(state, null, mockModel);

  assertEq(result.status, "gathering", "Phase 1 gate: status stays gathering");
  assert(result.domainPhaseComplete == null || result.domainPhaseComplete === false, "Phase 1 gate: domainPhaseComplete stays false (only 2 domain questions)");
  assertEq(result.messages[0].content, domainQ, "Phase 1 gate: domain question returned");
}

// ─── Section 16: gatherInfoNode — empty messages (conversation start) ─────────

console.log("\n=== 16. gatherInfoNode — empty messages / conversation start ===\n");

{
  const openingQ = "What type of service are you looking for today?";
  const mockModel = makeMockModel({ mainResponse: openingQ });

  const state = makeState({ messages: [] });

  const result = await gatherInfoNode(state, null, mockModel);

  assertEq(result.status, "gathering", "empty messages: status is 'gathering'");
  assertEq(result.messages[0].content, openingQ, "empty messages: opening question returned");
  assert(result.questionCount === 1, "empty messages: questionCount incremented to 1");
  assert(result.jobPost == null, "empty messages: no job post");
  // No messages → no domain count extracted (messages.length < 2)
  assert(result.domainQuestionCount == null || result.domainQuestionCount === 0, "empty messages: no domain count increment");
}

// ─── Section 17: gatherInfoNode — null/undefined profileAnswers graceful ──────

console.log("\n=== 17. gatherInfoNode — null profileAnswers handled gracefully ===\n");

{
  const q = "What type of service do you need?";
  const mockModel = makeMockModel({ mainResponse: q });

  // State with null profileAnswers
  const state = makeState({ messages: [new HumanMessage("help me")], profileAnswers: null });

  let threw = false;
  let result;
  try {
    result = await gatherInfoNode(state, null, mockModel);
  } catch (e) {
    threw = true;
    console.log("  ERROR:", e.message);
  }

  assert(!threw, "null profileAnswers: no exception thrown");
  assert(result?.status === "gathering", "null profileAnswers: returns gathering state");
}

// ─── Section 18: Full end-to-end: confirm flow → 'confirmed' status ────────────

console.log("\n=== 18. End-to-end: confirm flow ===\n");

{
  // reviewJobPostNode confirms → routeAfterReview sends to createJob
  const buyerConfirm = await reviewJobPostNode({
    messages: [new HumanMessage("looks good, go ahead")],
  });
  assertEq(buyerConfirm.status, "confirmed", "e2e confirm: status is 'confirmed'");
  assertEq(routeAfterReview(buyerConfirm), "createJob", "e2e confirm: routes to createJob");
}

// ─── Section 19: Full end-to-end: change request → back to gatherInfo ──────────

console.log("\n=== 19. End-to-end: change request flow ===\n");

{
  const buyerChange = await reviewJobPostNode({
    messages: [new HumanMessage("Can you update the budget range? I have a specific number in mind")],
  });
  assertEq(buyerChange.status, "gathering", "e2e change: status is 'gathering'");
  assertEq(routeAfterReview(buyerChange), "gatherInfo", "e2e change: routes back to gatherInfo");
}

// ─── Section 20: buildProfileQuestionsReference ───────────────────────────────

console.log("\n=== 20. buildProfileQuestionsReference ===\n");

{
  const ref = buildProfileQuestionsReference();
  assert(typeof ref === "string" && ref.length > 0, "reference: returns non-empty string");
  assertContains(ref, "PROFILE QUESTIONS REFERENCE", "reference: contains header");
  for (const q of PROFILE_QUESTIONS) {
    assertContains(ref, q.id, `reference: contains question id "${q.id}"`);
    assertContains(ref, q.label, `reference: contains question label "${q.label}"`);
  }
}

// ─── Section 21: State flow — review → change → re-gather preserves profile answers

console.log("\n=== 21. State preservation: profile answers survive review→change loop ===\n");

{
  // Simulate the state at review point with full profile answers
  const fullAnswers = allAnswers();

  // reviewJobPostNode does NOT touch profileAnswers — it only returns {status}
  const changeResult = await reviewJobPostNode({
    messages: [new HumanMessage("update the location please")],
  });
  assertEq(changeResult.status, "gathering", "review→change: status is gathering");
  assert(
    changeResult.profileAnswers == null,
    "review→change: reviewJobPostNode does not reset profileAnswers"
  );

  // When gatherInfo runs again with existing profile answers, they are preserved
  const jobPostAfterChange = `${JOB_POST_MARKER}
**Job Title:** Updated Job
**Service Category Needed:** Electronics
**Specific Service Type:** Laptop Repair
**Project Overview:** Updated overview
**Issues/Scope:** Screen issues
**Project Urgency:** Soon
**Project Scope/Complexity:** Medium
**Detailed Description:** MacBook screen flickers on battery. Tried SMC reset. No external display issue.
**Location:** Manhattan, NY
**Budget Range:** $300-$600
**Timeline/Schedule:** Next week
**Availability for Consultation/Work:** Weekdays
**Photos/Documentation:** No photos
**Special Requirements:** None
**Licensing/Credentials Required:** Prefer licensed
**Decision Timeline:** Within 2 weeks
**References/Reviews Important?:** Preferred
**Additional Comments:** None`;

  const mockModel = makeMockModel({
    mainResponse: jobPostAfterChange,
    classifierResponse: '{"questionId":"additionalComments","value":"skip"}',
  });

  // 14 answers in state, classifier picks up 15th this turn
  const answers14 = answersExcept("additionalComments");

  const state = makeState({
    messages: [
      new HumanMessage("update the location"),
      new AIMessage("Sure, anything else to change?"),
      new HumanMessage("no, that's all"),
    ],
    questionCount: 16,
    domainQuestionCount: 5,
    domainPhaseComplete: true,
    profileAnswers: answers14,
  });

  const result = await gatherInfoNode(state, null, mockModel);

  assertEq(result.status, "reviewing", "review→change→re-gather: job post regenerated to reviewing");
  assert(result.jobPost?.includes("Manhattan, NY"), "review→change→re-gather: updated location in job post");
}

// ─── Summary ──────────────────────────────────────────────────────────────────

console.log("\n" + "─".repeat(60));
console.log(`${passed} passed, ${failed} failed`);
if (failures.length > 0) {
  console.log("\nFailed assertions:");
  failures.forEach((f) => console.log("  -", f));
}
process.exit(failed > 0 ? 1 : 0);
