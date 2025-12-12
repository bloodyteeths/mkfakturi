<?php

namespace Tests\Unit;

use App\Models\Account;
use App\Models\AccountMapping;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Invoice;
use App\Models\Supplier;
use App\Services\AccountSuggestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountSuggestionServiceConfidenceTest extends TestCase
{
    use RefreshDatabase;

    protected AccountSuggestionService $service;

    protected Company $company;

    protected Account $receivablesAccount;

    protected Account $revenueAccount;

    protected Account $expenseAccount;

    protected Account $payablesAccount;

    protected Account $cashAccount;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AccountSuggestionService();

        // Create company
        $this->company = Company::factory()->create();

        // Seed standard accounts
        $this->seedStandardAccounts();
    }

    /**
     * Seed standard Macedonian accounts for testing
     */
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

        // Store commonly used accounts
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
    }

    public function test_exact_learned_mapping_for_customer_returns_confidence_095(): void
    {
        // Create a customer with a learned mapping
        $customer = Customer::factory()->create([
            'name' => 'Test Company',
            'company_id' => $this->company->id,
        ]);

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
        ]);

        $result = $this->service->suggestForInvoice($invoice);

        $this->assertEquals(0.95, $result['confidence']);
        $this->assertEquals($this->receivablesAccount->id, $result['debit_account_id']);
        $this->assertEquals($this->revenueAccount->id, $result['credit_account_id']);
    }

    public function test_pattern_match_from_historical_invoice_returns_confidence_085(): void
    {
        // Create a customer
        $customer = Customer::factory()->create([
            'name' => 'Tech Solutions',
            'company_id' => $this->company->id,
        ]);

        // Create a previously confirmed invoice
        $previousInvoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'confirmed_debit_account_id' => $this->receivablesAccount->id,
            'confirmed_credit_account_id' => $this->revenueAccount->id,
            'account_confirmed_at' => now()->subDays(5),
        ]);

        // Create new invoice for same customer
        $newInvoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
        ]);

        $result = $this->service->suggestForInvoice($newInvoice);

        $this->assertEquals(0.85, $result['confidence']);
        $this->assertEquals($this->receivablesAccount->id, $result['debit_account_id']);
        $this->assertEquals($this->revenueAccount->id, $result['credit_account_id']);
    }

    public function test_default_mapping_returns_confidence_070(): void
    {
        // Create default invoice mapping
        AccountMapping::create([
            'company_id' => $this->company->id,
            'entity_type' => AccountMapping::ENTITY_DEFAULT,
            'entity_id' => null,
            'debit_account_id' => $this->receivablesAccount->id,
            'credit_account_id' => $this->revenueAccount->id,
            'transaction_type' => AccountMapping::TRANSACTION_INVOICE,
        ]);

        // Create customer without specific mapping
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
        ]);

        $result = $this->service->suggestForInvoice($invoice);

        $this->assertEquals(0.70, $result['confidence']);
        $this->assertEquals($this->receivablesAccount->id, $result['debit_account_id']);
        $this->assertEquals($this->revenueAccount->id, $result['credit_account_id']);
    }

    public function test_fallback_to_generic_accounts_returns_confidence_050(): void
    {
        // Create customer without any mappings
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
        ]);

        $result = $this->service->suggestForInvoice($invoice);

        // Should fall back to generic lookup
        $this->assertEquals(0.50, $result['confidence']);
        $this->assertNotNull($result['debit_account_id']);
        $this->assertNotNull($result['credit_account_id']);
    }

    public function test_expense_category_mapping_returns_confidence_095(): void
    {
        // Create expense category with mapping
        $category = ExpenseCategory::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Office Supplies',
        ]);

        AccountMapping::create([
            'company_id' => $this->company->id,
            'entity_type' => AccountMapping::ENTITY_EXPENSE_CATEGORY,
            'entity_id' => $category->id,
            'debit_account_id' => $this->expenseAccount->id,
            'credit_account_id' => $this->cashAccount->id,
            'transaction_type' => AccountMapping::TRANSACTION_EXPENSE,
        ]);

        $expense = Expense::factory()->create([
            'company_id' => $this->company->id,
            'expense_category_id' => $category->id,
        ]);

        $result = $this->service->suggestForExpense($expense);

        $this->assertEquals(0.95, $result['confidence']);
        $this->assertEquals($this->expenseAccount->id, $result['debit_account_id']);
        $this->assertEquals($this->cashAccount->id, $result['credit_account_id']);
    }

    public function test_supplier_mapping_returns_confidence_090(): void
    {
        // Create supplier with mapping
        $supplier = Supplier::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Office Depot',
        ]);

        AccountMapping::create([
            'company_id' => $this->company->id,
            'entity_type' => AccountMapping::ENTITY_SUPPLIER,
            'entity_id' => $supplier->id,
            'debit_account_id' => $this->expenseAccount->id,
            'credit_account_id' => $this->payablesAccount->id,
            'transaction_type' => AccountMapping::TRANSACTION_EXPENSE,
        ]);

        $expense = Expense::factory()->create([
            'company_id' => $this->company->id,
            'supplier_id' => $supplier->id,
        ]);

        $result = $this->service->suggestForExpense($expense);

        $this->assertEquals(0.90, $result['confidence']);
        $this->assertEquals($this->expenseAccount->id, $result['debit_account_id']);
        $this->assertEquals($this->payablesAccount->id, $result['credit_account_id']);
    }

    public function test_historical_expense_pattern_returns_confidence_080(): void
    {
        // Create expense category
        $category = ExpenseCategory::factory()->create([
            'company_id' => $this->company->id,
        ]);

        // Create a previously confirmed expense
        $previousExpense = Expense::factory()->create([
            'company_id' => $this->company->id,
            'expense_category_id' => $category->id,
            'confirmed_debit_account_id' => $this->expenseAccount->id,
            'confirmed_credit_account_id' => $this->cashAccount->id,
            'account_confirmed_at' => now()->subDays(3),
        ]);

        // Create new expense for same category
        $newExpense = Expense::factory()->create([
            'company_id' => $this->company->id,
            'expense_category_id' => $category->id,
        ]);

        $result = $this->service->suggestForExpense($newExpense);

        $this->assertEquals(0.80, $result['confidence']);
        $this->assertEquals($this->expenseAccount->id, $result['debit_account_id']);
        $this->assertEquals($this->cashAccount->id, $result['credit_account_id']);
    }

    public function test_expense_default_mapping_returns_confidence_065(): void
    {
        // Create default expense mapping
        AccountMapping::create([
            'company_id' => $this->company->id,
            'entity_type' => AccountMapping::ENTITY_DEFAULT,
            'entity_id' => null,
            'debit_account_id' => $this->expenseAccount->id,
            'credit_account_id' => $this->payablesAccount->id,
            'transaction_type' => AccountMapping::TRANSACTION_EXPENSE,
        ]);

        $expense = Expense::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $result = $this->service->suggestForExpense($expense);

        $this->assertEquals(0.65, $result['confidence']);
        $this->assertEquals($this->expenseAccount->id, $result['debit_account_id']);
        $this->assertEquals($this->payablesAccount->id, $result['credit_account_id']);
    }

    public function test_confidence_ordering_prefers_exact_mapping_over_historical(): void
    {
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
        ]);

        // Create historical invoice
        Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'confirmed_debit_account_id' => $this->expenseAccount->id,
            'confirmed_credit_account_id' => $this->cashAccount->id,
            'account_confirmed_at' => now()->subDays(1),
        ]);

        // Create exact mapping (should take precedence)
        AccountMapping::create([
            'company_id' => $this->company->id,
            'entity_type' => AccountMapping::ENTITY_CUSTOMER,
            'entity_id' => $customer->id,
            'debit_account_id' => $this->receivablesAccount->id,
            'credit_account_id' => $this->revenueAccount->id,
            'transaction_type' => AccountMapping::TRANSACTION_INVOICE,
        ]);

        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
        ]);

        $result = $this->service->suggestForInvoice($invoice);

        // Should use exact mapping (0.95) not historical (0.85)
        $this->assertEquals(0.95, $result['confidence']);
        $this->assertEquals($this->receivablesAccount->id, $result['debit_account_id']);
        $this->assertEquals($this->revenueAccount->id, $result['credit_account_id']);
    }

    public function test_different_companies_have_isolated_suggestions(): void
    {
        $company2 = Company::factory()->create();

        // Create accounts for company 2
        $company2RevenueAccount = Account::factory()->revenue()->create([
            'company_id' => $company2->id,
            'code' => '999999',
        ]);

        $company2ReceivablesAccount = Account::factory()->asset()->create([
            'company_id' => $company2->id,
            'code' => '888888',
        ]);

        // Create customer in company 1
        $customer1 = Customer::factory()->create([
            'company_id' => $this->company->id,
        ]);

        AccountMapping::create([
            'company_id' => $this->company->id,
            'entity_type' => AccountMapping::ENTITY_CUSTOMER,
            'entity_id' => $customer1->id,
            'debit_account_id' => $this->receivablesAccount->id,
            'credit_account_id' => $this->revenueAccount->id,
            'transaction_type' => AccountMapping::TRANSACTION_INVOICE,
        ]);

        // Create customer in company 2
        $customer2 = Customer::factory()->create([
            'company_id' => $company2->id,
        ]);

        AccountMapping::create([
            'company_id' => $company2->id,
            'entity_type' => AccountMapping::ENTITY_CUSTOMER,
            'entity_id' => $customer2->id,
            'debit_account_id' => $company2ReceivablesAccount->id,
            'credit_account_id' => $company2RevenueAccount->id,
            'transaction_type' => AccountMapping::TRANSACTION_INVOICE,
        ]);

        // Test company 1 invoice
        $invoice1 = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer1->id,
        ]);

        $result1 = $this->service->suggestForInvoice($invoice1);
        $this->assertEquals($this->receivablesAccount->id, $result1['debit_account_id']);

        // Test company 2 invoice
        $invoice2 = Invoice::factory()->create([
            'company_id' => $company2->id,
            'customer_id' => $customer2->id,
        ]);

        $result2 = $this->service->suggestForInvoice($invoice2);
        $this->assertEquals($company2ReceivablesAccount->id, $result2['debit_account_id']);
    }
}
// CLAUDE-CHECKPOINT
