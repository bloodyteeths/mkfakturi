<?php

namespace Modules\Mk\Models;

use App\Models\Item;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NivelacijaItem extends Model
{
    protected $table = 'nivelacija_items';

    protected $fillable = [
        'nivelacija_id',
        'item_id',
        'warehouse_id',
        'quantity_on_hand',
        'old_retail_price',
        'new_retail_price',
        'old_wholesale_price',
        'new_wholesale_price',
        'old_markup_percent',
        'new_markup_percent',
        'price_difference',
        'total_difference',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity_on_hand' => 'decimal:4',
            'old_retail_price' => 'integer',
            'new_retail_price' => 'integer',
            'old_wholesale_price' => 'integer',
            'new_wholesale_price' => 'integer',
            'old_markup_percent' => 'decimal:2',
            'new_markup_percent' => 'decimal:2',
            'price_difference' => 'integer',
            'total_difference' => 'integer',
        ];
    }

    public function nivelacija(): BelongsTo
    {
        return $this->belongsTo(Nivelacija::class, 'nivelacija_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}

// CLAUDE-CHECKPOINT
