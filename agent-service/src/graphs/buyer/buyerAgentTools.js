import { tool } from '@langchain/core/tools';
import { z } from 'zod';
import { ChatOpenAI } from '@langchain/openai';
import { HumanMessage, SystemMessage } from '@langchain/core/messages';
import prisma from '../../prisma/client.js';
import { OPENAI_API_KEY } from '../../config/index.js';
import { getCustomerUserDetails, upsertJobEmbedding } from '../../services/index.js';
import { serviceCategoryManager } from '../../services/serviceCategoryManager.js';

/* ================================================================================
   BUYER AGENT TOOLS - Tools for the conversational buyer agent (job creation)
   Factory creates tools bound to buyerId and accessToken per invocation.
   ================================================================================ */

/**
 * Uses LLM to generate a professional RFP-style job post from collected conversation info.
 * The RFP structure is determined dynamically by the LLM based on service type and available data—
 * no hardcoded templates. Architect gets project/program/style/site sections; plumber gets issue/urgency/access;
 * cleaning gets scope/frequency; etc.
 */
async function generateJobPostWithLLM(collectedInfo, llm) {
  const prompt = `You are a professional job post writer for a service marketplace. Given collected information from a buyer conversation, generate a complete, professional job listing so providers can give accurate pricing and timelines.

COLLECTED INFORMATION (JSON):
${JSON.stringify(collectedInfo, null, 2)}

CRITICAL - DYNAMIC STRUCTURE:
Do NOT use a fixed template. Infer the appropriate structure from the data and what type of work is being requested. Use 3-6 sections with clear bold headers (**SECTION NAME**) that help providers understand the job and bid accurately.

For any type of work, organize the information logically:
- What needs to be done (scope, requirements, deliverables)
- Where and when (location, timeline, urgency)
- Context that matters (existing conditions, constraints, preferences)
- Budget and timeline expectations
- What providers should include in their response (quote, timeline, availability, credentials)

Include ONLY sections where you have meaningful data. Format professionally with markdown.

Output valid JSON only:
{
  "title": "<professional job title that describes the work>",
  "description": "<full markdown-formatted job post with **SECTION** headers and content>"
}`;

  try {
    const res = await llm.invoke([
      new SystemMessage('Output only valid JSON. No markdown code blocks.'),
      new HumanMessage(prompt),
    ]);
    let content = (res.content || '').trim();
    content = content.replace(/```json\n?/g, '').replace(/```\n?/g, '').trim();
    const parsed = JSON.parse(content);
    return {
      title: parsed.title || 'Service Request',
      description: parsed.description || '',
    };
  } catch (err) {
    console.error('[generateJobPostWithLLM] Error:', err.message);
    return null;
  }
}

function normalizeLocation(location) {
  if (location == null) return null;
  if (typeof location === 'string') return { address: location };
  if (typeof location === 'object' && (location.address !== undefined || location.lat !== undefined)) {
    return { address: location.address ?? '', lat: location.lat ?? null, lng: location.lng ?? null };
  }
  return null;
}

const createJobSchema = z.object({
  title: z.string().optional().describe('Job title for the listing.'),
  description: z.string().optional().describe('Detailed description of the work needed.'),
  budget_min: z.number().optional().describe('Minimum budget in dollars.'),
  budget_max: z.number().optional().describe('Maximum budget in dollars.'),
  start_date: z.string().optional().describe('When work should start (YYYY-MM-DD or ASAP).'),
  end_date: z.string().optional().describe('When work should end (YYYY-MM-DD or flexible).'),
  location: z.union([z.string(), z.object({
    address: z.string().optional(),
    lat: z.number().optional(),
    lng: z.number().optional(),
  })]).optional().describe('Address or location where service is needed.'),
  specific_requirements: z.record(z.string(), z.any()).optional().describe('Job-type-specific details captured from the conversation.'),
});

/**
 * Factory: creates buyer agent tools with buyerId and accessToken in closure.
 */
export function createBuyerAgentTools({ buyerId, accessToken }) {
  const createJobTool = tool(
    async ({
      title,
      description,
      budget_min,
      budget_max,
      start_date,
      end_date,
      location,
      specific_requirements,
    }) => {
      try {
        const llm = new ChatOpenAI({
          model: 'gpt-4o-mini',
          temperature: 0,
          openAIApiKey: OPENAI_API_KEY,
        });

        const budgetMax = budget_max ?? (budget_min != null ? budget_min * 2 : 100);
        const budgetMin = budget_min ?? (budget_max != null ? Math.floor(budget_max * 0.5) : 100);

        const buyerDetails = await getCustomerUserDetails(buyerId);

        const jobId = `job_${buyerId}_${Date.now()}`;

        // Build collected info for LLM-based job post generation
        const loc = normalizeLocation(location);
        const collectedInfo = {
          title_draft: title,
          description_draft: description,
          budget_min: budgetMin,
          budget_max: budgetMax,
          start_date: start_date || 'ASAP',
          end_date: end_date || 'flexible',
          location: loc?.address ?? (typeof location === 'string' ? location : null),
          ...(specific_requirements && Object.keys(specific_requirements).length > 0 ? specific_requirements : {}),
        };

        const hasRichData =
          (specific_requirements && Object.keys(specific_requirements).length > 0) ||
          (title && description) ||
          (loc?.address && (budget_min != null || budget_max != null));

        let finalTitle = title || 'Service Request';
        let finalDescription = description || 'Service request';

        if (hasRichData) {
          const generated = await generateJobPostWithLLM(collectedInfo, llm);
          if (generated) {
            finalTitle = generated.title;
            finalDescription = generated.description;
          }
        }

        let serviceCategoryId = null;
        let serviceCategoryName = null;
        try {
          const categories = await serviceCategoryManager.getCategoriesOrFetch(buyerId, accessToken);
          const userInput = [finalTitle, finalDescription, specific_requirements && Object.keys(specific_requirements).length ? JSON.stringify(specific_requirements) : ''].filter(Boolean).join(' ');
          if (categories?.length && userInput.trim()) {
            const match = await serviceCategoryManager.findCategory(userInput.slice(0, 500), categories, llm);
            if (match?.matched && (match.category_id != null || match.category_name)) {
              serviceCategoryId = match.category_id ?? null;
              serviceCategoryName = match.category_name ?? null;
              console.log(`[create_job] Matched service category: id=${serviceCategoryId}, name=${serviceCategoryName}`);
            }
          }
        } catch (err) {
          console.error('[create_job] Service category resolution failed:', err.message);
        }

        const payload = {
          id: jobId,
          buyerId,
          firstName: buyerDetails?.firstName ?? null,
          lastName: buyerDetails?.lastName ?? null,
          email: buyerDetails?.email ?? null,
          contactNumber: buyerDetails?.contactNumber ?? null,
          serviceCategoryId,
          serviceCategoryName,
          title: finalTitle,
          description: finalDescription,
          budget: { min: budgetMin, max: budgetMax },
          startDate: start_date || 'ASAP',
          endDate: end_date || 'flexible',
          location: loc,
          priorities: null,
          specificRequirements: specific_requirements ?? null,
          status: 'open',
        };

        const created = await prisma.jobListing.create({
          data: payload,
        });

        const job = {
          id: created.id,
          buyer_id: created.buyerId,
          title: created.title,
          description: created.description,
          service_category_id: created.serviceCategoryId,
          service_category_name: created.serviceCategoryName,
          budget: created.budget,
          startDate: created.startDate,
          endDate: created.endDate,
          location: created.location,
          priorities: created.priorities || [],
          created_at: created.createdAt,
        };

        console.log(`[create_job] Created job: ${jobId}`);

        upsertJobEmbedding(created.id, created).catch((err) => {
          console.error('[create_job] Job embedding failed:', err.message);
        });

        return JSON.stringify({ success: true, job });
      } catch (error) {
        console.error('[create_job] Error:', error.message);
        return JSON.stringify({ success: false, error: error.message });
      }
    },
    {
      name: 'create_job',
      description: `Create a job listing on the marketplace. Call when you have gathered enough info for providers to give accurate bids. Do NOT ask the user for confirmation—call this tool directly when ready.

IMPORTANT: Pass ALL collected info in specific_requirements. The tool uses an LLM to generate a professional job post from your data. You do NOT need to compose the description—just pass the structured data as key-value pairs that match what matters for the type of work.

Also pass: budget_min, budget_max (use defaults if user said "flexible" or "reasonable"), start_date, end_date, location, title (optional), description (optional).`,
      schema: createJobSchema,
    }
  );

  return [createJobTool];
}
