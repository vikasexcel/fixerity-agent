import { Annotation, StateGraph, START, END } from '@langchain/langgraph';
import prisma from '../../prisma/client.js';
import { buildOptimizedQueryForSellerProfile } from '../../services/sellerQueryService.js';
import { searchJobsByQuery } from '../../services/jobEmbeddingService.js';
import { rerankJobsForSeller } from '../../services/rerankService.js';

/* ================================================================================
   JOB MATCHING GRAPH - Find Jobs That Match Seller Profile
   Flow: profile -> LLM query -> JobsEmbedding search -> rerank with LLM -> top 10 full job details
   ================================================================================ */

const JobMatchingState = Annotation.Root({
  sellerId: Annotation(),
  sellerProfile: Annotation(),
  filters: Annotation(),
  matchedJobs: Annotation(),
  rankedJobs: Annotation(),
  error: Annotation(),
});

/* -------------------- LOAD SELLER PROFILE NODE -------------------- */

async function loadSellerProfileNode(state) {
  try {
    const providerId = parseInt(String(state.sellerId), 10);
    const profiles = await prisma.sellerProfile.findMany({
      where: !isNaN(providerId)
        ? { providerId, active: true }
        : { id: state.sellerId, active: true },
      orderBy: { updatedAt: 'desc' },
    });

    const profile = profiles[0];
    if (!profile) {
      return {
        error: 'No active profile found. Please create a profile first.',
        matchedJobs: [],
      };
    }

    const allServiceNames = profiles.length > 1
      ? [...new Set(profiles.flatMap((p) => p.serviceCategoryNames ?? []))]
      : (profile.serviceCategoryNames ?? []);

    console.log(`[JobMatching] Loaded ${profiles.length} profile(s) for seller ${state.sellerId}`);

    return {
      sellerProfile: {
        seller_id: profile.id,
        service_category_names: allServiceNames,
        service_area: profile.serviceArea,
        availability: profile.availability,
        credentials: profile.credentials,
        pricing: profile.pricing,
        preferences: profile.preferences,
        bio: profile.bio,
        profile_completeness_score: profile.profileCompletenessScore,
      },
    };
  } catch (error) {
    console.error('[JobMatching] Error loading profile:', error.message);
    return {
      error: 'Failed to load profile',
      matchedJobs: [],
    };
  }
}

/* -------------------- FIND MATCHING JOBS NODE (semantic search via JobsEmbedding) -------------------- */

function jobToMatchShape(j) {
  const budget = j.budget && typeof j.budget === 'object' ? j.budget : { min: null, max: null };
  return {
    job_id: j.id,
    buyer_id: j.buyerId,
    service_category_id: j.serviceCategoryId,
    service_category_name: j.serviceCategoryName,
    title: j.title,
    description: j.description,
    budget,
    start_date: j.startDate,
    end_date: j.endDate,
    location: j.location,
    priorities: j.priorities,
    status: j.status,
    num_bids_received: j.numBidsReceived,
    created_at: j.createdAt,
    specific_requirements: j.specificRequirements,
  };
}

async function findMatchingJobsNode(state) {
  const profile = state.sellerProfile;

  if (!profile) {
    return { matchedJobs: [] };
  }

  try {
    // 1) Build search query from seller profile (LLM)
    const query = await buildOptimizedQueryForSellerProfile(profile);
    if (!query || !String(query).trim()) {
      console.log('[JobMatching] Empty query, skipping search');
      return { matchedJobs: [] };
    }

    // 2) Semantic search on JobsEmbedding (open jobs only)
    const searchLimit = 40;
    const embeddingResults = await searchJobsByQuery(query, searchLimit);
    if (!embeddingResults || embeddingResults.length === 0) {
      console.log('[JobMatching] No jobs from embedding search');
      return { matchedJobs: [] };
    }

    const jobIds = embeddingResults.map((r) => r.job_id).filter(Boolean);
    if (jobIds.length === 0) return { matchedJobs: [] };

    // 3) Rerank with LLM -> top 10 job_ids
    const candidates = embeddingResults.map((r) => ({
      job_id: r.job_id,
      searchable_text: r.searchable_text,
    }));
    const rankedJobIds = await rerankJobsForSeller(profile, candidates, 10);

    if (!rankedJobIds || rankedJobIds.length === 0) {
      return { matchedJobs: [] };
    }

    // 4) Fetch full job details for ranked IDs (preserve order)
    const jobs = await prisma.jobListing.findMany({
      where: { id: { in: rankedJobIds }, status: 'open' },
    });
    const byId = new Map(jobs.map((j) => [j.id, j]));
    const ordered = rankedJobIds.map((id) => byId.get(id)).filter(Boolean);

    // 5) Build matchedJobs with rank and matchScore (100 for #1, decreasing)
    const matchedJobs = ordered.map((j, index) => {
      const rank = index + 1;
      const matchScore = Math.max(10, 100 - (rank - 1) * 8); // 100, 92, 84, ...
      return {
        ...jobToMatchShape(j),
        rank,
        matchScore,
      };
    });

    console.log(`[JobMatching] Semantic search + rerank: ${embeddingResults.length} candidates -> top ${matchedJobs.length} jobs`);
    return { matchedJobs };
  } catch (error) {
    console.error('[JobMatching] Error finding jobs:', error.message);
    return {
      matchedJobs: [],
      error: 'Failed to find matching jobs',
    };
  }
}

/* -------------------- RANK JOBS NODE (pass-through; ranking done in find node) -------------------- */

async function rankJobsNode(state) {
  const jobs = state.matchedJobs;
  if (!jobs || jobs.length === 0) {
    return { rankedJobs: [] };
  }
  // Already ranked by rerankJobsForSeller; return top 10 as rankedJobs
  return { rankedJobs: jobs.slice(0, 10) };
}

/* -------------------- GRAPH DEFINITION -------------------- */

const workflow = new StateGraph(JobMatchingState)
  .addNode('load_profile', loadSellerProfileNode)
  .addNode('find_jobs', findMatchingJobsNode)
  .addNode('rank_jobs', rankJobsNode)
  
  .addEdge(START, 'load_profile')
  .addEdge('load_profile', 'find_jobs')
  .addEdge('find_jobs', 'rank_jobs')
  .addEdge('rank_jobs', END);

export const jobMatchingGraph = workflow.compile();

/* -------------------- RUNNER FUNCTION -------------------- */

export async function findJobsForSeller(sellerId, filters = {}) {
  const initialState = {
    sellerId,
    filters,
    matchedJobs: [],
    rankedJobs: [],
  };

  const result = await jobMatchingGraph.invoke(initialState);

  return {
    jobs: result.rankedJobs || [],
    count: result.rankedJobs?.length || 0,
    error: result.error || null,
  };
}