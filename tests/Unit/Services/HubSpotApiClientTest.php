<?php

namespace Tests\Unit\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Modules\Mk\Bitrix\Services\HubSpotApiClient;
use Tests\TestCase;

/**
 * HubSpotApiClient Unit Tests
 *
 * Tests the HubSpot API client functionality including:
 * - Contact creation and update
 * - Contact search by email
 * - Contact upsert (create or update)
 * - Deal creation and stage retrieval
 * - Email engagement logging
 * - Note creation
 * - Task creation
 * - HTTP response mocking
 *
 * @ticket HUBSPOT-01 - HubSpot CRM Integration
 */
class HubSpotApiClientTest extends TestCase
{
    protected HubSpotApiClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        // Configure HubSpot access token and settings for tests
        Config::set('hubspot.access_token', 'test_access_token_12345');
        Config::set('hubspot.pipeline_id', 'default');
        Config::set('hubspot.deal_stages', [
            'new' => 'appointmentscheduled',
            'emailed' => 'qualifiedtobuy',
            'interested' => 'presentationscheduled',
            'lost' => 'closedlost',
        ]);

        $this->client = new HubSpotApiClient();
    }

    /** @test */
    public function test_create_contact_returns_id()
    {
        Http::fake([
            'https://api.hubapi.com/crm/v3/objects/contacts' => Http::response([
                'id' => '501',
                'properties' => [
                    'email' => 'john@example.com',
                    'firstname' => 'John',
                    'lastname' => 'Doe',
                ],
            ], 201),
        ]);

        $contactId = $this->client->createContact([
            'email' => 'john@example.com',
            'firstname' => 'John',
            'lastname' => 'Doe',
        ]);

        $this->assertEquals('501', $contactId);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.hubapi.com/crm/v3/objects/contacts' &&
                   $request['properties']['email'] === 'john@example.com' &&
                   $request['properties']['firstname'] === 'John';
        });
    }

    /** @test */
    public function test_update_contact_succeeds()
    {
        Http::fake([
            'https://api.hubapi.com/crm/v3/objects/contacts/501' => Http::response([
                'id' => '501',
                'properties' => [
                    'email' => 'john@example.com',
                    'phone' => '+38970123456',
                ],
            ], 200),
        ]);

        $result = $this->client->updateContact('501', [
            'phone' => '+38970123456',
        ]);

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/crm/v3/objects/contacts/501') &&
                   $request->method() === 'PATCH' &&
                   $request['properties']['phone'] === '+38970123456';
        });
    }

    /** @test */
    public function test_find_contact_by_email()
    {
        Http::fake([
            'https://api.hubapi.com/crm/v3/objects/contacts/search' => Http::response([
                'total' => 1,
                'results' => [
                    [
                        'id' => '501',
                        'properties' => [
                            'email' => 'john@example.com',
                            'firstname' => 'John',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $contact = $this->client->findContactByEmail('john@example.com');

        $this->assertNotNull($contact);
        $this->assertEquals('501', $contact['id']);
        $this->assertEquals('john@example.com', $contact['properties']['email']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/crm/v3/objects/contacts/search') &&
                   $request['filterGroups'][0]['filters'][0]['propertyName'] === 'email' &&
                   $request['filterGroups'][0]['filters'][0]['value'] === 'john@example.com';
        });
    }

    /** @test */
    public function test_find_contact_by_email_returns_null_when_not_found()
    {
        Http::fake([
            'https://api.hubapi.com/crm/v3/objects/contacts/search' => Http::response([
                'total' => 0,
                'results' => [],
            ], 200),
        ]);

        $contact = $this->client->findContactByEmail('notfound@example.com');

        $this->assertNull($contact);
    }

    /** @test */
    public function test_upsert_contact_creates_when_not_exists()
    {
        Http::fake([
            // First search returns empty - contact doesn't exist
            'https://api.hubapi.com/crm/v3/objects/contacts/search' => Http::response([
                'total' => 0,
                'results' => [],
            ], 200),
            // Then create new contact
            'https://api.hubapi.com/crm/v3/objects/contacts' => Http::response([
                'id' => '502',
                'properties' => [
                    'email' => 'new@example.com',
                    'firstname' => 'New',
                ],
            ], 201),
        ]);

        $contact = $this->client->upsertContact('new@example.com', [
            'firstname' => 'New',
        ]);

        $this->assertNotNull($contact);
        $this->assertEquals('502', $contact['id']);

        // Should have called search then create
        Http::assertSentCount(2);
    }

    /** @test */
    public function test_upsert_contact_updates_when_exists()
    {
        Http::fake([
            // Search returns existing contact
            'https://api.hubapi.com/crm/v3/objects/contacts/search' => Http::response([
                'total' => 1,
                'results' => [
                    [
                        'id' => '501',
                        'properties' => ['email' => 'existing@example.com'],
                    ],
                ],
            ], 200),
            // Then update existing contact
            'https://api.hubapi.com/crm/v3/objects/contacts/501' => Http::response([
                'id' => '501',
                'properties' => [
                    'email' => 'existing@example.com',
                    'firstname' => 'Updated',
                ],
            ], 200),
        ]);

        $contact = $this->client->upsertContact('existing@example.com', [
            'firstname' => 'Updated',
        ]);

        $this->assertNotNull($contact);
        $this->assertEquals('501', $contact['id']);

        // Should have called search then update
        Http::assertSentCount(2);
    }

    /** @test */
    public function test_create_deal_returns_id()
    {
        Http::fake([
            'https://api.hubapi.com/crm/v3/objects/deals' => Http::response([
                'id' => '101',
                'properties' => [
                    'dealname' => 'Partner: Test Company',
                    'dealstage' => 'appointmentscheduled',
                    'pipeline' => 'default',
                ],
            ], 201),
        ]);

        $dealId = $this->client->createDeal([
            'dealname' => 'Partner: Test Company',
            'dealstage' => 'appointmentscheduled',
        ]);

        $this->assertEquals('101', $dealId);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/crm/v3/objects/deals') &&
                   $request['properties']['dealname'] === 'Partner: Test Company';
        });
    }

    /** @test */
    public function test_get_deals_by_stage()
    {
        Http::fake([
            'https://api.hubapi.com/crm/v3/objects/deals/search' => Http::response([
                'total' => 2,
                'results' => [
                    [
                        'id' => '101',
                        'properties' => [
                            'dealname' => 'Deal 1',
                            'dealstage' => 'presentationscheduled',
                        ],
                    ],
                    [
                        'id' => '102',
                        'properties' => [
                            'dealname' => 'Deal 2',
                            'dealstage' => 'presentationscheduled',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $deals = $this->client->getDealsByStage('presentationscheduled');

        $this->assertCount(2, $deals);
        $this->assertEquals('101', $deals[0]['id']);
        $this->assertEquals('102', $deals[1]['id']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/crm/v3/objects/deals/search') &&
                   $request['filterGroups'][0]['filters'][0]['propertyName'] === 'dealstage' &&
                   $request['filterGroups'][0]['filters'][0]['value'] === 'presentationscheduled';
        });
    }

    /** @test */
    public function test_log_email_engagement()
    {
        Http::fake([
            'https://api.hubapi.com/crm/v3/objects/emails' => Http::response([
                'id' => '301',
                'properties' => [
                    'hs_email_subject' => 'Welcome to Facturino',
                    'hs_email_status' => 'SENT',
                ],
            ], 201),
        ]);

        $emailId = $this->client->logEmail('501', [
            'subject' => 'Welcome to Facturino',
            'body' => 'Thank you for your interest in Facturino.',
            'status' => 'SENT',
        ]);

        $this->assertEquals('301', $emailId);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/crm/v3/objects/emails') &&
                   $request['properties']['hs_email_subject'] === 'Welcome to Facturino' &&
                   $request['associations'][0]['to']['id'] === '501';
        });
    }

    /** @test */
    public function test_log_note()
    {
        Http::fake([
            // Create note
            'https://api.hubapi.com/crm/v3/objects/notes' => Http::response([
                'id' => '401',
                'properties' => [
                    'hs_note_body' => 'Lead opened email',
                ],
            ], 201),
            // Associate note with contact
            'https://api.hubapi.com/crm/v3/objects/notes/401/associations/contacts/501/note_to_contact' => Http::response([], 200),
        ]);

        $noteId = $this->client->createNote('501', 'Lead opened email');

        $this->assertEquals('401', $noteId);

        Http::assertSent(function ($request) {
            if (str_contains($request->url(), '/crm/v3/objects/notes') && !str_contains($request->url(), 'associations')) {
                return $request['properties']['hs_note_body'] === 'Lead opened email';
            }
            return true;
        });
    }

    /** @test */
    public function test_create_task()
    {
        Http::fake([
            // Create task
            'https://api.hubapi.com/crm/v3/objects/tasks' => Http::response([
                'id' => '601',
                'properties' => [
                    'hs_task_subject' => 'Follow up with lead',
                    'hs_task_status' => 'NOT_STARTED',
                ],
            ], 201),
            // Associate task with contact
            'https://api.hubapi.com/crm/v3/objects/tasks/601/associations/contacts/501/task_to_contact' => Http::response([], 200),
        ]);

        $taskId = $this->client->createTask(
            '501',
            'Follow up with lead',
            'Lead clicked email link, follow up immediately'
        );

        $this->assertEquals('601', $taskId);

        Http::assertSent(function ($request) {
            if (str_contains($request->url(), '/crm/v3/objects/tasks') && !str_contains($request->url(), 'associations')) {
                return $request['properties']['hs_task_subject'] === 'Follow up with lead' &&
                       $request['properties']['hs_task_body'] === 'Lead clicked email link, follow up immediately';
            }
            return true;
        });
    }

    /** @test */
    public function test_get_stage_id_returns_configured_value()
    {
        $stageId = $this->client->getStageId('interested');

        $this->assertEquals('presentationscheduled', $stageId);
    }

    /** @test */
    public function test_get_stage_id_returns_null_for_unknown_stage()
    {
        $stageId = $this->client->getStageId('unknown');

        $this->assertNull($stageId);
    }

    /** @test */
    public function test_update_deal_stage_succeeds()
    {
        Http::fake([
            'https://api.hubapi.com/crm/v3/objects/deals/101' => Http::response([
                'id' => '101',
                'properties' => [
                    'dealstage' => 'closedlost',
                ],
            ], 200),
        ]);

        $result = $this->client->updateDealStage('101', 'closedlost');

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/crm/v3/objects/deals/101') &&
                   $request->method() === 'PATCH' &&
                   $request['properties']['dealstage'] === 'closedlost';
        });
    }

    /** @test */
    public function test_get_contact_returns_contact_data()
    {
        Http::fake([
            'https://api.hubapi.com/crm/v3/objects/contacts/501*' => Http::response([
                'id' => '501',
                'properties' => [
                    'email' => 'john@example.com',
                    'firstname' => 'John',
                    'lastname' => 'Doe',
                ],
            ], 200),
        ]);

        $contact = $this->client->getContact('501');

        $this->assertNotNull($contact);
        $this->assertEquals('501', $contact['id']);
        $this->assertEquals('john@example.com', $contact['properties']['email']);
    }

    /** @test */
    public function test_create_property_succeeds()
    {
        Http::fake([
            'https://api.hubapi.com/crm/v3/properties/contacts' => Http::response([
                'name' => 'facturino_lead_id',
                'label' => 'Facturino Lead ID',
                'type' => 'string',
            ], 201),
        ]);

        $result = $this->client->createProperty('contacts', [
            'name' => 'facturino_lead_id',
            'label' => 'Facturino Lead ID',
            'type' => 'string',
            'fieldType' => 'text',
            'groupName' => 'contactinformation',
        ]);

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/crm/v3/properties/contacts') &&
                   $request['name'] === 'facturino_lead_id';
        });
    }

    /** @test */
    public function test_property_exists_returns_true_when_found()
    {
        Http::fake([
            'https://api.hubapi.com/crm/v3/properties/contacts' => Http::response([
                'results' => [
                    ['name' => 'facturino_lead_id', 'label' => 'Facturino Lead ID'],
                    ['name' => 'facturino_source', 'label' => 'Facturino Source'],
                ],
            ], 200),
        ]);

        $exists = $this->client->propertyExists('contacts', 'facturino_lead_id');

        $this->assertTrue($exists);
    }

    /** @test */
    public function test_property_exists_returns_false_when_not_found()
    {
        Http::fake([
            'https://api.hubapi.com/crm/v3/properties/contacts' => Http::response([
                'results' => [
                    ['name' => 'other_property', 'label' => 'Other Property'],
                ],
            ], 200),
        ]);

        $exists = $this->client->propertyExists('contacts', 'facturino_lead_id');

        $this->assertFalse($exists);
    }

    /** @test */
    public function test_get_pipelines_returns_array()
    {
        Http::fake([
            'https://api.hubapi.com/crm/v3/pipelines/deals' => Http::response([
                'results' => [
                    [
                        'id' => 'default',
                        'label' => 'Sales Pipeline',
                        'stages' => [
                            ['id' => 'appointmentscheduled', 'label' => 'Appointment Scheduled'],
                            ['id' => 'qualifiedtobuy', 'label' => 'Qualified to Buy'],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $pipelines = $this->client->getPipelines();

        $this->assertCount(1, $pipelines);
        $this->assertEquals('default', $pipelines[0]['id']);
        $this->assertEquals('Sales Pipeline', $pipelines[0]['label']);
    }

    /** @test */
    public function test_api_error_throws_exception()
    {
        Http::fake([
            'https://api.hubapi.com/crm/v3/objects/contacts' => Http::response([
                'status' => 'error',
                'message' => 'Invalid input JSON',
                'category' => 'VALIDATION_ERROR',
            ], 400),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('HubSpot API error');

        $this->client->request('POST', '/crm/v3/objects/contacts', ['properties' => []]);
    }

    /** @test */
    public function test_extract_domain_from_email_returns_domain()
    {
        $domain = $this->client->extractDomainFromEmail('john@company.mk');

        $this->assertEquals('company.mk', $domain);
    }

    /** @test */
    public function test_extract_domain_from_email_returns_null_for_personal_domains()
    {
        $domain = $this->client->extractDomainFromEmail('john@gmail.com');

        $this->assertNull($domain);
    }

    /** @test */
    public function test_associate_deal_to_contact_succeeds()
    {
        Http::fake([
            'https://api.hubapi.com/crm/v3/objects/deals/101/associations/contacts/501/deal_to_contact' => Http::response([], 200),
        ]);

        $result = $this->client->associateDealToContact('101', '501');

        $this->assertTrue($result);
    }

    /** @test */
    public function test_get_deals_in_stage_without_partner()
    {
        Http::fake([
            'https://api.hubapi.com/crm/v3/objects/deals/search' => Http::response([
                'results' => [
                    [
                        'id' => '101',
                        'properties' => [
                            'dealname' => 'Interested Lead',
                            'facturino_lead_id' => '12345',
                            'facturino_partner_id' => null,
                        ],
                    ],
                ],
            ], 200),
        ]);

        $deals = $this->client->getDealsInStageWithoutPartner('interested');

        $this->assertCount(1, $deals);
        $this->assertEquals('101', $deals[0]['id']);

        Http::assertSent(function ($request) {
            $filters = $request['filterGroups'][0]['filters'] ?? [];
            $hasStageFilter = false;
            $hasNoPartnerFilter = false;

            foreach ($filters as $filter) {
                if ($filter['propertyName'] === 'dealstage') {
                    $hasStageFilter = true;
                }
                if ($filter['propertyName'] === 'facturino_partner_id' && $filter['operator'] === 'NOT_HAS_PROPERTY') {
                    $hasNoPartnerFilter = true;
                }
            }

            return $hasStageFilter && $hasNoPartnerFilter;
        });
    }
}

// CLAUDE-CHECKPOINT
