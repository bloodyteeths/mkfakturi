<?php

namespace Modules\Mk\Services;

use App\Domain\Accounting\IfrsAdapter;
use App\Exceptions\PeriodLockedException;
use App\Models\StockMovement;
use App\Services\PeriodLockService;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Mk\Models\Manufacturing\Bom;
use Modules\Mk\Models\Manufacturing\CoProductionOutput;
use Modules\Mk\Models\Manufacturing\ProductionOrder;
use Modules\Mk\Models\Manufacturing\ProductionOrderLabor;
use Modules\Mk\Models\Manufacturing\ProductionOrderMaterial;
use Modules\Mk\Models\Manufacturing\ProductionOrderOverhead;

/**
 * Main orchestrator for the Manufacturing module.
 *
 * Coordinates BOM → Production Order → Stock Movement → GL posting.
 * All monetary values in cents (MKD × 100).
 */
class ManufacturingService
{
    public function __construct(
        protected StockService $stockService,
        protected PeriodLockService $periodLockService,
        protected CostAllocationService $costAllocationService,
    ) {}

    // ====================================================================
    // BOM Operations
    // ====================================================================

    /**
     * Create a new BOM with lines.
     *
     * @param  array  $data  BOM header data
     * @param  array  $lines  Array of line data [{item_id, quantity, unit_id, wastage_percent, sort_order}]
     */
    public function createBom(int $companyId, array $data, array $lines = []): Bom
    {
        return DB::transaction(function () use ($companyId, $data, $lines) {
            $bom = Bom::create(array_merge($data, [
                'company_id' => $companyId,
                'created_by' => auth()->id(),
            ]));

            foreach ($lines as $i => $lineData) {
                $bom->lines()->create(array_merge($lineData, [
                    'sort_order' => $lineData['sort_order'] ?? $i,
                ]));
            }

            return $bom->fresh(['lines', 'outputItem', 'outputUnit']);
        });
    }

    /**
     * Update BOM header and lines.
     */
    public function updateBom(Bom $bom, array $data, ?array $lines = null): Bom
    {
        return DB::transaction(function () use ($bom, $data, $lines) {
            $bom->update($data);

            if ($lines !== null) {
                // Replace all lines
                $bom->lines()->delete();
                foreach ($lines as $i => $lineData) {
                    $bom->lines()->create(array_merge($lineData, [
                        'sort_order' => $lineData['sort_order'] ?? $i,
                    ]));
                }
            }

            return $bom->fresh(['lines', 'outputItem', 'outputUnit']);
        });
    }

    /**
     * Delete BOM (soft) — only if not used by any production orders.
     */
    public function deleteBom(Bom $bom): bool
    {
        if ($bom->isUsedByOrders()) {
            throw new \RuntimeException('Cannot delete BOM that is used by production orders.');
        }

        return $bom->delete();
    }

    // ====================================================================
    // Production Order Lifecycle
    // ====================================================================

    /**
     * Create a production order from a BOM.
     *
     * Pre-fills planned materials from BOM lines with current WAC prices.
     *
     * @param  array  $options  Override fields: order_date, expected_completion_date, output_warehouse_id, notes
     */
    public function createProductionOrder(Bom $bom, float $quantity, array $options = []): ProductionOrder
    {
        $companyId = $bom->company_id;

        return DB::transaction(function () use ($bom, $quantity, $companyId, $options) {
            $order = ProductionOrder::create([
                'company_id' => $companyId,
                'currency_id' => $bom->currency_id,
                'bom_id' => $bom->id,
                'output_item_id' => $bom->output_item_id,
                'planned_quantity' => $quantity,
                'status' => ProductionOrder::STATUS_DRAFT,
                'order_date' => $options['order_date'] ?? now()->format('Y-m-d'),
                'expected_completion_date' => $options['expected_completion_date'] ?? null,
                'output_warehouse_id' => $options['output_warehouse_id'] ?? null,
                'notes' => $options['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            // Pre-fill planned materials from BOM lines
            $outputQty = (float) $bom->output_quantity > 0 ? (float) $bom->output_quantity : 1;

            foreach ($bom->lines as $line) {
                $qtyPerUnit = (float) $line->quantity / $outputQty;
                $wastageMultiplier = 1 + ((float) $line->wastage_percent / 100);
                $plannedQty = round($qtyPerUnit * $quantity * $wastageMultiplier, 4);

                // Get current WAC for planned cost
                $stock = $this->stockService->getItemStock($companyId, $line->item_id);
                $wac = $stock['weighted_average_cost'] ?? 0;

                $order->materials()->create([
                    'item_id' => $line->item_id,
                    'warehouse_id' => $options['output_warehouse_id'] ?? null,
                    'planned_quantity' => $plannedQty,
                    'planned_unit_cost' => $wac,
                ]);
            }

            return $order->fresh(['materials', 'bom', 'outputItem']);
        });
    }

    /**
     * Start production — status → in_progress.
     *
     * No stock movements at this point.
     */
    public function startProduction(ProductionOrder $order): void
    {
        if (! $order->canStart()) {
            throw new \RuntimeException('Order cannot be started from status: '.$order->status);
        }

        $order->update(['status' => ProductionOrder::STATUS_IN_PROGRESS]);
    }

    /**
     * Record material consumption during production.
     *
     * Creates stock OUT movement and updates material line.
     */
    public function recordMaterialConsumption(
        ProductionOrder $order,
        int $materialId,
        float $actualQty,
        float $wastageQty = 0,
        ?int $warehouseId = null,
        ?string $notes = null
    ): ProductionOrderMaterial {
        if (! $order->isInProgress()) {
            throw new \RuntimeException('Can only record consumption for in-progress orders.');
        }

        $material = $order->materials()->findOrFail($materialId);
        $companyId = $order->company_id;
        $warehouse = $warehouseId ?? $material->warehouse_id;

        // Get current WAC at consumption time
        $stock = $this->stockService->getItemStock($companyId, $material->item_id, $warehouse);
        $wac = $stock['weighted_average_cost'] ?? 0;

        return DB::transaction(function () use ($material, $actualQty, $wastageQty, $companyId, $warehouse, $wac, $notes, $order) {
            // Record stock out for consumed material
            $totalConsumed = $actualQty + $wastageQty;

            $stockMovement = null;
            if ($totalConsumed > 0 && $warehouse) {
                $stockMovement = $this->stockService->recordStockOut(
                    companyId: $companyId,
                    warehouseId: $warehouse,
                    itemId: $material->item_id,
                    quantity: $totalConsumed,
                    sourceType: StockMovement::SOURCE_PRODUCTION_CONSUME,
                    sourceId: $material->id,
                    movementDate: now()->format('Y-m-d'),
                    notes: $notes ?? "Production order #{$order->order_number}",
                    createdBy: auth()->id(),
                );
            }

            // Update material line
            $material->update([
                'actual_quantity' => $actualQty,
                'actual_unit_cost' => $wac,
                'wastage_quantity' => $wastageQty,
                'warehouse_id' => $warehouse,
                'stock_movement_id' => $stockMovement?->id,
                'notes' => $notes,
            ]);

            $material->calculateActualCost();
            $material->calculateVariance();
            $material->save();

            // Recalculate order totals
            $order->recalculateCosts();

            return $material->fresh();
        });
    }

    /**
     * Record labor cost entry.
     */
    public function recordLabor(ProductionOrder $order, array $laborData): ProductionOrderLabor
    {
        if (! $order->isInProgress()) {
            throw new \RuntimeException('Can only record labor for in-progress orders.');
        }

        $labor = $order->laborEntries()->create($laborData);
        $labor->calculateTotalCost();
        $labor->save();

        $order->recalculateCosts();

        return $labor->fresh();
    }

    /**
     * Record overhead cost entry.
     */
    public function recordOverhead(ProductionOrder $order, array $overheadData): ProductionOrderOverhead
    {
        if (! $order->isInProgress()) {
            throw new \RuntimeException('Can only record overhead for in-progress orders.');
        }

        $overhead = $order->overheadEntries()->create($overheadData);
        $order->recalculateCosts();

        return $overhead->fresh();
    }

    /**
     * Complete production — finalize costs, create stock IN, post to GL.
     *
     * @param  float  $actualQty  Actual quantity produced
     * @param  array|null  $coOutputs  Co-production outputs [{item_id, quantity, warehouse_id, allocation_method, allocation_percent}]
     */
    public function completeProduction(
        ProductionOrder $order,
        float $actualQty,
        ?array $coOutputs = null
    ): ProductionOrder {
        if (! $order->canComplete()) {
            throw new \RuntimeException('Order cannot be completed from status: '.$order->status);
        }

        // Enforce period lock on order date
        $this->periodLockService->enforceUnlocked($order->company_id, $order->order_date);

        return DB::transaction(function () use ($order, $actualQty, $coOutputs) {
            $companyId = $order->company_id;

            // Update order
            $order->update([
                'actual_quantity' => $actualQty,
                'status' => ProductionOrder::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);

            // Recalculate final costs
            $order->recalculateCosts();

            if ($coOutputs && count($coOutputs) > 1) {
                // Co-production: multiple outputs
                $this->handleCoProduction($order, $coOutputs);
            } else {
                // Single output: stock IN for finished good
                $this->handleSingleOutput($order, $actualQty);
            }

            // Calculate variances against BOM normative
            $order->calculateVariances();

            // Post to GL if IFRS is enabled
            $this->postToGl($order);

            return $order->fresh([
                'materials',
                'laborEntries',
                'overheadEntries',
                'coProductionOutputs',
            ]);
        });
    }

    /**
     * Cancel a production order.
     *
     * Reverses any stock movements already recorded.
     */
    public function cancelProduction(ProductionOrder $order, ?string $reason = null): void
    {
        if (! $order->canCancel()) {
            throw new \RuntimeException('Completed orders cannot be cancelled.');
        }

        DB::transaction(function () use ($order, $reason) {
            // Reverse stock movements for consumed materials
            foreach ($order->materials as $material) {
                if ($material->stock_movement_id) {
                    $movement = StockMovement::find($material->stock_movement_id);
                    if ($movement) {
                        $this->stockService->reverseMovement($movement, "Cancelled: {$order->order_number}");
                    }
                }
            }

            // Reverse stock movements for co-production outputs
            foreach ($order->coProductionOutputs as $output) {
                if ($output->stock_movement_id) {
                    $movement = StockMovement::find($output->stock_movement_id);
                    if ($movement) {
                        $this->stockService->reverseMovement($movement, "Cancelled: {$order->order_number}");
                    }
                }
            }

            $order->update([
                'status' => ProductionOrder::STATUS_CANCELLED,
                'notes' => trim($order->notes."\n[Cancelled] ".$reason),
            ]);

            // Reverse GL entries if posted
            $this->reverseGlEntries($order);
        });
    }

    // ====================================================================
    // Private Helpers
    // ====================================================================

    /**
     * Handle single-output production completion.
     */
    protected function handleSingleOutput(ProductionOrder $order, float $actualQty): void
    {
        if ($actualQty <= 0 || ! $order->output_warehouse_id) {
            return;
        }

        $costPerUnit = (int) $order->cost_per_unit;

        // Stock IN for finished goods
        $movement = $this->stockService->recordStockIn(
            companyId: $order->company_id,
            warehouseId: $order->output_warehouse_id,
            itemId: $order->output_item_id,
            quantity: $actualQty,
            unitCost: $costPerUnit,
            sourceType: StockMovement::SOURCE_PRODUCTION_OUTPUT,
            sourceId: $order->id,
            movementDate: now()->format('Y-m-d'),
            notes: "Production output: {$order->order_number}",
            createdBy: auth()->id(),
        );

        // Create primary co-production output record for tracking
        $order->coProductionOutputs()->create([
            'item_id' => $order->output_item_id,
            'is_primary' => true,
            'quantity' => $actualQty,
            'warehouse_id' => $order->output_warehouse_id,
            'allocation_method' => 'weight',
            'allocation_percent' => 100,
            'allocated_cost' => $order->total_production_cost,
            'cost_per_unit' => $costPerUnit,
            'stock_movement_id' => $movement->id,
        ]);
    }

    /**
     * Handle co-production with multiple outputs.
     */
    protected function handleCoProduction(ProductionOrder $order, array $coOutputs): void
    {
        // Create co-production output records
        foreach ($coOutputs as $outputData) {
            $order->coProductionOutputs()->create($outputData);
        }

        // Allocate costs
        $allocations = $this->costAllocationService->allocate($order);

        // Update each output with allocated costs and create stock movements
        foreach ($order->coProductionOutputs as $output) {
            if (! isset($allocations[$output->id])) {
                continue;
            }

            $allocation = $allocations[$output->id];
            $output->update($allocation);

            // Stock IN for each output
            if ((float) $output->quantity > 0 && $output->warehouse_id) {
                $sourceType = $output->is_primary
                    ? StockMovement::SOURCE_PRODUCTION_OUTPUT
                    : StockMovement::SOURCE_PRODUCTION_BYPRODUCT;

                $movement = $this->stockService->recordStockIn(
                    companyId: $order->company_id,
                    warehouseId: $output->warehouse_id,
                    itemId: $output->item_id,
                    quantity: (float) $output->quantity,
                    unitCost: $allocation['cost_per_unit'],
                    sourceType: $sourceType,
                    sourceId: $output->id,
                    movementDate: now()->format('Y-m-d'),
                    notes: "Co-production output: {$order->order_number}",
                    createdBy: auth()->id(),
                );

                $output->update(['stock_movement_id' => $movement->id]);
            }
        }
    }

    /**
     * Post production order to GL via IfrsAdapter.
     */
    protected function postToGl(ProductionOrder $order): void
    {
        try {
            $ifrs = app(IfrsAdapter::class);
            if (! $ifrs->isEnabled($order->company_id)) {
                return;
            }

            $ifrs->postProductionCompletion($order);
        } catch (\Throwable $e) {
            // GL posting errors are logged but never thrown (IFRS auto-posting pattern)
            Log::error('Manufacturing GL posting failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Reverse GL entries for a cancelled order.
     */
    protected function reverseGlEntries(ProductionOrder $order): void
    {
        try {
            $ifrs = app(IfrsAdapter::class);
            if (! $ifrs->isEnabled($order->company_id)) {
                return;
            }

            $ifrs->reverseProductionOrder($order);
        } catch (\Throwable $e) {
            Log::error('Manufacturing GL reversal failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // ====================================================================
    // Reports
    // ====================================================================

    /**
     * Get production cost analysis for a company.
     */
    public function getProductionCostReport(int $companyId, ?string $from = null, ?string $to = null): array
    {
        $query = ProductionOrder::where('company_id', $companyId)
            ->where('status', ProductionOrder::STATUS_COMPLETED);

        if ($from) {
            $query->where('order_date', '>=', $from);
        }
        if ($to) {
            $query->where('order_date', '<=', $to);
        }

        $orders = $query->with(['outputItem', 'bom'])->get();

        return [
            'total_orders' => $orders->count(),
            'total_material_cost' => $orders->sum('total_material_cost'),
            'total_labor_cost' => $orders->sum('total_labor_cost'),
            'total_overhead_cost' => $orders->sum('total_overhead_cost'),
            'total_wastage_cost' => $orders->sum('total_wastage_cost'),
            'total_production_cost' => $orders->sum('total_production_cost'),
            'total_variance' => $orders->sum('total_variance'),
            'orders' => $orders,
        ];
    }
}

// CLAUDE-CHECKPOINT
