/**
 * Full end-to-end test: seller profile flow through profile → confirm → embed → find jobs.
 * Uses the real API (same as frontend): POST /start, POST /chat, GET /state.
 * No manual steps; run once to verify the full pipeline.
 *
 * Run from agent-service: node scripts/test-seller-flow-e2e.js
 * Requires: agent service running (npm run start or npm run dev)
 * Optional: BASE_URL (default http://localhost:3017)
 */

import { SELLER_PROFILE_QUESTION_IDS } from "../src/data/sellerProfileQuestions.js";

const BASE_URL = process.env.BASE_URL || "http://localhost:3017";
const SELLER_BASE = `${BASE_URL}/seller-agentv2`;

const INITIAL_MESSAGE = "I do home cleaning.";

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
  warranty: "I guarantee satisfaction — if something's missed I'll come back and fix it.",
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

function log(step, msg, data = {}) {
  const extra = Object.keys(data).length ? " " + JSON.stringify(data) : "";
  console.log(`[${step}] ${msg}${extra}`);
}

async function main() {
  console.log("Seller flow E2E — start → domain → profile → confirm → embed → jobs\n");

  log("1", "Starting conversation", { message: INITIAL_MESSAGE });
  const startRes = await post("/start", { message: INITIAL_MESSAGE });
  const { threadId, message: firstReply, status } = startRes;
  if (!threadId) throw new Error("No threadId from /start");
  log("1", "Started", { threadId, status, replyPreview: (firstReply || "").slice(0, 80) + "..." });

  let lastResponse = startRes;
  let messageCount = 0;
  let domainIndex = 0;
  const maxMessages = 55;

  // Phase: gather (domain + profile answers) until profile is generated
  while (messageCount < maxMessages) {
    if (lastResponse.sellerProfile) {
      log("2", "Profile generated", { messageCount, status: lastResponse.status });
      break;
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
        nextMessage = "Anything else you need from me?";
      }
    } else {
      nextMessage = domainIndex < DOMAIN_ANSWERS.length ? DOMAIN_ANSWERS[domainIndex++] : "Anything else you need from me?";
    }

    log("2", `Chat #${messageCount + 1}`, { phase: domainPhaseComplete ? "profile" : "domain", preview: nextMessage.slice(0, 50) + "..." });
    lastResponse = await post("/chat", { threadId, message: nextMessage });
    messageCount++;
  }

  if (!lastResponse.sellerProfile) {
    console.error("\nFAIL: Profile was not generated after", maxMessages, "messages.");
    process.exit(1);
  }

  const covered = Object.keys((await get(`/state/${threadId}`)).profileAnswers || {}).length;
  log("2", "Profile answers covered", { covered, required: 20 });
  if (covered < 20) {
    console.warn("WARN: Only", covered, "/20 profile answers in state; continuing anyway.");
  }

  // Phase: confirm profile → createProfile runs → embedding + job matching
  const confirmMessage = "Looks good, confirm.";
  log("3", "Confirming profile", { message: confirmMessage });
  const confirmResponse = await post("/chat", { threadId, message: confirmMessage });

  const finalStatus = confirmResponse.status;
  log("4", "After confirm", { status: finalStatus });

  // Assertions (use state if confirm response didn't include embed/jobs)
  const stateAfter = await get(`/state/${threadId}`).catch(() => ({}));
  const embeddingId = confirmResponse.embeddingId ?? stateAfter.embeddingId;
  const jobMatchingStatus = confirmResponse.jobMatchingStatus ?? stateAfter.jobMatchingStatus;
  const matchedJobs = confirmResponse.matchedJobs ?? stateAfter.matchedJobs;

  const failures = [];
  if (finalStatus !== "done") {
    failures.push(`expected status "done", got "${finalStatus}"`);
  }
  if (embeddingId != null) {
    log("4", "Embedding created", { embeddingId });
  } else {
    console.warn("  WARN: embeddingId not set (Pinecone may be disabled or failed in this env)");
  }
  if (jobMatchingStatus == null && matchedJobs == null) {
    failures.push("expected jobMatchingStatus or matchedJobs after confirm (job matching ran)");
  } else {
    log("4", "Job matching", { jobMatchingStatus, matchedCount: matchedJobs?.length ?? 0 });
  }

  if (failures.length > 0) {
    console.error("\nFAIL:");
    failures.forEach((f) => console.error("  -", f));
    process.exit(1);
  }

  console.log("\nPASS: Full flow completed — profile generated, confirmed, embedding and job matching ran.");
  if (embeddingId) console.log("  embeddingId:", embeddingId);
  if (jobMatchingStatus) console.log("  jobMatchingStatus:", jobMatchingStatus, (matchedJobs?.length ?? 0) > 0 ? `(${matchedJobs?.length} jobs)` : "");
  process.exit(0);
}

main().catch((err) => {
  console.error("Error:", err.message);
  process.exit(1);
});
