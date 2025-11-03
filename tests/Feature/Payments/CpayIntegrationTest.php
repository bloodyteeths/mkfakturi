<?php

namespace Tests\Feature\Payments;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Modules\Mk\Services\CpayDriver;
use Tests\TestCase;

/**
 * CPAY Payment Integration Tests
 *
 * Tests the CPAY payment gateway integration including:
 * - Checkout URL generation with signature
 * - Payment callback processing
 * - Signature verification
 * - Idempotency checks
 * - Invoice status updates
 *
 * @group cpay
 * @group payments
 */
class CpayIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Customer $customer;
    protected Invoice $invoice;
    protected CpayDriver $cpayDriver;

    /**
     * Set up test environment
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Run database seeders
        $this->artisan('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
        $this->artisan('db:seed', ['--class' => 'DemoSeeder', '--force' => true]);

        // Enable feature flag for tests
        Config::set('mk.features.advanced_payments', true);

        // Set up CPAY config
        Config::set('mk.payment_gateways.cpay', [
            'merchant_id' => 'TEST_MERCHANT_123',
            'secret_key' => 'test_secret_key_456',
            'payment_url' => 'https://cpay.com.mk/payment',
        ]);

        // Create test company
        $this->company = Company::factory()->create([
            'name' => 'Test Company',
        ]);

        // Create test customer
        $this->customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Test Customer',
            'email' => 'customer@test.mk',
        ]);

        // Create test invoice
        $this->invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-2025-001',
            'total' => 10000, // 100.00 MKD (stored in cents)
            'status' => Invoice::STATUS_SENT,
        ]);

        // Initialize CPAY driver
        $this->cpayDriver = new CpayDriver();
    }

    /**
     * Test: Checkout URL is generated with valid signature
     *
     * @test
     */
    public function test_checkout_url_generated_with_valid_signature()
    {
        // Act: Generate checkout URL
        $result = $this->cpayDriver->createCheckout($this->invoice);

        // Assert: Check result structure
        $this->assertArrayHasKey('checkout_url', $result);
        $this->assertArrayHasKey('params', $result);

        // Assert: Check params contain required fields
        $params = $result['params'];
        $this->assertEquals('TEST_MERCHANT_123', $params['merchant_id']);
        $this->assertEquals('100.00', $params['amount']);
        $this->assertEquals('MKD', $params['currency']);
        $this->assertEquals('INV-2025-001', $params['order_id']);
        $this->assertArrayHasKey('signature', $params);

        // Assert: Signature is SHA256 hash (64 characters)
        $this->assertEquals(64, strlen($params['signature']));

        // Assert: Checkout URL contains query parameters
        $this->assertStringContainsString('merchant_id=TEST_MERCHANT_123', $result['checkout_url']);
        $this->assertStringContainsString('amount=100.00', $result['checkout_url']);
        $this->assertStringContainsString('currency=MKD', $result['checkout_url']);
        $this->assertStringContainsString('order_id=INV-2025-001', $result['checkout_url']);
    }

    /**
     * Test: Callback creates payment record
     *
     * @test
     */
    public function test_callback_creates_payment()
    {
        // Arrange: Prepare callback data
        $callbackData = [
            'merchant_id' => 'TEST_MERCHANT_123',
            'amount' => '100.00',
            'currency' => 'MKD',
            'order_id' => 'INV-2025-001',
            'transaction_id' => 'TXN-TEST-12345',
            'status' => 'APPROVED',
        ];

        // Generate valid signature
        $callbackData['signature'] = $this->generateTestSignature($callbackData);

        // Create request
        $request = new \Illuminate\Http\Request($callbackData);

        // Act: Process callback
        $this->cpayDriver->handleCallback($request);

        // Assert: Payment was created (amount stored in cents = 10000)
        $this->assertDatabaseHas('payments', [
            'invoice_id' => $this->invoice->id,
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'amount' => 10000, // 100.00 MKD stored as cents
            'transaction_reference' => 'TXN-TEST-12345',
            'payment_mode' => Payment::PAYMENT_MODE_CREDIT_CARD,
        ]);

        // Assert: Invoice status updated to PAID
        $this->invoice->refresh();
        $this->assertEquals(Invoice::STATUS_PAID, $this->invoice->status);
    }

    /**
     * Test: Invalid signature is rejected
     *
     * @test
     */
    public function test_invalid_signature_rejected()
    {
        // Arrange: Prepare callback data with invalid signature
        $callbackData = [
            'merchant_id' => 'TEST_MERCHANT_123',
            'amount' => '100.00',
            'currency' => 'MKD',
            'order_id' => 'INV-2025-001',
            'transaction_id' => 'TXN-TEST-12345',
            'status' => 'APPROVED',
            'signature' => 'invalid_signature_hash',
        ];

        // Create request
        $request = new \Illuminate\Http\Request($callbackData);

        // Act & Assert: Exception is thrown
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid CPAY signature');

        $this->cpayDriver->handleCallback($request);

        // Assert: No payment was created
        $this->assertDatabaseMissing('payments', [
            'invoice_id' => $this->invoice->id,
            'transaction_reference' => 'TXN-TEST-12345',
        ]);
    }

    /**
     * Test: Idempotency prevents duplicate payments
     *
     * @test
     */
    public function test_idempotency_prevents_duplicates()
    {
        // Arrange: Prepare callback data
        $callbackData = [
            'merchant_id' => 'TEST_MERCHANT_123',
            'amount' => '100.00',
            'currency' => 'MKD',
            'order_id' => 'INV-2025-001',
            'transaction_id' => 'TXN-TEST-12345',
            'status' => 'APPROVED',
        ];

        // Generate valid signature
        $callbackData['signature'] = $this->generateTestSignature($callbackData);

        // Create request
        $request1 = new \Illuminate\Http\Request($callbackData);
        $request2 = new \Illuminate\Http\Request($callbackData);

        // Act: Process callback twice
        $this->cpayDriver->handleCallback($request1);
        $this->cpayDriver->handleCallback($request2);

        // Assert: Only one payment was created
        $paymentCount = Payment::where('invoice_id', $this->invoice->id)
            ->where('transaction_reference', 'TXN-TEST-12345')
            ->count();

        $this->assertEquals(1, $paymentCount);

        // Assert: Cache key exists for idempotency
        $this->assertTrue(Cache::has('cpay_txn_TXN-TEST-12345'));
    }

    /**
     * Test: Invoice is marked as PAID after full payment
     *
     * @test
     */
    public function test_invoice_marked_paid()
    {
        // Arrange: Create invoice with specific total
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-2025-002',
            'total' => 10000, // 100.00 MKD
            'status' => Invoice::STATUS_SENT,
        ]);

        // Prepare callback data for full payment
        $callbackData = [
            'merchant_id' => 'TEST_MERCHANT_123',
            'amount' => '100.00',
            'currency' => 'MKD',
            'order_id' => 'INV-2025-002',
            'transaction_id' => 'TXN-TEST-67890',
            'status' => 'APPROVED',
        ];

        // Generate valid signature
        $callbackData['signature'] = $this->generateTestSignature($callbackData);

        // Create request
        $request = new \Illuminate\Http\Request($callbackData);

        // Act: Process callback
        $this->cpayDriver->handleCallback($request);

        // Assert: Invoice status is PAID
        $invoice->refresh();
        $this->assertEquals(Invoice::STATUS_PAID, $invoice->status);

        // Assert: Payment was created (amount in cents)
        $this->assertDatabaseHas('payments', [
            'invoice_id' => $invoice->id,
            'amount' => 10000, // 100.00 MKD stored as cents
            'transaction_reference' => 'TXN-TEST-67890',
        ]);
    }

    /**
     * Test: Feature flag disabled prevents checkout creation
     *
     * @test
     */
    public function test_feature_flag_disabled_prevents_checkout()
    {
        // Arrange: Disable feature flag
        Config::set('mk.features.advanced_payments', false);

        // Act & Assert: Exception is thrown
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Advanced payments feature is disabled');

        $this->cpayDriver->createCheckout($this->invoice);
    }

    /**
     * Test: Feature flag disabled prevents callback processing
     *
     * @test
     */
    public function test_feature_flag_disabled_prevents_callback()
    {
        // Arrange: Disable feature flag
        Config::set('mk.features.advanced_payments', false);

        // Prepare callback data
        $callbackData = [
            'merchant_id' => 'TEST_MERCHANT_123',
            'amount' => '100.00',
            'currency' => 'MKD',
            'order_id' => 'INV-2025-001',
            'transaction_id' => 'TXN-TEST-12345',
            'status' => 'APPROVED',
        ];

        $callbackData['signature'] = $this->generateTestSignature($callbackData);
        $request = new \Illuminate\Http\Request($callbackData);

        // Act & Assert: Exception is thrown
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Advanced payments feature is disabled');

        $this->cpayDriver->handleCallback($request);
    }

    /**
     * Test: Webhook endpoint exists and is accessible
     *
     * @test
     */
    public function test_webhook_endpoint_accessible()
    {
        // Arrange: Prepare callback data
        $callbackData = [
            'merchant_id' => 'TEST_MERCHANT_123',
            'amount' => '100.00',
            'currency' => 'MKD',
            'order_id' => 'INV-2025-001',
            'transaction_id' => 'TXN-TEST-12345',
            'status' => 'APPROVED',
        ];

        $callbackData['signature'] = $this->generateTestSignature($callbackData);

        // Act: Post to webhook endpoint
        $response = $this->post('/webhooks/cpay/callback', $callbackData);

        // Assert: Response is successful
        $response->assertStatus(200);
        $response->assertSeeText('OK');

        // Assert: Payment was created (amount in cents)
        $this->assertDatabaseHas('payments', [
            'invoice_id' => $this->invoice->id,
            'amount' => 10000, // 100.00 MKD stored as cents
            'transaction_reference' => 'TXN-TEST-12345',
        ]);
    }

    /**
     * Helper: Generate test signature
     *
     * This replicates the signature generation logic from CpayDriver
     * Note: Excludes 'timestamp' field as per existing driver logic
     *
     * @param array $data
     * @return string
     */
    protected function generateTestSignature(array $data): string
    {
        $signatureData = $data;
        unset($signatureData['signature']);
        unset($signatureData['timestamp']); // Exclude timestamp as per driver logic

        // Sort alphabetically
        ksort($signatureData);

        // Build signature string with pipe delimiter (as per existing driver)
        $signatureString = implode('|', $signatureData) . '|' . 'test_secret_key_456';

        // Generate SHA256 hash
        return hash('sha256', $signatureString);
    }
}
// CLAUDE-CHECKPOINT
