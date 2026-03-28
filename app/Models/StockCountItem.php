<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockCountItem extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'system_quantity' => 'decimal:4',
            'counted_quantity' => 'decimal:4',
            'variance_quantity' => 'decimal:4',
            'variance_value' => 'integer',
            'system_unit_cost' => 'integer',
        ];
    }

    public function stockCount(): BelongsTo
    {
        return $this->belongsTo(StockCount::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Get variance as percentage of system quantity.
     */
    public function getVariancePercentageAttribute(): ?float
    {
        if ($this->system_quantity == 0) {
            return $this->variance_quantity != 0 ? 100.0 : 0.0;
        }

        return round(($this->variance_quantity / $this->system_quantity) * 100, 2);
    }
}
// CLAUDE-CHECKPOINT
