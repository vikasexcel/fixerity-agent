/**
 * Seller embedding service — Upwork-style semantic matching.
 *
 * Key improvements over v1:
 *  1. buildSearchableText written in buyer-facing language so job queries
 *     and seller profiles share the same semantic space.
 *  2. Case-normalised category names on every insert AND every query.
 *  3. Handles all known credential/pricing/availability field shapes from DB.
 *  4. similarity_score (1 - cosine distance) exposed for downstream use.
 *  5. Pass-2 fallback: if strict category filter returns 0, widen to all
 *     active sellers so the reranker can still operate.
 *  6. backfillSellerEmbeddings() utility for one-time migration.
 */

import 'dotenv/config';
import { OpenAIEmbeddings } from '@langchain/openai';
import prisma from '../prisma/client.js';

const LOG_PREFIX = '[SellerEmbedding]';
const EMBEDDING_DIMENSION = 1536;
const OPENAI_API_KEY = process.env.OPENAI_API_KEY;

/* ─────────────────────────────────────────────────────────────
   HELPERS
───────────────────────────────────────────────────────────── */

function normaliseCategory(name) {
  return String(name || '').toLowerCase().trim();
}

/* ─────────────────────────────────────────────────────────────
   SEARCHABLE TEXT BUILDER
   
   Written in buyer-facing language — "I need someone who does X in Y"
   should match this text well. This bridges the semantic gap between
   buyer job queries and provider profile embeddings.
───────────────────────────────────────────────────────────── */

export function buildSearchableText(profile) {
  const parts = [];

  // ── 1. Services (strongest signal) ───────────────────────────────────────
  const services = Array.isArray(profile.serviceCategoryNames)
    ? profile.serviceCategoryNames.filter(Boolean)
    : [];
  if (services.length > 0) {
    parts.push(`This provider offers: ${services.join(', ')}.`);
    parts.push(`Available for ${services.join(' and ')} jobs.`);
  }

  // ── 2. Service area ───────────────────────────────────────────────────────
  const area = profile.serviceArea;
  if (area && typeof area === 'object') {
    const loc = [];
    if (area.location) loc.push(String(area.location));
    if (area.city)     loc.push(String(area.city));
    if (area.state)    loc.push(String(area.state));
    if (area.address)  loc.push(String(area.address));
    if (area.zip_codes && Array.isArray(area.zip_codes)) {
      loc.push(`zip codes: ${area.zip_codes.join(', ')}`);
    }
    if (area.radius_miles) loc.push(`within ${area.radius_miles} miles`);
    if (loc.length > 0) parts.push(`Serves: ${loc.join(', ')}.`);
  } else if (typeof area === 'string' && area.trim()) {
    parts.push(`Serves: ${area.trim()}.`);
  }

  // ── 3. Bio ────────────────────────────────────────────────────────────────
  if (profile.bio && String(profile.bio).trim()) {
    parts.push(String(profile.bio).trim());
  }

  // ── 4. Credentials — handle all known field shapes ────────────────────────
  const cred = profile.credentials;
  if (cred && typeof cred === 'object') {
    const cp = [];
    if (cred.licensed === true)             cp.push('licensed and insured');
    if (cred.license && String(cred.license).trim()) cp.push(String(cred.license).trim());
    if (cred.references_available === true)  cp.push('references available');
    if (cred.background_check === true)      cp.push('background checked');
    if (cred.years_experience != null)       cp.push(`${cred.years_experience} years of experience`);
    if (cred.certifications && Array.isArray(cred.certifications)) {
      cp.push(`certified in: ${cred.certifications.join(', ')}`);
    }
    if (cp.length > 0) parts.push(`Credentials: ${cp.join(', ')}.`);
  }

  // ── 5. Pricing — handle all known field shapes ────────────────────────────
  const pricing = profile.pricing;
  if (pricing && typeof pricing === 'object') {
    const pp = [];
    // Shape 1: { hourly_rate: 60 }
    if (pricing.hourly_rate != null) {
      pp.push(`hourly rate $${pricing.hourly_rate}`);
    }
    // Shape 2: { hourly_rate_min, hourly_rate_max }
    if (pricing.hourly_rate_min != null && pricing.hourly_rate_max != null) {
      pp.push(`hourly rate $${pricing.hourly_rate_min}–$${pricing.hourly_rate_max}`);
    } else if (pricing.hourly_rate_min != null) {
      pp.push(`from $${pricing.hourly_rate_min} per hour`);
    }
    // Shape 3: { fixed_prices: {...} }
    if (pricing.fixed_prices && typeof pricing.fixed_prices === 'object') {
      const fixed = Object.entries(pricing.fixed_prices)
        .map(([k, v]) => `${k}: $${v}`)
        .join(', ');
      if (fixed) pp.push(`fixed prices — ${fixed}`);
    }
    if (pricing.minimum_job != null) pp.push(`minimum job $${pricing.minimum_job}`);
    if (pp.length > 0) parts.push(`Pricing: ${pp.join('; ')}.`);
  }

  // ── 6. Availability — handle all known field shapes ───────────────────────
  const avail = profile.availability;
  if (avail && typeof avail === 'object') {
    const ap = [];
    if (avail.schedule) ap.push(String(avail.schedule));
    // Shape: { weekdays: "8 AM - 6 PM", weekends: "not available" }
    if (avail.weekdays && String(avail.weekdays).trim()) {
      ap.push(`weekdays: ${String(avail.weekdays).trim()}`);
    }
    if (avail.weekends && String(avail.weekends).trim()) {
      const wknd = String(avail.weekends).toLowerCase().trim();
      if (wknd !== 'not available' && wknd !== 'unavailable' && wknd !== 'no') {
        ap.push(`weekends: ${String(avail.weekends).trim()}`);
      }
    }
    // Shape: boolean flags
    if (avail.weekday_mornings) ap.push('weekday mornings');
    if (avail.weekday_evenings) ap.push('weekday evenings');
    if (avail.same_day)         ap.push('same-day available');
    if (avail.emergency)        ap.push('emergency/urgent jobs');
    if (ap.length > 0) parts.push(`Available: ${ap.join(', ')}.`);
  }

  // ── 7. Track record ───────────────────────────────────────────────────────
  if (profile.totalBidsAccepted && profile.totalBidsAccepted > 0) {
    parts.push(`Completed ${profile.totalBidsAccepted} jobs successfully.`);
  }

  // ── 8. Preferences ────────────────────────────────────────────────────────
  const prefs = profile.preferences;
  if (prefs && typeof prefs === 'object') {
    const rfp = [];
    if (prefs.job_types && Array.isArray(prefs.job_types)) {
      rfp.push(`specialises in: ${prefs.job_types.join(', ')}`);
    }
    if (prefs.min_budget != null) rfp.push(`minimum budget $${prefs.min_budget}`);
    if (prefs.max_budget != null) rfp.push(`maximum budget $${prefs.max_budget}`);
    if (rfp.length > 0) parts.push(`Preferences: ${rfp.join('; ')}.`);
  }

  return parts.join(' ').trim() || 'No profile content available.';
}

/* ─────────────────────────────────────────────────────────────
   EMBED
───────────────────────────────────────────────────────────── */

async function embedText(text) {
  if (!OPENAI_API_KEY || !OPENAI_API_KEY.trim() || !text || !String(text).trim()) {
    return null;
  }
  try {
    const client = new OpenAIEmbeddings({
      model: 'text-embedding-3-small',
      openAIApiKey: OPENAI_API_KEY,
      dimensions: EMBEDDING_DIMENSION,
    });
    const vectors = await client.embedDocuments([String(text).trim()]);
    const vec = vectors[0];
    return Array.isArray(vec) && vec.length === EMBEDDING_DIMENSION ? vec : null;
  } catch (err) {
    console.error(LOG_PREFIX, 'embedText error:', err.message);
    return null;
  }
}

/* ─────────────────────────────────────────────────────────────
   UPSERT
───────────────────────────────────────────────────────────── */

export async function upsertSellerEmbedding(sellerId, profile) {
  if (!sellerId || !profile) {
    console.warn(LOG_PREFIX, 'Missing sellerId or profile, skipping');
    return;
  }

  if (profile.embeddingDone === true) {
    console.log(LOG_PREFIX, `Already embedded (embeddingDone=true), skipping sellerId=${sellerId}`);
    return;
  }

  console.log(LOG_PREFIX, `Starting embedding for sellerId=${sellerId}`);

  if (!OPENAI_API_KEY || !OPENAI_API_KEY.trim()) {
    console.warn(LOG_PREFIX, 'OPENAI_API_KEY missing, skipping');
    return;
  }

  // Normalise category names BEFORE building text
  const normalisedProfile = {
    ...profile,
    serviceCategoryNames: Array.isArray(profile.serviceCategoryNames)
      ? profile.serviceCategoryNames.map(normaliseCategory).filter(Boolean)
      : [],
  };

  const searchableText = buildSearchableText(normalisedProfile);
  console.log(LOG_PREFIX, `searchableText length=${searchableText.length}, preview="${searchableText.slice(0, 120)}..."`);

  const embedding = await embedText(searchableText);
  if (!embedding) {
    console.error(LOG_PREFIX, 'Failed to generate embedding');
    return;
  }

  const vectorStr = '[' + embedding.join(',') + ']';

  try {
    await prisma.$executeRaw`
      DELETE FROM seller_embeddings WHERE seller_id = ${sellerId}
    `;

    await prisma.$executeRaw`
      INSERT INTO seller_embeddings (id, seller_id, embedding, searchable_text, updated_at)
      VALUES (gen_random_uuid(), ${sellerId}, ${vectorStr}::vector, ${searchableText}, now())
    `;

    // Persist normalised category names and mark embedding done
    await prisma.sellerProfile.update({
      where: { id: sellerId },
      data: {
        embeddingDone:        true,
        serviceCategoryNames: normalisedProfile.serviceCategoryNames,
      },
    });

    console.log(LOG_PREFIX, `✅ Embedding upserted for sellerId=${sellerId}`);
  } catch (err) {
    console.error(LOG_PREFIX, 'DB error during upsert:', err.message, err.stack);
  }
}

/* ─────────────────────────────────────────────────────────────
   SEARCH
───────────────────────────────────────────────────────────── */

export async function searchSellersByQuery(queryText, limit = 40, serviceCategoryFilter = null) {
  const cap = Math.min(Math.max(Number(limit) || 40, 1), 100);

  const categoryFilter = serviceCategoryFilter
    ? normaliseCategory(serviceCategoryFilter)
    : null;

  console.log('\n' + '='.repeat(60));
  console.log(`${LOG_PREFIX} Semantic search: query and retrieval`);
  console.log('='.repeat(60));
  console.log(`\n  Query: ${(queryText || '').slice(0, 200)}`);
  if (categoryFilter) console.log(`  Category filter (normalised): "${categoryFilter}"`);
  console.log('');

  const vector = await embedText(queryText || '');
  if (!vector) {
    console.warn(LOG_PREFIX, 'Could not embed query, returning []');
    return [];
  }

  const vectorStr = '[' + vector.join(',') + ']';
  let rows = [];

  // ── Pass 1: strict category filter ────────────────────────────────────────
  try {
    if (categoryFilter) {
      rows = await prisma.$queryRaw`
        SELECT
          e.seller_id                                          AS "seller_id",
          e.searchable_text                                    AS "searchable_text",
          (e.embedding <=> ${vectorStr}::vector)               AS distance,
          (1 - (e.embedding <=> ${vectorStr}::vector))         AS similarity_score
        FROM seller_embeddings e
        INNER JOIN seller_profiles p
          ON p.seller_id = e.seller_id
         AND p.active = true
         AND EXISTS (
               SELECT 1 FROM unnest(p.service_category_names) AS cat
               WHERE lower(trim(cat)) = ${categoryFilter}
             )
        ORDER BY e.embedding <=> ${vectorStr}::vector
        LIMIT ${cap}
      `;
    } else {
      rows = await prisma.$queryRaw`
        SELECT
          e.seller_id                                          AS "seller_id",
          e.searchable_text                                    AS "searchable_text",
          (e.embedding <=> ${vectorStr}::vector)               AS distance,
          (1 - (e.embedding <=> ${vectorStr}::vector))         AS similarity_score
        FROM seller_embeddings e
        INNER JOIN seller_profiles p
          ON p.seller_id = e.seller_id
         AND p.active = true
        ORDER BY e.embedding <=> ${vectorStr}::vector
        LIMIT ${cap}
      `;
    }
  } catch (err) {
    console.error(LOG_PREFIX, 'Pass-1 query error:', err.message);
    rows = [];
  }

  console.log(`  Pass 1 (category filter "${categoryFilter}"): ${rows.length} results`);

  // ── Pass 2: widen if pass 1 returned nothing ──────────────────────────────
  if (rows.length === 0 && categoryFilter) {
    console.log('  ⚠️  0 results — widening to all active sellers...');
    try {
      rows = await prisma.$queryRaw`
        SELECT
          e.seller_id                                          AS "seller_id",
          e.searchable_text                                    AS "searchable_text",
          (e.embedding <=> ${vectorStr}::vector)               AS distance,
          (1 - (e.embedding <=> ${vectorStr}::vector))         AS similarity_score
        FROM seller_embeddings e
        INNER JOIN seller_profiles p
          ON p.seller_id = e.seller_id
         AND p.active = true
        ORDER BY e.embedding <=> ${vectorStr}::vector
        LIMIT ${cap}
      `;
      console.log(`  Pass 2 (widened): ${rows.length} results`);
    } catch (err) {
      console.error(LOG_PREFIX, 'Pass-2 query error:', err.message);
      rows = [];
    }
  }

  const list = Array.isArray(rows) ? rows : [];
  console.log('\n  Retrieved (top ' + list.length + ' by cosine similarity):');
  list.slice(0, 10).forEach((r, i) => {
    const score = r?.similarity_score != null
      ? (Number(r.similarity_score) * 100).toFixed(2) : '—';
    console.log(`  [${i + 1}] seller_id: ${r?.seller_id}  similarity: ${score}%`);
    console.log(`       ${(r?.searchable_text ?? '').slice(0, 100)}...`);
  });
  if (list.length > 10) console.log(`  ... and ${list.length - 10} more`);
  console.log('  Total retrieved: ' + list.length + ' sellers');
  console.log('='.repeat(60) + '\n');

  return list.map((r) => ({
    seller_id:        r?.seller_id ?? '',
    searchable_text:  r?.searchable_text ?? '',
    distance:         r?.distance != null ? Number(r.distance) : undefined,
    similarity_score: r?.similarity_score != null ? Number(r.similarity_score) : undefined,
  }));
}

/* ─────────────────────────────────────────────────────────────
   BACKFILL UTILITY
───────────────────────────────────────────────────────────── */

export async function backfillSellerEmbeddings(batchSize = 50) {
  console.log(LOG_PREFIX, 'Starting backfill...');
  let offset = 0, totalProcessed = 0;

  while (true) {
    const profiles = await prisma.sellerProfile.findMany({
      where: { embeddingDone: false, active: true },
      take: batchSize,
      skip: offset,
      orderBy: { createdAt: 'asc' },
    });

    if (profiles.length === 0) break;

    for (const profile of profiles) {
      await upsertSellerEmbedding(profile.id, profile);
      totalProcessed++;
    }

    offset += profiles.length;
    console.log(LOG_PREFIX, `Backfill: ${totalProcessed} processed`);
    await new Promise((r) => setTimeout(r, 200));
  }

  console.log(LOG_PREFIX, `✅ Backfill complete: ${totalProcessed} profiles`);
  return totalProcessed;
}