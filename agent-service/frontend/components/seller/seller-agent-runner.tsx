'use client';

import { useState, useEffect } from 'react';
import { Zap, Check, CheckCircle } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { runSellerAgent } from '@/lib/agent-engine';
import type { Agent, Job } from '@/lib/dummy-data';

interface SellerAgentRunnerProps {
  agent: Agent;
  availableJobs: Job[];
  onClose: () => void;
}

interface AgentStatus {
  phase: 'initializing' | 'scanning' | 'matching' | 'analyzing' | 'complete';
  jobsScanned: number;
  currentJob?: string;
  progress: number;
  topMatches: number;
}

export function SellerAgentRunner({ agent, availableJobs, onClose }: SellerAgentRunnerProps) {
  const [status, setStatus] = useState<AgentStatus>({
    phase: 'initializing',
    jobsScanned: 0,
    progress: 0,
    topMatches: 0,
  });

  const [isRunning, setIsRunning] = useState(true);

  useEffect(() => {
    if (!isRunning) return;

    const runAgent = async () => {
      // Phase 1: Initializing
      await new Promise(resolve => setTimeout(resolve, 600));
      setStatus(prev => ({ ...prev, phase: 'scanning', progress: 15 }));

      // Phase 2: Scanning jobs
      for (let i = 0; i < availableJobs.length; i++) {
        await new Promise(resolve => setTimeout(resolve, 250));
        setStatus(prev => ({
          ...prev,
          jobsScanned: i + 1,
          currentJob: availableJobs[i].title,
          progress: 15 + ((i + 1) / availableJobs.length) * 30,
        }));
      }

      // Phase 3: Matching
      setStatus(prev => ({ ...prev, phase: 'matching', progress: 50 }));
      await new Promise(resolve => setTimeout(resolve, 700));

      // Phase 4: Analyzing results
      setStatus(prev => ({ ...prev, phase: 'analyzing', progress: 80 }));
      const matches = runSellerAgent(agent, availableJobs);
      const topMatches = matches.filter(m => m.score >= 70).length;

      await new Promise(resolve => setTimeout(resolve, 600));

      setStatus(prev => ({
        ...prev,
        phase: 'complete',
        progress: 100,
        topMatches,
      }));

      setIsRunning(false);
    };

    runAgent();
  }, []);

  const phases = {
    initializing: 'Starting seller agent...',
    scanning: `Scanning available jobs (${status.jobsScanned}/${availableJobs.length})`,
    matching: 'Matching your profile to opportunities...',
    analyzing: 'Analyzing match quality...',
    complete: 'Analysis Complete!',
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
            <h2 className="text-2xl font-bold text-foreground">Seller Agent Scanning</h2>
            <p className="text-sm text-muted-foreground">{agent.name}</p>
          </div>
        </div>

        {/* Current Status */}
        <div className="bg-secondary/30 rounded-lg p-4 mb-6">
          <div className="flex items-center gap-3 mb-2">
            {status.phase === 'complete' && (
              <CheckCircle size={20} className="text-accent" />
            )}
            <p className="font-semibold text-foreground">{phases[status.phase]}</p>
          </div>
          {status.currentJob && status.phase !== 'complete' && (
            <p className="text-sm text-muted-foreground ml-7">Current job: {status.currentJob}</p>
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
          {(['initializing', 'scanning', 'matching', 'analyzing', 'complete'] as const).map((phase, idx) => {
            const phases_order = ['initializing', 'scanning', 'matching', 'analyzing', 'complete'];
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
                  {phase === 'scanning' && 'Scan Available Jobs'}
                  {phase === 'matching' && 'Match Profile'}
                  {phase === 'analyzing' && 'Analyze Results'}
                  {phase === 'complete' && 'Complete'}
                </span>
              </div>
            );
          })}
        </div>

        {/* Results Summary */}
        {status.phase === 'complete' && (
          <div className="bg-secondary/30 rounded-lg p-4 mb-6">
            <div className="grid grid-cols-3 gap-4">
              <div className="text-center">
                <p className="text-2xl font-bold text-accent">{status.topMatches}</p>
                <p className="text-xs text-muted-foreground mt-1">Great Matches (70%+)</p>
              </div>
              <div className="text-center">
                <p className="text-2xl font-bold text-primary">{availableJobs.length}</p>
                <p className="text-xs text-muted-foreground mt-1">Total Opportunities</p>
              </div>
              <div className="text-center">
                <p className="text-2xl font-bold text-foreground">
                  {availableJobs.length > 0 ? Math.round((status.topMatches / availableJobs.length) * 100) : 0}%
                </p>
                <p className="text-xs text-muted-foreground mt-1">Match Rate</p>
              </div>
            </div>
          </div>
        )}

        {/* Help Text */}
        <div className="bg-primary/10 border border-primary/20 rounded-lg p-3 mb-6">
          <p className="text-xs text-foreground">
            <span className="font-semibold">How it works:</span> Your seller agent continuously scans for new job opportunities that match your profile, skills, and experience. When jobs are posted with requirements you can fulfill, you're notified with a match score.
          </p>
        </div>

        {/* Action Button */}
        <Button
          onClick={onClose}
          className="w-full bg-primary hover:bg-primary/90 text-primary-foreground"
          disabled={isRunning}
        >
          {isRunning ? 'Scanning...' : 'View Matched Opportunities'}
        </Button>
      </div>
    </div>
  );
}
