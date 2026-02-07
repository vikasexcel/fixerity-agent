/**
 * Agent service API client for buyer and seller agents.
 */

import type { Job, Deal } from './dummy-data';

const getAgentServiceUrl = (): string => {
  const url = process.env.NEXT_PUBLIC_AGENT_SERVICE_URL;
  if (!url) {
    throw new Error(
      'NEXT_PUBLIC_AGENT_SERVICE_URL is not set. Add it to .env.local (see .env.example).'
    );
  }
  return url.replace(/\/$/, '');
};

export async function matchJobToProviders(
  job: Job,
  userId: number,
  accessToken: string
): Promise<Deal[]> {
  const base = getAgentServiceUrl();
  const url = `${base}/agent/buyer/match`;
  const res = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      user_id: userId,
      access_token: accessToken,
      job: {
        id: job.id,
        title: job.title,
        description: job.description,
        budget: job.budget,
        startDate: job.startDate,
        endDate: job.endDate,
        priorities: job.priorities,
        service_category_id: job.service_category_id,
        sub_category_id: job.sub_category_id,
        lat: job.lat,
        long: job.long,
      },
    }),
  });
  const data = (await res.json().catch(() => ({}))) as { deals?: Deal[]; error?: string };
  if (!res.ok) {
    throw new Error(data?.error ?? res.statusText ?? 'Match request failed');
  }
  return data.deals ?? [];
}

/**
 * Get stored match quotes for a job from DB (no agent call). Use this to show recommended providers
 * with saved negotiation response (message, price, days, paymentSchedule, licensed, references).
 */
export async function getJobMatchResults(
  jobId: string,
  userId: number,
  accessToken: string
): Promise<Deal[]> {
  const base = getAgentServiceUrl();
  const res = await fetch(`${base}/agent/buyer/job-matches`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      user_id: userId,
      access_token: accessToken,
      job_id: jobId,
    }),
  });
  const data = (await res.json().catch(() => ({}))) as { deals?: Deal[]; error?: string };
  if (!res.ok) {
    throw new Error(data?.error ?? res.statusText ?? 'Failed to load job matches');
  }
  return data.deals ?? [];
}

/**
 * Match job to providers with buyer-seller negotiation (price and completion time).
 * Calls POST /agent/buyer/negotiate-and-match. Returns deals with negotiatedPrice and negotiatedCompletionDays.
 */
export async function matchJobToProvidersWithNegotiation(
  job: Job,
  userId: number,
  accessToken: string,
  options?: { negotiationMaxRounds?: number; negotiationTimeSeconds?: number }
): Promise<Deal[]> {
  const base = getAgentServiceUrl();
  const url = `${base}/agent/buyer/negotiate-and-match`;
  const body: {
    user_id: number;
    access_token: string;
    job: Record<string, unknown>;
    negotiation_max_rounds?: number;
    negotiation_time_seconds?: number;
  } = {
    user_id: userId,
    access_token: accessToken,
    job: {
      id: job.id,
      title: job.title,
      description: job.description,
      budget: job.budget,
      startDate: job.startDate,
      endDate: job.endDate,
      priorities: job.priorities,
      service_category_id: job.service_category_id,
      sub_category_id: job.sub_category_id,
      lat: job.lat,
      long: job.long,
    },
  };
  if (options?.negotiationMaxRounds != null) body.negotiation_max_rounds = options.negotiationMaxRounds;
  if (options?.negotiationTimeSeconds != null) body.negotiation_time_seconds = options.negotiationTimeSeconds;

  const res = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body),
  });
  const data = (await res.json().catch(() => ({}))) as { deals?: Deal[]; error?: string };
  if (!res.ok) {
    throw new Error(data?.error ?? res.statusText ?? 'Negotiate and match failed');
  }
  return data.deals ?? [];
}

/** One step in a negotiation (one offer from buyer or seller). */
export interface NegotiationStep {
  role: 'buyer' | 'seller';
  round: number;
  action: 'counter' | 'accept';
  /** Dialogue message from buyer or seller (may be present when offer is null). */
  message?: string;
  price?: number;
  completionDays?: number;
  /** Payment terms (e.g. "50% upfront, 50% upon completion"). */
  paymentSchedule?: string;
  licensed?: boolean;
  referencesAvailable?: boolean;
}

/** Event from negotiate-and-match-stream SSE. */
export type NegotiationStreamEvent =
  | { type: 'providers_fetched'; count: number }
  | { type: 'provider_start'; providerId: string; providerName: string }
  | { type: 'negotiation_step'; providerId: string; providerName: string; step: NegotiationStep }
  | { type: 'provider_done'; providerId: string; providerName: string; outcome: { status: string; negotiatedPrice: number; negotiatedCompletionDays: number } }
  | { type: 'done'; deals?: Deal[]; error?: string };

/**
 * Match job to providers with negotiation, streaming each step via onEvent (for live UI).
 * Uses POST /agent/buyer/negotiate-and-match-stream (SSE).
 */
export async function matchJobToProvidersWithNegotiationStream(
  job: Job,
  userId: number,
  accessToken: string,
  callbacks: {
    onEvent: (event: NegotiationStreamEvent) => void;
    signal?: AbortSignal;
  },
  options?: { negotiationMaxRounds?: number; negotiationTimeSeconds?: number }
): Promise<Deal[]> {
  const base = getAgentServiceUrl();
  const url = `${base}/agent/buyer/negotiate-and-match-stream`;
  const body: Record<string, unknown> = {
    user_id: userId,
    access_token: accessToken,
    job: {
      id: job.id,
      title: job.title,
      description: job.description,
      budget: job.budget,
      startDate: job.startDate,
      endDate: job.endDate,
      priorities: job.priorities,
      service_category_id: job.service_category_id,
      sub_category_id: job.sub_category_id,
      lat: job.lat,
      long: job.long,
    },
  };
  if (options?.negotiationMaxRounds != null) body.negotiation_max_rounds = options.negotiationMaxRounds;
  if (options?.negotiationTimeSeconds != null) body.negotiation_time_seconds = options.negotiationTimeSeconds;

  const res = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body),
    signal: callbacks.signal,
  });

  if (!res.ok) {
    const data = (await res.json().catch(() => ({}))) as { error?: string };
    throw new Error(data?.error ?? res.statusText ?? 'Negotiate and match stream failed');
  }

  const reader = res.body?.getReader();
  const decoder = new TextDecoder();
  let buffer = '';
  let lastDeals: Deal[] = [];

  if (reader) {
    while (true) {
      const { done, value } = await reader.read();
      if (done) break;
      buffer += decoder.decode(value, { stream: true });
      const lines = buffer.split('\n');
      buffer = lines.pop() ?? '';
      for (const line of lines) {
        if (line.startsWith('data: ')) {
          try {
            const event = JSON.parse(line.slice(6)) as NegotiationStreamEvent;
            if (event.type === 'done' && event.deals) lastDeals = event.deals;
            callbacks.onEvent(event);
          } catch {
            // skip malformed
          }
        }
      }
    }
    if (buffer.startsWith('data: ')) {
      try {
        const event = JSON.parse(buffer.slice(6)) as NegotiationStreamEvent;
        if (event.type === 'done' && event.deals) lastDeals = event.deals;
        callbacks.onEvent(event);
      } catch {
        // skip
      }
    }
  }

  return lastDeals;
}

export type ConversationTurn = { role: 'user' | 'assistant'; content: string };

export async function sendBuyerChatMessage(
  userId: number,
  accessToken: string,
  message: string,
  options?: { jobId?: string; jobTitle?: string; conversationHistory?: ConversationTurn[] }
): Promise<string> {
  const base = getAgentServiceUrl();
  const url = `${base}/agent/buyer/chat`;
  const body: {
    user_id: number;
    access_token: string;
    message: string;
    context: { jobId: string; jobTitle?: string; conversationHistory: ConversationTurn[] };
  } = {
    user_id: userId,
    access_token: accessToken,
    message,
    context: {
      jobId: options?.jobId ?? '',
      jobTitle: options?.jobTitle,
      conversationHistory: options?.conversationHistory ?? [],
    },
  };
  const res = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body),
  });
  const data = (await res.json().catch(() => ({}))) as { reply?: string; error?: string };
  if (!res.ok) {
    throw new Error(data?.error ?? res.statusText ?? 'Chat request failed');
  }
  return data.reply ?? '';
}

/** Options for buyer direct chat with a provider. */
export interface BuyerDirectChatOptions {
  jobId: string;
  jobTitle?: string;
  providerId: number;
  providerName?: string;
  price?: number;
  days?: number;
  paymentSchedule?: string;
  rating?: number;
  jobsCompleted?: number;
  conversationHistory?: ConversationTurn[];
}

/**
 * Send a direct chat message to a matched provider (AI-simulated provider response).
 */
export async function sendBuyerDirectChatMessage(
  userId: number,
  accessToken: string,
  message: string,
  options: BuyerDirectChatOptions
): Promise<string> {
  const base = getAgentServiceUrl();
  const url = `${base}/agent/buyer/direct-chat`;
  const body: Record<string, unknown> = {
    user_id: userId,
    access_token: accessToken,
    job_id: options.jobId,
    provider_id: options.providerId,
    message,
  };
  if (options.jobTitle) body.job_title = options.jobTitle;
  if (options.providerName) body.provider_name = options.providerName;
  if (options.price != null) body.price = options.price;
  if (options.days != null) body.days = options.days;
  if (options.paymentSchedule) body.payment_schedule = options.paymentSchedule;
  if (options.rating != null) body.rating = options.rating;
  if (options.jobsCompleted != null) body.jobs_completed = options.jobsCompleted;
  if (options.conversationHistory?.length) body.conversation_history = options.conversationHistory;

  const res = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body),
  });
  const data = (await res.json().catch(() => ({}))) as { reply?: string; error?: string };
  if (!res.ok) {
    throw new Error(data?.error ?? res.statusText ?? 'Direct chat request failed');
  }
  return data.reply ?? '';
}

/**
 * Clear Redis cache and Mem0 memories for a job (negotiation data + cached deals).
 * Call after recommended providers when user wants to reset/clean up for that job.
 */
export async function cleanupJobCache(
  userId: number,
  accessToken: string,
  jobId: string
): Promise<{ ok: boolean; redis?: { negotiationKeys: number; dealsKey: boolean }; mem0?: { deleted: number }; error?: string | null }> {
  const base = getAgentServiceUrl();
  const res = await fetch(`${base}/agent/buyer/job-cleanup`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      user_id: userId,
      access_token: accessToken,
      job_id: jobId,
    }),
  });
  const data = (await res.json().catch(() => ({}))) as {
    ok?: boolean;
    redis?: { negotiationKeys: number; dealsKey: boolean };
    mem0?: { deleted: number };
    error?: string;
  };
  if (!res.ok) {
    throw new Error(data?.error ?? res.statusText ?? 'Cleanup failed');
  }
  return {
    ok: data.ok ?? true,
    redis: data.redis,
    mem0: data.mem0,
    error: data.error ?? null,
  };
}

/** Profile returned by the agent (same source as match flow – Laravel provider-details). */
export interface SellerProfileFromAgent {
  provider_id: number;
  provider_name: string;
  average_rating: number;
  total_completed_order: number;
  num_of_rating?: number;
  package_list?: unknown[];
  licensed?: boolean;
  service_category_id?: number;
}

/**
 * Fetch the same provider profile the agent uses (Laravel provider-details).
 * Use this for dashboard stats so total_completed_order and average_rating match the agent.
 */
export async function getSellerProfile(
  providerId: number,
  accessToken: string,
  options?: { service_category_id?: number; lat?: number; long?: number }
): Promise<SellerProfileFromAgent> {
  const base = getAgentServiceUrl();
  const url = `${base}/agent/seller/profile`;
  const body: {
    provider_id: number;
    access_token: string;
    service_category_id?: number;
    lat?: number;
    long?: number;
  } = {
    provider_id: providerId,
    access_token: accessToken,
  };
  if (options?.service_category_id != null) body.service_category_id = options.service_category_id;
  if (options?.lat != null) body.lat = options.lat;
  if (options?.long != null) body.long = options.long;

  const res = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body),
  });
  const data = (await res.json().catch(() => ({}))) as { profile?: SellerProfileFromAgent; error?: string };
  if (!res.ok) {
    throw new Error(data?.error ?? res.statusText ?? 'Profile request failed');
  }
  if (!data.profile) {
    throw new Error('Profile not returned');
  }
  return data.profile;
}

/**
 * Match seller to available jobs.
 * Calls backend API which fetches real jobs from Laravel and evaluates matches.
 */
export async function matchSellerToJobs(
  providerId: number,
  accessToken: string,
  options?: {
    service_category_id?: number;
    sub_category_id?: number;
    agentConfig?: {
      average_rating: number;
      total_completed_order: number;
      num_of_rating: number;
      licensed: boolean;
      package_list: unknown[];
    };
  }
): Promise<Deal[]> {
  const base = getAgentServiceUrl();
  const url = `${base}/agent/seller/match`;
  const body: {
    provider_id: number;
    access_token: string;
    service_category_id?: number;
    sub_category_id?: number;
    agent_config?: {
      average_rating: number;
      total_completed_order: number;
      num_of_rating: number;
      licensed: boolean;
      package_list: unknown[];
    };
  } = {
    provider_id: providerId,
    access_token: accessToken,
  };
  if (options?.service_category_id != null) body.service_category_id = options.service_category_id;
  if (options?.sub_category_id != null) body.sub_category_id = options.sub_category_id;
  if (options?.agentConfig) body.agent_config = options.agentConfig;

  const res = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body),
  });
  const data = (await res.json().catch(() => ({}))) as { deals?: Deal[]; error?: string };
  if (!res.ok) {
    throw new Error(data?.error ?? res.statusText ?? 'Match request failed');
  }
  return data.deals ?? [];
}

/**
 * Send a chat message to the seller agent.
 * Backend agent uses real Laravel API to fetch provider data, packages, orders, etc.
 */
export async function sendSellerChatMessage(
  providerId: number,
  accessToken: string,
  message: string,
  options?: { orderId?: string; orderTitle?: string; conversationHistory?: ConversationTurn[] }
): Promise<string> {
  const base = getAgentServiceUrl();
  const url = `${base}/agent/seller/chat`;
  const body: {
    provider_id: number;
    access_token: string;
    message: string;
    order_id?: string;
    order_title?: string;
    conversation_history?: ConversationTurn[];
  } = {
    provider_id: providerId,
    access_token: accessToken,
    message,
  };
  if (options?.orderId) body.order_id = options.orderId;
  if (options?.orderTitle) body.order_title = options.orderTitle;
  if (options?.conversationHistory && options.conversationHistory.length > 0) {
    body.conversation_history = options.conversationHistory;
  }
  const res = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body),
  });
  const data = (await res.json().catch(() => ({}))) as { reply?: string; error?: string };
  if (!res.ok) {
    throw new Error(data?.error ?? res.statusText ?? 'Chat request failed');
  }
  return data.reply ?? '';
}

