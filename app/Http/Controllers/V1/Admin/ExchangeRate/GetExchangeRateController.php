<?php

namespace App\Http\Controllers\V1\Admin\ExchangeRate;

use App\Contracts\ExchangeRateProvider;
use App\Http\Controllers\Controller;
use App\Models\CompanySetting;
use App\Models\Currency;
use App\Models\ExchangeRateLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Get exchange rate for a currency relative to the company's base currency.
 *
 * Uses the company's configured exchange rate provider (NBRM by default).
 * Falls back to Frankfurter if the primary provider fails.
 */
class GetExchangeRateController extends Controller
{
    /**
     * Resolve the exchange rate provider for the given company.
     */
    protected function resolveProvider(int|string $companyId): ExchangeRateProvider
    {
        $companySetting = CompanySetting::getSetting('exchange_rate_provider', $companyId);
        $provider = $companySetting ?: config('mk.exchange_rates.provider', 'nbrm');

        return match ($provider) {
            'frankfurter' => app(\App\Services\FrankfurterExchangeRateService::class),
            default => app(\App\Services\NbrmExchangeRateService::class),
        };
    }

    /**
     * Get exchange rate for a currency relative to company's base currency.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request, Currency $currency)
    {
        $companyId = $request->header('company');
        $settings = CompanySetting::getSettings(['currency'], $companyId);
        $baseCurrency = Currency::findOrFail($settings['currency']);

        $provider = $this->resolveProvider($companyId);

        try {
            $rate = $provider->getRate($currency->code, $baseCurrency->code);

            // Log the rate for historical tracking
            ExchangeRateLog::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'base_currency_id' => $currency->id,
                    'currency_id' => $baseCurrency->id,
                ],
                [
                    'exchange_rate' => $rate,
                ]
            );

            return response()->json([
                'exchangeRate' => [$rate],
                'source' => $provider->getProviderName(),
            ], 200);
        } catch (\Exception $e) {
            Log::warning('Exchange rate fetch failed', [
                'provider' => $provider->getProviderName(),
                'from' => $currency->code,
                'to' => $baseCurrency->code,
                'error' => $e->getMessage(),
            ]);

            // Try fallback provider
            $fallbackProvider = $provider->getProviderName() === 'nbrm'
                ? app(\App\Services\FrankfurterExchangeRateService::class)
                : app(\App\Services\NbrmExchangeRateService::class);

            try {
                $rate = $fallbackProvider->getRate($currency->code, $baseCurrency->code);

                ExchangeRateLog::updateOrCreate(
                    [
                        'company_id' => $companyId,
                        'base_currency_id' => $currency->id,
                        'currency_id' => $baseCurrency->id,
                    ],
                    [
                        'exchange_rate' => $rate,
                    ]
                );

                return response()->json([
                    'exchangeRate' => [$rate],
                    'source' => $fallbackProvider->getProviderName(),
                ], 200);
            } catch (\Exception $fallbackErr) {
                return response()->json([
                    'error' => 'no_exchange_rate_available',
                    'message' => 'Could not fetch exchange rate. Please try again later.',
                ], 200);
            }
        }
    }
}
