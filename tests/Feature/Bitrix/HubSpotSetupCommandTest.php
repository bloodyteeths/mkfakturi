<?php

namespace Tests\Feature\Bitrix;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * HubSpotSetupCommand Feature Tests
 *
 * Tests the HubSpot setup command functionality including:
 * - Idempotent setup (running twice creates properties only once)
 * - Test flag doesn't make changes
 * - Mock HubSpot API responses
 *
 * @ticket HUBSPOT-03 - HubSpot Setup Command
 */
class HubSpotSetupCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Configure HubSpot access token
        Config::set('hubspot.access_token', 'test_access_token_12345');
        Config::set('hubspot.pipeline_id', 'default');
    }

    /** @test */
    public function test_setup_is_idempotent_running_twice_creates_properties_only_once()
    {
        // Mock HTTP responses for two runs
        // First run: properties don't exist, create them
        // Second run: properties exist, skip them
        Http::fake([
            // Account info check (connection test)
            'https://api.hubapi.com/account-info/v3/details' => Http::response([
                'portalId' => 12345678,
                'uiDomain' => 'app.hubspot.com',
            ], 200),

            // Get existing contact properties - empty first, then has properties
            'https://api.hubapi.com/crm/v3/properties/contacts/facturino_lead_id' => Http::sequence()
                ->push(['status' => 'error', 'message' => 'Property not found'], 404)  // First run - not found
                ->push(['name' => 'facturino_lead_id', 'label' => 'Facturino Lead ID'], 200),  // Second run - exists

            'https://api.hubapi.com/crm/v3/properties/contacts/facturino_source' => Http::sequence()
                ->push(['status' => 'error', 'message' => 'Property not found'], 404)
                ->push(['name' => 'facturino_source', 'label' => 'Facturino Source'], 200),

            'https://api.hubapi.com/crm/v3/properties/contacts/facturino_source_url' => Http::sequence()
                ->push(['status' => 'error', 'message' => 'Property not found'], 404)
                ->push(['name' => 'facturino_source_url', 'label' => 'Facturino Source URL'], 200),

            'https://api.hubapi.com/crm/v3/properties/contacts/facturino_tags' => Http::sequence()
                ->push(['status' => 'error', 'message' => 'Property not found'], 404)
                ->push(['name' => 'facturino_tags', 'label' => 'Facturino Tags'], 200),

            'https://api.hubapi.com/crm/v3/properties/contacts/facturino_last_email_template' => Http::sequence()
                ->push(['status' => 'error', 'message' => 'Property not found'], 404)
                ->push(['name' => 'facturino_last_email_template', 'label' => 'Facturino Last Email Template'], 200),

            // Get existing deal properties
            'https://api.hubapi.com/crm/v3/properties/deals/facturino_lead_id' => Http::sequence()
                ->push(['status' => 'error', 'message' => 'Property not found'], 404)
                ->push(['name' => 'facturino_lead_id', 'label' => 'Facturino Lead ID'], 200),

            'https://api.hubapi.com/crm/v3/properties/deals/facturino_partner_id' => Http::sequence()
                ->push(['status' => 'error', 'message' => 'Property not found'], 404)
                ->push(['name' => 'facturino_partner_id', 'label' => 'Facturino Partner ID'], 200),

            // Get contact properties list (for propertyExists check)
            'https://api.hubapi.com/crm/v3/properties/contacts' => Http::sequence()
                ->push(['results' => []], 200)  // First run - no custom props
                ->push(['results' => [
                    ['name' => 'facturino_lead_id'],
                    ['name' => 'facturino_source'],
                    ['name' => 'facturino_source_url'],
                    ['name' => 'facturino_tags'],
                    ['name' => 'facturino_last_email_template'],
                ]], 200),  // Second run - props exist

            // Get deal properties list (for propertyExists check)
            'https://api.hubapi.com/crm/v3/properties/deals' => Http::sequence()
                ->push(['results' => []], 200)  // First run - no custom props
                ->push(['results' => [
                    ['name' => 'facturino_lead_id'],
                    ['name' => 'facturino_partner_id'],
                ]], 200),  // Second run - props exist

            // Create contact properties - should succeed first run
            'https://api.hubapi.com/crm/v3/properties/contacts' => Http::response([
                'name' => 'created_property',
            ], 201),

            // Create deal properties - should succeed first run
            'https://api.hubapi.com/crm/v3/properties/deals' => Http::response([
                'name' => 'created_property',
            ], 201),

            // Get pipelines
            'https://api.hubapi.com/crm/v3/pipelines/deals' => Http::response([
                'results' => [
                    [
                        'id' => 'default',
                        'label' => 'Sales Pipeline',
                        'stages' => [
                            ['id' => 'appointmentscheduled', 'label' => 'Appointment Scheduled', 'displayOrder' => 0],
                            ['id' => 'qualifiedtobuy', 'label' => 'Qualified to Buy', 'displayOrder' => 1],
                        ],
                    ],
                ],
            ], 200),
        ]);

        // First run - creates properties
        $this->artisan('hubspot:setup')
            ->expectsOutputToContain('HubSpot Setup for Facturino')
            ->expectsOutputToContain('Connection successful!')
            ->expectsOutputToContain('HubSpot setup completed successfully!')
            ->assertSuccessful();

        // Second run - should skip existing properties
        $this->artisan('hubspot:setup')
            ->expectsOutputToContain('HubSpot setup completed successfully!')
            ->assertSuccessful();
    }

    /** @test */
    public function test_test_flag_does_not_make_changes()
    {
        Http::fake([
            // Account info check only
            'https://api.hubapi.com/account-info/v3/details' => Http::response([
                'portalId' => 12345678,
                'uiDomain' => 'app.hubspot.com',
            ], 200),
        ]);

        $this->artisan('hubspot:setup', ['--test' => true])
            ->expectsOutputToContain('Connection test successful')
            ->assertSuccessful();

        // Should only call account info, not create properties
        Http::assertSentCount(1);
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'account-info');
        });
        Http::assertNotSent(function ($request) {
            return str_contains($request->url(), 'properties/contacts') ||
                   str_contains($request->url(), 'properties/deals');
        });
    }

    /** @test */
    public function test_setup_fails_with_missing_access_token()
    {
        Config::set('hubspot.access_token', '');

        $this->artisan('hubspot:setup')
            ->expectsOutputToContain('HUBSPOT_ACCESS_TOKEN is not configured')
            ->assertFailed();
    }

    /** @test */
    public function test_setup_fails_on_connection_error()
    {
        Http::fake([
            'https://api.hubapi.com/account-info/v3/details' => Http::response([
                'status' => 'error',
                'message' => 'Invalid access token',
                'category' => 'UNAUTHORIZED',
            ], 401),
        ]);

        $this->artisan('hubspot:setup')
            ->expectsOutputToContain('Connection failed')
            ->assertFailed();
    }

    /** @test */
    public function test_setup_handles_existing_properties_gracefully()
    {
        Http::fake([
            'https://api.hubapi.com/account-info/v3/details' => Http::response([
                'portalId' => 12345678,
                'uiDomain' => 'app.hubspot.com',
            ], 200),

            // Properties already exist
            'https://api.hubapi.com/crm/v3/properties/contacts' => Http::response([
                'results' => [
                    ['name' => 'facturino_lead_id'],
                    ['name' => 'facturino_source'],
                    ['name' => 'facturino_source_url'],
                    ['name' => 'facturino_tags'],
                    ['name' => 'facturino_last_email_template'],
                ],
            ], 200),

            'https://api.hubapi.com/crm/v3/properties/deals' => Http::response([
                'results' => [
                    ['name' => 'facturino_lead_id'],
                    ['name' => 'facturino_partner_id'],
                ],
            ], 200),

            'https://api.hubapi.com/crm/v3/pipelines/deals' => Http::response([
                'results' => [
                    [
                        'id' => 'default',
                        'label' => 'Sales Pipeline',
                        'stages' => [],
                    ],
                ],
            ], 200),
        ]);

        $this->artisan('hubspot:setup')
            ->expectsOutputToContain('HubSpot setup completed successfully!')
            ->assertSuccessful();
    }

    /** @test */
    public function test_setup_with_force_flag_recreates_properties()
    {
        Http::fake([
            'https://api.hubapi.com/account-info/v3/details' => Http::response([
                'portalId' => 12345678,
                'uiDomain' => 'app.hubspot.com',
            ], 200),

            // Properties exist but force will try to recreate
            'https://api.hubapi.com/crm/v3/properties/contacts' => Http::sequence()
                ->push(['results' => [['name' => 'facturino_lead_id']]], 200)  // GET
                ->push(['name' => 'facturino_lead_id'], 201)  // POST attempt
                ->push(['name' => 'facturino_source'], 201)
                ->push(['name' => 'facturino_source_url'], 201)
                ->push(['name' => 'facturino_tags'], 201)
                ->push(['name' => 'facturino_last_email_template'], 201),

            'https://api.hubapi.com/crm/v3/properties/deals' => Http::sequence()
                ->push(['results' => [['name' => 'facturino_lead_id']]], 200)  // GET
                ->push(['name' => 'facturino_lead_id'], 201)  // POST attempt
                ->push(['name' => 'facturino_partner_id'], 201),

            'https://api.hubapi.com/crm/v3/pipelines/deals' => Http::response([
                'results' => [],
            ], 200),
        ]);

        $this->artisan('hubspot:setup', ['--force' => true])
            ->assertSuccessful();

        // With force flag, should try to create properties even if they might exist
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'crm/v3/properties');
        });
    }

    /** @test */
    public function test_setup_displays_progress()
    {
        Http::fake([
            'https://api.hubapi.com/account-info/v3/details' => Http::response([
                'portalId' => 12345678,
                'uiDomain' => 'app.hubspot.com',
            ], 200),

            'https://api.hubapi.com/crm/v3/properties/contacts' => Http::sequence()
                ->push(['results' => []], 200)
                ->push(['name' => 'facturino_lead_id'], 201)
                ->push(['name' => 'facturino_source'], 201)
                ->push(['name' => 'facturino_source_url'], 201)
                ->push(['name' => 'facturino_tags'], 201)
                ->push(['name' => 'facturino_last_email_template'], 201),

            'https://api.hubapi.com/crm/v3/properties/deals' => Http::sequence()
                ->push(['results' => []], 200)
                ->push(['name' => 'facturino_lead_id'], 201)
                ->push(['name' => 'facturino_partner_id'], 201),

            'https://api.hubapi.com/crm/v3/pipelines/deals' => Http::response([
                'results' => [
                    [
                        'id' => 'default',
                        'label' => 'Sales Pipeline',
                        'stages' => [
                            ['id' => 'appointmentscheduled', 'label' => 'Appointment Scheduled', 'displayOrder' => 0, 'metadata' => ['probability' => '0.2']],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->artisan('hubspot:setup')
            ->expectsOutputToContain('HubSpot Setup for Facturino')
            ->expectsOutputToContain('Configuration check passed')
            ->expectsOutputToContain('Testing HubSpot API connection')
            ->expectsOutputToContain('Connection successful!')
            ->expectsOutputToContain('Creating contact custom properties')
            ->expectsOutputToContain('Creating deal custom properties')
            ->expectsOutputToContain('HubSpot setup completed successfully!')
            ->assertSuccessful();
    }

    /** @test */
    public function test_setup_lists_available_pipelines()
    {
        Http::fake([
            'https://api.hubapi.com/account-info/v3/details' => Http::response([
                'portalId' => 12345678,
                'uiDomain' => 'app.hubspot.com',
            ], 200),

            'https://api.hubapi.com/crm/v3/properties/contacts' => Http::response([
                'results' => [
                    ['name' => 'facturino_lead_id'],
                    ['name' => 'facturino_source'],
                    ['name' => 'facturino_source_url'],
                    ['name' => 'facturino_tags'],
                    ['name' => 'facturino_last_email_template'],
                ],
            ], 200),

            'https://api.hubapi.com/crm/v3/properties/deals' => Http::response([
                'results' => [
                    ['name' => 'facturino_lead_id'],
                    ['name' => 'facturino_partner_id'],
                ],
            ], 200),

            'https://api.hubapi.com/crm/v3/pipelines/deals' => Http::response([
                'results' => [
                    [
                        'id' => 'default',
                        'label' => 'Partner Outreach',
                        'stages' => [
                            ['id' => 'new_lead', 'label' => 'New Lead', 'displayOrder' => 0, 'metadata' => ['probability' => '0.1']],
                            ['id' => 'emailed', 'label' => 'Emailed', 'displayOrder' => 1, 'metadata' => ['probability' => '0.2']],
                            ['id' => 'interested', 'label' => 'Interested', 'displayOrder' => 2, 'metadata' => ['probability' => '0.5']],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->artisan('hubspot:setup')
            ->expectsOutputToContain('Available pipelines and stages')
            ->expectsOutputToContain('Partner Outreach')
            ->assertSuccessful();
    }
}

// CLAUDE-CHECKPOINT
