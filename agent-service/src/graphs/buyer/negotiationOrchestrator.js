import { runMatching } from './negotiationGraph.js';
import { negotiationService, memoryService, cacheService } from '../../services/index.js';
import { NEGOTIATION_TIME_SECONDS } from '../../config/index.js';

/* ================================================================================
   NEGOTIATION ORCHESTRATOR - Using Prisma Services
   ================================================================================ */

/* -------------------- PROVIDER TOOLS -------------------- */

async function fetchProviderBasicDetails(providerId) {
  // This would call your actual API
  // For now, return mock data
  return {
    provider_id: providerId,
    first_name: 'John',
    last_name: 'Doe',
  };
}

async function fetchProvidersByCategory(accessToken, serviceCategoryId) {
  try {
    const response = await fetch('http://116.202.210.102:8002/api/provider/category', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        access_token: accessToken,
        service_category_id: serviceCategoryId,
      })
    });

    const data = await response.json();
    
    if (data.status === 1 && data.providers) {
      return { providers: data.providers, error: null };
    }
    
    return { providers: [], error: 'No providers found' };
  } catch (error) {
    console.error('[FetchProviders] Error:', error.message);
    return { providers: [], error: error.message };
  }
}

/* -------------------- HELPERS -------------------- */

function parsePriorities(prioritiesArray = []) {
  const parsed = {
    must_have: {},
    nice_to_have: {},
    bonus: {}
  };

  for (const p of prioritiesArray) {
    const level = p.level;
    const type = p.type;
    const value = p.value;

    if (!parsed[level]) continue;

    switch (type) {
      case 'price':
        parsed[level].max_price = Number(value);
        break;
      case 'startDate':
        parsed[level].start_date = value;
        break;
      case 'endDate':
        parsed[level].end_date = value;
        break;
      case 'rating':
        parsed[level].min_rating = Number(value);
        break;
      case 'jobsCompleted':
        parsed[level].min_jobs_completed = Number(value);
        break;
      case 'licensed':
        parsed[level].licensed = value === true || value === 'true' || value === '1';
        break;
      case 'references':
        parsed[level].references = value === true || value === 'true' || value === '1';
        break;
      default:
        break;
    }
  }

  return parsed;
}

function getProviderServiceData(provider, job) {
  return {
    average_rating: provider?.average_rating ?? provider?.rating ?? 0,
    total_completed_order: provider?.total_completed_order ?? provider?.jobsCompleted ?? 0,
    licensed: provider?.licensed !== false,
    referencesAvailable: (Number(provider?.num_of_rating ?? 0) || 0) > 0,
    deadline_in_days: provider?.deadline_in_days ?? 
                     provider?.service_deadline_days ?? 
                     provider?.completionDays ?? 
                     3,
  };
}

function rankProviders(providers = [], limit = 10) {
  return providers
    .map(p => ({
      ...p,
      rankScore: 
        (p.average_rating ?? 0) * 10 +
        Math.min(p.total_completed_order ?? 0, 50) +
        (p.licensed ? 20 : 0)
    }))
    .sort((a, b) => b.rankScore - a.rankScore)
    .slice(0, limit);
}

/* -------------------- SMART FILTERING (Using Memory Service) -------------------- */

async function smartFilterProviders(providers, buyerId, job) {
  if (!buyerId) return providers;

  const buyerPrefs = await memoryService.getBuyerPreferences(
    buyerId,
    job.service_category_id
  );

  if (!buyerPrefs || !buyerPrefs.memories || buyerPrefs.memories.length === 0) {
    console.log('[Memory] No buyer history found, using all providers');
    return providers;
  }

  console.log(`[Memory] Found ${buyerPrefs.memories.length} buyer preferences`);

  // TODO: Implement intelligent filtering based on memories
  return providers;
}

async function enhanceJobPriorities(job, buyerId) {
  if (!buyerId) return job;

  const recommendations = await memoryService.getJobRecommendations(buyerId, job);

  if (!recommendations || !recommendations.memories || recommendations.memories.length === 0) {
    console.log('[Memory] No recommendations found, using original priorities');
    return job;
  }

  console.log(`[Memory] Found ${recommendations.memories.length} recommendations`);
  console.log(`[Memory] Confidence: ${recommendations.recommendations?.confidence || 'unknown'}`);

  return job;
}

/* -------------------- MAIN ORCHESTRATOR -------------------- */

export async function runMatchAndRecommend(job, buyerAccessToken, options = {}) {
  const service_category_id = job?.service_category_id != null ? Number(job.service_category_id) : null;
  const service_category_name = job?.service_category_name || null;
  if (!service_category_id && !service_category_name) {
    return { deals: [], reply: 'Job must have a service (category name or ID).' };
  }

  const buyerId = job.buyer_id || options.buyerId;
  const useMemory = options.useMemory !== false;

  // Parse priorities if array format
  if (Array.isArray(job.priorities)) {
    job.priorities = parsePriorities(job.priorities);
  }

  // Enhance job priorities using memory
  if (useMemory && buyerId) {
    job = await enhanceJobPriorities(job, buyerId);
  }

  const maxRounds = Math.min(options.maxRounds ?? 1, 2);
  const timeLimitSeconds = job.agent_time_limit_seconds ?? options.timeSeconds ?? NEGOTIATION_TIME_SECONDS ?? 60;
  const deadline_ts = Date.now() + timeLimitSeconds * 1000;

  const jobSnippet = {
    id: job.id,
    title: job.title,
    budget: job.budget,
    startDate: job.startDate,
    endDate: job.endDate,
    priorities: job.priorities,
    service_category_id,
  };

  const { providers, error } = await fetchProvidersByCategory(buyerAccessToken, service_category_id ?? 0);
  if (error || !providers?.length) {
    return { deals: [], reply: 'No providers found for this service.' };
  }

  // Smart filter providers using memory
  let filteredProviders = providers;
  if (useMemory && buyerId) {
    filteredProviders = await smartFilterProviders(providers, buyerId, job);
  }

  // Rank providers
  const rankedProviders = rankProviders(filteredProviders, options.providerLimit ?? 10);

  // Batch cache all providers
  await cacheService.batchCacheProviders(rankedProviders);
  console.log(`[Cache] Cached ${rankedProviders.length} providers`);

  // Run negotiations
  const topDeals = [];

  for (const provider of rankedProviders) {
    const providerId = provider?.provider_id ?? provider?.id;
    if (!providerId) continue;

    const providerServiceData = getProviderServiceData(provider, job);

    const outcome = await runMatching({
      job: jobSnippet,
      providerId: String(providerId),
      providerServiceData,
      buyerId: buyerId,
      maxRounds,
      deadline_ts,
      useMem0Learning: useMemory,
      streamCallback: options.streamCallback,
    });

    if (!outcome?.quote) continue;

    const lastSellerMessage = Array.isArray(outcome.transcript)
      ? outcome.transcript.filter((m) => m.role === 'seller').pop()?.message
      : null;

    // Get provider details from cache
    const basic = await cacheService.getProviderBasic(
      providerId,
      async (id) => await fetchProviderBasicDetails(id)
    );
    
    const sellerNameFromBasic = basic ? [basic.first_name, basic.last_name].filter(Boolean).join(' ').trim() : null;
    const sellerName = sellerNameFromBasic ?? provider?.name ?? provider?.first_name ?? 'Provider';

    // Score immediately
    const scored = await scoreProvider({
      providerId: String(providerId),
      provider,
      quote: outcome.quote,
      negotiationMessage: lastSellerMessage ?? null,
      sellerName,
    }, job);

    if (scored.failedMustHave) {
      console.log(`[Skip] Provider ${providerId} failed must-have: ${scored.failureReason}`);
      
      // Store rejection in memory
      if (useMemory && buyerId) {
        await memoryService.storeBuyerNegotiation(buyerId, job.id, {
          job: jobSnippet,
          quote: outcome.quote,
          providerId: String(providerId),
          conversation: outcome.transcript,
          outcome: 'rejected_must_have'
        });
      }
      
      continue;
    }

    // Insert into top deals (keep only top 5)
    topDeals.push(scored);
    topDeals.sort((a, b) => b.matchScore - a.matchScore);
    if (topDeals.length > 5) {
      topDeals.pop();
    }

    // Store successful quote in memory
    if (useMemory && buyerId) {
      await memoryService.storeBuyerNegotiation(buyerId, job.id, {
        job: jobSnippet,
        quote: outcome.quote,
        providerId: String(providerId),
        conversation: outcome.transcript,
        outcome: 'presented'
      });

      await memoryService.storeProviderNegotiation(String(providerId), job.id, {
        job: jobSnippet,
        quote: outcome.quote,
        buyerId: buyerId,
        outcome: 'quoted'
      });
    }
  }

  return {
    deals: topDeals,
    reply: topDeals.length > 0
      ? `Found ${topDeals.length} providers that match your priorities.`
      : 'No providers matched your must-have requirements.',
    memory_used: useMemory && buyerId ? true : false,
  };
}

/* -------------------- SCORING HELPER -------------------- */

async function scoreProvider(result, job) {
  const r = result;

  // Must-have checks
  const mustHavePrice = !job.priorities?.must_have?.max_price || r.quote.price <= job.priorities.must_have.max_price;
  const mustHaveDates = !job.priorities?.must_have?.start_date || r.quote.can_meet_dates !== false;
  const mustHavePass = mustHavePrice && mustHaveDates;

  if (!mustHavePass) {
    return {
      id: `deal_${job.id}_${r.providerId}_${Date.now()}`,
      sellerId: r.providerId,
      sellerName: r.sellerName,
      quote: r.quote,
      matchScore: 0,
      negotiationMessage: r.negotiationMessage ?? null,
      failedMustHave: true,
      failureReason: !mustHavePrice ? 'Price exceeds max budget' : 'Cannot meet required dates',
    };
  }

  // Nice-to-have scoring
  const ratingScore = r.provider.average_rating >= (job.priorities?.nice_to_have?.min_rating ?? 0) ? 20 : 0;
  const jobsScore = r.provider.total_completed_order >= (job.priorities?.nice_to_have?.min_jobs_completed ?? 0) ? 20 : 0;

  // Bonus scoring
  const bonusScore =
    (job.priorities?.bonus?.licensed && r.provider.licensed ? 10 : 0) +
    (job.priorities?.bonus?.references && r.provider.referencesAvailable ? 10 : 0);

  const matchScore = 40 + ratingScore + jobsScore + bonusScore;

  return {
    id: `deal_${job.id}_${r.providerId}_${Date.now()}`,
    sellerId: r.providerId,
    sellerName: r.sellerName,
    quote: r.quote,
    matchScore,
    negotiationMessage: r.negotiationMessage ?? null,
  };
}

/* -------------------- ALIASES -------------------- */

export async function runNegotiationAndMatch(job, buyerAccessToken, options = {}) {
  return runMatchAndRecommend(job, buyerAccessToken, options);
}

export async function runNegotiationAndMatchStream(job, buyerAccessToken, options = {}, send) {
  // Same as runMatchAndRecommend but with streaming
  const result = await runMatchAndRecommend(job, buyerAccessToken, {
    ...options,
    streamCallback: send,
  });
  
  if (typeof send === 'function') {
    send({ 
      type: 'done', 
      deals: result.deals, 
      reply: result.reply,
      memory_used: result.memory_used,
    });
  }
  
  return result;
}

/* -------------------- UPDATE OUTCOME -------------------- */

export async function updateNegotiationOutcome(buyerId, jobId, providerId, outcome) {
  if (!buyerId || !jobId || !providerId) {
    console.warn('[Memory] Missing required IDs for outcome update');
    return;
  }

  await memoryService.updateNegotiationOutcome(buyerId, jobId, providerId, outcome);
}