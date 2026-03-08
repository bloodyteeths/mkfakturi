<?php

namespace Database\Seeders;

use App\Domain\Accounting\IfrsAdapter;
use App\Models\Address;
use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Tax;
use App\Models\TaxType;
use App\Models\User;
use IFRS\Models\Account;
use IFRS\Models\Category;
use IFRS\Models\Entity;
use IFRS\Models\ExchangeRate;
use IFRS\Models\ReportingPeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * UJP Tax Forms Test Data Seeder
 *
 * Creates a complete test company with IFRS entity, chart of accounts,
 * customers, suppliers, invoices, bills, and payments — all posted to IFRS.
 *
 * This provides real data for testing all 4 UJP tax forms:
 * - ДДВ-04 (VAT return) — needs invoices and bills with VAT
 * - ДБ (Corporate tax) — needs income statement (revenue - expenses)
 * - Образец 36 (Balance sheet) — needs assets, liabilities, equity
 * - Образец 37 (Income statement) — needs revenue and expense accounts
 *
 * Usage: php artisan db:seed --class=UjpFormsTestSeeder
 */
class UjpFormsTestSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding UJP Forms test data...');

        // 1. Create or find MKD currency
        $currency = $this->createCurrency();

        // 2. Create test user
        $user = $this->createUser();

        // 3. Create test company
        $company = $this->createCompany($user, $currency);

        // 4. Create IFRS entity + chart of accounts
        $entity = $this->createIfrsEntity($company, $currency);

        // 5. Create tax types (18% and 5%)
        $taxes = $this->createTaxTypes($company);

        // 6. Create customers
        $customers = $this->createCustomers($company, $currency);

        // 7. Create payment method
        $paymentMethod = $this->createPaymentMethod($company);

        // 8. Create invoices with IFRS posting
        $this->createInvoicesWithPosting($company, $entity, $customers, $taxes, $currency);

        // 9. Create bills with IFRS posting
        $this->createBillsWithPosting($company, $entity, $taxes, $currency);

        // 10. Enable IFRS for the company
        CompanySetting::setSettings(['ifrs_enabled' => 'YES'], $company->id);

        $this->command->info("UJP test data seeded for company: {$company->name} (ID: {$company->id})");
        $this->command->info("Test user: ujp-test@facturino.mk / password");
    }

    protected function createCurrency(): Currency
    {
        return Currency::firstOrCreate(
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
    }

    protected function createUser(): User
    {
        return User::firstOrCreate(
            ['email' => 'ujp-test@facturino.mk'],
            [
                'name' => 'УЈП Тест Корисник',
                'password' => bcrypt('password'),
                'role' => 'super admin',
            ]
        );
    }

    protected function createCompany(User $user, Currency $currency): Company
    {
        $company = Company::firstOrCreate(
            ['name' => 'УЈП Тест ДООЕЛ Скопје'],
            [
                'owner_id' => $user->id,
                'slug' => 'ujp-test-dooel',
                'unique_hash' => \Illuminate\Support\Str::random(20),
                'vat_number' => 'MK4030009123456',
                'tax_id' => '4030009123456',
            ]
        );

        // Create company address
        Address::firstOrCreate(
            ['company_id' => $company->id, 'type' => 'company'],
            [
                'name' => 'УЈП Тест ДООЕЛ',
                'address_street_1' => 'ул. Македонија бр. 15',
                'city' => 'Скопје',
                'state' => 'Скопје',
                'zip' => '1000',
                'country_id' => 129, // Macedonia
                'phone' => '+38970123456',
            ]
        );

        // Set currency
        CompanySetting::setSettings(['currency' => $currency->id], $company->id);

        return $company;
    }

    protected function createIfrsEntity(Company $company, Currency $currency): Entity
    {
        // Check if entity already exists
        if ($company->ifrs_entity_id) {
            $existing = Entity::find($company->ifrs_entity_id);
            if ($existing) {
                $this->command->info('IFRS entity already exists, skipping creation.');
                return $existing;
            }
        }

        // Create IFRS currency
        $ifrsCurrency = DB::table('ifrs_currencies')->where('currency_code', 'MKD')->first();
        if (!$ifrsCurrency) {
            $ifrsCurrencyId = DB::table('ifrs_currencies')->insertGetId([
                'name' => 'Macedonian Denar',
                'currency_code' => 'MKD',
                'entity_id' => 0, // Will update after entity creation
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $ifrsCurrencyId = $ifrsCurrency->id;
        }

        // Create entity
        $entityId = DB::table('ifrs_entities')->insertGetId([
            'name' => $company->name,
            'currency_id' => $ifrsCurrencyId,
            'year_start' => 1,
            'multi_currency' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Update currency entity_id if needed
        if (!$ifrsCurrency) {
            DB::table('ifrs_currencies')->where('id', $ifrsCurrencyId)->update(['entity_id' => $entityId]);
        }

        // Link entity to company
        $company->update(['ifrs_entity_id' => $entityId]);

        // Create exchange rate
        DB::table('ifrs_exchange_rates')->insertOrIgnore([
            'entity_id' => $entityId,
            'currency_id' => $ifrsCurrencyId,
            'valid_from' => '2025-01-01',
            'valid_to' => '2025-12-31',
            'rate' => 1.0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create reporting period for 2025
        DB::table('ifrs_reporting_periods')->insertOrIgnore([
            'entity_id' => $entityId,
            'calendar_year' => 2025,
            'period_count' => 1,
            'status' => 'OPEN',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create account categories
        $categories = $this->createAccountCategories($entityId);

        // Create chart of accounts
        $this->createChartOfAccounts($entityId, $ifrsCurrencyId, $categories);

        // Create IFRS VAT rates (required for line items)
        $vatAccount = DB::table('ifrs_accounts')
            ->where('entity_id', $entityId)
            ->where('account_type', Account::CURRENT_LIABILITY)
            ->first();
        $vatAccountId = $vatAccount ? $vatAccount->id : null;

        DB::table('ifrs_vats')->insertOrIgnore([
            ['entity_id' => $entityId, 'account_id' => $vatAccountId, 'code' => 'EXEMPT', 'name' => 'Tax Exempt (0%)', 'rate' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['entity_id' => $entityId, 'account_id' => $vatAccountId, 'code' => 'STD18', 'name' => 'ДДВ 18%', 'rate' => 18, 'created_at' => now(), 'updated_at' => now()],
            ['entity_id' => $entityId, 'account_id' => $vatAccountId, 'code' => 'RED5', 'name' => 'ДДВ 5%', 'rate' => 5, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $entity = Entity::find($entityId);
        $this->command->info("IFRS entity created: ID {$entityId}");

        return $entity;
    }

    protected function createAccountCategories(int $entityId): array
    {
        // Each category must have category_type matching the account_type of its accounts
        $defs = [
            Account::CURRENT_ASSET => 'Current Assets',
            Account::NON_CURRENT_ASSET => 'Non-Current Assets',
            Account::CONTRA_ASSET => 'Contra Assets',
            Account::BANK => 'Bank Accounts',
            Account::RECEIVABLE => 'Receivables',
            Account::INVENTORY => 'Inventory',
            Account::CURRENT_LIABILITY => 'Current Liabilities',
            Account::NON_CURRENT_LIABILITY => 'Long-term Liabilities',
            Account::PAYABLE => 'Payables',
            Account::EQUITY => 'Equity',
            Account::OPERATING_REVENUE => 'Operating Revenue',
            Account::NON_OPERATING_REVENUE => 'Other Revenue',
            Account::DIRECT_EXPENSE => 'Direct Expenses',
            Account::OPERATING_EXPENSE => 'Operating Expenses',
            Account::OTHER_EXPENSE => 'Other Expenses',
        ];

        $categories = [];
        foreach ($defs as $accountType => $name) {
            $cat = Category::firstOrCreate(
                ['name' => $name, 'entity_id' => $entityId],
                ['category_type' => $accountType]
            );
            $categories[$accountType] = $cat;
        }

        return $categories;
    }

    protected function createChartOfAccounts(int $entityId, int $currencyId, array $categories): void
    {
        $accounts = [
            // Assets (category key = account type for IFRS category_type match)
            ['code' => 1000, 'name' => 'Каса (Готовина)', 'type' => Account::BANK],
            ['code' => 1010, 'name' => 'Жиро сметка', 'type' => Account::BANK],
            ['code' => 1200, 'name' => 'Побарувања од купувачи', 'type' => Account::RECEIVABLE],
            ['code' => 1300, 'name' => 'Залихи', 'type' => Account::INVENTORY],
            ['code' => 1400, 'name' => 'Однапред платени трошоци', 'type' => Account::CURRENT_ASSET],
            ['code' => 1410, 'name' => 'ДДВ - Претходен данок', 'type' => Account::CURRENT_ASSET],
            ['code' => 1500, 'name' => 'Основни средства', 'type' => Account::NON_CURRENT_ASSET],
            ['code' => 1600, 'name' => 'Амортизација', 'type' => Account::CONTRA_ASSET],

            // Liabilities
            ['code' => 2000, 'name' => 'Обврски кон добавувачи', 'type' => Account::PAYABLE],
            ['code' => 2100, 'name' => 'ДДВ - Обврска за данок', 'type' => Account::CURRENT_LIABILITY],
            ['code' => 2110, 'name' => 'Данок на добивка', 'type' => Account::CURRENT_LIABILITY],
            ['code' => 2200, 'name' => 'Обврски за плати', 'type' => Account::CURRENT_LIABILITY],
            ['code' => 2500, 'name' => 'Долгорочни заеми', 'type' => Account::NON_CURRENT_LIABILITY],

            // Equity
            ['code' => 3000, 'name' => 'Основна главнина', 'type' => Account::EQUITY],
            ['code' => 3100, 'name' => 'Задржана добивка', 'type' => Account::EQUITY],

            // Revenue
            ['code' => 4000, 'name' => 'Приходи од продажба', 'type' => Account::OPERATING_REVENUE],
            ['code' => 4010, 'name' => 'Приходи од услуги', 'type' => Account::OPERATING_REVENUE],
            ['code' => 4500, 'name' => 'Финансиски приходи', 'type' => Account::NON_OPERATING_REVENUE],

            // Expenses
            ['code' => 5000, 'name' => 'Набавна вредност', 'type' => Account::DIRECT_EXPENSE],
            ['code' => 5200, 'name' => 'Трошоци за плати', 'type' => Account::OPERATING_EXPENSE],
            ['code' => 5300, 'name' => 'Наем', 'type' => Account::OPERATING_EXPENSE],
            ['code' => 5400, 'name' => 'Маркетинг', 'type' => Account::OPERATING_EXPENSE],
            ['code' => 5420, 'name' => 'Амортизација (трошок)', 'type' => Account::OPERATING_EXPENSE],
            ['code' => 5500, 'name' => 'Финансиски расходи', 'type' => Account::OTHER_EXPENSE],
            ['code' => 5700, 'name' => 'Останати расходи', 'type' => Account::OTHER_EXPENSE],
        ];

        foreach ($accounts as $acc) {
            Account::firstOrCreate(
                ['code' => $acc['code'], 'entity_id' => $entityId],
                [
                    'name' => $acc['name'],
                    'account_type' => $acc['type'],
                    'category_id' => $categories[$acc['type']]->id,
                    'currency_id' => $currencyId,
                    'entity_id' => $entityId,
                ]
            );
        }

        $this->command->info('Chart of accounts created: ' . count($accounts) . ' accounts');
    }

    protected function createTaxTypes(Company $company): array
    {
        // Standard 18%
        $taxType18 = TaxType::firstOrCreate(
            ['name' => 'ДДВ 18%', 'company_id' => $company->id],
            [
                'percent' => 18,
                'compound_tax' => false,
                'collective_tax' => 0,
                'description' => 'Стандардна стапка на ДДВ',
                'type' => 'MODULE',
            ]
        );

        // Reduced 5%
        $taxType5 = TaxType::firstOrCreate(
            ['name' => 'ДДВ 5%', 'company_id' => $company->id],
            [
                'percent' => 5,
                'compound_tax' => false,
                'collective_tax' => 0,
                'description' => 'Повластена стапка на ДДВ',
                'type' => 'MODULE',
            ]
        );

        return ['standard' => $taxType18, 'reduced' => $taxType5];
    }

    protected function createCustomers(Company $company, Currency $currency): array
    {
        $customerData = [
            ['name' => 'Алкалоид АД', 'email' => 'info@alkaloid-test.mk', 'phone' => '+38922400100'],
            ['name' => 'Тетекс ДООЕЛ', 'email' => 'info@teteks-test.mk', 'phone' => '+38943222111'],
            ['name' => 'Макпетрол АД', 'email' => 'info@makpetrol-test.mk', 'phone' => '+38923100200'],
            ['name' => 'ОКТА АД', 'email' => 'info@okta-test.mk', 'phone' => '+38923290100'],
        ];

        $customers = [];
        foreach ($customerData as $data) {
            $customer = Customer::firstOrCreate(
                ['name' => $data['name'], 'company_id' => $company->id],
                [
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'currency_id' => $currency->id,
                    'creator_id' => $company->owner_id,
                    'company_id' => $company->id,
                ]
            );
            $customers[] = $customer;
        }

        return $customers;
    }

    protected function createPaymentMethod(Company $company): PaymentMethod
    {
        return PaymentMethod::firstOrCreate(
            ['name' => 'Банкарски трансфер', 'company_id' => $company->id],
            ['company_id' => $company->id]
        );
    }

    protected function createInvoicesWithPosting(
        Company $company,
        Entity $entity,
        array $customers,
        array $taxes,
        Currency $currency
    ): void {
        $ifrsAdapter = app(IfrsAdapter::class);

        $invoiceData = [
            // Invoice 1: Standard 18% VAT, paid
            [
                'invoice_number' => 'УЈП-ФАК-001',
                'invoice_date' => '2025-01-15',
                'due_date' => '2025-02-15',
                'customer_idx' => 0,
                'items' => [
                    ['name' => 'Софтверски развој - 40 часа', 'quantity' => 40, 'price' => 1500, 'tax' => 'standard'],
                    ['name' => 'Хостинг - 12 месеци', 'quantity' => 12, 'price' => 3000, 'tax' => 'standard'],
                ],
                'paid' => true,
            ],
            // Invoice 2: Standard 18% VAT, unpaid
            [
                'invoice_number' => 'УЈП-ФАК-002',
                'invoice_date' => '2025-02-01',
                'due_date' => '2025-03-01',
                'customer_idx' => 1,
                'items' => [
                    ['name' => 'ИТ Консултации - 20 часа', 'quantity' => 20, 'price' => 1200, 'tax' => 'standard'],
                    ['name' => 'Техничка поддршка - 15 часа', 'quantity' => 15, 'price' => 1000, 'tax' => 'standard'],
                ],
                'paid' => false,
            ],
            // Invoice 3: Reduced 5% VAT, paid
            [
                'invoice_number' => 'УЈП-ФАК-003',
                'invoice_date' => '2025-01-20',
                'due_date' => '2025-02-20',
                'customer_idx' => 2,
                'items' => [
                    ['name' => 'Е-учебник - Дигитализација', 'quantity' => 50, 'price' => 500, 'tax' => 'reduced'],
                    ['name' => 'Онлајн обука - 10 сесии', 'quantity' => 10, 'price' => 2000, 'tax' => 'reduced'],
                ],
                'paid' => true,
            ],
            // Invoice 4: Mixed VAT, paid
            [
                'invoice_number' => 'УЈП-ФАК-004',
                'invoice_date' => '2025-01-25',
                'due_date' => '2025-02-25',
                'customer_idx' => 3,
                'items' => [
                    ['name' => 'Веб дизајн', 'quantity' => 1, 'price' => 45000, 'tax' => 'standard'],
                    ['name' => 'Е-книга лиценца', 'quantity' => 100, 'price' => 300, 'tax' => 'reduced'],
                ],
                'paid' => true,
            ],
            // Invoice 5: Standard 18%, partially paid (for realistic DDV-04)
            [
                'invoice_number' => 'УЈП-ФАК-005',
                'invoice_date' => '2025-01-10',
                'due_date' => '2025-02-10',
                'customer_idx' => 0,
                'items' => [
                    ['name' => 'Годишна одржување на систем', 'quantity' => 1, 'price' => 120000, 'tax' => 'standard'],
                ],
                'paid' => true,
            ],
            // Invoice 6: January, standard, paid
            [
                'invoice_number' => 'УЈП-ФАК-006',
                'invoice_date' => '2025-01-28',
                'due_date' => '2025-02-28',
                'customer_idx' => 1,
                'items' => [
                    ['name' => 'Интеграција со ERP систем', 'quantity' => 1, 'price' => 85000, 'tax' => 'standard'],
                ],
                'paid' => true,
            ],
        ];

        foreach ($invoiceData as $invData) {
            $customer = $customers[$invData['customer_idx']];

            // Calculate totals
            $subTotal = 0;
            $taxTotal = 0;
            foreach ($invData['items'] as $itemData) {
                $lineTotal = $itemData['quantity'] * $itemData['price'];
                $taxRate = $itemData['tax'] === 'standard' ? 18 : 5;
                $lineTax = round($lineTotal * $taxRate / 100);
                $subTotal += $lineTotal;
                $taxTotal += $lineTax;
            }
            $total = $subTotal + $taxTotal;

            // Check if invoice already exists
            $existing = Invoice::where('invoice_number', $invData['invoice_number'])
                ->where('company_id', $company->id)
                ->first();
            if ($existing) {
                continue;
            }

            // Create invoice (without triggering observers)
            $invoice = new Invoice();
            $invoice->invoice_number = $invData['invoice_number'];
            $invoice->invoice_date = $invData['invoice_date'];
            $invoice->due_date = $invData['due_date'];
            $invoice->company_id = $company->id;
            $invoice->customer_id = $customer->id;
            $invoice->currency_id = $currency->id;
            $invoice->creator_id = $company->owner_id;
            $invoice->template_name = 'invoice1';
            $invoice->unique_hash = \Illuminate\Support\Str::random(20);
            $invoice->sub_total = $subTotal;
            $invoice->tax = $taxTotal;
            $invoice->total = $total;
            $invoice->due_amount = $invData['paid'] ? 0 : $total;
            $invoice->tax_per_item = 'YES';
            $invoice->discount_per_item = 'NO';
            $invoice->status = Invoice::STATUS_COMPLETED;
            $invoice->paid_status = $invData['paid'] ? Invoice::STATUS_PAID : Invoice::STATUS_UNPAID;
            $invoice->saveQuietly();

            // Create invoice items
            foreach ($invData['items'] as $itemData) {
                $lineTotal = $itemData['quantity'] * $itemData['price'];
                $taxRate = $itemData['tax'] === 'standard' ? 18 : 5;
                $lineTax = round($lineTotal * $taxRate / 100);
                $taxType = $taxes[$itemData['tax']];

                $item = InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'name' => $itemData['name'],
                    'description' => $itemData['name'],
                    'quantity' => $itemData['quantity'],
                    'price' => $itemData['price'],
                    'total' => $lineTotal,
                    'company_id' => $company->id,
                    'discount_type' => 'fixed',
                    'discount' => 0,
                    'discount_val' => 0,
                    'tax' => $lineTax,
                ]);

                // Create tax entry for the item
                Tax::create([
                    'invoice_item_id' => $item->id,
                    'invoice_id' => $invoice->id,
                    'tax_type_id' => $taxType->id,
                    'company_id' => $company->id,
                    'name' => $taxType->name,
                    'amount' => $lineTax,
                    'percent' => $taxType->percent,
                    'compound_tax' => 0,
                    'base_amount' => $lineTotal,
                ]);
            }

            // Post to IFRS
            try {
                $ifrsAdapter->postInvoice($invoice);
                $this->command->line("  Invoice {$invData['invoice_number']} posted to IFRS");
            } catch (\Exception $e) {
                $this->command->warn("  Failed to post invoice {$invData['invoice_number']}: {$e->getMessage()}");
            }
        }

        $this->command->info('Invoices created and posted: ' . count($invoiceData));
    }

    protected function createBillsWithPosting(
        Company $company,
        Entity $entity,
        array $taxes,
        Currency $currency
    ): void {
        $ifrsAdapter = app(IfrsAdapter::class);

        $billData = [
            // Bill 1: Office rent
            [
                'bill_number' => 'УЈП-СМТ-001',
                'bill_date' => '2025-01-05',
                'due_date' => '2025-02-05',
                'items' => [
                    ['name' => 'Наем за канцеларија - Јануари', 'quantity' => 1, 'price' => 25000, 'tax' => 'standard'],
                ],
            ],
            // Bill 2: IT services
            [
                'bill_number' => 'УЈП-СМТ-002',
                'bill_date' => '2025-01-12',
                'due_date' => '2025-02-12',
                'items' => [
                    ['name' => 'Интернет услуги - Јануари', 'quantity' => 1, 'price' => 3500, 'tax' => 'standard'],
                    ['name' => 'Телефонија - Јануари', 'quantity' => 1, 'price' => 2500, 'tax' => 'standard'],
                ],
            ],
            // Bill 3: Office supplies (reduced VAT)
            [
                'bill_number' => 'УЈП-СМТ-003',
                'bill_date' => '2025-01-18',
                'due_date' => '2025-02-18',
                'items' => [
                    ['name' => 'Канцелариски материјали', 'quantity' => 1, 'price' => 8000, 'tax' => 'reduced'],
                ],
            ],
            // Bill 4: Marketing
            [
                'bill_number' => 'УЈП-СМТ-004',
                'bill_date' => '2025-01-22',
                'due_date' => '2025-02-22',
                'items' => [
                    ['name' => 'Дигитален маркетинг - Јануари', 'quantity' => 1, 'price' => 15000, 'tax' => 'standard'],
                ],
            ],
            // Bill 5: Salary costs (no VAT on salaries, but we model as standard for testing)
            [
                'bill_number' => 'УЈП-СМТ-005',
                'bill_date' => '2025-01-31',
                'due_date' => '2025-02-28',
                'items' => [
                    ['name' => 'Сметководствени услуги', 'quantity' => 1, 'price' => 12000, 'tax' => 'standard'],
                ],
            ],
        ];

        foreach ($billData as $bData) {
            // Calculate totals
            $subTotal = 0;
            $taxTotal = 0;
            foreach ($bData['items'] as $itemData) {
                $lineTotal = $itemData['quantity'] * $itemData['price'];
                $taxRate = $itemData['tax'] === 'standard' ? 18 : 5;
                $lineTax = round($lineTotal * $taxRate / 100);
                $subTotal += $lineTotal;
                $taxTotal += $lineTax;
            }
            $total = $subTotal + $taxTotal;

            // Check if bill already exists
            $existing = Bill::where('bill_number', $bData['bill_number'])
                ->where('company_id', $company->id)
                ->first();
            if ($existing) {
                continue;
            }

            // Create bill (without triggering observers)
            $bill = new Bill();
            $bill->bill_number = $bData['bill_number'];
            $bill->bill_date = $bData['bill_date'];
            $bill->due_date = $bData['due_date'];
            $bill->company_id = $company->id;
            $bill->currency_id = $currency->id;
            $bill->creator_id = $company->owner_id;
            $bill->unique_hash = \Illuminate\Support\Str::random(20);
            $bill->sub_total = $subTotal;
            $bill->tax = $taxTotal;
            $bill->total = $total;
            $bill->due_amount = 0;
            $bill->tax_per_item = 'YES';
            $bill->discount_per_item = 'NO';
            $bill->status = 'COMPLETED';
            $bill->paid_status = Bill::PAID_STATUS_PAID;
            $bill->saveQuietly();

            // Create bill items
            foreach ($bData['items'] as $itemData) {
                $lineTotal = $itemData['quantity'] * $itemData['price'];
                $taxRate = $itemData['tax'] === 'standard' ? 18 : 5;
                $lineTax = round($lineTotal * $taxRate / 100);
                $taxType = $taxes[$itemData['tax']];

                $item = BillItem::create([
                    'bill_id' => $bill->id,
                    'name' => $itemData['name'],
                    'description' => $itemData['name'],
                    'quantity' => $itemData['quantity'],
                    'price' => $itemData['price'],
                    'total' => $lineTotal,
                    'company_id' => $company->id,
                    'discount_type' => 'fixed',
                    'discount' => 0,
                    'discount_val' => 0,
                    'tax' => $lineTax,
                ]);

                // Create tax entry for the item
                Tax::create([
                    'bill_item_id' => $item->id,
                    'bill_id' => $bill->id,
                    'tax_type_id' => $taxType->id,
                    'company_id' => $company->id,
                    'name' => $taxType->name,
                    'amount' => $lineTax,
                    'percent' => $taxType->percent,
                    'compound_tax' => 0,
                    'base_amount' => $lineTotal,
                ]);
            }

            // Post to IFRS
            try {
                $ifrsAdapter->postBill($bill);
                $this->command->line("  Bill {$bData['bill_number']} posted to IFRS");
            } catch (\Exception $e) {
                $this->command->warn("  Failed to post bill {$bData['bill_number']}: {$e->getMessage()}");
            }
        }

        $this->command->info('Bills created and posted: ' . count($billData));
    }
}

// CLAUDE-CHECKPOINT
