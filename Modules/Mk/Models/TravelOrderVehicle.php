<?php

namespace Modules\Mk\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TravelOrderVehicle extends Model
{
    protected $table = 'travel_order_vehicles';

    protected $fillable = [
        'travel_order_id',
        'vehicle_type',
        'make',
        'model',
        'registration_plate',
        'capacity_tonnes',
        'fuel_type',
        'odometer_start',
        'odometer_end',
        'fuel_start_liters',
        'fuel_end_liters',
        'fuel_added_liters',
        'fuel_norm_per_100km',
    ];

    protected $casts = [
        'capacity_tonnes' => 'decimal:2',
        'odometer_start' => 'integer',
        'odometer_end' => 'integer',
        'fuel_start_liters' => 'decimal:2',
        'fuel_end_liters' => 'decimal:2',
        'fuel_added_liters' => 'decimal:2',
        'fuel_norm_per_100km' => 'decimal:2',
    ];

    public function travelOrder(): BelongsTo
    {
        return $this->belongsTo(TravelOrder::class);
    }

    /**
     * Total km driven (from odometer readings).
     */
    public function getTotalKmAttribute(): ?int
    {
        if ($this->odometer_start !== null && $this->odometer_end !== null) {
            return max(0, $this->odometer_end - $this->odometer_start);
        }
        return null;
    }

    /**
     * Total fuel consumed = start + added - end.
     */
    public function getFuelConsumedAttribute(): ?float
    {
        if ($this->fuel_start_liters !== null && $this->fuel_end_liters !== null) {
            $added = (float) ($this->fuel_added_liters ?? 0);
            return max(0, (float) $this->fuel_start_liters + $added - (float) $this->fuel_end_liters);
        }
        return null;
    }

    /**
     * Normative fuel consumption based on km and norm per 100km.
     */
    public function getNormConsumptionAttribute(): ?float
    {
        $km = $this->total_km;
        if ($km !== null && $this->fuel_norm_per_100km > 0) {
            return round($km * (float) $this->fuel_norm_per_100km / 100, 2);
        }
        return null;
    }

    /**
     * Fuel variance: actual - norm (positive = over-consumption).
     */
    public function getFuelVarianceAttribute(): ?float
    {
        $consumed = $this->fuel_consumed;
        $norm = $this->norm_consumption;
        if ($consumed !== null && $norm !== null) {
            return round($consumed - $norm, 2);
        }
        return null;
    }
}

// CLAUDE-CHECKPOINT
