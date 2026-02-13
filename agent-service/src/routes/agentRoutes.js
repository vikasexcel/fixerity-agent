
import express from 'express';
import { redisClient } from '../config/redis.js';
import memoryClient from '../memory/mem0.js';
import { sessionManager, sellerSessionManager, handleAgentChat } from '../graphs/UnifiedAgent.js';
import { sessionRepository } from '../../prisma/repositories/sessionRepository.js';
import { messageService } from '../services/index.js';

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

/* ================================================================================
   UNIFIED AGENT API ROUTES
   ================================================================================
   
   POST /api/agent/chat - Main streaming endpoint for all interactions
   GET  /api/agent/session/:sessionId - Get session state
   DELETE /api/agent/session/:sessionId - Clear session
   
   ================================================================================ */

/* -------------------- SSE HELPER -------------------- */

function setupSSE(res) {
  res.setHeader('Content-Type', 'text/event-stream');
  res.setHeader('Cache-Control', 'no-cache');
  res.setHeader('Connection', 'keep-alive');
  res.setHeader('X-Accel-Buffering', 'no'); // For nginx
  res.flushHeaders();

  // Send function that formats SSE properly
  const send = (data) => {
    try {
      res.write(`data: ${JSON.stringify(data)}\n\n`);
      // Force flush if available
      if (res.flush) res.flush();
    } catch (error) {
      console.error('[SSE] Error sending:', error.message);
    }
  };

  // Handle client disconnect
  const cleanup = () => {
    console.log('[SSE] Client disconnected');
  };
  
  res.on('close', cleanup);
  res.on('error', cleanup);

  return send;
}





/* -------------------- UNIFIED CHAT ENDPOINT -------------------- */

/**
 * POST /api/agent/chat
 * 
 * Unified chat endpoint for both buyers and sellers
 * 
 * Body:
 * - userType: 'buyer' | 'seller' (required)
 * - sessionId: string (optional - will create if not provided)
 * - buyerId: number (required if userType='buyer')
 * - sellerId: number (required if userType='seller')
 * - accessToken: string (required)
 * - message: string (required)
 */
router.post('/chat', async (req, res) => {
  const send = setupSSE(res);

  try {
    const { userType, sessionId, buyerId, sellerId, accessToken, message, resume, forceNewSession } = req.body;

    // Validate userType
    if (!userType || !['buyer', 'seller'].includes(userType)) {
      send({ type: 'error', error: 'userType must be "buyer" or "seller"' });
      res.end();
      return;
    }

    // Validate user ID based on type
    const userId = userType === 'buyer' ? buyerId : sellerId;
    if (!userId) {
      send({ type: 'error', error: `${userType}Id is required` });
      res.end();
      return;
    }

    if (!accessToken) {
      send({ type: 'error', error: 'accessToken is required' });
      res.end();
      return;
    }

    const isResume = userType === 'seller' && resume !== undefined && resume !== null;
    if (!isResume && !message) {
      send({ type: 'error', error: 'message is required (or send resume for seller after an interrupt)' });
      res.end();
      return;
    }

    if (isResume && !sessionId) {
      send({ type: 'error', error: 'sessionId is required when sending resume' });
      res.end();
      return;
    }

    // Build input object based on userType
    const input = {
      userType,
      sessionId,
      forceNewSession: forceNewSession === true || forceNewSession === 'true',
      accessToken: String(accessToken),
      message: message != null ? String(message) : undefined,
    };

    if (userType === 'buyer') {
      input.buyerId = String(buyerId);
    } else {
      input.sellerId = String(sellerId);
      if (isResume) input.resume = resume;
    }

    // Handle the chat
    await handleAgentChat(input, send);

    // End the stream
    send({ type: 'stream_end' });
    res.end();

  } catch (error) {
    console.error('[Agent Route] Error:', error);
    send({ type: 'error', error: error.message || 'Internal server error' });
    res.end();
  }
});

/* -------------------- GET SESSION STATE -------------------- */

/**
 * GET /api/agent/session/:sessionId
 * 
 * Retrieve the current state of a session (buyer or seller)
 */
router.get('/session/:sessionId', async (req, res) => {
  try {
    const { sessionId } = req.params;

    const dbSession = await sessionRepository.findById(sessionId);
    if (!dbSession || !dbSession.isActive) {
      return res.status(404).json({
        status: 0,
        message: 'Session not found',
        sessionId,
      });
    }

    const state = dbSession.state || {};
    const messages = await messageService.getConversationHistory(sessionId, { limit: 50, includeSystem: true });

    res.json({
      status: 1,
      message: 'Success',
      userType: dbSession.userType,
      session: {
        sessionId: dbSession.id,
        phase: dbSession.phase,
        userId: dbSession.userId,
        created_at: dbSession.createdAt,
        updated_at: dbSession.updatedAt,
        job: state.job || null,
        deals: state.deals || [],
        profile: state.profile || null,
        matchedJobs: state.matchedJobs || [],
      },
      messages: messages || [],
    });

  } catch (error) {
    console.error('[Agent Route] Get session error:', error);
    res.status(500).json({
      status: 0,
      message: error.message || 'Internal server error',
    });
  }
});

/* -------------------- DELETE SESSION -------------------- */

/**
 * DELETE /api/agent/session/:sessionId
 * 
 * Clear a session and all associated data (buyer or seller)
 */
router.delete('/session/:sessionId', async (req, res) => {
  try {
    const { sessionId } = req.params;

    // Try both managers
    await sessionManager.cleanup(sessionId).catch(() => {});
    await sellerSessionManager.cleanup(sessionId).catch(() => {});

    res.json({
      status: 1,
      message: 'Session cleared successfully',
      sessionId,
    });

  } catch (error) {
    console.error('[Agent Route] Delete session error:', error);
    res.status(500).json({
      status: 0,
      message: error.message || 'Internal server error',
    });
  }
});

/* -------------------- HEALTH CHECK -------------------- */

/**
 * GET /api/agent/health
 * 
 * Health check endpoint
 */
router.get('/health', (req, res) => {
  res.json({
    status: 1,
    message: 'Agent service is healthy',
    timestamp: Date.now(),
    supportedUserTypes: ['buyer', 'seller'],
  });
});

export default router;