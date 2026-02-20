/**
 * Seller query service.
 * Builds an optimized retrieval query from a seller profile for semantic search
 * over job embeddings.
 *
 * Key improvements over v1:
 *  1. profileToPromptText now reads ALL availability field shapes from the DB
 *     (weekdays/weekends strings, boolean flags, schedule string) — prevents
 *     the LLM from hallucinating availability.
 *  2. Pricing handles both { hourly_rate: 60 } and { hourly_rate_min, hourly_rate_max }
 *     shapes — was silently dropping pricing before.
 *  3. System prompt instructs LLM to build a provider-facing query that describes
 *     the KINDS OF JOBS the seller wants, not what the seller does — better
 *     semantic alignment with job postings.
 *  4. Returns the seller's service_category_names alongside the query so
 *     jobMatchingGraph can pass them to searchJobsByQuery as a category filter.
 */

import 'dotenv/config';
import { ChatOpenAI } from '@langchain/openai';
import { HumanMessage, SystemMessage } from '@langchain/core/messages';

const OPENAI_API_KEY = process.env.OPENAI_API_KEY;

const SYSTEM_PROMPT = `You are building a search query to find job listings that match a service provider's profile.

Given the seller's profile, output a SINGLE natural language query that describes the JOBS this provider is looking for.
Write it from the job-seeker's perspective — as if describing job postings they would want to find.

Rules:
- Include the specific service type(s) they offer (this is the most important signal)
- Include the location/area they serve
- Include budget range they are comfortable with (based on their pricing)
- Include availability signals ONLY if explicitly stated — never assume or invent availability
- Keep it under 150 words
- Output ONLY the query string — no JSON, no explanation, no prefix, no quotes`;

/* ─────────────────────────────────────────────────────────────
   PROFILE TEXT BUILDER
───────────────────────────────────────────────────────────── */

/**
 * Convert a seller profile to a structured text summary for the LLM.
 * Handles all known field shapes from the DB.
 *
 * @param {object} profile
 * @returns {string}
 */
function profileToPromptText(profile) {
  const parts = [];

  // ── Services ──────────────────────────────────────────────────────────────
  const services = Array.isArray(profile?.service_category_names)
    ? profile.service_category_names.filter(Boolean)
    : [];
  if (services.length > 0) {
    parts.push(`Services offered: ${services.join(', ')}`);
  }

  // ── Location ──────────────────────────────────────────────────────────────
  const area = profile?.service_area;
  if (area && typeof area === 'object') {
    const loc = area.location || area.city || area.address;
    if (loc) parts.push(`Service area: ${String(loc)}`);
  } else if (typeof area === 'string' && area.trim()) {
    parts.push(`Service area: ${area.trim()}`);
  }

  // ── Bio ───────────────────────────────────────────────────────────────────
  if (profile?.bio && String(profile.bio).trim()) {
    parts.push(`Bio: ${String(profile.bio).trim()}`);
  }

  // ── Availability — handle ALL known field shapes from DB ──────────────────
  // Shape 1: { weekdays: "8 AM - 6 PM", weekends: "not available" }
  // Shape 2: { weekday_evenings: true, weekends: true }
  // Shape 3: { schedule: "Mon-Fri 9am-5pm" }
  const avail = profile?.availability;
  if (avail && typeof avail === 'object') {
    const availParts = [];

    // Shape 3 — free-text schedule
    if (avail.schedule && String(avail.schedule).trim()) {
      availParts.push(String(avail.schedule).trim());
    }

    // Shape 1 — weekdays/weekends as descriptive strings
    if (avail.weekdays && String(avail.weekdays).trim()) {
      availParts.push(`weekdays: ${String(avail.weekdays).trim()}`);
    }
    if (avail.weekends && String(avail.weekends).trim()) {
      // Only add if it's not "not available" — avoid misleading the LLM
      const wknd = String(avail.weekends).trim().toLowerCase();
      if (wknd !== 'not available' && wknd !== 'unavailable' && wknd !== 'no') {
        availParts.push(`weekends: ${String(avail.weekends).trim()}`);
      } else {
        availParts.push('not available on weekends');
      }
    }

    // Shape 2 — boolean flags
    if (avail.weekday_mornings === true) availParts.push('weekday mornings');
    if (avail.weekday_evenings === true) availParts.push('weekday evenings');
    if (avail.same_day === true) availParts.push('same-day available');
    if (avail.emergency === true) availParts.push('emergency jobs');

    if (availParts.length > 0) {
      parts.push(`Availability: ${availParts.join(', ')}`);
    }
  }

  // ── Pricing — handle ALL known field shapes from DB ───────────────────────
  // Shape 1: { hourly_rate: 60 }           ← actual DB data
  // Shape 2: { hourly_rate_min: 50, hourly_rate_max: 80 }
  // Shape 3: { fixed_prices: { ... } }
  const pricing = profile?.pricing;
  if (pricing && typeof pricing === 'object') {
    const priceParts = [];

    // Shape 1 — single flat hourly rate
    if (pricing.hourly_rate != null) {
      priceParts.push(`$${pricing.hourly_rate} per hour`);
    }
    // Shape 2 — min/max range
    if (pricing.hourly_rate_min != null && pricing.hourly_rate_max != null) {
      priceParts.push(`$${pricing.hourly_rate_min}–$${pricing.hourly_rate_max} per hour`);
    } else if (pricing.hourly_rate_min != null) {
      priceParts.push(`from $${pricing.hourly_rate_min} per hour`);
    } else if (pricing.hourly_rate_max != null) {
      priceParts.push(`up to $${pricing.hourly_rate_max} per hour`);
    }
    // Shape 3 — fixed prices
    if (pricing.fixed_prices && typeof pricing.fixed_prices === 'object') {
      const fixed = Object.entries(pricing.fixed_prices)
        .map(([k, v]) => `${k}: $${v}`)
        .join(', ');
      if (fixed) priceParts.push(`fixed: ${fixed}`);
    }
    if (pricing.minimum_job != null) {
      priceParts.push(`minimum $${pricing.minimum_job}`);
    }

    if (priceParts.length > 0) {
      parts.push(`Pricing: ${priceParts.join('; ')}`);
    }
  }

  // ── Credentials ───────────────────────────────────────────────────────────
  const cred = profile?.credentials;
  if (cred && typeof cred === 'object') {
    const credParts = [];
    // Handle both { licensed: true } and { license: "general license" }
    if (cred.licensed === true) credParts.push('licensed');
    if (cred.license && String(cred.license).trim()) credParts.push(String(cred.license).trim());
    if (cred.references_available === true) credParts.push('references available');
    if (cred.background_check === true) credParts.push('background checked');
    if (cred.years_experience != null) credParts.push(`${cred.years_experience} years experience`);
    if (credParts.length > 0) parts.push(`Credentials: ${credParts.join(', ')}`);
  }

  // ── Preferences ───────────────────────────────────────────────────────────
  const prefs = profile?.preferences;
  if (prefs && typeof prefs === 'object' && Object.keys(prefs).length > 0) {
    const prefParts = [];
    if (prefs.job_types && Array.isArray(prefs.job_types)) {
      prefParts.push(`specialises in: ${prefs.job_types.join(', ')}`);
    }
    if (prefs.min_budget != null) prefParts.push(`min budget $${prefs.min_budget}`);
    if (prefs.max_budget != null) prefParts.push(`max budget $${prefs.max_budget}`);
    if (prefParts.length > 0) parts.push(`Preferences: ${prefParts.join('; ')}`);
  }

  return parts.join('\n') || 'No profile details available';
}

/* ─────────────────────────────────────────────────────────────
   MAIN EXPORT
───────────────────────────────────────────────────────────── */

/**
 * Build an optimized retrieval query for the given seller profile.
 * Also returns the seller's normalised service_category_names so the
 * caller can pass them directly to searchJobsByQuery as a category filter.
 *
 * @param {object} profile - Seller profile
 * @returns {Promise<{ query: string, serviceCategories: string[] }>}
 */
export async function buildOptimizedQueryForSellerProfile(profile) {
  const names = Array.isArray(profile?.service_category_names)
    ? profile.service_category_names.filter(Boolean)
    : [];

  const serviceCategories = names.map((n) => String(n).toLowerCase().trim()).filter(Boolean);

  if (!profile) {
    return { query: 'service provider jobs', serviceCategories: [] };
  }

  // ── Fallback if no OpenAI key ──────────────────────────────────────────────
  if (!OPENAI_API_KEY || !OPENAI_API_KEY.trim()) {
    const area = profile.service_area?.location || '';
    const fallback = [
      serviceCategories.length > 0 ? `${serviceCategories.join(', ')} jobs` : '',
      area ? `in ${area}` : '',
      profile.bio || '',
    ].filter(Boolean).join(' ').trim().slice(0, 500);

    const query = fallback || 'service provider jobs';
    console.log('[SellerQueryService] No OPENAI_API_KEY — fallback query:', query);
    return { query, serviceCategories };
  }

  const llm = new ChatOpenAI({
    model: 'gpt-4o-mini',
    temperature: 0.1,  // Lower temp = more deterministic, less hallucination
    openAIApiKey: OPENAI_API_KEY,
  });

  const userText = profileToPromptText(profile);

  console.log('\n' + '='.repeat(60));
  console.log('[SellerQueryService] Building retrieval query from seller profile');
  console.log('='.repeat(60));
  console.log('\n  Profile input:');
  console.log('  ' + '-'.repeat(56));
  userText.split('\n').forEach((line) => console.log('  ' + line));
  console.log('  ' + '-'.repeat(56) + '\n');

  let query;
  try {
    const response = await llm.invoke([
      new SystemMessage(SYSTEM_PROMPT),
      new HumanMessage(
        `Seller profile:\n${userText}\n\n` +
        `Output only the single search query string describing the job postings this seller wants to find:`,
      ),
    ]);

    const content = response?.content;
    if (typeof content === 'string' && content.trim()) {
      query = content.trim().slice(0, 1000);
    } else {
      query = serviceCategories.length > 0
        ? `${serviceCategories.join(', ')} jobs`
        : 'service provider jobs';
    }
  } catch (err) {
    console.error('[SellerQueryService] LLM error:', err.message);
    query = serviceCategories.length > 0
      ? `${serviceCategories.join(', ')} jobs`
      : 'service provider jobs';
  }

  console.log('  Built retrieval query (for job search):');
  console.log('  ' + '-'.repeat(56));
  console.log('  ' + query);
  console.log('  ' + '-'.repeat(56));
  console.log(`  Service categories for filter: [${serviceCategories.join(', ')}]`);
  console.log('='.repeat(60) + '\n');

  return { query, serviceCategories };
}