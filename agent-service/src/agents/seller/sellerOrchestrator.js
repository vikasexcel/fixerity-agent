import { findJobsForSeller } from './jobMatchingGraph.js';
import { SellerBid } from '../../models/SellerBid.js';
import { JobListing } from '../../models/JobListing.js';

/* ================================================================================
   SELLER ORCHESTRATOR - High-level seller operations
   ================================================================================ */

/* -------------------- GET SELLER DASHBOARD -------------------- */

export async function getSellerDashboard(sellerId) {
  try {
    // Get active bids
    const activeBids = await SellerBid.findAll({
      where: { seller_id: sellerId, status: 'pending' },
      include: [{ model: JobListing, as: 'job' }],
      order: [['created_at', 'DESC']],
      limit: 10,
    });

    // Get accepted bids (active jobs)
    const activeJobs = await SellerBid.findAll({
      where: { seller_id: sellerId, status: 'accepted' },
      include: [{ model: JobListing, as: 'job' }],
      order: [['created_at', 'DESC']],
      limit: 10,
    });

    // Find new matching jobs
    const matchingJobs = await findJobsForSeller(sellerId);

    return {
      activeBids: activeBids.map(b => b.toJSON()),
      activeJobs: activeJobs.map(b => b.toJSON()),
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
    const bid = await SellerBid.findByPk(bidId);
    
    if (!bid) {
      return { success: false, error: 'Bid not found' };
    }

    bid.status = newStatus;
    if (buyerResponse) {
      bid.buyer_response = buyerResponse;
    }
    
    await bid.save();

    // If accepted, update job listing and seller stats
    if (newStatus === 'accepted') {
      await JobListing.update(
        { 
          status: 'in_progress',
          selected_seller_id: bid.seller_id 
        },
        { where: { job_id: bid.job_id } }
      );

      await SellerProfile.increment('total_bids_accepted', {
        where: { seller_id: bid.seller_id }
      });
    }

    return { success: true, bid: bid.toJSON() };
  } catch (error) {
    console.error('[SellerOrchestrator] Bid status update error:', error.message);
    return { success: false, error: error.message };
  }
}

/* -------------------- WITHDRAW BID -------------------- */

export async function withdrawBid(bidId, sellerId) {
  try {
    const bid = await SellerBid.findOne({
      where: { bid_id: bidId, seller_id: sellerId }
    });

    if (!bid) {
      return { success: false, error: 'Bid not found' };
    }

    if (bid.status !== 'pending') {
      return { success: false, error: 'Can only withdraw pending bids' };
    }

    bid.status = 'withdrawn';
    await bid.save();

    return { success: true, bid: bid.toJSON() };
  } catch (error) {
    console.error('[SellerOrchestrator] Withdraw bid error:', error.message);
    return { success: false, error: error.message };
  }
}

/* -------------------- AUTO-BID MODE (Optional Feature) -------------------- */

export async function enableAutoBid(sellerId, rules = {}) {
  // Store auto-bid rules in Redis or DB
  // Rules: max_price, auto_accept_budget_range, etc.
  
  // When new jobs arrive, automatically submit bids if they match rules
  // This is an advanced feature for high-volume sellers
  
  console.log(`[SellerOrchestrator] Auto-bid enabled for seller ${sellerId}`);
  return { success: true, rules };
}