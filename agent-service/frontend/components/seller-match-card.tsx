'use client';

import React, { useState } from 'react';
import { MessageCircle, ChevronDown, ChevronUp } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import type { MatchedSeller } from '@/lib/buyer-agent-api';

export type SellerMatchCardProps = {
  rank: number;
  seller: MatchedSeller;
  onApprove: () => void;
  onReject: () => void;
  onMessage: () => void;
  decision?: 'approved' | 'rejected' | 'contacted';
};

const PROFILE_PREVIEW_LINES = 4;
const LINE_HEIGHT = 1.25;

function scoreColor(score: number): string {
  if (score >= 80) return 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 border-emerald-500/30';
  if (score >= 60) return 'bg-amber-500/15 text-amber-700 dark:text-amber-400 border-amber-500/30';
  return 'bg-muted text-muted-foreground border-border';
}

export function SellerMatchCard({
  rank,
  seller,
  onApprove,
  onReject,
  onMessage,
  decision,
}: SellerMatchCardProps) {
  const [expanded, setExpanded] = useState(false);
  const previewLength = PROFILE_PREVIEW_LINES * 40;
  const hasMore = seller.profileText.length > previewLength;
  const displayText = expanded || !hasMore
    ? seller.profileText
    : seller.profileText.slice(0, previewLength) + '…';

  const borderByDecision = decision
    ? decision === 'approved'
      ? 'border-emerald-500/50'
      : decision === 'rejected'
        ? 'border-muted opacity-80'
        : 'border-blue-500/50'
    : '';

  return (
    <article
      className={cn(
        'rounded-lg border bg-card p-4 shadow-sm transition-colors',
        borderByDecision || 'border-border'
      )}
      aria-label={`Seller match rank ${rank}: ${seller.sellerName}`}
    >
      <header className="flex flex-wrap items-center gap-2 gap-y-1.5 mb-3">
        <span
          className="rounded-md bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary"
          aria-hidden
        >
          Rank {rank}
        </span>
        <h3 className="text-sm font-semibold truncate flex-1 min-w-0">
          {seller.sellerName}
        </h3>
        <span
          className={cn(
            'rounded-md border px-2 py-0.5 text-xs font-medium',
            scoreColor(seller.matchScore)
          )}
          aria-label={`Match score: ${seller.matchScore} out of 100`}
        >
          {seller.matchScore}
        </span>
      </header>

      <p className="text-sm text-muted-foreground mb-3 leading-relaxed">
        {seller.matchExplanation}
      </p>

      {(seller.metadata?.location || seller.metadata?.rate) && (
        <div className="grid grid-cols-2 gap-x-4 gap-y-1 text-xs text-muted-foreground mb-3">
          {seller.metadata.location && (
            <span>Location: {seller.metadata.location}</span>
          )}
          {seller.metadata.rate && (
            <span>Rate: {seller.metadata.rate}</span>
          )}
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
          onClick={onApprove}
          disabled={!!decision}
          className="bg-emerald-600 hover:bg-emerald-700"
          aria-label={`Approve ${seller.sellerName}`}
        >
          Approve
        </Button>
        <Button
          type="button"
          size="sm"
          variant="outline"
          className="border-destructive/50 text-destructive hover:bg-destructive/10"
          onClick={onReject}
          disabled={!!decision}
          aria-label={`Reject ${seller.sellerName}`}
        >
          Reject
        </Button>
        <Button
          type="button"
          size="icon"
          variant="outline"
          onClick={onMessage}
          aria-label={`Message ${seller.sellerName}`}
        >
          <MessageCircle className="h-4 w-4" aria-hidden />
        </Button>
      </footer>
    </article>
  );
}
