<?php

namespace App\Services;

use App\Contracts\ExchangeRateProvider;
use App\Models\Currency;
use App\Models\ExchangeRateLog;
use App\Providers\CacheServiceProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Currency Exchange Service
 *
 * Abstraction layer for fetching and caching exchange rates.
 * Uses a configured ExchangeRateProvider (NBRM or Frankfurter)
 * with automatic fallback to the secondary provider on failure.
 */
class CurrencyExchangeService
{
    /**
     * The primary exchange rate provider.
     */
    protected ExchangeRateProvider $provider;

    /**
     * Create a new CurrencyExchangeService instance.
     *
     * @param  ExchangeRateProvider  $provider  The primary exchange rate provider
     */
    public function __construct(ExchangeRateProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * Get cached exchange rate between two currencies.
     *
     * @param  string  $fromCurrency  Source currency code
     * @param  string  $toCurrency  Target currency code
     * @param  int  $companyId  Company ID for cache scoping
     * @return float Exchange rate (1.0 as fallback if all providers fail)
     */
    public function getExchangeRate(string $fromCurrency, string $toCurrency, int $companyId): float
    {
        if ($fromCurrency === $toCurrency) {
            return 1.0;
        }

        $cacheKey = "exchange_rate:{$fromCurrency}:{$toCurrency}:{$companyId}";

        return Cache::companyRemember(
            $cacheKey,
            CacheServiceProvider::CACHE_TTLS['MEDIUM'],
            function () use ($fromCurrency, $toCurrency, $companyId) {
                return $this->fetchExchangeRate($fromCurrency, $toCurrency, $companyId);
            }
        );
    }

    /**
     * Get multiple exchange rates at once (more efficient).
     *
     * @param  array  $currencyPairs  Array of ['from' => 'EUR', 'to' => 'MKD'] pairs
     * @param  int  $companyId  Company ID for cache scoping
     * @return array Rates keyed by "from:to" string
     */
    public function getMultipleExchangeRates(array $currencyPairs, int $companyId): array
    {
        $rates = [];
        $missingPairs = [];

        // Check cache first
        foreach ($currencyPairs as $pair) {
            $from = $pair['from'];
            $to = $pair['to'];

            if ($from === $to) {
                $rates["{$from}:{$to}"] = 1.0;

                continue;
            }

            $cacheKey = "exchange_rate:{$from}:{$to}:{$companyId}";
            $cachedRate = Cache::get($cacheKey);

            if ($cachedRate !== null) {
                $rates["{$from}:{$to}"] = $cachedRate;
            } else {
                $missingPairs[] = $pair;
            }
        }

        // Fetch missing rates in batch
        if (! empty($missingPairs)) {
            $freshRates = $this->fetchMultipleExchangeRates($missingPairs, $companyId);
            $rates = array_merge($rates, $freshRates);
        }

        return $rates;
    }

    /**
     * Fetch exchange rate from provider or database.
     *
     * @param  string  $fromCurrency  Source currency code
     * @param  string  $toCurrency  Target currency code
     * @param  int  $companyId  Company ID
     * @return float Exchange rate
     */
    protected function fetchExchangeRate(string $fromCurrency, string $toCurrency, int $companyId): float
    {
        // First try to get from recent exchange rate logs
        $recentRate = ExchangeRateLog::where('company_id', $companyId)
            ->whereHas('baseCurrency', function ($query) use ($fromCurrency) {
                $query->where('code', $fromCurrency);
            })
            ->whereHas('currency', function ($query) use ($toCurrency) {
                $query->where('code', $toCurrency);
            })
            ->where('created_at', '>=', now()->subHours(6))
            ->orderBy('created_at', 'desc')
            ->first();

        if ($recentRate) {
            return $recentRate->exchange_rate;
        }

        // If not found, fetch from configured provider
        try {
            $rate = $this->fetchFromProvider($fromCurrency, $toCurrency);

            // Log the exchange rate
            $this->logExchangeRate($fromCurrency, $toCurrency, $rate, $companyId);

            return $rate;
        } catch (\Exception $e) {
            Log::warning("Primary provider ({$this->provider->getProviderName()}) failed, trying fallback", [
                'from' => $fromCurrency,
                'to' => $toCurrency,
                'error' => $e->getMessage(),
            ]);

            // Try fallback provider
            try {
                $rate = $this->fetchFromFallbackProvider($fromCurrency, $toCurrency);

                // Log the exchange rate
                $this->logExchangeRate($fromCurrency, $toCurrency, $rate, $companyId);

                return $rate;
            } catch (\Exception $fallbackException) {
                Log::error('All exchange rate providers failed', [
                    'from' => $fromCurrency,
                    'to' => $toCurrency,
                    'primary_error' => $e->getMessage(),
                    'fallback_error' => $fallbackException->getMessage(),
                ]);

                // Return fallback rate of 1.0
                return 1.0;
            }
        }
    }

    /**
     * Fetch multiple exchange rates in batch.
     *
     * @param  array  $currencyPairs  Array of currency pair arrays
     * @param  int  $companyId  Company ID
     * @return array Rates keyed by "from:to" string
     */
    protected function fetchMultipleExchangeRates(array $currencyPairs, int $companyId): array
    {
        $rates = [];

        foreach ($currencyPairs as $pair) {
            $from = $pair['from'];
            $to = $pair['to'];
            $rate = $this->fetchExchangeRate($from, $to, $companyId);

            $rates["{$from}:{$to}"] = $rate;

            // Cache the rate
            $cacheKey = "exchange_rate:{$from}:{$to}:{$companyId}";
            Cache::put($cacheKey, $rate, CacheServiceProvider::CACHE_TTLS['MEDIUM']);
        }

        return $rates;
    }

    /**
     * Fetch exchange rate from the primary configured provider.
     *
     * @param  string  $fromCurrency  Source currency code
     * @param  string  $toCurrency  Target currency code
     * @return float Exchange rate
     *
     * @throws \RuntimeException When provider fails
     */
    protected function fetchFromProvider(string $fromCurrency, string $toCurrency): float
    {
        return $this->provider->getRate($fromCurrency, $toCurrency);
    }

    /**
     * Fetch exchange rate from the fallback provider.
     *
     * If primary is NBRM, fallback is Frankfurter, and vice versa.
     *
     * @param  string  $fromCurrency  Source currency code
     * @param  string  $toCurrency  Target currency code
     * @return float Exchange rate
     *
     * @throws \RuntimeException When fallback provider also fails
     */
    protected function fetchFromFallbackProvider(string $fromCurrency, string $toCurrency): float
    {
        $primaryName = $this->provider->getProviderName();
        $fallbackName = $primaryName === 'nbrm' ? 'frankfurter' : 'nbrm';

        Log::info("Using fallback provider: {$fallbackName}", [
            'from' => $fromCurrency,
            'to' => $toCurrency,
        ]);

        /** @var ExchangeRateProvider $fallback */
        $fallback = match ($fallbackName) {
            'nbrm' => app(NbrmExchangeRateService::class),
            'frankfurter' => app(FrankfurterExchangeRateService::class),
            default => throw new \RuntimeException("Unknown fallback provider: {$fallbackName}"),
        };

        return $fallback->getRate($fromCurrency, $toCurrency);
    }

    /**
     * Log exchange rate to database.
     *
     * @param  string  $fromCurrency  Source currency code
     * @param  string  $toCurrency  Target currency code
     * @param  float  $rate  The exchange rate
     * @param  int  $companyId  Company ID
     */
    protected function logExchangeRate(string $fromCurrency, string $toCurrency, float $rate, int $companyId): void
    {
        try {
            $baseCurrency = Currency::where('code', $fromCurrency)->first();
            $targetCurrency = Currency::where('code', $toCurrency)->first();

            if ($baseCurrency && $targetCurrency) {
                ExchangeRateLog::create([
                    'company_id' => $companyId,
                    'base_currency_id' => $baseCurrency->id,
                    'currency_id' => $targetCurrency->id,
                    'exchange_rate' => $rate,
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to log exchange rate', [
                'from' => $fromCurrency,
                'to' => $toCurrency,
                'rate' => $rate,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Clear exchange rate cache for a specific currency pair.
     *
     * @param  string  $fromCurrency  Source currency code
     * @param  string  $toCurrency  Target currency code
     * @param  int  $companyId  Company ID
     */
    public function clearExchangeRateCache(string $fromCurrency, string $toCurrency, int $companyId): void
    {
        $cacheKey = "exchange_rate:{$fromCurrency}:{$toCurrency}:{$companyId}";
        Cache::forget($cacheKey);
    }

    /**
     * Clear all exchange rate cache for a company.
     *
     * @param  int  $companyId  Company ID
     */
    public function clearAllExchangeRateCache(int $companyId): void
    {
        if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
            $redis = Cache::getStore()->connection();
            $pattern = "mkaccounting_cache:company:{$companyId}:exchange_rate:*";
            $keys = $redis->keys($pattern);
            if (! empty($keys)) {
                $redis->del($keys);
            }
        }
    }

    /**
     * Warm up exchange rate cache for common currency pairs.
     *
     * @param  int  $companyId  Company ID
     * @param  array  $currencies  Currency codes to warm up
     */
    public function warmUpCache(int $companyId, array $currencies = ['EUR', 'USD', 'MKD']): void
    {
        $pairs = [];

        // Generate all possible pairs
        foreach ($currencies as $from) {
            foreach ($currencies as $to) {
                if ($from !== $to) {
                    $pairs[] = ['from' => $from, 'to' => $to];
                }
            }
        }

        // Fetch rates in batch
        $this->getMultipleExchangeRates($pairs, $companyId);

        Log::info('Exchange rate cache warmed up', [
            'company_id' => $companyId,
            'currencies' => $currencies,
            'pairs_count' => count($pairs),
            'provider' => $this->provider->getProviderName(),
        ]);
    }

    /**
     * Get the name of the currently configured provider.
     *
     * @return string Provider name
     */
    public function getProviderName(): string
    {
        return $this->provider->getProviderName();
    }
}
// CLAUDE-CHECKPOINT
