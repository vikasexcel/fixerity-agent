/**
 * Test script for AI category inference (Option 1).
 * Verifies that create_job infers and matches categories from conversation data.
 *
 * Prerequisites:
 * - Agent service dependencies installed
 * - Laravel API running (for getCategoriesOrFetch)
 * - Valid buyer credentials in env or passed as args
 *
 * Usage:
 *   node scripts/test-category-inference.js [buyerId] [accessToken]
 *
 * Example:
 *   node scripts/test-category-inference.js 2 652220102026020270
 */

import { createBuyerAgentTools } from '../src/graphs/buyer/buyerAgentTools.js';

const BUYER_ID = process.argv[2] ?? process.env.TEST_BUYER_ID ?? '2';
const ACCESS_TOKEN = process.argv[3] ?? process.env.TEST_ACCESS_TOKEN ?? '652220102026020270';

const CAT_SITTING_DATA = {
  job_type: 'cat sitting',
  cat_count: 2,
  cat_1_name: 'Milo',
  cat_1_age: 3,
  cat_1_personality: 'playful and social',
  cat_2_name: 'Luna',
  cat_2_age: 7,
  cat_2_personality: 'calm and shy',
  feeding_schedule: '8am and 6pm',
  visit_frequency: 'twice daily',
  visit_duration: '30-40 minutes',
  dates: 'March 10 to March 17',
  location: 'downtown Los Angeles',
};

const WALL_MURAL_DATA = {
  job_type: 'wall mural painting',
  wall_location: 'living room',
  indoor_outdoor: 'indoor',
  wall_size: '12 feet wide, 8 feet tall',
  style: 'realistic portrait with abstract background',
  colors: 'deep blues, gold accents',
  subject: 'portrait of homeowner',
};

const LAPTOP_REPAIR_DATA = {
  job_type: 'laptop repair',
  issue: 'won\'t turn on, possibly hardware',
  make_model: 'HP Pavilion 15',
  budget: { min: 100, max: 200 },
  deadline: 'one week',
  location: 'Austin, Texas',
};

async function runTest(name, conversationData) {
  console.log(`\n--- ${name} ---`);
  const tools = createBuyerAgentTools({ buyerId: BUYER_ID, accessToken: ACCESS_TOKEN });
  const createJobTool = tools.find((t) => t.name === 'create_job');
  if (!createJobTool) {
    console.error('create_job tool not found');
    return null;
  }

  try {
    const result = await createJobTool.invoke({ conversation_data: conversationData });
    const parsed = JSON.parse(result);
    if (parsed.success && parsed.job) {
      console.log('Job created:', parsed.job.id);
      console.log('Title:', parsed.job.title);
      console.log('Category inference: check server logs for [create_job] Inferred category and Matched to category');
      return parsed.job;
    }
    console.error('Tool returned:', parsed);
    return null;
  } catch (err) {
    console.error('Error:', err.message);
    return null;
  }
}

async function main() {
  console.log('Category Inference Test');
  console.log('Buyer ID:', BUYER_ID);
  console.log('Access Token:', ACCESS_TOKEN.slice(0, 8) + '...');

  await runTest('Cat Sitting', CAT_SITTING_DATA);
  await runTest('Wall Mural', WALL_MURAL_DATA);
  await runTest('Laptop Repair', LAPTOP_REPAIR_DATA);

  console.log('\nDone. Check server logs for [inferCategory] and [create_job] Matched to category.');
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
