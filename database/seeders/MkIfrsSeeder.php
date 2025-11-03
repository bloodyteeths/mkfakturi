<?php

namespace Database\Seeders;

use App\Models\User;
use IFRS\Models\Account;
use IFRS\Models\Category;
use IFRS\Models\Currency;
use IFRS\Models\Entity;
use IFRS\Models\ReportingPeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Macedonian Chart of Accounts Seeder for IFRS
 *
 * This seeder creates a basic Macedonian COA following the structure:
 * - Assets: 1000-1999
 * - Liabilities: 2000-2999
 * - Equity: 3000-3999
 * - Revenue: 4000-4999
 * - Expenses: 5000-5999
 *
 * @package Database\Seeders
 */
class MkIfrsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Set a default user for IFRS operations (required by the package)
        $user = User::first();
        if (!$user) {
            $this->command->warn('No users found. Please run DatabaseSeeder first.');
            return;
        }
        Auth::login($user);

        DB::beginTransaction();

        try {
            // Create entity and currencies using raw DB to bypass IFRS scopes
            // This is necessary because IFRS requires a user with entity relationship

            // Create entity first
            $entityId = DB::table('ifrs_entities')->insertGetId([
                'name' => 'Demo Company',
                'currency_id' => 1, // Temporary, will update
                'created_at' => now(),
                'updated_at' => now(),
            ], 'id');

            // Create currencies
            $mkdId = DB::table('ifrs_currencies')->insertGetId([
                'name' => 'Macedonian Denar',
                'currency_code' => 'MKD',
                'entity_id' => $entityId,
                'created_at' => now(),
                'updated_at' => now(),
            ], 'id');

            $eurId = DB::table('ifrs_currencies')->insertGetId([
                'name' => 'Euro',
                'currency_code' => 'EUR',
                'entity_id' => $entityId,
                'created_at' => now(),
                'updated_at' => now(),
            ], 'id');

            // Update entity with correct currency_id
            DB::table('ifrs_entities')
                ->where('id', $entityId)
                ->update(['currency_id' => $mkdId]);

            // Create current reporting period using raw DB
            $year = now()->year;
            if (!DB::table('ifrs_reporting_periods')->where('entity_id', $entityId)->where('calendar_year', $year)->exists()) {
                DB::table('ifrs_reporting_periods')->insert([
                    'period_count' => 1,
                    'calendar_year' => $year,
                    'entity_id' => $entityId,
                    'status' => ReportingPeriod::OPEN,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Create account categories using raw DB
            $categories = [
                ['name' => 'Current Assets', 'category_type' => Account::CURRENT_ASSET],
                ['name' => 'Fixed Assets', 'category_type' => Account::NON_CURRENT_ASSET],
                ['name' => 'Current Liabilities', 'category_type' => Account::CURRENT_LIABILITY],
                ['name' => 'Long-term Liabilities', 'category_type' => Account::NON_CURRENT_LIABILITY],
                ['name' => 'Equity', 'category_type' => Account::EQUITY],
                ['name' => 'Revenue', 'category_type' => Account::OPERATING_REVENUE],
                ['name' => 'Operating Expenses', 'category_type' => Account::OPERATING_EXPENSE],
                ['name' => 'Other Expenses', 'category_type' => Account::OTHER_EXPENSE],
            ];

            foreach ($categories as $cat) {
                if (!DB::table('ifrs_categories')->where('entity_id', $entityId)->where('name', $cat['name'])->exists()) {
                    DB::table('ifrs_categories')->insert([
                        'name' => $cat['name'],
                        'category_type' => $cat['category_type'],
                        'entity_id' => $entityId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // Seed Macedonian Chart of Accounts
            $accounts = [
                // ASSETS (1000-1999)
                // Current Assets
                ['code' => 1000, 'name' => 'Cash on Hand', 'type' => Account::BANK],
                ['code' => 1100, 'name' => 'Bank Account - MKD', 'type' => Account::BANK],
                ['code' => 1110, 'name' => 'Bank Account - EUR', 'type' => Account::BANK],
                ['code' => 1200, 'name' => 'Accounts Receivable', 'type' => Account::RECEIVABLE],
                ['code' => 1210, 'name' => 'Trade Receivables', 'type' => Account::RECEIVABLE],
                ['code' => 1300, 'name' => 'Inventory', 'type' => Account::INVENTORY],
                ['code' => 1400, 'name' => 'Prepaid Expenses', 'type' => Account::CURRENT_ASSET],
                ['code' => 1500, 'name' => 'VAT Receivable', 'type' => Account::CURRENT_ASSET],

                // Fixed Assets
                ['code' => 1600, 'name' => 'Property, Plant & Equipment', 'type' => Account::NON_CURRENT_ASSET],
                ['code' => 1610, 'name' => 'Furniture & Fixtures', 'type' => Account::NON_CURRENT_ASSET],
                ['code' => 1620, 'name' => 'Computer Equipment', 'type' => Account::NON_CURRENT_ASSET],
                ['code' => 1650, 'name' => 'Accumulated Depreciation', 'type' => Account::CONTRA_ASSET],

                // LIABILITIES (2000-2999)
                // Current Liabilities
                ['code' => 2000, 'name' => 'Accounts Payable', 'type' => Account::PAYABLE],
                ['code' => 2010, 'name' => 'Trade Payables', 'type' => Account::PAYABLE],
                ['code' => 2100, 'name' => 'VAT Payable', 'type' => Account::CONTROL],
                ['code' => 2200, 'name' => 'Accrued Expenses', 'type' => Account::CURRENT_LIABILITY],
                ['code' => 2300, 'name' => 'Salaries Payable', 'type' => Account::CURRENT_LIABILITY],
                ['code' => 2400, 'name' => 'Income Tax Payable', 'type' => Account::CURRENT_LIABILITY],

                // Long-term Liabilities
                ['code' => 2500, 'name' => 'Long-term Debt', 'type' => Account::NON_CURRENT_LIABILITY],
                ['code' => 2600, 'name' => 'Bank Loans', 'type' => Account::NON_CURRENT_LIABILITY],

                // EQUITY (3000-3999)
                ['code' => 3000, 'name' => 'Owner\'s Equity', 'type' => Account::EQUITY],
                ['code' => 3100, 'name' => 'Retained Earnings', 'type' => Account::EQUITY],
                ['code' => 3200, 'name' => 'Current Year Earnings', 'type' => Account::EQUITY],

                // REVENUE (4000-4999)
                // Operating Revenue
                ['code' => 4000, 'name' => 'Sales Revenue', 'type' => Account::OPERATING_REVENUE],
                ['code' => 4100, 'name' => 'Service Revenue', 'type' => Account::OPERATING_REVENUE],
                ['code' => 4200, 'name' => 'Consulting Revenue', 'type' => Account::OPERATING_REVENUE],

                // Non-Operating Revenue
                ['code' => 4500, 'name' => 'Interest Income', 'type' => Account::NON_OPERATING_REVENUE],
                ['code' => 4600, 'name' => 'Other Income', 'type' => Account::NON_OPERATING_REVENUE],

                // EXPENSES (5000-5999)
                // Operating Expenses
                ['code' => 5000, 'name' => 'Cost of Goods Sold', 'type' => Account::OPERATING_EXPENSE],
                ['code' => 5100, 'name' => 'Payment Processing Fees', 'type' => Account::OPERATING_EXPENSE],
                ['code' => 5200, 'name' => 'Salaries Expense', 'type' => Account::OPERATING_EXPENSE],
                ['code' => 5300, 'name' => 'Rent Expense', 'type' => Account::OPERATING_EXPENSE],
                ['code' => 5400, 'name' => 'Utilities Expense', 'type' => Account::OPERATING_EXPENSE],
                ['code' => 5500, 'name' => 'Office Supplies', 'type' => Account::OPERATING_EXPENSE],
                ['code' => 5600, 'name' => 'Marketing Expense', 'type' => Account::OPERATING_EXPENSE],
                ['code' => 5700, 'name' => 'Depreciation Expense', 'type' => Account::OPERATING_EXPENSE],
                ['code' => 5800, 'name' => 'Insurance Expense', 'type' => Account::OPERATING_EXPENSE],
                ['code' => 5900, 'name' => 'Professional Fees', 'type' => Account::OPERATING_EXPENSE],

                // Other Expenses
                ['code' => 8000, 'name' => 'Bank Charges', 'type' => Account::OTHER_EXPENSE],
                ['code' => 8100, 'name' => 'Interest Expense', 'type' => Account::OTHER_EXPENSE],
                ['code' => 8200, 'name' => 'Miscellaneous Expense', 'type' => Account::OTHER_EXPENSE],
            ];

            // Create accounts using raw DB
            foreach ($accounts as $accountData) {
                if (!DB::table('ifrs_accounts')->where('entity_id', $entityId)->where('code', $accountData['code'])->exists()) {
                    DB::table('ifrs_accounts')->insert([
                        'name' => $accountData['name'],
                        'account_type' => $accountData['type'],
                        'code' => $accountData['code'],
                        'entity_id' => $entityId,
                        'currency_id' => $mkdId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            DB::commit();

            $this->command->info('✓ Macedonian Chart of Accounts seeded successfully');
            $this->command->info('  - ' . count($accounts) . ' accounts created');
            $this->command->info('  - Currencies: MKD, EUR');
            $this->command->info('  - Entity: Demo Company');
            $this->command->info('  - Reporting Period: ' . $year);
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Failed to seed Macedonian COA: ' . $e->getMessage());
            throw $e;
        }
    }
}

// CLAUDE-CHECKPOINT
