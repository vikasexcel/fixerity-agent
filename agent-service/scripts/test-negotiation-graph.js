/**
 * Test the negotiation graph in isolation (one job + one provider).
 *
 * Prerequisites:
 *   - OPENAI_API_KEY set in agent-service/.env
 *
 * Usage (from agent-service directory):
 *   node scripts/test-negotiation-graph.js
 * Or from scripts directory:
 *   node test-negotiation-graph.js
 */

import path from 'path';
import { fileURLToPath } from 'url';
import dotenv from 'dotenv';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
dotenv.config({ path: path.join(__dirname, '..', '.env') });

const { runNegotiation } = await import('../src/agents/negotiationGraph.js');

const job = {
  id: 'job_test_1',
  title: 'Home cleaning',
  description: 'Full house clean',
  budget: { min: 50, max: 150 },
  startDate: '2025-03-01',
  endDate: '2025-03-15',
  priorities: [],
  service_category_id: 1,
};

const providerServiceData = {
  min_price: 60,
  max_price: 120,
  deadline_in_days: 7,
  average_rating: 4.5,
  total_completed_order: 10,
};

async function main() {
  console.log('Negotiation graph test: one job + one provider');
  console.log('Job budget:', job.budget);
  console.log('Provider range:', providerServiceData.min_price, '-', providerServiceData.max_price, ',', providerServiceData.deadline_in_days, 'days');
  console.log('');

  const result = await runNegotiation({
    job,
    providerId: 'provider_8001',
    providerServiceData,
    maxRounds: 3,
    deadline_ts: Date.now() + 60_000,
  });

  console.log('Result:', JSON.stringify(result, null, 2));

  const ok =
    typeof result.negotiatedPrice === 'number' &&
    typeof result.negotiatedCompletionDays === 'number' &&
    ['accepted', 'timeout'].includes(result.status);

  if (!ok) {
    console.error('Assertion failed: expected negotiatedPrice (number), negotiatedCompletionDays (number), status (accepted|timeout)');
    process.exit(1);
  }
  console.log('OK: negotiation completed with', result.status);
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
