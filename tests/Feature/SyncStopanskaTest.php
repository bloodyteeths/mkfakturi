<?php

use App\Models\BankAccount;
use App\Models\Company;
use App\Models\Currency;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Modules\Mk\Http\BankAuthController;
use Modules\Mk\Jobs\SyncStopanska;
use Modules\Mk\Services\StopanskaGateway;
use OakLabs\Psd2\AccountDetail;
use OakLabs\Psd2\Transaction;

/**
 * SyncStopanska Job Test Suite
 *
 * Tests for Stopanska Bank PSD2 transaction synchronization job
 * Covers all scenarios: success, failures, rate limiting, token handling
 *
 * Target: 80% coverage as per ROADMAP2.md
 */
describe('SyncStopanska Job', function () {

    beforeEach(function () {
        // Clear any existing data
        DB::table('bank_transactions')->truncate();
        DB::table('bank_accounts')->truncate();

        // Create test company and currency
        $this->company = Company::factory()->create();
        $this->currency = Currency::factory()->create(['code' => 'MKD']);

        // Mock queue system
        Queue::fake();
    });

    it('can be instantiated with required parameters', function () {
        $job = new SyncStopanska($this->company->id);

        expect($job)->toBeInstanceOf(SyncStopanska::class);
    });

    it('can be instantiated with optional parameters', function () {
        $job = new SyncStopanska(
            $this->company->id,
            123, // bank account ID
            60,  // days back
            200  // max transactions
        );

        expect($job)->toBeInstanceOf(SyncStopanska::class);
    });

    it('fails when no stored tokens are found', function () {
        // Mock BankAuthController to return null tokens
        $mockAuthController = Mockery::mock(BankAuthController::class);
        $mockAuthController->shouldReceive('getStoredTokens')
            ->with('stopanska', $this->company->id)
            ->andReturn(null);

        // Create job with mocked controller
        $job = new SyncStopanska($this->company->id);

        // We expect this to throw an exception
        expect(fn () => $job->handle())->toThrow(
            Exception::class,
            'No Stopanska bank connection found for company '.$this->company->id
        );
    });

    it('fails when stored tokens are expired', function () {
        // Mock expired tokens
        $expiredTokens = [
            'access_token' => 'expired_token',
            'expires_at' => Carbon::now()->subHour()->toDateTimeString(),
        ];

        $mockAuthController = Mockery::mock(BankAuthController::class);
        $mockAuthController->shouldReceive('getStoredTokens')
            ->with('stopanska', $this->company->id)
            ->andReturn($expiredTokens);

        $job = new SyncStopanska($this->company->id);

        expect(fn () => $job->handle())->toThrow(
            Exception::class,
            'Stopanska bank token has expired for company '.$this->company->id
        );
    });

    it('successfully syncs transactions with valid tokens', function () {
        // Mock valid tokens
        $validTokens = [
            'access_token' => 'valid_token_123',
            'expires_at' => Carbon::now()->addHour()->toDateTimeString(),
        ];

        // Mock account details
        $mockAccount = Mockery::mock(AccountDetail::class);
        $mockAccount->shouldReceive('getAccountNumber')->andReturn('200123456789');
        $mockAccount->shouldReceive('getIban')->andReturn('MK07200000000123456789');
        $mockAccount->shouldReceive('getName')->andReturn('Test Account');
        $mockAccount->shouldReceive('getCurrency')->andReturn('MKD');
        $mockAccount->shouldReceive('getBic')->andReturn('STBAMK22XXX');
        $mockAccount->shouldReceive('getBalance')->andReturn(5000.00);
        $mockAccount->shouldReceive('getId')->andReturn('acc_123');

        // Mock transaction
        $mockTransaction = Mockery::mock(Transaction::class);
        $mockTransaction->shouldReceive('getExternalUid')->andReturn('ext_12345');
        $mockTransaction->shouldReceive('getTransactionUid')->andReturn('txn_67890');
        $mockTransaction->shouldReceive('getAmount')->andReturn(150.50);
        $mockTransaction->shouldReceive('getCurrency')->andReturn('MKD');
        $mockTransaction->shouldReceive('getDescription')->andReturn('Test payment');
        $mockTransaction->shouldReceive('getCreatedAt')->andReturn(Carbon::now()->toDateTimeString());
        $mockTransaction->shouldReceive('getBookingStatus')->andReturn('booked');
        $mockTransaction->shouldReceive('getDebtorName')->andReturn('John Doe');
        $mockTransaction->shouldReceive('getCreditorName')->andReturn('Jane Smith');
        $mockTransaction->shouldReceive('getIban')->andReturn('MK07200000000123456789');
        $mockTransaction->shouldReceive('getRemittanceInformation')->andReturn('Payment reference');

        // Mock gateway
        $mockGateway = Mockery::mock(StopanskaGateway::class);
        $mockGateway->shouldReceive('setAccessToken')->with('valid_token_123');
        $mockGateway->shouldReceive('getAccountDetails')->andReturn([$mockAccount]);
        $mockGateway->shouldReceive('getSepaTransactions')->andReturn([$mockTransaction]);

        // Mock BankAuthController
        $mockAuthController = Mockery::mock(BankAuthController::class);
        $mockAuthController->shouldReceive('getStoredTokens')
            ->with('stopanska', $this->company->id)
            ->andReturn($validTokens);

        // We need to mock the actual classes in the job
        $this->mock(BankAuthController::class, function ($mock) use ($validTokens) {
            $mock->shouldReceive('getStoredTokens')
                ->with('stopanska', Mockery::any())
                ->andReturn($validTokens);
        });

        $this->mock(StopanskaGateway::class, function ($mock) use ($mockAccount, $mockTransaction) {
            $mock->shouldReceive('setAccessToken')->with('valid_token_123');
            $mock->shouldReceive('getAccountDetails')->andReturn([$mockAccount]);
            $mock->shouldReceive('getSepaTransactions')->andReturn([$mockTransaction]);
        });

        $job = new SyncStopanska($this->company->id);

        // This should not throw an exception
        expect(fn () => $job->handle())->not->toThrow();

        // Verify bank account was created
        $bankAccount = BankAccount::where('company_id', $this->company->id)
            ->where('account_number', '200123456789')
            ->first();

        expect($bankAccount)->not->toBeNull();
        expect($bankAccount->bank_name)->toBe('Stopanska Banka AD Skopje');
        expect($bankAccount->bank_code)->toBe('STB');

        // Verify transaction was stored
        $transaction = DB::table('bank_transactions')
            ->where('bank_account_id', $bankAccount->id)
            ->where('external_reference', 'ext_12345')
            ->first();

        expect($transaction)->not->toBeNull();
        expect($transaction->amount)->toBe(150.50);
        expect($transaction->description)->toBe('Test payment');
    });

    it('skips duplicate transactions', function () {
        // Create existing bank account
        $bankAccount = BankAccount::factory()->create([
            'company_id' => $this->company->id,
            'account_number' => '200123456789',
            'currency_id' => $this->currency->id,
        ]);

        // Create existing transaction
        DB::table('bank_transactions')->insert([
            'bank_account_id' => $bankAccount->id,
            'company_id' => $this->company->id,
            'external_reference' => 'ext_duplicate',
            'transaction_reference' => 'txn_duplicate',
            'amount' => 100.00,
            'currency' => 'MKD',
            'description' => 'Existing transaction',
            'transaction_date' => Carbon::now(),
            'booking_status' => 'booked',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Mock tokens and gateway to return the same transaction
        $validTokens = [
            'access_token' => 'valid_token_123',
            'expires_at' => Carbon::now()->addHour()->toDateTimeString(),
        ];

        $mockAccount = Mockery::mock(AccountDetail::class);
        $mockAccount->shouldReceive('getAccountNumber')->andReturn('200123456789');
        $mockAccount->shouldReceive('getId')->andReturn('acc_123');
        $mockAccount->shouldReceive('getCurrency')->andReturn('MKD');
        $mockAccount->shouldReceive('getName')->andReturn('Test Account');
        $mockAccount->shouldReceive('getIban')->andReturn('MK07200000000123456789');
        $mockAccount->shouldReceive('getBic')->andReturn('STBAMK22XXX');
        $mockAccount->shouldReceive('getBalance')->andReturn(5000.00);

        $mockTransaction = Mockery::mock(Transaction::class);
        $mockTransaction->shouldReceive('getExternalUid')->andReturn('ext_duplicate');
        $mockTransaction->shouldReceive('getCreatedAt')->andReturn(Carbon::now()->toDateTimeString());

        $this->mock(BankAuthController::class, function ($mock) use ($validTokens) {
            $mock->shouldReceive('getStoredTokens')->andReturn($validTokens);
        });

        $this->mock(StopanskaGateway::class, function ($mock) use ($mockAccount, $mockTransaction) {
            $mock->shouldReceive('setAccessToken');
            $mock->shouldReceive('getAccountDetails')->andReturn([$mockAccount]);
            $mock->shouldReceive('getSepaTransactions')->andReturn([$mockTransaction]);
        });

        $job = new SyncStopanska($this->company->id);
        $job->handle();

        // Should still only have 1 transaction (not duplicated)
        $transactionCount = DB::table('bank_transactions')
            ->where('bank_account_id', $bankAccount->id)
            ->where('external_reference', 'ext_duplicate')
            ->count();

        expect($transactionCount)->toBe(1);
    });

    it('skips old transactions beyond cutoff date', function () {
        $validTokens = [
            'access_token' => 'valid_token_123',
            'expires_at' => Carbon::now()->addHour()->toDateTimeString(),
        ];

        $mockAccount = Mockery::mock(AccountDetail::class);
        $mockAccount->shouldReceive('getAccountNumber')->andReturn('200123456789');
        $mockAccount->shouldReceive('getId')->andReturn('acc_123');
        $mockAccount->shouldReceive('getCurrency')->andReturn('MKD');
        $mockAccount->shouldReceive('getName')->andReturn('Test Account');
        $mockAccount->shouldReceive('getIban')->andReturn('MK07200000000123456789');
        $mockAccount->shouldReceive('getBic')->andReturn('STBAMK22XXX');
        $mockAccount->shouldReceive('getBalance')->andReturn(5000.00);

        // Mock old transaction (45 days old, job default is 30 days)
        $mockOldTransaction = Mockery::mock(Transaction::class);
        $mockOldTransaction->shouldReceive('getExternalUid')->andReturn('ext_old');
        $mockOldTransaction->shouldReceive('getCreatedAt')->andReturn(Carbon::now()->subDays(45)->toDateTimeString());

        $this->mock(BankAuthController::class, function ($mock) use ($validTokens) {
            $mock->shouldReceive('getStoredTokens')->andReturn($validTokens);
        });

        $this->mock(StopanskaGateway::class, function ($mock) use ($mockAccount, $mockOldTransaction) {
            $mock->shouldReceive('setAccessToken');
            $mock->shouldReceive('getAccountDetails')->andReturn([$mockAccount]);
            $mock->shouldReceive('getSepaTransactions')->andReturn([$mockOldTransaction]);
        });

        $job = new SyncStopanska($this->company->id);
        $job->handle();

        // Should not have stored the old transaction
        $transactionCount = DB::table('bank_transactions')
            ->where('external_reference', 'ext_old')
            ->count();

        expect($transactionCount)->toBe(0);
    });

    it('respects rate limiting with sleep between accounts', function () {
        // This test verifies the sleep(4) call for rate limiting
        // We'll test that multiple accounts trigger the rate limiting logic

        $validTokens = [
            'access_token' => 'valid_token_123',
            'expires_at' => Carbon::now()->addHour()->toDateTimeString(),
        ];

        // Mock multiple accounts
        $mockAccount1 = Mockery::mock(AccountDetail::class);
        $mockAccount1->shouldReceive('getAccountNumber')->andReturn('200123456789');
        $mockAccount1->shouldReceive('getId')->andReturn('acc_1');
        $mockAccount1->shouldReceive('getCurrency')->andReturn('MKD');
        $mockAccount1->shouldReceive('getName')->andReturn('Account 1');
        $mockAccount1->shouldReceive('getIban')->andReturn('MK07200000000123456789');
        $mockAccount1->shouldReceive('getBic')->andReturn('STBAMK22XXX');
        $mockAccount1->shouldReceive('getBalance')->andReturn(1000.00);

        $mockAccount2 = Mockery::mock(AccountDetail::class);
        $mockAccount2->shouldReceive('getAccountNumber')->andReturn('200987654321');
        $mockAccount2->shouldReceive('getId')->andReturn('acc_2');
        $mockAccount2->shouldReceive('getCurrency')->andReturn('MKD');
        $mockAccount2->shouldReceive('getName')->andReturn('Account 2');
        $mockAccount2->shouldReceive('getIban')->andReturn('MK07200000000987654321');
        $mockAccount2->shouldReceive('getBic')->andReturn('STBAMK22XXX');
        $mockAccount2->shouldReceive('getBalance')->andReturn(2000.00);

        $mockTransaction = Mockery::mock(Transaction::class);
        $mockTransaction->shouldReceive('getExternalUid')->andReturn('ext_rate_test');
        $mockTransaction->shouldReceive('getTransactionUid')->andReturn('txn_rate_test');
        $mockTransaction->shouldReceive('getAmount')->andReturn(100.00);
        $mockTransaction->shouldReceive('getCurrency')->andReturn('MKD');
        $mockTransaction->shouldReceive('getDescription')->andReturn('Rate limit test');
        $mockTransaction->shouldReceive('getCreatedAt')->andReturn(Carbon::now()->toDateTimeString());
        $mockTransaction->shouldReceive('getBookingStatus')->andReturn('booked');
        $mockTransaction->shouldReceive('getDebtorName')->andReturn('Test Debtor');
        $mockTransaction->shouldReceive('getCreditorName')->andReturn('Test Creditor');
        $mockTransaction->shouldReceive('getIban')->andReturn('MK07200000000123456789');
        $mockTransaction->shouldReceive('getRemittanceInformation')->andReturn('Rate test');

        $this->mock(BankAuthController::class, function ($mock) use ($validTokens) {
            $mock->shouldReceive('getStoredTokens')->andReturn($validTokens);
        });

        $this->mock(StopanskaGateway::class, function ($mock) use ($mockAccount1, $mockAccount2, $mockTransaction) {
            $mock->shouldReceive('setAccessToken');
            $mock->shouldReceive('getAccountDetails')->andReturn([$mockAccount1, $mockAccount2]);
            $mock->shouldReceive('getSepaTransactions')->andReturn([$mockTransaction]);
        });

        $startTime = microtime(true);

        $job = new SyncStopanska($this->company->id);
        $job->handle();

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Should take at least 4 seconds due to rate limiting sleep
        // (We have 2 accounts, after first account processes, it sleeps 4 seconds)
        expect($executionTime)->toBeGreaterThan(3.5); // Allow some tolerance
    });

    it('logs errors when sync fails', function () {
        Log::shouldReceive('info');  // Allow info logs
        Log::shouldReceive('error')
            ->once()
            ->with('Stopanska Bank sync failed', Mockery::type('array'));

        // Mock to throw exception
        $this->mock(BankAuthController::class, function ($mock) {
            $mock->shouldReceive('getStoredTokens')
                ->andThrow(new Exception('Database connection failed'));
        });

        $job = new SyncStopanska($this->company->id);

        expect(fn () => $job->handle())->toThrow(Exception::class, 'Database connection failed');
    });

    it('handles failed job with proper logging', function () {
        Log::shouldReceive('error')
            ->once()
            ->with('Stopanska sync job failed permanently', Mockery::type('array'));

        $job = new SyncStopanska($this->company->id);
        $exception = new Exception('Permanent failure');

        $job->failed($exception);

        // Test passes if no exceptions are thrown and logging is called
        expect(true)->toBeTrue();
    });

    it('creates new bank account when none exists', function () {
        $validTokens = [
            'access_token' => 'valid_token_123',
            'expires_at' => Carbon::now()->addHour()->toDateTimeString(),
        ];

        $mockAccount = Mockery::mock(AccountDetail::class);
        $mockAccount->shouldReceive('getAccountNumber')->andReturn('200999888777');
        $mockAccount->shouldReceive('getId')->andReturn('acc_new');
        $mockAccount->shouldReceive('getCurrency')->andReturn('MKD');
        $mockAccount->shouldReceive('getName')->andReturn('New Account');
        $mockAccount->shouldReceive('getIban')->andReturn('MK07200000000999888777');
        $mockAccount->shouldReceive('getBic')->andReturn('STBAMK22XXX');
        $mockAccount->shouldReceive('getBalance')->andReturn(3000.00);

        $this->mock(BankAuthController::class, function ($mock) use ($validTokens) {
            $mock->shouldReceive('getStoredTokens')->andReturn($validTokens);
        });

        $this->mock(StopanskaGateway::class, function ($mock) use ($mockAccount) {
            $mock->shouldReceive('setAccessToken');
            $mock->shouldReceive('getAccountDetails')->andReturn([$mockAccount]);
            $mock->shouldReceive('getSepaTransactions')->andReturn([]);
        });

        // Ensure no existing account
        expect(BankAccount::where('account_number', '200999888777')->count())->toBe(0);

        $job = new SyncStopanska($this->company->id);
        $job->handle();

        // Verify new account was created
        $newAccount = BankAccount::where('account_number', '200999888777')->first();
        expect($newAccount)->not->toBeNull();
        expect($newAccount->name)->toBe('New Account');
        expect($newAccount->current_balance)->toBe(3000.00);
        expect($newAccount->bank_name)->toBe('Stopanska Banka AD Skopje');
    });

    it('updates existing bank account balance', function () {
        // Create existing account with old balance
        $existingAccount = BankAccount::factory()->create([
            'company_id' => $this->company->id,
            'account_number' => '200555444333',
            'current_balance' => 1000.00,
            'currency_id' => $this->currency->id,
        ]);

        $validTokens = [
            'access_token' => 'valid_token_123',
            'expires_at' => Carbon::now()->addHour()->toDateTimeString(),
        ];

        $mockAccount = Mockery::mock(AccountDetail::class);
        $mockAccount->shouldReceive('getAccountNumber')->andReturn('200555444333');
        $mockAccount->shouldReceive('getId')->andReturn('acc_update');
        $mockAccount->shouldReceive('getCurrency')->andReturn('MKD');
        $mockAccount->shouldReceive('getName')->andReturn('Updated Account');
        $mockAccount->shouldReceive('getIban')->andReturn('MK07200000000555444333');
        $mockAccount->shouldReceive('getBic')->andReturn('STBAMK22XXX');
        $mockAccount->shouldReceive('getBalance')->andReturn(2500.00); // New balance

        $this->mock(BankAuthController::class, function ($mock) use ($validTokens) {
            $mock->shouldReceive('getStoredTokens')->andReturn($validTokens);
        });

        $this->mock(StopanskaGateway::class, function ($mock) use ($mockAccount) {
            $mock->shouldReceive('setAccessToken');
            $mock->shouldReceive('getAccountDetails')->andReturn([$mockAccount]);
            $mock->shouldReceive('getSepaTransactions')->andReturn([]);
        });

        $job = new SyncStopanska($this->company->id);
        $job->handle();

        // Verify balance was updated
        $existingAccount->refresh();
        expect($existingAccount->current_balance)->toBe(2500.00);
    });

    afterEach(function () {
        Mockery::close();
    });
});
