'use client';

import { Star, Sparkles, Target, X, Check } from 'lucide-react';
import type { Deal, Job } from '@/lib/dummy-data';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { RoleAvatar } from '@/components/ui/role-avatar';

interface DealDetailModalProps {
  deal: Deal;
  job?: Job | null;
  onClose: () => void;
  onAccept?: () => void;
}

export function DealDetailModal({ deal, job: jobProp, onClose, onAccept }: DealDetailModalProps) {
  const job = jobProp ?? deal.job;

  if (!job) return null;

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
          <div className="flex-1">
            <div className="flex items-center gap-3 mb-2">
              <RoleAvatar name={deal.sellerAgent.name} type="seller" size="lg" />
              <div>
                <h2 className="text-xl font-bold text-foreground">Opportunity for {deal.sellerAgent.name}</h2>
                <p className="text-sm text-muted-foreground">{job.title}</p>
              </div>
            </div>
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
          {/* Your Match Score */}
          <div className="bg-primary/10 border border-primary/30 rounded-lg p-6 text-center">
            <p className="text-muted-foreground mb-2">Your Match Score</p>
            <p className="text-5xl font-bold text-primary">{deal.matchScore}%</p>
            <p className="text-sm text-foreground mt-2">Excellent match for this opportunity</p>
          </div>

          {/* Job Details */}
          <div>
            <h3 className="text-lg font-semibold text-foreground mb-4">Job Details</h3>
            <div className="bg-secondary/30 rounded-lg p-4 border border-border/50 space-y-3">
              <div>
                <p className="text-sm text-muted-foreground mb-1">Description</p>
                <p className="text-foreground">{job.description}</p>
              </div>
              <div className="grid grid-cols-3 gap-3">
                <div>
                  <p className="text-xs text-muted-foreground mb-2">BUDGET</p>
                  <p className="font-bold text-foreground">${job.budget.min.toLocaleString()}</p>
                  <p className="text-xs text-foreground">${job.budget.max.toLocaleString()}</p>
                </div>
                <div>
                  <p className="text-xs text-muted-foreground mb-2">START</p>
                  <p className="font-bold text-foreground">{job.startDate}</p>
                </div>
                <div>
                  <p className="text-xs text-muted-foreground mb-2">END</p>
                  <p className="font-bold text-foreground">{job.endDate}</p>
                </div>
              </div>
            </div>
          </div>

          {/* Why You Match */}
          <div>
            <h3 className="text-lg font-semibold text-foreground mb-4">Why You Match This Job</h3>
            <div className="space-y-2">
              {deal.matchReasons.map((reason, idx) => (
                <div key={idx} className="flex items-start gap-3 bg-secondary/30 rounded-lg p-3">
                  <Check size={16} className="text-primary mt-0.5 flex-shrink-0" />
                  <p className="text-foreground text-sm">{reason.replace(/^[✓✗⚠]\s*/, '')}</p>
                </div>
              ))}
            </div>
          </div>

          {/* Buyer's Requirements */}
          <div>
            <h3 className="text-lg font-semibold text-foreground mb-4">Buyer's Requirements</h3>
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
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>

          {/* Your Profile Snapshot */}
          <div>
            <h3 className="text-lg font-semibold text-foreground mb-4">Your Profile Snapshot</h3>
            <div className="bg-accent/5 border border-accent/20 rounded-lg p-4">
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <p className="text-xs text-muted-foreground mb-1">RATING</p>
                  <p className="text-2xl font-bold text-accent flex items-center gap-1">
                    <Star size={20} className="fill-accent text-accent" />
                    {deal.sellerAgent.rating}
                  </p>
                </div>
                <div>
                  <p className="text-xs text-muted-foreground mb-1">COMPLETED JOBS</p>
                  <p className="text-2xl font-bold text-accent">{deal.sellerAgent.jobsCompleted}</p>
                </div>
                <div>
                  <p className="text-xs text-muted-foreground mb-1">LICENSE</p>
                  <p className="text-lg font-semibold text-foreground flex items-center gap-2">
                    {deal.sellerAgent.licensed ? <><Check size={18} /> Licensed</> : 'Not Licensed'}
                  </p>
                </div>
                <div>
                  <p className="text-xs text-muted-foreground mb-1">REFERENCES</p>
                  <p className="text-lg font-semibold text-foreground flex items-center gap-2">
                    {deal.sellerAgent.references ? <><Check size={18} /> Available</> : 'Not Available'}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Footer */}
        <div className="sticky bottom-0 bg-card border-t border-border p-6 flex gap-3">
          <Button onClick={onClose} variant="outline" className="flex-1 bg-transparent">
            Close
          </Button>
          <Button onClick={onAccept} className="flex-1 bg-accent hover:bg-accent/90 text-accent-foreground">
            Contact Buyer
          </Button>
        </div>
      </div>
    </div>
  );
}
