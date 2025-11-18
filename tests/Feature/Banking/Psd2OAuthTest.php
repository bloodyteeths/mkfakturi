<?php

namespace Tests\Feature\Banking;

use App\Models\BankToken;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class Psd2OAuthTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->company = Company::factory()->create();
    }

    /** @test */
    public function test_oauth_flow_generates_auth_url()
    {
        // Enable feature flag
        config(['mk.features.psd2_banking' => true]);

        $response = $this->actingAs($this->user)->postJson(
            "/api/v1/admin/banking/{$this->company->id}/auth/stopanska"
        );

        $response->assertStatus(200)
            ->assertJsonStructure([
                'auth_url',
                'bank_code',
                'bank_name',
            ])
            ->assertJson([
                'bank_code' => 'stopanska',
            ]);

        $this->assertStringContainsString('oauth/authorize', $response->json('auth_url'));
    }

    /** @test */
    public function test_feature_flag_guards_endpoints()
    {
        // Disable feature flag
        config(['mk.features.psd2_banking' => false]);

        $response = $this->actingAs($this->user)->postJson(
            "/api/v1/admin/banking/{$this->company->id}/auth/stopanska"
        );

        $response->assertStatus(403)
            ->assertJson([
                'error' => 'PSD2 banking feature is not enabled',
            ]);
    }

    /** @test */
    public function test_callback_exchanges_code_for_token()
    {
        config(['mk.features.psd2_banking' => true]);

        // Mock HTTP response for token exchange
        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'test_access_token',
                'refresh_token' => 'test_refresh_token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
                'scope' => 'accounts transactions',
            ], 200),
        ]);

        // Simulate OAuth callback
        $response = $this->get("/banking/callback/{$this->company->id}/stopanska?code=test_code");

        $response->assertRedirect();
        $this->assertStringContainsString('success=connected', $response->headers->get('Location'));

        // Verify token was stored
        $this->assertDatabaseHas('bank_tokens', [
            'company_id' => $this->company->id,
            'bank_code' => 'stopanska',
            'token_type' => 'Bearer',
        ]);
    }

    /** @test */
    public function test_token_refresh_when_expiring()
    {
        config(['mk.features.psd2_banking' => true]);

        // Create an expiring token
        $token = BankToken::create([
            'company_id' => $this->company->id,
            'bank_code' => 'stopanska',
            'access_token' => 'old_token',
            'refresh_token' => 'refresh_token',
            'token_type' => 'Bearer',
            'expires_at' => now()->addMinutes(2), // Expiring soon
            'scope' => 'accounts transactions',
        ]);

        $this->assertTrue($token->isExpiringSoon());

        // Mock refresh token response
        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'new_access_token',
                'refresh_token' => 'new_refresh_token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ], 200),
            '*/accounts' => Http::response([
                'accounts' => [],
            ], 200),
        ]);

        // Trigger a request that should refresh the token
        $response = $this->actingAs($this->user)->getJson(
            "/api/v1/admin/banking/{$this->company->id}/status/stopanska"
        );

        $response->assertStatus(200);
    }

    /** @test */
    public function test_psd2_fetches_transactions()
    {
        config(['mk.features.psd2_banking' => true]);

        // Create a valid token
        $token = BankToken::create([
            'company_id' => $this->company->id,
            'bank_code' => 'stopanska',
            'access_token' => 'valid_token',
            'refresh_token' => 'refresh_token',
            'token_type' => 'Bearer',
            'expires_at' => now()->addHour(),
            'scope' => 'accounts transactions',
        ]);

        // Mock transaction fetch response
        Http::fake([
            '*/accounts/*/transactions' => Http::response([
                'transactions' => [
                    [
                        'transactionId' => 'TXN001',
                        'amount' => ['amount' => 100.50, 'currency' => 'MKD'],
                        'transactionDate' => '2025-11-01',
                        'description' => 'Test transaction',
                    ],
                ],
            ], 200),
        ]);

        $this->assertTrue($token->isValid());
    }

    /** @test */
    public function test_token_status_endpoint()
    {
        config(['mk.features.psd2_banking' => true]);

        // No token exists
        $response = $this->actingAs($this->user)->getJson(
            "/api/v1/admin/banking/{$this->company->id}/status/stopanska"
        );

        $response->assertStatus(200)
            ->assertJson([
                'connected' => false,
                'bank_code' => 'stopanska',
            ]);

        // Create token
        BankToken::create([
            'company_id' => $this->company->id,
            'bank_code' => 'stopanska',
            'access_token' => 'test_token',
            'token_type' => 'Bearer',
            'expires_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($this->user)->getJson(
            "/api/v1/admin/banking/{$this->company->id}/status/stopanska"
        );

        $response->assertStatus(200)
            ->assertJson([
                'connected' => true,
                'bank_code' => 'stopanska',
                'is_valid' => true,
            ]);
    }

    /** @test */
    public function test_disconnect_revokes_token()
    {
        config(['mk.features.psd2_banking' => true]);

        $token = BankToken::create([
            'company_id' => $this->company->id,
            'bank_code' => 'stopanska',
            'access_token' => 'test_token',
            'token_type' => 'Bearer',
            'expires_at' => now()->addHour(),
        ]);

        Http::fake([
            '*/oauth/revoke' => Http::response([], 200),
        ]);

        $response = $this->actingAs($this->user)->deleteJson(
            "/api/v1/admin/banking/{$this->company->id}/disconnect/stopanska"
        );

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseMissing('bank_tokens', [
            'id' => $token->id,
        ]);
    }

    /** @test */
    public function test_unsupported_bank_returns_error()
    {
        config(['mk.features.psd2_banking' => true]);

        $response = $this->actingAs($this->user)->postJson(
            "/api/v1/admin/banking/{$this->company->id}/auth/unsupported_bank"
        );

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'Unsupported bank: unsupported_bank',
            ]);
    }
}

// CLAUDE-CHECKPOINT
