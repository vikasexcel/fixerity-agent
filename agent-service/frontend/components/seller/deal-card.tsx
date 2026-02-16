'use client';

import type { Deal, Job } from '@/lib/dummy-data';
import { Button } from '@/components/ui/button';

interface DealCardProps {
  deal: Deal;
  job?: Job | null;
  onView?: (deal: Deal) => void;
}

export function DealCard({ deal, job, onView }: DealCardProps) {
  if (!job) return null;

  const getMatchColor = () => {
    if (deal.matchScore >= 90) return 'bg-primary/10 border-primary/30';
    if (deal.matchScore >= 80) return 'bg-accent/10 border-accent/30';
    return 'bg-secondary/10 border-secondary/30';
  };

  const budgetMax = job.budget && typeof job.budget === 'object' && 'max' in job.budget ? Number(job.budget.max) : 0;
  const budgetMin = job.budget && typeof job.budget === 'object' && 'min' in job.budget ? Number(job.budget.min) : 0;
  const timeline = job.deadline ?? (job.startDate && job.endDate ? `${job.startDate} – ${job.endDate}` : job.startDate ?? job.endDate ?? '—');

  return (
    <div className={`bg-card border rounded-lg p-5 hover:border-primary/50 transition-colors ${getMatchColor()}`}>
      <div className="flex justify-between items-start mb-3">
        <div className="flex-1 min-w-0">
          <div className="flex items-center gap-2 flex-wrap">
            {deal.rank != null && (
              <span className="inline-flex items-center justify-center rounded-md bg-primary/20 text-primary font-bold text-sm w-8 h-8 shrink-0">
                #{deal.rank}
              </span>
            )}
            <h3 className="text-lg font-semibold text-foreground">{job.title}</h3>
          </div>
          <p className="text-sm text-muted-foreground mt-1 line-clamp-2">{job.description || 'No description'}</p>
        </div>
        <div className="text-right shrink-0 ml-2">
          <div className="text-3xl font-bold text-primary">{deal.matchScore}%</div>
          <p className="text-xs text-muted-foreground">Match</p>
        </div>
      </div>

      <div className="grid grid-cols-2 gap-3 mb-4">
        <div className="bg-secondary/50 rounded p-3">
          <p className="text-xs text-muted-foreground mb-1">Budget</p>
          <p className="font-semibold text-foreground">
            {budgetMin > 0 || budgetMax > 0 ? `$${budgetMin.toLocaleString()} – $${budgetMax.toLocaleString()}` : '—'}
          </p>
        </div>
        <div className="bg-secondary/50 rounded p-3">
          <p className="text-xs text-muted-foreground mb-1">Timeline</p>
          <p className="font-semibold text-foreground">{timeline}</p>
        </div>
      </div>

      <div className="mb-4">
        <p className="text-xs font-medium text-muted-foreground mb-2">WHY YOU MATCH</p>
        <div className="space-y-1">
          {deal.matchReasons.slice(0, 2).map((reason, idx) => (
            <p key={idx} className="text-sm text-foreground">
              {reason}
            </p>
          ))}
          {deal.matchReasons.length > 2 && (
            <p className="text-sm text-muted-foreground">+{deal.matchReasons.length - 2} more reasons</p>
          )}
        </div>
      </div>

      <Button onClick={() => onView?.(deal)} className="w-full bg-primary hover:bg-primary/90 text-primary-foreground">
        View Opportunity
      </Button>
    </div>
  );
}
