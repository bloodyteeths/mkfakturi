<?php

namespace Tests\Unit\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Modules\Mk\Bitrix\Services\Bitrix24ApiClient;
use Tests\TestCase;

/**
 * Bitrix24ApiClient Unit Tests
 *
 * Tests the Bitrix24 API client functionality including:
 * - Connection validation
 * - Lead creation and update
 * - User field creation (idempotency)
 * - HTTP response mocking
 *
 * @ticket BITRIX-01 - Bitrix24 CRM Integration
 */
class Bitrix24ApiClientTest extends TestCase
{
    protected Bitrix24ApiClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        // Configure Bitrix24 webhook URL for tests
        Config::set('bitrix.webhook_base_url', 'https://test.bitrix24.com/rest/1/testtoken');
        Config::set('bitrix.shared_secret', 'test_secret');
        Config::set('bitrix.lead_stages', [
            'NEW' => 'NEW',
            'EMAILED' => 'UC_EMAILED',
        ]);
        Config::set('bitrix.custom_fields', [
            'source' => 'UF_FCT_SOURCE',
            'partner_id' => 'UF_FCT_FACTURINO_PARTNER_ID',
        ]);

        $this->client = new Bitrix24ApiClient();
    }

    /** @test */
    public function test_connection_validation_succeeds_with_valid_response()
    {
        Http::fake([
            'https://test.bitrix24.com/rest/1/testtoken/profile.json' => Http::response([
                'result' => [
                    'ID' => '1',
                    'NAME' => 'Test User',
                    'PERSONAL_CITY' => 'Test City',
                ],
            ], 200),
        ]);

        $response = $this->client->request('profile');

        $this->assertArrayHasKey('result', $response);
        $this->assertEquals('Test User', $response['result']['NAME']);
    }

    /** @test */
    public function test_connection_validation_fails_with_api_error()
    {
        Http::fake([
            'https://test.bitrix24.com/rest/1/testtoken/profile.json' => Http::response([
                'error' => 'INVALID_TOKEN',
                'error_description' => 'Invalid webhook token',
            ], 200),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Bitrix24 API error: INVALID_TOKEN');

        $this->client->request('profile');
    }

    /** @test */
    public function test_create_lead_returns_lead_id()
    {
        Http::fake([
            'https://test.bitrix24.com/rest/1/testtoken/crm.lead.add.json' => Http::response([
                'result' => 12345,
            ], 200),
        ]);

        $leadId = $this->client->createLead([
            'TITLE' => 'Test Company',
            'NAME' => 'John Doe',
            'EMAIL' => [['VALUE' => 'john@example.com', 'VALUE_TYPE' => 'WORK']],
        ]);

        $this->assertEquals('12345', $leadId);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://test.bitrix24.com/rest/1/testtoken/crm.lead.add.json' &&
                   $request['fields']['TITLE'] === 'Test Company';
        });
    }

    /** @test */
    public function test_create_lead_returns_null_on_failure()
    {
        Http::fake([
            'https://test.bitrix24.com/rest/1/testtoken/crm.lead.add.json' => Http::response([
                'error' => 'ACCESS_DENIED',
                'error_description' => 'Access denied',
            ], 200),
        ]);

        $leadId = $this->client->createLead([
            'TITLE' => 'Test Company',
        ]);

        $this->assertNull($leadId);
    }

    /** @test */
    public function test_update_lead_succeeds()
    {
        Http::fake([
            'https://test.bitrix24.com/rest/1/testtoken/crm.lead.update.json' => Http::response([
                'result' => true,
            ], 200),
        ]);

        $result = $this->client->updateLead('12345', [
            'STATUS_ID' => 'UC_EMAILED',
        ]);

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://test.bitrix24.com/rest/1/testtoken/crm.lead.update.json' &&
                   $request['id'] === '12345' &&
                   $request['fields']['STATUS_ID'] === 'UC_EMAILED';
        });
    }

    /** @test */
    public function test_update_lead_returns_false_on_failure()
    {
        Http::fake([
            'https://test.bitrix24.com/rest/1/testtoken/crm.lead.update.json' => Http::response([
                'error' => 'NOT_FOUND',
                'error_description' => 'Lead not found',
            ], 200),
        ]);

        $result = $this->client->updateLead('99999', [
            'STATUS_ID' => 'UC_EMAILED',
        ]);

        $this->assertFalse($result);
    }

    /** @test */
    public function test_create_user_field_succeeds()
    {
        Http::fake([
            'https://test.bitrix24.com/rest/1/testtoken/crm.lead.userfield.add.json' => Http::response([
                'result' => 100,
            ], 200),
        ]);

        $result = $this->client->createUserField('FCT_SOURCE', 'string');

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://test.bitrix24.com/rest/1/testtoken/crm.lead.userfield.add.json' &&
                   $request['fields']['FIELD_NAME'] === 'FCT_SOURCE' &&
                   $request['fields']['USER_TYPE_ID'] === 'string';
        });
    }

    /** @test */
    public function test_create_user_field_is_idempotent_when_field_exists()
    {
        // First call - field created
        Http::fake([
            'https://test.bitrix24.com/rest/1/testtoken/crm.lead.userfield.add.json' => Http::sequence()
                ->push(['result' => 100], 200)
                ->push([
                    'error' => 'FIELD_NAME_USED',
                    'error_description' => 'Field name already used',
                ], 200),
        ]);

        // First call succeeds
        $result1 = $this->client->createUserField('FCT_SOURCE', 'string');
        $this->assertTrue($result1);

        // Second call - field already exists, but operation is idempotent
        // (returns false but doesn't throw)
        $result2 = $this->client->createUserField('FCT_SOURCE', 'string');
        $this->assertFalse($result2);

        Http::assertSentCount(2);
    }

    /** @test */
    public function test_get_user_fields_returns_array()
    {
        Http::fake([
            'https://test.bitrix24.com/rest/1/testtoken/crm.lead.userfield.list.json' => Http::response([
                'result' => [
                    ['ID' => '1', 'FIELD_NAME' => 'UF_CRM_FCT_SOURCE'],
                    ['ID' => '2', 'FIELD_NAME' => 'UF_CRM_FCT_CITY'],
                ],
            ], 200),
        ]);

        $fields = $this->client->getUserFields();

        $this->assertCount(2, $fields);
        $this->assertEquals('UF_CRM_FCT_SOURCE', $fields[0]['FIELD_NAME']);
    }

    /** @test */
    public function test_get_lead_returns_lead_data()
    {
        Http::fake([
            'https://test.bitrix24.com/rest/1/testtoken/crm.lead.get.json' => Http::response([
                'result' => [
                    'ID' => '12345',
                    'TITLE' => 'Test Company',
                    'NAME' => 'John Doe',
                    'STATUS_ID' => 'NEW',
                ],
            ], 200),
        ]);

        $lead = $this->client->getLead('12345');

        $this->assertNotNull($lead);
        $this->assertEquals('12345', $lead['ID']);
        $this->assertEquals('Test Company', $lead['TITLE']);
    }

    /** @test */
    public function test_get_lead_returns_null_when_not_found()
    {
        Http::fake([
            'https://test.bitrix24.com/rest/1/testtoken/crm.lead.get.json' => Http::response([
                'error' => 'NOT_FOUND',
                'error_description' => 'Lead not found',
            ], 200),
        ]);

        $lead = $this->client->getLead('99999');

        $this->assertNull($lead);
    }

    /** @test */
    public function test_move_lead_to_stage_calls_update_lead()
    {
        Http::fake([
            'https://test.bitrix24.com/rest/1/testtoken/crm.lead.update.json' => Http::response([
                'result' => true,
            ], 200),
        ]);

        $result = $this->client->moveLeadToStage('12345', 'UC_EMAILED');

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request['fields']['STATUS_ID'] === 'UC_EMAILED';
        });
    }

    /** @test */
    public function test_add_timeline_comment_succeeds()
    {
        Http::fake([
            'https://test.bitrix24.com/rest/1/testtoken/crm.timeline.comment.add.json' => Http::response([
                'result' => 567,
            ], 200),
        ]);

        $result = $this->client->addTimelineComment('12345', 'Email sent via Facturino');

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request['fields']['ENTITY_ID'] === '12345' &&
                   $request['fields']['ENTITY_TYPE'] === 'lead' &&
                   $request['fields']['COMMENT'] === 'Email sent via Facturino';
        });
    }

    /** @test */
    public function test_create_lead_status_succeeds()
    {
        Http::fake([
            'https://test.bitrix24.com/rest/1/testtoken/crm.status.add.json' => Http::response([
                'result' => 200,
            ], 200),
        ]);

        $result = $this->client->createLeadStatus('UC_EMAILED', 'Emailed', 20);

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request['fields']['STATUS_ID'] === 'UC_EMAILED' &&
                   $request['fields']['NAME'] === 'Emailed' &&
                   $request['fields']['SORT'] === 20;
        });
    }

    /** @test */
    public function test_get_stage_code_returns_configured_value()
    {
        $stageCode = $this->client->getStageCode('EMAILED');

        $this->assertEquals('UC_EMAILED', $stageCode);
    }

    /** @test */
    public function test_get_stage_code_returns_null_for_unknown_stage()
    {
        $stageCode = $this->client->getStageCode('UNKNOWN');

        $this->assertNull($stageCode);
    }

    /** @test */
    public function test_get_custom_field_name_returns_configured_value()
    {
        $fieldName = $this->client->getCustomFieldName('source');

        $this->assertEquals('UF_FCT_SOURCE', $fieldName);
    }

    /** @test */
    public function test_get_custom_field_name_returns_null_for_unknown_field()
    {
        $fieldName = $this->client->getCustomFieldName('unknown');

        $this->assertNull($fieldName);
    }

    /** @test */
    public function test_create_task_returns_task_id()
    {
        Http::fake([
            'https://test.bitrix24.com/rest/1/testtoken/tasks.task.add.json' => Http::response([
                'result' => [
                    'task' => [
                        'id' => '789',
                    ],
                ],
            ], 200),
        ]);

        $taskId = $this->client->createTask(
            'Follow up with lead',
            'Lead clicked email link, follow up immediately',
            '1',
            now()->addDay()->toIso8601String()
        );

        $this->assertEquals('789', $taskId);
    }
}

// CLAUDE-CHECKPOINT
