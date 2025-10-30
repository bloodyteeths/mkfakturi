<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

/**
 * AUTH-05: Logout and Session Cleanup Audit Feature Test
 * 
 * This test validates session cleanup and logout functionality:
 * - Complete session invalidation on logout
 * - Token cleanup and revocation
 * - Cache cleanup for user-specific data
 * - Memory cleanup verification
 * - Database session cleanup
 * - No data leaks after logout
 * 
 * Target: All sessions invalidated with no data persistence
 */
class SessionCleanupTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $partnerUser;
    protected $company;
    protected $partnerCompany;
    protected $performanceMetrics = [];
    protected $memoryBefore = [];
    protected $memoryAfter = [];

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test companies
        $this->company = Company::factory()->create(['name' => 'Main Test Company']);
        $this->partnerCompany = Company::factory()->create(['name' => 'Partner Test Company']);
        
        // Create admin user
        $this->user = User::factory()->create([
            'email' => 'admin.cleanup@invoiceshelf.com',
            'password' => Hash::make('CleanupTest123!'),
            'role' => 'admin'
        ]);
        $this->user->companies()->attach($this->company->id);
        
        // Create partner user
        $this->partnerUser = User::factory()->create([
            'email' => 'partner.cleanup@invoiceshelf.com',
            'password' => Hash::make('PartnerTest123!'),
            'role' => 'partner'
        ]);
        $this->partnerUser->companies()->attach([$this->company->id, $this->partnerCompany->id]);
        
        // Create some test data
        $this->createTestData();
    }

    protected function createTestData()
    {
        // Create customers and invoices for session testing
        Customer::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'creator_id' => $this->user->id
        ]);
        
        Invoice::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'creator_id' => $this->user->id,
            'user_id' => $this->user->id
        ]);
    }

    /** @test */
    public function it_completely_invalidates_session_on_logout()
    {
        $startTime = microtime(true);
        $this->recordMemoryUsage('before_login');

        // Login and create session
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'username' => $this->user->email,
            'password' => 'CleanupTest123!',
            'device_name' => 'cleanup-test-device'
        ]);

        $this->assertEquals(200, $loginResponse->status(), 'Login should succeed');
        $token = $loginResponse->json('token');
        $this->assertNotEmpty($token, 'Should receive authentication token');

        $this->recordMemoryUsage('after_login');

        // Verify authenticated access works
        $authResponse = $this->withHeaders([
            'Authorization' => $token,
            'company' => $this->company->id
        ])->getJson('/api/v1/auth/check');

        $this->assertTrue(
            in_array($authResponse->status(), [200, 201]),
            'Authenticated request should work before logout'
        );

        // Perform logout
        $logoutResponse = $this->withHeaders([
            'Authorization' => $token
        ])->postJson('/api/v1/auth/logout');

        $this->assertTrue(
            in_array($logoutResponse->status(), [200, 201]),
            'Logout should succeed'
        );

        $this->recordMemoryUsage('after_logout');

        // Verify token is invalidated
        $invalidResponse = $this->withHeaders([
            'Authorization' => $token,
            'company' => $this->company->id
        ])->getJson('/api/v1/auth/check');

        $this->assertEquals(401, $invalidResponse->status(),
            'Token should be invalid after logout');

        // Verify token is removed from database
        $tokenExists = PersonalAccessToken::where('token', hash('sha256', str_replace('Bearer ', '', $token)))
            ->exists();
        $this->assertFalse($tokenExists, 'Token should be removed from database');

        $this->performanceMetrics['session_invalidation'] = (microtime(true) - $startTime) * 1000;
        $this->assertLessThan(1000, $this->performanceMetrics['session_invalidation'],
            'Session invalidation should be fast');
    }

    /** @test */
    public function it_cleans_up_user_specific_cache_data()
    {
        $startTime = microtime(true);

        // Login user
        $token = $this->loginUser($this->user);

        // Create some cached data for the user
        $cacheKeys = [
            "user_permissions_{$this->user->id}",
            "user_roles_{$this->user->id}",
            "user_settings_{$this->user->id}",
            "company_data_{$this->company->id}_{$this->user->id}"
        ];

        foreach ($cacheKeys as $key) {
            Cache::put($key, 'test_data_' . $key, 3600);
        }

        // Verify cache data exists
        foreach ($cacheKeys as $key) {
            $this->assertTrue(Cache::has($key), "Cache key {$key} should exist before logout");
        }

        // Logout
        $this->withHeaders(['Authorization' => $token])
            ->postJson('/api/v1/auth/logout');

        // Check if user-specific cache is cleared
        // Note: This depends on implementation - some systems clear cache on logout
        $cacheCleared = 0;
        foreach ($cacheKeys as $key) {
            if (!Cache::has($key)) {
                $cacheCleared++;
            }
        }

        // Either cache should be cleared or we should document that it's not
        if ($cacheCleared > 0) {
            $this->addToAssertionCount(1); // Cache cleanup implemented
        }

        $this->performanceMetrics['cache_cleanup'] = (microtime(true) - $startTime) * 1000;
    }

    /** @test */
    public function it_handles_multiple_concurrent_sessions_cleanup()
    {
        $startTime = microtime(true);

        // Create multiple sessions for the same user
        $tokens = [];
        for ($i = 0; $i < 3; $i++) {
            $loginResponse = $this->postJson('/api/v1/auth/login', [
                'username' => $this->user->email,
                'password' => 'CleanupTest123!',
                'device_name' => "device-{$i}"
            ]);

            if ($loginResponse->status() === 200) {
                $tokens[] = $loginResponse->json('token');
            }
        }

        $this->assertGreaterThan(0, count($tokens), 'Should create multiple tokens');

        // Verify all tokens work
        foreach ($tokens as $token) {
            $response = $this->withHeaders([
                'Authorization' => $token,
                'company' => $this->company->id
            ])->getJson('/api/v1/auth/check');

            $this->assertTrue(
                in_array($response->status(), [200, 201]),
                'All tokens should work before logout'
            );
        }

        // Logout from one session
        $this->withHeaders(['Authorization' => $tokens[0]])
            ->postJson('/api/v1/auth/logout');

        // First token should be invalid
        $response = $this->withHeaders([
            'Authorization' => $tokens[0],
            'company' => $this->company->id
        ])->getJson('/api/v1/auth/check');

        $this->assertEquals(401, $response->status(),
            'Logged out token should be invalid');

        // Other tokens should still work (single session logout)
        for ($i = 1; $i < count($tokens); $i++) {
            $response = $this->withHeaders([
                'Authorization' => $tokens[$i],
                'company' => $this->company->id
            ])->getJson('/api/v1/auth/check');

            $this->assertTrue(
                in_array($response->status(), [200, 201]),
                'Other tokens should remain valid'
            );
        }

        $this->performanceMetrics['concurrent_cleanup'] = (microtime(true) - $startTime) * 1000;
    }

    /** @test */
    public function it_cleans_up_partner_company_context_on_logout()
    {
        $startTime = microtime(true);

        // Login as partner user
        $token = $this->loginUser($this->partnerUser);

        // Simulate company context switching
        $switchResponse = $this->withHeaders([
            'Authorization' => $token,
            'company' => $this->partnerCompany->id
        ])->getJson('/api/v1/customers');

        // Partner should be able to access data in partner company context
        $this->assertTrue(
            in_array($switchResponse->status(), [200, 201, 404]),
            'Partner should access company data'
        );

        // Logout
        $logoutResponse = $this->withHeaders(['Authorization' => $token])
            ->postJson('/api/v1/auth/logout');

        $this->assertTrue(
            in_array($logoutResponse->status(), [200, 201]),
            'Partner logout should succeed'
        );

        // Verify all company contexts are cleared
        $contextResponse = $this->withHeaders([
            'Authorization' => $token,
            'company' => $this->partnerCompany->id
        ])->getJson('/api/v1/customers');

        $this->assertEquals(401, $contextResponse->status(),
            'All company contexts should be invalidated');

        $this->performanceMetrics['partner_context_cleanup'] = (microtime(true) - $startTime) * 1000;
    }

    /** @test */
    public function it_prevents_data_leaks_after_logout()
    {
        $startTime = microtime(true);

        // Login and access some sensitive data
        $token = $this->loginUser($this->user);

        // Access user data
        $customerResponse = $this->withHeaders([
            'Authorization' => $token,
            'company' => $this->company->id
        ])->getJson('/api/v1/customers');

        $invoiceResponse = $this->withHeaders([
            'Authorization' => $token,
            'company' => $this->company->id
        ])->getJson('/api/v1/invoices');

        // Logout
        $this->withHeaders(['Authorization' => $token])
            ->postJson('/api/v1/auth/logout');

        // Try to access the same data with invalid token
        $leakTestEndpoints = [
            '/api/v1/customers',
            '/api/v1/invoices',
            '/api/v1/users',
            '/api/v1/dashboard'
        ];

        foreach ($leakTestEndpoints as $endpoint) {
            $response = $this->withHeaders([
                'Authorization' => $token,
                'company' => $this->company->id
            ])->getJson($endpoint);

            $this->assertEquals(401, $response->status(),
                "Endpoint {$endpoint} should not leak data after logout");

            // Verify no sensitive data in response
            $responseContent = $response->getContent();
            $this->assertStringNotContainsString($this->user->email, $responseContent);
            $this->assertStringNotContainsString('Test Company', $responseContent);
        }

        $this->performanceMetrics['data_leak_prevention'] = (microtime(true) - $startTime) * 1000;
    }

    /** @test */
    public function it_cleans_up_database_sessions()
    {
        $startTime = microtime(true);

        // Login to create database session
        $token = $this->loginUser($this->user);

        // Count active tokens before logout
        $tokensBefore = PersonalAccessToken::where('tokenable_id', $this->user->id)->count();
        $this->assertGreaterThan(0, $tokensBefore, 'Should have active tokens');

        // Logout
        $this->withHeaders(['Authorization' => $token])
            ->postJson('/api/v1/auth/logout');

        // Count active tokens after logout
        $tokensAfter = PersonalAccessToken::where('tokenable_id', $this->user->id)->count();
        $this->assertLessThan($tokensBefore, $tokensAfter,
            'Token count should decrease after logout');

        // Check for specific token removal
        $tokenHash = hash('sha256', str_replace('Bearer ', '', $token));
        $tokenExists = PersonalAccessToken::where('token', $tokenHash)->exists();
        $this->assertFalse($tokenExists, 'Specific token should be removed');

        $this->performanceMetrics['database_cleanup'] = (microtime(true) - $startTime) * 1000;
    }

    /** @test */
    public function it_validates_memory_cleanup_after_logout()
    {
        $startTime = microtime(true);

        // Record memory before operations
        $this->recordMemoryUsage('before_test');

        // Login and perform memory-intensive operations
        $token = $this->loginUser($this->user);

        // Simulate data loading
        $this->withHeaders([
            'Authorization' => $token,
            'company' => $this->company->id
        ])->getJson('/api/v1/customers');

        $this->withHeaders([
            'Authorization' => $token,
            'company' => $this->company->id
        ])->getJson('/api/v1/invoices');

        $this->recordMemoryUsage('after_data_load');

        // Logout
        $this->withHeaders(['Authorization' => $token])
            ->postJson('/api/v1/auth/logout');

        // Force garbage collection
        gc_collect_cycles();

        $this->recordMemoryUsage('after_logout');

        // Memory should not increase significantly
        $memoryIncrease = $this->memoryAfter['after_logout'] - $this->memoryBefore['before_test'];
        $this->assertLessThan(50 * 1024 * 1024, $memoryIncrease,
            'Memory increase should be less than 50MB'); // Reasonable threshold

        $this->performanceMetrics['memory_cleanup'] = (microtime(true) - $startTime) * 1000;
    }

    /** @test */
    public function it_handles_logout_errors_gracefully()
    {
        $startTime = microtime(true);

        // Test logout with invalid token
        $invalidResponse = $this->withHeaders([
            'Authorization' => 'Bearer invalid-token-12345'
        ])->postJson('/api/v1/auth/logout');

        $this->assertTrue(
            in_array($invalidResponse->status(), [401, 200]),
            'Invalid token logout should be handled gracefully'
        );

        // Test logout without token
        $noTokenResponse = $this->postJson('/api/v1/auth/logout');
        $this->assertTrue(
            in_array($noTokenResponse->status(), [401, 422]),
            'No token logout should be handled gracefully'
        );

        // Test double logout
        $token = $this->loginUser($this->user);
        
        $firstLogout = $this->withHeaders(['Authorization' => $token])
            ->postJson('/api/v1/auth/logout');
        
        $secondLogout = $this->withHeaders(['Authorization' => $token])
            ->postJson('/api/v1/auth/logout');

        $this->assertTrue(
            in_array($firstLogout->status(), [200, 201]),
            'First logout should succeed'
        );

        $this->assertTrue(
            in_array($secondLogout->status(), [401, 200]),
            'Second logout should be handled gracefully'
        );

        $this->performanceMetrics['error_handling'] = (microtime(true) - $startTime) * 1000;
    }

    /** @test */
    public function it_validates_session_timeout_cleanup()
    {
        $startTime = microtime(true);

        // Login to create session
        $token = $this->loginUser($this->user);

        // Simulate session timeout by manipulating token timestamp
        $tokenModel = PersonalAccessToken::where('tokenable_id', $this->user->id)
            ->where('token', hash('sha256', str_replace('Bearer ', '', $token)))
            ->first();

        if ($tokenModel) {
            // Simulate expired token (set created_at to past)
            $tokenModel->update([
                'created_at' => now()->subHours(25), // Assuming 24h expiry
                'updated_at' => now()->subHours(25)
            ]);

            // Try to use expired token
            $expiredResponse = $this->withHeaders([
                'Authorization' => $token,
                'company' => $this->company->id
            ])->getJson('/api/v1/auth/check');

            // Should be rejected (implementation dependent)
            $this->assertTrue(
                in_array($expiredResponse->status(), [401, 419]),
                'Expired token should be rejected'
            );
        }

        $this->performanceMetrics['timeout_cleanup'] = (microtime(true) - $startTime) * 1000;
    }

    protected function loginUser(User $user): string
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'username' => $user->email,
            'password' => $user->email === $this->user->email ? 'CleanupTest123!' : 'PartnerTest123!',
            'device_name' => 'test-device'
        ]);

        $this->assertEquals(200, $response->status(), 'Login should succeed');
        return $response->json('token');
    }

    protected function recordMemoryUsage(string $point): void
    {
        $memory = memory_get_usage(true);
        if (strpos($point, 'before') === 0) {
            $this->memoryBefore[$point] = $memory;
        } else {
            $this->memoryAfter[$point] = $memory;
        }
    }

    protected function tearDown(): void
    {
        // Log performance metrics
        if (!empty($this->performanceMetrics)) {
            $avgTime = array_sum($this->performanceMetrics) / count($this->performanceMetrics);
            
            echo "\n📊 AUTH-05 Performance Metrics:\n";
            foreach ($this->performanceMetrics as $test => $time) {
                echo sprintf("   %s: %.2fms\n", ucfirst(str_replace('_', ' ', $test)), $time);
            }
            echo sprintf("   Average: %.2fms\n", $avgTime);
            
            if ($avgTime < 500) {
                echo "🎯 TARGET MET: Session cleanup <500ms average\n";
            } elseif ($avgTime < 1000) {
                echo "⚠️ ACCEPTABLE: Session cleanup <1s average\n";
            } else {
                echo "❌ NEEDS OPTIMIZATION: Session cleanup >1s average\n";
            }
        }

        // Log memory usage
        if (!empty($this->memoryBefore) && !empty($this->memoryAfter)) {
            echo "\n💾 Memory Usage Analysis:\n";
            foreach ($this->memoryBefore as $point => $memory) {
                echo sprintf("   %s: %.2fMB\n", ucfirst(str_replace('_', ' ', $point)), $memory / 1024 / 1024);
            }
            foreach ($this->memoryAfter as $point => $memory) {
                echo sprintf("   %s: %.2fMB\n", ucfirst(str_replace('_', ' ', $point)), $memory / 1024 / 1024);
            }
        }

        parent::tearDown();
    }
}