<?php

namespace Modules\Mk\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class PerDiemRate extends Model
{
    protected $fillable = [
        'country_code',
        'country_name_mk',
        'country_name_en',
        'rate',
        'currency_code',
        'city',
        'effective_from',
        'effective_to',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    public function scopeForCountry(Builder $query, string $countryCode): Builder
    {
        return $query->where('country_code', strtoupper($countryCode));
    }

    public function scopeCurrentRates(Builder $query, ?string $date = null): Builder
    {
        $date = $date ?? now()->toDateString();

        return $query->where('effective_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $date);
            });
    }

    /**
     * Get the current rate for a country (general, not city-specific).
     */
    public static function getRateForCountry(string $countryCode, ?string $date = null): ?self
    {
        return static::forCountry($countryCode)
            ->currentRates($date)
            ->whereNull('city')
            ->first();
    }
}

// CLAUDE-CHECKPOINT
