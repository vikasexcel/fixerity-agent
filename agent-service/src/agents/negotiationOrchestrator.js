
import { runMatching, sessionStore, semanticMemory } from './negotiationGraph.js';
import { NEGOTIATION_TIME_SECONDS } from '../config/index.js';
import { redisClient } from '../config/redis.js';
import { fetchProviderBasicDetails, fetchProvidersByCategory } from '../tools/buyer/buyerAgentTools.js';

/* ================================================================================
   NEGOTIATION ORCHESTRATOR
   ================================================================================
   This module orchestrates the provider matching and negotiation process.
   It works with the unified agent system, receiving jobs from the conversation
   graph and finding/negotiating with providers.
   ================================================================================ */

/* ---------------- REDIS PROVIDER CACHE (Short-term: 24 hours) ---------------- */

class ProviderCache {
  constructor(redis) {
    this.redis = redis;
    this.TTL = 86400; // 24 hours
  }

  async cache(providerId, details) {
    const key = `provider:${providerId}:details`;
    await this.redis.setEx(key, this.TTL, JSON.stringify({
      ...details,
      cached_at: Date.now(),
    }));
  }

  async get(providerId) {
    const key = `provider:${providerId}:details`;
    const data = await this.redis.get(key);
    return data ? JSON.parse(data) : null;
  }

  async getCached(providerId, fetchFn) {
    const cached = await this.get(providerId);
    if (cached) {
      console.log(`[Redis] Provider ${providerId} cache hit`);
      return cached;
    }

    console.log(`[Redis] Provider ${providerId} cache miss, fetching...`);
    const fresh = await fetchFn(providerId);
    if (fresh) {
      await this.cache(providerId, fresh);
    }
    return fresh;
  }

  static BASIC_KEY(providerId) {
    return `provider:${providerId}:basic`;
  }

  async getBasicCached(providerId, fetchFn) {
    const key = ProviderCache.BASIC_KEY(providerId);
    try {
      const cached = await this.redis.get(key);
      if (cached) {
        console.log(`[Redis] Provider ${providerId} basic cache hit`);
        return JSON.parse(cached);
      }
    } catch {
      // ignore parse error
    }
    console.log(`[Redis] Provider ${providerId} basic cache miss, fetching from provider-basic-details API...`);
    const fresh = await fetchFn(providerId);
    if (fresh) {
      try {
        await this.redis.setEx(key, this.TTL, JSON.stringify({ ...fresh, cached_at: Date.now() }));
      } catch (e) {
        console.warn(`[Redis] Failed to cache basic details for ${providerId}:`, e.message);
      }
    }
    return fresh;
  }

  async batchCache(providers) {
    const multi = this.redis.multi();
    for (const provider of providers) {
      const key = `provider:${provider.provider_id || provider.id}:details`;
      multi.setEx(key, this.TTL, JSON.stringify({
        ...provider,
        cached_at: Date.now(),
      }));
    }
    await multi.exec();
  }
}

const providerCache = new ProviderCache(redisClient);

/* ---------------- PRIORITY PARSER ---------------- */

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

/* ---------------- PROVIDER DATA ---------------- */

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

/* ---------------- PROVIDER RANKING ---------------- */

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

/* ---------------- 🧠 SMART PROVIDER FILTERING (Using Mem0) ---------------- */

async function smartFilterProviders(providers, buyerId, job) {
  const buyerPrefs = await semanticMemory.getBuyerPreferences(
    buyerId,
    job.service_category_id
  );

  if (!buyerPrefs || !buyerPrefs.memories || buyerPrefs.memories.length === 0) {
    console.log('[Mem0] No buyer history found, using all providers');
    return providers;
  }

  console.log(`[Mem0] Found ${buyerPrefs.memories.length} buyer preferences, applying smart filter`);

  // TODO: Implement intelligent filtering based on memories
  return providers;
}

/* ---------------- 🧠 ENHANCE JOB PRIORITIES (Using Mem0) ---------------- */

async function enhanceJobPriorities(job, buyerId) {
  if (!buyerId) return job;

  const recommendations = await semanticMemory.getJobRecommendations(buyerId, job);

  if (!recommendations || !recommendations.memories || recommendations.memories.length === 0) {
    console.log('[Mem0] No recommendations found, using original priorities');
    return job;
  }

  console.log(`[Mem0] Found ${recommendations.memories.length} recommendations`);
  console.log(`[Mem0] Confidence: ${recommendations.recommendations?.confidence || 'unknown'}`);

  return job;
}

/* ---------------- MAIN ORCHESTRATOR ---------------- */

export async function runMatchAndRecommend(job, buyerAccessToken, options = {}) {
  const service_category_id = Number(job?.service_category_id);
  if (!service_category_id) {
    return { deals: [], reply: 'Job must have service_category_id.' };
  }

  const buyerId = job.buyer_id || options.buyerId;
  const useMem0 = options.useMem0 !== false;

  // Parse priorities if array format
  if (Array.isArray(job.priorities)) {
    job.priorities = parsePriorities(job.priorities);
  }

  // 🧠 STEP 1: Enhance job priorities using Mem0 learning
  if (useMem0 && buyerId) {
    job = await enhanceJobPriorities(job, buyerId);
  }

  const maxRounds = Math.min(options.maxRounds ?? 1, 2);
  const timeLimitSeconds = job.agent_time_limit_seconds ?? options.timeSeconds ?? options.negotiationTimeSeconds ?? NEGOTIATION_TIME_SECONDS ?? 60;
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

  const { providers, error } = await fetchProvidersByCategory(buyerAccessToken, service_category_id);
  if (error || !providers?.length) {
    return { deals: [], reply: 'No providers found.' };
  }

  // 🧠 STEP 2: Smart filter providers using Mem0
  let filteredProviders = providers;
  if (useMem0 && buyerId) {
    filteredProviders = await smartFilterProviders(providers, buyerId, job);
  }

  // Rank providers
  const rankedProviders = rankProviders(filteredProviders, options.providerLimit ?? 10);

  // Batch cache all providers
  await providerCache.batchCache(rankedProviders);
  console.log(`[Redis] Cached ${rankedProviders.length} providers`);

  // ⚡ STEP 3: Stream scoring
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
      useMem0Learning: useMem0,
      streamCallback: options.streamCallback,
    });

    if (!outcome?.quote) continue;

    const lastSellerMessage = Array.isArray(outcome.transcript)
      ? outcome.transcript.filter((m) => m.role === 'seller').pop()?.message
      : null;

    // Get provider details from cache
    const basic = await providerCache.getBasicCached(
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
      
      // 🧠 STEP 4a: Store rejection in Mem0
      if (useMem0 && buyerId) {
        await semanticMemory.storeBuyerNegotiation(buyerId, job.id, {
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

    // 🧠 STEP 4b: Store successful quote in Mem0
    if (useMem0 && buyerId) {
      await semanticMemory.storeBuyerNegotiation(buyerId, job.id, {
        job: jobSnippet,
        quote: outcome.quote,
        providerId: String(providerId),
        conversation: outcome.transcript,
        outcome: 'presented'
      });

      await semanticMemory.storeProviderNegotiation(String(providerId), job.id, {
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
    mem0_used: useMem0 && buyerId ? true : false,
  };
}

/* ---------------- SCORING HELPER ---------------- */

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

/* ---------------- ALIAS ---------------- */

export async function runNegotiationAndMatch(job, buyerAccessToken, options = {}) {
  return runMatchAndRecommend(job, buyerAccessToken, options);
}

/* ---------------- TRANSCRIPT HELPER ---------------- */

function transcriptToSteps(transcript = [], quote = null) {
  return (transcript || []).map((entry, i) => {
    const isLast = i === transcript.length - 1;
    const isSeller = entry.role === 'seller';
    const round = Math.floor(i / 2) + 1;
    const step = {
      role: entry.role,
      round,
      action: isLast && isSeller ? 'accept' : 'counter',
      message: entry.message,
    };
    if (isLast && isSeller && quote) {
      step.price = quote.price;
      step.completionDays = quote.days ?? quote.completionDays;
      step.paymentSchedule = quote.paymentSchedule;
      step.can_meet_dates = quote.can_meet_dates;
      step.licensed = quote.licensed;
      step.referencesAvailable = quote.referencesAvailable;
    }
    return step;
  });
}

/* ---------------- STREAMING VERSION ---------------- */

export async function runNegotiationAndMatchStream(job, buyerAccessToken, options = {}, send) {
  const service_category_id = Number(job?.service_category_id);
  if (!service_category_id) {
    if (typeof send === 'function') send({ type: 'done', deals: [], error: 'Job must have service_category_id.' });
    return;
  }

  const buyerId = job.buyer_id || options.buyerId;
  const useMem0 = options.useMem0 !== false;

  if (Array.isArray(job.priorities)) {
    job.priorities = parsePriorities(job.priorities);
  }

  // 🧠 Enhance job priorities using Mem0
  if (useMem0 && buyerId) {
    job = await enhanceJobPriorities(job, buyerId);
  }

  const maxRounds = Math.min(options.maxRounds ?? 1, 2);
  const timeLimitSeconds = job.agent_time_limit_seconds ?? options.timeSeconds ?? options.negotiationTimeSeconds ?? NEGOTIATION_TIME_SECONDS ?? 60;
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

  const { providers, error } = await fetchProvidersByCategory(buyerAccessToken, service_category_id);
  if (error || !providers?.length) {
    if (typeof send === 'function') send({ type: 'done', deals: [], error: error ?? 'No providers found.' });
    return;
  }

  // 🧠 Smart filter providers
  let filteredProviders = providers;
  if (useMem0 && buyerId) {
    filteredProviders = await smartFilterProviders(providers, buyerId, job);
  }

  const rankedProviders = rankProviders(filteredProviders, options.providerLimit ?? 10);
  
  await providerCache.batchCache(rankedProviders);
  console.log(`[Redis] Cached ${rankedProviders.length} providers for streaming`);
  
  if (typeof send === 'function') send({ type: 'providers_fetched', count: rankedProviders.length });

  const topDeals = [];

  for (const provider of rankedProviders) {
    const providerId = provider?.provider_id ?? provider?.id;
    if (!providerId) continue;

    const basic = await providerCache.getBasicCached(
      providerId,
      async (id) => await fetchProviderBasicDetails(id)
    );
    
    const nameFromBasic = basic ? [basic.first_name, basic.last_name].filter(Boolean).join(' ').trim() : null;
    const providerName = nameFromBasic ?? provider?.name ?? provider?.first_name ?? provider?.provider_name ?? `Provider ${providerId}`;

    if (typeof send === 'function') send({ type: 'provider_start', providerId: String(providerId), providerName });

    const providerServiceData = getProviderServiceData(provider, job);
    
    const outcome = await runMatching({
      job: jobSnippet,
      providerId: String(providerId),
      providerServiceData,
      buyerId: buyerId,
      maxRounds,
      deadline_ts,
      useMem0Learning: useMem0,
      streamCallback: send, // Pass stream callback to negotiation graph
    });

    const steps = transcriptToSteps(outcome?.transcript, outcome?.quote);
    for (const step of steps) {
      if (typeof send === 'function') {
        const payload = {
          role: step.role,
          round: step.round,
          action: step.action,
          message: step.message,
          price: step.price,
          completionDays: step.completionDays,
        };
        if (step.paymentSchedule != null) payload.paymentSchedule = step.paymentSchedule;
        if (step.can_meet_dates != null) payload.can_meet_dates = step.can_meet_dates;
        if (step.licensed != null) payload.licensed = step.licensed;
        if (step.referencesAvailable != null) payload.referencesAvailable = step.referencesAvailable;
        send({ type: 'negotiation_step', providerId: String(providerId), providerName, step: payload });
      }
    }

    const status = outcome?.status === 'timeout' ? 'timeout' : 'accepted';
    const negotiatedPrice = outcome?.quote?.price ?? 0;
    const negotiatedCompletionDays = outcome?.quote?.days ?? outcome?.quote?.completionDays ?? 0;
    if (typeof send === 'function') {
      send({
        type: 'provider_done',
        providerId: String(providerId),
        providerName,
        outcome: { status, negotiatedPrice, negotiatedCompletionDays },
      });
    }

    const lastSellerMessage = Array.isArray(outcome?.transcript)
      ? outcome.transcript.filter((m) => m.role === 'seller').pop()?.message
      : null;

    if (outcome?.quote) {
      const scored = await scoreProvider({
        providerId: String(providerId),
        provider,
        quote: outcome.quote,
        negotiationMessage: lastSellerMessage ?? null,
        sellerName: providerName,
      }, job);

      if (scored.failedMustHave) {
        console.log(`[Skip] Provider ${providerId} failed must-have: ${scored.failureReason}`);
        
        if (useMem0 && buyerId) {
          await semanticMemory.storeBuyerNegotiation(buyerId, job.id, {
            job: jobSnippet,
            quote: outcome.quote,
            providerId: String(providerId),
            conversation: outcome.transcript,
            outcome: 'rejected_must_have'
          });
        }
        
        continue;
      }

      topDeals.push(scored);
      topDeals.sort((a, b) => b.matchScore - a.matchScore);
      if (topDeals.length > 5) {
        topDeals.pop();
      }

      if (useMem0 && buyerId) {
        await semanticMemory.storeBuyerNegotiation(buyerId, job.id, {
          job: jobSnippet,
          quote: outcome.quote,
          providerId: String(providerId),
          conversation: outcome.transcript,
          outcome: 'presented'
        });

        await semanticMemory.storeProviderNegotiation(String(providerId), job.id, {
          job: jobSnippet,
          quote: outcome.quote,
          buyerId: buyerId,
          outcome: 'quoted'
        });
      }
    }
  }

  const reply = topDeals.length > 0
    ? `Found ${topDeals.length} providers that match your priorities.`
    : 'No providers matched your must-have requirements.';

  if (typeof send === 'function') send({ 
    type: 'done', 
    deals: topDeals, 
    reply,
    mem0_used: useMem0 && buyerId ? true : false,
  });
  
  return { deals: topDeals, reply, mem0_used: useMem0 && buyerId ? true : false };
}

/* ---------------- 🧠 UPDATE OUTCOME (When buyer accepts/rejects) ---------------- */

export async function updateNegotiationOutcome(buyerId, jobId, providerId, outcome) {
  if (!buyerId || !jobId || !providerId) {
    console.warn('[Mem0] Missing required IDs for outcome update');
    return;
  }

  try {
    await semanticMemory.memory.add({
      messages: [
        {
          role: "user",
          content: `Final decision for job ${jobId} with provider ${providerId}: ${outcome}`
        }
      ],
      user_id: `buyer_${buyerId}`,
      metadata: {
        type: 'outcome_update',
        job_id: jobId,
        provider_id: providerId,
        final_outcome: outcome,
        timestamp: Date.now()
      }
    });

    await semanticMemory.memory.add({
      messages: [
        {
          role: "assistant",
          content: `Quote for job ${jobId} was ${outcome} by buyer`
        }
      ],
      user_id: `provider_${providerId}`,
      metadata: {
        type: 'outcome_update',
        job_id: jobId,
        buyer_id: buyerId,
        final_outcome: outcome,
        timestamp: Date.now()
      }
    });

    console.log(`[Mem0] ✅ Updated outcome: ${outcome} for job ${jobId}`);
  } catch (error) {
    console.error('[Mem0] ❌ Error updating outcome:', error.message);
  }
}

/* ---------------- CLEANUP UTILITIES ---------------- */

export async function cleanupJobNegotiations(jobId) {
  const pattern = `negotiation:${jobId}:*`;
  const keys = await redisClient.keys(pattern);
  
  if (keys.length > 0) {
    await redisClient.del(...keys);
    console.log(`[Redis] Cleaned up ${keys.length} keys for job ${jobId}`);
  }
  
  return { cleaned: keys.length };
}

export async function cleanupExpiredNegotiations() {
  const pattern = `negotiation:*:status`;
  const keys = await redisClient.keys(pattern);
  
  let cleaned = 0;
  for (const key of keys) {
    const ttl = await redisClient.ttl(key);
    if (ttl === -1) {
      const baseKey = key.replace(':status', '');
      const allKeys = await redisClient.keys(`${baseKey}*`);
      await redisClient.del(...allKeys);
      cleaned += allKeys.length;
    }
  }
  
  console.log(`[Redis] Cleaned up ${cleaned} expired keys`);
  return { cleaned };
}

/* ---------------- EXPORT FOR UNIFIED AGENT ---------------- */

export { providerCache };