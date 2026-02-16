/**
 * Seller query service.
 * Builds an optimized retrieval query string from a seller profile for semantic search over job embeddings.
 */
import 'dotenv/config';
import { ChatOpenAI } from '@langchain/openai';
import { HumanMessage, SystemMessage } from '@langchain/core/messages';

const OPENAI_API_KEY = process.env.OPENAI_API_KEY;
const SYSTEM_PROMPT = `You are building a search query to find job listings that match a service provider's (seller's) profile.

Given the seller's profile (services, area, availability, pricing, credentials, bio), output a single, concise search query string that would best match job postings in a semantic search.
The query should capture: service types they offer, location/area they serve, budget range they work with, timeline/availability, and any credentials (e.g. licensed, references).
Use natural language as if describing the kinds of jobs this provider is looking for. Output ONLY the query string, no JSON, no explanation, no prefix.`;

/**
 * Build seller profile summary text for the LLM.
 * @param {object} profile - Seller profile (service_category_names, service_area, availability, credentials, pricing, preferences, bio)
 * @returns {string}
 */
function profileToPromptText(profile) {
  const parts = [];
  if (profile?.bio && String(profile.bio).trim()) {
    parts.push('Bio: ' + String(profile.bio).trim());
  }
  if (Array.isArray(profile?.service_category_names) && profile.service_category_names.length > 0) {
    parts.push('Services offered: ' + profile.service_category_names.join(', '));
  }
  if (profile?.service_area && typeof profile.service_area === 'object') {
    if (profile.service_area.location) {
      parts.push('Service area: ' + String(profile.service_area.location));
    }
  }
  if (profile?.availability) {
    const a = profile.availability;
    if (a.schedule) parts.push('Availability: ' + String(a.schedule));
    else if (a.weekday_evenings || a.weekends) {
      const opts = [];
      if (a.weekday_evenings) opts.push('weekday evenings');
      if (a.weekends) opts.push('weekends');
      if (opts.length) parts.push('Availability: ' + opts.join(', '));
    }
  }
  if (profile?.pricing && typeof profile.pricing === 'object') {
    const p = profile.pricing;
    if (p.hourly_rate_min != null || p.hourly_rate_max != null) {
      parts.push('Pricing: $' + (p.hourly_rate_min ?? '?') + '-' + (p.hourly_rate_max ?? '?') + ' per hour');
    }
  }
  if (profile?.credentials && typeof profile.credentials === 'object') {
    const c = profile.credentials;
    const credParts = [];
    if (c.licensed === true) credParts.push('licensed');
    if (c.references_available === true) credParts.push('references available');
    if (c.years_experience != null) credParts.push(c.years_experience + ' years experience');
    if (credParts.length) parts.push('Credentials: ' + credParts.join(', '));
  }
  if (profile?.preferences && typeof profile.preferences === 'object' && Object.keys(profile.preferences).length > 0) {
    parts.push('Preferences: ' + JSON.stringify(profile.preferences));
  }
  return parts.join('\n') || 'No profile details';
}

/**
 * Build an optimized retrieval query for the given seller profile using an LLM.
 * @param {object} profile - Seller profile (service_category_names, service_area, availability, credentials, pricing, bio, etc.)
 * @returns {Promise<string>} Single query string for embedding and semantic search over jobs
 */
export async function buildOptimizedQueryForSellerProfile(profile) {
  if (!profile) {
    return 'service provider jobs';
  }

  const names = profile?.service_category_names ?? [];
  const hasServices = Array.isArray(names) && names.length > 0;

  if (!OPENAI_API_KEY || !OPENAI_API_KEY.trim()) {
    const fallback = hasServices
      ? names.join(' ') + ' ' + (profile.bio || '') + ' ' + (profile.service_area?.location || '')
      : (profile.bio || 'service provider jobs');
    const query = String(fallback).trim().slice(0, 500);
    console.log('\n[SellerQueryService] No OPENAI_API_KEY — using fallback query:', query + '\n');
    return query || 'service provider jobs';
  }

  const llm = new ChatOpenAI({
    model: 'gpt-4o-mini',
    temperature: 0.2,
    openAIApiKey: OPENAI_API_KEY,
  });

  const userText = profileToPromptText(profile);

  console.log('\n' + '='.repeat(60));
  console.log('[SellerQueryService] Building retrieval query from seller profile');
  console.log('='.repeat(60));
  console.log('\n  Profile input:');
  console.log('  ' + '-'.repeat(56));
  console.log(userText.split('\n').map((line) => '  ' + line).join('\n'));
  console.log('  ' + '-'.repeat(56) + '\n');

  const response = await llm.invoke([
    new SystemMessage(SYSTEM_PROMPT),
    new HumanMessage(`Seller profile:\n${userText}\n\nOutput only the single search query string for finding matching jobs:`),
  ]);

  const content = response?.content;
  let query;
  if (typeof content !== 'string' || !content.trim()) {
    query = hasServices ? names.join(' ') + ' jobs' : 'service provider jobs';
  } else {
    query = content.trim().slice(0, 1000);
  }

  console.log('  Built retrieval query (for job search):');
  console.log('  ' + '-'.repeat(56));
  console.log('  ' + query);
  console.log('  ' + '-'.repeat(56));
  console.log('='.repeat(60) + '\n');

  return query;
}
