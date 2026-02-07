// routes/agentRoutes.js (or wherever your routes are)
import express from 'express';
import { ChatOpenAI } from '@langchain/openai';
import { HumanMessage, SystemMessage, AIMessage } from '@langchain/core/messages';
import { OPENAI_API_KEY } from '../config/index.js';
import { runNegotiationAndMatchStream, cleanupJobNegotiations } from '../agents/negotiationOrchestrator.js';
import { sessionStore, semanticMemory } from '../agents/negotiationGraph.js';
import { fetchProviderBasicDetails } from '../agents/buyerMatchAgent.js';
import { redisClient } from '../config/redis.js';
import memoryClient from '../memory/mem0.js';

const router = express.Router();


router.post('/buyer/negotiate-and-match-stream', async (req, res) => {
  const { 
    user_id: userId, 
    access_token: accessToken, 
    job, 
    negotiation_max_rounds: maxRounds, 
    negotiation_time_seconds: negotiationTimeSeconds 
  } = req.body ?? {};

  console.log('────────── Negotiate & Match Stream Request ──────────');
  console.log(`userId                    : ${userId}`);
  console.log(`accessToken               : ${accessToken?.substring(0, 20)}...`);
  console.log(`job                       : ${JSON.stringify(job)}`);
  console.log(`maxRounds                 : ${maxRounds}`);
  console.log(`negotiationTimeSeconds    : ${negotiationTimeSeconds}`);
  console.log('──────────────────────────────────────────────────────');

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
    
    // Save deals for job (you should have this function)
    if (result?.deals?.length && userId != null && job?.id) {
      saveDealsForJob(Number(userId), String(job.id), result.deals).catch((e) => 
        console.error('[job-match] save error:', e.message)
      );
    }
  } catch (err) {
    const message = err?.message ?? 'Negotiate and match stream failed';
    send({ type: 'done', deals: [], error: message });
  } finally {
    res.end();
  }
});

router.post('/buyer/chat', async (req, res) => {
  const body = req.body ?? {};
  const {
    user_id: userId,
    access_token: accessToken,
    message,
    context: bodyContext,
  } = body;

  // Accept either context object or flat job_id / job_title / conversation_history
  const context = bodyContext ?? {
    jobId: body.job_id ?? body.jobId,
    jobTitle: body.job_title ?? body.jobTitle,
    conversationHistory: body.conversation_history ?? body.conversationHistory ?? [],
  };

  console.log('────────── Buyer Chat Request ──────────');
  console.log(`userId        : ${userId}`);
  console.log(`message       : ${message}`);
  console.log(`jobId         : ${context?.jobId}`);
  console.log(`jobTitle      : ${context?.jobTitle}`);
  console.log(`history items : ${context?.conversationHistory?.length || 0}`);
  console.log('────────────────────────────────────────');

  // Validation
  if (userId == null || typeof accessToken !== 'string' || !accessToken.trim()) {
    return res.status(400).json({
      error: 'Missing or invalid user_id or access_token.',
    });
  }

  if (!message || typeof message !== 'string' || !message.trim()) {
    return res.status(400).json({
      error: 'Missing or invalid message.',
    });
  }

  if (!context || !context.jobId) {
    return res.status(400).json({
      error: 'Missing context.jobId.',
    });
  }

  try {
    const reply = await handleBuyerChat(
      Number(userId),
      accessToken.trim(),
      message.trim(),
      context
    );

    res.json({ reply });
  } catch (err) {
    console.error('[buyer-chat] Error:', err.message);
    res.status(500).json({
      error: err?.message || 'Failed to process chat message.',
    });
  }
});

/**
 * POST /agent/buyer/job-cleanup
 * Clears Redis (negotiation + deals cache) and Mem0 memories for the given job.
 * Body: { user_id, access_token, job_id }
 */
router.post('/buyer/job-cleanup', async (req, res) => {
  const { user_id: userId, access_token: accessToken, job_id: jobId } = req.body ?? {};

  if (userId == null || typeof accessToken !== 'string' || !accessToken.trim()) {
    return res.status(400).json({ error: 'Missing or invalid user_id or access_token.' });
  }
  if (!jobId || typeof jobId !== 'string' || !String(jobId).trim()) {
    return res.status(400).json({ error: 'Missing or invalid job_id.' });
  }

  const jobIdStr = String(jobId).trim();
  const userIdNum = Number(userId);
  const result = { redis: { negotiationKeys: 0, dealsKey: false }, mem0: { deleted: 0 }, error: null };

  try {
    // 1. Redis: cleanup negotiation keys for this job
    const redisNegotiation = await cleanupJobNegotiations(jobIdStr);
    result.redis.negotiationKeys = redisNegotiation.cleaned ?? 0;

    // 2. Redis: remove cached deals for this user+job
    const dealsKey = `deals:${userIdNum}:${jobIdStr}`;
    try {
      const removed = await redisClient.del(dealsKey);
      result.redis.dealsKey = removed > 0;
    } catch (e) {
      console.warn('[job-cleanup] Redis deals key error:', e.message);
    }

    // 3. Mem0: delete memories that have metadata.job_id === jobId
    try {
      const list = await memoryClient.getAll({ filters: { metadata: { job_id: jobIdStr } } });
      const memories = Array.isArray(list) ? list : (list?.results ?? list?.data ?? []);
      const ids = memories.filter((m) => m?.id).map((m) => m.id);
      if (ids.length > 0) {
        await memoryClient.batchDelete(ids);
        result.mem0.deleted = ids.length;
        console.log(`[job-cleanup] Mem0 deleted ${ids.length} memories for job ${jobIdStr}`);
      }
    } catch (e) {
      console.warn('[job-cleanup] Mem0 cleanup error (may not support filter):', e.message);
      result.error = result.error ? `${result.error}; Mem0: ${e.message}` : `Mem0: ${e.message}`;
    }

    console.log('[job-cleanup] Done for job', jobIdStr, result);
    res.json({ ok: true, ...result });
  } catch (err) {
    console.error('[job-cleanup] Error:', err.message);
    res.status(500).json({
      error: err?.message || 'Cleanup failed.',
      ...result,
    });
  }
});

/* ==================== CHAT HANDLER (Core Logic) ==================== */

async function handleBuyerChat(userId, accessToken, message, context) {
  const { jobId, jobTitle, conversationHistory = [] } = context;

  console.log(`[Chat] Processing message for user ${userId}, job ${jobId}`);

  // 1. Retrieve deals from Redis cache (fast) or database (fallback)
  const deals = await getDealsForJob(userId, jobId);
  console.log(`[Chat] Found ${deals?.length || 0} deals for job ${jobId}`);

  // 1b. Enrich deals with provider basic details (name, email, contact) from provider-basic-details API for chat answers
  const dealsWithDetails = await enrichDealsWithProviderBasicDetails(deals || []);

  // 2. Retrieve negotiation context from Redis (conversation transcripts)
  const negotiationContext = await getNegotiationContext(jobId);
  console.log(`[Chat] Found ${negotiationContext.length} negotiation transcripts`);

  // 3. (Optional) Get buyer preferences from Mem0
  let buyerPreferences = null;
  try {
    buyerPreferences = await semanticMemory.getBuyerPreferences(String(userId));
    console.log(`[Chat] Retrieved buyer preferences from Mem0`);
  } catch (err) {
    console.log(`[Chat] No Mem0 preferences found:`, err.message);
  }

  // 4. Build comprehensive context for LLM (include provider contact details so user can ask "how do I contact X?")
  const systemPrompt = buildSystemPrompt({
    jobId,
    jobTitle,
    deals: dealsWithDetails,
    negotiationContext,
    buyerPreferences,
  });

  // 5. Build conversation messages for LLM
  const llmMessages = buildLLMMessages(systemPrompt, conversationHistory, message);

  // 6. Call LLM to generate response
  const llm = new ChatOpenAI({
    model: 'gpt-4o-mini',
    temperature: 0.7,
    openAIApiKey: OPENAI_API_KEY,
  });

  const response = await llm.invoke(llmMessages);
  const reply = response.content.trim();

  console.log(`[Chat] Generated reply (${reply.length} chars)`);

  // 7. (Optional) Store conversation in Mem0 for learning
  try {
    await semanticMemory.memory.add({
      messages: [
        { role: 'user', content: message },
        { role: 'assistant', content: reply },
      ],
      user_id: `buyer_${userId}`,
      metadata: {
        type: 'followup_chat',
        job_id: jobId,
        timestamp: Date.now(),
      },
    });
    console.log(`[Chat] Stored conversation in Mem0`);
  } catch (err) {
    console.log(`[Chat] Failed to store in Mem0:`, err.message);
  }

  return reply;
}

/* ==================== HELPER: Enrich Deals With Provider Basic Details ==================== */

async function enrichDealsWithProviderBasicDetails(deals) {
  if (!deals || deals.length === 0) return deals;
  const enriched = await Promise.all(
    deals.map(async (deal) => {
      const providerId = deal.sellerId ?? deal.provider_id;
      if (!providerId) return deal;
      try {
        const basic = await fetchProviderBasicDetails(Number(providerId));
        if (basic) {
          return { ...deal, sellerBasicDetails: basic };
        }
      } catch (err) {
        console.warn(`[Chat] Could not fetch basic details for provider ${providerId}:`, err.message);
      }
      return deal;
    })
  );
  return enriched;
}

/* ==================== HELPER: Get Deals ==================== */

async function getDealsForJob(userId, jobId) {
  // Try Redis first (fast, recent)
  const cacheKey = `deals:${userId}:${jobId}`;
  
  try {
    const cached = await redisClient.get(cacheKey);
    if (cached) {
      console.log(`[Chat] Deals cache HIT for ${cacheKey}`);
      return JSON.parse(cached);
    }
  } catch (err) {
    console.log(`[Chat] Redis cache error:`, err.message);
  }

  // Fallback to database
  try {
    // TODO: Replace with your actual database query
    // const deals = await db.deals.findMany({ where: { userId, jobId } });
    
    // For now, return empty array if not in cache
    console.log(`[Chat] Deals cache MISS for ${cacheKey}, returning empty`);
    return [];
  } catch (err) {
    console.error(`[Chat] Database error:`, err.message);
    return [];
  }
}

/* ==================== HELPER: Get Negotiation Context ==================== */

async function getNegotiationContext(jobId) {
  const context = [];

  try {
    // Get all negotiation keys for this job
    const pattern = `negotiation:${jobId}:*:messages`;
    const keys = await redisClient.keys(pattern);

    console.log(`[Chat] Found ${keys.length} negotiation message keys`);

    for (const key of keys) {
      // Extract provider ID from key: negotiation:job_10:provider_123:messages
      const parts = key.split(':');
      const providerId = parts[2];

      // Get messages for this provider
      const messages = await sessionStore.getAllMessages(jobId, providerId);
      
      if (messages && messages.length > 0) {
        context.push({
          providerId,
          messages,
        });
      }
    }

    console.log(`[Chat] Retrieved ${context.length} provider negotiations`);
  } catch (err) {
    console.error(`[Chat] Error getting negotiation context:`, err.message);
  }

  return context;
}

/* ==================== HELPER: Build System Prompt ==================== */

function buildSystemPrompt({ jobId, jobTitle, deals, negotiationContext, buyerPreferences }) {
  let prompt = `You are a helpful assistant for a buyer who is looking for service providers.

Current Job:
- Job ID: ${jobId}
- Job Title: ${jobTitle}
`;

  // Add deals information (include provider basic details when available so user can ask for contact info)
  if (deals && deals.length > 0) {
    prompt += `\nMatched Providers (${deals.length} total):\n`;
    deals.forEach((deal, index) => {
      const displayName = deal.sellerName || `Provider ${deal.sellerId}`;
      prompt += `\n${index + 1}. ${displayName}
   - Match Score: ${deal.matchScore}%
   - Quote: $${deal.quote?.price || 'N/A'}
   - Completion Days: ${deal.quote?.completionDays || deal.quote?.days || 'N/A'}
   - Payment Schedule: ${deal.quote?.paymentSchedule || 'Not specified'}
   - Licensed: ${deal.quote?.licensed ? 'Yes' : 'No'}
   - References Available: ${deal.quote?.referencesAvailable ? 'Yes' : 'No'}
   - Can Meet Dates: ${deal.quote?.can_meet_dates !== false ? 'Yes' : 'No'}`;
      if (deal.sellerBasicDetails) {
        const b = deal.sellerBasicDetails;
        const fullName = [b.first_name, b.last_name].filter(Boolean).join(' ').trim() || displayName;
        prompt += `
   - Provider contact details: Name: ${fullName}${b.email ? `, Email: ${b.email}` : ''}${b.contact_number ? `, Contact number: ${b.contact_number}` : ''}`;
      }
      if (deal.sellerAgent) {
        prompt += `
   - Rating: ${deal.sellerAgent.rating}/5
   - Jobs Completed: ${deal.sellerAgent.jobsCompleted}`;
      }
      if (deal.negotiationMessage) {
        prompt += `
   - Message: "${deal.negotiationMessage}"`;
      }
    });
  } else {
    prompt += `\nNo providers matched for this job yet.`;
  }

  // Add negotiation transcripts
  if (negotiationContext && negotiationContext.length > 0) {
    prompt += `\n\nNegotiation Transcripts:\n`;
    negotiationContext.forEach((ctx) => {
      const dealInfo = deals?.find(d => d.sellerId === ctx.providerId);
      const providerName = dealInfo?.sellerName || `Provider ${ctx.providerId}`;
      
      prompt += `\n--- ${providerName} ---\n`;
      ctx.messages.forEach((msg) => {
        prompt += `${msg.role === 'buyer' ? 'Buyer' : 'Provider'}: ${msg.message}\n`;
      });
    });
  }

  // Add buyer preferences (if available from Mem0)
  if (buyerPreferences?.summary?.top_insights) {
    prompt += `\n\nBuyer's Past Preferences (learned):\n`;
    buyerPreferences.summary.top_insights.forEach((insight, i) => {
      prompt += `${i + 1}. ${insight.text}\n`;
    });
  }

  prompt += `\n\nInstructions:
- Answer the buyer's questions based on the information above
- Be helpful, friendly, and professional
- If asked about specific providers, reference their details accurately
- If asked to compare providers, explain differences clearly
- If information is not available, say so honestly
- Keep responses concise but informative
- If buyer asks about negotiating further, explain that they can contact providers directly`;

  return prompt;
}

/* ==================== HELPER: Build LLM Messages ==================== */

function buildLLMMessages(systemPrompt, conversationHistory, currentMessage) {
  const messages = [new SystemMessage(systemPrompt)];

  // Add conversation history (chronological order; assistant as AIMessage for correct roles)
  conversationHistory.forEach((msg) => {
    if (msg.role === 'user') {
      messages.push(new HumanMessage(msg.content));
    } else if (msg.role === 'assistant') {
      messages.push(new AIMessage(msg.content));
    }
  });

  // Add current message
  messages.push(new HumanMessage(currentMessage));

  return messages;
}

/* ==================== HELPER: Save Deals (You should already have this) ==================== */

async function saveDealsForJob(userId, jobId, deals) {
  // Cache in Redis for 1 hour
  const cacheKey = `deals:${userId}:${jobId}`;
  
  try {
    await redisClient.setex(cacheKey, 3600, JSON.stringify(deals));
    console.log(`[Chat] Cached ${deals.length} deals for ${cacheKey}`);
  } catch (err) {
    console.error(`[Chat] Failed to cache deals:`, err.message);
  }

  // TODO: Save to your database
  // Example:
  // await db.deals.createMany({
  //   data: deals.map(deal => ({
  //     userId,
  //     jobId,
  //     sellerId: deal.sellerId,
  //     matchScore: deal.matchScore,
  //     quote: JSON.stringify(deal.quote),
  //     // ... other fields
  //   }))
  // });

  console.log(`[Chat] Saved ${deals.length} deals to database for job ${jobId}`);
}

export default router;