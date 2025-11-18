<?php

namespace Tests\Feature\Banking;

use App\Models\BankConnection;
use App\Models\BankProvider;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Bank Connection Integration Tests
 *
 * Tests OAuth flow, connection lifecycle, and company isolation
 */
class BankConnectionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Company $company;

    protected BankProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user and company
        $this->user = User::factory()->create();
        $this->company = Company::factory()->create([
            'owner_id' => $this->user->id,
        ]);

        // Create bank provider
        $this->provider = BankProvider::create([
            'key' => 'nlb',
            'name' => 'NLB Banka AD Skopje',
            'base_url' => 'https://sandbox-ob-api.nlb.mk',
            'environment' => 'sandbox',
            'supports_ais' => true,
            'supports_pis' => false,
            'is_active' => true,
            'metadata' => ['bic' => 'NLBMKMK2XXX'],
        ]);
    }

    /** @test */
    public function test_can_initiate_oauth_flow()
    {
        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->postJson('/api/v1/'.$this->company->id.'/bank/oauth/start', [
                'bank_provider_key' => 'nlb',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'authorization_url',
                    'connection_id',
                    'bank_provider',
                ],
            ]);

        // Verify connection record created
        $this->assertDatabaseHas('bank_connections', [
            'company_id' => $this->company->id,
            'bank_provider_id' => $this->provider->id,
            'status' => BankConnection::STATUS_PENDING,
        ]);

        // Verify authorization URL contains required parameters
        $authUrl = $response->json('data.authorization_url');
        $this->assertStringContainsString('redirect_uri', $authUrl);
        $this->assertStringContainsString('client_id', $authUrl);
        $this->assertStringContainsString('state', $authUrl);
    }

    /** @test */
    public function test_oauth_flow_respects_company_isolation()
    {
        // Create another company
        $otherCompany = Company::factory()->create();

        // Create connection for first company
        $connection = BankConnection::create([
            'company_id' => $this->company->id,
            'bank_provider_id' => $this->provider->id,
            'status' => BankConnection::STATUS_ACTIVE,
            'created_by' => $this->user->id,
        ]);

        // Try to access from different company context
        $response = $this->actingAs($this->user)
            ->withHeader('company', $otherCompany->id)
            ->getJson('/api/v1/'.$otherCompany->id.'/bank/connections');

        // Should not see the first company's connection
        $response->assertStatus(200);
        $connections = $response->json('data');
        $this->assertEmpty($connections);
    }

    /** @test */
    public function test_can_list_bank_connections()
    {
        // Create multiple connections
        $connection1 = BankConnection::create([
            'company_id' => $this->company->id,
            'bank_provider_id' => $this->provider->id,
            'status' => BankConnection::STATUS_ACTIVE,
            'created_by' => $this->user->id,
            'connected_at' => now(),
        ]);

        $connection2 = BankConnection::create([
            'company_id' => $this->company->id,
            'bank_provider_id' => $this->provider->id,
            'status' => BankConnection::STATUS_EXPIRED,
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->getJson('/api/v1/'.$this->company->id.'/bank/connections');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'bank_provider',
                        'status',
                        'is_active',
                        'is_expired',
                    ],
                ],
            ]);
    }

    /** @test */
    public function test_can_revoke_bank_connection()
    {
        $connection = BankConnection::create([
            'company_id' => $this->company->id,
            'bank_provider_id' => $this->provider->id,
            'status' => BankConnection::STATUS_ACTIVE,
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->deleteJson('/api/v1/'.$this->company->id.'/bank/connections/'.$connection->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => BankConnection::STATUS_REVOKED,
                ],
            ]);

        // Verify database updated
        $this->assertDatabaseHas('bank_connections', [
            'id' => $connection->id,
            'status' => BankConnection::STATUS_REVOKED,
        ]);
    }

    /** @test */
    public function test_cannot_revoke_another_companys_connection()
    {
        // Create another company and connection
        $otherCompany = Company::factory()->create();
        $connection = BankConnection::create([
            'company_id' => $otherCompany->id,
            'bank_provider_id' => $this->provider->id,
            'status' => BankConnection::STATUS_ACTIVE,
            'created_by' => $this->user->id,
        ]);

        // Try to revoke from different company context
        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->deleteJson('/api/v1/'.$this->company->id.'/bank/connections/'.$connection->id);

        $response->assertStatus(404);

        // Verify connection not revoked
        $this->assertDatabaseHas('bank_connections', [
            'id' => $connection->id,
            'status' => BankConnection::STATUS_ACTIVE,
        ]);
    }

    /** @test */
    public function test_oauth_start_validates_bank_provider()
    {
        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->postJson('/api/v1/'.$this->company->id.'/bank/oauth/start', [
                'bank_provider_key' => 'invalid_bank',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['bank_provider_key']);
    }

    /** @test */
    public function test_oauth_start_requires_authentication()
    {
        $response = $this->postJson('/api/v1/'.$this->company->id.'/bank/oauth/start', [
            'bank_provider_key' => 'nlb',
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function test_expired_connections_are_identified_correctly()
    {
        $expiredConnection = BankConnection::create([
            'company_id' => $this->company->id,
            'bank_provider_id' => $this->provider->id,
            'status' => BankConnection::STATUS_ACTIVE,
            'created_by' => $this->user->id,
            'connected_at' => now()->subDays(90),
            'expires_at' => now()->subDay(), // Expired yesterday
        ]);

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->getJson('/api/v1/'.$this->company->id.'/bank/connections');

        $response->assertStatus(200);

        $connections = $response->json('data');
        $this->assertTrue($connections[0]['is_expired']);
        $this->assertFalse($connections[0]['is_active']);
    }

    /** @test */
    public function test_only_active_providers_can_be_used()
    {
        // Deactivate provider
        $this->provider->update(['is_active' => false]);

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->postJson('/api/v1/'.$this->company->id.'/bank/oauth/start', [
                'bank_provider_key' => 'nlb',
            ]);

        $response->assertStatus(500);
    }

    /** @test */
    public function test_connection_metadata_is_stored()
    {
        $connection = BankConnection::create([
            'company_id' => $this->company->id,
            'bank_provider_id' => $this->provider->id,
            'status' => BankConnection::STATUS_ACTIVE,
            'created_by' => $this->user->id,
            'metadata' => [
                'consent_id' => 'test-consent-123',
                'accounts_count' => 2,
            ],
        ]);

        $this->assertEquals('test-consent-123', $connection->metadata['consent_id']);
        $this->assertEquals(2, $connection->metadata['accounts_count']);
    }
}

// CLAUDE-CHECKPOINT
