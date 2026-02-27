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
let pineconeIndex = null;
let openaiClient = null;

function getPineconeIndex() {
  if (!pineconeIndex) {
    const pc = new Pinecone({ apiKey: process.env.PINECONE_API_KEY });
    pineconeIndex = pc.index(INDEX_NAME);
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

export {
  embedJobPost,
  embedSellerProfile,
  chunkText,
  extractMetadata,
  extractSellerMetadata,
  generateEmbeddings,
  NAMESPACE,
  SELLER_PROFILE_NAMESPACE,
  INDEX_NAME,
};
