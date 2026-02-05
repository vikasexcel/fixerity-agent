import { fetchProvidersByCategory, fetchProviderBasicDetails } from './buyerMatchAgent.js';
import { runMatching } from './negotiationGraph.js';
import { NEGOTIATION_TIME_SECONDS } from '../config/index.js';

/* ---------------- PROVIDER DATA ---------------- */

function getProviderServiceData(provider, job) {
  return {
    average_rating: provider?.average_rating ?? provider?.rating ?? 0,
    total_completed_order: provider?.total_completed_order ?? provider?.jobsCompleted ?? 0,
    licensed: provider?.licensed !== false,
    referencesAvailable: (Number(provider?.num_of_rating ?? 0) || 0) > 0,
  };
}

/* ---------------- MAIN ORCHESTRATOR ---------------- */

export async function runMatchAndRecommend(job, buyerAccessToken, options = {}) {
  const service_category_id = Number(job?.service_category_id);
  if (!service_category_id) {
    return { deals: [], reply: 'Job must have service_category_id.' };
  }

  // 🔒 HARD LIMIT — CLIENT REQUIREMENT
  const maxRounds = Math.min(options.maxRounds ?? 1, 2);
  const deadline_ts = Date.now() + (options.timeSeconds ?? NEGOTIATION_TIME_SECONDS ?? 60) * 1000;

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

  const results = [];

  for (const provider of providers.slice(0, options.providerLimit ?? 10)) {
    const providerId = provider?.provider_id ?? provider?.id;
    if (!providerId) continue;

    const providerServiceData = getProviderServiceData(provider, job);

    const outcome = await runMatching({
      job: jobSnippet,
      providerId: String(providerId),
      providerServiceData,
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

  /* ---------------- SCORING ---------------- */

  const scored = await Promise.all(
    results.map(async (r, idx) => {
      const basic = await fetchProviderBasicDetails(r.providerId);

      const priceScore =
        job.priorities?.must_have?.max_price &&
        r.quote.price <= job.priorities.must_have.max_price
          ? 40
          : 0;

      const ratingScore =
        r.provider.average_rating >= (job.priorities?.nice_to_have?.min_rating ?? 0)
          ? 20
          : 0;

      const bonusScore =
        (job.priorities?.bonus?.licensed && r.provider.licensed ? 10 : 0) +
        (job.priorities?.bonus?.references && r.provider.referencesAvailable ? 10 : 0);

      const matchScore = priceScore + ratingScore + bonusScore;

      return {
        id: `deal_${job.id}_${r.providerId}_${idx}`,
        sellerId: r.providerId,
        sellerName: basic?.first_name ?? r.provider.name ?? 'Provider',
        quote: r.quote,
        matchScore,
        negotiationMessage: r.negotiationMessage ?? null,
      };
    })
  );

  const topDeals = scored
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

  const maxRounds = Math.min(options.maxRounds ?? 1, 2);
  const deadline_ts = Date.now() + (options.timeSeconds ?? options.negotiationTimeSeconds ?? NEGOTIATION_TIME_SECONDS ?? 60) * 1000;
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

  const list = providers.slice(0, options.providerLimit ?? 10);
  if (typeof send === 'function') send({ type: 'providers_fetched', count: list.length });

  const results = [];
  for (const provider of list) {
    const providerId = provider?.provider_id ?? provider?.id;
    if (!providerId) continue;
    const providerName = provider?.name ?? provider?.first_name ?? `Provider ${providerId}`;

    if (typeof send === 'function') send({ type: 'provider_start', providerId: String(providerId), providerName });

    const providerServiceData = getProviderServiceData(provider, job);
    const outcome = await runMatching({
      job: jobSnippet,
      providerId: String(providerId),
      providerServiceData,
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

  /* same scoring as runMatchAndRecommend */
  const scored = await Promise.all(
    results.map(async (r, idx) => {
      const basic = await fetchProviderBasicDetails(r.providerId);
      const priceScore =
        job.priorities?.must_have?.max_price && r.quote.price <= job.priorities.must_have.max_price ? 40 : 0;
      const ratingScore =
        r.provider.average_rating >= (job.priorities?.nice_to_have?.min_rating ?? 0) ? 20 : 0;
      const bonusScore =
        (job.priorities?.bonus?.licensed && r.provider.licensed ? 10 : 0) +
        (job.priorities?.bonus?.references && r.provider.referencesAvailable ? 10 : 0);
      const matchScore = priceScore + ratingScore + bonusScore;
      return {
        id: `deal_${job.id}_${r.providerId}_${idx}`,
        sellerId: r.providerId,
        sellerName: basic?.first_name ?? r.provider.name ?? 'Provider',
        quote: r.quote,
        matchScore,
        negotiationMessage: r.negotiationMessage ?? null,
      };
    })
  );
  const topDeals = scored.sort((a, b) => b.matchScore - a.matchScore).slice(0, 5);
  const reply =
    topDeals.length > 0
      ? `Found ${topDeals.length} providers that match your priorities.`
      : 'No providers matched your must-have requirements.';

  if (typeof send === 'function') send({ type: 'done', deals: topDeals, reply });
  return { deals: topDeals, reply };
}
