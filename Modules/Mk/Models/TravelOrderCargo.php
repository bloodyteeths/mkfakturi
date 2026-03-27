<?php

namespace Modules\Mk\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TravelOrderCargo extends Model
{
    protected $table = 'travel_order_cargo';

    protected $fillable = [
        'travel_order_id',
        'travel_segment_id',
        'cmr_number',
        'sender_name',
        'sender_address',
        'receiver_name',
        'receiver_address',
        'goods_description',
        'packages_count',
        'gross_weight_kg',
        'loading_place',
        'unloading_place',
    ];

    protected $casts = [
        'packages_count' => 'integer',
        'gross_weight_kg' => 'decimal:2',
    ];

    public function travelOrder(): BelongsTo
    {
        return $this->belongsTo(TravelOrder::class);
    }

    public function segment(): BelongsTo
    {
        return $this->belongsTo(TravelSegment::class, 'travel_segment_id');
    }
}

// CLAUDE-CHECKPOINT
