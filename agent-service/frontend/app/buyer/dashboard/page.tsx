'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { dummyJobs, dummyDeals, dummyAgents } from '@/lib/dummy-data';
import { JobCard } from '@/components/buyer/job-card';
import { JobDetailModal } from '@/components/buyer/job-detail-modal';
import { CreateJobModal } from '@/components/buyer/create-job-modal';
import { AgentRunner } from '@/components/buyer/agent-runner';
import { Button } from '@/components/ui/button';
import { getAuthSession } from '@/lib/auth-context';
import type { Job, Deal } from '@/lib/dummy-data';

export default function BuyerDashboard() {
  const router = useRouter();
  const [jobs, setJobs] = useState<Job[]>(dummyJobs);
  const [deals, setDeals] = useState<Deal[]>(dummyDeals);
  const [selectedJob, setSelectedJob] = useState<Job | null>(null);
  const [filter, setFilter] = useState<'open' | 'matched' | 'completed' | 'all'>('open');
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [showAgentRunner, setShowAgentRunner] = useState(false);
  const [runningJob, setRunningJob] = useState<Job | null>(null);
  const user = getAuthSession().user;

  useEffect(() => {
    if (!user || user.role !== 'buyer') {
      router.push('/auth');
    }
  }, [user, router]);

  const handleJobCreate = (newJob: Job) => {
    setJobs([...jobs, newJob]);
    setShowCreateModal(false);
    setRunningJob(newJob);
    setShowAgentRunner(true);
  };

  const handleAgentComplete = (newDeals: Deal[]) => {
    setDeals([...deals, ...newDeals]);
    if (runningJob) {
      setJobs(jobs.map(j => (j.id === runningJob.id ? { ...j, status: 'matched' as const } : j)));
    }
  };

  const filteredJobs = filter === 'all' ? jobs : jobs.filter((j) => j.status === filter);

  return (
    <div className="min-h-screen bg-background">
      {/* Header */}
      <header className="sticky top-0 bg-card border-b border-border z-40">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6">
          <div className="flex justify-between items-center">
            <div>
              <h1 className="text-2xl sm:text-3xl font-bold text-foreground">Buyer Agent Dashboard</h1>
              <p className="text-muted-foreground text-sm mt-1">Welcome back, {user?.name}</p>
            </div>
            <div className="flex items-center gap-3">
              <Button 
                onClick={() => setShowCreateModal(true)}
                className="bg-primary hover:bg-primary/90 text-primary-foreground"
              >
                + Create Job
              </Button>
              <button
                onClick={() => {
                  localStorage.clear();
                  router.push('/auth');
                }}
                className="text-muted-foreground hover:text-foreground text-sm"
              >
                Sign Out
              </button>
            </div>
          </div>
        </div>
      </header>

      {/* Main Content */}
      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Quick Stats */}
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
          <div className="bg-card rounded-lg border border-border p-5">
            <p className="text-muted-foreground text-sm mb-2">Active Jobs</p>
            <p className="text-3xl font-bold text-foreground">
              {jobs.filter((j) => j.status === 'open').length}
            </p>
          </div>
          <div className="bg-card rounded-lg border border-border p-5">
            <p className="text-muted-foreground text-sm mb-2">Matched Deals</p>
            <p className="text-3xl font-bold text-primary">
              {deals.filter((d) => d.status === 'proposed').length}
            </p>
          </div>
          <div className="bg-card rounded-lg border border-border p-5">
            <p className="text-muted-foreground text-sm mb-2">Avg Match Score</p>
            <p className="text-3xl font-bold text-accent">
              {deals.length > 0 ? Math.round(deals.reduce((acc, d) => acc + d.matchScore, 0) / deals.length) : 0}%
            </p>
          </div>
          <div className="bg-card rounded-lg border border-border p-5">
            <p className="text-muted-foreground text-sm mb-2">Completed Jobs</p>
            <p className="text-3xl font-bold text-foreground">
              {jobs.filter((j) => j.status === 'completed').length}
            </p>
          </div>
        </div>

        {/* Filter Tabs */}
        <div className="flex gap-3 mb-6 overflow-x-auto pb-2">
          {(['open', 'matched', 'completed', 'all'] as const).map((status) => (
            <button
              key={status}
              onClick={() => setFilter(status)}
              className={`px-4 py-2 rounded-lg font-medium whitespace-nowrap transition-colors ${
                filter === status
                  ? 'bg-primary text-primary-foreground'
                  : 'bg-secondary text-secondary-foreground hover:bg-accent'
              }`}
            >
              {status.charAt(0).toUpperCase() + status.slice(1)} Jobs
            </button>
          ))}
        </div>

        {/* Jobs List */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          {filteredJobs.length > 0 ? (
            filteredJobs.map((job) => (
              <JobCard key={job.id} job={job} onView={setSelectedJob} />
            ))
          ) : (
            <div className="lg:col-span-2 text-center py-12">
              <p className="text-muted-foreground text-lg">No jobs in this category</p>
              <Button 
                onClick={() => setShowCreateModal(true)}
                className="mt-4 bg-primary hover:bg-primary/90 text-primary-foreground"
              >
                Create New Job
              </Button>
            </div>
          )}
        </div>
      </main>

      {/* Job Detail Modal */}
      {selectedJob && (
        <JobDetailModal 
          job={selectedJob} 
          deals={deals.filter(d => d.jobId === selectedJob.id)} 
          onClose={() => setSelectedJob(null)} 
        />
      )}

      {/* Create Job Modal */}
      {showCreateModal && (
        <CreateJobModal 
          onClose={() => setShowCreateModal(false)}
          onJobCreate={handleJobCreate}
        />
      )}

      {/* Agent Runner */}
      {showAgentRunner && runningJob && (
        <AgentRunner 
          job={runningJob}
          onComplete={handleAgentComplete}
          onClose={() => {
            setShowAgentRunner(false);
            setRunningJob(null);
          }}
        />
      )}
    </div>
  );
}
