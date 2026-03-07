<?php

namespace App\Http\Controllers\V1\Partner;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Mk\Services\BatchOperationService;

/**
 * Partner Batch Operation Controller
 *
 * Manages batch operations across multiple companies for partners.
 * Supports daily close, VAT returns, report exports, and period locks.
 */
class PartnerBatchOperationController extends Controller
{
    protected BatchOperationService $batchService;

    public function __construct(BatchOperationService $batchService)
    {
        $this->batchService = $batchService;
    }

    /**
     * List batch jobs for the current partner.
     */
    public function index(Request $request): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);

        if (!$partner) {
            return response()->json([
                'success' => false,
                'message' => 'Partner not found',
            ], 404);
        }

        $filters = [
            'status' => $request->query('status'),
            'operation_type' => $request->query('operation_type'),
            'per_page' => $request->query('per_page', 15),
        ];

        $jobs = $this->batchService->getJobs($partner->id, $filters);

        return response()->json([
            'success' => true,
            'data' => $jobs->items(),
            'meta' => [
                'current_page' => $jobs->currentPage(),
                'last_page' => $jobs->lastPage(),
                'per_page' => $jobs->perPage(),
                'total' => $jobs->total(),
            ],
        ]);
    }

    /**
     * Create a new batch job.
     */
    public function store(Request $request): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);

        if (!$partner) {
            return response()->json([
                'success' => false,
                'message' => 'Partner not found',
            ], 404);
        }

        $validated = $request->validate([
            'operation_type' => 'required|string|in:daily_close,vat_return,trial_balance_export,period_lock,journal_export',
            'company_ids' => 'required|array|min:1',
            'company_ids.*' => 'required|integer',
            'parameters' => 'nullable|array',
            'parameters.date' => 'nullable|date',
            'parameters.year' => 'nullable|integer|min:2020|max:2100',
            'parameters.month' => 'nullable|integer|min:1|max:12',
            'parameters.date_from' => 'nullable|date',
            'parameters.date_to' => 'nullable|date|after_or_equal:parameters.date_from',
            'parameters.period_start' => 'nullable|date',
            'parameters.period_end' => 'nullable|date|after_or_equal:parameters.period_start',
            'parameters.report_type' => 'nullable|string|in:trial_balance,general_ledger,journal_entries',
            'parameters.format' => 'nullable|string|in:csv,json',
            'parameters.type' => 'nullable|string|in:all,cash,invoices',
            'parameters.notes' => 'nullable|string|max:1000',
        ]);

        try {
            $batchJob = $this->batchService->createJob(
                $partner->id,
                $validated['operation_type'],
                $validated['company_ids'],
                $validated['parameters'] ?? []
            );

            return response()->json([
                'success' => true,
                'message' => 'Batch job created and queued.',
                'data' => $batchJob,
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create batch job: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show a single batch job with results.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);

        if (!$partner) {
            return response()->json([
                'success' => false,
                'message' => 'Partner not found',
            ], 404);
        }

        try {
            $batchJob = $this->batchService->getJob($partner->id, $id);

            return response()->json([
                'success' => true,
                'data' => array_merge($batchJob->toArray(), [
                    'progress_percentage' => $batchJob->progress_percentage,
                ]),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Batch job not found.',
            ], 404);
        }
    }

    /**
     * Cancel a queued batch job.
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);

        if (!$partner) {
            return response()->json([
                'success' => false,
                'message' => 'Partner not found',
            ], 404);
        }

        try {
            $batchJob = $this->batchService->cancelJob($partner->id, $id);

            return response()->json([
                'success' => true,
                'message' => 'Batch job cancelled.',
                'data' => $batchJob,
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Batch job not found.',
            ], 404);
        }
    }

    /**
     * Get available operation types.
     */
    public function operations(Request $request): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);

        if (!$partner) {
            return response()->json([
                'success' => false,
                'message' => 'Partner not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->batchService->getAvailableOperations(),
        ]);
    }

    /**
     * Lightweight progress endpoint for polling.
     */
    public function progress(Request $request, int $id): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);

        if (!$partner) {
            return response()->json([
                'success' => false,
                'message' => 'Partner not found',
            ], 404);
        }

        try {
            $batchJob = $this->batchService->getJob($partner->id, $id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $batchJob->id,
                    'status' => $batchJob->status,
                    'total_items' => $batchJob->total_items,
                    'completed_items' => $batchJob->completed_items,
                    'failed_items' => $batchJob->failed_items,
                    'progress_percentage' => $batchJob->progress_percentage,
                    'started_at' => $batchJob->started_at,
                    'completed_at' => $batchJob->completed_at,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Batch job not found.',
            ], 404);
        }
    }

    /**
     * Get partner from authenticated request.
     * For super admin, returns a "fake" partner object to allow access.
     */
    protected function getPartnerFromRequest(Request $request): ?Partner
    {
        $user = $request->user();

        if (!$user) {
            return null;
        }

        // Super admin gets a fake partner to pass validation
        if ($user->role === 'super admin') {
            $fakePartner = new Partner();
            $fakePartner->id = 0;
            $fakePartner->user_id = $user->id;
            $fakePartner->name = 'Super Admin';
            $fakePartner->email = $user->email;
            $fakePartner->is_super_admin = true;
            return $fakePartner;
        }

        return Partner::where('user_id', $user->id)->first();
    }

    /**
     * Check if partner has access to a company.
     * Super admin has access to all companies.
     */
    protected function hasCompanyAccess(Partner $partner, int $companyId): bool
    {
        // Super admin has access to all companies
        if ($partner->is_super_admin ?? false) {
            return true;
        }

        return $partner->companies()
            ->where('companies.id', $companyId)
            ->where('partner_company_links.is_active', true)
            ->exists();
    }
}

// CLAUDE-CHECKPOINT
