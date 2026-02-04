<?php

namespace App\Http\Controllers\Api\OtherService;

use App\Classes\UserClassApi;
use App\Classes\OnDemandClassApi;
use App\Http\Controllers\Controller;
use App\Models\BuyerJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BuyerJobController extends Controller
{
    public function __construct(
        private UserClassApi $userClassapi,
        private OnDemandClassApi $onDemandClassApi
    ) {}

    /**
     * POST customer/on-demand/job/create
     * Create a buyer job.
     */
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|numeric',
            'access_token' => 'required',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'budget_min' => 'nullable|numeric|min:0',
            'budget_max' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'service_category_id' => 'nullable|numeric',
            'sub_category_id' => 'nullable|numeric',
            'lat' => 'nullable|numeric',
            'long' => 'nullable|numeric',
            'priorities' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'message' => $validator->errors()->first(),
                'message_code' => 9,
            ]);
        }

        $user = $this->userClassapi->checkUserAllow($request->get('user_id'), $request->get('access_token'));
        if ($user instanceof \Illuminate\Http\JsonResponse) {
            return $user;
        }

        $job = BuyerJob::create([
            'user_id' => $request->get('user_id'),
            'title' => $request->get('title'),
            'description' => $request->get('description'),
            'budget_min' => $request->get('budget_min'),
            'budget_max' => $request->get('budget_max'),
            'start_date' => $request->get('start_date'),
            'end_date' => $request->get('end_date'),
            'service_category_id' => $request->get('service_category_id'),
            'sub_category_id' => $request->get('sub_category_id'),
            'lat' => $request->get('lat'),
            'long' => $request->get('long'),
            'status' => 'open',
            'priorities' => $request->get('priorities'),
        ]);

        return response()->json([
            'status' => 1,
            'message' => __('user_messages.1'),
            'message_code' => 1,
            'job' => $this->formatJob($job),
        ]);
    }

    /**
     * POST customer/on-demand/job/list
     * List buyer jobs for the authenticated user, or all open jobs if service_category_id is provided (for sellers).
     * Supports both user_id (for buyers) and provider_id (for sellers/providers) authentication.
     */
    public function list(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'nullable|numeric',
            'provider_id' => 'nullable|numeric',
            'access_token' => 'required',
            'status' => 'nullable|in:open,matched,completed,all',
            'service_category_id' => 'nullable|numeric',
            'sub_category_id' => 'nullable|numeric',
            'lat' => 'nullable|numeric',
            'long' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'message' => $validator->errors()->first(),
                'message_code' => 9,
            ]);
        }

        $providerId = $request->get('provider_id');
        $accessToken = $request->get('access_token');

        // Prefer provider auth when provider_id is present (seller/agent scan). Never use user_id when provider_id is sent.
        $userId = $providerId ? null : $request->get('user_id');
        $authenticatedAsProvider = (bool) $providerId;

        if ($providerId) {
            $provider = $this->onDemandClassApi->providerRegisterAllow($providerId, $accessToken);
            if ($provider instanceof \Illuminate\Http\JsonResponse) {
                return $provider;
            }
        } elseif ($userId) {
            $user = $this->userClassapi->checkUserAllow($userId, $accessToken);
            if ($user instanceof \Illuminate\Http\JsonResponse) {
                return $user;
            }
        } else {
            return response()->json([
                'status' => 0,
                'message' => 'Either user_id or provider_id is required',
                'message_code' => 9,
            ]);
        }

        $serviceCategoryId = $request->get('service_category_id');

        // If service_category_id is provided, return all open jobs for that category (for sellers)
        // Otherwise, return jobs for the authenticated user only (for buyers)
        if ($serviceCategoryId) {
            $query = BuyerJob::where('service_category_id', $serviceCategoryId)
                ->where('status', 'open');

            if ($request->has('sub_category_id')) {
                $query->where('sub_category_id', $request->get('sub_category_id'));
            }

            if ($request->has('lat') && $request->has('long')) {
                $query->whereNotNull('lat')->whereNotNull('long');
            }
        } else {
            if ($authenticatedAsProvider) {
                $query = BuyerJob::where('status', 'open');
            } else {
                $query = BuyerJob::where('user_id', $userId);
                $status = $request->get('status', 'all');
                if ($status !== 'all') {
                    $query->where('status', $status);
                }
            }
        }
        
        $jobs = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => 1,
            'message' => __('user_messages.1'),
            'message_code' => 1,
            'jobs' => $jobs->map(fn ($j) => $this->formatJob($j))->values()->all(),
        ]);
    }

    /**
     * Update job status (e.g. after matching).
     */
    public function updateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|numeric',
            'access_token' => 'required',
            'job_id' => 'required|numeric',
            'status' => 'required|in:open,matched,completed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'message' => $validator->errors()->first(),
                'message_code' => 9,
            ]);
        }

        $user = $this->userClassapi->checkUserAllow($request->get('user_id'), $request->get('access_token'));
        if ($user instanceof \Illuminate\Http\JsonResponse) {
            return $user;
        }

        $job = BuyerJob::where('id', $request->get('job_id'))
            ->where('user_id', $request->get('user_id'))
            ->first();

        if (!$job) {
            return response()->json([
                'status' => 0,
                'message' => 'Job not found',
                'message_code' => 9,
            ]);
        }

        $job->update(['status' => $request->get('status')]);

        return response()->json([
            'status' => 1,
            'message' => __('user_messages.1'),
            'message_code' => 1,
            'job' => $this->formatJob($job),
        ]);
    }

    private function formatJob(BuyerJob $job): array
    {
        return [
            'id' => 'job_' . $job->id,
            'buyerId' => (string) $job->user_id,
            'title' => $job->title,
            'description' => $job->description ?? '',
            'budget' => [
                'min' => (float) ($job->budget_min ?? 0),
                'max' => (float) ($job->budget_max ?? 0),
            ],
            'startDate' => $job->start_date,
            'endDate' => $job->end_date,
            'priorities' => $job->priorities ?? [],
            'createdAt' => $job->created_at->format('Y-m-d'),
            'status' => $job->status,
            'service_category_id' => $job->service_category_id,
            'sub_category_id' => $job->sub_category_id,
            'lat' => $job->lat ? (float) $job->lat : null,
            'long' => $job->long ? (float) $job->long : null,
        ];
    }
}
