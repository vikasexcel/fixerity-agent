/**
 * Seller embedding service.
 * When a seller profile exists in the DB (created or updated), we generate an embedding
 * from the profile content using OpenAI and upsert it into SellerEmbedding for semantic matching.
 */
import 'dotenv/config';

import { OpenAIEmbeddings } from '@langchain/openai';

import prisma from '../prisma/client.js';

const LOG_PREFIX = '[SellerEmbedding]';
const EMBEDDING_DIMENSION = 1536;
const OPENAI_API_KEY = process.env.OPENAI_API_KEY;

/**
 * Get embedding vector for a single query string (same model/dimension as seller embeddings).
 * @param {string} text - Query or text to embed
 * @returns {Promise<number[]|null>} 1536-dim vector or null on error
 */
async function embedQuery(text) {
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
 * Build searchable text from a seller profile (Prisma model or plain object with same shape).
 * Used for both embedding and searchable_text storage.
 * @param {object} profile - Seller profile (id, bio, serviceCategoryNames, serviceArea, availability, pricing, credentials, etc.)
 * @returns {string}
 */
function buildSearchableText(profile) {
  const parts = [];

  if (profile.bio && String(profile.bio).trim()) {
    parts.push(String(profile.bio).trim());
  }
  if (Array.isArray(profile.serviceCategoryNames) && profile.serviceCategoryNames.length > 0) {
    parts.push('Services: ' + profile.serviceCategoryNames.join(', '));
  }
  if (profile.serviceArea && typeof profile.serviceArea === 'object' && profile.serviceArea.location) {
    parts.push('Area: ' + String(profile.serviceArea.location));
  }
  if (profile.availability) {
    const a = profile.availability;
    if (a.schedule) parts.push('Availability: ' + String(a.schedule));
    else if (a.weekday_evenings || a.weekends) {
      const opts = [];
      if (a.weekday_evenings) opts.push('weekday evenings');
      if (a.weekends) opts.push('weekends');
      if (opts.length) parts.push('Availability: ' + opts.join(', '));
    }
  }
  if (profile.pricing && typeof profile.pricing === 'object') {
    const p = profile.pricing;
    if (p.hourly_rate_min != null || p.hourly_rate_max != null) {
      parts.push('Pricing: $' + (p.hourly_rate_min ?? '?') + '-' + (p.hourly_rate_max ?? '?') + ' per hour');
    }
    if (p.fixed_prices && Object.keys(p.fixed_prices).length > 0) {
      parts.push('Fixed: ' + Object.entries(p.fixed_prices).map(([k, v]) => `${k} $${v}`).join(', '));
    }
  }
  if (profile.credentials && typeof profile.credentials === 'object') {
    const c = profile.credentials;
    const credParts = [];
    if (c.licensed === true) credParts.push('licensed');
    if (c.references_available === true) credParts.push('references available');
    if (c.years_experience != null) credParts.push(c.years_experience + ' years experience');
    if (credParts.length) parts.push('Credentials: ' + credParts.join(', '));
  }

  const text = parts.join(' | ').trim();
  return text || 'No profile content';
}

/**
 * Generate embedding for a seller profile and store in seller_embeddings.
 * Only runs when profile.embeddingDone is false. One embedding row per profile (seller_id = profile id).
 * Replaces this profile's embedding row if it exists (delete then insert for this seller_id only).
 * After success, sets profile.embeddingDone = true in DB.
 * Logs each step with [SellerEmbedding] prefix.
 *
 * @param {string} sellerId - Seller profile ID (UUID)
 * @param {object} profile - Seller profile object (Prisma model or same shape, incl. embeddingDone)
 * @returns {Promise<void>}
 */
export async function upsertSellerEmbedding(sellerId, profile) {
  if (!sellerId || !profile) {
    console.warn(LOG_PREFIX, 'Missing sellerId or profile, skipping');
    return;
  }

  if (profile.embeddingDone === true) {
    console.log(LOG_PREFIX, 'Profile already embedded (embeddingDone=true), skipping sellerId=', sellerId);
    return;
  }

  console.log(LOG_PREFIX, 'Starting embedding for sellerId=', sellerId, '(embeddingDone=false)');

  if (!OPENAI_API_KEY || OPENAI_API_KEY.trim() === '') {
    console.warn(LOG_PREFIX, 'OPENAI_API_KEY is missing, skipping embedding generation');
    return;
  }

  const searchableText = buildSearchableText(profile);
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
      DELETE FROM seller_embeddings WHERE seller_id = ${sellerId}
    `;
    console.log(LOG_PREFIX, 'Replaced existing embedding for this profile, sellerId=', sellerId, 'deleted rows=', deleteResult);

    await prisma.$executeRaw`
      INSERT INTO seller_embeddings (id, seller_id, embedding, searchable_text, updated_at)
      VALUES (gen_random_uuid(), ${sellerId}, ${vectorStr}::vector, ${searchableText}, now())
    `;
    console.log(LOG_PREFIX, 'Inserted embedding for sellerId=', sellerId);

    await prisma.sellerProfile.update({
      where: { id: sellerId },
      data: { embeddingDone: true },
    });
    console.log(LOG_PREFIX, 'Set embeddingDone=true for sellerId=', sellerId);
  } catch (rawErr) {
    console.error(LOG_PREFIX, 'Error in raw SQL or profile update:', rawErr.message, rawErr.stack);
  }
}

/**
 * Semantic search over seller embeddings: embed the query and return top sellers by cosine similarity.
 * Joins to seller_profiles and filters active = true. Same embedding model/dimension as upsert.
 *
 * @param {string} queryText - Search query (e.g. LLM-optimized job query)
 * @param {number} [limit=40] - Max number of sellers to return
 * @returns {Promise<Array<{ seller_id: string, searchable_text: string, distance?: number }>>}
 */
export async function searchSellersByQuery(queryText, limit = 40) {
  const cap = Math.min(Math.max(Number(limit) || 40, 1), 100);

  console.log('\n' + '='.repeat(60));
  console.log('[SellerEmbedding] Semantic search: query and retrieval');
  console.log('='.repeat(60));
  console.log('\n  Query used for search:');
  console.log('  ' + '-'.repeat(56));
  console.log('  ' + (queryText || '(empty)'));
  console.log('  ' + '-'.repeat(56) + '\n');

  const vector = await embedQuery(queryText || '');
  if (!vector) {
    console.warn(LOG_PREFIX, 'searchSellersByQuery: no query embedding, returning []');
    console.log('='.repeat(60) + '\n');
    return [];
  }

  const vectorStr = '[' + vector.join(',') + ']';
  try {
    const rows = await prisma.$queryRaw`
      SELECT e.seller_id AS "seller_id", e.searchable_text AS "searchable_text",
             (e.embedding <=> ${vectorStr}::vector) AS distance
      FROM seller_embeddings e
      INNER JOIN seller_profiles p ON p.seller_id = e.seller_id AND p.active = true
      ORDER BY e.embedding <=> ${vectorStr}::vector
      LIMIT ${cap}
    `;
    const list = Array.isArray(rows) ? rows : [];

    console.log('  Retrieved (top ' + list.length + ' by cosine similarity):');
    console.log('  ' + '-'.repeat(56));
    list.forEach((r, i) => {
      const sid = r?.seller_id ?? '';
      const text = (r?.searchable_text ?? '').slice(0, 200);
      const dist = r?.distance != null ? Number(r.distance).toFixed(4) : '—';
      console.log('  [' + (i + 1) + '] seller_id: ' + sid);
      console.log('      distance: ' + dist);
      console.log('      searchable_text: ' + (text || '(none)') + (text.length >= 200 ? '...' : ''));
      console.log('');
    });
    console.log('  ' + '-'.repeat(56));
    console.log('  Total retrieved: ' + list.length + ' sellers');
    console.log('='.repeat(60) + '\n');

    return list.map((r) => ({
      seller_id: r?.seller_id ?? '',
      searchable_text: r?.searchable_text ?? '',
      distance: r?.distance != null ? Number(r.distance) : undefined,
    }));
  } catch (err) {
    console.error(LOG_PREFIX, 'searchSellersByQuery error:', err.message, err.stack);
    console.log('='.repeat(60) + '\n');
    return [];
  }
}
