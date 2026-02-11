'use client';

import { useState, useRef, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import { ArrowLeft, Send, Loader2, ChevronDown, ChevronRight, User, Building2, Check } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  unifiedAgentChatStream,
  type UnifiedChatStreamEvent,
  type NegotiationStep,
} from '@/lib/agent-api';
import { useAuth, getAccessToken } from '@/lib/auth-context';
import type { Deal } from '@/lib/dummy-data';
import { ProviderMatchCard } from '@/components/ProviderMatchCard';

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
  | { type: 'provider_matches'; deals: Deal[] }; // New type for final matches

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

export default function UnifiedChatPage() {
  const router = useRouter();
  const sessionIdRef = useRef<string | null>(null);
  const messagesEndRef = useRef<HTMLDivElement>(null);

  const [messages, setMessages] = useState<UnifiedChatMessage[]>([]);
  const [inputValue, setInputValue] = useState('');
  const [sending, setSending] = useState(false);
  const [phase, setPhase] = useState<string>('conversation');

  const { session } = useAuth();
  const user = session.user;
  const token = getAccessToken();

  useEffect(() => {
    if (!session.isLoading && (!user || user.role !== 'buyer')) {
      router.push('/auth');
    }
  }, [session.isLoading, user, router]);

  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [messages]);

  const findLastMatchIndex = () => {
    let idx = -1;
    messages.forEach((m, i) => {
      if (m.type === 'match') idx = i;
    });
    return idx;
  };

  const updateLastMatch = (updates: Partial<Extract<UnifiedChatMessage, { type: 'match' }>>) => {
    const idx = findLastMatchIndex();
    if (idx < 0) return;
    setMessages((prev) =>
      prev.map((m, i) => (i === idx && m.type === 'match' ? { ...m, ...updates } : m))
    );
  };

  const handleApprove = async (deal: Deal) => {
    console.log('Approving deal:', deal);
    setMessages((prev) => [
      ...prev,
      { 
        type: 'assistant', 
        content: `Great! Booking ${deal.sellerName} for $${deal.quote.price}. They'll contact you shortly!` 
      }
    ]);
    // TODO: Call API to approve/book the provider
  };

  const handleReject = async (deal: Deal) => {
    console.log('Rejecting deal:', deal);
    setMessages((prev) => [
      ...prev,
      { 
        type: 'assistant', 
        content: `Noted. I've removed ${deal.sellerName} from your options. Would you like to see other providers?` 
      }
    ]);
    // TODO: Call API to reject the provider
  };

  const handleContact = async (deal: Deal) => {
    console.log('Contacting provider:', deal);
    // TODO: Navigate to direct messaging with provider
    // Example: router.push(`/buyer/messages/${deal.sellerId}`);
  };

  const handleSend = async (e: React.FormEvent) => {
    e.preventDefault();
    const text = inputValue.trim();
    if (!text || !user || !token || sending) return;

    setInputValue('');
    setMessages((prev) => [...prev, { type: 'user', content: text }]);
    setSending(true);

    const providersById = new Map<string, NegotiationProviderLog>();

    try {
      const { sessionId } = await unifiedAgentChatStream(
        'buyer',
        String(user.id),
        token,
        text,
        {
          sessionId: sessionIdRef.current,
          onEvent: (event: UnifiedChatStreamEvent) => {
            if (event.type === 'session' && event.sessionId) {
              sessionIdRef.current = event.sessionId;
            }
            if (event.type === 'phase') {
              setPhase(event.phase);
            }
            if (event.type === 'message') {
              setMessages((prev) => [...prev, { type: 'assistant', content: event.text }]);
            }
            if (event.type === 'phase_transition' && event.to === 'negotiation') {
              setPhase('negotiation');
              setMessages((prev) => [
                ...prev,
                {
                  type: 'match',
                  phase: 'evaluating',
                  negotiationProviders: [],
                  deals: undefined,
                },
              ]);
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
                
                // Add provider matches as a separate message for better UI
                if (event.deals && event.deals.length > 0) {
                  setMessages((prev) => [
                    ...prev,
                    { type: 'provider_matches', deals: event.deals }
                  ]);
                }
              }
              if (event.reply) {
                setMessages((prev) => [...prev, { type: 'assistant', content: event.reply! }]);
              }
            }
            if (event.type === 'error') {
              setMessages((prev) => [...prev, { type: 'error', content: event.error }]);
            }
          },
        }
      );
      if (sessionId) sessionIdRef.current = sessionId;
    } catch (err) {
      setMessages((prev) => [
        ...prev,
        { type: 'error', content: err instanceof Error ? err.message : 'Something went wrong.' },
      ]);
    } finally {
      setSending(false);
    }
  };

  if (!user || user.role !== 'buyer') {
    return null;
  }

  return (
    <div className="min-h-screen bg-background flex flex-col">
      <header className="sticky top-0 bg-card border-b border-border z-40">
        <div className="max-w-3xl mx-auto px-4 py-3 flex items-center gap-3">
          <Link
            href="/buyer/dashboard"
            className="text-muted-foreground hover:text-foreground flex items-center gap-1 text-sm"
          >
            <ArrowLeft size={18} />
            Back
          </Link>
          <h1 className="text-lg font-semibold text-foreground">Chat to find providers</h1>
          {phase && (
            <span className="text-xs text-muted-foreground capitalize ml-auto">{phase}</span>
          )}
        </div>
      </header>

      <main className="flex-1 max-w-3xl w-full mx-auto px-4 py-4 flex flex-col">
        {messages.length === 0 && (
          <div className="flex-1 flex items-center justify-center text-center px-4">
            <p className="text-muted-foreground text-sm">
              Describe what you need (e.g. &quot;I need home cleaning&quot;). I&apos;ll ask about budget and dates, then find providers for you.
            </p>
          </div>
        )}

        <div className="space-y-4 pb-4">
          {messages.map((m, i) => {
            if (m.type === 'user') {
              return (
                <div key={i} className="flex justify-end">
                  <div className="bg-primary text-primary-foreground rounded-lg px-4 py-2 max-w-[85%]">
                    <p className="text-sm">{m.content}</p>
                  </div>
                </div>
              );
            }
            if (m.type === 'assistant') {
              return (
                <div key={i} className="flex justify-start">
                  <div className="bg-secondary/50 rounded-lg px-4 py-2 max-w-[85%]">
                    <p className="text-sm text-foreground whitespace-pre-wrap">{m.content}</p>
                  </div>
                </div>
              );
            }
            if (m.type === 'error') {
              return (
                <div key={i} className="flex justify-start">
                  <div className="bg-destructive/10 text-destructive rounded-lg px-4 py-2 max-w-[85%]">
                    <p className="text-sm">{m.content}</p>
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

        <div ref={messagesEndRef} />

        <form onSubmit={handleSend} className="sticky bottom-0 bg-background pt-2 pb-4 flex gap-2">
          <Input
            value={inputValue}
            onChange={(e) => setInputValue(e.target.value)}
            placeholder="Type your message..."
            className="flex-1"
            disabled={sending}
          />
          <Button type="submit" disabled={sending || !inputValue.trim()}>
            {sending ? <Loader2 size={18} className="animate-spin" /> : <Send size={18} />}
          </Button>
        </form>
      </main>
    </div>
  );
}