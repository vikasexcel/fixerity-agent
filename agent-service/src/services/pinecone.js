import { Pinecone } from "@pinecone-database/pinecone";
import OpenAI from "openai";
import { v4 as uuidv4 } from "uuid";
import { RecursiveCharacterTextSplitter } from "@langchain/textsplitters";

// ─── Config ──────────────────────────────────────────────────────────────────
const INDEX_NAME = "fixerity-agent";
const NAMESPACE = "seller-jobs";
const SELLER_PROFILE_NAMESPACE = "seller-profile";
const EMBEDDING_MODEL = "text-embedding-3-large";
const EMBEDDING_DIMENSIONS = 1024;

// Chunking config (used by LangChain splitter)
const CHUNK_SIZE = 500;
const CHUNK_OVERLAP = 100;

// ─── Clients (lazy-initialized) ──────────────────────────────────────────────
let pineconeClient = null;
let pineconeIndex = null;
let openaiClient = null;

function getPineconeClient() {
  if (!pineconeClient) {
    pineconeClient = new Pinecone({ apiKey: process.env.PINECONE_API_KEY });
  }
  return pineconeClient;
}

function getPineconeIndex() {
  if (!pineconeIndex) {
    pineconeIndex = getPineconeClient().index(INDEX_NAME);
  }
  return pineconeIndex;
}

function getOpenAI() {
  if (!openaiClient) {
    openaiClient = new OpenAI({ apiKey: process.env.OPENAI_API_KEY });
  }
  return openaiClient;
}

// ─── Chunking (LangChain) ────────────────────────────────────────────────────

const textSplitter = new RecursiveCharacterTextSplitter({
  chunkSize: CHUNK_SIZE,
  chunkOverlap: CHUNK_OVERLAP,
});

/**
 * Split text into overlapping chunks using LangChain RecursiveCharacterTextSplitter.
 * Returns only non-empty chunks.
 */
async function chunkText(text, _chunkSize = CHUNK_SIZE, _chunkOverlap = CHUNK_OVERLAP) {
  if (text == null || typeof text !== "string") {
    return [];
  }
  const trimmed = text.trim();
  if (trimmed.length === 0) {
    return [];
  }
  const docs = await textSplitter.createDocuments([trimmed]);
  const chunks = docs.map((d) => d.pageContent).filter((c) => c && c.length > 0);
  return chunks;
}

// ─── Metadata Extraction ─────────────────────────────────────────────────────

/**
 * Extract structured metadata from the free-form job post text.
 * This metadata is stored alongside each vector in Pinecone for filtering.
 */
function extractMetadata(jobPost) {
  const metadata = {};
  const lines = jobPost.split("\n").map((l) => l.trim()).filter(Boolean);

  // Extract title (first non-empty line, stripped of markdown formatting)
  if (lines.length > 0) {
    metadata.title = lines[0].replace(/^#+\s*/, "").replace(/\*\*/g, "").trim();
  }

  // Extract budget/price info (value on the same line after the keyword)
  const budgetMatch = jobPost.match(
    /(?:budget|price|cost|rate|compensation|pay)\s*(?:guidance|range|estimate)?[:\s-]+([^\n]+)/i
  );
  if (budgetMatch) {
    metadata.budget = budgetMatch[1].trim().replace(/^\*\*|\*\*$/g, "");
  }

  // Extract location (value on the same line after the keyword)
  const locationMatch = jobPost.match(
    /(?:project\s+)?(?:location|city|state|area|region|address)[:\s-]+([^\n]+)/i
  );
  if (locationMatch) {
    metadata.location = locationMatch[1].trim().replace(/^\*\*|\*\*$/g, "");
  }

  // Extract timeline/deadline (value on the same line after the keyword)
  const timelineMatch = jobPost.match(
    /(?:timeline|deadline|start date|duration|timeframe|schedule|decision\s+timeline)[:\s-]+([^\n]+)/i
  );
  if (timelineMatch) {
    metadata.timeline = timelineMatch[1].trim().replace(/^\*\*|\*\*$/g, "");
  }

  // Extract category/domain from the content
  const categories = [
    "architecture",
    "construction",
    "plumbing",
    "electrical",
    "landscaping",
    "painting",
    "cleaning",
    "moving",
    "software",
    "web development",
    "design",
    "photography",
    "videography",
    "writing",
    "marketing",
    "consulting",
    "tutoring",
    "pet care",
    "dog walking",
    "home repair",
    "hvac",
    "roofing",
    "flooring",
    "carpentry",
    "welding",
    "auto repair",
    "catering",
    "event planning",
    "wedding",
    "legal",
    "accounting",
    "real estate",
  ];

  const lowerPost = jobPost.toLowerCase();
  const foundCategories = categories.filter((cat) => lowerPost.includes(cat));
  if (foundCategories.length > 0) {
    metadata.categories = foundCategories;
  }

  // Extract scope keywords
  const scopeKeywords = [];
  const scopePatterns = [
    /residential/i,
    /commercial/i,
    /industrial/i,
    /new (?:construction|build)/i,
    /renovation/i,
    /repair/i,
    /maintenance/i,
    /one[- ]time/i,
    /recurring/i,
    /ongoing/i,
    /urgent/i,
    /remote/i,
    /on[- ]site/i,
    /full[- ]time/i,
    /part[- ]time/i,
    /freelance/i,
    /contract/i,
  ];
  for (const pattern of scopePatterns) {
    const match = lowerPost.match(pattern);
    if (match) {
      scopeKeywords.push(match[0].toLowerCase());
    }
  }
  if (scopeKeywords.length > 0) {
    metadata.scope = scopeKeywords;
  }

  return metadata;
}

/**
 * Extract structured metadata from seller profile text (for filtering in Pinecone).
 * Used only for vector metadata; namespace stays SELLER_PROFILE_NAMESPACE.
 */
function extractSellerMetadata(profileText) {
  const metadata = {};
  const lines = profileText.split("\n").map((l) => l.trim()).filter(Boolean);

  if (lines.length > 0) {
    metadata.title = lines[0].replace(/^#+\s*/, "").replace(/\*\*/g, "").trim();
  }

  const locationMatch = profileText.match(
    /(?:service\s+area|location|city|area|region|based\s+in)[:\s-]+([^\n]+)/i
  );
  if (locationMatch) {
    metadata.location = locationMatch[1].trim().replace(/^\*\*|\*\*$/g, "");
  }

  const rateMatch = profileText.match(
    /(?:rate|price|hourly|fee)[:\s-]+([^\n]+)/i
  );
  if (rateMatch) {
    metadata.rate = rateMatch[1].trim().replace(/^\*\*|\*\*$/g, "");
  }

  return metadata;
}

// ─── Embedding ───────────────────────────────────────────────────────────────

/**
 * Generate embeddings for an array of text strings.
 */
async function generateEmbeddings(texts) {
  const openai = getOpenAI();
  const response = await openai.embeddings.create({
    model: EMBEDDING_MODEL,
    input: texts,
    dimensions: EMBEDDING_DIMENSIONS,
  });
  return response.data.map((d) => d.embedding);
}

// ─── Main: Embed & Upsert ───────────────────────────────────────────────────

/**
 * Embed a job post into Pinecone with proper chunking, metadata, and overlap.
 *
 * @param {string} jobPost – The full job post text
 * @param {string} threadId – The conversation thread ID (used for grouping)
 * @returns {{ embeddingId: string|null, jobMetadata: object, chunkCount: number }}
 */
async function embedJobPost(jobPost, threadId) {
  const log = (msg, data = {}) =>
    console.log("[Pinecone embedJobPost]", msg, Object.keys(data).length ? data : "");

  // Guard: no valid job post text
  if (jobPost == null || typeof jobPost !== "string") {
    log("Skipping embed: job post is null or not a string", { jobPostType: typeof jobPost });
    return { embeddingId: null, jobMetadata: {}, chunkCount: 0 };
  }
  const trimmedPost = jobPost.trim();
  if (trimmedPost.length === 0) {
    log("Skipping embed: job post is empty or whitespace");
    return { embeddingId: null, jobMetadata: {}, chunkCount: 0 };
  }

  log("Starting embed", { jobPostLength: trimmedPost.length, threadId });

  const jobId = uuidv4();
  const metadata = extractMetadata(jobPost);

  // Add standard metadata fields
  metadata.jobId = jobId;
  metadata.threadId = threadId;
  metadata.createdAt = new Date().toISOString();
  metadata.source = "buyer-agent";

  // Chunk the job post (LangChain text splitter)
  const chunks = await chunkText(trimmedPost);
  log("Chunking done", { chunkCount: chunks.length });

  if (chunks.length === 0) {
    log("Skipping upsert: no chunks produced");
    return { embeddingId: null, jobMetadata: metadata, chunkCount: 0 };
  }

  // Generate embeddings for all chunks
  const embeddings = await generateEmbeddings(chunks);
  log("Embeddings generated", { embeddingCount: embeddings.length });

  if (embeddings.length !== chunks.length) {
    log("Mismatch: embeddings count != chunks count; skipping upsert to avoid invalid vectors", {
      chunks: chunks.length,
      embeddings: embeddings.length,
    });
    return { embeddingId: null, jobMetadata: metadata, chunkCount: chunks.length };
  }

  // Build Pinecone vectors (only include those with valid values)
  const vectors = [];
  for (let i = 0; i < chunks.length; i++) {
    const val = embeddings[i];
    if (val == null || !Array.isArray(val) || val.length === 0) {
      log("Skipping chunk with invalid embedding", { chunkIndex: i });
      continue;
    }
    vectors.push({
      id: `${jobId}_chunk_${i}`,
      values: val,
      metadata: {
        ...metadata,
        chunkIndex: i,
        totalChunks: chunks.length,
        chunkText: chunks[i],
        fullJobPost: i === 0 ? jobPost : undefined,
      },
    });
  }

  if (vectors.length === 0) {
    log("Skipping upsert: no valid vectors (Pinecone requires at least 1 record)");
    return { embeddingId: null, jobMetadata: metadata, chunkCount: chunks.length };
  }

  // Store full job post only in the first chunk to save space
  for (let i = 1; i < vectors.length; i++) {
    delete vectors[i].metadata.fullJobPost;
  }

  // Upsert into Pinecone (only when we have at least one vector)
  const index = getPineconeIndex();
  const ns = index.namespace(NAMESPACE);

  const BATCH_SIZE = 100;
  for (let i = 0; i < vectors.length; i += BATCH_SIZE) {
    const batch = vectors.slice(i, i + BATCH_SIZE);
    await ns.upsert({ records: batch });
    log("Upserted batch", { batchIndex: i / BATCH_SIZE, batchSize: batch.length });
  }

  log("Embed complete", { embeddingId: jobId, chunkCount: vectors.length });
  return {
    embeddingId: jobId,
    jobMetadata: metadata,
    chunkCount: vectors.length,
  };
}

/**
 * Embed a seller profile into Pinecone (namespace seller-profile only).
 * Job embeds and seller profile embeds stay in separate namespaces; do not mix.
 *
 * @param {string} sellerProfile – The full seller profile text
 * @param {string} threadId – The conversation thread ID (used for grouping)
 * @returns {{ embeddingId: string|null, profileMetadata: object, chunkCount: number }}
 */
async function embedSellerProfile(sellerProfile, threadId) {
  const log = (msg, data = {}) =>
    console.log("[Pinecone embedSellerProfile]", msg, Object.keys(data).length ? data : "");

  if (sellerProfile == null || typeof sellerProfile !== "string") {
    log("Skipping embed: seller profile is null or not a string", {
      profileType: typeof sellerProfile,
    });
    return { embeddingId: null, profileMetadata: {}, chunkCount: 0 };
  }
  const trimmed = sellerProfile.trim();
  if (trimmed.length === 0) {
    log("Skipping embed: seller profile is empty or whitespace");
    return { embeddingId: null, profileMetadata: {}, chunkCount: 0 };
  }

  log("Starting embed", {
    profileLength: trimmed.length,
    threadId,
    namespace: SELLER_PROFILE_NAMESPACE,
  });

  const profileId = uuidv4();
  const metadata = extractSellerMetadata(sellerProfile);
  metadata.profileId = profileId;
  metadata.threadId = threadId;
  metadata.createdAt = new Date().toISOString();
  metadata.source = "seller-agent";

  const chunks = await chunkText(trimmed);
  log("Chunking done", { chunkCount: chunks.length });

  if (chunks.length === 0) {
    log("Skipping upsert: no chunks produced");
    return { embeddingId: null, profileMetadata: metadata, chunkCount: 0 };
  }

  const embeddings = await generateEmbeddings(chunks);
  log("Embeddings generated", { embeddingCount: embeddings.length });

  if (embeddings.length !== chunks.length) {
    log("Mismatch: embeddings count != chunks count; skipping upsert", {
      chunks: chunks.length,
      embeddings: embeddings.length,
    });
    return { embeddingId: null, profileMetadata: metadata, chunkCount: chunks.length };
  }

  const vectors = [];
  for (let i = 0; i < chunks.length; i++) {
    const val = embeddings[i];
    if (val == null || !Array.isArray(val) || val.length === 0) {
      log("Skipping chunk with invalid embedding", { chunkIndex: i });
      continue;
    }
    vectors.push({
      id: `${profileId}_chunk_${i}`,
      values: val,
      metadata: {
        ...metadata,
        chunkIndex: i,
        totalChunks: chunks.length,
        chunkText: chunks[i],
        fullProfile: i === 0 ? sellerProfile : undefined,
      },
    });
  }

  if (vectors.length === 0) {
    log("Skipping upsert: no valid vectors");
    return { embeddingId: null, profileMetadata: metadata, chunkCount: chunks.length };
  }

  for (let i = 1; i < vectors.length; i++) {
    delete vectors[i].metadata.fullProfile;
  }

  const index = getPineconeIndex();
  const ns = index.namespace(SELLER_PROFILE_NAMESPACE);

  const BATCH_SIZE = 100;
  for (let i = 0; i < vectors.length; i += BATCH_SIZE) {
    const batch = vectors.slice(i, i + BATCH_SIZE);
    await ns.upsert({ records: batch });
    log("Upserted batch", { batchIndex: i / BATCH_SIZE, batchSize: batch.length });
  }

  log("Embed complete", { embeddingId: profileId, chunkCount: vectors.length });
  return {
    embeddingId: profileId,
    profileMetadata: metadata,
    chunkCount: vectors.length,
  };
}

// ─── Seller search & rerank (for buyer agent) ────────────────────────────────

const RERANK_MODEL = "bge-reranker-v2-m3";
const SEARCH_TOP_K = 15;
const RERANK_TOP_N = 5;

const SELLER_MATCH_LOG_PREFIX = "[SellerMatch]";

function logSection(title) {
  console.log("\n" + "─".repeat(60));
  console.log(`${SELLER_MATCH_LOG_PREFIX} ${title}`);
  console.log("─".repeat(60));
}

function logLine(label, value) {
  console.log(`  ${label}: ${value}`);
}

function truncate(text, maxLen = 120) {
  if (text == null || typeof text !== "string") return String(text);
  const t = text.trim();
  return t.length <= maxLen ? t : t.slice(0, maxLen) + "…";
}

/**
 * Search seller-profile namespace by job post (embed job, query by vector).
 * Deduplicates by profileId and returns unique profiles with best chunk.
 *
 * @param {string} jobPost – Full job post text
 * @param {number} [topK=10] – Max number of unique profiles to consider
 * @returns {Promise<Array<{ profileId: string, profileText: string, metadata: object }>>}
 */
async function searchSellerProfiles(jobPost, topK = 10) {
  logSection("1. SEARCH – Query & raw results");

  if (jobPost == null || typeof jobPost !== "string" || jobPost.trim().length === 0) {
    logLine("Status", "Skipped (empty job post)");
    return [];
  }

  logLine("Query (job post preview)", truncate(jobPost, 200));
  logLine("Namespace", SELLER_PROFILE_NAMESPACE);
  logLine("TopK requested", SEARCH_TOP_K);

  const [embedding] = await generateEmbeddings([jobPost.trim()]);
  if (!embedding || !Array.isArray(embedding) || embedding.length === 0) {
    logLine("Status", "Skipped (no embedding)");
    return [];
  }

  logLine("Embedding dimensions", embedding.length);

  const index = getPineconeIndex();
  const ns = index.namespace(SELLER_PROFILE_NAMESPACE);

  const response = await ns.query({
    vector: embedding,
    topK: SEARCH_TOP_K,
    includeMetadata: true,
    includeValues: false,
  });

  const matches = response.matches || [];
  logLine("Raw matches count", matches.length);

  if (matches.length === 0) {
    logLine("Status", "No matches – returning []");
    return [];
  }

  console.log("\n  Raw hits (id, score):");
  matches.forEach((m, i) => {
    const pid = m.metadata?.profileId || (m.id && m.id.replace(/_chunk_\d+$/, "")) || m.id;
    console.log(`    ${i + 1}. id=${m.id} profileId=${pid} score=${(m.score ?? 0).toFixed(4)}`);
  });

  const byProfile = new Map();
  for (const m of matches) {
    const profileId = m.metadata?.profileId || (m.id && m.id.replace(/_chunk_\d+$/, ""));
    if (!profileId) continue;
    const existing = byProfile.get(profileId);
    const profileText = m.metadata?.fullProfile || m.metadata?.chunkText || "";
    const score = m.score ?? 0;
    if (!existing || score > (existing.score ?? 0)) {
      byProfile.set(profileId, {
        profileId,
        profileText: profileText || existing?.profileText || "",
        metadata: { ...m.metadata },
        score,
      });
    } else if (existing && !existing.profileText && profileText) {
      existing.profileText = profileText;
    }
  }

  const list = Array.from(byProfile.values())
    .sort((a, b) => (b.score ?? 0) - (a.score ?? 0))
    .slice(0, topK)
    .map(({ profileId, profileText, metadata }) => ({
      profileId,
      profileText,
      metadata: metadata || {},
    }));

  console.log("\n  After dedupe by profileId (best chunk per profile):");
  logLine("Profiles kept", list.length);
  list.forEach((p, i) => {
    const title = (p.metadata?.title || (p.profileText || "").split("\n")[0] || "").slice(0, 50);
    console.log(`    ${i + 1}. ${p.profileId}  "${truncate(title, 40)}"`);
  });

  return list;
}

/**
 * Rerank profile results using Pinecone Inference (bge-reranker-v2-m3).
 *
 * @param {string} jobPost – Job post text (query for reranker)
 * @param {Array<{ profileId: string, profileText: string, metadata: object }>} searchResults
 * @param {number} [topN=5]
 * @returns {Promise<Array<{ profileId: string, profileText: string, metadata: object }>>}
 */
async function rerankSellerProfiles(jobPost, searchResults, topN = RERANK_TOP_N) {
  logSection("2. RERANK – Pinecone Inference");

  if (searchResults.length === 0) {
    logLine("Status", "No input – returning []");
    return [];
  }

  logLine("Query (job post preview)", truncate(jobPost, 150));
  logLine("Model", RERANK_MODEL);
  logLine("Input documents count", searchResults.length);
  logLine("TopN", topN);

  const documents = searchResults.map((r) => r.profileText || r.metadata?.chunkText || "");
  if (documents.every((d) => !d)) {
    logLine("Status", "No text to rerank – using search order");
    return searchResults.slice(0, topN);
  }

  try {
    const inference = getPineconeClient().inference;
    const result = await inference.rerank({
      model: RERANK_MODEL,
      query: jobPost.trim(),
      documents,
      topN,
      returnDocuments: true,
      rankFields: ["text"],
    });

    const hits = result.data || result.results || result.hits || [];
    logLine("Rerank API hits count", hits.length);

    console.log("\n  Rerank result (original index → relevance score):");
    hits.forEach((h, i) => {
      const idx = typeof h.index === "number" ? h.index : parseInt(h.index, 10);
      const score = (h.score != null ? h.score : 0).toFixed(4);
      const profileId = searchResults[idx]?.profileId ?? "?";
      console.log(`    ${i + 1}. index=${idx} profileId=${profileId} score=${score}`);
    });

    const order = hits.map((h) => (typeof h.index === "number" ? h.index : parseInt(h.index, 10)));
    const reranked = order
      .filter((i) => i >= 0 && i < searchResults.length)
      .map((i) => searchResults[i]);

    console.log("\n  Final reranked order (profileIds):");
    reranked.forEach((p, i) => console.log(`    ${i + 1}. ${p.profileId}`));

    return reranked;
  } catch (err) {
    console.error(`${SELLER_MATCH_LOG_PREFIX} Rerank failed, using search order:`, err.message);
    logLine("Fallback", "Using original search order");
    return searchResults.slice(0, topN);
  }
}

/**
 * Use OpenAI to score (0–100) and explain each seller match for the job.
 *
 * @param {string} jobPost – Job post text
 * @param {Array<{ profileId: string, profileText: string, metadata: object }>} profiles
 * @returns {Promise<Array<{ profileId: string, sellerName: string, profileText: string, matchScore: number, matchExplanation: string, metadata: object }>>}
 */
async function scoreWithLLM(jobPost, profiles) {
  logSection("3. LLM SCORING – Match score & explanation");

  if (profiles.length === 0) {
    logLine("Status", "No profiles – returning []");
    return [];
  }

  const model = process.env.OPENAI_MODEL || "gpt-4o-mini";
  logLine("Model", model);
  logLine("Profiles to score", profiles.length);
  logLine("Job post length (chars)", jobPost.length);

  const openai = getOpenAI();
  const systemPrompt = `You are a job-matching expert. For each seller profile, analyze how well they match the job requirements. Respond with a JSON array of objects. Each object must have: "profileIndex" (0-based index of the profile), "matchScore" (number 0-100), "matchExplanation" (2-3 sentences). No other text.`;

  const profileList = profiles
    .map(
      (p, i) =>
        `[Profile ${i}] (profileId: ${p.profileId})\n${(p.profileText || "").slice(0, 2000)}`
    )
    .join("\n\n");

  const userPrompt = `Job post:\n${jobPost.slice(0, 3000)}\n\nSeller profiles:\n${profileList}\n\nOutput JSON array with profileIndex, matchScore, matchExplanation for each profile.`;

  try {
    const completion = await openai.chat.completions.create({
      model,
      messages: [
        { role: "system", content: systemPrompt },
        { role: "user", content: userPrompt },
      ],
      temperature: 0.3,
    });

    const content = (completion.choices?.[0]?.message?.content || "").trim();
    logLine("LLM response length (chars)", content.length);

    const jsonMatch = content.match(/\[[\s\S]*\]/);
    const arr = jsonMatch ? JSON.parse(jsonMatch[0]) : [];
    logLine("Parsed JSON array length", arr.length);

    console.log("\n  LLM raw output (profileIndex, matchScore, matchExplanation preview):");
    arr.forEach((o, i) => {
      const score = o.matchScore != null ? o.matchScore : "?";
      const expl = truncate(String(o.matchExplanation || ""), 80);
      console.log(`    ${i + 1}. index=${o.profileIndex} score=${score}  "${expl}"`);
    });

    const byIndex = new Map(arr.map((o) => [Number(o.profileIndex), o]));

    const result = profiles.map((p, i) => {
      const meta = p.metadata || {};
      const title = meta.title || (p.profileText || "").split("\n")[0] || "Seller";
      const sellerName = title.replace(/^#+\s*/, "").replace(/\*\*/g, "").trim() || "Seller";
      const scored = byIndex.get(i) || {};
      return {
        profileId: p.profileId,
        sellerName,
        profileText: p.profileText || "",
        matchScore: Math.min(100, Math.max(0, Number(scored.matchScore) ?? 50)),
        matchExplanation: String(scored.matchExplanation || "Match analysis not available.").slice(0, 500),
        metadata: {
          location: meta.location,
          rate: meta.rate,
          ...meta,
        },
      };
    });

    return result;
  } catch (err) {
    console.error(`${SELLER_MATCH_LOG_PREFIX} LLM failed:`, err.message);
    logLine("Fallback", "Using default score 50 for all");
    return profiles.map((p) => {
      const meta = p.metadata || {};
      const title = meta.title || (p.profileText || "").split("\n")[0] || "Seller";
      return {
        profileId: p.profileId,
        sellerName: title.replace(/^#+\s*/, "").trim() || "Seller",
        profileText: p.profileText || "",
        matchScore: 50,
        matchExplanation: "Scoring unavailable.",
        metadata: { location: meta.location, rate: meta.rate, ...meta },
      };
    });
  }
}

/**
 * Find best matching sellers for a job: search → rerank → LLM score.
 *
 * @param {string} jobPost – Full job post text
 * @returns {Promise<Array<{ profileId, sellerName, profileText, matchScore, matchExplanation, metadata }>>}
 */
async function findMatchingSellers(jobPost) {
  console.log("\n" + "═".repeat(60));
  console.log(`${SELLER_MATCH_LOG_PREFIX} FIND MATCHING SELLERS – START`);
  console.log("═".repeat(60));
  logLine("Job post length", (jobPost || "").length);
  logLine("Pipeline", "search → rerank → LLM score");

  const searchResults = await searchSellerProfiles(jobPost, 10);
  if (searchResults.length === 0) {
    logSection("FINAL – No sellers matched");
    logLine("Result", "[]");
    console.log("");
    return [];
  }

  const reranked = await rerankSellerProfiles(jobPost, searchResults, RERANK_TOP_N);
  const result = await scoreWithLLM(jobPost, reranked);

  logSection("4. FULL MATCH BREAKDOWN – Final ranked sellers");
  logLine("Total returned", result.length);
  console.log("");
  result.forEach((s, i) => {
    console.log(`  ┌─ Rank ${i + 1} ─────────────────────────────────`);
    logLine("  │ profileId", s.profileId);
    logLine("  │ sellerName", s.sellerName);
    logLine("  │ matchScore", s.matchScore);
    if (s.metadata?.location) logLine("  │ location", s.metadata.location);
    if (s.metadata?.rate) logLine("  │ rate", s.metadata.rate);
    console.log(`  │ matchExplanation: ${truncate(s.matchExplanation, 200)}`);
    console.log("  └" + "─".repeat(40));
  });

  console.log("\n" + "═".repeat(60));
  console.log(`${SELLER_MATCH_LOG_PREFIX} FIND MATCHING SELLERS – END (${result.length} sellers)`);
  console.log("═".repeat(60) + "\n");

  return result;
}

export {
  embedJobPost,
  embedSellerProfile,
  chunkText,
  extractMetadata,
  extractSellerMetadata,
  generateEmbeddings,
  findMatchingSellers,
  searchSellerProfiles,
  rerankSellerProfiles,
  scoreWithLLM,
  NAMESPACE,
  SELLER_PROFILE_NAMESPACE,
  INDEX_NAME,
};
