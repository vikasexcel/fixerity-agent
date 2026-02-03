/**
 * Laravel jobs API client for buyer jobs.
 */

import { apiPost } from './api';
import type { Job } from './dummy-data';

type ApiJob = {
  id: string;
  buyerId: string;
  title: string;
  description: string;
  budget: { min: number; max: number };
  startDate: string;
  endDate: string;
  priorities: Array<{ type: string; level: string; value?: string | number; description: string }>;
  createdAt: string;
  status: 'open' | 'matched' | 'completed';
  service_category_id?: number;
  sub_category_id?: number;
  lat?: number;
  long?: number;
};

type CreateJobPayload = {
  user_id: number;
  access_token: string;
  title: string;
  description?: string;
  budget_min?: number;
  budget_max?: number;
  start_date?: string;
  end_date?: string;
  service_category_id?: number;
  sub_category_id?: number;
  lat?: number;
  long?: number;
  priorities?: Array<{ type: string; level: string; value?: string | number; description: string }>;
};

function mapApiJobToJob(apiJob: ApiJob): Job {
  return {
    id: apiJob.id,
    buyerId: apiJob.buyerId,
    title: apiJob.title,
    description: apiJob.description,
    budget: apiJob.budget,
    startDate: apiJob.startDate,
    endDate: apiJob.endDate,
    priorities: apiJob.priorities,
    createdAt: apiJob.createdAt,
    status: apiJob.status,
    service_category_id: apiJob.service_category_id,
    sub_category_id: apiJob.sub_category_id,
    lat: apiJob.lat,
    long: apiJob.long,
  };
}

export async function createJob(
  userId: number,
  accessToken: string,
  payload: Omit<CreateJobPayload, 'user_id' | 'access_token'>
): Promise<Job> {
  const data = await apiPost<{ status: number; job: ApiJob }>('customer/on-demand/job/create', {
    user_id: userId,
    access_token: accessToken,
    ...payload,
  });
  if (data.status !== 1 || !data.job) {
    throw new Error('Failed to create job');
  }
  return mapApiJobToJob(data.job);
}

export async function listJobs(
  userId: number,
  accessToken: string,
  status?: 'open' | 'matched' | 'completed' | 'all'
): Promise<Job[]> {
  const data = await apiPost<{ status: number; jobs: ApiJob[] }>('customer/on-demand/job/list', {
    user_id: userId,
    access_token: accessToken,
    ...(status && { status }),
  });
  if (data.status !== 1) {
    throw new Error('Failed to list jobs');
  }
  return (data.jobs ?? []).map(mapApiJobToJob);
}

export async function updateJobStatus(
  userId: number,
  accessToken: string,
  jobId: number,
  status: 'open' | 'matched' | 'completed'
): Promise<void> {
  const data = await apiPost<{ status: number }>('customer/on-demand/job/update-status', {
    user_id: userId,
    access_token: accessToken,
    job_id: jobId,
    status,
  });
  if (data.status !== 1) {
    throw new Error('Failed to update job status');
  }
}
