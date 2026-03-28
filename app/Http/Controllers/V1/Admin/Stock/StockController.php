<?php

namespace App\Http\Controllers\V1\Admin\Stock;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\DemandForecastService;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PDF;

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
        $categoryId = $request->query('category_id');
        $itemId = $request->query('item_id');
        $orderByField = $request->query('orderByField', 'name');
        $orderBy = in_array(strtolower($request->query('orderBy', 'asc')), ['asc', 'desc']) ? $request->query('orderBy', 'asc') : 'asc';
        $limit = (int) $request->query('limit', 15);

        // Build query
        $query = Item::where('company_id', $companyId)
            ->where('track_quantity', true)
            ->with(['unit', 'currency', 'itemCategory']);

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
            $query->where('category_id', $categoryId);
        }

        // Apply sorting — whitelist allowed orderBy fields to prevent SQL injection
        // We can sort by quantity because StockService updates the item.quantity field
        $allowedSortFields = ['name', 'sku', 'price', 'quantity', 'created_at'];
        if (in_array($orderByField, $allowedSortFields)) {
            $query->orderBy($orderByField, $orderBy);
        } else {
            $query->orderBy('name', 'asc');
        }
        // CLAUDE-CHECKPOINT

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

        if (! $item->track_quantity) {
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

        // When viewing ALL warehouses (no filter), we need to recalculate running balances
        // because stored balance_quantity/value are per-warehouse
        $needsBalanceRecalculation = ! $warehouseId && $movements->isNotEmpty();
        $movementBalances = [];

        if ($needsBalanceRecalculation) {
            // Sort by date ASC to calculate running balance chronologically
            $sortedMovements = $movements->sortBy([
                ['movement_date', 'asc'],
                ['id', 'asc'],
            ])->values();

            // Calculate running balance and WAC across all warehouses
            $runningQty = 0;
            $runningValue = 0;
            $movementBalances = [];

            foreach ($sortedMovements as $movement) {
                // Calculate WAC before this movement (for stock OUT)
                $currentWac = $runningQty > 0 ? (int) round($runningValue / $runningQty) : 0;

                // Calculate value change
                if ($movement->quantity > 0) {
                    // Stock IN: add at unit cost
                    $unitCost = $movement->unit_cost ?? 0;
                    $lineValue = (int) ($movement->quantity * $unitCost);
                    $runningValue += $lineValue;
                } else {
                    // Stock OUT: remove at calculated WAC (not stored total_cost which may be wrong)
                    $unitCost = $currentWac;
                    $lineValue = (int) (abs($movement->quantity) * $currentWac);
                    $runningValue = max(0, $runningValue - $lineValue);
                }

                $runningQty += $movement->quantity;

                $movementBalances[$movement->id] = [
                    'balance_quantity' => $runningQty,
                    'balance_value' => $runningValue,
                    'unit_cost' => $unitCost,
                    'line_value' => $lineValue,
                ];
            }

            // Reverse back to DESC order for display (newest first)
            $movements = $sortedMovements->reverse()->values();
        }

        // Format movements for response
        $formattedMovements = $movements->map(function ($movement) use ($needsBalanceRecalculation, $movementBalances) {
            // Get recalculated values for all-warehouse view
            $recalc = $needsBalanceRecalculation ? ($movementBalances[$movement->id] ?? null) : null;

            // Unit cost: use recalculated value, or calculate from stored total_cost, or use stored unit_cost
            $effectiveUnitCost = $movement->unit_cost;
            if ($recalc) {
                $effectiveUnitCost = $recalc['unit_cost'];
            } elseif ($movement->isStockOut() && $movement->unit_cost === null && $movement->total_cost !== null && $movement->total_cost > 0) {
                $effectiveUnitCost = (int) round(abs($movement->total_cost / $movement->quantity));
            }

            // Line value: use recalculated value or stored total_cost
            $lineValue = $recalc ? $recalc['line_value'] : abs($movement->total_cost ?? 0);

            // Balance values: use recalculated or stored
            $balanceQty = $recalc ? $recalc['balance_quantity'] : $movement->balance_quantity;
            $balanceVal = $recalc ? $recalc['balance_value'] : $movement->balance_value;

            return [
                'id' => $movement->id,
                'date' => $movement->movement_date->format('Y-m-d'),
                'movement_date' => $movement->movement_date->format('Y-m-d'),
                'source_type' => $movement->source_type,
                'source_type_label' => $movement->source_type_label,
                'source_id' => $movement->source_id,
                'warehouse_id' => $movement->warehouse_id,
                'warehouse_name' => $movement->warehouse?->name,
                'quantity' => $movement->quantity,
                'absolute_quantity' => $movement->absolute_quantity,
                'unit_cost' => $effectiveUnitCost,
                'total_cost' => $movement->total_cost,
                'line_value' => $lineValue,
                'balance_quantity' => $balanceQty,
                'balance_value' => $balanceVal,
                'weighted_average_cost' => $movement->weighted_average_cost,
                'notes' => $movement->notes,
                'description' => $movement->notes,
                'reference' => $movement->source_id ? "#{$movement->source_id}" : null,
                'created_by' => $movement->creator?->name,
                'is_stock_in' => $movement->isStockIn(),
                'is_stock_out' => $movement->isStockOut(),
            ];
        });

        // Calculate opening and closing balance
        // Closing balance is the current stock (already summed across warehouses)
        $closingBalance = [
            'quantity' => $currentStock['quantity'],
            'value' => $currentStock['total_value'],
        ];

        // Opening balance logic:
        // - If date filter applied: balance BEFORE the first movement in the filtered period
        // - If NO date filter (all time): balance AFTER the first movement (initial recorded state)
        $hasDateFilter = $fromDate || $toDate;
        $openingBalance = ['quantity' => 0, 'value' => 0];

        if ($movements->isNotEmpty()) {
            // Get the oldest movement (last in DESC-sorted list)
            $oldestMovement = $movements->last();

            if ($hasDateFilter) {
                // With date filter: show balance BEFORE the filtered period starts
                if ($needsBalanceRecalculation) {
                    $oldestBalance = $movementBalances[$oldestMovement->id] ?? null;
                    if ($oldestBalance) {
                        $openingBalance = [
                            'quantity' => $oldestBalance['balance_quantity'] - $oldestMovement->quantity,
                            'value' => max(0, $oldestBalance['balance_value'] - abs($oldestMovement->total_cost ?? 0)),
                        ];
                    }
                } else {
                    $openingBalance = [
                        'quantity' => $oldestMovement->balance_quantity - $oldestMovement->quantity,
                        'value' => max(0, $oldestMovement->balance_value - abs($oldestMovement->quantity * ($oldestMovement->unit_cost ?? 0))),
                    ];
                }
            } else {
                // No date filter (all time): show balance AFTER the first movement (initial state)
                if ($needsBalanceRecalculation) {
                    $oldestBalance = $movementBalances[$oldestMovement->id] ?? null;
                    if ($oldestBalance) {
                        $openingBalance = [
                            'quantity' => $oldestBalance['balance_quantity'],
                            'value' => $oldestBalance['balance_value'],
                        ];
                    }
                } else {
                    $openingBalance = [
                        'quantity' => $oldestMovement->balance_quantity,
                        'value' => $oldestMovement->balance_value,
                    ];
                }
            }
        }

        return response()->json([
            'data' => [
                'item' => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'sku' => $item->sku,
                    'barcode' => $item->barcode,
                    'unit_name' => $item->unit?->name,
                    'minimum_quantity' => $item->minimum_quantity,
                ],
                'opening_balance' => $openingBalance,
                'closing_balance' => $closingBalance,
                'current_stock' => $currentStock,
                'movements' => $formattedMovements,
                'summary' => [
                    'total_movements' => $movements->count(),
                    'stock_in_count' => $movements->filter(fn ($m) => $m->isStockIn())->count(),
                    'stock_out_count' => $movements->filter(fn ($m) => $m->isStockOut())->count(),
                ],
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
                'low_stock_items' => count(array_filter($inventory, fn ($i) => $i['is_low_stock'])),
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
     * Get stock dashboard summary for the dashboard widget.
     *
     * Returns aggregate inventory data: total value, item count, total quantity,
     * low stock count, and top 5 low stock items for quick dashboard overview.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function dashboardSummary(Request $request): JsonResponse
    {
        $companyId = $request->header('company');

        // Get all trackable items
        $items = Item::where('company_id', $companyId)
            ->where('track_quantity', true)
            ->with(['unit'])
            ->get();

        $totalValue = 0;
        $totalQuantity = 0;
        $lowStockItems = [];

        foreach ($items as $item) {
            $stock = $this->stockService->getItemStock($companyId, $item->id);

            $totalValue += $stock['total_value'];
            $totalQuantity += $stock['quantity'];

            // Check for low stock: item has minimum_quantity set and current stock is at or below minimum
            if ($item->minimum_quantity && $item->minimum_quantity > 0 && $stock['quantity'] <= $item->minimum_quantity) {
                $shortage = $item->minimum_quantity - $stock['quantity'];
                $shortagePercentage = $item->minimum_quantity > 0
                    ? round(($shortage / $item->minimum_quantity) * 100, 2)
                    : 0;

                $lowStockItems[] = [
                    'item_id' => $item->id,
                    'item_name' => $item->name,
                    'item_sku' => $item->sku,
                    'unit_name' => $item->unit?->name,
                    'current_quantity' => $stock['quantity'],
                    'minimum_quantity' => $item->minimum_quantity,
                    'shortage' => $shortage,
                    'shortage_percentage' => $shortagePercentage,
                ];
            }
        }

        // Sort low stock items by shortage percentage (most critical first) and take top 5
        usort($lowStockItems, function ($a, $b) {
            return $b['shortage_percentage'] <=> $a['shortage_percentage'];
        });

        $topLowStock = array_slice($lowStockItems, 0, 5);

        // Get last 5 recent movements for the dashboard widget
        $recentMovements = StockMovement::where('company_id', $companyId)
            ->with(['item:id,name', 'warehouse:id,name'])
            ->orderBy('movement_date', 'desc')
            ->orderBy('id', 'desc')
            ->limit(5)
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'date' => $m->movement_date->format('Y-m-d'),
                'item_name' => $m->item?->name,
                'warehouse_name' => $m->warehouse?->name,
                'source_type' => $m->source_type,
                'source_type_label' => $m->source_type_label,
                'quantity' => $m->quantity,
                'is_stock_in' => $m->isStockIn(),
            ]);

        return response()->json([
            'total_value' => $totalValue,
            'total_items' => $items->count(),
            'total_quantity' => $totalQuantity,
            'low_stock_count' => count($lowStockItems),
            'low_stock_items' => $topLowStock,
            'recent_movements' => $recentMovements,
        ]);
    }

    /**
     * Get low stock items with pagination.
     */
    public function lowStock(Request $request): JsonResponse
    {
        $companyId = $request->header('company');
        $warehouseId = $request->query('warehouse_id');
        $search = $request->query('search');
        $severity = $request->query('severity');
        $orderByField = $request->query('orderByField', 'shortage_percentage');
        $orderBy = $request->query('orderBy', 'desc');
        $limit = (int) $request->query('limit', 10);
        $page = (int) $request->query('page', 1);

        // Build query for items with track_quantity and minimum_quantity set
        $query = Item::where('company_id', $companyId)
            ->where('track_quantity', true)
            ->whereNotNull('minimum_quantity')
            ->where('minimum_quantity', '>', 0)
            ->with(['unit', 'currency']);

        // Apply search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        // Get all matching items first
        $allItems = $query->get();

        // Filter for low stock (quantity <= minimum_quantity)
        $lowStockItems = $allItems->filter(function ($item) use ($companyId, $warehouseId) {
            $stock = $this->stockService->getItemStock($companyId, $item->id, $warehouseId);

            return $stock['quantity'] <= $item->minimum_quantity;
        });

        // Map to response format with shortage calculation
        $formatted = $lowStockItems->map(function ($item) use ($companyId, $warehouseId) {
            $stock = $this->stockService->getItemStock($companyId, $item->id, $warehouseId);
            $shortage = $item->minimum_quantity - $stock['quantity'];
            $shortagePercentage = $item->minimum_quantity > 0
                ? round(($shortage / $item->minimum_quantity) * 100, 2)
                : 0;

            return [
                'item_id' => $item->id,
                'item_name' => $item->name,
                'item_sku' => $item->sku,
                'item_barcode' => $item->barcode,
                'unit_name' => $item->unit?->name,
                'current_quantity' => $stock['quantity'],
                'minimum_quantity' => $item->minimum_quantity,
                'shortage' => $shortage,
                'shortage_percentage' => $shortagePercentage,
                'weighted_average_cost' => $stock['weighted_average_cost'],
            ];
        })->values();

        // Apply severity filter after calculating shortage
        if ($severity === 'critical') {
            $formatted = $formatted->filter(fn ($i) => $i['shortage_percentage'] >= 75);
        } elseif ($severity === 'warning') {
            $formatted = $formatted->filter(fn ($i) => $i['shortage_percentage'] >= 25 && $i['shortage_percentage'] < 75);
        } elseif ($severity === 'low') {
            $formatted = $formatted->filter(fn ($i) => $i['shortage_percentage'] < 25);
        }

        // Sort
        $sorted = $formatted->sortBy(function ($item) use ($orderByField) {
            return $item[$orderByField] ?? 0;
        }, SORT_REGULAR, $orderBy === 'desc');

        // Manual pagination
        $total = $sorted->count();
        $lastPage = max(1, (int) ceil($total / $limit));
        $offset = ($page - 1) * $limit;
        $paginatedData = $sorted->slice($offset, $limit)->values();

        return response()->json([
            'data' => $paginatedData,
            'meta' => [
                'current_page' => $page,
                'last_page' => $lastPage,
                'per_page' => $limit,
                'total' => $total,
            ],
        ]);
    }

    /**
     * Stock Overview Dashboard — KPIs, charts, recent movements, top items.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $companyId = $request->header('company');

        // Get all trackable items with stock
        $items = Item::where('company_id', $companyId)
            ->where('track_quantity', true)
            ->with(['unit'])
            ->get();

        $totalValue = 0;
        $totalItems = 0;
        $lowStockCount = 0;
        $criticalStockCount = 0;
        $topItemsByValue = [];

        foreach ($items as $item) {
            $stock = $this->stockService->getItemStock($companyId, $item->id);
            $qty = $stock['quantity'];
            $val = $stock['total_value'];

            if ($qty != 0 || $val != 0) {
                $totalItems++;
                $totalValue += $val;

                $topItemsByValue[] = [
                    'item_id' => $item->id,
                    'name' => $item->name,
                    'sku' => $item->sku,
                    'quantity' => $qty,
                    'unit_name' => $item->unit?->name,
                    'total_value' => $val,
                ];
            }

            if ($qty <= 0) {
                $criticalStockCount++;
            } elseif ($item->minimum_quantity && $item->minimum_quantity > 0 && $qty <= $item->minimum_quantity) {
                $lowStockCount++;
            }
        }

        // Top 5 items by value
        usort($topItemsByValue, fn ($a, $b) => $b['total_value'] <=> $a['total_value']);
        $topItemsByValue = array_slice($topItemsByValue, 0, 5);

        // Warehouses with stock
        $warehouses = Warehouse::where('company_id', $companyId)
            ->where('is_active', true)
            ->get();

        $warehousesWithStock = 0;
        $warehouseValues = [];
        foreach ($warehouses as $warehouse) {
            $whValue = 0;
            $hasStock = false;
            $whMovements = StockMovement::where('company_id', $companyId)
                ->where('warehouse_id', $warehouse->id)
                ->selectRaw('item_id, SUM(quantity) as total_qty')
                ->groupBy('item_id')
                ->havingRaw('SUM(quantity) != 0')
                ->get();

            if ($whMovements->isNotEmpty()) {
                $hasStock = true;
                foreach ($whMovements as $m) {
                    $itemStock = $this->stockService->getItemStock($companyId, $m->item_id, $warehouse->id);
                    $whValue += $itemStock['total_value'];
                }
            }

            if ($hasStock) {
                $warehousesWithStock++;
                $warehouseValues[] = [
                    'warehouse_id' => $warehouse->id,
                    'name' => $warehouse->name,
                    'value' => $whValue,
                ];
            }
        }

        // Recent 10 movements
        $recentMovements = StockMovement::where('company_id', $companyId)
            ->with(['item:id,name', 'warehouse:id,name'])
            ->orderBy('movement_date', 'desc')
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'date' => $m->movement_date->format('Y-m-d'),
                'item_name' => $m->item?->name,
                'warehouse_name' => $m->warehouse?->name,
                'source_type' => $m->source_type,
                'source_type_label' => $m->source_type_label,
                'quantity' => $m->quantity,
                'is_stock_in' => $m->isStockIn(),
            ]);

        // Movement trend — daily count for last 30 days
        $thirtyDaysAgo = now()->subDays(30)->startOfDay();
        $movementTrend = StockMovement::where('company_id', $companyId)
            ->where('movement_date', '>=', $thirtyDaysAgo)
            ->selectRaw('DATE(movement_date) as date, COUNT(*) as count')
            ->groupByRaw('DATE(movement_date)')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date')
            ->toArray();

        // Fill in missing days with 0
        $trendData = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $trendData[] = [
                'date' => $date,
                'count' => $movementTrend[$date] ?? 0,
            ];
        }

        return response()->json([
            'total_items' => $totalItems,
            'total_value' => $totalValue,
            'warehouses_count' => $warehousesWithStock,
            'low_stock_count' => $lowStockCount,
            'critical_stock_count' => $criticalStockCount,
            'recent_movements' => $recentMovements,
            'top_items_by_value' => $topItemsByValue,
            'movement_trend' => $trendData,
            'stock_by_warehouse' => $warehouseValues,
        ]);
    }
    // CLAUDE-CHECKPOINT

    /**
     * Generate PDF for an item's stock card (lagerska kartica).
     *
     * Reuses the itemCard logic to fetch movement history, then renders
     * the lagerska-kartica blade template as a PDF.
     *
     * @param  int  $itemId  The item ID
     */
    public function itemCardPdf(Request $request, int $itemId)
    {
        $companyId = $request->header('company');
        $warehouseId = $request->query('warehouse_id');
        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');

        $item = Item::where('company_id', $companyId)
            ->where('id', $itemId)
            ->with(['unit', 'currency'])
            ->firstOrFail();

        $company = \App\Models\Company::with('address')->find($companyId);
        $currency = \App\Models\Currency::where('code', 'MKD')->first()
            ?: \App\Models\Currency::first();

        $warehouseName = null;
        if ($warehouseId) {
            $warehouse = Warehouse::find($warehouseId);
            $warehouseName = $warehouse?->name;
        }

        // Fetch movements (all, no limit for PDF)
        $movements = $this->stockService->getMovementHistory(
            $companyId,
            $itemId,
            $warehouseId,
            $fromDate,
            $toDate,
            10000
        );

        // Calculate running balances (same logic as itemCard)
        $needsRecalc = ! $warehouseId && $movements->isNotEmpty();

        // Sort chronologically for PDF (oldest first)
        $sortedMovements = $movements->sortBy([
            ['movement_date', 'asc'],
            ['id', 'asc'],
        ])->values();

        $runningQty = 0;
        $runningValue = 0;
        $formattedMovements = [];

        foreach ($sortedMovements as $movement) {
            $currentWac = $runningQty > 0 ? (int) round($runningValue / $runningQty) : 0;

            if ($movement->quantity > 0) {
                $unitCost = $movement->unit_cost ?? 0;
                $totalCost = (int) ($movement->quantity * $unitCost);
                $runningValue += $totalCost;
            } else {
                $unitCost = $currentWac;
                $totalCost = (int) (abs($movement->quantity) * $currentWac);
                $runningValue = max(0, $runningValue - $totalCost);
            }

            $runningQty += $movement->quantity;

            $formattedMovements[] = [
                'date' => $movement->movement_date->format('d.m.Y'),
                'document' => $movement->notes ?: ($movement->source_type_label ?? $movement->source_type),
                'source_type' => $movement->source_type,
                'quantity' => $movement->quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $movement->quantity > 0 ? $totalCost : -$totalCost,
                'balance_quantity' => $runningQty,
                'balance_value' => $runningValue,
            ];
        }

        // Opening balance: state before first movement
        $openingBalance = [
            'quantity' => 0,
            'value' => 0,
            'wac' => 0,
        ];

        view()->share([
            'company' => $company,
            'currency' => $currency,
            'item_name' => $item->name,
            'item_sku' => $item->sku,
            'item_barcode' => $item->barcode,
            'item_unit' => $item->unit?->name,
            'min_stock' => $item->minimum_quantity,
            'max_stock' => null,
            'warehouse_name' => $warehouseName ?? 'Сите магацини',
            'from_date' => $fromDate ?: 'почеток',
            'to_date' => $toDate ?: date('d.m.Y'),
            'opening_balance' => $openingBalance,
            'movements' => $formattedMovements,
        ]);

        $pdf = PDF::loadView('app.pdf.reports.lagerska-kartica')
            ->setPaper('a4', 'landscape');

        if ($request->has('preview')) {
            return view('app.pdf.reports.lagerska-kartica');
        }

        $filename = "lagerska-kartica-{$item->sku}-" . date('Y-m-d') . '.pdf';

        return $pdf->stream($filename);
    }

    /**
     * Get demand forecast for low stock items.
     * Uses moving average of last 90 days consumption.
     */
    public function demandForecast(Request $request): JsonResponse
    {
        $companyId = $request->header('company');
        $itemIds = $request->query('item_ids');

        $service = app(DemandForecastService::class);
        $forecasts = $service->forecastItems((int) $companyId, $itemIds);

        return response()->json(['data' => $forecasts]);
    }

    /**
     * AI-enhanced demand analysis for critical items.
     * Triggers Gemini analysis for items with < 14 days of stock.
     */
    public function demandForecastAI(Request $request): JsonResponse
    {
        $companyId = $request->header('company');

        $service = app(DemandForecastService::class);
        $forecasts = $service->forecastItems((int) $companyId);
        $analysis = $service->analyzeWithAI((int) $companyId, $forecasts);

        return response()->json(['data' => $analysis]);
    }
}

// CLAUDE-CHECKPOINT
