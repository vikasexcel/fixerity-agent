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

  // Create a map of service_category_id to provider service data for quick lookup
  const providerServiceDataMap = {};
  providerServiceDataArray.forEach((data) => {
    if (data && data.service_cat_id) {
      providerServiceDataMap[data.service_cat_id] = data;
    }
  });

  // Prepare data for LLM
  const llm = new ChatOpenAI({
    modelName: 'gpt-4o-mini',
    temperature: 0.3,
    apiKey: OPENAI_API_KEY,
  });

  // Create a matching prompt for the LLM
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

  // Process jobs in batches to avoid token limits
  const batchSize = 10;
  const allMatches = [];

  for (let i = 0; i < jobs.length; i += batchSize) {
    const batch = jobs.slice(i, i + batchSize);
    
    // Prepare job data with service category mapping for LLM
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
      
      // Try to extract JSON from the response - look for array pattern
      let jsonMatch = content.match(/\[[\s\S]*\]/);
      if (!jsonMatch) {
        // Try to find JSON object with matches array
        const objMatch = content.match(/\{[\s\S]*\}/);
        if (objMatch) {
          try {
            const parsed = JSON.parse(objMatch[0]);
            if (parsed.matches && Array.isArray(parsed.matches)) {
              matchResults = parsed.matches;
            }
          } catch (e) {
            // Continue to try array pattern
          }
        }
      }
      
      if (jsonMatch) {
        try {
          matchResults = JSON.parse(jsonMatch[0]);
          if (!Array.isArray(matchResults)) {
            matchResults = [];
          }
        } catch (e) {
          console.warn('[SellerMatchAgent] Failed to parse LLM response as JSON:', e.message);
          console.warn('[SellerMatchAgent] Response content:', content.substring(0, 500));
        }
      }

      // Process each job in the batch
      batch.forEach((job, idx) => {
        const jobId = typeof job.id === 'string' ? job.id.replace('job_', '') : job.id;
        
        // Find matching result by jobId
        let matchResult = matchResults.find((r) => String(r.jobId) === String(jobId));
        
        if (!matchResult && matchResults[idx]) {
          // Fallback: use index-based matching
          matchResult = matchResults[idx];
        }
        
        if (!matchResult) {
          // Fallback: create a basic match result with score based on service category match
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

        // Get provider service data for this job's category
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
      // Create fallback matches for this batch
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

  // Sort by match score and return top 5
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
    console.warn('[SellerMatchAgent] Invalid service_category_id:', serviceCategoryId);
    return null;
  }

  try {
    const path = 'on-demand/provider-service-data';
    const payload = {
      provider_id: providerId,
      access_token: accessToken,
      service_category_id: Number(serviceCategoryId),
    };

    console.log(`[SellerMatchAgent] Calling provider-service-data API for service_category_id: ${serviceCategoryId}`);
    const data = await post(path, payload, { providerId, accessToken });

    if (data.status === 1 && data.data) {
      console.log(`[SellerMatchAgent] Successfully fetched provider service data for service_category_id: ${serviceCategoryId}`);
      return data.data;
    } else {
      console.warn(`[SellerMatchAgent] Provider service data API returned status ${data.status} for service_category_id ${serviceCategoryId}:`, data.message);
      return null;
    }
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

    console.log('[SellerMatchAgent] Calling provider-service-list API with:', { path, providerId });
    const data = await post(path, payload, { providerId, accessToken });

    console.log('[SellerMatchAgent] Provider-service-list API response:', {
      status: data.status,
      serviceCount: data.provider_service_list?.length || 0,
      message: data.message,
    });

    if (data.status !== 1 || !data.provider_service_list || !Array.isArray(data.provider_service_list)) {
      console.warn('[SellerMatchAgent] No provider services found or invalid response');
      return [];
    }

    // Extract service_category_id values from provider_service_list
    const serviceCategoryIds = data.provider_service_list
      .map((service) => service.service_category_id)
      .filter((id) => id != null && Number(id) > 0)
      .map((id) => Number(id));

    // Remove duplicates
    const uniqueCategoryIds = [...new Set(serviceCategoryIds)];

    console.log('[SellerMatchAgent] Extracted service category IDs:', uniqueCategoryIds);
    return uniqueCategoryIds;
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
    agentConfig,
  } = options;

  // Step 1: Get provider service list to find actual service categories
  console.log('[SellerMatchAgent] Step 1: Getting provider service list...');
  let serviceCategoryIds = await getProviderServiceList(providerId, accessToken);

  if (!serviceCategoryIds || serviceCategoryIds.length === 0) {
    console.warn('[SellerMatchAgent] No service categories found for provider. Returning empty matches.');
    return { matches: [] };
  }

  // If a specific service_category_id is provided, filter to only that category
  if (service_category_id && Number(service_category_id) > 0) {
    const requestedCategoryId = Number(service_category_id);
    if (serviceCategoryIds.includes(requestedCategoryId)) {
      serviceCategoryIds = [requestedCategoryId];
      console.log('[SellerMatchAgent] Filtered to specific service category:', requestedCategoryId);
    } else {
      console.warn(`[SellerMatchAgent] Requested service_category_id ${requestedCategoryId} not found in provider's services. Available:`, serviceCategoryIds);
      return { matches: [] };
    }
  }

  console.log('[SellerMatchAgent] Found service categories:', serviceCategoryIds);

  // Step 2: Fetch jobs and provider service data in parallel for each service category
  console.log('[SellerMatchAgent] Step 2: Fetching jobs and provider service data in parallel for', serviceCategoryIds.length, 'service categories...');
  
  const categoryDataPromises = serviceCategoryIds.map(async (categoryId) => {
    try {
      // Fetch jobs for this category
      const path = 'customer/on-demand/job/list';
      const jobPayload = {
        provider_id: providerId,
        access_token: accessToken,
        service_category_id: categoryId,
        ...(sub_category_id && { sub_category_id: Number(sub_category_id) }),
      };

      console.log(`[SellerMatchAgent] Fetching jobs for service_category_id: ${categoryId}`);
      const jobData = await post(path, jobPayload, { providerId, accessToken });

      const jobs = (jobData.status === 1 && Array.isArray(jobData.jobs)) ? jobData.jobs : [];
      console.log(`[SellerMatchAgent] Found ${jobs.length} jobs for service_category_id: ${categoryId}`);

      // Fetch provider service data for this category
      console.log(`[SellerMatchAgent] Fetching provider service data for service_category_id: ${categoryId}`);
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

  // Wait for all requests to complete
  const categoryResults = await Promise.all(categoryDataPromises);
  
  // Step 3: Combine all jobs and provider service data
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

  console.log('[SellerMatchAgent] Step 3: Combined', allJobs.length, 'jobs from all service categories');
  console.log('[SellerMatchAgent] Found provider service data for', providerServiceDataArray.length, 'service categories');

  if (allJobs.length === 0) {
    console.log('[SellerMatchAgent] No jobs found for any service category');
    return { matches: [] };
  }

  if (providerServiceDataArray.length === 0) {
    console.warn('[SellerMatchAgent] No provider service data found. Cannot perform matching.');
    return { matches: [] };
  }

  // Provider name is not critical for matching functionality
  // We'll use 'Provider' as default since provider-service-data API doesn't return provider name
  providerName = 'Provider';

  // Step 4: Use LLM to match jobs with provider service data
  console.log('[SellerMatchAgent] Step 4: Using LLM to match', allJobs.length, 'jobs with provider service data...');
  const matches = await matchWithLLM(allJobs, providerServiceDataArray, providerId, providerName);
  console.log('[SellerMatchAgent] Generated', matches.length, 'matches');

  const matchesSummary = `Matched ${matches.length} jobs for seller ${providerId}`;
  await mem0.addForProvider(providerId, [
    { role: 'user', content: `Matching jobs for provider ${providerId} with ${providerServiceDataArray.length} service categories` },
    { role: 'assistant', content: matchesSummary },
  ]);

  return { matches };
}
