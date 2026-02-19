<?php

namespace App\Contracts;

use Carbon\Carbon;

/**
 * Interface for exchange rate providers.
 *
 * Implemented by FrankfurterExchangeRateService and NbrmExchangeRateService.
 * Provides a unified API for fetching currency exchange rates from
 * different external data sources.
 *
 * @see \App\Services\FrankfurterExchangeRateService
 * @see \App\Services\NbrmExchangeRateService
 */
interface ExchangeRateProvider
{
    /**
     * Get exchange rate for a currency pair.
     *
     * @param  string  $from  Source currency code (e.g., 'EUR')
     * @param  string  $to  Target currency code (e.g., 'MKD')
     * @param  Carbon|null  $date  Date for historical rate (null = latest)
     * @return float Exchange rate
     *
     * @throws \RuntimeException When rate cannot be fetched
     */
    public function getRate(string $from, string $to, ?Carbon $date = null): float;

    /**
     * Get rates for multiple currency pairs.
     *
     * @param  array  $pairs  Array of pairs [['from' => 'EUR', 'to' => 'MKD'], ...]
     * @param  Carbon|null  $date  Date for historical rates (null = latest)
     * @return array Rates keyed by pair string ['EUR/MKD' => 61.5, ...]
     */
    public function getMultipleRates(array $pairs, ?Carbon $date = null): array;

    /**
     * Get list of supported currency codes.
     *
     * @return string[] Array of ISO 4217 currency codes
     */
    public function getSupportedCurrencies(): array;

    /**
     * Get provider name for logging and identification.
     *
     * @return string Provider identifier (e.g., 'nbrm', 'frankfurter')
     */
    public function getProviderName(): string;
}
