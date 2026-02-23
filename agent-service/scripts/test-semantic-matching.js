/**
 * Test Option 2: Provider matching without categories (semantic search only).
 *
 * Verifies that runProviderMatching works when job has no service_category_id or service_category_name.
 *
 * Usage: node scripts/test-semantic-matching.js
 */

import { runProviderMatching } from '../src/graphs/buyer/providerMatchingGraph.js';

// Job with NO category - relies purely on semantic search
const jobNoCategory = {
  id: 'test_job_no_category',
  title: 'Cat sitting for two cats',
  description: 'I need someone to look after my two cats while I\'m away for a week. Feeding at 8am and 6pm, litter box maintenance, home access via lockbox.',
  budget: { min: 100, max: 200 },
  startDate: '2025-03-10',
  endDate: '2025-03-17',
  priorities: [],
  service_category_id: null,
  service_category_name: null,
  location: { address: 'Downtown Los Angeles' },
};

// Job WITH category - verify existing flow still works
const jobWithCategory = {
  id: 'test_job_with_category',
  title: 'Home cleaning',
  description: 'Full house clean',
  budget: { min: 50, max: 150 },
  startDate: '2025-03-01',
  endDate: '2025-03-15',
  priorities: [],
  service_category_id: 1,
  service_category_name: 'Cleaning',
  location: null,
};

async function main() {
  console.log('=== Test 1: Job WITHOUT category (semantic search only) ===\n');
  const result1 = await runProviderMatching(jobNoCategory);
  console.log('\nProviders found:', result1?.providers?.length ?? 0);
  if (result1?.providers?.length > 0) {
    console.log('First provider:', result1.providers[0]?.provider_id ?? result1.providers[0]?.id);
  }

  console.log('\n=== Test 2: Job WITH category (verify existing flow) ===\n');
  const result2 = await runProviderMatching(jobWithCategory);
  console.log('\nProviders found:', result2?.providers?.length ?? 0);

  console.log('\n=== Tests complete ===');
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
