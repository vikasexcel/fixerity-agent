/**
 * Service category manager - shared by buyer (job creation) and provider (profile creation).
 * Fetches and matches service categories from Laravel API.
 */

import { HumanMessage, SystemMessage } from '@langchain/core/messages';
import { LARAVEL_API_BASE_URL } from '../config/index.js';
import { cacheService } from './cacheService.js';

class ServiceCategoryManager {
  async fetchFromAPI(userId, accessToken) {
    try {
      const response = await fetch(`${LARAVEL_API_BASE_URL}/customer/home`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          user_id: userId,
          access_token: accessToken,
          app_version: '1.0'
        })
      });

      const data = await response.json();
      if (data.status === 1 && data.services) {
        return data.services;
      }
      return null;
    } catch (error) {
      console.error('[ServiceCategory] API fetch error:', error.message);
      return null;
    }
  }

  /**
   * Fetch service categories for sellers/providers (on-demand/get-service-list).
   * Returns same shape as customer API: { service_category_id, service_category_name }.
   */
  async fetchProviderFromAPI(providerId, accessToken) {
    try {
      const response = await fetch(`${LARAVEL_API_BASE_URL}/on-demand/get-service-list`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          provider_id: providerId,
          access_token: accessToken,
        })
      });

      const data = await response.json();
      if (data.status === 1 && data.service_category_list) {
        return data.service_category_list.map((s) => ({
          service_category_id: s.service_cat_id ?? s.service_category_id,
          service_category_name: s.service_cat_name ?? s.service_category_name,
        }));
      }
      return null;
    } catch (error) {
      console.error('[ServiceCategory] Provider API fetch error:', error.message);
      return null;
    }
  }

  async getCategoriesOrFetch(userId, accessToken) {
    return await cacheService.getServiceCategories(
      async () => await this.fetchFromAPI(userId, accessToken),
      accessToken
    );
  }

  /**
   * Get provider/seller service categories (for seller profile flow).
   * Uses on-demand/get-service-list and caches per provider.
   */
  async getProviderCategoriesOrFetch(providerId, accessToken) {
    return await cacheService.getOrFetch(
      `service_categories:provider:${providerId}`,
      async () => await this.fetchProviderFromAPI(providerId, accessToken),
      86400
    );
  }

  async findCategory(userInput, categories, llm) {
    if (!categories || categories.length === 0) {
      return null;
    }

    const categoryList = categories.map(c => 
      `- ID: ${c.service_category_id}, Name: "${c.service_category_name}"`
    ).join('\n');

    const prompt = `
You are a service category matcher. Given a user's request, find the BEST matching service category.

Available categories:
${categoryList}

User's request: "${userInput}"

Instructions:
1. Find the category that BEST matches what the user is looking for
2. Be FLEXIBLE with matching - consider synonyms, related terms, and broader categories
3. Examples of good matches:
   - "Technology Services" → "Computer Repair" or "IT Services" or "Electronics"
   - "Pet Care" → "Pet Sitting" or "Dog Walking" or "Veterinary"
   - "Art Services" → "Painting" or "Interior Design" or "Custom Art"
   - "Home Improvement" → "Plumbing" or "Electrical" or "Handyman"
4. Only return null if NO category is even remotely related
5. When in doubt, pick the closest related category

Reply ONLY with JSON:
{
  "matched": true/false,
  "category_id": <number or null>,
  "category_name": "<string or null>",
  "confidence": "<high/medium/low>",
  "reason": "<brief explanation>"
}
`;

    try {
      const res = await llm.invoke([
        new SystemMessage('Only output valid JSON. Be flexible and generous with matching.'),
        new HumanMessage(prompt),
      ]);

      let content = res.content.trim();
      content = content.replace(/```json\n?/g, '').replace(/```\n?/g, '');
      const result = JSON.parse(content);
      
      console.log(`[ServiceCategory] Match result for "${userInput}":`, result);
      
      return result;
    } catch (error) {
      console.error('[ServiceCategory] LLM matching error:', error.message);
      return null;
    }
  }
}

export const serviceCategoryManager = new ServiceCategoryManager();
