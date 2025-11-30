<?php

namespace App\Http\Controllers\V1\Admin\Stock;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\StockMovement;
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
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function inventory(Request $request): JsonResponse
    {
        $companyId = $request->header('company');
        $warehouseId = $request->query('warehouse_id');

        // Get all trackable items for the company
        $items = Item::where('company_id', $companyId)
            ->where('track_quantity', true)
            ->with(['unit', 'currency'])
            ->get();

        $inventory = [];

        foreach ($items as $item) {
            if ($warehouseId) {
                // Single warehouse inventory
                $stock = $this->stockService->getItemStock($companyId, $item->id, (int) $warehouseId);

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
                }
            } else {
                // All warehouses - get breakdown
                $stockByWarehouse = $this->stockService->getItemStockByWarehouse($companyId, $item->id);
                $totalStock = $this->stockService->getItemStock($companyId, $item->id);

                if ($totalStock['quantity'] != 0 || $request->query('show_zero_stock')) {
                    $inventory[] = [
                        'item_id' => $item->id,
                        'item_name' => $item->name,
                        'item_sku' => $item->sku,
                        'item_barcode' => $item->barcode,
                        'unit_name' => $item->unit?->name,
                        'quantity' => $totalStock['quantity'],
                        'weighted_average_cost' => $totalStock['weighted_average_cost'],
                        'total_value' => $totalStock['total_value'],
                        'minimum_quantity' => $item->minimum_quantity,
                        'is_low_stock' => $item->minimum_quantity && $totalStock['quantity'] <= $item->minimum_quantity,
                        'warehouses' => array_values($stockByWarehouse),
                    ];
                }
            }
        }

        // Sort by low stock first, then by name
        usort($inventory, function ($a, $b) {
            if ($a['is_low_stock'] != $b['is_low_stock']) {
                return $b['is_low_stock'] <=> $a['is_low_stock'];
            }
            return $a['item_name'] <=> $b['item_name'];
        });

        return response()->json([
            'inventory' => $inventory,
            'summary' => [
                'total_items' => count($inventory),
                'total_value' => array_sum(array_column($inventory, 'total_value')),
                'low_stock_items' => count(array_filter($inventory, fn($i) => $i['is_low_stock'])),
            ],
        ]);
    }

    /**
     * Get stock card (movement history) for a specific item.
     *
     * @param Request $request
     * @param int $itemId
     * @return JsonResponse
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
     *
     * @param Request $request
     * @param int $warehouseId
     * @return JsonResponse
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
     *
     * @param Request $request
     * @return JsonResponse
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
     *
     * @param Request $request
     * @return JsonResponse
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
