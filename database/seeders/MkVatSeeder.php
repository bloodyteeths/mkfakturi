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
     * - Zero-rated: 0% for exports and international transport
     * - Exempt: 0% for banking, insurance, healthcare, education (no input credit)
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
                'category' => TaxType::CATEGORY_STANDARD,
            ],
            [
                'name' => 'ДДВ 10%',
                'percent' => 10.00,
                'compound_tax' => 0,
                'collective_tax' => 0,
                'description' => 'Преференцијална стапка на ДДВ за угостителски услуги во Македонија',
                'type' => TaxType::TYPE_GENERAL,
                'category' => TaxType::CATEGORY_HOSPITALITY,
            ],
            [
                'name' => 'ДДВ 5%',
                'percent' => 5.00,
                'compound_tax' => 0,
                'collective_tax' => 0,
                'description' => 'Намалена стапка на данок на додадена вредност за Македонија (основни добра и услуги)',
                'type' => TaxType::TYPE_GENERAL,
                'category' => TaxType::CATEGORY_REDUCED,
            ],
            [
                'name' => 'ДДВ 0% - Извоз',
                'percent' => 0.00,
                'compound_tax' => 0,
                'collective_tax' => 0,
                'description' => 'Нулта стапка за извоз на добра и меѓународен транспорт (со право на одбивка на претходен данок)',
                'type' => TaxType::TYPE_GENERAL,
                'category' => TaxType::CATEGORY_ZERO_RATED,
                'calculation_type' => 'percentage',
            ],
            [
                'name' => 'Ослободено од ДДВ',
                'percent' => 0.00,
                'compound_tax' => 0,
                'collective_tax' => 0,
                'description' => 'Ослободени дејности без право на одбивка (банкарство, осигурување, здравство, образование)',
                'type' => TaxType::TYPE_GENERAL,
                'category' => TaxType::CATEGORY_EXEMPT,
                'calculation_type' => 'percentage',
            ],
        ];

        foreach ($vatRates as $vatRate) {
            $existing = TaxType::whereNull('company_id')
                ->where('name', $vatRate['name'])
                ->first();

            if ($existing) {
                // Backfill category on existing types if missing
                if (! $existing->category && isset($vatRate['category'])) {
                    $existing->update(['category' => $vatRate['category']]);
                }

                continue;
            }

            TaxType::create($vatRate);
        }
    }
}

// CLAUDE-CHECKPOINT
