<?php

namespace Tests\Unit;

use App\Models\Account;
use App\Models\AccountMapping;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Services\AccountSuggestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for AccountSuggestionService (QA-01)
 *
 * Tests the AI-powered account suggestion functionality including:
 * - Name-based matching
 * - Historical pattern learning
 * - Graceful handling of missing data
 * - Learning updates from confirmations
 * - Company scoping
 */
class AccountSuggestionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AccountSuggestionService $service;

    protected Company $company1;

    protected Company $company2;

    protected User $user;

    protected Customer $customer;

    protected Account $receivablesAccount;

    protected Account $revenueAccount;

    protected Account $expenseAccount;

    protected Account $payablesAccount;

    protected Account $cashAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new AccountSuggestionService;

        // Create companies
        $this->company1 = Company::factory()->create(['name' => 'Test Company 1']);
        $this->company2 = Company::factory()->create(['name' => 'Test Company 2']);

        // Create user
        $this->user = User::factory()->create();

        // Create customer for company 1
        $this->customer = Customer::factory()->create([
            'company_id' => $this->company1->id,
            'name' => 'Test Customer',
        ]);

        // Create accounts for company 1
        $this->receivablesAccount = Account::factory()->create([
            'company_id' => $this->company1->id,
            'code' => '1121',
            'name' => 'Accounts Receivable',
            'type' => Account::TYPE_ASSET,
            'is_active' => true,
        ]);

        $this->revenueAccount = Account::factory()->create([
            'company_id' => $this->company1->id,
            'code' => '4100',
            'name' => 'Sales Revenue',
            'type' => Account::TYPE_REVENUE,
            'is_active' => true,
        ]);

        $this->expenseAccount = Account::factory()->create([
            'company_id' => $this->company1->id,
            'code' => '5200',
            'name' => 'Operating Expenses',
            'type' => Account::TYPE_EXPENSE,
            'is_active' => true,
        ]);

        $this->payablesAccount = Account::factory()->create([
            'company_id' => $this->company1->id,
            'code' => '2110',
            'name' => 'Accounts Payable',
            'type' => Account::TYPE_LIABILITY,
            'is_active' => true,
        ]);

        $this->cashAccount = Account::factory()->create([
            'company_id' => $this->company1->id,
            'code' => '1112',
            'name' => 'Bank Account',
            'type' => Account::TYPE_ASSET,
            'is_active' => true,
        ]);
    }

    /**
     * Test 1: Suggests account based on entity name (customer mapping)
     */
    public function testSuggestsAccountBasedOnEntityName(): void
    {
        // Create explicit customer mapping
        AccountMapping::create([
            'company_id' => $this->company1->id,
            'entity_type' => AccountMapping::ENTITY_CUSTOMER,
            'entity_id' => $this->customer->id,
            'transaction_type' => AccountMapping::TRANSACTION_INVOICE,
            'debit_account_id' => $this->receivablesAccount->id,
            'credit_account_id' => $this->revenueAccount->id,
        ]);

        // Create invoice for this customer
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company1->id,
            'customer_id' => $this->customer->id,
            'total' => 10000,
            'sub_total' => 10000,
            'tax' => 0,
        ]);

        // Get suggestion
        $suggestion = $this->service->suggestForInvoice($invoice);

        // Assert correct accounts are suggested
        $this->assertEquals($this->receivablesAccount->id, $suggestion['debit_account_id']);
        $this->assertEquals($this->revenueAccount->id, $suggestion['credit_account_id']);
        $this->assertEquals(0.95, $suggestion['confidence']); // High confidence for explicit mapping
    }

    /**
     * Test 2: Suggests account based on historical patterns
     */
    public function testSuggestsAccountBasedOnHistory(): void
    {
        // Create a confirmed invoice (historical pattern)
        $historicalInvoice = Invoice::factory()->create([
            'company_id' => $this->company1->id,
            'customer_id' => $this->customer->id,
            'confirmed_debit_account_id' => $this->receivablesAccount->id,
            'confirmed_credit_account_id' => $this->revenueAccount->id,
            'account_confirmed_at' => now()->subDays(5),
            'account_confirmed_by' => $this->user->id,
            'total' => 5000,
            'sub_total' => 5000,
            'tax' => 0,
        ]);

        // Create new invoice for same customer (no explicit mapping)
        $newInvoice = Invoice::factory()->create([
            'company_id' => $this->company1->id,
            'customer_id' => $this->customer->id,
            'total' => 10000,
            'sub_total' => 10000,
            'tax' => 0,
        ]);

        // Get suggestion
        $suggestion = $this->service->suggestForInvoice($newInvoice);

        // Assert it uses historical pattern
        $this->assertEquals($this->receivablesAccount->id, $suggestion['debit_account_id']);
        $this->assertEquals($this->revenueAccount->id, $suggestion['credit_account_id']);
        $this->assertEquals(0.85, $suggestion['confidence']); // High confidence for historical pattern
    }

    /**
     * Test 3: Returns null when no match (graceful handling)
     */
    public function testReturnsNullWhenNoMatch(): void
    {
        // Create company with no accounts
        $emptyCompany = Company::factory()->create(['name' => 'Empty Company']);
        $emptyCustomer = Customer::factory()->create([
            'company_id' => $emptyCompany->id,
            'name' => 'Empty Customer',
        ]);

        // Create invoice with no mappings or accounts
        $invoice = Invoice::factory()->create([
            'company_id' => $emptyCompany->id,
            'customer_id' => $emptyCustomer->id,
            'total' => 10000,
            'sub_total' => 10000,
            'tax' => 0,
        ]);

        // Get suggestion
        $suggestion = $this->service->suggestForInvoice($invoice);

        // Assert null accounts with low confidence
        $this->assertNull($suggestion['debit_account_id']);
        $this->assertNull($suggestion['credit_account_id']);
        $this->assertEquals(0.50, $suggestion['confidence']); // Low confidence
    }

    /**
     * Test 4: Confirm suggestion updates learning
     */
    public function testConfirmSuggestionUpdatesLearning(): void
    {
        // Create invoice
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company1->id,
            'customer_id' => $this->customer->id,
            'total' => 10000,
            'sub_total' => 10000,
            'tax' => 0,
        ]);

        // Confirm suggestion
        $this->service->confirmSuggestion(
            $invoice,
            $this->receivablesAccount->id,
            $this->revenueAccount->id,
            $this->user
        );

        // Assert invoice was updated
        $invoice->refresh();
        $this->assertEquals($this->receivablesAccount->id, $invoice->confirmed_debit_account_id);
        $this->assertEquals($this->revenueAccount->id, $invoice->confirmed_credit_account_id);
        $this->assertNotNull($invoice->account_confirmed_at);
        $this->assertEquals($this->user->id, $invoice->account_confirmed_by);

        // Assert mapping was created
        $mapping = AccountMapping::where('company_id', $this->company1->id)
            ->where('entity_type', AccountMapping::ENTITY_CUSTOMER)
            ->where('entity_id', $this->customer->id)
            ->where('transaction_type', AccountMapping::TRANSACTION_INVOICE)
            ->first();

        $this->assertNotNull($mapping);
        $this->assertEquals($this->receivablesAccount->id, $mapping->debit_account_id);
        $this->assertEquals($this->revenueAccount->id, $mapping->credit_account_id);
    }

    /**
     * Test 5: Suggestions are company-scoped
     */
    public function testSuggestionsAreCompanyScoped(): void
    {
        // Create mapping for company 2
        $company2Account = Account::factory()->create([
            'company_id' => $this->company2->id,
            'code' => '9999',
            'name' => 'Company 2 Account',
            'type' => Account::TYPE_REVENUE,
            'is_active' => true,
        ]);

        $customer2 = Customer::factory()->create([
            'company_id' => $this->company2->id,
            'name' => 'Company 2 Customer',
        ]);

        AccountMapping::create([
            'company_id' => $this->company2->id,
            'entity_type' => AccountMapping::ENTITY_CUSTOMER,
            'entity_id' => $customer2->id,
            'transaction_type' => AccountMapping::TRANSACTION_INVOICE,
            'debit_account_id' => $company2Account->id,
            'credit_account_id' => $company2Account->id,
        ]);

        // Create invoice for company 1
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company1->id,
            'customer_id' => $this->customer->id,
            'total' => 10000,
            'sub_total' => 10000,
            'tax' => 0,
        ]);

        // Get suggestion
        $suggestion = $this->service->suggestForInvoice($invoice);

        // Assert company 2's accounts are NOT suggested
        $this->assertNotEquals($company2Account->id, $suggestion['debit_account_id']);
        $this->assertNotEquals($company2Account->id, $suggestion['credit_account_id']);
    }

    /**
     * Test: Expense suggestion with category mapping
     */
    public function testSuggestsExpenseAccountBasedOnCategory(): void
    {
        // Create expense category
        $category = ExpenseCategory::factory()->create([
            'company_id' => $this->company1->id,
            'name' => 'Office Supplies',
        ]);

        // Create category mapping
        AccountMapping::create([
            'company_id' => $this->company1->id,
            'entity_type' => AccountMapping::ENTITY_EXPENSE_CATEGORY,
            'entity_id' => $category->id,
            'transaction_type' => AccountMapping::TRANSACTION_EXPENSE,
            'debit_account_id' => $this->expenseAccount->id,
            'credit_account_id' => $this->payablesAccount->id,
        ]);

        // Create expense
        $expense = Expense::factory()->create([
            'company_id' => $this->company1->id,
            'expense_category_id' => $category->id,
            'amount' => 5000,
        ]);

        // Get suggestion
        $suggestion = $this->service->suggestForExpense($expense);

        // Assert correct accounts
        $this->assertEquals($this->expenseAccount->id, $suggestion['debit_account_id']);
        $this->assertEquals($this->payablesAccount->id, $suggestion['credit_account_id']);
        $this->assertEquals(0.95, $suggestion['confidence']);
    }

    /**
     * Test: Payment suggestion with payment method mapping
     */
    public function testSuggestsPaymentAccountBasedOnPaymentMethod(): void
    {
        // Create payment method
        $paymentMethod = PaymentMethod::factory()->create([
            'company_id' => $this->company1->id,
            'name' => 'Bank Transfer',
        ]);

        // Create payment method mapping
        AccountMapping::create([
            'company_id' => $this->company1->id,
            'entity_type' => AccountMapping::ENTITY_PAYMENT_METHOD,
            'entity_id' => $paymentMethod->id,
            'transaction_type' => AccountMapping::TRANSACTION_PAYMENT,
            'debit_account_id' => $this->cashAccount->id,
            'credit_account_id' => $this->receivablesAccount->id,
        ]);

        // Create payment
        $payment = Payment::factory()->create([
            'company_id' => $this->company1->id,
            'customer_id' => $this->customer->id,
            'payment_method_id' => $paymentMethod->id,
            'amount' => 5000,
        ]);

        // Get suggestion
        $suggestion = $this->service->suggestForPayment($payment);

        // Assert correct accounts
        $this->assertEquals($this->cashAccount->id, $suggestion['debit_account_id']);
        $this->assertEquals($this->receivablesAccount->id, $suggestion['credit_account_id']);
        $this->assertEquals(0.95, $suggestion['confidence']);
    }
}
// CLAUDE-CHECKPOINT
