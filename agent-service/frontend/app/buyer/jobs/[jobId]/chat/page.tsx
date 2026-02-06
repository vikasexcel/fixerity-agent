'use client';

import { useState, useEffect, useRef } from 'react';
import { useRouter, useParams } from 'next/navigation';
import Link from 'next/link';
import { ArrowLeft, Zap, Star, Send, Loader2, ChevronDown, ChevronRight, User, Building2, Check } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { matchJobToProvidersWithNegotiationStream, sendBuyerChatMessage, type NegotiationStep } from '@/lib/agent-api';
import { useAuth, getAccessToken } from '@/lib/auth-context';
import { listJobs, updateJobStatus } from '@/lib/jobs-api';
import type { Job, Deal } from '@/lib/dummy-data';

type MatchPhase = 'initializing' | 'scanning' | 'evaluating' | 'ranking' | 'complete' | 'error';

export type NegotiationProviderLog = {
  providerId: string;
  providerName: string;
  steps: NegotiationStep[];
  outcome?: { status: string; negotiatedPrice: number; negotiatedCompletionDays: number };
};

type ChatMessage =
  | { type: 'match'; phase: MatchPhase; deals?: Deal[]; error?: string; negotiationProviders?: NegotiationProviderLog[]; providersCount?: number }
  | { type: 'user'; content: string }
  | { type: 'assistant'; content: string };

function normalizeJobId(id: string): string {
  return String(id).trim();
}

function NegotiationProviderCard({ provider, rank, score }: { provider: NegotiationProviderLog; rank?: number; score?: number }) {
  const [open, setOpen] = useState(true);
  const { providerName, steps, outcome } = provider;
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
        <span className="font-medium text-foreground truncate">{providerName}</span>
        {score != null && (
          <span className="text-sm font-semibold text-accent shrink-0">{score}%</span>
        )}
        {outcome && (
          <span className="ml-auto flex items-center gap-1 text-xs text-muted-foreground shrink-0">
            {outcome.status === 'accepted' && <Check size={14} className="text-accent" />}
            ${outcome.negotiatedPrice} · {outcome.negotiatedCompletionDays}d
          </span>
        )}
      </button>
      {open && (
        <div className="px-3 pb-3 pt-0 space-y-1.5">
          {steps.length === 0 && (
            <p className="text-xs text-muted-foreground py-1">Waiting for offers…</p>
          )}
          {steps.map((step, i) => (
            <div key={i} className="flex items-start gap-2 text-sm">
              <span className="shrink-0 mt-0.5">
                {step.role === 'buyer' ? (
                  <User size={14} className="text-primary" />
                ) : (
                  <Building2 size={14} className="text-accent" />
                )}
              </span>
              <div className="min-w-0 flex-1 text-foreground">
                <span>
                  <strong className="text-foreground">{step.role === 'buyer' ? 'You' : 'Provider'}</strong>
                  {step.action === 'accept' && (
                    <span className="text-accent ml-1">· Quote</span>
                  )}
                  {step.price != null && step.completionDays != null && (
                    <span> · ${step.price}, {step.completionDays} day{step.completionDays !== 1 ? 's' : ''}</span>
                  )}
                </span>
                {step.message && (
                  <p className="text-muted-foreground mt-0.5 text-xs leading-relaxed">{step.message}</p>
                )}
                {(step.paymentSchedule != null || step.licensed != null || step.referencesAvailable != null) && (
                  <ul className="text-muted-foreground mt-1.5 text-xs leading-relaxed space-y-0.5 list-none pl-0">
                    {step.paymentSchedule != null && (
                      <li><span className="text-foreground/80">Payment:</span> {step.paymentSchedule}</li>
                    )}
                    {step.licensed != null && (
                      <li><span className="text-foreground/80">Licensed:</span> {step.licensed ? 'Yes' : 'No'}</li>
                    )}
                    {step.referencesAvailable != null && (
                      <li><span className="text-foreground/80">References:</span> {step.referencesAvailable ? 'Yes' : 'No'}</li>
                    )}
                  </ul>
                )}
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}

export default function JobChatPage() {
  const router = useRouter();
  const params = useParams();
  const jobId = params?.jobId ? String(params.jobId) : null;
  const messagesEndRef = useRef<HTMLDivElement>(null);
  const inputRef = useRef<HTMLInputElement>(null);

  const [job, setJob] = useState<Job | null>(null);
  const [jobNotFound, setJobNotFound] = useState(false);
  const [loading, setLoading] = useState(true);
  const [messages, setMessages] = useState<ChatMessage[]>([]);
  const [inputValue, setInputValue] = useState('');
  const [sending, setSending] = useState(false);

  const { session } = useAuth();
  const user = session.user;
  const token = getAccessToken();

  // Resolve job: sessionStorage first (from create), then listJobs
  useEffect(() => {
    if (!jobId || !user || user.role !== 'buyer') return;

    const resolveJob = async () => {
      setLoading(true);
      setJobNotFound(false);

      try {
        const stored = typeof window !== 'undefined' ? sessionStorage.getItem(`buyer_job_${jobId}`) : null;
        if (stored) {
          try {
            const parsed = JSON.parse(stored) as Job;
            if (parsed && parsed.id) {
              setJob(parsed);
              sessionStorage.removeItem(`buyer_job_${jobId}`);
              setLoading(false);
              return;
            }
          } catch {
            // invalid json, fall through
          }
        }

        const jobs = await listJobs(Number(user.id), token!);
        const normalized = normalizeJobId(jobId);
        const found = jobs.find((j) => normalizeJobId(j.id) === normalized || j.id === jobId);
        if (found) {
          setJob(found);
        } else {
          setJobNotFound(true);
        }
      } catch {
        setJobNotFound(true);
      } finally {
        setLoading(false);
      }
    };

    if (token) {
      resolveJob();
    } else {
      setLoading(false);
      setJobNotFound(true);
    }
  }, [jobId, user, token]);

  // Auth redirect
  useEffect(() => {
    if (!session.isLoading && (!user || user.role !== 'buyer')) {
      router.push('/auth');
    }
  }, [session.isLoading, user, router]);

  // Run negotiation + match with live streaming when job is resolved
  useEffect(() => {
    if (!job || !user || !token || messages.length > 0) return;

    const runMatch = async () => {
      setMessages([
        {
          type: 'match',
          phase: 'evaluating',
          deals: undefined,
          error: undefined,
          negotiationProviders: [],
        },
      ]);

      const providersById = new Map<string, NegotiationProviderLog>();

      function updateMatchMessage(updates: Partial<Extract<ChatMessage, { type: 'match' }>>) {
        setMessages((prev) =>
          prev.map((m, i) => (i === 0 && m.type === 'match' ? { ...m, ...updates } : m))
        );
      }

      try {
        await matchJobToProvidersWithNegotiationStream(
          job,
          Number(user.id),
          token,
          {
            onEvent: (event) => {
              if (event.type === 'providers_fetched') {
                updateMatchMessage({ providersCount: event.count });
              } else if (event.type === 'provider_start') {
                providersById.set(event.providerId, {
                  providerId: event.providerId,
                  providerName: event.providerName,
                  steps: [],
                });
                updateMatchMessage({ negotiationProviders: Array.from(providersById.values()) });
              } else if (event.type === 'negotiation_step') {
                const log = providersById.get(event.providerId);
                if (log) {
                  const updated = { ...log, steps: [...log.steps, event.step] };
                  providersById.set(event.providerId, updated);
                  updateMatchMessage({ negotiationProviders: Array.from(providersById.values()) });
                }
              } else if (event.type === 'provider_done') {
                const log = providersById.get(event.providerId);
                if (log) {
                  const updated = { ...log, outcome: event.outcome };
                  providersById.set(event.providerId, updated);
                  updateMatchMessage({ negotiationProviders: Array.from(providersById.values()) });
                }
              } else if (event.type === 'done') {
                if (event.error) {
                  updateMatchMessage({ phase: 'error', error: event.error, deals: event.deals ?? [] });
                } else {
                  updateMatchMessage({
                    phase: 'complete',
                    deals: event.deals ?? [],
                    negotiationProviders: Array.from(providersById.values()),
                  });
                }
              }
            },
          },
          {}
        );

        setMessages((prev) =>
          prev.map((m, i) => {
            if (i === 0 && m.type === 'match' && m.phase !== 'complete' && m.phase !== 'error') {
              return { ...m, phase: 'complete' as const, deals: m.deals ?? [], negotiationProviders: Array.from(providersById.values()) };
            }
            return m;
          })
        );

        const numJobId = job.id.startsWith('job_') ? parseInt(job.id.replace('job_', ''), 10) : parseInt(job.id, 10);
        if (!isNaN(numJobId)) {
          updateJobStatus(Number(user.id), token, numJobId, 'matched').catch(() => {});
        }
      } catch (err) {
        setMessages((prev) =>
          prev.map((m, i) =>
            i === 0 && m.type === 'match'
              ? { ...m, phase: 'error' as const, error: err instanceof Error ? err.message : 'Matching failed. Please try again.' }
              : m
          )
        );
      }
    };

    runMatch();
  }, [job?.id, user?.id, token]);

  const scrollToBottom = () => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  };

  useEffect(() => {
    scrollToBottom();
  }, [messages]);

  const handleSend = async (e: React.FormEvent) => {
    e.preventDefault();
    const text = inputValue.trim();
    if (!text || !job || !user || !token || sending) return;

    setInputValue('');
    setMessages((prev) => [...prev, { type: 'user', content: text }]);
    setSending(true);

    const conversationHistory = messages
      .filter((m): m is { type: 'user'; content: string } | { type: 'assistant'; content: string } =>
        m.type === 'user' || m.type === 'assistant'
      )
      .map((m) => ({ role: m.type as 'user' | 'assistant', content: m.content }));

    try {
      const reply = await sendBuyerChatMessage(Number(user.id), token, text, {
        jobId: job.id,
        jobTitle: job.title,
        conversationHistory,
      });
      setMessages((prev) => [...prev, { type: 'assistant', content: reply }]);
    } catch (err) {
      setMessages((prev) => [
        ...prev,
        {
          type: 'assistant',
          content: err instanceof Error ? err.message : 'Failed to get a response. Please try again.',
        },
      ]);
    } finally {
      setSending(false);
      inputRef.current?.focus();
    }
  };

  if (!user || user.role !== 'buyer') {
    return null;
  }

  if (loading) {
    return (
      <div className="min-h-screen bg-background flex items-center justify-center">
        <div className="text-center">
          <p className="text-muted-foreground">Loading job...</p>
        </div>
      </div>
    );
  }

  if (jobNotFound || !job) {
    return (
      <div className="min-h-screen bg-background flex items-center justify-center">
        <div className="bg-card border border-border rounded-lg p-8 max-w-md w-full mx-4 text-center">
          <p className="text-foreground font-medium mb-2">Job not found</p>
          <p className="text-muted-foreground text-sm mb-6">
            The job may have been deleted or you don&apos;t have access to it.
          </p>
          <Link href="/buyer/dashboard">
            <Button className="bg-primary hover:bg-primary/90 text-primary-foreground">
              Back to Dashboard
            </Button>
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-background flex flex-col">
      {/* Header */}
      <header className="sticky top-0 bg-card border-b border-border z-40">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 py-4 flex items-center gap-4">
          <Link
            href="/buyer/dashboard"
            className="text-muted-foreground hover:text-foreground transition p-1 rounded-lg hover:bg-secondary"
            aria-label="Back to dashboard"
          >
            <ArrowLeft size={24} />
          </Link>
          <div className="flex-1 min-w-0">
            <h1 className="text-lg font-semibold text-foreground truncate">{job.title}</h1>
            <p className="text-sm text-muted-foreground truncate">Job Chat</p>
          </div>
        </div>
      </header>

      {/* Messages */}
      <main className="flex-1 overflow-y-auto max-w-4xl w-full mx-auto px-4 sm:px-6 py-6 space-y-4">
        {messages.map((msg, idx) => {
          if (msg.type === 'match') {
            const isRunning = msg.phase !== 'complete' && msg.phase !== 'error';
            const providers = msg.negotiationProviders ?? [];
            return (
              <div
                key={idx}
                className="bg-card border border-border rounded-lg p-4 flex flex-col gap-3"
                role="article"
                aria-label="Agent match result"
              >
                <div className="flex items-center gap-3">
                  <div
                    className={`p-2 rounded-lg ${
                      msg.phase === 'complete' ? 'bg-accent' : msg.phase === 'error' ? 'bg-destructive/20' : 'bg-primary'
                    }`}
                  >
                    <Zap
                      size={20}
                      className={
                        msg.phase === 'complete'
                          ? 'text-accent-foreground'
                          : msg.phase === 'error'
                            ? 'text-destructive'
                            : 'text-primary-foreground'
                      }
                    />
                  </div>
                  <div>
                    {isRunning && (
                      <p className="font-medium text-foreground">
                        Negotiating with providers{msg.providersCount != null ? ` (${msg.providersCount} found)` : ''}…
                      </p>
                    )}
                    {msg.phase === 'complete' && (
                      <p className="font-medium text-foreground">Negotiation complete</p>
                    )}
                    {msg.phase === 'error' && (
                      <p className="font-medium text-destructive">Matching failed</p>
                    )}
                  </div>
                </div>
                {(msg.phase === 'error' && msg.error) && (
                  <p className="text-sm text-muted-foreground">You can still ask follow-up questions about this job.</p>
                )}
                {providers.length > 0 && (() => {
                  // Rank: by negotiated price asc, then completion days asc; no outcome last
                  const ranked = [...providers].sort((a, b) => {
                    const oa = a.outcome;
                    const ob = b.outcome;
                    if (!oa && !ob) return 0;
                    if (!oa) return 1;
                    if (!ob) return -1;
                    if (oa.negotiatedPrice !== ob.negotiatedPrice) return oa.negotiatedPrice - ob.negotiatedPrice;
                    return oa.negotiatedCompletionDays - ob.negotiatedCompletionDays;
                  });
                  const dealsById = new Map((msg.deals ?? []).map((d) => [d.sellerId, d]));
                  return (
                    <div className="mt-2 space-y-3">
                      <p className="text-sm font-semibold text-foreground">Live negotiation by provider</p>
                      <div className="space-y-2 max-h-[320px] overflow-y-auto pr-1">
                        {ranked.map((prov, i) => (
                          <NegotiationProviderCard
                            key={prov.providerId}
                            provider={prov}
                            rank={i + 1}
                            score={msg.phase === 'complete' ? dealsById.get(prov.providerId)?.matchScore : undefined}
                          />
                        ))}
                      </div>
                      {msg.phase === 'complete' && (
                        <div className="space-y-3 pt-2 border-t border-border/50">
                          <p className="text-sm font-semibold text-foreground">Provider profile</p>
                          <div className="space-y-2">
                            {ranked.map((prov, i) => {
                              const deal = dealsById.get(prov.providerId);
                              const agent = deal?.sellerAgent;
                              const name = agent?.name ?? deal?.sellerName ?? prov.providerName;
                              const rankNum = i + 1;
                              const scoreVal = deal?.matchScore;
                              return (
                                <div
                                  key={prov.providerId}
                                  className="bg-secondary/30 rounded-lg border border-border/50 p-3 space-y-1.5"
                                >
                                  <div className="flex items-center justify-between gap-2">
                                    <p className="font-medium text-foreground">
                                      <span className="text-primary font-semibold">{rankNum}.</span> {name}
                                    </p>
                                    {scoreVal != null && (
                                      <span className="text-sm font-semibold text-accent shrink-0">{scoreVal}% match</span>
                                    )}
                                  </div>
                                  {agent != null ? (
                                    <p className="text-xs text-muted-foreground flex items-center gap-1">
                                      <Star size={12} className="fill-muted-foreground text-muted-foreground" />
                                      {agent.rating} · {agent.jobsCompleted} jobs
                                      {agent.licensed != null && (agent.licensed ? ' · Licensed' : ' · Not licensed')}
                                      {agent.references != null && (agent.references ? ' · References' : '')}
                                    </p>
                                  ) : deal?.quote != null ? (
                                    <p className="text-xs text-muted-foreground">
                                      {deal.quote.price != null && `Quote: $${deal.quote.price}`}
                                      {deal.quote.completionDays != null && ` · ${deal.quote.completionDays}d`}
                                    </p>
                                  ) : null}
                                  {prov.outcome && (
                                    <p className="text-xs text-accent">
                                      ${prov.outcome.negotiatedPrice} · {prov.outcome.negotiatedCompletionDays}d
                                    </p>
                                  )}
                                </div>
                              );
                            })}
                          </div>
                        </div>
                      )}
                    </div>
                  );
                })()}
                {msg.phase === 'complete' && (
                  <p className="text-sm text-muted-foreground">You can ask follow-up questions about this job or the matched providers below.</p>
                )}
                {msg.phase === 'complete' && msg.deals && msg.deals.length > 0 && (
                  <div className="mt-2 space-y-2">
                    <p className="text-sm font-semibold text-foreground">Top matches</p>
                    <div className="space-y-2">
                      {msg.deals.map((match, i) => (
                        <div
                          key={match.id}
                          className="flex items-center justify-between bg-secondary/50 rounded-lg p-3"
                        >
                          <div className="flex items-center gap-2">
                            <span className="text-sm font-semibold text-primary">{i + 1}.</span>
                            <div>
                              <p className="text-sm font-medium text-foreground">{match.sellerAgent?.name ?? match.sellerName ?? 'Provider'}</p>
                              {match.sellerAgent != null ? (
                                <p className="text-xs text-muted-foreground flex items-center gap-1">
                                  <Star size={12} className="fill-muted-foreground text-muted-foreground" />
                                  {match.sellerAgent.rating} • {match.sellerAgent.jobsCompleted} jobs
                                </p>
                              ) : match.quote?.price != null ? (
                                <p className="text-xs text-muted-foreground">Quote: ${match.quote.price}</p>
                              ) : null}
                            </div>
                          </div>
                          <div className="text-right">
                            <p className="font-semibold text-lg text-accent">{match.matchScore}%</p>
                            <p className="text-xs text-muted-foreground">match</p>
                            {(match.negotiatedPrice != null || match.negotiatedCompletionDays != null) && (
                              <p className="text-xs text-foreground mt-1">
                                {match.negotiatedPrice != null && `$${match.negotiatedPrice}`}
                                {match.negotiatedPrice != null && match.negotiatedCompletionDays != null && ' · '}
                                {match.negotiatedCompletionDays != null && `${match.negotiatedCompletionDays}d`}
                              </p>
                            )}
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>
                )}
                {msg.phase === 'complete' && msg.deals?.length === 0 && !providers.length && (
                  <p className="text-sm text-muted-foreground">No providers found for this job.</p>
                )}
              </div>
            );
          }
          if (msg.type === 'user') {
            return (
              <div key={idx} className="flex justify-end" role="article" aria-label="Your message">
                <div className="bg-primary text-primary-foreground rounded-lg px-4 py-2 max-w-[85%]">
                  <p className="text-sm whitespace-pre-wrap break-words">{msg.content}</p>
                </div>
              </div>
            );
          }
          if (msg.type === 'assistant') {
            return (
              <div key={idx} className="flex justify-start" role="article" aria-label="Assistant reply">
                <div className="bg-card border border-border rounded-lg px-4 py-2 max-w-[85%]">
                  <p className="text-sm text-foreground whitespace-pre-wrap break-words">{msg.content}</p>
                </div>
              </div>
            );
          }
          return null;
        })}
        {sending && (
          <div className="flex justify-start" role="status" aria-live="polite" aria-label="Agent is thinking">
            <div className="bg-card border border-border rounded-lg px-4 py-3 max-w-[85%] flex items-center gap-2">
              <Loader2 size={18} className="animate-spin text-primary shrink-0" />
              <p className="text-sm text-muted-foreground">Thinking...</p>
            </div>
          </div>
        )}
        <div ref={messagesEndRef} />
      </main>

      {/* Suggested follow-up prompts */}
      <div className="sticky bottom-0 bg-background border-t border-border py-4">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 space-y-3">
          <p className="text-xs text-muted-foreground">Suggested questions:</p>
          <div className="flex flex-wrap gap-2">
            {[
              'What Quote do we have?',
              'Tell me about the top provider',
              'What is the payment schedule?',
              'Who is licensed and has references?',
              'What is the timeline for completion?',
            ].map((label) => (
              <Button
                key={label}
                type="button"
                variant="outline"
                size="sm"
                className="text-xs bg-card border-border"
                disabled={sending}
                onClick={() => {
                  setInputValue(label);
                  inputRef.current?.focus();
                }}
              >
                {label}
              </Button>
            ))}
          </div>
          <form onSubmit={handleSend} className="flex gap-2">
            <Input
              ref={inputRef}
              type="text"
              placeholder="Ask a follow-up question about this job, providers, or payments..."
              value={inputValue}
              onChange={(e) => setInputValue(e.target.value)}
              disabled={sending}
              className="flex-1 bg-card border-border"
              aria-label="Message input"
              autoComplete="off"
            />
            <Button
              type="submit"
              disabled={sending || !inputValue.trim()}
              className="bg-primary hover:bg-primary/90 text-primary-foreground shrink-0"
              aria-label="Send message"
            >
              <Send size={20} />
            </Button>
          </form>
        </div>
      </div>
    </div>
  );
}
