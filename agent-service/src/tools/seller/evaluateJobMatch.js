/**
 * Seller tool: Evaluate how well a seller profile matches a job's requirements.
 *
 * This is a server-side evaluation tool that calculates match scores.
 * Similar to buyerMatchAgent's evaluateAndRank but evaluates seller-to-job matching.
 *
 * Required: job_json (job object), provider_profile_json (seller profile object)
 *
 * Use when calculating match scores between seller capabilities and job requirements.
 */

import { tool } from '@langchain/core/tools';
import { z } from 'zod';

const schema = z.object({
  job_json: z.string().describe('JSON string of the job object with id, title, budget, priorities, etc.'),
  provider_profile_json: z.string().describe('JSON string of the provider profile with rating, packages, location, etc.'),
});

/**
 * Evaluate a single job against seller profile.
 * @param {Object} job - Job object with priorities, budget, etc.
 * @param {Object} providerProfile - Provider profile with rating, packages, etc.
 * @returns {{ score: number; reasons: string[] }}
 */
function evaluateJobMatch(job, providerProfile) {
  let baseScore = 100;
  const reasons = [];
  const jobPriorities = job.priorities || [];
  const maxBudget = job.budget?.max ?? 999999;

  const providerRating = parseFloat(providerProfile.average_rating) || 0;
  const jobsCompleted = parseInt(providerProfile.total_completed_order, 10) || 0;
  const hourlyRate = providerProfile.package_list?.[0]?.package_price
    ? parseFloat(providerProfile.package_list[0].package_price)
    : 0;
  const hasReferences = (parseInt(providerProfile.num_of_rating, 10) || 0) > 0;
  const licensed = providerProfile.licensed !== false;

  for (const priority of jobPriorities) {
    let matched = false;
    let reason = '';

    switch (priority.type) {
      case 'price': {
        const providerMonthlyRate = (hourlyRate || 0) * 160;
        matched = providerMonthlyRate <= maxBudget;
        reason = matched
          ? `Price within budget ($${maxBudget})`
          : `Rate exceeds budget ($${providerMonthlyRate.toFixed(0)} estimated)`;
        break;
      }
      case 'startDate':
      case 'endDate':
        matched = true;
        reason = `Available for dates`;
        break;
      case 'rating': {
        const required = Number(priority.value) || 4;
        matched = providerRating >= required;
        reason = matched
          ? `Rating ${providerRating}★ meets ${required}★`
          : `Rating ${providerRating}★ below ${required}★`;
        break;
      }
      case 'jobsCompleted': {
        const required = Number(priority.value) || 10;
        matched = jobsCompleted >= required;
        reason = matched
          ? `${jobsCompleted} jobs exceeds ${required}`
          : `${jobsCompleted} jobs below ${required}`;
        break;
      }
      case 'licensed':
        matched = licensed;
        reason = licensed ? 'Licensed' : 'Not licensed';
        break;
      case 'references':
        matched = hasReferences;
        reason = hasReferences ? 'References available' : 'No references';
        break;
      default:
        matched = false;
        reason = 'Unknown requirement';
    }

    reasons.push(matched ? `✓ ${reason}` : `✗ ${reason}`);
    if (priority.level === 'bonus' && matched) baseScore += 5;
    if (priority.level === 'must_have' && !matched) baseScore -= 25;
    if (priority.level === 'nice_to_have' && !matched) baseScore -= 10;
  }

  const finalScore = Math.max(0, Math.min(100, baseScore));
  return { score: finalScore, reasons };
}

/**
 * @param {typeof import('../../lib/laravelClient.js').post} laravelClient
 * @param {number} [providerId]
 * @param {string} [accessToken]
 * @returns {import('@langchain/core/tools').StructuredToolInterface}
 */
export function createEvaluateJobMatchTool(laravelClient, providerId, accessToken) {
  return tool(
    async (input) => {
      try {
        const job = typeof input.job_json === 'string' ? JSON.parse(input.job_json) : input.job_json;
        const providerProfile = typeof input.provider_profile_json === 'string' 
          ? JSON.parse(input.provider_profile_json) 
          : input.provider_profile_json;

        const { score, reasons } = evaluateJobMatch(job, providerProfile);

        return JSON.stringify({
          job_id: job.id,
          match_score: Math.round(score),
          match_reasons: reasons,
          provider_id: providerId,
        });
      } catch (err) {
        return `Failed to evaluate job match: ${err.message}`;
      }
    },
    {
      name: 'evaluateJobMatch',
      description: 'Calculate match score (0-100) between a seller profile and a job\'s requirements. Requires job_json and provider_profile_json. Returns match score and detailed reasons. Use when evaluating how well a job matches the seller\'s capabilities.',
      schema,
    }
  );
}
