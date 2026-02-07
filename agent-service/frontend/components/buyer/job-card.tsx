'use client';

import { Job } from '@/lib/dummy-data';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Eye, Zap, Users } from 'lucide-react';

interface JobCardProps {
  job: Job;
  onViewDetails?: (job: Job) => void;
  onStartAgent?: (job: Job) => void;
  onRecommendProviders?: (job: Job) => void;
}

export function JobCard({ job, onViewDetails, onStartAgent, onRecommendProviders }: JobCardProps) {
  const getPriorityColor = (level: string) => {
    switch (level) {
      case 'must_have':
        return 'bg-destructive/10 text-destructive border-destructive/20';
      case 'nice_to_have':
        return 'bg-accent/10 text-accent border-accent/20';
      case 'bonus':
        return 'bg-primary/10 text-primary border-primary/20';
      default:
        return 'bg-muted text-muted-foreground';
    }
  };

  const mustHaves = job.priorities.filter((p) => p.level === 'must_have');

  return (
    <div className="bg-card border border-border rounded-lg p-5 hover:border-primary/50 transition-colors">
      <div className="flex items-start justify-between mb-3">
        <div>
          <h3 className="text-lg font-semibold text-foreground">{job.title}</h3>
          <p className="text-sm text-muted-foreground mt-1">{job.description}</p>
        </div>
        <Badge variant="secondary">{job.status}</Badge>
      </div>

      <div className="grid grid-cols-2 gap-3 mb-4">
        <div className="bg-secondary/50 rounded p-3">
          <p className="text-xs text-muted-foreground mb-1">Budget</p>
          <p className="font-semibold text-foreground">${job.budget.min.toLocaleString()} - ${job.budget.max.toLocaleString()}</p>
        </div>
        <div className="bg-secondary/50 rounded p-3">
          <p className="text-xs text-muted-foreground mb-1">Timeline</p>
          <p className="font-semibold text-foreground">{job.deadline || 'TBD'}</p>
        </div>
      </div>

      <div className="mb-4">
        <p className="text-xs font-medium text-muted-foreground mb-2">MUST HAVE PRIORITIES</p>
        <div className="flex flex-wrap gap-2">
          {mustHaves.slice(0, 3).map((priority, idx) => (
            <Badge key={idx} variant="outline" className={getPriorityColor(priority.level)}>
              {priority.type.replace(/([A-Z])/g, ' $1').toLowerCase()}
            </Badge>
          ))}
          {mustHaves.length > 3 && <Badge variant="outline">+{mustHaves.length - 3} more</Badge>}
        </div>
      </div>

      <div className="flex flex-col gap-2">
        <Button
          onClick={() => onViewDetails?.(job)}
          variant="outline"
          className="w-full bg-transparent"
        >
          <Eye size={16} className="mr-2 shrink-0" />
          View details
        </Button>
        <Button
          onClick={() => onStartAgent?.(job)}
          className="w-full bg-primary hover:bg-primary/90 text-primary-foreground"
        >
          <Zap size={16} className="mr-2 shrink-0" />
          Start agent
        </Button>
        <Button
          onClick={() => onRecommendProviders?.(job)}
          variant="secondary"
          className="w-full"
        >
          <Users size={16} className="mr-2 shrink-0" />
          Recommend providers
        </Button>
      </div>
    </div>
  );
}
