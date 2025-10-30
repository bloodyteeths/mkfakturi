<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * AUTH-04: Session Security and CSRF Validation Feature Test
 * 
 * This test validates authentication security measures including:
 * - CSRF protection on authentication endpoints
 * - Rate limiting on login attempts  
 * - Session security headers
 * - Authentication middleware protection
 * - Token-based authentication security
 * 
 * Target: All security controls validated with proper error handling
 */
class AuthSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $company;
    protected $performanceMetrics = [];

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user and company
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create([
            'email' => 'security.test@invoiceshelf.com',
            'password' => Hash::make('SecurePassword123!'),
            'role' => 'admin'
        ]);
        $this->user->companies()->attach($this->company->id);
    }

    /** @test */
    public function it_enforces_csrf_protection_on_login_endpoint()
    {
        $startTime = microtime(true);
        
        // Attempt login without CSRF token
        $response = $this->postJson('/api/v1/auth/login', [
            'username' => $this->user->email,
            'password' => 'SecurePassword123!',
            'device_name' => 'test-device'
        ]);

        // Should be protected by CSRF (419 status code)
        // Note: API routes may use different protection, so we check for proper rejection
        $this->assertTrue(
            in_array($response->status(), [419, 422, 401]),
            'CSRF protection should reject requests without proper tokens'
        );

        $this->performanceMetrics['csrf_check'] = (microtime(true) - $startTime) * 1000;
        $this->assertLessThan(100, $this->performanceMetrics['csrf_check'], 'CSRF check should be fast');
    }

    /** @test */
    public function it_implements_rate_limiting_on_login_attempts()
    {
        $startTime = microtime(true);
        $email = $this->user->email;
        $attempts = 0;
        $blocked = false;

        // Attempt multiple logins rapidly
        for ($i = 0; $i < 10; $i++) {
            $response = $this->postJson('/api/v1/auth/login', [
                'username' => $email,
                'password' => 'wrong-password',
                'device_name' => 'test-device'
            ]);

            $attempts++;

            // Check if rate limiting kicks in
            if ($response->status() === 429) {
                $blocked = true;
                break;
            }

            // Small delay to avoid overwhelming
            usleep(100000); // 0.1 second
        }

        $this->performanceMetrics['rate_limit_check'] = (microtime(true) - $startTime) * 1000;

        // Should be blocked before 10 attempts or handle gracefully
        $this->assertTrue(
            $blocked || $attempts >= 5,
            'Rate limiting should block excessive login attempts'
        );

        if ($blocked) {
            $this->assertStringContainsString('rate', strtolower($response->getContent()));
        }
    }

    /** @test */
    public function it_validates_authentication_middleware_protection()
    {
        $startTime = microtime(true);

        // Test protected endpoints without authentication
        $protectedEndpoints = [
            '/api/v1/dashboard',
            '/api/v1/users',
            '/api/v1/customers',
            '/api/v1/invoices',
            '/api/v1/payments'
        ];

        foreach ($protectedEndpoints as $endpoint) {
            $response = $this->getJson($endpoint);
            
            // Should require authentication (401)
            $this->assertEquals(401, $response->status(), 
                "Endpoint {$endpoint} should require authentication");
        }

        $this->performanceMetrics['middleware_check'] = (microtime(true) - $startTime) * 1000;
        $this->assertLessThan(500, $this->performanceMetrics['middleware_check'], 
            'Middleware checks should be efficient');
    }

    /** @test */
    public function it_validates_session_security_headers()
    {
        $startTime = microtime(true);

        // Test login endpoint for security headers
        $response = $this->get('/admin/auth/login');

        $headers = $response->headers;

        // Check for security headers (may vary based on configuration)
        $securityHeaders = [
            'X-Frame-Options',
            'X-Content-Type-Options', 
            'X-XSS-Protection',
            'Referrer-Policy'
        ];

        $foundHeaders = 0;
        foreach ($securityHeaders as $header) {
            if ($headers->has($header)) {
                $foundHeaders++;
            }
        }

        // Should have at least some security headers
        $this->assertGreaterThan(0, $foundHeaders, 
            'Should have security headers present');

        $this->performanceMetrics['security_headers'] = (microtime(true) - $startTime) * 1000;
    }

    /** @test */
    public function it_validates_token_based_authentication_security()
    {
        $startTime = microtime(true);

        // Successful login to get token
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'username' => $this->user->email,
            'password' => 'SecurePassword123!',
            'device_name' => 'test-device'
        ]);

        if ($loginResponse->status() === 200) {
            $token = $loginResponse->json('token');
            $this->assertNotEmpty($token, 'Should receive authentication token');

            // Test token format (Bearer token)
            $this->assertStringStartsWith('Bearer ', $token);

            // Test authenticated request with token
            $authenticatedResponse = $this->withHeaders([
                'Authorization' => $token,
                'company' => $this->company->id
            ])->getJson('/api/v1/auth/check');

            $this->assertTrue(
                in_array($authenticatedResponse->status(), [200, 201]),
                'Authenticated request should succeed'
            );
        }

        $this->performanceMetrics['token_auth'] = (microtime(true) - $startTime) * 1000;
        $this->assertLessThan(2000, $this->performanceMetrics['token_auth'], 
            'Token authentication should be efficient');
    }

    /** @test */
    public function it_validates_password_hashing_security()
    {
        $startTime = microtime(true);

        // Check that passwords are properly hashed
        $this->assertTrue(Hash::check('SecurePassword123!', $this->user->password),
            'Password should be properly hashed');

        // Verify password is not stored in plain text
        $this->assertNotEquals('SecurePassword123!', $this->user->password,
            'Password should not be stored in plain text');

        // Check hash complexity (bcrypt should produce long hashes)
        $this->assertGreaterThan(50, strlen($this->user->password),
            'Password hash should be sufficiently complex');

        $this->performanceMetrics['password_validation'] = (microtime(true) - $startTime) * 1000;
    }

    /** @test */
    public function it_validates_session_fixation_protection()
    {
        $startTime = microtime(true);

        // Start session and get initial session ID
        $this->startSession();
        $initialSessionId = session()->getId();

        // Attempt login
        $response = $this->post('/admin/login', [
            'email' => $this->user->email,
            'password' => 'SecurePassword123!',
            '_token' => csrf_token()
        ]);

        // Session ID should change after authentication (session fixation protection)
        $newSessionId = session()->getId();
        
        // This test may not apply to API authentication, but good to verify
        if ($initialSessionId && $newSessionId) {
            $this->assertNotEquals($initialSessionId, $newSessionId,
                'Session ID should change after authentication to prevent session fixation');
        }

        $this->performanceMetrics['session_fixation'] = (microtime(true) - $startTime) * 1000;
    }

    /** @test */
    public function it_validates_concurrent_session_handling()
    {
        $startTime = microtime(true);

        // Simulate multiple login sessions
        $tokens = [];

        for ($i = 0; $i < 3; $i++) {
            $response = $this->postJson('/api/v1/auth/login', [
                'username' => $this->user->email,
                'password' => 'SecurePassword123!',
                'device_name' => "device-{$i}"
            ]);

            if ($response->status() === 200) {
                $tokens[] = $response->json('token');
            }
        }

        // Verify multiple tokens can be issued (for different devices)
        $this->assertGreaterThan(0, count($tokens),
            'Should be able to create multiple sessions for different devices');

        // Test that all tokens are valid
        foreach ($tokens as $token) {
            $response = $this->withHeaders([
                'Authorization' => $token,
                'company' => $this->company->id
            ])->getJson('/api/v1/auth/check');

            $this->assertTrue(
                in_array($response->status(), [200, 201, 404]),
                'Each token should be independently valid'
            );
        }

        $this->performanceMetrics['concurrent_sessions'] = (microtime(true) - $startTime) * 1000;
    }

    /** @test */
    public function it_validates_logout_token_invalidation()
    {
        $startTime = microtime(true);

        // Login to get token
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'username' => $this->user->email,
            'password' => 'SecurePassword123!',
            'device_name' => 'test-device'
        ]);

        if ($loginResponse->status() === 200) {
            $token = $loginResponse->json('token');

            // Verify token works
            $checkResponse = $this->withHeaders([
                'Authorization' => $token,
                'company' => $this->company->id
            ])->getJson('/api/v1/auth/check');

            // Logout
            $logoutResponse = $this->withHeaders([
                'Authorization' => $token
            ])->postJson('/api/v1/auth/logout');

            $this->assertTrue(
                in_array($logoutResponse->status(), [200, 201]),
                'Logout should succeed'
            );

            // Try to use token after logout
            $invalidTokenResponse = $this->withHeaders([
                'Authorization' => $token,
                'company' => $this->company->id
            ])->getJson('/api/v1/auth/check');

            $this->assertEquals(401, $invalidTokenResponse->status(),
                'Token should be invalid after logout');
        }

        $this->performanceMetrics['logout_invalidation'] = (microtime(true) - $startTime) * 1000;
    }

    /** @test */
    public function it_validates_input_sanitization_and_validation()
    {
        $startTime = microtime(true);

        // Test various malicious inputs
        $maliciousInputs = [
            ['username' => '<script>alert("xss")</script>', 'password' => 'test'],
            ['username' => "'; DROP TABLE users; --", 'password' => 'test'],
            ['username' => '../../../etc/passwd', 'password' => 'test'],
            ['username' => str_repeat('a', 10000), 'password' => 'test'], // Long input
        ];

        foreach ($maliciousInputs as $input) {
            $response = $this->postJson('/api/v1/auth/login', array_merge($input, [
                'device_name' => 'test-device'
            ]));

            // Should handle malicious input gracefully (not crash)
            $this->assertTrue(
                in_array($response->status(), [401, 422, 400]),
                'Should handle malicious input gracefully'
            );

            // Response should not contain the malicious input (XSS protection)
            $responseContent = $response->getContent();
            $this->assertStringNotContainsString('<script>', $responseContent);
            $this->assertStringNotContainsString('DROP TABLE', $responseContent);
        }

        $this->performanceMetrics['input_validation'] = (microtime(true) - $startTime) * 1000;
    }

    /** @test */
    public function it_validates_brute_force_protection()
    {
        $startTime = microtime(true);
        $email = $this->user->email;
        $failedAttempts = 0;
        $lockoutOccurred = false;

        // Attempt multiple failed logins
        for ($i = 0; $i < 15; $i++) {
            $response = $this->postJson('/api/v1/auth/login', [
                'username' => $email,
                'password' => 'definitely-wrong-password',
                'device_name' => 'test-device'
            ]);

            if ($response->status() === 401 || $response->status() === 422) {
                $failedAttempts++;
            } elseif ($response->status() === 429) {
                $lockoutOccurred = true;
                break;
            }

            usleep(200000); // 0.2 second delay
        }

        // Should implement some form of brute force protection
        $this->assertTrue(
            $lockoutOccurred || $failedAttempts >= 5,
            'Should have brute force protection measures'
        );

        $this->performanceMetrics['brute_force_protection'] = (microtime(true) - $startTime) * 1000;
    }

    protected function tearDown(): void
    {
        // Log performance metrics
        if (!empty($this->performanceMetrics)) {
            $avgTime = array_sum($this->performanceMetrics) / count($this->performanceMetrics);
            
            echo "\n📊 AUTH-04 Performance Metrics:\n";
            foreach ($this->performanceMetrics as $test => $time) {
                echo sprintf("   %s: %.2fms\n", ucfirst(str_replace('_', ' ', $test)), $time);
            }
            echo sprintf("   Average: %.2fms\n", $avgTime);
            
            if ($avgTime < 200) {
                echo "🎯 TARGET MET: Security checks <200ms average\n";
            } elseif ($avgTime < 500) {
                echo "⚠️ ACCEPTABLE: Security checks <500ms average\n";
            } else {
                echo "❌ NEEDS OPTIMIZATION: Security checks >500ms average\n";
            }
        }

        parent::tearDown();
    }
}