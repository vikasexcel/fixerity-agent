/**
 * Orchestrator: fetches providers for a job, runs negotiation graph per provider,
 * then builds and returns deals with negotiated price and completion time.
 */

import { fetchProvidersByCategory, fetchProviderBasicDetails } from './buyerMatchAgent.js';
import { runNegotiation, runNegotiationStream } from './negotiationGraph.js';
import { NEGOTIATION_MAX_ROUNDS, NEGOTIATION_TIME_SECONDS } from '../config/index.js';

/**
 * Build provider service data for negotiation from provider object or job defaults.
 * @param {Object} provider - Provider from fetchProvidersByCategory (may have min_price, max_price, deadline_in_days)
 * @param {Object} job - Job with budget
 * @returns {Object} { min_price, max_price, deadline_in_days, average_rating?, total_completed_order? }
 */
function getProviderServiceDataForNegotiation(provider, job) {
  const budget = job?.budget ?? { min: 0, max: 999999 };
  const min = Number(provider?.min_price ?? budget.min ?? 0);
  const max = Number(provider?.max_price ?? budget.max ?? 999999);
  const days = Number(provider?.deadline_in_days ?? provider?.deadline_in_days ?? 7);
  return {
    min_price: min,
    max_price: max,
    deadline_in_days: days,
    average_rating: provider?.average_rating ?? provider?.rating ?? 0,
    total_completed_order: provider?.total_completed_order ?? provider?.jobsCompleted ?? 0,
  };
}

/**
 * Run negotiation for all providers and return ranked deals with negotiated terms.
 * @param {Object} job - Job with id, title, budget, startDate, endDate, priorities, service_category_id
 * @param {string} buyerAccessToken - Buyer's access token
 * @param {Object} [options] - { maxRounds?, negotiationTimeSeconds? }
 * @returns {Promise<{ deals: Array, reply?: string }>}
 */
export async function runNegotiationAndMatch(job, buyerAccessToken, options = {}) {
  const service_category_id = Number(job?.service_category_id) || 0;
  if (!service_category_id) {
    return { deals: [], message: 'Job must have service_category_id to match providers.' };
  }

  const maxRounds = options.maxRounds ?? NEGOTIATION_MAX_ROUNDS ?? 5;
  const timeSeconds = options.negotiationTimeSeconds ?? NEGOTIATION_TIME_SECONDS ?? 60;
  const deadline_ts = Date.now() + timeSeconds * 1000;

  const jobSnippet = {
    id: job.id,
    title: job.title ?? 'Job',
    description: job.description ?? '',
    budget: job.budget ?? { min: 0, max: 999999 },
    startDate: job.startDate ?? '',
    endDate: job.endDate ?? '',
    priorities: job.priorities ?? [],
    service_category_id,
  };

  const { providers, error } = await fetchProvidersByCategory(buyerAccessToken, service_category_id);
  if (error || !Array.isArray(providers) || providers.length === 0) {
    return { deals: [], reply: error ? `No providers: ${error}` : 'No providers found for this category.' };
  }

  const limit = Math.min(providers.length, options.providerLimit ?? 10);
  const toProcess = providers.slice(0, limit);

  const results = [];
  for (const provider of toProcess) {
    const providerId = provider?.provider_id ?? provider?.id ?? provider?.userId;
    if (!providerId) continue;
    const providerServiceData = getProviderServiceDataForNegotiation(provider, job);
    try {
      const outcome = await runNegotiation({
        job: jobSnippet,
        providerId: String(providerId),
        providerServiceData,
        maxRounds,
        deadline_ts,
      });
      results.push({
        providerId: String(providerId),
        provider,
        outcome,
      });
    } catch (err) {
      console.error(`[NegotiationOrchestrator] negotiation failed for provider ${providerId}:`, err?.message);
      results.push({
        providerId: String(providerId),
        provider,
        outcome: {
          status: 'timeout',
          negotiatedPrice: providerServiceData.min_price ?? jobSnippet.budget?.min ?? 0,
          negotiatedCompletionDays: providerServiceData.deadline_in_days ?? 7,
        },
      });
    }
  }

  const deals = await Promise.all(
    results.map(async (r, idx) => {
      const { providerId, provider, outcome } = r;
      const basic = await fetchProviderBasicDetails(providerId);
      const name = basic
        ? [basic.first_name, basic.last_name].filter(Boolean).join(' ').trim()
        : (provider?.name ?? provider?.first_name ?? 'Provider');
      const rating = Number(provider?.average_rating ?? provider?.rating ?? 0);
      const jobsCompleted = Number(provider?.total_completed_order ?? provider?.jobsCompleted ?? 0);
      const matchScore = Math.min(100, 50 + Math.round((outcome.negotiatedCompletionDays <= 7 ? 15 : 0) + (outcome.status === 'accepted' ? 20 : 0)));
      const sellerAgent = {
        id: `agent_${providerId}`,
        userId: providerId,
        name: name || 'Provider',
        type: 'seller',
        rating,
        jobsCompleted,
        licensed: provider?.licensed !== false,
        references: (Number(provider?.num_of_rating ?? 0) || 0) > 0,
        createdAt: new Date().toISOString().split('T')[0],
      };
      if (basic) {
        sellerAgent.email = basic.email;
        sellerAgent.contact_number = basic.contact_number;
        sellerAgent.providerBasicDetails = basic;
      }
      return {
        id: `deal_${job.id}_${providerId}_${idx}`,
        jobId: job.id,
        sellerId: providerId,
        sellerAgent,
        matchScore,
        matchReasons: outcome.status === 'accepted'
          ? ['Negotiation agreed', `Price $${outcome.negotiatedPrice}`, `${outcome.negotiatedCompletionDays} days`]
          : ['Terms negotiated within time limit', `Price $${outcome.negotiatedPrice}`, `${outcome.negotiatedCompletionDays} days`],
        status: 'proposed',
        createdAt: new Date().toISOString().split('T')[0],
        job: jobSnippet,
        negotiatedPrice: outcome.negotiatedPrice,
        negotiatedCompletionDays: outcome.negotiatedCompletionDays,
        negotiationStatus: outcome.status,
      };
    })
  );

  const sorted = deals.sort((a, b) => (b.matchScore ?? 0) - (a.matchScore ?? 0));
  const topDeals = sorted.slice(0, 5);

  const reply = topDeals.length > 0
    ? `Found ${topDeals.length} provider(s) with negotiated terms. Best: ${topDeals[0]?.sellerAgent?.name ?? 'N/A'} at $${topDeals[0]?.negotiatedPrice ?? '—'} in ${topDeals[0]?.negotiatedCompletionDays ?? '—'} days.`
    : 'No deals after negotiation.';

  return { deals: topDeals, reply };
}

/**
 * Same as runNegotiationAndMatch but streams events to onEvent for live UI.
 * Events: { type: 'providers_fetched', count }, { type: 'provider_start', providerId, providerName },
 *   { type: 'negotiation_step', providerId, providerName, step }, { type: 'provider_done', providerId, providerName, outcome },
 *   { type: 'done', deals }.
 * @param {Object} job
 * @param {string} buyerAccessToken
 * @param {Object} options - { maxRounds?, negotiationTimeSeconds?, providerLimit? }
 * @param {function(object): void} onEvent
 */
export async function runNegotiationAndMatchStream(job, buyerAccessToken, options = {}, onEvent) {
  const emit = (event) => {
    if (typeof onEvent === 'function') onEvent(event);
  };

  const service_category_id = Number(job?.service_category_id) || 0;
  if (!service_category_id) {
    emit({ type: 'done', deals: [], error: 'Job must have service_category_id to match providers.' });
    return { deals: [], reply: 'Job must have service_category_id.' };
  }

  const maxRounds = options.maxRounds ?? NEGOTIATION_MAX_ROUNDS ?? 5;
  const timeSeconds = options.negotiationTimeSeconds ?? NEGOTIATION_TIME_SECONDS ?? 60;
  const deadline_ts = Date.now() + timeSeconds * 1000;

  const jobSnippet = {
    id: job.id,
    title: job.title ?? 'Job',
    description: job.description ?? '',
    budget: job.budget ?? { min: 0, max: 999999 },
    startDate: job.startDate ?? '',
    endDate: job.endDate ?? '',
    priorities: job.priorities ?? [],
    service_category_id,
  };

  const { providers, error } = await fetchProvidersByCategory(buyerAccessToken, service_category_id);
  if (error || !Array.isArray(providers) || providers.length === 0) {
    emit({ type: 'done', deals: [], error: error ? `No providers: ${error}` : 'No providers found.' });
    return { deals: [], reply: error ? `No providers: ${error}` : 'No providers found for this category.' };
  }

  emit({ type: 'providers_fetched', count: providers.length });

  const limit = Math.min(providers.length, options.providerLimit ?? 10);
  const toProcess = providers.slice(0, limit);
  const results = [];

  for (const provider of toProcess) {
    const providerId = provider?.provider_id ?? provider?.id ?? provider?.userId;
    if (!providerId) continue;

    const basic = await fetchProviderBasicDetails(providerId);
    const providerName = basic
      ? [basic.first_name, basic.last_name].filter(Boolean).join(' ').trim()
      : (provider?.name ?? provider?.first_name ?? `Provider ${providerId}`);

    emit({ type: 'provider_start', providerId: String(providerId), providerName });

    const providerServiceData = getProviderServiceDataForNegotiation(provider, job);
    try {
      const outcome = await runNegotiationStream(
        {
          job: jobSnippet,
          providerId: String(providerId),
          providerServiceData,
          maxRounds,
          deadline_ts,
        },
        (step) => emit({ type: 'negotiation_step', providerId: String(providerId), providerName, step })
      );
      results.push({ providerId: String(providerId), provider, outcome });
      emit({ type: 'provider_done', providerId: String(providerId), providerName, outcome });
    } catch (err) {
      console.error(`[NegotiationOrchestrator] negotiation failed for provider ${providerId}:`, err?.message);
      const outcome = {
        status: 'timeout',
        negotiatedPrice: providerServiceData.min_price ?? jobSnippet.budget?.min ?? 0,
        negotiatedCompletionDays: providerServiceData.deadline_in_days ?? 7,
      };
      results.push({ providerId: String(providerId), provider, outcome });
      emit({ type: 'provider_done', providerId: String(providerId), providerName, outcome });
    }
  }

  const deals = await Promise.all(
    results.map(async (r, idx) => {
      const { providerId, provider, outcome } = r;
      const basic = await fetchProviderBasicDetails(providerId);
      const name = basic
        ? [basic.first_name, basic.last_name].filter(Boolean).join(' ').trim()
        : (provider?.name ?? provider?.first_name ?? 'Provider');
      const rating = Number(provider?.average_rating ?? provider?.rating ?? 0);
      const jobsCompleted = Number(provider?.total_completed_order ?? provider?.jobsCompleted ?? 0);
      const matchScore = Math.min(100, 50 + Math.round((outcome.negotiatedCompletionDays <= 7 ? 15 : 0) + (outcome.status === 'accepted' ? 20 : 0)));
      const sellerAgent = {
        id: `agent_${providerId}`,
        userId: providerId,
        name: name || 'Provider',
        type: 'seller',
        rating,
        jobsCompleted,
        licensed: provider?.licensed !== false,
        references: (Number(provider?.num_of_rating ?? 0) || 0) > 0,
        createdAt: new Date().toISOString().split('T')[0],
      };
      if (basic) {
        sellerAgent.email = basic.email;
        sellerAgent.contact_number = basic.contact_number;
        sellerAgent.providerBasicDetails = basic;
      }
      return {
        id: `deal_${job.id}_${providerId}_${idx}`,
        jobId: job.id,
        sellerId: providerId,
        sellerAgent,
        matchScore,
        matchReasons: outcome.status === 'accepted'
          ? ['Negotiation agreed', `Price $${outcome.negotiatedPrice}`, `${outcome.negotiatedCompletionDays} days`]
          : ['Terms negotiated within time limit', `Price $${outcome.negotiatedPrice}`, `${outcome.negotiatedCompletionDays} days`],
        status: 'proposed',
        createdAt: new Date().toISOString().split('T')[0],
        job: jobSnippet,
        negotiatedPrice: outcome.negotiatedPrice,
        negotiatedCompletionDays: outcome.negotiatedCompletionDays,
        negotiationStatus: outcome.status,
      };
    })
  );

  const sorted = deals.sort((a, b) => (b.matchScore ?? 0) - (a.matchScore ?? 0));
  const topDeals = sorted.slice(0, 5);
  emit({ type: 'done', deals: topDeals });
  return { deals: topDeals };
}
