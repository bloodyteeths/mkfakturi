<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Health Check Endpoint Tests
 *
 * Tests the comprehensive health check system
 * CLAUDE-CHECKPOINT
 */
class HealthCheckTest extends TestCase
{
    /**
     * Test health endpoint returns 200 when healthy
     */
    public function test_health_endpoint_returns_success_when_healthy(): void
    {
        $response = $this->get('/health');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'timestamp',
            'version',
            'environment',
            'checks' => [
                'database',
                'redis',
                'queues',
                'signer',
                'bank_sync',
                'storage',
                'backup',
                'certificates',
            ],
        ]);
    }

    /**
     * Test health endpoint returns correct status value
     */
    public function test_health_endpoint_returns_correct_status(): void
    {
        $response = $this->get('/health');

        $data = $response->json();

        $this->assertContains($data['status'], ['healthy', 'degraded']);
    }

    /**
     * Test ping endpoint works
     */
    public function test_ping_endpoint_works(): void
    {
        $response = $this->get('/ping');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'timestamp',
        ]);
    }

    /**
     * Test API ping endpoint works
     */
    public function test_api_ping_endpoint_works(): void
    {
        $response = $this->get('/api/ping');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
        ]);
    }

    /**
     * Test ready endpoint works
     */
    public function test_ready_endpoint_works(): void
    {
        $response = $this->get('/ready');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'timestamp',
        ]);
    }

    /**
     * Test health checks include all expected components
     */
    public function test_health_checks_include_all_components(): void
    {
        $response = $this->get('/health');

        $data = $response->json();

        $this->assertArrayHasKey('database', $data['checks']);
        $this->assertArrayHasKey('redis', $data['checks']);
        $this->assertArrayHasKey('queues', $data['checks']);
        $this->assertArrayHasKey('signer', $data['checks']);
        $this->assertArrayHasKey('bank_sync', $data['checks']);
        $this->assertArrayHasKey('storage', $data['checks']);
        $this->assertArrayHasKey('backup', $data['checks']);
        $this->assertArrayHasKey('certificates', $data['checks']);
    }

    /**
     * Test database check passes
     */
    public function test_database_check_passes(): void
    {
        $response = $this->get('/health');

        $data = $response->json();

        $this->assertTrue($data['checks']['database']);
    }
}
// CLAUDE-CHECKPOINT
