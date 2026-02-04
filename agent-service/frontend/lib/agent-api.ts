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
    job_id?: string;
    job_title?: string;
    conversation_history?: ConversationTurn[];
  } = {
    user_id: userId,
    access_token: accessToken,
    message,
  };
  if (options?.jobId) body.job_id = options.jobId;
  if (options?.jobTitle) body.job_title = options.jobTitle;
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
