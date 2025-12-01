<?php

namespace App\Http\Controllers\V1\Admin\Stock;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\StockService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Stock Reports Controller
 *
 * Provides read-only stock evidence endpoints for:
 * - Item Stock Card (movement history with running balance)
 * - Warehouse Inventory (current stock by warehouse)
 * - Inventory Valuation (total stock value report)
 *
 * All endpoints require FACTURINO_STOCK_V1_ENABLED feature flag.
 */
class StockReportsController extends Controller
{
    protected StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Check if stock module is enabled, return 403 if not.
     */
    protected function checkStockEnabled(): ?JsonResponse
    {
        if (! StockService::isEnabled()) {
            return response()->json([
                'success' => false,
                'message' => 'Stock module is not enabled.',
            ], 403);
        }

        return null;
    }

    /**
     * Item Stock Card - Movement history with running balance.
     *
     * GET /api/v1/stock/items/{item}/card
     */
    public function itemCard(Request $request, Item $item): JsonResponse
    {
        if ($error = $this->checkStockEnabled()) {
            return $error;
        }

        // Authorization: item must belong to user's company
        $companyId = $request->header('company');
        if ($item->company_id != $companyId) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found.',
            ], 404);
        }

        // Parse filters
        $warehouseId = $request->input('warehouse_id');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        // Validate warehouse if provided
        $warehouse = null;
        if ($warehouseId) {
            $warehouse = Warehouse::where('id', $warehouseId)
                ->where('company_id', $companyId)
                ->first();

            if (! $warehouse) {
                return response()->json([
                    'success' => false,
                    'message' => 'Warehouse not found.',
                ], 404);
            }
        }

        // Get opening balance (balance before from_date)
        $openingBalance = $this->calculateOpeningBalance(
            $companyId,
            $item->id,
            $warehouseId,
            $fromDate
        );

        // Get movements within date range
        $movements = $this->getMovementsWithDetails(
            $companyId,
            $item->id,
            $warehouseId,
            $fromDate,
            $toDate
        );

        // Calculate closing balance
        $closingBalance = $this->calculateClosingBalance(
            $openingBalance,
            $movements
        );

        // Format movements for response
        $formattedMovements = $movements->map(function ($movement) {
            return [
                'id' => $movement->id,
                'date' => $movement->movement_date->format('Y-m-d'),
                'source_type' => $this->getSourceTypeLabel($movement->source_type),
                'source_type_raw' => $movement->source_type,
                'source_id' => $movement->source_id,
                'reference' => $this->getMovementReference($movement),
                'description' => $this->getMovementDescription($movement),
                'quantity' => (float) $movement->quantity,
                'unit_cost' => $movement->unit_cost,
                'line_value' => $movement->total_cost,
                'balance_quantity' => (float) $movement->balance_quantity,
                'balance_value' => (int) $movement->balance_value,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'item' => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'sku' => $item->sku,
                    'barcode' => $item->barcode,
                    'unit' => $item->unit_name,
                ],
                'warehouse' => $warehouse ? [
                    'id' => $warehouse->id,
                    'name' => $warehouse->name,
                ] : null,
                'filters' => [
                    'from_date' => $fromDate,
                    'to_date' => $toDate,
                ],
                'opening_balance' => [
                    'quantity' => $openingBalance['quantity'],
                    'value' => $openingBalance['value'],
                ],
                'movements' => $formattedMovements,
                'closing_balance' => [
                    'quantity' => $closingBalance['quantity'],
                    'value' => $closingBalance['value'],
                ],
            ],
        ]);
    }

    /**
     * Warehouse Inventory - Current stock by warehouse.
     *
     * GET /api/v1/stock/warehouses/{warehouse}/inventory
     */
    public function warehouseInventory(Request $request, Warehouse $warehouse): JsonResponse
    {
        if ($error = $this->checkStockEnabled()) {
            return $error;
        }

        // Authorization: warehouse must belong to user's company
        $companyId = $request->header('company');
        if ($warehouse->company_id != $companyId) {
            return response()->json([
                'success' => false,
                'message' => 'Warehouse not found.',
            ], 404);
        }

        // Parse filters
        $asOfDate = $request->input('as_of_date', Carbon::today()->format('Y-m-d'));
        $search = $request->input('search');

        // Get all trackable items for this company
        $itemsQuery = Item::where('company_id', $companyId)
            ->where('track_quantity', true);

        if ($search) {
            $itemsQuery->where(function ($query) use ($search) {
                $query->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('sku', 'LIKE', "%{$search}%")
                    ->orWhere('barcode', 'LIKE', "%{$search}%");
            });
        }

        $items = $itemsQuery->get();

        // Get inventory for each item
        $inventoryItems = [];
        foreach ($items as $item) {
            $stock = $this->getStockAsOfDate(
                $companyId,
                $item->id,
                $warehouse->id,
                $asOfDate
            );

            // Only include items with non-zero quantity
            if ($stock['quantity'] != 0) {
                $inventoryItems[] = [
                    'item_id' => $item->id,
                    'name' => $item->name,
                    'sku' => $item->sku,
                    'barcode' => $item->barcode,
                    'unit' => $item->unit_name,
                    'quantity' => $stock['quantity'],
                    'unit_cost' => $stock['weighted_average_cost'],
                    'total_value' => $stock['total_value'],
                ];
            }
        }

        // Calculate totals
        $totalQuantity = array_sum(array_column($inventoryItems, 'quantity'));
        $totalValue = array_sum(array_column($inventoryItems, 'total_value'));

        return response()->json([
            'success' => true,
            'data' => [
                'warehouse' => [
                    'id' => $warehouse->id,
                    'name' => $warehouse->name,
                    'code' => $warehouse->code,
                ],
                'as_of_date' => $asOfDate,
                'items' => $inventoryItems,
                'totals' => [
                    'quantity' => $totalQuantity,
                    'value' => $totalValue,
                ],
            ],
        ]);
    }

    /**
     * Inventory Valuation - Total stock value report.
     *
     * GET /api/v1/stock/inventory-valuation
     */
    public function inventoryValuation(Request $request): JsonResponse
    {
        if ($error = $this->checkStockEnabled()) {
            return $error;
        }

        $companyId = $request->header('company');
        $asOfDate = $request->input('as_of_date', Carbon::today()->format('Y-m-d'));
        $warehouseId = $request->input('warehouse_id');
        $groupBy = $request->input('group_by', 'warehouse');

        // Validate warehouse if provided
        if ($warehouseId) {
            $warehouse = Warehouse::where('id', $warehouseId)
                ->where('company_id', $companyId)
                ->first();

            if (! $warehouse) {
                return response()->json([
                    'success' => false,
                    'message' => 'Warehouse not found.',
                ], 404);
            }
        }

        // Get all warehouses for this company
        $warehousesQuery = Warehouse::where('company_id', $companyId)
            ->where('is_active', true);

        if ($warehouseId) {
            $warehousesQuery->where('id', $warehouseId);
        }

        $warehouses = $warehousesQuery->get();

        // Get trackable items
        $items = Item::where('company_id', $companyId)
            ->where('track_quantity', true)
            ->get();

        if ($groupBy === 'warehouse') {
            return $this->valuationByWarehouse($warehouses, $items, $companyId, $asOfDate);
        } else {
            return $this->valuationByItem($warehouses, $items, $companyId, $asOfDate);
        }
    }

    /**
     * Inventory List - Simple list for physical counting.
     *
     * GET /api/v1/stock/inventory-list
     */
    public function inventoryList(Request $request): JsonResponse
    {
        if ($error = $this->checkStockEnabled()) {
            return $error;
        }

        $companyId = $request->header('company');
        $asOfDate = $request->input('as_of_date', Carbon::today()->format('Y-m-d'));
        $warehouseId = $request->input('warehouse_id');

        // Build warehouse query
        $warehousesQuery = Warehouse::where('company_id', $companyId)
            ->where('is_active', true);

        if ($warehouseId) {
            $warehouse = Warehouse::where('id', $warehouseId)
                ->where('company_id', $companyId)
                ->first();

            if (! $warehouse) {
                return response()->json([
                    'success' => false,
                    'message' => 'Warehouse not found.',
                ], 404);
            }
            $warehousesQuery->where('id', $warehouseId);
        }

        $warehouses = $warehousesQuery->get();

        // Get trackable items
        $items = Item::where('company_id', $companyId)
            ->where('track_quantity', true)
            ->orderBy('name')
            ->get();

        $inventoryList = [];

        foreach ($warehouses as $warehouse) {
            foreach ($items as $item) {
                $stock = $this->getStockAsOfDate(
                    $companyId,
                    $item->id,
                    $warehouse->id,
                    $asOfDate
                );

                if ($stock['quantity'] != 0) {
                    $inventoryList[] = [
                        'warehouse_id' => $warehouse->id,
                        'warehouse_name' => $warehouse->name,
                        'warehouse_code' => $warehouse->code,
                        'item_id' => $item->id,
                        'item_name' => $item->name,
                        'sku' => $item->sku,
                        'barcode' => $item->barcode,
                        'unit' => $item->unit_name,
                        'quantity' => $stock['quantity'],
                    ];
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'as_of_date' => $asOfDate,
                'warehouse_id' => $warehouseId,
                'items' => $inventoryList,
                'total_items' => count($inventoryList),
            ],
        ]);
    }

    /**
     * Get list of warehouses for dropdown.
     *
     * GET /api/v1/stock/warehouses
     */
    public function warehouses(Request $request): JsonResponse
    {
        if ($error = $this->checkStockEnabled()) {
            return $error;
        }

        $companyId = $request->header('company');

        $warehouses = Warehouse::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'is_default']);

        return response()->json([
            'success' => true,
            'data' => $warehouses,
        ]);
    }

    // ========================================
    // HELPER METHODS
    // ========================================

    /**
     * Calculate opening balance before a date.
     */
    protected function calculateOpeningBalance(
        int $companyId,
        int $itemId,
        ?int $warehouseId,
        ?string $fromDate
    ): array {
        if (! $fromDate) {
            return ['quantity' => 0, 'value' => 0];
        }

        $query = StockMovement::where('company_id', $companyId)
            ->where('item_id', $itemId)
            ->where('movement_date', '<', $fromDate);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        $lastMovement = $query
            ->orderBy('movement_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        if (! $lastMovement) {
            return ['quantity' => 0, 'value' => 0];
        }

        return [
            'quantity' => (float) $lastMovement->balance_quantity,
            'value' => (int) $lastMovement->balance_value,
        ];
    }

    /**
     * Get movements with details.
     */
    protected function getMovementsWithDetails(
        int $companyId,
        int $itemId,
        ?int $warehouseId,
        ?string $fromDate,
        ?string $toDate
    ) {
        $query = StockMovement::where('company_id', $companyId)
            ->where('item_id', $itemId)
            ->with(['warehouse', 'creator']);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($fromDate) {
            $query->where('movement_date', '>=', $fromDate);
        }

        if ($toDate) {
            $query->where('movement_date', '<=', $toDate);
        }

        return $query
            ->orderBy('movement_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();
    }

    /**
     * Calculate closing balance from opening and movements.
     */
    protected function calculateClosingBalance(array $openingBalance, $movements): array
    {
        if ($movements->isEmpty()) {
            return $openingBalance;
        }

        $lastMovement = $movements->last();

        return [
            'quantity' => (float) $lastMovement->balance_quantity,
            'value' => (int) $lastMovement->balance_value,
        ];
    }

    /**
     * Get stock as of a specific date.
     */
    protected function getStockAsOfDate(
        int $companyId,
        int $itemId,
        int $warehouseId,
        string $asOfDate
    ): array {
        $lastMovement = StockMovement::where('company_id', $companyId)
            ->where('item_id', $itemId)
            ->where('warehouse_id', $warehouseId)
            ->where('movement_date', '<=', $asOfDate)
            ->orderBy('movement_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        if (! $lastMovement) {
            return [
                'quantity' => 0,
                'total_value' => 0,
                'weighted_average_cost' => 0,
            ];
        }

        $quantity = (float) $lastMovement->balance_quantity;
        $value = (int) $lastMovement->balance_value;

        return [
            'quantity' => $quantity,
            'total_value' => $value,
            'weighted_average_cost' => $quantity > 0 ? (int) round($value / $quantity) : 0,
        ];
    }

    /**
     * Get human-readable source type label.
     */
    protected function getSourceTypeLabel(string $sourceType): string
    {
        $labels = [
            StockMovement::SOURCE_INITIAL => 'initial',
            StockMovement::SOURCE_BILL_ITEM => 'bill',
            StockMovement::SOURCE_INVOICE_ITEM => 'invoice',
            StockMovement::SOURCE_ADJUSTMENT => 'adjustment',
            StockMovement::SOURCE_TRANSFER_IN => 'transfer_in',
            StockMovement::SOURCE_TRANSFER_OUT => 'transfer_out',
        ];

        return $labels[$sourceType] ?? $sourceType;
    }

    /**
     * Get reference number for a movement.
     */
    protected function getMovementReference(StockMovement $movement): ?string
    {
        $meta = $movement->meta ?? [];

        switch ($movement->source_type) {
            case StockMovement::SOURCE_BILL_ITEM:
                return $meta['bill_number'] ?? null;
            case StockMovement::SOURCE_INVOICE_ITEM:
                return $meta['invoice_number'] ?? null;
            case StockMovement::SOURCE_TRANSFER_IN:
            case StockMovement::SOURCE_TRANSFER_OUT:
                return 'TRF-'.$movement->id;
            default:
                return null;
        }
    }

    /**
     * Get description for a movement.
     */
    protected function getMovementDescription(StockMovement $movement): ?string
    {
        return $movement->notes;
    }

    /**
     * Valuation report grouped by warehouse.
     */
    protected function valuationByWarehouse($warehouses, $items, int $companyId, string $asOfDate): JsonResponse
    {
        $warehouseData = [];
        $grandTotalQuantity = 0;
        $grandTotalValue = 0;

        foreach ($warehouses as $warehouse) {
            $warehouseQuantity = 0;
            $warehouseValue = 0;

            foreach ($items as $item) {
                $stock = $this->getStockAsOfDate(
                    $companyId,
                    $item->id,
                    $warehouse->id,
                    $asOfDate
                );

                $warehouseQuantity += $stock['quantity'];
                $warehouseValue += $stock['total_value'];
            }

            if ($warehouseQuantity != 0 || $warehouseValue != 0) {
                $warehouseData[] = [
                    'warehouse' => [
                        'id' => $warehouse->id,
                        'name' => $warehouse->name,
                        'code' => $warehouse->code,
                    ],
                    'total_quantity' => $warehouseQuantity,
                    'total_value' => $warehouseValue,
                ];

                $grandTotalQuantity += $warehouseQuantity;
                $grandTotalValue += $warehouseValue;
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'as_of_date' => $asOfDate,
                'group_by' => 'warehouse',
                'warehouses' => $warehouseData,
                'grand_total' => [
                    'quantity' => $grandTotalQuantity,
                    'value' => $grandTotalValue,
                ],
            ],
        ]);
    }

    /**
     * Valuation report grouped by item.
     */
    protected function valuationByItem($warehouses, $items, int $companyId, string $asOfDate): JsonResponse
    {
        $itemData = [];
        $grandTotalQuantity = 0;
        $grandTotalValue = 0;

        foreach ($items as $item) {
            $itemQuantity = 0;
            $itemValue = 0;

            foreach ($warehouses as $warehouse) {
                $stock = $this->getStockAsOfDate(
                    $companyId,
                    $item->id,
                    $warehouse->id,
                    $asOfDate
                );

                $itemQuantity += $stock['quantity'];
                $itemValue += $stock['total_value'];
            }

            if ($itemQuantity != 0 || $itemValue != 0) {
                $itemData[] = [
                    'item' => [
                        'id' => $item->id,
                        'name' => $item->name,
                        'sku' => $item->sku,
                        'barcode' => $item->barcode,
                        'unit' => $item->unit_name,
                    ],
                    'total_quantity' => $itemQuantity,
                    'total_value' => $itemValue,
                    'weighted_average_cost' => $itemQuantity > 0 ? (int) round($itemValue / $itemQuantity) : 0,
                ];

                $grandTotalQuantity += $itemQuantity;
                $grandTotalValue += $itemValue;
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'as_of_date' => $asOfDate,
                'group_by' => 'item',
                'items' => $itemData,
                'grand_total' => [
                    'quantity' => $grandTotalQuantity,
                    'value' => $grandTotalValue,
                ],
            ],
        ]);
    }
}
// CLAUDE-CHECKPOINT
