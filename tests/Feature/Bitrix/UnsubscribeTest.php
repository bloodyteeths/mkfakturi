<?php

namespace Tests\Feature\Bitrix;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Unsubscribe Feature Tests
 *
 * Tests unsubscribe flow including:
 * - Valid token shows confirmation page
 * - Invalid/expired token shows error page
 * - Processing unsubscribe adds to suppression list
 * - Token can only be used once
 *
 * @ticket HUBSPOT-06 - Unsubscribe Handler with HubSpot Integration
 */
class UnsubscribeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create the tables needed for tests
        $this->createOutreachTables();

        // Configure HubSpot access token
        Config::set('hubspot.access_token', 'test_access_token_12345');
        Config::set('hubspot.pipeline_id', 'default');

        // Fake HTTP calls to HubSpot
        Http::fake([
            'https://api.hubapi.com/*' => Http::response([
                'id' => '12345',
                'properties' => [],
            ], 200),
        ]);
    }

    /**
     * Create the necessary database tables for unsubscribe tests.
     */
    protected function createOutreachTables(): void
    {
        if (!\Schema::hasTable('outreach_unsubscribe_tokens')) {
            \Schema::create('outreach_unsubscribe_tokens', function ($table) {
                $table->id();
                $table->string('token')->unique();
                $table->string('email');
                $table->string('hubspot_contact_id')->nullable();
                $table->string('hubspot_deal_id')->nullable();
                $table->timestamp('used_at')->nullable();
                $table->timestamps();
            });
        }

        if (!\Schema::hasTable('outreach_suppressions')) {
            \Schema::create('outreach_suppressions', function ($table) {
                $table->id();
                $table->string('email')->unique();
                $table->string('type');
                $table->string('reason')->nullable();
                $table->string('source_message_id')->nullable();
                $table->string('source_token')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Create a valid unsubscribe token.
     */
    protected function createToken(string $email, ?string $hubspotContactId = null, ?string $hubspotDealId = null, ?Carbon $createdAt = null): string
    {
        $token = bin2hex(random_bytes(32));

        \DB::table('outreach_unsubscribe_tokens')->insert([
            'token' => $token,
            'email' => $email,
            'hubspot_contact_id' => $hubspotContactId,
            'hubspot_deal_id' => $hubspotDealId,
            'created_at' => $createdAt ?? now(),
            'updated_at' => $createdAt ?? now(),
        ]);

        return $token;
    }

    /** @test */
    public function test_valid_token_shows_confirmation_page()
    {
        $email = 'valid@example.com';
        $token = $this->createToken($email);

        $response = $this->get('/unsubscribe?token=' . $token);

        $response->assertStatus(200);
        $response->assertViewIs('outreach.unsubscribe');
        $response->assertViewHas('token', $token);
        $response->assertViewHas('email', $email);
    }

    /** @test */
    public function test_invalid_token_shows_error_page()
    {
        $response = $this->get('/unsubscribe?token=invalid-token-that-does-not-exist');

        $response->assertStatus(200);
        $response->assertViewIs('outreach.unsubscribe-invalid');
        $response->assertViewHas('message', 'Invalid unsubscribe token.');
    }

    /** @test */
    public function test_missing_token_shows_error_page()
    {
        $response = $this->get('/unsubscribe');

        $response->assertStatus(200);
        $response->assertViewIs('outreach.unsubscribe-invalid');
        $response->assertViewHas('message', 'No unsubscribe token provided.');
    }

    /** @test */
    public function test_expired_token_shows_error_page()
    {
        $email = 'expired@example.com';
        // Create token from 31 days ago (tokens expire after 30 days)
        $token = $this->createToken($email, null, null, now()->subDays(31));

        $response = $this->get('/unsubscribe?token=' . $token);

        $response->assertStatus(200);
        $response->assertViewIs('outreach.unsubscribe-invalid');
        $response->assertViewHas('message', 'This unsubscribe link has expired.');
    }

    /** @test */
    public function test_processing_unsubscribe_adds_to_suppression_list()
    {
        $email = 'unsubscribe@example.com';
        $token = $this->createToken($email);

        $response = $this->post('/unsubscribe', ['token' => $token]);

        $response->assertStatus(200);
        $response->assertViewIs('outreach.unsubscribe-success');
        $response->assertViewHas('email', $email);

        // Email should be in suppression list
        $this->assertDatabaseHas('outreach_suppressions', [
            'email' => $email,
            'type' => 'unsub',
        ]);
    }

    /** @test */
    public function test_token_can_only_be_used_once()
    {
        $email = 'once@example.com';
        $token = $this->createToken($email);

        // First use - should succeed
        $response1 = $this->post('/unsubscribe', ['token' => $token]);
        $response1->assertStatus(200);
        $response1->assertViewIs('outreach.unsubscribe-success');

        // Token should now be marked as used
        $this->assertDatabaseHas('outreach_unsubscribe_tokens', [
            'token' => $token,
        ]);

        $tokenRecord = \DB::table('outreach_unsubscribe_tokens')->where('token', $token)->first();
        $this->assertNotNull($tokenRecord->used_at);

        // Second use - should fail
        $response2 = $this->post('/unsubscribe', ['token' => $token]);
        $response2->assertStatus(200);
        $response2->assertViewIs('outreach.unsubscribe-invalid');
        $response2->assertViewHas('message', 'This unsubscribe link has already been used.');
    }

    /** @test */
    public function test_used_token_shows_error_on_get()
    {
        $email = 'used@example.com';
        $token = $this->createToken($email);

        // Mark token as used
        \DB::table('outreach_unsubscribe_tokens')
            ->where('token', $token)
            ->update(['used_at' => now()]);

        $response = $this->get('/unsubscribe?token=' . $token);

        $response->assertStatus(200);
        $response->assertViewIs('outreach.unsubscribe-invalid');
        $response->assertViewHas('message', 'This unsubscribe link has already been used.');
    }

    /** @test */
    public function test_process_without_token_shows_error()
    {
        $response = $this->post('/unsubscribe', []);

        $response->assertStatus(200);
        $response->assertViewIs('outreach.unsubscribe-invalid');
        $response->assertViewHas('message', 'No unsubscribe token provided.');
    }

    /** @test */
    public function test_process_with_invalid_token_shows_error()
    {
        $response = $this->post('/unsubscribe', ['token' => 'invalid-token']);

        $response->assertStatus(200);
        $response->assertViewIs('outreach.unsubscribe-invalid');
        $response->assertViewHas('message', 'Invalid unsubscribe token.');
    }

    /** @test */
    public function test_unsubscribe_updates_hubspot_deal_status()
    {
        $email = 'hubspot-unsub@example.com';
        $hubspotContactId = '501';
        $hubspotDealId = '101';
        $token = $this->createToken($email, $hubspotContactId, $hubspotDealId);

        $this->post('/unsubscribe', ['token' => $token]);

        // Verify HubSpot API was called to update deal stage to Lost
        Http::assertSent(function ($request) use ($hubspotDealId) {
            return str_contains($request->url(), "crm/v3/objects/deals/{$hubspotDealId}") &&
                   $request->method() === 'PATCH' &&
                   ($request['properties']['dealstage'] ?? null) === 'closedlost';
        });

        // Verify HubSpot note was added to contact
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'crm/v3/objects/notes') &&
                   str_contains($request['properties']['hs_note_body'] ?? '', 'Unsubscribed');
        });
    }

    /** @test */
    public function test_unsubscribe_without_hubspot_contact_still_succeeds()
    {
        $email = 'no-hubspot@example.com';
        $token = $this->createToken($email);  // No HubSpot contact/deal ID

        $response = $this->post('/unsubscribe', ['token' => $token]);

        $response->assertStatus(200);
        $response->assertViewIs('outreach.unsubscribe-success');

        // Email should still be suppressed
        $this->assertDatabaseHas('outreach_suppressions', [
            'email' => $email,
            'type' => 'unsub',
        ]);
    }

    /** @test */
    public function test_token_near_expiration_still_works()
    {
        $email = 'nearexpiry@example.com';
        // Create token from 29 days ago (1 day before expiration)
        $token = $this->createToken($email, null, null, now()->subDays(29));

        $response = $this->get('/unsubscribe?token=' . $token);

        $response->assertStatus(200);
        $response->assertViewIs('outreach.unsubscribe');
    }

    /** @test */
    public function test_suppression_stores_source_token()
    {
        $email = 'source-token@example.com';
        $token = $this->createToken($email);

        $this->post('/unsubscribe', ['token' => $token]);

        $suppression = \DB::table('outreach_suppressions')
            ->where('email', $email)
            ->first();

        $this->assertEquals($token, $suppression->source_token);
    }

    /** @test */
    public function test_duplicate_suppression_is_ignored()
    {
        $email = 'duplicate@example.com';

        // Create first suppression
        \DB::table('outreach_suppressions')->insert([
            'email' => $email,
            'type' => 'bounce',
            'reason' => 'Hard bounce',
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        // Now unsubscribe
        $token = $this->createToken($email);
        $response = $this->post('/unsubscribe', ['token' => $token]);

        $response->assertStatus(200);
        $response->assertViewIs('outreach.unsubscribe-success');

        // Should still only have one suppression record (insertOrIgnore)
        $suppressionCount = \DB::table('outreach_suppressions')
            ->where('email', $email)
            ->count();

        $this->assertEquals(1, $suppressionCount);
    }

    /** @test */
    public function test_unsubscribe_page_shows_email()
    {
        $email = 'showme@example.com';
        $token = $this->createToken($email);

        $response = $this->get('/unsubscribe?token=' . $token);

        $response->assertStatus(200);
        $response->assertSee($email);
    }

    /** @test */
    public function test_success_page_shows_email()
    {
        $email = 'success@example.com';
        $token = $this->createToken($email);

        $response = $this->post('/unsubscribe', ['token' => $token]);

        $response->assertStatus(200);
        $response->assertViewHas('email', $email);
    }
}

// CLAUDE-CHECKPOINT
