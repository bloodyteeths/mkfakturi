<?php

namespace Tests\Feature\Bitrix;

use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Modules\Mk\Bitrix\Models\HubSpotLeadMap;
use Modules\Mk\Bitrix\Models\OutreachLead;
use Tests\TestCase;

/**
 * HubSpotPollDeals Feature Tests
 *
 * Tests the HubSpot deal polling command functionality including:
 * - Finding deals in "Interested" stage
 * - Partner creation from deal
 * - Idempotency (deal already has partner_id)
 * - Dry-run flag
 *
 * @ticket HUBSPOT-05 - Poll Deals Command
 */
class HubSpotPollDealsTest extends TestCase
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
        Config::set('affiliate.direct_rate', 0.20);

        // Disable email sending
        Mail::fake();
    }

    /**
     * Create the necessary database tables for poll tests.
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

        if (!\Schema::hasTable('hubspot_lead_maps')) {
            \Schema::create('hubspot_lead_maps', function ($table) {
                $table->id();
                $table->string('email')->unique();
                $table->unsignedBigInteger('outreach_lead_id')->nullable();
                $table->string('hubspot_contact_id')->nullable();
                $table->string('hubspot_company_id')->nullable();
                $table->string('hubspot_deal_id')->nullable();
                $table->string('status')->nullable();
                $table->timestamp('last_synced_at')->nullable();
                $table->timestamps();
            });
        }

        // Create partners table if it doesn't exist (normally created by other migrations)
        if (!\Schema::hasTable('partners')) {
            \Schema::create('partners', function ($table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('name');
                $table->string('email');
                $table->string('phone')->nullable();
                $table->string('company_name')->nullable();
                $table->boolean('is_active')->default(false);
                $table->string('kyc_status')->default('pending');
                $table->decimal('commission_rate', 5, 2)->default(0.20);
                $table->timestamps();
            });
        }
    }

    /** @test */
    public function test_finding_deals_in_interested_stage()
    {
        Http::fake([
            // Get pipelines to resolve stage ID
            'https://api.hubapi.com/crm/v3/pipelines/deals' => Http::response([
                'results' => [
                    [
                        'id' => 'default',
                        'label' => 'Sales Pipeline',
                        'stages' => [
                            ['id' => 'new_lead', 'label' => 'New Lead'],
                            ['id' => 'emailed', 'label' => 'Emailed'],
                            ['id' => 'interested', 'label' => 'Interested'],
                            ['id' => 'invite_sent', 'label' => 'Invite Sent'],
                        ],
                    ],
                ],
            ], 200),

            // Search deals in interested stage
            'https://api.hubapi.com/crm/v3/objects/deals/search' => Http::response([
                'total' => 2,
                'results' => [
                    [
                        'id' => '101',
                        'properties' => [
                            'dealname' => 'Partner: Interested Company 1',
                            'dealstage' => 'interested',
                            'facturino_lead_id' => null,
                            'facturino_partner_id' => null,
                        ],
                    ],
                    [
                        'id' => '102',
                        'properties' => [
                            'dealname' => 'Partner: Interested Company 2',
                            'dealstage' => 'interested',
                            'facturino_lead_id' => null,
                            'facturino_partner_id' => null,
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->artisan('hubspot:poll-deals', ['--stage' => 'interested'])
            ->expectsOutputToContain('Polling for deals in stage: Interested')
            ->expectsOutputToContain('Found 2 deals in stage')
            ->assertSuccessful();

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'deals/search') &&
                   $request['filterGroups'][0]['filters'][0]['propertyName'] === 'dealstage';
        });
    }

    /** @test */
    public function test_partner_creation_from_deal()
    {
        // Create local lead
        $lead = OutreachLead::create([
            'email' => 'newpartner@example.com',
            'company_name' => 'New Partner Company',
            'contact_name' => 'John Doe',
            'phone' => '+38970123456',
            'status' => 'emailed',
        ]);

        // Create HubSpot mapping
        HubSpotLeadMap::create([
            'email' => 'newpartner@example.com',
            'outreach_lead_id' => $lead->id,
            'hubspot_contact_id' => '501',
            'hubspot_deal_id' => '101',
            'status' => 'emailed',
        ]);

        Http::fake([
            // Get pipelines
            'https://api.hubapi.com/crm/v3/pipelines/deals' => Http::response([
                'results' => [
                    [
                        'id' => 'default',
                        'label' => 'Sales Pipeline',
                        'stages' => [
                            ['id' => 'interested', 'label' => 'Interested'],
                            ['id' => 'invite_sent', 'label' => 'Invite Sent'],
                        ],
                    ],
                ],
            ], 200),

            // Search deals
            'https://api.hubapi.com/crm/v3/objects/deals/search' => Http::response([
                'results' => [
                    [
                        'id' => '101',
                        'properties' => [
                            'dealname' => 'Partner: New Partner Company',
                            'dealstage' => 'interested',
                            'facturino_lead_id' => (string) $lead->id,
                            'facturino_partner_id' => null,
                        ],
                    ],
                ],
            ], 200),

            // Update deal with partner ID
            'https://api.hubapi.com/crm/v3/objects/deals/101' => Http::response([
                'id' => '101',
                'properties' => [
                    'facturino_partner_id' => '1',
                    'dealstage' => 'invite_sent',
                ],
            ], 200),

            // Create note
            'https://api.hubapi.com/crm/v3/objects/notes' => Http::response([
                'id' => '301',
            ], 201),

            // Associate note
            'https://api.hubapi.com/crm/v3/objects/notes/*/associations/*' => Http::response([], 200),
        ]);

        $this->artisan('hubspot:poll-deals', ['--stage' => 'interested'])
            ->expectsOutputToContain('Partner created')
            ->expectsOutputToContain('Partners created: 1')
            ->assertSuccessful();

        // Verify partner was created
        $this->assertDatabaseHas('partners', [
            'email' => 'newpartner@example.com',
            'company_name' => 'New Partner Company',
        ]);

        // Verify user was created
        $this->assertDatabaseHas('users', [
            'email' => 'newpartner@example.com',
        ]);

        // Verify lead was updated
        $lead->refresh();
        $this->assertNotNull($lead->partner_id);
    }

    /** @test */
    public function test_idempotency_deal_already_has_partner_id()
    {
        Http::fake([
            // Get pipelines
            'https://api.hubapi.com/crm/v3/pipelines/deals' => Http::response([
                'results' => [
                    [
                        'id' => 'default',
                        'label' => 'Sales Pipeline',
                        'stages' => [
                            ['id' => 'interested', 'label' => 'Interested'],
                        ],
                    ],
                ],
            ], 200),

            // Search deals - deal already has partner_id
            'https://api.hubapi.com/crm/v3/objects/deals/search' => Http::response([
                'results' => [
                    [
                        'id' => '101',
                        'properties' => [
                            'dealname' => 'Partner: Already Has Partner',
                            'dealstage' => 'interested',
                            'facturino_lead_id' => '123',
                            'facturino_partner_id' => '999',  // Already has partner
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->artisan('hubspot:poll-deals', ['--stage' => 'interested'])
            ->expectsOutputToContain('Already has partner ID: 999')
            ->expectsOutputToContain('Skipped (has partner): 1')
            ->assertSuccessful();

        // No partner should be created
        $this->assertEquals(0, Partner::count());
    }

    /** @test */
    public function test_dry_run_flag()
    {
        // Create local lead
        $lead = OutreachLead::create([
            'email' => 'dryrun@example.com',
            'company_name' => 'Dry Run Company',
            'status' => 'emailed',
        ]);

        Http::fake([
            // Get pipelines
            'https://api.hubapi.com/crm/v3/pipelines/deals' => Http::response([
                'results' => [
                    [
                        'id' => 'default',
                        'label' => 'Sales Pipeline',
                        'stages' => [
                            ['id' => 'interested', 'label' => 'Interested'],
                        ],
                    ],
                ],
            ], 200),

            // Search deals
            'https://api.hubapi.com/crm/v3/objects/deals/search' => Http::response([
                'results' => [
                    [
                        'id' => '101',
                        'properties' => [
                            'dealname' => 'Partner: Dry Run Company',
                            'dealstage' => 'interested',
                            'facturino_lead_id' => (string) $lead->id,
                            'facturino_partner_id' => null,
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->artisan('hubspot:poll-deals', ['--stage' => 'interested', '--dry-run' => true])
            ->expectsOutputToContain('DRY RUN MODE')
            ->expectsOutputToContain('[DRY-RUN] Would create partner')
            ->assertSuccessful();

        // No partner should be created in dry run
        $this->assertEquals(0, Partner::count());
    }

    /** @test */
    public function test_skips_deals_without_facturino_lead_id()
    {
        Http::fake([
            // Get pipelines
            'https://api.hubapi.com/crm/v3/pipelines/deals' => Http::response([
                'results' => [
                    [
                        'id' => 'default',
                        'label' => 'Sales Pipeline',
                        'stages' => [
                            ['id' => 'interested', 'label' => 'Interested'],
                        ],
                    ],
                ],
            ], 200),

            // Search deals - no facturino_lead_id
            'https://api.hubapi.com/crm/v3/objects/deals/search' => Http::response([
                'results' => [
                    [
                        'id' => '101',
                        'properties' => [
                            'dealname' => 'Partner: No Lead ID',
                            'dealstage' => 'interested',
                            'facturino_lead_id' => null,
                            'facturino_partner_id' => null,
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->artisan('hubspot:poll-deals', ['--stage' => 'interested'])
            ->expectsOutputToContain('No facturino_lead_id')
            ->expectsOutputToContain('Skipped (no lead ID): 1')
            ->assertSuccessful();

        // No partner should be created
        $this->assertEquals(0, Partner::count());
    }

    /** @test */
    public function test_uses_existing_partner_if_email_already_exists()
    {
        // Create existing partner
        $user = User::create([
            'name' => 'Existing Partner',
            'email' => 'existing@example.com',
            'password' => bcrypt('password'),
        ]);

        $partner = Partner::create([
            'user_id' => $user->id,
            'name' => 'Existing Partner',
            'email' => 'existing@example.com',
            'is_active' => true,
        ]);

        // Create local lead with same email
        $lead = OutreachLead::create([
            'email' => 'existing@example.com',
            'company_name' => 'Existing Company',
            'status' => 'emailed',
        ]);

        Http::fake([
            // Get pipelines
            'https://api.hubapi.com/crm/v3/pipelines/deals' => Http::response([
                'results' => [
                    [
                        'id' => 'default',
                        'label' => 'Sales Pipeline',
                        'stages' => [
                            ['id' => 'interested', 'label' => 'Interested'],
                        ],
                    ],
                ],
            ], 200),

            // Search deals
            'https://api.hubapi.com/crm/v3/objects/deals/search' => Http::response([
                'results' => [
                    [
                        'id' => '101',
                        'properties' => [
                            'dealname' => 'Partner: Existing Company',
                            'dealstage' => 'interested',
                            'facturino_lead_id' => (string) $lead->id,
                            'facturino_partner_id' => null,
                        ],
                    ],
                ],
            ], 200),

            // Update deal with existing partner ID
            'https://api.hubapi.com/crm/v3/objects/deals/101' => Http::response([
                'id' => '101',
                'properties' => [
                    'facturino_partner_id' => (string) $partner->id,
                ],
            ], 200),
        ]);

        $this->artisan('hubspot:poll-deals', ['--stage' => 'interested'])
            ->expectsOutputToContain('Partner already exists')
            ->assertSuccessful();

        // Should not create new partner
        $this->assertEquals(1, Partner::count());

        // Deal should be updated with existing partner ID
        Http::assertSent(function ($request) use ($partner) {
            return str_contains($request->url(), 'deals/101') &&
                   $request->method() === 'PATCH' &&
                   ($request['properties']['facturino_partner_id'] ?? null) === (string) $partner->id;
        });
    }

    /** @test */
    public function test_handles_no_deals_found()
    {
        Http::fake([
            // Get pipelines
            'https://api.hubapi.com/crm/v3/pipelines/deals' => Http::response([
                'results' => [
                    [
                        'id' => 'default',
                        'label' => 'Sales Pipeline',
                        'stages' => [
                            ['id' => 'interested', 'label' => 'Interested'],
                        ],
                    ],
                ],
            ], 200),

            // Search deals - empty
            'https://api.hubapi.com/crm/v3/objects/deals/search' => Http::response([
                'results' => [],
            ], 200),
        ]);

        $this->artisan('hubspot:poll-deals', ['--stage' => 'interested'])
            ->expectsOutputToContain('No deals found in this stage')
            ->assertSuccessful();
    }

    /** @test */
    public function test_fails_when_hubspot_not_configured()
    {
        Config::set('hubspot.access_token', '');

        $this->artisan('hubspot:poll-deals', ['--stage' => 'interested'])
            ->expectsOutputToContain('HubSpot is not configured')
            ->assertFailed();
    }

    /** @test */
    public function test_fails_when_stage_not_found()
    {
        Http::fake([
            // Get pipelines - stage doesn't exist
            'https://api.hubapi.com/crm/v3/pipelines/deals' => Http::response([
                'results' => [
                    [
                        'id' => 'default',
                        'label' => 'Sales Pipeline',
                        'stages' => [
                            ['id' => 'new_lead', 'label' => 'New Lead'],
                            ['id' => 'emailed', 'label' => 'Emailed'],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->artisan('hubspot:poll-deals', ['--stage' => 'nonexistent'])
            ->expectsOutputToContain('Could not find stage')
            ->assertFailed();
    }

    /** @test */
    public function test_displays_results_summary()
    {
        Http::fake([
            // Get pipelines
            'https://api.hubapi.com/crm/v3/pipelines/deals' => Http::response([
                'results' => [
                    [
                        'id' => 'default',
                        'label' => 'Sales Pipeline',
                        'stages' => [
                            ['id' => 'interested', 'label' => 'Interested'],
                        ],
                    ],
                ],
            ], 200),

            // Search deals - empty
            'https://api.hubapi.com/crm/v3/objects/deals/search' => Http::response([
                'results' => [],
            ], 200),
        ]);

        $this->artisan('hubspot:poll-deals', ['--stage' => 'interested'])
            ->expectsOutputToContain('Polling completed')
            ->assertSuccessful();
    }
}

// CLAUDE-CHECKPOINT
