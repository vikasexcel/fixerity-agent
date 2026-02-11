'use client';

import { useState, useEffect, useRef } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import { ArrowLeft, Send } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useAuth } from '@/lib/auth-context';

type Message = { role: 'seller' | 'buyer'; content: string };

const MOCK_MESSAGES: Message[] = [
  { role: 'buyer', content: 'Hi, I need help with a plumbing job next week.' },
  { role: 'seller', content: 'Sure, I can help. What day works for you?' },
];

export default function SellerMessagesPage() {
  const router = useRouter();
  const messagesEndRef = useRef<HTMLDivElement>(null);
  const [messages, setMessages] = useState<Message[]>(MOCK_MESSAGES);
  const [inputValue, setInputValue] = useState('');

  const { session } = useAuth();
  const user = session.user;

  useEffect(() => {
    if (!session.isLoading && (!user || user.role !== 'seller')) {
      router.push('/auth');
    }
  }, [session.isLoading, user, router]);

  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [messages]);

  const handleSend = (e: React.FormEvent) => {
    e.preventDefault();
    const text = inputValue.trim();
    if (!text) return;

    setInputValue('');
    setMessages((prev) => [...prev, { role: 'seller', content: text }]);
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
            />
            <Button
              type="submit"
              disabled={!inputValue.trim()}
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
