'use client';

import { useAuth } from '@/lib/auth-context';
import { useRouter } from 'next/navigation';
import { useEffect } from 'react';

export default function BuyerAgentLayout({
  children,
}: { children: React.ReactNode }) {
  const { session } = useAuth();
  const router = useRouter();

  useEffect(() => {
    if (session.isLoading) return;
    if (!session.user) {
      router.replace('/auth');
      return;
    }
    if (session.user.role !== 'buyer') {
      router.replace('/auth');
    }
  }, [session.isLoading, session.user, router]);

  if (session.isLoading) {
    return (
      <div className="min-h-screen bg-background flex items-center justify-center">
        <p className="text-muted-foreground">Loading...</p>
      </div>
    );
  }

  if (!session.user || session.user.role !== 'buyer') {
    return null;
  }

  return <>{children}</>;
}
