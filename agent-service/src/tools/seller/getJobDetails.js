/**
 * Seller tool: Get detailed information about a specific job.
 *
 * Laravel: POST /api/customer/on-demand/job/list (with job_id filter)
 * Note: May need backend endpoint like /api/on-demand/job/details for providers
 *
 * Required: provider_id, access_token, job_id
 *
 * Use when the seller wants to see full details of a specific job including priorities and requirements.
 */

import { tool } from '@langchain/core/tools';
import { z } from 'zod';

const path = 'customer/on-demand/job/list';

const schema = z.object({
  job_id: z.number().describe('Job ID to get details for'),
});

/**
 * @param {typeof import('../../lib/laravelClient.js').post} laravelClient
 * @param {number} [providerId]
 * @param {string} [accessToken]
 * @returns {import('@langchain/core/tools').StructuredToolInterface}
 */
export function createGetJobDetailsTool(laravelClient, providerId, accessToken) {
  return tool(
    async (input) => {
      try {
        const payload = {
          job_id: input.job_id,
          status: 'all',
        };
        const data = await laravelClient(path, payload, { userId: providerId, providerId, accessToken });
        if (data.jobs && Array.isArray(data.jobs)) {
          const job = data.jobs.find(j => {
            const jobIdStr = String(j.id || '').replace('job_', '');
            return jobIdStr === String(input.job_id) || j.id === input.job_id;
          });
          if (job) {
            return JSON.stringify({ status: 1, job });
          }
        }
        
        return JSON.stringify({ status: 0, message: 'Job not found', job: null });
      } catch (err) {
        return `Failed to get job details: ${err.message}`;
      }
    },
    {
      name: 'getJobDetails',
      description: 'Get detailed information about a specific job including title, description, budget, priorities, and requirements. Requires job_id. Use when the seller wants to see full job requirements before matching.',
      schema,
    }
  );
}
