<?php

namespace Modules\Mk\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Mk\Models\Compensation;
use Modules\Mk\Services\CompensationService;

class CompensationController extends Controller
{
    protected CompensationService $service;

    public function __construct(CompensationService $service)
    {
        $this->service = $service;
    }

    /**
     * List compensations for the current company with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $query = Compensation::forCompany($companyId)
            ->with(['customer:id,name', 'supplier:id,name', 'createdBy'])
            ->orderBy('compensation_date', 'desc');

        // Apply filters
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
            $compensations = $query->get();

            return response()->json([
                'success' => true,
                'data' => $compensations,
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
    public function show(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $compensation = Compensation::forCompany($companyId)
            ->with(['customer', 'supplier', 'items', 'createdBy', 'confirmedBy'])
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
    public function store(Request $request): JsonResponse
    {
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

        $companyId = (int) $request->header('company');
        $userId = Auth::id();

        try {
            $compensation = $this->service->create($companyId, $request->all(), $userId);

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
     * Update a draft compensation.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $compensation = Compensation::forCompany($companyId)
            ->where('id', $id)
            ->first();

        if (!$compensation) {
            return response()->json(['success' => false, 'message' => 'Compensation not found'], 404);
        }

        $request->validate([
            'compensation_date' => 'nullable|date',
            'type' => 'nullable|in:bilateral,unilateral',
            'notes' => 'nullable|string|max:2000',
            'items' => 'nullable|array|min:1',
            'items.*.side' => 'required_with:items|in:receivable,payable',
            'items.*.document_type' => 'required_with:items|in:invoice,bill,credit_note',
            'items.*.document_id' => 'required_with:items|integer',
            'items.*.amount_offset' => 'required_with:items|integer|min:1',
        ]);

        try {
            $compensation = $this->service->update($compensation, $request->all());

            return response()->json([
                'success' => true,
                'data' => $compensation,
                'message' => 'Compensation updated successfully',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Confirm a draft compensation.
     */
    public function confirm(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $compensation = Compensation::forCompany($companyId)
            ->where('id', $id)
            ->first();

        if (!$compensation) {
            return response()->json(['success' => false, 'message' => 'Compensation not found'], 404);
        }

        try {
            $compensation = $this->service->confirm($compensation, Auth::id());

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
    public function cancel(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $compensation = Compensation::forCompany($companyId)
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
     * Get compensation opportunities (counterparties with both receivables and payables).
     */
    public function opportunities(Request $request): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $opportunities = $this->service->getOpportunities($companyId);

        return response()->json([
            'success' => true,
            'data' => $opportunities,
            'count' => count($opportunities),
        ]);
    }

    /**
     * Get eligible documents (unpaid invoices/bills) for a counterparty.
     */
    public function eligibleDocuments(Request $request): JsonResponse
    {
        $companyId = (int) $request->header('company');
        $customerId = $request->query('customer_id') ? (int) $request->query('customer_id') : null;
        $supplierId = $request->query('supplier_id') ? (int) $request->query('supplier_id') : null;

        if (!$customerId && !$supplierId) {
            return response()->json([
                'success' => false,
                'message' => 'Either customer_id or supplier_id is required',
            ], 400);
        }

        $documents = $this->service->getEligibleDocuments($companyId, $customerId, $supplierId);

        return response()->json([
            'success' => true,
            'data' => $documents,
        ]);
    }

    /**
     * Generate and return PDF for a compensation.
     */
    public function pdf(Request $request, int $id)
    {
        $companyId = (int) $request->header('company');

        $compensation = Compensation::forCompany($companyId)
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
}

// CLAUDE-CHECKPOINT
