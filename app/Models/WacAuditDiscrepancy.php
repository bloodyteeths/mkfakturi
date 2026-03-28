<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WacAuditDiscrepancy extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'stored_balance_quantity' => 'decimal:4',
            'expected_balance_quantity' => 'decimal:4',
            'stored_balance_value' => 'integer',
            'expected_balance_value' => 'integer',
            'quantity_drift' => 'decimal:4',
            'value_drift' => 'integer',
            'is_root_cause' => 'boolean',
        ];
    }

    public function auditRun(): BelongsTo
    {
        return $this->belongsTo(WacAuditRun::class, 'audit_run_id');
    }

    public function movement(): BelongsTo
    {
        return $this->belongsTo(StockMovement::class, 'movement_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function scopeRootCauses($query)
    {
        return $query->where('is_root_cause', true);
    }
}
