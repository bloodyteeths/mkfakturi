<?php

namespace App\Services;

use App\Models\Currency;
use App\Models\ExchangeRateLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Frankfurter Exchange Rate Service
 *
 * Free exchange rate API using European Central Bank data.
 * No API key required. Includes MKD rates.
 *
 * @see https://frankfurter.dev/
 */
class FrankfurterExchangeRateService
{
    protected const API_BASE = 'https://api.frankfurter.dev/v1';

    protected const CACHE_TTL_HOURS = 4; // Cache rates for 4 hours

    /**
     * Get exchange rate between two currencies
     *
     * @param  string  $fromCurrency  Source currency code (e.g., 'EUR')
     * @param  string  $toCurrency  Target currency code (e.g., 'MKD')
     * @return float|null Exchange rate or null on failure
     */
    public function getRate(string $fromCurrency, string $toCurrency): ?float
    {
        // Same currency = 1
        if ($fromCurrency === $toCurrency) {
            return 1.0;
        }

        $cacheKey = "exchange_rate_{$fromCurrency}_{$toCurrency}";

        return Cache::remember($cacheKey, now()->addHours(self::CACHE_TTL_HOURS), function () use ($fromCurrency, $toCurrency) {
            return $this->fetchRate($fromCurrency, $toCurrency);
        });
    }

    /**
     * Fetch rate from Frankfurter API
     */
    protected function fetchRate(string $fromCurrency, string $toCurrency): ?float
    {
        try {
            $url = self::API_BASE."/latest?base={$fromCurrency}&symbols={$toCurrency}";

            $response = Http::timeout(10)->get($url);

            if (! $response->successful()) {
                Log::warning('Frankfurter API request failed', [
                    'status' => $response->status(),
                    'from' => $fromCurrency,
                    'to' => $toCurrency,
                ]);

                // Try reverse rate if base currency not supported (EUR is always supported)
                return $this->fetchReverseRate($fromCurrency, $toCurrency);
            }

            $data = $response->json();

            if (isset($data['rates'][$toCurrency])) {
                $rate = (float) $data['rates'][$toCurrency];

                Log::debug('Frankfurter rate fetched', [
                    'from' => $fromCurrency,
                    'to' => $toCurrency,
                    'rate' => $rate,
                ]);

                return $rate;
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Frankfurter API error', [
                'error' => $e->getMessage(),
                'from' => $fromCurrency,
                'to' => $toCurrency,
            ]);

            return null;
        }
    }

    /**
     * Fetch reverse rate when direct rate not available
     * ECB only publishes rates with EUR as base, so we calculate cross-rates
     */
    protected function fetchReverseRate(string $fromCurrency, string $toCurrency): ?float
    {
        try {
            // Get both currencies relative to EUR
            $url = self::API_BASE."/latest?base=EUR&symbols={$fromCurrency},{$toCurrency}";

            $response = Http::timeout(10)->get($url);

            if (! $response->successful()) {
                return null;
            }

            $data = $response->json();

            if (isset($data['rates'][$fromCurrency]) && isset($data['rates'][$toCurrency])) {
                $fromRate = (float) $data['rates'][$fromCurrency];
                $toRate = (float) $data['rates'][$toCurrency];

                // Cross rate: toCurrency/fromCurrency
                $rate = $toRate / $fromRate;

                Log::debug('Frankfurter cross-rate calculated', [
                    'from' => $fromCurrency,
                    'to' => $toCurrency,
                    'rate' => $rate,
                ]);

                return $rate;
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Frankfurter cross-rate error', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get exchange rate and save to log
     *
     * @param  int  $companyId  Company ID
     * @param  Currency  $fromCurrency  Source currency
     * @param  Currency  $toCurrency  Target currency
     * @return float|null Exchange rate
     */
    public function getAndLogRate(int $companyId, Currency $fromCurrency, Currency $toCurrency): ?float
    {
        $rate = $this->getRate($fromCurrency->code, $toCurrency->code);

        if ($rate !== null) {
            // Save to exchange rate log for historical tracking
            ExchangeRateLog::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'base_currency_id' => $fromCurrency->id,
                    'currency_id' => $toCurrency->id,
                ],
                [
                    'exchange_rate' => $rate,
                ]
            );
        }

        return $rate;
    }

    /**
     * Get all available currencies from Frankfurter
     *
     * @return array Currency codes
     */
    public function getSupportedCurrencies(): array
    {
        $cacheKey = 'frankfurter_currencies';

        return Cache::remember($cacheKey, now()->addDay(), function () {
            try {
                $response = Http::timeout(10)->get(self::API_BASE.'/currencies');

                if ($response->successful()) {
                    return array_keys($response->json());
                }

                return [];
            } catch (\Exception $e) {
                Log::error('Frankfurter currencies fetch error', [
                    'error' => $e->getMessage(),
                ]);

                return [];
            }
        });
    }

    /**
     * Get latest rates for multiple currencies
     *
     * @param  string  $baseCurrency  Base currency code
     * @param  array  $targetCurrencies  Array of target currency codes
     * @return array Rates keyed by currency code
     */
    public function getMultipleRates(string $baseCurrency, array $targetCurrencies): array
    {
        if (empty($targetCurrencies)) {
            return [];
        }

        $symbols = implode(',', $targetCurrencies);
        $cacheKey = "exchange_rates_{$baseCurrency}_{$symbols}";

        return Cache::remember($cacheKey, now()->addHours(self::CACHE_TTL_HOURS), function () use ($baseCurrency, $symbols) {
            try {
                $url = self::API_BASE."/latest?base={$baseCurrency}&symbols={$symbols}";

                $response = Http::timeout(10)->get($url);

                if ($response->successful()) {
                    return $response->json()['rates'] ?? [];
                }

                // Fallback: try with EUR base and calculate cross-rates
                return $this->getMultipleRatesViaEur($baseCurrency, explode(',', $symbols));
            } catch (\Exception $e) {
                Log::error('Frankfurter multiple rates error', [
                    'error' => $e->getMessage(),
                ]);

                return [];
            }
        });
    }

    /**
     * Get multiple rates via EUR cross-rate calculation
     */
    protected function getMultipleRatesViaEur(string $baseCurrency, array $targetCurrencies): array
    {
        try {
            $allCurrencies = array_merge([$baseCurrency], $targetCurrencies);
            $symbols = implode(',', array_unique($allCurrencies));

            $url = self::API_BASE."/latest?base=EUR&symbols={$symbols}";
            $response = Http::timeout(10)->get($url);

            if (! $response->successful()) {
                return [];
            }

            $rates = $response->json()['rates'] ?? [];

            if (! isset($rates[$baseCurrency])) {
                return [];
            }

            $baseRate = $rates[$baseCurrency];
            $result = [];

            foreach ($targetCurrencies as $currency) {
                if (isset($rates[$currency])) {
                    $result[$currency] = $rates[$currency] / $baseRate;
                }
            }

            return $result;
        } catch (\Exception $e) {
            return [];
        }
    }
}

// CLAUDE-CHECKPOINT
