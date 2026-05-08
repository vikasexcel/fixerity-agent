/**
 * Buyer Agent V2 API client.
 * Uses NEXT_PUBLIC_AGENT_API_URL (Express agent service).
 */

function getAgentBaseUrl(): string {
  const url = process.env.NEXT_PUBLIC_AGENT_API_URL;
  if (!url) {
    throw new Error(
      'NEXT_PUBLIC_AGENT_API_URL is not set. Add it to .env.local (see .env.example).'
    );
  }
  return url.replace(/\/$/, '');
}

export type BuyerStatus = 'gathering' | 'reviewing' | 'confirmed' | 'done';

export type MatchedSeller = {
  profileId: string;
  sellerName: string;
  profileText: string;
  matchScore: number;
  matchExplanation: string;
  metadata?: {
    location?: string;
    rate?: string;
    [key: string]: unknown;
  };
};

export type MatchingStatus = 'searching' | 'found' | 'error' | null;

export type StartResponse = {
  threadId: string;
  message: string;
  status?: BuyerStatus;
};

export type ChatResponse = {
  threadId: string;
  message: string;
  status?: BuyerStatus;
  /** Generated job post text (when status is "reviewing" or after "done") */
  jobPost?: string;
  /** Placeholder tokens found in the job post (e.g. "[SOME_FIELD]") */
  placeholders?: string[];
  /** Matched seller profiles after job is published */
  matchedSellers?: MatchedSeller[];
  matchingStatus?: MatchingStatus;
};

export type ThreadMessage = {
  role: 'user' | 'assistant';
  content: string;
};

export type ThreadStateResponse = {
  threadId: string;
  messages: ThreadMessage[];
  status?: BuyerStatus;
  questionCount?: number;
  jobPost?: string;
  placeholders?: string[];
  matchedSellers?: MatchedSeller[];
  matchingStatus?: MatchingStatus;
  sellerDecisions?: Record<string, 'approved' | 'rejected' | 'contacted'>;
};

export async function startConversation(message?: string): Promise<StartResponse> {
  const base = getAgentBaseUrl();
  const url = `${base}/buyer-agentv2/start`;
  const res = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify(message != null ? { message } : {}),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    const err = (data as { error?: string }).error ?? res.statusText;
    throw new Error(err);
  }
  return data as StartResponse;
}

export async function sendMessage(
  threadId: string,
  message: string
): Promise<ChatResponse> {
  const base = getAgentBaseUrl();
  const url = `${base}/buyer-agentv2/chat`;
  const res = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify({ threadId, message }),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    const err = (data as { error?: string }).error ?? res.statusText;
    throw new Error(err);
  }
  return data as ChatResponse;
}

export async function getThreadState(threadId: string): Promise<ThreadStateResponse> {
  const base = getAgentBaseUrl();
  const url = `${base}/buyer-agentv2/state/${encodeURIComponent(threadId)}`;
  const res = await fetch(url, {
    method: 'GET',
    headers: { Accept: 'application/json' },
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    const err = (data as { error?: string }).error ?? res.statusText;
    throw new Error(err);
  }
  return data as ThreadStateResponse;
}

export async function recordSellerDecision(
  threadId: string,
  profileId: string,
  decision: 'approved' | 'rejected' | 'contacted'
): Promise<void> {
  const base = getAgentBaseUrl();
  const url = `${base}/buyer-agentv2/seller-decision`;
  const res = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify({ threadId, profileId, decision }),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    const err = (data as { error?: string }).error ?? res.statusText;
    throw new Error(err);
  }
}
