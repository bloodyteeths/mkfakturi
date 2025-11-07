<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Company;
use App\Models\User;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Customer;
use Modules\Mk\Services\StopanskaGateway;
use Modules\Mk\Services\NlbGateway;
use Modules\Mk\Services\KomerGateway;
use Modules\Mk\Services\Matcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * OPS-06: Banking PSD2 Integration Testing
 * 
 * Tests complete banking integration with Macedonia banks:
 * - Stopanska Banka API connections
 * - NLB Banka API connections
 * - Komer Banka API connections
 * - PSD2 compliance validation
 * - Transaction matching algorithms
 * - Account balance synchronization
 * - Payment status reconciliation
 */
class BankingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $company;
    protected $stopanskaAccount;
    protected $nlbAccount;
    protected $komerAccount;
    protected $stopanskaGateway;
    protected $nlbGateway;
    protected $komerGateway;
    protected $matcher;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->setupTestEnvironment();
        $this->createTestData();
        $this->setupBankingServices();
    }

    protected function setupTestEnvironment(): void
    {
        // Configure test banking credentials
        Config::set('mk.banking.stopanska.client_id', 'test_client_id');
        Config::set('mk.banking.stopanska.client_secret', 'test_client_secret');
        Config::set('mk.banking.stopanska.api_url', 'https://sandbox-api.ob.stb.kibs.mk/xs2a/v1');
        Config::set('mk.banking.stopanska.sandbox', true);
        
        Config::set('mk.banking.nlb.client_id', 'test_nlb_client');
        Config::set('mk.banking.nlb.client_secret', 'test_nlb_secret');
        Config::set('mk.banking.nlb.api_url', 'https://developer-ob.nlb.mk/apis/xs2a/v1');
        Config::set('mk.banking.nlb.sandbox', true);
        
        Config::set('mk.banking.komer.client_id', 'test_komer_client');
        Config::set('mk.banking.komer.client_secret', 'test_komer_secret');
        Config::set('mk.banking.komer.api_url', 'https://test-api.komerbank.mk');
        Config::set('mk.banking.komer.sandbox', true);
    }

    protected function createTestData(): void
    {
        // Create company
        $this->company = Company::factory()->create([
            'name' => 'Тест Компанија за Банкарство ДОО',
            'vat_number' => 'MK4030009501234'
        ]);

        // Create user
        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'admin'
        ]);

        // Create bank accounts for each bank
        $this->stopanskaAccount = BankAccount::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Stopanska Banka Main Account',
            'bank_name' => 'Stopanska Banka AD',
            'account_number' => '1001234567890123',
            'iban' => 'MK07200000000012345678',
            'swift_code' => 'STBKMK22',
            'currency' => 'MKD',
            'bank_code' => 'STB',
            'is_primary' => true,
            'api_enabled' => true,
            'last_sync' => null
        ]);

        $this->nlbAccount = BankAccount::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'NLB Banka Secondary Account',
            'bank_name' => 'NLB Banka AD',
            'account_number' => '2001234567890123',
            'iban' => 'MK07210000000012345678',
            'swift_code' => 'NLBMMK2X',
            'currency' => 'MKD',
            'bank_code' => 'NLB',
            'is_primary' => false,
            'api_enabled' => true,
            'last_sync' => null
        ]);

        $this->komerAccount = BankAccount::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Komer Banka USD Account',
            'bank_name' => 'Komercijalna Banka AD',
            'account_number' => '3001234567890123',
            'iban' => 'MK07240000000012345678',
            'swift_code' => 'KOBMMK22',
            'currency' => 'USD',
            'bank_code' => 'KOM',
            'is_primary' => false,
            'api_enabled' => true,
            'last_sync' => null
        ]);

        // Create test customer with invoice for payment matching
        $customer = Customer::factory()->create([
            'name' => 'Тест Клиент ДООЕл',
            'tax_id' => 'MK4030009501235',
            'company_id' => $this->company->id
        ]);

        // Create test invoice for payment matching
        Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'ТСТ-2025-001',
            'total' => 15000, // 150.00 MKD
            'status' => 'SENT',
            'due_date' => Carbon::now()->addDays(15)
        ]);
    }

    protected function setupBankingServices(): void
    {
        $this->stopanskaGateway = new StopanskaGateway();
        $this->nlbGateway = new NlbGateway();
        $this->komerGateway = new KomerGateway();
        $this->matcher = new Matcher();
    }

    /** @test */
    public function it_can_authenticate_with_stopanska_banka_api()
    {
        // Mock successful authentication response
        Http::fake([
            'sandbox-api.ob.stb.kibs.mk/xs2a/v1/oauth2/token' => Http::response([
                'access_token' => 'test_access_token_123',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
                'scope' => 'account:read transaction:read'
            ], 200)
        ]);

        // Test authentication
        $authResult = $this->stopanskaGateway->authenticate();

        $this->assertTrue($authResult['success']);
        $this->assertEquals('test_access_token_123', $authResult['access_token']);
        $this->assertEquals(3600, $authResult['expires_in']);

        // Verify HTTP request was made correctly
        Http::assertSent(function ($request) {
            return $request->url() === 'https://sandbox-api.ob.stb.kibs.mk/xs2a/v1/oauth2/token' &&
                   $request->method() === 'POST' &&
                   $request->data()['grant_type'] === 'client_credentials';
        });
    }

    /** @test */
    public function it_can_fetch_account_balance_from_stopanska()
    {
        // Mock authentication
        $this->mockStopanskaAuth();

        // Mock account balance response
        Http::fake([
            'sandbox-api.ob.stb.kibs.mk/xs2a/v1/accounts/*/balance' => Http::response([
                'account_number' => '1001234567890123',
                'iban' => 'MK07200000000012345678',
                'currency' => 'MKD',
                'available_balance' => 125000.50,
                'current_balance' => 125000.50,
                'reserved_amount' => 0.00,
                'last_update' => Carbon::now()->toISOString()
            ], 200)
        ]);

        // Fetch balance
        $balance = $this->stopanskaGateway->getAccountBalance($this->stopanskaAccount);

        $this->assertTrue($balance['success']);
        $this->assertEquals(125000.50, $balance['available_balance']);
        $this->assertEquals('MKD', $balance['currency']);
        $this->assertEquals('1001234567890123', $balance['account_number']);
    }

    /** @test */
    public function it_can_fetch_transactions_from_nlb_banka()
    {
        // Mock NLB authentication
        $this->mockNlbAuth();

        // Mock transaction response
        Http::fake([
            'developer-ob.nlb.mk/apis/xs2a/v1/accounts/*/transactions*' => Http::response([
                'transactions' => [
                    [
                        'transaction_id' => 'NLB-TXN-001',
                        'booking_date' => Carbon::now()->subDays(2)->toDateString(),
                        'value_date' => Carbon::now()->subDays(2)->toDateString(),
                        'amount' => 15000.00,
                        'currency' => 'MKD',
                        'credit_debit_indicator' => 'CRDT',
                        'debtor_name' => 'Тест Клиент ДООЕл',
                        'debtor_iban' => 'MK07200000000087654321',
                        'creditor_name' => 'Тест Компанија за Банкарство ДОО',
                        'creditor_iban' => 'MK07210000000012345678',
                        'remittance_info' => 'Плаќање за фактура ТСТ-2025-001',
                        'bank_transaction_code' => 'PMNT-ICDT-INST'
                    ],
                    [
                        'transaction_id' => 'NLB-TXN-002',
                        'booking_date' => Carbon::now()->subDays(1)->toDateString(),
                        'value_date' => Carbon::now()->subDays(1)->toDateString(),
                        'amount' => 5000.00,
                        'currency' => 'MKD',
                        'credit_debit_indicator' => 'DBIT',
                        'debtor_name' => 'Тест Компанија за Банкарство ДОО',
                        'debtor_iban' => 'MK07210000000012345678',
                        'creditor_name' => 'Електричество на Македонија',
                        'creditor_iban' => 'MK07200000000099887766',
                        'remittance_info' => 'Сметка за електрична енергија',
                        'bank_transaction_code' => 'PMNT-ICDT-INST'
                    ]
                ],
                'pagination' => [
                    'page' => 1,
                    'per_page' => 10,
                    'total' => 2
                ]
            ], 200)
        ]);

        // Fetch transactions
        $dateFrom = Carbon::now()->subDays(7);
        $dateTo = Carbon::now();
        $transactions = $this->nlbGateway->getTransactions($this->nlbAccount, $dateFrom, $dateTo);

        $this->assertTrue($transactions['success']);
        $this->assertCount(2, $transactions['transactions']);
        
        // Verify first transaction (incoming payment)
        $transaction1 = $transactions['transactions'][0];
        $this->assertEquals('NLB-TXN-001', $transaction1['transaction_id']);
        $this->assertEquals(15000.00, $transaction1['amount']);
        $this->assertEquals('CRDT', $transaction1['credit_debit_indicator']);
        $this->assertStringContainsString('ТСТ-2025-001', $transaction1['remittance_info']);

        // Verify second transaction (outgoing payment)
        $transaction2 = $transactions['transactions'][1];
        $this->assertEquals('NLB-TXN-002', $transaction2['transaction_id']);
        $this->assertEquals(5000.00, $transaction2['amount']);
        $this->assertEquals('DBIT', $transaction2['credit_debit_indicator']);
    }

    /** @test */
    public function it_can_sync_komer_banka_usd_account()
    {
        // Mock Komer authentication
        $this->mockKomerAuth();

        // Mock USD account transactions
        Http::fake([
            'test-api.komerbank.mk/psd2/accounts/*/transactions*' => Http::response([
                'account' => [
                    'iban' => 'MK07240000000012345678',
                    'currency' => 'USD',
                    'current_balance' => 2500.75
                ],
                'transactions' => [
                    [
                        'id' => 'KOM-USD-001',
                        'booking_date' => Carbon::now()->subDays(1)->toDateString(),
                        'amount' => 850.00,
                        'currency' => 'USD',
                        'type' => 'CREDIT',
                        'counterparty_name' => 'International Client LLC',
                        'counterparty_iban' => 'US123456789012345678',
                        'reference' => 'INV-INT-2025-005',
                        'description' => 'Payment for international services'
                    ]
                ]
            ], 200)
        ]);

        // Sync account
        $syncResult = $this->komerGateway->syncAccount($this->komerAccount);

        $this->assertTrue($syncResult['success']);
        $this->assertEquals(2500.75, $syncResult['balance']);
        $this->assertEquals('USD', $syncResult['currency']);
        $this->assertCount(1, $syncResult['new_transactions']);
    }

    /** @test */
    public function it_can_match_incoming_payments_to_invoices()
    {
        // Create bank transaction
        $transaction = BankTransaction::factory()->create([
            'bank_account_id' => $this->stopanskaAccount->id,
            'transaction_id' => 'STB-PAY-123',
            'amount' => 15000.00,
            'currency' => 'MKD',
            'type' => 'CREDIT',
            'counterparty_name' => 'Тест Клиент ДООЕл',
            'counterparty_iban' => 'MK07200000000087654321',
            'reference' => 'Плаќање за фактура ТСТ-2025-001',
            'description' => 'Плаќање за услуги',
            'booking_date' => Carbon::now(),
            'value_date' => Carbon::now(),
            'status' => 'PENDING'
        ]);

        // Run matching algorithm
        $matchResult = $this->matcher->matchTransactionToInvoice($transaction);

        $this->assertTrue($matchResult['success']);
        $this->assertNotNull($matchResult['invoice']);
        $this->assertEquals('ТСТ-2025-001', $matchResult['invoice']->invoice_number);
        $this->assertEquals(100, $matchResult['confidence']); // Perfect match

        // Verify payment was created
        $this->assertDatabaseHas('payments', [
            'invoice_id' => $matchResult['invoice']->id,
            'amount' => 15000,
            'payment_method' => 'bank_transfer',
            'reference_number' => 'STB-PAY-123'
        ]);

        // Verify transaction status updated
        $transaction->refresh();
        $this->assertEquals('MATCHED', $transaction->status);
    }

    /** @test */
    public function it_handles_partial_payment_matching()
    {
        // Create invoice with higher amount
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'invoice_number' => 'ТСТ-2025-002',
            'total' => 30000, // 300.00 MKD
            'status' => 'SENT'
        ]);

        // Create partial payment transaction
        $transaction = BankTransaction::factory()->create([
            'bank_account_id' => $this->stopanskaAccount->id,
            'transaction_id' => 'STB-PARTIAL-456',
            'amount' => 15000.00, // Half payment
            'currency' => 'MKD',
            'type' => 'CREDIT',
            'reference' => 'Делумно плаќање ТСТ-2025-002',
            'booking_date' => Carbon::now(),
            'status' => 'PENDING'
        ]);

        // Match partial payment
        $matchResult = $this->matcher->matchTransactionToInvoice($transaction);

        $this->assertTrue($matchResult['success']);
        $this->assertEquals('ТСТ-2025-002', $matchResult['invoice']->invoice_number);
        $this->assertTrue($matchResult['partial_payment']);

        // Verify payment created with correct amount
        $this->assertDatabaseHas('payments', [
            'invoice_id' => $invoice->id,
            'amount' => 15000,
            'payment_method' => 'bank_transfer'
        ]);

        // Verify invoice status updated to partially paid
        $invoice->refresh();
        $this->assertEquals('PARTIALLY_PAID', $invoice->status);
        $this->assertEquals(15000, $invoice->paid_status); // Remaining amount
    }

    /** @test */
    public function it_handles_multi_bank_account_synchronization()
    {
        // Mock all bank authentications
        $this->mockStopanskaAuth();
        $this->mockNlbAuth(); 
        $this->mockKomerAuth();

        // Mock responses for all banks
        Http::fake([
            'sandbox-api.ob.stb.kibs.mk/xs2a/v1/accounts/*/transactions*' => Http::response([
                'transactions' => [
                    [
                        'transaction_id' => 'STB-001',
                        'amount' => 5000.00,
                        'currency' => 'MKD',
                        'type' => 'CREDIT',
                        'booking_date' => Carbon::now()->toDateString()
                    ]
                ]
            ], 200),
            'developer-ob.nlb.mk/apis/xs2a/v1/accounts/*/transactions*' => Http::response([
                'transactions' => [
                    [
                        'transaction_id' => 'NLB-001',
                        'amount' => 8000.00,
                        'currency' => 'MKD',
                        'credit_debit_indicator' => 'CRDT',
                        'booking_date' => Carbon::now()->toDateString()
                    ]
                ]
            ], 200),
            'test-api.komerbank.mk/psd2/accounts/*/transactions*' => Http::response([
                'transactions' => [
                    [
                        'id' => 'KOM-001',
                        'amount' => 500.00,
                        'currency' => 'USD',
                        'type' => 'CREDIT',
                        'booking_date' => Carbon::now()->toDateString()
                    ]
                ]
            ], 200)
        ]);

        // Sync all accounts
        $syncResults = [];
        $syncResults['stopanska'] = $this->stopanskaGateway->syncAccount($this->stopanskaAccount);
        $syncResults['nlb'] = $this->nlbGateway->syncAccount($this->nlbAccount);
        $syncResults['komer'] = $this->komerGateway->syncAccount($this->komerAccount);

        // Verify all syncs succeeded
        foreach ($syncResults as $bank => $result) {
            $this->assertTrue($result['success'], "Sync failed for {$bank}");
            $this->assertGreaterThan(0, count($result['new_transactions'] ?? []));
        }

        // Verify transactions were stored
        $this->assertDatabaseHas('bank_transactions', ['transaction_id' => 'STB-001']);
        $this->assertDatabaseHas('bank_transactions', ['transaction_id' => 'NLB-001']);
        $this->assertDatabaseHas('bank_transactions', ['transaction_id' => 'KOM-001']);
    }

    /** @test */
    public function it_handles_currency_conversion_for_matching()
    {
        // Create invoice in MKD
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'invoice_number' => 'USD-2025-001',
            'total' => 30000, // 300.00 MKD
            'status' => 'SENT'
        ]);

        // Create USD transaction (equivalent amount)
        $transaction = BankTransaction::factory()->create([
            'bank_account_id' => $this->komerAccount->id,
            'transaction_id' => 'KOM-USD-CONV-001',
            'amount' => 500.00, // ~30000 MKD at 60:1 rate
            'currency' => 'USD',
            'type' => 'CREDIT',
            'reference' => 'Payment for USD-2025-001',
            'booking_date' => Carbon::now(),
            'status' => 'PENDING'
        ]);

        // Mock exchange rate service
        Http::fake([
            'api.exchangerate-api.com/v4/latest/USD' => Http::response([
                'rates' => [
                    'MKD' => 60.0
                ]
            ], 200)
        ]);

        // Match with currency conversion
        $matchResult = $this->matcher->matchTransactionToInvoice($transaction, ['convert_currency' => true]);

        $this->assertTrue($matchResult['success']);
        $this->assertEquals('USD-2025-001', $matchResult['invoice']->invoice_number);
        $this->assertTrue($matchResult['currency_converted']);
        $this->assertEquals(30000, $matchResult['converted_amount_mkd']);

        // Verify payment recorded with conversion info
        $this->assertDatabaseHas('payments', [
            'invoice_id' => $invoice->id,
            'amount' => 30000, // Converted to MKD
            'payment_method' => 'bank_transfer',
            'currency' => 'USD',
            'exchange_rate' => 60.0,
            'original_amount' => 500.00
        ]);
    }

    /** @test */
    public function it_measures_banking_sync_performance()
    {
        $this->mockStopanskaAuth();
        
        // Mock large transaction response
        $transactions = [];
        for ($i = 1; $i <= 100; $i++) {
            $transactions[] = [
                'transaction_id' => "STB-PERF-{$i}",
                'amount' => rand(1000, 50000),
                'currency' => 'MKD',
                'type' => 'CREDIT',
                'booking_date' => Carbon::now()->subDays(rand(1, 30))->toDateString()
            ];
        }

        Http::fake([
            'sandbox-api.ob.stb.kibs.mk/xs2a/v1/accounts/*/transactions*' => Http::response([
                'transactions' => $transactions
            ], 200)
        ]);

        // Measure sync performance
        $startTime = microtime(true);
        
        $syncResult = $this->stopanskaGateway->syncAccount($this->stopanskaAccount);
        
        $endTime = microtime(true);
        $syncTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        // Verify performance requirements
        $this->assertLessThan(5000, $syncTime, 'Bank sync should complete within 5 seconds');
        $this->assertTrue($syncResult['success']);
        $this->assertEquals(100, count($syncResult['new_transactions']));

        // Log performance metrics
        Log::info('Banking Sync Performance Test', [
            'bank' => 'stopanska',
            'transaction_count' => 100,
            'sync_time_ms' => round($syncTime, 2),
            'transactions_per_second' => round(100 / ($syncTime / 1000), 2)
        ]);
    }

    /** @test */
    public function it_handles_psd2_compliance_validation()
    {
        // Test consent management
        $consentResult = $this->stopanskaGateway->requestConsent([
            'account_access' => ['transactions', 'balances'],
            'valid_until' => Carbon::now()->addDays(90)->toISOString(),
            'frequency_per_day' => 4
        ]);

        $this->assertTrue($consentResult['success']);
        $this->assertNotEmpty($consentResult['consent_id']);

        // Test API rate limiting compliance
        for ($i = 1; $i <= 5; $i++) {
            $response = $this->stopanskaGateway->getAccountBalance($this->stopanskaAccount);
            if ($i <= 4) {
                $this->assertTrue($response['success'], "Request {$i} should succeed");
            } else {
                // 5th request should be rate limited (PSD2 allows 4 per day)
                $this->assertFalse($response['success']);
                $this->assertEquals('RATE_LIMITED', $response['error_code']);
            }
        }

        // Test strong customer authentication (SCA)
        $scaResult = $this->stopanskaGateway->initiateStrongAuthentication([
            'user_id' => $this->user->id,
            'challenge_type' => 'SMS'
        ]);

        $this->assertTrue($scaResult['success']);
        $this->assertNotEmpty($scaResult['challenge_id']);
    }

    /** @test */
    public function it_handles_banking_api_errors_gracefully()
    {
        // Test network timeout
        Http::fake([
            'sandbox-api.ob.stb.kibs.mk/**' => Http::response('', 408) // Request timeout
        ]);

        $result = $this->stopanskaGateway->getAccountBalance($this->stopanskaAccount);
        
        $this->assertFalse($result['success']);
        $this->assertEquals('TIMEOUT', $result['error_code']);
        $this->assertStringContainsString('timeout', $result['error_message']);

        // Test invalid credentials
        Http::fake([
            'sandbox-api.ob.stb.kibs.mk/xs2a/v1/oauth2/token' => Http::response([
                'error' => 'invalid_client',
                'error_description' => 'Client authentication failed'
            ], 401)
        ]);

        $authResult = $this->stopanskaGateway->authenticate();
        
        $this->assertFalse($authResult['success']);
        $this->assertEquals('INVALID_CREDENTIALS', $authResult['error_code']);

        // Test service unavailable
        Http::fake([
            'developer-ob.nlb.mk/apis/xs2a/v1/**' => Http::response([
                'error' => 'service_unavailable',
                'message' => 'Banking service temporarily unavailable'
            ], 503)
        ]);

        $transactionResult = $this->nlbGateway->getTransactions($this->nlbAccount, Carbon::now()->subDays(7), Carbon::now());
        
        $this->assertFalse($transactionResult['success']);
        $this->assertEquals('SERVICE_UNAVAILABLE', $transactionResult['error_code']);
    }

    // Helper methods for mocking bank authentications
    protected function mockStopanskaAuth(): void
    {
        Http::fake([
            'sandbox-api.ob.stb.kibs.mk/xs2a/v1/oauth2/token' => Http::response([
                'access_token' => 'stopanska_test_token',
                'expires_in' => 3600
            ], 200)
        ]);
    }

    protected function mockNlbAuth(): void
    {
        Http::fake([
            'auth.sandbox.mk.open-bank.io/v1/authentication/tenants/nlb/connect/token' => Http::response([
                'access_token' => 'nlb_test_token',
                'expires_in' => 3600
            ], 200)
        ]);
    }

    protected function mockKomerAuth(): void
    {
        Http::fake([
            'test-api.komerbank.mk/oauth/token' => Http::response([
                'access_token' => 'komer_test_token',
                'expires_in' => 3600
            ], 200)
        ]);
    }

    protected function tearDown(): void
    {
        // Clean up test data
        Http::preventStrayRequests();
        parent::tearDown();
    }
}
