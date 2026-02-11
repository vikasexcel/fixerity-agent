'use client';

import { useState, useEffect, useRef } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import { ArrowLeft, Send, Loader2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useAuth, getAccessToken } from '@/lib/auth-context';
import { unifiedAgentChatStream } from '@/lib/agent-api';

type Message = { role: 'seller' | 'buyer'; content: string };

export default function SellerMessagesPage() {
  const router = useRouter();
  const messagesEndRef = useRef<HTMLDivElement>(null);
  const sessionIdRef = useRef<string | null>(null);
  const [messages, setMessages] = useState<Message[]>([]);
  const [inputValue, setInputValue] = useState('');
  const [sending, setSending] = useState(false);

  const { session } = useAuth();
  const user = session.user;
  const token = getAccessToken();

  useEffect(() => {
    if (!session.isLoading && (!user || user.role !== 'seller')) {
      router.push('/auth');
    }
  }, [session.isLoading, user, router]);

  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [messages]);

  const handleSend = async (e: React.FormEvent) => {
    e.preventDefault();
    const text = inputValue.trim();
    if (!text || !user || !token || sending) return;

    setInputValue('');
    setMessages((prev) => [...prev, { role: 'seller', content: text }]);
    setSending(true);

    try {
      const { sessionId } = await unifiedAgentChatStream(
        'seller',
        String(user.id),
        token,
        text,
        {
          sessionId: sessionIdRef.current,
          onEvent: (event) => {
            if (event.type === 'session' && event.sessionId) {
              sessionIdRef.current = event.sessionId;
            }
            if (event.type === 'message') {
              setMessages((prev) => [...prev, { role: 'buyer', content: event.text }]);
            }
            if (event.type === 'error') {
              setMessages((prev) => [...prev, { role: 'buyer', content: `Error: ${event.error}` }]);
            }
          },
        }
      );
      if (sessionId) sessionIdRef.current = sessionId;
    } catch (err) {
      setMessages((prev) => [
        ...prev,
        { role: 'buyer', content: err instanceof Error ? err.message : 'Something went wrong.' },
      ]);
    } finally {
      setSending(false);
    }
  };

  if (!user || user.role !== 'seller') {
    return null;
  }

  return (
    <div className="min-h-screen bg-background flex flex-col">
      <header className="sticky top-0 bg-card border-b border-border z-40">
        <div className="max-w-2xl mx-auto px-4 sm:px-6 py-4 flex items-center gap-4">
          <Link
            href="/seller/dashboard"
            className="text-muted-foreground hover:text-foreground transition p-1 rounded-lg hover:bg-secondary"
            aria-label="Back to dashboard"
          >
            <ArrowLeft size={24} />
          </Link>
          <h1 className="text-lg font-semibold text-foreground">Chat with buyer</h1>
        </div>
      </header>

      <main className="flex-1 overflow-y-auto max-w-2xl w-full mx-auto px-4 sm:px-6 py-4 space-y-4">
        {messages.map((msg, idx) =>
          msg.role === 'seller' ? (
            <div key={idx} className="flex justify-end" role="article" aria-label="Your message">
              <div className="bg-primary text-primary-foreground rounded-lg px-4 py-2 max-w-[85%]">
                <p className="text-sm whitespace-pre-wrap break-words">{msg.content}</p>
              </div>
            </div>
          ) : (
            <div key={idx} className="flex justify-start" role="article" aria-label="Buyer message">
              <div className="bg-card border border-border rounded-lg px-4 py-2 max-w-[85%]">
                <p className="text-sm text-foreground whitespace-pre-wrap break-words">{msg.content}</p>
              </div>
            </div>
          )
        )}
        <div ref={messagesEndRef} />
      </main>

      <div className="sticky bottom-0 bg-background border-t border-border py-4">
        <div className="max-w-2xl mx-auto px-4 sm:px-6">
          <form onSubmit={handleSend} className="flex gap-2">
            <Input
              type="text"
              placeholder="Type a message..."
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
              {sending ? <Loader2 size={20} className="animate-spin" /> : <Send size={20} />}
            </Button>
          </form>
        </div>
      </div>
    </div>
  );
}
