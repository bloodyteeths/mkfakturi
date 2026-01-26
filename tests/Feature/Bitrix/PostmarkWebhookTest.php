<?php

namespace Tests\Feature\Bitrix;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * PostmarkWebhook Feature Tests
 *
 * Tests Postmark webhook handling including:
 * - Bounce event adds to suppression list
 * - Complaint event adds to suppression list
 * - Open event updates outreach_send status
 * - Idempotency (same event_id processed only once)
 *
 * @ticket HUBSPOT-05 - Postmark Webhook Handler with HubSpot Integration
 */
class PostmarkWebhookTest extends TestCase
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
     * Create the necessary database tables for webhook tests.
     */
    protected function createOutreachTables(): void
    {
        if (!\Schema::hasTable('outreach_events')) {
            \Schema::create('outreach_events', function ($table) {
                $table->id();
                $table->string('message_id')->nullable();
                $table->string('event_type');
                $table->string('email')->nullable();
                $table->json('payload')->nullable();
                $table->timestamp('processed_at')->nullable();
                $table->timestamps();

                $table->index(['message_id', 'event_type']);
            });
        }

        if (!\Schema::hasTable('outreach_sends')) {
            \Schema::create('outreach_sends', function ($table) {
                $table->id();
                $table->unsignedBigInteger('outreach_lead_id')->nullable();
                $table->string('email')->nullable();
                $table->string('hubspot_contact_id')->nullable();
                $table->string('hubspot_deal_id')->nullable();
                $table->string('message_id')->nullable()->index();
                $table->string('template_key')->nullable();
                $table->string('status')->default('sent');
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->timestamp('opened_at')->nullable();
                $table->timestamp('clicked_at')->nullable();
                $table->timestamp('bounced_at')->nullable();
                $table->timestamp('spam_complained_at')->nullable();
                $table->string('bounce_reason')->nullable();
                $table->integer('open_count')->default(0);
                $table->integer('click_count')->default(0);
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
     * Create an outreach send record for testing.
     */
    protected function createOutreachSend(string $messageId, string $email, ?string $hubspotContactId = null, ?string $hubspotDealId = null): int
    {
        return \DB::table('outreach_sends')->insertGetId([
            'message_id' => $messageId,
            'email' => $email,
            'hubspot_contact_id' => $hubspotContactId,
            'hubspot_deal_id' => $hubspotDealId,
            'template_key' => 'initial',
            'status' => 'sent',
            'sent_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @test */
    public function test_bounce_event_adds_to_suppression_list()
    {
        $messageId = 'pm-bounce-123';
        $email = 'bounced@example.com';

        $this->createOutreachSend($messageId, $email);

        $payload = [
            'RecordType' => 'Bounce',
            'MessageID' => $messageId,
            'Recipient' => $email,
            'Type' => 'HardBounce',
            'Description' => 'The address does not exist',
        ];

        $response = $this->postJson('/webhooks/postmark', $payload);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'received']);

        // Email should be in suppression list
        $this->assertDatabaseHas('outreach_suppressions', [
            'email' => $email,
            'type' => 'bounce',
        ]);

        // Outreach send should be marked as bounced
        $this->assertDatabaseHas('outreach_sends', [
            'message_id' => $messageId,
            'status' => 'bounced',
        ]);
    }

    /** @test */
    public function test_complaint_event_adds_to_suppression_list()
    {
        $messageId = 'pm-spam-456';
        $email = 'complained@example.com';

        $this->createOutreachSend($messageId, $email);

        $payload = [
            'RecordType' => 'SpamComplaint',
            'MessageID' => $messageId,
            'Recipient' => $email,
        ];

        $response = $this->postJson('/webhooks/postmark', $payload);

        $response->assertStatus(200);

        // Email should be in suppression list
        $this->assertDatabaseHas('outreach_suppressions', [
            'email' => $email,
            'type' => 'spam',
        ]);

        // Outreach send should be marked as spam_complaint
        $this->assertDatabaseHas('outreach_sends', [
            'message_id' => $messageId,
            'status' => 'spam_complaint',
        ]);
    }

    /** @test */
    public function test_open_event_updates_outreach_send_status()
    {
        $messageId = 'pm-open-789';
        $email = 'opened@example.com';

        $this->createOutreachSend($messageId, $email, '501', '101');

        $payload = [
            'RecordType' => 'Open',
            'MessageID' => $messageId,
            'Recipient' => $email,
        ];

        $response = $this->postJson('/webhooks/postmark', $payload);

        $response->assertStatus(200);

        // Outreach send should be marked as opened
        $send = \DB::table('outreach_sends')->where('message_id', $messageId)->first();
        $this->assertEquals('opened', $send->status);
        $this->assertNotNull($send->opened_at);
        $this->assertEquals(1, $send->open_count);
    }

    /** @test */
    public function test_multiple_open_events_increment_count()
    {
        $messageId = 'pm-multi-open-111';
        $email = 'multiopen@example.com';

        $this->createOutreachSend($messageId, $email);

        $payload = [
            'RecordType' => 'Open',
            'MessageID' => $messageId,
            'Recipient' => $email,
        ];

        // First open
        $this->postJson('/webhooks/postmark', $payload);

        // Force a different event_id for second open (normally Postmark would use different IDs)
        \DB::table('outreach_events')->truncate();

        // Second open
        $this->postJson('/webhooks/postmark', $payload);

        $send = \DB::table('outreach_sends')->where('message_id', $messageId)->first();
        $this->assertEquals(2, $send->open_count);
    }

    /** @test */
    public function test_click_event_updates_outreach_send_status()
    {
        $messageId = 'pm-click-222';
        $email = 'clicked@example.com';

        $this->createOutreachSend($messageId, $email, '501', '101');

        $payload = [
            'RecordType' => 'Click',
            'MessageID' => $messageId,
            'Recipient' => $email,
            'OriginalLink' => 'https://app.facturino.mk/demo',
        ];

        $response = $this->postJson('/webhooks/postmark', $payload);

        $response->assertStatus(200);

        // Outreach send should be marked as clicked
        $send = \DB::table('outreach_sends')->where('message_id', $messageId)->first();
        $this->assertEquals('clicked', $send->status);
        $this->assertNotNull($send->clicked_at);
        $this->assertEquals(1, $send->click_count);
    }

    /** @test */
    public function test_delivery_event_updates_status()
    {
        $messageId = 'pm-delivery-333';
        $email = 'delivered@example.com';

        $this->createOutreachSend($messageId, $email);

        $payload = [
            'RecordType' => 'Delivery',
            'MessageID' => $messageId,
            'Recipient' => $email,
        ];

        $response = $this->postJson('/webhooks/postmark', $payload);

        $response->assertStatus(200);

        // Outreach send should be marked as delivered
        $this->assertDatabaseHas('outreach_sends', [
            'message_id' => $messageId,
            'status' => 'delivered',
        ]);
    }

    /** @test */
    public function test_idempotency_same_event_processed_only_once()
    {
        $messageId = 'pm-idempotent-444';
        $email = 'idempotent@example.com';

        $this->createOutreachSend($messageId, $email);

        $payload = [
            'RecordType' => 'Open',
            'MessageID' => $messageId,
            'Recipient' => $email,
        ];

        // First request
        $response1 = $this->postJson('/webhooks/postmark', $payload);
        $response1->assertStatus(200);
        $response1->assertJson(['status' => 'received']);

        $eventsCount = \DB::table('outreach_events')->count();

        // Second identical request
        $response2 = $this->postJson('/webhooks/postmark', $payload);
        $response2->assertStatus(200);
        $response2->assertJson(['status' => 'already_processed']);

        // Event count should not increase
        $this->assertEquals($eventsCount, \DB::table('outreach_events')->count());
    }

    /** @test */
    public function test_bounce_event_updates_hubspot_deal_status()
    {
        $messageId = 'pm-bounce-hubspot-555';
        $email = 'hubspot-bounce@example.com';
        $hubspotContactId = '501';
        $hubspotDealId = '101';

        $this->createOutreachSend($messageId, $email, $hubspotContactId, $hubspotDealId);

        $payload = [
            'RecordType' => 'Bounce',
            'MessageID' => $messageId,
            'Recipient' => $email,
            'Type' => 'HardBounce',
            'Description' => 'Address does not exist',
        ];

        $this->postJson('/webhooks/postmark', $payload);

        // Verify HubSpot API was called to update deal stage to Lost
        Http::assertSent(function ($request) use ($hubspotDealId) {
            return str_contains($request->url(), "crm/v3/objects/deals/{$hubspotDealId}") &&
                   $request->method() === 'PATCH' &&
                   ($request['properties']['dealstage'] ?? null) === 'closedlost';
        });

        // Verify HubSpot note was added to contact
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'crm/v3/objects/notes') &&
                   str_contains($request['properties']['hs_note_body'] ?? '', 'bounced');
        });
    }

    /** @test */
    public function test_spam_complaint_updates_hubspot_deal_status()
    {
        $messageId = 'pm-spam-hubspot-666';
        $email = 'hubspot-spam@example.com';
        $hubspotContactId = '502';
        $hubspotDealId = '102';

        $this->createOutreachSend($messageId, $email, $hubspotContactId, $hubspotDealId);

        $payload = [
            'RecordType' => 'SpamComplaint',
            'MessageID' => $messageId,
            'Recipient' => $email,
        ];

        $this->postJson('/webhooks/postmark', $payload);

        // Verify HubSpot API was called to update deal stage to Lost
        Http::assertSent(function ($request) use ($hubspotDealId) {
            return str_contains($request->url(), "crm/v3/objects/deals/{$hubspotDealId}") &&
                   $request->method() === 'PATCH' &&
                   ($request['properties']['dealstage'] ?? null) === 'closedlost';
        });
    }

    /** @test */
    public function test_click_event_creates_hubspot_task()
    {
        $messageId = 'pm-click-hubspot-777';
        $email = 'hubspot-click@example.com';
        $hubspotContactId = '503';
        $hubspotDealId = '103';

        $this->createOutreachSend($messageId, $email, $hubspotContactId, $hubspotDealId);

        $payload = [
            'RecordType' => 'Click',
            'MessageID' => $messageId,
            'Recipient' => $email,
            'OriginalLink' => 'https://app.facturino.mk/signup',
        ];

        $this->postJson('/webhooks/postmark', $payload);

        // Verify HubSpot task was created
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'crm/v3/objects/tasks') &&
                   str_contains($request['properties']['hs_task_subject'] ?? '', 'Call today');
        });
    }

    /** @test */
    public function test_webhook_handles_unknown_event_type()
    {
        $payload = [
            'RecordType' => 'UnknownEvent',
            'MessageID' => 'pm-unknown-888',
            'Recipient' => 'unknown@example.com',
        ];

        $response = $this->postJson('/webhooks/postmark', $payload);

        // Should still return success (webhook acknowledged)
        $response->assertStatus(200);
        $response->assertJson(['status' => 'received']);
    }

    /** @test */
    public function test_webhook_handles_missing_send_record()
    {
        // No outreach_send record exists for this message
        $payload = [
            'RecordType' => 'Open',
            'MessageID' => 'pm-nonexistent-999',
            'Recipient' => 'nonexistent@example.com',
        ];

        $response = $this->postJson('/webhooks/postmark', $payload);

        // Should still return success (webhook acknowledged, even if no matching send)
        $response->assertStatus(200);

        // Event should still be recorded
        $this->assertDatabaseHas('outreach_events', [
            'message_id' => 'pm-nonexistent-999',
            'event_type' => 'Open',
        ]);
    }

    /** @test */
    public function test_bounce_stores_reason()
    {
        $messageId = 'pm-bounce-reason-111';
        $email = 'bounce-reason@example.com';

        $this->createOutreachSend($messageId, $email);

        $payload = [
            'RecordType' => 'Bounce',
            'MessageID' => $messageId,
            'Recipient' => $email,
            'Type' => 'HardBounce',
            'Description' => 'User mailbox does not exist',
        ];

        $this->postJson('/webhooks/postmark', $payload);

        $send = \DB::table('outreach_sends')->where('message_id', $messageId)->first();
        $this->assertStringContainsString('HardBounce', $send->bounce_reason);
        $this->assertStringContainsString('User mailbox does not exist', $send->bounce_reason);
    }
}

// CLAUDE-CHECKPOINT
