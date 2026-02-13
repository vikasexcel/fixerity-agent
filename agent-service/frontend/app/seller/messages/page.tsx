'use client';

import { useState, useEffect, useRef, useCallback } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import {
  ArrowLeft,
  Send,
  Loader2,
  MessageSquarePlus,
  MessageCircle,
  PanelLeftClose,
  PanelLeft,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useAuth, getAccessToken } from '@/lib/auth-context';
import { unifiedAgentChatStream } from '@/lib/agent-api';
import { cn } from '@/lib/utils';

type Message = { role: 'seller' | 'buyer'; content: string };

type Chat = {
  id: string;
  sessionId: string | null;
  title: string;
  messages: Message[];
  updatedAt: number;
};

const STORAGE_KEY_PREFIX = 'seller-chats-';
const MAX_TITLE_LENGTH = 32;

function getChatsKey(userId: string) {
  return `${STORAGE_KEY_PREFIX}${userId}`;
}

function loadChats(userId: string): Chat[] {
  if (typeof window === 'undefined') return [];
  try {
    const raw = localStorage.getItem(getChatsKey(userId));
    if (!raw) return [];
    const parsed = JSON.parse(raw) as Chat[];
    return Array.isArray(parsed) ? parsed : [];
  } catch {
    return [];
  }
}

function saveChats(userId: string, chats: Chat[]) {
  if (typeof window === 'undefined') return;
  try {
    localStorage.setItem(getChatsKey(userId), JSON.stringify(chats));
  } catch {
    // ignore
  }
}

function createNewChat(): Chat {
  return {
    id: `chat-${Date.now()}`,
    sessionId: null,
    title: 'New chat',
    messages: [],
    updatedAt: Date.now(),
  };
}

export default function SellerMessagesPage() {
  const router = useRouter();
  const messagesEndRef = useRef<HTMLDivElement>(null);
  const [chats, setChats] = useState<Chat[]>([]);
  const [activeChatId, setActiveChatId] = useState<string | null>(null);
  const [inputValue, setInputValue] = useState('');
  const [sending, setSending] = useState(false);
  const [sidebarOpen, setSidebarOpen] = useState(true);

  const { session } = useAuth();
  const user = session.user;
  const token = getAccessToken();

  const activeChat = activeChatId ? chats.find((c) => c.id === activeChatId) : null;
  const currentSessionId = activeChat?.sessionId ?? null;
  const displayMessages = activeChat?.messages ?? [];

  // Load chats from localStorage when user is available
  useEffect(() => {
    if (!user?.id || user.role !== 'seller') return;
    const stored = loadChats(String(user.id));
    if (stored.length === 0) {
      const newChat = createNewChat();
      setChats([newChat]);
      setActiveChatId(newChat.id);
      saveChats(String(user.id), [newChat]);
    } else {
      setChats(stored);
      setActiveChatId((prev) => (prev && stored.some((c) => c.id === prev) ? prev : stored[0].id));
    }
  }, [user?.id, user?.role]);

  // Persist chats whenever they change (for current user)
  useEffect(() => {
    if (!user?.id || user.role !== 'seller' || chats.length === 0) return;
    saveChats(String(user.id), chats);
  }, [chats, user?.id, user?.role]);

  useEffect(() => {
    if (!session.isLoading && (!user || user.role !== 'seller')) {
      router.push('/auth');
    }
  }, [session.isLoading, user, router]);

  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth', block: 'end' });
  }, [displayMessages]);

  const updateActiveChat = useCallback(
    (updates: Partial<Chat>) => {
      if (!activeChatId || !user?.id) return;
      setChats((prev) =>
        prev.map((c) =>
          c.id === activeChatId ? { ...c, ...updates, updatedAt: Date.now() } : c
        )
      );
    },
    [activeChatId, user?.id]
  );

  const handleNewChat = useCallback(() => {
    if (!user?.id) return;
    const newChat = createNewChat();
    setChats((prev) => [newChat, ...prev]);
    setActiveChatId(newChat.id);
    setSidebarOpen(true);
  }, [user?.id]);

  const handleSelectChat = useCallback((id: string) => {
    setActiveChatId(id);
    setSidebarOpen(false);
  }, []);

  const handleSend = async (e: React.FormEvent) => {
    e.preventDefault();
    const text = inputValue.trim();
    if (!text || !user || !token || sending || !activeChatId) return;

    setInputValue('');
    const userMsg: Message = { role: 'seller', content: text };
    setChats((prev) =>
      prev.map((c) =>
        c.id === activeChatId
          ? { ...c, messages: [...(c.messages ?? []), userMsg], updatedAt: Date.now() }
          : c
      )
    );
    setSending(true);

    const sessionIdToUse = currentSessionId;
    const isNewChat = activeChat?.sessionId == null;

    try {
      const { sessionId } = await unifiedAgentChatStream(
        'seller',
        String(user.id),
        token,
        text,
        {
          sessionId: sessionIdToUse,
          forceNewSession: isNewChat,
          onEvent: (event) => {
            if (event.type === 'session' && event.sessionId) {
              updateActiveChat({ sessionId: event.sessionId });
            }
            if (event.type === 'message' && event.text != null) {
              const assistantMsg: Message = { role: 'buyer', content: String(event.text) };
              setChats((prev) =>
                prev.map((c) =>
                  c.id === activeChatId
                    ? { ...c, messages: [...(c.messages ?? []), assistantMsg], updatedAt: Date.now() }
                    : c
                )
              );
            }
            if (event.type === 'error') {
              const errMsg: Message = { role: 'buyer', content: `Error: ${event.error}` };
              setChats((prev) =>
                prev.map((c) =>
                  c.id === activeChatId
                    ? { ...c, messages: [...(c.messages ?? []), errMsg], updatedAt: Date.now() }
                    : c
                )
              );
            }
          },
        }
      );

      const newSessionId = sessionId ?? sessionIdToUse;
      if (newSessionId) {
        updateActiveChat({ sessionId: newSessionId });
      }
      const shouldUpdateTitle = activeChat?.title === 'New chat' || !activeChat?.title;
      if (shouldUpdateTitle) {
        const newTitle =
          text.slice(0, MAX_TITLE_LENGTH) + (text.length > MAX_TITLE_LENGTH ? '…' : '');
        updateActiveChat({ title: newTitle });
      }
    } catch (err) {
      const errMsg: Message = {
        role: 'buyer',
        content: err instanceof Error ? err.message : 'Something went wrong.',
      };
      setChats((prev) =>
        prev.map((c) =>
          c.id === activeChatId
            ? { ...c, messages: [...(c.messages ?? []), errMsg], updatedAt: Date.now() }
            : c
        )
      );
    } finally {
      setSending(false);
    }
  };

  if (!user || user.role !== 'seller') {
    return null;
  }

  return (
    <div className="min-h-screen bg-background flex">
      {/* Backdrop when sidebar is open on mobile */}
      {sidebarOpen && (
        <button
          type="button"
          aria-label="Close sidebar"
          className="fixed inset-0 bg-black/40 z-20 md:hidden"
          onClick={() => setSidebarOpen(false)}
        />
      )}
      {/* Sidebar: overlay on small screens, inline on md+ */}
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
              href="/seller/dashboard"
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
          </div>
        </div>
      </aside>

      {/* Main: chat area */}
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
          <h1 className="text-lg font-semibold text-foreground truncate">
            {activeChat?.title ?? 'Chat with buyer'}
          </h1>
        </header>

        <main className="flex-1 overflow-y-auto overflow-x-hidden px-4 sm:px-6 py-4 max-w-2xl w-full mx-auto space-y-4">
          {displayMessages.map((msg, idx) =>
            msg.role === 'seller' ? (
              <div
                key={idx}
                className="flex justify-end"
                role="article"
                aria-label="Your message"
              >
                <div className="bg-primary text-primary-foreground rounded-lg px-4 py-2 max-w-[85%]">
                  <p className="text-sm whitespace-pre-wrap break-words">{msg.content}</p>
                </div>
              </div>
            ) : (
              <div
                key={idx}
                className="flex justify-start w-full"
                role="article"
                aria-label="Assistant message"
              >
                <div className="bg-card border border-border rounded-lg px-4 py-3 w-full max-w-full min-w-0">
                  <div className="text-sm text-foreground whitespace-pre-wrap break-words leading-relaxed overflow-visible">
                    {msg.content}
                  </div>
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
