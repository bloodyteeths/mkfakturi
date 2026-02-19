<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\LeaveType;
use Illuminate\Database\Seeder;

/**
 * Leave Types Seeder
 *
 * Seeds default leave types per company based on Macedonian labor law:
 * - Annual Leave (Годишен одмор): 20 days at 100% pay
 * - Sick Leave (Боледување): 30 days at 70% pay (employer-funded)
 * - Maternity Leave (Породилно отсуство): 270 days at 100% pay
 * - Unpaid Leave (Неплатено отсуство): 30 days at 0% pay
 *
 * Idempotent: Uses updateOrCreate to avoid duplicates on re-runs.
 */
class LeaveTypesSeeder extends Seeder
{
    /**
     * Seed default leave types for all companies.
     */
    public function run(): void
    {
        $leaveTypes = [
            [
                'code' => LeaveType::CODE_ANNUAL,
                'name' => 'Annual Leave',
                'name_mk' => 'Годишен одмор',
                'max_days_per_year' => 20,
                'pay_percentage' => 100.00,
                'is_active' => true,
            ],
            [
                'code' => LeaveType::CODE_SICK,
                'name' => 'Sick Leave',
                'name_mk' => 'Боледување',
                'max_days_per_year' => 30,
                'pay_percentage' => 70.00,
                'is_active' => true,
            ],
            [
                'code' => LeaveType::CODE_MATERNITY,
                'name' => 'Maternity Leave',
                'name_mk' => 'Породилно отсуство',
                'max_days_per_year' => 270,
                'pay_percentage' => 100.00,
                'is_active' => true,
            ],
            [
                'code' => LeaveType::CODE_UNPAID,
                'name' => 'Unpaid Leave',
                'name_mk' => 'Неплатено отсуство',
                'max_days_per_year' => 30,
                'pay_percentage' => 0.00,
                'is_active' => true,
            ],
        ];

        $companies = Company::all();

        foreach ($companies as $company) {
            foreach ($leaveTypes as $leaveType) {
                LeaveType::updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'code' => $leaveType['code'],
                    ],
                    $leaveType
                );
            }
        }
    }
}

