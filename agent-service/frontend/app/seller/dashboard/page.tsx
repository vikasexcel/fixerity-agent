'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import type { Deal, Agent } from '@/lib/dummy-data';
import { DealCard } from '@/components/seller/deal-card';
import { DealDetailModal } from '@/components/seller/deal-detail-modal';
import { Button } from '@/components/ui/button';
import { RoleAvatar } from '@/components/ui/role-avatar';
import { Star, Check, X, Loader2 } from 'lucide-react';
import { getAuthSession, getAccessToken } from '@/lib/auth-context';
import { matchSellerToJobs, getSellerProfile } from '@/lib/agent-api';
import { getProviderHome, type ProviderHomeResponse } from '@/lib/provider-api';

export default function SellerDashboard() {
  const router = useRouter();
  const [selectedDeal, setSelectedDeal] = useState<Deal | null>(null);
  const [filter, setFilter] = useState<'all' | 'high' | 'medium'>('all');
  const [loading, setLoading] = useState(true);
  const [loadingDeals, setLoadingDeals] = useState(false);
  const [providerData, setProviderData] = useState<ProviderHomeResponse | null>(null);
  const [agentProfile, setAgentProfile] = useState<{ provider_name: string; average_rating: number; total_completed_order: number } | null>(null);
  const [deals, setDeals] = useState<Deal[]>([]);
  const user = getAuthSession().user;
  const token = getAccessToken();

  useEffect(() => {
    if (!user || user.role !== 'seller') {
      router.push('/auth');
      return;
    }
  }, [user, router]);

  // Fetch provider details: use agent profile (same source as match) for stats so total_completed_order matches
  useEffect(() => {
    if (!user || !token || user.role !== 'seller') return;

    const fetchProviderData = async () => {
      try {
        setLoading(true);
        const [profile, homeData] = await Promise.all([
          getSellerProfile(Number(user.id), token).catch(() => null),
          getProviderHome(Number(user.id), token).catch(() => null),
        ]);
        if (profile) setAgentProfile({ provider_name: profile.provider_name, average_rating: profile.average_rating, total_completed_order: profile.total_completed_order });
        if (homeData) setProviderData(homeData);
      } catch (err) {
        console.error('Failed to fetch provider data:', err);
      } finally {
        setLoading(false);
      }
    };

    fetchProviderData();
  }, [user?.id, token]);

  // Run scan manually (no auto-scan on load/refresh)
  const runScan = async () => {
    if (!user || !token || user.role !== 'seller') return;
    try {
      setLoadingDeals(true);
      const matchedDeals = await matchSellerToJobs(Number(user.id), token);
      setDeals(matchedDeals);
    } catch (err) {
      console.error('Failed to fetch deals:', err);
    } finally {
      setLoadingDeals(false);
    }
  };

  // Prefer agent profile (same as match flow) so total_completed_order shows 25 when agent has it
  const completedCount = agentProfile?.total_completed_order ?? providerData?.total_completed_order ?? providerData?.completed_order?.length ?? 0;
  const ratingFromApi = agentProfile?.average_rating ?? providerData?.average_rating ?? 0;
  const displayName = agentProfile?.provider_name ?? providerData?.provider_name ?? user?.name ?? 'Seller';

  const currentAgent: Agent | null = user
    ? {
        id: `agent_${user.id}`,
        userId: user.id,
        name: displayName,
        type: 'seller',
        rating: ratingFromApi,
        jobsCompleted: completedCount,
        licensed: false,
        references: false,
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
              {loading ? (
                <div className="flex items-center gap-2 mt-1">
                  <Loader2 size={14} className="animate-spin text-muted-foreground" />
                  <p className="text-muted-foreground text-sm">Loading profile...</p>
                </div>
              ) : (
                <p className="text-muted-foreground text-sm mt-1">
                  Welcome, {displayName} • {completedCount} projects completed
                </p>
              )}
            </div>
            <div className="flex items-center gap-3">
              <Button
                onClick={runScan}
                disabled={loadingDeals}
                className="bg-accent hover:bg-accent/90 text-accent-foreground"
              >
                {loadingDeals ? <Loader2 size={16} className="animate-spin mr-2" /> : null}
                Scan for jobs
              </Button>
              <Button
                onClick={() => router.push('/seller/chat')}
                className="bg-primary hover:bg-primary/90 text-primary-foreground"
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
        {loading ? (
          <div className="bg-card border border-border rounded-lg p-6 mb-8">
            <div className="flex items-center justify-center py-8">
              <Loader2 size={24} className="animate-spin text-muted-foreground" />
              <p className="ml-3 text-muted-foreground">Loading profile...</p>
            </div>
          </div>
        ) : (
          <div className="bg-card border border-border rounded-lg p-6 mb-8">
            <div className="flex items-start gap-6">
              <RoleAvatar name={displayName} type="seller" size="lg" />
              <div className="flex-1">
                <div className="flex items-center gap-4 mb-2">
                  <h2 className="text-2xl font-bold text-foreground">{displayName}</h2>
                  <div className="flex items-center gap-4">
                    <div className="text-center">
                      <p className="text-2xl font-bold text-accent flex items-center justify-center gap-1">
                        <Star size={20} className="fill-accent text-accent" />
                        {ratingFromApi > 0 ? ratingFromApi.toFixed(1) : '—'}
                      </p>
                      <p className="text-xs text-muted-foreground">Rating</p>
                    </div>
                    <div className="text-center">
                      <p className="text-2xl font-bold text-primary">{completedCount}</p>
                      <p className="text-xs text-muted-foreground">Completed</p>
                    </div>
                    <div className="text-center">
                      <p className="text-lg font-bold text-foreground flex items-center justify-center">
                        {providerData?.current_status === 1 ? <Check size={20} className="text-green-500" /> : <X size={20} className="text-red-500" />}
                      </p>
                      <p className="text-xs text-muted-foreground">Status</p>
                    </div>
                    {providerData?.provider_service_radius && (
                      <div className="text-center">
                        <p className="text-lg font-bold text-foreground">{providerData.provider_service_radius}</p>
                        <p className="text-xs text-muted-foreground">Service Radius</p>
                      </div>
                    )}
                  </div>
                </div>
                {(() => {
                  const list = providerData?.provider_services_list;
                  const items: { label: string }[] = Array.isArray(list)
                    ? list.map((s) => ({ label: typeof s === 'object' && s && 'service_cat_name' in s ? (s as { service_cat_name: string }).service_cat_name : String(s) }))
                    : typeof list === 'string' && list.trim()
                      ? list.split(',').map((s) => ({ label: s.trim() }))
                      : [];
                  return items.length > 0 ? (
                    <div className="flex flex-wrap gap-2 mt-4">
                      {items.map((item, idx) => (
                        <span key={idx} className="bg-secondary text-secondary-foreground text-xs px-3 py-1 rounded">
                          {item.label}
                        </span>
                      ))}
                    </div>
                  ) : null;
                })()}
              </div>
            </div>
          </div>
        )}

        {/* Quick Stats */}
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
          <div className="bg-card rounded-lg border border-border p-5">
            <p className="text-muted-foreground text-sm mb-2">Available Deals</p>
            <p className="text-3xl font-bold text-foreground">{deals.length}</p>
          </div>
          <div className="bg-card rounded-lg border border-border p-5">
            <p className="text-muted-foreground text-sm mb-2">High Match</p>
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
          {loadingDeals ? (
            <div className="lg:col-span-2 text-center py-12">
              <Loader2 size={32} className="animate-spin text-muted-foreground mx-auto mb-4" />
              <p className="text-muted-foreground text-lg">Scanning for matching jobs...</p>
            </div>
          ) : filteredDeals.length > 0 ? (
            filteredDeals.map((deal) => (
              <DealCard key={deal.id} deal={deal} job={deal.job} onView={setSelectedDeal} />
            ))
          ) : (
            <div className="lg:col-span-2 text-center py-12">
              <p className="text-muted-foreground text-lg">No matching jobs found at this time</p>
              <p className="text-muted-foreground text-sm mt-2">Run a scan to find matching jobs</p>
              <Button
                onClick={runScan}
                disabled={loadingDeals}
                className="mt-4 bg-primary hover:bg-primary/90 text-primary-foreground"
              >
                {loadingDeals ? <Loader2 size={16} className="animate-spin mr-2" /> : null}
                Scan for jobs
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
