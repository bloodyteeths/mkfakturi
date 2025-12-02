<?php

namespace App\Services;

use App\Models\BillItem;
use App\Models\Company;
use App\Models\InvoiceItem;
use App\Models\Item;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Stock Management Service
 *
 * Core service for inventory management with weighted average cost valuation.
 * Handles all stock movements including:
 * - Stock IN from purchases (bills)
 * - Stock OUT from sales (invoices)
 * - Manual adjustments
 * - Warehouse transfers
 * - Initial stock setup
 *
 * Features:
 * - Weighted Average Cost (WAC) valuation method
 * - Multi-warehouse support
 * - Running balance tracking
 * - Audit trail of all movements
 * - Feature flag support (FACTURINO_STOCK_V1_ENABLED)
 *
 * @version 1.0.0
 */
class StockService
{
    /**
     * Check if stock module is enabled.
     * Stock module is always enabled - no feature flag needed.
     */
    public static function isEnabled(): bool
    {
        return true;
    }

    /**
     * Check if stock tracking is enabled for a specific item.
     */
    public function isItemTrackable(Item $item): bool
    {
        return self::isEnabled() && $item->track_quantity;
    }

    /**
     * Record stock IN movement (e.g., from purchase/bill).
     *
     * @param  float  $quantity  Must be positive
     * @param  int  $unitCost  Cost per unit in cents
     *
     * @throws Exception
     */
    public function recordStockIn(
        int $companyId,
        int $warehouseId,
        int $itemId,
        float $quantity,
        int $unitCost,
        string $sourceType = StockMovement::SOURCE_BILL_ITEM,
        ?int $sourceId = null,
        ?string $movementDate = null,
        ?string $notes = null,
        ?array $meta = null,
        ?int $createdBy = null
    ): StockMovement {
        if ($quantity <= 0) {
            throw new Exception('Stock IN quantity must be positive');
        }

        return $this->recordMovement(
            $companyId,
            $warehouseId,
            $itemId,
            abs($quantity), // Ensure positive
            $unitCost,
            $sourceType,
            $sourceId,
            $movementDate,
            $notes,
            $meta,
            $createdBy
        );
    }

    /**
     * Record stock OUT movement (e.g., from sale/invoice).
     *
     * @param  float  $quantity  Will be stored as negative
     *
     * @throws Exception
     */
    public function recordStockOut(
        int $companyId,
        int $warehouseId,
        int $itemId,
        float $quantity,
        string $sourceType = StockMovement::SOURCE_INVOICE_ITEM,
        ?int $sourceId = null,
        ?string $movementDate = null,
        ?string $notes = null,
        ?array $meta = null,
        ?int $createdBy = null
    ): StockMovement {
        if ($quantity <= 0) {
            throw new Exception('Stock OUT quantity must be positive');
        }

        // Get current weighted average cost for the item
        $currentStock = $this->getItemStock($companyId, $itemId, $warehouseId);

        // Stock OUT movements don't have their own unit cost
        // The cost is calculated from the weighted average
        return $this->recordMovement(
            $companyId,
            $warehouseId,
            $itemId,
            -abs($quantity), // Ensure negative for OUT
            null, // Unit cost will be calculated from WAC
            $sourceType,
            $sourceId,
            $movementDate,
            $notes,
            $meta,
            $createdBy
        );
    }

    /**
     * Record a stock adjustment (positive or negative).
     *
     * @param  float  $quantity  Can be positive (increase) or negative (decrease)
     * @param  int|null  $unitCost  Required for positive adjustments
     */
    public function recordAdjustment(
        int $companyId,
        int $warehouseId,
        int $itemId,
        float $quantity,
        ?int $unitCost = null,
        ?string $notes = null,
        ?array $meta = null,
        ?int $createdBy = null
    ): StockMovement {
        // For positive adjustments, require unit cost
        if ($quantity > 0 && $unitCost === null) {
            throw new Exception('Unit cost is required for positive stock adjustments');
        }

        return $this->recordMovement(
            $companyId,
            $warehouseId,
            $itemId,
            $quantity,
            $quantity > 0 ? $unitCost : null,
            StockMovement::SOURCE_ADJUSTMENT,
            null,
            null,
            $notes ?? 'Manual stock adjustment',
            $meta,
            $createdBy
        );
    }

    /**
     * Record initial stock for an item.
     *
     * @param  int  $unitCost  Cost per unit in cents
     */
    public function recordInitialStock(
        int $companyId,
        int $warehouseId,
        int $itemId,
        float $quantity,
        int $unitCost,
        ?string $notes = null,
        ?int $createdBy = null
    ): StockMovement {
        return $this->recordMovement(
            $companyId,
            $warehouseId,
            $itemId,
            $quantity,
            $unitCost,
            StockMovement::SOURCE_INITIAL,
            null,
            null,
            $notes ?? 'Initial stock entry',
            null,
            $createdBy
        );
    }

    /**
     * Transfer stock between warehouses.
     *
     * @return array ['out' => StockMovement, 'in' => StockMovement]
     *
     * @throws Exception
     */
    public function transferStock(
        int $companyId,
        int $fromWarehouseId,
        int $toWarehouseId,
        int $itemId,
        float $quantity,
        ?string $notes = null,
        ?int $createdBy = null
    ): array {
        if ($fromWarehouseId === $toWarehouseId) {
            throw new Exception('Source and destination warehouse cannot be the same');
        }

        if ($quantity <= 0) {
            throw new Exception('Transfer quantity must be positive');
        }

        // Check if there's enough stock in source warehouse
        $currentStock = $this->getItemStock($companyId, $itemId, $fromWarehouseId);
        if ($currentStock['quantity'] < $quantity) {
            throw new Exception("Insufficient stock in source warehouse. Available: {$currentStock['quantity']}, Requested: {$quantity}");
        }

        DB::beginTransaction();
        try {
            // Get current weighted average cost
            $wac = $currentStock['weighted_average_cost'] ?? 0;

            // Record OUT from source warehouse
            $outMovement = $this->recordMovement(
                $companyId,
                $fromWarehouseId,
                $itemId,
                -$quantity,
                null,
                StockMovement::SOURCE_TRANSFER_OUT,
                null,
                null,
                $notes ?? "Transfer to warehouse ID: {$toWarehouseId}",
                ['to_warehouse_id' => $toWarehouseId],
                $createdBy
            );

            // Record IN to destination warehouse (preserve cost)
            $inMovement = $this->recordMovement(
                $companyId,
                $toWarehouseId,
                $itemId,
                $quantity,
                $wac,
                StockMovement::SOURCE_TRANSFER_IN,
                null,
                null,
                $notes ?? "Transfer from warehouse ID: {$fromWarehouseId}",
                ['from_warehouse_id' => $fromWarehouseId, 'transfer_out_id' => $outMovement->id],
                $createdBy
            );

            // Link the movements
            $outMovement->update(['meta' => array_merge($outMovement->meta ?? [], ['transfer_in_id' => $inMovement->id])]);

            DB::commit();

            Log::info('Stock transfer completed', [
                'company_id' => $companyId,
                'item_id' => $itemId,
                'from_warehouse' => $fromWarehouseId,
                'to_warehouse' => $toWarehouseId,
                'quantity' => $quantity,
                'unit_cost' => $wac,
            ]);

            return [
                'out' => $outMovement,
                'in' => $inMovement,
            ];
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Core movement recording method.
     * Calculates running balance and weighted average cost.
     */
    protected function recordMovement(
        int $companyId,
        int $warehouseId,
        int $itemId,
        float $quantity,
        ?int $unitCost,
        string $sourceType,
        ?int $sourceId,
        ?string $movementDate,
        ?string $notes,
        ?array $meta,
        ?int $createdBy
    ): StockMovement {
        DB::beginTransaction();
        try {
            // Get current stock state for this item in this warehouse
            $currentStock = $this->getItemStock($companyId, $itemId, $warehouseId);
            $currentQuantity = $currentStock['quantity'];
            $currentValue = $currentStock['total_value'];
            $currentWac = $currentStock['weighted_average_cost'];

            // Calculate new balance
            $newQuantity = $currentQuantity + $quantity;

            // Calculate costs based on movement direction
            $totalCost = null;
            $newValue = $currentValue;

            if ($quantity > 0) {
                // Stock IN: Add value at provided unit cost
                $totalCost = (int) ($quantity * $unitCost);
                $newValue = $currentValue + $totalCost;
            } else {
                // Stock OUT: Remove value at weighted average cost
                if ($currentWac > 0) {
                    $totalCost = (int) (abs($quantity) * $currentWac);
                    $newValue = max(0, $currentValue - $totalCost);
                } else {
                    $totalCost = 0;
                    $newValue = 0;
                }
            }

            // Create the movement record
            $movement = StockMovement::create([
                'company_id' => $companyId,
                'warehouse_id' => $warehouseId,
                'item_id' => $itemId,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
                'movement_date' => $movementDate ? Carbon::parse($movementDate) : Carbon::now(),
                'notes' => $notes,
                'balance_quantity' => $newQuantity,
                'balance_value' => $newValue,
                'meta' => $meta,
                'created_by' => $createdBy ?? auth()->id(),
            ]);

            // Update item's quantity field if tracked
            $this->updateItemQuantity($itemId, $warehouseId);

            DB::commit();

            Log::info('Stock movement recorded', [
                'movement_id' => $movement->id,
                'company_id' => $companyId,
                'warehouse_id' => $warehouseId,
                'item_id' => $itemId,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'source_type' => $sourceType,
                'balance_quantity' => $newQuantity,
                'balance_value' => $newValue,
            ]);

            return $movement;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to record stock movement', [
                'error' => $e->getMessage(),
                'company_id' => $companyId,
                'item_id' => $itemId,
            ]);
            throw $e;
        }
    }

    /**
     * Get current stock for an item (optionally in specific warehouse).
     *
     * @param  int|null  $warehouseId  If null, returns total across all warehouses
     * @return array ['quantity' => float, 'total_value' => int, 'weighted_average_cost' => int]
     */
    public function getItemStock(int $companyId, int $itemId, ?int $warehouseId = null): array
    {
        $query = StockMovement::where('company_id', $companyId)
            ->where('item_id', $itemId);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        $latestMovement = $query
            ->orderBy('movement_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        if (! $latestMovement) {
            return [
                'quantity' => 0,
                'total_value' => 0,
                'weighted_average_cost' => 0,
            ];
        }

        // If querying all warehouses, we need to sum across warehouse latest movements
        if (! $warehouseId) {
            $stockByWarehouse = $this->getItemStockByWarehouse($companyId, $itemId);
            $totalQuantity = 0;
            $totalValue = 0;

            foreach ($stockByWarehouse as $stock) {
                $totalQuantity += $stock['quantity'];
                $totalValue += $stock['total_value'];
            }

            return [
                'quantity' => $totalQuantity,
                'total_value' => $totalValue,
                'weighted_average_cost' => $totalQuantity > 0 ? (int) round($totalValue / $totalQuantity) : 0,
            ];
        }

        return [
            'quantity' => (float) $latestMovement->balance_quantity,
            'total_value' => (int) $latestMovement->balance_value,
            'weighted_average_cost' => $latestMovement->balance_quantity > 0
                ? (int) round($latestMovement->balance_value / $latestMovement->balance_quantity)
                : 0,
        ];
    }

    /**
     * Get stock breakdown by warehouse for an item.
     *
     * @return array<int, array> Keyed by warehouse_id
     */
    public function getItemStockByWarehouse(int $companyId, int $itemId): array
    {
        $warehouses = Warehouse::where('company_id', $companyId)->get();
        $result = [];

        foreach ($warehouses as $warehouse) {
            $stock = $this->getItemStock($companyId, $itemId, $warehouse->id);
            if ($stock['quantity'] != 0) {
                $result[$warehouse->id] = array_merge($stock, [
                    'warehouse_id' => $warehouse->id,
                    'warehouse_name' => $warehouse->name,
                ]);
            }
        }

        return $result;
    }

    /**
     * Get stock movement history for an item.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getMovementHistory(
        int $companyId,
        int $itemId,
        ?int $warehouseId = null,
        ?string $fromDate = null,
        ?string $toDate = null,
        int $limit = 100
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
            ->orderBy('movement_date', 'desc')
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Update the item's quantity field based on total stock.
     */
    protected function updateItemQuantity(int $itemId, ?int $warehouseId = null): void
    {
        $item = Item::find($itemId);
        if (! $item || ! $item->track_quantity) {
            return;
        }

        // Calculate total stock across all warehouses
        $totalStock = $this->getItemStock($item->company_id, $itemId);
        $item->update(['quantity' => (int) $totalStock['quantity']]);
    }

    /**
     * Process stock movement from a bill item (purchase).
     *
     * @param  int|null  $warehouseId  Override warehouse from bill item
     */
    public function processStockFromBillItem(BillItem $billItem, ?int $warehouseId = null): ?StockMovement
    {
        // Check if stock tracking is enabled
        if (! self::isEnabled()) {
            return null;
        }

        // Check if item exists and is trackable
        if (! $billItem->item_id) {
            return null;
        }

        $item = Item::find($billItem->item_id);
        if (! $item || ! $item->track_quantity) {
            return null;
        }

        // Get warehouse (from bill item, parameter, or default)
        $whId = $warehouseId ?? $billItem->warehouse_id;
        if (! $whId) {
            $warehouse = Warehouse::getOrCreateDefault($billItem->company_id);
            $whId = $warehouse->id;
        }

        // Calculate unit cost from bill item (use base_price for consistency)
        $unitCost = (int) ($billItem->base_price ?? $billItem->price);

        return $this->recordStockIn(
            $billItem->company_id,
            $whId,
            $billItem->item_id,
            (float) $billItem->quantity,
            $unitCost,
            StockMovement::SOURCE_BILL_ITEM,
            $billItem->id,
            $billItem->bill?->bill_date,
            "Stock IN from Bill #{$billItem->bill?->bill_number}",
            [
                'bill_id' => $billItem->bill_id,
                'bill_number' => $billItem->bill?->bill_number,
            ]
        );
    }

    /**
     * Process stock movement from an invoice item (sale).
     *
     * @param  int|null  $warehouseId  Override warehouse from invoice item
     */
    public function processStockFromInvoiceItem(InvoiceItem $invoiceItem, ?int $warehouseId = null): ?StockMovement
    {
        // Check if stock tracking is enabled
        if (! self::isEnabled()) {
            return null;
        }

        // Check if item exists and is trackable
        if (! $invoiceItem->item_id) {
            return null;
        }

        $item = Item::find($invoiceItem->item_id);
        if (! $item || ! $item->track_quantity) {
            return null;
        }

        // Get warehouse (from invoice item, parameter, or default)
        $whId = $warehouseId ?? $invoiceItem->warehouse_id;
        if (! $whId) {
            $warehouse = Warehouse::getOrCreateDefault($invoiceItem->company_id);
            $whId = $warehouse->id;
        }

        return $this->recordStockOut(
            $invoiceItem->company_id,
            $whId,
            $invoiceItem->item_id,
            (float) $invoiceItem->quantity,
            StockMovement::SOURCE_INVOICE_ITEM,
            $invoiceItem->id,
            $invoiceItem->invoice?->invoice_date,
            "Stock OUT from Invoice #{$invoiceItem->invoice?->invoice_number}",
            [
                'invoice_id' => $invoiceItem->invoice_id,
                'invoice_number' => $invoiceItem->invoice?->invoice_number,
            ]
        );
    }

    /**
     * Reverse a stock movement (e.g., when deleting an invoice/bill).
     */
    public function reverseMovement(StockMovement $movement, ?string $reason = null): StockMovement
    {
        // Create opposite movement
        $reverseQuantity = -$movement->quantity;

        // For reversing IN movements (now becoming OUT)
        $unitCost = null;
        if ($reverseQuantity < 0) {
            // Original was IN, reverse is OUT - no unit cost needed
            $unitCost = null;
        } else {
            // Original was OUT, reverse is IN - use original WAC
            $wac = $movement->weighted_average_cost ?? 0;
            $unitCost = $wac;
        }

        return $this->recordMovement(
            $movement->company_id,
            $movement->warehouse_id,
            $movement->item_id,
            $reverseQuantity,
            $unitCost,
            StockMovement::SOURCE_ADJUSTMENT,
            null,
            null,
            $reason ?? "Reversal of movement #{$movement->id}: {$movement->source_type}",
            [
                'reversed_movement_id' => $movement->id,
                'original_source_type' => $movement->source_type,
                'original_source_id' => $movement->source_id,
            ],
            auth()->id()
        );
    }

    /**
     * Get low stock items for a company.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getLowStockItems(int $companyId)
    {
        return Item::where('company_id', $companyId)
            ->where('track_quantity', true)
            ->whereNotNull('minimum_quantity')
            ->whereRaw('quantity <= minimum_quantity')
            ->get();
    }

    /**
     * Get stock valuation report for a company.
     */
    public function getStockValuationReport(int $companyId, ?int $warehouseId = null): array
    {
        $items = Item::where('company_id', $companyId)
            ->where('track_quantity', true)
            ->get();

        $report = [
            'items' => [],
            'total_quantity' => 0,
            'total_value' => 0,
        ];

        foreach ($items as $item) {
            $stock = $this->getItemStock($companyId, $item->id, $warehouseId);

            if ($stock['quantity'] != 0) {
                $report['items'][] = [
                    'item_id' => $item->id,
                    'item_name' => $item->name,
                    'sku' => $item->sku,
                    'barcode' => $item->barcode,
                    'quantity' => $stock['quantity'],
                    'weighted_average_cost' => $stock['weighted_average_cost'],
                    'total_value' => $stock['total_value'],
                ];

                $report['total_quantity'] += $stock['quantity'];
                $report['total_value'] += $stock['total_value'];
            }
        }

        return $report;
    }

    /**
     * Get service status.
     */
    public function getServiceStatus(): array
    {
        return [
            'service_name' => 'Stock Service',
            'version' => '1.0.0',
            'enabled' => self::isEnabled(),
            'features' => [
                'weighted_average_cost' => true,
                'multi_warehouse' => true,
                'stock_transfers' => true,
                'movement_history' => true,
                'low_stock_alerts' => true,
                'valuation_reports' => true,
            ],
            'created_date' => '2025-11-30',
        ];
    }
}
// CLAUDE-CHECKPOINT
