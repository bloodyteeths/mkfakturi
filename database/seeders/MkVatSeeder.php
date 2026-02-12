<?php

namespace Database\Seeders;

use App\Models\TaxType;
use Illuminate\Database\Seeder;

class MkVatSeeder extends Seeder
{
    /**
     * Seed VAT rates for Macedonia (North Macedonia).
     *
     * As per Macedonian tax regulations (Закон за данокот на додадена вредност):
     * - Standard VAT rate: 18%
     * - Restaurant/hospitality VAT rate: 10% (угостителски услуги)
     * - Reduced VAT rate: 5% (for essential goods and services)
     */
    public function run(): void
    {
        $vatRates = [
            [
                'name' => 'ДДВ 18%',
                'percent' => 18.00,
                'compound_tax' => 0,
                'collective_tax' => 0,
                'description' => 'Стандардна стапка на данок на додадена вредност за Македонија',
                'type' => TaxType::TYPE_GENERAL,
            ],
            [
                'name' => 'ДДВ 10%',
                'percent' => 10.00,
                'compound_tax' => 0,
                'collective_tax' => 0,
                'description' => 'Преференцијална стапка на ДДВ за угостителски услуги во Македонија',
                'type' => TaxType::TYPE_GENERAL,
            ],
            [
                'name' => 'ДДВ 5%',
                'percent' => 5.00,
                'compound_tax' => 0,
                'collective_tax' => 0,
                'description' => 'Намалена стапка на данок на додадена вредност за Македонија (основни добра и услуги)',
                'type' => TaxType::TYPE_GENERAL,
            ],
        ];

        foreach ($vatRates as $vatRate) {
            // Idempotent: skip if rate already exists
            if (TaxType::whereNull('company_id')->where('name', $vatRate['name'])->exists()) {
                continue;
            }
            TaxType::create($vatRate);
        }
    }
}
