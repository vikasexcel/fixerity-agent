'use client';

import Link from 'next/link';
import { Star, Sparkles, Target, X, MessageCircle } from 'lucide-react';
import { Job, Deal } from '@/lib/dummy-data';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { RoleAvatar } from '@/components/ui/role-avatar';

interface JobDetailModalProps {
  job: Job;
  deals: Deal[];
  onClose: () => void;
}

export function JobDetailModal({ job, deals, onClose }: JobDetailModalProps) {
  const jobDeals = deals.filter((d) => d.jobId === job.id);

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

          {/* Matched Agents */}
          <div>
            <h3 className="text-lg font-semibold text-foreground mb-4">
              Recommended Agents ({jobDeals.length} Matches)
            </h3>
            <div className="space-y-3">
              {jobDeals.map((deal) => (
                <div key={deal.id} className="bg-secondary/30 rounded-lg p-4 border border-accent/30">
                  <div className="flex items-start justify-between mb-3">
                    <div className="flex items-center gap-3">
                      <RoleAvatar name={deal.sellerAgent.name} type="seller" size="md" />
                      <div>
                        <p className="font-semibold text-foreground">{deal.sellerAgent.name}</p>
                        <div className="flex items-center gap-2 text-sm">
                          <span className="text-accent flex items-center gap-1">
                            <Star size={14} className="fill-accent text-accent" />
                            {deal.sellerAgent.rating}
                          </span>
                          <span className="text-muted-foreground">• {deal.sellerAgent.jobsCompleted} jobs</span>
                        </div>
                      </div>
                    </div>
                    <div className="text-right">
                      <p className="text-2xl font-bold text-primary">{deal.matchScore}%</p>
                      <p className="text-xs text-muted-foreground">Match Score</p>
                    </div>
                  </div>

                  <div className="mb-3">
                    <p className="text-xs font-semibold text-muted-foreground mb-2">WHY MATCHED</p>
                    <div className="space-y-1">
                      {deal.matchReasons.map((reason, idx) => (
                        <p key={idx} className="text-sm text-foreground">
                          {reason.replace(/^[✓✗⚠]\s*/, '')}
                        </p>
                      ))}
                    </div>
                  </div>

                  <div className="flex gap-2">
                    <Button className="flex-1 bg-primary hover:bg-primary/90 text-primary-foreground text-sm">
                      View Profile
                    </Button>
                    {deal.status === 'proposed' && (
                      <Button variant="outline" className="flex-1 text-sm bg-transparent">
                        Schedule Call
                      </Button>
                    )}
                  </div>
                </div>
              ))}
            </div>
          </div>
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
