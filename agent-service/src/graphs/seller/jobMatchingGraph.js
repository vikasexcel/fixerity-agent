import { Annotation, StateGraph, START, END } from '@langchain/langgraph';
import prisma from '../../prisma/client.js';

/* ================================================================================
   JOB MATCHING GRAPH - Find Jobs That Match Seller Profile
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
      }
    };
  } catch (error) {
    console.error('[JobMatching] Error loading profile:', error.message);
    return { 
      error: 'Failed to load profile',
      matchedJobs: [],
    };
  }
}

/* -------------------- FIND MATCHING JOBS NODE -------------------- */

/** Match job service name to seller's service names (explicit names, no API IDs). */
function jobMatchesSellerServices(jobServiceName, sellerServiceNames) {
  if (!jobServiceName || !sellerServiceNames?.length) return false;
  const jobNorm = jobServiceName.trim().toLowerCase();
  if (!jobNorm) return false;
  return sellerServiceNames.some((name) => {
    const n = (name || '').trim().toLowerCase();
    if (!n) return false;
    return n === jobNorm || n.includes(jobNorm) || jobNorm.includes(n);
  });
}

async function findMatchingJobsNode(state) {
  const profile = state.sellerProfile;
  const sellerNames = profile?.service_category_names ?? [];

  if (!profile || sellerNames.length === 0) {
    return { matchedJobs: [] };
  }

  try {
    const jobs = await prisma.jobListing.findMany({
      where: { status: 'open' },
      orderBy: { createdAt: 'desc' },
      take: 100,
    });

    const matched = jobs.filter((j) =>
      jobMatchesSellerServices(j.serviceCategoryName ?? '', sellerNames)
    ).slice(0, 50);

    console.log(`[JobMatching] Found ${matched.length} potential matches (of ${jobs.length} open jobs)`);

    const matchedJobs = matched.map((j) => ({
      job_id: j.id,
      buyer_id: j.buyerId,
      service_category_id: j.serviceCategoryId,
      service_category_name: j.serviceCategoryName,
      title: j.title,
      description: j.description,
      budget: j.budget,
      start_date: j.startDate,
      end_date: j.endDate,
      location: j.location,
      priorities: j.priorities,
      status: j.status,
      num_bids_received: j.numBidsReceived,
      created_at: j.createdAt,
    }));

    return { matchedJobs };
  } catch (error) {
    console.error('[JobMatching] Error finding jobs:', error.message);
    return {
      matchedJobs: [],
      error: 'Failed to find matching jobs',
    };
  }
}

/* -------------------- RANK JOBS NODE -------------------- */

async function rankJobsNode(state) {
  const profile = state.sellerProfile;
  const jobs = state.matchedJobs;

  if (!jobs || jobs.length === 0) {
    return { rankedJobs: [] };
  }

  // Score each job
  const scoredJobs = jobs.map(job => {
    let score = 0;

    // Service category match (base score)
    score += 20;

    // Budget compatibility
    if (job.budget?.max && profile.pricing?.hourly_rate_max) {
      const budgetRatio = job.budget.max / profile.pricing.hourly_rate_max;
      if (budgetRatio >= 1) {
        score += 20; // Budget is within seller's range
      } else if (budgetRatio >= 0.8) {
        score += 10; // Close to range
      }
    } else {
      score += 10; // No pricing conflict
    }

    // Location match (if available)
    if (profile.service_area?.location && job.location?.address) {
      // Simplified: check if strings match
      // TODO: Implement proper geolocation distance calculation
      if (job.location.address.toLowerCase().includes(profile.service_area.location.toLowerCase())) {
        score += 15;
      }
    } else {
      score += 5; // Partial score if no location data
    }

    // Urgency bonus (newer jobs)
    const ageInDays = (Date.now() - new Date(job.created_at).getTime()) / (1000 * 60 * 60 * 24);
    if (ageInDays < 1) score += 15;
    else if (ageInDays < 3) score += 10;
    else if (ageInDays < 7) score += 5;

    // Availability match (simplified)
    if (profile.availability?.weekday_evenings && job.start_date?.includes('evening')) {
      score += 10;
    }
    if (profile.availability?.weekends && (job.start_date?.includes('saturday') || job.start_date?.includes('sunday'))) {
      score += 10;
    }

    return {
      ...job,
      matchScore: Math.min(score, 100), // Cap at 100
    };
  });

  // Sort by score descending
  const ranked = scoredJobs.sort((a, b) => b.matchScore - a.matchScore);

  console.log(`[JobMatching] Ranked ${ranked.length} jobs`);
  
  return { rankedJobs: ranked.slice(0, 20) }; // Top 20
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