<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Customer;
use App\Models\Company;
use App\Models\Currency;
use App\Models\PaymentMethod;
use App\Services\PaymentService;
use Modules\Mk\Services\CpayDriver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

/**
 * CPAY Gateway End-to-End Test Suite
 * 
 * Comprehensive testing for CPAY integration with existing payment processing
 * architecture. Tests complete workflow from invoice creation to payment completion.
 * 
 * Test Coverage:
 * - Invoice payment flow with Macedonia cards
 * - CPAY driver integration with PaymentService
 * - Payment callback processing and verification
 * - Invoice status updates (unpaid → paid)
 * - Payment record creation and management
 * - Error handling and rollback scenarios
 * - Macedonia-specific payment scenarios
 * 
 * Success Criteria:
 * - Invoice can be paid with MK card via CPAY
 * - Complete payment workflow from invoice to paid status works
 * - Payment records are properly created and linked
 * - Invoice status updates correctly after payment
 * - Error scenarios are handled gracefully
 * 
 * Required for CPAY-02 task: "Ensure invoice payment flow works with Macedonia cards"
 * 
 * @version 1.0.0
 * @created 2025-07-26 - CPAY-02 end-to-end testing implementation
 * @author Claude Code - Based on ROADMAP-FINAL requirements
 */
class CpayGatewayTest extends TestCase
{
    use RefreshDatabase, WithFaker;
    
    protected $paymentService;
    protected $cpayDriver;
    protected $company;
    protected $customer;
    protected $currency;
    protected $invoice;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Initialize services
        $this->paymentService = new PaymentService();
        $this->cpayDriver = new CpayDriver();
        
        // Create test data
        $this->createTestData();
        
        // Configure CPAY test environment
        $this->configureCpayTestEnvironment();
    }
    
    /** @test */
    public function it_can_create_cpay_payment_request_for_macedonia_invoice()
    {
        // Given: An unpaid invoice in MKD currency
        $this->assertEquals('MKD', $this->invoice->currency->code);
        $this->assertGreaterThan(0, $this->invoice->due_amount);
        
        // When: Creating CPAY payment request
        $result = $this->paymentService->createInvoicePaymentRequest(
            $this->invoice, 
            PaymentService::GATEWAY_CPAY,
            [
                'preferred_bank' => '250' // Stopanska Bank
            ]
        );
        
        // Then: Payment request should be created successfully
        $this->assertTrue($result['success'], 'Payment request creation should succeed');
        $this->assertEquals(PaymentService::GATEWAY_CPAY, $result['gateway']);
        $this->assertNotNull($result['payment']);
        $this->assertNotNull($result['payment_request']);
        $this->assertNotNull($result['redirect_url']);
        
        // Verify payment record was created
        $payment = $result['payment'];
        $this->assertEquals($this->invoice->id, $payment->invoice_id);
        $this->assertEquals($this->customer->id, $payment->customer_id);
        $this->assertEquals($this->company->id, $payment->company_id);
        $this->assertEquals(PaymentService::GATEWAY_CPAY, $payment->gateway);
        $this->assertEquals(Payment::GATEWAY_STATUS_PENDING, $payment->gateway_status);
        $this->assertNotNull($payment->gateway_order_id);
        
        Log::info('CPAY payment request created successfully', [
            'payment_id' => $payment->id,
            'order_id' => $payment->gateway_order_id,
            'amount' => $payment->amount
        ]);
    }
    
    /** @test */
    public function it_processes_successful_cpay_payment_callback()
    {
        // Given: A pending payment request
        $paymentResult = $this->paymentService->createInvoicePaymentRequest(
            $this->invoice, 
            PaymentService::GATEWAY_CPAY
        );
        $payment = $paymentResult['payment'];
        $orderId = $payment->gateway_order_id;
        
        // When: Processing successful CPAY callback
        $callbackData = [
            'status' => 'APPROVED',
            'order_id' => $orderId,
            'transaction_id' => 'TXN-MK-' . uniqid(),
            'amount' => $payment->amount,
            'currency' => 'MKD',
            'bank_code' => '250',
            'auth_code' => 'AUTH' . rand(100000, 999999),
            'card_mask' => '****-****-****-1234',
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ];
        
        // Generate valid signature for callback
        $callbackData['signature'] = $this->generateCpaySignature($callbackData);
        
        $callbackResult = $this->paymentService->processPaymentCallback(
            $callbackData, 
            PaymentService::GATEWAY_CPAY
        );
        
        // Then: Callback should be processed successfully
        $this->assertTrue($callbackResult['success'], 'Callback processing should succeed');
        $this->assertNotNull($callbackResult['payment']);
        
        // Verify payment record was updated
        $payment->refresh();
        $this->assertEquals(Payment::GATEWAY_STATUS_COMPLETED, $payment->gateway_status);
        $this->assertEquals($callbackData['transaction_id'], $payment->gateway_transaction_id);
        $this->assertNotNull($payment->gateway_response);
        
        // Verify invoice status was updated
        $this->invoice->refresh();
        $this->assertEquals(Invoice::STATUS_PAID, $this->invoice->paid_status);
        $this->assertEquals(0, $this->invoice->due_amount);
        
        Log::info('CPAY payment callback processed successfully', [
            'payment_id' => $payment->id,
            'transaction_id' => $payment->gateway_transaction_id,
            'invoice_status' => $this->invoice->paid_status
        ]);
    }
    
    /** @test */
    public function it_handles_failed_cpay_payment_callback()
    {
        // Given: A pending payment request
        $paymentResult = $this->paymentService->createInvoicePaymentRequest(
            $this->invoice, 
            PaymentService::GATEWAY_CPAY
        );
        $payment = $paymentResult['payment'];
        $orderId = $payment->gateway_order_id;
        
        // When: Processing failed CPAY callback
        $callbackData = [
            'status' => 'DECLINED',
            'order_id' => $orderId,
            'amount' => $payment->amount,
            'currency' => 'MKD',
            'bank_code' => '250',
            'error_message' => 'Insufficient funds',
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ];
        
        $callbackData['signature'] = $this->generateCpaySignature($callbackData);
        
        $callbackResult = $this->paymentService->processPaymentCallback(
            $callbackData, 
            PaymentService::GATEWAY_CPAY
        );
        
        // Then: Callback should be processed but payment failed
        $this->assertTrue($callbackResult['success'], 'Callback processing should succeed');
        
        // Verify payment record shows failure
        $payment->refresh();
        $this->assertEquals(Payment::GATEWAY_STATUS_FAILED, $payment->gateway_status);
        $this->assertNotNull($payment->gateway_response);
        
        // Verify invoice remains unpaid
        $this->invoice->refresh();
        $this->assertNotEquals(Invoice::STATUS_PAID, $this->invoice->paid_status);
        $this->assertGreaterThan(0, $this->invoice->due_amount);
        
        Log::info('CPAY payment failure handled correctly', [
            'payment_id' => $payment->id,
            'gateway_status' => $payment->gateway_status,
            'invoice_status' => $this->invoice->paid_status
        ]);
    }
    
    /** @test */
    public function it_automatically_routes_mkd_invoices_to_cpay()
    {
        // Given: An invoice in MKD currency (no explicit gateway specified)
        $this->assertEquals('MKD', $this->invoice->currency->code);
        
        // When: Creating payment request without specifying gateway
        $result = $this->paymentService->createInvoicePaymentRequest($this->invoice);
        
        // Then: Should automatically route to CPAY
        $this->assertTrue($result['success']);
        $this->assertEquals(PaymentService::GATEWAY_CPAY, $result['gateway']);
        
        Log::info('MKD invoice automatically routed to CPAY', [
            'invoice_id' => $this->invoice->id,
            'currency' => $this->invoice->currency->code,
            'gateway' => $result['gateway']
        ]);
    }
    
    /** @test */
    public function it_handles_invalid_signature_in_callback()
    {
        // Given: A pending payment request
        $paymentResult = $this->paymentService->createInvoicePaymentRequest(
            $this->invoice, 
            PaymentService::GATEWAY_CPAY
        );
        $payment = $paymentResult['payment'];
        
        // When: Processing callback with invalid signature
        $callbackData = [
            'status' => 'APPROVED',
            'order_id' => $payment->gateway_order_id,
            'transaction_id' => 'TXN-FAKE',
            'amount' => $payment->amount,
            'currency' => 'MKD',
            'signature' => 'invalid_signature_123456'
        ];
        
        $callbackResult = $this->paymentService->processPaymentCallback(
            $callbackData, 
            PaymentService::GATEWAY_CPAY
        );
        
        // Then: Callback should fail due to invalid signature
        $this->assertFalse($callbackResult['success']);
        $this->assertStringContainsString('signature', strtolower($callbackResult['error']));
        
        // Verify payment remains pending
        $payment->refresh();
        $this->assertEquals(Payment::GATEWAY_STATUS_PENDING, $payment->gateway_status);
        
        Log::info('Invalid signature correctly rejected', [
            'payment_id' => $payment->id,
            'error' => $callbackResult['error']
        ]);
    }
    
    /** @test */
    public function it_validates_payment_amounts_for_macedonia_banking()
    {
        // Test various payment amounts for Macedonia banking validation
        $testCases = [
            ['amount' => 0.001, 'should_fail' => true, 'reason' => 'Too small'],
            ['amount' => 0.01, 'should_fail' => false, 'reason' => 'Minimum valid'],
            ['amount' => 150.50, 'should_fail' => false, 'reason' => 'Normal amount'],
            ['amount' => 999999.99, 'should_fail' => false, 'reason' => 'Maximum valid'],
            ['amount' => 1000000, 'should_fail' => true, 'reason' => 'Too large'],
            ['amount' => -100, 'should_fail' => true, 'reason' => 'Negative amount']
        ];
        
        foreach ($testCases as $testCase) {
            // Create test invoice with specific amount
            $testInvoice = $this->createTestInvoice($testCase['amount']);
            
            try {
                $result = $this->paymentService->createInvoicePaymentRequest(
                    $testInvoice, 
                    PaymentService::GATEWAY_CPAY
                );
                
                if ($testCase['should_fail']) {
                    $this->assertFalse($result['success'], 
                        "Amount {$testCase['amount']} should fail: {$testCase['reason']}");
                } else {
                    $this->assertTrue($result['success'], 
                        "Amount {$testCase['amount']} should succeed: {$testCase['reason']}");
                }
                
            } catch (\Exception $e) {
                if (!$testCase['should_fail']) {
                    $this->fail("Amount {$testCase['amount']} should not throw exception: {$e->getMessage()}");
                }
            }
        }
        
        Log::info('Payment amount validation tests completed');
    }
    
    /** @test */
    public function it_supports_macedonia_bank_codes()
    {
        $macedoniaBanks = [
            '250' => 'Stopanska Banka AD Skopje',
            '260' => 'Komercijalna Banka AD Skopje',
            '270' => 'TTK Banka AD Skopje',
            '300' => 'NLB Tutunska Banka AD Skopje'
        ];
        
        foreach ($macedoniaBanks as $bankCode => $bankName) {
            // When: Creating payment with specific bank preference
            $result = $this->paymentService->createInvoicePaymentRequest(
                $this->invoice, 
                PaymentService::GATEWAY_CPAY,
                ['preferred_bank' => $bankCode]
            );
            
            // Then: Payment should be created successfully
            $this->assertTrue($result['success'], "Bank {$bankCode} ({$bankName}) should be supported");
            
            // Verify bank preference is included in payment data
            $paymentRequest = $result['payment_request'];
            $this->assertArrayHasKey('payment_data', $paymentRequest);
            
            Log::info('Macedonia bank supported', [
                'bank_code' => $bankCode,
                'bank_name' => $bankName
            ]);
        }
    }
    
    /** @test */
    public function it_handles_macedonia_vat_calculations()
    {
        // Test Macedonia VAT rates: 18% standard, 5% reduced
        $testCases = [
            ['net' => 1000.00, 'vat_rate' => 0.18, 'expected_vat' => 180.00, 'expected_total' => 1180.00],
            ['net' => 1000.00, 'vat_rate' => 0.05, 'expected_vat' => 50.00, 'expected_total' => 1050.00],
            ['net' => 2500.50, 'vat_rate' => 0.18, 'expected_vat' => 450.09, 'expected_total' => 2950.59]
        ];
        
        foreach ($testCases as $testCase) {
            $vatResult = $this->cpayDriver->calculateMacedoniaVat(
                $testCase['net'], 
                $testCase['vat_rate']
            );
            
            $this->assertEquals($testCase['expected_vat'], $vatResult['vat_amount'], 
                "VAT calculation incorrect for {$testCase['vat_rate']} rate", 0.01);
            $this->assertEquals($testCase['expected_total'], $vatResult['total_amount'], 
                "Total calculation incorrect for {$testCase['vat_rate']} rate", 0.01);
            $this->assertEquals('MKD', $vatResult['currency']);
        }
        
        Log::info('Macedonia VAT calculations verified');
    }
    
    /** @test */
    public function it_provides_payment_service_status()
    {
        // When: Getting payment service status
        $status = $this->paymentService->getServiceStatus();
        
        // Then: Should return comprehensive status information
        $this->assertArrayHasKey('service_name', $status);
        $this->assertArrayHasKey('version', $status);
        $this->assertArrayHasKey('gateways', $status);
        $this->assertArrayHasKey('features', $status);
        
        // Verify CPAY gateway is available
        $this->assertArrayHasKey(PaymentService::GATEWAY_CPAY, $status['gateways']);
        $cpayStatus = $status['gateways'][PaymentService::GATEWAY_CPAY];
        $this->assertEquals('operational', $cpayStatus['status']);
        $this->assertArrayHasKey('configuration', $cpayStatus);
        
        // Verify key features are available
        $features = $status['features'];
        $this->assertTrue($features['multi_gateway_support']);
        $this->assertTrue($features['automatic_gateway_routing']);
        $this->assertTrue($features['invoice_integration']);
        $this->assertTrue($features['macedonia_domestic_payments']);
        
        Log::info('Payment service status verified', $status);
    }
    
    /** @test */
    public function it_tests_cpay_connectivity()
    {
        // When: Testing CPAY connectivity
        $connectivityResults = $this->paymentService->testConnectivity();
        
        // Then: Should return connectivity status for all gateways
        $this->assertArrayHasKey(PaymentService::GATEWAY_CPAY, $connectivityResults);
        $this->assertArrayHasKey(PaymentService::GATEWAY_PADDLE, $connectivityResults);
        $this->assertArrayHasKey(PaymentService::GATEWAY_BANK_TRANSFER, $connectivityResults);
        $this->assertArrayHasKey(PaymentService::GATEWAY_MANUAL, $connectivityResults);
        
        // CPAY should have meaningful connectivity test
        $cpayResult = $connectivityResults[PaymentService::GATEWAY_CPAY];
        $this->assertArrayHasKey('success', $cpayResult);
        
        Log::info('Payment gateway connectivity tested', $connectivityResults);
    }
    
    /** @test */
    public function it_completes_full_invoice_to_paid_workflow()
    {
        // Given: An unpaid invoice
        $this->assertEquals(Invoice::STATUS_SENT, $this->invoice->status);
        $this->assertGreaterThan(0, $this->invoice->due_amount);
        $originalDueAmount = $this->invoice->due_amount;
        
        // Step 1: Create payment request
        $paymentResult = $this->paymentService->createInvoicePaymentRequest(
            $this->invoice, 
            PaymentService::GATEWAY_CPAY
        );
        $this->assertTrue($paymentResult['success']);
        $payment = $paymentResult['payment'];
        
        // Step 2: Simulate successful payment callback
        $callbackData = [
            'status' => 'APPROVED',
            'order_id' => $payment->gateway_order_id,
            'transaction_id' => 'TXN-MK-FULL-' . uniqid(),
            'amount' => $payment->amount,
            'currency' => 'MKD',
            'bank_code' => '250',
            'auth_code' => 'AUTH' . rand(100000, 999999),
            'card_mask' => '****-****-****-5678',
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ];
        $callbackData['signature'] = $this->generateCpaySignature($callbackData);
        
        $callbackResult = $this->paymentService->processPaymentCallback(
            $callbackData, 
            PaymentService::GATEWAY_CPAY
        );
        $this->assertTrue($callbackResult['success']);
        
        // Step 3: Verify complete workflow
        $payment->refresh();
        $this->invoice->refresh();
        
        // Payment should be completed
        $this->assertEquals(Payment::GATEWAY_STATUS_COMPLETED, $payment->gateway_status);
        $this->assertEquals($callbackData['transaction_id'], $payment->gateway_transaction_id);
        
        // Invoice should be fully paid
        $this->assertEquals(Invoice::STATUS_PAID, $this->invoice->paid_status);
        $this->assertEquals(0, $this->invoice->due_amount);
        
        // Payment amount should match original due amount
        $this->assertEquals($originalDueAmount, $payment->amount);
        
        Log::info('Complete invoice-to-paid workflow verified', [
            'invoice_id' => $this->invoice->id,
            'payment_id' => $payment->id,
            'original_due' => $originalDueAmount,
            'payment_amount' => $payment->amount,
            'final_due' => $this->invoice->due_amount,
            'invoice_status' => $this->invoice->paid_status
        ]);
    }
    
    // Helper Methods
    
    protected function createTestData(): void
    {
        // Create company
        $this->company = Company::factory()->create([
            'name' => 'Test Company Macedonia',
            'address' => 'Test Address, Skopje'
        ]);
        
        // Create MKD currency
        $this->currency = Currency::factory()->create([
            'name' => 'Macedonian Denar',
            'code' => 'MKD',
            'symbol' => 'ден'
        ]);
        
        // Create customer in Macedonia
        $this->customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Test Customer MK',
            'email' => 'customer@example.mk',
            'phone' => '+38970123456'
        ]);
        
        // Create test invoice
        $this->invoice = $this->createTestInvoice(2500.00);
    }
    
    protected function createTestInvoice(float $amount): Invoice
    {
        return Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'currency_id' => $this->currency->id,
            'invoice_number' => 'INV-MK-' . rand(1000, 9999),
            'total' => $amount,
            'due_amount' => $amount,
            'status' => Invoice::STATUS_SENT,
            'paid_status' => Invoice::STATUS_UNPAID,
            'exchange_rate' => 1
        ]);
    }
    
    protected function configureCpayTestEnvironment(): void
    {
        // Set CPAY test configuration via environment
        Config::set('cpay.merchant_id', 'TEST_MERCHANT_12345');
        Config::set('cpay.secret_key', 'test_secret_key_abc123');
        Config::set('cpay.payment_url', 'https://cpay.com.mk/payment/test');
        Config::set('cpay.test_mode', true);
    }
    
    protected function generateCpaySignature(array $data): string
    {
        // Simulate CPAY signature generation for testing
        $signatureData = $data;
        unset($signatureData['signature']);
        unset($signatureData['timestamp']);
        
        ksort($signatureData);
        $signatureString = implode('|', $signatureData) . '|test_secret_key_abc123';
        
        return hash('sha256', $signatureString);
    }
    
    protected function tearDown(): void
    {
        // Clean up test data
        Log::info('CPAY Gateway test completed');
        parent::tearDown();
    }
}

