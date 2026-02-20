'use client';

import { useState, useRef, useEffect, useCallback } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import {
  ArrowLeft,
  Send,
  Loader2,
  ChevronDown,
  ChevronRight,
  User,
  Building2,
  Check,
  MessageSquarePlus,
  MessageCircle,
  PanelLeftClose,
  PanelLeft,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  unifiedAgentChatStream,
  getUserSessions,
  getSessionMessages,
  type UnifiedChatStreamEvent,
  type NegotiationStep,
  type SessionPreview,
  type SessionMessage,
} from '@/lib/agent-api';
import { useAuth, getAccessToken } from '@/lib/auth-context';
import type { Deal } from '@/lib/dummy-data';
import { ProviderMatchCard } from '@/components/ProviderMatchCard';
import { cn } from '@/lib/utils';

type MatchPhase = 'evaluating' | 'complete' | 'error';

type NegotiationProviderLog = {
  providerId: string;
  providerName: string;
  steps: NegotiationStep[];
  outcome?: { status: string; negotiatedPrice: number; negotiatedCompletionDays: number };
};

type UnifiedChatMessage =
  | { type: 'match'; phase: MatchPhase; deals?: Deal[]; error?: string; negotiationProviders?: NegotiationProviderLog[]; providersCount?: number }
  | { type: 'user'; content: string }
  | { type: 'assistant'; content: string }
  | { type: 'error'; content: string }
  | { type: 'provider_matches'; deals: Deal[] };

type ChatSession = {
  id: string;
  sessionId: string | null;
  title: string;
  phase: string;
  messages: UnifiedChatMessage[];
  updatedAt: number;
  isLoaded: boolean;
};

const MAX_TITLE_LENGTH = 40;

function NegotiationProviderCard({
  provider,
  rank,
}: {
  provider: NegotiationProviderLog;
  rank?: number;
}) {
  const [open, setOpen] = useState(true);
  const { providerName, steps, outcome } = provider;
  const displayName = providerName?.trim() || (rank != null ? `Provider ${rank}` : 'Provider');
  const quoteLine =
    outcome != null
      ? `$${outcome.negotiatedPrice}, ${outcome.negotiatedCompletionDays} day${outcome.negotiatedCompletionDays !== 1 ? 's' : ''}`
      : null;

  return (
    <div className="bg-secondary/30 rounded-lg border border-border/50 overflow-hidden">
      <button
        type="button"
        onClick={() => setOpen((o) => !o)}
        className="w-full flex items-center gap-2 p-3 text-left hover:bg-secondary/50 transition"
      >
        {open ? <ChevronDown size={16} className="text-muted-foreground shrink-0" /> : <ChevronRight size={16} className="text-muted-foreground shrink-0" />}
        <Building2 size={16} className="text-primary shrink-0" />
        {rank != null && <span className="text-sm font-semibold text-primary shrink-0">{rank}.</span>}
        <span className="font-medium text-foreground truncate">{displayName}</span>
        {outcome && (
          <span className="ml-auto text-xs text-muted-foreground shrink-0">
            {outcome.status === 'accepted' && <Check size={14} className="inline text-accent mr-1" />}
            {quoteLine}
          </span>
        )}
      </button>
      {open && (
        <div className="px-3 pb-3 pt-0 space-y-1.5">
          {steps.length === 0 && <p className="text-xs text-muted-foreground py-1">Waiting for offers…</p>}
          {steps.map((step, i) => (
            <div key={i} className="flex items-start gap-2 text-sm">
              <span className="shrink-0 mt-0.5">
                {step.role === 'buyer' ? <User size={14} className="text-primary" /> : <Building2 size={14} className="text-accent" />}
              </span>
              <div className="min-w-0 flex-1 text-foreground">
                <span>
                  <strong>{step.role === 'buyer' ? 'You' : 'Provider'}</strong>
                  {step.action === 'accept' && <span className="text-accent ml-1">· Quote</span>}
                  {step.price != null && step.completionDays != null && (
                    <span> · ${step.price}, {step.completionDays} day{step.completionDays !== 1 ? 's' : ''}</span>
                  )}
                </span>
                {step.message && <p className="text-muted-foreground mt-0.5 text-xs leading-relaxed">{step.message}</p>}
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}

function convertServerMessages(messages: SessionMessage[]): UnifiedChatMessage[] {
  return messages
    .filter((m) => m.role !== 'system')
    .map((m) => {
      if (m.role === 'user') {
        return { type: 'user' as const, content: m.content };
      }
      return { type: 'assistant' as const, content: m.content };
    });
}

function createNewChat(): ChatSession {
  return {
    id: `chat-${Date.now()}`,
    sessionId: null,
    title: 'New chat',
    phase: 'conversation',
    messages: [],
    updatedAt: Date.now(),
    isLoaded: true,
  };
}

export default function UnifiedChatPage() {
  const router = useRouter();
  const messagesEndRef = useRef<HTMLDivElement>(null);

  const [chats, setChats] = useState<ChatSession[]>([]);
  const [activeChatId, setActiveChatId] = useState<string | null>(null);
  const [inputValue, setInputValue] = useState('');
  const [sending, setSending] = useState(false);
  const [sidebarOpen, setSidebarOpen] = useState(true);
  const [loadingChats, setLoadingChats] = useState(true);
  const [loadingMessages, setLoadingMessages] = useState(false);

  const { session } = useAuth();
  const user = session.user;
  const token = getAccessToken();

  const activeChat = activeChatId ? chats.find((c) => c.id === activeChatId) : null;
  const displayMessages = activeChat?.messages ?? [];
  const phase = activeChat?.phase ?? 'conversation';

  useEffect(() => {
    if (!session.isLoading && (!user || user.role !== 'buyer')) {
      router.push('/auth');
    }
  }, [session.isLoading, user, router]);

  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [displayMessages]);

  useEffect(() => {
    if (!user?.id || user.role !== 'buyer') return;

    const loadSessions = async () => {
      setLoadingChats(true);
      try {
        const sessions = await getUserSessions('buyer', String(user.id), { limit: 50 });
        if (sessions.length === 0) {
          const newChat = createNewChat();
          setChats([newChat]);
          setActiveChatId(newChat.id);
        } else {
          const loadedChats: ChatSession[] = sessions.map((s: SessionPreview) => ({
            id: s.sessionId,
            sessionId: s.sessionId,
            title: s.title || 'Chat',
            phase: s.phase,
            messages: [],
            updatedAt: new Date(s.updatedAt).getTime(),
            isLoaded: false,
          }));
          setChats(loadedChats);
          setActiveChatId(loadedChats[0].id);
        }
      } catch (err) {
        console.error('Failed to load sessions:', err);
        const newChat = createNewChat();
        setChats([newChat]);
        setActiveChatId(newChat.id);
      } finally {
        setLoadingChats(false);
      }
    };

    loadSessions();
  }, [user?.id, user?.role]);

  useEffect(() => {
    if (!activeChat || activeChat.isLoaded || !activeChat.sessionId) return;

    const loadMessages = async () => {
      setLoadingMessages(true);
      try {
        const { session: sessionData, messages } = await getSessionMessages(activeChat.sessionId!);
        const convertedMessages = convertServerMessages(messages);
        setChats((prev) =>
          prev.map((c) =>
            c.id === activeChat.id
              ? { ...c, messages: convertedMessages, phase: sessionData.phase, isLoaded: true }
              : c
          )
        );
      } catch (err) {
        console.error('Failed to load messages:', err);
        setChats((prev) =>
          prev.map((c) => (c.id === activeChat.id ? { ...c, isLoaded: true } : c))
        );
      } finally {
        setLoadingMessages(false);
      }
    };

    loadMessages();
  }, [activeChat?.id, activeChat?.isLoaded, activeChat?.sessionId]);

  const updateActiveChat = useCallback(
    (updates: Partial<ChatSession>) => {
      if (!activeChatId) return;
      setChats((prev) =>
        prev.map((c) =>
          c.id === activeChatId ? { ...c, ...updates, updatedAt: Date.now() } : c
        )
      );
    },
    [activeChatId]
  );

  const handleNewChat = useCallback(() => {
    const newChat = createNewChat();
    setChats((prev) => [newChat, ...prev]);
    setActiveChatId(newChat.id);
    setSidebarOpen(false);
  }, []);

  const handleSelectChat = useCallback((id: string) => {
    setActiveChatId(id);
    setSidebarOpen(false);
  }, []);

  const findLastMatchIndex = useCallback(() => {
    let idx = -1;
    displayMessages.forEach((m, i) => {
      if (m.type === 'match') idx = i;
    });
    return idx;
  }, [displayMessages]);

  const updateLastMatch = useCallback(
    (updates: Partial<Extract<UnifiedChatMessage, { type: 'match' }>>) => {
      const idx = findLastMatchIndex();
      if (idx < 0) return;
      setChats((prev) =>
        prev.map((c) => {
          if (c.id !== activeChatId) return c;
          const newMessages = c.messages.map((m, i) =>
            i === idx && m.type === 'match' ? { ...m, ...updates } : m
          );
          return { ...c, messages: newMessages, updatedAt: Date.now() };
        })
      );
    },
    [activeChatId, findLastMatchIndex]
  );

  const handleApprove = async (deal: Deal) => {
    console.log('Approving deal:', deal);
    const msg: UnifiedChatMessage = {
      type: 'assistant',
      content: `Great! Booking ${deal.sellerName} for $${deal.quote.price}. They'll contact you shortly!`,
    };
    setChats((prev) =>
      prev.map((c) =>
        c.id === activeChatId ? { ...c, messages: [...c.messages, msg], updatedAt: Date.now() } : c
      )
    );
  };

  const handleReject = async (deal: Deal) => {
    console.log('Rejecting deal:', deal);
    const msg: UnifiedChatMessage = {
      type: 'assistant',
      content: `Noted. I've removed ${deal.sellerName} from your options. Would you like to see other providers?`,
    };
    setChats((prev) =>
      prev.map((c) =>
        c.id === activeChatId ? { ...c, messages: [...c.messages, msg], updatedAt: Date.now() } : c
      )
    );
  };

  const handleContact = async (deal: Deal) => {
    console.log('Contacting provider:', deal);
  };

  const handleSend = async (e: React.FormEvent) => {
    e.preventDefault();
    const text = inputValue.trim();
    if (!text || !user || !token || sending || !activeChatId) return;

    setInputValue('');
    const userMsg: UnifiedChatMessage = { type: 'user', content: text };
    setChats((prev) =>
      prev.map((c) =>
        c.id === activeChatId ? { ...c, messages: [...c.messages, userMsg], updatedAt: Date.now() } : c
      )
    );
    setSending(true);

    const providersById = new Map<string, NegotiationProviderLog>();
    const currentSessionId = activeChat?.sessionId ?? null;
    const isNewChat = currentSessionId == null;

    try {
      const { sessionId } = await unifiedAgentChatStream(
        'buyer',
        String(user.id),
        token,
        text,
        {
          sessionId: currentSessionId,
          forceNewSession: isNewChat,
          onEvent: (event: UnifiedChatStreamEvent) => {
            if (event.type === 'session' && event.sessionId) {
              updateActiveChat({ sessionId: event.sessionId });
            }
            if (event.type === 'phase') {
              updateActiveChat({ phase: event.phase });
            }
            if (event.type === 'message') {
              const assistantMsg: UnifiedChatMessage = { type: 'assistant', content: event.text };
              setChats((prev) =>
                prev.map((c) =>
                  c.id === activeChatId
                    ? { ...c, messages: [...c.messages, assistantMsg], updatedAt: Date.now() }
                    : c
                )
              );
            }
            if (event.type === 'phase_transition' && event.to === 'negotiation') {
              updateActiveChat({ phase: 'negotiation' });
              const matchMsg: UnifiedChatMessage = {
                type: 'match',
                phase: 'evaluating',
                negotiationProviders: [],
                deals: undefined,
              };
              setChats((prev) =>
                prev.map((c) =>
                  c.id === activeChatId
                    ? { ...c, messages: [...c.messages, matchMsg], updatedAt: Date.now() }
                    : c
                )
              );
            }
            if (event.type === 'providers_fetched') {
              updateLastMatch({ providersCount: event.count });
            }
            if (event.type === 'provider_start') {
              providersById.set(event.providerId, {
                providerId: event.providerId,
                providerName: event.providerName,
                steps: [],
              });
              updateLastMatch({ negotiationProviders: Array.from(providersById.values()) });
            }
            if (event.type === 'negotiation_step') {
              const log = providersById.get(event.providerId);
              if (log) {
                const updated = { ...log, steps: [...log.steps, event.step] };
                providersById.set(event.providerId, updated);
                updateLastMatch({ negotiationProviders: Array.from(providersById.values()) });
              }
            }
            if (event.type === 'provider_done') {
              const log = providersById.get(event.providerId);
              if (log) {
                const updated = { ...log, outcome: event.outcome };
                providersById.set(event.providerId, updated);
                updateLastMatch({ negotiationProviders: Array.from(providersById.values()) });
              }
            }
            if (event.type === 'done') {
              if (event.error) {
                updateLastMatch({ phase: 'error', error: event.error, deals: event.deals ?? [] });
              } else {
                updateLastMatch({
                  phase: 'complete',
                  deals: event.deals ?? [],
                  negotiationProviders: Array.from(providersById.values()),
                });
                if (event.deals && event.deals.length > 0) {
                  const matchesMsg: UnifiedChatMessage = { type: 'provider_matches', deals: event.deals };
                  setChats((prev) =>
                    prev.map((c) =>
                      c.id === activeChatId
                        ? { ...c, messages: [...c.messages, matchesMsg], updatedAt: Date.now() }
                        : c
                    )
                  );
                }
              }
              if (event.reply) {
                const replyMsg: UnifiedChatMessage = { type: 'assistant', content: event.reply };
                setChats((prev) =>
                  prev.map((c) =>
                    c.id === activeChatId
                      ? { ...c, messages: [...c.messages, replyMsg], updatedAt: Date.now() }
                      : c
                  )
                );
              }
            }
            if (event.type === 'error') {
              const errMsg: UnifiedChatMessage = { type: 'error', content: event.error };
              setChats((prev) =>
                prev.map((c) =>
                  c.id === activeChatId
                    ? { ...c, messages: [...c.messages, errMsg], updatedAt: Date.now() }
                    : c
                )
              );
            }
          },
        }
      );

      if (sessionId) {
        updateActiveChat({ sessionId });
      }

      const shouldUpdateTitle = activeChat?.title === 'New chat' || !activeChat?.title;
      if (shouldUpdateTitle) {
        const newTitle = text.slice(0, MAX_TITLE_LENGTH) + (text.length > MAX_TITLE_LENGTH ? '…' : '');
        updateActiveChat({ title: newTitle });
      }
    } catch (err) {
      const errMsg: UnifiedChatMessage = {
        type: 'error',
        content: err instanceof Error ? err.message : 'Something went wrong.',
      };
      setChats((prev) =>
        prev.map((c) =>
          c.id === activeChatId ? { ...c, messages: [...c.messages, errMsg], updatedAt: Date.now() } : c
        )
      );
    } finally {
      setSending(false);
    }
  };

  if (!user || user.role !== 'buyer') {
    return null;
  }

  return (
    <div className="min-h-screen bg-background flex">
      {sidebarOpen && (
        <button
          type="button"
          aria-label="Close sidebar"
          className="fixed inset-0 bg-black/40 z-20 md:hidden"
          onClick={() => setSidebarOpen(false)}
        />
      )}

      <aside
        className={cn(
          'flex flex-col border-r border-border bg-card text-card-foreground z-30',
          'md:transition-[width] md:duration-200 md:ease-out',
          sidebarOpen
            ? 'w-[280px] min-w-[280px] fixed inset-y-0 left-0 md:relative md:inset-auto'
            : 'w-0 min-w-0 overflow-hidden md:inline'
        )}
      >
        <div className={cn('flex flex-col h-full w-[280px]', !sidebarOpen && 'invisible w-0')}>
          <div className="p-3 border-b border-border flex items-center justify-between shrink-0">
            <Link
              href="/buyer/dashboard"
              className="text-muted-foreground hover:text-foreground transition p-1.5 rounded-lg hover:bg-secondary"
              aria-label="Back to dashboard"
            >
              <ArrowLeft size={20} />
            </Link>
            <Button
              variant="ghost"
              size="icon"
              onClick={() => setSidebarOpen(false)}
              className="shrink-0 lg:hidden"
              aria-label="Close sidebar"
            >
              <PanelLeftClose size={20} />
            </Button>
          </div>
          <Button
            onClick={handleNewChat}
            className="m-3 gap-2 bg-primary text-primary-foreground hover:bg-primary/90"
            aria-label="New chat"
          >
            <MessageSquarePlus size={18} />
            New chat
          </Button>
          <div className="flex-1 overflow-y-auto px-2 pb-4">
            {loadingChats ? (
              <div className="flex items-center justify-center py-8">
                <Loader2 size={20} className="animate-spin text-muted-foreground" />
              </div>
            ) : (
              <nav className="space-y-0.5" aria-label="Chat list">
                {chats.map((chat) => (
                  <button
                    key={chat.id}
                    type="button"
                    onClick={() => handleSelectChat(chat.id)}
                    className={cn(
                      'w-full text-left rounded-lg px-3 py-2.5 text-sm transition-colors flex items-center gap-2',
                      activeChatId === chat.id
                        ? 'bg-sidebar-accent text-sidebar-accent-foreground'
                        : 'hover:bg-sidebar-accent/60 text-muted-foreground hover:text-foreground'
                    )}
                  >
                    <MessageCircle size={16} className="shrink-0 opacity-70" />
                    <span className="truncate flex-1">{chat.title}</span>
                  </button>
                ))}
              </nav>
            )}
          </div>
        </div>
      </aside>

      <div className="flex-1 flex flex-col min-w-0">
        <header className="sticky top-0 bg-card border-b border-border z-20 flex items-center gap-2 px-4 py-3">
          <Button
            variant="ghost"
            size="icon"
            onClick={() => setSidebarOpen((o) => !o)}
            aria-label={sidebarOpen ? 'Close sidebar' : 'Open sidebar'}
          >
            {sidebarOpen ? <PanelLeftClose size={20} /> : <PanelLeft size={20} />}
          </Button>
          <h1 className="text-lg font-semibold text-foreground truncate flex-1">
            {activeChat?.title ?? 'Chat to find providers'}
          </h1>
          {phase && (
            <span className="text-xs text-muted-foreground capitalize shrink-0">{phase}</span>
          )}
        </header>

        <main className="flex-1 overflow-y-auto overflow-x-hidden px-4 sm:px-6 py-4 max-w-3xl w-full mx-auto">
          {loadingMessages ? (
            <div className="flex items-center justify-center py-8">
              <Loader2 size={24} className="animate-spin text-muted-foreground" />
            </div>
          ) : displayMessages.length === 0 ? (
            <div className="flex-1 flex items-center justify-center text-center px-4 min-h-[200px]">
              <p className="text-muted-foreground text-sm">
                Describe what you need (e.g. &quot;I need home cleaning&quot;). I&apos;ll ask about budget and dates, then find providers for you.
              </p>
            </div>
          ) : (
            <div className="space-y-4 pb-4">
              {displayMessages.map((m, i) => {
                const safeContent = (c: unknown): string =>
                  typeof c === 'string' ? c : c !== null && typeof c === 'object' ? JSON.stringify(c, null, 2) : String(c ?? '');
                if (m.type === 'user') {
                  return (
                    <div key={i} className="flex justify-end">
                      <div className="bg-primary text-primary-foreground rounded-lg px-4 py-2 max-w-[85%]">
                        <p className="text-sm whitespace-pre-wrap break-words">{safeContent(m.content)}</p>
                      </div>
                    </div>
                  );
                }
                if (m.type === 'assistant') {
                  return (
                    <div key={i} className="flex justify-start w-full">
                      <div className="bg-card border border-border rounded-lg px-4 py-3 w-full max-w-full min-w-0">
                        <div className="text-sm text-foreground whitespace-pre-wrap break-words leading-relaxed">
                          {safeContent(m.content)}
                        </div>
                      </div>
                    </div>
                  );
                }
                if (m.type === 'error') {
                  return (
                    <div key={i} className="flex justify-start">
                      <div className="bg-destructive/10 text-destructive rounded-lg px-4 py-2 max-w-[85%]">
                        <p className="text-sm">{safeContent(m.content)}</p>
                      </div>
                    </div>
                  );
                }
                if (m.type === 'match') {
                  return (
                    <div key={i} className="space-y-3">
                      <div className="text-sm font-medium text-muted-foreground">
                        {m.phase === 'evaluating' && (m.providersCount != null ? `Finding providers (${m.providersCount})…` : 'Finding providers…')}
                        {m.phase === 'complete' && `Found ${m.deals?.length ?? 0} provider(s).`}
                        {m.phase === 'error' && (m.error || 'Something went wrong.')}
                      </div>
                      {m.negotiationProviders && m.negotiationProviders.length > 0 && (
                        <div className="space-y-2">
                          {m.negotiationProviders.map((prov, idx) => (
                            <NegotiationProviderCard key={prov.providerId} provider={prov} rank={idx + 1} />
                          ))}
                        </div>
                      )}
                    </div>
                  );
                }
                if (m.type === 'provider_matches') {
                  return (
                    <div key={i} className="space-y-3">
                      <div className="bg-card border border-border rounded-lg p-4">
                        <h3 className="text-base font-semibold text-foreground mb-3 flex items-center gap-2">
                          <Building2 size={18} className="text-primary" />
                          Top Matches
                        </h3>
                        <div className="space-y-3">
                          {m.deals.map((deal, idx) => (
                            <ProviderMatchCard
                              key={deal.id || idx}
                              deal={deal}
                              rank={idx + 1}
                              onApprove={handleApprove}
                              onReject={handleReject}
                              onContact={handleContact}
                            />
                          ))}
                        </div>
                      </div>
                    </div>
                  );
                }
                return null;
              })}
            </div>
          )}
          <div ref={messagesEndRef} />
        </main>

        <div className="sticky bottom-0 bg-background border-t border-border py-4">
          <div className="max-w-3xl mx-auto px-4 sm:px-6">
            <form onSubmit={handleSend} className="flex gap-2">
              <Input
                type="text"
                placeholder="Type your message..."
                value={inputValue}
                onChange={(e) => setInputValue(e.target.value)}
                className="flex-1 bg-card border-border"
                aria-label="Message input"
                autoComplete="off"
                disabled={sending}
              />
              <Button
                type="submit"
                disabled={!inputValue.trim() || sending}
                className="bg-primary hover:bg-primary/90 text-primary-foreground shrink-0"
                aria-label="Send message"
              >
                {sending ? (
                  <Loader2 size={20} className="animate-spin" />
                ) : (
                  <Send size={20} />
                )}
              </Button>
            </form>
          </div>
        </div>
      </div>
    </div>
  );
}
