/**
 * Seller Match Agent: LangGraph React agent for seller-to-job matching.
 * Uses Mem0 for context, searchAvailableJobs tool, and server-side evaluation
 * to return ranked job matches for a seller.
 */

import { ChatOpenAI } from '@langchain/openai';
import { post } from '../lib/laravelClient.js';
import * as mem0 from '../memory/mem0Client.js';
import { OPENAI_API_KEY } from '../config/index.js';

/**
 * Match jobs with provider service data using LLM.
 * @param {Array} jobs - Job data from Laravel
 * @param {Array} providerServiceDataArray - Array of provider service data objects, one per service category
 * @param {number|string} providerId - Provider ID
 * @param {string} providerName - Provider name
 * @returns {Promise<Array>} Top 5 matches with matchScore and matchReasons
 */
async function matchWithLLM(jobs, providerServiceDataArray, providerId, providerName) {
  if (!jobs || jobs.length === 0) {
    return [];
  }

  const providerServiceDataMap = {};
  providerServiceDataArray.forEach((data) => {
    if (data && data.service_cat_id) {
      providerServiceDataMap[data.service_cat_id] = data;
    }
  });

  const llm = new ChatOpenAI({
    modelName: 'gpt-4o-mini',
    temperature: 0.3,
    apiKey: OPENAI_API_KEY,
  });

  const systemPrompt = `You are an expert job matching system. Your task is to evaluate jobs against provider service data and determine match quality.

For each job, consider:
1. Price compatibility: Compare job budget (min/max) with provider's min_price and max_price
2. Deadline compatibility: Check if provider's deadline_in_days aligns with job timeline
3. Rating requirements: Compare job priority rating requirements with provider's average_rating
4. Experience: Compare job priority jobsCompleted requirements with provider's total_completed_order
5. Licensing: Check if job requires licensed provider and provider's licensed status
6. Other job priorities: Consider any other priorities specified in the job

Return a JSON array with match results. Each result should have:
- jobId: The job ID
- matchScore: A score from 0-100 indicating match quality
- matchReasons: Array of strings explaining why it matches or doesn't match
- isMatch: Boolean indicating if this is a viable match (score >= 50)

Be thorough but concise in your evaluation.`;

  const batchSize = 10;
  const allMatches = [];

  for (let i = 0; i < jobs.length; i += batchSize) {
    const batch = jobs.slice(i, i + batchSize);
    const jobsWithServiceData = batch.map((job) => {
      const serviceData = providerServiceDataMap[job.service_category_id] || providerServiceDataArray[0];
      return {
        ...job,
        matchingServiceData: serviceData,
      };
    });
    
    const userPrompt = `Evaluate these ${batch.length} jobs against the provider service data.

Provider Service Data (use the matching service data for each job based on service_category_id):
${JSON.stringify(providerServiceDataArray, null, 2)}

Jobs to evaluate (each job has matchingServiceData field):
${JSON.stringify(jobsWithServiceData, null, 2)}

For each job, evaluate the match using the matchingServiceData. Consider:
- Price: job budget vs provider min_price/max_price
- Deadline: job timeline vs provider deadline_in_days  
- Rating: job rating requirements vs provider average_rating
- Experience: job jobsCompleted requirements vs provider total_completed_order
- Licensing: job licensed requirement vs provider licensed status
- Other priorities in job.priorities array

Return ONLY a valid JSON array with this exact structure:
[
  {
    "jobId": "job_id_here",
    "matchScore": 75,
    "matchReasons": ["Reason 1", "Reason 2"],
    "isMatch": true
  },
  ...
]

Make sure the JSON is valid and complete.`;

    try {
      const response = await llm.invoke([
        { role: 'system', content: systemPrompt },
        { role: 'user', content: userPrompt },
      ]);

      let matchResults = [];
      const content = response.content || '';
      let jsonMatch = content.match(/\[[\s\S]*\]/);
      if (!jsonMatch) {
        const objMatch = content.match(/\{[\s\S]*\}/);
        if (objMatch) {
          try {
            const parsed = JSON.parse(objMatch[0]);
            if (parsed.matches && Array.isArray(parsed.matches)) {
              matchResults = parsed.matches;
            }
          } catch (e) {}
        }
      }
      
      if (jsonMatch) {
        try {
          matchResults = JSON.parse(jsonMatch[0]);
          if (!Array.isArray(matchResults)) {
            matchResults = [];
          }
        } catch (e) {}
      }

      batch.forEach((job, idx) => {
        const jobId = typeof job.id === 'string' ? job.id.replace('job_', '') : job.id;
        let matchResult = matchResults.find((r) => String(r.jobId) === String(jobId));
        if (!matchResult && matchResults[idx]) {
          matchResult = matchResults[idx];
        }
        if (!matchResult) {
          const serviceData = providerServiceDataMap[job.service_category_id] || providerServiceDataArray[0];
          matchResult = {
            jobId: jobId,
            matchScore: serviceData ? 60 : 40, // Higher score if service data exists
            matchReasons: serviceData 
              ? ['Service category matches provider capabilities']
              : ['Limited match information available'],
            isMatch: true,
          };
        }

        const serviceData = providerServiceDataMap[job.service_category_id] || providerServiceDataArray[0];
        const providerRating = parseFloat(serviceData?.average_rating) || 0;
        const jobsCompleted = parseInt(serviceData?.total_completed_order, 10) || 0;
        const licensed = serviceData?.licensed !== false;
        const hasReferences = (parseInt(serviceData?.num_of_rating, 10) || 0) > 0;

        allMatches.push({
          id: `match_${providerId}_${jobId}_${i + idx}`,
          jobId: jobId,
          sellerId: String(providerId),
          matchScore: Math.max(0, Math.min(100, Math.round(matchResult.matchScore || 50))),
          matchReasons: Array.isArray(matchResult.matchReasons) 
            ? matchResult.matchReasons 
            : (matchResult.matchReasons ? [matchResult.matchReasons] : ['Match evaluated by LLM']),
          status: 'proposed',
          createdAt: new Date().toISOString().split('T')[0],
          job: {
            id: jobId,
            title: job.title ?? 'Job',
            description: job.description ?? '',
            budget: job.budget ?? { min: 0, max: 0 },
            startDate: job.startDate ?? '',
            endDate: job.endDate ?? '',
            priorities: job.priorities ?? [],
            service_category_id: job.service_category_id,
            sub_category_id: job.sub_category_id,
            lat: job.lat,
            long: job.long,
          },
          sellerAgent: {
            id: `agent_${providerId}`,
            userId: String(providerId),
            name: providerName || 'Provider',
            type: 'seller',
            rating: providerRating,
            jobsCompleted: jobsCompleted,
            licensed: licensed,
            references: hasReferences,
            createdAt: new Date().toISOString().split('T')[0],
          },
        });
      });
    } catch (err) {
      console.error(`[SellerMatchAgent] Error in LLM matching for batch ${i}-${i + batch.length}:`, err.message);
      batch.forEach((job, idx) => {
        const jobId = typeof job.id === 'string' ? job.id.replace('job_', '') : job.id;
        const serviceData = providerServiceDataMap[job.service_category_id] || providerServiceDataArray[0];
        const providerRating = parseFloat(serviceData?.average_rating) || 0;
        const jobsCompleted = parseInt(serviceData?.total_completed_order, 10) || 0;
        const licensed = serviceData?.licensed !== false;
        const hasReferences = (parseInt(serviceData?.num_of_rating, 10) || 0) > 0;

        allMatches.push({
          id: `match_${providerId}_${jobId}_${i + idx}`,
          jobId: jobId,
          sellerId: String(providerId),
          matchScore: 50,
          matchReasons: ['Match evaluation error - using fallback score'],
          status: 'proposed',
          createdAt: new Date().toISOString().split('T')[0],
          job: {
            id: jobId,
            title: job.title ?? 'Job',
            description: job.description ?? '',
            budget: job.budget ?? { min: 0, max: 0 },
            startDate: job.startDate ?? '',
            endDate: job.endDate ?? '',
            priorities: job.priorities ?? [],
            service_category_id: job.service_category_id,
            sub_category_id: job.sub_category_id,
            lat: job.lat,
            long: job.long,
          },
          sellerAgent: {
            id: `agent_${providerId}`,
            userId: String(providerId),
            name: providerName || 'Provider',
            type: 'seller',
            rating: providerRating,
            jobsCompleted: jobsCompleted,
            licensed: licensed,
            references: hasReferences,
            createdAt: new Date().toISOString().split('T')[0],
          },
        });
      });
    }
  }

  return allMatches
    .sort((a, b) => b.matchScore - a.matchScore)
    .slice(0, 5);
}

/**
 * Get provider service data for a specific service category.
 * @param {number|string} providerId
 * @param {string} accessToken
 * @param {number} serviceCategoryId
 * @returns {Promise<Object|null>} Provider service data or null if not found
 */
export async function getProviderServiceData(providerId, accessToken, serviceCategoryId) {
  if (!serviceCategoryId || Number(serviceCategoryId) <= 0) {
    return null;
  }

  try {
    const path = 'on-demand/provider-service-data';
    const payload = {
      provider_id: providerId,
      access_token: accessToken,
      service_category_id: Number(serviceCategoryId),
    };
    const data = await post(path, payload, { providerId, accessToken });

    if (data.status === 1 && data.data) {
      return data.data;
    }
    return null;
  } catch (err) {
    console.error(`[SellerMatchAgent] Error getting provider service data for service_category_id ${serviceCategoryId}:`, err.message);
    return null;
  }
}

/**
 * Get provider service list to extract actual service categories.
 * @param {number|string} providerId
 * @param {string} accessToken
 * @returns {Promise<Array<number>>} Array of service_category_id values
 */
export async function getProviderServiceList(providerId, accessToken) {
  try {
    const path = 'on-demand/provider-service-list';
    const payload = {
      provider_id: providerId,
      access_token: accessToken,
    };
    const data = await post(path, payload, { providerId, accessToken });

    if (data.status !== 1 || !data.provider_service_list || !Array.isArray(data.provider_service_list)) {
      return [];
    }

    const serviceCategoryIds = data.provider_service_list
      .map((service) => service.service_category_id)
      .filter((id) => id != null && Number(id) > 0)
      .map((id) => Number(id));
    return [...new Set(serviceCategoryIds)];
  } catch (err) {
    console.error('[SellerMatchAgent] Error getting provider service list:', err.message);
    return [];
  }
}


/**
 * Run the Seller Match Agent.
 * @param {number|string} providerId
 * @param {string} accessToken
 * @param {Object} [options] - Optional: service_category_id, sub_category_id, agentConfig
 * @returns {Promise<{ matches: Array }>}
 */
export async function runSellerMatchAgent(providerId, accessToken, options = {}) {
  const {
    service_category_id,
    sub_category_id,
  } = options;

  let serviceCategoryIds = await getProviderServiceList(providerId, accessToken);

  if (!serviceCategoryIds || serviceCategoryIds.length === 0) {
    return { matches: [] };
  }

  if (service_category_id && Number(service_category_id) > 0) {
    const requestedCategoryId = Number(service_category_id);
    if (serviceCategoryIds.includes(requestedCategoryId)) {
      serviceCategoryIds = [requestedCategoryId];
    } else {
      return { matches: [] };
    }
  }

  const categoryDataPromises = serviceCategoryIds.map(async (categoryId) => {
    try {
      const path = 'customer/on-demand/job/list';
      const jobPayload = {
        provider_id: providerId,
        access_token: accessToken,
        service_category_id: categoryId,
        ...(sub_category_id && { sub_category_id: Number(sub_category_id) }),
      };
      const jobData = await post(path, jobPayload, { providerId, accessToken });
      const jobs = (jobData.status === 1 && Array.isArray(jobData.jobs)) ? jobData.jobs : [];
      const providerServiceData = await getProviderServiceData(providerId, accessToken, categoryId);

      return {
        categoryId,
        jobs,
        providerServiceData,
      };
    } catch (err) {
      console.error(`[SellerMatchAgent] Error processing service_category_id ${categoryId}:`, err.message);
      return {
        categoryId,
        jobs: [],
        providerServiceData: null,
      };
    }
  });

  const categoryResults = await Promise.all(categoryDataPromises);
  const allJobs = [];
  const providerServiceDataArray = [];
  let providerName = 'Provider';

  categoryResults.forEach((result) => {
    if (result.jobs && result.jobs.length > 0) {
      allJobs.push(...result.jobs);
    }
    if (result.providerServiceData) {
      providerServiceDataArray.push(result.providerServiceData);
    }
  });

  if (allJobs.length === 0 || providerServiceDataArray.length === 0) {
    return { matches: [] };
  }

  providerName = 'Provider';
  const matches = await matchWithLLM(allJobs, providerServiceDataArray, providerId, providerName);

  const matchesSummary = `Matched ${matches.length} jobs for seller ${providerId}`;
  await mem0.addForProvider(providerId, [
    { role: 'user', content: `Matching jobs for provider ${providerId} with ${providerServiceDataArray.length} service categories` },
    { role: 'assistant', content: matchesSummary },
  ]);

  return { matches };
}
