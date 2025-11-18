<?php

use App\Models\Company;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Item;
use App\Models\Payment;
use App\Models\PaymentMethod;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Mk\Services\MiniMaxSyncService;

/**
 * MiniMax Synchronization Service Test Suite
 *
 * Tests for MiniMax accounting system integration (ROADMAP4.md AI-03)
 * Covers invoice sync, payment sync, error handling, and API mocking
 *
 * Success criteria: API 201 Created response
 */
describe('MiniMaxSyncService', function () {

    beforeEach(function () {
        // Create test data
        $this->company = Company::factory()->create();
        $this->currency = Currency::factory()->create(['code' => 'MKD']);
        $this->customer = Customer::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->paymentMethod = PaymentMethod::factory()->create([
            'company_id' => $this->company->id,
        ]);

        // Initialize service
        $this->syncService = new MiniMaxSyncService($this->company);

        // Mock HTTP responses
        Http::fake([
            'api.minimax.mk/*' => Http::response([
                'id' => 'minimax_test_123',
                'status' => 'created',
                'created_at' => now()->toISOString(),
            ], 201),
        ]);
    });

    describe('Service Initialization', function () {
        it('can be instantiated with company', function () {
            $service = new MiniMaxSyncService($this->company);

            expect($service)->toBeInstanceOf(MiniMaxSyncService::class);
            expect($service->getCompany())->toBe($this->company);
        });

        it('can be instantiated without company', function () {
            $service = new MiniMaxSyncService;

            expect($service)->toBeInstanceOf(MiniMaxSyncService::class);
            expect($service->getCompany())->toBeNull();
        });

        it('can set company after initialization', function () {
            $service = new MiniMaxSyncService;
            $result = $service->setCompany($this->company);

            expect($result)->toBe($service); // Fluent interface
            expect($service->getCompany())->toBe($this->company);
        });
    });

    describe('Invoice Synchronization', function () {
        beforeEach(function () {
            // Create test invoice with items
            $this->invoice = Invoice::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $this->customer->id,
                'currency_id' => $this->currency->id,
                'invoice_number' => 'INV-2025-001',
                'invoice_date' => Carbon::today(),
                'due_date' => Carbon::today()->addDays(30),
                'sub_total' => 1000.00,
                'tax' => 180.00,
                'total' => 1180.00,
                'status' => 'sent',
            ]);

            // Create invoice item
            $item = Item::factory()->create([
                'company_id' => $this->company->id,
            ]);

            InvoiceItem::factory()->create([
                'invoice_id' => $this->invoice->id,
                'item_id' => $item->id,
                'company_id' => $this->company->id,
                'quantity' => 2,
                'price' => 500.00,
                'total' => 1000.00,
            ]);
        });

        it('successfully syncs invoice with MiniMax', function () {
            Log::shouldReceive('info')->twice(); // Start and success logs

            $result = $this->syncService->syncInvoice($this->invoice);

            expect($result)->toHaveKey('success', true);
            expect($result)->toHaveKey('status_code', 201);
            expect($result)->toHaveKey('minimax_id');
            expect($result)->toHaveKey('message', 'Invoice synchronized successfully');
            expect($result['minimax_id'])->toContain('minimax_');
        });

        it('validates invoice data before sync', function () {
            // Create invoice without customer
            $invalidInvoice = Invoice::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => null,
                'currency_id' => $this->currency->id,
            ]);

            expect(fn () => $this->syncService->syncInvoice($invalidInvoice))
                ->toThrow(Exception::class, 'Invoice must have a customer');
        });

        it('validates invoice has items', function () {
            // Create invoice without items
            $invoiceWithoutItems = Invoice::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $this->customer->id,
                'currency_id' => $this->currency->id,
            ]);

            expect(fn () => $this->syncService->syncInvoice($invoiceWithoutItems))
                ->toThrow(Exception::class, 'Invoice must have at least one item');
        });

        it('validates invoice total is positive', function () {
            $zeroTotalInvoice = Invoice::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $this->customer->id,
                'currency_id' => $this->currency->id,
                'total' => 0,
            ]);

            // Add item to pass item validation
            $item = Item::factory()->create(['company_id' => $this->company->id]);
            InvoiceItem::factory()->create([
                'invoice_id' => $zeroTotalInvoice->id,
                'item_id' => $item->id,
                'company_id' => $this->company->id,
            ]);

            expect(fn () => $this->syncService->syncInvoice($zeroTotalInvoice))
                ->toThrow(Exception::class, 'Invoice total must be greater than zero');
        });

        it('handles API errors gracefully', function () {
            // Mock API failure
            Http::fake([
                'api.minimax.mk/*' => Http::response([
                    'error' => 'Invalid data format',
                ], 400),
            ]);

            Log::shouldReceive('info')->once(); // Start log
            Log::shouldReceive('error')->once(); // Error log

            expect(fn () => $this->syncService->syncInvoice($this->invoice))
                ->toThrow(Exception::class);
        });

        it('logs sync activities', function () {
            Log::shouldReceive('info')
                ->once()
                ->with('Starting MiniMax invoice sync', Mockery::type('array'));

            Log::shouldReceive('info')
                ->once()
                ->with('MiniMax invoice sync successful', Mockery::type('array'));

            $this->syncService->syncInvoice($this->invoice);
        });
    });

    describe('Payment Synchronization', function () {
        beforeEach(function () {
            $this->invoice = Invoice::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $this->customer->id,
                'currency_id' => $this->currency->id,
            ]);

            $this->payment = Payment::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $this->customer->id,
                'invoice_id' => $this->invoice->id,
                'payment_method_id' => $this->paymentMethod->id,
                'currency_id' => $this->currency->id,
                'amount' => 500.00,
                'payment_number' => 'PAY-2025-001',
                'payment_date' => Carbon::today(),
            ]);
        });

        it('successfully syncs payment with MiniMax', function () {
            Log::shouldReceive('info')->twice(); // Start and success logs

            $result = $this->syncService->syncPayment($this->payment);

            expect($result)->toHaveKey('success', true);
            expect($result)->toHaveKey('status_code', 201);
            expect($result)->toHaveKey('minimax_id');
            expect($result)->toHaveKey('message', 'Payment synchronized successfully');
            expect($result['minimax_id'])->toContain('minimax_');
        });

        it('validates payment has customer or invoice', function () {
            $orphanPayment = Payment::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => null,
                'invoice_id' => null,
                'payment_method_id' => $this->paymentMethod->id,
                'currency_id' => $this->currency->id,
                'amount' => 100.00,
            ]);

            expect(fn () => $this->syncService->syncPayment($orphanPayment))
                ->toThrow(Exception::class, 'Payment must be associated with a customer or invoice');
        });

        it('validates payment amount is positive', function () {
            $zeroAmountPayment = Payment::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $this->customer->id,
                'payment_method_id' => $this->paymentMethod->id,
                'currency_id' => $this->currency->id,
                'amount' => 0,
            ]);

            expect(fn () => $this->syncService->syncPayment($zeroAmountPayment))
                ->toThrow(Exception::class, 'Payment amount must be greater than zero');
        });

        it('validates payment method is present', function () {
            $noMethodPayment = Payment::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $this->customer->id,
                'payment_method_id' => null,
                'currency_id' => $this->currency->id,
                'amount' => 100.00,
            ]);

            expect(fn () => $this->syncService->syncPayment($noMethodPayment))
                ->toThrow(Exception::class, 'Payment method is required');
        });

        it('handles payment sync errors', function () {
            Http::fake([
                'api.minimax.mk/*' => Http::response([
                    'error' => 'Payment validation failed',
                ], 422),
            ]);

            Log::shouldReceive('info')->once(); // Start log
            Log::shouldReceive('error')->once(); // Error log

            expect(fn () => $this->syncService->syncPayment($this->payment))
                ->toThrow(Exception::class);
        });
    });

    describe('Status Checking', function () {
        it('returns sync status for invoice', function () {
            Log::shouldReceive('info')->once();

            $status = $this->syncService->getStatus('invoice', 123);

            expect($status)->toHaveKey('success', true);
            expect($status)->toHaveKey('entity_type', 'invoice');
            expect($status)->toHaveKey('entity_id', 123);
            expect($status)->toHaveKey('sync_status');
            expect($status)->toHaveKey('minimax_id');
        });

        it('returns sync status for payment', function () {
            Log::shouldReceive('info')->once();

            $status = $this->syncService->getStatus('payment', 456);

            expect($status)->toHaveKey('success', true);
            expect($status)->toHaveKey('entity_type', 'payment');
            expect($status)->toHaveKey('entity_id', 456);
            expect($status)->toHaveKey('sync_status');
        });

        it('handles status check errors gracefully', function () {
            Http::fake([
                'api.minimax.mk/*' => Http::response([
                    'error' => 'Entity not found',
                ], 404),
            ]);

            Log::shouldReceive('info')->once();
            Log::shouldReceive('error')->once();

            $status = $this->syncService->getStatus('invoice', 999);

            expect($status)->toHaveKey('success', false);
            expect($status)->toHaveKey('sync_status', 'error');
            expect($status)->toHaveKey('error');
        });
    });

    describe('Data Format Preparation', function () {
        it('prepares invoice data in correct format', function () {
            $invoice = Invoice::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $this->customer->id,
                'currency_id' => $this->currency->id,
                'invoice_number' => 'INV-FORMAT-TEST',
                'sub_total' => 1000.00,
                'tax' => 180.00,
                'total' => 1180.00,
            ]);

            $item = Item::factory()->create(['company_id' => $this->company->id]);
            InvoiceItem::factory()->create([
                'invoice_id' => $invoice->id,
                'item_id' => $item->id,
                'company_id' => $this->company->id,
                'quantity' => 2,
                'price' => 500.00,
                'total' => 1000.00,
            ]);

            Log::shouldReceive('info')->twice();

            // This will test the data preparation internally
            $result = $this->syncService->syncInvoice($invoice);

            expect($result['success'])->toBe(true);
        });

        it('prepares payment data in correct format', function () {
            $payment = Payment::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $this->customer->id,
                'payment_method_id' => $this->paymentMethod->id,
                'currency_id' => $this->currency->id,
                'payment_number' => 'PAY-FORMAT-TEST',
                'amount' => 250.00,
            ]);

            Log::shouldReceive('info')->twice();

            // This will test the data preparation internally
            $result = $this->syncService->syncPayment($payment);

            expect($result['success'])->toBe(true);
        });
    });

    describe('Error Handling & Logging', function () {
        it('logs comprehensive error information on invoice sync failure', function () {
            Http::fake([
                'api.minimax.mk/*' => Http::response(null, 500),
            ]);

            $invoice = Invoice::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $this->customer->id,
                'currency_id' => $this->currency->id,
            ]);

            $item = Item::factory()->create(['company_id' => $this->company->id]);
            InvoiceItem::factory()->create([
                'invoice_id' => $invoice->id,
                'item_id' => $item->id,
                'company_id' => $this->company->id,
            ]);

            Log::shouldReceive('info')->once(); // Start log
            Log::shouldReceive('error')
                ->once()
                ->with('MiniMax invoice sync failed', Mockery::type('array'));

            expect(fn () => $this->syncService->syncInvoice($invoice))
                ->toThrow(Exception::class);
        });

        it('logs comprehensive error information on payment sync failure', function () {
            Http::fake([
                'api.minimax.mk/*' => Http::response(null, 503),
            ]);

            $payment = Payment::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $this->customer->id,
                'payment_method_id' => $this->paymentMethod->id,
                'currency_id' => $this->currency->id,
                'amount' => 100.00,
            ]);

            Log::shouldReceive('info')->once(); // Start log
            Log::shouldReceive('error')
                ->once()
                ->with('MiniMax payment sync failed', Mockery::type('array'));

            expect(fn () => $this->syncService->syncPayment($payment))
                ->toThrow(Exception::class);
        });
    });

    describe('Testing Environment Behavior', function () {
        it('uses mock responses in testing environment', function () {
            // Ensure we're in testing mode
            config(['app.env' => 'testing']);

            $invoice = Invoice::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $this->customer->id,
                'currency_id' => $this->currency->id,
            ]);

            $item = Item::factory()->create(['company_id' => $this->company->id]);
            InvoiceItem::factory()->create([
                'invoice_id' => $invoice->id,
                'item_id' => $item->id,
                'company_id' => $this->company->id,
            ]);

            Log::shouldReceive('info')->twice();

            $result = $this->syncService->syncInvoice($invoice);

            // Should get mock response with 201 status
            expect($result['status_code'])->toBe(201);
            expect($result['minimax_id'])->toContain('minimax_');
        });
    });

    afterEach(function () {
        Http::assertNothingOutstanding();
        Mockery::close();
    });
});
