<?php

namespace Tests\Unit\Domain\Accounting;

use App\Domain\Accounting\IfrsAdapter;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use IFRS\Models\Account;
use IFRS\Models\Currency;
use IFRS\Models\Entity;
use IFRS\Models\ReportingPeriod;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Unit tests for IfrsAdapter
 *
 * Tests the core accounting adapter functionality including:
 * - Invoice posting to ledger
 * - Payment posting to ledger
 * - Fee posting to ledger
 * - Feature flag checks
 */
class IfrsAdapterTest extends TestCase
{
    protected IfrsAdapter $adapter;

    protected Company $company;

    protected Customer $customer;

    protected Currency $currency;

    protected Entity $entity;

    protected function setUp(): void
    {
        parent::setUp();

        if (! env('RUN_IFRS_TESTS', false)) {
            $this->markTestSkipped('IFRS integration tests are disabled by default (set RUN_IFRS_TESTS=true to enable).');
        }

        // Ensure core app tables are migrated
        Artisan::call('migrate', ['--force' => true]);

        // Ensure IFRS tables are migrated for these integration-style tests
        Artisan::call('migrate', [
            '--path' => 'vendor/ekmungai/eloquent-ifrs/database/migrations',
            '--force' => true,
        ]);

        if (! Schema::hasTable('ifrs_entities')) {
            $this->markTestSkipped('IFRS tables are not available in this environment.');
        }

        $this->adapter = new IfrsAdapter;

        // Create test entity
        $this->entity = Entity::create([
            'name' => 'Test Company',
            // currency_id will be set after currency creation
        ]);

        // Ensure there is a reporting period for the current year
        ReportingPeriod::firstOrCreate(
            [
                'calendar_year' => (int) date('Y'),
                'entity_id' => $this->entity->id,
            ],
            [
                'period_count' => 1,
                'status' => ReportingPeriod::OPEN,
            ]
        );

        // Create test currency explicitly linked to the IFRS entity
        $this->currency = Currency::create([
            'name' => 'Macedonian Denar',
            'currency_code' => 'MKD',
            'entity_id' => $this->entity->id,
        ]);

        // Create test company
        $this->company = Company::factory()->create([
            'name' => 'Test Company',
            'currency_id' => $this->currency->id,
        ]);

        // Create test customer
        $this->customer = Customer::factory()->create([
            'name' => 'Test Customer',
            'company_id' => $this->company->id,
        ]);
    }

    /** @test */
    public function it_skips_posting_when_feature_is_disabled()
    {
        // Ensure feature is disabled
        config(['ifrs.enabled' => false]);
        putenv('FEATURE_ACCOUNTING_BACKBONE=false');

        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-001',
            'total' => 10000, // 100.00 in cents
            'sub_total' => 10000,
            'tax' => 0,
            'status' => Invoice::STATUS_SENT,
        ]);

        // Should not throw exception
        $this->adapter->postInvoice($invoice);

        // Should not have created IFRS transaction
        $this->assertNull($invoice->fresh()->ifrs_transaction_id);
    }

    /** @test */
    public function it_posts_invoice_to_ledger_when_feature_enabled()
    {
        // Enable feature
        config(['ifrs.enabled' => true]);

        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-001',
            'total' => 12000, // 120.00 in cents
            'sub_total' => 10000, // 100.00 in cents
            'tax' => 2000, // 20.00 in cents
            'status' => Invoice::STATUS_SENT,
            'invoice_date' => now(),
        ]);

        $this->adapter->postInvoice($invoice);

        // Verify invoice has IFRS transaction ID
        $invoice->refresh();
        $this->assertNotNull($invoice->ifrs_transaction_id);

        // Verify accounts were created
        $this->assertDatabaseHas('ifrs_accounts', [
            'name' => 'Accounts Receivable',
            'account_type' => Account::RECEIVABLE,
        ]);

        $this->assertDatabaseHas('ifrs_accounts', [
            'name' => 'Sales Revenue',
            'account_type' => Account::OPERATING_REVENUE,
        ]);

        $this->assertDatabaseHas('ifrs_accounts', [
            'name' => 'Tax Payable',
            'account_type' => Account::CONTROL,
        ]);

        // Verify transaction was created
        $this->assertDatabaseHas('ifrs_transactions', [
            'id' => $invoice->ifrs_transaction_id,
            'narration' => "Invoice #{$invoice->invoice_number} - {$this->customer->name}",
        ]);
    }

    /** @test */
    public function it_posts_payment_to_ledger_when_feature_enabled()
    {
        // Enable feature
        config(['ifrs.enabled' => true]);

        $payment = Payment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'payment_number' => 'PAY-001',
            'amount' => 10000, // 100.00 in cents
            'payment_date' => now(),
            'gateway_status' => Payment::GATEWAY_STATUS_COMPLETED,
        ]);

        $this->adapter->postPayment($payment);

        // Verify payment has IFRS transaction ID
        $payment->refresh();
        $this->assertNotNull($payment->ifrs_transaction_id);

        // Verify accounts were created
        $this->assertDatabaseHas('ifrs_accounts', [
            'name' => 'Cash and Bank',
            'account_type' => Account::BANK,
        ]);

        $this->assertDatabaseHas('ifrs_accounts', [
            'name' => 'Accounts Receivable',
            'account_type' => Account::RECEIVABLE,
        ]);

        // Verify transaction was created
        $this->assertDatabaseHas('ifrs_transactions', [
            'id' => $payment->ifrs_transaction_id,
            'narration' => "Payment #{$payment->payment_number} - {$this->customer->name}",
        ]);
    }

    /** @test */
    public function it_posts_fee_to_ledger_when_feature_enabled()
    {
        // Enable feature
        config(['ifrs.enabled' => true]);

        $payment = Payment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'payment_number' => 'PAY-002',
            'amount' => 10000,
            'payment_date' => now(),
        ]);

        $fee = 250; // 2.50 in cents

        $this->adapter->postFee($payment, $fee);

        // Verify fee expense account was created
        $this->assertDatabaseHas('ifrs_accounts', [
            'name' => 'Payment Processing Fees',
            'account_type' => Account::OPERATING_EXPENSE,
        ]);

        // Verify fee transaction was created
        $this->assertDatabaseHas('ifrs_transactions', [
            'narration' => "Payment processing fee for #{$payment->payment_number}",
        ]);
    }

    /** @test */
    public function it_gets_trial_balance()
    {
        // Enable feature
        config(['ifrs.enabled' => true]);

        // Create some transactions first
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'total' => 10000,
            'sub_total' => 10000,
            'tax' => 0,
            'status' => Invoice::STATUS_SENT,
        ]);

        $this->adapter->postInvoice($invoice);

        $result = $this->adapter->getTrialBalance($this->company);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('date', $result);
        $this->assertArrayHasKey('total_debits', $result);
        $this->assertArrayHasKey('total_credits', $result);
        $this->assertArrayHasKey('is_balanced', $result);
    }

    /** @test */
    public function it_returns_error_when_feature_disabled_for_reports()
    {
        // Disable feature
        config(['ifrs.enabled' => false]);
        putenv('FEATURE_ACCOUNTING_BACKBONE=false');

        $result = $this->adapter->getTrialBalance($this->company);

        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Accounting backbone feature is disabled', $result['error']);
    }
}

// CLAUDE-CHECKPOINT
