import express from 'express';
import dotenv from 'dotenv';
import { runBuyerAgent } from './agents/buyerAgent.js';
import { PORT } from './config/index.js';

dotenv.config();

const app = express();
app.use(express.json());

app.get('/', (req, res) => {
  res.send('Hello World');
});

/**
 * POST /agent/buyer/chat
 * Body: { user_id: number, message: string, access_token: string }
 * Returns: { reply: string }
 */
app.post('/agent/buyer/chat', async (req, res) => {
  const { user_id: userId, message, access_token: accessToken } = req.body ?? {};

  if (userId == null || typeof message !== 'string' || !message.trim() || typeof accessToken !== 'string' || !accessToken.trim()) {
    return res.status(400).json({
      error: 'Missing or invalid body: user_id, message, and access_token are required.',
    });
  }

  try {
    const { reply } = await runBuyerAgent(userId, message.trim(), accessToken.trim());
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
