/**
 * Test script for buyer agent: 15 profile fields and job post validation.
 * Run from agent-service: node scripts/test-buyer-agent.js
 */
import { PROFILE_QUESTIONS, PROFILE_QUESTION_IDS } from "../src/data/profileQuestions.js";
import { getMissingJobPostFields } from "../src/agents/buyerAgent.js";

let passed = 0;
let failed = 0;

function assert(condition, message) {
  if (condition) {
    passed++;
    console.log("  OK:", message);
  } else {
    failed++;
    console.log("  FAIL:", message);
  }
}

console.log("Buyer Agent tests\n");

// 1. Exactly 15 profile questions
assert(PROFILE_QUESTION_IDS.length === 15, "PROFILE_QUESTION_IDS has 15 items");
assert(PROFILE_QUESTIONS.length === 15, "PROFILE_QUESTIONS has 15 items");

// 2. Empty content => all missing
const missingEmpty = getMissingJobPostFields("");
assert(missingEmpty.length === 15, "Empty job post has 15 missing fields");

// 3. Minimal job post with all 15 labels => none missing
const minimalPost = PROFILE_QUESTIONS.map((q) => `**${q.label}:** (filled)`).join("\n");
const missingFull = getMissingJobPostFields(minimalPost);
assert(missingFull.length === 0, "Job post with all 15 labels has 0 missing fields");

// 4. Partial post => some missing
const partialPost = "**Service Category Needed:** Repair. **Location:** Brooklyn.";
const missingPartial = getMissingJobPostFields(partialPost);
assert(missingPartial.length > 0 && missingPartial.length < 15, "Partial post has some missing fields");

console.log("\n" + passed + " passed, " + failed + " failed");
process.exit(failed > 0 ? 1 : 0);
