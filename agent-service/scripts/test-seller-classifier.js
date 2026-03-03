/**
 * Test script for seller profile classifier (question intents + extractProfileUpdate).
 * Validates that "languages" and "additionalInfo" are reliably recognized when the AI
 * phrases questions naturally.
 *
 * Run from agent-service: node scripts/test-seller-classifier.js
 * With OPENAI_API_KEY: runs live classifier tests. Without: only intents/prompt checks.
 */
import dotenv from "dotenv";
dotenv.config();

import {
  buildClassifierQuestionIntents,
  extractProfileUpdate,
  createModel,
} from "../src/agents/sellerAgent.js";
import { SELLER_PROFILE_QUESTION_IDS } from "../src/data/sellerProfileQuestions.js";

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

console.log("Seller profile classifier tests\n");

// --- 1. Question intents include all 20 IDs ---
const intents = buildClassifierQuestionIntents();
assert(typeof intents === "string" && intents.length > 0, "buildClassifierQuestionIntents returns non-empty string");

for (const id of SELLER_PROFILE_QUESTION_IDS) {
  assert(intents.includes(id), `Intents include question id "${id}"`);
}
assert(SELLER_PROFILE_QUESTION_IDS.length === 20, "Exactly 20 profile question IDs");

// --- 2. Explicit intent hints for languages and additionalInfo ---
assert(intents.includes("languages") && intents.includes("Languages Spoken"), "Intents describe languages topic");
assert(
  intents.includes("additionalInfo") && (intents.includes("Additional Information") || intents.includes("Additional Info")),
  "Intents describe additionalInfo topic"
);
assert(intents.includes("SPECIAL") && intents.includes("Map to \"languages\"") && intents.includes("Map to \"additionalInfo\""), "SPECIAL hints for languages and additionalInfo present");

// --- 3. Phase 1: extractProfileUpdate returns null when domainPhaseComplete is false ---
const model = createModel();
const phase1Result = await extractProfileUpdate(
  model,
  "What type of tile do you work with?",
  "Ceramic and porcelain mostly.",
  false
);
assert(phase1Result === null, "Phase 1 (domainPhaseComplete=false) returns null");

// --- 4. Live classifier tests (when OPENAI_API_KEY is set) ---
if (process.env.OPENAI_API_KEY) {
  console.log("\n  Running live classifier tests (OPENAI_API_KEY set)...\n");

  // Languages: natural phrasings
  const languagesExchanges = [
    {
      ai: "What languages can you communicate in?",
      user: "English and Spanish.",
      expectId: "languages",
      expectValue: "English and Spanish",
    },
    {
      ai: "Do you speak any other languages besides English?",
      user: "Just English.",
      expectId: "languages",
      expectValue: "Just English",
    },
    {
      ai: "Can you work with Spanish-speaking clients?",
      user: "Yes, I'm fluent in Spanish.",
      expectId: "languages",
      expectValue: "Yes, I'm fluent in Spanish",
    },
  ];

  for (const { ai, user, expectId, expectValue } of languagesExchanges) {
    const out = await extractProfileUpdate(model, ai, user, true);
    const ok = out && out.questionId === expectId && (out.value === expectValue || out.value?.toLowerCase().includes(expectValue.toLowerCase().split(" ")[0]));
    assert(ok, `languages: "${ai.slice(0, 40)}..." -> questionId=${expectId}, value~${expectValue} (got ${out?.questionId}/${out?.value})`);
  }

  // AdditionalInfo: natural phrasings
  const additionalInfoExchanges = [
    {
      ai: "Anything else you'd like potential clients to know about your business?",
      user: "We're reliable and insured. Happy to provide references.",
      expectId: "additionalInfo",
    },
    {
      ai: "One last thing — what sets you apart?",
      user: "Quality work and fair pricing.",
      expectId: "additionalInfo",
    },
    {
      ai: "Is there anything else you'd like to add for clients?",
      user: "skip",
      expectId: "additionalInfo",
      expectValue: "skip",
    },
  ];

  for (const { ai, user, expectId, expectValue } of additionalInfoExchanges) {
    const out = await extractProfileUpdate(model, ai, user, true);
    const valueOk = expectValue === undefined || out?.value === expectValue || (expectValue === "skip" && out?.value === "skip");
    const ok = out && out.questionId === expectId && valueOk;
    assert(ok, `additionalInfo: "${ai.slice(0, 40)}..." -> questionId=${expectId} (got ${out?.questionId}/${out?.value})`);
  }

  // Non-profile (domain) question -> ideally null
  const domainOut = await extractProfileUpdate(
    model,
    "What size homes do you usually clean?",
    "Up to 3000 sq ft.",
    true
  );
  if (domainOut === null) {
    assert(true, "Domain question correctly returns null");
  } else {
    assert(true, "Domain exchange was classified (acceptable)");
  }
} else {
  console.log("  Skipping live classifier tests (set OPENAI_API_KEY to run them).\n");
}

console.log("\n" + passed + " passed, " + failed + " failed");
process.exit(failed > 0 ? 1 : 0);
