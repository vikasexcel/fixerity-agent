/**
 * Automated test script for seller profile flow (home cleaning).
 * Runs the full reproduction steps without manual typing:
 * 1. Start new conversation with "I do home cleaning"
 * 2. Answer 5–8 domain questions about cleaning
 * 3. Answer all 20 profile questions
 * 4. Assert profile is generated
 *
 * Run from agent-service: node scripts/test-seller-profile.js
 * Requires: agent service running (npm run dev) and BASE_URL (default http://localhost:3000)
 */

import { SELLER_PROFILE_QUESTION_IDS } from "../src/data/sellerProfileQuestions.js";

const BASE_URL = process.env.BASE_URL || "http://localhost:3017";
const SELLER_BASE = `${BASE_URL}/seller-agentv2`;

// Initial message (step 3 in reproduction steps)
const INITIAL_MESSAGE = "I do home cleaning";

// Domain-specific answers for home cleaning (5–8 questions; we provide 8)
const DOMAIN_ANSWERS = [
  "Residential house cleaning — regular weekly or biweekly and deep cleans.",
  "I bring my own vacuum, mop, and basic supplies; clients can provide specialty products if they prefer.",
  "I'm fine with pets as long as they're not aggressive; I can work around them.",
  "Most clients are every one or two weeks; some want monthly deep cleans.",
  "I usually do homes up to about 3,000 sq ft; larger I'd quote separately.",
  "A typical clean is 2–3 hours; deep cleans or move-in/out can be half a day.",
  "I do inside only; no outdoor windows or gutters.",
  "I focus on kitchens, bathrooms, floors, and dusting; I can add laundry or organizing for an extra fee.",
];

// Answers for the 20 profile questions (same order as SELLER_PROFILE_QUESTION_IDS)
const PROFILE_ANSWERS = {
  serviceType: "General house cleaning and general labor — residential focus.",
  projectArrangement: "Part-time and side business; I have a few regular clients.",
  licensing: "No license required for cleaning in my area; I'm bonded.",
  businessStructure: "Individual, sole proprietor.",
  insurance: "Yes, I have liability insurance. I can share details on request.",
  availability: "Weekends and some evenings; weekdays by arrangement.",
  experience: "About 5 years of residential cleaning; hundreds of homes completed.",
  serviceArea: "Greater Seattle area; up to 25 miles travel.",
  pricingStructure: "Flat rate per visit based on size and scope; typically $120–180 per clean.",
  paymentTerms: "Payment after each visit; no deposit for regular clients.",
  paymentMethods: "Cash, Venmo, Zelle, or check.",
  references: "Yes, I can provide 2–3 references from current clients.",
  specializations: "Eco-friendly products available; experience with move-in/out and post-construction.",
  minimumJobSize: "Minimum $80 per visit for small apartments.",
  materialsEquipment: "I provide my own tools and basic supplies; client provides if they want specific brands.",
  warranty: "I guarantee satisfaction — if something’s missed I’ll come back and fix it.",
  portfolio: "I have photos of before/after I can share; no formal portfolio.",
  reviews: "I have a few Google reviews; happy to share the link.",
  languages: "English and Spanish.",
  additionalInfo: "Reliable, detail-oriented, and I leave homes tidy. I focus on kitchens and bathrooms and work around your schedule.",
};

async function post(path, body) {
  const res = await fetch(`${SELLER_BASE}${path}`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(body),
  });
  if (!res.ok) {
    const text = await res.text();
    throw new Error(`${res.status} ${path}: ${text}`);
  }
  return res.json();
}

async function get(path) {
  const res = await fetch(`${SELLER_BASE}${path}`);
  if (!res.ok) {
    const text = await res.text();
    throw new Error(`${res.status} ${path}: ${text}`);
  }
  return res.json();
}

async function main() {
  console.log("Seller profile test (home cleaning)\n");
  console.log("1. Starting new conversation with:", INITIAL_MESSAGE);

  const startRes = await post("/start", { message: INITIAL_MESSAGE });
  const { threadId, message: firstReply, status } = startRes;
  console.log("   threadId:", threadId);
  console.log("   status:", status);
  console.log("   first reply (trimmed):", (firstReply || "").slice(0, 120) + "...\n");

  let domainIndex = 0;
  let lastResponse = startRes;
  let messageCount = 0;
  const maxMessages = 40;

  while (messageCount < maxMessages) {
    if (lastResponse.sellerProfile) {
      console.log("2. Profile generated successfully after", messageCount, "messages.\n");
      console.log("--- SELLER PROFILE (excerpt) ---");
      console.log((lastResponse.sellerProfile || "").slice(0, 800) + (lastResponse.sellerProfile?.length > 800 ? "..." : ""));
      console.log("--- END EXCERPT ---\n");
      if (lastResponse.placeholders?.length) {
        console.log("Placeholders:", lastResponse.placeholders);
      }
      process.exit(0);
    }

    const state = await get(`/state/${threadId}`).catch(() => ({}));
    const profileAnswers = state.profileAnswers || {};
    const domainPhaseComplete = state.domainPhaseComplete === true;

    let nextMessage;
    if (!domainPhaseComplete && domainIndex < DOMAIN_ANSWERS.length) {
      nextMessage = DOMAIN_ANSWERS[domainIndex++];
    } else if (domainPhaseComplete) {
      const nextId = SELLER_PROFILE_QUESTION_IDS.find((id) => profileAnswers[id] == null);
      if (nextId) {
        nextMessage = PROFILE_ANSWERS[nextId] ?? "skip";
      } else {
        nextMessage = null;
      }
    } else {
      nextMessage = domainIndex < DOMAIN_ANSWERS.length ? DOMAIN_ANSWERS[domainIndex++] : null;
    }

    if (nextMessage == null) {
      console.log("   All answers sent; prompting for profile. Sent", messageCount, "messages.");
      lastResponse = await post("/chat", { threadId, message: "Anything else you need from me?" });
      messageCount++;
      continue;
    }

    console.log("   #" + (messageCount + 1) + ":", nextMessage.slice(0, 55) + (nextMessage.length > 55 ? "..." : ""));
    lastResponse = await post("/chat", { threadId, message: nextMessage });
    messageCount++;
  }

  console.error("FAIL: Profile was not generated after", maxMessages, "messages.");
  process.exit(1);
}

main().catch((err) => {
  console.error("Error:", err.message);
  process.exit(1);
});
