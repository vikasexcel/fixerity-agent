/**
 * Seller Match Agent: LangGraph React agent for seller-to-job matching.
 * Uses Mem0 for context, searchAvailableJobs tool, and server-side evaluation
 * to return ranked job matches for a seller.
 */

import { ChatOpenAI } from '@langchain/openai';
import { createReactAgent } from '@langchain/langgraph/prebuilt';
import { HumanMessage } from '@langchain/core/messages';
import { tool } from '@langchain/core/tools';
import { z } from 'zod';
import { post } from '../lib/laravelClient.js';
import * as mem0 from '../memory/mem0Client.js';
import { OPENAI_API_KEY } from '../config/index.js';

/**
 * Evaluate and rank jobs against seller profile (reverse of buyer match).
 * @param {Array} jobs - Job data from Laravel
 * @param {Object} providerProfile - Provider profile with rating, packages, location, etc.
 * @returns {Array} Top 5 matches with matchScore and matchReasons
 */
function evaluateAndRank(jobs, providerProfile) {
  const providerRating = parseFloat(providerProfile.average_rating) || 0;
  const jobsCompleted = parseInt(providerProfile.total_completed_order, 10) || 0;
  const hourlyRate = providerProfile.package_list?.[0]?.package_price
    ? parseFloat(providerProfile.package_list[0].package_price)
    : 0;
  const hasReferences = (parseInt(providerProfile.num_of_rating, 10) || 0) > 0;
  const licensed = providerProfile.licensed !== false; // Assume licensed unless explicitly false

  const scored = jobs.map((job) => {
    let baseScore = 100;
    const reasons = [];
    const jobPriorities = job.priorities || [];
    const maxBudget = job.budget?.max ?? 999999;

    for (const priority of jobPriorities) {
      let matched = false;
      let reason = '';

      switch (priority.type) {
        case 'price': {
          const providerMonthlyRate = (hourlyRate || 0) * 160; // ~160 hours/month
          matched = providerMonthlyRate <= maxBudget;
          reason = matched
            ? `Price within budget ($${maxBudget})`
            : `Rate exceeds budget ($${providerMonthlyRate.toFixed(0)} estimated)`;
          break;
        }
        case 'startDate':
        case 'endDate':
          matched = true; // Assume available for dates
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
    return { job, score: finalScore, reasons };
  });

  return scored
    .sort((a, b) => b.score - a.score)
    .slice(0, 5)
    .map((s, idx) => {
      const job = s.job;
      // Normalize job ID (remove 'job_' prefix if present)
      const jobId = typeof job.id === 'string' ? job.id.replace('job_', '') : job.id;
      
      return {
        id: `match_${providerProfile.provider_id}_${jobId}_${idx}`,
        jobId: jobId,
        sellerId: String(providerProfile.provider_id),
        matchScore: Math.round(s.score),
        matchReasons: s.reasons,
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
          id: `agent_${providerProfile.provider_id}`,
          userId: String(providerProfile.provider_id),
          name: providerProfile.provider_name || 'Provider',
          type: 'seller',
          rating: providerRating,
          jobsCompleted: jobsCompleted,
          licensed: licensed,
          references: hasReferences,
          hourlyRate: hourlyRate,
          createdAt: new Date().toISOString().split('T')[0],
        },
      };
    });
}

/**
 * Get provider profile details including packages and ratings.
 * @param {number|string} providerId
 * @param {string} accessToken
 * @param {number} serviceCategoryId
 * @param {number} subCategoryId
 * @param {number} lat
 * @param {number} long
 * @returns {Promise<Object>} Provider profile
 */
export async function getProviderProfile(providerId, accessToken, serviceCategoryId, subCategoryId, lat, long) {
  // We must try multiple service categories: the provider's actual category is in the DB (e.g. 11 for Dog Walking).
  // If the caller passes a category (e.g. 1), try it first, then try 1-20 so we discover the real one.
  const fallbackCategories = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20];
  const requested = serviceCategoryId && Number(serviceCategoryId) > 0 ? Number(serviceCategoryId) : null;
  const serviceCategoriesToTry = requested
    ? [requested, ...fallbackCategories.filter((id) => id !== requested)]
    : fallbackCategories;

  for (const tryServiceCategoryId of serviceCategoriesToTry) {
    try {
      // Get provider details
      const providerDetailsPath = 'customer/on-demand/provider-details';
      const providerDetails = await post(providerDetailsPath, {
        provider_id: providerId,
        service_category_id: tryServiceCategoryId,
        lat: lat || 0,
        long: long || 0,
      }, { providerId, accessToken });

      if (providerDetails.status === 1) {
        // API returns provider details directly (not nested)
        const provider = providerDetails;
        
        // Get provider packages - try using provider_service_id if available
        let packages = [];
        if (provider.provider_service_id) {
          try {
            const packagePath = 'on-demand/package-list';
            const packageData = await post(packagePath, {
              provider_service_id: provider.provider_service_id,
            }, { providerId, accessToken });
            
            if (packageData.status === 1 && packageData.package_list) {
              packages = Array.isArray(packageData.package_list) ? packageData.package_list : [];
            }
          } catch (err) {
            console.warn('[SellerMatchAgent] Could not fetch packages (this is optional):', err.message);
          }
        }

        console.log(`[SellerMatchAgent] Successfully fetched provider profile for service_category_id: ${tryServiceCategoryId}`);
        return {
          provider_id: providerId,
          provider_name: provider.provider_name || 'Provider',
          average_rating: parseFloat(provider.average_rating) || 0,
          total_completed_order: parseInt(provider.total_completed_order, 10) || 0,
          num_of_rating: 0, // API doesn't return this
          package_list: packages,
          licensed: true, // Assume licensed unless explicitly false
          service_category_id: tryServiceCategoryId, // Use the one that worked
          sub_category_id: subCategoryId,
          lat: lat || 0,
          long: long || 0,
        };
      } else {
        console.warn(`[SellerMatchAgent] Provider details API returned status ${providerDetails.status} for service_category_id ${tryServiceCategoryId}:`, providerDetails.message);
      }
    } catch (err) {
      console.warn(`[SellerMatchAgent] Error getting provider profile for service_category_id ${tryServiceCategoryId}:`, err.message);
      // Continue to next service category
      continue;
    }
  }
  
  // If all service categories failed, return minimal profile
  console.error('[SellerMatchAgent] Could not fetch provider profile for any service category. Using fallback profile.');
  console.error('[SellerMatchAgent] This usually means the provider needs to:');
  console.error('[SellerMatchAgent] 1. Have a provider_service for a service category');
  console.error('[SellerMatchAgent] 2. Have packages with status=1 for that service');
  console.error('[SellerMatchAgent] 3. Have other_service_provider_details record');
  
  return {
    provider_id: providerId,
    provider_name: 'Provider',
    average_rating: 0,
    total_completed_order: 0,
    num_of_rating: 0,
    package_list: [],
    licensed: true,
    service_category_id: serviceCategoryId || 1,
    sub_category_id: subCategoryId || 1,
    lat: lat || 0,
    long: long || 0,
  };
}

/**
 * Create the match tool that searches jobs and returns ranked matches.
 */
function createMatchJobsTool(providerId, accessToken) {
  return tool(
    async (input) => {
      const { service_category_id, sub_category_id, lat, long, provider_profile_json } = input;
      const providerProfile = typeof provider_profile_json === 'string' 
        ? JSON.parse(provider_profile_json) 
        : provider_profile_json;

      const path = 'customer/on-demand/job/list';
      const payload = {
        status: 'open', // Only search open jobs
        ...(service_category_id && { service_category_id: Number(service_category_id) }),
        ...(sub_category_id && { sub_category_id: Number(sub_category_id) }),
        ...(lat != null && { lat: Number(lat) }),
        ...(long != null && { long: Number(long) }),
      };

      try {
        // For providers, use provider_id (not user_id) for authentication
        console.log('[SellerMatchAgent] Calling job list API with:', { path, payload, providerId });
        const data = await post(path, payload, { providerId, accessToken });
        
        console.log('[SellerMatchAgent] Job list API response:', { 
          status: data.status, 
          jobsCount: data.jobs?.length || 0,
          message: data.message 
        });
        
        if (data.status !== 1 || !data.jobs || data.jobs.length === 0) {
          return JSON.stringify({
            matches: [],
            message: data?.message || 'No open jobs found for this category and location.',
          });
        }

        // Filter jobs by category if provided
        let jobs = data.jobs;
        console.log('[SellerMatchAgent] Jobs before filtering:', jobs.length);
        
        if (service_category_id) {
          jobs = jobs.filter(j => 
            j.service_category_id == service_category_id || 
            j.service_category_id === Number(service_category_id)
          );
          console.log('[SellerMatchAgent] Jobs after service_category filter:', jobs.length);
        }
        if (sub_category_id) {
          jobs = jobs.filter(j => 
            j.sub_category_id == sub_category_id || 
            j.sub_category_id === Number(sub_category_id)
          );
          console.log('[SellerMatchAgent] Jobs after sub_category filter:', jobs.length);
        }

        console.log('[SellerMatchAgent] Evaluating matches for', jobs.length, 'jobs');
        const matches = evaluateAndRank(jobs, providerProfile);
        console.log('[SellerMatchAgent] Generated', matches.length, 'matches');
        return JSON.stringify({ matches });
      } catch (err) {
        console.error('[SellerMatchAgent] Error in matchSellerToJobs tool:', err.message);
        const isNoData = err.message === 'Data Not Found' || /not found|no job/i.test(err.message);
        return JSON.stringify({
          matches: [],
          error: err.message,
          ...(isNoData && { message: 'No open jobs found for this category and location.' }),
        });
      }
    },
    {
      name: 'matchSellerToJobs',
      description:
        'Search for open jobs by service category and location, then evaluate and rank them against the seller profile. Returns top 5 matches. Use this when you have a seller profile and search params (service_category_id, sub_category_id, lat, long).',
      schema: z.object({
        service_category_id: z.number().optional().describe('Service category ID'),
        sub_category_id: z.number().optional().describe('Sub-category ID'),
        lat: z.number().optional().describe('Latitude'),
        long: z.number().optional().describe('Longitude'),
        provider_profile_json: z.string().describe('JSON string of the provider profile with rating, packages, etc.'),
      }),
    }
  );
}

/**
 * Extract matches from agent execution (last tool result).
 * ToolMessage or any message with JSON content containing matches.
 */
function extractMatchesFromResult(result) {
  const messages = result?.messages ?? [];
  for (let i = messages.length - 1; i >= 0; i--) {
    const msg = messages[i];
    const content = msg?.content;
    if (typeof content === 'string' && content.trim().startsWith('{')) {
      try {
        const parsed = JSON.parse(content);
        if (parsed.matches && Array.isArray(parsed.matches)) return parsed.matches;
      } catch {}
    }
    if (Array.isArray(content)) {
      for (const block of content) {
        const str = typeof block === 'string' ? block : block?.content ?? block?.text;
        if (str && typeof str === 'string' && str.trim().startsWith('{')) {
          try {
            const parsed = JSON.parse(str);
            if (parsed.matches && Array.isArray(parsed.matches)) return parsed.matches;
          } catch {}
        }
      }
    }
  }
  return [];
}

/**
 * Run the Seller Match Agent.
 * @param {number|string} providerId
 * @param {string} accessToken
 * @param {Object} [options] - Optional: service_category_id, sub_category_id, lat, long
 * @returns {Promise<{ matches: Array }>}
 */
export async function runSellerMatchAgent(providerId, accessToken, options = {}) {
  const {
    service_category_id,
    sub_category_id,
    lat,
    long,
  } = options;

  // Get provider profile first - this will try multiple service categories if needed
  const providerProfile = await getProviderProfile(
    providerId,
    accessToken,
    service_category_id || 1,
    sub_category_id || 1,
    lat || 0,
    long || 0
  );

  // Use the service_category_id that worked for the provider profile
  const effectiveServiceCategoryId = providerProfile.service_category_id || service_category_id || 1;

  console.log('[SellerMatchAgent] Provider profile fetched:', {
    provider_id: providerProfile.provider_id,
    provider_name: providerProfile.provider_name,
    average_rating: providerProfile.average_rating,
    total_completed_order: providerProfile.total_completed_order,
    package_list_length: providerProfile.package_list?.length || 0,
    service_category_id: effectiveServiceCategoryId,
  });

  const profileSummary = `Seller: ${providerProfile.provider_name}. Rating: ${providerProfile.average_rating}★. Jobs completed: ${providerProfile.total_completed_order}.`;
  const memoryContext = await mem0.searchForProvider(providerId, profileSummary, { limit: 5 });
  
  const systemPrompt = `You are a seller matching assistant. Your task is to match a seller profile to available jobs.
Use the matchSellerToJobs tool ONCE with the provider profile (as JSON string), service_category_id, sub_category_id, lat, and long.
The provider profile will have: provider_id, provider_name, average_rating, total_completed_order, package_list, etc.
After calling the tool and receiving the results, STOP. Do not call the tool again. The tool result contains the final matches.`;

  const matchTool = createMatchJobsTool(providerId, accessToken);
  const llm = new ChatOpenAI({
    model: 'gpt-4o-mini',
    temperature: 0,
    openAIApiKey: OPENAI_API_KEY,
  }).bindTools([matchTool]);

  const agent = createReactAgent({ 
    llm, 
    tools: [matchTool], 
    prompt: systemPrompt,
    recursionLimit: 10, // Limit to prevent infinite loops
  });

  const providerProfileForTool = {
    provider_id: providerProfile.provider_id,
    provider_name: providerProfile.provider_name,
    average_rating: providerProfile.average_rating,
    total_completed_order: providerProfile.total_completed_order,
    num_of_rating: providerProfile.num_of_rating,
    package_list: providerProfile.package_list,
    licensed: providerProfile.licensed,
  };

  console.log('[SellerMatchAgent] Calling agent with provider profile:', JSON.stringify(providerProfileForTool, null, 2));
  console.log('[SellerMatchAgent] Using service_category_id:', effectiveServiceCategoryId);
  const userMessage = `Match this seller profile to available jobs. Provider: ${JSON.stringify(providerProfileForTool)}. Use service_category_id=${effectiveServiceCategoryId}, sub_category_id=${sub_category_id || 1}, lat=${lat || 0}, long=${long || 0}.`;
  const result = await agent.invoke({ messages: [new HumanMessage(userMessage)] });

  let matches = extractMatchesFromResult(result);
  if (matches.length === 0) {
    // Fallback: call tool directly with the effective service_category_id
    const toolResult = await matchTool.invoke({
      service_category_id: effectiveServiceCategoryId,
      sub_category_id: sub_category_id || 1,
      lat: lat || 0,
      long: long || 0,
      provider_profile_json: JSON.stringify(providerProfileForTool),
    });
    const parsed = JSON.parse(toolResult);
    matches = parsed.matches || [];
  }

  const matchesSummary = `Matched ${matches.length} jobs for seller ${providerProfile.provider_name}`;
  await mem0.addForProvider(providerId, [
    { role: 'user', content: profileSummary },
    { role: 'assistant', content: matchesSummary },
  ]);

  return { matches };
}
