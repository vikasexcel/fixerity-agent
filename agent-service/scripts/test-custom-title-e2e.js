/**
 * End-to-End Test — Custom Conversation Title (Buyer + Seller Agents)
 *
 * Verifies:
 *  1. Starting a buyer conversation sets initial title from the opening message.
 *  2. Sending subsequent chat messages does NOT overwrite the title.
 *  3. PATCH /conversations/:threadId/title sets a custom title.
 *  4. Further chat messages still do NOT overwrite the custom title.
 *  5. The custom title persists in GET /conversations list.
 *  6. The custom title persists in GET /conversations/:threadId detail.
 *  7. Same behaviour for the seller agent.
 *  8. PATCH with empty/missing title returns 400.
 *  9. PATCH for a non-existent threadId returns 404.
 *
 * Run:
 *   cd agent-service
 *   node --env-file=.env scripts/test-custom-title-e2e.js
 *   OR
 *   npx dotenv -e .env -- node scripts/test-custom-title-e2e.js
 */

import "dotenv/config";

const BASE_URL = process.env.AGENT_API_URL || `http://localhost:${process.env.PORT || 20159}`;

// ─── Minimal test harness ─────────────────────────────────────────────────────

let passed = 0;
let failed = 0;
const failures = [];

function ok(cond, label) {
  if (cond) {
    passed++;
    console.log("  PASS:", label);
  } else {
    failed++;
    failures.push(label);
    console.log("  FAIL:", label);
  }
}

async function http(method, path, body) {
  const opts = {
    method,
    headers: { "Content-Type": "application/json" },
  };
  if (body !== undefined) opts.body = JSON.stringify(body);
  const r = await fetch(`${BASE_URL}${path}`, opts);
  const data = await r.json().catch(() => ({}));
  return { status: r.status, data };
}

const get   = (path)        => http("GET",   path);
const post  = (path, body)  => http("POST",  path, body);
const patch = (path, body)  => http("PATCH", path, body);

function step(label) {
  console.log(`\n${"─".repeat(60)}`);
  console.log(`STEP: ${label}`);
  console.log("─".repeat(60));
}

// ─── Main ─────────────────────────────────────────────────────────────────────

async function main() {
  console.log("╔══════════════════════════════════════════════════════════════╗");
  console.log("║   Custom Conversation Title — E2E Test (Buyer + Seller)      ║");
  console.log("╚══════════════════════════════════════════════════════════════╝");
  console.log(`  Agent URL: ${BASE_URL}\n`);

  // ── Health check ─────────────────────────────────────────────────────────
  step("0 — Health check");
  const health = await get("/health");
  ok(health.status === 200, `Agent service reachable (got ${health.status})`);
  if (health.status !== 200) {
    console.error("\nFATAL: Agent service not reachable. Aborting.");
    process.exit(1);
  }

  // ══════════════════════════════════════════════════════════════════════════
  //  BUYER AGENT
  // ══════════════════════════════════════════════════════════════════════════

  console.log("\n\n  ╔══════════════════════════╗");
  console.log("  ║   BUYER AGENT TESTS       ║");
  console.log("  ╚══════════════════════════╝");

  // ── B1: Start buyer conversation ─────────────────────────────────────────
  step("B1 — Start buyer conversation");
  const BUYER_OPENER = "I need to hire a plumber to fix a leaking pipe in my kitchen.";
  const buyerStart = await post("/buyer-agentv2/start", { message: BUYER_OPENER });

  ok(buyerStart.status === 200, `Start returns 200 (got ${buyerStart.status})`);
  const buyerThreadId = buyerStart.data?.threadId;
  ok(typeof buyerThreadId === "string" && buyerThreadId.length > 0, `threadId returned: ${buyerThreadId}`);

  if (!buyerThreadId) {
    console.error("\nFATAL: No buyer threadId. Aborting.");
    process.exit(1);
  }

  // ── B2: Verify initial title set from opener ──────────────────────────────
  step("B2 — Verify initial title set from opening message");
  const buyerDetail1 = await get(`/conversations/${buyerThreadId}`);
  ok(buyerDetail1.status === 200, `GET /conversations/${buyerThreadId} returns 200`);
  const initialBuyerTitle = buyerDetail1.data?.conversation?.title;
  console.log(`  Initial title: "${initialBuyerTitle}"`);
  ok(
    typeof initialBuyerTitle === "string" && initialBuyerTitle.length > 0,
    "Initial title is a non-empty string"
  );
  ok(
    initialBuyerTitle === BUYER_OPENER.slice(0, 60),
    `Initial title is first 60 chars of opener (got: "${initialBuyerTitle}")`
  );

  // ── B3: Send a chat message and confirm title is NOT overwritten ──────────
  step("B3 — Send chat message, confirm title unchanged");
  const buyerChatReply = "The leak is under the sink and it's getting worse.";
  const buyerChat1 = await post("/buyer-agentv2/chat", {
    threadId: buyerThreadId,
    message: buyerChatReply,
  });
  ok(buyerChat1.status === 200, `Chat returns 200 (got ${buyerChat1.status})`);

  const buyerDetail2 = await get(`/conversations/${buyerThreadId}`);
  const titleAfterChat = buyerDetail2.data?.conversation?.title;
  console.log(`  Title after chat: "${titleAfterChat}"`);
  ok(
    titleAfterChat === initialBuyerTitle,
    `Title unchanged after chat (expected: "${initialBuyerTitle}", got: "${titleAfterChat}")`
  );
  ok(
    titleAfterChat !== buyerChatReply.slice(0, 60),
    "Title was NOT replaced with chat message content"
  );

  // ── B4: Set a custom title via PATCH ─────────────────────────────────────
  step("B4 — PATCH custom title");
  const CUSTOM_BUYER_TITLE = "Kitchen plumbing repair — urgent";
  const patchBuyer = await patch(`/conversations/${buyerThreadId}/title`, {
    title: CUSTOM_BUYER_TITLE,
  });
  ok(patchBuyer.status === 200, `PATCH title returns 200 (got ${patchBuyer.status})`);
  ok(patchBuyer.data?.threadId === buyerThreadId, "Response threadId matches");
  ok(patchBuyer.data?.title === CUSTOM_BUYER_TITLE, `Response title is "${CUSTOM_BUYER_TITLE}" (got: "${patchBuyer.data?.title}")`);

  // ── B5: Confirm custom title persists in GET detail ───────────────────────
  step("B5 — Custom title persists in GET /conversations/:threadId");
  const buyerDetail3 = await get(`/conversations/${buyerThreadId}`);
  const titleAfterPatch = buyerDetail3.data?.conversation?.title;
  console.log(`  Title after PATCH: "${titleAfterPatch}"`);
  ok(titleAfterPatch === CUSTOM_BUYER_TITLE, `Custom title preserved (got: "${titleAfterPatch}")`);

  // ── B6: Send another chat message — custom title must stay ────────────────
  step("B6 — Send another chat message, custom title must NOT be overwritten");
  const buyerChat2 = await post("/buyer-agentv2/chat", {
    threadId: buyerThreadId,
    message: "I also need you to check the water pressure while you're here.",
  });
  ok(buyerChat2.status === 200, `Second chat returns 200 (got ${buyerChat2.status})`);

  const buyerDetail4 = await get(`/conversations/${buyerThreadId}`);
  const titleAfterSecondChat = buyerDetail4.data?.conversation?.title;
  console.log(`  Title after second chat: "${titleAfterSecondChat}"`);
  ok(
    titleAfterSecondChat === CUSTOM_BUYER_TITLE,
    `Custom title still preserved after more chats (got: "${titleAfterSecondChat}")`
  );

  // ── B7: Custom title in list endpoint ────────────────────────────────────
  step("B7 — Custom title appears in GET /conversations?agentType=buyer");
  const buyerList = await get("/conversations?agentType=buyer");
  ok(buyerList.status === 200, `List returns 200 (got ${buyerList.status})`);
  const buyerConvs = buyerList.data?.conversations || [];
  const buyerInList = buyerConvs.find((c) => c.threadId === buyerThreadId);
  ok(buyerInList != null, "Our conversation is in the list");
  ok(
    buyerInList?.title === CUSTOM_BUYER_TITLE,
    `List entry has custom title (got: "${buyerInList?.title}")`
  );

  // ══════════════════════════════════════════════════════════════════════════
  //  SELLER AGENT
  // ══════════════════════════════════════════════════════════════════════════

  console.log("\n\n  ╔══════════════════════════╗");
  console.log("  ║   SELLER AGENT TESTS      ║");
  console.log("  ╚══════════════════════════╝");

  // ── S1: Start seller conversation ────────────────────────────────────────
  step("S1 — Start seller conversation");
  const SELLER_OPENER = "I offer professional web design and SEO services for small businesses.";
  const sellerStart = await post("/seller-agentv2/start", { message: SELLER_OPENER });

  ok(sellerStart.status === 200, `Start returns 200 (got ${sellerStart.status})`);
  const sellerThreadId = sellerStart.data?.threadId;
  ok(typeof sellerThreadId === "string" && sellerThreadId.length > 0, `threadId returned: ${sellerThreadId}`);

  if (!sellerThreadId) {
    console.error("\nFATAL: No seller threadId. Aborting.");
    process.exit(1);
  }

  // ── S2: Verify initial title ──────────────────────────────────────────────
  step("S2 — Verify initial title set from opening message");
  const sellerDetail1 = await get(`/conversations/${sellerThreadId}`);
  ok(sellerDetail1.status === 200, `GET /conversations/${sellerThreadId} returns 200`);
  const initialSellerTitle = sellerDetail1.data?.conversation?.title;
  console.log(`  Initial title: "${initialSellerTitle}"`);
  ok(
    typeof initialSellerTitle === "string" && initialSellerTitle.length > 0,
    "Initial title is a non-empty string"
  );
  ok(
    initialSellerTitle === SELLER_OPENER.slice(0, 60),
    `Initial title is first 60 chars of opener (got: "${initialSellerTitle}")`
  );

  // ── S3: Send a chat message and confirm title is NOT overwritten ──────────
  step("S3 — Send chat message, confirm title unchanged");
  const sellerChatReply = "I have 5 years of experience working with WordPress and Shopify.";
  const sellerChat1 = await post("/seller-agentv2/chat", {
    threadId: sellerThreadId,
    message: sellerChatReply,
  });
  ok(sellerChat1.status === 200, `Chat returns 200 (got ${sellerChat1.status})`);

  const sellerDetail2 = await get(`/conversations/${sellerThreadId}`);
  const sellerTitleAfterChat = sellerDetail2.data?.conversation?.title;
  console.log(`  Title after chat: "${sellerTitleAfterChat}"`);
  ok(
    sellerTitleAfterChat === initialSellerTitle,
    `Title unchanged after chat (expected: "${initialSellerTitle}", got: "${sellerTitleAfterChat}")`
  );
  ok(
    sellerTitleAfterChat !== sellerChatReply.slice(0, 60),
    "Title was NOT replaced with chat message content"
  );

  // ── S4: Set a custom title via PATCH ─────────────────────────────────────
  step("S4 — PATCH custom title");
  const CUSTOM_SELLER_TITLE = "Web design & SEO — John Smith";
  const patchSeller = await patch(`/conversations/${sellerThreadId}/title`, {
    title: CUSTOM_SELLER_TITLE,
  });
  ok(patchSeller.status === 200, `PATCH title returns 200 (got ${patchSeller.status})`);
  ok(patchSeller.data?.threadId === sellerThreadId, "Response threadId matches");
  ok(patchSeller.data?.title === CUSTOM_SELLER_TITLE, `Response title is "${CUSTOM_SELLER_TITLE}" (got: "${patchSeller.data?.title}")`);

  // ── S5: Confirm custom title persists in GET detail ───────────────────────
  step("S5 — Custom title persists in GET /conversations/:threadId");
  const sellerDetail3 = await get(`/conversations/${sellerThreadId}`);
  const sellerTitleAfterPatch = sellerDetail3.data?.conversation?.title;
  console.log(`  Title after PATCH: "${sellerTitleAfterPatch}"`);
  ok(sellerTitleAfterPatch === CUSTOM_SELLER_TITLE, `Custom title preserved (got: "${sellerTitleAfterPatch}")`);

  // ── S6: Send another chat message — custom title must stay ────────────────
  step("S6 — Send another chat message, custom title must NOT be overwritten");
  const sellerChat2 = await post("/seller-agentv2/chat", {
    threadId: sellerThreadId,
    message: "My typical project turnaround is 2 to 4 weeks.",
  });
  ok(sellerChat2.status === 200, `Second chat returns 200 (got ${sellerChat2.status})`);

  const sellerDetail4 = await get(`/conversations/${sellerThreadId}`);
  const sellerTitleAfterSecondChat = sellerDetail4.data?.conversation?.title;
  console.log(`  Title after second chat: "${sellerTitleAfterSecondChat}"`);
  ok(
    sellerTitleAfterSecondChat === CUSTOM_SELLER_TITLE,
    `Custom title still preserved after more chats (got: "${sellerTitleAfterSecondChat}")`
  );

  // ── S7: Custom title in list endpoint ────────────────────────────────────
  step("S7 — Custom title appears in GET /conversations?agentType=seller");
  const sellerList = await get("/conversations?agentType=seller");
  ok(sellerList.status === 200, `List returns 200 (got ${sellerList.status})`);
  const sellerConvs = sellerList.data?.conversations || [];
  const sellerInList = sellerConvs.find((c) => c.threadId === sellerThreadId);
  ok(sellerInList != null, "Our seller conversation is in the list");
  ok(
    sellerInList?.title === CUSTOM_SELLER_TITLE,
    `List entry has custom title (got: "${sellerInList?.title}")`
  );

  // ══════════════════════════════════════════════════════════════════════════
  //  EDGE CASES
  // ══════════════════════════════════════════════════════════════════════════

  console.log("\n\n  ╔══════════════════════════╗");
  console.log("  ║   EDGE CASE TESTS         ║");
  console.log("  ╚══════════════════════════╝");

  // ── E1: Empty title → 400 ────────────────────────────────────────────────
  step("E1 — PATCH with empty title returns 400");
  const emptyTitleRes = await patch(`/conversations/${buyerThreadId}/title`, { title: "" });
  ok(emptyTitleRes.status === 400, `Empty title returns 400 (got ${emptyTitleRes.status})`);

  // ── E2: Missing title field → 400 ────────────────────────────────────────
  step("E2 — PATCH with missing title field returns 400");
  const missingTitleRes = await patch(`/conversations/${buyerThreadId}/title`, {});
  ok(missingTitleRes.status === 400, `Missing title returns 400 (got ${missingTitleRes.status})`);

  // ── E3: Non-existent threadId → 404 ──────────────────────────────────────
  step("E3 — PATCH for non-existent threadId returns 404");
  const notFoundRes = await patch("/conversations/00000000-0000-0000-0000-000000000000/title", {
    title: "Ghost conversation",
  });
  ok(notFoundRes.status === 404, `Non-existent threadId returns 404 (got ${notFoundRes.status})`);

  // ── E4: Whitespace-only title → 400 ──────────────────────────────────────
  step("E4 — PATCH with whitespace-only title returns 400");
  const wsRes = await patch(`/conversations/${buyerThreadId}/title`, { title: "   " });
  ok(wsRes.status === 400, `Whitespace-only title returns 400 (got ${wsRes.status})`);

  // ── E5: Title is trimmed ──────────────────────────────────────────────────
  step("E5 — PATCH title is stored trimmed");
  const PADDED_TITLE = "  Trimmed title test  ";
  const trimRes = await patch(`/conversations/${buyerThreadId}/title`, { title: PADDED_TITLE });
  ok(trimRes.status === 200, `PATCH returns 200 (got ${trimRes.status})`);
  ok(trimRes.data?.title === PADDED_TITLE.trim(), `Title stored trimmed (got: "${trimRes.data?.title}")`);

  // ── Summary ───────────────────────────────────────────────────────────────
  console.log(`\n${"═".repeat(60)}`);
  console.log(`  RESULTS: ${passed} passed, ${failed} failed`);
  if (failures.length > 0) {
    console.log("\n  Failed assertions:");
    failures.forEach((f) => console.log(`    ✗ ${f}`));
  } else {
    console.log("\n  All assertions passed!");
  }
  console.log(`  Buyer threadId : ${buyerThreadId}`);
  console.log(`  Seller threadId: ${sellerThreadId}`);
  console.log(`${"═".repeat(60)}`);

  process.exit(failed > 0 ? 1 : 0);
}

main().catch((err) => {
  console.error("\nFATAL ERROR:", err);
  process.exit(1);
});
