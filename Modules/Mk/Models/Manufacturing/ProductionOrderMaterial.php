<?php

namespace Modules\Mk\Models\Manufacturing;

use App\Models\Item;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionOrderMaterial extends Model
{
    protected $table = 'production_order_materials';

    protected $fillable = [
        'production_order_id',
        'item_id',
        'warehouse_id',
        'planned_quantity',
        'planned_unit_cost',
        'actual_quantity',
        'actual_unit_cost',
        'actual_total_cost',
        'wastage_quantity',
        'wastage_cost',
        'quantity_variance',
        'cost_variance',
        'notes',
        'stock_movement_id',
    ];

    protected function casts(): array
    {
        return [
            'planned_quantity' => 'decimal:4',
            'planned_unit_cost' => 'integer',
            'actual_quantity' => 'decimal:4',
            'actual_unit_cost' => 'integer',
            'actual_total_cost' => 'integer',
            'wastage_quantity' => 'decimal:4',
            'wastage_cost' => 'integer',
            'quantity_variance' => 'decimal:4',
            'cost_variance' => 'integer',
        ];
    }

    // ---- Relationships ----

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function stockMovement(): BelongsTo
    {
        return $this->belongsTo(StockMovement::class);
    }

    // ---- Business Methods ----

    /**
     * Calculate variance between planned and actual.
     */
    public function calculateVariance(): void
    {
        $this->quantity_variance = (float) $this->actual_quantity - (float) $this->planned_quantity;
        $plannedTotal = (int) round((float) $this->planned_quantity * (float) $this->planned_unit_cost);
        $this->cost_variance = (int) $this->actual_total_cost - $plannedTotal;
    }

    /**
     * Calculate actual total cost from quantity × unit cost.
     */
    public function calculateActualCost(): void
    {
        $this->actual_total_cost = (int) round((float) $this->actual_quantity * (float) $this->actual_unit_cost);
        $this->wastage_cost = (int) round((float) $this->wastage_quantity * (float) $this->actual_unit_cost);
    }
}

// CLAUDE-CHECKPOINT
