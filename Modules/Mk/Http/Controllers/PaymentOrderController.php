<?php

namespace Modules\Mk\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Mk\Models\PaymentBatch;
use Modules\Mk\Services\PaymentOrderService;
use Modules\Mk\Services\Pp30PdfService;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Payment Order Controller
 *
 * Handles payment order (Налог за плаќање) CRUD and workflow
 * for company users via the main app interface.
 */
class PaymentOrderController extends Controller
{
    protected PaymentOrderService $service;

    public function __construct(PaymentOrderService $service)
    {
        $this->service = $service;
    }

    /**
     * Verify the authenticated user has access to the company.
     */
    protected function authorizeCompanyAccess(Request $request): int
    {
        $companyId = (int) $request->header('company');
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated');
        }

        // Super admins can access any company
        if ($user->role === 'super admin') {
            return $companyId;
        }

        // Verify user belongs to this company
        $hasAccess = CompanyUser::where('company_id', $companyId)
            ->where('user_id', $user->id)
            ->exists();

        if (! $hasAccess) {
            // Also check if user is the company owner
            $isOwner = Company::where('id', $companyId)
                ->where('owner_id', $user->id)
                ->exists();

            if (! $isOwner) {
                abort(403, 'Access denied to this company');
            }
        }

        return $companyId;
    }

    /**
     * List payment batches for the company.
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = $this->authorizeCompanyAccess($request);

        $query = PaymentBatch::forCompany($companyId)
            ->with(['createdBy:id,name', 'approvedBy:id,name']);

        if ($request->filled('status')) {
            $query->byStatus($request->input('status'));
        }

        if ($request->filled('format')) {
            $query->where('format', $request->input('format'));
        }

        if ($request->filled('search')) {
            $query->where('batch_number', 'like', '%' . $request->input('search') . '%');
        }

        if ($request->filled('from_date')) {
            $query->where('batch_date', '>=', $request->input('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->where('batch_date', '<=', $request->input('to_date'));
        }

        $sortBy = in_array($request->input('sort_by'), ['batch_number', 'batch_date', 'total_amount', 'status', 'format'])
            ? $request->input('sort_by') : 'batch_date';
        $sortOrder = $request->input('sort_order') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortOrder);

        $limit = $request->input('limit', 15);
        $batches = $limit === 'all'
            ? $query->get()
            : $query->paginate((int) $limit);

        return response()->json([
            'success' => true,
            'data' => $batches,
        ]);
    }

    /**
     * Show a single batch with its items.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $companyId = $this->authorizeCompanyAccess($request);

        $batch = PaymentBatch::forCompany($companyId)
            ->with(['items.bill:id,bill_number,due_date,total', 'createdBy:id,name', 'approvedBy:id,name', 'bankAccount:id,account_name,iban'])
            ->where('id', $id)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $batch,
        ]);
    }

    /**
     * Get payable (unpaid) bills for selection.
     */
    public function payableBills(Request $request): JsonResponse
    {
        $companyId = $this->authorizeCompanyAccess($request);

        $filters = $request->only(['supplier_id', 'due_before', 'due_after', 'min_amount', 'max_amount']);
        $bills = $this->service->getPayableBills($companyId, $filters);

        return response()->json([
            'success' => true,
            'data' => $bills,
        ]);
    }

    /**
     * Create a new payment batch from selected bills.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'batch_date' => 'required|date',
            'format' => 'required|in:pp30,pp50,sepa_sct,csv',
            'urgency' => 'nullable|in:redovno,itno',
            'bank_account_id' => 'nullable|integer',
            'notes' => 'nullable|string|max:2000',
            'bill_ids' => 'required|array|min:1',
            'bill_ids.*' => 'integer|exists:bills,id',
            'payment_code' => 'nullable|string|max:3',
            'tax_number' => 'nullable|string|max:13',
            'revenue_code' => 'nullable|string|max:10',
            'program_code' => 'nullable|string|max:10',
            'municipality_code' => 'nullable|string|max:10',
            'approval_reference' => 'nullable|string|max:50',
        ]);

        $companyId = $this->authorizeCompanyAccess($request);

        $data = $request->only(['batch_date', 'format', 'urgency', 'bank_account_id', 'notes', 'bill_ids',
            'payment_code', 'tax_number', 'revenue_code', 'program_code', 'municipality_code', 'approval_reference']);
        $data['created_by'] = Auth::id();

        try {
            $result = $this->service->createBatch($companyId, $data, autoApprove: true);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        $batch = $result['batch'];
        $skippedBills = $result['skipped_bills'] ?? [];
        $skippedInBatch = $result['skipped_in_batch'] ?? [];

        $response = [
            'success' => true,
            'message' => 'Payment order created',
            'data' => $batch,
        ];

        // Report skipped bills to frontend
        if (! empty($skippedBills)) {
            $response['warnings'] = ['Skipped fully paid bills: '.implode(', ', $skippedBills)];
        }
        if (! empty($skippedInBatch)) {
            $response['warnings'][] = count($skippedInBatch).' bill(s) already in active batches were excluded.';
        }

        return response()->json($response, 201);
    }

    /**
     * Approve a payment batch.
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $companyId = $this->authorizeCompanyAccess($request);

        $batch = PaymentBatch::forCompany($companyId)
            ->where('id', $id)
            ->firstOrFail();

        try {
            $batch = $this->service->approve($batch, Auth::id());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment order approved',
            'data' => $batch,
        ]);
    }

    /**
     * Export payment batch file (download).
     */
    public function export(Request $request, int $id): StreamedResponse|JsonResponse
    {
        $companyId = $this->authorizeCompanyAccess($request);

        $batch = PaymentBatch::forCompany($companyId)
            ->where('id', $id)
            ->firstOrFail();

        try {
            $result = $this->service->export($batch);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->streamDownload(function () use ($result) {
            echo $result['content'];
        }, $result['filename'], [
            'Content-Type' => $result['mime'],
        ]);
    }

    /**
     * Confirm payment batch (creates actual bill payments).
     */
    public function confirm(Request $request, int $id): JsonResponse
    {
        $companyId = $this->authorizeCompanyAccess($request);

        $batch = PaymentBatch::forCompany($companyId)
            ->where('id', $id)
            ->firstOrFail();

        try {
            $batch = $this->service->confirm($batch);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment order confirmed. Bill payments created.',
            'data' => $batch,
        ]);
    }

    /**
     * Cancel a payment batch.
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $companyId = $this->authorizeCompanyAccess($request);

        $batch = PaymentBatch::forCompany($companyId)
            ->where('id', $id)
            ->firstOrFail();

        try {
            $batch = $this->service->cancel($batch);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment order cancelled',
            'data' => $batch,
        ]);
    }

    /**
     * Get overdue bills summary.
     */
    public function overdueSummary(Request $request): JsonResponse
    {
        $companyId = $this->authorizeCompanyAccess($request);
        $summary = $this->service->getOverdueSummary($companyId);

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * Update a draft payment batch.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'batch_date' => 'nullable|date',
            'format' => 'nullable|in:pp30,pp50,sepa_sct,csv',
            'urgency' => 'nullable|in:redovno,itno',
            'bank_account_id' => 'nullable|integer',
            'notes' => 'nullable|string|max:2000',
            'bill_ids' => 'nullable|array|min:1',
            'bill_ids.*' => 'integer|exists:bills,id',
        ]);

        $companyId = $this->authorizeCompanyAccess($request);

        $batch = PaymentBatch::forCompany($companyId)
            ->where('id', $id)
            ->firstOrFail();

        try {
            $batch = $this->service->updateBatch($batch, $request->only([
                'batch_date', 'format', 'urgency', 'bank_account_id', 'notes', 'bill_ids',
            ]));
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'data' => $batch]);
    }

    /**
     * Bulk approve payment batches.
     */
    public function bulkApprove(Request $request): JsonResponse
    {
        $request->validate(['batch_ids' => 'required|array|min:1', 'batch_ids.*' => 'integer']);
        $companyId = $this->authorizeCompanyAccess($request);

        $results = $this->service->bulkApprove($companyId, $request->input('batch_ids'), Auth::id());

        return response()->json(['success' => true, 'data' => $results]);
    }

    /**
     * Bulk export payment batches.
     */
    public function bulkExport(Request $request)
    {
        $request->validate(['batch_ids' => 'required|array|min:1', 'batch_ids.*' => 'integer']);
        $companyId = $this->authorizeCompanyAccess($request);

        try {
            $result = $this->service->bulkExport($companyId, $request->input('batch_ids'));
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->streamDownload(function () use ($result) {
            echo $result['content'];
        }, $result['filename'], [
            'Content-Type' => $result['mime'],
        ]);
    }

    /**
     * Bulk cancel payment batches.
     */
    public function bulkCancel(Request $request): JsonResponse
    {
        $request->validate(['batch_ids' => 'required|array|min:1', 'batch_ids.*' => 'integer']);
        $companyId = $this->authorizeCompanyAccess($request);

        $results = $this->service->bulkCancel($companyId, $request->input('batch_ids'));

        return response()->json(['success' => true, 'data' => $results]);
    }

    /**
     * Duplicate a payment batch.
     */
    public function duplicate(Request $request, int $id): JsonResponse
    {
        $companyId = $this->authorizeCompanyAccess($request);

        $batch = PaymentBatch::forCompany($companyId)
            ->where('id', $id)
            ->firstOrFail();

        try {
            $result = $this->service->duplicateBatch($batch, Auth::id());
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'data' => $result['batch']], 201);
    }

    /**
     * Generate PP30 payment slip PDF for a batch.
     */
    public function pp30Pdf(Request $request, int $id)
    {
        $companyId = $this->authorizeCompanyAccess($request);

        $batch = PaymentBatch::forCompany($companyId)
            ->where('id', $id)
            ->firstOrFail();

        try {
            $pp30Service = app(Pp30PdfService::class);
            $pdf = $pp30Service->generateForBatch($batch);

            return $pdf->download("PP30_{$batch->batch_number}.pdf");
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Generate PP50 payment slip PDF for a batch.
     */
    public function pp50Pdf(Request $request, int $id)
    {
        $companyId = $this->authorizeCompanyAccess($request);

        $batch = PaymentBatch::forCompany($companyId)
            ->where('id', $id)
            ->firstOrFail();

        try {
            $pp30Service = app(Pp30PdfService::class);
            $pdf = $pp30Service->generatePp50ForBatch($batch);

            return $pdf->download("PP50_{$batch->batch_number}.pdf");
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}

// CLAUDE-CHECKPOINT
