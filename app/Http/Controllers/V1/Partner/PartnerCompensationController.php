<?php

namespace App\Http\Controllers\V1\Partner;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Partner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Mk\Models\Compensation;
use Modules\Mk\Services\CompensationService;

class PartnerCompensationController extends Controller
{
    protected CompensationService $service;

    public function __construct(CompensationService $service)
    {
        $this->service = $service;
    }

    /**
     * List compensations for a partner's client company.
     */
    public function index(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $query = Compensation::forCompany($company)
            ->with(['customer:id,name', 'supplier:id,name', 'createdBy:id,name'])
            ->orderBy('compensation_date', 'desc');

        if ($status = $request->query('status')) {
            $query->byStatus($status);
        }

        if ($dateFrom = $request->query('date_from')) {
            $query->where('compensation_date', '>=', $dateFrom);
        }

        if ($dateTo = $request->query('date_to')) {
            $query->where('compensation_date', '<=', $dateTo);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('compensation_number', 'LIKE', '%' . $search . '%')
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'LIKE', '%' . $search . '%');
                  })
                  ->orWhereHas('supplier', function ($sq) use ($search) {
                      $sq->where('name', 'LIKE', '%' . $search . '%');
                  });
            });
        }

        $limit = $request->query('limit', 15);
        if ($limit === 'all') {
            return response()->json([
                'success' => true,
                'data' => $query->get(),
            ]);
        }

        $compensations = $query->paginate((int) $limit);

        return response()->json([
            'success' => true,
            'data' => $compensations->items(),
            'meta' => [
                'current_page' => $compensations->currentPage(),
                'last_page' => $compensations->lastPage(),
                'per_page' => $compensations->perPage(),
                'total' => $compensations->total(),
            ],
        ]);
    }

    /**
     * Show a single compensation with items.
     */
    public function show(Request $request, int $company, int $id): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $compensation = Compensation::forCompany($company)
            ->with(['customer', 'supplier', 'items', 'createdBy:id,name', 'confirmedBy:id,name'])
            ->where('id', $id)
            ->first();

        if (!$compensation) {
            return response()->json(['success' => false, 'message' => 'Compensation not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $compensation,
        ]);
    }

    /**
     * Create a new draft compensation.
     */
    public function store(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $request->validate([
            'counterparty_type' => 'required|in:customer,supplier,both',
            'customer_id' => 'nullable|integer',
            'supplier_id' => 'nullable|integer',
            'compensation_date' => 'required|date',
            'type' => 'required|in:bilateral,unilateral',
            'notes' => 'nullable|string|max:2000',
            'currency_id' => 'nullable|integer',
            'items' => 'required|array|min:1',
            'items.*.side' => 'required|in:receivable,payable',
            'items.*.document_type' => 'required|in:invoice,bill,credit_note',
            'items.*.document_id' => 'required|integer',
            'items.*.amount_offset' => 'required|integer|min:1',
        ]);

        try {
            $compensation = $this->service->create($company, $request->all(), $request->user()?->id);

            return response()->json([
                'success' => true,
                'data' => $compensation,
                'message' => 'Compensation created successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Confirm a draft compensation.
     */
    public function confirm(Request $request, int $company, int $id): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $compensation = Compensation::forCompany($company)
            ->where('id', $id)
            ->first();

        if (!$compensation) {
            return response()->json(['success' => false, 'message' => 'Compensation not found'], 404);
        }

        try {
            $compensation = $this->service->confirm($compensation, $request->user()?->id);

            return response()->json([
                'success' => true,
                'data' => $compensation,
                'message' => 'Compensation confirmed successfully',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Cancel a draft compensation.
     */
    public function cancel(Request $request, int $company, int $id): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $compensation = Compensation::forCompany($company)
            ->where('id', $id)
            ->first();

        if (!$compensation) {
            return response()->json(['success' => false, 'message' => 'Compensation not found'], 404);
        }

        try {
            $compensation = $this->service->cancel($compensation);

            return response()->json([
                'success' => true,
                'data' => $compensation,
                'message' => 'Compensation cancelled',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get compensation opportunities for a company.
     */
    public function opportunities(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $opportunities = $this->service->getOpportunities($company);

        return response()->json([
            'success' => true,
            'data' => $opportunities,
            'count' => count($opportunities),
        ]);
    }

    /**
     * Get eligible documents for a counterparty.
     */
    public function eligibleDocuments(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $customerId = $request->query('customer_id') ? (int) $request->query('customer_id') : null;
        $supplierId = $request->query('supplier_id') ? (int) $request->query('supplier_id') : null;

        if (!$customerId && !$supplierId) {
            return response()->json([
                'success' => false,
                'message' => 'Either customer_id or supplier_id is required',
            ], 400);
        }

        $documents = $this->service->getEligibleDocuments($company, $customerId, $supplierId);

        return response()->json([
            'success' => true,
            'data' => $documents,
        ]);
    }

    /**
     * Generate and return PDF.
     */
    public function pdf(Request $request, int $company, int $id)
    {
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $compensation = Compensation::forCompany($company)
            ->where('id', $id)
            ->first();

        if (!$compensation) {
            return response()->json(['success' => false, 'message' => 'Compensation not found'], 404);
        }

        $pdf = $this->service->generatePdf($compensation);

        $filename = sprintf(
            'kompenzacija_%s_%s.pdf',
            $compensation->compensation_number,
            $compensation->compensation_date->format('Y-m-d')
        );

        return $pdf->download($filename);
    }

    // ---- Partner access helpers (same pattern as PartnerAccountingReportsController) ----

    /**
     * Get partner from authenticated request.
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
