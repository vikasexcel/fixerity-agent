'use client';

import { useState, useEffect } from 'react';
import { X, Loader2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useAuth, getAccessToken } from '@/lib/auth-context';
import { matchSellerToJobs } from '@/lib/agent-api';
import { getAgentConfigForAgent } from '@/lib/agent-config';
import type { Deal } from '@/lib/dummy-data';
import { DealCard } from './deal-card';
import { DealDetailModal } from './deal-detail-modal';

interface ServiceAgentRunnerProps {
  serviceCategoryId: number;
  subCategoryId?: number;
  onClose: () => void;
}

export function ServiceAgentRunner({ serviceCategoryId, subCategoryId, onClose }: ServiceAgentRunnerProps) {
  const { session } = useAuth();
  const user = session.user;
  const token = getAccessToken();
  const providerId = user?.role === 'seller' ? Number(user.id) : 0;

  const [loading, setLoading] = useState(false);
  const [deals, setDeals] = useState<Deal[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [selectedDeal, setSelectedDeal] = useState<Deal | null>(null);

  const runAgent = async () => {
    if (!user || !token || user.role !== 'seller' || !providerId) {
      setError('Not authenticated');
      return;
    }

    setLoading(true);
    setError(null);
    setDeals([]);

    try {
      // Load agent configuration if exists from database
      const agentConfig = await getAgentConfigForAgent(providerId, token, serviceCategoryId);

      // Call seller agent API
      const matchedDeals = await matchSellerToJobs(providerId, token, {
        service_category_id: serviceCategoryId,
        sub_category_id: subCategoryId,
        agentConfig: agentConfig || undefined,
      });

      setDeals(matchedDeals);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to run agent');
    } finally {
      setLoading(false);
    }
  };

  // Auto-run on mount
  useEffect(() => {
    if (providerId && token) {
      runAgent();
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  return (
    <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div className="bg-card border border-border rounded-lg shadow-lg w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
        {/* Header */}
        <div className="flex items-center justify-between p-6 border-b border-border">
          <div>
            <h2 className="text-2xl font-bold text-foreground">Seller Agent Results</h2>
            <p className="text-sm text-muted-foreground mt-1">
              Service Category: {serviceCategoryId}
              {subCategoryId && ` • Sub Category: ${subCategoryId}`}
            </p>
          </div>
          <button
            onClick={onClose}
            className="text-muted-foreground hover:text-foreground transition-colors"
          >
            <X size={24} />
          </button>
        </div>

        {/* Content */}
        <div className="flex-1 overflow-y-auto p-6">
          {loading && (
            <div className="flex flex-col items-center justify-center py-12">
              <Loader2 size={32} className="animate-spin text-primary mb-4" />
              <p className="text-muted-foreground">Running seller agent...</p>
            </div>
          )}

          {error && (
            <div className="mb-4 p-4 bg-destructive/10 border border-destructive/20 rounded-lg">
              <p className="text-destructive font-medium mb-2">Error</p>
              <p className="text-sm text-destructive/80">{error}</p>
              <Button
                onClick={runAgent}
                variant="outline"
                className="mt-3"
                size="sm"
              >
                Retry
              </Button>
            </div>
          )}

          {!loading && !error && deals.length === 0 && (
            <div className="text-center py-12">
              <p className="text-muted-foreground mb-4">No matching jobs found</p>
              <Button onClick={runAgent} variant="outline">
                Run Again
              </Button>
            </div>
          )}

          {!loading && !error && deals.length > 0 && (
            <div className="space-y-4">
              <div className="flex items-center justify-between mb-4">
                <div>
                  <p className="text-sm font-medium text-foreground">
                    Top {deals.length} matching {deals.length === 1 ? 'job' : 'jobs'} (rank order)
                  </p>
                  <p className="text-xs text-muted-foreground mt-0.5">
                    Full job details below; best match first.
                  </p>
                </div>
                <Button onClick={runAgent} variant="outline" size="sm">
                  Refresh
                </Button>
              </div>

              <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
                {deals.map((deal) => (
                  <DealCard
                    key={deal.id}
                    deal={deal}
                    job={deal.job}
                    onView={setSelectedDeal}
                  />
                ))}
              </div>
            </div>
          )}
        </div>

        {/* Footer */}
        <div className="border-t border-border p-6">
          <Button onClick={onClose} className="w-full">
            Close
          </Button>
        </div>
      </div>

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
