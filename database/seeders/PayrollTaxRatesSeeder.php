<?php

namespace Database\Seeders;

use App\Models\PayrollTaxRate;
use Illuminate\Database\Seeder;

class PayrollTaxRatesSeeder extends Seeder
{
    /**
     * Seed payroll tax rates for Macedonia (North Macedonia).
     *
     * As per Macedonian payroll regulations for 2024:
     * - Pension insurance (employee): 9%
     * - Pension insurance (employer): 9%
     * - Health insurance (employee): 3.75%
     * - Health insurance (employer): 3.75%
     * - Unemployment insurance (employee): 1.2%
     * - Additional contribution (employee): 0.5%
     * - Income tax: 10% flat rate
     */
    public function run(): void
    {
        $taxRates = [
            [
                'code' => 'PIO_EMPLOYEE',
                'name' => 'Pension Insurance (Employee)',
                'name_mk' => 'Пензиско осигурување (вработен)',
                'rate' => 0.0900,
                'type' => 'employee',
                'effective_from' => '2024-01-01',
                'effective_to' => null,
                'is_active' => true,
            ],
            [
                'code' => 'PIO_EMPLOYER',
                'name' => 'Pension Insurance (Employer)',
                'name_mk' => 'Пензиско осигурување (работодавач)',
                'rate' => 0.0900,
                'type' => 'employer',
                'effective_from' => '2024-01-01',
                'effective_to' => null,
                'is_active' => true,
            ],
            [
                'code' => 'HEALTH_EMPLOYEE',
                'name' => 'Health Insurance (Employee)',
                'name_mk' => 'Здравствено осигурување (вработен)',
                'rate' => 0.0375,
                'type' => 'employee',
                'effective_from' => '2024-01-01',
                'effective_to' => null,
                'is_active' => true,
            ],
            [
                'code' => 'HEALTH_EMPLOYER',
                'name' => 'Health Insurance (Employer)',
                'name_mk' => 'Здравствено осигурување (работодавач)',
                'rate' => 0.0375,
                'type' => 'employer',
                'effective_from' => '2024-01-01',
                'effective_to' => null,
                'is_active' => true,
            ],
            [
                'code' => 'UNEMPLOYMENT',
                'name' => 'Unemployment Insurance',
                'name_mk' => 'Осигурување од невработеност',
                'rate' => 0.0120,
                'type' => 'employee',
                'effective_from' => '2024-01-01',
                'effective_to' => null,
                'is_active' => true,
            ],
            [
                'code' => 'ADDITIONAL',
                'name' => 'Additional Contribution',
                'name_mk' => 'Дополнителен придонес',
                'rate' => 0.0050,
                'type' => 'employee',
                'effective_from' => '2024-01-01',
                'effective_to' => null,
                'is_active' => true,
            ],
            [
                'code' => 'INCOME_TAX',
                'name' => 'Personal Income Tax',
                'name_mk' => 'Данок на личен доход',
                'rate' => 0.1000,
                'type' => 'employee',
                'effective_from' => '2024-01-01',
                'effective_to' => null,
                'is_active' => true,
            ],
        ];

        foreach ($taxRates as $taxRate) {
            PayrollTaxRate::updateOrCreate(
                ['code' => $taxRate['code']],
                $taxRate
            );
        }
    }
}

// LLM-CHECKPOINT
