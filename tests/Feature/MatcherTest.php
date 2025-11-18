<?php

use App\Models\BankAccount;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Mk\Services\Matcher;

/**
 * Matcher Service Test Suite
 *
 * Tests for invoice-transaction matching service
 * Covers all scenarios: amount matching, date proximity, reference matching,
 * confidence scoring, payment creation, and edge cases
 *
 * Target: All scenarios coverage as per ROADMAP2.md
 */
describe('Matcher Service', function () {

    beforeEach(function () {
        // Clear relevant tables
        DB::table('bank_transactions')->truncate();
        DB::table('payments')->truncate();
        DB::table('invoices')->truncate();
        DB::table('customers')->truncate();

        // Create test data
        $this->company = Company::factory()->create();
        $this->currency = Currency::factory()->create(['code' => 'MKD']);
        $this->customer = Customer::factory()->create(['company_id' => $this->company->id]);

        // Create bank account for transactions
        $this->bankAccount = BankAccount::factory()->create([
            'company_id' => $this->company->id,
            'currency_id' => $this->currency->id,
        ]);

        $this->matcher = new Matcher($this->company->id);
    });

    describe('Constructor', function () {
        it('can be instantiated with default parameters', function () {
            $matcher = new Matcher($this->company->id);
            expect($matcher)->toBeInstanceOf(Matcher::class);
        });

        it('can be instantiated with custom parameters', function () {
            $matcher = new Matcher($this->company->id, 14, 0.05);
            expect($matcher)->toBeInstanceOf(Matcher::class);
        });
    });

    describe('Amount Matching', function () {
        it('perfectly matches exact amounts', function () {
            // Create invoice and transaction with exact same amount
            $invoice = Invoice::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $this->customer->id,
                'currency_id' => $this->currency->id,
                'status' => 'SENT',
                'total' => 1000.00,
                'due_date' => now()->addDays(5),
                'invoice_number' => 'INV-2025-001',
            ]);

            DB::table('bank_transactions')->insert([
                'bank_account_id' => $this->bankAccount->id,
                'company_id' => $this->company->id,
                'external_reference' => 'TXN-12345',
                'amount' => 1000.00,
                'currency' => 'MKD',
                'description' => 'Payment for INV-2025-001',
                'transaction_date' => now()->addDays(6),
                'matched_invoice_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $matches = $this->matcher->matchAllTransactions();

            expect($matches)->toHaveCount(1);
            expect($matches[0]['invoice_id'])->toBe($invoice->id);
            expect($matches[0]['amount'])->toBe(1000.00);
            expect($matches[0]['confidence'])->toBeGreaterThan(90.0);
        });

        it('matches amounts within tolerance', function () {
            $invoice = Invoice::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $this->customer->id,
                'currency_id' => $this->currency->id,
                'status' => 'SENT',
                'total' => 1000.00,
                'due_date' => now()->addDays(5),
                'invoice_number' => 'INV-2025-002',
            ]);

            // Transaction with 0.5% difference (within 1% default tolerance)
            DB::table('bank_transactions')->insert([
                'bank_account_id' => $this->bankAccount->id,
                'company_id' => $this->company->id,
                'external_reference' => 'TXN-12346',
                'amount' => 1005.00, // 0.5% more than invoice
                'currency' => 'MKD',
                'description' => 'Payment for INV-2025-002',
                'transaction_date' => now()->addDays(6),
                'matched_invoice_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $matches = $this->matcher->matchAllTransactions();

            expect($matches)->toHaveCount(1);
            expect($matches[0]['confidence'])->toBeGreaterThan(80.0);
        });

        it('rejects amounts outside tolerance', function () {
            $invoice = Invoice::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $this->customer->id,
                'currency_id' => $this->currency->id,
                'status' => 'SENT',
                'total' => 1000.00,
                'due_date' => now()->addDays(5),
                'invoice_number' => 'INV-2025-003',
            ]);

            // Transaction with 15% difference (outside reasonable tolerance)
            DB::table('bank_transactions')->insert([
                'bank_account_id' => $this->bankAccount->id,
                'company_id' => $this->company->id,
                'external_reference' => 'TXN-12347',
                'amount' => 1150.00, // 15% more than invoice
                'currency' => 'MKD',
                'description' => 'Payment for something else',
                'transaction_date' => now()->addDays(6),
                'matched_invoice_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $matches = $this->matcher->matchAllTransactions();

            expect($matches)->toHaveCount(0);
        });
    });

    describe('Date Proximity Matching', function () {
        it('gives high score for same-day payments', function () {
            $dueDate = now()->addDays(5);

            $invoice = Invoice::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $this->customer->id,
                'currency_id' => $this->currency->id,
                'status' => 'SENT',
                'total' => 500.00,
                'due_date' => $dueDate,
                'invoice_number' => 'INV-2025-004',
            ]);

            DB::table('bank_transactions')->insert([
                'bank_account_id' => $this->bankAccount->id,
                'company_id' => $this->company->id,
                'external_reference' => 'TXN-12348',
                'amount' => 500.00,
                'currency' => 'MKD',
                'description' => 'Payment for INV-2025-004',
                'transaction_date' => $dueDate, // Same day as due date
                'matched_invoice_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $matches = $this->matcher->matchAllTransactions();

            expect($matches)->toHaveCount(1);
            expect($matches[0]['confidence'])->toBeGreaterThan(95.0);
        });

        it('gives lower score for distant dates', function () {
            $dueDate = now()->addDays(5);

            $invoice = Invoice::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $this->customer->id,
                'currency_id' => $this->currency->id,
                'status' => 'SENT',
                'total' => 500.00,
                'due_date' => $dueDate,
                'invoice_number' => 'INV-2025-005',
            ]);

            DB::table('bank_transactions')->insert([
                'bank_account_id' => $this->bankAccount->id,
                'company_id' => $this->company->id,
                'external_reference' => 'TXN-12349',
                'amount' => 500.00,
                'currency' => 'MKD',
                'description' => 'Payment for INV-2025-005',
                'transaction_date' => $dueDate->copy()->addDays(10), // 10 days after due date
                'matched_invoice_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $matches = $this->matcher->matchAllTransactions();

            expect($matches)->toHaveCount(1);
            expect($matches[0]['confidence'])->toBeLessThan(80.0);
        });
    });

    describe('Reference Matching', function () {
        it('matches invoice number in transaction description', function () {
            $invoice = Invoice::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $this->customer->id,
                'currency_id' => $this->currency->id,
                'status' => 'SENT',
                'total' => 750.00,
                'due_date' => now()->addDays(5),
                'invoice_number' => 'INV-2025-006',
            ]);

            DB::table('bank_transactions')->insert([
                'bank_account_id' => $this->bankAccount->id,
                'company_id' => $this->company->id,
                'external_reference' => 'TXN-12350',
                'amount' => 750.00,
                'currency' => 'MKD',
                'description' => 'Payment for invoice INV-2025-006',
                'transaction_date' => now()->addDays(6),
                'matched_invoice_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $matches = $this->matcher->matchAllTransactions();

            expect($matches)->toHaveCount(1);
            expect($matches[0]['confidence'])->toBeGreaterThan(95.0);
        });

        it('matches invoice number in remittance information', function () {
            $invoice = Invoice::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $this->customer->id,
                'currency_id' => $this->currency->id,
                'status' => 'SENT',
                'total' => 600.00,
                'due_date' => now()->addDays(5),
                'invoice_number' => 'INV-2025-007',
            ]);

            DB::table('bank_transactions')->insert([
                'bank_account_id' => $this->bankAccount->id,
                'company_id' => $this->company->id,
                'external_reference' => 'TXN-12351',
                'amount' => 600.00,
                'currency' => 'MKD',
                'description' => 'Bank transfer',
                'remittance_info' => 'Ref: INV-2025-007',
                'transaction_date' => now()->addDays(6),
                'matched_invoice_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $matches = $this->matcher->matchAllTransactions();

            expect($matches)->toHaveCount(1);
            expect($matches[0]['confidence'])->toBeGreaterThan(95.0);
        });

        it('matches partial invoice number (last 4 digits)', function () {
            $invoice = Invoice::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $this->customer->id,
                'currency_id' => $this->currency->id,
                'status' => 'SENT',
                'total' => 400.00,
                'due_date' => now()->addDays(5),
                'invoice_number' => 'INV-2025-1234',
            ]);

            DB::table('bank_transactions')->insert([
                'bank_account_id' => $this->bankAccount->id,
                'company_id' => $this->company->id,
                'external_reference' => 'TXN-12352',
                'amount' => 400.00,
                'currency' => 'MKD',
                'description' => 'Payment ref 1234',
                'transaction_date' => now()->addDays(6),
                'matched_invoice_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $matches = $this->matcher->matchAllTransactions();

            expect($matches)->toHaveCount(1);
            expect($matches[0]['confidence'])->toBeGreaterThan(75.0);
        });

        it('matches customer name in creditor field', function () {
            $customer = Customer::factory()->create([
                'company_id' => $this->company->id,
                'name' => 'Acme Corporation Ltd',
            ]);

            $invoice = Invoice::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $customer->id,
                'currency_id' => $this->currency->id,
                'status' => 'SENT',
                'total' => 300.00,
                'due_date' => now()->addDays(5),
                'invoice_number' => 'INV-2025-008',
            ]);

            DB::table('bank_transactions')->insert([
                'bank_account_id' => $this->bankAccount->id,
                'company_id' => $this->company->id,
                'external_reference' => 'TXN-12353',
                'amount' => 300.00,
                'currency' => 'MKD',
                'description' => 'Payment received',
                'creditor_name' => 'ACME CORPORATION LTD',
                'transaction_date' => now()->addDays(6),
                'matched_invoice_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $matches = $this->matcher->matchAllTransactions();

            expect($matches)->toHaveCount(1);
            expect($matches[0]['confidence'])->toBeGreaterThan(70.0);
        });
    });

    describe('Payment Creation', function () {
        it('creates payment record and updates invoice status', function () {
            $invoice = Invoice::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $this->customer->id,
                'currency_id' => $this->currency->id,
                'status' => 'SENT',
                'total' => 800.00,
                'due_date' => now()->addDays(5),
                'invoice_number' => 'INV-2025-009',
            ]);

            DB::table('bank_transactions')->insert([
                'id' => 123,
                'bank_account_id' => $this->bankAccount->id,
                'company_id' => $this->company->id,
                'external_reference' => 'TXN-12354',
                'amount' => 800.00,
                'currency' => 'MKD',
                'description' => 'Payment for INV-2025-009',
                'transaction_date' => now()->addDays(6),
                'matched_invoice_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $matches = $this->matcher->matchAllTransactions();

            expect($matches)->toHaveCount(1);

            // Verify payment was created
            $payment = Payment::where('invoice_id', $invoice->id)->first();
            expect($payment)->not->toBeNull();
            expect($payment->amount)->toBe(800.00);
            expect($payment->payment_method)->toBe('bank_transfer');
            expect($payment->reference)->toBe('TXN-12354');

            // Verify invoice status was updated
            $invoice->refresh();
            expect($invoice->status)->toBe('PAID');
            expect($invoice->paid_status)->toBe(Payment::STATUS_COMPLETED);

            // Verify transaction was marked as matched
            $transaction = DB::table('bank_transactions')->where('id', 123)->first();
            expect($transaction->matched_invoice_id)->toBe($invoice->id);
            expect($transaction->matched_payment_id)->toBe($payment->id);
            expect($transaction->matched_at)->not->toBeNull();
        });

        it('generates unique payment numbers', function () {
            // Create two invoices and transactions
            $invoice1 = Invoice::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $this->customer->id,
                'currency_id' => $this->currency->id,
                'status' => 'SENT',
                'total' => 100.00,
                'due_date' => now()->addDays(5),
                'invoice_number' => 'INV-2025-010',
            ]);

            $invoice2 = Invoice::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $this->customer->id,
                'currency_id' => $this->currency->id,
                'status' => 'SENT',
                'total' => 200.00,
                'due_date' => now()->addDays(5),
                'invoice_number' => 'INV-2025-011',
            ]);

            DB::table('bank_transactions')->insert([
                [
                    'bank_account_id' => $this->bankAccount->id,
                    'company_id' => $this->company->id,
                    'external_reference' => 'TXN-A',
                    'amount' => 100.00,
                    'currency' => 'MKD',
                    'description' => 'Payment for INV-2025-010',
                    'transaction_date' => now()->addDays(6),
                    'matched_invoice_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'bank_account_id' => $this->bankAccount->id,
                    'company_id' => $this->company->id,
                    'external_reference' => 'TXN-B',
                    'amount' => 200.00,
                    'currency' => 'MKD',
                    'description' => 'Payment for INV-2025-011',
                    'transaction_date' => now()->addDays(7),
                    'matched_invoice_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);

            $matches = $this->matcher->matchAllTransactions();

            expect($matches)->toHaveCount(2);

            $payments = Payment::whereIn('invoice_id', [$invoice1->id, $invoice2->id])->get();
            expect($payments)->toHaveCount(2);

            // Check that payment numbers are unique
            $paymentNumbers = $payments->pluck('payment_number')->toArray();
            expect(count($paymentNumbers))->toBe(count(array_unique($paymentNumbers)));
        });
    });

    describe('Edge Cases and Error Handling', function () {
        it('skips already matched transactions', function () {
            $invoice = Invoice::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $this->customer->id,
                'currency_id' => $this->currency->id,
                'status' => 'SENT',
                'total' => 500.00,
                'due_date' => now()->addDays(5),
                'invoice_number' => 'INV-2025-012',
            ]);

            // Create already matched transaction
            DB::table('bank_transactions')->insert([
                'bank_account_id' => $this->bankAccount->id,
                'company_id' => $this->company->id,
                'external_reference' => 'TXN-MATCHED',
                'amount' => 500.00,
                'currency' => 'MKD',
                'description' => 'Payment for INV-2025-012',
                'transaction_date' => now()->addDays(6),
                'matched_invoice_id' => $invoice->id, // Already matched
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $matches = $this->matcher->matchAllTransactions();

            expect($matches)->toHaveCount(0);
        });

        it('skips negative amount transactions', function () {
            $invoice = Invoice::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $this->customer->id,
                'currency_id' => $this->currency->id,
                'status' => 'SENT',
                'total' => 500.00,
                'due_date' => now()->addDays(5),
                'invoice_number' => 'INV-2025-013',
            ]);

            // Create negative amount transaction (outgoing payment)
            DB::table('bank_transactions')->insert([
                'bank_account_id' => $this->bankAccount->id,
                'company_id' => $this->company->id,
                'external_reference' => 'TXN-NEGATIVE',
                'amount' => -500.00, // Negative amount
                'currency' => 'MKD',
                'description' => 'Outgoing payment',
                'transaction_date' => now()->addDays(6),
                'matched_invoice_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $matches = $this->matcher->matchAllTransactions();

            expect($matches)->toHaveCount(0);
        });

        it('skips old transactions outside matching window', function () {
            $invoice = Invoice::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $this->customer->id,
                'currency_id' => $this->currency->id,
                'status' => 'SENT',
                'total' => 500.00,
                'due_date' => now()->addDays(5),
                'invoice_number' => 'INV-2025-014',
            ]);

            // Create old transaction (outside 7-day default window)
            DB::table('bank_transactions')->insert([
                'bank_account_id' => $this->bankAccount->id,
                'company_id' => $this->company->id,
                'external_reference' => 'TXN-OLD',
                'amount' => 500.00,
                'currency' => 'MKD',
                'description' => 'Payment for INV-2025-014',
                'transaction_date' => now()->subDays(10), // 10 days ago
                'matched_invoice_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $matches = $this->matcher->matchAllTransactions();

            expect($matches)->toHaveCount(0);
        });

        it('skips invoices that are not SENT status', function () {
            $paidInvoice = Invoice::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $this->customer->id,
                'currency_id' => $this->currency->id,
                'status' => 'PAID', // Already paid
                'total' => 500.00,
                'due_date' => now()->addDays(5),
                'invoice_number' => 'INV-2025-015',
            ]);

            DB::table('bank_transactions')->insert([
                'bank_account_id' => $this->bankAccount->id,
                'company_id' => $this->company->id,
                'external_reference' => 'TXN-PAID-INVOICE',
                'amount' => 500.00,
                'currency' => 'MKD',
                'description' => 'Payment for INV-2025-015',
                'transaction_date' => now()->addDays(6),
                'matched_invoice_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $matches = $this->matcher->matchAllTransactions();

            expect($matches)->toHaveCount(0);
        });

        it('prevents duplicate matching of same invoice', function () {
            $invoice = Invoice::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $this->customer->id,
                'currency_id' => $this->currency->id,
                'status' => 'SENT',
                'total' => 1000.00,
                'due_date' => now()->addDays(5),
                'invoice_number' => 'INV-2025-016',
            ]);

            // Create two transactions that could match the same invoice
            DB::table('bank_transactions')->insert([
                [
                    'bank_account_id' => $this->bankAccount->id,
                    'company_id' => $this->company->id,
                    'external_reference' => 'TXN-FIRST',
                    'amount' => 1000.00,
                    'currency' => 'MKD',
                    'description' => 'Payment for INV-2025-016',
                    'transaction_date' => now()->addDays(6),
                    'matched_invoice_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'bank_account_id' => $this->bankAccount->id,
                    'company_id' => $this->company->id,
                    'external_reference' => 'TXN-SECOND',
                    'amount' => 1000.00,
                    'currency' => 'MKD',
                    'description' => 'Another payment for INV-2025-016',
                    'transaction_date' => now()->addDays(7),
                    'matched_invoice_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);

            $matches = $this->matcher->matchAllTransactions();

            // Should only match one transaction to the invoice
            expect($matches)->toHaveCount(1);

            // Invoice should only have one payment
            $payments = Payment::where('invoice_id', $invoice->id)->count();
            expect($payments)->toBe(1);
        });

        it('handles database errors gracefully during payment creation', function () {
            $invoice = Invoice::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $this->customer->id,
                'currency_id' => $this->currency->id,
                'status' => 'SENT',
                'total' => 500.00,
                'due_date' => now()->addDays(5),
                'invoice_number' => 'INV-2025-017',
            ]);

            DB::table('bank_transactions')->insert([
                'bank_account_id' => $this->bankAccount->id,
                'company_id' => $this->company->id,
                'external_reference' => 'TXN-ERROR',
                'amount' => 500.00,
                'currency' => 'MKD',
                'description' => 'Payment for INV-2025-017',
                'transaction_date' => now()->addDays(6),
                'matched_invoice_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Mock Payment::create to throw an exception
            $this->mock(Payment::class, function ($mock) {
                $mock->shouldReceive('create')->andThrow(new Exception('Database error'));
            });

            Log::shouldReceive('info'); // Allow info logs
            Log::shouldReceive('error')->once(); // Expect error log

            $matches = $this->matcher->matchAllTransactions();

            // Should return empty matches due to error
            expect($matches)->toHaveCount(0);
        });
    });

    describe('Single Transaction Matching', function () {
        it('can match a specific transaction', function () {
            $invoice = Invoice::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $this->customer->id,
                'currency_id' => $this->currency->id,
                'status' => 'SENT',
                'total' => 350.00,
                'due_date' => now()->addDays(5),
                'invoice_number' => 'INV-2025-018',
            ]);

            $transactionId = DB::table('bank_transactions')->insertGetId([
                'bank_account_id' => $this->bankAccount->id,
                'company_id' => $this->company->id,
                'external_reference' => 'TXN-SINGLE',
                'amount' => 350.00,
                'currency' => 'MKD',
                'description' => 'Payment for INV-2025-018',
                'transaction_date' => now()->addDays(6),
                'matched_invoice_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $transaction = DB::table('bank_transactions')->where('id', $transactionId)->first();
            $match = $this->matcher->matchTransaction($transaction);

            expect($match)->not->toBeNull();
            expect($match['invoice_id'])->toBe($invoice->id);
            expect($match['amount'])->toBe(350.00);
        });

        it('returns null when no match found for specific transaction', function () {
            // Create transaction with no matching invoice
            $transactionId = DB::table('bank_transactions')->insertGetId([
                'bank_account_id' => $this->bankAccount->id,
                'company_id' => $this->company->id,
                'external_reference' => 'TXN-NO-MATCH',
                'amount' => 9999.99,
                'currency' => 'MKD',
                'description' => 'Random payment',
                'transaction_date' => now()->addDays(6),
                'matched_invoice_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $transaction = DB::table('bank_transactions')->where('id', $transactionId)->first();
            $match = $this->matcher->matchTransaction($transaction);

            expect($match)->toBeNull();
        });
    });

    describe('Statistics and Reporting', function () {
        it('calculates matching statistics correctly', function () {
            // Create some matched and unmatched transactions
            DB::table('bank_transactions')->insert([
                [
                    'bank_account_id' => $this->bankAccount->id,
                    'company_id' => $this->company->id,
                    'external_reference' => 'TXN-MATCHED-1',
                    'amount' => 100.00,
                    'currency' => 'MKD',
                    'description' => 'Matched payment',
                    'transaction_date' => now(),
                    'matched_invoice_id' => 1, // Matched
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'bank_account_id' => $this->bankAccount->id,
                    'company_id' => $this->company->id,
                    'external_reference' => 'TXN-MATCHED-2',
                    'amount' => 200.00,
                    'currency' => 'MKD',
                    'description' => 'Another matched payment',
                    'transaction_date' => now(),
                    'matched_invoice_id' => 2, // Matched
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'bank_account_id' => $this->bankAccount->id,
                    'company_id' => $this->company->id,
                    'external_reference' => 'TXN-UNMATCHED',
                    'amount' => 300.00,
                    'currency' => 'MKD',
                    'description' => 'Unmatched payment',
                    'transaction_date' => now(),
                    'matched_invoice_id' => null, // Not matched
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);

            $stats = $this->matcher->getMatchingStats();

            expect($stats['total_transactions'])->toBe(3);
            expect($stats['matched_transactions'])->toBe(2);
            expect($stats['unmatched_transactions'])->toBe(1);
            expect($stats['match_rate'])->toBe(66.7);
        });

        it('handles empty statistics correctly', function () {
            $stats = $this->matcher->getMatchingStats();

            expect($stats['total_transactions'])->toBe(0);
            expect($stats['matched_transactions'])->toBe(0);
            expect($stats['unmatched_transactions'])->toBe(0);
            expect($stats['match_rate'])->toBe(0);
        });
    });
});
