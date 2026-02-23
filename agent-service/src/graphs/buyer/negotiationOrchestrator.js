import { runMatching } from './negotiationGraph.js';
import { runProviderMatching } from './providerMatchingGraph.js';
import { negotiationService, memoryService, cacheService, getProviderBasicDetails } from '../../services/index.js';
import prisma from '../../prisma/client.js';
import { NEGOTIATION_TIME_SECONDS } from '../../config/index.js';

/* ================================================================================
   NEGOTIATION ORCHESTRATOR - Using Prisma Services (SellerProfile + LLM tools)
   ================================================================================ */

/* -------------------- PROVIDER DETAILS (for cache display name fallback) -------------------- */

async function fetchProviderBasicDetails(providerOrProfileId) {
  // providerOrProfileId may be profile id (UUID) from ranked sellers or provider id (numeric) from external API
  const numeric = parseInt(String(providerOrProfileId), 10);
  if (isNaN(numeric)) {
    // It's a SellerProfile id (UUID) – get name, email, contact from our DB
    const profile = await prisma.sellerProfile.findUnique({
      where: { id: providerOrProfileId },
      select: { id: true, providerId: true, firstName: true, lastName: true, email: true, contactNumber: true },
    });
    if (profile) {
      return {
        provider_id: profile.providerId,
        first_name: profile.firstName ?? 'Provider',
        last_name: profile.lastName ?? profile.id?.toString().slice(0, 8) ?? '',
        email: profile.email ?? null,
        contact_number: profile.contactNumber ?? null,
        gender: null,
      };
    }
    return {
      provider_id: providerOrProfileId,
      first_name: 'Provider',
      last_name: providerOrProfileId?.toString().slice(0, 8) ?? '',
      email: null,
      contact_number: null,
      gender: null,
    };
  }
  const details = await getProviderBasicDetails(numeric);
  return {
    provider_id: numeric,
    first_name: details?.firstName ?? 'Provider',
    last_name: details?.lastName ?? String(numeric).slice(0, 8) ?? '',
    email: details?.email ?? null,
    contact_number: details?.contactNumber ?? null,
    gender: details?.gender ?? null,
  };
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

  // Allow matching even without categories - embeddings/semantic search is primary method
  if (!service_category_id && !service_category_name) {
    console.log('[ProviderMatching] No category provided - will use pure semantic search (embeddings only).');
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
    service_category_name: job.service_category_name ?? null,
    description: job.description ?? null,
    location: job.location ?? null,
  };

  const { providers, error } = await runProviderMatching({ ...job, service_category_name: job.service_category_name || service_category_name });
  if (error || !providers?.length) {
    return { deals: [], reply: error || 'No providers found for this service.' };
  }

  if (typeof options.streamCallback === 'function') {
    options.streamCallback({ type: 'providers_fetched', count: providers.length });
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

    // Get provider details from SellerProfile first (provider is from sellerProfileToProvider with profile spread)
    const sellerNameFromProfile = [provider.firstName, provider.lastName].filter(Boolean).join(' ').trim();
    const basic = await cacheService.getProviderBasic(
      providerId,
      async (id) => await fetchProviderBasicDetails(id)
    );
    const sellerNameFromBasic = basic ? [basic.first_name, basic.last_name].filter(Boolean).join(' ').trim() : null;
    const sellerName = sellerNameFromProfile || sellerNameFromBasic || provider?.name || provider?.firstName || 'Provider';
    const sellerEmail = provider.email ?? basic?.email ?? null;
    const sellerContactNumber = provider.contactNumber ?? basic?.contact_number ?? null;

    // Score immediately
    const scored = await scoreProvider({
      providerId: String(providerId),
      provider,
      quote: outcome.quote,
      negotiationMessage: lastSellerMessage ?? null,
      sellerName,
      sellerEmail,
      sellerContactNumber,
    }, job);

    if (scored.failedMustHave) {
      console.log(`[Skip] Provider ${providerId} failed must-have: ${scored.failureReason}`);
      
      // Store rejection in memory
      if (useMemory && buyerId) {
        await memoryService.storeBuyerNegotiation({
          buyerId,
          jobId: job.id,
          negotiationData: {
            job: jobSnippet,
            quote: outcome.quote,
            providerId: String(providerId),
            conversation: outcome.transcript,
            outcome: 'rejected_must_have'
          }
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
      await memoryService.storeBuyerNegotiation({
        buyerId,
        jobId: job.id,
        negotiationData: {
          job: jobSnippet,
          quote: outcome.quote,
          providerId: String(providerId),
          conversation: outcome.transcript,
          outcome: 'presented'
        }
      });

      await memoryService.storeProviderNegotiation({
        providerId: String(providerId),
        jobId: job.id,
        negotiationData: {
          job: jobSnippet,
          quote: outcome.quote,
          buyerId: buyerId,
          outcome: 'quoted'
        }
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
      sellerEmail: r.sellerEmail ?? null,
      sellerContactNumber: r.sellerContactNumber ?? null,
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
    sellerEmail: r.sellerEmail ?? null,
    sellerContactNumber: r.sellerContactNumber ?? null,
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