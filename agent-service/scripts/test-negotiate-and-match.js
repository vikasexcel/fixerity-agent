/**
 * Test POST /agent/buyer/negotiate-and-match (full flow: fetch providers, negotiate, return deals).
 *
 * Prerequisites:
 *   - Agent service running: npm run start (or node src/index.js)
 *   - Laravel running with seeded data (for provider list)
 *   - OPENAI_API_KEY in agent-service/.env
 *
 * Test credentials (same as test-buyer-agent.js): user_id: 2, access_token: 652220102026020270
 *
 * Usage (from agent-service directory):
 *   node scripts/test-negotiate-and-match.js
 */

const AGENT_URL = process.env.AGENT_URL ?? 'http://localhost:3001';
const TEST_USER_ID = process.env.TEST_USER_ID ?? 2;
const TEST_ACCESS_TOKEN = process.env.TEST_ACCESS_TOKEN ?? '652220102026020270';

const job = {
  id: 'job_1',
  title: 'Home cleaning',
  description: 'Full house clean',
  budget: { min: 50, max: 150 },
  startDate: '2025-03-01',
  endDate: '2025-03-15',
  priorities: [],
  service_category_id: 1,
};

async function main() {
  const url = `${AGENT_URL}/agent/buyer/negotiate-and-match`;
  const res = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      user_id: TEST_USER_ID,
      access_token: TEST_ACCESS_TOKEN,
      job,
    }),
  });

  const data = await res.json().catch(() => ({}));

  if (!res.ok) {
    console.error('HTTP', res.status, data?.error ?? data);
    process.exit(1);
  }

  const deals = data.deals;
  if (!Array.isArray(deals)) {
    console.error('Response missing or invalid deals array:', data);
    process.exit(1);
  }

  if (deals.length > 0) {
    const first = deals[0];
    if (
      typeof first.negotiatedPrice !== 'number' ||
      typeof first.negotiatedCompletionDays !== 'number'
    ) {
      console.error('First deal missing negotiatedPrice or negotiatedCompletionDays:', first);
      process.exit(1);
    }
    console.log('First deal:', first.sellerAgent?.name, '|', first.negotiatedPrice, '|', first.negotiatedCompletionDays, 'days');
  }

  console.log('Deals count:', deals.length);
  if (data.reply) console.log('Reply:', data.reply);
  console.log('OK');
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
