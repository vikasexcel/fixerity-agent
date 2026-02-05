'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { EditProfileModal } from '@/components/seller/edit-profile-modal';
import { ServiceManagement } from '@/components/seller/service-management';
import { Button } from '@/components/ui/button';
import { RoleAvatar } from '@/components/ui/role-avatar';
import { Star, Check, X, Loader2 } from 'lucide-react';
import { useAuth, getAccessToken } from '@/lib/auth-context';
import { getSellerProfile } from '@/lib/agent-api';
import { getProviderHome, type ProviderHomeResponse } from '@/lib/provider-api';

export default function SellerDashboard() {
  const router = useRouter();
  const [showEditProfile, setShowEditProfile] = useState(false);
  const [loading, setLoading] = useState(true);
  const [providerData, setProviderData] = useState<ProviderHomeResponse | null>(null);
  const [agentProfile, setAgentProfile] = useState<{ provider_name: string; average_rating: number; total_completed_order: number } | null>(null);
  const { session, logout } = useAuth();
  const user = session.user;
  const token = getAccessToken();

  useEffect(() => {
    if (!session.isLoading && (!user || user.role !== 'seller')) {
      router.push('/auth');
      return;
    }
  }, [session.isLoading, user, router]);

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

  // Prefer agent profile (same as match flow) so total_completed_order shows 25 when agent has it
  const completedCount = agentProfile?.total_completed_order ?? providerData?.total_completed_order ?? providerData?.completed_order?.length ?? 0;
  const ratingFromApi = agentProfile?.average_rating ?? providerData?.average_rating ?? 0;
  const displayName = agentProfile?.provider_name ?? providerData?.provider_name ?? user?.name ?? 'Seller';

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
              {/* <Button
                onClick={runScan}
                disabled={loadingDeals}
                className="bg-accent hover:bg-accent/90 text-accent-foreground"
              >
                {loadingDeals ? <Loader2 size={16} className="animate-spin mr-2" /> : null}
                Scan for jobs
              </Button> */}
              {/* <Button
                onClick={() => router.push('/seller/chat')}
                className="bg-primary hover:bg-primary/90 text-primary-foreground"
              >
                Run Agent Scan
              </Button> */}
              <Button
                onClick={() => setShowEditProfile(true)}
                className="bg-primary hover:bg-primary/90 text-primary-foreground"
              >
                Edit Profile
              </Button>
              <button
                onClick={() => {
                  logout();
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

        <ServiceManagement />
      </main>

      {/* Edit Profile Modal */}
      {showEditProfile && (
        <EditProfileModal
          onClose={() => setShowEditProfile(false)}
          onSave={() => {
            // Reload provider data after save
            if (user && token) {
              const fetchProviderData = async () => {
                try {
                  const [profile, homeData] = await Promise.all([
                    getSellerProfile(Number(user.id), token).catch(() => null),
                    getProviderHome(Number(user.id), token).catch(() => null),
                  ]);
                  if (profile) setAgentProfile({ provider_name: profile.provider_name, average_rating: profile.average_rating, total_completed_order: profile.total_completed_order });
                  if (homeData) setProviderData(homeData);
                } catch (err) {
                  console.error('Failed to reload provider data:', err);
                }
              };
              fetchProviderData();
            }
          }}
        />
      )}
    </div>
  );
}
