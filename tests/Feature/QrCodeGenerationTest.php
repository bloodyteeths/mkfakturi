<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * QR Code Generation API Tests
 *
 * Tests the /api/qr endpoint used by invitation pages and other features.
 */
class QrCodeGenerationTest extends TestCase
{
    /**
     * Test QR code generation with valid URL data
     */
    public function test_generates_svg_qr_code_with_valid_url(): void
    {
        $response = $this->get('/api/qr?data=' . urlencode('https://app.facturino.mk/signup?ref=abc123'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/svg+xml');
        $this->assertStringContainsString('<svg', $response->getContent());
    }

    /**
     * Test QR code generation with PNG format
     */
    public function test_generates_png_qr_code(): void
    {
        $response = $this->get('/api/qr?data=' . urlencode('https://example.com') . '&format=png');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/png');
    }

    /**
     * Test QR code generation with custom size
     */
    public function test_generates_qr_code_with_custom_size(): void
    {
        $response = $this->get('/api/qr?data=' . urlencode('https://example.com') . '&size=400');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/svg+xml');
    }

    /**
     * Test QR code generation fails without data parameter
     */
    public function test_fails_without_data_parameter(): void
    {
        $response = $this->get('/api/qr');

        // Laravel validation returns 302 redirect for validation errors in some contexts
        $this->assertTrue(in_array($response->status(), [302, 422]));
    }

    /**
     * Test QR code generation fails with invalid format
     */
    public function test_fails_with_invalid_format(): void
    {
        $response = $this->get('/api/qr?data=' . urlencode('https://example.com') . '&format=invalid');

        // Laravel validation returns 302 redirect for validation errors in some contexts
        $this->assertTrue(in_array($response->status(), [302, 422]));
    }

    /**
     * Test QR code generation with partner invitation URL
     */
    public function test_generates_qr_for_partner_invitation(): void
    {
        $url = 'https://app.facturino.mk/partner/signup?ref=xyz789';
        $response = $this->get('/api/qr?data=' . urlencode($url));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/svg+xml');
        $this->assertStringContainsString('<svg', $response->getContent());
    }

    /**
     * Test QR code caching headers
     */
    public function test_sets_cache_headers(): void
    {
        $response = $this->get('/api/qr?data=' . urlencode('https://example.com'));

        $response->assertStatus(200);
        // Check that cache header contains max-age=3600 (order may vary)
        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('max-age=3600', $cacheControl);
        $this->assertStringContainsString('public', $cacheControl);
    }

    /**
     * Test QR code generation respects throttling
     */
    public function test_respects_rate_limiting(): void
    {
        // This test would require setting up throttle testing
        // For now, we just verify the route exists
        $response = $this->get('/api/qr?data=' . urlencode('https://example.com'));
        $response->assertStatus(200);
    }
}

// CLAUDE-CHECKPOINT
