<?php

namespace Tests\Unit\Domain\Accounting;

use App\Domain\Accounting\IfrsAdapter;
use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Company;
use App\Models\CreditNote;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\Payment;
use App\Models\Supplier;
use App\Models\Unit;
use IFRS\Models\Account;
use IFRS\Models\Currency;
use IFRS\Models\Entity;
use IFRS\Models\ReportingPeriod;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
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

        $this->adapter = app(IfrsAdapter::class);

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

    // ── postBill per-item routing tests ──────────────────────────────

    /** @test */
    public function it_posts_bill_expense_items_to_class_4()
    {
        config(['ifrs.enabled' => true]);

        $supplier = Supplier::factory()->create(['company_id' => $this->company->id]);
        $bill = Bill::factory()->create([
            'company_id' => $this->company->id,
            'supplier_id' => $supplier->id,
            'currency_id' => $this->currency->id,
            'sub_total' => 10000,
            'tax' => 1800,
            'total' => 11800,
            'status' => 'COMPLETED',
            'bill_date' => now(),
        ]);

        // Freeform items (no item_id) — should route to expense
        BillItem::factory()->create([
            'bill_id' => $bill->id,
            'company_id' => $this->company->id,
            'item_id' => null,
            'price' => 5000,
            'quantity' => 1,
            'tax' => 900,
            'total' => 5900,
            'base_price' => 5000,
            'base_tax' => 900,
            'base_total' => 5900,
        ]);
        BillItem::factory()->create([
            'bill_id' => $bill->id,
            'company_id' => $this->company->id,
            'item_id' => null,
            'price' => 5000,
            'quantity' => 1,
            'tax' => 900,
            'total' => 5900,
            'base_price' => 5000,
            'base_tax' => 900,
            'base_total' => 5900,
        ]);

        $this->adapter->postBill($bill);

        $bill->refresh();
        $this->assertNotNull($bill->ifrs_transaction_id);

        // All debits should go to an expense account (OPERATING_EXPENSE)
        $debitLines = DB::table('ifrs_line_items')
            ->where('transaction_id', $bill->ifrs_transaction_id)
            ->where('credited', false)
            ->get();

        // Should have exactly 1 grouped debit line (both items to same expense account)
        $this->assertCount(1, $debitLines);
        $this->assertEquals(100.00, $debitLines->first()->amount);
    }

    /** @test */
    public function it_posts_bill_inventory_items_to_purchase_calculation()
    {
        config(['ifrs.enabled' => true]);

        $supplier = Supplier::factory()->create(['company_id' => $this->company->id]);
        $unit = Unit::factory()->create(['company_id' => $this->company->id]);
        $item = Item::factory()->create([
            'company_id' => $this->company->id,
            'currency_id' => $this->currency->id,
            'unit_id' => $unit->id,
            'track_quantity' => true,
        ]);

        $bill = Bill::factory()->create([
            'company_id' => $this->company->id,
            'supplier_id' => $supplier->id,
            'currency_id' => $this->currency->id,
            'sub_total' => 20000,
            'tax' => 3600,
            'total' => 23600,
            'status' => 'COMPLETED',
            'bill_date' => now(),
        ]);

        BillItem::factory()->create([
            'bill_id' => $bill->id,
            'company_id' => $this->company->id,
            'item_id' => $item->id,
            'price' => 10000,
            'quantity' => 2,
            'tax' => 3600,
            'total' => 23600,
            'base_price' => 10000,
            'base_tax' => 3600,
            'base_total' => 23600,
        ]);

        $this->adapter->postBill($bill);

        $bill->refresh();
        $this->assertNotNull($bill->ifrs_transaction_id);

        // The debit should go to purchase calculation account (303), not expense
        $debitLines = DB::table('ifrs_line_items')
            ->where('transaction_id', $bill->ifrs_transaction_id)
            ->where('credited', false)
            ->get();

        // Filter to the main debit (not VAT)
        $mainDebit = $debitLines->first();
        $ifrsAccount = Account::find($mainDebit->account_id);

        // Should be CURRENT_ASSET (purchase calculation 303), not OPERATING_EXPENSE
        $this->assertEquals(Account::CURRENT_ASSET, $ifrsAccount->account_type);
        $this->assertEquals(200.00, $mainDebit->amount);
    }

    /** @test */
    public function it_posts_bill_mixed_items_to_separate_accounts()
    {
        config(['ifrs.enabled' => true]);

        $supplier = Supplier::factory()->create(['company_id' => $this->company->id]);
        $unit = Unit::factory()->create(['company_id' => $this->company->id]);
        $inventoryItem = Item::factory()->create([
            'company_id' => $this->company->id,
            'currency_id' => $this->currency->id,
            'unit_id' => $unit->id,
            'track_quantity' => true,
        ]);

        $bill = Bill::factory()->create([
            'company_id' => $this->company->id,
            'supplier_id' => $supplier->id,
            'currency_id' => $this->currency->id,
            'sub_total' => 15000,
            'tax' => 2700,
            'total' => 17700,
            'status' => 'COMPLETED',
            'bill_date' => now(),
        ]);

        // Inventory item — 10,000 net
        BillItem::factory()->create([
            'bill_id' => $bill->id,
            'company_id' => $this->company->id,
            'item_id' => $inventoryItem->id,
            'price' => 10000,
            'quantity' => 1,
            'tax' => 1800,
            'total' => 11800,
            'base_price' => 10000,
            'base_tax' => 1800,
            'base_total' => 11800,
        ]);

        // Freeform service item — 5,000 net
        BillItem::factory()->create([
            'bill_id' => $bill->id,
            'company_id' => $this->company->id,
            'item_id' => null,
            'price' => 5000,
            'quantity' => 1,
            'tax' => 900,
            'total' => 5900,
            'base_price' => 5000,
            'base_tax' => 900,
            'base_total' => 5900,
        ]);

        $this->adapter->postBill($bill);

        $bill->refresh();
        $this->assertNotNull($bill->ifrs_transaction_id);

        // Should have TWO non-VAT debit lines (one per account type)
        $debitLines = DB::table('ifrs_line_items')
            ->where('transaction_id', $bill->ifrs_transaction_id)
            ->where('credited', false)
            ->get();

        // Collect account types from IFRS
        $accountTypes = $debitLines->map(function ($line) {
            return Account::find($line->account_id)->account_type;
        })->unique()->values()->toArray();

        sort($accountTypes);

        // Should have both CURRENT_ASSET (303) and OPERATING_EXPENSE (4xx)
        $this->assertContains(Account::CURRENT_ASSET, $accountTypes);
        $this->assertContains(Account::OPERATING_EXPENSE, $accountTypes);

        // Total debits should equal sub_total
        $totalDebits = $debitLines->sum('amount');
        $this->assertEquals(150.00, $totalDebits);
    }

    /** @test */
    public function it_posts_bill_with_no_items_to_expense_fallback()
    {
        config(['ifrs.enabled' => true]);

        $supplier = Supplier::factory()->create(['company_id' => $this->company->id]);
        $bill = Bill::factory()->create([
            'company_id' => $this->company->id,
            'supplier_id' => $supplier->id,
            'currency_id' => $this->currency->id,
            'sub_total' => 5000,
            'tax' => 0,
            'total' => 5000,
            'status' => 'COMPLETED',
            'bill_date' => now(),
        ]);

        // No items — empty bill
        $this->adapter->postBill($bill);

        $bill->refresh();
        $this->assertNotNull($bill->ifrs_transaction_id);

        // Should still post full sub_total to expense account
        $debitLines = DB::table('ifrs_line_items')
            ->where('transaction_id', $bill->ifrs_transaction_id)
            ->where('credited', false)
            ->get();

        $this->assertCount(1, $debitLines);
        $this->assertEquals(50.00, $debitLines->first()->amount);
    }

    // ── postCreditNote VAT tests ──────────────────────────────────

    /** @test */
    public function it_posts_credit_note_with_rate_specific_vat()
    {
        config(['ifrs.enabled' => true]);

        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-CN-001',
            'total' => 11800,
            'sub_total' => 10000,
            'tax' => 1800,
            'status' => Invoice::STATUS_SENT,
            'invoice_date' => now(),
        ]);

        $creditNote = CreditNote::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'invoice_id' => $invoice->id,
            'credit_note_number' => 'CN-001',
            'credit_note_date' => now(),
            'total' => 11800,
            'sub_total' => 10000,
            'tax' => 1800,
            'status' => 'SENT',
        ]);

        // Add tax record with 18% rate
        $creditNote->taxes()->create([
            'company_id' => $this->company->id,
            'name' => 'DDV 18%',
            'percent' => 18,
            'amount' => 1800,
            'base_amount' => 1800,
            'exchange_rate' => 1,
            'currency_id' => $this->currency->id,
        ]);

        $this->adapter->postCreditNote($creditNote);

        $creditNote->refresh();
        $this->assertNotNull($creditNote->ifrs_transaction_id);

        // VAT line item should exist as a debit (reducing output VAT liability)
        $vatLines = DB::table('ifrs_line_items')
            ->where('transaction_id', $creditNote->ifrs_transaction_id)
            ->where('credited', false)
            ->get();

        // Should have at least 2 debit lines: revenue reversal + VAT reversal
        $this->assertGreaterThanOrEqual(2, $vatLines->count());

        // Check that a VAT account exists (should be rate-specific, not generic 230)
        $vatAccountIds = $vatLines->pluck('account_id')->toArray();
        $vatAccounts = Account::whereIn('id', $vatAccountIds)->get();

        $hasControlAccount = $vatAccounts->contains(function ($acc) {
            return $acc->account_type === Account::CONTROL;
        });
        $this->assertTrue($hasControlAccount, 'Credit note should have a VAT CONTROL account debit');
    }
}

