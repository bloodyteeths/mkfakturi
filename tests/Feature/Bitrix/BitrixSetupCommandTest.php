<?php

namespace Tests\Feature\Bitrix;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * BitrixSetupCommand Feature Tests
 *
 * Tests the Bitrix24 setup command functionality including:
 * - Idempotent setup (running twice creates fields only once)
 * - Test flag doesn't make changes
 * - Mock Bitrix API responses
 *
 * @ticket BITRIX-03 - Bitrix Setup Command
 */
class BitrixSetupCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Configure Bitrix24 webhook URL
        Config::set('bitrix.webhook_base_url', 'https://test.bitrix24.com/rest/1/testtoken');
        Config::set('bitrix.shared_secret', 'test_secret');
    }

    /** @test */
    public function test_setup_is_idempotent_running_twice_creates_fields_only_once()
    {
        // Mock HTTP responses for two runs
        // First run: fields don't exist, create them
        // Second run: fields exist, skip them
        Http::fake([
            // Profile check (connection test)
            'https://test.bitrix24.com/rest/1/testtoken/profile.json' => Http::response([
                'result' => ['ID' => '1', 'NAME' => 'Test User'],
            ], 200),

            // Get existing user fields - empty first, then has fields
            'https://test.bitrix24.com/rest/1/testtoken/crm.lead.userfield.list.json' => Http::sequence()
                ->push(['result' => []], 200)  // First run - no fields
                ->push(['result' => [          // Second run - fields exist
                    ['FIELD_NAME' => 'UF_CRM_FCT_SOURCE'],
                    ['FIELD_NAME' => 'UF_CRM_FCT_SOURCE_URL'],
                    ['FIELD_NAME' => 'UF_CRM_FCT_CITY'],
                    ['FIELD_NAME' => 'UF_CRM_FCT_TAGS'],
                    ['FIELD_NAME' => 'UF_CRM_FCT_FACTURINO_PARTNER_ID'],
                    ['FIELD_NAME' => 'UF_CRM_FCT_LAST_POSTMARK_MESSAGE_ID'],
                ]], 200),

            // Get existing statuses - empty first, then has statuses
            'https://test.bitrix24.com/rest/1/testtoken/crm.status.list.json' => Http::sequence()
                ->push(['result' => []], 200)  // First run
                ->push(['result' => [          // Second run - statuses exist
                    ['STATUS_ID' => 'NEW'],
                    ['STATUS_ID' => 'UC_EMAILED'],
                    ['STATUS_ID' => 'UC_FOLLOWUP'],
                    ['STATUS_ID' => 'UC_INTERESTED'],
                    ['STATUS_ID' => 'UC_INVITE_SENT'],
                    ['STATUS_ID' => 'UC_PARTNER_CREATED'],
                    ['STATUS_ID' => 'UC_ACTIVE'],
                    ['STATUS_ID' => 'JUNK'],
                ]], 200),

            // Create user fields - should succeed first run
            'https://test.bitrix24.com/rest/1/testtoken/crm.lead.userfield.add.json' => Http::response([
                'result' => 100,
            ], 200),

            // Create statuses - should succeed first run
            'https://test.bitrix24.com/rest/1/testtoken/crm.status.add.json' => Http::response([
                'result' => 200,
            ], 200),
        ]);

        // First run - creates fields and statuses
        $this->artisan('bitrix:setup')
            ->expectsOutputToContain('Bitrix24 Setup for Facturino')
            ->expectsOutputToContain('Connection successful!')
            ->expectsOutputToContain('Bitrix24 setup completed successfully!')
            ->assertSuccessful();

        // Second run - should skip existing fields
        $this->artisan('bitrix:setup')
            ->expectsOutputToContain('Bitrix24 setup completed successfully!')
            ->assertSuccessful();
    }

    /** @test */
    public function test_test_flag_does_not_make_changes()
    {
        Http::fake([
            // Profile check only
            'https://test.bitrix24.com/rest/1/testtoken/profile.json' => Http::response([
                'result' => ['ID' => '1', 'NAME' => 'Test User'],
            ], 200),
        ]);

        $this->artisan('bitrix:setup', ['--test' => true])
            ->expectsOutputToContain('Connection test successful')
            ->assertSuccessful();

        // Should only call profile, not create fields or statuses
        Http::assertSentCount(1);
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'profile.json');
        });
        Http::assertNotSent(function ($request) {
            return str_contains($request->url(), 'userfield.add') ||
                   str_contains($request->url(), 'status.add');
        });
    }

    /** @test */
    public function test_setup_fails_with_invalid_webhook_url()
    {
        Config::set('bitrix.webhook_base_url', '');

        $this->artisan('bitrix:setup')
            ->expectsOutputToContain('BITRIX24_WEBHOOK_BASE_URL is not configured')
            ->assertFailed();
    }

    /** @test */
    public function test_setup_fails_on_connection_error()
    {
        Http::fake([
            'https://test.bitrix24.com/rest/1/testtoken/profile.json' => Http::response([
                'error' => 'INVALID_TOKEN',
                'error_description' => 'Invalid webhook token',
            ], 200),
        ]);

        $this->artisan('bitrix:setup')
            ->expectsOutputToContain('Connection failed')
            ->assertFailed();
    }

    /** @test */
    public function test_setup_handles_existing_fields_gracefully()
    {
        Http::fake([
            'https://test.bitrix24.com/rest/1/testtoken/profile.json' => Http::response([
                'result' => ['ID' => '1', 'NAME' => 'Test User'],
            ], 200),

            'https://test.bitrix24.com/rest/1/testtoken/crm.lead.userfield.list.json' => Http::response([
                'result' => [
                    ['FIELD_NAME' => 'UF_CRM_FCT_SOURCE'],
                ],
            ], 200),

            'https://test.bitrix24.com/rest/1/testtoken/crm.status.list.json' => Http::response([
                'result' => [],
            ], 200),

            // Return error for existing field
            'https://test.bitrix24.com/rest/1/testtoken/crm.lead.userfield.add.json' => Http::sequence()
                ->push([
                    'error' => 'FIELD_NAME_USED',
                    'error_description' => 'Field name already used',
                ], 200)
                ->push(['result' => 101], 200)
                ->push(['result' => 102], 200)
                ->push(['result' => 103], 200)
                ->push(['result' => 104], 200)
                ->push(['result' => 105], 200),

            'https://test.bitrix24.com/rest/1/testtoken/crm.status.add.json' => Http::response([
                'result' => 200,
            ], 200),
        ]);

        $this->artisan('bitrix:setup')
            ->expectsOutputToContain('Bitrix24 setup completed successfully!')
            ->assertSuccessful();
    }

    /** @test */
    public function test_setup_skips_builtin_statuses()
    {
        Http::fake([
            'https://test.bitrix24.com/rest/1/testtoken/profile.json' => Http::response([
                'result' => ['ID' => '1', 'NAME' => 'Test User'],
            ], 200),

            'https://test.bitrix24.com/rest/1/testtoken/crm.lead.userfield.list.json' => Http::response([
                'result' => [],
            ], 200),

            'https://test.bitrix24.com/rest/1/testtoken/crm.status.list.json' => Http::response([
                'result' => [],
            ], 200),

            'https://test.bitrix24.com/rest/1/testtoken/crm.lead.userfield.add.json' => Http::response([
                'result' => 100,
            ], 200),

            'https://test.bitrix24.com/rest/1/testtoken/crm.status.add.json' => Http::response([
                'result' => 200,
            ], 200),
        ]);

        $this->artisan('bitrix:setup')
            ->assertSuccessful();

        // Should not try to create built-in statuses like NEW, JUNK
        Http::assertNotSent(function ($request) {
            if (!str_contains($request->url(), 'crm.status.add')) {
                return false;
            }
            $statusId = $request['fields']['STATUS_ID'] ?? '';
            return in_array($statusId, ['NEW', 'JUNK', 'PROCESSED']);
        });
    }

    /** @test */
    public function test_setup_with_force_flag_recreates_fields()
    {
        Http::fake([
            'https://test.bitrix24.com/rest/1/testtoken/profile.json' => Http::response([
                'result' => ['ID' => '1', 'NAME' => 'Test User'],
            ], 200),

            'https://test.bitrix24.com/rest/1/testtoken/crm.lead.userfield.list.json' => Http::response([
                'result' => [
                    ['FIELD_NAME' => 'UF_CRM_FCT_SOURCE'],
                ],
            ], 200),

            'https://test.bitrix24.com/rest/1/testtoken/crm.status.list.json' => Http::response([
                'result' => [
                    ['STATUS_ID' => 'UC_EMAILED'],
                ],
            ], 200),

            'https://test.bitrix24.com/rest/1/testtoken/crm.lead.userfield.add.json' => Http::response([
                'result' => 100,
            ], 200),

            'https://test.bitrix24.com/rest/1/testtoken/crm.status.add.json' => Http::response([
                'result' => 200,
            ], 200),
        ]);

        $this->artisan('bitrix:setup', ['--force' => true])
            ->assertSuccessful();

        // With force flag, should try to create fields even if they exist
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'crm.lead.userfield.add');
        });
    }

    /** @test */
    public function test_setup_displays_progress()
    {
        Http::fake([
            'https://test.bitrix24.com/rest/1/testtoken/profile.json' => Http::response([
                'result' => ['ID' => '1', 'NAME' => 'Test User'],
            ], 200),

            'https://test.bitrix24.com/rest/1/testtoken/crm.lead.userfield.list.json' => Http::response([
                'result' => [],
            ], 200),

            'https://test.bitrix24.com/rest/1/testtoken/crm.status.list.json' => Http::response([
                'result' => [],
            ], 200),

            'https://test.bitrix24.com/rest/1/testtoken/crm.lead.userfield.add.json' => Http::response([
                'result' => 100,
            ], 200),

            'https://test.bitrix24.com/rest/1/testtoken/crm.status.add.json' => Http::response([
                'result' => 200,
            ], 200),
        ]);

        $this->artisan('bitrix:setup')
            ->expectsOutputToContain('Bitrix24 Setup for Facturino')
            ->expectsOutputToContain('Configuration check passed')
            ->expectsOutputToContain('Testing Bitrix24 API connection')
            ->expectsOutputToContain('Connection successful!')
            ->expectsOutputToContain('Loading existing configuration')
            ->expectsOutputToContain('Creating custom user fields')
            ->expectsOutputToContain('Creating lead statuses')
            ->expectsOutputToContain('Bitrix24 setup completed successfully!')
            ->assertSuccessful();
    }
}

// CLAUDE-CHECKPOINT
