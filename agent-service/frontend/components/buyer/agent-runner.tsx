'use client';

import { useState, useEffect } from 'react';
import { Zap, Check, Star } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { runBuyerAgent, generateMatchExplanation } from '@/lib/agent-engine';
import { dummyAgents } from '@/lib/dummy-data';
import type { Job, Deal } from '@/lib/dummy-data';

interface AgentRunnerProps {
  job: Job;
  onComplete: (deals: Deal[]) => void;
  onClose: () => void;
}

interface AgentStatus {
  phase: 'initializing' | 'scanning' | 'evaluating' | 'ranking' | 'complete';
  agentsScanned: number;
  currentAgent?: string;
  progress: number;
}

export function AgentRunner({ job, onComplete, onClose }: AgentRunnerProps) {
  const [status, setStatus] = useState<AgentStatus>({
    phase: 'initializing',
    agentsScanned: 0,
    progress: 0,
  });

  const [matches, setMatches] = useState<any[]>([]);
  const [isRunning, setIsRunning] = useState(true);

  useEffect(() => {
    if (!isRunning) return;

    const runAgent = async () => {
      // Phase 1: Initializing
      await new Promise(resolve => setTimeout(resolve, 800));
      setStatus(prev => ({ ...prev, phase: 'scanning', progress: 15 }));

      // Phase 2: Scanning agents
      const agents = [...dummyAgents].filter(a => a.type === 'seller');
      for (let i = 0; i < agents.length; i++) {
        await new Promise(resolve => setTimeout(resolve, 300));
        setStatus(prev => ({
          ...prev,
          agentsScanned: i + 1,
          currentAgent: agents[i].name,
          progress: 15 + ((i + 1) / agents.length) * 30,
        }));
      }

      // Phase 3: Evaluating
      setStatus(prev => ({ ...prev, phase: 'evaluating', progress: 50 }));
      await new Promise(resolve => setTimeout(resolve, 800));

      // Phase 4: Ranking and matching
      setStatus(prev => ({ ...prev, phase: 'ranking', progress: 80 }));
      await new Promise(resolve => setTimeout(resolve, 600));

      // Get matches
      const matchResults = runBuyerAgent(job, dummyAgents);
      const deals: Deal[] = matchResults.map((result, idx) => ({
        id: `deal_${job.id}_${idx}`,
        jobId: job.id,
        sellerId: result.agent.userId,
        sellerAgent: result.agent,
        matchScore: Math.round(result.score),
        matchReasons: result.reasons,
        status: 'proposed' as const,
        createdAt: new Date().toISOString().split('T')[0],
      }));

      setMatches(deals);
      setStatus(prev => ({ ...prev, phase: 'complete', progress: 100 }));
      await new Promise(resolve => setTimeout(resolve, 500));

      setIsRunning(false);
      onComplete(deals);
    };

    runAgent();
  }, []);

  const phases = {
    initializing: 'Initializing buyer agent...',
    scanning: `Scanning available agents (${status.agentsScanned}/${dummyAgents.filter(a => a.type === 'seller').length})`,
    evaluating: 'Evaluating agent capabilities...',
    ranking: 'Ranking and matching...',
    complete: 'Matching complete!',
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
            {status.phase === 'complete' && (
              <Check size={20} className="text-accent" />
            )}
            <p className="font-semibold text-foreground">{phases[status.phase]}</p>
          </div>
          {status.currentAgent && status.phase !== 'complete' && (
            <p className="text-sm text-muted-foreground ml-7">Currently evaluating: {status.currentAgent}</p>
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
            Back
          </Button>
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
        </div>
      </div>
    </div>
  );
}
