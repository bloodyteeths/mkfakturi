<?php

namespace App\Http\Controllers\V1\Partner;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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

        // Base validation
        $rules = [
            'operation_type' => 'required|string|in:daily_close,vat_return,trial_balance_export,period_lock,journal_export,balance_sheet_export,income_statement_export',
            'company_ids' => 'required|array|min:1',
            'company_ids.*' => 'required|integer',
            'parameters' => 'required|array',
            'parameters.notes' => 'nullable|string|max:1000',
        ];

        // Per-operation required parameter rules
        $opType = $request->input('operation_type');
        $paramRules = match ($opType) {
            'daily_close' => [
                'parameters.date' => 'required|date',
                'parameters.type' => 'nullable|string|in:all,cash,invoices',
            ],
            'vat_return' => [
                'parameters.year' => 'required|integer|min:2020|max:2100',
                'parameters.month' => 'required|integer|min:1|max:12',
            ],
            'trial_balance_export', 'journal_export' => [
                'parameters.date_from' => 'required|date',
                'parameters.date_to' => 'required|date|after_or_equal:parameters.date_from',
                'parameters.format' => 'required|string|in:csv,json',
                'parameters.report_type' => 'nullable|string|in:trial_balance,general_ledger,journal_entries',
            ],
            'balance_sheet_export', 'income_statement_export' => [
                'parameters.as_of_date' => 'required|date',
                'parameters.format' => 'required|string|in:csv,json',
            ],
            'period_lock' => [
                'parameters.period_start' => 'required|date',
                'parameters.period_end' => 'required|date|after_or_equal:parameters.period_start',
            ],
            default => [],
        };

        $validated = $request->validate(array_merge($rules, $paramRules));

        try {
            $batchJob = $this->batchService->createJob(
                $partner,
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
     * Download a file generated by a batch job result.
     */
    public function download(Request $request, int $id, int $companyId): \Symfony\Component\HttpFoundation\StreamedResponse|JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);

        if (!$partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }

        try {
            $batchJob = $this->batchService->getJob($partner->id, $id);

            // Find the result for this company (cast to int for safe comparison)
            $result = collect($batchJob->results ?? [])
                ->first(fn($r) => (int) ($r['company_id'] ?? 0) === $companyId);

            if (!$result || empty($result['file_path'])) {
                return response()->json(['success' => false, 'message' => 'No file available for this company.'], 404);
            }

            $filePath = $result['file_path'];
            $disk = Storage::disk('local');

            if (!$disk->exists($filePath)) {
                \Log::warning('BatchDownload: file not found', [
                    'batch_job_id' => $id,
                    'company_id' => $companyId,
                    'file_path' => $filePath,
                ]);
                return response()->json(['success' => false, 'message' => 'File not found on storage.'], 404);
            }

            $filename = basename($filePath);

            return $disk->download($filePath, $filename);
        } catch (\Exception $e) {
            \Log::error('BatchDownload error', [
                'batch_job_id' => $id,
                'company_id' => $companyId,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['success' => false, 'message' => 'Download failed: ' . $e->getMessage()], 500);
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

}

// CLAUDE-CHECKPOINT
