/**
 * Job embedding service.
 * When a job is created, generate an embedding from the job content using OpenAI
 * and store it in JobsEmbedding for semantic matching.
 */
import 'dotenv/config';

import { OpenAIEmbeddings } from '@langchain/openai';

import prisma from '../prisma/client.js';

const LOG_PREFIX = '[JobEmbedding]';
const EMBEDDING_DIMENSION = 1536;
const OPENAI_API_KEY = process.env.OPENAI_API_KEY;

/**
 * Get embedding vector for a single query string (same model/dimension as job embeddings).
 * @param {string} text - Query or text to embed
 * @returns {Promise<number[]|null>} 1536-dim vector or null on error
 */
export async function embedQueryForJob(text) {
  if (!OPENAI_API_KEY || !OPENAI_API_KEY.trim() || !text || !String(text).trim()) {
    return null;
  }
  const embeddings = new OpenAIEmbeddings({
    model: 'text-embedding-3-small',
    openAIApiKey: OPENAI_API_KEY,
    dimensions: EMBEDDING_DIMENSION,
  });
  const vectors = await embeddings.embedDocuments([String(text).trim()]);
  const vec = vectors[0];
  return Array.isArray(vec) && vec.length === EMBEDDING_DIMENSION ? vec : null;
}

/**
 * Build searchable text from a job (Prisma model or plain object with same shape).
 * @param {object} job - Job listing (id, title, description, serviceCategoryName, budget, location, priorities, etc.)
 * @returns {string}
 */
function buildSearchableText(job) {
  const parts = [];

  if (job.title && String(job.title).trim()) {
    parts.push(String(job.title).trim());
  }
  if (job.description && String(job.description).trim()) {
    parts.push(String(job.description).trim());
  }
  if (job.serviceCategoryName && String(job.serviceCategoryName).trim()) {
    parts.push('Service: ' + String(job.serviceCategoryName).trim());
  }
  if (job.budget && typeof job.budget === 'object') {
    const b = job.budget;
    if (b.min != null || b.max != null) {
      parts.push('Budget: $' + (b.min ?? '?') + '-' + (b.max ?? '?') + ' range');
    }
  }
  if (job.location && typeof job.location === 'object') {
    const loc = job.location;
    if (loc.address) parts.push('Location: ' + String(loc.address));
    if (loc.city) parts.push('City: ' + String(loc.city));
    if (loc.state) parts.push('State: ' + String(loc.state));
  }
  if (Array.isArray(job.priorities) && job.priorities.length > 0) {
    parts.push('Priorities: ' + job.priorities.join(', '));
  } else if (job.priorities && typeof job.priorities === 'object') {
    const p = job.priorities;
    if (Array.isArray(p)) parts.push('Priorities: ' + p.join(', '));
  }
  if (job.startDate) parts.push('Start: ' + String(job.startDate));
  if (job.endDate) parts.push('End: ' + String(job.endDate));
  if (job.specificRequirements && typeof job.specificRequirements === 'object') {
    const sr = job.specificRequirements;
    if (typeof sr === 'string') parts.push('Requirements: ' + sr);
    else if (Object.keys(sr).length > 0) parts.push('Requirements: ' + JSON.stringify(sr));
  }

  const text = parts.join(' | ').trim();
  return text || 'No job content';
}

/**
 * Generate embedding for a job and store in job_embeddings.
 * One embedding row per job. Replaces existing embedding for this job_id if present (idempotent).
 *
 * @param {string} jobId - Job listing ID (UUID)
 * @param {object} job - Job object (Prisma model or same shape)
 * @returns {Promise<void>}
 */
export async function upsertJobEmbedding(jobId, job) {
  if (!jobId || !job) {
    console.warn(LOG_PREFIX, 'Missing jobId or job, skipping');
    return;
  }

  console.log(LOG_PREFIX, 'Starting embedding for jobId=', jobId);

  if (!OPENAI_API_KEY || OPENAI_API_KEY.trim() === '') {
    console.warn(LOG_PREFIX, 'OPENAI_API_KEY is missing, skipping embedding generation');
    return;
  }

  const searchableText = buildSearchableText(job);
  const preview = searchableText.length > 120 ? searchableText.slice(0, 120) + '...' : searchableText;
  console.log(LOG_PREFIX, 'Built searchableText length=', searchableText.length, 'preview=', preview);

  let embedding;
  try {
    console.log(LOG_PREFIX, 'Calling OpenAI embeddings API');
    const embeddings = new OpenAIEmbeddings({
      model: 'text-embedding-3-small',
      openAIApiKey: OPENAI_API_KEY,
      dimensions: EMBEDDING_DIMENSION,
    });
    const vectors = await embeddings.embedDocuments([searchableText]);
    embedding = vectors[0];
    if (!Array.isArray(embedding) || embedding.length !== EMBEDDING_DIMENSION) {
      console.error(LOG_PREFIX, 'Unexpected embedding length:', embedding?.length, 'expected', EMBEDDING_DIMENSION);
      return;
    }
    console.log(LOG_PREFIX, 'OpenAI embeddings API success, dimension=', embedding.length);
  } catch (err) {
    console.error(LOG_PREFIX, 'Error calling OpenAI:', err.message, err.stack);
    return;
  }

  const vectorStr = '[' + embedding.join(',') + ']';

  try {
    const deleteResult = await prisma.$executeRaw`
      DELETE FROM job_embeddings WHERE job_id = ${jobId}
    `;
    if (deleteResult > 0) {
      console.log(LOG_PREFIX, 'Replaced existing embedding for jobId=', jobId, 'deleted rows=', deleteResult);
    }

    await prisma.$executeRaw`
      INSERT INTO job_embeddings (embedding_id, job_id, embedding, searchable_text, updated_at)
      VALUES (gen_random_uuid(), ${jobId}, ${vectorStr}::vector, ${searchableText}, now())
    `;
    console.log(LOG_PREFIX, 'Inserted embedding for jobId=', jobId);
  } catch (rawErr) {
    console.error(LOG_PREFIX, 'Error in raw SQL:', rawErr.message, rawErr.stack);
  }
}

/**
 * Semantic search over job embeddings: embed the query and return top jobs by cosine similarity.
 * Only returns open jobs. Same embedding model/dimension as upsert.
 *
 * @param {string} queryText - Search query (e.g. seller profile–based query)
 * @param {number} [limit=40] - Max number of jobs to return
 * @returns {Promise<Array<{ job_id: string, searchable_text: string, distance?: number }>>}
 */
export async function searchJobsByQuery(queryText, limit = 40) {
  const cap = Math.min(Math.max(Number(limit) || 40, 1), 100);

  console.log('\n' + '='.repeat(60));
  console.log(LOG_PREFIX + ' Semantic search: query and retrieval');
  console.log('='.repeat(60));
  console.log('\n  Query used for search:');
  console.log('  ' + '-'.repeat(56));
  console.log('  ' + (queryText || '(empty)'));
  console.log('  ' + '-'.repeat(56) + '\n');

  const vector = await embedQueryForJob(queryText || '');
  if (!vector) {
    console.warn(LOG_PREFIX, 'searchJobsByQuery: no query embedding, returning []');
    console.log('='.repeat(60) + '\n');
    return [];
  }

  const vectorStr = '[' + vector.join(',') + ']';
  try {
    const rows = await prisma.$queryRaw`
      SELECT e.job_id AS "job_id", e.searchable_text AS "searchable_text",
             (e.embedding <=> ${vectorStr}::vector) AS distance
      FROM job_embeddings e
      INNER JOIN job_listings j ON j.job_id = e.job_id AND j.status = 'open'
      ORDER BY e.embedding <=> ${vectorStr}::vector
      LIMIT ${cap}
    `;
    const list = Array.isArray(rows) ? rows : [];

    console.log('  Retrieved (top ' + list.length + ' by cosine similarity):');
    list.forEach((r, i) => {
      const jid = r?.job_id ?? '';
      const text = (r?.searchable_text ?? '').slice(0, 120);
      const dist = r?.distance != null ? Number(r.distance).toFixed(4) : '—';
      console.log('  [' + (i + 1) + '] job_id: ' + jid + ' distance: ' + dist);
    });
    console.log('='.repeat(60) + '\n');

    return list.map((r) => ({
      job_id: r?.job_id ?? '',
      searchable_text: r?.searchable_text ?? '',
      distance: r?.distance != null ? Number(r.distance) : undefined,
    }));
  } catch (err) {
    console.error(LOG_PREFIX, 'searchJobsByQuery error:', err.message, err.stack);
    console.log('='.repeat(60) + '\n');
    return [];
  }
}
