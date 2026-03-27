<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Per-diem rates per Уредба за издатоците за службени патувања и селидби во странство.
 * Rates effective from 2015-01-01 (last major revision).
 */
class PerDiemRatesSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('per_diem_rates')) {
            return;
        }

        $rates = [
            // European countries (EUR unless noted)
            ['AL', 'Албанија', 'Albania', 74.00, 'EUR'],
            ['AT', 'Австрија', 'Austria', 94.00, 'EUR'],
            ['BE', 'Белгија', 'Belgium', 92.00, 'EUR'],
            ['BA', 'Босна и Херцеговина', 'Bosnia and Herzegovina', 75.00, 'EUR'],
            ['BG', 'Бугарија', 'Bulgaria', 75.00, 'EUR'],
            ['HR', 'Хрватска', 'Croatia', 81.00, 'EUR'],
            ['CZ', 'Чешка', 'Czech Republic', 82.00, 'EUR'],
            ['DK', 'Данска', 'Denmark', 97.00, 'EUR'],
            ['EE', 'Естонија', 'Estonia', 91.00, 'EUR'],
            ['FI', 'Финска', 'Finland', 92.00, 'EUR'],
            ['FR', 'Франција', 'France', 85.00, 'EUR'],
            ['DE', 'Германија', 'Germany', 87.00, 'EUR'],
            ['GR', 'Грција', 'Greece', 61.00, 'EUR'],
            ['HU', 'Унгарија', 'Hungary', 79.00, 'EUR'],
            ['IS', 'Исланд', 'Iceland', 88.00, 'EUR'],
            ['IE', 'Ирска', 'Ireland', 86.00, 'EUR'],
            ['IT', 'Италија', 'Italy', 76.00, 'EUR'],
            ['XK', 'Косово', 'Kosovo', 45.00, 'EUR'],
            ['LV', 'Летонија', 'Latvia', 87.00, 'EUR'],
            ['LT', 'Литванија', 'Lithuania', 83.00, 'EUR'],
            ['LU', 'Луксембург', 'Luxembourg', 81.00, 'EUR'],
            ['MT', 'Малта', 'Malta', 88.00, 'EUR'],
            ['MD', 'Молдавија', 'Moldova', 106.00, 'EUR'],
            ['ME', 'Црна Гора', 'Montenegro', 78.00, 'EUR'],
            ['NL', 'Холандија', 'Netherlands', 90.00, 'EUR'],
            ['NO', 'Норвешка', 'Norway', 103.00, 'EUR'],
            ['PL', 'Полска', 'Poland', 53.00, 'EUR'],
            ['PT', 'Португалија', 'Portugal', 88.00, 'EUR'],
            ['RO', 'Романија', 'Romania', 76.00, 'EUR'],
            ['RU', 'Русија', 'Russia', 72.00, 'EUR'],
            ['RS', 'Србија', 'Serbia', 82.00, 'EUR'],
            ['SK', 'Словачка', 'Slovakia', 83.00, 'EUR'],
            ['SI', 'Словенија', 'Slovenia', 82.00, 'EUR'],
            ['ES', 'Шпанија', 'Spain', 87.00, 'EUR'],
            ['SE', 'Шведска', 'Sweden', 90.00, 'EUR'],
            ['CH', 'Швајцарија', 'Switzerland', 130.00, 'CHF'],
            ['TR', 'Турција', 'Turkey', 82.00, 'EUR'],
            ['UA', 'Украина', 'Ukraine', 79.00, 'EUR'],
            ['GB', 'Велика Британија', 'United Kingdom', 92.00, 'GBP'],
            // Non-European
            ['US', 'САД', 'United States', 118.00, 'USD'],
            ['CN', 'Кина', 'China', 72.00, 'EUR'],
            ['JP', 'Јапонија', 'Japan', 100.00, 'EUR'],
            ['EG', 'Египет', 'Egypt', 72.00, 'USD'],
            ['IL', 'Израел', 'Israel', 96.00, 'USD'],
            ['AE', 'ОАЕ', 'United Arab Emirates', 95.00, 'USD'],
            // Domestic
            ['MK', 'Македонија', 'North Macedonia', 3430.00, 'MKD'],
        ];

        $now = now();
        $effectiveFrom = '2015-01-01';

        foreach ($rates as [$code, $nameMk, $nameEn, $rate, $currency]) {
            DB::table('per_diem_rates')->updateOrInsert(
                [
                    'country_code' => $code,
                    'city' => null,
                    'effective_from' => $effectiveFrom,
                ],
                [
                    'country_name_mk' => $nameMk,
                    'country_name_en' => $nameEn,
                    'rate' => $rate,
                    'currency_code' => $currency,
                    'effective_to' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}

// CLAUDE-CHECKPOINT
