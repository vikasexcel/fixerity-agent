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
