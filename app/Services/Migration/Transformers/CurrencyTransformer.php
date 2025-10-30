<?php

namespace App\Services\Migration\Transformers;

use App\Models\Currency;
use App\Models\ExchangeRateLog;
use App\Models\ExchangeRateProvider;
use App\Models\CompanySetting;
use App\Traits\ExchangeRateProvidersTrait;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * CurrencyTransformer - Handle EUR↔MKD conversion for Macedonia accounting data
 * 
 * This transformer handles currency conversion between EUR (European standard) and MKD 
 * (Macedonian Denar) for data migration from Macedonian accounting systems like Onivo, 
 * Megasoft, and Pantheon. It leverages the existing exchange rate infrastructure while
 * providing specialized migration-focused functionality.
 * 
 * Features:
 * - EUR to MKD conversion using real-time or cached rates
 * - MKD to EUR conversion for reverse operations
 * - Batch transformation for performance
 * - Historical rate fallback for legacy data
 * - Comprehensive error handling and validation
 * - Cache management for API rate limiting
 * - Macedonia-specific currency handling
 * - Support for multiple exchange rate providers
 * 
 * Common Macedonia business scenarios:
 * - Import invoices denominated in EUR but need MKD storage
 * - Convert historical transactions with appropriate exchange rates
 * - Handle mixed currency datasets from accounting exports
 * 
 * @package App\Services\Migration\Transformers
 */
class CurrencyTransformer
{
    use ExchangeRateProvidersTrait;

    /**
     * Macedonia Denar currency code
     */
    private const MKD_CURRENCY_CODE = 'MKD';

    /**
     * Euro currency code
     */
    private const EUR_CURRENCY_CODE = 'EUR';

    /**
     * Default precision for currency calculations (2 decimal places)
     */
    private const DEFAULT_PRECISION = 2;

    /**
     * Cache TTL for exchange rates (1 hour)
     */
    private const CACHE_TTL = 3600;

    /**
     * Fallback EUR to MKD rate (used when APIs fail)
     * Based on typical EUR/MKD rate around 61.5 - should be updated periodically
     */
    private const FALLBACK_EUR_TO_MKD_RATE = 61.5;

    /**
     * Company ID for context-aware operations
     */
    private ?int $companyId;

    /**
     * Create new CurrencyTransformer instance
     * 
     * @param int|null $companyId Company ID for context-aware operations
     */
    public function __construct(?int $companyId = null)
    {
        $this->companyId = $companyId ?? request()->header('company');
    }

    /**
     * Transform EUR amount to MKD using current or historical exchange rate
     * 
     * @param string|float|null $eurAmount Amount in EUR
     * @param string|null $date Date for historical rate lookup (Y-m-d format)
     * @param int $precision Decimal precision (default: 2)
     * @return string|null MKD amount or NULL if conversion fails
     */
    public function eurToMkd($eurAmount, ?string $date = null, int $precision = self::DEFAULT_PRECISION): ?string
    {
        if (empty($eurAmount) && $eurAmount !== 0 && $eurAmount !== '0') {
            return null;
        }

        try {
            $floatAmount = (float) $eurAmount;
            
            if (!is_finite($floatAmount)) {
                return null;
            }

            $exchangeRate = $this->getEurToMkdRate($date);
            
            if ($exchangeRate === null) {
                Log::warning('CurrencyTransformer: Failed to get EUR to MKD rate', [
                    'amount' => $eurAmount,
                    'date' => $date,
                    'company_id' => $this->companyId
                ]);
                return null;
            }

            $mkdAmount = $floatAmount * $exchangeRate;
            
            return number_format($mkdAmount, $precision, '.', '');
            
        } catch (Exception $e) {
            Log::error('CurrencyTransformer: EUR to MKD conversion failed', [
                'amount' => $eurAmount,
                'date' => $date,
                'error' => $e->getMessage(),
                'company_id' => $this->companyId
            ]);
            
            return null;
        }
    }

    /**
     * Transform MKD amount to EUR using current or historical exchange rate
     * 
     * @param string|float|null $mkdAmount Amount in MKD
     * @param string|null $date Date for historical rate lookup (Y-m-d format)
     * @param int $precision Decimal precision (default: 2)
     * @return string|null EUR amount or NULL if conversion fails
     */
    public function mkdToEur($mkdAmount, ?string $date = null, int $precision = self::DEFAULT_PRECISION): ?string
    {
        if (empty($mkdAmount) && $mkdAmount !== 0 && $mkdAmount !== '0') {
            return null;
        }

        try {
            $floatAmount = (float) $mkdAmount;
            
            if (!is_finite($floatAmount)) {
                return null;
            }

            $exchangeRate = $this->getEurToMkdRate($date);
            
            if ($exchangeRate === null || $exchangeRate <= 0) {
                Log::warning('CurrencyTransformer: Failed to get EUR to MKD rate for reverse conversion', [
                    'amount' => $mkdAmount,
                    'date' => $date,  
                    'company_id' => $this->companyId
                ]);
                return null;
            }

            $eurAmount = $floatAmount / $exchangeRate;
            
            return number_format($eurAmount, $precision, '.', '');
            
        } catch (Exception $e) {
            Log::error('CurrencyTransformer: MKD to EUR conversion failed', [
                'amount' => $mkdAmount,
                'date' => $date,
                'error' => $e->getMessage(),
                'company_id' => $this->companyId
            ]);
            
            return null;
        }
    }

    /**
     * Transform currency amounts in batch for better performance
     * 
     * @param array $amounts Array of amounts to convert
     * @param string $fromCurrency Source currency code (EUR/MKD)
     * @param string $toCurrency Target currency code (EUR/MKD)
     * @param string|null $date Date for historical rate lookup
     * @param int $precision Decimal precision
     * @return array Array of converted amounts with same keys
     */
    public function transformBatch(
        array $amounts, 
        string $fromCurrency, 
        string $toCurrency, 
        ?string $date = null, 
        int $precision = self::DEFAULT_PRECISION
    ): array {
        // Skip conversion if same currency
        if (strtoupper($fromCurrency) === strtoupper($toCurrency)) {
            return $amounts;
        }

        $converted = [];
        $fromCurrency = strtoupper($fromCurrency);
        $toCurrency = strtoupper($toCurrency);

        // Get exchange rate once for batch processing
        $exchangeRate = null;
        if ($fromCurrency === self::EUR_CURRENCY_CODE && $toCurrency === self::MKD_CURRENCY_CODE) {
            $exchangeRate = $this->getEurToMkdRate($date);
        } elseif ($fromCurrency === self::MKD_CURRENCY_CODE && $toCurrency === self::EUR_CURRENCY_CODE) {
            $baseRate = $this->getEurToMkdRate($date);
            $exchangeRate = $baseRate ? (1 / $baseRate) : null;
        }

        if ($exchangeRate === null) {
            Log::warning('CurrencyTransformer: Batch conversion failed - no exchange rate', [
                'from' => $fromCurrency,
                'to' => $toCurrency,
                'date' => $date,
                'count' => count($amounts)
            ]);
            
            // Return array with null values
            return array_fill_keys(array_keys($amounts), null);
        }

        foreach ($amounts as $key => $amount) {
            try {
                if (empty($amount) && $amount !== 0 && $amount !== '0') {
                    $converted[$key] = null;
                    continue;
                }

                $floatAmount = (float) $amount;
                
                if (!is_finite($floatAmount)) {
                    $converted[$key] = null;
                    continue;
                }

                $convertedAmount = $floatAmount * $exchangeRate;
                $converted[$key] = number_format($convertedAmount, $precision, '.', '');
                
            } catch (Exception $e) {
                $converted[$key] = null;
            }
        }

        return $converted;
    }

    /**
     * Transform currency amounts in a collection while preserving structure
     * 
     * @param Collection $collection Collection containing currency data
     * @param string|array $amountFields Field name(s) containing amounts to convert
     * @param string $fromCurrency Source currency code
     * @param string $toCurrency Target currency code
     * @param string|null $dateField Field containing date for historical rates (optional)
     * @param int $precision Decimal precision
     * @return Collection Transformed collection
     */
    public function transformCollection(
        Collection $collection,
        $amountFields,
        string $fromCurrency,
        string $toCurrency,
        ?string $dateField = null,
        int $precision = self::DEFAULT_PRECISION
    ): Collection {
        $fields = is_array($amountFields) ? $amountFields : [$amountFields];
        
        return $collection->map(function ($item) use ($fields, $fromCurrency, $toCurrency, $dateField, $precision) {
            // Extract date if field specified
            $date = null;
            if ($dateField) {
                if (is_array($item)) {
                    $date = $item[$dateField] ?? null;
                } elseif (is_object($item) && property_exists($item, $dateField)) {
                    $date = $item->{$dateField};
                }
            }

            // Convert each specified field
            foreach ($fields as $field) {
                $amount = null;
                
                if (is_array($item)) {
                    $amount = $item[$field] ?? null;
                } elseif (is_object($item) && property_exists($item, $field)) {
                    $amount = $item->{$field};
                }

                if ($amount !== null) {
                    $converted = $this->convertAmount($amount, $fromCurrency, $toCurrency, $date, $precision);
                    
                    if (is_array($item)) {
                        $item[$field] = $converted;
                    } elseif (is_object($item)) {
                        $item->{$field} = $converted;
                    }
                }
            }
            
            return $item;
        });
    }

    /**
     * Get current EUR to MKD exchange rate
     * Uses cached rate if available, otherwise fetches from provider
     * 
     * @param string|null $date Historical date for rate lookup (Y-m-d format)
     * @return float|null Exchange rate or NULL if unavailable
     */
    public function getEurToMkdRate(?string $date = null): ?float
    {
        try {
            // Use historical rate if date specified
            if ($date && $date !== date('Y-m-d')) {
                return $this->getHistoricalRate($date);
            }

            // Check cache first
            $cacheKey = "exchange_rate_eur_mkd_" . ($this->companyId ?? 'default');
            $cachedRate = Cache::get($cacheKey);
            
            if ($cachedRate !== null) {
                return (float) $cachedRate;
            }

            // Try to get rate from active provider
            $rate = $this->fetchCurrentExchangeRate();
            
            if ($rate !== null) {
                // Cache the rate
                Cache::put($cacheKey, $rate, self::CACHE_TTL);
                return $rate;
            }

            // Fallback to latest rate from database
            $dbRate = $this->getLatestDatabaseRate();
            
            if ($dbRate !== null) {
                return $dbRate;
            }

            // Last resort: use fallback rate and log warning
            Log::warning('CurrencyTransformer: Using fallback EUR to MKD rate', [
                'fallback_rate' => self::FALLBACK_EUR_TO_MKD_RATE,
                'company_id' => $this->companyId
            ]);
            
            return self::FALLBACK_EUR_TO_MKD_RATE;
            
        } catch (Exception $e) {
            Log::error('CurrencyTransformer: Failed to get EUR to MKD rate', [
                'date' => $date,
                'error' => $e->getMessage(),
                'company_id' => $this->companyId
            ]);
            
            return self::FALLBACK_EUR_TO_MKD_RATE;
        }
    }

    /**
     * Convert amount between any supported currencies
     * 
     * @param string|float $amount Amount to convert
     * @param string $fromCurrency Source currency code
     * @param string $toCurrency Target currency code  
     * @param string|null $date Date for historical rates
     * @param int $precision Decimal precision
     * @return string|null Converted amount or NULL if conversion fails
     */
    private function convertAmount($amount, string $fromCurrency, string $toCurrency, ?string $date = null, int $precision = self::DEFAULT_PRECISION): ?string
    {
        $fromCurrency = strtoupper($fromCurrency);
        $toCurrency = strtoupper($toCurrency);

        // No conversion needed if same currency
        if ($fromCurrency === $toCurrency) {
            return (string) $amount;
        }

        // Handle EUR to MKD
        if ($fromCurrency === self::EUR_CURRENCY_CODE && $toCurrency === self::MKD_CURRENCY_CODE) {
            return $this->eurToMkd($amount, $date, $precision);
        }

        // Handle MKD to EUR
        if ($fromCurrency === self::MKD_CURRENCY_CODE && $toCurrency === self::EUR_CURRENCY_CODE) {
            return $this->mkdToEur($amount, $date, $precision);
        }

        // Unsupported currency pair
        Log::warning('CurrencyTransformer: Unsupported currency conversion', [
            'from' => $fromCurrency,
            'to' => $toCurrency,
            'amount' => $amount
        ]);
        
        return null;
    }

    /**
     * Fetch current exchange rate from active provider
     * 
     * @return float|null Current EUR to MKD rate or NULL if unavailable
     */
    private function fetchCurrentExchangeRate(): ?float
    {
        try {
            // Get active exchange rate provider that supports EUR and MKD
            $provider = ExchangeRateProvider::whereJsonContains('currencies', self::EUR_CURRENCY_CODE)
                ->whereJsonContains('currencies', self::MKD_CURRENCY_CODE)
                ->where('active', true)
                ->first();

            if (!$provider) {
                return null;
            }

            // Use the existing trait method to get exchange rate
            $filter = [
                'key' => $provider->key,
                'driver' => $provider->driver,
                'driver_config' => $provider->driver_config
            ];

            $response = $this->getExchangeRate($filter, self::EUR_CURRENCY_CODE, self::MKD_CURRENCY_CODE);
            
            if ($response->status() === 200) {
                $responseData = $response->getData(true);
                if (isset($responseData['exchangeRate'][0])) {
                    return (float) $responseData['exchangeRate'][0];
                }
            }

            return null;
            
        } catch (Exception $e) {
            Log::error('CurrencyTransformer: Failed to fetch current exchange rate', [
                'error' => $e->getMessage(),
                'company_id' => $this->companyId
            ]);
            
            return null;
        }
    }

    /**
     * Get latest exchange rate from database logs
     * 
     * @return float|null Latest EUR to MKD rate from database
     */
    private function getLatestDatabaseRate(): ?float
    {
        try {
            $eurCurrency = Currency::where('code', self::EUR_CURRENCY_CODE)->first();
            $mkdCurrency = Currency::where('code', self::MKD_CURRENCY_CODE)->first();

            if (!$eurCurrency || !$mkdCurrency) {
                return null;
            }

            $rate = ExchangeRateLog::where('base_currency_id', $eurCurrency->id)
                ->where('currency_id', $mkdCurrency->id)
                ->orderBy('created_at', 'desc')
                ->value('exchange_rate');

            return $rate ? (float) $rate : null;
            
        } catch (Exception $e) {
            Log::error('CurrencyTransformer: Failed to get database exchange rate', [
                'error' => $e->getMessage(),
                'company_id' => $this->companyId
            ]);
            
            return null;
        }
    }

    /**
     * Get historical exchange rate for specific date
     * 
     * @param string $date Date in Y-m-d format
     * @return float|null Historical exchange rate or NULL if unavailable
     */
    private function getHistoricalRate(string $date): ?float
    {
        try {
            $eurCurrency = Currency::where('code', self::EUR_CURRENCY_CODE)->first();
            $mkdCurrency = Currency::where('code', self::MKD_CURRENCY_CODE)->first();

            if (!$eurCurrency || !$mkdCurrency) {
                return null;
            }

            // Try to find rate on exact date
            $rate = ExchangeRateLog::where('base_currency_id', $eurCurrency->id)
                ->where('currency_id', $mkdCurrency->id)
                ->whereDate('created_at', $date)
                ->orderBy('created_at', 'desc')
                ->value('exchange_rate');

            if ($rate) {
                return (float) $rate;
            }

            // Fallback to nearest rate before the date
            $rate = ExchangeRateLog::where('base_currency_id', $eurCurrency->id)
                ->where('currency_id', $mkdCurrency->id)
                ->whereDate('created_at', '<=', $date)
                ->orderBy('created_at', 'desc')
                ->value('exchange_rate');

            return $rate ? (float) $rate : null;
            
        } catch (Exception $e) {
            Log::error('CurrencyTransformer: Failed to get historical exchange rate', [
                'date' => $date,
                'error' => $e->getMessage(),
                'company_id' => $this->companyId
            ]);
            
            return null;
        }
    }

    /**
     * Validate currency code format
     * 
     * @param string $currencyCode Currency code to validate
     * @return bool True if valid ISO currency code format
     */
    public function isValidCurrencyCode(string $currencyCode): bool
    {
        return preg_match('/^[A-Z]{3}$/', strtoupper($currencyCode)) === 1;
    }

    /**
     * Get supported currency codes for transformation
     * 
     * @return array Array of supported currency codes
     */
    public function getSupportedCurrencies(): array
    {
        return [self::EUR_CURRENCY_CODE, self::MKD_CURRENCY_CODE];
    }

    /**
     * Get transformation statistics for batch operations
     * 
     * @param array $originalAmounts Original amounts array
     * @param array $transformedAmounts Transformed amounts array
     * @return array Statistics with success/failure counts
     */
    public function getTransformationStats(array $originalAmounts, array $transformedAmounts): array
    {
        $total = count($originalAmounts);
        $successful = count(array_filter($transformedAmounts, function ($amount) {
            return $amount !== null;
        }));
        $failed = $total - $successful;
        $emptyInput = count(array_filter($originalAmounts, function ($amount) {
            return empty($amount) && $amount !== 0 && $amount !== '0';
        }));

        return [
            'total' => $total,
            'successful' => $successful,
            'failed' => $failed,
            'empty_input' => $emptyInput,
            'success_rate' => $total > 0 ? round(($successful / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Clear exchange rate cache
     * Useful when rates need to be refreshed immediately
     * 
     * @return bool True if cache was cleared successfully
     */
    public function clearRateCache(): bool
    {
        $cacheKey = "exchange_rate_eur_mkd_" . ($this->companyId ?? 'default');
        return Cache::forget($cacheKey);
    }

    /**
     * Handle edge cases common in Macedonian accounting exports
     * 
     * @param string $currencyString Currency string that might need cleaning
     * @return string|null Cleaned currency code or null if invalid
     */
    public function handleCurrencyEdgeCases(string $currencyString): ?string
    {
        $cleaned = strtoupper(trim($currencyString));
        
        // Handle common variations
        $currencyMap = [
            'EURO' => 'EUR',
            'EVRO' => 'EUR', // Macedonian spelling
            'EUROS' => 'EUR',
            'DENAR' => 'MKD',
            'DENARI' => 'MKD', // Macedonian spelling
            'МАКЕДОНСКИ ДЕНАР' => 'MKD',
            'MK' => 'MKD',
        ];
        
        if (isset($currencyMap[$cleaned])) {
            return $currencyMap[$cleaned];
        }
        
        // Return if already valid
        if ($this->isValidCurrencyCode($cleaned)) {
            return $cleaned;
        }
        
        return null;
    }
}