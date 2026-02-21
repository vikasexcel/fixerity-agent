import { tool } from '@langchain/core/tools';
import { z } from 'zod';
import { ChatOpenAI } from '@langchain/openai';
import { HumanMessage, SystemMessage } from '@langchain/core/messages';
import prisma from '../../prisma/client.js';
import { OPENAI_API_KEY } from '../../config/index.js';
import { getProviderBasicDetails } from '../../services/providerDetailsService.js';
import { upsertSellerEmbedding } from '../../services/sellerEmbeddingService.js';
import { randomUUID } from 'crypto';
import { logProviderTools } from '../../utils/providerProfileLogger.js';

/* ================================================================================
   PROVIDER AGENT TOOLS - Tools for the conversational provider agent (profile creation)
   Factory creates tools bound to sellerId and accessToken per invocation.
   Similar to buyer create_job - uses LLM to generate profile from collected data.
   ================================================================================ */

/**
 * Uses LLM to generate a professional seller profile from collected conversation info.
 * CONVERSATION-DERIVED: No predefined schema. Extract ALL relevant details from the
 * conversation into a flexible structure that adapts to any service type.
 */
async function generateSellerProfileWithLLM(collectedInfo, llm) {
  const prompt = `You are a professional profile writer for a service marketplace. Given collected information from a provider conversation, generate a complete, professional seller profile.

COLLECTED INFORMATION (from conversation - JSON):
${JSON.stringify(collectedInfo, null, 2)}

CRITICAL - CONVERSATION-DERIVED PROFILE (no predefined schema):
Extract EVERYTHING the provider said. Do NOT drop details to fit a template. The profile must reflect what was actually discussed.

1. "bio": 2-4 sentence professional intro in first person. Include: specialty, equipment/tools, certifications, project types, what makes them stand out. Use the exact terms they used (e.g. "foundation crack repair", "fiber-reinforced concrete").

2. "service_category_names": Array of service types. Include primary + sub-services (e.g. ["concrete", "foundation repair", "new construction", "foundation waterproofing"]).

3. "credentials": Standard + domain-specific. Include: licensed, insured, years_experience, references_available, certifications (array), certifications_not_held (e.g. ["ACI"]), emergency_available, etc.

4. "pricing": hourly_rate_min, hourly_rate_max, fixed_prices. Include pricing_notes if they mentioned flat-rate, project-based, etc.

5. "preferences": Standard (min_job_size_hours, max_travel_distance, provides_materials, preferred_payment) PLUS a "conversation_profile" object with ALL domain-specific details from the conversation:
   - equipment: array of tools/equipment mentioned
   - materials: array (e.g. concrete mix types, fiber-reinforced)
   - project_sizes_sqft: { min, max } or string
   - project_focus: "residential" | "commercial" | "both"
   - additional_services: array (e.g. ["foundation waterproofing"])
   - Any other key-value pairs the provider mentioned

Output valid JSON only:
{
  "bio": "<2-4 sentences, first person, includes equipment/certs/project types from conversation>",
  "service_category_names": ["<primary>", "<sub1>", "<sub2>", ...],
  "credentials": { "licensed": bool, "insured": bool, "years_experience": number, "certifications": [], "certifications_not_held": [], ... },
  "pricing": { "hourly_rate_min": number, "hourly_rate_max": number, "fixed_prices": {}, "pricing_notes": "optional" },
  "preferences": {
    "min_job_size_hours": number|null,
    "max_travel_distance": number|null,
    "provides_materials": bool|null,
    "preferred_payment": [],
    "conversation_profile": {
      "equipment": ["<exact terms from conversation>"],
      "materials": ["<mix types, materials mentioned>"],
      "project_sizes_sqft": { "min": number, "max": number } | "<description>",
      "project_focus": "residential"|"commercial"|"both",
      "additional_services": ["<e.g. waterproofing>"],
      "<any_other_key>": "<value from conversation>"
    }
  }
}

Include ONLY fields that have data. Use null for unknown. The conversation_profile must capture ALL domain-specific details—equipment, materials, project sizes, focus, additional services—so the profile is searchable and matches jobs accurately.`;

  try {
    const res = await llm.invoke([
      new SystemMessage('Output only valid JSON. No markdown code blocks.'),
      new HumanMessage(prompt),
    ]);
    let content = (res.content || '').trim();
    content = content.replace(/```json\n?/g, '').replace(/```\n?/g, '').trim();
    return JSON.parse(content);
  } catch (err) {
    console.error('[generateSellerProfileWithLLM] Error:', err.message);
    return null;
  }
}

function normalizeServiceArea(area) {
  if (area == null) return null;
  if (typeof area === 'string') return { location: area };
  if (typeof area === 'object') {
    return {
      location: area.location ?? area.address ?? null,
      city: area.city ?? null,
      state: area.state ?? null,
      radius_miles: area.radius_miles ?? null,
      zip_codes: area.zip_codes ?? null,
    };
  }
  return null;
}

const createSellerProfileSchema = z.object({
  service_category_name: z.string().describe('Primary service type (e.g. concrete, plumbing, home cleaning). Required.'),
  specific_requirements: z.record(z.string(), z.any()).optional().describe('Domain-specific details: concrete: equipment, certifications, project_sizes; plumber: license_type, emergency; etc.'),
  service_area: z.union([
    z.string(),
    z.object({
      location: z.string().optional(),
      city: z.string().optional(),
      state: z.string().optional(),
      radius_miles: z.number().optional(),
      zip_codes: z.array(z.string()).optional(),
    }),
  ]).optional().describe('Where the provider serves.'),
  availability: z.union([
    z.string(),
    z.object({
      schedule: z.string().optional(),
      weekdays: z.string().optional(),
      weekends: z.string().optional(),
      weekday_evenings: z.boolean().optional(),
      emergency: z.boolean().optional(),
    }),
  ]).optional().describe('When the provider is available.'),
  pricing: z.object({
    hourly_rate_min: z.number().optional(),
    hourly_rate_max: z.number().optional(),
    fixed_prices: z.record(z.string(), z.number()).optional(),
  }).optional().describe('Pricing structure.'),
  credentials: z.object({
    licensed: z.boolean().optional(),
    insured: z.boolean().optional(),
    years_experience: z.number().optional(),
    references_available: z.boolean().optional(),
    certifications: z.array(z.string()).optional(),
  }).optional().describe('Credentials and trust signals.'),
  bio: z.string().optional().describe('Short intro - tool will generate if omitted.'),
});

/**
 * Factory: creates provider agent tools with sellerId and accessToken in closure.
 * Pass serviceCategoryManager from the caller to avoid circular deps.
 */
export function createProviderAgentTools({ sellerId, accessToken, serviceCategoryManager }) {
  const createSellerProfileTool = tool(
    async ({
      service_category_name,
      specific_requirements,
      service_area,
      availability,
      pricing,
      credentials,
      bio,
    }) => {
      const providerId = parseInt(String(sellerId), 10);
      logProviderTools('create_seller_profile_called', {
        providerId: isNaN(providerId) ? null : providerId,
        service_category_name: service_category_name ?? null,
        specific_requirements_keys: specific_requirements ? Object.keys(specific_requirements) : [],
        specific_requirements: specific_requirements ?? null,
        service_area: service_area ?? null,
        availability: availability ?? null,
        pricing: pricing ?? null,
        credentials: credentials ?? null,
        has_bio: !!bio,
      });
      try {
        const llm = new ChatOpenAI({
          model: 'gpt-4o-mini',
          temperature: 0,
          openAIApiKey: OPENAI_API_KEY,
        });

        const categories = await serviceCategoryManager.getProviderCategoriesOrFetch(providerId, accessToken);
        const match = categories?.length
          ? await serviceCategoryManager.findCategory(service_category_name, categories, llm)
          : null;

        logProviderTools('category_match', {
          providerId,
          categoriesCount: categories?.length ?? 0,
          matched: match?.matched ?? false,
          category_name: match?.category_name ?? null,
        });

        const serviceCategoryName = (match?.matched && match?.category_name)
          ? match.category_name.trim()
          : (service_category_name || '').trim() || service_category_name;

        const serviceAreaObj = normalizeServiceArea(service_area);
        const availabilityObj = typeof availability === 'string'
          ? { schedule: availability }
          : (availability && typeof availability === 'object' ? availability : null);

        const collectedInfo = {
          service_category_name: serviceCategoryName,
          bio_draft: bio,
          service_area: serviceAreaObj,
          availability: availabilityObj,
          pricing: pricing ?? {},
          credentials: credentials ?? {},
          ...(specific_requirements && Object.keys(specific_requirements).length > 0 ? specific_requirements : {}),
        };

        logProviderTools('collected_info', {
          providerId,
          service_category_name: serviceCategoryName,
          collectedInfoKeys: Object.keys(collectedInfo),
          collectedInfo,
        });

        const hasRichData =
          (specific_requirements && Object.keys(specific_requirements).length > 0) ||
          (serviceAreaObj?.location) ||
          (availabilityObj?.schedule) ||
          (pricing?.hourly_rate_max || (pricing?.fixed_prices && Object.keys(pricing.fixed_prices).length > 0)) ||
          (credentials?.years_experience != null || credentials?.licensed != null);

        let generated = null;
        if (hasRichData) {
          generated = await generateSellerProfileWithLLM(collectedInfo, llm);
        }

        const finalBio = generated?.bio ?? bio ?? (serviceCategoryName ? `Professional ${serviceCategoryName} provider.` : 'Service provider.');
        const finalServiceNames = generated?.service_category_names?.length
          ? generated.service_category_names
          : [serviceCategoryName || 'General Service'];
        const finalCredentials = { ...(credentials ?? {}), ...(generated?.credentials ?? {}) };
        const finalPricing = generated?.pricing ?? pricing ?? { hourly_rate_min: null, hourly_rate_max: null, fixed_prices: {} };
        const defaultPrefs = { min_job_size_hours: null, max_travel_distance: null, provides_materials: null, preferred_payment: [] };
        const finalPreferences = generated?.preferences
          ? { ...defaultPrefs, ...generated.preferences }
          : defaultPrefs;

        let score = 0;
        if (finalServiceNames.length > 0) score += 20;
        if (serviceAreaObj?.location) score += 15;
        if (availabilityObj?.schedule) score += 15;
        if (finalPricing?.hourly_rate_max || (finalPricing?.fixed_prices && Object.keys(finalPricing.fixed_prices).length > 0)) score += 15;
        if (finalCredentials?.years_experience) score += 10;
        if (finalCredentials?.licensed !== undefined && finalCredentials?.licensed !== null) score += 5;
        if (finalCredentials?.references_available) score += 5;
        if (finalBio) score += 10;
        if (finalPreferences?.min_job_size_hours != null) score += 5;
        if (finalPreferences?.conversation_profile && Object.keys(finalPreferences.conversation_profile).length > 0) score += 5;

        const providerDetails = await getProviderBasicDetails(providerId);

        const profileData = {
          providerId,
          firstName: providerDetails?.firstName ?? null,
          lastName: providerDetails?.lastName ?? null,
          email: providerDetails?.email ?? null,
          gender: providerDetails?.gender ?? null,
          contactNumber: providerDetails?.contactNumber ?? null,
          serviceCategoryNames: finalServiceNames,
          serviceArea: serviceAreaObj,
          availability: availabilityObj,
          credentials: finalCredentials,
          pricing: finalPricing,
          preferences: finalPreferences,
          bio: finalBio,
          profileCompletenessScore: score,
          active: true,
        };

        const existing = await prisma.sellerProfile.findFirst({
          where: { providerId, active: true },
          orderBy: { updatedAt: 'desc' },
        });

        let created;
        if (existing) {
          created = await prisma.sellerProfile.update({
            where: { id: existing.id },
            data: profileData,
          });
          logProviderTools('create_seller_profile_success', {
            profileId: created.id,
            providerId,
            updated: true,
            serviceCategoryNames: created.serviceCategoryNames,
            profileCompletenessScore: created.profileCompletenessScore,
          });
        } else {
          created = await prisma.sellerProfile.create({
            data: { id: randomUUID(), ...profileData },
          });
          logProviderTools('create_seller_profile_success', {
            profileId: created.id,
            providerId,
            updated: false,
            serviceCategoryNames: created.serviceCategoryNames,
            profileCompletenessScore: created.profileCompletenessScore,
          });
        }

        upsertSellerEmbedding(created.id, created).catch((err) => {
          console.error('[create_seller_profile] Embedding failed:', err.message);
        });

        const profile = {
          id: created.id,
          seller_id: created.id,
          provider_id: created.providerId,
          service_category_names: created.serviceCategoryNames,
          service_area: created.serviceArea,
          availability: created.availability,
          credentials: created.credentials,
          pricing: created.pricing,
          preferences: created.preferences,
          bio: created.bio,
          profile_completeness_score: created.profileCompletenessScore,
          active: created.active,
        };

        return JSON.stringify({ success: true, profile });
      } catch (error) {
        logProviderTools('create_seller_profile_error', {
          providerId: parseInt(String(sellerId), 10) || null,
          error: error.message,
          stack: error.stack,
        });
        return JSON.stringify({ success: false, error: error.message });
      }
    },
    {
      name: 'create_seller_profile',
      description: `Create a seller profile. Call IMMEDIATELY when you have enough info—NEVER ask "Shall I create?" or "Ready to save?". Required: service_category_name.

Enough info = services + domain-specific details (equipment, certs, project types for concrete; license for plumber; etc.) + service_area + availability. You must ask domain questions FIRST—never lead with service area.

Pass ALL collected info in specific_requirements: concrete: equipment, certifications, project_sizes_sqft, new_build_vs_repair, materials; plumber: license_type, emergency_available; electrician: license_level, ev_charger, solar. Also pass: service_area, availability, pricing, credentials.`,
      schema: createSellerProfileSchema,
    }
  );

  return [createSellerProfileTool];
}
