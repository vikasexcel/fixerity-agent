'use client';

import { useState, useEffect, useRef } from 'react';
import { useRouter, useParams } from 'next/navigation';
import Link from 'next/link';
import { ArrowLeft, Zap, Star, Send, Loader2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { matchJobToProviders, sendBuyerChatMessage } from '@/lib/agent-api';
import { getAuthSession, getAccessToken } from '@/lib/auth-context';
import { listJobs, updateJobStatus } from '@/lib/jobs-api';
import type { Job, Deal } from '@/lib/dummy-data';

type MatchPhase = 'initializing' | 'scanning' | 'evaluating' | 'ranking' | 'complete' | 'error';

type ChatMessage =
  | { type: 'match'; phase: MatchPhase; deals?: Deal[]; error?: string }
  | { type: 'user'; content: string }
  | { type: 'assistant'; content: string };

function normalizeJobId(id: string): string {
  return String(id).trim();
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

  const user = getAuthSession().user;
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
    if (!user || user.role !== 'buyer') {
      router.push('/auth');
    }
  }, [user, router]);

  // Run match when job is resolved
  useEffect(() => {
    if (!job || !user || !token || messages.length > 0) return;

    const runMatch = async () => {
      setMessages([
        {
          type: 'match',
          phase: 'scanning',
          deals: undefined,
          error: undefined,
        },
      ]);

      try {
        const deals = await matchJobToProviders(job, Number(user.id), token);
        setMessages([
          {
            type: 'match',
            phase: 'complete',
            deals,
            error: undefined,
          },
        ]);

        const numJobId = job.id.startsWith('job_') ? parseInt(job.id.replace('job_', ''), 10) : parseInt(job.id, 10);
        if (!isNaN(numJobId)) {
          updateJobStatus(Number(user.id), token, numJobId, 'matched').catch(() => {});
        }
      } catch (err) {
        setMessages([
          {
            type: 'match',
            phase: 'error',
            deals: undefined,
            error: err instanceof Error ? err.message : 'Matching failed. Please try again.',
          },
        ]);
      }
    };

    runMatch();
  }, [job?.id]);

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
                      <p className="font-medium text-foreground">Matching your job to providers...</p>
                    )}
                    {msg.phase === 'complete' && (
                      <p className="font-medium text-foreground">Match complete</p>
                    )}
                    {msg.phase === 'error' && (
                      <p className="font-medium text-destructive">Matching failed</p>
                    )}
                  </div>
                </div>
                {msg.phase === 'error' && msg.error && (
                  <p className="text-sm text-muted-foreground">You can still ask follow-up questions about this job.</p>
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
                              <p className="text-sm font-medium text-foreground">{match.sellerAgent.name}</p>
                              <p className="text-xs text-muted-foreground flex items-center gap-1">
                                <Star size={12} className="fill-muted-foreground text-muted-foreground" />
                                {match.sellerAgent.rating} • {match.sellerAgent.jobsCompleted} jobs
                              </p>
                            </div>
                          </div>
                          <div className="text-right">
                            <p className="font-semibold text-lg text-accent">{match.matchScore}%</p>
                            <p className="text-xs text-muted-foreground">match</p>
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>
                )}
                {msg.phase === 'complete' && msg.deals?.length === 0 && (
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

      {/* Input */}
      <div className="sticky bottom-0 bg-background border-t border-border py-4">
        <div className="max-w-4xl mx-auto px-4 sm:px-6">
          <form onSubmit={handleSend} className="flex gap-2">
            <Input
              ref={inputRef}
              type="text"
              placeholder="Ask a follow-up question about this job..."
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
