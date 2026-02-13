import { Annotation, StateGraph, START, END } from '@langchain/langgraph';
import { ChatOpenAI } from '@langchain/openai';
import { HumanMessage, SystemMessage } from '@langchain/core/messages';
import { OPENAI_API_KEY } from '../../config/index.js';
import prisma from '../../prisma/client.js';

/* ================================================================================
   SELLER BIDDING GRAPH - Generate and Submit Bids
   ================================================================================ */

const SellerBiddingState = Annotation.Root({
  sellerId: Annotation(),
  jobId: Annotation(),
  job: Annotation(),
  sellerProfile: Annotation(),
  userInput: Annotation(), // Optional: seller's custom message/pricing
  generatedBid: Annotation(),
  submittedBid: Annotation(),
  error: Annotation(),
});

/* -------------------- LOAD DATA NODE -------------------- */

async function loadDataNode(state) {
  try {
    const [job, profile] = await Promise.all([
      prisma.jobListing.findUnique({
        where: { id: state.jobId }
      }),
      prisma.sellerProfile.findUnique({
        where: { id: state.sellerId, active: true }
      })
    ]);

    if (!job) {
      return { error: 'Job not found' };
    }

    if (!profile) {
      return { error: 'Seller profile not found' };
    }

    console.log(`[SellerBidding] Loaded job ${state.jobId} and profile for seller ${state.sellerId}`);

    return {
      job: {
        job_id: job.id,
        buyer_id: job.buyerId,
        service_category_id: job.serviceCategoryId,
        title: job.title,
        description: job.description,
        budget: job.budget,
        start_date: job.startDate,
        end_date: job.endDate,
        location: job.location,
        priorities: job.priorities,
        status: job.status,
      },
      sellerProfile: {
        seller_id: profile.id,
        service_category_names: profile.serviceCategoryNames ?? [],
        credentials: profile.credentials,
        pricing: profile.pricing,
        availability: profile.availability,
      },
    };
  } catch (error) {
    console.error('[SellerBidding] Error loading data:', error.message);
    return { error: 'Failed to load data' };
  }
}

/* -------------------- GENERATE BID NODE -------------------- */

async function generateBidNode(state) {
  const llm = new ChatOpenAI({
    model: 'gpt-4o-mini',
    temperature: 0.7,
    openAIApiKey: OPENAI_API_KEY,
  });

  const job = state.job;
  const profile = state.sellerProfile;
  const userInput = state.userInput || {};

  const prompt = `
You are helping a service provider generate a bid for a job.

Job Details:
- Title: ${job.title}
- Description: ${job.description || 'No description'}
- Budget: $${job.budget?.min || '?'}-$${job.budget?.max || '?'}
- Start Date: ${job.start_date || 'ASAP'}
- End Date: ${job.end_date || 'Flexible'}
- Location: ${job.location?.address || 'Not specified'}

Seller Profile:
- Experience: ${profile.credentials?.years_experience || 'Not specified'} years
- Licensed: ${profile.credentials?.licensed ? 'Yes' : 'No'}
- Insured: ${profile.credentials?.insured ? 'Yes' : 'No'}
- References Available: ${profile.credentials?.references_available ? 'Yes' : 'No'}
- Pricing: ${profile.pricing?.hourly_rate_max ? '$' + profile.pricing.hourly_rate_min + '-$' + profile.pricing.hourly_rate_max + '/hr' : 'Custom pricing'}
- Availability: ${profile.availability?.schedule || 'Flexible'}

${userInput.customMessage ? `Seller's Custom Message: "${userInput.customMessage}"` : ''}
${userInput.customPrice ? `Seller's Custom Price: $${userInput.customPrice}` : ''}

Generate a professional bid that includes:
1. A friendly, professional message to the buyer
2. A competitive quote (consider budget and seller's rates)
3. Estimated timeline/completion days
4. Payment terms
5. Mention of credentials if they add value

Pricing Strategy:
- If job budget max is given and seller has hourly rate: quote 85-95% of budget max
- If custom price provided by seller: use that
- Otherwise: use seller's typical rate

Reply ONLY with JSON:
{
  "message": "<professional message to buyer>",
  "quoted_price": <number>,
  "quoted_timeline": "<e.g., '3 days' or 'Complete by 2025-02-20'>",
  "quoted_completion_days": <number of days>,
  "payment_terms": "<e.g., '20% upfront, 80% on completion'>",
  "can_meet_dates": true/false
}
`;

  try {
    const res = await llm.invoke([
      new SystemMessage('Only output valid JSON. Be professional and competitive.'),
      new HumanMessage(prompt),
    ]);

    let content = res.content.trim();
    content = content.replace(/```json\n?/g, '').replace(/```\n?/g, '');
    const bid = JSON.parse(content);

    console.log(`[SellerBidding] Generated bid: $${bid.quoted_price} for ${bid.quoted_completion_days} days`);

    return { generatedBid: bid };
  } catch (error) {
    console.error('[SellerBidding] Error generating bid:', error.message);

    // Fallback bid
    const fallbackPrice = userInput.customPrice ||
      (job.budget?.max ? job.budget.max * 0.9 : 100);

    return {
      generatedBid: {
        message: `I'd be happy to help with your ${job.title} project. I have ${profile.credentials?.years_experience || 2} years of experience and can complete this efficiently.`,
        quoted_price: fallbackPrice,
        quoted_timeline: '3 days',
        quoted_completion_days: 3,
        payment_terms: '20% upfront, 80% on completion',
        can_meet_dates: true,
      }
    };
  }
}

/* -------------------- SUBMIT BID NODE -------------------- */

async function submitBidNode(state) {
  const bid = state.generatedBid;
  const profile = state.sellerProfile;

  try {
    const bidId = `bid_${state.sellerId}_${state.jobId}_${Date.now()}`;

    const submittedBid = await prisma.sellerBid.create({
      data: {
        id: bidId,
        jobId: state.jobId,
        sellerId: state.sellerId,
        quotedPrice: bid.quoted_price,
        quotedTimeline: bid.quoted_timeline,
        quotedCompletionDays: bid.quoted_completion_days,
        paymentTerms: bid.payment_terms,
        canMeetDates: bid.can_meet_dates,
        message: bid.message,
        sellerCredentials: {
          licensed: profile.credentials?.licensed || false,
          insured: profile.credentials?.insured || false,
          years_experience: profile.credentials?.years_experience || 0,
          references_available: profile.credentials?.references_available || false,
        },
        status: 'pending',
      },
    });

    // Update job listing bid count
    await prisma.jobListing.update({
      where: { id: state.jobId },
      data: {
        numBidsReceived: {
          increment: 1,
        },
      },
    });

    // Update seller profile bid count
    await prisma.sellerProfile.update({
      where: { id: state.sellerId },
      data: {
        totalBidsSubmitted: {
          increment: 1,
        },
      },
    });

    console.log(`[SellerBidding] Submitted bid ${bidId}`);

    return {
      submittedBid: {
        bid_id: submittedBid.id,
        job_id: submittedBid.jobId,
        seller_id: submittedBid.sellerId,
        quoted_price: Number(submittedBid.quotedPrice),
        quoted_timeline: submittedBid.quotedTimeline,
        quoted_completion_days: submittedBid.quotedCompletionDays,
        payment_terms: submittedBid.paymentTerms,
        can_meet_dates: submittedBid.canMeetDates,
        message: submittedBid.message,
        seller_credentials: submittedBid.sellerCredentials,
        status: submittedBid.status,
        created_at: submittedBid.createdAt,
      }
    };
  } catch (error) {
    console.error('[SellerBidding] Error submitting bid:', error.message);
    return {
      error: 'Failed to submit bid'
    };
  }
}

/* -------------------- GRAPH DEFINITION -------------------- */

const workflow = new StateGraph(SellerBiddingState)
  .addNode('load_data', loadDataNode)
  .addNode('generate_bid', generateBidNode)
  .addNode('submit_bid', submitBidNode)

  .addEdge(START, 'load_data')
  .addEdge('load_data', 'generate_bid')
  .addEdge('generate_bid', 'submit_bid')
  .addEdge('submit_bid', END);

export const sellerBiddingGraph = workflow.compile();

/* -------------------- RUNNER FUNCTION -------------------- */

export async function submitSellerBid(input) {
  const { sellerId, jobId, customMessage, customPrice } = input;

  const initialState = {
    sellerId,
    jobId,
    userInput: {
      customMessage: customMessage || null,
      customPrice: customPrice || null,
    },
  };

  const result = await sellerBiddingGraph.invoke(initialState);

  return {
    bid: result.submittedBid || null,
    error: result.error || null,
  };
}

/**
 * Generate bid only (no DB write). Used by seller tool for HITL: show quote, interrupt, then submit on resume.
 */
export async function generateBidForJob(sellerId, jobId, customMessage = null, customPrice = null) {
  const state = {
    sellerId,
    jobId,
    userInput: { customMessage, customPrice },
  };
  const afterLoad = await loadDataNode(state);
  if (afterLoad.error) return { error: afterLoad.error };
  const afterGenerate = await generateBidNode({ ...state, ...afterLoad });
  return {
    job: afterLoad.job,
    sellerProfile: afterLoad.sellerProfile,
    generatedBid: afterGenerate.generatedBid,
  };
}

/**
 * Create bid in DB from a pre-generated bid. Used after HITL approval.
 */
export async function createBidInDb(sellerId, jobId, generatedBid, sellerProfile) {
  try {
    const bidId = `bid_${sellerId}_${jobId}_${Date.now()}`;
    const submittedBid = await prisma.sellerBid.create({
      data: {
        id: bidId,
        jobId,
        sellerId,
        quotedPrice: generatedBid.quoted_price,
        quotedTimeline: generatedBid.quoted_timeline,
        quotedCompletionDays: generatedBid.quoted_completion_days,
        paymentTerms: generatedBid.payment_terms,
        canMeetDates: generatedBid.can_meet_dates,
        message: generatedBid.message,
        sellerCredentials: {
          licensed: sellerProfile?.credentials?.licensed || false,
          insured: sellerProfile?.credentials?.insured || false,
          years_experience: sellerProfile?.credentials?.years_experience || 0,
          references_available: sellerProfile?.credentials?.references_available || false,
        },
        status: 'pending',
      },
    });
    await prisma.jobListing.update({
      where: { id: jobId },
      data: { numBidsReceived: { increment: 1 } },
    });
    await prisma.sellerProfile.update({
      where: { id: sellerId },
      data: { totalBidsSubmitted: { increment: 1 } },
    });
    return {
      bid_id: submittedBid.id,
      quoted_price: Number(submittedBid.quotedPrice),
      quoted_completion_days: submittedBid.quotedCompletionDays,
    };
  } catch (error) {
    console.error('[SellerBidding] createBidInDb error:', error.message);
    return { error: error.message };
  }
}
