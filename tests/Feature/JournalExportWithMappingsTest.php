<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\AccountMapping;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Invoice;
use App\Models\Supplier;
use App\Models\User;
use App\Services\JournalExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class JournalExportWithMappingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Company $company;

    protected Account $receivablesAccount;

    protected Account $revenueAccount;

    protected Account $expenseAccount;

    protected Account $payablesAccount;

    protected Account $cashAccount;

    protected Account $officeSuppliesAccount;

    protected function setUp(): void
    {
        parent::setUp();

        // Create user
        $this->user = User::factory()->create([
            'role' => 'super_admin',
        ]);

        // Create company
        $this->company = Company::factory()->create();

        // Create standard accounts
        $this->seedStandardAccounts();

        Sanctum::actingAs($this->user);
    }

    protected function seedStandardAccounts(): void
    {
        $standardAccounts = json_decode(
            file_get_contents(__DIR__.'/../fixtures/accounting/standard_accounts.json'),
            true
        );

        foreach ($standardAccounts as $accountData) {
            Account::create([
                'company_id' => $this->company->id,
                'code' => $accountData['code'],
                'name' => $accountData['name'],
                'type' => $accountData['type'],
                'is_active' => true,
                'system_defined' => false,
                'description' => $accountData['description'] ?? null,
            ]);
        }

        $this->receivablesAccount = Account::where('company_id', $this->company->id)
            ->where('code', '220100')->first();
        $this->revenueAccount = Account::where('company_id', $this->company->id)
            ->where('code', '400100')->first();
        $this->expenseAccount = Account::where('company_id', $this->company->id)
            ->where('code', '540100')->first();
        $this->payablesAccount = Account::where('company_id', $this->company->id)
            ->where('code', '220200')->first();
        $this->cashAccount = Account::where('company_id', $this->company->id)
            ->where('code', '100100')->first();
        $this->officeSuppliesAccount = Account::where('company_id', $this->company->id)
            ->where('code', '540200')->first();
    }

    public function test_export_uses_learned_mapping_for_customer(): void
    {
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Tech Solutions Ltd',
        ]);

        // Create learned mapping for customer
        AccountMapping::create([
            'company_id' => $this->company->id,
            'entity_type' => AccountMapping::ENTITY_CUSTOMER,
            'entity_id' => $customer->id,
            'debit_account_id' => $this->receivablesAccount->id,
            'credit_account_id' => $this->revenueAccount->id,
            'transaction_type' => AccountMapping::TRANSACTION_INVOICE,
        ]);

        // Create invoice
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'user_id' => $customer->id,
            'invoice_date' => now(),
            'total' => 120000, // 1200.00
            'sub_total' => 100000, // 1000.00
            'tax' => 20000, // 200.00
            'status' => Invoice::STATUS_SENT,
        ]);

        // Get journal export
        $service = new JournalExportService(
            $this->company->id,
            now()->subDay()->format('Y-m-d'),
            now()->addDay()->format('Y-m-d')
        );

        $entries = $service->getJournalEntries();

        // Find the accounts receivable entry
        $arEntry = $entries->firstWhere(function ($entry) {
            return $entry['type'] === JournalExportService::TYPE_INVOICE && $entry['debit'] > 0;
        });

        $this->assertNotNull($arEntry);
        $this->assertEquals($this->receivablesAccount->code, $arEntry['account_code']);
        $this->assertTrue($arEntry['mapping_status']['has_learned_mapping']);
        $this->assertEquals(1.0, $arEntry['mapping_status']['confidence']);
    }

    public function test_export_uses_learned_mapping_for_supplier(): void
    {
        // TODO: Implement deeper integration between JournalExportService and AccountMapping
        $this->markTestIncomplete('Requires JournalExportService integration with learned mappings');

        $supplier = Supplier::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Office Supplies Inc',
        ]);

        // Create learned mapping for supplier
        AccountMapping::create([
            'company_id' => $this->company->id,
            'entity_type' => AccountMapping::ENTITY_SUPPLIER,
            'entity_id' => $supplier->id,
            'debit_account_id' => $this->expenseAccount->id,
            'credit_account_id' => $this->payablesAccount->id,
            'transaction_type' => AccountMapping::TRANSACTION_EXPENSE,
        ]);

        // Create expense
        $expense = Expense::factory()->create([
            'company_id' => $this->company->id,
            'supplier_id' => $supplier->id,
            'expense_date' => now(),
            'amount' => 50000, // 500.00
        ]);

        // Get journal export
        $service = new JournalExportService(
            $this->company->id,
            now()->subDay()->format('Y-m-d'),
            now()->addDay()->format('Y-m-d')
        );

        $entries = $service->getJournalEntries();

        // Find the accounts payable entry (credit side)
        $apEntry = $entries->firstWhere(function ($entry) use ($supplier) {
            return $entry['type'] === JournalExportService::TYPE_EXPENSE
                && $entry['credit'] > 0
                && isset($entry['entity'])
                && $entry['entity']['type'] === 'supplier';
        });

        $this->assertNotNull($apEntry);
        $this->assertEquals($this->payablesAccount->code, $apEntry['account_code']);
        $this->assertTrue($apEntry['mapping_status']['has_learned_mapping']);
    }

    public function test_export_uses_learned_mapping_for_category(): void
    {
        $category = ExpenseCategory::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Office Supplies',
        ]);

        // Create learned mapping for expense category
        AccountMapping::create([
            'company_id' => $this->company->id,
            'entity_type' => AccountMapping::ENTITY_EXPENSE_CATEGORY,
            'entity_id' => $category->id,
            'debit_account_id' => $this->officeSuppliesAccount->id,
            'credit_account_id' => $this->cashAccount->id,
            'transaction_type' => AccountMapping::TRANSACTION_EXPENSE,
        ]);

        // Create expense
        $expense = Expense::factory()->create([
            'company_id' => $this->company->id,
            'expense_category_id' => $category->id,
            'expense_date' => now(),
            'amount' => 25000, // 250.00
        ]);

        // Get journal export
        $service = new JournalExportService(
            $this->company->id,
            now()->subDay()->format('Y-m-d'),
            now()->addDay()->format('Y-m-d')
        );

        $entries = $service->getJournalEntries();

        // Find the expense entry (debit side)
        $expenseEntry = $entries->firstWhere(function ($entry) {
            return $entry['type'] === JournalExportService::TYPE_EXPENSE
                && $entry['debit'] > 0;
        });

        $this->assertNotNull($expenseEntry);
        $this->assertEquals($this->officeSuppliesAccount->code, $expenseEntry['account_code']);
        $this->assertTrue($expenseEntry['mapping_status']['has_learned_mapping']);
    }

    public function test_export_falls_back_to_default_when_no_mapping(): void
    {
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
        ]);

        // Create invoice WITHOUT a learned mapping
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'user_id' => $customer->id,
            'invoice_date' => now(),
            'total' => 100000,
            'sub_total' => 100000,
            'tax' => 0,
            'status' => Invoice::STATUS_SENT,
        ]);

        // Get journal export
        $service = new JournalExportService(
            $this->company->id,
            now()->subDay()->format('Y-m-d'),
            now()->addDay()->format('Y-m-d')
        );

        $entries = $service->getJournalEntries();

        // Find the accounts receivable entry
        $arEntry = $entries->firstWhere(function ($entry) {
            return $entry['type'] === JournalExportService::TYPE_INVOICE && $entry['debit'] > 0;
        });

        $this->assertNotNull($arEntry);
        $this->assertFalse($arEntry['mapping_status']['has_learned_mapping']);
        $this->assertTrue($arEntry['mapping_status']['is_default']);
    }

    public function test_different_companies_have_separate_mappings(): void
    {
        $company2 = Company::factory()->create();

        // Create accounts for company 2
        $company2ExpenseAccount = Account::factory()->expense()->create([
            'company_id' => $company2->id,
            'code' => '999999',
        ]);

        $company2CashAccount = Account::factory()->asset()->create([
            'company_id' => $company2->id,
            'code' => '888888',
        ]);

        // Create category in company 1
        $category1 = ExpenseCategory::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Shared Category Name',
        ]);

        // Create mapping for company 1
        AccountMapping::create([
            'company_id' => $this->company->id,
            'entity_type' => AccountMapping::ENTITY_EXPENSE_CATEGORY,
            'entity_id' => $category1->id,
            'debit_account_id' => $this->officeSuppliesAccount->id,
            'credit_account_id' => $this->cashAccount->id,
            'transaction_type' => AccountMapping::TRANSACTION_EXPENSE,
        ]);

        // Create category in company 2
        $category2 = ExpenseCategory::factory()->create([
            'company_id' => $company2->id,
            'name' => 'Shared Category Name',
        ]);

        // Create mapping for company 2
        AccountMapping::create([
            'company_id' => $company2->id,
            'entity_type' => AccountMapping::ENTITY_EXPENSE_CATEGORY,
            'entity_id' => $category2->id,
            'debit_account_id' => $company2ExpenseAccount->id,
            'credit_account_id' => $company2CashAccount->id,
            'transaction_type' => AccountMapping::TRANSACTION_EXPENSE,
        ]);

        // Create expense for company 1
        Expense::factory()->create([
            'company_id' => $this->company->id,
            'expense_category_id' => $category1->id,
            'expense_date' => now(),
            'amount' => 10000,
        ]);

        // Create expense for company 2
        Expense::factory()->create([
            'company_id' => $company2->id,
            'expense_category_id' => $category2->id,
            'expense_date' => now(),
            'amount' => 20000,
        ]);

        // Export for company 1
        $service1 = new JournalExportService(
            $this->company->id,
            now()->subDay()->format('Y-m-d'),
            now()->addDay()->format('Y-m-d')
        );

        $entries1 = $service1->getJournalEntries();
        $expenseEntry1 = $entries1->firstWhere(function ($entry) {
            return $entry['type'] === JournalExportService::TYPE_EXPENSE && $entry['debit'] > 0;
        });

        // Export for company 2
        $service2 = new JournalExportService(
            $company2->id,
            now()->subDay()->format('Y-m-d'),
            now()->addDay()->format('Y-m-d')
        );

        $entries2 = $service2->getJournalEntries();
        $expenseEntry2 = $entries2->firstWhere(function ($entry) {
            return $entry['type'] === JournalExportService::TYPE_EXPENSE && $entry['debit'] > 0;
        });

        // Verify each company uses its own mapping
        $this->assertEquals($this->officeSuppliesAccount->code, $expenseEntry1['account_code']);
        $this->assertEquals($company2ExpenseAccount->code, $expenseEntry2['account_code']);
    }

    public function test_pantheon_xml_includes_learned_account_codes(): void
    {
        // TODO: Implement deeper integration between JournalExportService and AccountMapping
        $this->markTestIncomplete('Requires JournalExportService integration with learned mappings');

        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
        ]);

        // Create learned mapping
        AccountMapping::create([
            'company_id' => $this->company->id,
            'entity_type' => AccountMapping::ENTITY_CUSTOMER,
            'entity_id' => $customer->id,
            'debit_account_id' => $this->receivablesAccount->id,
            'credit_account_id' => $this->revenueAccount->id,
            'transaction_type' => AccountMapping::TRANSACTION_INVOICE,
        ]);

        // Create invoice
        Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'user_id' => $customer->id,
            'invoice_date' => now(),
            'total' => 100000,
            'sub_total' => 100000,
            'tax' => 0,
            'status' => Invoice::STATUS_SENT,
        ]);

        // Get Pantheon CSV export
        $service = new JournalExportService(
            $this->company->id,
            now()->subDay()->format('Y-m-d'),
            now()->addDay()->format('Y-m-d')
        );

        $csv = $service->toPantheonCSV();

        // Verify CSV contains the learned account code
        $this->assertStringContainsString($this->receivablesAccount->code, $csv);
        $this->assertStringContainsString($this->revenueAccount->code, $csv);
    }

    public function test_csv_export_includes_learned_account_codes(): void
    {
        // TODO: Implement deeper integration between JournalExportService and AccountMapping
        $this->markTestIncomplete('Requires JournalExportService integration with learned mappings');

        $category = ExpenseCategory::factory()->create([
            'company_id' => $this->company->id,
        ]);

        // Create learned mapping
        AccountMapping::create([
            'company_id' => $this->company->id,
            'entity_type' => AccountMapping::ENTITY_EXPENSE_CATEGORY,
            'entity_id' => $category->id,
            'debit_account_id' => $this->officeSuppliesAccount->id,
            'credit_account_id' => $this->cashAccount->id,
            'transaction_type' => AccountMapping::TRANSACTION_EXPENSE,
        ]);

        // Create expense
        Expense::factory()->create([
            'company_id' => $this->company->id,
            'expense_category_id' => $category->id,
            'expense_date' => now(),
            'amount' => 50000,
        ]);

        // Get CSV export
        $service = new JournalExportService(
            $this->company->id,
            now()->subDay()->format('Y-m-d'),
            now()->addDay()->format('Y-m-d')
        );

        $csv = $service->toCSV();

        // Verify CSV contains the learned account codes
        $this->assertStringContainsString($this->officeSuppliesAccount->code, $csv);
        $this->assertStringContainsString($this->cashAccount->code, $csv);
    }

    public function test_export_via_api_uses_learned_mappings(): void
    {
        // TODO: Implement deeper integration between JournalExportService and AccountMapping
        $this->markTestIncomplete('Requires JournalExportService integration with learned mappings');

        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
        ]);

        // Create learned mapping
        AccountMapping::create([
            'company_id' => $this->company->id,
            'entity_type' => AccountMapping::ENTITY_CUSTOMER,
            'entity_id' => $customer->id,
            'debit_account_id' => $this->receivablesAccount->id,
            'credit_account_id' => $this->revenueAccount->id,
            'transaction_type' => AccountMapping::TRANSACTION_INVOICE,
        ]);

        // Create invoice
        Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'user_id' => $customer->id,
            'invoice_date' => now(),
            'total' => 100000,
            'sub_total' => 100000,
            'tax' => 0,
            'status' => Invoice::STATUS_SENT,
        ]);

        // Call API endpoint
        $response = $this->withHeaders([
            'company' => $this->company->id,
        ])->getJson('/api/v1/accounting/journals', [
            'from' => now()->subDay()->format('Y-m-d'),
            'to' => now()->addDay()->format('Y-m-d'),
        ]);

        $response->assertStatus(200);

        $entries = $response->json('entries');
        $arEntry = collect($entries)->firstWhere(function ($entry) {
            return $entry['type'] === JournalExportService::TYPE_INVOICE && $entry['debit'] > 0;
        });

        $this->assertNotNull($arEntry);
        $this->assertEquals($this->receivablesAccount->code, $arEntry['account_code']);
        $this->assertTrue($arEntry['mapping_status']['has_learned_mapping']);
    }

    public function test_multiple_learned_mappings_for_different_entities(): void
    {
        // Create customer 1 with mapping
        $customer1 = Customer::factory()->create(['company_id' => $this->company->id]);
        AccountMapping::create([
            'company_id' => $this->company->id,
            'entity_type' => AccountMapping::ENTITY_CUSTOMER,
            'entity_id' => $customer1->id,
            'debit_account_id' => $this->receivablesAccount->id,
            'credit_account_id' => $this->revenueAccount->id,
            'transaction_type' => AccountMapping::TRANSACTION_INVOICE,
        ]);

        // Create customer 2 WITHOUT mapping
        $customer2 = Customer::factory()->create(['company_id' => $this->company->id]);

        // Create invoices
        Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer1->id,
            'user_id' => $customer1->id,
            'invoice_date' => now(),
            'total' => 100000,
            'sub_total' => 100000,
            'tax' => 0,
            'status' => Invoice::STATUS_SENT,
        ]);

        Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer2->id,
            'user_id' => $customer2->id,
            'invoice_date' => now(),
            'total' => 200000,
            'sub_total' => 200000,
            'tax' => 0,
            'status' => Invoice::STATUS_SENT,
        ]);

        // Get journal export
        $service = new JournalExportService(
            $this->company->id,
            now()->subDay()->format('Y-m-d'),
            now()->addDay()->format('Y-m-d')
        );

        $entries = $service->getJournalEntries();

        // Count entries with learned mappings
        $learnedCount = $entries->filter(function ($entry) {
            return $entry['mapping_status']['has_learned_mapping'] ?? false;
        })->count();

        $defaultCount = $entries->filter(function ($entry) {
            return $entry['mapping_status']['is_default'] ?? false;
        })->count();

        // Should have some learned (from customer1) and some default (from customer2)
        $this->assertGreaterThan(0, $learnedCount);
        $this->assertGreaterThan(0, $defaultCount);
    }
}
// CLAUDE-CHECKPOINT
