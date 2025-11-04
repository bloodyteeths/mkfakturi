<?php

namespace Tests\Feature\Accounting;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Customer;
use App\Models\User;
use App\Models\Currency as AppCurrency;
use App\Domain\Accounting\IfrsAdapter;
use IFRS\Models\Currency as IfrsCurrency;
use IFRS\Models\Entity;
use IFRS\Models\Account;
use IFRS\Models\Transaction;
use IFRS\Models\ReportingPeriod;
use IFRS\Reports\TrialBalance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Multi-tenant Accounting Isolation Tests
 *
 * Tests that IFRS accounting data is properly isolated per company:
 * - Each company has its own IFRS Entity
 * - Accounts are scoped to entities
 * - Transactions are isolated per company
 * - Reports show only company-specific data
 * - Cross-company access is properly denied
 */
class MultiTenantAccountingTest extends TestCase
{
    use RefreshDatabase;

    // Disable Laravel test transactions to avoid conflicts with IFRS package transactions
    protected $connectionsToTransact = [];

    protected Company $company1;
    protected Company $company2;
    protected User $user1;
    protected User $user2;
    protected Customer $customer1;
    protected Customer $customer2;
    protected AppCurrency $appCurrency;
    protected IfrsCurrency $ifrsCurrency;
    protected Entity $entity1;
    protected Entity $entity2;
    protected IfrsAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();

        // Enable accounting feature
        config(['ifrs.enabled' => true]);

        $this->adapter = new IfrsAdapter();

        // Create App Currency for users (separate from IFRS Currency)
        $this->appCurrency = AppCurrency::create([
            'name' => 'Macedonian Denar',
            'code' => 'MKD',
            'symbol' => 'ден',
            'precision' => 2,
            'thousand_separator' => ',',
            'decimal_separator' => '.',
            'swap_rate_multiplier' => 1,
        ]);

        // Create a temporary entity for IFRS currency creation
        $tempEntity = Entity::create([
            'name' => 'Temporary Entity',
        ]);

        // Set entity_id explicitly to avoid Auth::user()->entity error
        $this->ifrsCurrency = new IfrsCurrency([
            'name' => 'Macedonian Denar',
            'currency_code' => 'MKD',
            'entity_id' => $tempEntity->id,
        ]);
        $this->ifrsCurrency->save();

        // Now create proper entities with currency
        // Create Entity 1 for Company 1
        $this->entity1 = Entity::create([
            'name' => 'Test Company 1',
            'currency_id' => $this->ifrsCurrency->id,
            'year_start' => 1,
            'multi_currency' => false,
        ]);

        // Create Entity 2 for Company 2
        $this->entity2 = Entity::create([
            'name' => 'Test Company 2',
            'currency_id' => $this->ifrsCurrency->id,
            'year_start' => 1,
            'multi_currency' => false,
        ]);

        // Delete temp entity
        $tempEntity->delete();

        // Create Company 1 with its entity
        $this->user1 = User::factory()->create(['email' => 'user1@test.com']);
        $this->company1 = Company::factory()->create([
            'name' => 'Test Company 1',
            'currency_id' => $this->appCurrency->id, // App currency for company
            'ifrs_entity_id' => $this->entity1->id,
        ]);
        $this->user1->companies()->attach($this->company1->id);

        // Create Company 2 with its entity
        $this->user2 = User::factory()->create(['email' => 'user2@test.com']);
        $this->company2 = Company::factory()->create([
            'name' => 'Test Company 2',
            'currency_id' => $this->appCurrency->id, // App currency for company
            'ifrs_entity_id' => $this->entity2->id,
        ]);
        $this->user2->companies()->attach($this->company2->id);

        // Create customers for each company
        $this->customer1 = Customer::factory()->create([
            'name' => 'Customer 1',
            'company_id' => $this->company1->id,
        ]);

        $this->customer2 = Customer::factory()->create([
            'name' => 'Customer 2',
            'company_id' => $this->company2->id,
        ]);

        // Create accounts for each entity
        $this->createAccountsForEntity($this->entity1->id);
        $this->createAccountsForEntity($this->entity2->id);

        // Create reporting periods for entities
        $this->createReportingPeriodsForEntity($this->entity1);
        $this->createReportingPeriodsForEntity($this->entity2);
    }

    /**
     * Create reporting periods for an entity (5 years back to 2 years forward)
     */
    protected function createReportingPeriodsForEntity(Entity $entity): void
    {
        $currentYear = now()->year;

        for ($year = $currentYear - 5; $year <= $currentYear + 2; $year++) {
            ReportingPeriod::create([
                'calendar_year' => $year,
                'period_count' => 1,
                'entity_id' => $entity->id,
                'status' => ReportingPeriod::OPEN,
            ]);
        }
    }

    /**
     * Create basic accounts for an entity
     */
    protected function createAccountsForEntity(int $entityId): void
    {
        // Create Accounts Receivable
        Account::create([
            'code' => '1200',
            'name' => 'Accounts Receivable',
            'account_type' => Account::RECEIVABLE,
            'currency_id' => $this->ifrsCurrency->id,
            'entity_id' => $entityId,
        ]);

        // Create Cash Account
        Account::create([
            'code' => '1000',
            'name' => 'Cash and Bank',
            'account_type' => Account::BANK,
            'currency_id' => $this->ifrsCurrency->id,
            'entity_id' => $entityId,
        ]);

        // Create Revenue Account
        Account::create([
            'code' => '4000',
            'name' => 'Sales Revenue',
            'account_type' => Account::OPERATING_REVENUE,
            'currency_id' => $this->ifrsCurrency->id,
            'entity_id' => $entityId,
        ]);

        // Create Tax Payable Account
        Account::create([
            'code' => '2100',
            'name' => 'Tax Payable',
            'account_type' => Account::CONTROL,
            'currency_id' => $this->ifrsCurrency->id,
            'entity_id' => $entityId,
        ]);
    }

    /** @test */
    public function test_accounting_data_is_isolated_per_company()
    {
        // Create invoice for Company 1
        $invoice1 = Invoice::factory()->create([
            'company_id' => $this->company1->id,
            'customer_id' => $this->customer1->id,
            'invoice_number' => 'INV-001',
            'total' => 10000, // 100.00 MKD
            'sub_total' => 10000,
            'tax' => 0,
            'status' => Invoice::STATUS_SENT,
            'invoice_date' => now(),
        ]);

        // Post invoice to ledger for Company 1
        $this->adapter->postInvoice($invoice1);

        // Create invoice for Company 2
        $invoice2 = Invoice::factory()->create([
            'company_id' => $this->company2->id,
            'customer_id' => $this->customer2->id,
            'invoice_number' => 'INV-002',
            'total' => 20000, // 200.00 MKD
            'sub_total' => 20000,
            'tax' => 0,
            'status' => Invoice::STATUS_SENT,
            'invoice_date' => now(),
        ]);

        // Post invoice to ledger for Company 2
        $this->adapter->postInvoice($invoice2);

        // Verify Company 1 has only its own accounts
        $company1Accounts = Account::where('entity_id', $this->entity1->id)->get();
        $this->assertGreaterThan(0, $company1Accounts->count());

        // Verify Company 2 has only its own accounts
        $company2Accounts = Account::where('entity_id', $this->entity2->id)->get();
        $this->assertGreaterThan(0, $company2Accounts->count());

        // Verify no account overlap between entities
        $company1AccountIds = $company1Accounts->pluck('id')->toArray();
        $company2AccountIds = $company2Accounts->pluck('id')->toArray();
        $this->assertEmpty(array_intersect($company1AccountIds, $company2AccountIds));

        // Verify Company 1 transactions are scoped to entity 1
        $invoice1->refresh();
        $this->assertNotNull($invoice1->ifrs_transaction_id);
        $transaction1 = Transaction::find($invoice1->ifrs_transaction_id);
        $this->assertNotNull($transaction1);

        // Get the account for this transaction and verify it belongs to entity 1
        $account1 = Account::find($transaction1->account_id);
        $this->assertEquals($this->entity1->id, $account1->entity_id);

        // Verify Company 2 transactions are scoped to entity 2
        $invoice2->refresh();
        $this->assertNotNull($invoice2->ifrs_transaction_id);
        $transaction2 = Transaction::find($invoice2->ifrs_transaction_id);
        $this->assertNotNull($transaction2);

        // Get the account for this transaction and verify it belongs to entity 2
        $account2 = Account::find($transaction2->account_id);
        $this->assertEquals($this->entity2->id, $account2->entity_id);

        // Verify trial balance for Company 1 shows only Company 1 data
        $trialBalance1 = new TrialBalance((string)now()->year, $this->entity1);
        $this->assertNotNull($trialBalance1);

        // All accounts in trial balance should belong to entity 1
        foreach ($trialBalance1->accounts as $account) {
            $this->assertEquals($this->entity1->id, $account->entity_id,
                "Account {$account->code} should belong to entity 1");
        }

        // Verify trial balance for Company 2 shows only Company 2 data
        $trialBalance2 = new TrialBalance((string)now()->year, $this->entity2);
        $this->assertNotNull($trialBalance2);

        // All accounts in trial balance should belong to entity 2
        foreach ($trialBalance2->accounts as $account) {
            $this->assertEquals($this->entity2->id, $account->entity_id,
                "Account {$account->code} should belong to entity 2");
        }
    }

    /** @test */
    public function test_cross_company_accounting_access_denied()
    {
        // Create invoice for Company 1
        $invoice1 = Invoice::factory()->create([
            'company_id' => $this->company1->id,
            'customer_id' => $this->customer1->id,
            'invoice_number' => 'INV-001',
            'total' => 10000,
            'sub_total' => 10000,
            'tax' => 0,
            'status' => Invoice::STATUS_SENT,
        ]);

        $this->adapter->postInvoice($invoice1);

        // User from Company 2 tries to access Company 1's accounting data
        $response = $this->actingAs($this->user2)
            ->getJson("/api/v1/admin/{$this->company1->id}/accounting/trial-balance");

        // Should be forbidden (403) or not found (404) depending on middleware
        $this->assertTrue(
            in_array($response->status(), [403, 404]),
            "Expected 403 or 404, got {$response->status()}"
        );
    }

    /** @test */
    public function test_company_without_entity_returns_proper_error()
    {
        // Create company without entity
        $companyWithoutEntity = Company::factory()->create([
            'name' => 'Company Without Entity',
            'currency_id' => $this->appCurrency->id,
            'ifrs_entity_id' => null,
        ]);

        $user = User::factory()->create();
        $user->companies()->attach($companyWithoutEntity->id);

        $customer = Customer::factory()->create([
            'name' => 'Test Customer',
            'company_id' => $companyWithoutEntity->id,
        ]);

        // Try to create invoice for company without entity
        $invoice = Invoice::factory()->create([
            'company_id' => $companyWithoutEntity->id,
            'customer_id' => $customer->id,
            'total' => 10000,
            'sub_total' => 10000,
            'tax' => 0,
            'status' => Invoice::STATUS_SENT,
        ]);

        // Posting should handle gracefully (either auto-create entity or log error)
        try {
            $this->adapter->postInvoice($invoice);

            // If it succeeds, verify entity was auto-created
            $companyWithoutEntity->refresh();
            $this->assertNotNull($companyWithoutEntity->ifrs_entity_id);
        } catch (\Exception $e) {
            // If it fails, verify error is logged appropriately
            $this->assertStringContainsString('entity', strtolower($e->getMessage()));
        }
    }

    /** @test */
    public function test_seeder_creates_entity_per_company()
    {
        // Create fresh companies
        $newCompany1 = Company::factory()->create(['name' => 'New Company 1']);
        $newCompany2 = Company::factory()->create(['name' => 'New Company 2']);

        // Run the seeder
        $this->artisan('db:seed', ['--class' => 'MkIfrsSeeder']);

        // Verify each company now has an entity
        $newCompany1->refresh();
        $newCompany2->refresh();

        $this->assertNotNull($newCompany1->ifrs_entity_id);
        $this->assertNotNull($newCompany2->ifrs_entity_id);
        $this->assertNotEquals($newCompany1->ifrs_entity_id, $newCompany2->ifrs_entity_id);

        // Verify each entity has its own chart of accounts
        $entity1Accounts = Account::where('entity_id', $newCompany1->ifrs_entity_id)->get();
        $entity2Accounts = Account::where('entity_id', $newCompany2->ifrs_entity_id)->get();

        $this->assertGreaterThan(0, $entity1Accounts->count());
        $this->assertGreaterThan(0, $entity2Accounts->count());

        // Verify accounts are isolated
        $entity1AccountIds = $entity1Accounts->pluck('id')->toArray();
        $entity2AccountIds = $entity2Accounts->pluck('id')->toArray();
        $this->assertEmpty(array_intersect($entity1AccountIds, $entity2AccountIds));
    }

    /** @test */
    public function test_payments_are_isolated_per_company()
    {
        // Create payment for Company 1
        $payment1 = Payment::factory()->create([
            'company_id' => $this->company1->id,
            'customer_id' => $this->customer1->id,
            'payment_number' => 'PAY-001',
            'amount' => 5000, // 50.00 MKD
            'payment_date' => now(),
            'gateway_status' => Payment::GATEWAY_STATUS_COMPLETED,
        ]);

        $this->adapter->postPayment($payment1);

        // Create payment for Company 2
        $payment2 = Payment::factory()->create([
            'company_id' => $this->company2->id,
            'customer_id' => $this->customer2->id,
            'payment_number' => 'PAY-002',
            'amount' => 8000, // 80.00 MKD
            'payment_date' => now(),
            'gateway_status' => Payment::GATEWAY_STATUS_COMPLETED,
        ]);

        $this->adapter->postPayment($payment2);

        // Verify payment transactions are isolated
        $payment1->refresh();
        $payment2->refresh();

        $this->assertNotNull($payment1->ifrs_transaction_id);
        $this->assertNotNull($payment2->ifrs_transaction_id);

        $transaction1 = Transaction::find($payment1->ifrs_transaction_id);
        $transaction2 = Transaction::find($payment2->ifrs_transaction_id);

        // Verify transactions use accounts from correct entities
        $account1 = Account::find($transaction1->account_id);
        $account2 = Account::find($transaction2->account_id);

        $this->assertEquals($this->entity1->id, $account1->entity_id);
        $this->assertEquals($this->entity2->id, $account2->entity_id);
    }

    /** @test */
    public function test_trial_balance_api_returns_only_company_data()
    {
        // Create transactions for both companies
        $invoice1 = Invoice::factory()->create([
            'company_id' => $this->company1->id,
            'customer_id' => $this->customer1->id,
            'total' => 10000,
            'sub_total' => 10000,
            'tax' => 0,
            'status' => Invoice::STATUS_SENT,
        ]);
        $this->adapter->postInvoice($invoice1);

        $invoice2 = Invoice::factory()->create([
            'company_id' => $this->company2->id,
            'customer_id' => $this->customer2->id,
            'total' => 20000,
            'sub_total' => 20000,
            'tax' => 0,
            'status' => Invoice::STATUS_SENT,
        ]);
        $this->adapter->postInvoice($invoice2);

        // Get trial balance for Company 1
        $trialBalance1 = $this->adapter->getTrialBalance($this->company1);

        // Verify it doesn't contain Company 2's data
        $this->assertArrayHasKey('accounts', $trialBalance1);
        foreach ($trialBalance1['accounts'] as $account) {
            $this->assertEquals($this->entity1->id, $account->entity_id);
        }

        // Get trial balance for Company 2
        $trialBalance2 = $this->adapter->getTrialBalance($this->company2);

        // Verify it doesn't contain Company 1's data
        $this->assertArrayHasKey('accounts', $trialBalance2);
        foreach ($trialBalance2['accounts'] as $account) {
            $this->assertEquals($this->entity2->id, $account->entity_id);
        }
    }
}

// CLAUDE-CHECKPOINT
