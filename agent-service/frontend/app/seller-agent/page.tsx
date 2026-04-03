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
  type MatchedJob,
} from '@/lib/seller-agent-api';
import { listConversations, updateConversationTitle, type ConversationMeta } from '@/lib/conversations-api';
import { useAuth } from '@/lib/auth-context';
import { cn } from '@/lib/utils';
import { JobMatchCard } from '@/components/job-match-card';

type ThreadMeta = { threadId: string; title: string; updatedAt: number };

function conversationMetaToThreadMeta(c: ConversationMeta): ThreadMeta {
  return {
    threadId: c.threadId,
    title: c.title || 'New conversation',
    updatedAt: new Date(c.updatedAt).getTime(),
  };
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
  const [matchedJobs, setMatchedJobs] = useState<MatchedJob[] | null>(null);
  const [jobMatchingStatus, setJobMatchingStatus] = useState<'found' | 'error' | null>(null);
  const [jobDecisions, setJobDecisions] = useState<Record<string, 'interested' | 'skipped'>>({});
  const [jobsPage, setJobsPage] = useState(1);
  const [renamingThreadId, setRenamingThreadId] = useState<string | null>(null);
  const [renameValue, setRenameValue] = useState('');
  const inputRef = React.useRef<HTMLTextAreaElement>(null);
  const renameInputRef = React.useRef<HTMLInputElement>(null);

  const MATCHES_PAGE_SIZE = 10;
  const jobsList = matchedJobs ?? [];
  const jobsTotalPages = Math.max(1, Math.ceil(jobsList.length / MATCHES_PAGE_SIZE));
  const jobsStart = (jobsPage - 1) * MATCHES_PAGE_SIZE;
  const paginatedJobs = jobsList.slice(jobsStart, jobsStart + MATCHES_PAGE_SIZE);

  /** Refresh the sidebar thread list from the DB. */
  const refreshThreadList = useCallback(async () => {
    try {
      const conversations = await listConversations('seller');
      setThreadList(conversations.map(conversationMetaToThreadMeta));
    } catch {
      // Non-fatal — sidebar may be stale, but the chat still works
    }
  }, []);

  /** Optimistically add/update an entry in the sidebar, then refresh from DB. */
  const updateThreadList = useCallback((id: string, title: string) => {
    setThreadList((prev) => {
      const filtered = prev.filter((t) => t.threadId !== id);
      return [{ threadId: id, title: title || 'New conversation', updatedAt: Date.now() }, ...filtered];
    });
    setTimeout(() => refreshThreadList(), 500);
  }, [refreshThreadList]);

  useEffect(() => {
    refreshThreadList();
  }, [refreshThreadList]);

  useEffect(() => {
    setJobsPage(1);
  }, [jobsList.length]);

  const startNewChat = useCallback(async () => {
    setError(null);
    setLoading(true);
    setSellerProfile(null);
    setPlaceholders(null);
    setStatus(null);
    setMatchedJobs(null);
    setJobMatchingStatus(null);
    setJobDecisions({});
    setJobsPage(1);
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
    setMatchedJobs(null);
    setJobMatchingStatus(null);
    setJobDecisions({});
    setJobsPage(1);
    try {
      const state = await getThreadState(id);
      setThreadId(state.threadId);
      setStatus(state.status ?? null);
      setMessages(state.messages ?? []);
      if (state.sellerProfile != null) setSellerProfile(state.sellerProfile);
      if (state.placeholders?.length) setPlaceholders(state.placeholders);
      if (state.matchedJobs != null) setMatchedJobs(state.matchedJobs);
      if (state.jobMatchingStatus != null) setJobMatchingStatus(state.jobMatchingStatus);
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
        if (res.matchedJobs != null) setMatchedJobs(res.matchedJobs);
        if (res.jobMatchingStatus != null) setJobMatchingStatus(res.jobMatchingStatus);
        updateThreadList(threadId, threadList.find((t) => t.threadId === threadId)?.title ?? 'New conversation');
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

  const startRename = useCallback((t: ThreadMeta, e: React.MouseEvent) => {
    e.stopPropagation();
    setRenamingThreadId(t.threadId);
    setRenameValue(t.title);
    setTimeout(() => renameInputRef.current?.select(), 0);
  }, []);

  const commitRename = useCallback(async () => {
    if (!renamingThreadId) return;
    const trimmed = renameValue.trim();
    if (trimmed) {
      try {
        await updateConversationTitle(renamingThreadId, trimmed);
        setThreadList((prev) =>
          prev.map((t) => t.threadId === renamingThreadId ? { ...t, title: trimmed } : t)
        );
      } catch {
        // silently ignore — title stays as-is in the UI
      }
    }
    setRenamingThreadId(null);
  }, [renamingThreadId, renameValue]);

  const handleRenameKeyDown = useCallback((e: React.KeyboardEvent) => {
    if (e.key === 'Enter') { e.preventDefault(); commitRename(); }
    if (e.key === 'Escape') { setRenamingThreadId(null); }
  }, [commitRename]);

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
              <li key={t.threadId} className="group relative">
                {renamingThreadId === t.threadId ? (
                  <input
                    ref={renameInputRef}
                    type="text"
                    value={renameValue}
                    onChange={(e) => setRenameValue(e.target.value)}
                    onBlur={commitRename}
                    onKeyDown={handleRenameKeyDown}
                    className="w-full rounded-md px-3 py-2 text-sm bg-background border border-ring focus:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    aria-label="Rename conversation"
                  />
                ) : (
                  <div className="flex items-center">
                    <button
                      type="button"
                      onClick={() => loadThread(t.threadId)}
                      disabled={loading}
                      className={cn(
                        'flex-1 text-left rounded-md px-3 py-2 text-sm truncate focus:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:opacity-50',
                        threadId === t.threadId
                          ? 'bg-accent text-accent-foreground'
                          : 'hover:bg-muted text-foreground'
                      )}
                    >
                      {t.title}
                    </button>
                    <button
                      type="button"
                      onClick={(e) => startRename(t, e)}
                      className="shrink-0 opacity-0 group-hover:opacity-100 p-1 mr-1 rounded text-muted-foreground hover:text-foreground focus:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                      aria-label="Rename conversation"
                      title="Rename"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    </button>
                  </div>
                )}
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
          {loading && status === 'reviewing' && (
            <p className="text-sm text-muted-foreground max-w-3xl flex items-center gap-2" role="status">
              <span className="inline-block h-4 w-4 border-2 border-primary border-t-transparent rounded-full animate-spin" aria-hidden />
              Finding the best jobs for your profile…
            </p>
          )}
          {jobMatchingStatus === 'found' && jobsList.length > 0 && (
            <div className="space-y-3 max-w-3xl">
              <h2 className="text-sm font-semibold">Matched Jobs for Your Profile</h2>
              <p className="text-xs text-muted-foreground">
                Showing {jobsStart + 1}–{Math.min(jobsStart + MATCHES_PAGE_SIZE, jobsList.length)} of {jobsList.length} (sorted by match score)
              </p>
              <ul className="space-y-3 list-none p-0 m-0">
                {paginatedJobs.map((job, i) => (
                  <li key={job.jobId}>
                    <JobMatchCard
                      rank={jobsStart + i + 1}
                      job={job}
                      onInterested={() => setJobDecisions((prev) => ({ ...prev, [job.jobId]: 'interested' }))}
                      onSkip={() => setJobDecisions((prev) => ({ ...prev, [job.jobId]: 'skipped' }))}
                      decision={jobDecisions[job.jobId]}
                    />
                  </li>
                ))}
              </ul>
              {jobsList.length > MATCHES_PAGE_SIZE && (
                <nav
                  className="flex items-center justify-between gap-2 pt-2 border-t border-border"
                  aria-label="Jobs pagination"
                >
                  <button
                    type="button"
                    onClick={() => setJobsPage((p) => Math.max(1, p - 1))}
                    disabled={jobsPage <= 1}
                    className="rounded-md border border-border bg-background px-3 py-1.5 text-sm font-medium hover:bg-muted disabled:opacity-50 disabled:pointer-events-none"
                  >
                    Previous
                  </button>
                  <span className="text-sm text-muted-foreground">
                    Page {jobsPage} of {jobsTotalPages}
                  </span>
                  <button
                    type="button"
                    onClick={() => setJobsPage((p) => Math.min(jobsTotalPages, p + 1))}
                    disabled={jobsPage >= jobsTotalPages}
                    className="rounded-md border border-border bg-background px-3 py-1.5 text-sm font-medium hover:bg-muted disabled:opacity-50 disabled:pointer-events-none"
                  >
                    Next
                  </button>
                </nav>
              )}
            </div>
          )}
          {jobMatchingStatus === 'found' && (!matchedJobs || matchedJobs.length === 0) && (
            <p className="text-sm text-muted-foreground max-w-3xl" role="status">
              We weren&apos;t able to find jobs for you right now.
            </p>
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
