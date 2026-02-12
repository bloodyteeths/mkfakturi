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
 * NBRM (National Bank of Republic of Macedonia) Exchange Rate Service.
 *
 * Fetches official daily exchange rates from the NBRM XML API.
 * All NBRM rates are quoted as MKD per 1 foreign currency unit.
 * Uses the middle rate (sredena_vrednost) for conversions.
 *
 * @see https://www.nbrm.mk/kursna-lista-en.nspx
 */
class NbrmExchangeRateService implements ExchangeRateProvider
{
    /**
     * NBRM currencies that are always published in the daily rate list.
     * These are the most common currencies on the NBRM kursna lista.
     */
    protected const KNOWN_CURRENCIES = [
        'EUR', 'USD', 'GBP', 'CHF', 'SEK', 'NOK', 'DKK', 'JPY',
        'CAD', 'AUD', 'HRK', 'BAM', 'RSD', 'TRY', 'PLN', 'CZK',
        'HUF', 'RON', 'BGN', 'CNY',
    ];

    /**
     * Get the NBRM API base URL from config.
     */
    protected function getBaseUrl(): string
    {
        return config('mk.exchange_rates.nbrm.base_url', 'https://www.nbrm.mk/KLServiceNOV');
    }

    /**
     * Get the cache TTL in seconds from config.
     */
    protected function getCacheTtl(): int
    {
        return (int) config('mk.exchange_rates.nbrm.cache_ttl', 86400);
    }

    /**
     * {@inheritdoc}
     */
    public function getRate(string $from, string $to, ?Carbon $date = null): float
    {
        if ($from === $to) {
            return 1.0;
        }

        $date = $date ?? Carbon::today();
        $dateKey = $date->format('Y-m-d');
        $cacheKey = "nbrm_rate:{$from}:{$to}:{$dateKey}";

        return Cache::remember($cacheKey, $this->getCacheTtl(), function () use ($from, $to, $date) {
            return $this->fetchRate($from, $to, $date);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getMultipleRates(array $pairs, ?Carbon $date = null): array
    {
        $date = $date ?? Carbon::today();
        $results = [];

        // Fetch all NBRM rates for the date once (they come in a single XML)
        $allRates = $this->fetchAllRates($date);

        foreach ($pairs as $pair) {
            $from = $pair['from'];
            $to = $pair['to'];
            $key = "{$from}/{$to}";

            if ($from === $to) {
                $results[$key] = 1.0;

                continue;
            }

            try {
                $rate = $this->calculateRate($from, $to, $allRates);
                $results[$key] = $rate;
            } catch (\RuntimeException $e) {
                Log::warning('NBRM: Could not calculate rate for pair', [
                    'from' => $from,
                    'to' => $to,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedCurrencies(): array
    {
        $cacheKey = 'nbrm_supported_currencies';

        return Cache::remember($cacheKey, $this->getCacheTtl(), function () {
            try {
                $rates = $this->fetchAllRates(Carbon::today());

                // MKD is the base currency, always supported
                $currencies = array_merge(['MKD'], array_keys($rates));

                return array_unique($currencies);
            } catch (\Exception $e) {
                Log::warning('NBRM: Failed to fetch supported currencies, returning known list', [
                    'error' => $e->getMessage(),
                ]);

                // Return known list as fallback
                return array_merge(['MKD'], self::KNOWN_CURRENCIES);
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getProviderName(): string
    {
        return 'nbrm';
    }

    /**
     * Fetch a single exchange rate from NBRM.
     *
     * @param  string  $from  Source currency code
     * @param  string  $to  Target currency code
     * @param  Carbon  $date  Date for the rate
     * @return float The exchange rate
     *
     * @throws \RuntimeException When the rate cannot be fetched
     */
    protected function fetchRate(string $from, string $to, Carbon $date): float
    {
        $allRates = $this->fetchAllRates($date);

        $rate = $this->calculateRate($from, $to, $allRates);

        $this->logRate($from, $to, $rate);

        return $rate;
    }

    /**
     * Calculate the exchange rate between two currencies using NBRM rates.
     *
     * NBRM rates are all quoted as: MKD per N units of foreign currency.
     * The rate array stores the MKD value per 1 unit of foreign currency.
     *
     * Conversion logic:
     * - FROM MKD to X: rate = 1 / allRates[X]  (inverted, gives X per 1 MKD)
     *   Wait, we want: how many X do I get for 1 MKD?
     *   allRates[X] = MKD per 1 X, so 1 MKD = 1/allRates[X] X
     * - FROM X to MKD: rate = allRates[X]  (direct)
     * - FROM X to Y (cross-rate): rate = allRates[Y] != 0 ? allRates[X] / allRates[Y] : error
     *   Wait: 1 X = allRates[X] MKD, and 1 Y = allRates[Y] MKD
     *   So 1 X = allRates[X] / allRates[Y] Y
     *
     * @param  string  $from  Source currency code
     * @param  string  $to  Target currency code
     * @param  array  $allRates  NBRM rates keyed by currency code (MKD per 1 foreign unit)
     * @return float The calculated exchange rate
     *
     * @throws \RuntimeException When the rate cannot be calculated
     */
    protected function calculateRate(string $from, string $to, array $allRates): float
    {
        // Case 1: from foreign to MKD
        if ($to === 'MKD') {
            if (! isset($allRates[$from])) {
                throw new \RuntimeException("NBRM does not publish rate for {$from}");
            }

            return $allRates[$from];
        }

        // Case 2: from MKD to foreign
        if ($from === 'MKD') {
            if (! isset($allRates[$to])) {
                throw new \RuntimeException("NBRM does not publish rate for {$to}");
            }

            if ($allRates[$to] == 0) {
                throw new \RuntimeException("NBRM rate for {$to} is zero, cannot invert");
            }

            return 1.0 / $allRates[$to];
        }

        // Case 3: cross-rate via MKD (e.g., EUR to USD)
        if (! isset($allRates[$from])) {
            throw new \RuntimeException("NBRM does not publish rate for {$from}");
        }

        if (! isset($allRates[$to])) {
            throw new \RuntimeException("NBRM does not publish rate for {$to}");
        }

        if ($allRates[$to] == 0) {
            throw new \RuntimeException("NBRM rate for {$to} is zero, cannot calculate cross-rate");
        }

        // 1 FROM = allRates[FROM] MKD = (allRates[FROM] / allRates[TO]) TO
        return $allRates[$from] / $allRates[$to];
    }

    /**
     * Fetch all exchange rates from NBRM for a given date.
     *
     * The NBRM API returns XML with elements like:
     * <KursZbirkaNOV>
     *   <Valuta>EUR</Valuta>
     *   <Nomin>1</Nomin>
     *   <Sreden>61.5</Sreden>
     *   ...
     * </KursZbirkaNOV>
     *
     * Returns an array keyed by currency code with the MKD rate per 1 unit.
     *
     * @param  Carbon  $date  Date to fetch rates for
     * @return array Rates keyed by currency code ['EUR' => 61.5, 'USD' => 56.2, ...]
     *
     * @throws \RuntimeException When NBRM API is unavailable or returns invalid data
     */
    protected function fetchAllRates(Carbon $date): array
    {
        $dateKey = $date->format('Y-m-d');
        $cacheKey = "nbrm_all_rates:{$dateKey}";

        return Cache::remember($cacheKey, $this->getCacheTtl(), function () use ($date) {
            return $this->callNbrmApi($date);
        });
    }

    /**
     * Make the actual HTTP call to the NBRM API and parse the XML response.
     *
     * @param  Carbon  $date  Date to fetch rates for
     * @return array Rates keyed by currency code
     *
     * @throws \RuntimeException When NBRM API is unavailable or returns invalid data
     */
    protected function callNbrmApi(Carbon $date): array
    {
        $formattedDate = $date->format('d.m.Y');
        $url = $this->getBaseUrl().'/GetExchangeRateD';

        Log::info('NBRM: Fetching exchange rates', [
            'url' => $url,
            'date' => $formattedDate,
        ]);

        try {
            $response = Http::timeout(15)
                ->get($url, [
                    'StartDate' => $formattedDate,
                    'EndDate' => $formattedDate,
                ]);

            if (! $response->successful()) {
                throw new \RuntimeException(
                    "NBRM API returned HTTP {$response->status()}"
                );
            }

            $body = $response->body();

            if (empty($body)) {
                throw new \RuntimeException('NBRM API returned empty response');
            }

            return $this->parseNbrmXml($body);
        } catch (\RuntimeException $e) {
            // Re-throw RuntimeExceptions (our own errors)
            throw $e;
        } catch (\Exception $e) {
            Log::error('NBRM: API call failed', [
                'error' => $e->getMessage(),
                'date' => $formattedDate,
            ]);

            throw new \RuntimeException(
                'NBRM API is unavailable: '.$e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Parse the NBRM XML response into an array of rates.
     *
     * The NBRM XML format contains <KursZbirkaNOV> elements with:
     * - <Valuta> or <Oznaka>: Currency code (e.g., "EUR")
     * - <Nomin>: Denomination/units (e.g., 1, 100 for JPY)
     * - <Sreden> or <Sredna>: Middle rate (sredena vrednost)
     * - <Kupoven>: Buy rate
     * - <Prodazen>: Sell rate
     *
     * We use the middle rate (Sreden/Sredna) and normalize by denomination.
     *
     * @param  string  $xml  Raw XML response from NBRM
     * @return array Rates keyed by currency code (per 1 unit)
     *
     * @throws \RuntimeException When XML cannot be parsed
     */
    public function parseNbrmXml(string $xml): array
    {
        // Suppress XML warnings and parse
        $previousValue = libxml_use_internal_errors(true);

        try {
            $doc = simplexml_load_string($xml);

            if ($doc === false) {
                $errors = libxml_get_errors();
                libxml_clear_errors();
                $errorMessages = array_map(fn ($e) => trim($e->message), $errors);

                throw new \RuntimeException(
                    'Failed to parse NBRM XML: '.implode('; ', $errorMessages)
                );
            }

            $rates = [];

            // NBRM returns items in <KursZbirkaNOV> elements
            // The root element might vary, so we search for rate entries
            $entries = $doc->xpath('//KursZbirkaNOV') ?: $doc->xpath('//KursZbirka') ?: [];

            if (empty($entries)) {
                // Try direct children if XPath fails
                foreach ($doc->children() as $child) {
                    if (isset($child->Valuta) || isset($child->Oznaka)) {
                        $entries[] = $child;
                    }
                }
            }

            foreach ($entries as $entry) {
                // Currency code: try <Oznaka> first, then <Valuta>
                $currencyCode = trim((string) ($entry->Oznaka ?? $entry->Valuta ?? ''));

                if (empty($currencyCode)) {
                    continue;
                }

                // Denomination (units): how many units the rate is for
                $denomination = (int) ($entry->Nomin ?? $entry->Edinici ?? 1);
                if ($denomination <= 0) {
                    $denomination = 1;
                }

                // Middle rate (sredena vrednost): try multiple field names
                $middleRate = 0.0;
                foreach (['Sreden', 'Sredna', 'sredena_vrednost'] as $field) {
                    if (isset($entry->{$field})) {
                        $rateValue = str_replace(',', '.', (string) $entry->{$field});
                        $middleRate = (float) $rateValue;

                        break;
                    }
                }

                if ($middleRate <= 0) {
                    Log::debug('NBRM: Skipping currency with zero/negative middle rate', [
                        'currency' => $currencyCode,
                    ]);

                    continue;
                }

                // Normalize to per-1-unit rate
                // NBRM says: $denomination units of $currencyCode = $middleRate MKD
                // So 1 unit of $currencyCode = $middleRate / $denomination MKD
                $ratePerUnit = $middleRate / $denomination;

                $rates[strtoupper($currencyCode)] = round($ratePerUnit, 6);
            }

            if (empty($rates)) {
                throw new \RuntimeException('NBRM XML contained no valid exchange rate entries');
            }

            Log::info('NBRM: Parsed exchange rates', [
                'count' => count($rates),
                'currencies' => array_keys($rates),
            ]);

            return $rates;
        } finally {
            libxml_use_internal_errors($previousValue);
        }
    }

    /**
     * Log the fetched rate to the ExchangeRateLog table for auditing.
     *
     * @param  string  $from  Source currency code
     * @param  string  $to  Target currency code
     * @param  float  $rate  The exchange rate
     */
    protected function logRate(string $from, string $to, float $rate): void
    {
        try {
            $baseCurrency = Currency::where('code', $from)->first();
            $targetCurrency = Currency::where('code', $to)->first();

            if ($baseCurrency && $targetCurrency) {
                ExchangeRateLog::create([
                    'company_id' => null, // System-level rate, not company-specific
                    'base_currency_id' => $baseCurrency->id,
                    'currency_id' => $targetCurrency->id,
                    'exchange_rate' => $rate,
                ]);

                Log::debug('NBRM: Rate logged', [
                    'from' => $from,
                    'to' => $to,
                    'rate' => $rate,
                    'provider' => 'nbrm',
                ]);
            }
        } catch (\Exception $e) {
            // Don't let logging failures break the rate fetch
            Log::warning('NBRM: Failed to log exchange rate', [
                'from' => $from,
                'to' => $to,
                'rate' => $rate,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
// CLAUDE-CHECKPOINT
