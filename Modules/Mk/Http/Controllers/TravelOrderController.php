<?php

namespace Modules\Mk\Http\Controllers;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Mk\Models\TravelOrder;
use Modules\Mk\Services\TravelOrderService;

class TravelOrderController extends Controller
{
    protected TravelOrderService $service;

    public function __construct(TravelOrderService $service)
    {
        $this->service = $service;
    }

    /**
     * List travel orders with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $filters = [
            'status' => $request->query('status'),
            'type' => $request->query('type'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
            'limit' => $request->query('limit', 15),
        ];

        $result = $this->service->list($companyId, $filters);

        return response()->json([
            'success' => true,
            'data' => $result['data'],
            'meta' => $result['meta'],
        ]);
    }

    /**
     * Show a single travel order.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $order = TravelOrder::forCompany($companyId)
            ->with(['employee', 'segments', 'expenses', 'approvedByUser'])
            ->where('id', $id)
            ->first();

        if (! $order) {
            return response()->json(['success' => false, 'message' => 'Travel order not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $order,
        ]);
    }

    /**
     * Create a new travel order.
     */
    public function store(Request $request): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $request->validate([
            'type' => 'required|in:domestic,foreign',
            'purpose' => 'required|string|max:1000',
            'departure_date' => 'required|date',
            'return_date' => 'required|date|after_or_equal:departure_date',
            'employee_id' => 'nullable|integer',
            'advance_amount' => 'nullable|integer|min:0',
            'cost_center_id' => 'nullable|integer',
            'notes' => 'nullable|string|max:2000',
            'segments' => 'required|array|min:1',
            'segments.*.from_city' => 'required|string|max:150',
            'segments.*.to_city' => 'required|string|max:150',
            'segments.*.country_code' => 'nullable|string|max:2',
            'segments.*.departure_at' => 'required|date',
            'segments.*.arrival_at' => 'required|date',
            'segments.*.transport_type' => 'required|in:car,bus,train,plane,other',
            'segments.*.distance_km' => 'nullable|numeric|min:0',
            'segments.*.per_diem_rate' => 'nullable|numeric|min:0',
            'segments.*.accommodation_provided' => 'nullable|boolean',
            'segments.*.meals_provided' => 'nullable|boolean',
            'expenses' => 'nullable|array',
            'expenses.*.category' => 'required|in:transport,accommodation,meals,other',
            'expenses.*.description' => 'required|string|max:255',
            'expenses.*.amount' => 'required|integer|min:0',
            'expenses.*.currency_code' => 'nullable|string|max:3',
            'expenses.*.receipt_path' => 'nullable|string|max:500',
        ]);

        try {
            $order = $this->service->create($companyId, $request->all(), $request->user()->id);

            return response()->json([
                'success' => true,
                'data' => $order,
                'message' => 'Travel order created successfully.',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Update a travel order (draft only).
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $order = TravelOrder::forCompany($companyId)->where('id', $id)->first();

        if (! $order) {
            return response()->json(['success' => false, 'message' => 'Travel order not found'], 404);
        }

        $request->validate([
            'type' => 'nullable|in:domestic,foreign',
            'purpose' => 'nullable|string|max:1000',
            'departure_date' => 'nullable|date',
            'return_date' => 'nullable|date',
            'employee_id' => 'nullable|integer',
            'advance_amount' => 'nullable|integer|min:0',
            'cost_center_id' => 'nullable|integer',
            'notes' => 'nullable|string|max:2000',
            'segments' => 'nullable|array|min:1',
            'segments.*.from_city' => 'required|string|max:150',
            'segments.*.to_city' => 'required|string|max:150',
            'segments.*.country_code' => 'nullable|string|max:2',
            'segments.*.departure_at' => 'required|date',
            'segments.*.arrival_at' => 'required|date',
            'segments.*.transport_type' => 'required|in:car,bus,train,plane,other',
            'segments.*.distance_km' => 'nullable|numeric|min:0',
            'segments.*.per_diem_rate' => 'nullable|numeric|min:0',
            'segments.*.accommodation_provided' => 'nullable|boolean',
            'segments.*.meals_provided' => 'nullable|boolean',
            'expenses' => 'nullable|array',
            'expenses.*.category' => 'required|in:transport,accommodation,meals,other',
            'expenses.*.description' => 'required|string|max:255',
            'expenses.*.amount' => 'required|integer|min:0',
            'expenses.*.currency_code' => 'nullable|string|max:3',
            'expenses.*.receipt_path' => 'nullable|string|max:500',
        ]);

        try {
            $updated = $this->service->update($order, $request->all());

            return response()->json([
                'success' => true,
                'data' => $updated,
                'message' => 'Travel order updated successfully.',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Approve a travel order.
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $order = TravelOrder::forCompany($companyId)->where('id', $id)->first();

        if (! $order) {
            return response()->json(['success' => false, 'message' => 'Travel order not found'], 404);
        }

        try {
            $approved = $this->service->approve($order, $request->user()->id);

            return response()->json([
                'success' => true,
                'data' => $approved,
                'message' => 'Travel order approved.',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Settle a travel order.
     */
    public function settle(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $order = TravelOrder::forCompany($companyId)->where('id', $id)->first();

        if (! $order) {
            return response()->json(['success' => false, 'message' => 'Travel order not found'], 404);
        }

        try {
            $settled = $this->service->settle($order);

            return response()->json([
                'success' => true,
                'data' => $settled,
                'message' => 'Travel order settled.',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Reject a travel order.
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $order = TravelOrder::forCompany($companyId)->where('id', $id)->first();

        if (! $order) {
            return response()->json(['success' => false, 'message' => 'Travel order not found'], 404);
        }

        try {
            $rejected = $this->service->reject($order);

            return response()->json([
                'success' => true,
                'data' => $rejected,
                'message' => 'Travel order rejected.',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Soft delete a travel order (draft only).
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $order = TravelOrder::forCompany($companyId)->where('id', $id)->first();

        if (! $order) {
            return response()->json(['success' => false, 'message' => 'Travel order not found'], 404);
        }

        try {
            $this->service->delete($order);

            return response()->json([
                'success' => true,
                'message' => 'Travel order deleted.',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Download travel order as PDF.
     */
    public function pdf(Request $request, int $id): Response
    {
        $companyId = (int) $request->header('company');

        $order = TravelOrder::forCompany($companyId)
            ->with(['employee', 'segments', 'expenses', 'approvedByUser', 'company.address'])
            ->where('id', $id)
            ->first();

        if (! $order) {
            abort(404, 'Travel order not found');
        }

        $employee = $order->employee;
        $company = $order->company;

        $pdf = Pdf::loadView('app.pdf.reports.travel-order', compact('order', 'employee', 'company'));
        $pdf->setPaper('A4', 'portrait');

        $filename = "paten-nalog-{$order->travel_number}.pdf";

        return $pdf->download($filename);
    }

    /**
     * Get summary statistics.
     */
    public function summary(Request $request): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $summary = $this->service->getSummary($companyId);

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }
}

// CLAUDE-CHECKPOINT
