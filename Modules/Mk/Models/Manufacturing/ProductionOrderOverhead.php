<?php

namespace Modules\Mk\Models\Manufacturing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionOrderOverhead extends Model
{
    protected $table = 'production_order_overhead';

    protected $fillable = [
        'production_order_id',
        'description',
        'amount',
        'allocation_method',
        'allocation_base',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'allocation_base' => 'decimal:4',
        ];
    }

    // ---- Relationships ----

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }
}

// CLAUDE-CHECKPOINT
