<?php

namespace App\Services;

use App\Models\Currency;
use App\Models\ExchangeRateLog;
use App\Providers\CacheServiceProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CurrencyExchangeService
{
    /**
     * Get cached exchange rate between two currencies
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
     * Get multiple exchange rates at once (more efficient)
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
        if (!empty($missingPairs)) {
            $freshRates = $this->fetchMultipleExchangeRates($missingPairs, $companyId);
            $rates = array_merge($rates, $freshRates);
        }
        
        return $rates;
    }

    /**
     * Fetch exchange rate from external API or database
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

        // If not found, fetch from external API
        try {
            $rate = $this->fetchFromExternalAPI($fromCurrency, $toCurrency);
            
            // Log the exchange rate
            $this->logExchangeRate($fromCurrency, $toCurrency, $rate, $companyId);
            
            return $rate;
        } catch (\Exception $e) {
            Log::error('Failed to fetch exchange rate', [
                'from' => $fromCurrency,
                'to' => $toCurrency,
                'error' => $e->getMessage(),
            ]);
            
            // Return fallback rate of 1.0
            return 1.0;
        }
    }

    /**
     * Fetch multiple exchange rates in batch
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
     * Fetch exchange rate from external API (e.g., Fixer.io, ExchangeRate-API)
     */
    protected function fetchFromExternalAPI(string $fromCurrency, string $toCurrency): float
    {
        // Using ExchangeRate-API as it's free and reliable
        $response = Http::timeout(10)->get("https://api.exchangerate-api.com/v4/latest/{$fromCurrency}");
        
        if ($response->successful()) {
            $data = $response->json();
            return $data['rates'][$toCurrency] ?? 1.0;
        }
        
        throw new \Exception('Failed to fetch exchange rate from API');
    }

    /**
     * Log exchange rate to database
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
     * Clear exchange rate cache for a specific currency pair
     */
    public function clearExchangeRateCache(string $fromCurrency, string $toCurrency, int $companyId): void
    {
        $cacheKey = "exchange_rate:{$fromCurrency}:{$toCurrency}:{$companyId}";
        Cache::forget($cacheKey);
    }

    /**
     * Clear all exchange rate cache for a company
     */
    public function clearAllExchangeRateCache(int $companyId): void
    {
        if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
            $redis = Cache::getStore()->connection();
            $pattern = "mkaccounting_cache:company:{$companyId}:exchange_rate:*";
            $keys = $redis->keys($pattern);
            if (!empty($keys)) {
                $redis->del($keys);
            }
        }
    }

    /**
     * Warm up exchange rate cache for common currency pairs
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
        ]);
    }
}