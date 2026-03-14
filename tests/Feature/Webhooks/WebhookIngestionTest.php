<?php

namespace Tests\Feature\Webhooks;

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Mk\Services\CpayDriver;
use Tests\TestCase;

class WebhookIngestionTest extends TestCase
{
    use RefreshDatabase;

    public function test_paddle_webhook_stores_event(): void
    {
        $company = Company::factory()->create();

        $payload = [
            'event_id' => 'evt_test_123',
            'event_type' => 'transaction.completed',
            'data' => [
                'custom_data' => [
                    'company_id' => $company->id,
                    'invoice_id' => 1,
                ],
            ],
        ];

        $response = $this->postJson('/webhooks/paddle', $payload, [
            'Paddle-Signature' => 'test-signature',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'received']);

        $this->assertDatabaseHas('gateway_webhook_events', [
            'company_id' => $company->id,
            'provider' => 'paddle',
            'event_type' => 'transaction.completed',
            'event_id' => 'evt_test_123',
            'status' => 'pending',
        ]);
    }

    public function test_cpay_webhook_stores_event(): void
    {
        $company = Company::factory()->create();

        // Mock CpayDriver to accept any signature (avoids coupling test to signature algorithm)
        $mock = $this->mock(CpayDriver::class);
        $mock->shouldReceive('verifySignature')->once()->andReturn(true);

        $payload = [
            'transaction_id' => 'txn_test_456',
            'status' => 'success',
            'merchant_data' => [
                'company_id' => $company->id,
            ],
            'signature' => 'test-signature',
        ];

        $response = $this->postJson('/webhooks/cpay', $payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('gateway_webhook_events', [
            'company_id' => $company->id,
            'provider' => 'cpay',
            'event_id' => 'txn_test_456',
            'status' => 'pending',
        ]);
    }

    public function test_cpay_webhook_rejects_invalid_signature(): void
    {
        $mock = $this->mock(CpayDriver::class);
        $mock->shouldReceive('verifySignature')->once()->andReturn(false);

        $payload = [
            'transaction_id' => 'txn_test_789',
            'status' => 'success',
            'merchant_data' => ['company_id' => 1],
            'signature' => 'bad-signature',
        ];

        $response = $this->postJson('/webhooks/cpay', $payload);

        $response->assertStatus(401);
        $response->assertJson(['error' => 'Invalid signature']);
    }

    public function test_webhook_rejects_missing_company_id(): void
    {
        $payload = [
            'event_id' => 'evt_test_789',
            'event_type' => 'transaction.completed',
            'data' => [],
        ];

        $response = $this->postJson('/webhooks/paddle', $payload);

        $response->assertStatus(400);
        $response->assertJson(['error' => 'Missing company_id']);
    }
}
