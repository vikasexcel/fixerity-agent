/**
 * Seller Agent V2 API client.
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

export type SellerStatus = 'gathering' | 'reviewing' | 'confirmed' | 'done';

export type StartResponse = {
  threadId: string;
  message: string;
  status?: SellerStatus;
};

export type ChatResponse = {
  threadId: string;
  message: string;
  status?: SellerStatus;
  /** Generated seller profile text (when status is "reviewing" or after "done") */
  sellerProfile?: string;
  /** Placeholder tokens found in the profile (e.g. "[YOUR CITY]") */
  placeholders?: string[];
};

export type ThreadMessage = {
  role: 'user' | 'assistant';
  content: string;
};

export type ThreadStateResponse = {
  threadId: string;
  messages: ThreadMessage[];
  status?: SellerStatus;
  questionCount?: number;
  sellerProfile?: string;
  placeholders?: string[];
};

export async function startConversation(message?: string): Promise<StartResponse> {
  const base = getAgentBaseUrl();
  const url = `${base}/seller-agentv2/start`;
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
  const url = `${base}/seller-agentv2/chat`;
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
  const url = `${base}/seller-agentv2/state/${encodeURIComponent(threadId)}`;
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
