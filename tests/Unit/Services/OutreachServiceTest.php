<?php

namespace Tests\Unit\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Mockery;
use Modules\Mk\Bitrix\Models\OutreachLead;
use Modules\Mk\Bitrix\Models\OutreachSend;
use Modules\Mk\Bitrix\Models\Suppression;
use Modules\Mk\Bitrix\Services\Bitrix24ApiClient;
use Modules\Mk\Bitrix\Services\OutreachService;
use Modules\Mk\Bitrix\Services\PostmarkOutreachService;
use Tests\TestCase;

/**
 * OutreachService Unit Tests
 *
 * Tests the outreach service functionality including:
 * - Suppression list blocking
 * - canSendToLead validation
 * - Unsubscribe token generation and verification
 * - Daily/hourly rate limits
 *
 * @ticket BITRIX-02 - Outreach Service
 */
class OutreachServiceTest extends TestCase
{
    use RefreshDatabase;

    protected OutreachService $service;

    protected $mockBitrixClient;

    protected $mockPostmarkService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create the tables needed for tests
        $this->createOutreachTables();

        // Configure app key for token generation
        Config::set('app.key', 'base64:'.base64_encode('test-secret-key-32-bytes!!!!!!!'));
        Config::set('app.url', 'https://app.facturino.mk');
        Config::set('bitrix.outreach.daily_limit', 100);
        Config::set('bitrix.outreach.hourly_limit', 20);

        // Create mock services
        $this->mockBitrixClient = Mockery::mock(Bitrix24ApiClient::class);
        $this->mockPostmarkService = Mockery::mock(PostmarkOutreachService::class);

        $this->service = new OutreachService(
            $this->mockBitrixClient,
            $this->mockPostmarkService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Create the necessary database tables for outreach tests.
     */
    protected function createOutreachTables(): void
    {
        if (!\Schema::hasTable('outreach_leads')) {
            \Schema::create('outreach_leads', function ($table) {
                $table->id();
                $table->string('email')->index();
                $table->string('company_name')->nullable();
                $table->string('contact_name')->nullable();
                $table->string('phone')->nullable();
                $table->string('city')->nullable();
                $table->string('source')->nullable();
                $table->string('source_url')->nullable();
                $table->json('tags')->nullable();
                $table->string('status')->default('new');
                $table->unsignedBigInteger('partner_id')->nullable();
                $table->timestamp('last_contacted_at')->nullable();
                $table->timestamps();
            });
        }

        if (!\Schema::hasTable('suppressions')) {
            \Schema::create('suppressions', function ($table) {
                $table->id();
                $table->string('email')->unique();
                $table->string('reason');
                $table->string('source')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (!\Schema::hasTable('outreach_sends')) {
            \Schema::create('outreach_sends', function ($table) {
                $table->id();
                $table->unsignedBigInteger('outreach_lead_id')->nullable();
                $table->string('email')->nullable();
                $table->string('bitrix_lead_id')->nullable();
                $table->string('template_key');
                $table->string('postmark_message_id')->nullable();
                $table->string('status')->default('pending');
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->timestamp('opened_at')->nullable();
                $table->timestamp('clicked_at')->nullable();
                $table->timestamp('bounced_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /** @test */
    public function test_suppression_blocks_sending()
    {
        // Add email to suppression list
        Suppression::suppress('blocked@example.com', 'unsubscribe');

        // Create a lead with the suppressed email
        $lead = OutreachLead::create([
            'email' => 'blocked@example.com',
            'company_name' => 'Test Company',
            'status' => OutreachLead::STATUS_NEW,
        ]);

        // canSendToLead should return false
        $canSend = $this->service->canSendToLead($lead);

        $this->assertFalse($canSend);
    }

    /** @test */
    public function test_can_send_to_lead_returns_false_for_suppressed_email()
    {
        // Suppress the email with bounce reason
        Suppression::suppress('bounced@example.com', Suppression::REASON_BOUNCE);

        $lead = OutreachLead::create([
            'email' => 'bounced@example.com',
            'company_name' => 'Bounced Company',
            'status' => OutreachLead::STATUS_NEW,
        ]);

        $this->assertFalse($this->service->canSendToLead($lead));
    }

    /** @test */
    public function test_can_send_to_lead_returns_true_for_new_lead()
    {
        $lead = OutreachLead::create([
            'email' => 'new@example.com',
            'company_name' => 'New Company',
            'status' => OutreachLead::STATUS_NEW,
        ]);

        $this->assertTrue($this->service->canSendToLead($lead));
    }

    /** @test */
    public function test_can_send_to_lead_returns_false_for_recently_contacted_lead()
    {
        $lead = OutreachLead::create([
            'email' => 'recent@example.com',
            'company_name' => 'Recent Company',
            'status' => OutreachLead::STATUS_EMAILED,
            'last_contacted_at' => now()->subDay(), // Contacted 1 day ago (within 2 day cooldown)
        ]);

        $this->assertFalse($this->service->canSendToLead($lead));
    }

    /** @test */
    public function test_can_send_to_lead_returns_true_after_cooldown_period()
    {
        $lead = OutreachLead::create([
            'email' => 'cooldown@example.com',
            'company_name' => 'Cooldown Company',
            'status' => OutreachLead::STATUS_EMAILED,
            'last_contacted_at' => now()->subDays(3), // Contacted 3 days ago (past 2 day cooldown)
        ]);

        $this->assertTrue($this->service->canSendToLead($lead));
    }

    /** @test */
    public function test_can_send_to_lead_returns_false_for_lost_status()
    {
        $lead = OutreachLead::create([
            'email' => 'lost@example.com',
            'company_name' => 'Lost Company',
            'status' => OutreachLead::STATUS_LOST,
        ]);

        $this->assertFalse($this->service->canSendToLead($lead));
    }

    /** @test */
    public function test_can_send_to_lead_returns_false_for_partner_created_status()
    {
        $lead = OutreachLead::create([
            'email' => 'partner@example.com',
            'company_name' => 'Partner Company',
            'status' => OutreachLead::STATUS_PARTNER_CREATED,
        ]);

        $this->assertFalse($this->service->canSendToLead($lead));
    }

    /** @test */
    public function test_generate_unsubscribe_token_creates_valid_token()
    {
        $email = 'test@example.com';
        $token = $this->service->generateUnsubscribeToken($email);

        $this->assertNotEmpty($token);
        $this->assertIsString($token);

        // Token should be base64 encoded
        $decoded = base64_decode($token);
        $this->assertNotFalse($decoded);

        // Should contain pipe-separated parts
        $parts = explode('|', $decoded);
        $this->assertCount(3, $parts);

        // Third part should be the email
        $this->assertEquals($email, $parts[2]);
    }

    /** @test */
    public function test_process_unsubscribe_marks_email_suppressed()
    {
        $email = 'unsubscribe@example.com';

        // Create a valid token
        $secret = config('app.key');
        $timestamp = now()->timestamp;
        $hash = hash_hmac('sha256', $email . '|' . $timestamp, $secret);
        $token = base64_encode($hash . '|' . $timestamp . '|' . $email);

        // Create a lead with this email
        OutreachLead::create([
            'email' => $email,
            'company_name' => 'Unsubscribe Test',
            'status' => OutreachLead::STATUS_EMAILED,
        ]);

        $result = $this->service->processUnsubscribe($token);

        $this->assertTrue($result);

        // Email should now be suppressed
        $this->assertTrue(Suppression::isSuppressed($email));

        // Lead status should be updated to lost
        $lead = OutreachLead::where('email', $email)->first();
        $this->assertEquals(OutreachLead::STATUS_LOST, $lead->status);
    }

    /** @test */
    public function test_process_unsubscribe_fails_with_invalid_token()
    {
        $result = $this->service->processUnsubscribe('invalid-token');

        $this->assertFalse($result);
    }

    /** @test */
    public function test_process_unsubscribe_fails_with_expired_token()
    {
        $email = 'expired@example.com';

        // Create a token from 8 days ago (tokens expire after 7 days)
        $secret = config('app.key');
        $timestamp = now()->subDays(8)->timestamp;
        $hash = hash_hmac('sha256', $email . '|' . $timestamp, $secret);
        $token = base64_encode($hash . '|' . $timestamp . '|' . $email);

        $result = $this->service->processUnsubscribe($token);

        $this->assertFalse($result);
    }

    /** @test */
    public function test_process_unsubscribe_fails_with_tampered_token()
    {
        $email = 'tampered@example.com';

        // Create a token with wrong hash
        $timestamp = now()->timestamp;
        $fakeHash = hash_hmac('sha256', 'wrong-email|' . $timestamp, config('app.key'));
        $token = base64_encode($fakeHash . '|' . $timestamp . '|' . $email);

        $result = $this->service->processUnsubscribe($token);

        $this->assertFalse($result);
    }

    /** @test */
    public function test_is_email_suppressed_returns_true_for_suppressed()
    {
        Suppression::suppress('suppressed@example.com', 'bounce');

        $this->assertTrue($this->service->isEmailSuppressed('suppressed@example.com'));
    }

    /** @test */
    public function test_is_email_suppressed_returns_false_for_not_suppressed()
    {
        $this->assertFalse($this->service->isEmailSuppressed('active@example.com'));
    }

    /** @test */
    public function test_is_email_suppressed_is_case_insensitive()
    {
        Suppression::suppress('test@example.com', 'unsubscribe');

        $this->assertTrue($this->service->isEmailSuppressed('TEST@EXAMPLE.COM'));
        $this->assertTrue($this->service->isEmailSuppressed('Test@Example.Com'));
    }

    /** @test */
    public function test_suppress_email_creates_suppression_record()
    {
        $suppression = $this->service->suppressEmail(
            'newsuppression@example.com',
            Suppression::REASON_MANUAL,
            ['note' => 'Admin added']
        );

        $this->assertInstanceOf(Suppression::class, $suppression);
        $this->assertEquals('newsuppression@example.com', $suppression->email);
        $this->assertEquals(Suppression::REASON_MANUAL, $suppression->reason);

        $this->assertDatabaseHas('suppressions', [
            'email' => 'newsuppression@example.com',
            'reason' => Suppression::REASON_MANUAL,
        ]);
    }

    /** @test */
    public function test_daily_limit_is_respected()
    {
        // Mock the PostmarkOutreachService to check limits
        $this->mockPostmarkService
            ->shouldReceive('isWithinDailyLimit')
            ->andReturn(false);

        $this->mockPostmarkService
            ->shouldReceive('isWithinHourlyLimit')
            ->andReturn(true);

        $lead = OutreachLead::create([
            'email' => 'daily-limit@example.com',
            'company_name' => 'Daily Limit Test',
            'status' => OutreachLead::STATUS_NEW,
        ]);

        // sendInitialEmail should return null when daily limit exceeded
        $result = $this->service->sendInitialEmail($lead);

        $this->assertNull($result);
    }

    /** @test */
    public function test_hourly_limit_is_respected()
    {
        // Mock the PostmarkOutreachService to check limits
        $this->mockPostmarkService
            ->shouldReceive('isWithinDailyLimit')
            ->andReturn(true);

        $this->mockPostmarkService
            ->shouldReceive('isWithinHourlyLimit')
            ->andReturn(false);

        $lead = OutreachLead::create([
            'email' => 'hourly-limit@example.com',
            'company_name' => 'Hourly Limit Test',
            'status' => OutreachLead::STATUS_NEW,
        ]);

        // sendInitialEmail should return null when hourly limit exceeded
        $result = $this->service->sendInitialEmail($lead);

        $this->assertNull($result);
    }

    /** @test */
    public function test_send_initial_email_succeeds_when_within_limits()
    {
        // Create lead without mapping to avoid Bitrix sync
        $lead = OutreachLead::create([
            'email' => 'valid@example.com',
            'company_name' => 'Valid Company',
            'status' => OutreachLead::STATUS_NEW,
        ]);

        // Mock Postmark service
        $this->mockPostmarkService
            ->shouldReceive('isWithinDailyLimit')
            ->andReturn(true);

        $this->mockPostmarkService
            ->shouldReceive('isWithinHourlyLimit')
            ->andReturn(true);

        $this->mockPostmarkService
            ->shouldReceive('sendOutreachEmail')
            ->once()
            ->andReturn('pm-test-123');

        $result = $this->service->sendInitialEmail($lead);

        $this->assertInstanceOf(OutreachSend::class, $result);
        $this->assertEquals('initial', $result->template_key);
        $this->assertEquals('pm-test-123', $result->postmark_message_id);

        // Lead status should be updated
        $lead->refresh();
        $this->assertEquals(OutreachLead::STATUS_EMAILED, $lead->status);
        $this->assertNotNull($lead->last_contacted_at);
    }

    /** @test */
    public function test_get_sent_count_today()
    {
        // Create some sends
        OutreachSend::create([
            'email' => 'test1@example.com',
            'template_key' => 'initial',
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        OutreachSend::create([
            'email' => 'test2@example.com',
            'template_key' => 'initial',
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        // Old send from yesterday
        OutreachSend::create([
            'email' => 'old@example.com',
            'template_key' => 'initial',
            'status' => 'sent',
            'sent_at' => now()->subDay(),
            'created_at' => now()->subDay(),
        ]);

        $count = $this->service->getSentCountToday();

        $this->assertEquals(2, $count);
    }

    /** @test */
    public function test_get_sent_count_this_hour()
    {
        // Create some sends this hour
        OutreachSend::create([
            'email' => 'hour1@example.com',
            'template_key' => 'initial',
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        // Old send from 2 hours ago
        OutreachSend::create([
            'email' => 'oldhour@example.com',
            'template_key' => 'initial',
            'status' => 'sent',
            'sent_at' => now()->subHours(2),
            'created_at' => now()->subHours(2),
        ]);

        $count = $this->service->getSentCountThisHour();

        $this->assertEquals(1, $count);
    }

    /** @test */
    public function test_get_pending_leads_returns_new_leads()
    {
        // Create various leads
        OutreachLead::create([
            'email' => 'pending1@example.com',
            'company_name' => 'Pending 1',
            'status' => OutreachLead::STATUS_NEW,
        ]);

        OutreachLead::create([
            'email' => 'pending2@example.com',
            'company_name' => 'Pending 2',
            'status' => OutreachLead::STATUS_NEW,
        ]);

        OutreachLead::create([
            'email' => 'emailed@example.com',
            'company_name' => 'Already Emailed',
            'status' => OutreachLead::STATUS_EMAILED,
        ]);

        $pendingLeads = $this->service->getPendingLeads(10);

        $this->assertCount(2, $pendingLeads);
        $this->assertTrue($pendingLeads->every(fn ($lead) => $lead->status === OutreachLead::STATUS_NEW));
    }
}

// CLAUDE-CHECKPOINT
