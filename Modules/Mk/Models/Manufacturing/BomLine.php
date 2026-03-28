<?php

namespace Modules\Mk\Models\Manufacturing;

use App\Models\Item;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BomLine extends Model
{
    protected $table = 'bom_lines';

    protected $fillable = [
        'bom_id',
        'item_id',
        'quantity',
        'unit_id',
        'wastage_percent',
        'notes',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'wastage_percent' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    // ---- Relationships ----

    public function bom(): BelongsTo
    {
        return $this->belongsTo(Bom::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}

// CLAUDE-CHECKPOINT
