import JobMatchQuote from '../models/JobMatchQuote.js';

/**
 * Save deals from negotiate-and-match to MySQL (upsert by job_id + user_id + provider_id).
 * @param {number} userId - Buyer user id
 * @param {string} jobId - Job id
 * @param {Array<{ id: string, sellerId: string, sellerName?: string, matchScore: number, quote: { price?, days?, paymentSchedule?, licensed?, referencesAvailable? }, negotiationMessage?: string }>} deals
 */
export async function saveDealsForJob(userId, jobId, deals) {
  if (!Array.isArray(deals) || !jobId || userId == null) return;
  const jobIdStr = String(jobId);
  const userIdNum = Number(userId);
  for (const d of deals) {
    const providerId = d.sellerId ?? d.provider_id;
    if (!providerId) continue;
    try {
      const [row] = await JobMatchQuote.findOrCreate({
        where: { job_id: jobIdStr, user_id: userIdNum, provider_id: String(providerId) },
        defaults: {
          job_id: jobIdStr,
          user_id: userIdNum,
          provider_id: String(providerId),
          seller_name: d.sellerName ?? d.seller_name ?? null,
          match_score: d.matchScore ?? d.match_score ?? null,
          negotiation_message: d.negotiationMessage ?? d.negotiation_message ?? null,
          quote_price: d.quote?.price != null ? Number(d.quote.price) : null,
          quote_days: d.quote?.days ?? d.quote?.completionDays ?? null,
          payment_schedule: d.quote?.paymentSchedule ?? null,
          licensed: d.quote?.licensed ?? null,
          references_available: d.quote?.referencesAvailable ?? null,
        },
      });
      const updated = {
        seller_name: d.sellerName ?? d.seller_name ?? null,
        match_score: d.matchScore ?? d.match_score ?? null,
        negotiation_message: d.negotiationMessage ?? d.negotiation_message ?? null,
        quote_price: d.quote?.price != null ? Number(d.quote.price) : null,
        quote_days: d.quote?.days ?? d.quote?.completionDays ?? null,
        payment_schedule: d.quote?.paymentSchedule ?? null,
        licensed: d.quote?.licensed ?? null,
        references_available: d.quote?.referencesAvailable ?? null,
      };
      await row.update(updated);
    } catch (err) {
      console.error('[JobMatchQuote] save failed for', jobIdStr, providerId, err.message);
    }
  }
}

/**
 * Load stored match quotes for a job from MySQL and return as deal-shaped array for API.
 * @param {number} userId
 * @param {string} jobId
 * @returns {Promise<Array>} Deals with id, sellerId, sellerName, matchScore, quote, negotiationMessage, etc.
 */
export async function getDealsForJob(userId, jobId) {
  if (!jobId || userId == null) return [];
  const rows = await JobMatchQuote.findAll({
    where: { job_id: String(jobId), user_id: Number(userId) },
    order: [['match_score', 'DESC'], ['created_at', 'DESC']],
  });
  return rows.map((r, idx) => ({
    id: `deal_${jobId}_${r.provider_id}_${idx}`,
    sellerId: r.provider_id,
    sellerName: r.seller_name ?? 'Provider',
    matchScore: r.match_score ?? 0,
    negotiatedPrice: r.quote_price != null ? Number(r.quote_price) : undefined,
    negotiatedCompletionDays: r.quote_days ?? undefined,
    quote: {
      price: r.quote_price != null ? Number(r.quote_price) : undefined,
      days: r.quote_days ?? undefined,
      paymentSchedule: r.payment_schedule ?? undefined,
      licensed: r.licensed ?? undefined,
      referencesAvailable: r.references_available ?? undefined,
    },
    negotiationMessage: r.negotiation_message ?? undefined,
  }));
}
