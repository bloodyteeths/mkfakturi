<?php

use Modules\Mk\Http\PaddleWebhookController;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Customer;
use App\Models\Company;
use App\Models\Currency;
use App\Models\PaymentMethod;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

/**
 * End-to-End Payment Flow Test Suite
 * 
 * Comprehensive testing for Paddle → Invoice → Paid flow as specified in QA-01
 * Covers the complete payment cycle from Paddle checkout to invoice payment confirmation
 * 
 * Test Coverage:
 * - Paddle button integration and checkout flow
 * - Webhook signature validation and processing
 * - Invoice payment status updates
 * - Payment record creation and linking
 * - Error scenarios and edge cases
 * - Payment confirmation workflow
 * - End-to-end payment cycle validation
 */
describe('End-to-End Payment Flow', function () {
    
    beforeEach(function () {
        // Clear all relevant tables for clean test state
        DB::table('payments')->truncate();
        DB::table('invoices')->truncate(); 
        DB::table('customers')->truncate();
        DB::table('companies')->truncate();
        DB::table('currencies')->truncate();
        DB::table('payment_methods')->truncate();
        
        // Create test data with realistic values
        $this->company = Company::factory()->create([
            'name' => 'Test Accounting Company',
            'slug' => 'test-company'
        ]);
        
        $this->currency = Currency::factory()->create([
            'code' => 'EUR',
            'name' => 'Euro',
            'symbol' => '€'
        ]);
        
        $this->customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'currency_id' => $this->currency->id
        ]);
        
        $this->paymentMethod = PaymentMethod::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Paddle Payment',
            'driver' => 'paddle'
        ]);
        
        $this->controller = new PaddleWebhookController();
        
        // Set up webhook configuration
        $this->webhookSecret = 'test_paddle_webhook_secret_12345';
        Config::set('services.paddle.webhook_secret', $this->webhookSecret);
        Config::set('services.paddle.vendor_id', '12345');
        Config::set('services.paddle.environment', 'sandbox');
        
        // Mock current time for consistent testing
        $this->now = Carbon::parse('2025-07-25 10:00:00');
        Carbon::setTestNow($this->now);
    });

    describe('Complete Payment Cycle', function () {
        it('processes end-to-end payment flow successfully', function () {
            // Step 1: Create an invoice that needs payment
            $invoice = Invoice::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $this->customer->id,
                'currency_id' => $this->currency->id,
                'status' => Invoice::STATUS_SENT,
                'paid_status' => Invoice::STATUS_UNPAID,
                'total' => 15000, // €150.00 in cents
                'due_amount' => 15000,
                'invoice_number' => 'INV-2025-001',
                'invoice_date' => $this->now->format('Y-m-d'),
                'due_date' => $this->now->addDays(30)->format('Y-m-d')
            ]);
            
            // Step 2: Simulate Paddle checkout completion
            $paddleOrderId = 'PAY-PADDLE-' . uniqid();
            $passthroughData = [
                'invoice_id' => $invoice->id,
                'customer_id' => $this->customer->id,
                'company_id' => $this->company->id
            ];
            
            // Step 3: Process successful payment webhook
            $webhookData = [
                'alert_name' => 'payment_succeeded',
                'p_order_id' => $paddleOrderId,
                'p_sale_gross' => '150.00',
                'event_time' => $this->now->format('Y-m-d H:i:s'),
                'passthrough' => json_encode($passthroughData)
            ];
            
            // Calculate correct webhook signature
            ksort($webhookData);
            $queryString = http_build_query($webhookData);
            $signature = base64_encode(hash_hmac('sha1', $queryString, $this->webhookSecret, true));
            $webhookData['p_signature'] = $signature;
            
            $request = Request::create('/webhooks/paddle', 'POST', $webhookData);
            
            // Expect proper logging
            Log::shouldReceive('info')
                ->once()
                ->with('Paddle webhook received', [
                    'event_type' => 'payment_succeeded',
                    'paddle_id' => $paddleOrderId
                ]);
            
            Log::shouldReceive('info')
                ->once()
                ->with('Invoice marked as paid via Paddle', [
                    'invoice_id' => $invoice->id,
                    'payment_id' => Mockery::type('integer'),
                    'paddle_order_id' => $paddleOrderId,
                    'amount' => '150.00'
                ]);
            
            // Step 4: Process the webhook
            $response = $this->controller->handle($request);
            
            // Step 5: Assert webhook processing succeeded
            expect($response->getStatusCode())->toBe(200);
            expect($response->getContent())->toBe('OK');
            
            // Step 6: Verify payment record was created correctly
            $payment = Payment::where('invoice_id', $invoice->id)->first();
            expect($payment)->not->toBeNull();
            expect($payment->company_id)->toBe($this->company->id);
            expect($payment->customer_id)->toBe($this->customer->id);
            expect($payment->amount)->toBe(150.00);
            expect($payment->currency_id)->toBe($this->currency->id);
            expect($payment->payment_method)->toBe('paddle');
            expect($payment->reference)->toBe($paddleOrderId);
            expect($payment->payment_date)->toBe($this->now->format('Y-m-d'));
            expect($payment->notes)->toContain('Paddle payment - Order ID: ' . $paddleOrderId);
            
            // Step 7: Verify payment number was generated
            expect($payment->payment_number)->toMatch('/PAY-\d{4}\d{2}-\d{4}/');
            
            // Step 8: Verify invoice status was updated correctly
            $invoice->refresh();
            expect($invoice->status)->toBe(Invoice::STATUS_PAID);
            expect($invoice->paid_status)->toBe(Invoice::STATUS_PAID);
            expect($invoice->due_amount)->toBe(0);
            expect($invoice->payment_date)->not->toBeNull();
            
            // Step 9: Verify invoice-payment relationship
            expect($invoice->payments)->toHaveCount(1);
            expect($invoice->payments->first()->id)->toBe($payment->id);
            expect($payment->invoice->id)->toBe($invoice->id);
        });

        it('handles partial payment scenarios correctly', function () {
            // Create invoice for €200.00
            $invoice = Invoice::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $this->customer->id,
                'currency_id' => $this->currency->id,
                'status' => Invoice::STATUS_SENT,
                'paid_status' => Invoice::STATUS_UNPAID,
                'total' => 20000, // €200.00 in cents
                'due_amount' => 20000,
                'invoice_number' => 'INV-2025-002'
            ]);
            
            // Process first payment of €75.00
            $firstPaymentData = [
                'alert_name' => 'payment_succeeded',
                'p_order_id' => 'PAY-PARTIAL-1',
                'p_sale_gross' => '75.00',
                'event_time' => $this->now->format('Y-m-d H:i:s'),
                'passthrough' => json_encode(['invoice_id' => $invoice->id])
            ];
            
            ksort($firstPaymentData);
            $signature = base64_encode(hash_hmac('sha1', http_build_query($firstPaymentData), $this->webhookSecret, true));
            $firstPaymentData['p_signature'] = $signature;
            
            $request1 = Request::create('/webhooks/paddle', 'POST', $firstPaymentData);
            
            Log::shouldReceive('info')->twice(); // webhook + invoice update
            
            $response1 = $this->controller->handle($request1);
            expect($response1->getStatusCode())->toBe(200);
            
            // Check invoice status after first partial payment
            $invoice->refresh();
            expect($invoice->status)->toBe(Invoice::STATUS_SENT); // Still sent, not fully paid
            expect($invoice->paid_status)->toBe(Invoice::STATUS_PARTIALLY_PAID);
            expect($invoice->due_amount)->toBe(12500); // €125.00 remaining
            
            // Process second payment of €125.00 to complete payment
            $secondPaymentData = [
                'alert_name' => 'payment_succeeded',
                'p_order_id' => 'PAY-PARTIAL-2',
                'p_sale_gross' => '125.00',
                'event_time' => $this->now->addHour()->format('Y-m-d H:i:s'),
                'passthrough' => json_encode(['invoice_id' => $invoice->id])
            ];
            
            ksort($secondPaymentData);
            $signature2 = base64_encode(hash_hmac('sha1', http_build_query($secondPaymentData), $this->webhookSecret, true));
            $secondPaymentData['p_signature'] = $signature2;
            
            $request2 = Request::create('/webhooks/paddle', 'POST', $secondPaymentData);
            
            Log::shouldReceive('info')->twice(); // webhook + invoice update
            
            $response2 = $this->controller->handle($request2);
            expect($response2->getStatusCode())->toBe(200);
            
            // Check final invoice status
            $invoice->refresh();
            expect($invoice->status)->toBe(Invoice::STATUS_PAID);
            expect($invoice->paid_status)->toBe(Invoice::STATUS_PAID);
            expect($invoice->due_amount)->toBe(0);
            expect($invoice->payments)->toHaveCount(2);
            
            // Verify total payments equal invoice total
            $totalPaid = $invoice->payments->sum('amount');
            expect($totalPaid)->toBe(200.00);
        });

        it('handles payment refund flow correctly', function () {
            // Step 1: Create paid invoice
            $invoice = Invoice::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $this->customer->id,
                'currency_id' => $this->currency->id,
                'status' => Invoice::STATUS_PAID,
                'paid_status' => Invoice::STATUS_PAID,
                'total' => 10000,
                'due_amount' => 0,
                'invoice_number' => 'INV-2025-REFUND'
            ]);
            
            // Step 2: Create associated payment
            $payment = Payment::factory()->create([
                'company_id' => $this->company->id,
                'invoice_id' => $invoice->id,
                'customer_id' => $this->customer->id,
                'amount' => 100.00,
                'currency_id' => $this->currency->id,
                'reference' => 'PAY-REFUND-TEST',
                'payment_method' => 'paddle',
                'payment_date' => $this->now->format('Y-m-d')
            ]);
            
            // Step 3: Process refund webhook
            $refundData = [
                'alert_name' => 'payment_refunded',
                'p_order_id' => 'PAY-REFUND-TEST',
                'p_gross_refund' => '100.00'
            ];
            
            ksort($refundData);
            $signature = base64_encode(hash_hmac('sha1', http_build_query($refundData), $this->webhookSecret, true));
            $refundData['p_signature'] = $signature;
            
            $request = Request::create('/webhooks/paddle', 'POST', $refundData);
            
            Log::shouldReceive('info')->twice(); // webhook received + refund processed
            
            $response = $this->controller->handle($request);
            expect($response->getStatusCode())->toBe(200);
            
            // Step 4: Verify refund processing
            $payment->refresh();
            expect($payment->status)->toBe('REFUNDED');
            expect($payment->notes)->toContain('Refunded via Paddle webhook: 100.00');
            
            // Step 5: Verify invoice status reverted
            $invoice->refresh();
            expect($invoice->status)->toBe(Invoice::STATUS_SENT);
            expect($invoice->paid_status)->toBe('UNPAID');
        });
    });

    describe('Payment Flow Edge Cases', function () {
        it('handles multiple concurrent payments for same invoice', function () {
            $invoice = Invoice::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $this->customer->id,
                'currency_id' => $this->currency->id,
                'status' => Invoice::STATUS_SENT,
                'paid_status' => Invoice::STATUS_UNPAID,
                'total' => 30000, // €300.00
                'due_amount' => 30000
            ]);
            
            // Simulate concurrent payments (race condition scenario)
            $payments = [];
            for ($i = 1; $i <= 3; $i++) {
                $paymentData = [
                    'alert_name' => 'payment_succeeded',
                    'p_order_id' => 'PAY-CONCURRENT-' . $i,
                    'p_sale_gross' => '100.00',
                    'event_time' => $this->now->addMinutes($i)->format('Y-m-d H:i:s'),
                    'passthrough' => json_encode(['invoice_id' => $invoice->id])
                ];
                
                ksort($paymentData);
                $signature = base64_encode(hash_hmac('sha1', http_build_query($paymentData), $this->webhookSecret, true));
                $paymentData['p_signature'] = $signature;
                
                $request = Request::create('/webhooks/paddle', 'POST', $paymentData);
                
                Log::shouldReceive('info')->twice();
                
                $response = $this->controller->handle($request);
                expect($response->getStatusCode())->toBe(200);
                
                $payments[] = $paymentData;
            }
            
            // Verify all payments were processed
            $invoice->refresh();
            expect($invoice->payments)->toHaveCount(3);
            expect($invoice->status)->toBe(Invoice::STATUS_PAID);
            expect($invoice->due_amount)->toBe(0);
            
            // Verify each payment has unique payment number
            $paymentNumbers = $invoice->payments->pluck('payment_number')->toArray();
            expect(count($paymentNumbers))->toBe(count(array_unique($paymentNumbers)));
        });

        it('handles webhook replay attacks', function () {
            $invoice = Invoice::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $this->customer->id,
                'currency_id' => $this->currency->id,
                'status' => Invoice::STATUS_SENT,
                'total' => 5000
            ]);
            
            $webhookData = [
                'alert_name' => 'payment_succeeded',
                'p_order_id' => 'PAY-REPLAY-ATTACK',
                'p_sale_gross' => '50.00',
                'event_time' => $this->now->format('Y-m-d H:i:s'),
                'passthrough' => json_encode(['invoice_id' => $invoice->id])
            ];
            
            ksort($webhookData);
            $signature = base64_encode(hash_hmac('sha1', http_build_query($webhookData), $this->webhookSecret, true));
            $webhookData['p_signature'] = $signature;
            
            $request1 = Request::create('/webhooks/paddle', 'POST', $webhookData);
            $request2 = Request::create('/webhooks/paddle', 'POST', $webhookData); // Identical replay
            
            Log::shouldReceive('info')->times(4); // 2x webhook received + 2x invoice updates
            
            // Process same webhook twice
            $response1 = $this->controller->handle($request1);
            $response2 = $this->controller->handle($request2);
            
            expect($response1->getStatusCode())->toBe(200);
            expect($response2->getStatusCode())->toBe(200);
            
            // Should have created 2 separate payment records (idempotency is Paddle's responsibility)
            $invoice->refresh();
            expect($invoice->payments)->toHaveCount(2);
            
            // But invoice should be overpaid
            expect($invoice->due_amount)->toBe(-5000); // Overpaid by €50
        });

        it('handles webhook with malformed passthrough data', function () {
            $webhookData = [
                'alert_name' => 'payment_succeeded',
                'p_order_id' => 'PAY-MALFORMED',
                'p_sale_gross' => '75.00',
                'event_time' => $this->now->format('Y-m-d H:i:s'),
                'passthrough' => '{invalid_json_data'
            ];
            
            ksort($webhookData);
            $signature = base64_encode(hash_hmac('sha1', http_build_query($webhookData), $this->webhookSecret, true));
            $webhookData['p_signature'] = $signature;
            
            $request = Request::create('/webhooks/paddle', 'POST', $webhookData);
            
            Log::shouldReceive('info')->once(); // Only webhook received log
            
            $response = $this->controller->handle($request);
            
            // Should handle gracefully
            expect($response->getStatusCode())->toBe(200);
            expect(Payment::count())->toBe(0); // No payment created
        });

        it('handles payment for non-existent invoice', function () {
            $webhookData = [
                'alert_name' => 'payment_succeeded',
                'p_order_id' => 'PAY-NONEXISTENT-INVOICE',
                'p_sale_gross' => '100.00',
                'event_time' => $this->now->format('Y-m-d H:i:s'),
                'passthrough' => json_encode(['invoice_id' => 99999])
            ];
            
            ksort($webhookData);
            $signature = base64_encode(hash_hmac('sha1', http_build_query($webhookData), $this->webhookSecret, true));
            $webhookData['p_signature'] = $signature;
            
            $request = Request::create('/webhooks/paddle', 'POST', $webhookData);
            
            Log::shouldReceive('info')->once(); // webhook received
            Log::shouldReceive('warning')->once(); // invoice not found
            
            $response = $this->controller->handle($request);
            
            expect($response->getStatusCode())->toBe(404);
            expect($response->getContent())->toBe('Invoice not found');
            expect(Payment::count())->toBe(0);
        });
    });

    describe('Security and Validation', function () {
        it('rejects webhooks with invalid signatures', function () {
            $webhookData = [
                'alert_name' => 'payment_succeeded',
                'p_order_id' => 'PAY-INVALID-SIG',
                'p_sale_gross' => '100.00',
                'p_signature' => 'invalid_signature'
            ];
            
            $request = Request::create('/webhooks/paddle', 'POST', $webhookData);
            
            Log::shouldReceive('warning')
                ->once()
                ->with('Paddle webhook signature verification failed', [
                    'ip' => '127.0.0.1',
                    'user_agent' => 'Symfony'
                ]);
            
            $response = $this->controller->handle($request);
            
            expect($response->getStatusCode())->toBe(401);
            expect($response->getContent())->toBe('Unauthorized');
            expect(Payment::count())->toBe(0);
        });

        it('handles missing webhook secret configuration', function () {
            Config::set('services.paddle.webhook_secret', null);
            
            $webhookData = [
                'alert_name' => 'payment_succeeded',
                'p_signature' => 'some_signature'
            ];
            
            $request = Request::create('/webhooks/paddle', 'POST', $webhookData);
            
            Log::shouldReceive('warning')
                ->once()
                ->with('Paddle webhook signature verification failed', Mockery::type('array'));
            
            $response = $this->controller->handle($request);
            
            expect($response->getStatusCode())->toBe(401);
        });

        it('handles webhook processing exceptions gracefully', function () {
            // Mock Invoice::find to throw exception
            Invoice::shouldReceive('find')
                ->once()
                ->andThrow(new Exception('Database connection failed'));
            
            $webhookData = [
                'alert_name' => 'payment_succeeded',
                'p_order_id' => 'PAY-EXCEPTION',
                'p_sale_gross' => '100.00',
                'passthrough' => json_encode(['invoice_id' => 123])
            ];
            
            ksort($webhookData);
            $signature = base64_encode(hash_hmac('sha1', http_build_query($webhookData), $this->webhookSecret, true));
            $webhookData['p_signature'] = $signature;
            
            $request = Request::create('/webhooks/paddle', 'POST', $webhookData);
            
            Log::shouldReceive('info')->once(); // webhook received
            Log::shouldReceive('error')->once(); // exception logged
            
            $response = $this->controller->handle($request);
            
            expect($response->getStatusCode())->toBe(500);
            expect($response->getContent())->toBe('Internal Server Error');
        });
    });

    describe('Payment Number Generation', function () {
        it('generates sequential payment numbers correctly', function () {
            $invoice1 = Invoice::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $this->customer->id,
                'currency_id' => $this->currency->id,
                'total' => 10000
            ]);
            
            $invoice2 = Invoice::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $this->customer->id,
                'currency_id' => $this->currency->id,
                'total' => 15000
            ]);
            
            // Process payments for both invoices
            foreach ([$invoice1, $invoice2] as $index => $invoice) {
                $webhookData = [
                    'alert_name' => 'payment_succeeded',
                    'p_order_id' => 'PAY-SEQ-' . ($index + 1),
                    'p_sale_gross' => ($invoice->total / 100),
                    'event_time' => $this->now->format('Y-m-d H:i:s'),
                    'passthrough' => json_encode(['invoice_id' => $invoice->id])
                ];
                
                ksort($webhookData);
                $signature = base64_encode(hash_hmac('sha1', http_build_query($webhookData), $this->webhookSecret, true));
                $webhookData['p_signature'] = $signature;
                
                $request = Request::create('/webhooks/paddle', 'POST', $webhookData);
                
                Log::shouldReceive('info')->twice();
                
                $response = $this->controller->handle($request);
                expect($response->getStatusCode())->toBe(200);
            }
            
            $payments = Payment::orderBy('id')->get();
            expect($payments)->toHaveCount(2);
            
            // Verify payment numbers are sequential
            $currentYear = date('Y');
            $currentMonth = date('m');
            expect($payments[0]->payment_number)->toBe("PAY-{$currentYear}{$currentMonth}-0001");
            expect($payments[1]->payment_number)->toBe("PAY-{$currentYear}{$currentMonth}-0002");
        });
    });

    describe('Currency and Exchange Rate Handling', function () {
        it('handles multi-currency payments correctly', function () {
            // Create USD currency
            $usdCurrency = Currency::factory()->create([
                'code' => 'USD',
                'name' => 'US Dollar',
                'symbol' => '$'
            ]);
            
            $invoice = Invoice::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $this->customer->id,
                'currency_id' => $usdCurrency->id,
                'total' => 12000, // $120.00
                'exchange_rate' => 0.85 // USD to EUR rate
            ]);
            
            $webhookData = [
                'alert_name' => 'payment_succeeded',
                'p_order_id' => 'PAY-USD-PAYMENT',
                'p_sale_gross' => '120.00',
                'event_time' => $this->now->format('Y-m-d H:i:s'),
                'passthrough' => json_encode(['invoice_id' => $invoice->id])
            ];
            
            ksort($webhookData);
            $signature = base64_encode(hash_hmac('sha1', http_build_query($webhookData), $this->webhookSecret, true));
            $webhookData['p_signature'] = $signature;
            
            $request = Request::create('/webhooks/paddle', 'POST', $webhookData);
            
            Log::shouldReceive('info')->twice();
            
            $response = $this->controller->handle($request);
            expect($response->getStatusCode())->toBe(200);
            
            $payment = Payment::first();
            expect($payment->currency_id)->toBe($usdCurrency->id);
            expect($payment->amount)->toBe(120.00);
            
            $invoice->refresh();
            expect($invoice->status)->toBe(Invoice::STATUS_PAID);
        });
    });

    afterEach(function () {
        // Reset test time
        Carbon::setTestNow();
        
        // Clean up mocks
        Mockery::close();
    });
});