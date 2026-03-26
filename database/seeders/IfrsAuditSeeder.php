<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Currency;
use App\Models\User;
use Carbon\Carbon;
use IFRS\Models\Account;
use IFRS\Models\Category;
use IFRS\Models\Transaction;
use IFRS\Models\Vat;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * IFRS Accounting Audit Seeder
 *
 * Creates a comprehensive test company with 23 accounts across all IFRS types
 * and 18 journal entries spanning 2024-2025 to exercise every code path in
 * AopReportService, IfrsAdapter, and the UJP tax form services.
 *
 * Edge cases covered:
 * - Contra-asset (1600 depreciation with credit balance)
 * - Code-type collision (600 WIP as INVENTORY, not OPERATING_REVENUE)
 * - Multi-year data (2024 + 2025 for P&L prior/current split)
 * - Compound transactions (sale+VAT, rent+VAT)
 * - Credit-normal sign convention (PAYABLE, CONTROL, EQUITY)
 * - Zero-balance accounts after full payment (AP, Wages)
 * - Type-fallback accounts (4-digit codes not in code-to-AOP map)
 *
 * Usage:
 *   php artisan db:seed --class=IfrsAuditSeeder
 *   $this->seed(IfrsAuditSeeder::class)  // in tests
 */
class IfrsAuditSeeder extends Seeder
{
    /** @var int Set after seeding for test retrieval */
    public static int $companyId = 0;

    /**
     * Pre-calculated expected values for test assertions.
     * All amounts in MKD (integer, no decimals).
     */
    public const EXPECTED = [
        // Balance Sheet totals
        'total_aktiva' => 627000,
        'total_pasiva' => 627000,

        // P&L injection
        'current_year_pnl' => 4000,   // 2025: rev 192K - exp 188K
        'prior_years_pnl' => 105000,  // 2024: rev 200K - exp 95K
        'total_pnl' => 109000,

        // Individual AOP values (BS, current year 2025)
        'aop_013' => 120000,  // Equipment (150K) + Accum Depr (-30K) via type fallback
        'aop_038' => 10000,   // Raw Materials (120K purchased - 80K consumed - 30K to WIP)
        'aop_040' => 30000,   // WIP (code 600, INVENTORY type)
        'aop_047' => 275000,  // AR (200K + 118K - 118K + 75K)
        'aop_049' => 9000,    // VAT Input
        'aop_060' => 183000,  // Bank (500K - 150K - 120K + 118K - 59K + 5K - 3K - 120K + 12K)
        'aop_066' => 500000,  // Share Capital
        'aop_075' => 105000,  // Accumulated profit (prior years)
        'aop_077' => 4000,    // Current year profit
        'aop_097' => 0,       // AP (fully paid: -59K + 59K = 0)
        'aop_101' => 18000,   // VAT Output

        // Income Statement totals (2025 only)
        'is_total_revenue' => 192000,  // 100K + 75K + 12K + 5K
        'is_total_expenses' => 188000, // 50K + 120K + 15K + 3K
        'is_aop_202' => 175000,        // Sales: 720→100K + 731→75K
        'is_aop_203' => 12000,         // Other income: 760→12K
        'is_aop_211' => 50000,         // Rent services: 412→50K
        'is_aop_214' => 120000,        // Salaries: 420→120K
        'is_aop_218' => 15000,         // Depreciation: 430→15K
        'is_aop_223' => 5000,          // Financial income: 770→5K
        'is_aop_224' => 3000,          // Financial expenses: 470→3K
    ];

    public function run(): void
    {
        $this->command?->info('Seeding IFRS Audit test data...');

        // 1. Create MKD currency
        $currency = Currency::firstOrCreate(
            ['code' => 'MKD'],
            [
                'name' => 'Macedonian Denar',
                'symbol' => 'ден.',
                'precision' => 0,
                'thousand_separator' => '.',
                'decimal_separator' => ',',
                'swap_currency_symbol' => true,
            ]
        );

        // 2. Create test user and authenticate
        $user = User::firstOrCreate(
            ['email' => 'ifrs-audit@facturino.mk'],
            [
                'name' => 'IFRS Audit User',
                'password' => bcrypt('password'),
                'role' => 'super admin',
            ]
        );
        Auth::login($user);

        // 3. Create test company
        $company = Company::firstOrCreate(
            ['name' => 'IFRS Audit ДООЕЛ'],
            [
                'owner_id' => $user->id,
                'slug' => 'ifrs-audit-dooel',
                'unique_hash' => Str::random(20),
                'vat_number' => 'MK4030009999999',
            ]
        );
        self::$companyId = $company->id;
        CompanySetting::setSettings(['currency' => $currency->id], $company->id);

        // 4. Create IFRS infrastructure
        $this->createIfrsInfrastructure($company);

        // 5. Enable IFRS
        config(['ifrs.enabled' => true]);
        CompanySetting::setSettings(['ifrs_enabled' => 'YES'], $company->id);

        // 6. Post journal entries
        $this->postJournalEntries($company);

        $this->command?->info("IFRS Audit data seeded: company ID {$company->id}");
    }

    protected function createIfrsInfrastructure(Company $company): void
    {
        // SQLite FK workaround for circular entity↔currency dependency
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
        }

        // Create entity
        $entityId = DB::table('ifrs_entities')->insertGetId([
            'name' => $company->name,
            'currency_id' => null,
            'year_start' => 1,
            'multi_currency' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create IFRS currency
        $ifrsCurrencyId = DB::table('ifrs_currencies')->insertGetId([
            'name' => 'Macedonian Denar',
            'currency_code' => 'MKD',
            'entity_id' => $entityId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Link currency to entity
        DB::table('ifrs_entities')
            ->where('id', $entityId)
            ->update(['currency_id' => $ifrsCurrencyId]);

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON');
        }

        // Link entity to company
        $company->update(['ifrs_entity_id' => $entityId]);

        // Set entity context on authenticated user (required by IFRS EntityScope).
        // Must use withoutGlobalScopes to avoid circular dependency:
        // EntityScope needs user->entity->id, but loading entity triggers EntityScope.
        $user = Auth::user();
        if ($user) {
            $entity = \IFRS\Models\Entity::withoutGlobalScopes()->find($entityId);
            $user->entity_id = $entityId;
            $user->setRelation('entity', $entity);
        }

        // Exchange rates for 2024 and 2025
        foreach ([2024, 2025, 2026] as $year) {
            DB::table('ifrs_exchange_rates')->insertOrIgnore([
                'entity_id' => $entityId,
                'currency_id' => $ifrsCurrencyId,
                'valid_from' => "{$year}-01-01",
                'valid_to' => "{$year}-12-31",
                'rate' => 1.0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Reporting periods for 2024, 2025, 2026
        foreach ([2024, 2025, 2026] as $year) {
            DB::table('ifrs_reporting_periods')->insertOrIgnore([
                'entity_id' => $entityId,
                'calendar_year' => $year,
                'period_count' => 1,
                'status' => 'OPEN',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create categories (one per IFRS account type)
        $categoryTypes = [
            Account::BANK => 'Bank Accounts',
            Account::RECEIVABLE => 'Receivables',
            Account::CURRENT_ASSET => 'Current Assets',
            Account::NON_CURRENT_ASSET => 'Non-Current Assets',
            Account::CONTRA_ASSET => 'Contra Assets',
            Account::INVENTORY => 'Inventory',
            Account::PAYABLE => 'Payables',
            Account::CONTROL => 'Control Accounts',
            Account::CURRENT_LIABILITY => 'Current Liabilities',
            Account::NON_CURRENT_LIABILITY => 'Long-term Liabilities',
            Account::EQUITY => 'Equity',
            Account::OPERATING_REVENUE => 'Operating Revenue',
            Account::NON_OPERATING_REVENUE => 'Other Revenue',
            Account::DIRECT_EXPENSE => 'Direct Expenses',
            Account::OPERATING_EXPENSE => 'Operating Expenses',
            Account::OTHER_EXPENSE => 'Other Expenses',
        ];

        $categories = [];
        foreach ($categoryTypes as $type => $name) {
            $categories[$type] = Category::create([
                'name' => $name,
                'category_type' => $type,
                'entity_id' => $entityId,
            ]);
        }

        // Create chart of accounts (23 accounts)
        $accounts = [
            // Assets
            ['code' => 100, 'name' => 'Каса', 'type' => Account::BANK],
            ['code' => 102, 'name' => 'Жиро-сметка', 'type' => Account::BANK],
            ['code' => 120, 'name' => 'Побарувања од купувачи', 'type' => Account::RECEIVABLE],
            ['code' => 131, 'name' => 'ДДВ - Претходен данок', 'type' => Account::RECEIVABLE],
            ['code' => 310, 'name' => 'Суровини и материјали', 'type' => Account::INVENTORY],
            ['code' => 600, 'name' => 'Недовршено производство', 'type' => Account::INVENTORY],
            ['code' => 1520, 'name' => 'Опрема', 'type' => Account::NON_CURRENT_ASSET],
            ['code' => 1600, 'name' => 'Акумулирана амортизација', 'type' => Account::CONTRA_ASSET],

            // Liabilities
            ['code' => 220, 'name' => 'Обврски кон добавувачи', 'type' => Account::PAYABLE],
            ['code' => 231, 'name' => 'ДДВ - Обврска', 'type' => Account::CONTROL],
            ['code' => 240, 'name' => 'Обврски за плати', 'type' => Account::CURRENT_LIABILITY],
            ['code' => 280, 'name' => 'Долгорочни заеми', 'type' => Account::NON_CURRENT_LIABILITY],

            // Equity
            ['code' => 900, 'name' => 'Основна главнина', 'type' => Account::EQUITY],
            ['code' => 940, 'name' => 'Задржана добивка', 'type' => Account::EQUITY],

            // Revenue
            ['code' => 720, 'name' => 'Приходи од продажба', 'type' => Account::OPERATING_REVENUE],
            ['code' => 731, 'name' => 'Приходи од извоз', 'type' => Account::OPERATING_REVENUE],
            ['code' => 760, 'name' => 'Останати приходи', 'type' => Account::NON_OPERATING_REVENUE],
            ['code' => 770, 'name' => 'Приходи од камати', 'type' => Account::NON_OPERATING_REVENUE],

            // Expenses
            ['code' => 400, 'name' => 'Трошоци за материјали', 'type' => Account::DIRECT_EXPENSE],
            ['code' => 412, 'name' => 'Закупнини', 'type' => Account::OPERATING_EXPENSE],
            ['code' => 420, 'name' => 'Плати нето', 'type' => Account::OPERATING_EXPENSE],
            ['code' => 430, 'name' => 'Амортизација', 'type' => Account::OPERATING_EXPENSE],
            ['code' => 470, 'name' => 'Камати (расход)', 'type' => Account::OTHER_EXPENSE],
        ];

        foreach ($accounts as $acc) {
            Account::create([
                'code' => $acc['code'],
                'name' => $acc['name'],
                'account_type' => $acc['type'],
                'category_id' => $categories[$acc['type']]->id,
                'currency_id' => $ifrsCurrencyId,
                'entity_id' => $entityId,
            ]);
        }

        $this->command?->info('  Created ' . count($accounts) . ' accounts');
    }

    protected function postJournalEntries(Company $company): void
    {
        $entityId = $company->ifrs_entity_id;
        $currencyId = DB::table('ifrs_currencies')
            ->where('entity_id', $entityId)
            ->value('id');

        // Create 0% Exempt VAT (required by ifrs_line_items.vat_id NOT NULL in SQLite)
        $vatId = null;
        if (Schema::hasColumn('ifrs_line_items', 'vat_id')) {
            $vat = Vat::create([
                'name' => 'Exempt',
                'code' => 'EX',
                'rate' => 0,
                'entity_id' => $entityId,
            ]);
            $vatId = $vat->id;
        }

        $entries = [
            // ── 2024: Prior-year transactions ──────────────────────────
            [
                'date' => '2024-03-01',
                'narration' => 'Capital injection',
                'line_items' => [
                    ['account_code' => '100', 'account_name' => 'Bank', 'amount' => 500000, 'credited' => false],
                    ['account_code' => '900', 'account_name' => 'Capital', 'amount' => 500000, 'credited' => true],
                ],
            ],
            [
                'date' => '2024-06-15',
                'narration' => 'Equipment purchase',
                'line_items' => [
                    ['account_code' => '1520', 'account_name' => 'Equipment', 'amount' => 150000, 'credited' => false],
                    ['account_code' => '100', 'account_name' => 'Bank', 'amount' => 150000, 'credited' => true],
                ],
            ],
            [
                'date' => '2024-07-01',
                'narration' => 'Raw materials purchase',
                'line_items' => [
                    ['account_code' => '310', 'account_name' => 'Raw Materials', 'amount' => 120000, 'credited' => false],
                    ['account_code' => '100', 'account_name' => 'Bank', 'amount' => 120000, 'credited' => true],
                ],
            ],
            [
                'date' => '2024-09-01',
                'narration' => 'Sales revenue (2024)',
                'line_items' => [
                    ['account_code' => '120', 'account_name' => 'AR', 'amount' => 200000, 'credited' => false],
                    ['account_code' => '720', 'account_name' => 'Sales', 'amount' => 200000, 'credited' => true],
                ],
            ],
            [
                'date' => '2024-09-01',
                'narration' => 'Materials consumed (2024)',
                'line_items' => [
                    ['account_code' => '400', 'account_name' => 'Materials Expense', 'amount' => 80000, 'credited' => false],
                    ['account_code' => '310', 'account_name' => 'Raw Materials', 'amount' => 80000, 'credited' => true],
                ],
            ],
            [
                'date' => '2024-12-15',
                'narration' => 'Depreciation (2024)',
                'line_items' => [
                    ['account_code' => '430', 'account_name' => 'Depreciation Exp', 'amount' => 15000, 'credited' => false],
                    ['account_code' => '1600', 'account_name' => 'Accum Depr', 'amount' => 15000, 'credited' => true],
                ],
            ],

            // ── 2025: Current-year transactions ────────────────────────
            [
                'date' => '2025-01-15',
                'narration' => 'Sale with 18% VAT',
                'line_items' => [
                    ['account_code' => '120', 'account_name' => 'AR', 'amount' => 118000, 'credited' => false],
                    ['account_code' => '720', 'account_name' => 'Sales', 'amount' => 100000, 'credited' => true],
                    ['account_code' => '231', 'account_name' => 'VAT Output', 'amount' => 18000, 'credited' => true],
                ],
            ],
            [
                'date' => '2025-01-20',
                'narration' => 'Rent expense with VAT',
                'line_items' => [
                    ['account_code' => '412', 'account_name' => 'Rent', 'amount' => 50000, 'credited' => false],
                    ['account_code' => '131', 'account_name' => 'VAT Input', 'amount' => 9000, 'credited' => false],
                    ['account_code' => '220', 'account_name' => 'AP', 'amount' => 59000, 'credited' => true],
                ],
            ],
            [
                'date' => '2025-02-01',
                'narration' => 'Customer payment received',
                'line_items' => [
                    ['account_code' => '100', 'account_name' => 'Bank', 'amount' => 118000, 'credited' => false],
                    ['account_code' => '120', 'account_name' => 'AR', 'amount' => 118000, 'credited' => true],
                ],
            ],
            [
                'date' => '2025-02-15',
                'narration' => 'Supplier payment',
                'line_items' => [
                    ['account_code' => '220', 'account_name' => 'AP', 'amount' => 59000, 'credited' => false],
                    ['account_code' => '100', 'account_name' => 'Bank', 'amount' => 59000, 'credited' => true],
                ],
            ],
            [
                'date' => '2025-03-01',
                'narration' => 'Interest income',
                'line_items' => [
                    ['account_code' => '100', 'account_name' => 'Bank', 'amount' => 5000, 'credited' => false],
                    ['account_code' => '770', 'account_name' => 'Interest Income', 'amount' => 5000, 'credited' => true],
                ],
            ],
            [
                'date' => '2025-03-15',
                'narration' => 'Interest expense',
                'line_items' => [
                    ['account_code' => '470', 'account_name' => 'Interest Exp', 'amount' => 3000, 'credited' => false],
                    ['account_code' => '100', 'account_name' => 'Bank', 'amount' => 3000, 'credited' => true],
                ],
            ],
            [
                'date' => '2025-04-01',
                'narration' => 'WIP inventory build-up',
                'line_items' => [
                    ['account_code' => '600', 'account_name' => 'WIP', 'amount' => 30000, 'credited' => false],
                    ['account_code' => '310', 'account_name' => 'Raw Materials', 'amount' => 30000, 'credited' => true],
                ],
            ],
            [
                'date' => '2025-06-01',
                'narration' => 'Salary accrual',
                'line_items' => [
                    ['account_code' => '420', 'account_name' => 'Salaries', 'amount' => 120000, 'credited' => false],
                    ['account_code' => '240', 'account_name' => 'Wages Payable', 'amount' => 120000, 'credited' => true],
                ],
            ],
            [
                'date' => '2025-06-01',
                'narration' => 'Salary payment',
                'line_items' => [
                    ['account_code' => '240', 'account_name' => 'Wages Payable', 'amount' => 120000, 'credited' => false],
                    ['account_code' => '100', 'account_name' => 'Bank', 'amount' => 120000, 'credited' => true],
                ],
            ],
            [
                'date' => '2025-09-01',
                'narration' => 'Export services',
                'line_items' => [
                    ['account_code' => '120', 'account_name' => 'AR', 'amount' => 75000, 'credited' => false],
                    ['account_code' => '731', 'account_name' => 'Export Services', 'amount' => 75000, 'credited' => true],
                ],
            ],
            [
                'date' => '2025-11-01',
                'narration' => 'Other income (asset sale)',
                'line_items' => [
                    ['account_code' => '100', 'account_name' => 'Bank', 'amount' => 12000, 'credited' => false],
                    ['account_code' => '760', 'account_name' => 'Other Income', 'amount' => 12000, 'credited' => true],
                ],
            ],
            [
                'date' => '2025-12-15',
                'narration' => 'Depreciation (2025)',
                'line_items' => [
                    ['account_code' => '430', 'account_name' => 'Depreciation Exp', 'amount' => 15000, 'credited' => false],
                    ['account_code' => '1600', 'account_name' => 'Accum Depr', 'amount' => 15000, 'credited' => true],
                ],
            ],
        ];

        $posted = 0;

        // Build account lookup: code → Account model
        $accountLookup = [];
        foreach (Account::withoutGlobalScopes()->where('entity_id', $entityId)->get() as $acct) {
            $accountLookup[(string) $acct->code] = $acct;
        }

        foreach ($entries as $i => $entry) {
            // Resolve accounts for this entry
            $firstAccountId = null;
            $resolvedItems = [];
            foreach ($entry['line_items'] as $item) {
                $code = $item['account_code'];
                if (! isset($accountLookup[$code])) {
                    throw new \RuntimeException("Account code {$code} not found for entry #{$i}");
                }
                $account = $accountLookup[$code];
                if (! $firstAccountId) {
                    $firstAccountId = $account->id;
                }
                $resolvedItems[] = [
                    'account_id' => $account->id,
                    'amount' => $item['amount'],
                    'credited' => $item['credited'],
                ];
            }

            // Create transaction (compound mode, main_account_amount=0)
            $transaction = Transaction::create([
                'account_id' => $firstAccountId,
                'transaction_date' => Carbon::parse($entry['date']),
                'narration' => $entry['narration'],
                'transaction_type' => Transaction::JN,
                'currency_id' => $currencyId,
                'entity_id' => $entityId,
                'compound' => true,
                'main_account_amount' => 0,
                'credited' => false,
            ]);

            // Insert line items with vat_id if column exists
            foreach ($resolvedItems as $item) {
                $insertData = [
                    'transaction_id' => $transaction->id,
                    'account_id' => $item['account_id'],
                    'amount' => $item['amount'],
                    'quantity' => 1,
                    'credited' => $item['credited'],
                    'entity_id' => $entityId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                if ($vatId !== null) {
                    $insertData['vat_id'] = $vatId;
                }
                DB::table('ifrs_line_items')->insert($insertData);
            }

            // Post the transaction to the ledger
            $transaction->load('lineItems');
            $transaction->post();
            $posted++;
        }

        $this->command?->info("  Posted {$posted}/" . count($entries) . ' journal entries');
    }
}

// CLAUDE-CHECKPOINT
