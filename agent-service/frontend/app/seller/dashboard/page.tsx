'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import type { Deal, Agent } from '@/lib/dummy-data';
import { DealCard } from '@/components/seller/deal-card';
import { DealDetailModal } from '@/components/seller/deal-detail-modal';
import { Button } from '@/components/ui/button';
import { RoleAvatar } from '@/components/ui/role-avatar';
import { Star, Check, X } from 'lucide-react';
import { getAuthSession } from '@/lib/auth-context';

export default function SellerDashboard() {
  const router = useRouter();
  const [selectedDeal, setSelectedDeal] = useState<Deal | null>(null);
  const [filter, setFilter] = useState<'all' | 'high' | 'medium'>('all');
  const user = getAuthSession().user;

  useEffect(() => {
    if (!user || user.role !== 'seller') {
      router.push('/auth');
    }
  }, [user, router]);

  const [deals, setDeals] = useState<Deal[]>([]);

  const currentAgent: Agent | null = user
    ? {
        id: `agent_${user.id}`,
        userId: user.id,
        name: user.name ?? 'Seller',
        type: 'seller',
        rating: 4.5,
        jobsCompleted: 0,
        licensed: true,
        references: true,
        bio: '',
        createdAt: new Date().toISOString().split('T')[0],
      }
    : null;

  const filteredDeals =
    filter === 'all'
      ? deals
      : filter === 'high'
        ? deals.filter((d) => d.matchScore >= 85)
        : deals.filter((d) => d.matchScore >= 70 && d.matchScore < 85);

  return (
    <div className="min-h-screen bg-background">
      {/* Header */}
      <header className="sticky top-0 bg-card border-b border-border z-40">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6">
          <div className="flex justify-between items-center">
            <div>
              <h1 className="text-2xl sm:text-3xl font-bold text-foreground">Seller Agent Dashboard</h1>
              <p className="text-muted-foreground text-sm mt-1">
                Welcome, {currentAgent?.name} • {currentAgent?.jobsCompleted} projects completed
              </p>
            </div>
            <div className="flex items-center gap-3">
              <Button 
                onClick={() => router.push('/seller/chat')}
                className="bg-accent hover:bg-accent/90 text-accent-foreground"
              >
                Run Agent Scan
              </Button>
              <Button className="bg-primary hover:bg-primary/90 text-primary-foreground">
                Edit Profile
              </Button>
              <button
                onClick={() => {
                  localStorage.clear();
                  router.push('/auth');
                }}
                className="text-muted-foreground hover:text-foreground text-sm"
              >
                Sign Out
              </button>
            </div>
          </div>
        </div>
      </header>

      {/* Main Content */}
      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Profile Card */}
        <div className="bg-card border border-border rounded-lg p-6 mb-8">
          <div className="flex items-start gap-6">
            <RoleAvatar name={currentAgent?.name} type="seller" size="lg" />
            <div className="flex-1">
              <div className="flex items-center gap-4 mb-2">
                <h2 className="text-2xl font-bold text-foreground">{currentAgent?.name}</h2>
                <div className="flex items-center gap-4">
                  <div className="text-center">
                    <p className="text-2xl font-bold text-accent flex items-center justify-center gap-1">
                      <Star size={20} className="fill-accent text-accent" />
                      {currentAgent?.rating}
                    </p>
                    <p className="text-xs text-muted-foreground">Rating</p>
                  </div>
                  <div className="text-center">
                    <p className="text-2xl font-bold text-primary">{currentAgent?.jobsCompleted}</p>
                    <p className="text-xs text-muted-foreground">Jobs</p>
                  </div>
                  <div className="text-center">
                    <p className="text-lg font-bold text-foreground flex items-center justify-center">
                      {currentAgent?.licensed ? <Check size={20} /> : <X size={20} />}
                    </p>
                    <p className="text-xs text-muted-foreground">Licensed</p>
                  </div>
                </div>
              </div>
              <p className="text-muted-foreground mb-4">{currentAgent?.bio}</p>
              {currentAgent?.skills && (
                <div className="flex flex-wrap gap-2">
                  {currentAgent.skills.map((skill, idx) => (
                    <span key={idx} className="bg-secondary text-secondary-foreground text-xs px-3 py-1 rounded">
                      {skill}
                    </span>
                  ))}
                </div>
              )}
            </div>
          </div>
        </div>

        {/* Quick Stats */}
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
          <div className="bg-card rounded-lg border border-border p-5">
            <p className="text-muted-foreground text-sm mb-2">Available Deals</p>
            <p className="text-3xl font-bold text-foreground">{deals.length}</p>
          </div>
          <div className="bg-card rounded-lg border border-border p-5">
            <p className="text-muted-foreground text-sm mb-2">High Match (85%+)</p>
            <p className="text-3xl font-bold text-primary">{deals.filter((d) => d.matchScore >= 85).length}</p>
          </div>
          <div className="bg-card rounded-lg border border-border p-5">
            <p className="text-muted-foreground text-sm mb-2">Avg Match Score</p>
            <p className="text-3xl font-bold text-accent">
              {deals.length > 0 ? Math.round(deals.reduce((acc, d) => acc + d.matchScore, 0) / deals.length) : 0}%
            </p>
          </div>
          <div className="bg-card rounded-lg border border-border p-5">
            <p className="text-muted-foreground text-sm mb-2">Contacted</p>
            <p className="text-3xl font-bold text-foreground">
              {deals.filter((d) => d.status !== 'proposed').length}
            </p>
          </div>
        </div>

        {/* Filter Tabs */}
        <div className="flex gap-3 mb-6 overflow-x-auto pb-2">
          {(['all', 'high', 'medium'] as const).map((status) => (
            <button
              key={status}
              onClick={() => setFilter(status)}
              className={`px-4 py-2 rounded-lg font-medium whitespace-nowrap transition-colors ${
                filter === status
                  ? 'bg-primary text-primary-foreground'
                  : 'bg-secondary text-secondary-foreground hover:bg-accent'
              }`}
            >
              {status === 'all' ? 'All Deals' : status === 'high' ? 'High Match (85%+)' : 'Medium Match (70%+)'}
            </button>
          ))}
        </div>

        {/* Deals List */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          {filteredDeals.length > 0 ? (
            filteredDeals.map((deal) => (
              <DealCard key={deal.id} deal={deal} job={deal.job} onView={setSelectedDeal} />
            ))
          ) : (
            <div className="lg:col-span-2 text-center py-12">
              <p className="text-muted-foreground text-lg">No deals in this category</p>
              <Button className="mt-4 bg-primary hover:bg-primary/90 text-primary-foreground">
                Update Profile
              </Button>
            </div>
          )}
        </div>
      </main>

      {/* Deal Detail Modal */}
      {selectedDeal && (
        <DealDetailModal
          deal={selectedDeal}
          job={selectedDeal.job}
          onClose={() => setSelectedDeal(null)}
          onAccept={() => {
            alert('Contact request sent to buyer!');
            setSelectedDeal(null);
          }}
        />
      )}
    </div>
  );
}
