<?php

namespace Tests\Unit;

use Tests\TestCase;
use Modules\Mk\Services\PantheonSyncService;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Currency;
use App\Models\InvoiceItem;
use App\Models\PaymentMethod;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

/**
 * Test PantheonSyncService for PANTHEON web-services integration
 * 
 * Tests API integration methods with mocked HTTP responses
 * Success criteria: API HTTP 200 response handling
 * Covers error scenarios and retry logic
 */
class PantheonSyncTest extends TestCase
{
    use RefreshDatabase;

    protected PantheonSyncService $service;
    protected MockHandler $mockHandler;
    protected Invoice $testInvoice;
    protected Payment $testPayment;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create mock handler for HTTP client
        $this->mockHandler = new MockHandler();
        $handlerStack = HandlerStack::create($this->mockHandler);
        
        // Create service instance and inject mock client
        $this->service = new PantheonSyncService();
        
        // Use reflection to inject mock client
        $reflection = new \ReflectionClass($this->service);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($this->service, new Client(['handler' => $handlerStack]));
        
        // Create test data
        $this->createTestData();
    }

    /** @test */
    public function it_can_push_invoice_successfully()
    {
        // Mock successful API response
        $this->mockHandler->append(new Response(200, [], json_encode([
            'success' => true,
            'message' => 'Invoice pushed successfully',
            'data' => [
                'pantheon_id' => 'PAN-INV-12345',
                'status' => 'created'
            ]
        ])));

        $result = $this->service->pushInvoice($this->testInvoice);

        $this->assertTrue($result['success']);
        $this->assertEquals('Invoice pushed successfully', $result['message']);
        $this->assertEquals('PAN-INV-12345', $result['data']['pantheon_id']);
    }

    /** @test */
    public function it_can_push_payment_successfully()
    {
        // Mock successful API response
        $this->mockHandler->append(new Response(200, [], json_encode([
            'success' => true,
            'message' => 'Payment pushed successfully',
            'data' => [
                'pantheon_id' => 'PAN-PAY-67890',
                'status' => 'processed'
            ]
        ])));

        $result = $this->service->pushPayment($this->testPayment);

        $this->assertTrue($result['success']);
        $this->assertEquals('Payment pushed successfully', $result['message']);
        $this->assertEquals('PAN-PAY-67890', $result['data']['pantheon_id']);
    }

    /** @test */
    public function it_can_get_status_successfully()
    {
        // Mock successful status response
        $this->mockHandler->append(new Response(200, [], json_encode([
            'status' => 'online',
            'api_version' => '2.0',
            'response_time_ms' => 150
        ])));

        $result = $this->service->getStatus();

        $this->assertEquals('online', $result['status']);
        $this->assertEquals('sandbox', $result['environment']);
        $this->assertEquals('2.0', $result['api_version']);
        $this->assertArrayHasKey('last_check', $result);
    }

    /** @test */
    public function it_handles_push_invoice_api_error()
    {
        // Mock API error response
        $this->mockHandler->append(new Response(400, [], json_encode([
            'success' => false,
            'message' => 'Invalid invoice data',
            'errors' => ['invoice_number' => 'Already exists']
        ])));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('PANTHEON API returned error: Invalid invoice data');

        $this->service->pushInvoice($this->testInvoice);
    }

    /** @test */
    public function it_handles_push_payment_api_error()
    {
        // Mock API error response
        $this->mockHandler->append(new Response(422, [], json_encode([
            'success' => false,
            'message' => 'Payment validation failed',
            'errors' => ['amount' => 'Must be greater than zero']
        ])));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('PANTHEON API returned error: Payment validation failed');

        $this->service->pushPayment($this->testPayment);
    }

    /** @test */
    public function it_handles_network_errors()
    {
        // Mock network error
        $this->mockHandler->append(new RequestException(
            'Connection timeout',
            new Request('POST', 'invoices/push')
        ));

        $this->expectException(RequestException::class);
        $this->expectExceptionMessage('Connection timeout');

        $this->service->pushInvoice($this->testInvoice);
    }

    /** @test */
    public function it_handles_authentication_errors()
    {
        // Mock 401 Unauthorized response
        $this->mockHandler->append(new Response(401, [], json_encode([
            'success' => false,
            'message' => 'Invalid API key'
        ])));

        $this->expectException(\Exception::class);
        
        Log::shouldReceive('error')->once();
        Log::shouldReceive('warning')->once()->with('PANTHEON API authentication failed - check API key and company code');

        $this->service->pushInvoice($this->testInvoice);
    }

    /** @test */
    public function it_handles_rate_limit_errors()
    {
        // Mock 429 Rate Limited response
        $this->mockHandler->append(new Response(429, [], json_encode([
            'success' => false,
            'message' => 'Rate limit exceeded'
        ])));

        $this->expectException(\Exception::class);
        
        Log::shouldReceive('error')->once();
        Log::shouldReceive('warning')->once()->with('PANTHEON API rate limit exceeded - implement retry logic');

        $this->service->pushPayment($this->testPayment);
    }

    /** @test */
    public function it_handles_server_errors()
    {
        // Mock 500 Server Error response
        $this->mockHandler->append(new Response(500, [], json_encode([
            'success' => false,
            'message' => 'Internal server error'
        ])));

        $this->expectException(\Exception::class);
        
        Log::shouldReceive('error')->once();
        Log::shouldReceive('warning')->once()->with('PANTHEON API server error - consider retry with backoff');

        $this->service->getStatus();
    }

    /** @test */
    public function it_returns_offline_status_on_connection_failure()
    {
        // Mock connection failure
        $this->mockHandler->append(new RequestException(
            'Connection refused',
            new Request('GET', 'status')
        ));

        Log::shouldReceive('error')->once();

        $result = $this->service->getStatus();

        $this->assertEquals('offline', $result['status']);
        $this->assertEquals('sandbox', $result['environment']);
        $this->assertStringContains('Connection refused', $result['error']);
    }

    /** @test */
    public function it_can_test_connection_successfully()
    {
        // Mock successful status response for connection test
        $this->mockHandler->append(new Response(200, [], json_encode([
            'status' => 'online',
            'api_version' => '2.0'
        ])));

        $result = $this->service->testConnection();

        $this->assertTrue($result['success']);
        $this->assertEquals('PANTHEON API connection successful', $result['message']);
        $this->assertArrayHasKey('status', $result);
    }

    /** @test */
    public function it_can_test_connection_failure()
    {
        // Mock connection failure
        $this->mockHandler->append(new RequestException(
            'Connection failed',
            new Request('GET', 'status')
        ));

        Log::shouldReceive('error')->once();

        $result = $this->service->testConnection();

        $this->assertFalse($result['success']);
        $this->assertStringContains('PANTHEON API connection failed', $result['message']);
        $this->assertEquals('Connection failed', $result['error']);
    }

    /** @test */
    public function it_returns_correct_configuration()
    {
        $config = $this->service->getConfiguration();

        $this->assertArrayHasKey('base_url', $config);
        $this->assertEquals('sandbox', $config['environment']);
        $this->assertEquals('DEMO001', $config['company_code']);
        $this->assertTrue($config['api_key_set']);
        $this->assertArrayHasKey('endpoints', $config);
        $this->assertArrayHasKey('push_invoice', $config['endpoints']);
        $this->assertArrayHasKey('push_payment', $config['endpoints']);
        $this->assertArrayHasKey('status', $config['endpoints']);
    }

    /** @test */
    public function it_formats_invoice_data_correctly()
    {
        // Mock successful response to capture the formatted data
        $this->mockHandler->append(new Response(200, [], json_encode([
            'success' => true,
            'data' => ['pantheon_id' => 'test']
        ])));

        // Capture the last request to verify the data format
        $container = [];
        $history = \GuzzleHttp\Middleware::history($container);
        
        // Add history middleware to handler stack
        $handlerStack = HandlerStack::create($this->mockHandler);
        $handlerStack->push($history);
        
        // Recreate client with history middleware
        $reflection = new \ReflectionClass($this->service);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($this->service, new Client(['handler' => $handlerStack]));

        $this->service->pushInvoice($this->testInvoice);

        $this->assertCount(1, $container);
        $request = $container[0]['request'];
        $requestData = json_decode($request->getBody()->getContents(), true);

        // Verify invoice data structure
        $this->assertArrayHasKey('company_code', $requestData);
        $this->assertArrayHasKey('invoice', $requestData);
        $this->assertEquals('DEMO001', $requestData['company_code']);
        $this->assertEquals($this->testInvoice->id, $requestData['invoice']['external_id']);
        $this->assertEquals($this->testInvoice->invoice_number, $requestData['invoice']['invoice_number']);
        $this->assertArrayHasKey('customer', $requestData['invoice']);
        $this->assertArrayHasKey('items', $requestData['invoice']);
        $this->assertArrayHasKey('totals', $requestData['invoice']);
    }

    /** @test */
    public function it_formats_payment_data_correctly()
    {
        // Mock successful response
        $this->mockHandler->append(new Response(200, [], json_encode([
            'success' => true,
            'data' => ['pantheon_id' => 'test']
        ])));

        // Capture the request data
        $container = [];
        $history = \GuzzleHttp\Middleware::history($container);
        $handlerStack = HandlerStack::create($this->mockHandler);
        $handlerStack->push($history);
        
        $reflection = new \ReflectionClass($this->service);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($this->service, new Client(['handler' => $handlerStack]));

        $this->service->pushPayment($this->testPayment);

        $this->assertCount(1, $container);
        $request = $container[0]['request'];
        $requestData = json_decode($request->getBody()->getContents(), true);

        // Verify payment data structure
        $this->assertEquals('DEMO001', $requestData['company_code']);
        $this->assertEquals($this->testPayment->id, $requestData['payment']['external_id']);
        $this->assertEquals($this->testPayment->payment_number, $requestData['payment']['payment_number']);
        $this->assertArrayHasKey('invoice', $requestData['payment']);
        $this->assertArrayHasKey('customer', $requestData['payment']);
        $this->assertArrayHasKey('payment_method', $requestData['payment']);
    }

    /**
     * Create test data for invoice and payment
     */
    protected function createTestData()
    {
        // Create currency
        $currency = Currency::factory()->create([
            'name' => 'Macedonian Denar',
            'code' => 'MKD',
            'symbol' => 'ден'
        ]);

        // Create company
        $company = Company::factory()->create([
            'name' => 'Тест Компанија ПАНТЕОН',
            'vat_number' => 'MK4030009501234'
        ]);

        // Create customer
        $customer = Customer::factory()->create([
            'name' => 'ПАНТЕОН Тест Клиент',
            'email' => 'test@pantheon.mk',
            'phone' => '+389 2 123 456',
            'company_id' => $company->id
        ]);

        // Create payment method
        $paymentMethod = PaymentMethod::factory()->create([
            'name' => 'Банкарски трансфер',
            'company_id' => $company->id
        ]);

        // Create test invoice
        $this->testInvoice = Invoice::factory()->create([
            'invoice_number' => 'PANT-2025-001',
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'currency_id' => $currency->id,
            'sub_total' => 10000, // 100.00 MKD
            'tax_total' => 1800,   // 18.00 MKD
            'total' => 11800,      // 118.00 MKD
            'status' => 'SENT',
            'notes' => 'ПАНТЕОН тест фактура'
        ]);

        // Create invoice item
        InvoiceItem::factory()->create([
            'invoice_id' => $this->testInvoice->id,
            'name' => 'ПАНТЕОН Интеграција',
            'description' => 'Веб сервиси синхронизација',
            'quantity' => 1,
            'price' => 10000,
            'total' => 10000
        ]);

        // Create test payment
        $this->testPayment = Payment::factory()->create([
            'payment_number' => 'PAY-PANT-001',
            'payment_date' => now(),
            'amount' => 11800, // 118.00 MKD
            'invoice_id' => $this->testInvoice->id,
            'customer_id' => $customer->id,
            'company_id' => $company->id,
            'currency_id' => $currency->id,
            'payment_method_id' => $paymentMethod->id,
            'payment_mode' => 'BANK_TRANSFER',
            'notes' => 'ПАНТЕОН тест плаќање'
        ]);

        // Load relationships
        $this->testInvoice->load(['company', 'customer', 'items', 'currency']);
        $this->testPayment->load(['invoice', 'customer', 'paymentMethod', 'currency']);
    }
}

