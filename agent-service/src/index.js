import express from 'express';
import dotenv from 'dotenv';

import { runBuyerMatchAgent } from './agents/buyerMatchAgent.js';
import { runBuyerAgent } from './agents/buyerChatAgent.js';
import { runNegotiationAndMatch, runNegotiationAndMatchStream } from './agents/negotiationOrchestrator.js';
import { runSellerMatchAgent, getProviderServiceData } from './agents/sellerMatchAgent.js';
import { PORT } from './config/index.js';
import cors from 'cors';
import { sequelize } from './db.js';
import JobMatchQuote from './models/JobMatchQuote.js';
import { saveDealsForJob, getDealsForJob } from './lib/jobMatchQuoteStore.js';
dotenv.config();

const app = express();

app.use(express.json());
app.use(cors({
  origin: process.env.FRONTEND_URL,
  credentials: true,
}));
app.get('/', (req, res) => {
  res.send('Hello World');
});

/**
 * POST /agent/buyer/match
 * Body: { user_id, access_token, job: { id, title, budget, priorities, service_category_id?, ... }, message?: string }
 * Returns: { deals: Array, reply?: string }
 */
app.post('/agent/buyer/match', async (req, res) => {
  const { user_id: userId, access_token: accessToken, job, message } = req.body ?? {};

  if (userId == null || typeof accessToken !== 'string' || !accessToken.trim() || !job || typeof job !== 'object') {
    return res.status(400).json({
      error: 'Missing or invalid body: user_id, access_token, and job are required.',
    });
  }
  if (!Array.isArray(job.priorities)) {
    return res.status(400).json({
      error: 'Job must include a priorities array.',
    });
  }

  const opts = typeof message === 'string' && message.trim() ? { userMessage: message.trim() } : {};

  try {
    const result = await runBuyerMatchAgent(userId, accessToken.trim(), job, opts);
    return res.json(result);
  } catch (err) {
    const message = err?.message ?? 'Match request failed';
    if (message.includes('401') || message.toLowerCase().includes('unauthorized')) {
      return res.status(401).json({ error: 'Unauthorized. Check your access token.' });
    }
    if (message.includes('status') && message.includes('0')) {
      return res.status(502).json({ error: 'Backend API error. Please try again.' });
    }
    return res.status(500).json({ error: message });
  }
});

/**
 * POST /agent/buyer/negotiate-and-match
 * Body: { user_id, access_token, job: { id, title, budget, priorities, service_category_id?, ... }, negotiation_max_rounds?, negotiation_time_seconds? }
 * Returns: { deals: Array, reply?: string } with each deal including negotiatedPrice, negotiatedCompletionDays, negotiationStatus
 */
app.post('/agent/buyer/negotiate-and-match', async (req, res) => {
  const { user_id: userId, access_token: accessToken, job, negotiation_max_rounds: maxRounds, negotiation_time_seconds: negotiationTimeSeconds } = req.body ?? {};

  if (userId == null || typeof accessToken !== 'string' || !accessToken.trim() || !job || typeof job !== 'object') {
    return res.status(400).json({
      error: 'Missing or invalid body: user_id, access_token, and job are required.',
    });
  }
  if (!Array.isArray(job.priorities)) {
    return res.status(400).json({
      error: 'Job must include a priorities array.',
    });
  }

  const opts = {};
  if (maxRounds != null && Number(maxRounds) > 0) opts.maxRounds = Number(maxRounds);
  if (negotiationTimeSeconds != null && Number(negotiationTimeSeconds) > 0) opts.negotiationTimeSeconds = Number(negotiationTimeSeconds);

  try {
    const result = await runNegotiationAndMatch(job, accessToken.trim(), opts);
    if (result?.deals?.length && userId != null && job?.id) {
      saveDealsForJob(Number(userId), String(job.id), result.deals).catch((e) => console.error('[job-match] save', e.message));
    }
    return res.json(result);
  } catch (err) {
    const message = err?.message ?? 'Negotiate and match failed';
    if (message.includes('401') || message.toLowerCase().includes('unauthorized')) {
      return res.status(401).json({ error: 'Unauthorized. Check your access token.' });
    }
    if (message.includes('status') && message.includes('0')) {
      return res.status(502).json({ error: 'Backend API error. Please try again.' });
    }
    return res.status(500).json({ error: message });
  }
});

/**
 * POST /agent/buyer/negotiate-and-match-stream
 * Same body as negotiate-and-match. Response: Server-Sent Events (text/event-stream).
 * Events: providers_fetched, provider_start, negotiation_step, provider_done, done.
 */
app.post('/agent/buyer/negotiate-and-match-stream', async (req, res) => {
  const { user_id: userId, access_token: accessToken, job, negotiation_max_rounds: maxRounds, negotiation_time_seconds: negotiationTimeSeconds } = req.body ?? {};

  if (userId == null || typeof accessToken !== 'string' || !accessToken.trim() || !job || typeof job !== 'object') {
    return res.status(400).json({
      error: 'Missing or invalid body: user_id, access_token, and job are required.',
    });
  }
  if (!Array.isArray(job.priorities)) {
    return res.status(400).json({
      error: 'Job must include a priorities array.',
    });
  }

  const opts = {};
  if (maxRounds != null && Number(maxRounds) > 0) opts.maxRounds = Number(maxRounds);
  if (negotiationTimeSeconds != null && Number(negotiationTimeSeconds) > 0) opts.negotiationTimeSeconds = Number(negotiationTimeSeconds);

  res.setHeader('Content-Type', 'text/event-stream');
  res.setHeader('Cache-Control', 'no-cache');
  res.setHeader('Connection', 'keep-alive');
  res.setHeader('X-Accel-Buffering', 'no');
  res.flushHeaders && res.flushHeaders();

  const send = (event) => {
    res.write(`data: ${JSON.stringify(event)}\n\n`);
    if (typeof res.flush === 'function') res.flush();
  };

  try {
    const result = await runNegotiationAndMatchStream(job, accessToken.trim(), opts, send);
    if (result?.deals?.length && userId != null && job?.id) {
      saveDealsForJob(Number(userId), String(job.id), result.deals).catch((e) => console.error('[job-match] save', e.message));
    }
  } catch (err) {
    const message = err?.message ?? 'Negotiate and match stream failed';
    send({ type: 'done', deals: [], error: message });
  } finally {
    res.end();
  }
});

/**
 * POST /agent/buyer/job-matches
 * Body: { user_id, access_token, job_id }
 * Returns: { deals: Array } — stored match quotes from DB (no agent call).
 */
app.post('/agent/buyer/job-matches', async (req, res) => {
  const { user_id: userId, access_token: accessToken, job_id: jobId } = req.body ?? {};

  if (userId == null || typeof accessToken !== 'string' || !accessToken.trim() || !jobId) {
    return res.status(400).json({
      error: 'Missing or invalid body: user_id, access_token, and job_id are required.',
    });
  }

  try {
    const deals = await getDealsForJob(Number(userId), String(jobId));
    return res.json({ deals });
  } catch (err) {
    const message = err?.message ?? 'Failed to load job matches';
    return res.status(500).json({ error: message });
  }
});

/**
 * POST /agent/buyer/chat
 * Body: { user_id: number, message: string, access_token: string, conversation_history?: Array<{role,content}>, job_id?: string, job_title?: string }
 * Returns: { reply: string }
 */
app.post('/agent/buyer/chat', async (req, res) => {
  const { user_id: userId, message, access_token: accessToken, conversation_history, job_id: jobId, job_title: jobTitle } = req.body ?? {};

  if (userId == null || typeof message !== 'string' || !message.trim() || typeof accessToken !== 'string' || !accessToken.trim()) {
    return res.status(400).json({
      error: 'Missing or invalid body: user_id, message, and access_token are required.',
    });
  }

  const opts = {};
  if (Array.isArray(conversation_history) && conversation_history.length > 0) {
    opts.conversationHistory = conversation_history.filter(
      (m) => m && (m.role === 'user' || m.role === 'assistant') && typeof m.content === 'string'
    );
  }
  if (jobId && typeof jobId === 'string' && jobId.trim()) opts.jobId = jobId.trim();
  if (jobTitle && typeof jobTitle === 'string' && jobTitle.trim()) opts.jobTitle = jobTitle.trim();

  try {
    const { reply } = await runBuyerAgent(userId, message.trim(), accessToken.trim(), opts);
    return res.json({ reply });
  } catch (err) {
    const message = err?.message ?? 'Agent request failed';
    if (message.includes('401') || message.toLowerCase().includes('unauthorized')) {
      return res.status(401).json({ error: 'Unauthorized. Check your access token.' });
    }
    if (message.includes('status') && message.includes('0')) {
      return res.status(502).json({ error: 'Backend API error. Please try again.' });
    }
    return res.status(500).json({ error: message });
  }
});

/**
 * POST /agent/seller/chat
 * Body: { provider_id: number, access_token: string, message: string, conversation_history?: Array<{role,content}>, order_id?: string, order_title?: string }
 * Returns: { reply: string }
 */
app.post('/agent/seller/chat', async (req, res) => {
  const { provider_id: providerId, access_token: accessToken, message, conversation_history, order_id: orderId, order_title: orderTitle } = req.body ?? {};

  if (providerId == null || typeof message !== 'string' || !message.trim() || typeof accessToken !== 'string' || !accessToken.trim()) {
    return res.status(400).json({
      error: 'Missing or invalid body: provider_id, message, and access_token are required.',
    });
  }

  const opts = {};
  if (Array.isArray(conversation_history) && conversation_history.length > 0) {
    opts.conversationHistory = conversation_history.filter(
      (m) => m && (m.role === 'user' || m.role === 'assistant') && typeof m.content === 'string'
    );
  }
  if (orderId && typeof orderId === 'string' && orderId.trim()) opts.orderId = orderId.trim();
  if (orderTitle && typeof orderTitle === 'string' && orderTitle.trim()) opts.orderTitle = orderTitle.trim();

  try {
    const { reply } = await runSellerAgent(providerId, message.trim(), accessToken.trim(), opts);
    return res.json({ reply });
  } catch (err) {
    const message = err?.message ?? 'Agent request failed';
    if (message.includes('401') || message.toLowerCase().includes('unauthorized')) {
      return res.status(401).json({ error: 'Unauthorized. Check your access token.' });
    }
    if (message.includes('status') && message.includes('0')) {
      return res.status(502).json({ error: 'Backend API error. Please try again.' });
    }
    return res.status(500).json({ error: message });
  }
});

/**
 * POST /agent/seller/match
 * Body: { provider_id: number, access_token: string, service_category_id?, sub_category_id?, agent_config? }
 * Returns: { deals: Array }
 */
app.post('/agent/seller/match', async (req, res) => {
  const { provider_id: providerId, access_token: accessToken, service_category_id, sub_category_id, agent_config, message } = req.body ?? {};

  if (providerId == null || typeof accessToken !== 'string' || !accessToken.trim()) {
    return res.status(400).json({
      error: 'Missing or invalid body: provider_id and access_token are required.',
    });
  }

  const options = {};
  if (service_category_id != null) options.service_category_id = Number(service_category_id);
  if (sub_category_id != null) options.sub_category_id = Number(sub_category_id);
  if (agent_config != null && typeof agent_config === 'object') {
    options.agentConfig = agent_config;
  }
  if (typeof message === 'string' && message.trim()) options.userMessage = message.trim();

  try {
    const result = await runSellerMatchAgent(providerId, accessToken.trim(), options);
    return res.json({ deals: result.matches, ...(result.reply && { reply: result.reply }) });
  } catch (err) {
    const message = err?.message ?? 'Match request failed';
    if (message.includes('401') || message.toLowerCase().includes('unauthorized')) {
      return res.status(401).json({ error: 'Unauthorized. Check your access token.' });
    }
    if (message.includes('status') && message.includes('0')) {
      return res.status(502).json({ error: 'Backend API error. Please try again.' });
    }
    return res.status(500).json({ error: message });
  }
});

/**
 * POST /agent/seller/profile
 * Returns the provider service data the agent uses (from Laravel provider-service-data).
 * Use this on the dashboard so total_completed_order and average_rating match what the agent sees.
 * Body: { provider_id: number, access_token: string, service_category_id }
 * Returns: { profile: { provider_id, service_cat_id, average_rating, total_completed_order, ... } }
 */
app.post('/agent/seller/profile', async (req, res) => {
  const { provider_id: providerId, access_token: accessToken, service_category_id } = req.body ?? {};

  if (providerId == null || typeof accessToken !== 'string' || !accessToken.trim()) {
    return res.status(400).json({
      error: 'Missing or invalid body: provider_id and access_token are required.',
    });
  }

  const serviceCategoryId = service_category_id ?? 1;

  try {
    const profileData = await getProviderServiceData(
      providerId,
      accessToken.trim(),
      serviceCategoryId
    );
    
    if (!profileData) {
      return res.status(404).json({
        error: 'Provider service data not found for the specified service category.',
      });
    }

    // Transform to match expected profile format
    const profile = {
      provider_id: providerId,
      provider_name: 'Provider', // provider-service-data doesn't include provider_name
      average_rating: parseFloat(profileData.average_rating) || 0,
      total_completed_order: parseInt(profileData.total_completed_order, 10) || 0,
      num_of_rating: parseInt(profileData.num_of_rating, 10) || 0,
      licensed: profileData.licensed !== false,
      service_category_id: profileData.service_cat_id,
      min_price: profileData.min_price,
      max_price: profileData.max_price,
      deadline_in_days: profileData.deadline_in_days,
      package_list: profileData.package_list || [],
    };

    return res.json({ profile });
  } catch (err) {
    const message = err?.message ?? 'Profile request failed';
    if (message.includes('401') || message.toLowerCase().includes('unauthorized')) {
      return res.status(401).json({ error: 'Unauthorized. Check your access token.' });
    }
    return res.status(500).json({ error: message });
  }
});

/**
 * POST /agent/seller/scan
 * Alias for /agent/seller/match - scans new jobs for seller
 * Body: { provider_id: number, access_token: string, service_category_id?, sub_category_id?, agent_config? }
 * Returns: { deals: Array }
 */
app.post('/agent/seller/scan', async (req, res) => {
  // Reuse the match endpoint logic
  const { provider_id: providerId, access_token: accessToken, service_category_id, sub_category_id, agent_config, message } = req.body ?? {};

  if (providerId == null || typeof accessToken !== 'string' || !accessToken.trim()) {
    return res.status(400).json({
      error: 'Missing or invalid body: provider_id and access_token are required.',
    });
  }

  const options = {};
  if (service_category_id != null) options.service_category_id = Number(service_category_id);
  if (sub_category_id != null) options.sub_category_id = Number(sub_category_id);
  if (agent_config != null && typeof agent_config === 'object') {
    options.agentConfig = agent_config;
  }
  if (typeof message === 'string' && message.trim()) options.userMessage = message.trim();

  try {
    const result = await runSellerMatchAgent(providerId, accessToken.trim(), options);
    return res.json({ deals: result.matches, ...(result.reply && { reply: result.reply }) });
  } catch (err) {
    const message = err?.message ?? 'Scan request failed';
    if (message.includes('401') || message.toLowerCase().includes('unauthorized')) {
      return res.status(401).json({ error: 'Unauthorized. Check your access token.' });
    }
    if (message.includes('status') && message.includes('0')) {
      return res.status(502).json({ error: 'Backend API error. Please try again.' });
    }
    return res.status(500).json({ error: message });
  }
});

/**
 * POST /webhook/provider-registered
 * Called when a new provider is added/registered (e.g. by Laravel).
 * Body: { provider_id: number, service_category_id?, sub_category_id?, lat?, long?, event? }
 * Returns: { received: true, event: "provider_registered" }
 * Does not invoke any agent; acknowledge only.
 */
app.post('/webhook/provider-registered', (req, res) => {
  const webhookSecret = process.env.WEBHOOK_SECRET;
  if (webhookSecret) {
    const headerSecret = req.get('X-Webhook-Secret');
    if (headerSecret !== webhookSecret) {
      return res.status(401).json({ error: 'Unauthorized. Invalid or missing X-Webhook-Secret.' });
    }
  }

  const body = req.body ?? {};
  const providerId = body.provider_id;
  const numId = Number(providerId);
  if (providerId == null || !Number.isFinite(numId) || numId < 1) {
    return res.status(400).json({
      error: 'Missing or invalid body: provider_id (positive number) is required.',
    });
  }
  console.log('[webhook] provider-registered triggered', {
    provider_id: numId,
    service_category_id: body.service_category_id,
    sub_category_id: body.sub_category_id,
    at: new Date().toISOString(),
  });

  return res.status(200).json({
    received: true,
    event: body.event ?? 'provider_registered',
  });
});

async function startServer() {
  try {
    await sequelize.authenticate();
    console.log('Database connection established.');
    await JobMatchQuote.sync();
    console.log('Table job_match_quotes ready (created if not exist).');
  } catch (err) {
    console.error('Database error:', err.message);
    process.exitCode = 1;
  }
  app.listen(PORT, () => {
    console.log(`Server is running on port ${PORT}`);
  });
}

startServer();
