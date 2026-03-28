<?php

namespace Modules\Mk\Models\Manufacturing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionOrderLabor extends Model
{
    protected $table = 'production_order_labor';

    protected $fillable = [
        'production_order_id',
        'description',
        'hours',
        'rate_per_hour',
        'total_cost',
        'work_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'hours' => 'decimal:2',
            'rate_per_hour' => 'integer',
            'total_cost' => 'integer',
            'work_date' => 'date',
        ];
    }

    // ---- Relationships ----

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    // ---- Business Methods ----

    /**
     * Calculate total cost from hours × rate.
     */
    public function calculateTotalCost(): void
    {
        $this->total_cost = (int) round((float) $this->hours * (float) $this->rate_per_hour);
    }
}

// CLAUDE-CHECKPOINT
