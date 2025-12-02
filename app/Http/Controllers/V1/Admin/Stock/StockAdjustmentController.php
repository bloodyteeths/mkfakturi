<?php

namespace App\Http\Controllers\V1\Admin\Stock;

use App\Http\Controllers\Controller;
use App\Http\Requests\InitialStockRequest;
use App\Http\Requests\StockAdjustmentRequest;
use App\Http\Requests\StockTransferRequest;
use App\Models\Item;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Stock Adjustment Controller
 *
 * Handles stock adjustments, transfers, and initial stock entries.
 * These are the operational endpoints for day-to-day inventory management.
 */
class StockAdjustmentController extends Controller
{
    protected StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * List stock adjustments (movements with source_type = 'adjustment').
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = $request->header('company');
        $warehouseId = $request->query('warehouse_id');
        $itemId = $request->query('item_id');
        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');
        $limit = (int) $request->query('limit', 50);

        $query = StockMovement::where('company_id', $companyId)
            ->where('source_type', StockMovement::SOURCE_ADJUSTMENT)
            ->with(['warehouse', 'item', 'creator'])
            ->orderBy('movement_date', 'desc')
            ->orderBy('id', 'desc');

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($itemId) {
            $query->where('item_id', $itemId);
        }

        if ($fromDate) {
            $query->where('movement_date', '>=', $fromDate);
        }

        if ($toDate) {
            $query->where('movement_date', '<=', $toDate);
        }

        $adjustments = $query->paginate($limit);

        return response()->json([
            'data' => $adjustments->map(function ($movement) {
                return $this->formatMovement($movement);
            }),
            'meta' => [
                'current_page' => $adjustments->currentPage(),
                'last_page' => $adjustments->lastPage(),
                'per_page' => $adjustments->perPage(),
                'total' => $adjustments->total(),
            ],
        ]);
    }

    /**
     * Create a stock adjustment.
     */
    public function store(StockAdjustmentRequest $request): JsonResponse
    {
        $data = $request->getAdjustmentData();
        $companyId = $request->header('company');

        // Verify item has track_quantity enabled
        $item = Item::where('company_id', $companyId)
            ->where('id', $data['item_id'])
            ->firstOrFail();

        if (! $item->track_quantity) {
            return response()->json([
                'error' => 'Stock tracking is not enabled for this item.',
                'message' => 'Please enable "Track Inventory" on the item first.',
            ], 422);
        }

        // Check for negative stock if removing
        if ($data['quantity'] < 0) {
            $currentStock = $this->stockService->getItemStock(
                $companyId,
                $data['item_id'],
                $data['warehouse_id']
            );

            if ($currentStock['quantity'] + $data['quantity'] < 0) {
                return response()->json([
                    'error' => 'Insufficient stock.',
                    'message' => "Cannot remove {$data['quantity']} units. Current stock: {$currentStock['quantity']}",
                    'available' => $currentStock['quantity'],
                ], 422);
            }
        }

        try {
            $movement = $this->stockService->recordAdjustment(
                $companyId,
                $data['warehouse_id'],
                $data['item_id'],
                $data['quantity'],
                $data['unit_cost'],
                $data['notes'],
                ['reason' => $data['reason']],
                auth()->id()
            );

            $movement->load(['warehouse', 'item', 'creator']);

            return response()->json([
                'success' => true,
                'message' => 'Stock adjustment created successfully.',
                'data' => $this->formatMovement($movement),
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Stock adjustment failed', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            return response()->json([
                'error' => 'Failed to create stock adjustment.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * View a specific adjustment.
     */
    public function show(int $id): JsonResponse
    {
        $movement = StockMovement::where('source_type', StockMovement::SOURCE_ADJUSTMENT)
            ->with(['warehouse', 'item', 'creator'])
            ->findOrFail($id);

        return response()->json([
            'data' => $this->formatMovement($movement),
        ]);
    }

    /**
     * Delete/reverse an adjustment.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $companyId = $request->header('company');

        $movement = StockMovement::where('company_id', $companyId)
            ->where('source_type', StockMovement::SOURCE_ADJUSTMENT)
            ->findOrFail($id);

        try {
            // Create a reversal movement
            $reversal = $this->stockService->reverseMovement(
                $movement,
                'Adjustment reversed by user'
            );

            return response()->json([
                'success' => true,
                'message' => 'Stock adjustment reversed successfully.',
                'reversal_id' => $reversal->id,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to reverse adjustment.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create warehouse transfer.
     */
    public function transfer(StockTransferRequest $request): JsonResponse
    {
        $data = $request->getTransferData();
        $companyId = $request->header('company');

        // Verify item has track_quantity enabled
        $item = Item::where('company_id', $companyId)
            ->where('id', $data['item_id'])
            ->firstOrFail();

        if (! $item->track_quantity) {
            return response()->json([
                'error' => 'Stock tracking is not enabled for this item.',
            ], 422);
        }

        // Check stock availability in source warehouse
        $sourceStock = $this->stockService->getItemStock(
            $companyId,
            $data['item_id'],
            $data['from_warehouse_id']
        );

        if ($sourceStock['quantity'] < $data['quantity']) {
            return response()->json([
                'error' => 'Insufficient stock in source warehouse.',
                'message' => "Available: {$sourceStock['quantity']}, Requested: {$data['quantity']}",
                'available' => $sourceStock['quantity'],
            ], 422);
        }

        try {
            $movements = $this->stockService->transferStock(
                $companyId,
                $data['from_warehouse_id'],
                $data['to_warehouse_id'],
                $data['item_id'],
                $data['quantity'],
                $data['notes'],
                auth()->id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Stock transfer completed successfully.',
                'data' => [
                    'out_movement' => $this->formatMovement($movements['out']->load(['warehouse', 'item'])),
                    'in_movement' => $this->formatMovement($movements['in']->load(['warehouse', 'item'])),
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to transfer stock.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List warehouse transfers.
     */
    public function transfers(Request $request): JsonResponse
    {
        $companyId = $request->header('company');
        $limit = (int) $request->query('limit', 50);

        $transfers = StockMovement::where('company_id', $companyId)
            ->whereIn('source_type', [StockMovement::SOURCE_TRANSFER_IN, StockMovement::SOURCE_TRANSFER_OUT])
            ->with(['warehouse', 'item', 'creator'])
            ->orderBy('movement_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($limit);

        return response()->json([
            'data' => $transfers->map(function ($movement) {
                return $this->formatMovement($movement);
            }),
            'meta' => [
                'current_page' => $transfers->currentPage(),
                'last_page' => $transfers->lastPage(),
                'total' => $transfers->total(),
            ],
        ]);
    }

    /**
     * Create initial stock entry.
     */
    public function initialStock(InitialStockRequest $request): JsonResponse
    {
        $data = $request->getInitialStockData();
        $companyId = $request->header('company');

        // Verify item
        $item = Item::where('company_id', $companyId)
            ->where('id', $data['item_id'])
            ->firstOrFail();

        if (! $item->track_quantity) {
            return response()->json([
                'error' => 'Stock tracking is not enabled for this item.',
            ], 422);
        }

        try {
            $movement = $this->stockService->recordInitialStock(
                $companyId,
                $data['warehouse_id'],
                $data['item_id'],
                $data['quantity'],
                $data['unit_cost'],
                $data['notes'],
                auth()->id()
            );

            $movement->load(['warehouse', 'item', 'creator']);

            return response()->json([
                'success' => true,
                'message' => 'Initial stock recorded successfully.',
                'data' => $this->formatMovement($movement),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to record initial stock.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available stock for an item (for UI validation).
     */
    public function itemStock(Request $request, int $itemId): JsonResponse
    {
        $companyId = $request->header('company');
        $warehouseId = $request->query('warehouse_id');

        $item = Item::where('company_id', $companyId)
            ->where('id', $itemId)
            ->firstOrFail();

        if (! $item->track_quantity) {
            return response()->json([
                'track_quantity' => false,
                'message' => 'Stock tracking not enabled for this item.',
            ]);
        }

        if ($warehouseId) {
            $stock = $this->stockService->getItemStock($companyId, $itemId, (int) $warehouseId);
            $warehouse = Warehouse::find($warehouseId);

            return response()->json([
                'track_quantity' => true,
                'item_id' => $itemId,
                'item_name' => $item->name,
                'warehouse_id' => $warehouseId,
                'warehouse_name' => $warehouse?->name,
                'quantity' => $stock['quantity'],
                'total_value' => $stock['total_value'],
                'unit_cost' => $stock['weighted_average_cost'],
            ]);
        }

        // Return stock by all warehouses
        $stockByWarehouse = $this->stockService->getItemStockByWarehouse($companyId, $itemId);
        $totalStock = $this->stockService->getItemStock($companyId, $itemId);

        return response()->json([
            'track_quantity' => true,
            'item_id' => $itemId,
            'item_name' => $item->name,
            'total' => $totalStock,
            'by_warehouse' => array_values($stockByWarehouse),
        ]);
    }

    /**
     * Format movement for API response.
     */
    private function formatMovement(StockMovement $movement): array
    {
        return [
            'id' => $movement->id,
            'warehouse_id' => $movement->warehouse_id,
            'warehouse_name' => $movement->warehouse?->name,
            'item_id' => $movement->item_id,
            'item_name' => $movement->item?->name,
            'item_sku' => $movement->item?->sku,
            'source_type' => $movement->source_type,
            'source_type_label' => $movement->source_type_label,
            'quantity' => $movement->quantity,
            'is_stock_in' => $movement->isStockIn(),
            'is_stock_out' => $movement->isStockOut(),
            'unit_cost' => $movement->unit_cost,
            'total_cost' => $movement->total_cost,
            'balance_quantity' => $movement->balance_quantity,
            'balance_value' => $movement->balance_value,
            'movement_date' => $movement->movement_date?->format('Y-m-d'),
            'notes' => $movement->notes,
            'meta' => $movement->meta,
            'created_by' => $movement->creator?->name,
            'created_at' => $movement->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
// CLAUDE-CHECKPOINT
