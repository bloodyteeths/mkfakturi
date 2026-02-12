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
 * Uses the configured ExchangeRateProvider (NBRM by default, Frankfurter as fallback).
 */
class GetExchangeRateController extends Controller
{
    /**
     * The exchange rate provider.
     */
    protected ExchangeRateProvider $provider;

    /**
     * @param  ExchangeRateProvider  $provider  Injected via service container
     */
    public function __construct(ExchangeRateProvider $provider)
    {
        $this->provider = $provider;
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

        try {
            $rate = $this->provider->getRate($currency->code, $baseCurrency->code);

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
                'source' => $this->provider->getProviderName(),
            ], 200);
        } catch (\Exception $e) {
            Log::warning('Exchange rate fetch failed', [
                'provider' => $this->provider->getProviderName(),
                'from' => $currency->code,
                'to' => $baseCurrency->code,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'no_exchange_rate_available',
                'message' => 'Could not fetch exchange rate. Please try again later.',
            ], 200);
        }
    }
}
// CLAUDE-CHECKPOINT
