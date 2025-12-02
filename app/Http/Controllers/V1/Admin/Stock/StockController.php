<?php

namespace App\Http\Controllers\V1\Admin\Stock;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Stock Controller
 *
 * Handles stock inventory reporting and analytics.
 * Provides endpoints for:
 * - Current inventory levels
 * - Item stock cards (movement history)
 * - Warehouse inventory reports
 */
class StockController extends Controller
{
    protected StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Get current inventory levels for all items.
     */
    /**
     * Get current inventory levels for all items.
     */
    public function inventory(Request $request): JsonResponse
    {
        $companyId = $request->header('company');
        $warehouseId = $request->query('warehouse_id');
        $search = $request->query('search');
        $categoryId = $request->query('category');
        $itemId = $request->query('item_id');
        $orderByField = $request->query('orderByField', 'name');
        $orderBy = $request->query('orderBy', 'asc');
        $limit = (int) $request->query('limit', 15);

        // Build query
        $query = Item::where('company_id', $companyId)
            ->where('track_quantity', true)
            ->with(['unit', 'currency']);

        // Apply filters
        if ($itemId) {
            $query->where('id', $itemId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        if ($categoryId) {
            // Assuming category is a string field or relation. 
            // Based on Create.vue it seems to be a text field 'category'
            $query->where('category', 'like', "%{$categoryId}%");
        }

        // Apply sorting
        // We can sort by quantity because StockService updates the item.quantity field
        $allowedSortFields = ['name', 'sku', 'price', 'quantity', 'created_at'];
        if (in_array($orderByField, $allowedSortFields)) {
            $query->orderBy($orderByField, $orderBy);
        } else {
            $query->orderBy('name', 'asc');
        }

        // Paginate
        $paginatedItems = $query->paginate($limit);

        $inventory = [];

        // Get warehouse name if filtering by warehouse
        $warehouseName = null;
        if ($warehouseId) {
            $warehouse = Warehouse::find($warehouseId);
            $warehouseName = $warehouse?->name;
        }

        foreach ($paginatedItems as $item) {
            if ($warehouseId) {
                // Single warehouse inventory
                $stock = $this->stockService->getItemStock($companyId, $item->id, (int) $warehouseId);

                // For single warehouse, we might show zero stock items if requested
                // But since we paginated Items (which exist regardless of warehouse stock),
                // we should show them, but maybe with 0 quantity for that warehouse.

                $inventory[] = [
                    'item_id' => $item->id,
                    'name' => $item->name,
                    'sku' => $item->sku,
                    'barcode' => $item->barcode,
                    'unit_name' => $item->unit?->name,
                    'warehouse_name' => $warehouseName,
                    'quantity' => $stock['quantity'],
                    'unit_cost' => $stock['weighted_average_cost'],
                    'total_value' => $stock['total_value'],
                    'minimum_quantity' => $item->minimum_quantity,
                    'is_low_stock' => $item->minimum_quantity && $stock['quantity'] <= $item->minimum_quantity,
                ];
            } else {
                // All warehouses - get breakdown
                // We use the item->quantity for the main display as it should be synced
                // But we fetch fresh stock data to be sure and get value
                $totalStock = $this->stockService->getItemStock($companyId, $item->id);

                // Optimization: Only fetch warehouse breakdown if needed (e.g. for detail view)
                // For list view, we might not need it. But let's keep it if it's not too heavy.
                // Actually, fetching breakdown for every item in list is N+1 queries.
                // Let's skip breakdown for the main list to be fast.

                $inventory[] = [
                    'item_id' => $item->id,
                    'name' => $item->name,
                    'sku' => $item->sku,
                    'barcode' => $item->barcode,
                    'unit_name' => $item->unit?->name,
                    'warehouse_name' => 'All Warehouses',
                    'quantity' => $totalStock['quantity'],
                    'unit_cost' => $totalStock['weighted_average_cost'],
                    'total_value' => $totalStock['total_value'],
                    'minimum_quantity' => $item->minimum_quantity,
                    'is_low_stock' => $item->minimum_quantity && $totalStock['quantity'] <= $item->minimum_quantity,
                    // 'warehouses' => ... // Skipped for performance in list view
                ];
            }
        }

        return response()->json([
            'data' => $inventory,
            'meta' => [
                'current_page' => $paginatedItems->currentPage(),
                'last_page' => $paginatedItems->lastPage(),
                'per_page' => $paginatedItems->perPage(),
                'total' => $paginatedItems->total(),
            ],
            // Keep summary for compatibility but it might be partial now
            // Ideally summary should be a separate endpoint or calculated separately
            'summary' => [
                'total_items' => $paginatedItems->total(),
                // Total value is hard to calculate for ALL items without iterating all.
                // We can return 0 or calculate it via aggregation query if needed.
                // For now, let's remove it or set to 0 to avoid confusion, or calculate only for this page.
                'total_value' => array_sum(array_column($inventory, 'total_value')),
                'low_stock_items' => 0, // Requires separate query
            ],
        ]);
    }

    /**
     * Get stock card (movement history) for a specific item.
     */
    public function itemCard(Request $request, int $itemId): JsonResponse
    {
        $companyId = $request->header('company');
        $warehouseId = $request->query('warehouse_id');
        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');
        $limit = (int) $request->query('limit', 100);

        // Get item details
        $item = Item::where('company_id', $companyId)
            ->where('id', $itemId)
            ->with(['unit', 'currency'])
            ->firstOrFail();

        if (!$item->track_quantity) {
            return response()->json([
                'error' => 'Stock tracking is not enabled for this item',
            ], 400);
        }

        // Get current stock
        $currentStock = $this->stockService->getItemStock($companyId, $itemId, $warehouseId);

        // Get movement history
        $movements = $this->stockService->getMovementHistory(
            $companyId,
            $itemId,
            $warehouseId,
            $fromDate,
            $toDate,
            $limit
        );

        // Format movements for response
        $formattedMovements = $movements->map(function ($movement) {
            return [
                'id' => $movement->id,
                'movement_date' => $movement->movement_date->format('Y-m-d'),
                'source_type' => $movement->source_type,
                'source_type_label' => $movement->source_type_label,
                'source_id' => $movement->source_id,
                'warehouse_id' => $movement->warehouse_id,
                'warehouse_name' => $movement->warehouse?->name,
                'quantity' => $movement->quantity,
                'absolute_quantity' => $movement->absolute_quantity,
                'unit_cost' => $movement->unit_cost,
                'total_cost' => $movement->total_cost,
                'balance_quantity' => $movement->balance_quantity,
                'balance_value' => $movement->balance_value,
                'weighted_average_cost' => $movement->weighted_average_cost,
                'notes' => $movement->notes,
                'created_by' => $movement->creator?->name,
                'is_stock_in' => $movement->isStockIn(),
                'is_stock_out' => $movement->isStockOut(),
            ];
        });

        return response()->json([
            'item' => [
                'id' => $item->id,
                'name' => $item->name,
                'sku' => $item->sku,
                'barcode' => $item->barcode,
                'unit_name' => $item->unit?->name,
                'minimum_quantity' => $item->minimum_quantity,
            ],
            'current_stock' => $currentStock,
            'movements' => $formattedMovements,
            'summary' => [
                'total_movements' => $movements->count(),
                'stock_in_count' => $movements->filter(fn($m) => $m->isStockIn())->count(),
                'stock_out_count' => $movements->filter(fn($m) => $m->isStockOut())->count(),
            ],
        ]);
    }

    /**
     * Get inventory for a specific warehouse.
     */
    public function warehouseInventory(Request $request, int $warehouseId): JsonResponse
    {
        $companyId = $request->header('company');

        // Verify warehouse belongs to company
        $warehouse = Warehouse::where('company_id', $companyId)
            ->where('id', $warehouseId)
            ->firstOrFail();

        // Get all trackable items
        $items = Item::where('company_id', $companyId)
            ->where('track_quantity', true)
            ->with(['unit', 'currency'])
            ->get();

        $inventory = [];
        $totalValue = 0;

        foreach ($items as $item) {
            $stock = $this->stockService->getItemStock($companyId, $item->id, $warehouseId);

            if ($stock['quantity'] != 0 || $request->query('show_zero_stock')) {
                $inventory[] = [
                    'item_id' => $item->id,
                    'item_name' => $item->name,
                    'item_sku' => $item->sku,
                    'item_barcode' => $item->barcode,
                    'unit_name' => $item->unit?->name,
                    'quantity' => $stock['quantity'],
                    'weighted_average_cost' => $stock['weighted_average_cost'],
                    'total_value' => $stock['total_value'],
                    'minimum_quantity' => $item->minimum_quantity,
                    'is_low_stock' => $item->minimum_quantity && $stock['quantity'] <= $item->minimum_quantity,
                ];

                $totalValue += $stock['total_value'];
            }
        }

        return response()->json([
            'warehouse' => [
                'id' => $warehouse->id,
                'name' => $warehouse->name,
                'code' => $warehouse->code,
                'address' => $warehouse->address,
                'is_default' => $warehouse->is_default,
                'is_active' => $warehouse->is_active,
            ],
            'inventory' => $inventory,
            'summary' => [
                'total_items' => count($inventory),
                'total_value' => $totalValue,
                'low_stock_items' => count(array_filter($inventory, fn($i) => $i['is_low_stock'])),
            ],
        ]);
    }

    /**
     * Get stock valuation report.
     */
    public function valuationReport(Request $request): JsonResponse
    {
        $companyId = $request->header('company');
        $warehouseId = $request->query('warehouse_id');

        $report = $this->stockService->getStockValuationReport($companyId, $warehouseId);

        return response()->json($report);
    }

    /**
     * Get low stock items.
     */
    public function lowStock(Request $request): JsonResponse
    {
        $companyId = $request->header('company');

        $lowStockItems = $this->stockService->getLowStockItems($companyId);

        $formatted = $lowStockItems->map(function ($item) use ($companyId) {
            $stock = $this->stockService->getItemStock($companyId, $item->id);

            return [
                'item_id' => $item->id,
                'item_name' => $item->name,
                'item_sku' => $item->sku,
                'item_barcode' => $item->barcode,
                'unit_name' => $item->unit?->name,
                'current_quantity' => $stock['quantity'],
                'minimum_quantity' => $item->minimum_quantity,
                'shortage' => $item->minimum_quantity - $stock['quantity'],
                'weighted_average_cost' => $stock['weighted_average_cost'],
            ];
        });

        return response()->json([
            'low_stock_items' => $formatted,
            'count' => $formatted->count(),
        ]);
    }
}
// CLAUDE-CHECKPOINT
