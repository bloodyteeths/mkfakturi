<?php

namespace Tests\Feature\Banking;

use App\Models\BankAccount;
use App\Models\BankConnection;
use App\Models\BankProvider;
use App\Models\Company;
use App\Models\Currency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Bank Account Integration Tests
 *
 * Tests account listing, transaction fetching, and data synchronization
 */
class BankAccountTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected BankProvider $provider;
    protected BankConnection $connection;
    protected Currency $currency;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->user = User::factory()->create();
        $this->company = Company::factory()->create([
            'owner_id' => $this->user->id,
        ]);

        $this->currency = Currency::firstOrCreate(
            ['code' => 'MKD'],
            ['name' => 'Macedonian Denar', 'symbol' => 'ден']
        );

        $this->provider = BankProvider::create([
            'key' => 'nlb',
            'name' => 'NLB Banka AD Skopje',
            'base_url' => 'https://sandbox-ob-api.nlb.mk',
            'environment' => 'sandbox',
            'supports_ais' => true,
            'supports_pis' => false,
            'is_active' => true,
        ]);

        $this->connection = BankConnection::create([
            'company_id' => $this->company->id,
            'bank_provider_id' => $this->provider->id,
            'status' => BankConnection::STATUS_ACTIVE,
            'created_by' => $this->user->id,
            'connected_at' => now(),
        ]);
    }

    /** @test */
    public function test_can_list_bank_accounts_from_active_connections()
    {
        // Create local bank account records
        $account = BankAccount::create([
            'company_id' => $this->company->id,
            'bank_code' => 'nlb',
            'bank_name' => 'NLB Banka',
            'account_number' => '200-0000000000-00',
            'iban' => 'MK07200099900000001',
            'currency_id' => $this->currency->id,
            'current_balance' => 50000.00,
            'external_id' => 'nlb_account_123',
            'is_active' => true,
        ]);

        // Mock PSD2 API response
        Http::fake([
            'sandbox-ob-api.nlb.mk/accounts' => Http::response([
                'accounts' => [
                    [
                        'id' => 'nlb_account_123',
                        'iban' => 'MK07200099900000001',
                        'accountNumber' => '200-0000000000-00',
                        'currency' => 'MKD',
                        'balance' => 50000.00,
                        'product' => 'Current Account',
                    ]
                ]
            ], 200),
        ]);

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->getJson('/api/v1/' . $this->company->id . '/bank/accounts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'connection_id',
                        'bank_provider',
                        'bank_code',
                        'account_id',
                        'iban',
                        'balance',
                        'currency',
                    ]
                ]
            ]);
    }

    /** @test */
    public function test_fetching_accounts_updates_last_sync_timestamp()
    {
        $initialSyncTime = $this->connection->last_synced_at;

        // Mock API response
        Http::fake([
            'sandbox-ob-api.nlb.mk/*' => Http::response([
                'accounts' => []
            ], 200),
        ]);

        $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->getJson('/api/v1/' . $this->company->id . '/bank/accounts');

        $this->connection->refresh();
        $this->assertNotNull($this->connection->last_synced_at);
        $this->assertTrue($this->connection->last_synced_at > $initialSyncTime);
    }

    /** @test */
    public function test_can_fetch_transactions_for_account()
    {
        // Create bank account
        $account = BankAccount::create([
            'company_id' => $this->company->id,
            'bank_code' => 'nlb',
            'bank_name' => 'NLB Banka',
            'external_id' => 'nlb_account_123',
            'currency_id' => $this->currency->id,
            'is_active' => true,
        ]);

        // Mock transactions API
        Http::fake([
            'sandbox-ob-api.nlb.mk/accounts/*/transactions*' => Http::response([
                'transactions' => [
                    [
                        'transactionId' => 'txn_001',
                        'bookingDate' => '2025-11-01',
                        'transactionAmount' => ['amount' => 1000.00],
                        'creditorName' => 'Test Customer',
                        'remittanceInformationUnstructured' => 'Payment for invoice #123',
                    ]
                ]
            ], 200),
        ]);

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->getJson('/api/v1/' . $this->company->id . '/bank/accounts/nlb_account_123/transactions?' . http_build_query([
                'connection_id' => $this->connection->id,
                'date_from' => '2025-11-01',
                'date_to' => '2025-11-10',
            ]));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'account_id',
                    'date_from',
                    'date_to',
                    'transaction_count',
                    'transactions',
                ]
            ]);
    }

    /** @test */
    public function test_transaction_fetch_validates_date_range()
    {
        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->getJson('/api/v1/' . $this->company->id . '/bank/accounts/test_account/transactions?' . http_build_query([
                'connection_id' => $this->connection->id,
                'date_from' => '2025-11-10',
                'date_to' => '2025-11-01', // Invalid: to before from
            ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['date_to']);
    }

    /** @test */
    public function test_transaction_fetch_requires_connection_id()
    {
        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->getJson('/api/v1/' . $this->company->id . '/bank/accounts/test_account/transactions');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['connection_id']);
    }

    /** @test */
    public function test_can_sync_bank_account_transactions()
    {
        // Create bank account
        $account = BankAccount::create([
            'company_id' => $this->company->id,
            'bank_code' => 'nlb',
            'bank_name' => 'NLB Banka',
            'external_id' => 'nlb_account_123',
            'currency_id' => $this->currency->id,
            'is_active' => true,
        ]);

        // Mock transactions API
        Http::fake([
            'sandbox-ob-api.nlb.mk/accounts/*/transactions*' => Http::response([
                'transactions' => [
                    [
                        'transactionId' => 'txn_001',
                        'bookingDate' => '2025-11-05',
                        'transactionAmount' => ['amount' => 1000.00],
                        'creditorName' => 'Customer A',
                    ],
                    [
                        'transactionId' => 'txn_002',
                        'bookingDate' => '2025-11-06',
                        'transactionAmount' => ['amount' => -500.00],
                        'debtorName' => 'Supplier B',
                    ],
                ]
            ], 200),
        ]);

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->postJson('/api/v1/' . $this->company->id . '/bank/accounts/' . $account->id . '/sync', [
                'date_from' => '2025-11-01',
                'date_to' => '2025-11-10',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'bank_account_id' => $account->id,
                    'synced_count' => 2,
                ]
            ]);

        // Verify transactions stored in database
        $this->assertDatabaseHas('bank_transactions', [
            'bank_account_id' => $account->id,
            'reference' => 'txn_001',
        ]);

        $this->assertDatabaseHas('bank_transactions', [
            'bank_account_id' => $account->id,
            'reference' => 'txn_002',
        ]);
    }

    /** @test */
    public function test_only_active_connections_can_fetch_data()
    {
        // Set connection to expired
        $this->connection->update(['status' => BankConnection::STATUS_EXPIRED]);

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->getJson('/api/v1/' . $this->company->id . '/bank/accounts');

        $response->assertStatus(200);

        // Should return empty array since no active connections
        $this->assertEmpty($response->json('data'));
    }

    /** @test */
    public function test_company_isolation_for_bank_accounts()
    {
        // Create account for first company
        $account = BankAccount::create([
            'company_id' => $this->company->id,
            'bank_code' => 'nlb',
            'external_id' => 'nlb_account_123',
            'currency_id' => $this->currency->id,
        ]);

        // Create another company
        $otherCompany = Company::factory()->create();

        // Try to sync from different company context
        $response = $this->actingAs($this->user)
            ->withHeader('company', $otherCompany->id)
            ->postJson('/api/v1/' . $otherCompany->id . '/bank/accounts/' . $account->id . '/sync');

        $response->assertStatus(404);
    }

    /** @test */
    public function test_default_date_range_is_last_30_days()
    {
        // Create bank account
        $account = BankAccount::create([
            'company_id' => $this->company->id,
            'bank_code' => 'nlb',
            'external_id' => 'nlb_account_123',
            'currency_id' => $this->currency->id,
        ]);

        // Mock API to capture request
        Http::fake([
            'sandbox-ob-api.nlb.mk/accounts/*/transactions*' => Http::response([
                'transactions' => []
            ], 200),
        ]);

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->postJson('/api/v1/' . $this->company->id . '/bank/accounts/' . $account->id . '/sync');

        $response->assertStatus(200);

        // Verify date range in response
        $data = $response->json('data');
        $dateFrom = \Carbon\Carbon::parse($data['date_from']);
        $dateTo = \Carbon\Carbon::parse($data['date_to']);

        $this->assertEquals(30, $dateTo->diffInDays($dateFrom));
    }
}

// CLAUDE-CHECKPOINT
