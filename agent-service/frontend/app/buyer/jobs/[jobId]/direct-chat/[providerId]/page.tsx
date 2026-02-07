'use client';

import { useState, useEffect, useRef } from 'react';
import { useRouter, useParams, useSearchParams } from 'next/navigation';
import Link from 'next/link';
import { ArrowLeft, Send, Loader2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { sendBuyerDirectChatMessage } from '@/lib/agent-api';
import { useAuth, getAccessToken } from '@/lib/auth-context';
import { listJobs } from '@/lib/jobs-api';
import type { Job } from '@/lib/dummy-data';

function normalizeJobId(id: string): string {
  return String(id).trim();
}

export default function DirectChatPage() {
  const router = useRouter();
  const params = useParams();
  const searchParams = useSearchParams();
  const jobId = params?.jobId ? String(params.jobId) : null;
  const providerId = params?.providerId ? String(params.providerId) : null;
  const providerName = searchParams.get('name') ?? 'Provider';
  const priceParam = searchParams.get('price');
  const daysParam = searchParams.get('days');
  const ratingParam = searchParams.get('rating');
  const jobsParam = searchParams.get('jobs');
  const paymentSchedule = searchParams.get('payment') ?? undefined;

  const [job, setJob] = useState<Job | null>(null);
  const [jobNotFound, setJobNotFound] = useState(false);
  const [loading, setLoading] = useState(true);
  const [messages, setMessages] = useState<Array<{ role: 'user' | 'assistant'; content: string }>>([]);
  const [inputValue, setInputValue] = useState('');
  const [sending, setSending] = useState(false);
  const messagesEndRef = useRef<HTMLDivElement>(null);

  const { session } = useAuth();
  const user = session.user;
  const token = getAccessToken();

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
            if (parsed?.id) {
              setJob(parsed);
              setLoading(false);
              return;
            }
          } catch {
            // fall through
          }
        }
        const jobs = await listJobs(Number(user.id), token!);
        const normalized = normalizeJobId(jobId);
        const found = jobs.find((j) => normalizeJobId(j.id) === normalized || j.id === jobId);
        if (found) setJob(found);
        else setJobNotFound(true);
      } catch {
        setJobNotFound(true);
      } finally {
        setLoading(false);
      }
    };

    if (token) resolveJob();
    else setLoading(false);
  }, [jobId, user, token]);

  useEffect(() => {
    if (!session.isLoading && (!user || user.role !== 'buyer')) {
      router.push('/auth');
    }
  }, [session.isLoading, user, router]);

  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [messages]);

  const handleSend = async (e: React.FormEvent) => {
    e.preventDefault();
    const text = inputValue.trim();
    if (!text || !job || !user || !token || sending || !providerId) return;

    setInputValue('');
    setMessages((prev) => [...prev, { role: 'user', content: text }]);
    setSending(true);

    try {
      const reply = await sendBuyerDirectChatMessage(Number(user.id), token, text, {
        jobId: job.id,
        jobTitle: job.title,
        providerId: Number(providerId),
        providerName,
        price: priceParam != null ? Number(priceParam) : undefined,
        days: daysParam != null ? Number(daysParam) : undefined,
        paymentSchedule,
        rating: ratingParam != null ? Number(ratingParam) : undefined,
        jobsCompleted: jobsParam != null ? Number(jobsParam) : undefined,
        conversationHistory: messages,
      });
      setMessages((prev) => [...prev, { role: 'assistant', content: reply }]);
    } catch (err) {
      const errMsg = err instanceof Error ? err.message : 'Failed to send. Please try again.';
      setMessages((prev) => [...prev, { role: 'assistant', content: `Error: ${errMsg}` }]);
    } finally {
      setSending(false);
    }
  };

  if (!user || user.role !== 'buyer') return null;

  if (loading) {
    return (
      <div className="min-h-screen bg-background flex items-center justify-center">
        <div className="text-center">
          <Loader2 className="animate-spin mx-auto mb-2" size={32} />
          <p className="text-muted-foreground">Loading...</p>
        </div>
      </div>
    );
  }

  if (jobNotFound || !job) {
    return (
      <div className="min-h-screen bg-background flex items-center justify-center">
        <div className="bg-card border border-border rounded-lg p-8 max-w-md w-full mx-4 text-center">
          <p className="text-foreground font-medium mb-2">Job not found</p>
          <Link href="/buyer/dashboard">
            <Button className="bg-primary hover:bg-primary/90 text-primary-foreground">Back to Dashboard</Button>
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-background flex flex-col">
      <header className="sticky top-0 bg-card border-b border-border z-40">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 py-4 flex items-center gap-4">
          <Link
            href={`/buyer/jobs/${encodeURIComponent(jobId!)}/chat`}
            className="text-muted-foreground hover:text-foreground transition p-1 rounded-lg hover:bg-secondary"
            aria-label="Back to job chat"
          >
            <ArrowLeft size={24} />
          </Link>
          <div className="flex-1 min-w-0">
            <h1 className="text-lg font-semibold text-foreground truncate">Direct Chat with Provider {providerName}</h1>
            <p className="text-sm text-muted-foreground truncate">{job.title}</p>
          </div>
        </div>
      </header>

      <main className="flex-1 overflow-y-auto max-w-4xl w-full mx-auto px-4 sm:px-6 py-4 space-y-3">
        {messages.length === 0 && (
          <p className="text-sm text-muted-foreground">Start the conversation. Ask about their quote, availability, or next steps.</p>
        )}
        {messages.map((m, i) =>
          m.role === 'user' ? (
            <div key={i} className="flex justify-end">
              <div className="bg-primary text-primary-foreground rounded-lg px-4 py-2 max-w-[85%]">
                <p className="text-sm whitespace-pre-wrap break-words">You: {m.content}</p>
              </div>
            </div>
          ) : (
            <div key={i} className="flex justify-start">
              <div className="bg-card border border-border rounded-lg px-4 py-2 max-w-[85%]">
                <p className="text-sm font-medium text-foreground mb-0.5">{providerName}:</p>
                <p className="text-sm text-muted-foreground whitespace-pre-wrap break-words">{m.content}</p>
              </div>
            </div>
          )
        )}
        {sending && (
          <div className="flex justify-start">
            <div className="bg-secondary/50 rounded-lg px-4 py-2 flex items-center gap-2">
              <Loader2 size={18} className="animate-spin text-primary" />
              <span className="text-sm text-muted-foreground">{providerName} is typing...</span>
            </div>
          </div>
        )}
        <div ref={messagesEndRef} />
      </main>

      <div className="sticky bottom-0 bg-background border-t border-border py-4">
        <div className="max-w-4xl mx-auto px-4 sm:px-6">
          <form onSubmit={handleSend} className="flex gap-2">
            <Input
              type="text"
              placeholder="Type your message..."
              value={inputValue}
              onChange={(e) => setInputValue(e.target.value)}
              disabled={sending}
              className="flex-1 bg-card border-border"
            />
            <Button type="submit" disabled={sending || !inputValue.trim()} className="shrink-0">
              <Send size={20} />
            </Button>
          </form>
        </div>
      </div>
    </div>
  );
}
