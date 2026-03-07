<?php

namespace Modules\Mk\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TravelExpense extends Model
{
    protected $table = 'travel_expenses';

    protected $fillable = [
        'travel_order_id',
        'category',
        'description',
        'amount',
        'currency_code',
        'receipt_path',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
        ];
    }

    protected $appends = [
        'formattedAmount',
    ];

    // ---- Relationships ----

    public function travelOrder(): BelongsTo
    {
        return $this->belongsTo(TravelOrder::class, 'travel_order_id');
    }

    // ---- Accessors ----

    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount / 100, 2, '.', ',');
    }
}

// CLAUDE-CHECKPOINT
