<?php

namespace Tests\Feature\Accounting;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Customer;
use App\Models\User;
use IFRS\Models\Currency;
use IFRS\Models\Entity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Integration tests for IFRS Accounting Backbone
 *
 * Tests the full integration including:
 * - Invoice creation triggers ledger posting
 * - Payment creation triggers ledger posting
 * - API endpoints return correct data
 * - Feature flag properly gates functionality
 */
class IfrsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $user;
    protected Customer $customer;
    protected Currency $currency;
    protected Entity $entity;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test currency
        $this->currency = Currency::create([
            'name' => 'Macedonian Denar',
            'currency_code' => 'MKD',
        ]);

        // Create test entity
        $this->entity = Entity::create([
            'name' => 'Test Company',
            'currency_id' => $this->currency->id,
        ]);

        // Create test user and company
        $this->user = User::factory()->create();
        $this->company = Company::factory()->create([
            'name' => 'Test Company',
            'currency_id' => $this->currency->id,
        ]);

        // Associate user with company
        $this->user->companies()->attach($this->company->id);

        // Create test customer
        $this->customer = Customer::factory()->create([
            'name' => 'Test Customer',
            'company_id' => $this->company->id,
        ]);
    }

    /** @test */
    public function it_creates_invoice_and_posts_to_ledger_when_feature_enabled()
    {
        // Enable feature
        config(['ifrs.enabled' => true]);

        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-001',
            'total' => 10000,
            'sub_total' => 10000,
            'tax' => 0,
            'status' => Invoice::STATUS_SENT,
            'invoice_date' => now(),
        ]);

        // Verify invoice was created
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'invoice_number' => 'INV-001',
        ]);

        // Verify IFRS transaction was created via observer
        $invoice->refresh();
        $this->assertNotNull($invoice->ifrs_transaction_id);

        // Verify ledger entries exist
        $this->assertDatabaseHas('ifrs_transactions', [
            'id' => $invoice->ifrs_transaction_id,
        ]);
    }

    /** @test */
    public function it_creates_payment_and_posts_to_ledger_when_feature_enabled()
    {
        // Enable feature
        config(['ifrs.enabled' => true]);

        $payment = Payment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'payment_number' => 'PAY-001',
            'amount' => 10000,
            'payment_date' => now(),
            'gateway_status' => Payment::GATEWAY_STATUS_COMPLETED,
        ]);

        // Verify payment was created
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'payment_number' => 'PAY-001',
        ]);

        // Verify IFRS transaction was created via observer
        $payment->refresh();
        $this->assertNotNull($payment->ifrs_transaction_id);
    }

    /** @test */
    public function trial_balance_api_returns_data_when_feature_enabled()
    {
        // Enable feature
        config(['ifrs.enabled' => true]);

        // Create some transactions
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'total' => 10000,
            'sub_total' => 10000,
            'tax' => 0,
            'status' => Invoice::STATUS_SENT,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/admin/{$this->company->id}/accounting/trial-balance");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'trial_balance' => [
                    'date',
                    'total_debits',
                    'total_credits',
                    'is_balanced',
                ],
            ]);
    }

    /** @test */
    public function balance_sheet_api_returns_data_when_feature_enabled()
    {
        // Enable feature
        config(['ifrs.enabled' => true]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/admin/{$this->company->id}/accounting/balance-sheet");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'balance_sheet' => [
                    'date',
                    'total_assets',
                    'total_liabilities',
                    'total_equity',
                ],
            ]);
    }

    /** @test */
    public function income_statement_api_returns_data_when_feature_enabled()
    {
        // Enable feature
        config(['ifrs.enabled' => true]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/admin/{$this->company->id}/accounting/income-statement?start=2025-01-01&end=2025-12-31");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'income_statement' => [
                    'start_date',
                    'end_date',
                    'total_revenue',
                    'total_expenses',
                    'net_income',
                ],
            ]);
    }

    /** @test */
    public function accounting_apis_return_error_when_feature_disabled()
    {
        // Disable feature
        config(['ifrs.enabled' => false]);
        putenv('FEATURE_ACCOUNTING_BACKBONE=false');

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/admin/{$this->company->id}/accounting/trial-balance");

        $response->assertStatus(403)
            ->assertJson([
                'error' => 'Accounting backbone feature is disabled',
            ]);
    }

    /** @test */
    public function income_statement_requires_date_parameters()
    {
        // Enable feature
        config(['ifrs.enabled' => true]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/admin/{$this->company->id}/accounting/income-statement");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start', 'end']);
    }
}

// CLAUDE-CHECKPOINT
