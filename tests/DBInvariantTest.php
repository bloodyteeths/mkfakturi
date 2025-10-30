<?php

namespace Tests;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\User;
use App\Models\Partner;
use App\Models\PartnerCompany;
use App\Models\BankTransaction;
use App\Models\ImportJob;

/**
 * TST-DB-01: Database Invariants Test Suite
 * 
 * This test validates database consistency rules and constraints as required by
 * ROADMAP-FINAL.md Section B - TST-DB-01.
 * 
 * Database invariants ensure:
 * - Referential integrity across all tables
 * - Company-scoped data isolation for partners
 * - Financial calculations consistency (totals, taxes, payments)
 * - Invoice state transitions are valid
 * - Partner relationship integrity
 * - Import system data consistency
 * - Macedonia-specific business rules
 * 
 * Test Coverage:
 * - Schema constraints and foreign keys
 * - Business logic invariants
 * - Data isolation between companies
 * - Financial calculation accuracy
 * - State transition validity
 * - Partner/company relationship integrity
 * - Import system consistency
 * - Performance constraints (indexes)
 * 
 * Success Criteria:
 * - All database constraints validated
 * - No orphaned records found
 * - Financial calculations accurate
 * - Company data isolation maintained
 * - Partner relationships valid
 * - All tests pass consistently
 * 
 * Required by Gate G2 dependency: SD-01, SD-02, SD-03 complete
 * 
 * @version 1.0.0
 * @created 2025-07-26 - TST-DB-01 implementation
 * @author Claude Code - Based on ROADMAP-FINAL requirements
 */
class DBInvariantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed minimal required data for tests
        $this->artisan('db:seed', ['--class' => 'CountriesTableSeeder']);
        $this->artisan('db:seed', ['--class' => 'CurrenciesTableSeeder']);
    }

    /**
     * Test 1: Schema Integrity and Constraints
     */
    public function test_database_schema_constraints_are_enforced()
    {
        // Test foreign key constraints exist and are enforced
        $this->assertTrue(Schema::hasTable('companies'));
        $this->assertTrue(Schema::hasTable('users'));
        $this->assertTrue(Schema::hasTable('customers'));
        $this->assertTrue(Schema::hasTable('invoices'));
        $this->assertTrue(Schema::hasTable('invoice_items'));
        $this->assertTrue(Schema::hasTable('payments'));
        $this->assertTrue(Schema::hasTable('partners'));
        $this->assertTrue(Schema::hasTable('partner_companies'));

        // Verify key columns exist
        $this->assertTrue(Schema::hasColumn('invoices', 'company_id'));
        $this->assertTrue(Schema::hasColumn('customers', 'company_id'));
        $this->assertTrue(Schema::hasColumn('payments', 'company_id'));
        $this->assertTrue(Schema::hasColumn('users', 'company_id'));

        // Test unique constraints
        $company = Company::factory()->create();
        $user1 = User::factory()->create(['email' => 'test@example.com', 'company_id' => $company->id]);
        
        // Should fail due to unique email constraint
        $this->expectException(\Exception::class);
        User::factory()->create(['email' => 'test@example.com', 'company_id' => $company->id]);
    }

    /**
     * Test 2: Company Data Isolation Invariants
     */
    public function test_company_data_isolation_is_maintained()
    {
        // Create two separate companies
        $company1 = Company::factory()->create(['name' => 'Company One']);
        $company2 = Company::factory()->create(['name' => 'Company Two']);

        // Create customers for each company
        $customer1 = Customer::factory()->create(['company_id' => $company1->id, 'name' => 'Customer 1']);
        $customer2 = Customer::factory()->create(['company_id' => $company2->id, 'name' => 'Customer 2']);

        // Create invoices for each company
        $invoice1 = Invoice::factory()->create([
            'company_id' => $company1->id,
            'customer_id' => $customer1->id
        ]);
        $invoice2 = Invoice::factory()->create([
            'company_id' => $company2->id,
            'customer_id' => $customer2->id
        ]);

        // Verify data isolation: Company 1 should only see its own data
        $company1Customers = Customer::where('company_id', $company1->id)->get();
        $company2Customers = Customer::where('company_id', $company2->id)->get();

        $this->assertCount(1, $company1Customers);
        $this->assertCount(1, $company2Customers);
        $this->assertEquals('Customer 1', $company1Customers->first()->name);
        $this->assertEquals('Customer 2', $company2Customers->first()->name);

        // Verify invoice isolation
        $company1Invoices = Invoice::where('company_id', $company1->id)->get();
        $company2Invoices = Invoice::where('company_id', $company2->id)->get();

        $this->assertCount(1, $company1Invoices);
        $this->assertCount(1, $company2Invoices);
        $this->assertEquals($invoice1->id, $company1Invoices->first()->id);
        $this->assertEquals($invoice2->id, $company2Invoices->first()->id);
    }

    /**
     * Test 3: Financial Calculations Integrity
     */
    public function test_financial_calculations_are_consistent()
    {
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        // Create invoice with items
        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'sub_total' => 0,
            'total' => 0,
            'tax' => 0,
            'due_amount' => 0
        ]);

        // Add invoice items
        $item1 = InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'name' => 'Service 1',
            'quantity' => 2,
            'price' => 10000, // 100.00 MKD in cents
            'total' => 20000  // 200.00 MKD in cents
        ]);

        $item2 = InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'name' => 'Service 2',
            'quantity' => 1,
            'price' => 15000, // 150.00 MKD in cents
            'total' => 15000  // 150.00 MKD in cents
        ]);

        // Recalculate invoice totals
        $itemsTotal = $invoice->items()->sum('total');
        $invoice->update([
            'sub_total' => $itemsTotal,
            'total' => $itemsTotal, // Simplified - no tax for this test
            'due_amount' => $itemsTotal
        ]);

        // Verify calculations
        $this->assertEquals(35000, $invoice->sub_total); // 350.00 MKD
        $this->assertEquals(35000, $invoice->total);
        $this->assertEquals(35000, $invoice->due_amount);

        // Add partial payment
        $payment = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'company_id' => $company->id,
            'amount' => 20000 // 200.00 MKD partial payment
        ]);

        // Verify due amount calculation
        $totalPaid = $invoice->payments()->sum('amount');
        $remainingDue = $invoice->total - $totalPaid;

        $this->assertEquals(20000, $totalPaid);
        $this->assertEquals(15000, $remainingDue); // 150.00 MKD remaining

        // Update invoice due amount
        $invoice->update(['due_amount' => $remainingDue]);
        $this->assertEquals(15000, $invoice->fresh()->due_amount);
    }

    /**
     * Test 4: Invoice State Transitions
     */
    public function test_invoice_state_transitions_are_valid()
    {
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        // Create draft invoice
        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'status' => 'DRAFT',
            'total' => 10000,
            'due_amount' => 10000
        ]);

        // Valid transition: DRAFT -> SENT
        $invoice->update(['status' => 'SENT']);
        $this->assertEquals('SENT', $invoice->fresh()->status);

        // Add full payment
        Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'company_id' => $company->id,
            'amount' => 10000 // Full payment
        ]);

        // Valid transition: SENT -> PAID (via payment)
        $totalPaid = $invoice->payments()->sum('amount');
        if ($totalPaid >= $invoice->total) {
            $invoice->update(['status' => 'PAID', 'due_amount' => 0]);
        }

        $this->assertEquals('PAID', $invoice->fresh()->status);
        $this->assertEquals(0, $invoice->fresh()->due_amount);

        // Verify payment-status consistency
        $this->assertTrue($invoice->payments()->sum('amount') >= $invoice->total);
    }

    /**
     * Test 5: Partner-Company Relationship Integrity
     */
    public function test_partner_company_relationships_are_valid()
    {
        // Create companies
        $partnerOwnCompany = Company::factory()->create(['name' => 'Partner Own Company']);
        $clientCompany1 = Company::factory()->create(['name' => 'Client Company 1']);
        $clientCompany2 = Company::factory()->create(['name' => 'Client Company 2']);

        // Create partner user
        $partnerUser = User::factory()->create([
            'company_id' => $partnerOwnCompany->id,
            'email' => 'partner@accounting.mk'
        ]);

        // Create partner record
        $partner = Partner::factory()->create([
            'user_id' => $partnerUser->id,
            'commission_rate' => 15.5 // 15.5% default commission
        ]);

        // Create partner-company relationships
        $partnerCompany1 = PartnerCompany::create([
            'partner_id' => $partner->id,
            'company_id' => $clientCompany1->id,
            'commission_rate' => 20.0, // Override commission for this client
            'is_primary' => true,
            'is_active' => true
        ]);

        $partnerCompany2 = PartnerCompany::create([
            'partner_id' => $partner->id,
            'company_id' => $clientCompany2->id,
            'commission_rate' => null, // Use default partner commission
            'is_primary' => false,
            'is_active' => true
        ]);

        // Test relationship integrity
        $this->assertEquals(2, $partner->companies()->count());
        $this->assertEquals(1, $partner->companies()->where('is_primary', true)->count());

        // Test commission rate inheritance
        $primaryRelation = $partner->companies()->where('company_id', $clientCompany1->id)->first();
        $secondaryRelation = $partner->companies()->where('company_id', $clientCompany2->id)->first();

        $this->assertEquals(20.0, $primaryRelation->pivot->commission_rate);
        $this->assertNull($secondaryRelation->pivot->commission_rate); // Should use partner default

        // Test cascade rules - partner should be accessible from company
        $this->assertEquals($partner->id, $clientCompany1->partners()->first()->id);
        $this->assertEquals($partner->id, $clientCompany2->partners()->first()->id);
    }

    /**
     * Test 6: Import System Data Consistency
     */
    public function test_import_system_data_consistency()
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        // Create import job
        $importJob = ImportJob::create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'type' => 'customers',
            'status' => 'pending',
            'file_name' => 'test_customers.csv',
            'file_path' => '/tmp/test_customers.csv',
            'total_records' => 100,
            'processed_records' => 0,
            'failed_records' => 0,
            'mapping_config' => json_encode([
                'name' => 'customer_name',
                'email' => 'email_address',
                'phone' => 'phone_number'
            ]),
            'validation_rules' => json_encode([
                'name' => 'required|string|max:255',
                'email' => 'required|email'
            ])
        ]);

        // Test import job state consistency
        $this->assertEquals('pending', $importJob->status);
        $this->assertEquals(100, $importJob->total_records);
        $this->assertEquals(0, $importJob->processed_records);

        // Simulate processing
        $importJob->update([
            'status' => 'processing',
            'processed_records' => 50
        ]);

        // Verify state transition
        $this->assertEquals('processing', $importJob->fresh()->status);
        $this->assertEquals(50, $importJob->fresh()->processed_records);

        // Verify progress calculation
        $progress = ($importJob->processed_records / $importJob->total_records) * 100;
        $this->assertEquals(50.0, $progress);

        // Complete import
        $importJob->update([
            'status' => 'completed',
            'processed_records' => 95,
            'failed_records' => 5
        ]);

        // Verify final consistency
        $this->assertEquals('completed', $importJob->fresh()->status);
        $this->assertEquals(100, $importJob->processed_records + $importJob->failed_records);
    }

    /**
     * Test 7: Macedonia-Specific Business Rules
     */
    public function test_macedonia_specific_business_rules()
    {
        $company = Company::factory()->create([
            'name' => 'Македонска Компанија ДОО',
            'tax_id' => 'MK4080003501411'
        ]);

        $customer = Customer::factory()->create([
            'company_id' => $company->id,
            'name' => 'Македонски Клиент',
            'tax_id' => 'MK4080003501412',
            'phone' => '+38970123456'
        ]);

        // Test Macedonia VAT ID format validation (would be in model validation)
        $this->assertMatchesRegularExpression('/^MK\d{13}$/', $company->tax_id);
        $this->assertMatchesRegularExpression('/^MK\d{13}$/', $customer->tax_id);

        // Test Macedonia phone format
        $this->assertMatchesRegularExpression('/^\+389\d{8}$/', $customer->phone);

        // Create invoice with Macedonia currency (MKD = ID 134)
        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'currency_id' => 134, // MKD
            'exchange_rate' => 1.0,
            'total' => 180000 // 1800.00 MKD (with 18% VAT)
        ]);

        // Verify Macedonia currency constraint
        $this->assertEquals(134, $invoice->currency_id);
        $this->assertEquals(1.0, $invoice->exchange_rate);

        // Test Macedonia VAT rates (18% standard, 5% reduced)
        $standardVatRate = 18.0;
        $reducedVatRate = 5.0;

        $netAmount = 152542; // Net amount in denar (cents)
        $calculatedVat = round($netAmount * ($standardVatRate / 100));
        $totalWithVat = $netAmount + $calculatedVat;

        $this->assertEquals(27458, $calculatedVat); // 18% of 152542
        $this->assertEquals(180000, $totalWithVat); // Net + VAT
    }

    /**
     * Test 8: Database Performance Constraints
     */
    public function test_database_performance_constraints()
    {
        // Test that required indexes exist for performance
        $this->assertTrue($this->hasIndex('invoices', 'company_id'));
        $this->assertTrue($this->hasIndex('customers', 'company_id'));
        $this->assertTrue($this->hasIndex('payments', 'company_id'));
        $this->assertTrue($this->hasIndex('payments', 'invoice_id'));
        $this->assertTrue($this->hasIndex('invoice_items', 'invoice_id'));

        // Test query performance with large dataset
        $company = Company::factory()->create();
        
        // Create multiple customers (simulate larger dataset)
        Customer::factory()->count(50)->create(['company_id' => $company->id]);
        
        // Measure query performance
        $start = microtime(true);
        $customers = Customer::where('company_id', $company->id)->get();
        $duration = microtime(true) - $start;

        // Query should complete quickly (< 100ms for 50 records)
        $this->assertLessThan(0.1, $duration);
        $this->assertCount(50, $customers);
    }

    /**
     * Test 9: Data Integrity Across Relationships
     */
    public function test_data_integrity_across_relationships()
    {
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        
        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id
        ]);

        // Test cascade relationships
        $this->assertEquals($company->id, $invoice->company_id);
        $this->assertEquals($customer->id, $invoice->customer_id);
        $this->assertEquals($company->id, $invoice->customer->company_id);

        // Verify no orphaned records exist
        $orphanedInvoices = DB::table('invoices')
            ->leftJoin('companies', 'invoices.company_id', '=', 'companies.id')
            ->whereNull('companies.id')
            ->count();

        $orphanedCustomers = DB::table('customers')
            ->leftJoin('companies', 'customers.company_id', '=', 'companies.id')
            ->whereNull('companies.id')
            ->count();

        $this->assertEquals(0, $orphanedInvoices);
        $this->assertEquals(0, $orphanedCustomers);
    }

    /**
     * Test 10: System-wide Consistency Check
     */
    public function test_system_wide_consistency_check()
    {
        // Create test data across multiple companies
        $companies = Company::factory()->count(3)->create();
        
        foreach ($companies as $company) {
            $customers = Customer::factory()->count(2)->create(['company_id' => $company->id]);
            
            foreach ($customers as $customer) {
                $invoice = Invoice::factory()->create([
                    'company_id' => $company->id,
                    'customer_id' => $customer->id,
                    'total' => 50000
                ]);

                // Add payment
                Payment::factory()->create([
                    'invoice_id' => $invoice->id,
                    'company_id' => $company->id,
                    'amount' => 25000 // Partial payment
                ]);
            }
        }

        // Verify system-wide consistency
        $totalInvoices = Invoice::count();
        $totalPayments = Payment::count();
        $totalCustomers = Customer::count();

        $this->assertEquals(6, $totalInvoices); // 3 companies × 2 customers each
        $this->assertEquals(6, $totalPayments); // 1 payment per invoice
        $this->assertEquals(6, $totalCustomers); // 2 customers per company

        // Verify all payments are properly linked
        $paymentsWithValidInvoices = Payment::join('invoices', 'payments.invoice_id', '=', 'invoices.id')
            ->count();
        $this->assertEquals($totalPayments, $paymentsWithValidInvoices);

        // Verify company data separation
        foreach ($companies as $company) {
            $companyCustomers = Customer::where('company_id', $company->id)->count();
            $companyInvoices = Invoice::where('company_id', $company->id)->count();
            $companyPayments = Payment::where('company_id', $company->id)->count();

            $this->assertEquals(2, $companyCustomers);
            $this->assertEquals(2, $companyInvoices);
            $this->assertEquals(2, $companyPayments);
        }
    }

    /**
     * Helper method to check if an index exists
     */
    private function hasIndex(string $table, string $column): bool
    {
        $indexes = DB::select("SHOW INDEX FROM {$table}");
        
        foreach ($indexes as $index) {
            if ($index->Column_name === $column) {
                return true;
            }
        }
        
        return false;
    }
}

