import express from 'express';
import dotenv from 'dotenv';
import { PORT } from './config/index.js';
import cors from 'cors';
import { sequelize } from './db.js';
import JobMatchQuote from './models/JobMatchQuote.js';
import SellerProfile from './models/SellerProfile.js';
import { JobListing } from './models/JobListing.js';
import { SellerBid } from './models/SellerBid.js';
import memoryClient from './memory/mem0.js';
import agentRoutes from './routes/agentRoutes.js';
import { connectDB } from './primsadb.js';
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

app.use('/agent', agentRoutes);


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
    await connectDB();
    console.log('Prisma connection established.');
  } catch (err) {
    console.error('Database error:', err.message);
    process.exitCode = 1;
  }
  app.listen(PORT, () => {
    console.log(`Server is running on port ${PORT}`);
  });
}

startServer();
