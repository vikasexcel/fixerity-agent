import express from 'express';
import dotenv from 'dotenv';
import { runBuyerAgent } from './agents/buyerAgent.js';
import { runBuyerMatchAgent } from './agents/buyerMatchAgent.js';
import { PORT } from './config/index.js';
import cors from 'cors';

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
 * Body: { user_id: number, access_token: string, job: { id, title, budget, priorities, service_category_id?, sub_category_id?, lat?, long? } }
 * Returns: { deals: Array }
 */
app.post('/agent/buyer/match', async (req, res) => {
  const { user_id: userId, access_token: accessToken, job } = req.body ?? {};

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

  try {
    const { deals } = await runBuyerMatchAgent(userId, accessToken.trim(), job);
    return res.json({ deals });
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

app.listen(PORT, () => {
  console.log(`Server is running on port ${PORT}`);
});
