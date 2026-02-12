<?php

namespace App\Services;

use App\Contracts\ExchangeRateProvider;
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
class FrankfurterExchangeRateService implements ExchangeRateProvider
{
    /**
     * Get the Frankfurter API base URL from config.
     */
    protected function getBaseUrl(): string
    {
        return config('mk.exchange_rates.frankfurter.base_url', 'https://api.frankfurter.dev/v1');
    }

    /**
     * Get the cache TTL in seconds from config.
     */
    protected function getCacheTtlSeconds(): int
    {
        return (int) config('mk.exchange_rates.frankfurter.cache_ttl', 14400);
    }

    /**
     * {@inheritdoc}
     */
    public function getRate(string $from, string $to, ?Carbon $date = null): float
    {
        // Same currency = 1
        if ($from === $to) {
            return 1.0;
        }

        $dateKey = $date ? $date->format('Y-m-d') : 'latest';
        $cacheKey = "frankfurter_rate:{$from}:{$to}:{$dateKey}";

        return Cache::remember($cacheKey, $this->getCacheTtlSeconds(), function () use ($from, $to, $date) {
            $rate = $this->fetchRate($from, $to, $date);

            if ($rate === null) {
                throw new \RuntimeException("Frankfurter: Could not fetch rate for {$from}/{$to}");
            }

            return $rate;
        });
    }

    /**
     * Fetch rate from Frankfurter API.
     *
     * @param  string  $fromCurrency  Source currency code
     * @param  string  $toCurrency  Target currency code
     * @param  Carbon|null  $date  Date for historical rate
     * @return float|null Exchange rate or null on failure
     */
    protected function fetchRate(string $fromCurrency, string $toCurrency, ?Carbon $date = null): ?float
    {
        try {
            $endpoint = $date ? $date->format('Y-m-d') : 'latest';
            $url = $this->getBaseUrl()."/{$endpoint}?base={$fromCurrency}&symbols={$toCurrency}";

            $response = Http::timeout(10)->get($url);

            if (! $response->successful()) {
                Log::warning('Frankfurter API request failed', [
                    'status' => $response->status(),
                    'from' => $fromCurrency,
                    'to' => $toCurrency,
                ]);

                // Try reverse rate if base currency not supported (EUR is always supported)
                return $this->fetchReverseRate($fromCurrency, $toCurrency, $date);
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
     * Fetch reverse rate when direct rate not available.
     * ECB only publishes rates with EUR as base, so we calculate cross-rates.
     *
     * @param  string  $fromCurrency  Source currency code
     * @param  string  $toCurrency  Target currency code
     * @param  Carbon|null  $date  Date for historical rate
     * @return float|null Exchange rate or null on failure
     */
    protected function fetchReverseRate(string $fromCurrency, string $toCurrency, ?Carbon $date = null): ?float
    {
        try {
            $endpoint = $date ? $date->format('Y-m-d') : 'latest';
            $url = $this->getBaseUrl()."/{$endpoint}?base=EUR&symbols={$fromCurrency},{$toCurrency}";

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
     * Get exchange rate and save to log.
     *
     * This is a convenience method used by the exchange rate controller.
     * Not part of the ExchangeRateProvider interface.
     *
     * @param  int  $companyId  Company ID
     * @param  Currency  $fromCurrency  Source currency
     * @param  Currency  $toCurrency  Target currency
     * @return float|null Exchange rate
     */
    public function getAndLogRate(int $companyId, Currency $fromCurrency, Currency $toCurrency): ?float
    {
        try {
            $rate = $this->getRate($fromCurrency->code, $toCurrency->code);
        } catch (\RuntimeException $e) {
            Log::warning('Frankfurter: getAndLogRate failed', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }

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

        return $rate;
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedCurrencies(): array
    {
        $cacheKey = 'frankfurter_currencies';

        return Cache::remember($cacheKey, now()->addDay(), function () {
            try {
                $response = Http::timeout(10)->get($this->getBaseUrl().'/currencies');

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
     * {@inheritdoc}
     */
    public function getMultipleRates(array $pairs, ?Carbon $date = null): array
    {
        if (empty($pairs)) {
            return [];
        }

        $results = [];

        // Group pairs by base currency for efficient API calls
        $grouped = [];
        foreach ($pairs as $pair) {
            $from = $pair['from'];
            $to = $pair['to'];

            if ($from === $to) {
                $results["{$from}/{$to}"] = 1.0;

                continue;
            }

            $grouped[$from][] = $to;
        }

        foreach ($grouped as $baseCurrency => $targetCurrencies) {
            $rates = $this->fetchMultipleRatesForBase($baseCurrency, $targetCurrencies, $date);

            foreach ($rates as $targetCurrency => $rate) {
                $results["{$baseCurrency}/{$targetCurrency}"] = $rate;
            }
        }

        return $results;
    }

    /**
     * Fetch multiple rates for a single base currency.
     *
     * @param  string  $baseCurrency  Base currency code
     * @param  array  $targetCurrencies  Array of target currency codes
     * @param  Carbon|null  $date  Date for historical rates
     * @return array Rates keyed by target currency code
     */
    protected function fetchMultipleRatesForBase(string $baseCurrency, array $targetCurrencies, ?Carbon $date = null): array
    {
        $symbols = implode(',', $targetCurrencies);
        $dateKey = $date ? $date->format('Y-m-d') : 'latest';
        $cacheKey = "frankfurter_rates:{$baseCurrency}:{$symbols}:{$dateKey}";

        return Cache::remember($cacheKey, $this->getCacheTtlSeconds(), function () use ($baseCurrency, $symbols, $targetCurrencies, $date) {
            try {
                $endpoint = $date ? $date->format('Y-m-d') : 'latest';
                $url = $this->getBaseUrl()."/{$endpoint}?base={$baseCurrency}&symbols={$symbols}";

                $response = Http::timeout(10)->get($url);

                if ($response->successful()) {
                    return $response->json()['rates'] ?? [];
                }

                // Fallback: try with EUR base and calculate cross-rates
                return $this->getMultipleRatesViaEur($baseCurrency, $targetCurrencies, $date);
            } catch (\Exception $e) {
                Log::error('Frankfurter multiple rates error', [
                    'error' => $e->getMessage(),
                ]);

                return [];
            }
        });
    }

    /**
     * Get multiple rates via EUR cross-rate calculation.
     *
     * @param  string  $baseCurrency  Base currency code
     * @param  array  $targetCurrencies  Array of target currency codes
     * @param  Carbon|null  $date  Date for historical rates
     * @return array Rates keyed by target currency code
     */
    protected function getMultipleRatesViaEur(string $baseCurrency, array $targetCurrencies, ?Carbon $date = null): array
    {
        try {
            $allCurrencies = array_merge([$baseCurrency], $targetCurrencies);
            $symbols = implode(',', array_unique($allCurrencies));

            $endpoint = $date ? $date->format('Y-m-d') : 'latest';
            $url = $this->getBaseUrl()."/{$endpoint}?base=EUR&symbols={$symbols}";
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

    /**
     * {@inheritdoc}
     */
    public function getProviderName(): string
    {
        return 'frankfurter';
    }
}
// CLAUDE-CHECKPOINT
