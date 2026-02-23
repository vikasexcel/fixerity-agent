import { tool } from '@langchain/core/tools';
import { z } from 'zod';
import { ChatOpenAI } from '@langchain/openai';
import { HumanMessage, SystemMessage } from '@langchain/core/messages';
import prisma from '../../prisma/client.js';
import { OPENAI_API_KEY } from '../../config/index.js';
import { getCustomerUserDetails, upsertJobEmbedding } from '../../services/index.js';

/* ================================================================================
   BUYER AGENT TOOLS - Tools for the conversational buyer agent (job creation)
   Zero predefined structure. AI determines questions and collects data freely.
   Factory creates tools bound to buyerId and accessToken per invocation.
   ================================================================================ */

/**
 * Uses LLM to generate a professional job post from free-form conversation data.
 * AI decides structure, format, and organization based on the type of work.
 */
async function generateJobPostWithLLM(conversationData, llm) {
  const prompt = `Generate a professional job post for a service marketplace.

CONVERSATION DATA:
${JSON.stringify(conversationData, null, 2)}

Create a comprehensive job post that helps service providers give accurate bids. Use whatever format, structure, and sections make sense for this specific type of work.

Output valid JSON only:
{
  "title": "<clear, descriptive job title>",
  "post": "<complete job post in markdown format>"
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
      post: parsed.post || '',
    };
  } catch (err) {
    console.error('[generateJobPostWithLLM] Error:', err.message);
    return { title: 'Service Request', post: 'Unable to generate job post' };
  }
}

/**
 * Uses LLM to infer a general service category from conversation data.
 * Returns a simple category name (e.g., "Pet Care", "Home Improvement", "Art Services").
 */
async function inferCategoryFromConversation(conversationData, llm) {
  const prompt = `Based on the following conversation data about a service request, infer a GENERAL service category name.

CONVERSATION DATA:
${JSON.stringify(conversationData, null, 2)}

Examples of good category names:
- "Pet Care" (for dog walking, cat sitting, pet grooming)
- "Home Improvement" (for painting, repairs, renovations)
- "Art Services" (for murals, portraits, custom artwork)
- "Technology Services" (for laptop repair, IT support)
- "Cleaning Services" (for house cleaning, office cleaning)
- "Moving Services" (for relocation, packing)
- "Personal Services" (for tutoring, personal training)
- "Professional Services" (for tax prep, accounting, legal)

Output ONLY a simple category name (2-4 words max) as plain text, no JSON, no explanation.`;

  try {
    const res = await llm.invoke([
      new SystemMessage('Output only the category name as plain text.'),
      new HumanMessage(prompt),
    ]);
    const category = (res.content || '').trim();
    console.log(`[inferCategory] Inferred category: "${category}"`);
    return category || 'General Services';
  } catch (err) {
    console.error('[inferCategory] Error:', err.message);
    return 'General Services';
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

function extractBudget(conversationData) {
  // Only extract budget if user explicitly mentioned it
  if (conversationData?.budget && typeof conversationData.budget === 'object') {
    const b = conversationData.budget;
    const min = b.min ?? b.budget_min;
    const max = b.max ?? b.budget_max;
    if (min != null || max != null) {
      return { min: Number(min ?? 0), max: Number(max ?? 0) };
    }
  }
  // Check for budget_min/max fields
  const min = conversationData?.budget_min ?? conversationData?.budget_minimum;
  const max = conversationData?.budget_max ?? conversationData?.budget_maximum;
  if (min != null || max != null) {
    return { min: Number(min ?? 0), max: Number(max ?? 0) };
  }
  // No budget mentioned - return null to indicate flexible/TBD
  return null;
}

/**
 * Factory: creates buyer agent tools with buyerId and accessToken in closure.
 */
export function createBuyerAgentTools({ buyerId, accessToken }) {
  const createJobTool = tool(
    async ({ conversation_data }) => {
      try {
        const data = conversation_data ?? {};
        const llm = new ChatOpenAI({
          model: 'gpt-4o-mini',
          temperature: 0.7,
          openAIApiKey: OPENAI_API_KEY,
        });

        const buyerDetails = await getCustomerUserDetails(buyerId);
        const jobId = `job_${buyerId}_${Date.now()}`;

        const generated = await generateJobPostWithLLM(data, llm);
        const budget = extractBudget(data) ?? { min: 0, max: 0 };
        const location = normalizeLocation(data.location);

        // Infer category name from conversation (no API matching needed)
        let serviceCategoryId = null;
        let serviceCategoryName = null;

        try {
          const inferredCategory = await inferCategoryFromConversation(data, llm);
          console.log(`[create_job] Inferred category: "${inferredCategory}"`);
          
          // Use the inferred category name directly (no API lookup/matching)
          serviceCategoryName = inferredCategory;
          console.log(`[create_job] Using inferred category: "${serviceCategoryName}"`);
        } catch (err) {
          console.error('[create_job] Category inference failed:', err.message);
          // Fallback to a generic category if inference fails
          serviceCategoryName = 'General Services';
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
          title: generated.title,
          description: generated.post,
          budget,
          startDate: data.start_date ?? data.start ?? 'ASAP',
          endDate: data.end_date ?? data.deadline ?? 'flexible',
          location,
          priorities: null,
          specificRequirements: data,
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
          budget: created.budget,
          startDate: created.startDate,
          endDate: created.endDate,
          location: created.location,
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
      description: 'Create a job listing. Call when you have gathered comprehensive information through conversation. Pass everything you collected as conversation_data - the system will generate an appropriate job post.',
      schema: z.object({
        conversation_data: z.record(z.string(), z.any()).describe('All information collected from the conversation as key-value pairs'),
      }),
    }
  );

  return [createJobTool];
}
