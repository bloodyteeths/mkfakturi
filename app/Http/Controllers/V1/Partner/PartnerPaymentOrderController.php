<?php

namespace App\Http\Controllers\V1\Partner;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Partner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Mk\Models\PaymentBatch;
use Modules\Mk\Services\PaymentOrderService;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Partner Payment Order Controller
 *
 * Provides payment order (Налог за плаќање) management for
 * partner accountants accessing their client companies.
 */
class PartnerPaymentOrderController extends Controller
{
    protected PaymentOrderService $service;

    public function __construct(PaymentOrderService $service)
    {
        $this->service = $service;
    }

    /**
     * List payment batches for a client company.
     */
    public function index(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner || ! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'Access denied'], 403);
        }

        $query = PaymentBatch::forCompany($company)
            ->with(['createdBy:id,name', 'approvedBy:id,name'])
            ->orderByDesc('batch_date');

        if ($request->filled('status')) {
            $query->byStatus($request->input('status'));
        }

        if ($request->filled('from_date')) {
            $query->where('batch_date', '>=', $request->input('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->where('batch_date', '<=', $request->input('to_date'));
        }

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
     * Show a single batch with items.
     */
    public function show(Request $request, int $company, int $id): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner || ! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'Access denied'], 403);
        }

        $batch = PaymentBatch::forCompany($company)
            ->with(['items.bill:id,bill_number,due_date,total', 'createdBy:id,name', 'approvedBy:id,name', 'bankAccount:id,account_name,iban'])
            ->where('id', $id)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $batch,
        ]);
    }

    /**
     * Get payable (unpaid) bills for a client company.
     */
    public function payableBills(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner || ! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'Access denied'], 403);
        }

        $filters = $request->only(['supplier_id', 'due_before', 'due_after', 'min_amount', 'max_amount']);
        $bills = $this->service->getPayableBills($company, $filters);

        return response()->json([
            'success' => true,
            'data' => $bills,
        ]);
    }

    /**
     * Create a payment batch for a client company.
     */
    public function store(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner || ! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'Access denied'], 403);
        }

        $request->validate([
            'batch_date' => 'required|date',
            'format' => 'required|in:pp30,pp50,sepa_sct,csv',
            'bank_account_id' => 'nullable|integer',
            'notes' => 'nullable|string|max:2000',
            'bill_ids' => 'required|array|min:1',
            'bill_ids.*' => 'integer|exists:bills,id',
        ]);

        $data = $request->only(['batch_date', 'format', 'bank_account_id', 'notes', 'bill_ids']);
        $data['created_by'] = $request->user()?->id;

        $batch = $this->service->createBatch($company, $data);

        return response()->json([
            'success' => true,
            'message' => 'Payment order created',
            'data' => $batch,
        ], 201);
    }

    /**
     * Approve a payment batch.
     */
    public function approve(Request $request, int $company, int $id): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner || ! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'Access denied'], 403);
        }

        $batch = PaymentBatch::forCompany($company)
            ->where('id', $id)
            ->firstOrFail();

        try {
            $batch = $this->service->approve($batch, $request->user()?->id ?? 0);
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
     * Export payment batch file.
     */
    public function export(Request $request, int $company, int $id): StreamedResponse|JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner || ! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'Access denied'], 403);
        }

        $batch = PaymentBatch::forCompany($company)
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
     * Confirm payment batch (creates bill payments).
     */
    public function confirm(Request $request, int $company, int $id): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner || ! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'Access denied'], 403);
        }

        $batch = PaymentBatch::forCompany($company)
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
    public function cancel(Request $request, int $company, int $id): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner || ! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'Access denied'], 403);
        }

        $batch = PaymentBatch::forCompany($company)
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
     * Get overdue bills summary for a client company.
     */
    public function overdueSummary(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner || ! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'Access denied'], 403);
        }

        $summary = $this->service->getOverdueSummary($company);

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    // ----- Partner Access Helpers -----

    /**
     * Get partner from authenticated request.
     * For super admin, returns a "fake" partner object to allow access.
     */
    protected function getPartnerFromRequest(Request $request): ?Partner
    {
        $user = $request->user();
        if (! $user) {
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
