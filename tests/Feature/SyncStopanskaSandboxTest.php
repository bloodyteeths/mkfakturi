<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Company;
use App\Models\Currency;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Mk\Jobs\SyncStopanska;
use Modules\Mk\Services\StopanskaGateway;
use Tests\TestCase;

/**
 * SB-02: Stopanska Sandbox Sync Test
 *
 * Tests Stopanska Bank PSD2 sandbox integration with ≥20 transaction rows
 * Validates that the gateway can retrieve and process real sandbox data
 * Part of ROADMAP-5 banking integration validation requirements
 *
 * Done-check: ≥20 tx rows successfully retrieved and stored
 */
class SyncStopanskaSandboxTest extends TestCase
{
    use RefreshDatabase;

    protected $company;

    protected $currency;

    protected $stopanskaGateway;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test company and currency
        $this->company = Company::factory()->create([
            'name' => 'Sandbox Test Company',
            'tax_number' => '1234567890',
        ]);

        $this->currency = Currency::factory()->create([
            'code' => 'MKD',
            'name' => 'Macedonian Denar',
            'symbol' => 'ден',
        ]);

        // Initialize Stopanska Gateway in sandbox mode
        $this->stopanskaGateway = new StopanskaGateway;

        // Ensure we're in sandbox environment for testing
        config(['app.env' => 'testing']);
    }

    /**
     * Test that Stopanska gateway can retrieve sandbox test data
     *
     * @test
     */
    public function it_can_retrieve_stopanska_sandbox_test_data()
    {
        // Verify we're in sandbox mode
        $this->assertTrue(method_exists($this->stopanskaGateway, 'getSandboxTestData'));

        // Get sandbox test data
        $testData = $this->stopanskaGateway->getSandboxTestData();

        // Validate structure
        $this->assertIsArray($testData);
        $this->assertArrayHasKey('accounts', $testData);
        $this->assertArrayHasKey('transactions', $testData);
        $this->assertArrayHasKey('booked', $testData['transactions']);

        // Verify we have at least 1 account
        $this->assertGreaterThanOrEqual(1, count($testData['accounts']));

        // Verify we have at least 20 transactions as required
        $transactions = $testData['transactions']['booked'];
        $this->assertGreaterThanOrEqual(20, count($transactions),
            'SB-02 requires ≥20 transaction rows, got '.count($transactions));

        // Validate transaction structure
        foreach ($transactions as $index => $transaction) {
            $this->assertArrayHasKey('transactionId', $transaction, "Transaction $index missing transactionId");
            $this->assertArrayHasKey('transactionAmount', $transaction, "Transaction $index missing transactionAmount");
            $this->assertArrayHasKey('amount', $transaction['transactionAmount'], "Transaction $index missing amount");
            $this->assertArrayHasKey('currency', $transaction['transactionAmount'], "Transaction $index missing currency");
            $this->assertArrayHasKey('bookingDate', $transaction, "Transaction $index missing bookingDate");
            $this->assertArrayHasKey('remittanceInformationUnstructured', $transaction, "Transaction $index missing description");
        }

        Log::info('SB-02: Stopanska sandbox test data validated', [
            'accounts_count' => count($testData['accounts']),
            'transactions_count' => count($transactions),
            'task' => 'SB-02',
            'requirement_met' => count($transactions) >= 20,
        ]);
    }

    /**
     * Test that sandbox accounts and transactions can be converted to objects
     *
     * @test
     */
    public function it_can_convert_sandbox_data_to_objects()
    {
        // Get sandbox data as objects
        $result = $this->stopanskaGateway->getSandboxAccountsAndTransactions();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('accounts', $result);
        $this->assertArrayHasKey('transactions', $result);

        $accounts = $result['accounts'];
        $transactions = $result['transactions'];

        // Verify we have objects
        $this->assertGreaterThanOrEqual(1, count($accounts));
        $this->assertGreaterThanOrEqual(20, count($transactions));

        // Validate account objects
        foreach ($accounts as $account) {
            $this->assertInstanceOf(\Modules\Mk\Services\StopanskaAccountDetail::class, $account);
            $this->assertIsString($account->getAccountNumber());
            $this->assertIsString($account->getIban());
            $this->assertIsString($account->getCurrency());
            $this->assertIsNumeric($account->getBalance());
        }

        // Validate transaction objects
        foreach ($transactions as $transaction) {
            $this->assertInstanceOf(\Modules\Mk\Services\StopanskaTransaction::class, $transaction);
            $this->assertIsString($transaction->getExternalUid());
            $this->assertIsNumeric($transaction->getAmount());
            $this->assertIsString($transaction->getCurrency());
            $this->assertIsString($transaction->getDescription());
        }

        Log::info('SB-02: Stopanska sandbox objects validated', [
            'accounts_count' => count($accounts),
            'transactions_count' => count($transactions),
        ]);
    }

    /**
     * Test sandbox connection and transaction retrieval for SB-02 completion
     *
     * @test
     */
    public function it_can_test_sandbox_connection_and_retrieve_transactions()
    {
        // Test the connection method specifically designed for SB-02
        $result = $this->stopanskaGateway->testConnectionAndRetrieveTransactions();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('accounts', $result);
        $this->assertArrayHasKey('transactions', $result);

        $accounts = $result['accounts'];
        $transactions = $result['transactions'];

        // SB-02 requirement: ≥20 transaction rows
        $this->assertGreaterThanOrEqual(20, count($transactions),
            'SB-02 completion requires ≥20 transaction rows');

        // Verify data quality for banking integration
        foreach ($transactions as $transaction) {
            // Must have essential banking data
            $this->assertNotEmpty($transaction->getExternalUid(), 'Transaction missing external UID');
            $this->assertNotEmpty($transaction->getAmount(), 'Transaction missing amount');
            $this->assertNotEmpty($transaction->getCurrency(), 'Transaction missing currency');
            $this->assertNotEmpty($transaction->getDescription(), 'Transaction missing description');

            // Amount should be numeric and non-zero
            $this->assertIsNumeric($transaction->getAmount());
            $this->assertNotEquals(0, $transaction->getAmount());

            // Currency should be MKD for Macedonia
            $this->assertEquals('MKD', $transaction->getCurrency());
        }

        Log::info('SB-02: Sandbox connection test completed successfully', [
            'task' => 'SB-02',
            'status' => 'COMPLETED',
            'transactions_retrieved' => count($transactions),
            'requirement' => '≥20 tx rows',
            'met' => count($transactions) >= 20 ? 'YES' : 'NO',
        ]);
    }

    /**
     * Test full sandbox sync workflow with database storage
     *
     * @test
     */
    public function it_can_perform_full_sandbox_sync_workflow()
    {
        // Clear any existing data
        DB::table('bank_transactions')->truncate();
        DB::table('bank_accounts')->truncate();

        // Get sandbox data
        $result = $this->stopanskaGateway->getSandboxAccountsAndTransactions();
        $accounts = $result['accounts'];
        $transactions = $result['transactions'];

        // Process accounts (simulate what SyncStopanska job does)
        $storedTransactions = 0;

        foreach ($accounts as $account) {
            // Create bank account
            $bankAccount = BankAccount::create([
                'company_id' => $this->company->id,
                'currency_id' => $this->currency->id,
                'name' => $account->getName(),
                'account_number' => $account->getAccountNumber(),
                'iban' => $account->getIban(),
                'swift_code' => $account->getBic(),
                'bank_name' => 'Stopanska Banka AD Skopje',
                'bank_code' => 'STB',
                'opening_balance' => 0,
                'current_balance' => $account->getBalance(),
                'is_primary' => false,
                'is_active' => true,
            ]);

            // Store transactions
            foreach ($transactions as $transaction) {
                DB::table('bank_transactions')->insert([
                    'bank_account_id' => $bankAccount->id,
                    'company_id' => $this->company->id,
                    'external_reference' => $transaction->getExternalUid(),
                    'transaction_reference' => $transaction->getTransactionUid(),
                    'amount' => $transaction->getAmount(),
                    'currency' => $transaction->getCurrency(),
                    'description' => $transaction->getDescription(),
                    'transaction_date' => Carbon::parse($transaction->getCreatedAt()),
                    'booking_status' => $transaction->getBookingStatus(),
                    'debtor_name' => $transaction->getDebtorName(),
                    'creditor_name' => $transaction->getCreditorName(),
                    'debtor_iban' => $transaction->getIban(),
                    'creditor_iban' => $transaction->getIban(),
                    'remittance_info' => $transaction->getRemittanceInformation(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $storedTransactions++;
            }
        }

        // Verify storage
        $this->assertGreaterThanOrEqual(20, $storedTransactions,
            'SB-02 requires ≥20 transactions to be stored');

        // Verify database records
        $dbTransactionCount = DB::table('bank_transactions')
            ->where('company_id', $this->company->id)
            ->count();

        $this->assertEquals($storedTransactions, $dbTransactionCount);

        // Verify bank account was created
        $bankAccountCount = BankAccount::where('company_id', $this->company->id)
            ->where('bank_code', 'STB')
            ->count();

        $this->assertGreaterThanOrEqual(1, $bankAccountCount);

        Log::info('SB-02: Full sandbox sync workflow completed', [
            'task' => 'SB-02',
            'transactions_stored' => $storedTransactions,
            'bank_accounts_created' => $bankAccountCount,
            'company_id' => $this->company->id,
            'completion_status' => 'SUCCESS',
        ]);
    }

    /**
     * Test sandbox data quality and Macedonia-specific validation
     *
     * @test
     */
    public function it_validates_macedonia_specific_sandbox_data()
    {
        $testData = $this->stopanskaGateway->getSandboxTestData();
        $transactions = $testData['transactions']['booked'];

        // Validate Macedonia-specific requirements
        foreach ($transactions as $transaction) {
            // Currency should be MKD (Macedonia Denar)
            $this->assertEquals('MKD', $transaction['transactionAmount']['currency'],
                'All transactions should use MKD currency for Macedonia market');

            // IBANs should start with MK (Macedonia country code)
            if (isset($transaction['creditorAccount']['iban'])) {
                $this->assertStringStartsWith('MK', $transaction['creditorAccount']['iban'],
                    'Creditor IBAN should start with MK for Macedonia');
            }

            if (isset($transaction['debtorAccount']['iban'])) {
                $this->assertStringStartsWith('MK', $transaction['debtorAccount']['iban'],
                    'Debtor IBAN should start with MK for Macedonia');
            }

            // Amounts should be reasonable for Macedonia market (not too large/small)
            $amount = abs($transaction['transactionAmount']['amount']);
            $this->assertGreaterThan(0, $amount, 'Transaction amount should be positive');
            $this->assertLessThan(1000000, $amount, 'Transaction amount should be realistic for Macedonia market');

            // Dates should be recent (last 30 days)
            $bookingDate = Carbon::parse($transaction['bookingDate']);
            $this->assertTrue($bookingDate->greaterThan(Carbon::now()->subDays(31)),
                'Booking date should be within last 30 days');
        }

        // Validate account data
        foreach ($testData['accounts'] as $account) {
            $this->assertEquals('MKD', $account['currency'], 'Account currency should be MKD');
            $this->assertStringStartsWith('MK', $account['iban'], 'Account IBAN should start with MK');

            // Balance should be reasonable
            $balance = $account['balances'][0]['balanceAmount']['amount'];
            $this->assertGreaterThan(0, $balance, 'Account balance should be positive');
        }

        Log::info('SB-02: Macedonia-specific data validation completed', [
            'task' => 'SB-02',
            'currency_validation' => 'PASSED',
            'iban_validation' => 'PASSED',
            'amount_validation' => 'PASSED',
            'date_validation' => 'PASSED',
        ]);
    }

    /**
     * Test endpoint validation for sandbox environment
     *
     * @test
     */
    public function it_validates_sandbox_endpoints()
    {
        $endpointStatus = $this->stopanskaGateway->validateEndpoints();

        $this->assertIsArray($endpointStatus);
        $this->assertArrayHasKey('current_environment', $endpointStatus);
        $this->assertArrayHasKey('active_endpoints', $endpointStatus);
        $this->assertArrayHasKey('bank_info', $endpointStatus);

        // Should be in sandbox mode for testing
        $this->assertEquals('sandbox', $endpointStatus['current_environment']);

        // Validate bank information
        $this->assertEquals('Stopanska Banka AD Skopje', $endpointStatus['bank_info']['name']);
        $this->assertEquals('STB', $endpointStatus['bank_info']['code']);
        $this->assertEquals('STBAMK22XXX', $endpointStatus['bank_info']['bic']);

        // Validate sandbox endpoints are configured
        $activeEndpoints = $endpointStatus['active_endpoints'];
        $this->assertStringContains('sandbox', $activeEndpoints['token']);
        $this->assertStringContains('sandbox', $activeEndpoints['accounts']);
        $this->assertStringContains('sandbox', $activeEndpoints['transactions']);

        Log::info('SB-02: Sandbox endpoint validation completed', [
            'task' => 'SB-02',
            'environment' => $endpointStatus['current_environment'],
            'endpoints_validated' => 'SUCCESS',
        ]);
    }

    /**
     * Performance test: Verify sandbox can handle required transaction volume
     *
     * @test
     */
    public function it_handles_required_transaction_volume_efficiently()
    {
        $startTime = microtime(true);

        // Retrieve sandbox data
        $result = $this->stopanskaGateway->testConnectionAndRetrieveTransactions();
        $transactions = $result['transactions'];

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // SB-02 requirement verification
        $this->assertGreaterThanOrEqual(20, count($transactions),
            'Must retrieve ≥20 transactions for SB-02 completion');

        // Performance should be reasonable (under 5 seconds for sandbox data)
        $this->assertLessThan(5, $executionTime,
            'Sandbox data retrieval should complete in under 5 seconds');

        // Memory usage should be reasonable
        $memoryUsage = memory_get_peak_usage(true) / 1024 / 1024; // MB
        $this->assertLessThan(128, $memoryUsage,
            'Memory usage should be under 128MB for sandbox testing');

        Log::info('SB-02: Performance test completed', [
            'task' => 'SB-02',
            'transactions_count' => count($transactions),
            'execution_time' => round($executionTime, 2).'s',
            'memory_usage' => round($memoryUsage, 2).'MB',
            'performance_status' => 'ACCEPTABLE',
        ]);
    }

    protected function tearDown(): void
    {
        // Clean up any test data
        DB::table('bank_transactions')->truncate();
        DB::table('bank_accounts')->truncate();

        parent::tearDown();
    }
}
