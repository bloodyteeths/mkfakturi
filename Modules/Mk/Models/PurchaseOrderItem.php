<?php

namespace Modules\Mk\Models;

use App\Models\Item;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    protected $table = 'purchase_order_items';

    protected $fillable = [
        'purchase_order_id',
        'item_id',
        'name',
        'quantity',
        'received_quantity',
        'price',
        'tax',
        'total',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'float',
            'received_quantity' => 'float',
            'price' => 'integer',
            'tax' => 'integer',
            'total' => 'integer',
        ];
    }

    protected $appends = [
        'remaining_quantity',
        'formattedPrice',
        'formattedTotal',
    ];

    // ---- Relationships ----

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    // ---- Accessors ----

    /**
     * Get remaining quantity (ordered - received).
     */
    public function getRemainingQuantityAttribute(): float
    {
        return max(0, $this->quantity - $this->received_quantity);
    }

    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price / 100, 2, '.', ',');
    }

    public function getFormattedTotalAttribute(): string
    {
        return number_format($this->total / 100, 2, '.', ',');
    }
}

// CLAUDE-CHECKPOINT
