/**
 * Job embedding service.
 *
 * Key improvements over v1:
 *  1. buildSearchableText — written in provider-facing natural language so a
 *     seller query ("I provide concrete work in San Jose") matches well.
 *  2. searchJobsByQuery now accepts serviceCategoryNames[] filter — only jobs
 *     matching the seller's services are returned (mirrors seller-side filter).
 *  3. Pass-2 fallback: if strict category filter returns 0, widen to all open
 *     jobs so the reranker can still operate.
 *  4. similarity_score (1 - distance) exposed for downstream use.
 *  5. Normalises category names to lowercase consistently.
 */

import 'dotenv/config';
import { OpenAIEmbeddings } from '@langchain/openai';
import prisma from '../prisma/client.js';

const LOG_PREFIX = '[JobEmbedding]';
const EMBEDDING_DIMENSION = 1536;
const OPENAI_API_KEY = process.env.OPENAI_API_KEY;

/* ─────────────────────────────────────────────────────────────
   HELPERS
───────────────────────────────────────────────────────────── */

function normaliseCategory(name) {
  return String(name || '').toLowerCase().trim();
}

/* ─────────────────────────────────────────────────────────────
   EMBED
───────────────────────────────────────────────────────────── */

export async function embedQueryForJob(text) {
  if (!OPENAI_API_KEY || !OPENAI_API_KEY.trim() || !text || !String(text).trim()) {
    return null;
  }
  try {
    const embeddings = new OpenAIEmbeddings({
      model: 'text-embedding-3-small',
      openAIApiKey: OPENAI_API_KEY,
      dimensions: EMBEDDING_DIMENSION,
    });
    const vectors = await embeddings.embedDocuments([String(text).trim()]);
    const vec = vectors[0];
    return Array.isArray(vec) && vec.length === EMBEDDING_DIMENSION ? vec : null;
  } catch (err) {
    console.error(LOG_PREFIX, 'embedQueryForJob error:', err.message);
    return null;
  }
}

/* ─────────────────────────────────────────────────────────────
   SEARCHABLE TEXT BUILDER
   
   CRITICAL DESIGN: Written in provider-facing language — the same natural
   language a seller uses when searching ("I do concrete work, looking for
   jobs in San Jose with budget around $5000") should match well against
   this text. This bridges the semantic gap between seller queries and job
   postings.
───────────────────────────────────────────────────────────── */

export function buildSearchableText(job) {
  const parts = [];

  // ── 1. Service category (strongest signal — repeat for emphasis) ──────────
  const service = (job.serviceCategoryName || job.service_category_name || '').trim();
  if (service) {
    parts.push(`This job requires a ${service} provider.`);
    parts.push(`Looking for someone who offers ${service} services.`);
  }

  // ── 2. Job title and description ──────────────────────────────────────────
  if (job.title && String(job.title).trim()) {
    parts.push(String(job.title).trim());
  }
  if (job.description && String(job.description).trim()) {
    parts.push(String(job.description).trim());
  }

  // ── 3. Location ───────────────────────────────────────────────────────────
  const loc = job.location;
  if (loc && typeof loc === 'object') {
    const locParts = [];
    if (loc.address) locParts.push(String(loc.address));
    if (loc.city)    locParts.push(String(loc.city));
    if (loc.state)   locParts.push(String(loc.state));
    if (locParts.length) parts.push(`Location: ${locParts.join(', ')}.`);
  } else if (typeof loc === 'string' && loc.trim()) {
    parts.push(`Location: ${loc.trim()}.`);
  }

  // ── 4. Budget ─────────────────────────────────────────────────────────────
  const budget = job.budget;
  if (budget && typeof budget === 'object') {
    const min = budget.min ?? budget.Min;
    const max = budget.max ?? budget.Max;
    if (min != null && max != null) {
      parts.push(`Budget: $${min}–$${max}.`);
    } else if (max != null) {
      parts.push(`Budget: up to $${max}.`);
    } else if (min != null) {
      parts.push(`Budget: from $${min}.`);
    }
  }

  // ── 5. Timeline ───────────────────────────────────────────────────────────
  if (job.startDate || job.start_date) {
    parts.push(`Start date: ${job.startDate || job.start_date}.`);
  }
  if (job.endDate || job.end_date) {
    const end = job.endDate || job.end_date;
    if (end && end !== 'flexible') parts.push(`End date: ${end}.`);
  }

  // ── 6. Priorities / requirements ──────────────────────────────────────────
  const priorities = job.priorities;
  if (priorities && typeof priorities === 'object' && !Array.isArray(priorities)) {
    const must = priorities.must_have;
    if (must && typeof must === 'object' && Object.keys(must).length > 0) {
      parts.push(`Must have: ${Object.keys(must).join(', ')}.`);
    }
    const nice = priorities.nice_to_have;
    if (nice && typeof nice === 'object' && Object.keys(nice).length > 0) {
      parts.push(`Preferred: ${Object.keys(nice).join(', ')}.`);
    }
  } else if (Array.isArray(priorities) && priorities.length > 0) {
    parts.push(`Priorities: ${priorities.join(', ')}.`);
  }

  const sr = job.specificRequirements || job.specific_requirements;
  if (sr) {
    if (typeof sr === 'string' && sr.trim()) {
      parts.push(`Requirements: ${sr.trim()}.`);
    } else if (typeof sr === 'object' && Object.keys(sr).length > 0) {
      parts.push(`Requirements: ${JSON.stringify(sr)}.`);
    }
  }

  const text = parts.join(' ').trim();
  return text || 'No job content available.';
}

/* ─────────────────────────────────────────────────────────────
   UPSERT
───────────────────────────────────────────────────────────── */

/**
 * Generate embedding for a job and upsert into job_embeddings.
 * Normalises serviceCategoryName to lowercase before storing.
 *
 * @param {string} jobId - JobListing.id
 * @param {object} job   - Job object (Prisma model or same shape)
 */
export async function upsertJobEmbedding(jobId, job) {
  if (!jobId || !job) {
    console.warn(LOG_PREFIX, 'Missing jobId or job, skipping');
    return;
  }

  console.log(LOG_PREFIX, `Starting embedding for jobId=${jobId}`);

  if (!OPENAI_API_KEY || OPENAI_API_KEY.trim() === '') {
    console.warn(LOG_PREFIX, 'OPENAI_API_KEY missing, skipping');
    return;
  }

  // Normalise category name before building text
  const normalisedJob = {
    ...job,
    serviceCategoryName: normaliseCategory(job.serviceCategoryName || job.service_category_name || ''),
  };

  const searchableText = buildSearchableText(normalisedJob);
  const preview = searchableText.length > 120 ? searchableText.slice(0, 120) + '...' : searchableText;
  console.log(LOG_PREFIX, `searchableText length=${searchableText.length}, preview="${preview}"`);

  let embedding;
  try {
    const embeddings = new OpenAIEmbeddings({
      model: 'text-embedding-3-small',
      openAIApiKey: OPENAI_API_KEY,
      dimensions: EMBEDDING_DIMENSION,
    });
    const vectors = await embeddings.embedDocuments([searchableText]);
    embedding = vectors[0];
    if (!Array.isArray(embedding) || embedding.length !== EMBEDDING_DIMENSION) {
      console.error(LOG_PREFIX, 'Unexpected embedding dimension:', embedding?.length);
      return;
    }
    console.log(LOG_PREFIX, 'Embedding generated, dimension=', embedding.length);
  } catch (err) {
    console.error(LOG_PREFIX, 'OpenAI error:', err.message);
    return;
  }

  const vectorStr = '[' + embedding.join(',') + ']';

  try {
    const deleted = await prisma.$executeRaw`
      DELETE FROM job_embeddings WHERE job_id = ${jobId}
    `;
    if (deleted > 0) {
      console.log(LOG_PREFIX, `Replaced existing embedding for jobId=${jobId}`);
    }

    await prisma.$executeRaw`
      INSERT INTO job_embeddings (embedding_id, job_id, embedding, searchable_text, updated_at)
      VALUES (gen_random_uuid(), ${jobId}, ${vectorStr}::vector, ${searchableText}, now())
    `;
    console.log(LOG_PREFIX, `✅ Embedding upserted for jobId=${jobId}`);
  } catch (err) {
    console.error(LOG_PREFIX, 'DB error:', err.message, err.stack);
  }
}

/* ─────────────────────────────────────────────────────────────
   SEARCH
───────────────────────────────────────────────────────────── */

/**
 * Semantic search over job embeddings for a seller.
 *
 * Strategy:
 *  1. Normalise each category in serviceCategoryNames.
 *  2. Pass 1 — strict: only jobs whose service_category_name matches one of
 *     the seller's service categories.
 *  3. Pass 2 — if Pass 1 returns 0, widen to all open jobs so the reranker
 *     can filter by relevance (prevents hard 0-results failure).
 *  4. Returns similarity_score (1 - distance) for downstream thresholding.
 *
 * @param {string}   queryText              - Seller-profile–based search query
 * @param {number}   [limit=40]             - Max results
 * @param {string[]} [serviceCategoryNames] - Seller's service categories (raw, normalised internally)
 * @returns {Promise<Array<{ job_id, searchable_text, similarity_score, distance }>>}
 */
export async function searchJobsByQuery(queryText, limit = 40, serviceCategoryNames = []) {
  const cap = Math.min(Math.max(Number(limit) || 40, 1), 100);

  // Normalise all category names
  const categories = Array.isArray(serviceCategoryNames)
    ? serviceCategoryNames.map(normaliseCategory).filter(Boolean)
    : [];

  console.log('\n' + '='.repeat(60));
  console.log(`${LOG_PREFIX} Semantic search: query and retrieval`);
  console.log('='.repeat(60));
  console.log(`\n  Query used for search:`);
  console.log('  ' + '-'.repeat(56));
  console.log('  ' + (queryText || '(empty)'));
  if (categories.length > 0) {
    console.log(`  Service category filter (normalised): [${categories.join(', ')}]`);
  }
  console.log('  ' + '-'.repeat(56) + '\n');

  const vector = await embedQueryForJob(queryText || '');
  if (!vector) {
    console.warn(LOG_PREFIX, 'Could not embed query, returning []');
    console.log('='.repeat(60) + '\n');
    return [];
  }

  const vectorStr = '[' + vector.join(',') + ']';
  let rows = [];

  // ── Pass 1: strict category filter ────────────────────────────────────────
  try {
    if (categories.length > 0) {
      // Match job's service_category_name against any of the seller's categories
      rows = await prisma.$queryRaw`
        SELECT
          e.job_id                                               AS "job_id",
          e.searchable_text                                      AS "searchable_text",
          (e.embedding <=> ${vectorStr}::vector)                 AS distance,
          (1 - (e.embedding <=> ${vectorStr}::vector))           AS similarity_score
        FROM job_embeddings e
        INNER JOIN job_listings j
          ON j.job_id = e.job_id
         AND j.status = 'open'
         AND lower(trim(j.service_category_name)) = ANY(${categories}::text[])
        ORDER BY e.embedding <=> ${vectorStr}::vector
        LIMIT ${cap}
      `;
    } else {
      // No category filter — return all open jobs ranked by similarity
      rows = await prisma.$queryRaw`
        SELECT
          e.job_id                                               AS "job_id",
          e.searchable_text                                      AS "searchable_text",
          (e.embedding <=> ${vectorStr}::vector)                 AS distance,
          (1 - (e.embedding <=> ${vectorStr}::vector))           AS similarity_score
        FROM job_embeddings e
        INNER JOIN job_listings j
          ON j.job_id = e.job_id
         AND j.status = 'open'
        ORDER BY e.embedding <=> ${vectorStr}::vector
        LIMIT ${cap}
      `;
    }
  } catch (err) {
    console.error(LOG_PREFIX, 'Pass-1 query error:', err.message);
    rows = [];
  }

  console.log(`  Pass 1 (category filter [${categories.join(', ')}]): ${rows.length} results`);

  // ── Pass 2: widen if pass 1 returned nothing ──────────────────────────────
  if (rows.length === 0 && categories.length > 0) {
    console.log('  ⚠️  0 results with category filter — widening to all open jobs...');
    try {
      rows = await prisma.$queryRaw`
        SELECT
          e.job_id                                               AS "job_id",
          e.searchable_text                                      AS "searchable_text",
          (e.embedding <=> ${vectorStr}::vector)                 AS distance,
          (1 - (e.embedding <=> ${vectorStr}::vector))           AS similarity_score
        FROM job_embeddings e
        INNER JOIN job_listings j
          ON j.job_id = e.job_id
         AND j.status = 'open'
        ORDER BY e.embedding <=> ${vectorStr}::vector
        LIMIT ${cap}
      `;
      console.log(`  Pass 2 (widened, no category filter): ${rows.length} results`);
    } catch (err) {
      console.error(LOG_PREFIX, 'Pass-2 query error:', err.message);
      rows = [];
    }
  }

  // ── Log results ────────────────────────────────────────────────────────────
  const list = Array.isArray(rows) ? rows : [];
  console.log('\n  Retrieved (top ' + list.length + ' by cosine similarity):');
  console.log('  ' + '-'.repeat(56));
  list.slice(0, 10).forEach((r, i) => {
    const score   = r?.similarity_score != null ? (Number(r.similarity_score) * 100).toFixed(2) : '—';
    const preview = (r?.searchable_text ?? '').slice(0, 100);
    console.log(`  [${i + 1}] job_id: ${r?.job_id}  similarity: ${score}%`);
    console.log(`       ${preview}...`);
    console.log('');
  });
  if (list.length > 10) console.log(`  ... and ${list.length - 10} more jobs`);
  console.log('  ' + '-'.repeat(56));
  console.log('  Total retrieved: ' + list.length + ' jobs');
  console.log('='.repeat(60) + '\n');

  return list.map((r) => ({
    job_id:          r?.job_id ?? '',
    searchable_text: r?.searchable_text ?? '',
    distance:        r?.distance != null ? Number(r.distance) : undefined,
    similarity_score: r?.similarity_score != null ? Number(r.similarity_score) : undefined,
  }));
}

/* ─────────────────────────────────────────────────────────────
   BACKFILL UTILITY
───────────────────────────────────────────────────────────── */

/**
 * Re-embed all open job listings. Run once after deploying to backfill
 * existing jobs with the new richer searchable text.
 *
 * @param {number} [batchSize=50]
 * @returns {Promise<number>} Total jobs processed
 */
export async function backfillJobEmbeddings(batchSize = 50) {
  console.log(LOG_PREFIX, 'Starting backfill of job embeddings...');
  let offset = 0;
  let totalProcessed = 0;

  while (true) {
    const jobs = await prisma.jobListing.findMany({
      where: { status: 'open' },
      take: batchSize,
      skip: offset,
      orderBy: { createdAt: 'asc' },
    });

    if (jobs.length === 0) break;

    for (const job of jobs) {
      await upsertJobEmbedding(job.id, job);
      totalProcessed++;
    }

    offset += jobs.length;
    console.log(LOG_PREFIX, `Backfill progress: ${totalProcessed} jobs processed`);
    await new Promise((r) => setTimeout(r, 200));
  }

  console.log(LOG_PREFIX, `✅ Backfill complete. Total processed: ${totalProcessed}`);
  return totalProcessed;
}