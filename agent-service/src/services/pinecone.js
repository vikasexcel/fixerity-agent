import { Pinecone } from "@pinecone-database/pinecone";
import OpenAI from "openai";
import { v4 as uuidv4 } from "uuid";
import { RecursiveCharacterTextSplitter } from "@langchain/textsplitters";

// ─── Config ──────────────────────────────────────────────────────────────────
const INDEX_NAME = "fixerity-agent";
const NAMESPACE = "seller-jobs";
const SELLER_PROFILE_NAMESPACE = "seller-profile";
const CATEGORIES_NAMESPACE = process.env.PINECONE_CATEGORIES_NAMESPACE || "categories";
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

function buildTaxonomyMetadata(taxonomy = {}) {
  const normalized = {
    categoryId: taxonomy.categoryId ?? taxonomy.category?.id,
    categoryName: taxonomy.categoryName ?? taxonomy.category?.name,
    subcategoryId: taxonomy.subcategoryId ?? taxonomy.subcategory?.id,
    subcategoryName: taxonomy.subcategoryName ?? taxonomy.subcategory?.name,
    serviceId: taxonomy.serviceId ?? taxonomy.service?.id,
    serviceName: taxonomy.serviceName ?? taxonomy.service?.name,
  };

  return Object.fromEntries(
    Object.entries(normalized).filter(([, value]) => value != null && String(value).trim() !== "")
  );
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
 * @param {object} [taxonomy={}] – Optional normalized taxonomy for the job
 * @returns {{ embeddingId: string|null, jobMetadata: object, chunkCount: number }}
 */
async function embedJobPost(jobPost, threadId, taxonomy = {}) {
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
  const metadata = {
    ...extractMetadata(jobPost),
    ...buildTaxonomyMetadata(taxonomy),
  };

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
 * @param {object} [taxonomy={}] – Optional normalized taxonomy for the seller profile
 * @returns {{ embeddingId: string|null, profileMetadata: object, chunkCount: number }}
 */
async function embedSellerProfile(sellerProfile, threadId, taxonomy = {}) {
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
  const metadata = {
    ...extractSellerMetadata(sellerProfile),
    ...buildTaxonomyMetadata(taxonomy),
  };
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

// ─── Category retrieval (for buyer job classification) ───────────────────────

const CATEGORY_SEARCH_TOP_K = 20;

/**
 * Generate a focused category search query from the full job post.
 * The query is optimized for semantic search against the category taxonomy.
 *
 * @param {string} jobPost – Full job post text
 * @returns {Promise<string>} – Search query string
 */
async function generateCategorySearchQuery(jobPost) {
  if (!jobPost || typeof jobPost !== "string" || jobPost.trim().length === 0) {
    return "";
  }

  const openai = getOpenAI();
  const prompt = `You are a taxonomy classifier. Given a job post, produce a SHORT search query (1-2 sentences, max 100 words) that best describes the service type/category needed. Focus on: service domain, specific task, and industry. Output ONLY the query text, no preamble.

Job post:
${jobPost.slice(0, 3000)}

Search query:`;

  try {
    const completion = await openai.chat.completions.create({
      model: process.env.OPENAI_MODEL || "gpt-4o-mini",
      messages: [{ role: "user", content: prompt }],
      temperature: 0.2,
      max_tokens: 150,
    });
    const query = (completion.choices?.[0]?.message?.content || "").trim();
    return query;
  } catch (err) {
    console.error("[Pinecone generateCategorySearchQuery]", err.message);
    return jobPost.slice(0, 500);
  }
}

/**
 * Search the categories namespace by embedding the query and returning top matches.
 *
 * @param {string} query – Category search query
 * @param {number} [topK=20]
 * @returns {Promise<Array<{ id: string, score: number, metadata: object }>>}
 */
async function searchCategories(query, topK = CATEGORY_SEARCH_TOP_K) {
  if (!query || typeof query !== "string" || query.trim().length === 0) {
    return [];
  }

  const [embedding] = await generateEmbeddings([query.trim()]);
  if (!embedding || !Array.isArray(embedding) || embedding.length === 0) {
    return [];
  }

  const index = getPineconeIndex();
  const ns = index.namespace(CATEGORIES_NAMESPACE);

  const response = await ns.query({
    vector: embedding,
    topK,
    includeMetadata: true,
    includeValues: false,
  });

  return (response.matches || []).map((m) => ({
    id: m.id,
    score: m.score ?? 0,
    metadata: m.metadata || {},
  }));
}

/**
 * From category search results, select the best service record and return full hierarchy.
 * Prefers "service" records; falls back to "subcategory" then "category".
 *
 * @param {Array<{ id: string, score: number, metadata: object }>} matches
 * @returns {{ categoryId: string|null, categoryName: string|null, subcategoryId: string|null, subcategoryName: string|null, serviceId: string|null, serviceName: string|null }}
 */
function selectBestServiceWithParents(matches) {
  const result = {
    categoryId: null,
    categoryName: null,
    subcategoryId: null,
    subcategoryName: null,
    serviceId: null,
    serviceName: null,
  };

  for (const m of matches) {
    const meta = m.metadata || {};
    const recordType = meta.recordType;

    if (recordType === "service") {
      result.categoryId = meta.categoryId ?? result.categoryId;
      result.categoryName = meta.categoryName ?? result.categoryName;
      result.subcategoryId = meta.subcategoryId ?? result.subcategoryId;
      result.subcategoryName = meta.subcategoryName ?? result.subcategoryName;
      result.serviceId = meta.serviceId ?? meta.recordId?.replace(/^service:/, "") ?? result.serviceId;
      result.serviceName = meta.serviceName ?? meta.title ?? result.serviceName;
      return result;
    }
  }

  for (const m of matches) {
    const meta = m.metadata || {};
    const recordType = meta.recordType;

    if (recordType === "subcategory") {
      result.categoryId = meta.categoryId ?? result.categoryId;
      result.categoryName = meta.categoryName ?? result.categoryName;
      result.subcategoryId = meta.subcategoryId ?? meta.recordId?.replace(/^subcategory:/, "") ?? result.subcategoryId;
      result.subcategoryName = meta.subcategoryName ?? meta.title ?? result.subcategoryName;
      return result;
    }
  }

  for (const m of matches) {
    const meta = m.metadata || {};
    const recordType = meta.recordType;

    if (recordType === "category") {
      result.categoryId = meta.categoryId ?? meta.recordId?.replace(/^category:/, "") ?? result.categoryId;
      result.categoryName = meta.categoryName ?? meta.title ?? result.categoryName;
      return result;
    }
  }

  return result;
}

/**
 * Generate category search query, search categories namespace, and return best service with parent hierarchy.
 *
 * @param {string} jobPost – Full job post text
 * @returns {Promise<{ searchQuery: string, category: object, subcategory: object, service: object }>}
 */
async function retrieveBestCategoryForJob(jobPost) {
  const searchQuery = await generateCategorySearchQuery(jobPost);
  const matches = await searchCategories(searchQuery);
  const hierarchy = selectBestServiceWithParents(matches);

  return {
    searchQuery,
    category: {
      id: hierarchy.categoryId,
      name: hierarchy.categoryName,
    },
    subcategory: {
      id: hierarchy.subcategoryId,
      name: hierarchy.subcategoryName,
    },
    service: {
      id: hierarchy.serviceId,
      name: hierarchy.serviceName,
    },
  };
}

/**
 * Generate a focused category search query from a seller profile.
 * Optimized for semantic search against the category taxonomy.
 *
 * @param {string} sellerProfile – Full seller profile text
 * @returns {Promise<string>} – Search query string
 */
async function generateCategorySearchQueryForProfile(sellerProfile) {
  if (!sellerProfile || typeof sellerProfile !== "string" || sellerProfile.trim().length === 0) {
    return "";
  }

  const openai = getOpenAI();
  const prompt = `You are a taxonomy classifier. Given a seller profile (skills, services offered, experience), produce a SHORT search query (1-2 sentences, max 100 words) that best describes the service type/category they offer. Focus on: service domain, specific skills/tasks, and industry. Output ONLY the query text, no preamble.

Seller profile:
${sellerProfile.slice(0, 3000)}

Search query:`;

  try {
    const completion = await openai.chat.completions.create({
      model: process.env.OPENAI_MODEL || "gpt-4o-mini",
      messages: [{ role: "user", content: prompt }],
      temperature: 0.2,
      max_tokens: 150,
    });
    const query = (completion.choices?.[0]?.message?.content || "").trim();
    return query;
  } catch (err) {
    console.error("[Pinecone generateCategorySearchQueryForProfile]", err.message);
    return sellerProfile.slice(0, 500);
  }
}

/**
 * Generate category search query from seller profile, search categories namespace, and return best service with parent hierarchy.
 *
 * @param {string} sellerProfile – Full seller profile text
 * @returns {Promise<{ searchQuery: string, category: object, subcategory: object, service: object }>}
 */
async function retrieveBestCategoryForProfile(sellerProfile) {
  const searchQuery = await generateCategorySearchQueryForProfile(sellerProfile);
  const matches = await searchCategories(searchQuery);
  const hierarchy = selectBestServiceWithParents(matches);

  return {
    searchQuery,
    category: {
      id: hierarchy.categoryId,
      name: hierarchy.categoryName,
    },
    subcategory: {
      id: hierarchy.subcategoryId,
      name: hierarchy.subcategoryName,
    },
    service: {
      id: hierarchy.serviceId,
      name: hierarchy.serviceName,
    },
  };
}

// ─── Seller search & rerank (for buyer agent) ────────────────────────────────

const RERANK_MODEL = "bge-reranker-v2-m3";
const SEARCH_TOP_K = 100;
const RERANK_TOP_N = 5;
const SELLER_FINAL_TOP_N = 10;
const LLM_INPUT_LIMIT = 20;
const MIN_MATCH_SCORE = 1;

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

function summarizeTaxonomy(taxonomy = {}) {
  const meta = buildTaxonomyMetadata(taxonomy);
  return {
    categoryId: meta.categoryId ?? null,
    categoryName: meta.categoryName ?? null,
    subcategoryId: meta.subcategoryId ?? null,
    subcategoryName: meta.subcategoryName ?? null,
    serviceId: meta.serviceId ?? null,
    serviceName: meta.serviceName ?? null,
  };
}

function buildRetrievalQuery(fallbackText, taxonomy = {}) {
  const serviceName = taxonomy.service?.name || taxonomy.serviceName;
  const subcategoryName = taxonomy.subcategory?.name || taxonomy.subcategoryName;
  const categoryName = taxonomy.category?.name || taxonomy.categoryName;
  const searchQuery = taxonomy.searchQuery?.trim();

  if (searchQuery) {
    return searchQuery;
  }

  const parts = [serviceName, subcategoryName, categoryName].filter(Boolean);
  if (parts.length > 0) {
    return `Service: ${parts.join(" > ")}. ${String(fallbackText || "").trim().slice(0, 500)}`.trim();
  }

  return String(fallbackText || "").trim();
}

function buildTaxonomyFilterCandidates(taxonomy = {}) {
  const meta = summarizeTaxonomy(taxonomy);
  const filters = [];

  if (meta.serviceId) {
    filters.push({
      label: `serviceId=${meta.serviceId}`,
      filter: { serviceId: { $eq: meta.serviceId } },
    });
  }
  if (meta.subcategoryId) {
    filters.push({
      label: `subcategoryId=${meta.subcategoryId}`,
      filter: { subcategoryId: { $eq: meta.subcategoryId } },
    });
  }
  if (meta.categoryId) {
    filters.push({
      label: `categoryId=${meta.categoryId}`,
      filter: { categoryId: { $eq: meta.categoryId } },
    });
  }

  filters.push({ label: "none", filter: null });
  return filters;
}

async function queryWithFilterFallback(ns, vector, topK, taxonomy = {}) {
  const filterCandidates = buildTaxonomyFilterCandidates(taxonomy);

  for (const candidate of filterCandidates) {
    const response = await ns.query({
      vector,
      topK,
      includeMetadata: true,
      includeValues: false,
      ...(candidate.filter ? { filter: candidate.filter } : {}),
    });

    const matches = response.matches || [];
    if (matches.length > 0 || candidate.filter == null) {
      return { matches, appliedFilter: candidate.label };
    }
  }

  return { matches: [], appliedFilter: "none" };
}

function getTaxonomyMatchLevel(candidateMeta = {}, taxonomy = {}) {
  const requested = summarizeTaxonomy(taxonomy);
  if (requested.serviceId && candidateMeta.serviceId === requested.serviceId) return "service";
  if (requested.subcategoryId && candidateMeta.subcategoryId === requested.subcategoryId) return "subcategory";
  if (requested.categoryId && candidateMeta.categoryId === requested.categoryId) return "category";
  return "none";
}

function fallbackMatchScore({ rank, metadata, taxonomy }) {
  const level = getTaxonomyMatchLevel(metadata, taxonomy);
  const baseByLevel = {
    service: 72,
    subcategory: 42,
    category: 18,
    none: 0,
  };
  const base = baseByLevel[level] ?? 0;
  if (base === 0) {
    return 0;
  }

  const rankPenalty = Math.min(24, Math.max(0, rank - 1) * 2);
  return Math.max(1, base - rankPenalty);
}

function normalizeMatchScore(scored, fallbackScore = 0) {
  if (scored?.isMatch === false) {
    return 0;
  }

  const rawScore = Number(scored?.matchScore);
  if (!Number.isFinite(rawScore)) {
    return Math.max(0, fallbackScore);
  }

  return Math.min(100, Math.max(0, Math.round(rawScore)));
}

/**
 * Search seller-profile namespace by job post (embed job, query by vector).
 * Deduplicates by profileId and returns unique profiles with best chunk.
 *
 * @param {string} jobPost – Full job post text
 * @param {number} [topK=10] – Max number of unique profiles to consider
 * @returns {Promise<Array<{ profileId: string, profileText: string, metadata: object }>>}
 */
async function searchSellerProfiles(jobPost, topK = 10, taxonomy = {}) {
  logSection("1. SEARCH – Query & raw results");

  if (jobPost == null || typeof jobPost !== "string" || jobPost.trim().length === 0) {
    logLine("Status", "Skipped (empty job post)");
    return [];
  }

  const retrievalQuery = buildRetrievalQuery(jobPost, taxonomy);
  logLine("Query (matching query preview)", truncate(retrievalQuery, 200));
  logLine("Namespace", SELLER_PROFILE_NAMESPACE);
  logLine("TopK requested", SEARCH_TOP_K);
  logLine("Requested service", taxonomy.service?.name || taxonomy.serviceName || "unknown");

  const [embedding] = await generateEmbeddings([retrievalQuery]);
  if (!embedding || !Array.isArray(embedding) || embedding.length === 0) {
    logLine("Status", "Skipped (no embedding)");
    return [];
  }

  logLine("Embedding dimensions", embedding.length);

  const index = getPineconeIndex();
  const ns = index.namespace(SELLER_PROFILE_NAMESPACE);

  const { matches, appliedFilter } = await queryWithFilterFallback(ns, embedding, SEARCH_TOP_K, taxonomy);
  logLine("Applied filter", appliedFilter);
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
async function rerankSellerProfiles(jobPost, searchResults, topN = RERANK_TOP_N, taxonomy = {}) {
  logSection("2. RERANK – Pinecone Inference");

  if (searchResults.length === 0) {
    logLine("Status", "No input – returning []");
    return [];
  }

  const rerankQuery = buildRetrievalQuery(jobPost, taxonomy);
  logLine("Query (rerank query preview)", truncate(rerankQuery, 150));
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
      query: rerankQuery,
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
async function scoreWithLLM(jobPost, profiles, taxonomy = {}) {
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
  const taxonomySummary = summarizeTaxonomy(taxonomy);
  const systemPrompt = `You are matching seller profiles to a buyer job.

For each seller profile, read the buyer job and the seller profile text, then give:
- matchScore: integer from 0 to 100
- matchExplanation: short analysis in 1-2 sentences explaining why the score fits

Scoring guidance:
- 90-100: excellent match
- 70-89: strong match
- 40-69: decent match
- 10-39: weak but related match
- 0-9: very poor match or barely related

Return ONLY a JSON array. Each object must contain:
- profileIndex: number
- matchScore: integer 0-100
- matchExplanation: string`;

  const profileList = profiles
    .map(
      (p, i) => {
        const meta = p.metadata || {};
        return `[Profile ${i}] (profileId: ${p.profileId})
Title: ${meta.title || "Unknown"}
Service: ${meta.serviceName || "Unknown"}
Subcategory: ${meta.subcategoryName || "Unknown"}
Category: ${meta.categoryName || "Unknown"}
Location: ${meta.location || "Unknown"}
Rate: ${meta.rate || "Unknown"}
Profile text:
${(p.profileText || "").slice(0, 1800)}`;
      }
    )
    .join("\n\n");

  const userPrompt = `Requested taxonomy:
- Service: ${taxonomySummary.serviceName || "Unknown"} (${taxonomySummary.serviceId || "n/a"})
- Subcategory: ${taxonomySummary.subcategoryName || "Unknown"} (${taxonomySummary.subcategoryId || "n/a"})
- Category: ${taxonomySummary.categoryName || "Unknown"} (${taxonomySummary.categoryId || "n/a"})

Buyer job:
${jobPost.slice(0, 3200)}

Seller profiles:
${profileList}

Score every seller profile against this buyer job and return the JSON array only.`;

  try {
    const completion = await openai.chat.completions.create({
      model,
      messages: [
        { role: "system", content: systemPrompt },
        { role: "user", content: userPrompt },
      ],
      temperature: 0.1,
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
      const fallbackScore = fallbackMatchScore({ rank: i + 1, metadata: meta, taxonomy });
      const normalizedScore = normalizeMatchScore(scored, fallbackScore);
      return {
        profileId: p.profileId,
        sellerName,
        profileText: p.profileText || "",
        matchScore: normalizedScore,
        matchExplanation: String(
          scored.matchExplanation ||
            (normalizedScore > 0
              ? "Weak related match based on taxonomy and retrieval signals."
              : "Not a relevant match.")
        ).slice(0, 500),
        vectorRank: i + 1,
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
    logLine("Fallback", "Using taxonomy-aware heuristic scores");
    return profiles.map((p, i) => {
      const meta = p.metadata || {};
      const title = meta.title || (p.profileText || "").split("\n")[0] || "Seller";
      const fallbackScore = fallbackMatchScore({ rank: i + 1, metadata: meta, taxonomy });
      return {
        profileId: p.profileId,
        sellerName: title.replace(/^#+\s*/, "").trim() || "Seller",
        profileText: p.profileText || "",
        matchScore: fallbackScore,
        matchExplanation:
          fallbackScore > 0
            ? "Relevant match based on taxonomy and retrieval signals."
            : "Not a relevant match.",
        vectorRank: i + 1,
        metadata: { location: meta.location, rate: meta.rate, ...meta },
      };
    });
  }
}

/**
 * Find best matching sellers for a job: filtered search → LLM score.
 *
 * @param {string} jobPost – Full job post text
 * @returns {Promise<Array<{ profileId, sellerName, profileText, matchScore, matchExplanation, metadata }>>}
 */
async function findMatchingSellers(jobPost, taxonomy = {}) {
  console.log("\n" + "═".repeat(60));
  console.log(`${SELLER_MATCH_LOG_PREFIX} FIND MATCHING SELLERS – START`);
  console.log("═".repeat(60));
  logLine("Job post length", (jobPost || "").length);
  logLine("Pipeline", "taxonomy-filtered search → LLM score → ranked top 10");

  const searchResults = await searchSellerProfiles(jobPost, SEARCH_TOP_K, taxonomy);
  if (searchResults.length === 0) {
    logSection("FINAL – No sellers matched");
    logLine("Result", "[]");
    console.log("");
    return [];
  }

  const llmCandidates = searchResults.slice(0, LLM_INPUT_LIMIT);
  logLine("Candidates sent to LLM", llmCandidates.length);
  const scored = await scoreWithLLM(jobPost, llmCandidates, taxonomy);

  const sorted = scored.slice().sort((a, b) => (b.matchScore ?? 0) - (a.matchScore ?? 0));

  const result = sorted.slice(0, SELLER_FINAL_TOP_N);

  if (result.length === 0) {
    logSection("4. FINAL – No sellers available after scoring");
    logLine("Result", "[]");
    console.log("");
    return [];
  }

  logSection("4. FULL MATCH BREAKDOWN – All scored sellers");
  logLine("Total shown", result.length);
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

// ─── Job search & rerank (for seller agent) ───────────────────────────────────

const JOB_MATCH_LOG_PREFIX = "[JobMatch]";

function logJobSection(title) {
  console.log("\n" + "─".repeat(60));
  console.log(`${JOB_MATCH_LOG_PREFIX} ${title}`);
  console.log("─".repeat(60));
}

/**
 * Search seller-jobs namespace by seller profile (embed profile, query by vector).
 * Deduplicates by jobId and returns unique jobs with best chunk.
 *
 * @param {string} sellerProfile – Full seller profile text
 * @param {number} [topK=10] – Max number of unique jobs to consider
 * @returns {Promise<Array<{ jobId: string, jobText: string, metadata: object }>>}
 */
async function searchJobsForSeller(sellerProfile, topK = 10, taxonomy = {}) {
  logJobSection("1. SEARCH – Query & raw results");

  if (sellerProfile == null || typeof sellerProfile !== "string" || sellerProfile.trim().length === 0) {
    logLine("Status", "Skipped (empty seller profile)");
    return [];
  }

  const retrievalQuery = buildRetrievalQuery(sellerProfile, taxonomy);
  logLine("Query (matching query preview)", truncate(retrievalQuery, 200));
  logLine("Namespace", NAMESPACE);
  logLine("TopK requested", SEARCH_TOP_K);
  logLine("Seller service", taxonomy.service?.name || taxonomy.serviceName || "unknown");

  const [embedding] = await generateEmbeddings([retrievalQuery]);
  if (!embedding || !Array.isArray(embedding) || embedding.length === 0) {
    logLine("Status", "Skipped (no embedding)");
    return [];
  }

  logLine("Embedding dimensions", embedding.length);

  const index = getPineconeIndex();
  const ns = index.namespace(NAMESPACE);

  const { matches, appliedFilter } = await queryWithFilterFallback(ns, embedding, SEARCH_TOP_K, taxonomy);
  logLine("Applied filter", appliedFilter);
  logLine("Raw matches count", matches.length);

  if (matches.length === 0) {
    logLine("Status", "No matches – returning []");
    return [];
  }

  console.log("\n  Raw hits (id, jobId, score):");
  matches.forEach((m, i) => {
    const jid = m.metadata?.jobId || (m.id && m.id.replace(/_chunk_\d+$/, "")) || m.id;
    console.log(`    ${i + 1}. id=${m.id} jobId=${jid} score=${(m.score ?? 0).toFixed(4)}`);
  });

  const byJob = new Map();
  for (const m of matches) {
    const jobId = m.metadata?.jobId || (m.id && m.id.replace(/_chunk_\d+$/, ""));
    if (!jobId) continue;
    const existing = byJob.get(jobId);
    const jobText = m.metadata?.fullJobPost || m.metadata?.chunkText || "";
    const score = m.score ?? 0;
    if (!existing || score > (existing.score ?? 0)) {
      byJob.set(jobId, {
        jobId,
        jobText: jobText || existing?.jobText || "",
        metadata: { ...m.metadata },
        score,
      });
    } else if (existing && !existing.jobText && jobText) {
      existing.jobText = jobText;
    }
  }

  const list = Array.from(byJob.values())
    .sort((a, b) => (b.score ?? 0) - (a.score ?? 0))
    .slice(0, topK)
    .map(({ jobId, jobText, metadata }) => ({
      jobId,
      jobText,
      metadata: metadata || {},
    }));

  console.log("\n  After dedupe by jobId (best chunk per job):");
  logLine("Jobs kept", list.length);
  list.forEach((j, i) => {
    const title = (j.metadata?.title || (j.jobText || "").split("\n")[0] || "").slice(0, 50);
    console.log(`    ${i + 1}. ${j.jobId}  "${truncate(title, 40)}"`);
  });

  return list;
}

/**
 * Rerank job results using Pinecone Inference (bge-reranker-v2-m3).
 *
 * @param {string} sellerProfile – Seller profile text (query for reranker)
 * @param {Array<{ jobId: string, jobText: string, metadata: object }>} searchResults
 * @param {number} [topN=5]
 * @returns {Promise<Array<{ jobId: string, jobText: string, metadata: object }>>}
 */
async function rerankJobs(sellerProfile, searchResults, topN = RERANK_TOP_N, taxonomy = {}) {
  logJobSection("2. RERANK – Pinecone Inference");

  if (searchResults.length === 0) {
    logLine("Status", "No input – returning []");
    return [];
  }

  const rerankQuery = buildRetrievalQuery(sellerProfile, taxonomy);
  logLine("Query (rerank query preview)", truncate(rerankQuery, 150));
  logLine("Model", RERANK_MODEL);
  logLine("Input documents count", searchResults.length);
  logLine("TopN", topN);

  const documents = searchResults.map((r) => r.jobText || r.metadata?.chunkText || "");
  if (documents.every((d) => !d)) {
    logLine("Status", "No text to rerank – using search order");
    return searchResults.slice(0, topN);
  }

  try {
    const inference = getPineconeClient().inference;
    const result = await inference.rerank({
      model: RERANK_MODEL,
      query: rerankQuery,
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
      const jobId = searchResults[idx]?.jobId ?? "?";
      console.log(`    ${i + 1}. index=${idx} jobId=${jobId} score=${score}`);
    });

    const order = hits.map((h) => (typeof h.index === "number" ? h.index : parseInt(h.index, 10)));
    const reranked = order
      .filter((i) => i >= 0 && i < searchResults.length)
      .map((i) => searchResults[i]);

    console.log("\n  Final reranked order (jobIds):");
    reranked.forEach((j, i) => console.log(`    ${i + 1}. ${j.jobId}`));

    return reranked;
  } catch (err) {
    console.error(`${JOB_MATCH_LOG_PREFIX} Rerank failed, using search order:`, err.message);
    logLine("Fallback", "Using original search order");
    return searchResults.slice(0, topN);
  }
}

/**
 * Use OpenAI to score (0–100) and explain each job match for the seller profile.
 *
 * @param {string} sellerProfile – Seller profile text
 * @param {Array<{ jobId: string, jobText: string, metadata: object }>} jobs
 * @returns {Promise<Array<{ jobId: string, jobTitle: string, jobText: string, matchScore: number, matchExplanation: string, metadata: object }>>}
 */
async function scoreJobsWithLLM(sellerProfile, jobs, taxonomy = {}) {
  logJobSection("3. LLM SCORING – Job match score & explanation");

  if (jobs.length === 0) {
    logLine("Status", "No jobs – returning []");
    return [];
  }

  const model = process.env.OPENAI_MODEL || "gpt-4o-mini";
  logLine("Model", model);
  logLine("Jobs to score", jobs.length);
  logLine("Seller profile length (chars)", sellerProfile.length);

  const openai = getOpenAI();
  const taxonomySummary = summarizeTaxonomy(taxonomy);
  const systemPrompt = `You are matching buyer jobs to a seller profile.

For each job, read the seller profile and the buyer job text, then give:
- matchScore: integer from 0 to 100
- matchExplanation: short analysis in 1-2 sentences explaining why the score fits

Scoring guidance:
- 90-100: excellent match
- 70-89: strong match
- 40-69: decent match
- 10-39: weak but related match
- 0-9: very poor match or barely related

Return ONLY a JSON array. Each object must contain:
- jobIndex: number
- matchScore: integer 0-100
- matchExplanation: string`;

  const jobList = jobs
    .map(
      (j, i) => {
        const meta = j.metadata || {};
        return `[Job ${i}] (jobId: ${j.jobId})
Title: ${meta.title || "Unknown"}
Service: ${meta.serviceName || "Unknown"}
Subcategory: ${meta.subcategoryName || "Unknown"}
Category: ${meta.categoryName || "Unknown"}
Location: ${meta.location || "Unknown"}
Budget: ${meta.budget || "Unknown"}
Timeline: ${meta.timeline || "Unknown"}
Job text:
${(j.jobText || "").slice(0, 1800)}`;
      }
    )
    .join("\n\n");

  const userPrompt = `Seller taxonomy:
- Service: ${taxonomySummary.serviceName || "Unknown"} (${taxonomySummary.serviceId || "n/a"})
- Subcategory: ${taxonomySummary.subcategoryName || "Unknown"} (${taxonomySummary.subcategoryId || "n/a"})
- Category: ${taxonomySummary.categoryName || "Unknown"} (${taxonomySummary.categoryId || "n/a"})

Seller profile:
${sellerProfile.slice(0, 3200)}

Jobs:
${jobList}

Score every job against this seller profile and return the JSON array only.`;

  try {
    const completion = await openai.chat.completions.create({
      model,
      messages: [
        { role: "system", content: systemPrompt },
        { role: "user", content: userPrompt },
      ],
      temperature: 0.1,
    });

    const content = (completion.choices?.[0]?.message?.content || "").trim();
    logLine("LLM response length (chars)", content.length);

    const jsonMatch = content.match(/\[[\s\S]*\]/);
    const arr = jsonMatch ? JSON.parse(jsonMatch[0]) : [];
    logLine("Parsed JSON array length", arr.length);

    console.log("\n  LLM raw output (jobIndex, matchScore, matchExplanation preview):");
    arr.forEach((o, i) => {
      const score = o.matchScore != null ? o.matchScore : "?";
      const expl = truncate(String(o.matchExplanation || ""), 80);
      console.log(`    ${i + 1}. index=${o.jobIndex} score=${score}  "${expl}"`);
    });

    const byIndex = new Map(arr.map((o) => [Number(o.jobIndex), o]));

    return jobs.map((j, i) => {
      const meta = j.metadata || {};
      const title = meta.title || (j.jobText || "").split("\n")[0] || "Job";
      const jobTitle = title.replace(/^#+\s*/, "").replace(/\*\*/g, "").trim() || "Job";
      const scored = byIndex.get(i) || {};
      const fallbackScore = fallbackMatchScore({ rank: i + 1, metadata: meta, taxonomy });
      return {
        jobId: j.jobId,
        jobTitle,
        jobText: j.jobText || "",
        matchScore: normalizeMatchScore(scored, fallbackScore),
        matchExplanation: String(
          scored.matchExplanation || (fallbackScore > 0 ? "Relevant match based on taxonomy and retrieval signals." : "Not a relevant match.")
        ).slice(0, 500),
        metadata: {
          budget: meta.budget,
          location: meta.location,
          timeline: meta.timeline,
          ...meta,
        },
      };
    });
  } catch (err) {
    console.error(`${JOB_MATCH_LOG_PREFIX} LLM failed:`, err.message);
    logLine("Fallback", "Using taxonomy-aware heuristic scores");
    return jobs.map((j, i) => {
      const meta = j.metadata || {};
      const title = meta.title || (j.jobText || "").split("\n")[0] || "Job";
      const fallbackScore = fallbackMatchScore({ rank: i + 1, metadata: meta, taxonomy });
      return {
        jobId: j.jobId,
        jobTitle: title.replace(/^#+\s*/, "").trim() || "Job",
        jobText: j.jobText || "",
        matchScore: fallbackScore,
        matchExplanation:
          fallbackScore > 0
            ? "Relevant match based on taxonomy and retrieval signals."
            : "Not a relevant match.",
        metadata: { budget: meta.budget, location: meta.location, timeline: meta.timeline, ...meta },
      };
    });
  }
}

/**
 * Find best matching jobs for a seller: filtered search → LLM score.
 *
 * @param {string} sellerProfile – Full seller profile text
 * @returns {Promise<Array<{ jobId, jobTitle, jobText, matchScore, matchExplanation, metadata }>>}
 */
async function findMatchingJobs(sellerProfile, taxonomy = {}) {
  console.log("\n" + "═".repeat(60));
  console.log(`${JOB_MATCH_LOG_PREFIX} FIND MATCHING JOBS – START`);
  console.log("═".repeat(60));
  logLine("Seller profile length", (sellerProfile || "").length);
  logLine("Pipeline", "taxonomy-filtered search → LLM score → ranked jobs");

  const searchResults = await searchJobsForSeller(sellerProfile, SEARCH_TOP_K, taxonomy);
  if (searchResults.length === 0) {
    logJobSection("FINAL – No jobs matched");
    logLine("Result", "[]");
    console.log("");
    return [];
  }

  const llmCandidates = searchResults.slice(0, LLM_INPUT_LIMIT);
  logLine("Candidates sent to LLM", llmCandidates.length);
  const result = await scoreJobsWithLLM(sellerProfile, llmCandidates, taxonomy);

  const sorted = result.slice().sort((a, b) => (b.matchScore ?? 0) - (a.matchScore ?? 0));

  if (sorted.length === 0) {
    logJobSection("4. FINAL – No jobs available after scoring");
    logLine("Result", "[]");
    console.log("");
    return [];
  }

  logJobSection("4. FULL MATCH BREAKDOWN – All scored jobs");
  logLine("Total shown", sorted.length);
  console.log("");
  sorted.forEach((job, i) => {
    console.log(`  ┌─ Rank ${i + 1} ─────────────────────────────────`);
    logLine("  │ jobId", job.jobId);
    logLine("  │ jobTitle", truncate(job.jobTitle, 60));
    logLine("  │ matchScore", job.matchScore);
    if (job.metadata?.budget) logLine("  │ budget", job.metadata.budget);
    if (job.metadata?.location) logLine("  │ location", job.metadata.location);
    if (job.metadata?.timeline) logLine("  │ timeline", job.metadata.timeline);
    console.log(`  │ matchExplanation: ${truncate(job.matchExplanation, 200)}`);
    console.log("  └" + "─".repeat(40));
  });

  console.log("\n" + "═".repeat(60));
  console.log(`${JOB_MATCH_LOG_PREFIX} FIND MATCHING JOBS – END (${sorted.length} jobs)`);
  console.log("═".repeat(60) + "\n");

  return sorted;
}

export {
  embedJobPost,
  embedSellerProfile,
  chunkText,
  extractMetadata,
  extractSellerMetadata,
  generateEmbeddings,
  retrieveBestCategoryForJob,
  retrieveBestCategoryForProfile,
  findMatchingSellers,
  searchSellerProfiles,
  rerankSellerProfiles,
  scoreWithLLM,
  findMatchingJobs,
  searchJobsForSeller,
  rerankJobs,
  scoreJobsWithLLM,
  NAMESPACE,
  SELLER_PROFILE_NAMESPACE,
  CATEGORIES_NAMESPACE,
  INDEX_NAME,
};
