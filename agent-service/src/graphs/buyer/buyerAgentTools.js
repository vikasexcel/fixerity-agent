import { tool } from '@langchain/core/tools';
import { z } from 'zod';
import { ChatOpenAI } from '@langchain/openai';
import { HumanMessage, SystemMessage } from '@langchain/core/messages';
import prisma from '../../prisma/client.js';
import { OPENAI_API_KEY } from '../../config/index.js';
import { getCustomerUserDetails, upsertJobEmbedding } from '../../services/index.js';

/* ================================================================================
   BUYER AGENT TOOLS - Tools for the conversational buyer agent (job creation)
   Factory creates tools bound to buyerId and accessToken per invocation.
   ================================================================================ */

/**
 * Uses LLM to generate a professional job post from collected conversation info.
 * Produces structured sections dynamically based on available data.
 * Sections: Job Title, Description (Project Overview, Proposed Program, Site Info, Budget & Timeline, Proposal Requirements).
 */
async function generateJobPostWithLLM(collectedInfo, llm) {
  const prompt = `You are a professional job post writer for a service marketplace. Given the following collected information from a buyer conversation, generate a complete, professional job listing that providers can use to give accurate pricing and timelines.

COLLECTED INFORMATION (JSON):
${JSON.stringify(collectedInfo, null, 2)}

INSTRUCTIONS:
1. Generate a professional, concise JOB TITLE (e.g. "Request for Architect – New Single-Family Residence, Saratoga CA").
2. Generate a full DESCRIPTION with structured sections. Use ONLY sections that have relevant data. Format each section with a bold header followed by content.

POSSIBLE SECTIONS (include only when data exists):
- **PROJECT OVERVIEW** – Project type, target dates (design start, construction start, move-in), location summary
- **SCOPE OF SERVICES** – For architect: concept/schematic, design development, construction docs, permit support, structural/civil coordination, construction admin, etc.
- **PROPOSED PROGRAM** – Living area (sq ft), stories, garage, bedrooms, bathrooms, office, kitchen type, special features (high ceilings, large windows, outdoor living)
- **STYLE PREFERENCES** – Architectural style (modern, contemporary, etc.) and any design direction
- **SITE INFORMATION** – Lot size, zoning, topography, utilities, survey/soil report availability, constraints (easements, setbacks, HOA, tree restrictions)
- **BUDGET & TIMELINE** – Design fee range, construction budget (if provided), level of finish, start/end dates, decision timeline
- **PROPOSAL REQUIREMENTS** – What providers should include (scope by phase, fee estimate, timeline, permit assumptions, revision rounds, similar projects, availability)

For simpler job types (plumber, cleaning, etc.): use fewer sections—Description, Scope/Details, Budget & Timeline. Adapt to the job type.

Output valid JSON only:
{
  "title": "<professional job title>",
  "description": "<full markdown-formatted description with **SECTION** headers and content>"
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
  service_category_name: z.string().describe('Type of service needed (e.g. architect, plumber, home cleaning). Required.'),
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
  specific_requirements: z.record(z.string(), z.any()).optional().describe('Job-type-specific details (e.g. architect: scope, style, sq_ft).'),
});

/**
 * Factory: creates buyer agent tools with buyerId and accessToken in closure.
 * Pass serviceCategoryManager from the caller (conversationGraph) to avoid circular deps.
 */
export function createBuyerAgentTools({ buyerId, accessToken, serviceCategoryManager }) {
  const createJobTool = tool(
    async ({
      service_category_name,
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

        const categories = await serviceCategoryManager.getCategoriesOrFetch(buyerId, accessToken);
        const match = categories?.length
          ? await serviceCategoryManager.findCategory(service_category_name, categories, llm)
          : null;

        const serviceCategoryId = match?.matched && match?.category_id != null ? match.category_id : null;
        const serviceCategoryName = (match?.category_name || service_category_name || '').trim() || service_category_name;

        const budgetMax = budget_max ?? (budget_min != null ? budget_min * 2 : 100);
        const budgetMin = budget_min ?? (budget_max != null ? Math.floor(budget_max * 0.5) : 100);

        const buyerDetails = await getCustomerUserDetails(buyerId);

        const jobId = `job_${buyerId}_${Date.now()}`;

        // Build collected info for LLM-based job post generation
        const loc = normalizeLocation(location);
        const collectedInfo = {
          service_category_name: serviceCategoryName,
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

        let finalTitle = title || `${serviceCategoryName || 'Service'} request`;
        let finalDescription = description || (serviceCategoryName ? `Looking for ${serviceCategoryName}` : 'Service request');

        if (hasRichData) {
          const generated = await generateJobPostWithLLM(collectedInfo, llm);
          if (generated) {
            finalTitle = generated.title;
            finalDescription = generated.description;
          }
        }

        const payload = {
          id: jobId,
          buyerId,
          firstName: buyerDetails?.firstName ?? null,
          lastName: buyerDetails?.lastName ?? null,
          email: buyerDetails?.email ?? null,
          contactNumber: buyerDetails?.contactNumber ?? null,
          serviceCategoryId,
          serviceCategoryName: serviceCategoryName || null,
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
      description: `Create a job listing on the marketplace. Call when you have gathered enough info for providers to give accurate bids. Required: service_category_name.

IMPORTANT: Pass ALL collected info in specific_requirements. The tool uses an LLM to generate a professional job post (title + full description with sections) from your data. You do NOT need to compose the description—just pass the structured data.

specific_requirements: Pass every detail the user shared. For architect: lot_size_sqft, zoning, topography, city, state, living_area_sqft, stories, bedrooms, bathrooms, garage, office, special_features, style, scope_phases, survey_available, constraints, design_fee_min/max, construction_budget_min/max, level_of_finish, design_start_target, construction_start_target, proposals_until, selection_target. For other jobs: job-type-specific fields (issue_type, urgency, frequency, sq_ft, etc.).

Also pass: budget_min, budget_max, start_date, end_date, location. The tool generates Project Overview, Proposed Program, Site Information, Budget & Timeline, Proposal Requirements dynamically.`,
      schema: createJobSchema,
    }
  );

  return [createJobTool];
}
