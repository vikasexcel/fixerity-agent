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
  const prompt = `You are a professional profile writer for a service marketplace. Given collected information from a provider conversation, generate a complete professional seller profile using ONLY facts from the conversation.

COLLECTED INFORMATION (from conversation - JSON):
${JSON.stringify(collectedInfo, null, 2)}

CRITICAL:
- Conversation-derived only: extract EVERYTHING the provider said.
- Do NOT invent values.
- If unknown, return null or empty array/object.
- Keep domain-specific details rich and searchable.

Return valid JSON only with this shape:
{
  "service_category_names": ["<primary>", "<sub1>", "..."],
  "bio": "<2-4 sentence professional summary in first person>",
  "service_area": { "location": "<string|null>", "city": "<string|null>", "state": "<string|null>", "radius_miles": <number|null>, "zip_codes": [] },
  "availability": { "schedule": "<string|null>", "weekdays": "<string|null>", "weekends": "<string|null>", "weekday_evenings": <bool|null>, "emergency": <bool|null> },
  "credentials": { "licensed": <bool|null>, "insured": <bool|null>, "years_experience": <number|null>, "references_available": <bool|null>, "certifications": [], "certifications_not_held": [] },
  "pricing": { "hourly_rate_min": <number|null>, "hourly_rate_max": <number|null>, "fixed_prices": {}, "pricing_notes": "<string|null>", "packages": [] },
  "marketplace_profile": {
    "service_title": "<string|null>",
    "tagline": "<1-2 sentences|null>",
    "short_description": "<string|null>",
    "long_description": "<string|null>",
    "delivery_or_completion_time": "<string|null>",
    "languages_spoken": []
  },
  "preferences": {
    "min_job_size_hours": <number|null>,
    "max_travel_distance": <number|null>,
    "provides_materials": <bool|null>,
    "preferred_payment": [],
    "conversation_profile": {
      "equipment": [],
      "materials": [],
      "project_sizes_sqft": { "min": <number|null>, "max": <number|null> },
      "project_focus": "<residential|commercial|both|null>",
      "additional_services": [],
      "<other_domain_specific_key>": "<value>"
    }
  }
}

Notes:
- service_title/tagline/descriptions must be professional and directly grounded in user facts.
- languages_spoken must come from conversation only.
- Include domain depth in conversation_profile (tools, materials, methods, project sizes, standards, compliance, etc.).`;

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

function normalizeAvailability(availability) {
  if (availability == null) return null;
  if (typeof availability === 'string') return { schedule: availability };
  if (typeof availability === 'object') {
    return {
      schedule: availability.schedule ?? null,
      weekdays: availability.weekdays ?? null,
      weekends: availability.weekends ?? null,
      weekday_evenings: availability.weekday_evenings ?? null,
      emergency: availability.emergency ?? null,
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

        const providerDetails = await getProviderBasicDetails(providerId);
        const serviceAreaObj = normalizeServiceArea(service_area);
        const availabilityObj = normalizeAvailability(availability);

        const collectedInfo = {
          service_category_name: serviceCategoryName,
          bio_draft: bio,
          provider_identity: {
            first_name: providerDetails?.firstName ?? null,
            last_name: providerDetails?.lastName ?? null,
            email: providerDetails?.email ?? null,
            contact_number: providerDetails?.contactNumber ?? null,
          },
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

        const generatedServiceArea = normalizeServiceArea(generated?.service_area);
        const generatedAvailability = normalizeAvailability(generated?.availability);
        const finalServiceArea = generatedServiceArea ?? serviceAreaObj;
        const finalAvailability = generatedAvailability ?? availabilityObj;
        const finalBio = generated?.bio ?? bio ?? null;
        const finalServiceNames = generated?.service_category_names?.length
          ? generated.service_category_names
          : (serviceCategoryName ? [serviceCategoryName] : []);
        const finalCredentials = { ...(credentials ?? {}), ...(generated?.credentials ?? {}) };
        const finalPricing = generated?.pricing ?? pricing ?? { hourly_rate_min: null, hourly_rate_max: null, fixed_prices: {} };
        const defaultPrefs = {
          min_job_size_hours: null,
          max_travel_distance: null,
          provides_materials: null,
          preferred_payment: [],
          conversation_profile: {},
          marketplace_profile: {},
        };
        const generatedPrefs = generated?.preferences && typeof generated.preferences === 'object'
          ? generated.preferences
          : {};
        const finalPreferences = {
          ...defaultPrefs,
          ...generatedPrefs,
          conversation_profile: generatedPrefs?.conversation_profile && typeof generatedPrefs.conversation_profile === 'object'
            ? generatedPrefs.conversation_profile
            : {},
          marketplace_profile: {
            ...(generatedPrefs?.marketplace_profile && typeof generatedPrefs.marketplace_profile === 'object'
              ? generatedPrefs.marketplace_profile
              : {}),
            ...(generated?.marketplace_profile && typeof generated.marketplace_profile === 'object'
              ? generated.marketplace_profile
              : {}),
          },
        };

        let score = 0;
        if (finalServiceNames.length > 0) score += 20;
        if (finalServiceArea?.location) score += 15;
        if (finalAvailability?.schedule || finalAvailability?.weekdays || finalAvailability?.weekends) score += 15;
        if (finalPricing?.hourly_rate_max || (finalPricing?.fixed_prices && Object.keys(finalPricing.fixed_prices).length > 0)) score += 15;
        if (finalCredentials?.years_experience) score += 10;
        if (finalCredentials?.licensed !== undefined && finalCredentials?.licensed !== null) score += 5;
        if (finalCredentials?.references_available) score += 5;
        if (finalBio) score += 10;
        if (finalPreferences?.marketplace_profile?.service_title) score += 5;
        if (finalPreferences?.marketplace_profile?.tagline) score += 5;
        if (finalPreferences?.marketplace_profile?.delivery_or_completion_time) score += 5;
        if (finalPreferences?.conversation_profile && Object.keys(finalPreferences.conversation_profile).length > 0) score += 5;

        const profileData = {
          providerId,
          firstName: providerDetails?.firstName ?? null,
          lastName: providerDetails?.lastName ?? null,
          email: providerDetails?.email ?? null,
          gender: providerDetails?.gender ?? null,
          contactNumber: providerDetails?.contactNumber ?? null,
          serviceCategoryNames: finalServiceNames,
          serviceArea: finalServiceArea,
          availability: finalAvailability,
          credentials: finalCredentials,
          pricing: finalPricing,
          preferences: finalPreferences,
          bio: finalBio,
          profileCompletenessScore: score,
          active: true,
        };

        // Always create a new profile (never update existing).
        // This allows one provider to have multiple seller profiles.
        const created = await prisma.sellerProfile.create({
          data: { id: randomUUID(), ...profileData },
        });
        logProviderTools('create_seller_profile_success', {
          profileId: created.id,
          providerId,
          updated: false,
          serviceCategoryNames: created.serviceCategoryNames,
          profileCompletenessScore: created.profileCompletenessScore,
        });

        upsertSellerEmbedding(created.id, created).catch((err) => {
          console.error('[create_seller_profile] Embedding failed:', err.message);
        });

        const profile = {
          id: created.id,
          seller_id: created.id,
          provider_id: created.providerId,
          first_name: created.firstName,
          last_name: created.lastName,
          email: created.email,
          contact_number: created.contactNumber,
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

Pass ALL collected info in specific_requirements: concrete: equipment, certifications, project_sizes_sqft, new_build_vs_repair, materials; plumber: license_type, emergency_available; electrician: license_level, ev_charger, solar.
Also include marketplace fields in specific_requirements when known: service_title, tagline, short_description, long_description, delivery_or_completion_time, languages_spoken, packages.
Also pass: service_area, availability, pricing, credentials. Never invent values.`,
      schema: createSellerProfileSchema,
    }
  );

  return [createSellerProfileTool];
}
