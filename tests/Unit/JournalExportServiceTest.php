<?php

namespace Tests\Unit;

use App\Models\Account;
use App\Models\AccountMapping;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\JournalExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for JournalExportService (QA-02)
 *
 * Tests journal export functionality including:
 * - Pantheon XML/CSV format export
 * - Zonel CSV format export
 * - Generic CSV export
 * - Date range filtering
 * - Account code mapping resolution
 * - Graceful handling of missing mappings
 */
class JournalExportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Currency $currency;

    protected Customer $customer;

    protected Account $receivablesAccount;

    protected Account $revenueAccount;

    protected Account $cashAccount;

    protected Account $expenseAccount;

    protected Account $payablesAccount;

    protected Account $taxAccount;

    protected function setUp(): void
    {
        parent::setUp();

        // Create currency
        $this->currency = Currency::factory()->create([
            'code' => 'MKD',
            'symbol' => 'ден',
            'name' => 'Macedonian Denar',
        ]);

        // Create company
        $this->company = Company::factory()->create([
            'name' => 'Test Company',
            'currency_id' => $this->currency->id,
        ]);

        // Create customer
        $this->customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Test Customer',
        ]);

        // Create accounts
        $this->receivablesAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'code' => '220100',
            'name' => 'Accounts Receivable',
            'type' => Account::TYPE_ASSET,
            'is_active' => true,
        ]);

        $this->revenueAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'code' => '660100',
            'name' => 'Sales Revenue',
            'type' => Account::TYPE_REVENUE,
            'is_active' => true,
        ]);

        $this->cashAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'code' => '240100',
            'name' => 'Bank Account',
            'type' => Account::TYPE_ASSET,
            'is_active' => true,
        ]);

        $this->expenseAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'code' => '540100',
            'name' => 'Operating Expenses',
            'type' => Account::TYPE_EXPENSE,
            'is_active' => true,
        ]);

        $this->payablesAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'code' => '220200',
            'name' => 'Accounts Payable',
            'type' => Account::TYPE_LIABILITY,
            'is_active' => true,
        ]);

        $this->taxAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'code' => '270100',
            'name' => 'VAT Payable',
            'type' => Account::TYPE_LIABILITY,
            'is_active' => true,
        ]);
    }

    /**
     * Test 1: Export to Pantheon CSV format
     */
    public function testExportToPantheonFormat(): void
    {
        // Create invoice
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-001',
            'invoice_date' => now()->startOfMonth()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'total' => 10000, // 100.00 MKD
            'sub_total' => 10000,
            'tax' => 0,
            'status' => Invoice::STATUS_SENT,
            'currency_id' => $this->currency->id,
        ]);

        // Debug: Check if invoice was created
        $this->assertNotNull($invoice);
        $this->assertEquals('INV-001', $invoice->invoice_number);

        // Create export service
        $service = new JournalExportService(
            $this->company->id,
            now()->startOfMonth()->format('Y-m-d'),
            now()->endOfMonth()->format('Y-m-d')
        );

        // Export to Pantheon format
        $csv = $service->toPantheonCSV();

        // Assert CSV structure
        $this->assertStringContainsString('Datum;Dokument;Konto;Partner;Opis;Dolg;Potr;Valuta', $csv);
        $this->assertStringContainsString('INV-001', $csv);
        $this->assertStringContainsString('Test Customer', $csv);
        $this->assertStringContainsString('MKD', $csv);

        // Assert delimiter is semicolon
        $lines = explode("\n", trim($csv));
        $this->assertGreaterThan(1, count($lines));
        $this->assertStringContainsString(';', $lines[0]);

        // Assert date format is Macedonian (dd.mm.yyyy)
        $dataLine = $lines[1] ?? '';
        if ($dataLine) {
            $this->assertMatchesRegularExpression('/\d{2}\.\d{2}\.\d{4}/', $dataLine);
        }
    }

    /**
     * Test 2: Export to Zonel CSV format
     */
    public function testExportToZonelFormat(): void
    {
        // Create invoice
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-002',
            'invoice_date' => now()->startOfMonth()->format('Y-m-d'),
            'total' => 20000, // 200.00 MKD
            'sub_total' => 20000,
            'tax' => 0,
            'status' => Invoice::STATUS_SENT,
            'currency_id' => $this->currency->id,
        ]);

        // Create export service
        $service = new JournalExportService(
            $this->company->id,
            now()->startOfMonth()->format('Y-m-d'),
            now()->endOfMonth()->format('Y-m-d')
        );

        // Export to Zonel format
        $csv = $service->toZonelCSV();

        // Assert CSV structure
        $this->assertStringContainsString('DATUM|BROJ_DOK|SIFRA_KONTO|NAZIV_KONTO|OPIS|DOLZUVA|POBARUVA|SIFRA_PARTNER|NAZIV_PARTNER', $csv);
        $this->assertStringContainsString('INV-002', $csv);
        $this->assertStringContainsString('Test Customer', $csv);

        // Assert delimiter is pipe
        $lines = explode("\n", trim($csv));
        $this->assertGreaterThan(1, count($lines));
        $this->assertStringContainsString('|', $lines[0]);

        // Assert date format is Zonel (ddmmyyyy)
        $dataLine = $lines[1] ?? '';
        if ($dataLine) {
            $this->assertMatchesRegularExpression('/\d{8}/', $dataLine);
        }
    }

    /**
     * Test 3: Export to generic CSV format
     */
    public function testExportToGenericCsv(): void
    {
        // Create invoice
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-003',
            'invoice_date' => now()->startOfMonth(),
            'total' => 15000, // 150.00 MKD
            'sub_total' => 15000,
            'tax' => 0,
            'status' => Invoice::STATUS_SENT,
            'currency_id' => $this->currency->id,
        ]);

        // Create export service
        $service = new JournalExportService(
            $this->company->id,
            now()->startOfMonth()->format('Y-m-d'),
            now()->endOfMonth()->format('Y-m-d')
        );

        // Export to generic CSV
        $csv = $service->toCSV();

        // Assert CSV structure
        $this->assertStringContainsString('Date,Reference,Type,Account Code,Account Name,Description,Debit,Credit,Customer/Supplier,Currency', $csv);
        $this->assertStringContainsString('INV-003', $csv);
        $this->assertStringContainsString('Test Customer', $csv);
        $this->assertStringContainsString('invoice', $csv);
        $this->assertStringContainsString('MKD', $csv);

        // Assert it has debit and credit entries
        $this->assertStringContainsString('220100', $csv); // Receivables
        $this->assertStringContainsString('660100', $csv); // Revenue
    }

    /**
     * Test 4: Export filters by date range
     */
    public function testExportFiltersByDateRange(): void
    {
        // Create invoice in current month
        $currentMonthInvoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-CURRENT',
            'invoice_date' => now()->startOfMonth(),
            'total' => 10000,
            'sub_total' => 10000,
            'tax' => 0,
            'status' => Invoice::STATUS_SENT,
            'currency_id' => $this->currency->id,
        ]);

        // Create invoice in last month (should be excluded)
        $lastMonthInvoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-LAST-MONTH',
            'invoice_date' => now()->subMonth()->startOfMonth(),
            'total' => 20000,
            'sub_total' => 20000,
            'tax' => 0,
            'status' => Invoice::STATUS_SENT,
            'currency_id' => $this->currency->id,
        ]);

        // Create export service for current month only
        $service = new JournalExportService(
            $this->company->id,
            now()->startOfMonth()->format('Y-m-d'),
            now()->endOfMonth()->format('Y-m-d')
        );

        // Export to CSV
        $csv = $service->toCSV();

        // Assert current month invoice is included
        $this->assertStringContainsString('INV-CURRENT', $csv);

        // Assert last month invoice is NOT included
        $this->assertStringNotContainsString('INV-LAST-MONTH', $csv);
    }

    /**
     * Test 5: Export includes account codes from mappings
     */
    public function testExportIncludesAccountCodes(): void
    {
        // Create invoice
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-004',
            'invoice_date' => now()->startOfMonth(),
            'total' => 10000,
            'sub_total' => 10000,
            'tax' => 0,
            'status' => Invoice::STATUS_SENT,
            'currency_id' => $this->currency->id,
        ]);

        // Create export service
        $service = new JournalExportService(
            $this->company->id,
            now()->startOfMonth()->format('Y-m-d'),
            now()->endOfMonth()->format('Y-m-d')
        );

        // Get journal entries
        $entries = $service->getJournalEntries();

        // Assert entries have account codes
        $this->assertGreaterThan(0, $entries->count());

        foreach ($entries as $entry) {
            $this->assertArrayHasKey('account_code', $entry);
            $this->assertNotEmpty($entry['account_code']);
            $this->assertArrayHasKey('account_name', $entry);
            $this->assertNotEmpty($entry['account_name']);
        }
    }

    /**
     * Test 6: Export handles missing mappings gracefully
     */
    public function testExportHandlesMissingMappings(): void
    {
        // Create company with no account mappings
        $newCompany = Company::factory()->create([
            'name' => 'New Company',
            'currency_id' => $this->currency->id,
        ]);

        $newCustomer = Customer::factory()->create([
            'company_id' => $newCompany->id,
            'name' => 'New Customer',
        ]);

        // Create invoice without account mappings
        $invoice = Invoice::factory()->create([
            'company_id' => $newCompany->id,
            'customer_id' => $newCustomer->id,
            'invoice_number' => 'INV-NO-MAPPING',
            'invoice_date' => now()->startOfMonth(),
            'total' => 10000,
            'sub_total' => 10000,
            'tax' => 0,
            'status' => Invoice::STATUS_SENT,
            'currency_id' => $this->currency->id,
        ]);

        // Create export service
        $service = new JournalExportService(
            $newCompany->id,
            now()->startOfMonth()->format('Y-m-d'),
            now()->endOfMonth()->format('Y-m-d')
        );

        // Should not throw exception
        $entries = $service->getJournalEntries();
        $this->assertGreaterThan(0, $entries->count());

        // Should fall back to default codes
        foreach ($entries as $entry) {
            $this->assertNotEmpty($entry['account_code']);
            // Default codes should be returned (220100, 660100, etc.)
            $this->assertMatchesRegularExpression('/^\d{6}$/', $entry['account_code']);
        }

        // Should be able to export without errors
        $csv = $service->toCSV();
        $this->assertNotEmpty($csv);
        $this->assertStringContainsString('INV-NO-MAPPING', $csv);
    }

    /**
     * Test: Export includes payments
     */
    public function testExportIncludesPayments(): void
    {
        // Create payment
        $payment = Payment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'payment_number' => 'PAY-001',
            'payment_date' => now()->startOfMonth(),
            'amount' => 5000, // 50.00 MKD
            'currency_id' => $this->currency->id,
        ]);

        // Create export service
        $service = new JournalExportService(
            $this->company->id,
            now()->startOfMonth()->format('Y-m-d'),
            now()->endOfMonth()->format('Y-m-d')
        );

        // Export to CSV
        $csv = $service->toCSV();

        // Assert payment is included
        $this->assertStringContainsString('PAY-001', $csv);
        $this->assertStringContainsString('payment', $csv);
    }

    /**
     * Test: Export includes expenses
     */
    public function testExportIncludesExpenses(): void
    {
        // Create expense category
        $category = ExpenseCategory::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Office Supplies',
        ]);

        // Create expense
        $expense = Expense::factory()->create([
            'company_id' => $this->company->id,
            'expense_category_id' => $category->id,
            'expense_date' => now()->startOfMonth(),
            'amount' => 3000, // 30.00 MKD
            'currency_id' => $this->currency->id,
        ]);

        // Create export service
        $service = new JournalExportService(
            $this->company->id,
            now()->startOfMonth()->format('Y-m-d'),
            now()->endOfMonth()->format('Y-m-d')
        );

        // Export to CSV
        $csv = $service->toCSV();

        // Assert expense is included
        $this->assertStringContainsString('expense', $csv);
        $this->assertStringContainsString('Office Supplies', $csv);
    }

    /**
     * Test: Export summary is balanced
     */
    public function testExportSummaryIsBalanced(): void
    {
        // Create invoice
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-BALANCE',
            'invoice_date' => now()->startOfMonth(),
            'total' => 10000,
            'sub_total' => 10000,
            'tax' => 0,
            'status' => Invoice::STATUS_SENT,
            'currency_id' => $this->currency->id,
        ]);

        // Create export service
        $service = new JournalExportService(
            $this->company->id,
            now()->startOfMonth()->format('Y-m-d'),
            now()->endOfMonth()->format('Y-m-d')
        );

        // Get summary
        $summary = $service->getSummary();

        // Assert summary has required fields
        $this->assertArrayHasKey('total_debit', $summary);
        $this->assertArrayHasKey('total_credit', $summary);
        $this->assertArrayHasKey('is_balanced', $summary);

        // Assert debits equal credits (balanced)
        $this->assertTrue($summary['is_balanced']);
        $this->assertEquals($summary['total_debit'], $summary['total_credit']);
    }
}
// CLAUDE-CHECKPOINT
