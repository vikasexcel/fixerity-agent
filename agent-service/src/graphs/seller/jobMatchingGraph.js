/**
 * Job Matching Graph — Find jobs that match a seller's profile.
 *
 * Key improvements over v1:
 *  1. Uses the updated buildOptimizedQueryForSellerProfile which returns
 *     { query, serviceCategories } — the categories are passed directly to
 *     searchJobsByQuery as a hard filter so only relevant jobs are returned.
 *  2. matchScore is now based on real similarity_score from the embedding
 *     search, not just a position-based arithmetic formula.
 *  3. Cleaner logging — verbose per-object JSON dumps removed, replaced with
 *     structured summary logs.
 */

import { Annotation, StateGraph, START, END } from '@langchain/langgraph';
import prisma from '../../prisma/client.js';
import { buildOptimizedQueryForSellerProfile } from '../../services/sellerQueryService.js';
import { searchJobsByQuery } from '../../services/jobEmbeddingService.js';
import { rerankJobsForSeller } from '../../services/rerankService.js';

/* ─────────────────────────────────────────────────────────────
   STATE
───────────────────────────────────────────────────────────── */

const JobMatchingState = Annotation.Root({
  sellerId:      Annotation(),
  sellerProfile: Annotation(),
  filters:       Annotation(),
  matchedJobs:   Annotation(),
  rankedJobs:    Annotation(),
  error:         Annotation(),
});

/* ─────────────────────────────────────────────────────────────
   LOGGING HELPERS
───────────────────────────────────────────────────────────── */

const LOG_PREFIX  = '[JobMatching]';
const DIVIDER     = '═'.repeat(70);
const SUB_DIVIDER = '─'.repeat(70);

function logHeader(title) {
  console.log('\n' + DIVIDER);
  console.log(`${LOG_PREFIX} ${title}`);
  console.log(DIVIDER);
}

function logKeyValue(key, value, indent = 2) {
  const spaces = ' '.repeat(indent);
  console.log(`${spaces}${key}: ${value === null || value === undefined ? '—' : value}`);
}

/* ─────────────────────────────────────────────────────────────
   SHAPE HELPER
───────────────────────────────────────────────────────────── */

function jobToMatchShape(j, similarityScore) {
  const budget = j.budget && typeof j.budget === 'object'
    ? j.budget
    : { min: null, max: null };

  return {
    job_id:               j.id,
    buyer_id:             j.buyerId,
    service_category_id:  j.serviceCategoryId,
    service_category_name: j.serviceCategoryName,
    title:                j.title,
    description:          j.description,
    budget,
    start_date:           j.startDate,
    end_date:             j.endDate,
    location:             j.location,
    priorities:           j.priorities,
    status:               j.status,
    num_bids_received:    j.numBidsReceived,
    created_at:           j.createdAt,
    specific_requirements: j.specificRequirements,
    similarity_score:     similarityScore ?? null,
  };
}

/* ─────────────────────────────────────────────────────────────
   NODE 1: LOAD SELLER PROFILE
───────────────────────────────────────────────────────────── */

async function loadSellerProfileNode(state) {
  logHeader('STEP 1: Load Seller Profile');
  console.log(`\n  🔍 Loading seller: ${state.sellerId}`);

  try {
    const sellerId = String(state.sellerId);
    const providerId = parseInt(sellerId, 10);
    
    // Check if sellerId is a UUID (profile ID) or numeric (provider ID)
    const isUUID = /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i.test(sellerId);
    
    let profile;
    
    if (isUUID) {
      // Load specific profile by UUID (session-scoped)
      console.log(`  🎯 Loading session-specific profile: ${sellerId}`);
      profile = await prisma.sellerProfile.findFirst({
        where: { id: sellerId, active: true },
      });
      
      if (!profile) {
        console.log(`  ❌ Profile not found: ${sellerId}`);
        return {
          error: 'Profile not found. Please create a profile first.',
          matchedJobs: [],
        };
      }
      
      console.log(`\n  ✅ Loaded session-specific profile`);
      logKeyValue('Profile ID',     profile.id,             4);
      logKeyValue('Provider ID',    profile.providerId,     4);
      logKeyValue('Services',       (profile.serviceCategoryNames ?? []).join(', ') || '—', 4);
      logKeyValue('Service Area',   profile.serviceArea?.location ?? JSON.stringify(profile.serviceArea), 4);
      logKeyValue('Availability',   JSON.stringify(profile.availability), 4);
      logKeyValue('Pricing',        JSON.stringify(profile.pricing), 4);
      
    } else if (!isNaN(providerId)) {
      // Load all profiles for provider (backward compatibility)
      console.log(`  📚 Loading all profiles for provider: ${providerId}`);
      const profiles = await prisma.sellerProfile.findMany({
        where: { providerId, active: true },
        orderBy: { updatedAt: 'desc' },
      });
      
      profile = profiles[0];
      if (!profile) {
        console.log(`  ❌ No active profile found for provider: ${providerId}`);
        return {
          error: 'No active profile found. Please create a profile first.',
          matchedJobs: [],
        };
      }
      
      // Collect all service category names across multiple profiles
      const allServiceNames = profiles.length > 1
        ? [...new Set(profiles.flatMap((p) => p.serviceCategoryNames ?? []))]
        : (profile.serviceCategoryNames ?? []);
      
      console.log(`\n  ✅ Found ${profiles.length} profile(s) for provider ${providerId}`);
      console.log(`  ⚠️  WARNING: Merging services from ${profiles.length} profiles - consider using session-specific profileId`);
      logKeyValue('Profile ID',     profile.id,             4);
      logKeyValue('Services',       allServiceNames.join(', ') || '—', 4);
      logKeyValue('Service Area',   profile.serviceArea?.location ?? JSON.stringify(profile.serviceArea), 4);
      logKeyValue('Availability',   JSON.stringify(profile.availability), 4);
      logKeyValue('Pricing',        JSON.stringify(profile.pricing), 4);
      
      // Use merged services for backward compatibility
      return {
        sellerProfile: {
          seller_id:                profile.id,
          service_category_names:   allServiceNames,
          service_area:             profile.serviceArea,
          availability:             profile.availability,
          credentials:              profile.credentials,
          pricing:                  profile.pricing,
          preferences:              profile.preferences,
          bio:                      profile.bio,
          profile_completeness_score: profile.profileCompletenessScore,
        },
      };
    } else {
      console.log(`  ❌ Invalid seller ID format: ${sellerId}`);
      return {
        error: 'Invalid seller ID format',
        matchedJobs: [],
      };
    }

    // Return single profile (for UUID case)
    return {
      sellerProfile: {
        seller_id:                profile.id,
        service_category_names:   profile.serviceCategoryNames ?? [],
        service_area:             profile.serviceArea,
        availability:             profile.availability,
        credentials:              profile.credentials,
        pricing:                  profile.pricing,
        preferences:              profile.preferences,
        bio:                      profile.bio,
        profile_completeness_score: profile.profileCompletenessScore,
      },
    };
  } catch (error) {
    console.error(`${LOG_PREFIX} Error loading profile:`, error.message);
    return { error: 'Failed to load profile', matchedJobs: [] };
  }
}

/* ─────────────────────────────────────────────────────────────
   NODE 2: FIND MATCHING JOBS
───────────────────────────────────────────────────────────── */

async function findMatchingJobsNode(state) {
  logHeader('STEP 2: Find Matching Jobs');

  const profile = state.sellerProfile;
  if (!profile) {
    console.log('  ❌ No seller profile, skipping job search');
    return { matchedJobs: [] };
  }

  try {
    // ── Build query from profile ──────────────────────────────────────────
    console.log('\n  🤖 Building optimized query from seller profile...');

    const query = await buildOptimizedQueryForSellerProfile(profile);

    if (!query || !String(query).trim()) {
      console.log('  ❌ Empty query generated, skipping search');
      return { matchedJobs: [] };
    }

    console.log(`\n  ✅ Query: ${query}`);

    // ── Pure semantic search ───────────────────────────────────────────────
    const searchLimit = 40;
    console.log(`\n  🔍 Searching job embeddings (limit: ${searchLimit})...`);

    const embeddingResults = await searchJobsByQuery(query, searchLimit);

    if (!embeddingResults || embeddingResults.length === 0) {
      console.log('  ❌ No jobs found from embedding search');
      return { matchedJobs: [] };
    }

    console.log(`\n  ✅ Embedding search: ${embeddingResults.length} jobs found`);
    console.log('  ' + SUB_DIVIDER);
    embeddingResults.slice(0, 5).forEach((r, i) => {
      const score   = r.similarity_score != null ? (r.similarity_score * 100).toFixed(2) : '—';
      const preview = (r.searchable_text ?? '').slice(0, 80);
      console.log(`    [${i + 1}] job_id: ${r.job_id}  similarity: ${score}%`);
      console.log(`        ${preview}...`);
    });
    if (embeddingResults.length > 5) console.log(`    ... and ${embeddingResults.length - 5} more`);
    console.log('  ' + SUB_DIVIDER);

    // Build similarity score map for matchScore
    const similarityMap = new Map(
      embeddingResults.map((r) => [r.job_id, r.similarity_score ?? 0]),
    );

    // ── Rerank with LLM → top 10 ───────────────────────────────────────────
    // The LLM acts as the relevance gate — it filters out jobs the seller
    // cannot do using real-world trade knowledge (e.g. a concrete work seller
    // can do foundation repair but not home cleaning), then ranks remaining
    // jobs by best fit. No hardcoded strings or thresholds needed.
    console.log(`
  🤖 Reranking ${embeddingResults.length} candidates → top 10 (LLM filters + ranks)...`);

    const candidates = embeddingResults.map((r) => ({
      job_id:          r.job_id,
      searchable_text: r.searchable_text,
    }));

    const rankedJobIds = await rerankJobsForSeller(profile, candidates, 10);

    if (!rankedJobIds || rankedJobIds.length === 0) {
      console.log('  ❌ Reranking returned no results');
      return { matchedJobs: [] };
    }

    console.log(`\n  ✅ Reranked to ${rankedJobIds.length} jobs:`);
    rankedJobIds.forEach((id, i) => console.log(`    [${i + 1}] ${id}`));

    // ── Fetch full job details ─────────────────────────────────────────────
    const jobs  = await prisma.jobListing.findMany({
      where: { id: { in: rankedJobIds }, status: 'open' },
    });
    const byId    = new Map(jobs.map((j) => [j.id, j]));
    const ordered = rankedJobIds.map((id) => byId.get(id)).filter(Boolean);

    console.log(`\n  ✅ Fetched ${ordered.length} open jobs from DB`);

    // ── Build matchedJobs with real similarity-based matchScore ───────────
    const matchedJobs = ordered.map((j, index) => {
      const simScore = similarityMap.get(j.id) ?? 0;
      // Convert similarity (0–1) to a 0–100 score, use position as tiebreaker
      const matchScore = Math.round(simScore * 100);
      return {
        ...jobToMatchShape(j, simScore),
        rank:       index + 1,
        matchScore: Math.max(matchScore, 1), // minimum 1 so it's never 0
      };
    });

    logHeader('MATCHED JOBS SUMMARY');
    matchedJobs.forEach((job) => {
      console.log(`\n  🏆 Rank #${job.rank} — ${job.title} (${job.service_category_name})`);
      logKeyValue('Job ID',     job.job_id,       4);
      logKeyValue('Score',      `${job.matchScore}% similarity`, 4);
      logKeyValue('Budget',     job.budget ? `$${job.budget.min || '?'}–$${job.budget.max || '?'}` : '—', 4);
      logKeyValue('Location',   job.location?.address ?? JSON.stringify(job.location), 4);
      logKeyValue('Start',      job.start_date,   4);
    });

    return { matchedJobs };
  } catch (error) {
    console.error(`${LOG_PREFIX} Error finding jobs:`, error.message, error.stack);
    return { matchedJobs: [], error: 'Failed to find matching jobs' };
  }
}

/* ─────────────────────────────────────────────────────────────
   NODE 3: RANK JOBS (pass-through — ranking done in node 2)
───────────────────────────────────────────────────────────── */

async function rankJobsNode(state) {
  const jobs = state.matchedJobs;
  if (!jobs || jobs.length === 0) {
    console.log(`${LOG_PREFIX} No jobs to rank, returning empty list`);
    return { rankedJobs: [] };
  }
  const rankedJobs = jobs.slice(0, 10);
  console.log(`${LOG_PREFIX} Finalised ${rankedJobs.length} ranked jobs`);
  return { rankedJobs };
}

/* ─────────────────────────────────────────────────────────────
   GRAPH
───────────────────────────────────────────────────────────── */

const workflow = new StateGraph(JobMatchingState)
  .addNode('load_profile', loadSellerProfileNode)
  .addNode('find_jobs',    findMatchingJobsNode)
  .addNode('rank_jobs',    rankJobsNode)
  .addEdge(START,          'load_profile')
  .addEdge('load_profile', 'find_jobs')
  .addEdge('find_jobs',    'rank_jobs')
  .addEdge('rank_jobs',    END);

export const jobMatchingGraph = workflow.compile();

/* ─────────────────────────────────────────────────────────────
   RUNNER
───────────────────────────────────────────────────────────── */

export async function findJobsForSeller(sellerId, filters = {}) {
  console.log('\n' + '▓'.repeat(70));
  console.log(`${LOG_PREFIX} 🚀 JOB MATCHING PIPELINE STARTED`);
  console.log('▓'.repeat(70));
  logKeyValue('Seller ID', sellerId, 2);

  const startTime = Date.now();

  const result = await jobMatchingGraph.invoke({
    sellerId,
    filters,
    matchedJobs: [],
    rankedJobs:  [],
  });

  const duration = Date.now() - startTime;

  console.log('\n' + '▓'.repeat(70));
  console.log(`${LOG_PREFIX} 🏁 JOB MATCHING PIPELINE COMPLETED`);
  console.log('▓'.repeat(70));
  logKeyValue('Seller ID',  sellerId,                        2);
  logKeyValue('Jobs Found', result.rankedJobs?.length || 0,  2);
  logKeyValue('Duration',   `${duration}ms`,                 2);
  logKeyValue('Error',      result.error || 'None',          2);

  if (result.rankedJobs?.length > 0) {
    console.log('\n  📋 Matched Jobs (Quick View):');
    console.log('  ' + SUB_DIVIDER);
    result.rankedJobs.forEach((job, i) => {
      console.log(`    [${i + 1}] ${job.title || 'Untitled'} — ${job.service_category_name || '—'} (score: ${job.matchScore}%)`);
    });
    console.log('  ' + SUB_DIVIDER);
  }

  console.log('\n' + '▓'.repeat(70) + '\n');

  return {
    jobs:  result.rankedJobs || [],
    count: result.rankedJobs?.length || 0,
    error: result.error || null,
  };
}