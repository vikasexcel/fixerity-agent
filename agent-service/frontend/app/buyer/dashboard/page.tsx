'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { JobCard } from '@/components/buyer/job-card';
import { JobDetailModal, type JobDetailModalMode } from '@/components/buyer/job-detail-modal';
import { CreateJobModal } from '@/components/buyer/create-job-modal';
import { Button } from '@/components/ui/button';
import { useAuth, getAccessToken } from '@/lib/auth-context';
import { listJobs } from '@/lib/jobs-api';
import type { Job, Deal } from '@/lib/dummy-data';

export default function BuyerDashboard() {
  const router = useRouter();
  const { session, logout } = useAuth();
  const user = session.user;
  const [jobs, setJobs] = useState<Job[]>([]);
  const [deals, setDeals] = useState<Deal[]>([]);
  const [selectedJob, setSelectedJob] = useState<Job | null>(null);
  const [detailModalMode, setDetailModalMode] = useState<JobDetailModalMode>('with-recommendations');
  const [filter, setFilter] = useState<'open' | 'matched' | 'completed' | 'all'>('open');
  const [showCreateModal, setShowCreateModal] = useState(false);

  useEffect(() => {
    if (!session.isLoading && (!user || user.role !== 'buyer')) {
      router.push('/auth');
    }
  }, [session.isLoading, user, router]);

  useEffect(() => {
    if (!user || user.role !== 'buyer') return;
    const token = getAccessToken();
    if (!token) return;
    listJobs(Number(user.id), token)
      .then(setJobs)
      .catch(() => setJobs([]));
  }, [user?.id, user?.role]);

  const handleJobCreate = (newJob: Job) => {
    setJobs((prev) => [...prev, newJob]);
    setShowCreateModal(false);
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
                variant="outline"
                onClick={() => router.push('/buyer/unified-chat')}
                className="border-border"
              >
                Chat to find providers
              </Button>
              <Button 
                onClick={() => setShowCreateModal(true)}
                className="bg-primary hover:bg-primary/90 text-primary-foreground"
              >
                + Create Job
              </Button>
              <button
                onClick={() => {
                  logout();
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
              <JobCard
                key={job.id}
                job={job}
                onViewDetails={(j) => {
                  setSelectedJob(j);
                  setDetailModalMode('details-only');
                }}
                onStartAgent={(j) => router.push(`/buyer/jobs/${encodeURIComponent(j.id)}/chat`)}
                onRecommendProviders={(j) => {
                  setSelectedJob(j);
                  setDetailModalMode('with-recommendations');
                }}
              />
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
          mode={detailModalMode}
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
    </div>
  );
}
