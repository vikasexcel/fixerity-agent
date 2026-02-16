import { findJobsForSeller } from './jobMatchingGraph.js';
import prisma from '../../prisma/client.js';

/* ================================================================================
   SELLER ORCHESTRATOR - High-level seller operations
   ================================================================================ */

/* -------------------- GET SELLER DASHBOARD -------------------- */

export async function getSellerDashboard(sellerId) {
  try {
    const providerId = parseInt(String(sellerId), 10);
    const profileIds = !isNaN(providerId)
      ? (await prisma.sellerProfile.findMany({
          where: { providerId },
          select: { id: true },
        })).map((p) => p.id)
      : [sellerId];

    // Get active bids (all profiles for this provider)
    const activeBids = await prisma.sellerBid.findMany({
      where: profileIds.length > 0 ? { sellerId: { in: profileIds }, status: 'pending' } : { id: 'impossible' },
      include: { 
        job: true 
      },
      orderBy: { createdAt: 'desc' },
      take: 10,
    });

    // Get accepted bids (active jobs)
    const activeJobs = await prisma.sellerBid.findMany({
      where: profileIds.length > 0 ? { sellerId: { in: profileIds }, status: 'accepted' } : { id: 'impossible' },
      include: { 
        job: true 
      },
      orderBy: { createdAt: 'desc' },
      take: 10,
    });

    // Find new matching jobs
    const matchingJobs = await findJobsForSeller(sellerId);

    return {
      activeBids: activeBids.map(b => ({
        bid_id: b.id,
        job_id: b.jobId,
        quoted_price: Number(b.quotedPrice),
        quoted_completion_days: b.quotedCompletionDays,
        status: b.status,
        job: b.job ? {
          title: b.job.title,
          budget: b.job.budget,
          start_date: b.job.startDate,
        } : null,
        created_at: b.createdAt,
      })),
      activeJobs: activeJobs.map(b => ({
        bid_id: b.id,
        job_id: b.jobId,
        quoted_price: Number(b.quotedPrice),
        status: b.status,
        job: b.job ? {
          title: b.job.title,
          budget: b.job.budget,
          start_date: b.job.startDate,
          status: b.job.status,
        } : null,
        created_at: b.createdAt,
      })),
      matchingJobs: matchingJobs.jobs || [],
      stats: {
        pending_bids: activeBids.length,
        active_jobs: activeJobs.length,
        new_matches: matchingJobs.count || 0,
      }
    };
  } catch (error) {
    console.error('[SellerOrchestrator] Dashboard error:', error.message);
    return {
      activeBids: [],
      activeJobs: [],
      matchingJobs: [],
      stats: { pending_bids: 0, active_jobs: 0, new_matches: 0 },
      error: error.message,
    };
  }
}

/* -------------------- HANDLE BID ACCEPTANCE/REJECTION -------------------- */

export async function handleBidStatusUpdate(bidId, newStatus, buyerResponse = null) {
  try {
    const bid = await prisma.sellerBid.findUnique({
      where: { id: bidId }
    });
    
    if (!bid) {
      return { success: false, error: 'Bid not found' };
    }

    const updateData = {
      status: newStatus,
    };

    if (buyerResponse) {
      updateData.buyerResponse = buyerResponse;
    }

    const updated = await prisma.sellerBid.update({
      where: { id: bidId },
      data: updateData,
    });

    // If accepted, update job listing and seller stats
    if (newStatus === 'accepted') {
      await prisma.jobListing.update({
        where: { id: bid.jobId },
        data: { 
          status: 'in_progress',
          selectedSellerId: bid.sellerId,
        },
      });

      await prisma.sellerProfile.update({
        where: { id: bid.sellerId },
        data: {
          totalBidsAccepted: {
            increment: 1,
          },
        },
      });
    }

    return { 
      success: true, 
      bid: {
        bid_id: updated.id,
        status: updated.status,
        buyer_response: updated.buyerResponse,
      }
    };
  } catch (error) {
    console.error('[SellerOrchestrator] Bid status update error:', error.message);
    return { success: false, error: error.message };
  }
}

/* -------------------- WITHDRAW BID -------------------- */

export async function withdrawBid(bidId, sellerId) {
  try {
    const bid = await prisma.sellerBid.findUnique({
      where: { id: bidId }
    });

    if (!bid) {
      return { success: false, error: 'Bid not found' };
    }

    const providerId = parseInt(String(sellerId), 10);
    const profileIds = !isNaN(providerId)
      ? (await prisma.sellerProfile.findMany({
          where: { providerId },
          select: { id: true },
        })).map((p) => p.id)
      : [sellerId];
    if (!profileIds.includes(bid.sellerId)) {
      return { success: false, error: 'Unauthorized' };
    }

    if (bid.status !== 'pending') {
      return { success: false, error: 'Can only withdraw pending bids' };
    }

    const updated = await prisma.sellerBid.update({
      where: { id: bidId },
      data: { status: 'withdrawn' },
    });

    return { 
      success: true, 
      bid: {
        bid_id: updated.id,
        status: updated.status,
      }
    };
  } catch (error) {
    console.error('[SellerOrchestrator] Withdraw bid error:', error.message);
    return { success: false, error: error.message };
  }
}