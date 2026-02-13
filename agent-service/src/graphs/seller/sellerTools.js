import { tool } from '@langchain/core/tools';
import { z } from 'zod';
import { interrupt } from '@langchain/langgraph';
import prisma from '../../prisma/client.js';
import { findJobsForSeller } from './jobMatchingGraph.js';
import { getSellerDashboard, withdrawBid as orchestratorWithdrawBid } from './sellerOrchestrator.js';
import { generateBidForJob, createBidInDb } from './sellerBiddingGraph.js';

/* ================================================================================
   SELLER TOOLS - Tools for the seller agent (profile, jobs, bids, dashboard)
   Human-in-the-loop: submit_bid and withdraw_bid use interrupt() for approval.
   ================================================================================ */

const sellerIdSchema = z.string().describe('The seller user ID (use the one from context).');
const jobIdSchema = z.string().describe('Job listing ID.');
const bidIdSchema = z.string().describe('Bid ID.');

/* -------------------- GET SELLER PROFILE -------------------- */

export const getSellerProfileTool = tool(
  async ({ sellerId }) => {
    console.log('[get_seller_profile] Tool called with sellerId:', sellerId);
    const profile = await prisma.sellerProfile.findFirst({
      where: { id: sellerId, active: true },
    });
    if (!profile) {
      console.log('[get_seller_profile] No active profile found for sellerId:', sellerId);
      return JSON.stringify({ found: false, message: 'No active profile found. Create one first.' });
    }
    console.log('[get_seller_profile] Returning profile from DB:', JSON.stringify({
      seller_id: profile.id,
      serviceCategoryNames: profile.serviceCategoryNames,
      service_area: profile.serviceArea,
      availability: profile.availability,
      credentials: profile.credentials,
      pricing: profile.pricing,
      preferences: profile.preferences,
      bio: profile.bio ? `${String(profile.bio).slice(0, 80)}...` : null,
      profileCompletenessScore: profile.profileCompletenessScore,
    }, null, 2));
    return JSON.stringify({
      found: true,
      profile: {
        seller_id: profile.id,
        service_category_names: profile.serviceCategoryNames ?? [],
        service_area: profile.serviceArea,
        availability: profile.availability,
        credentials: profile.credentials,
        pricing: profile.pricing,
        preferences: profile.preferences,
        bio: profile.bio,
        profile_completeness_score: profile.profileCompletenessScore,
      },
    });
  },
  {
    name: 'get_seller_profile',
    description: 'Get the current seller profile. Use to show profile details or check if profile exists.',
    schema: z.object({ sellerId: sellerIdSchema }),
  }
);

/* -------------------- UPDATE SELLER PROFILE -------------------- */

function parseOptionalJson(value) {
  if (value == null || value === '') return undefined;
  if (typeof value === 'object') return value;
  try {
    return typeof value === 'string' ? JSON.parse(value) : undefined;
  } catch {
    return undefined;
  }
}

function parseServiceCategoryNames(value) {
  if (value == null || value === '') return undefined;
  if (Array.isArray(value)) return value.every((x) => typeof x === 'string') ? value : value.map(String);
  if (typeof value === 'string') {
    try {
      const parsed = JSON.parse(value);
      return Array.isArray(parsed) ? parsed.map((x) => (typeof x === 'string' ? x : String(x))) : undefined;
    } catch {
      return [value.trim()].filter(Boolean);
    }
  }
  return undefined;
}

export const updateSellerProfileTool = tool(
  async ({ sellerId, pricing, availability, bio, service_area, service_category_names, credentials, preferences }) => {
    console.log('[update_seller_profile] Tool called with raw args:', {
      sellerId,
      pricing: typeof pricing === 'string' ? pricing.slice(0, 200) : pricing,
      availability: typeof availability === 'string' ? availability.slice(0, 200) : availability,
      bio: bio != null ? `${String(bio).slice(0, 100)}${String(bio).length > 100 ? '...' : ''}` : null,
      service_area: typeof service_area === 'string' ? service_area.slice(0, 200) : service_area,
      service_category_names,
      credentials: typeof credentials === 'string' ? credentials.slice(0, 200) : credentials,
      preferences: typeof preferences === 'string' ? preferences.slice(0, 200) : preferences,
    });
    const updateData = {};
    const pricingObj = parseOptionalJson(pricing);
    if (pricingObj != null) updateData.pricing = pricingObj;
    const availabilityObj = parseOptionalJson(availability);
    if (availabilityObj != null) updateData.availability = availabilityObj;
    if (bio != null && bio !== '') updateData.bio = String(bio);
    const serviceAreaObj = parseOptionalJson(service_area);
    if (serviceAreaObj != null) updateData.serviceArea = serviceAreaObj;
    const serviceNames = parseServiceCategoryNames(service_category_names);
    if (serviceNames != null) updateData.serviceCategoryNames = serviceNames;
    const credentialsObj = parseOptionalJson(credentials);
    if (credentialsObj != null) updateData.credentials = credentialsObj;
    const preferencesObj = parseOptionalJson(preferences);
    if (preferencesObj != null) updateData.preferences = preferencesObj;

    if (Object.keys(updateData).length === 0) {
      console.log('[update_seller_profile] No valid fields to update, skipping.');
      return JSON.stringify({ success: false, message: 'No valid fields to update.' });
    }

    console.log('[update_seller_profile] Parsed updateData being written to seller_profile:', JSON.stringify(updateData, null, 2));

    const profile = await prisma.sellerProfile.upsert({
      where: { id: sellerId },
      create: {
        id: sellerId,
        active: true,
        serviceCategoryNames: updateData.serviceCategoryNames ?? [],
        ...updateData,
      },
      update: updateData,
    });
    console.log('[update_seller_profile] Upsert success. Profile after update:', JSON.stringify({
      seller_id: profile.id,
      serviceCategoryNames: profile.serviceCategoryNames,
      serviceArea: profile.serviceArea,
      availability: profile.availability,
      credentials: profile.credentials,
      pricing: profile.pricing,
      preferences: profile.preferences,
      bio: profile.bio ? `${String(profile.bio).slice(0, 80)}...` : null,
    }, null, 2));
    return JSON.stringify({ success: true, message: 'Profile updated.', seller_id: profile.id });
  },
  {
    name: 'update_seller_profile',
    description: 'Update the seller profile (partial update). Pass only the fields to change (pricing, availability, bio, service_area, service_category_names, credentials, preferences).',
    schema: z.object({
      sellerId: sellerIdSchema,
      pricing: z.string().optional().describe('JSON string for pricing object, e.g. {"hourly_rate_min":50,"hourly_rate_max":100}'),
      availability: z.string().optional().describe('JSON string for availability object'),
      bio: z.string().optional(),
      service_area: z.string().optional().describe('JSON string for service area object'),
      service_category_names: z.string().optional().describe('JSON array of service names, e.g. ["home cleaning", "deep cleaning"]'),
      credentials: z.string().optional().describe('JSON string for credentials object'),
      preferences: z.string().optional().describe('JSON string for preferences object'),
    }),
  }
);

/* -------------------- GET MATCHED JOBS -------------------- */

export const getMatchedJobsTool = tool(
  async ({ sellerId }) => {
    const result = await findJobsForSeller(sellerId);
    if (result.error) return JSON.stringify({ error: result.error, jobs: [] });
    return JSON.stringify({ jobs: result.jobs || [], count: result.count || 0 });
  },
  {
    name: 'get_matched_jobs',
    description: 'Get list of jobs that match the seller profile. Use when the seller wants to browse or see available jobs.',
    schema: z.object({ sellerId: sellerIdSchema }),
  }
);

/* -------------------- GET JOB DETAILS -------------------- */

export const getJobDetailsTool = tool(
  async ({ jobId }) => {
    const job = await prisma.jobListing.findUnique({ where: { id: jobId } });
    if (!job) return JSON.stringify({ found: false, message: 'Job not found.' });
    return JSON.stringify({
      found: true,
      job: {
        job_id: job.id,
        title: job.title,
        description: job.description,
        budget: job.budget,
        start_date: job.startDate,
        end_date: job.endDate,
        location: job.location,
        priorities: job.priorities,
        status: job.status,
        num_bids_received: job.numBidsReceived,
      },
    });
  },
  {
    name: 'get_job_details',
    description: 'Get full details of a single job by ID. Use when the seller asks about a specific job.',
    schema: z.object({ jobId: jobIdSchema }),
  }
);

/* -------------------- LIST MY BIDS -------------------- */

export const listMyBidsTool = tool(
  async ({ sellerId, status }) => {
    const where = { sellerId };
    if (status && ['pending', 'accepted', 'withdrawn', 'rejected'].includes(status)) {
      where.status = status;
    }
    const bids = await prisma.sellerBid.findMany({
      where,
      include: { job: true },
      orderBy: { createdAt: 'desc' },
      take: 20,
    });
    return JSON.stringify({
      bids: bids.map((b) => ({
        bid_id: b.id,
        job_id: b.jobId,
        quoted_price: Number(b.quotedPrice),
        quoted_completion_days: b.quotedCompletionDays,
        status: b.status,
        job_title: b.job?.title,
        created_at: b.createdAt,
      })),
      count: bids.length,
    });
  },
  {
    name: 'list_my_bids',
    description: 'List the seller bids. Optionally filter by status: pending, accepted, withdrawn, rejected.',
    schema: z.object({
      sellerId: sellerIdSchema,
      status: z.enum(['pending', 'accepted', 'withdrawn', 'rejected']).optional(),
    }),
  }
);

/* -------------------- GET BID DETAILS -------------------- */

export const getBidDetailsTool = tool(
  async ({ bidId, sellerId }) => {
    const bid = await prisma.sellerBid.findUnique({
      where: { id: bidId },
      include: { job: true },
    });
    if (!bid) return JSON.stringify({ found: false, message: 'Bid not found.' });
    if (bid.sellerId !== sellerId) return JSON.stringify({ found: false, message: 'Unauthorized: not your bid.' });
    return JSON.stringify({
      found: true,
      bid: {
        bid_id: bid.id,
        job_id: bid.jobId,
        quoted_price: Number(bid.quotedPrice),
        quoted_timeline: bid.quotedTimeline,
        quoted_completion_days: bid.quotedCompletionDays,
        message: bid.message,
        status: bid.status,
        job: bid.job ? { title: bid.job.title, budget: bid.job.budget, status: bid.job.status } : null,
        created_at: bid.createdAt,
      },
    });
  },
  {
    name: 'get_bid_details',
    description: 'Get full details of a single bid by ID. Only returns the bid if it belongs to the seller.',
    schema: z.object({ bidId: bidIdSchema, sellerId: sellerIdSchema }),
  }
);

/* -------------------- SUBMIT BID (with HITL) -------------------- */

export const submitBidTool = tool(
  async ({ sellerId, jobId, customMessage, customPrice }) => {
    const generated = await generateBidForJob(sellerId, jobId, customMessage || null, customPrice || null);
    if (generated.error) return JSON.stringify({ success: false, error: generated.error });

    const { job, sellerProfile, generatedBid } = generated;
    const payload = {
      action: 'submit_bid',
      message: 'Approve submitting this bid?',
      jobId,
      jobTitle: job?.title || jobId,
      quotedPrice: generatedBid.quoted_price,
      quotedCompletionDays: generatedBid.quoted_completion_days,
      bidMessage: generatedBid.message,
      sellerId,
    };

    const response = interrupt(payload);
    const approved = response === true || (response && typeof response === 'object' && response.action === 'approve');
    if (approved) {
      const result = await createBidInDb(sellerId, jobId, generatedBid, sellerProfile);
      if (result.error) return JSON.stringify({ success: false, error: result.error });
      return JSON.stringify({
        success: true,
        message: `Bid submitted successfully. Price: $${result.quoted_price}, ${result.quoted_completion_days} days.`,
        bid_id: result.bid_id,
      });
    }
    return JSON.stringify({ success: false, message: 'Bid cancelled by user.' });
  },
  {
    name: 'submit_bid',
    description: 'Submit a bid for a job. Requires sellerId and jobId. Optionally include customMessage or customPrice. This will pause for user approval before submitting.',
    schema: z.object({
      sellerId: sellerIdSchema,
      jobId: jobIdSchema,
      customMessage: z.string().optional(),
      customPrice: z.number().optional(),
    }),
  }
);

/* -------------------- WITHDRAW BID (with HITL) -------------------- */

export const withdrawBidTool = tool(
  async ({ bidId, sellerId }) => {
    const bid = await prisma.sellerBid.findUnique({
      where: { id: bidId },
      include: { job: true },
    });
    if (!bid) return JSON.stringify({ success: false, error: 'Bid not found.' });
    if (bid.sellerId !== sellerId) return JSON.stringify({ success: false, error: 'Unauthorized.' });
    if (bid.status !== 'pending') return JSON.stringify({ success: false, error: 'Can only withdraw pending bids.' });

    const payload = {
      action: 'withdraw_bid',
      message: 'Approve withdrawing this bid?',
      bidId,
      jobTitle: bid.job?.title,
      sellerId,
    };

    const response = interrupt(payload);
    const approved = response === true || (response && typeof response === 'object' && response.action === 'approve');
    if (approved) {
      const result = await orchestratorWithdrawBid(bidId, sellerId);
      if (!result.success) return JSON.stringify({ success: false, error: result.error });
      return JSON.stringify({ success: true, message: 'Bid withdrawn.' });
    }
    return JSON.stringify({ success: false, message: 'Withdrawal cancelled by user.' });
  },
  {
    name: 'withdraw_bid',
    description: 'Withdraw a pending bid. Requires bidId and sellerId. Pauses for user approval before withdrawing.',
    schema: z.object({ bidId: bidIdSchema, sellerId: sellerIdSchema }),
  }
);

/* -------------------- GET SELLER DASHBOARD -------------------- */

export const getSellerDashboardTool = tool(
  async ({ sellerId }) => {
    const dashboard = await getSellerDashboard(sellerId);
    return JSON.stringify({
      stats: dashboard.stats,
      activeBids: dashboard.activeBids,
      activeJobs: dashboard.activeJobs,
      new_matches: dashboard.matchingJobs?.length || 0,
    });
  },
  {
    name: 'get_seller_dashboard',
    description: 'Get the seller dashboard: pending bids count, active jobs count, new job matches, and lists of active bids and jobs.',
    schema: z.object({ sellerId: sellerIdSchema }),
  }
);

/* -------------------- ALL TOOLS (for binding to LLM) -------------------- */

export const sellerTools = [
  getSellerProfileTool,
  updateSellerProfileTool,
  getMatchedJobsTool,
  getJobDetailsTool,
  listMyBidsTool,
  getBidDetailsTool,
  submitBidTool,
  withdrawBidTool,
  getSellerDashboardTool,
];

export const sellerToolsByName = Object.fromEntries(sellerTools.map((t) => [t.name, t]));
