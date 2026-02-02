/**
 * Full buyer agent test script using seeded test data.
 *
 * Prerequisites:
 * - Laravel running (php artisan serve) with seeded data: php artisan db:seed --class=BuyerAgentTestDataSeeder
 * - Agent service running: npm run start (or node src/index.js)
 *
 * Test credentials (from BuyerAgentTestDataSeeder):
 *   user_id: 2
 *   access_token: 652220102026020270
 *
 * Usage:
 *   node scripts/test-buyer-agent.js                    # run all sample messages
 *   node scripts/test-buyer-agent.js "Your message"    # run one message
 *
 * Sample messages exercise: searchProviders, getProviderDetails, getProviderPackages,
 * getOrderHistory, getWalletBalance, getAddressList, getProviderReviews, getProviderTimeSlots.
 */

const AGENT_URL = process.env.AGENT_URL ?? 'http://localhost:3001';
const TEST_USER_ID = 2;
const TEST_ACCESS_TOKEN = '652220102026020270';

const SAMPLE_MESSAGES = [
  {
    name: 'searchProviders (Baby Care near seeded location)',
    message: 'Search for Baby Care providers near latitude 22.3 and longitude 70.8. Use service category 12 and sub category 2.',
  },
  {
    name: 'getProviderDetails',
    message: 'Get details for provider 8001 for Baby Care at latitude 22.3 and longitude 70.8.',
  },
  {
    name: 'getProviderPackages',
    message: 'What packages does provider 8001 offer for Baby Care (service category 12)?',
  },
  {
    name: 'getOrderHistory',
    message: 'What is my order history? Use timezone UTC.',
  },
  {
    name: 'getWalletBalance',
    message: "What's my wallet balance?",
  },
  {
    name: 'getAddressList',
    message: 'List my saved addresses.',
  },
  {
    name: 'getProviderReviews',
    message: 'Show me reviews for provider 8001 in Baby Care, page 1.',
  },
  {
    name: 'getProviderTimeSlots',
    message: 'What time slots does provider 8001 have for tomorrow? Use today plus one day for the date.',
  },
];

async function sendMessage(message) {
  const res = await fetch(`${AGENT_URL}/agent/buyer/chat`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      user_id: TEST_USER_ID,
      message,
      access_token: TEST_ACCESS_TOKEN,
    }),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    throw new Error(data?.error ?? res.statusText ?? `HTTP ${res.status}`);
  }
  return data?.reply ?? '(no reply)';
}

async function main() {
  const singleMessage = process.argv.slice(2).join(' ').trim();

  if (singleMessage) {
    console.log('Message:', singleMessage);
    console.log('---');
    try {
      const reply = await sendMessage(singleMessage);
      console.log('Reply:', reply);
    } catch (err) {
      console.error('Error:', err.message);
      process.exit(1);
    }
    return;
  }

  console.log('Buyer Agent Test (seeded data: user_id=2, access_token=652220102026020270)\n');
  console.log(`Agent URL: ${AGENT_URL}\n`);

  for (const { name, message } of SAMPLE_MESSAGES) {
    console.log(`[${name}]`);
    console.log('  Message:', message);
    try {
      const reply = await sendMessage(message);
      const preview = reply.length > 200 ? reply.slice(0, 200) + '...' : reply;
      console.log('  Reply:', preview);
    } catch (err) {
      console.log('  Error:', err.message);
    }
    console.log('');
  }
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
