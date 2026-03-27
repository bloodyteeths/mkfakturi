<?php

namespace Modules\Mk\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TravelOrderCrew extends Model
{
    protected $table = 'travel_order_crew';

    protected $fillable = [
        'travel_order_id',
        'name',
        'role',
        'license_number',
        'license_category',
        'cpc_number',
    ];

    public function travelOrder(): BelongsTo
    {
        return $this->belongsTo(TravelOrder::class);
    }
}

// CLAUDE-CHECKPOINT
