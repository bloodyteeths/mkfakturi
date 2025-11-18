<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Services\McpDataProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * AI Data Mapping Validation Tests
 *
 * Validates that data flows correctly from database → McpDataProvider → AI prompts
 * Prevents regressions like status vs paid_status field mismatches
 *
 * Critical validations:
 * - Revenue uses paid_status field (not status)
 * - Expenses are aggregated from expenses table (not hardcoded 0)
 * - Payment reconciliation calculates correctly
 * - Trial balance uses correct invoice statuses
 * - AI prompts receive all expected data fields
 */
class AiDataMappingTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $company;

    protected $dataProvider;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed required database tables
        $this->seed(\Database\Seeders\CurrenciesTableSeeder::class);
        $this->seed(\Database\Seeders\CountriesTableSeeder::class);

        // Create test user with ID 1 (factories expect this)
        $this->user = User::factory()->create(['id' => 1]);

        // Create test company and associate with user
        $this->company = Company::factory()->create([
            'name' => 'Test Company',
            'owner_id' => $this->user->id,
        ]);

        // Associate user with company (many-to-many relationship)
        $this->user->companies()->attach($this->company->id);

        // Create default payment method (ID 1 expected by PaymentFactory)
        \App\Models\PaymentMethod::factory()->create([
            'id' => 1,
            'company_id' => $this->company->id,
            'name' => 'Cash',
        ]);

        $this->dataProvider = new McpDataProvider;

        // Set AI provider to null to avoid actual API calls
        Config::set('ai.default_provider', 'null');
    }

    /**
     * Test that McpDataProvider returns correct data structure
     */
    public function test_mcp_data_provider_returns_correct_structure()
    {
        $stats = $this->dataProvider->getCompanyStats($this->company);

        // Verify all required keys exist
        $this->assertArrayHasKey('company_id', $stats);
        $this->assertArrayHasKey('company_name', $stats);
        $this->assertArrayHasKey('revenue', $stats);
        $this->assertArrayHasKey('expenses', $stats);
        $this->assertArrayHasKey('outstanding', $stats);
        $this->assertArrayHasKey('customers', $stats);
        $this->assertArrayHasKey('invoices_count', $stats);
        $this->assertArrayHasKey('payments_received', $stats);
        $this->assertArrayHasKey('payment_variance', $stats);
        $this->assertArrayHasKey('pending_invoices', $stats);
        $this->assertArrayHasKey('overdue_invoices', $stats);
        $this->assertArrayHasKey('draft_invoices', $stats);

        // Verify data types
        $this->assertIsInt($stats['company_id']);
        $this->assertIsString($stats['company_name']);
        $this->assertIsFloat($stats['revenue']);
        $this->assertIsFloat($stats['expenses']);
        $this->assertIsFloat($stats['outstanding']);
        $this->assertIsInt($stats['customers']);
        $this->assertIsInt($stats['invoices_count']);
        $this->assertIsFloat($stats['payments_received']);
        $this->assertIsFloat($stats['payment_variance']);
    }

    /**
     * CRITICAL TEST: Revenue uses paid_status field, not status field
     * This prevents regression of the status vs paid_status bug
     */
    public function test_revenue_uses_correct_paid_status_field()
    {
        $customer = Customer::factory()->create(['company_id' => $this->company->id]);

        // Create invoice with status=COMPLETED and paid_status=PAID
        $paidInvoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'status' => 'COMPLETED',
            'paid_status' => 'PAID',
            'total' => 10000, // 100.00 MKD
            'invoice_date' => now(),
        ]);

        $stats = $this->dataProvider->getCompanyStats($this->company);

        // Revenue should be 10000 because paid_status is PAID
        $this->assertEquals(10000.0, $stats['revenue'],
            'Revenue should count invoice with paid_status=PAID');

        // Create another invoice with status=PAID but paid_status=UNPAID
        $unpaidInvoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'status' => 'PAID', // Misleading status
            'paid_status' => 'UNPAID', // Actual payment status
            'total' => 5000,
            'invoice_date' => now(),
        ]);

        $stats = $this->dataProvider->getCompanyStats($this->company);

        // Revenue should still be 10000, NOT 15000
        $this->assertEquals(10000.0, $stats['revenue'],
            'Revenue should NOT count invoice with paid_status=UNPAID even if status=PAID');
    }

    /**
     * Test that expenses are actually aggregated from database
     * Not hardcoded to 0
     */
    public function test_expenses_are_aggregated_from_database()
    {
        // Create expenses
        Expense::factory()->create([
            'company_id' => $this->company->id,
            'amount' => 1000, // 10.00 MKD
            'expense_date' => now(),
        ]);

        Expense::factory()->create([
            'company_id' => $this->company->id,
            'amount' => 2500, // 25.00 MKD
            'expense_date' => now(),
        ]);

        Expense::factory()->create([
            'company_id' => $this->company->id,
            'amount' => 1500, // 15.00 MKD
            'expense_date' => now(),
        ]);

        $stats = $this->dataProvider->getCompanyStats($this->company);

        // Expenses should sum to 5000 (50.00 MKD)
        $this->assertEquals(5000.0, $stats['expenses'],
            'Expenses should be aggregated from expenses table, not hardcoded to 0');

        // Test with no expenses
        Expense::where('company_id', $this->company->id)->delete();
        $stats = $this->dataProvider->getCompanyStats($this->company);

        $this->assertEquals(0.0, $stats['expenses'],
            'Expenses should be 0 when no expenses exist');
    }

    /**
     * Test payment reconciliation variance calculation
     */
    public function test_payment_reconciliation_calculates_correctly()
    {
        $customer = Customer::factory()->create(['company_id' => $this->company->id]);

        // Scenario 1: Perfect reconciliation
        $invoice1 = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'status' => 'COMPLETED',
            'paid_status' => 'PAID',
            'total' => 10000,
            'invoice_date' => now(),
        ]);

        Payment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'invoice_id' => $invoice1->id,
            'amount' => 10000,
            'payment_date' => now(),
        ]);

        $stats = $this->dataProvider->getCompanyStats($this->company);

        $this->assertEquals(10000.0, $stats['revenue']);
        $this->assertEquals(10000.0, $stats['payments_received']);
        $this->assertEquals(0.0, $stats['payment_variance'],
            'Payment variance should be 0 when payments match revenue');

        // Scenario 2: Partial payment
        $invoice2 = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'status' => 'COMPLETED',
            'paid_status' => 'PARTIALLY_PAID',
            'total' => 5000,
            'invoice_date' => now(),
        ]);

        Payment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'invoice_id' => $invoice2->id,
            'amount' => 3000,
            'payment_date' => now(),
        ]);

        $stats = $this->dataProvider->getCompanyStats($this->company);

        $this->assertEquals(10000.0, $stats['revenue'],
            'Revenue should only count fully paid invoices');
        $this->assertEquals(13000.0, $stats['payments_received']);
        $this->assertEquals(-3000.0, $stats['payment_variance'],
            'Payment variance should be negative when more payments than revenue');
    }

    /**
     * Test trial balance uses correct invoice statuses
     */
    public function test_trial_balance_uses_correct_statuses()
    {
        $customer = Customer::factory()->create(['company_id' => $this->company->id]);

        // Create COMPLETED invoice
        Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'status' => 'COMPLETED',
            'paid_status' => 'PAID',
            'total' => 10000,
            'invoice_date' => now(),
        ]);

        // Create SENT invoice
        Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'status' => 'SENT',
            'paid_status' => 'UNPAID',
            'total' => 5000,
            'invoice_date' => now(),
        ]);

        // Create DRAFT invoice (should not be in trial balance)
        Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'status' => 'DRAFT',
            'paid_status' => 'UNPAID',
            'total' => 3000,
            'invoice_date' => now(),
        ]);

        $balance = $this->dataProvider->getTrialBalance($this->company);

        // Debits should include COMPLETED and SENT (10000 + 5000 = 15000)
        $this->assertEquals(15000.0, $balance['debits'],
            'Debits should include COMPLETED and SENT invoices');

        // Credits should only include PAID invoices (10000)
        $this->assertEquals(10000.0, $balance['credits'],
            'Credits should only include invoices with paid_status=PAID');

        // Balance should be debits - credits (5000)
        $this->assertEquals(5000.0, $balance['balance'],
            'Balance should be debits minus credits');
    }

    /**
     * Test outstanding amount uses paid_status correctly
     */
    public function test_outstanding_amount_uses_paid_status()
    {
        $customer = Customer::factory()->create(['company_id' => $this->company->id]);

        // Create paid invoice - should NOT be in outstanding
        Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'status' => 'COMPLETED',
            'paid_status' => 'PAID',
            'total' => 10000,
            'due_amount' => 0,
            'invoice_date' => now(),
        ]);

        // Create unpaid invoice - SHOULD be in outstanding
        Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'status' => 'SENT',
            'paid_status' => 'UNPAID',
            'total' => 5000,
            'due_amount' => 5000,
            'invoice_date' => now(),
        ]);

        // Create draft invoice - should NOT be in outstanding
        Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'status' => 'DRAFT',
            'paid_status' => 'UNPAID',
            'total' => 3000,
            'due_amount' => 3000,
            'invoice_date' => now(),
        ]);

        $stats = $this->dataProvider->getCompanyStats($this->company);

        $this->assertEquals(5000.0, $stats['outstanding'],
            'Outstanding should only include SENT/COMPLETED invoices with paid_status != PAID');
    }

    /**
     * Test invoice counts by status
     */
    public function test_invoice_counts_by_status()
    {
        $customer = Customer::factory()->create(['company_id' => $this->company->id]);

        // Create 2 draft invoices
        Invoice::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'status' => 'DRAFT',
            'invoice_date' => now(),
        ]);

        // Create 3 sent invoices (not overdue)
        Invoice::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'status' => 'SENT',
            'due_date' => now()->addDays(10),
            'invoice_date' => now(),
        ]);

        // Create 1 overdue invoice
        Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'status' => 'SENT',
            'due_date' => now()->subDays(5),
            'invoice_date' => now()->subDays(10),
        ]);

        // Create 2 completed invoices
        Invoice::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'status' => 'COMPLETED',
            'invoice_date' => now(),
        ]);

        $stats = $this->dataProvider->getCompanyStats($this->company);

        $this->assertEquals(8, $stats['invoices_count'], 'Total invoice count should be 8');
        $this->assertEquals(2, $stats['draft_invoices'], 'Should have 2 draft invoices');
        $this->assertEquals(4, $stats['pending_invoices'], 'Should have 4 SENT invoices (3 + 1 overdue)');
        $this->assertEquals(1, $stats['overdue_invoices'], 'Should have 1 overdue invoice');
    }

    /**
     * Test that AI prompt builder receives all expected fields
     */
    public function test_ai_prompts_receive_all_required_fields()
    {
        $customer = Customer::factory()->create(['company_id' => $this->company->id]);

        // Create sample data
        Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'status' => 'COMPLETED',
            'paid_status' => 'PAID',
            'total' => 20000,
            'invoice_date' => now(),
        ]);

        Expense::factory()->create([
            'company_id' => $this->company->id,
            'amount' => 5000,
            'expense_date' => now(),
        ]);

        Payment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'amount' => 20000,
            'payment_date' => now(),
        ]);

        $stats = $this->dataProvider->getCompanyStats($this->company);
        $trialBalance = $this->dataProvider->getTrialBalance($this->company);

        // Verify stats has all fields needed for prompts
        $requiredStatsFields = [
            'revenue', 'expenses', 'outstanding', 'customers',
            'invoices_count', 'pending_invoices', 'overdue_invoices',
            'draft_invoices', 'payments_received', 'payment_variance',
        ];

        foreach ($requiredStatsFields as $field) {
            $this->assertArrayHasKey($field, $stats,
                "Stats must include {$field} for AI prompts");
        }

        // Verify trial balance structure
        $this->assertArrayHasKey('debits', $trialBalance);
        $this->assertArrayHasKey('credits', $trialBalance);
        $this->assertArrayHasKey('balance', $trialBalance);

        // Verify calculated values are correct
        $this->assertEquals(20000.0, $stats['revenue']);
        $this->assertEquals(5000.0, $stats['expenses']);
        $this->assertEquals(20000.0, $stats['payments_received']);
        $this->assertEquals(0.0, $stats['payment_variance']);
    }

    /**
     * Test searchInvoices includes paid_status field
     */
    public function test_search_invoices_includes_paid_status()
    {
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Test Customer',
        ]);

        Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-001',
            'status' => 'COMPLETED',
            'paid_status' => 'PAID',
            'total' => 10000,
            'due_amount' => 0,
            'invoice_date' => now(),
        ]);

        $invoices = $this->dataProvider->searchInvoices($this->company);

        $this->assertCount(1, $invoices);
        $this->assertArrayHasKey('paid_status', $invoices[0],
            'Search results must include paid_status field');
        $this->assertArrayHasKey('due_amount', $invoices[0],
            'Search results must include due_amount field');
        $this->assertEquals('PAID', $invoices[0]['paid_status']);
        $this->assertEquals('COMPLETED', $invoices[0]['status']);
    }

    /**
     * Test error handling returns correct fallback structure
     */
    public function test_error_fallback_returns_correct_structure()
    {
        // Create a company with invalid ID to trigger error path
        $invalidCompany = new Company;
        $invalidCompany->id = 99999;
        $invalidCompany->name = 'Invalid Company';

        // This should trigger the catch block and return fallback data
        $stats = $this->dataProvider->getCompanyStats($invalidCompany);

        // Verify fallback structure includes all required fields with zeros
        $this->assertEquals(0, $stats['revenue']);
        $this->assertEquals(0, $stats['expenses']);
        $this->assertEquals(0, $stats['outstanding']);
        $this->assertEquals(0, $stats['payments_received']);
        $this->assertEquals(0, $stats['payment_variance']);
    }
}

// CLAUDE-CHECKPOINT
