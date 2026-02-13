import { tool } from '@langchain/core/tools';
import { z } from 'zod';
import prisma from '../../prisma/client.js';
import { getSellerProfileTool } from '../seller/sellerTools.js';

/* ================================================================================
   BUYER TOOLS - Tools for matching a job to sellers (provider discovery from SellerProfile)
   ================================================================================ */

/** Match job service name to seller's service names (same logic as jobMatchingGraph). */
function sellerMatchesJobService(jobServiceName, sellerServiceNames) {
  if (!jobServiceName || !sellerServiceNames?.length) return false;
  const jobNorm = String(jobServiceName).trim().toLowerCase();
  if (!jobNorm) return false;
  return sellerServiceNames.some((name) => {
    const n = (name || '').trim().toLowerCase();
    if (!n) return false;
    return n === jobNorm || n.includes(jobNorm) || jobNorm.includes(n);
  });
}

/**
 * List seller profiles that match the job's service category.
 * Used by the buyer matching LLM to get candidates for ranking.
 */
export const listSellerProfilesForJobTool = tool(
  async ({ service_category_name, limit = 20 }) => {
    const name = service_category_name != null ? String(service_category_name).trim() : '';
    if (!name) {
      return JSON.stringify({ sellers: [], error: 'service_category_name is required.' });
    }
    const cap = Math.min(Math.max(Number(limit) || 20, 1), 50);

    try {
      const all = await prisma.sellerProfile.findMany({
        where: { active: true },
        take: 200,
      });
      const matched = all.filter((p) =>
        sellerMatchesJobService(name, p.serviceCategoryNames ?? [])
      );
      const sliced = matched.slice(0, cap);

      const sellers = sliced.map((p) => ({
        seller_id: p.id,
        service_category_names: p.serviceCategoryNames ?? [],
        credentials: p.credentials,
        pricing: p.pricing,
        bio: p.bio ? String(p.bio).slice(0, 200) + (p.bio.length > 200 ? '...' : '') : null,
        profile_completeness_score: p.profileCompletenessScore ?? 0,
        total_bids_accepted: p.totalBidsAccepted ?? 0,
        total_bids_submitted: p.totalBidsSubmitted ?? 0,
      }));

      console.log(`[list_seller_profiles_for_job] service="${name}" -> ${sellers.length} sellers`);
      return JSON.stringify({ sellers, count: sellers.length });
    } catch (error) {
      console.error('[list_seller_profiles_for_job] Error:', error.message);
      return JSON.stringify({ sellers: [], error: error.message });
    }
  },
  {
    name: 'list_seller_profiles_for_job',
    description: 'Get seller profiles that offer the given service. Call with the job\'s service_category_name to find candidate providers. Returns seller_id, credentials, pricing, bio, and stats.',
    schema: z.object({
      service_category_name: z.string().describe('Service category name (e.g. from the job).'),
      limit: z.number().optional().describe('Max number of sellers to return (default 20, max 50).'),
    }),
  }
);

/** Reuse seller agent tool for fetching one profile (read-only). */
export const getSellerProfileForBuyerTool = getSellerProfileTool;

export const buyerTools = [
  listSellerProfilesForJobTool,
  getSellerProfileForBuyerTool,
];
