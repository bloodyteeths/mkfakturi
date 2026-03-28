<?php

namespace Modules\Mk\Models\Manufacturing;

use App\Models\Item;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CoProductionOutput extends Model
{
    use SoftDeletes;

    protected $table = 'co_production_outputs';

    protected $fillable = [
        'production_order_id',
        'item_id',
        'is_primary',
        'quantity',
        'warehouse_id',
        'allocation_method',
        'allocation_percent',
        'allocated_cost',
        'cost_per_unit',
        'stock_movement_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'quantity' => 'decimal:4',
            'allocation_percent' => 'decimal:4',
            'allocated_cost' => 'integer',
            'cost_per_unit' => 'integer',
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
     * Calculate cost per unit from allocated cost and quantity.
     */
    public function calculateCostPerUnit(): void
    {
        $qty = (float) $this->quantity;
        $this->cost_per_unit = $qty > 0
            ? (int) round((int) $this->allocated_cost / $qty)
            : 0;
    }
}

// CLAUDE-CHECKPOINT
