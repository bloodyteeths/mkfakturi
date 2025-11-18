<?php

namespace Database\Seeders;

use App\Models\BankAccount;
use App\Models\Commission;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Database\Seeder;

class PartnerTablesSeeder extends Seeder
{
    /**
     * Seed sample data for partner-related tables.
     */
    public function run(): void
    {
        // Get or create a sample user for partner association
        $user = User::first();
        if (! $user) {
            $user = User::create([
                'name' => 'Sample Partner User',
                'email' => 'partner@example.com',
                'password' => bcrypt('password'),
            ]);
        }

        // Get first company and currency for associations
        $company = Company::first();
        $currency = Currency::first();

        if (! $company || ! $currency) {
            $this->command->info('Warning: No company or currency found. Creating basic records...');

            if (! $currency) {
                $currency = Currency::create([
                    'name' => 'Macedonian Denar',
                    'code' => 'MKD',
                    'symbol' => 'ден',
                    'precision' => 0, // FIXED: MKD has no decimal places
                    'thousand_separator' => '.', // FIXED: MKD uses dot for thousands (120.000)
                    'decimal_separator' => ',',
                    'position' => 'right',
                ]);
            }

            if (! $company) {
                $company = Company::create([
                    'name' => 'Sample Company',
                    'email' => 'company@example.com',
                    'slug' => 'sample-company',
                    'currency_id' => $currency->id,
                ]);
            }
        }

        // Create sample partners
        $partners = [
            [
                'name' => 'Марко Петровски',
                'email' => 'marko.petrovski@email.com',
                'phone' => '+38970123456',
                'company_name' => 'Сметководствена Агенција Петровски',
                'tax_id' => '4080998345006', // Sample Macedonian EDB
                'registration_number' => '7654321', // Sample EMBS
                'bank_account' => '200003345678901234',
                'bank_name' => 'Стопанска Банка АД Скопје',
                'commission_rate' => 15.00,
                'is_active' => true,
                'user_id' => $user->id,
                'notes' => 'Искусен сметководител со 10 години практика во МСП сегментот.',
            ],
            [
                'name' => 'Ана Јовановска',
                'email' => 'ana.jovanovska@email.com',
                'phone' => '+38971987654',
                'company_name' => 'Финанси Плус ДОО',
                'tax_id' => '4080001234567',
                'registration_number' => '1234567',
                'bank_account' => '300001234567890123',
                'bank_name' => 'НЛБ Банка АД Скопје',
                'commission_rate' => 12.50,
                'is_active' => true,
                'user_id' => null, // Partner without user account
                'notes' => 'Специјализирана за ДДВ и корпоративни финансии.',
            ],
        ];

        foreach ($partners as $partnerData) {
            $partner = Partner::create($partnerData);

            // Create a sample commission for each partner
            Commission::create([
                'partner_id' => $partner->id,
                'company_id' => $company->id,
                'commission_type' => 'monthly',
                'commission_amount' => 5000.00, // 5000 MKD monthly retainer
                'commission_rate' => $partner->commission_rate,
                'base_amount' => 5000.00, // Base amount for monthly commission calculation
                'currency_id' => 1, // Assuming MKD is currency ID 1
                'period_start' => now()->startOfMonth(),
                'period_end' => now()->endOfMonth(),
                'status' => 'pending',
                'notes' => 'Месечен ретајнер за сметководствени услуги',
            ]);
        }

        // Create sample bank accounts for the company
        $bankAccounts = [
            [
                'account_name' => 'Основна сметка - Стопанска',
                'account_number' => '200001234567890123',
                'iban' => 'MK07200001234567890123',
                'swift_code' => 'STBAMK22',
                'bank_name' => 'Стопанска Банка АД Скопје',
                'bank_code' => '300',
                'branch' => 'Центар',
                'account_type' => 'business',
                'currency_id' => $currency->id,
                'company_id' => $company->id,
                'opening_balance' => 150000.00,
                'current_balance' => 175000.00,
                'is_primary' => true,
                'is_active' => true,
            ],
            [
                'account_name' => 'Девизна сметка - НЛБ',
                'account_number' => '300005432109876543',
                'iban' => 'MK07300005432109876543',
                'swift_code' => 'NLBMK22',
                'bank_name' => 'НЛБ Банка АД Скопје',
                'bank_code' => '310',
                'branch' => 'Кисела Вода',
                'account_type' => 'business',
                'currency_id' => $currency->id,
                'company_id' => $company->id,
                'opening_balance' => 25000.00,
                'current_balance' => 28500.00,
                'is_primary' => false,
                'is_active' => true,
            ],
        ];

        foreach ($bankAccounts as $accountData) {
            BankAccount::create($accountData);
        }

        $this->command->info('Partner tables seeded successfully!');
        $this->command->info('Created: '.Partner::count().' partners');
        $this->command->info('Created: '.BankAccount::count().' bank accounts');
        $this->command->info('Created: '.Commission::count().' commissions');
    }
}
