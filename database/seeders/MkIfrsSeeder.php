<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use IFRS\Models\Account;
use IFRS\Models\Currency;
use IFRS\Models\Entity;
use IFRS\Models\Category;
use Illuminate\Support\Facades\Log;

/**
 * Macedonian Chart of Accounts Seeder
 *
 * Seeds a standard Macedonian chart of accounts following IFRS standards:
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
    public function run(): void
    {
        $this->command->info('Seeding Macedonian Chart of Accounts...');

        // Get all companies and create an IFRS Entity for each one
        $companies = \App\Models\Company::all();

        if ($companies->isEmpty()) {
            $this->command->warn('No companies found. Skipping chart of accounts seeding.');
            return;
        }

        // Create or get MKD currency (need an entity_id, so use first company's entity)
        // Create a temporary entity for currency if needed
        $firstEntity = Entity::first();
        if (!$firstEntity) {
            $firstCompany = $companies->first();
            $firstEntity = Entity::create([
                'name' => $firstCompany->name . ' (System)',
                'year_start' => 1,
                'multi_currency' => false,
            ]);
        }

        // Create or get MKD currency with entity_id
        $mkd = Currency::where('currency_code', 'MKD')->first();
        if (!$mkd) {
            $mkd = new Currency();
            $mkd->currency_code = 'MKD';
            $mkd->name = 'Macedonian Denar';
            $mkd->entity_id = $firstEntity->id;
            $mkd->save();
        }

        foreach ($companies as $company) {
            $this->command->info("Seeding chart of accounts for company: {$company->name}");

            // Create or get IFRS Entity for this company
            if ($company->ifrs_entity_id) {
                $entity = Entity::find($company->ifrs_entity_id);
            } else {
                $entity = Entity::create([
                    'name' => $company->name,
                    'currency_id' => $mkd->id,
                    'year_start' => 1, // January
                    'multi_currency' => false,
                ]);

                // Link entity to company
                $company->update(['ifrs_entity_id' => $entity->id]);
            }

            // Create account categories for this entity
            $categories = $this->createCategories($entity->id);

            // Seed chart of accounts for this entity
            $this->seedAssetAccounts($mkd->id, $categories, $entity->id);
            $this->seedLiabilityAccounts($mkd->id, $categories, $entity->id);
            $this->seedEquityAccounts($mkd->id, $categories, $entity->id);
            $this->seedRevenueAccounts($mkd->id, $categories, $entity->id);
            $this->seedExpenseAccounts($mkd->id, $categories, $entity->id);
        }

        $this->command->info('Macedonian Chart of Accounts seeded successfully!');
    }

    /**
     * Create account categories for an entity
     *
     * @param int $entityId
     * @return array
     */
    protected function createCategories(int $entityId): array
    {
        $categories = [
            'current_assets' => Category::firstOrCreate(
                [
                    'name' => 'Current Assets',
                    'entity_id' => $entityId,
                ],
                ['category_type' => Account::CURRENT_ASSET]
            ),
            'fixed_assets' => Category::firstOrCreate(
                [
                    'name' => 'Fixed Assets',
                    'entity_id' => $entityId,
                ],
                ['category_type' => Account::NON_CURRENT_ASSET]
            ),
            'contra_assets' => Category::firstOrCreate(
                [
                    'name' => 'Contra Assets',
                    'entity_id' => $entityId,
                ],
                ['category_type' => Account::CONTRA_ASSET]
            ),
            'current_liabilities' => Category::firstOrCreate(
                [
                    'name' => 'Current Liabilities',
                    'entity_id' => $entityId,
                ],
                ['category_type' => Account::CURRENT_LIABILITY]
            ),
            'long_term_liabilities' => Category::firstOrCreate(
                [
                    'name' => 'Long-term Liabilities',
                    'entity_id' => $entityId,
                ],
                ['category_type' => Account::NON_CURRENT_LIABILITY]
            ),
            'equity' => Category::firstOrCreate(
                [
                    'name' => 'Equity',
                    'entity_id' => $entityId,
                ],
                ['category_type' => Account::EQUITY]
            ),
            'operating_revenue' => Category::firstOrCreate(
                [
                    'name' => 'Operating Revenue',
                    'entity_id' => $entityId,
                ],
                ['category_type' => Account::OPERATING_REVENUE]
            ),
            'other_revenue' => Category::firstOrCreate(
                [
                    'name' => 'Other Revenue',
                    'entity_id' => $entityId,
                ],
                ['category_type' => Account::NON_OPERATING_REVENUE]
            ),
            'direct_expenses' => Category::firstOrCreate(
                [
                    'name' => 'Direct Expenses',
                    'entity_id' => $entityId,
                ],
                ['category_type' => Account::DIRECT_EXPENSE]
            ),
            'operating_expenses' => Category::firstOrCreate(
                [
                    'name' => 'Operating Expenses',
                    'entity_id' => $entityId,
                ],
                ['category_type' => Account::OPERATING_EXPENSE]
            ),
            'other_expenses' => Category::firstOrCreate(
                [
                    'name' => 'Other Expenses',
                    'entity_id' => $entityId,
                ],
                ['category_type' => Account::OTHER_EXPENSE]
            ),
        ];

        return $categories;
    }

    /**
     * Seed asset accounts (1000-1999)
     *
     * @param int $currencyId
     * @param array $categories
     * @param int $entityId
     * @return void
     */
    protected function seedAssetAccounts(int $currencyId, array $categories, int $entityId): void
    {
        $accounts = [
            // Current Assets (1000-1299) - Use CURRENT_ASSET type to match category
            ['code' => '1000', 'name' => 'Касаи банки (Cash and Bank)', 'type' => Account::CURRENT_ASSET, 'category' => 'current_assets'],
            ['code' => '1010', 'name' => 'Каса (Cash on Hand)', 'type' => Account::CURRENT_ASSET, 'category' => 'current_assets'],
            ['code' => '1020', 'name' => 'Жиро сметка (Current Account)', 'type' => Account::CURRENT_ASSET, 'category' => 'current_assets'],
            ['code' => '1030', 'name' => 'Девизна сметка (Foreign Currency Account)', 'type' => Account::CURRENT_ASSET, 'category' => 'current_assets'],

            // Receivables (1200-1299) - Use CURRENT_ASSET type to match category
            ['code' => '1200', 'name' => 'Побарувања од купувачи (Accounts Receivable)', 'type' => Account::CURRENT_ASSET, 'category' => 'current_assets'],
            ['code' => '1210', 'name' => 'Побарувања - домашни (Domestic Receivables)', 'type' => Account::CURRENT_ASSET, 'category' => 'current_assets'],
            ['code' => '1220', 'name' => 'Побарувања - странски (Foreign Receivables)', 'type' => Account::CURRENT_ASSET, 'category' => 'current_assets'],
            ['code' => '1290', 'name' => 'Сомнителни побарувања (Doubtful Receivables)', 'type' => Account::CURRENT_ASSET, 'category' => 'current_assets'],

            // Inventory (1300-1399) - Use CURRENT_ASSET type to match category
            ['code' => '1300', 'name' => 'Залихи (Inventory)', 'type' => Account::CURRENT_ASSET, 'category' => 'current_assets'],
            ['code' => '1310', 'name' => 'Суровини (Raw Materials)', 'type' => Account::CURRENT_ASSET, 'category' => 'current_assets'],
            ['code' => '1320', 'name' => 'Готови производи (Finished Goods)', 'type' => Account::CURRENT_ASSET, 'category' => 'current_assets'],
            ['code' => '1330', 'name' => 'Стока (Merchandise)', 'type' => Account::CURRENT_ASSET, 'category' => 'current_assets'],

            // Other Current Assets (1400-1499)
            ['code' => '1400', 'name' => 'Аванси дадени (Prepaid Expenses)', 'type' => Account::CURRENT_ASSET, 'category' => 'current_assets'],
            ['code' => '1410', 'name' => 'ДДВ за поврат (VAT Receivable)', 'type' => Account::CURRENT_ASSET, 'category' => 'current_assets'],

            // Fixed Assets (1500-1999)
            ['code' => '1500', 'name' => 'Недвижен имот (Real Estate)', 'type' => Account::NON_CURRENT_ASSET, 'category' => 'fixed_assets'],
            ['code' => '1510', 'name' => 'Згради (Buildings)', 'type' => Account::NON_CURRENT_ASSET, 'category' => 'fixed_assets'],
            ['code' => '1520', 'name' => 'Опрема (Equipment)', 'type' => Account::NON_CURRENT_ASSET, 'category' => 'fixed_assets'],
            ['code' => '1530', 'name' => 'Возила (Vehicles)', 'type' => Account::NON_CURRENT_ASSET, 'category' => 'fixed_assets'],
            ['code' => '1540', 'name' => 'Компјутери и софтвер (Computers and Software)', 'type' => Account::NON_CURRENT_ASSET, 'category' => 'fixed_assets'],

            // Contra Assets
            ['code' => '1600', 'name' => 'Акумулирана амортизација (Accumulated Depreciation)', 'type' => Account::CONTRA_ASSET, 'category' => 'contra_assets'],
        ];

        foreach ($accounts as $account) {
            Account::firstOrCreate(
                [
                    'code' => $account['code'],
                    'entity_id' => $entityId,
                ],
                [
                    'name' => $account['name'],
                    'account_type' => $account['type'],
                    'category_id' => $categories[$account['category']]->id ?? null,
                    'currency_id' => $currencyId,
                    'entity_id' => $entityId,
                ]
            );
        }

        $this->command->info('Asset accounts seeded (1000-1999)');
    }

    /**
     * Seed liability accounts (2000-2999)
     *
     * @param int $currencyId
     * @param array $categories
     * @param int $entityId
     * @return void
     */
    protected function seedLiabilityAccounts(int $currencyId, array $categories, int $entityId): void
    {
        $accounts = [
            // Current Liabilities (2000-2499) - Use CURRENT_LIABILITY type to match category
            ['code' => '2000', 'name' => 'Обврски кон добавувачи (Accounts Payable)', 'type' => Account::CURRENT_LIABILITY, 'category' => 'current_liabilities'],
            ['code' => '2010', 'name' => 'Обврски - домашни (Domestic Payables)', 'type' => Account::CURRENT_LIABILITY, 'category' => 'current_liabilities'],
            ['code' => '2020', 'name' => 'Обврски - странски (Foreign Payables)', 'type' => Account::CURRENT_LIABILITY, 'category' => 'current_liabilities'],

            // Tax Liabilities (2100-2199) - Use CURRENT_LIABILITY type to match category
            ['code' => '2100', 'name' => 'ДДВ за уплата (VAT Payable)', 'type' => Account::CURRENT_LIABILITY, 'category' => 'current_liabilities'],
            ['code' => '2110', 'name' => 'Данок на добивка (Corporate Tax Payable)', 'type' => Account::CURRENT_LIABILITY, 'category' => 'current_liabilities'],
            ['code' => '2120', 'name' => 'Персонален данок (Personal Income Tax Payable)', 'type' => Account::CURRENT_LIABILITY, 'category' => 'current_liabilities'],

            // Payroll Liabilities (2200-2299)
            ['code' => '2200', 'name' => 'Плати за исплата (Wages Payable)', 'type' => Account::CURRENT_LIABILITY, 'category' => 'current_liabilities'],
            ['code' => '2210', 'name' => 'Придонеси за исплата (Social Security Payable)', 'type' => Account::CURRENT_LIABILITY, 'category' => 'current_liabilities'],

            // Short-term Debt (2300-2499)
            ['code' => '2300', 'name' => 'Краткорочни кредити (Short-term Loans)', 'type' => Account::CURRENT_LIABILITY, 'category' => 'current_liabilities'],
            ['code' => '2400', 'name' => 'Аванси примени (Deferred Revenue)', 'type' => Account::CURRENT_LIABILITY, 'category' => 'current_liabilities'],

            // Long-term Liabilities (2500-2999)
            ['code' => '2500', 'name' => 'Долгорочни кредити (Long-term Loans)', 'type' => Account::NON_CURRENT_LIABILITY, 'category' => 'long_term_liabilities'],
            ['code' => '2600', 'name' => 'Хипотеки (Mortgages)', 'type' => Account::NON_CURRENT_LIABILITY, 'category' => 'long_term_liabilities'],
        ];

        foreach ($accounts as $account) {
            Account::firstOrCreate(
                [
                    'code' => $account['code'],
                    'entity_id' => $entityId,
                ],
                [
                    'name' => $account['name'],
                    'account_type' => $account['type'],
                    'category_id' => $categories[$account['category']]->id ?? null,
                    'currency_id' => $currencyId,
                    'entity_id' => $entityId,
                ]
            );
        }

        $this->command->info('Liability accounts seeded (2000-2999)');
    }

    /**
     * Seed equity accounts (3000-3999)
     *
     * @param int $currencyId
     * @param array $categories
     * @param int $entityId
     * @return void
     */
    protected function seedEquityAccounts(int $currencyId, array $categories, int $entityId): void
    {
        $accounts = [
            ['code' => '3000', 'name' => 'Капитал (Share Capital)', 'type' => Account::EQUITY, 'category' => 'equity'],
            ['code' => '3100', 'name' => 'Задржана добивка (Retained Earnings)', 'type' => Account::EQUITY, 'category' => 'equity'],
            ['code' => '3200', 'name' => 'Тековна добивка (Current Year Earnings)', 'type' => Account::EQUITY, 'category' => 'equity'],
            ['code' => '3300', 'name' => 'Приватни повлекувања (Owner Drawings)', 'type' => Account::EQUITY, 'category' => 'equity'],
        ];

        foreach ($accounts as $account) {
            Account::firstOrCreate(
                [
                    'code' => $account['code'],
                    'entity_id' => $entityId,
                ],
                [
                    'name' => $account['name'],
                    'account_type' => $account['type'],
                    'category_id' => $categories[$account['category']]->id ?? null,
                    'currency_id' => $currencyId,
                    'entity_id' => $entityId,
                ]
            );
        }

        $this->command->info('Equity accounts seeded (3000-3999)');
    }

    /**
     * Seed revenue accounts (4000-4999)
     *
     * @param int $currencyId
     * @param array $categories
     * @param int $entityId
     * @return void
     */
    protected function seedRevenueAccounts(int $currencyId, array $categories, int $entityId): void
    {
        $accounts = [
            // Operating Revenue (4000-4499)
            ['code' => '4000', 'name' => 'Приходи од продажба (Sales Revenue)', 'type' => Account::OPERATING_REVENUE, 'category' => 'operating_revenue'],
            ['code' => '4010', 'name' => 'Приходи од услуги (Service Revenue)', 'type' => Account::OPERATING_REVENUE, 'category' => 'operating_revenue'],
            ['code' => '4020', 'name' => 'Приходи од консалтинг (Consulting Revenue)', 'type' => Account::OPERATING_REVENUE, 'category' => 'operating_revenue'],
            ['code' => '4100', 'name' => 'Попусти одобрени (Sales Discounts)', 'type' => Account::OPERATING_REVENUE, 'category' => 'operating_revenue'],

            // Other Revenue (4500-4999)
            ['code' => '4500', 'name' => 'Приходи од камата (Interest Income)', 'type' => Account::NON_OPERATING_REVENUE, 'category' => 'other_revenue'],
            ['code' => '4600', 'name' => 'Приходи од течајни разлики (Foreign Exchange Gains)', 'type' => Account::NON_OPERATING_REVENUE, 'category' => 'other_revenue'],
            ['code' => '4700', 'name' => 'Останати приходи (Other Income)', 'type' => Account::NON_OPERATING_REVENUE, 'category' => 'other_revenue'],
        ];

        foreach ($accounts as $account) {
            Account::firstOrCreate(
                [
                    'code' => $account['code'],
                    'entity_id' => $entityId,
                ],
                [
                    'name' => $account['name'],
                    'account_type' => $account['type'],
                    'category_id' => $categories[$account['category']]->id ?? null,
                    'currency_id' => $currencyId,
                    'entity_id' => $entityId,
                ]
            );
        }

        $this->command->info('Revenue accounts seeded (4000-4999)');
    }

    /**
     * Seed expense accounts (5000-5999)
     *
     * @param int $currencyId
     * @param array $categories
     * @param int $entityId
     * @return void
     */
    protected function seedExpenseAccounts(int $currencyId, array $categories, int $entityId): void
    {
        $accounts = [
            // Cost of Sales (5000-5099) - Direct Expenses
            ['code' => '5000', 'name' => 'Трошоци на продадена стока (Cost of Goods Sold)', 'type' => Account::DIRECT_EXPENSE, 'category' => 'direct_expenses'],

            // Operating Expenses (5100-5499)
            ['code' => '5100', 'name' => 'Провизии за плаќање (Payment Processing Fees)', 'type' => Account::OPERATING_EXPENSE, 'category' => 'operating_expenses'],
            ['code' => '5110', 'name' => 'Банкарски провизии (Bank Fees)', 'type' => Account::OPERATING_EXPENSE, 'category' => 'operating_expenses'],
            ['code' => '5200', 'name' => 'Плати и надоместоци (Salaries and Wages)', 'type' => Account::OPERATING_EXPENSE, 'category' => 'operating_expenses'],
            ['code' => '5210', 'name' => 'Придонеси (Social Security Contributions)', 'type' => Account::OPERATING_EXPENSE, 'category' => 'operating_expenses'],
            ['code' => '5300', 'name' => 'Кирија (Rent)', 'type' => Account::OPERATING_EXPENSE, 'category' => 'operating_expenses'],
            ['code' => '5310', 'name' => 'Комунални услуги (Utilities)', 'type' => Account::OPERATING_EXPENSE, 'category' => 'operating_expenses'],
            ['code' => '5320', 'name' => 'Телефон и интернет (Telephone and Internet)', 'type' => Account::OPERATING_EXPENSE, 'category' => 'operating_expenses'],
            ['code' => '5400', 'name' => 'Маркетинг и реклама (Marketing and Advertising)', 'type' => Account::OPERATING_EXPENSE, 'category' => 'operating_expenses'],
            ['code' => '5410', 'name' => 'Канцелариски материјал (Office Supplies)', 'type' => Account::OPERATING_EXPENSE, 'category' => 'operating_expenses'],
            ['code' => '5420', 'name' => 'Амортизација (Depreciation)', 'type' => Account::OPERATING_EXPENSE, 'category' => 'operating_expenses'],

            // Other Expenses (5500-5999)
            ['code' => '5500', 'name' => 'Камати (Interest Expense)', 'type' => Account::OTHER_EXPENSE, 'category' => 'other_expenses'],
            ['code' => '5600', 'name' => 'Загуби од течајни разлики (Foreign Exchange Losses)', 'type' => Account::OTHER_EXPENSE, 'category' => 'other_expenses'],
            ['code' => '5700', 'name' => 'Останати трошоци (Other Expenses)', 'type' => Account::OTHER_EXPENSE, 'category' => 'other_expenses'],
        ];

        foreach ($accounts as $account) {
            Account::firstOrCreate(
                [
                    'code' => $account['code'],
                    'entity_id' => $entityId,
                ],
                [
                    'name' => $account['name'],
                    'account_type' => $account['type'],
                    'category_id' => $categories[$account['category']]->id ?? null,
                    'currency_id' => $currencyId,
                    'entity_id' => $entityId,
                ]
            );
        }

        $this->command->info('Expense accounts seeded (5000-5999)');
    }
}

// CLAUDE-CHECKPOINT
