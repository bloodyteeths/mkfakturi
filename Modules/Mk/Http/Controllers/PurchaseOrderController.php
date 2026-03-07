<?php

namespace Modules\Mk\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Mk\Models\PurchaseOrder;
use Modules\Mk\Services\PurchaseOrderService;

class PurchaseOrderController extends Controller
{
    protected PurchaseOrderService $service;

    public function __construct(PurchaseOrderService $service)
    {
        $this->service = $service;
    }

    /**
     * List purchase orders for the current company.
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $result = $this->service->list($companyId, $request->query());

        return response()->json([
            'success' => true,
            'data' => $result['data'],
            'meta' => $result['meta'],
        ]);
    }

    /**
     * Show a single purchase order with items and receipts.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $po = PurchaseOrder::forCompany($companyId)
            ->with([
                'supplier',
                'items.item',
                'createdBy:id,name',
                'currency:id,name,code,symbol',
                'warehouse',
                'convertedBill',
                'goodsReceipts.items',
            ])
            ->where('id', $id)
            ->first();

        if (!$po) {
            return response()->json(['success' => false, 'message' => 'Purchase order not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $po,
        ]);
    }

    /**
     * Create a new purchase order.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'supplier_id' => 'nullable|integer|exists:suppliers,id',
            'po_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date',
            'currency_id' => 'nullable|integer',
            'warehouse_id' => 'nullable|integer',
            'notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'nullable|integer',
            'items.*.name' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.price' => 'required|integer|min:0',
            'items.*.tax' => 'nullable|integer|min:0',
        ]);

        $companyId = (int) $request->header('company');
        $userId = Auth::id();

        try {
            $po = $this->service->create($companyId, $request->all(), $userId);

            return response()->json([
                'success' => true,
                'data' => $po,
                'message' => 'Purchase order created successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Update a draft purchase order.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $po = PurchaseOrder::forCompany($companyId)
            ->where('id', $id)
            ->first();

        if (!$po) {
            return response()->json(['success' => false, 'message' => 'Purchase order not found'], 404);
        }

        $request->validate([
            'supplier_id' => 'nullable|integer',
            'po_date' => 'nullable|date',
            'expected_delivery_date' => 'nullable|date',
            'currency_id' => 'nullable|integer',
            'warehouse_id' => 'nullable|integer',
            'notes' => 'nullable|string|max:2000',
            'items' => 'nullable|array|min:1',
            'items.*.item_id' => 'nullable|integer',
            'items.*.name' => 'required_with:items|string|max:255',
            'items.*.quantity' => 'required_with:items|numeric|min:0.0001',
            'items.*.price' => 'required_with:items|integer|min:0',
            'items.*.tax' => 'nullable|integer|min:0',
        ]);

        try {
            $po = $this->service->update($po, $request->all());

            return response()->json([
                'success' => true,
                'data' => $po,
                'message' => 'Purchase order updated successfully',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Mark purchase order as sent.
     */
    public function send(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $po = PurchaseOrder::forCompany($companyId)
            ->where('id', $id)
            ->first();

        if (!$po) {
            return response()->json(['success' => false, 'message' => 'Purchase order not found'], 404);
        }

        try {
            $po = $this->service->send($po);

            return response()->json([
                'success' => true,
                'data' => $po,
                'message' => 'Purchase order sent successfully',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Receive goods for a purchase order.
     */
    public function receiveGoods(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $po = PurchaseOrder::forCompany($companyId)
            ->where('id', $id)
            ->first();

        if (!$po) {
            return response()->json(['success' => false, 'message' => 'Purchase order not found'], 404);
        }

        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.purchase_order_item_id' => 'required|integer',
            'items.*.quantity_received' => 'required|numeric|min:0',
            'items.*.quantity_accepted' => 'nullable|numeric|min:0',
            'items.*.quantity_rejected' => 'nullable|numeric|min:0',
        ]);

        try {
            $receipt = $this->service->receiveGoods($po, $request->input('items'), Auth::id());

            // Reload PO with updated data
            $po = $po->fresh(['items', 'supplier', 'goodsReceipts']);

            return response()->json([
                'success' => true,
                'data' => [
                    'receipt' => $receipt,
                    'purchase_order' => $po,
                ],
                'message' => 'Goods received successfully',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Convert purchase order to bill.
     */
    public function convertToBill(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $po = PurchaseOrder::forCompany($companyId)
            ->where('id', $id)
            ->first();

        if (!$po) {
            return response()->json(['success' => false, 'message' => 'Purchase order not found'], 404);
        }

        try {
            $bill = $this->service->convertToBill($po);

            return response()->json([
                'success' => true,
                'data' => [
                    'bill' => $bill,
                    'purchase_order' => $po->fresh(),
                ],
                'message' => 'Bill created from purchase order',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Three-way match check (PO vs Receipt vs Bill).
     */
    public function threeWayMatch(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $po = PurchaseOrder::forCompany($companyId)
            ->where('id', $id)
            ->first();

        if (!$po) {
            return response()->json(['success' => false, 'message' => 'Purchase order not found'], 404);
        }

        $result = $this->service->threeWayMatch($po);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Cancel a purchase order.
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $po = PurchaseOrder::forCompany($companyId)
            ->where('id', $id)
            ->first();

        if (!$po) {
            return response()->json(['success' => false, 'message' => 'Purchase order not found'], 404);
        }

        try {
            $po = $this->service->cancel($po);

            return response()->json([
                'success' => true,
                'data' => $po,
                'message' => 'Purchase order cancelled',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Delete a purchase order (draft only).
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $po = PurchaseOrder::forCompany($companyId)
            ->where('id', $id)
            ->first();

        if (!$po) {
            return response()->json(['success' => false, 'message' => 'Purchase order not found'], 404);
        }

        try {
            $this->service->deletePo($po);

            return response()->json([
                'success' => true,
                'message' => 'Purchase order deleted',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}

// CLAUDE-CHECKPOINT
