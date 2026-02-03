/**
 * Agent service API client for buyer match.
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
