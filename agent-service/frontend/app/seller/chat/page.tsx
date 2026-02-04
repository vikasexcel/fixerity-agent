'use client';

import { useState, useEffect, useRef } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import { ArrowLeft, Zap, Star, Send, Loader2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { matchSellerToJobs, sendSellerChatMessage } from '@/lib/agent-api';
import { getAuthSession, getAccessToken } from '@/lib/auth-context';
import type { Deal } from '@/lib/dummy-data';

type MatchPhase = 'initializing' | 'scanning' | 'evaluating' | 'ranking' | 'complete' | 'error';

type ChatMessage =
  | { type: 'match'; phase: MatchPhase; deals?: Deal[]; error?: string }
  | { type: 'user'; content: string }
  | { type: 'assistant'; content: string };

export default function SellerChatPage() {
  const router = useRouter();
  const messagesEndRef = useRef<HTMLDivElement>(null);
  const inputRef = useRef<HTMLInputElement>(null);

  const [loading, setLoading] = useState(true);
  const [messages, setMessages] = useState<ChatMessage[]>([]);
  const [inputValue, setInputValue] = useState('');
  const [sending, setSending] = useState(false);
  const [deals, setDeals] = useState<Deal[]>([]);

  const user = getAuthSession().user;
  const token = getAccessToken();

  // Auth redirect
  useEffect(() => {
    if (!user || user.role !== 'seller') {
      router.push('/auth');
    }
  }, [user, router]);

  // Run match when page loads
  useEffect(() => {
    if (!user || !token || user.role !== 'seller' || messages.length > 0) return;

    const runMatch = async () => {
      setLoading(true);
      setMessages([
        {
          type: 'match',
          phase: 'scanning',
          deals: undefined,
          error: undefined,
        },
      ]);

      try {
        const matchedDeals = await matchSellerToJobs(Number(user.id), token);
        setDeals(matchedDeals);
        setMessages([
          {
            type: 'match',
            phase: 'complete',
            deals: matchedDeals,
            error: undefined,
          },
        ]);
      } catch (err) {
        setMessages([
          {
            type: 'match',
            phase: 'error',
            deals: undefined,
            error: err instanceof Error ? err.message : 'Matching failed. Please try again.',
          },
        ]);
      } finally {
        setLoading(false);
      }
    };

    runMatch();
  }, [user?.id, token]);

  const scrollToBottom = () => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  };

  useEffect(() => {
    scrollToBottom();
  }, [messages]);

  const handleSend = async (e: React.FormEvent) => {
    e.preventDefault();
    const text = inputValue.trim();
    if (!text || !user || !token || sending) return;

    setInputValue('');
    setMessages((prev) => [...prev, { type: 'user', content: text }]);
    setSending(true);

    const conversationHistory = messages
      .filter((m): m is { type: 'user'; content: string } | { type: 'assistant'; content: string } =>
        m.type === 'user' || m.type === 'assistant'
      )
      .map((m) => ({ role: m.type as 'user' | 'assistant', content: m.content }));

    try {
      const reply = await sendSellerChatMessage(Number(user.id), token, text, {
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

  if (!user || user.role !== 'seller') {
    return null;
  }

  if (loading && messages.length === 0) {
    return (
      <div className="min-h-screen bg-background flex items-center justify-center">
        <div className="text-center">
          <Loader2 size={32} className="animate-spin text-primary mx-auto mb-4" />
          <p className="text-muted-foreground">Scanning for matching jobs...</p>
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
            href="/seller/dashboard"
            className="text-muted-foreground hover:text-foreground transition p-1 rounded-lg hover:bg-secondary"
            aria-label="Back to dashboard"
          >
            <ArrowLeft size={24} />
          </Link>
          <div className="flex-1 min-w-0">
            <h1 className="text-lg font-semibold text-foreground truncate">Seller Agent Chat</h1>
            <p className="text-sm text-muted-foreground truncate">Find and discuss matching job opportunities</p>
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
                      <p className="font-medium text-foreground">Scanning available jobs...</p>
                    )}
                    {msg.phase === 'complete' && (
                      <p className="font-medium text-foreground">Scan complete</p>
                    )}
                    {msg.phase === 'error' && (
                      <p className="font-medium text-destructive">Scan failed</p>
                    )}
                  </div>
                </div>
                {(msg.phase === 'error' && msg.error) && (
                  <p className="text-sm text-muted-foreground">You can still ask follow-up questions about your profile or available jobs.</p>
                )}
                {msg.phase === 'complete' && (
                  <p className="text-sm text-muted-foreground">You can ask follow-up questions about these matches or your profile below.</p>
                )}
                {msg.phase === 'complete' && msg.deals && msg.deals.length > 0 && (
                  <div className="mt-2 space-y-2">
                    <p className="text-sm font-semibold text-foreground">Top matches</p>
                    <div className="space-y-2">
                      {msg.deals.map((deal, i) => (
                        <div
                          key={deal.id}
                          className="flex items-center justify-between bg-secondary/50 rounded-lg p-3"
                        >
                          <div className="flex items-center gap-2 flex-1 min-w-0">
                            <span className="text-sm font-semibold text-primary shrink-0">{i + 1}.</span>
                            <div className="min-w-0 flex-1">
                              <p className="text-sm font-medium text-foreground truncate">{deal.job?.title ?? 'Job'}</p>
                              <p className="text-xs text-muted-foreground">
                                Match: {deal.matchScore}% • {deal.job?.budget?.min ? `$${deal.job.budget.min}` : ''} - {deal.job?.budget?.max ? `$${deal.job.budget.max}` : ''}
                              </p>
                            </div>
                          </div>
                          <div className="text-right shrink-0">
                            <p className="font-semibold text-lg text-accent">{deal.matchScore}%</p>
                            <p className="text-xs text-muted-foreground">match</p>
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>
                )}
                {msg.phase === 'complete' && msg.deals?.length === 0 && (
                  <p className="text-sm text-muted-foreground">No matching jobs found at this time. Try again later or ask me about your profile.</p>
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
              placeholder="Ask about matching jobs, your profile, packages, or orders..."
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
