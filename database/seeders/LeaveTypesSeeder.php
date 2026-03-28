<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\LeaveType;
use Illuminate\Database\Seeder;

/**
 * Leave Types Seeder
 *
 * Seeds default leave types per company based on Macedonian labor law
 * (Закон за работни односи, Art. 137, 146-149):
 *
 * - Annual Leave (Годишен одмор): 20 days at 100% pay (Art. 137)
 * - Sick Leave (Боледување): 30 days at 70% pay, employer-funded (Art. 112)
 * - Sick Leave - Work Injury: 30 days at 100% pay from day 1 (Art. 113)
 * - Maternity Leave (Породилно): 270 days at 100% pay
 * - Parental Leave (Татковско): 7 days at 100% pay
 * - Marriage Leave (Брак): 3 days at 100% pay (Art. 146)
 * - Bereavement Leave (Смрт): 5 days at 100% pay (Art. 146)
 * - Blood Donation: 2 days at 100% pay (Art. 146)
 * - Study/Exam Leave: 7 days at 100% pay (Art. 146)
 * - Moving House: 2 days at 100% pay (Art. 146)
 * - Natural Disaster: 3 days at 100% pay (Art. 146)
 * - Unpaid Leave (Неплатено): 90 days at 0% pay (Art. 149)
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
                'code' => LeaveType::CODE_SICK_WORK_INJURY,
                'name' => 'Sick Leave - Work Injury',
                'name_mk' => 'Боледување - Повреда на работа',
                'max_days_per_year' => 30,
                'pay_percentage' => 100.00,
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
                'code' => LeaveType::CODE_PARENTAL,
                'name' => 'Parental Leave (Father)',
                'name_mk' => 'Татковско отсуство',
                'max_days_per_year' => 7,
                'pay_percentage' => 100.00,
                'is_active' => true,
            ],
            [
                'code' => LeaveType::CODE_MARRIAGE,
                'name' => 'Marriage Leave',
                'name_mk' => 'Склучување на брак',
                'max_days_per_year' => 3,
                'pay_percentage' => 100.00,
                'is_active' => true,
            ],
            [
                'code' => LeaveType::CODE_BEREAVEMENT,
                'name' => 'Bereavement Leave',
                'name_mk' => 'Смрт на близок член',
                'max_days_per_year' => 5,
                'pay_percentage' => 100.00,
                'is_active' => true,
            ],
            [
                'code' => LeaveType::CODE_BLOOD_DONATION,
                'name' => 'Blood Donation Leave',
                'name_mk' => 'Доброволно давање крв',
                'max_days_per_year' => 2,
                'pay_percentage' => 100.00,
                'is_active' => true,
            ],
            [
                'code' => LeaveType::CODE_STUDY,
                'name' => 'Study/Exam Leave',
                'name_mk' => 'Стручно образование / Испити',
                'max_days_per_year' => 7,
                'pay_percentage' => 100.00,
                'is_active' => true,
            ],
            [
                'code' => LeaveType::CODE_MOVING,
                'name' => 'Moving House Leave',
                'name_mk' => 'Преселба',
                'max_days_per_year' => 2,
                'pay_percentage' => 100.00,
                'is_active' => true,
            ],
            [
                'code' => LeaveType::CODE_NATURAL_DISASTER,
                'name' => 'Natural Disaster Leave',
                'name_mk' => 'Елементарна непогода',
                'max_days_per_year' => 3,
                'pay_percentage' => 100.00,
                'is_active' => true,
            ],
            [
                'code' => LeaveType::CODE_UNPAID,
                'name' => 'Unpaid Leave',
                'name_mk' => 'Неплатено отсуство',
                'max_days_per_year' => 90,
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
