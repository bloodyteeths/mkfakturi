<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * NBRM middle exchange rates to MKD.
 * EUR is pegged at ~61.5 MKD. Others are approximate NBRM rates.
 */
class CurrencyExchangeRatesSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('currency_exchange_rates')) {
            return;
        }

        $rates = [
            ['EUR', 61.5395],
            ['USD', 56.5516],
            ['CHF', 62.1109],
            ['GBP', 71.5000],
            ['BGN', 31.4651],
            ['RSD', 0.5254],
            ['ALL', 0.6121],
            ['TRY', 1.7572],
            ['CZK', 2.4960],
            ['DKK', 8.2475],
            ['HUF', 0.1608],
            ['NOK', 5.3949],
            ['PLN', 14.4857],
            ['RON', 12.3675],
            ['RUB', 0.6404],
            ['SEK', 5.3671],
            ['MKD', 1.0000],
        ];

        $now = now();
        $effectiveDate = '2026-01-01';

        foreach ($rates as [$code, $rate]) {
            DB::table('currency_exchange_rates')->updateOrInsert(
                [
                    'currency_code' => $code,
                    'effective_date' => $effectiveDate,
                ],
                [
                    'rate_to_mkd' => $rate,
                    'source' => 'nbrm',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}

// CLAUDE-CHECKPOINT
