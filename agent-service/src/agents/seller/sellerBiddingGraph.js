import { Annotation, StateGraph, START, END } from '@langchain/langgraph';
import { ChatOpenAI } from '@langchain/openai';
import { HumanMessage, SystemMessage } from '@langchain/core/messages';
import { OPENAI_API_KEY } from '../../config/index.js';
import { JobListing } from '../../models/JobListing.js';
import { SellerProfile } from '../../models/SellerProfile.js';
import { SellerBid } from '../../models/SellerBid.js';

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
      JobListing.findByPk(state.jobId),
      SellerProfile.findOne({ where: { seller_id: state.sellerId, active: true } })
    ]);

    if (!job) {
      return { error: 'Job not found' };
    }

    if (!profile) {
      return { error: 'Seller profile not found' };
    }

    console.log(`[SellerBidding] Loaded job ${state.jobId} and profile for seller ${state.sellerId}`);
    
    return { 
      job: job.toJSON(),
      sellerProfile: profile.toJSON(),
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

    const submittedBid = await SellerBid.create({
      bid_id: bidId,
      job_id: state.jobId,
      seller_id: state.sellerId,
      quoted_price: bid.quoted_price,
      quoted_timeline: bid.quoted_timeline,
      quoted_completion_days: bid.quoted_completion_days,
      payment_terms: bid.payment_terms,
      can_meet_dates: bid.can_meet_dates,
      message: bid.message,
      seller_credentials: {
        licensed: profile.credentials?.licensed || false,
        insured: profile.credentials?.insured || false,
        years_experience: profile.credentials?.years_experience || 0,
        references_available: profile.credentials?.references_available || false,
      },
      status: 'pending',
    });

    // Update job listing bid count
    await JobListing.increment('num_bids_received', {
      where: { job_id: state.jobId }
    });

    // Update seller profile bid count
    await SellerProfile.increment('total_bids_submitted', {
      where: { seller_id: state.sellerId }
    });

    console.log(`[SellerBidding] Submitted bid ${bidId}`);
    
    return { 
      submittedBid: submittedBid.toJSON() 
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