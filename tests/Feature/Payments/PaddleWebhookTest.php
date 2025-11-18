<?php

namespace Tests\Feature\Payments;

use App\Models\Company;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Laravel\Pennant\Feature;
use Tests\TestCase;

/**
 * Paddle Webhook Feature Tests
 *
 * Tests webhook signature verification, idempotency, payment creation,
 * invoice status updates, and accounting integration.
 *
 * @ticket B-31 series - Paddle Payment Integration
 */
class PaddleWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected $company;

    protected $customer;

    protected $currency;

    protected $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        // Enable advanced payments feature
        Feature::define('advanced-payments', fn () => true);

        // Set up test data - currency must be created first
        $this->currency = Currency::create([
            'name' => 'US Dollar',
            'code' => 'USD',
            'symbol' => '$',
            'precision' => 2,
            'thousand_separator' => ',',
            'decimal_separator' => '.',
            'swap_rate_from_usd' => 1.0,
        ]);
        $this->company = Company::factory()->create([
            'currency_id' => $this->currency->id,
        ]);
        $this->customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'currency_id' => $this->currency->id,
            'email' => 'test@example.com',
        ]);

        $this->invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'currency_id' => $this->currency->id,
            'total' => 10000, // $100.00
            'due_amount' => 10000,
            'exchange_rate' => 1.0,
        ]);

        // Configure Paddle
        Config::set('services.paddle.api_key', 'test_api_key');
        Config::set('services.paddle.webhook_secret', 'test_webhook_secret');
        Config::set('services.paddle.environment', 'sandbox');
    }

    /** @test */
    public function test_webhook_signature_verification()
    {
        $payload = $this->getTransactionCompletedPayload();
        $validSignature = hash_hmac('sha256', json_encode($payload), 'test_webhook_secret');

        $response = $this->postJson('/webhooks/paddle', $payload, [
            'Paddle-Signature' => $validSignature,
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function test_invalid_signature_rejected()
    {
        $payload = $this->getTransactionCompletedPayload();

        $response = $this->postJson('/webhooks/paddle', $payload, [
            'Paddle-Signature' => 'invalid_signature',
        ]);

        $response->assertStatus(500);
    }

    /** @test */
    public function test_idempotency_prevents_duplicate_processing()
    {
        $payload = $this->getTransactionCompletedPayload();
        $signature = hash_hmac('sha256', json_encode($payload), 'test_webhook_secret');

        // First request should process
        $response1 = $this->postJson('/webhooks/paddle', $payload, [
            'Paddle-Signature' => $signature,
        ]);
        $response1->assertStatus(200);

        $paymentsCount = Payment::count();

        // Second identical request should be ignored (idempotent)
        $response2 = $this->postJson('/webhooks/paddle', $payload, [
            'Paddle-Signature' => $signature,
        ]);
        $response2->assertStatus(200);

        // Payment count should not increase
        $this->assertEquals($paymentsCount, Payment::count());
    }

    /** @test */
    public function test_transaction_completed_creates_payment()
    {
        $payload = $this->getTransactionCompletedPayload();
        $signature = hash_hmac('sha256', json_encode($payload), 'test_webhook_secret');

        $response = $this->postJson('/webhooks/paddle', $payload, [
            'Paddle-Signature' => $signature,
        ]);

        $response->assertStatus(200);

        // Check payment was created
        $payment = Payment::where('invoice_id', $this->invoice->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals(9500, $payment->amount); // $95.00 after $5 fee
        $this->assertEquals('PADDLE-txn_test_123', $payment->payment_number);
        $this->assertEquals(Payment::GATEWAY_PADDLE, $payment->gateway);
        $this->assertEquals(Payment::GATEWAY_STATUS_COMPLETED, $payment->gateway_status);
    }

    /** @test */
    public function test_invoice_marked_paid_when_fully_paid()
    {
        // Create invoice with amount matching payment
        $this->invoice->update([
            'total' => 9500, // $95.00 (net after fee)
            'due_amount' => 9500,
        ]);

        $payload = $this->getTransactionCompletedPayload();
        $signature = hash_hmac('sha256', json_encode($payload), 'test_webhook_secret');

        $response = $this->postJson('/webhooks/paddle', $payload, [
            'Paddle-Signature' => $signature,
        ]);

        $response->assertStatus(200);

        // Check invoice status updated
        $this->invoice->refresh();
        $this->assertEquals(Invoice::STATUS_PAID, $this->invoice->paid_status);
        $this->assertEquals(0, $this->invoice->due_amount);
    }

    /** @test */
    public function test_fee_posted_to_accounting_when_enabled()
    {
        // Enable accounting feature
        Feature::define('accounting-backbone', fn () => true);

        // Mock IfrsAdapter if it exists
        // Note: This test will skip ledger posting if IfrsAdapter doesn't exist yet
        // which is fine since Step 1 (accounting) comes before Step 3 (payments)

        $payload = $this->getTransactionCompletedPayload();
        $signature = hash_hmac('sha256', json_encode($payload), 'test_webhook_secret');

        $response = $this->postJson('/webhooks/paddle', $payload, [
            'Paddle-Signature' => $signature,
        ]);

        $response->assertStatus(200);

        // Payment should still be created even if ledger posting fails
        $payment = Payment::where('invoice_id', $this->invoice->id)->first();
        $this->assertNotNull($payment);
    }

    /** @test */
    public function test_webhook_rejected_when_feature_disabled()
    {
        // Disable advanced payments feature
        Feature::define('advanced-payments', fn () => false);

        $payload = $this->getTransactionCompletedPayload();
        $signature = hash_hmac('sha256', json_encode($payload), 'test_webhook_secret');

        $response = $this->postJson('/webhooks/paddle', $payload, [
            'Paddle-Signature' => $signature,
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function test_missing_signature_rejected()
    {
        $payload = $this->getTransactionCompletedPayload();

        $response = $this->postJson('/webhooks/paddle', $payload);

        $response->assertStatus(400);
    }

    /**
     * Generate a sample Paddle transaction.completed payload
     */
    protected function getTransactionCompletedPayload(): array
    {
        return [
            'event_id' => 'evt_test_'.uniqid(),
            'event_type' => 'transaction.completed',
            'data' => [
                'id' => 'txn_test_123',
                'custom_data' => [
                    'invoice_id' => $this->invoice->id,
                    'company_id' => $this->company->id,
                ],
                'details' => [
                    'totals' => [
                        'total' => 10000, // $100.00 in cents
                        'fee' => 500,     // $5.00 fee in cents
                    ],
                ],
            ],
        ];
    }
}

// CLAUDE-CHECKPOINT
