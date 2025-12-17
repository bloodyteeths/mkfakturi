<?php

namespace App\Http\Controllers\V1\Admin\ExchangeRate;

use App\Http\Controllers\Controller;
use App\Models\CompanySetting;
use App\Models\Currency;
use App\Services\FrankfurterExchangeRateService;
use Illuminate\Http\Request;

class GetExchangeRateController extends Controller
{
    protected FrankfurterExchangeRateService $exchangeRateService;

    public function __construct(FrankfurterExchangeRateService $exchangeRateService)
    {
        $this->exchangeRateService = $exchangeRateService;
    }

    /**
     * Get exchange rate for a currency relative to company's base currency
     *
     * Uses free Frankfurter API (European Central Bank data)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request, Currency $currency)
    {
        $companyId = $request->header('company');
        $settings = CompanySetting::getSettings(['currency'], $companyId);
        $baseCurrency = Currency::findOrFail($settings['currency']);

        // Get rate from Frankfurter (free, no API key needed)
        $rate = $this->exchangeRateService->getAndLogRate(
            $companyId,
            $currency,      // from
            $baseCurrency   // to
        );

        if ($rate !== null) {
            return response()->json([
                'exchangeRate' => [$rate],
                'source' => 'frankfurter',
            ], 200);
        }

        return response()->json([
            'error' => 'no_exchange_rate_available',
            'message' => 'Could not fetch exchange rate. Please try again later.',
        ], 200);
    }
}

// CLAUDE-CHECKPOINT
