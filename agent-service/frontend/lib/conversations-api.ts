/**
 * Conversations API client.
 * Talks to GET /conversations and GET /conversations/:threadId on the agent service.
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

export type ConversationMeta = {
  id: string;
  threadId: string;
  title: string;
  status: string;
  agentType: 'buyer' | 'seller';
  createdAt: string;
  updatedAt: string;
};

export type ConversationDetail = ConversationMeta & {
  messages: { role: 'user' | 'assistant'; content: string }[];
  stateSnapshot: Record<string, unknown> | null;
};

/**
 * List all conversations for a given agent type, newest first.
 */
export async function listConversations(
  agentType: 'buyer' | 'seller'
): Promise<ConversationMeta[]> {
  const base = getAgentBaseUrl();
  const url = `${base}/conversations?agentType=${agentType}`;
  const res = await fetch(url, {
    method: 'GET',
    headers: { Accept: 'application/json' },
    // Always fetch fresh — don't cache the list
    cache: 'no-store',
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    const err = (data as { error?: string }).error ?? res.statusText;
    throw new Error(err);
  }
  return (data as { conversations: ConversationMeta[] }).conversations;
}

/**
 * Get a single conversation with messages + state snapshot.
 */
export async function getConversation(
  threadId: string
): Promise<ConversationDetail> {
  const base = getAgentBaseUrl();
  const url = `${base}/conversations/${encodeURIComponent(threadId)}`;
  const res = await fetch(url, {
    method: 'GET',
    headers: { Accept: 'application/json' },
    cache: 'no-store',
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    const err = (data as { error?: string }).error ?? res.statusText;
    throw new Error(err);
  }
  return (data as { conversation: ConversationDetail }).conversation;
}
