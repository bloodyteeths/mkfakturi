<?php

use App\Models\Company;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Modules\Mk\Http\PaddleWebhookController;

/**
 * Paddle Webhook Controller Test Suite
 *
 * Tests for Paddle payment webhook handling
 * Covers signature validation, payment processing, refunds, and error scenarios
 *
 * Target: Signature validation as per ROADMAP2.md
 */
describe('Paddle Webhook Controller', function () {

    beforeEach(function () {
        // Clear relevant tables
        DB::table('payments')->truncate();
        DB::table('invoices')->truncate();
        DB::table('customers')->truncate();

        // Create test data
        $this->company = Company::factory()->create();
        $this->currency = Currency::factory()->create(['code' => 'USD']);
        $this->customer = Customer::factory()->create(['company_id' => $this->company->id]);

        $this->controller = new PaddleWebhookController;

        // Set up webhook secret for testing
        $this->webhookSecret = 'test_webhook_secret_123';
        Config::set('services.paddle.webhook_secret', $this->webhookSecret);
    });

    describe('Signature Verification', function () {
        it('validates correct webhook signatures', function () {
            $data = [
                'alert_name' => 'payment_succeeded',
                'p_order_id' => 'PAY-123456',
                'p_sale_gross' => '100.00',
                'event_time' => '2025-07-25 10:00:00',
            ];

            // Calculate correct signature
            ksort($data);
            $queryString = http_build_query($data);
            $signature = base64_encode(hash_hmac('sha1', $queryString, $this->webhookSecret, true));
            $data['p_signature'] = $signature;

            $request = Request::create('/webhook/paddle', 'POST', $data);

            // Use reflection to test protected method
            $reflection = new ReflectionClass($this->controller);
            $method = $reflection->getMethod('verifyWebhookSignature');
            $method->setAccessible(true);

            $result = $method->invokeArgs($this->controller, [$request]);

            expect($result)->toBeTrue();
        });

        it('rejects incorrect webhook signatures', function () {
            $data = [
                'alert_name' => 'payment_succeeded',
                'p_order_id' => 'PAY-123456',
                'p_sale_gross' => '100.00',
                'event_time' => '2025-07-25 10:00:00',
                'p_signature' => 'invalid_signature',
            ];

            $request = Request::create('/webhook/paddle', 'POST', $data);

            $reflection = new ReflectionClass($this->controller);
            $method = $reflection->getMethod('verifyWebhookSignature');
            $method->setAccessible(true);

            $result = $method->invokeArgs($this->controller, [$request]);

            expect($result)->toBeFalse();
        });

        it('rejects webhooks without signatures', function () {
            $data = [
                'alert_name' => 'payment_succeeded',
                'p_order_id' => 'PAY-123456',
                'p_sale_gross' => '100.00',
                'event_time' => '2025-07-25 10:00:00',
                // No p_signature field
            ];

            $request = Request::create('/webhook/paddle', 'POST', $data);

            $reflection = new ReflectionClass($this->controller);
            $method = $reflection->getMethod('verifyWebhookSignature');
            $method->setAccessible(true);

            $result = $method->invokeArgs($this->controller, [$request]);

            expect($result)->toBeFalse();
        });

        it('fails when webhook secret is not configured', function () {
            Config::set('services.paddle.webhook_secret', null);

            $data = [
                'alert_name' => 'payment_succeeded',
                'p_signature' => 'some_signature',
            ];

            $request = Request::create('/webhook/paddle', 'POST', $data);

            Log::shouldReceive('warning')
                ->once()
                ->with('Paddle webhook secret not configured');

            $reflection = new ReflectionClass($this->controller);
            $method = $reflection->getMethod('verifyWebhookSignature');
            $method->setAccessible(true);

            $result = $method->invokeArgs($this->controller, [$request]);

            expect($result)->toBeFalse();
        });
    });

    describe('Payment Succeeded Webhook', function () {
        it('processes successful payment webhook correctly', function () {
            $invoice = Invoice::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $this->customer->id,
                'currency_id' => $this->currency->id,
                'status' => 'SENT',
                'total' => 150.00,
                'invoice_number' => 'INV-2025-001',
            ]);

            $data = [
                'alert_name' => 'payment_succeeded',
                'p_order_id' => 'PAY-PADDLE-123',
                'p_sale_gross' => '150.00',
                'event_time' => '2025-07-25 10:30:00',
                'passthrough' => json_encode([
                    'invoice_id' => $invoice->id,
                    'customer_id' => $this->customer->id,
                ]),
            ];

            // Calculate signature
            ksort($data);
            $queryString = http_build_query($data);
            $signature = base64_encode(hash_hmac('sha1', $queryString, $this->webhookSecret, true));
            $data['p_signature'] = $signature;

            $request = Request::create('/webhook/paddle', 'POST', $data);

            Log::shouldReceive('info')->twice(); // webhook received + invoice paid

            $response = $this->controller->handle($request);

            expect($response->getStatusCode())->toBe(200);

            // Verify payment was created
            $payment = Payment::where('invoice_id', $invoice->id)->first();
            expect($payment)->not->toBeNull();
            expect($payment->amount)->toBe(150.00);
            expect($payment->payment_method)->toBe('paddle');
            expect($payment->reference)->toBe('PAY-PADDLE-123');

            // Verify invoice status was updated
            $invoice->refresh();
            expect($invoice->status)->toBe('PAID');
            expect($invoice->paid_status)->toBe(Payment::STATUS_COMPLETED);
        });

        it('handles missing invoice gracefully', function () {
            $data = [
                'alert_name' => 'payment_succeeded',
                'p_order_id' => 'PAY-MISSING-INVOICE',
                'p_sale_gross' => '100.00',
                'event_time' => '2025-07-25 10:30:00',
                'passthrough' => json_encode([
                    'invoice_id' => 99999, // Non-existent invoice ID
                ]),
            ];

            ksort($data);
            $queryString = http_build_query($data);
            $signature = base64_encode(hash_hmac('sha1', $queryString, $this->webhookSecret, true));
            $data['p_signature'] = $signature;

            $request = Request::create('/webhook/paddle', 'POST', $data);

            Log::shouldReceive('info')->once(); // webhook received
            Log::shouldReceive('warning')->once(); // invoice not found

            $response = $this->controller->handle($request);

            expect($response->getStatusCode())->toBe(404);
            expect($response->getContent())->toBe('Invoice not found');
        });

        it('handles webhook without invoice ID in passthrough', function () {
            $data = [
                'alert_name' => 'payment_succeeded',
                'p_order_id' => 'PAY-NO-INVOICE-ID',
                'p_sale_gross' => '75.00',
                'event_time' => '2025-07-25 10:30:00',
                'passthrough' => json_encode([
                    'customer_id' => $this->customer->id,
                    // No invoice_id provided
                ]),
            ];

            ksort($data);
            $queryString = http_build_query($data);
            $signature = base64_encode(hash_hmac('sha1', $queryString, $this->webhookSecret, true));
            $data['p_signature'] = $signature;

            $request = Request::create('/webhook/paddle', 'POST', $data);

            Log::shouldReceive('info')->once(); // webhook received

            $response = $this->controller->handle($request);

            expect($response->getStatusCode())->toBe(200);
            // Should not create any payment since no invoice ID
            expect(Payment::count())->toBe(0);
        });

        it('generates unique payment numbers', function () {
            // Create two invoices
            $invoice1 = Invoice::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $this->customer->id,
                'currency_id' => $this->currency->id,
                'status' => 'SENT',
                'total' => 100.00,
            ]);

            $invoice2 = Invoice::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $this->customer->id,
                'currency_id' => $this->currency->id,
                'status' => 'SENT',
                'total' => 200.00,
            ]);

            // Process two webhooks
            foreach ([$invoice1, $invoice2] as $index => $invoice) {
                $data = [
                    'alert_name' => 'payment_succeeded',
                    'p_order_id' => 'PAY-UNIQUE-'.($index + 1),
                    'p_sale_gross' => $invoice->total,
                    'event_time' => '2025-07-25 10:30:00',
                    'passthrough' => json_encode([
                        'invoice_id' => $invoice->id,
                    ]),
                ];

                ksort($data);
                $queryString = http_build_query($data);
                $signature = base64_encode(hash_hmac('sha1', $queryString, $this->webhookSecret, true));
                $data['p_signature'] = $signature;

                $request = Request::create('/webhook/paddle', 'POST', $data);

                Log::shouldReceive('info')->twice();

                $this->controller->handle($request);
            }

            $payments = Payment::all();
            expect($payments)->toHaveCount(2);

            // Verify payment numbers are unique
            $paymentNumbers = $payments->pluck('payment_number')->toArray();
            expect(count($paymentNumbers))->toBe(count(array_unique($paymentNumbers)));
        });
    });

    describe('Payment Failed Webhook', function () {
        it('logs payment failures correctly', function () {
            $data = [
                'alert_name' => 'payment_failed',
                'p_order_id' => 'PAY-FAILED-123',
                'payment_method' => 'credit_card',
                'passthrough' => json_encode([
                    'invoice_id' => 123,
                ]),
            ];

            ksort($data);
            $queryString = http_build_query($data);
            $signature = base64_encode(hash_hmac('sha1', $queryString, $this->webhookSecret, true));
            $data['p_signature'] = $signature;

            $request = Request::create('/webhook/paddle', 'POST', $data);

            Log::shouldReceive('info')->once(); // webhook received
            Log::shouldReceive('warning')->once(); // payment failed

            $response = $this->controller->handle($request);

            expect($response->getStatusCode())->toBe(200);
        });
    });

    describe('Subscription Payment Webhook', function () {
        it('processes subscription payments correctly', function () {
            $data = [
                'alert_name' => 'subscription_payment_succeeded',
                'subscription_id' => 'SUB-123456',
                'p_order_id' => 'PAY-SUB-789',
                'p_sale_gross' => '50.00',
            ];

            ksort($data);
            $queryString = http_build_query($data);
            $signature = base64_encode(hash_hmac('sha1', $queryString, $this->webhookSecret, true));
            $data['p_signature'] = $signature;

            $request = Request::create('/webhook/paddle', 'POST', $data);

            Log::shouldReceive('info')->twice(); // webhook received + subscription payment

            $response = $this->controller->handle($request);

            expect($response->getStatusCode())->toBe(200);
        });
    });

    describe('Payment Refund Webhook', function () {
        it('processes refunds correctly', function () {
            // Create existing payment
            $invoice = Invoice::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $this->customer->id,
                'currency_id' => $this->currency->id,
                'status' => 'PAID',
                'total' => 200.00,
            ]);

            $payment = Payment::factory()->create([
                'company_id' => $this->company->id,
                'invoice_id' => $invoice->id,
                'customer_id' => $this->customer->id,
                'amount' => 200.00,
                'currency_id' => $this->currency->id,
                'reference' => 'PAY-REFUND-TEST',
                'payment_method' => 'paddle',
            ]);

            $data = [
                'alert_name' => 'payment_refunded',
                'p_order_id' => 'PAY-REFUND-TEST',
                'p_gross_refund' => '200.00',
            ];

            ksort($data);
            $queryString = http_build_query($data);
            $signature = base64_encode(hash_hmac('sha1', $queryString, $this->webhookSecret, true));
            $data['p_signature'] = $signature;

            $request = Request::create('/webhook/paddle', 'POST', $data);

            Log::shouldReceive('info')->twice(); // webhook received + refund processed

            $response = $this->controller->handle($request);

            expect($response->getStatusCode())->toBe(200);

            // Verify payment status was updated
            $payment->refresh();
            expect($payment->status)->toBe('REFUNDED');
            expect($payment->notes)->toContain('Refunded via Paddle webhook: 200.00');

            // Verify invoice status was reverted
            $invoice->refresh();
            expect($invoice->status)->toBe('SENT');
            expect($invoice->paid_status)->toBe('UNPAID');
        });

        it('handles refund for non-existent payment', function () {
            $data = [
                'alert_name' => 'payment_refunded',
                'p_order_id' => 'PAY-NONEXISTENT',
                'p_gross_refund' => '100.00',
            ];

            ksort($data);
            $queryString = http_build_query($data);
            $signature = base64_encode(hash_hmac('sha1', $queryString, $this->webhookSecret, true));
            $data['p_signature'] = $signature;

            $request = Request::create('/webhook/paddle', 'POST', $data);

            Log::shouldReceive('info')->twice(); // webhook received + refund logged

            $response = $this->controller->handle($request);

            expect($response->getStatusCode())->toBe(200);
            // Should not crash when payment is not found
        });
    });

    describe('Error Handling', function () {
        it('rejects webhooks with invalid signatures', function () {
            $data = [
                'alert_name' => 'payment_succeeded',
                'p_order_id' => 'PAY-INVALID-SIG',
                'p_signature' => 'definitely_not_valid',
            ];

            $request = Request::create('/webhook/paddle', 'POST', $data);

            Log::shouldReceive('warning')->once();

            $response = $this->controller->handle($request);

            expect($response->getStatusCode())->toBe(401);
            expect($response->getContent())->toBe('Unauthorized');
        });

        it('handles unknown webhook events gracefully', function () {
            $data = [
                'alert_name' => 'unknown_event_type',
                'p_order_id' => 'PAY-UNKNOWN-EVENT',
            ];

            ksort($data);
            $queryString = http_build_query($data);
            $signature = base64_encode(hash_hmac('sha1', $queryString, $this->webhookSecret, true));
            $data['p_signature'] = $signature;

            $request = Request::create('/webhook/paddle', 'POST', $data);

            Log::shouldReceive('info')->twice(); // webhook received + unhandled event

            $response = $this->controller->handle($request);

            expect($response->getStatusCode())->toBe(200);
        });

        it('handles exceptions during webhook processing', function () {
            // Mock Invoice::find to throw an exception
            $this->mock(Invoice::class, function ($mock) {
                $mock->shouldReceive('find')->andThrow(new Exception('Database error'));
            });

            $data = [
                'alert_name' => 'payment_succeeded',
                'p_order_id' => 'PAY-EXCEPTION-TEST',
                'p_sale_gross' => '100.00',
                'passthrough' => json_encode(['invoice_id' => 123]),
            ];

            ksort($data);
            $queryString = http_build_query($data);
            $signature = base64_encode(hash_hmac('sha1', $queryString, $this->webhookSecret, true));
            $data['p_signature'] = $signature;

            $request = Request::create('/webhook/paddle', 'POST', $data);

            Log::shouldReceive('info')->once(); // webhook received
            Log::shouldReceive('error')->once(); // exception logged

            $response = $this->controller->handle($request);

            expect($response->getStatusCode())->toBe(500);
            expect($response->getContent())->toBe('Internal Server Error');
        });
    });

    describe('Webhook Data Processing', function () {
        it('correctly parses passthrough data', function () {
            $invoice = Invoice::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $this->customer->id,
                'currency_id' => $this->currency->id,
                'status' => 'SENT',
                'total' => 300.00,
            ]);

            $passthroughData = [
                'invoice_id' => $invoice->id,
                'customer_id' => $this->customer->id,
                'metadata' => 'test_metadata',
            ];

            $data = [
                'alert_name' => 'payment_succeeded',
                'p_order_id' => 'PAY-PASSTHROUGH-TEST',
                'p_sale_gross' => '300.00',
                'event_time' => '2025-07-25 15:45:00',
                'passthrough' => json_encode($passthroughData),
            ];

            ksort($data);
            $queryString = http_build_query($data);
            $signature = base64_encode(hash_hmac('sha1', $queryString, $this->webhookSecret, true));
            $data['p_signature'] = $signature;

            $request = Request::create('/webhook/paddle', 'POST', $data);

            Log::shouldReceive('info')->twice();

            $response = $this->controller->handle($request);

            expect($response->getStatusCode())->toBe(200);

            $payment = Payment::where('reference', 'PAY-PASSTHROUGH-TEST')->first();
            expect($payment)->not->toBeNull();
            expect($payment->invoice_id)->toBe($invoice->id);
        });

        it('handles malformed passthrough JSON gracefully', function () {
            $data = [
                'alert_name' => 'payment_succeeded',
                'p_order_id' => 'PAY-MALFORMED-JSON',
                'p_sale_gross' => '100.00',
                'passthrough' => '{invalid_json',  // Malformed JSON
            ];

            ksort($data);
            $queryString = http_build_query($data);
            $signature = base64_encode(hash_hmac('sha1', $queryString, $this->webhookSecret, true));
            $data['p_signature'] = $signature;

            $request = Request::create('/webhook/paddle', 'POST', $data);

            Log::shouldReceive('info')->once(); // webhook received

            $response = $this->controller->handle($request);

            expect($response->getStatusCode())->toBe(200);
            // Should not crash on malformed JSON
        });

        it('correctly formats payment dates from event_time', function () {
            $invoice = Invoice::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $this->customer->id,
                'currency_id' => $this->currency->id,
                'status' => 'SENT',
                'total' => 175.00,
            ]);

            $eventTime = '2025-07-25 14:30:45';

            $data = [
                'alert_name' => 'payment_succeeded',
                'p_order_id' => 'PAY-DATE-TEST',
                'p_sale_gross' => '175.00',
                'event_time' => $eventTime,
                'passthrough' => json_encode(['invoice_id' => $invoice->id]),
            ];

            ksort($data);
            $queryString = http_build_query($data);
            $signature = base64_encode(hash_hmac('sha1', $queryString, $this->webhookSecret, true));
            $data['p_signature'] = $signature;

            $request = Request::create('/webhook/paddle', 'POST', $data);

            Log::shouldReceive('info')->twice();

            $response = $this->controller->handle($request);

            expect($response->getStatusCode())->toBe(200);

            $payment = Payment::where('reference', 'PAY-DATE-TEST')->first();
            expect($payment->payment_date)->toBe('2025-07-25');
        });
    });

    afterEach(function () {
        Mockery::close();
    });
});
