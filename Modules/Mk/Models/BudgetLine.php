<?php

namespace Modules\Mk\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetLine extends Model
{
    protected $table = 'budget_lines';

    protected $fillable = [
        'budget_id',
        'account_type',
        'ifrs_account_id',
        'cost_center_id',
        'period_start',
        'period_end',
        'amount',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'amount' => 'decimal:2',
            'ifrs_account_id' => 'integer',
            'cost_center_id' => 'integer',
        ];
    }

    // ---- Relationships ----

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    // ---- Accessors ----

    /**
     * Get formatted amount with thousands separator.
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format((float) $this->amount, 2, '.', ',');
    }
}

// CLAUDE-CHECKPOINT
