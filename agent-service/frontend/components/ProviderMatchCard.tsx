'use client';

import { useState } from 'react';
import { Check, X, MessageSquare, ChevronDown, ChevronUp, Star, Briefcase, DollarSign, Clock } from 'lucide-react';
import { Button } from '@/components/ui/button';
import type { Deal } from '@/lib/dummy-data';

interface ProviderMatchCardProps {
  deal: Deal;
  rank: number;
  onApprove: (deal: Deal) => void;
  onReject: (deal: Deal) => void;
  onContact: (deal: Deal) => void;
}

export function ProviderMatchCard({
  deal,
  rank,
  onApprove,
  onReject,
  onContact,
}: ProviderMatchCardProps) {
  const [showDetails, setShowDetails] = useState(false);

  const quote = deal.quote;
  const sellerName = (deal.sellerName ?? (deal as any).name)?.trim() || `Provider ${rank}`;

  const rating = (deal as any).provider?.average_rating || 4.5;
  const jobsCompleted = (deal as any).provider?.total_completed_order || 0;
  const sellerEmail = deal.sellerEmail ?? (deal as any).email ?? null;
  const sellerContactNumber = deal.sellerContactNumber ?? (deal as any).contactNumber ?? null;
  const hasContactDetails = !!sellerEmail || !!sellerContactNumber;

  return (
    <div className="bg-card border border-border rounded-lg overflow-hidden hover:shadow-md transition-shadow">
      {/* Main Card Header */}
      <div className="p-4">
        <div className="flex items-start justify-between gap-3">
          {/* Rank Badge */}
          <div className="flex items-center gap-3">
            <div className="flex items-center justify-center w-8 h-8 rounded-full bg-primary/10 text-primary font-bold text-sm shrink-0">
              {rank}
            </div>
            
            {/* Provider Info */}
            <div className="flex-1 min-w-0">
              <h3 className="font-semibold text-foreground text-base truncate">
                {sellerName}
              </h3>
              
              {/* Price and Duration */}
              <div className="flex items-center gap-3 mt-1 text-sm">
                <span className="flex items-center gap-1 text-primary font-medium">
                  <DollarSign size={14} />
                  {quote.price}
                </span>
                <span className="text-muted-foreground">•</span>
                <span className="flex items-center gap-1 text-muted-foreground">
                  <Clock size={14} />
                  {quote.days} day{quote.days !== 1 ? 's' : ''}
                </span>
              </div>

              {/* Rating and Jobs */}
              <div className="flex items-center gap-3 mt-1.5 text-xs text-muted-foreground">
                <span className="flex items-center gap-1">
                  <Star size={12} className="fill-yellow-400 text-yellow-400" />
                  {rating.toFixed(1)}/5
                </span>
                <span className="flex items-center gap-1">
                  <Briefcase size={12} />
                  {jobsCompleted} jobs
                </span>
              </div>
            </div>
          </div>

          {/* Action Buttons */}
          <div className="flex items-center gap-1 shrink-0">
            <Button
              size="icon"
              variant="ghost"
              className="h-8 w-8 text-green-600 hover:text-green-700 hover:bg-green-50"
              onClick={() => onApprove(deal)}
              title="Approve"
            >
              <Check size={18} />
            </Button>
            <Button
              size="icon"
              variant="ghost"
              className="h-8 w-8 text-red-600 hover:text-red-700 hover:bg-red-50"
              onClick={() => onReject(deal)}
              title="Reject"
            >
              <X size={18} />
            </Button>
            <Button
              size="icon"
              variant="ghost"
              className="h-8 w-8 text-blue-600 hover:text-blue-700 hover:bg-blue-50"
              onClick={() => onContact(deal)}
              title="Contact"
            >
              <MessageSquare size={18} />
            </Button>
          </div>
        </div>

        {/* View Details Toggle */}
        <button
          onClick={() => setShowDetails(!showDetails)}
          className="mt-3 text-xs text-primary hover:text-primary/80 flex items-center gap-1 transition-colors"
        >
          {showDetails ? (
            <>
              <ChevronUp size={14} />
              Hide Details
            </>
          ) : (
            <>
              <ChevronDown size={14} />
              View Details
            </>
          )}
        </button>
      </div>

      {/* Expandable Details Section */}
      {showDetails && (
        <div className="px-4 pb-4 pt-0 border-t border-border/50 bg-secondary/20">
          <div className="space-y-2 text-sm mt-3">
            {/* Payment Schedule */}
            {quote.paymentSchedule && (
              <div>
                <span className="text-muted-foreground">Payment:</span>{' '}
                <span className="text-foreground">{quote.paymentSchedule}</span>
              </div>
            )}

            {/* Licensed Status */}
            {quote.licensed !== undefined && (
              <div>
                <span className="text-muted-foreground">Licensed:</span>{' '}
                <span className={quote.licensed ? 'text-green-600' : 'text-muted-foreground'}>
                  {quote.licensed ? '✓ Yes' : '✗ No'}
                </span>
              </div>
            )}

            {/* References */}
            {quote.referencesAvailable !== undefined && (
              <div>
                <span className="text-muted-foreground">References:</span>{' '}
                <span className={quote.referencesAvailable ? 'text-green-600' : 'text-muted-foreground'}>
                  {quote.referencesAvailable ? '✓ Available' : '✗ Not available'}
                </span>
              </div>
            )}

            {/* Can Meet Dates */}
            {quote.can_meet_dates !== undefined && (
              <div>
                <span className="text-muted-foreground">Can meet dates:</span>{' '}
                <span className={quote.can_meet_dates ? 'text-green-600' : 'text-orange-600'}>
                  {quote.can_meet_dates ? '✓ Yes' : '⚠ No'}
                </span>
              </div>
            )}

            {/* Match Score */}
            {deal.matchScore !== undefined && (
              <div>
                <span className="text-muted-foreground">Match Score:</span>{' '}
                <span className="text-foreground font-medium">{deal.matchScore}/100</span>
              </div>
            )}

            {/* Provider contact details (email, contact number) */}
            {hasContactDetails && (
              <div className="mt-3 pt-3 border-t border-border/30">
                <p className="text-xs text-muted-foreground mb-2">Provider details</p>
                <div className="space-y-1.5 text-sm text-foreground">
                  {sellerEmail && (
                    <div>
                      <span className="text-muted-foreground">Email:</span>{' '}
                      <a href={`mailto:${sellerEmail}`} className="text-primary hover:underline">
                        {sellerEmail}
                      </a>
                    </div>
                  )}
                  {sellerContactNumber && (
                    <div>
                      <span className="text-muted-foreground">Contact:</span>{' '}
                      <a href={`tel:${sellerContactNumber}`} className="text-primary hover:underline">
                        {sellerContactNumber}
                      </a>
                    </div>
                  )}
                </div>
              </div>
            )}
          </div>
        </div>
      )}
    </div>
  );
}