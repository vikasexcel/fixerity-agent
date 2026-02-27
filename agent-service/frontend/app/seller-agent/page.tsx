'use client';

import React, {
  useCallback,
  useEffect,
  useState,
  startTransition,
} from 'react';
import {
  startConversation,
  sendMessage as sendMessageApi,
  getThreadState,
  type ThreadMessage,
  type ChatResponse,
  type SellerStatus,
} from '@/lib/seller-agent-api';
import { useAuth } from '@/lib/auth-context';
import { cn } from '@/lib/utils';

const THREADS_STORAGE_KEY = 'seller_agent_threads';

type ThreadMeta = { threadId: string; title: string; updatedAt: number };

function loadThreadList(): ThreadMeta[] {
  if (typeof window === 'undefined') return [];
  try {
    const raw = localStorage.getItem(THREADS_STORAGE_KEY);
    if (!raw) return [];
    const parsed = JSON.parse(raw);
    return Array.isArray(parsed) ? parsed : [];
  } catch {
    return [];
  }
}

function saveThreadList(list: ThreadMeta[]) {
  if (typeof window === 'undefined') return;
  localStorage.setItem(THREADS_STORAGE_KEY, JSON.stringify(list));
}

function addOrUpdateThread(list: ThreadMeta[], threadId: string, title: string): ThreadMeta[] {
  const now = Date.now();
  const filtered = list.filter((t) => t.threadId !== threadId);
  return [{ threadId, title: title || 'New conversation', updatedAt: now }, ...filtered];
}

export default function SellerAgentPage() {
  const { session, logout } = useAuth();
  const [threadId, setThreadId] = useState<string | null>(null);
  const [threadList, setThreadList] = useState<ThreadMeta[]>([]);
  const [messages, setMessages] = useState<ThreadMessage[]>([]);
  const [inputValue, setInputValue] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [status, setStatus] = useState<SellerStatus | null>(null);
  const [sellerProfile, setSellerProfile] = useState<string | null>(null);
  const [placeholders, setPlaceholders] = useState<string[] | null>(null);
  const inputRef = React.useRef<HTMLTextAreaElement>(null);

  const updateThreadList = useCallback((threadId: string, title: string) => {
    setThreadList((prev) => {
      const next = addOrUpdateThread(prev, threadId, title);
      saveThreadList(next);
      return next;
    });
  }, []);

  useEffect(() => {
    setThreadList(loadThreadList());
  }, []);

  const startNewChat = useCallback(async () => {
    setError(null);
    setLoading(true);
    setSellerProfile(null);
    setPlaceholders(null);
    setStatus(null);
    try {
      const res = await startConversation();
      setThreadId(res.threadId);
      setStatus(res.status ?? null);
      setMessages([
        { role: 'user', content: 'I want to create a seller profile. Help me get started.' },
        { role: 'assistant', content: res.message },
      ]);
      updateThreadList(res.threadId, 'New conversation');
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to start conversation');
      setMessages([]);
    } finally {
      setLoading(false);
      startTransition(() => inputRef.current?.focus());
    }
  }, [updateThreadList]);

  const loadThread = useCallback(async (id: string) => {
    setError(null);
    setLoading(true);
    setSellerProfile(null);
    setPlaceholders(null);
    setStatus(null);
    try {
      const state = await getThreadState(id);
      setThreadId(state.threadId);
      setStatus(state.status ?? null);
      setMessages(state.messages ?? []);
      if (state.sellerProfile != null) setSellerProfile(state.sellerProfile);
      if (state.placeholders?.length) setPlaceholders(state.placeholders);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load conversation');
      setMessages([]);
    } finally {
      setLoading(false);
      startTransition(() => inputRef.current?.focus());
    }
  }, []);

  const handleSend = useCallback(
    async (e: React.FormEvent) => {
      e.preventDefault();
      const text = inputValue.trim();
      if (!text) return;
      if (!threadId) {
        setError('Start a new chat first.');
        return;
      }
      setInputValue('');
      setError(null);
      const userMessage: ThreadMessage = { role: 'user', content: text };
      setMessages((prev) => [...prev, userMessage]);
      setLoading(true);
      try {
        const res: ChatResponse = await sendMessageApi(threadId, text);
        setMessages((prev) => [
          ...prev,
          { role: 'assistant', content: res.message },
        ]);
        setStatus(res.status ?? null);
        if (res.sellerProfile != null) setSellerProfile(res.sellerProfile);
        if (res.placeholders?.length) setPlaceholders(res.placeholders);
        const title = text.slice(0, 40) + (text.length > 40 ? '…' : '');
        updateThreadList(threadId, title);
      } catch (err) {
        setError(err instanceof Error ? err.message : 'Failed to send message');
        setMessages((prev) => prev.slice(0, -1));
        setInputValue(text);
      } finally {
        setLoading(false);
        startTransition(() => inputRef.current?.focus());
      }
    },
    [inputValue, threadId, updateThreadList]
  );

  const handleKeyDown = useCallback(
    (e: React.KeyboardEvent) => {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        handleSend(e as unknown as React.FormEvent);
      }
    },
    [handleSend]
  );

  const messageListId = 'seller-agent-messages';

  return (
    <div className="flex h-screen bg-background text-foreground">
      <aside
        className="w-64 shrink-0 border-r border-border bg-card flex flex-col"
        aria-label="Conversation sidebar"
      >
        <div className="p-3 border-b border-border">
          <button
            type="button"
            onClick={startNewChat}
            disabled={loading}
            className="w-full flex items-center justify-center gap-2 rounded-md border border-border bg-primary text-primary-foreground py-2.5 px-3 text-sm font-medium hover:bg-primary/90 focus:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:opacity-50"
            aria-label="Start new chat"
          >
            <span aria-hidden>+</span> New chat
          </button>
        </div>
        <nav
          className="flex-1 overflow-y-auto p-2"
          aria-label="Previous conversations"
        >
          <ul className="space-y-1">
            {threadList.map((t) => (
              <li key={t.threadId}>
                <button
                  type="button"
                  onClick={() => loadThread(t.threadId)}
                  disabled={loading}
                  className={cn(
                    'w-full text-left rounded-md px-3 py-2 text-sm truncate focus:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:opacity-50',
                    threadId === t.threadId
                      ? 'bg-accent text-accent-foreground'
                      : 'hover:bg-muted text-foreground'
                  )}
                >
                  {t.title}
                </button>
              </li>
            ))}
          </ul>
        </nav>
        <div className="p-2 border-t border-border">
          <p className="text-xs text-muted-foreground truncate px-2" title={session.user?.email}>
            {session.user?.name || session.user?.email || 'Seller'}
          </p>
          <button
            type="button"
            onClick={() => logout()}
            className="mt-1 w-full text-left rounded-md px-3 py-1.5 text-sm text-muted-foreground hover:bg-muted focus:outline-none focus-visible:ring-2 focus-visible:ring-ring"
          >
            Sign out
          </button>
        </div>
      </aside>

      <main className="flex-1 flex flex-col min-w-0" id="main-content" role="main">
        <header className="shrink-0 border-b border-border px-4 py-3 bg-card">
          <h1 className="text-lg font-semibold">Seller Agent</h1>
          <p className="text-sm text-muted-foreground">
            Create a seller profile with the help of the assistant.
          </p>
        </header>

        <section
          id={messageListId}
          className="flex-1 overflow-y-auto p-4 space-y-4"
          aria-live="polite"
          aria-label="Chat messages"
        >
          {messages.length === 0 && !loading && !threadId && (
            <p className="text-muted-foreground text-center py-8">
              Click &quot;New chat&quot; to start a conversation.
            </p>
          )}
          {messages.length === 0 && !loading && threadId && (
            <p className="text-muted-foreground text-center py-8">
              Send a message to continue.
            </p>
          )}
          {messages.map((m, i) => (
            <article
              key={i}
              className={cn(
                'rounded-lg px-4 py-2.5 max-w-3xl',
                m.role === 'user'
                  ? 'bg-primary text-primary-foreground ml-auto'
                  : 'bg-muted text-foreground'
              )}
              aria-label={m.role === 'user' ? 'Your message' : 'Assistant message'}
            >
              <p className="text-sm whitespace-pre-wrap break-words">{m.content}</p>
            </article>
          ))}
          {status === 'reviewing' && (
            <p className="text-sm text-muted-foreground max-w-3xl" role="status">
              Review the seller profile below. Reply to confirm or ask for changes.
            </p>
          )}
          {sellerProfile && (
            <article
              className="rounded-lg border border-border bg-card p-4 max-w-3xl"
              aria-label="Generated seller profile"
            >
              <h2 className="text-sm font-semibold mb-2">Seller profile</h2>
              <pre className="text-xs overflow-x-auto whitespace-pre-wrap break-words font-sans">
                {sellerProfile}
              </pre>
              {placeholders && placeholders.length > 0 && (
                <>
                  <h3 className="text-sm font-semibold mt-3 mb-1">Placeholders</h3>
                  <ul className="flex flex-wrap gap-1.5 text-xs">
                    {placeholders.map((p, i) => (
                      <li
                        key={i}
                        className="rounded bg-muted px-2 py-0.5 font-mono"
                      >
                        {p}
                      </li>
                    ))}
                  </ul>
                </>
              )}
            </article>
          )}
        </section>

        {error && (
          <div
            id="seller-agent-error"
            className="shrink-0 px-4 py-2 bg-destructive/10 text-destructive text-sm"
            role="alert"
          >
            {error}
          </div>
        )}

        <form
          onSubmit={handleSend}
          className="shrink-0 border-t border-border p-4 bg-card"
          aria-label="Send a message"
        >
          <div className="flex gap-2 max-w-3xl mx-auto">
            <label htmlFor="seller-agent-input" className="sr-only">
              Message
            </label>
            <textarea
              id="seller-agent-input"
              ref={inputRef}
              value={inputValue}
              onChange={(e) => setInputValue(e.target.value)}
              onKeyDown={handleKeyDown}
              placeholder="Type your message…"
              rows={2}
              disabled={loading || !threadId}
              className="flex-1 rounded-md border border-border bg-input px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:opacity-50 resize-none min-h-[44px]"
              aria-describedby={error ? 'seller-agent-error' : undefined}
            />
            <button
              type="submit"
              disabled={loading || !threadId || !inputValue.trim()}
              className="shrink-0 rounded-md bg-primary text-primary-foreground px-4 py-2 text-sm font-medium hover:bg-primary/90 focus:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:opacity-50 disabled:pointer-events-none"
              aria-label="Send message"
            >
              Send
            </button>
          </div>
        </form>
      </main>
    </div>
  );
}
