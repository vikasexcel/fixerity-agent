import { fetchProvidersByCategory, fetchProviderBasicDetails } from './buyerMatchAgent.js';
import { runMatching } from './negotiationGraph.js';
import { NEGOTIATION_TIME_SECONDS } from '../config/index.js';

/* ---------------- PRIORITY PARSER ---------------- */

/**
 * Convert array-based priorities to nested object format
 * Input:  [{ type: "price", level: "must_have", value: "1300" }, ...]
 * Output: { must_have: { max_price: 1300, start_date: "..." }, nice_to_have: {...}, bonus: {...} }
 */
function parsePriorities(prioritiesArray = []) {
  const parsed = {
    must_have: {},
    nice_to_have: {},
    bonus: {}
  };

  for (const p of prioritiesArray) {
    const level = p.level; // "must_have" | "nice_to_have" | "bonus"
    const type = p.type;   // "price" | "startDate" | "endDate" | "rating" | "jobsCompleted" | "licensed" | "references"
    const value = p.value;

    if (!parsed[level]) continue; // Skip invalid levels

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
        // Unknown type, skip
        break;
    }
  }

  return parsed;
}

/* ---------------- PROVIDER DATA ---------------- */

/**
 * Extract and normalize provider service data including deadline_in_days
 */
function getProviderServiceData(provider, job) {
  return {
    average_rating: provider?.average_rating ?? provider?.rating ?? 0,
    total_completed_order: provider?.total_completed_order ?? provider?.jobsCompleted ?? 0,
    licensed: provider?.licensed !== false,
    referencesAvailable: (Number(provider?.num_of_rating ?? 0) || 0) > 0,
    // ✅ ADD deadline_in_days - this is the provider's typical completion time for this service
    deadline_in_days: provider?.deadline_in_days ?? 
                     provider?.service_deadline_days ?? 
                     provider?.completionDays ?? 
                     3, // fallback only if provider data doesn't have it
  };
}

/* ---------------- PROVIDER RANKING ---------------- */

/**
 * Rank providers by quality score to avoid always contacting same sellers
 * Considers: rating, completed jobs, licensing status
 */
function rankProviders(providers = [], limit = 10) {
  return providers
    .map(p => ({
      ...p,
      rankScore: 
        (p.average_rating ?? 0) * 10 +              // Rating weight (0-50 points)
        Math.min(p.total_completed_order ?? 0, 50) + // Jobs cap at 50 points
        (p.licensed ? 20 : 0)                        // Licensed bonus
    }))
    .sort((a, b) => b.rankScore - a.rankScore)       // Highest score first
    .slice(0, limit);
}

/* ---------------- MAIN ORCHESTRATOR ---------------- */

export async function runMatchAndRecommend(job, buyerAccessToken, options = {}) {
  const service_category_id = Number(job?.service_category_id);
  if (!service_category_id) {
    return { deals: [], reply: 'Job must have service_category_id.' };
  }

  // ✅ PARSE PRIORITIES IF ARRAY FORMAT
  if (Array.isArray(job.priorities)) {
    job.priorities = parsePriorities(job.priorities);
  }

  // 🔒 HARD LIMIT — CLIENT REQUIREMENT
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

  // ✅ RANK PROVIDERS BEFORE NEGOTIATING
  const rankedProviders = rankProviders(providers, options.providerLimit ?? 10);

  const results = [];

  for (const provider of rankedProviders) {
    const providerId = provider?.provider_id ?? provider?.id;
    if (!providerId) continue;

    // ✅ NOW INCLUDES deadline_in_days
    const providerServiceData = getProviderServiceData(provider, job);

    const outcome = await runMatching({
      job: jobSnippet,
      providerId: String(providerId),
      providerServiceData, // ✅ Contains deadline_in_days
      maxRounds,
      deadline_ts,
    });

    if (!outcome?.quote) continue;

    const lastSellerMessage = Array.isArray(outcome.transcript)
      ? outcome.transcript.filter((m) => m.role === 'seller').pop()?.message
      : null;

    results.push({
      providerId: String(providerId),
      provider,
      quote: outcome.quote,
      negotiationMessage: lastSellerMessage ?? null,
    });
  }

  /* ---------------- SCORING WITH MUST-HAVE ENFORCEMENT ---------------- */

  const scored = await Promise.all(
    results.map(async (r, idx) => {
      const basic = await fetchProviderBasicDetails(r.providerId);
      const sellerNameFromBasic = basic ? [basic.first_name, basic.last_name].filter(Boolean).join(' ').trim() : null;
      const sellerName = sellerNameFromBasic ?? r.provider?.name ?? r.provider?.first_name ?? 'Provider';

      // ✅ MUST HAVE (if ANY fail, matchScore = 0)
      const mustHavePrice = !job.priorities?.must_have?.max_price || r.quote.price <= job.priorities.must_have.max_price;
      const mustHaveDates = !job.priorities?.must_have?.start_date || r.quote.can_meet_dates !== false;
      const mustHavePass = mustHavePrice && mustHaveDates;

      if (!mustHavePass) {
        return {
          id: `deal_${job.id}_${r.providerId}_${idx}`,
          sellerId: r.providerId,
          sellerName,
          quote: r.quote,
          matchScore: 0, // ❌ FAILED MUST-HAVE
          negotiationMessage: r.negotiationMessage ?? null,
          failedMustHave: true,
          failureReason: !mustHavePrice ? 'Price exceeds max budget' : 'Cannot meet required dates',
        };
      }

      // ✅ NICE TO HAVE (20pts each)
      const ratingScore =
        r.provider.average_rating >= (job.priorities?.nice_to_have?.min_rating ?? 0) ? 20 : 0;
      
      const jobsScore = 
        r.provider.total_completed_order >= (job.priorities?.nice_to_have?.min_jobs_completed ?? 0) ? 20 : 0;

      // ✅ BONUS (10pts each)
      const bonusScore =
        (job.priorities?.bonus?.licensed && r.provider.licensed ? 10 : 0) +
        (job.priorities?.bonus?.references && r.provider.referencesAvailable ? 10 : 0);

      const matchScore = 40 + ratingScore + jobsScore + bonusScore; // Max = 100

      return {
        id: `deal_${job.id}_${r.providerId}_${idx}`,
        sellerId: r.providerId,
        sellerName,
        quote: r.quote,
        matchScore,
        negotiationMessage: r.negotiationMessage ?? null,
      };
    })
  );

  // ✅ FILTER OUT FAILED MUST-HAVES
  const topDeals = scored
    .filter(d => !d.failedMustHave)
    .sort((a, b) => b.matchScore - a.matchScore)
    .slice(0, 5);

  return {
    deals: topDeals,
    reply:
      topDeals.length > 0
        ? `Found ${topDeals.length} providers that match your priorities.`
        : 'No providers matched your must-have requirements.',
  };
}

/** Alias for runMatchAndRecommend (used by index.js). */
export async function runNegotiationAndMatch(job, buyerAccessToken, options = {}) {
  return runMatchAndRecommend(job, buyerAccessToken, options);
}

/**
 * Convert graph transcript (role + message) + quote into frontend NegotiationStep[].
 */
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

/** Stream version: runs match per provider and emits provider_start, negotiation_step, provider_done, then done with deals. */
export async function runNegotiationAndMatchStream(job, buyerAccessToken, options = {}, send) {
  const service_category_id = Number(job?.service_category_id);
  if (!service_category_id) {
    if (typeof send === 'function') send({ type: 'done', deals: [], error: 'Job must have service_category_id.' });
    return;
  }

  // ✅ PARSE PRIORITIES IF ARRAY FORMAT
  if (Array.isArray(job.priorities)) {
    job.priorities = parsePriorities(job.priorities);
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

  // ✅ RANK PROVIDERS BEFORE NEGOTIATING
  const rankedProviders = rankProviders(providers, options.providerLimit ?? 10);
  
  if (typeof send === 'function') send({ type: 'providers_fetched', count: rankedProviders.length });

  const results = [];
  for (const provider of rankedProviders) {
    const providerId = provider?.provider_id ?? provider?.id;
    if (!providerId) continue;
    const basic = await fetchProviderBasicDetails(providerId);
    const nameFromBasic = basic ? [basic.first_name, basic.last_name].filter(Boolean).join(' ').trim() : null;
    const providerName = nameFromBasic ?? provider?.name ?? provider?.first_name ?? provider?.provider_name ?? `Provider ${providerId}`;

    if (typeof send === 'function') send({ type: 'provider_start', providerId: String(providerId), providerName });

    // ✅ NOW INCLUDES deadline_in_days
    const providerServiceData = getProviderServiceData(provider, job);
    
    const outcome = await runMatching({
      job: jobSnippet,
      providerId: String(providerId),
      providerServiceData, // ✅ Contains deadline_in_days
      maxRounds,
      deadline_ts,
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
      results.push({
        providerId: String(providerId),
        provider,
        quote: outcome.quote,
        negotiationMessage: lastSellerMessage ?? null,
      });
    }
  }

  /* ---------------- SAME SCORING AS runMatchAndRecommend ---------------- */
  
  const scored = await Promise.all(
    results.map(async (r, idx) => {
      const basic = await fetchProviderBasicDetails(r.providerId);
      const sellerNameFromBasic = basic ? [basic.first_name, basic.last_name].filter(Boolean).join(' ').trim() : null;
      const sellerName = sellerNameFromBasic ?? r.provider?.name ?? r.provider?.first_name ?? 'Provider';

      // ✅ MUST HAVE (if ANY fail, matchScore = 0)
      const mustHavePrice = !job.priorities?.must_have?.max_price || r.quote.price <= job.priorities.must_have.max_price;
      const mustHaveDates = !job.priorities?.must_have?.start_date || r.quote.can_meet_dates !== false;
      const mustHavePass = mustHavePrice && mustHaveDates;

      if (!mustHavePass) {
        return {
          id: `deal_${job.id}_${r.providerId}_${idx}`,
          sellerId: r.providerId,
          sellerName,
          quote: r.quote,
          matchScore: 0,
          negotiationMessage: r.negotiationMessage ?? null,
          failedMustHave: true,
          failureReason: !mustHavePrice ? 'Price exceeds max budget' : 'Cannot meet required dates',
        };
      }

      // ✅ NICE TO HAVE (20pts each)
      const ratingScore =
        r.provider.average_rating >= (job.priorities?.nice_to_have?.min_rating ?? 0) ? 20 : 0;
      
      const jobsScore = 
        r.provider.total_completed_order >= (job.priorities?.nice_to_have?.min_jobs_completed ?? 0) ? 20 : 0;

      // ✅ BONUS (10pts each)
      const bonusScore =
        (job.priorities?.bonus?.licensed && r.provider.licensed ? 10 : 0) +
        (job.priorities?.bonus?.references && r.provider.referencesAvailable ? 10 : 0);

      const matchScore = 40 + ratingScore + jobsScore + bonusScore; // Max = 100

      return {
        id: `deal_${job.id}_${r.providerId}_${idx}`,
        sellerId: r.providerId,
        sellerName,
        quote: r.quote,
        matchScore,
        negotiationMessage: r.negotiationMessage ?? null,
      };
    })
  );
  
  // ✅ FILTER OUT FAILED MUST-HAVES
  const topDeals = scored
    .filter(d => !d.failedMustHave)
    .sort((a, b) => b.matchScore - a.matchScore)
    .slice(0, 5);
    
  const reply =
    topDeals.length > 0
      ? `Found ${topDeals.length} providers that match your priorities.`
      : 'No providers matched your must-have requirements.';

  if (typeof send === 'function') send({ type: 'done', deals: topDeals, reply });
  return { deals: topDeals, reply };
}