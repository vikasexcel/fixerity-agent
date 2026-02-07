'use client';

import { useState, useEffect } from 'react';
import { Zap, Check, Star } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { matchJobToProviders, matchJobToProvidersWithNegotiation } from '@/lib/agent-api';
import { useAuth, getAccessToken } from '@/lib/auth-context';
import type { Job, Deal } from '@/lib/dummy-data';

interface AgentRunnerProps {
  job: Job;
  onComplete: (deals: Deal[]) => void;
  onClose: () => void;
  /** When true, uses negotiate-and-match API (buyer-seller negotiation on price and completion time). */
  useNegotiation?: boolean;
}

interface AgentStatus {
  phase: 'initializing' | 'scanning' | 'evaluating' | 'ranking' | 'complete' | 'error';
  agentsScanned: number;
  currentAgent?: string;
  progress: number;
  error?: string;
}

export function AgentRunner({ job, onComplete, onClose, useNegotiation = false }: AgentRunnerProps) {
  const { session } = useAuth();
  const [status, setStatus] = useState<AgentStatus>({
    phase: 'initializing',
    agentsScanned: 0,
    progress: 0,
  });

  const [matches, setMatches] = useState<Deal[]>([]);
  const [isRunning, setIsRunning] = useState(true);

  useEffect(() => {
    if (!isRunning || !session.user) return;

    const runAgent = async () => {
      const user = session.user;
      const token = getAccessToken();
      if (!user || !token) {
        setStatus((prev) => ({
          ...prev,
          phase: 'error',
          error: 'Please sign in to run the agent.',
        }));
        setIsRunning(false);
        return;
      }

      setStatus((prev) => ({
        ...prev,
        phase: useNegotiation ? 'evaluating' : 'scanning',
        progress: 20,
      }));

      try {
        const deals = useNegotiation
          ? await matchJobToProvidersWithNegotiation(job, Number(user.id), token)
          : await matchJobToProviders(job, Number(user.id), token);
        setStatus((prev) => ({ ...prev, phase: 'complete', progress: 100 }));
        setMatches(deals);
        onComplete(deals);
      } catch (err) {
        setStatus((prev) => ({
          ...prev,
          phase: 'error',
          error: err instanceof Error ? err.message : 'Matching failed. Please try again.',
        }));
      } finally {
        setIsRunning(false);
      }
    };

    runAgent();
  }, [job.id, session.user, useNegotiation]);

  const phases: Record<AgentStatus['phase'], string> = {
    initializing: 'Initializing buyer agent...',
    scanning: 'Scanning available providers...',
    evaluating: useNegotiation ? 'Negotiating with providers...' : 'Evaluating capabilities...',
    ranking: 'Ranking and matching...',
    complete: 'Matching complete!',
    error: status.error ?? 'Error',
  };

  return (
    <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div className="bg-card rounded-xl border border-border max-w-2xl w-full p-6">
        {/* Header */}
        <div className="flex items-center gap-3 mb-6">
          <div className={`p-2 rounded-lg ${status.phase === 'complete' ? 'bg-accent' : 'bg-primary'}`}>
            <Zap size={24} className={status.phase === 'complete' ? 'text-accent-foreground' : 'text-primary-foreground'} />
          </div>
          <div>
            <h2 className="text-2xl font-bold text-foreground">Buyer Agent Running</h2>
            <p className="text-sm text-muted-foreground">{job.title}</p>
          </div>
        </div>

        {/* Current Status */}
        <div className="bg-secondary/30 rounded-lg p-4 mb-6">
          <div className="flex items-center gap-3 mb-2">
            {(status.phase === 'complete' || status.phase === 'error') && (
              <Check size={20} className={status.phase === 'complete' ? 'text-accent' : 'text-destructive'} />
            )}
            <p className="font-semibold text-foreground">{phases[status.phase]}</p>
          </div>
          {status.phase === 'error' && status.error && (
            <p className="text-sm text-destructive mt-2">{status.error}</p>
          )}
        </div>

        {/* Progress Bar */}
        <div className="mb-6">
          <div className="flex justify-between items-center mb-2">
            <span className="text-sm font-medium text-foreground">Progress</span>
            <span className="text-sm text-muted-foreground">{status.progress}%</span>
          </div>
          <div className="w-full bg-secondary rounded-full h-2 overflow-hidden">
            <div
              className={`h-full transition-all duration-300 ${
                status.phase === 'complete' ? 'bg-accent' : 'bg-primary'
              }`}
              style={{ width: `${status.progress}%` }}
            />
          </div>
        </div>

        {/* Timeline */}
        <div className="space-y-3 mb-6">
          {(['initializing', 'scanning', 'evaluating', 'ranking', 'complete'] as const).map((phase, idx) => {
            const phases_order = ['initializing', 'scanning', 'evaluating', 'ranking', 'complete'];
            const phaseIndex = phases_order.indexOf(phase);
            const statusIndex = phases_order.indexOf(status.phase);
            const isComplete = phaseIndex < statusIndex;
            const isCurrent = phase === status.phase;

            return (
              <div key={phase} className="flex items-center gap-3">
                <div
                  className={`w-6 h-6 rounded-full flex items-center justify-center text-xs font-semibold transition ${
                    isComplete
                      ? 'bg-accent text-accent-foreground'
                      : isCurrent
                        ? 'bg-primary text-primary-foreground'
                        : 'bg-muted text-muted-foreground'
                  }`}
                >
                  {isComplete ? <Check size={14} /> : idx + 1}
                </div>
                <span
                  className={`text-sm transition ${
                    isComplete || isCurrent ? 'text-foreground font-medium' : 'text-muted-foreground'
                  }`}
                >
                  {phase === 'initializing' && 'Initialize Agent'}
                  {phase === 'scanning' && 'Scan Available Agents'}
                  {phase === 'evaluating' && 'Evaluate Capabilities'}
                  {phase === 'ranking' && 'Rank Matches'}
                  {phase === 'complete' && 'Complete'}
                </span>
              </div>
            );
          })}
        </div>

        {/* Results Preview */}
        {status.phase === 'complete' && matches.length > 0 && (
          <div className="bg-secondary/30 rounded-lg p-4 mb-6">
            <p className="text-sm font-semibold text-foreground mb-3">Top 5 Matches Found</p>
            <div className="space-y-2">
              {matches.map((match, idx) => (
                <div key={match.id} className="flex items-center justify-between">
                  <div className="flex items-center gap-2">
                    <span className="text-sm font-semibold text-primary">{idx + 1}.</span>
                    <div>
                      <p className="text-sm font-medium text-foreground">{match.sellerAgent.name}</p>
                      <p className="text-xs text-muted-foreground flex items-center gap-1">
                        <Star size={12} className="fill-muted-foreground text-muted-foreground" />
                        {match.sellerAgent.rating} • {match.sellerAgent.jobsCompleted} jobs
                      </p>
                    </div>
                  </div>
                  <div className="text-right">
                    <p className="font-semibold text-lg text-accent">{match.matchScore}%</p>
                    <p className="text-xs text-muted-foreground">match</p>
                    {(match.negotiatedPrice != null || match.negotiatedCompletionDays != null) && (
                      <p className="text-xs text-foreground mt-1">
                        {match.negotiatedPrice != null && `$${match.negotiatedPrice}`}
                        {match.negotiatedPrice != null && match.negotiatedCompletionDays != null && ' · '}
                        {match.negotiatedCompletionDays != null && `${match.negotiatedCompletionDays}d`}
                      </p>
                    )}
                  </div>
                </div>
              ))}
            </div>
          </div>
        )}

        {/* Action Buttons */}
        <div className="flex gap-3">
          <Button
            onClick={onClose}
            variant="outline"
            className="flex-1 bg-transparent"
            disabled={isRunning}
          >
            {status.phase === 'error' ? 'Close' : 'Back'}
          </Button>
          {status.phase !== 'error' && (
            <Button
              onClick={() => {
                onComplete(matches);
                onClose();
              }}
              className="flex-1 bg-primary hover:bg-primary/90 text-primary-foreground"
              disabled={!matches.length || isRunning}
            >
              View Matches
            </Button>
          )}
        </div>
      </div>
    </div>
  );
}
