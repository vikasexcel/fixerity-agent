'use client';

import React, { useState } from 'react';
import { ChevronDown, ChevronUp } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import type { MatchedJob } from '@/lib/seller-agent-api';

export type JobMatchCardProps = {
  rank: number;
  job: MatchedJob;
  onInterested: () => void;
  onSkip: () => void;
  decision?: 'interested' | 'skipped';
};

const JOB_PREVIEW_LINES = 4;
const LINE_HEIGHT = 1.25;

function scoreColor(score: number): string {
  if (score >= 80) return 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 border-emerald-500/30';
  if (score >= 60) return 'bg-amber-500/15 text-amber-700 dark:text-amber-400 border-amber-500/30';
  return 'bg-muted text-muted-foreground border-border';
}

export function JobMatchCard({
  rank,
  job,
  onInterested,
  onSkip,
  decision,
}: JobMatchCardProps) {
  const [expanded, setExpanded] = useState(false);
  const previewLength = JOB_PREVIEW_LINES * 40;
  const hasMore = job.jobText.length > previewLength;
  const displayText = expanded || !hasMore
    ? job.jobText
    : job.jobText.slice(0, previewLength) + '…';

  const meta = job.metadata || {};
  const budget = meta.budget as string | undefined;
  const location = meta.location as string | undefined;
  const timeline = meta.timeline as string | undefined;

  const borderByDecision = decision
    ? decision === 'interested'
      ? 'border-emerald-500/50'
      : 'border-muted opacity-80'
    : '';

  return (
    <article
      className={cn(
        'rounded-lg border bg-card p-4 shadow-sm transition-colors',
        borderByDecision || 'border-border'
      )}
      aria-label={`Job match rank ${rank}: ${job.jobTitle}`}
    >
      <header className="flex flex-wrap items-center gap-2 gap-y-1.5 mb-3">
        <span
          className="rounded-md bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary"
          aria-hidden
        >
          Rank {rank}
        </span>
        <h3 className="text-sm font-semibold truncate flex-1 min-w-0">
          {job.jobTitle}
        </h3>
        <span
          className={cn(
            'rounded-md border px-2 py-0.5 text-xs font-medium',
            scoreColor(job.matchScore)
          )}
          aria-label={`Match score: ${job.matchScore} out of 100`}
        >
          {job.matchScore}
        </span>
      </header>

      <p className="text-sm text-muted-foreground mb-3 leading-relaxed">
        {job.matchExplanation}
      </p>

      {(budget || location || timeline) && (
        <div className="grid grid-cols-2 gap-x-4 gap-y-1 text-xs text-muted-foreground mb-3">
          {budget && <span>Budget: {budget}</span>}
          {location && <span>Location: {location}</span>}
          {timeline && <span>Timeline: {timeline}</span>}
        </div>
      )}

      <div className="mb-3">
        <p
          className="text-xs text-muted-foreground whitespace-pre-wrap break-words font-sans"
          style={{ lineHeight: LINE_HEIGHT }}
        >
          {displayText}
        </p>
        {hasMore && (
          <button
            type="button"
            onClick={() => setExpanded((e) => !e)}
            className="mt-1 text-xs text-primary hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring rounded"
            aria-expanded={expanded}
          >
            {expanded ? (
              <>Show less <ChevronUp className="inline h-3 w-3" /></>
            ) : (
              <>Show more <ChevronDown className="inline h-3 w-3" /></>
            )}
          </button>
        )}
      </div>

      <footer className="flex flex-wrap items-center gap-2 pt-2 border-t border-border">
        <Button
          type="button"
          size="sm"
          variant="default"
          onClick={onInterested}
          disabled={!!decision}
          className="bg-emerald-600 hover:bg-emerald-700"
          aria-label={`Interested in ${job.jobTitle}`}
        >
          Interested
        </Button>
        <Button
          type="button"
          size="sm"
          variant="outline"
          className="border-muted-foreground/50 text-muted-foreground hover:bg-muted"
          onClick={onSkip}
          disabled={!!decision}
          aria-label={`Skip ${job.jobTitle}`}
        >
          Skip
        </Button>
      </footer>
    </article>
  );
}
