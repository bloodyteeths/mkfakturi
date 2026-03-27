<?php

namespace Modules\Mk\Services;

use App\Models\CompanySetting;
use Modules\Mk\Models\CurrencyExchangeRate;
use Modules\Mk\Models\PerDiemRate;

class PerDiemService
{
    /**
     * Get the domestic per-diem rate (MKD) for a company.
     * Configurable via company setting, falls back to config default.
     */
    public function getDomesticRate(?int $companyId = null): int
    {
        if ($companyId) {
            $custom = CompanySetting::getSetting('travel_per_diem_domestic', $companyId);
            if ($custom && is_numeric($custom)) {
                return (int) $custom;
            }
        }

        return (int) config('travel-expenses.domestic.full_day_amount', 3430);
    }

    /**
     * Get domestic half-day rate.
     */
    public function getDomesticHalfRate(?int $companyId = null): int
    {
        return (int) round($this->getDomesticRate($companyId) / 2);
    }

    /**
     * Get the per-diem rate for a country.
     *
     * @return array{rate: float, currency: string, country_name_mk: string}|null
     */
    public function getRateForCountry(string $countryCode, ?string $date = null): ?array
    {
        $record = PerDiemRate::getRateForCountry($countryCode, $date);
        if (!$record) {
            return null;
        }

        return [
            'rate' => (float) $record->rate,
            'currency' => $record->currency_code,
            'country_name_mk' => $record->country_name_mk,
            'country_name_en' => $record->country_name_en,
        ];
    }

    /**
     * Get exchange rate for a currency to MKD.
     */
    public function getExchangeRate(string $currencyCode, ?string $date = null): ?float
    {
        if (strtoupper($currencyCode) === 'MKD') {
            return 1.0;
        }

        $rate = CurrencyExchangeRate::latestRate($currencyCode, $date);
        return $rate ? (float) $rate->rate_to_mkd : null;
    }

    /**
     * Calculate per-diem for a segment.
     *
     * @param float $hours Total hours of the segment
     * @param string $countryCode Country code (MK for domestic)
     * @param array $meals ['breakfast' => bool, 'lunch' => bool, 'dinner' => bool]
     * @param int|null $companyId For domestic rate override
     * @param string|null $date For rate lookup
     * @return array{amount: float, amount_mkd: float, currency: string, days: float, rate: float, reductions: float}
     */
    public function calculatePerDiem(
        float $hours,
        string $countryCode,
        array $meals = [],
        ?int $companyId = null,
        ?string $date = null
    ): array {
        $countryCode = strtoupper($countryCode);
        $isDomestic = $countryCode === 'MK';

        // Determine days
        if ($hours < 8) {
            return ['amount' => 0, 'amount_mkd' => 0, 'currency' => 'MKD', 'days' => 0, 'rate' => 0, 'reductions' => 0];
        }

        if ($hours <= 12) {
            $days = 0.5;
        } else {
            $days = max(1, ceil($hours / 24));
        }

        // Determine rate
        if ($isDomestic) {
            $fullRate = $this->getDomesticRate($companyId);
            $halfRate = $this->getDomesticHalfRate($companyId);
            $currency = 'MKD';
            $rate = $days === 0.5 ? $halfRate : $fullRate;
            $exchangeRate = 1.0;
        } else {
            $countryRate = $this->getRateForCountry($countryCode, $date);
            if (!$countryRate) {
                // Default: 90 USD for unlisted countries
                $rate = 90.00;
                $currency = 'USD';
            } else {
                $rate = $countryRate['rate'];
                $currency = $countryRate['currency'];
            }
            $exchangeRate = $this->getExchangeRate($currency, $date) ?? 61.5395; // fallback EUR
        }

        // Calculate base amount
        // For domestic: half rate is a flat amount, not rate * 0.5
        // For foreign: rate * days (per 24h)
        $baseAmount = ($isDomestic && $days === 0.5) ? $rate : $rate * $days;

        // Apply meal reductions
        $reductions = config('travel-expenses.meal_reductions', []);
        $reductionPct = 0;
        if (!empty($meals['breakfast'])) {
            $reductionPct += $reductions['breakfast'] ?? 0.10;
        }
        if (!empty($meals['lunch'])) {
            $reductionPct += $reductions['lunch'] ?? 0.30;
        }
        if (!empty($meals['dinner'])) {
            $reductionPct += $reductions['dinner'] ?? 0.30;
        }

        $reductionAmount = $baseAmount * $reductionPct;
        $finalAmount = max(0, $baseAmount - $reductionAmount);

        // Convert to MKD
        $amountMkd = $isDomestic ? $finalAmount : round($finalAmount * $exchangeRate, 2);

        return [
            'amount' => round($finalAmount, 2),
            'amount_mkd' => round($amountMkd, 2),
            'currency' => $currency,
            'days' => $days,
            'rate' => $rate,
            'reductions' => round($reductionPct, 2),
        ];
    }

    /**
     * Get all current per-diem rates (for API endpoint).
     */
    public function getAllRates(?string $date = null): array
    {
        $rates = PerDiemRate::currentRates($date)
            ->whereNull('city')
            ->orderBy('country_name_en')
            ->get();

        return $rates->map(function ($r) {
            return [
                'country_code' => $r->country_code,
                'country_name_mk' => $r->country_name_mk,
                'country_name_en' => $r->country_name_en,
                'rate' => (float) $r->rate,
                'currency' => $r->currency_code,
            ];
        })->toArray();
    }

    /**
     * Get all exchange rates (for API endpoint).
     */
    public function getAllExchangeRates(?string $date = null): array
    {
        $date = $date ?? now()->toDateString();

        $currencies = CurrencyExchangeRate::select('currency_code')
            ->distinct()
            ->pluck('currency_code');

        $rates = [];
        foreach ($currencies as $code) {
            $rate = CurrencyExchangeRate::latestRate($code, $date);
            if ($rate) {
                $rates[] = [
                    'currency_code' => $rate->currency_code,
                    'rate_to_mkd' => (float) $rate->rate_to_mkd,
                    'effective_date' => $rate->effective_date->toDateString(),
                ];
            }
        }

        return $rates;
    }
}

// CLAUDE-CHECKPOINT
