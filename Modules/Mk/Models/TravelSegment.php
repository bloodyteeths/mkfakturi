<?php

namespace Modules\Mk\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TravelSegment extends Model
{
    protected $table = 'travel_segments';

    protected $fillable = [
        'travel_order_id',
        'from_city',
        'to_city',
        'country_code',
        'departure_at',
        'arrival_at',
        'transport_type',
        'distance_km',
        'accommodation_provided',
        'meals_provided',
        'per_diem_rate',
        'per_diem_days',
        'per_diem_amount',
    ];

    protected function casts(): array
    {
        return [
            'departure_at' => 'datetime',
            'arrival_at' => 'datetime',
            'distance_km' => 'decimal:2',
            'accommodation_provided' => 'boolean',
            'meals_provided' => 'boolean',
            'per_diem_rate' => 'decimal:2',
            'per_diem_days' => 'decimal:2',
            'per_diem_amount' => 'integer',
        ];
    }

    // ---- Relationships ----

    public function travelOrder(): BelongsTo
    {
        return $this->belongsTo(TravelOrder::class, 'travel_order_id');
    }
}

// CLAUDE-CHECKPOINT
