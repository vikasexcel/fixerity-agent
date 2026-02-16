'use client';

import { useState, useEffect } from 'react';
import Link from 'next/link';
import { Star, Sparkles, Target, X, MessageCircle, Loader2 } from 'lucide-react';
import { Job, Deal } from '@/lib/dummy-data';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { RoleAvatar } from '@/components/ui/role-avatar';
import { useAuth, getAccessToken } from '@/lib/auth-context';
import { getJobMatchResults } from '@/lib/agent-api';

export type JobDetailModalMode = 'details-only' | 'with-recommendations';

interface JobDetailModalProps {
  job: Job;
  deals?: Deal[];
  mode?: JobDetailModalMode;
  onClose: () => void;
}

export function JobDetailModal({ job, deals: dealsProp = [], mode = 'with-recommendations', onClose }: JobDetailModalProps) {
  const { session } = useAuth();
  const user = session.user;
  const token = getAccessToken();
  const [matches, setMatches] = useState<Deal[] | null>(null);
  const [matchesLoading, setMatchesLoading] = useState(mode === 'with-recommendations');
  const [matchesError, setMatchesError] = useState<string | null>(null);
  const showRecommendations = mode === 'with-recommendations';

  useEffect(() => {
    if (!showRecommendations || !job || !user || user.role !== 'buyer' || !token) {
      setMatchesLoading(false);
      if (!showRecommendations) setMatches(null);
      return;
    }
    setMatchesLoading(true);
    setMatchesError(null);
    getJobMatchResults(job.id, Number(user.id), token)
      .then((deals) => {
        const withJobId = (deals ?? []).map((d) => ({ ...d, jobId: d.jobId || job.id }));
        setMatches(withJobId);
        setMatchesLoading(false);
      })
      .catch((err) => {
        setMatchesError(err instanceof Error ? err.message : 'Failed to load matches');
        setMatches([]);
        setMatchesLoading(false);
      });
  }, [showRecommendations, job?.id, user?.id, token]);

  const jobDeals = (matches !== null ? matches : dealsProp.filter((d) => d.jobId === job.id));

  const getPriorityIcon = (level: string) => {
    const iconClass = 'text-primary mt-1 flex-shrink-0';
    switch (level) {
      case 'must_have':
        return <Star size={18} className={iconClass} />;
      case 'nice_to_have':
        return <Sparkles size={18} className={iconClass} />;
      case 'bonus':
        return <Target size={18} className={iconClass} />;
      default:
        return <span className="text-muted-foreground">•</span>;
    }
  };

  const getPriorityLabel = (level: string) => {
    switch (level) {
      case 'must_have':
        return 'MUST HAVE';
      case 'nice_to_have':
        return 'NICE TO HAVE';
      case 'bonus':
        return 'BONUS';
      default:
        return '';
    }
  };

  return (
    <div className="fixed inset-0 bg-black/50 flex items-end sm:items-center justify-center z-50 p-4">
      <div className="bg-card rounded-lg border border-border w-full max-w-2xl max-h-[90vh] overflow-y-auto shadow-lg">
        {/* Header */}
        <div className="sticky top-0 bg-card border-b border-border p-6 flex justify-between items-start">
          <div>
            <h2 className="text-2xl font-bold text-foreground">{job.title}</h2>
            <p className="text-muted-foreground text-sm mt-1">{job.description}</p>
          </div>
          <button
            onClick={onClose}
            className="text-muted-foreground hover:text-foreground p-1"
            aria-label="Close"
          >
            <X size={20} />
          </button>
        </div>

        {/* Content */}
        <div className="p-6 space-y-6">
          {/* Budget & Timeline */}
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div className="bg-secondary/50 rounded-lg p-4">
              <p className="text-xs font-semibold text-muted-foreground mb-2">BUDGET</p>
              <p className="text-xl font-bold text-foreground">
                ${job.budget.min.toLocaleString()} - ${job.budget.max.toLocaleString()}
              </p>
            </div>
            <div className="bg-secondary/50 rounded-lg p-4">
              <p className="text-xs font-semibold text-muted-foreground mb-2">START DATE</p>
              <p className="text-lg font-bold text-foreground">{job.startDate}</p>
            </div>
            <div className="bg-secondary/50 rounded-lg p-4">
              <p className="text-xs font-semibold text-muted-foreground mb-2">END DATE</p>
              <p className="text-lg font-bold text-foreground">{job.endDate}</p>
            </div>
          </div>

          {/* Priorities */}
          <div>
            <h3 className="text-lg font-semibold text-foreground mb-4">Job Requirements & Priorities</h3>
            <div className="space-y-3">
              {job.priorities.map((priority, idx) => (
                <div key={idx} className="bg-secondary/30 rounded-lg p-4 border border-border/50">
                  <div className="flex items-start gap-3">
                    <span className="flex items-center">{getPriorityIcon(priority.level)}</span>
                    <div className="flex-1">
                      <div className="flex items-center gap-2 mb-1">
                        <span className="font-semibold text-foreground capitalize">
                          {priority.type.replace(/([A-Z])/g, ' $1').toLowerCase()}
                        </span>
                        <Badge variant="outline" className="text-xs">
                          {getPriorityLabel(priority.level)}
                        </Badge>
                      </div>
                      <p className="text-sm text-muted-foreground">{priority.description}</p>
                      {priority.value && (
                        <p className="text-sm font-medium text-foreground mt-2">
                          Value: {typeof priority.value === 'number' ? `$${priority.value}` : priority.value}
                        </p>
                      )}
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>

          {/* Matched Agents - only when mode is with-recommendations */}
          {showRecommendations && (
          <div>
            <h3 className="text-lg font-semibold text-foreground mb-4">
              Recommended Agents ({matchesLoading ? '…' : jobDeals.length} Matches)
            </h3>
            {matchesLoading && (
              <div className="flex items-center gap-2 text-muted-foreground py-4">
                <Loader2 size={20} className="animate-spin shrink-0" />
                <span className="text-sm">Loading recommended agents…</span>
              </div>
            )}
            {!matchesLoading && matchesError && (
              <p className="text-sm text-muted-foreground py-2">{matchesError}</p>
            )}
            {!matchesLoading && !matchesError && (
            <div className="space-y-3">
              {jobDeals.map((deal) => (
                <div key={deal.id} className="bg-secondary/30 rounded-lg p-4 border border-accent/30">
                  <div className="flex items-start justify-between mb-3">
                    <div className="flex items-center gap-3">
                      <RoleAvatar name={deal.sellerAgent?.name ?? deal.sellerName ?? 'Provider'} type="seller" size="md" />
                      <div>
                        <p className="font-semibold text-foreground">{deal.sellerAgent?.name ?? deal.sellerName ?? 'Provider'}</p>
                        {deal.sellerAgent != null ? (
                          <div className="flex items-center gap-2 text-sm">
                            <span className="text-accent flex items-center gap-1">
                              <Star size={14} className="fill-accent text-accent" />
                              {deal.sellerAgent.rating}
                            </span>
                            <span className="text-muted-foreground">• {deal.sellerAgent.jobsCompleted} jobs</span>
                          </div>
                        ) : null}
                      </div>
                    </div>
                    <div className="text-right">
                      <p className="text-2xl font-bold text-primary">{deal.matchScore}%</p>
                      <p className="text-xs text-muted-foreground">Match Score</p>
                    </div>
                  </div>

                  {(deal.negotiatedPrice != null || deal.negotiatedCompletionDays != null || deal.quote?.price != null) && (
                    <div className="mb-3 flex gap-4 text-sm flex-wrap">
                      {(deal.negotiatedPrice ?? deal.quote?.price) != null && (
                        <span className="text-foreground font-medium">
                          Agreed price: ${Number(deal.negotiatedPrice ?? deal.quote?.price).toLocaleString()}
                        </span>
                      )}
                      {(deal.negotiatedCompletionDays ?? deal.quote?.days ?? deal.quote?.completionDays) != null && (
                        <span className="text-foreground font-medium">
                          Completion: {Number(deal.negotiatedCompletionDays ?? deal.quote?.days ?? deal.quote?.completionDays)} day{Number(deal.negotiatedCompletionDays ?? deal.quote?.days ?? deal.quote?.completionDays) !== 1 ? 's' : ''}
                        </span>
                      )}
                    </div>
                  )}

                  {(deal.sellerEmail != null || deal.sellerContactNumber != null || deal.quote?.paymentSchedule != null || deal.quote?.licensed != null || deal.quote?.referencesAvailable != null) && (
                    <div className="mb-3 rounded-md bg-muted/50 p-3 text-sm">
                      {(deal.sellerEmail != null || deal.sellerContactNumber != null) && (
                        <>
                          <p className="text-xs font-semibold text-muted-foreground mb-1">Provider details</p>
                          <div className="space-y-1 mb-2">
                            {deal.sellerEmail && (
                              <p className="text-foreground">
                                <span className="text-muted-foreground">Email:</span>{' '}
                                <a href={`mailto:${deal.sellerEmail}`} className="text-primary hover:underline">{deal.sellerEmail}</a>
                              </p>
                            )}
                            {deal.sellerContactNumber && (
                              <p className="text-foreground">
                                <span className="text-muted-foreground">Contact:</span>{' '}
                                <a href={`tel:${deal.sellerContactNumber}`} className="text-primary hover:underline">{deal.sellerContactNumber}</a>
                              </p>
                            )}
                          </div>
                        </>
                      )}
                      <ul className="space-y-0.5 text-muted-foreground list-none pl-0">
                        {deal.quote?.paymentSchedule != null && (
                          <li><span className="text-foreground/80">Payment:</span> {deal.quote.paymentSchedule}</li>
                        )}
                        {deal.quote?.licensed != null && (
                          <li><span className="text-foreground/80">Licensed:</span> {deal.quote.licensed ? 'Yes' : 'No'}</li>
                        )}
                        {deal.quote?.referencesAvailable != null && (
                          <li><span className="text-foreground/80">References:</span> {deal.quote.referencesAvailable ? 'Yes' : 'No'}</li>
                        )}
                      </ul>
                    </div>
                  )}

                  {Array.isArray(deal.matchReasons) && deal.matchReasons.length > 0 && (
                    <div className="mb-3">
                      <p className="text-xs font-semibold text-muted-foreground mb-2">WHY MATCHED</p>
                      <div className="space-y-1">
                        {deal.matchReasons.map((reason, idx) => (
                          <p key={idx} className="text-sm text-foreground">
                            {String(reason).replace(/^[✓✗⚠]\s*/, '')}
                          </p>
                        ))}
                      </div>
                    </div>
                  )}

                  <div className="flex gap-2">
                    <Button className="flex-1 bg-primary hover:bg-primary/90 text-primary-foreground text-sm">
                      View Profile
                    </Button>
                    <Button variant="outline" className="flex-1 text-sm bg-transparent">
                      Schedule Call
                    </Button>
                  </div>
                </div>
              ))}
            </div>
            )}
          </div>
          )}
        </div>

        {/* Footer */}
        <div className="sticky bottom-0 bg-card border-t border-border p-6 flex gap-3">
          <Button onClick={onClose} variant="outline" className="flex-1 bg-transparent">
            Close
          </Button>
          <Link href={`/buyer/jobs/${encodeURIComponent(job.id)}/chat`} className="flex-1">
            <Button className="w-full bg-primary hover:bg-primary/90 text-primary-foreground flex items-center justify-center gap-2">
              <MessageCircle size={18} />
              Open Chat
            </Button>
          </Link>
        </div>
      </div>
    </div>
  );
}
