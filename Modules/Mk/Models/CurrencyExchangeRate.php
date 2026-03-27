<?php

namespace Modules\Mk\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class CurrencyExchangeRate extends Model
{
    protected $fillable = [
        'currency_code',
        'rate_to_mkd',
        'source',
        'effective_date',
    ];

    protected $casts = [
        'rate_to_mkd' => 'decimal:6',
        'effective_date' => 'date',
    ];

    /**
     * Get the latest exchange rate for a currency.
     */
    public static function latestRate(string $currencyCode, ?string $date = null): ?self
    {
        $date = $date ?? now()->toDateString();

        return static::where('currency_code', strtoupper($currencyCode))
            ->where('effective_date', '<=', $date)
            ->orderBy('effective_date', 'desc')
            ->first();
    }

    /**
     * Convert an amount from a foreign currency to MKD.
     */
    public static function convertToMkd(float $amount, string $currencyCode, ?string $date = null): ?float
    {
        if (strtoupper($currencyCode) === 'MKD') {
            return $amount;
        }

        $rate = static::latestRate($currencyCode, $date);
        if (!$rate) {
            return null;
        }

        return round($amount * (float) $rate->rate_to_mkd, 2);
    }
}

// CLAUDE-CHECKPOINT
