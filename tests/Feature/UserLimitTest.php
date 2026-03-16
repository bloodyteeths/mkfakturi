<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckUserLimit;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\User;
use App\Services\UserCountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserLimitTest extends TestCase
{
    use RefreshDatabase;

    // ─── UserCountService Tests ──────────────────────────────────

    /** @test */
    public function user_count_service_counts_company_users()
    {
        $owner = User::factory()->create();
        $company = Company::factory()->create(['owner_id' => $owner->id]);

        // Attach owner to company
        DB::table('user_company')->insert([
            'user_id' => $owner->id,
            'company_id' => $company->id,
        ]);

        // Add a second user
        $user2 = User::factory()->create();
        DB::table('user_company')->insert([
            'user_id' => $user2->id,
            'company_id' => $company->id,
        ]);

        $service = new UserCountService();
        $this->assertEquals(2, $service->getUserCount($company->id));
    }

    /** @test */
    public function user_count_service_excludes_partners_from_count()
    {
        $owner = User::factory()->create();
        $company = Company::factory()->create(['owner_id' => $owner->id]);

        // Attach owner
        DB::table('user_company')->insert([
            'user_id' => $owner->id,
            'company_id' => $company->id,
        ]);

        // Create a partner user and attach to company
        $partnerUser = User::factory()->create(['role' => 'partner']);
        DB::table('user_company')->insert([
            'user_id' => $partnerUser->id,
            'company_id' => $company->id,
        ]);

        // Insert partner record (makes them excluded from count)
        DB::table('partners')->insert([
            'user_id' => $partnerUser->id,
            'name' => 'Test Partner',
            'email' => 'partner-' . $partnerUser->id . '@test.com',
            'commission_rate' => 20,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = new UserCountService();
        // Only owner should count, partner excluded
        $this->assertEquals(1, $service->getUserCount($company->id));
    }

    /** @test */
    public function user_limit_returns_correct_limit_per_tier()
    {
        $service = new UserCountService();

        // Free tier
        $company = Company::factory()->create();
        CompanySubscription::factory()->active()->create([
            'company_id' => $company->id,
            'plan' => 'free',
        ]);
        $company->load('subscription');
        $this->assertEquals(1, $service->getUserLimit($company));

        // Standard tier
        $company2 = Company::factory()->create();
        CompanySubscription::factory()->active()->create([
            'company_id' => $company2->id,
            'plan' => 'standard',
        ]);
        $company2->load('subscription');
        $this->assertEquals(3, $service->getUserLimit($company2));

        // Business tier
        $company3 = Company::factory()->create();
        CompanySubscription::factory()->active()->create([
            'company_id' => $company3->id,
            'plan' => 'business',
        ]);
        $company3->load('subscription');
        $this->assertEquals(5, $service->getUserLimit($company3));

        // Max tier - unlimited
        $company4 = Company::factory()->create();
        CompanySubscription::factory()->active()->create([
            'company_id' => $company4->id,
            'plan' => 'max',
        ]);
        $company4->load('subscription');
        $this->assertNull($service->getUserLimit($company4));
    }

    /** @test */
    public function has_reached_limit_true_when_at_limit()
    {
        $owner = User::factory()->create();
        $company = Company::factory()->create(['owner_id' => $owner->id]);
        CompanySubscription::factory()->active()->create([
            'company_id' => $company->id,
            'plan' => 'free', // 1 user limit
        ]);
        $company->load('subscription');

        // Attach owner (1 user = at limit for free)
        DB::table('user_company')->insert([
            'user_id' => $owner->id,
            'company_id' => $company->id,
        ]);

        $service = new UserCountService();
        $this->assertTrue($service->hasReachedLimit($company));
    }

    /** @test */
    public function has_reached_limit_false_when_under_limit()
    {
        $owner = User::factory()->create();
        $company = Company::factory()->create(['owner_id' => $owner->id]);
        CompanySubscription::factory()->active()->create([
            'company_id' => $company->id,
            'plan' => 'standard', // 3 user limit
        ]);
        $company->load('subscription');

        // Attach owner (1 user, limit 3)
        DB::table('user_company')->insert([
            'user_id' => $owner->id,
            'company_id' => $company->id,
        ]);

        $service = new UserCountService();
        $this->assertFalse($service->hasReachedLimit($company));
    }

    /** @test */
    public function has_reached_limit_false_for_unlimited_plan()
    {
        $owner = User::factory()->create();
        $company = Company::factory()->create(['owner_id' => $owner->id]);
        CompanySubscription::factory()->active()->create([
            'company_id' => $company->id,
            'plan' => 'max',
        ]);
        $company->load('subscription');

        // Add many users
        for ($i = 0; $i < 10; $i++) {
            $user = User::factory()->create();
            DB::table('user_company')->insert([
                'user_id' => $user->id,
                'company_id' => $company->id,
            ]);
        }

        $service = new UserCountService();
        $this->assertFalse($service->hasReachedLimit($company));
    }

    /** @test */
    public function usage_stats_returns_correct_structure()
    {
        $owner = User::factory()->create();
        $company = Company::factory()->create(['owner_id' => $owner->id]);
        CompanySubscription::factory()->active()->create([
            'company_id' => $company->id,
            'plan' => 'standard',
        ]);
        $company->load('subscription');

        DB::table('user_company')->insert([
            'user_id' => $owner->id,
            'company_id' => $company->id,
        ]);

        $service = new UserCountService();
        $stats = $service->getUsageStats($company);

        $this->assertArrayHasKey('current_count', $stats);
        $this->assertArrayHasKey('limit', $stats);
        $this->assertArrayHasKey('remaining', $stats);
        $this->assertArrayHasKey('is_unlimited', $stats);
        $this->assertArrayHasKey('has_reached_limit', $stats);
        $this->assertArrayHasKey('usage_percentage', $stats);

        $this->assertEquals(1, $stats['current_count']);
        $this->assertEquals(3, $stats['limit']);
        $this->assertEquals(2, $stats['remaining']);
        $this->assertFalse($stats['is_unlimited']);
        $this->assertFalse($stats['has_reached_limit']);
    }

    // ─── CheckUserLimit Middleware Tests ─────────────────────────

    /** @test */
    public function middleware_blocks_when_user_limit_reached()
    {
        $owner = User::factory()->create(['role' => 'admin']);
        $company = Company::factory()->create(['owner_id' => $owner->id]);
        CompanySubscription::factory()->active()->create([
            'company_id' => $company->id,
            'plan' => 'free', // 1 user limit
        ]);

        // Owner already attached (at limit)
        DB::table('user_company')->insert([
            'user_id' => $owner->id,
            'company_id' => $company->id,
        ]);

        $request = Request::create('/api/v1/users', 'POST');
        $request->setUserResolver(fn () => $owner);
        $request->headers->set('company', (string) $company->id);

        $middleware = app(CheckUserLimit::class);
        $response = $middleware->handle($request, fn () => new Response('ok'));

        $this->assertEquals(402, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('user_limit_reached', $data['error']);
    }

    /** @test */
    public function middleware_allows_when_under_limit()
    {
        $owner = User::factory()->create(['role' => 'admin']);
        $company = Company::factory()->create(['owner_id' => $owner->id]);
        CompanySubscription::factory()->active()->create([
            'company_id' => $company->id,
            'plan' => 'standard', // 3 user limit
        ]);

        // 1 user, limit 3
        DB::table('user_company')->insert([
            'user_id' => $owner->id,
            'company_id' => $company->id,
        ]);

        $request = Request::create('/api/v1/users', 'POST');
        $request->setUserResolver(fn () => $owner);
        $request->headers->set('company', (string) $company->id);

        $middleware = app(CheckUserLimit::class);
        $response = $middleware->handle($request, fn () => new Response('ok'));

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function middleware_bypasses_for_super_admin()
    {
        $superAdmin = User::factory()->create(['role' => 'super admin']);
        $company = Company::factory()->create(['owner_id' => $superAdmin->id]);
        CompanySubscription::factory()->active()->create([
            'company_id' => $company->id,
            'plan' => 'free', // 1 user limit
        ]);

        // Already at limit
        DB::table('user_company')->insert([
            'user_id' => $superAdmin->id,
            'company_id' => $company->id,
        ]);

        $request = Request::create('/api/v1/users', 'POST');
        $request->setUserResolver(fn () => $superAdmin);
        $request->headers->set('company', (string) $company->id);

        $middleware = app(CheckUserLimit::class);
        $response = $middleware->handle($request, fn () => new Response('ok'));

        // Super admin should bypass, get 200
        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function middleware_bypasses_for_partner_with_access()
    {
        $partnerUser = User::factory()->create(['role' => 'partner']);
        $company = Company::factory()->create();
        CompanySubscription::factory()->active()->create([
            'company_id' => $company->id,
            'plan' => 'free', // 1 user limit
        ]);

        // Company at limit
        $existingUser = User::factory()->create();
        DB::table('user_company')->insert([
            'user_id' => $existingUser->id,
            'company_id' => $company->id,
        ]);

        // Create partner record and link
        $partnerId = DB::table('partners')->insertGetId([
            'user_id' => $partnerUser->id,
            'name' => 'Test Partner',
            'email' => 'partner-' . $partnerUser->id . '@test.com',
            'commission_rate' => 20,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('partner_company_links')->insert([
            'partner_id' => $partnerId,
            'company_id' => $company->id,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request = Request::create('/api/v1/users', 'POST');
        $request->setUserResolver(fn () => $partnerUser);
        $request->headers->set('company', (string) $company->id);

        $middleware = app(CheckUserLimit::class);
        $response = $middleware->handle($request, fn () => new Response('ok'));

        // Partner with access should bypass, get 200
        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function middleware_does_not_bypass_for_partner_without_access()
    {
        $partnerUser = User::factory()->create(['role' => 'partner']);
        $company = Company::factory()->create();
        CompanySubscription::factory()->active()->create([
            'company_id' => $company->id,
            'plan' => 'free',
        ]);

        // Company at limit
        $existingUser = User::factory()->create();
        DB::table('user_company')->insert([
            'user_id' => $existingUser->id,
            'company_id' => $company->id,
        ]);

        // Partner exists but NO link to this company
        DB::table('partners')->insert([
            'user_id' => $partnerUser->id,
            'name' => 'Test Partner',
            'email' => 'partner-' . $partnerUser->id . '@test.com',
            'commission_rate' => 20,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request = Request::create('/api/v1/users', 'POST');
        $request->setUserResolver(fn () => $partnerUser);
        $request->headers->set('company', (string) $company->id);

        $middleware = app(CheckUserLimit::class);
        $response = $middleware->handle($request, fn () => new Response('ok'));

        // Partner without access should NOT bypass
        $this->assertEquals(402, $response->getStatusCode());
    }

    /** @test */
    public function middleware_skips_non_post_requests()
    {
        $user = User::factory()->create(['role' => 'admin']);

        $request = Request::create('/api/v1/users', 'GET');
        $request->setUserResolver(fn () => $user);
        $request->headers->set('company', '1');

        $middleware = app(CheckUserLimit::class);
        $response = $middleware->handle($request, fn () => new Response('ok'));

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function middleware_returns_400_without_company_header()
    {
        $user = User::factory()->create(['role' => 'admin']);

        $request = Request::create('/api/v1/users', 'POST');
        $request->setUserResolver(fn () => $user);
        // No company header set

        $middleware = app(CheckUserLimit::class);
        $response = $middleware->handle($request, fn () => new Response('ok'));

        $this->assertEquals(400, $response->getStatusCode());
    }

    /** @test */
    public function middleware_response_includes_upgrade_info()
    {
        $owner = User::factory()->create(['role' => 'admin']);
        $company = Company::factory()->create(['owner_id' => $owner->id]);
        CompanySubscription::factory()->active()->create([
            'company_id' => $company->id,
            'plan' => 'free',
        ]);

        DB::table('user_company')->insert([
            'user_id' => $owner->id,
            'company_id' => $company->id,
        ]);

        $request = Request::create('/api/v1/users', 'POST');
        $request->setUserResolver(fn () => $owner);
        $request->headers->set('company', (string) $company->id);

        $middleware = app(CheckUserLimit::class);
        $response = $middleware->handle($request, fn () => new Response('ok'));

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('usage', $data);
        $this->assertArrayHasKey('upgrade', $data);
        $this->assertTrue($data['upgrade']['required']);
        $this->assertTrue($data['usage']['has_reached_limit']);
    }

    // ─── Cache Tests ─────────────────────────────────────────────

    /** @test */
    public function increment_cache_updates_count()
    {
        $owner = User::factory()->create();
        $company = Company::factory()->create(['owner_id' => $owner->id]);

        DB::table('user_company')->insert([
            'user_id' => $owner->id,
            'company_id' => $company->id,
        ]);

        $service = new UserCountService();

        // Prime the cache
        $count = $service->getUserCount($company->id);
        $this->assertEquals(1, $count);

        // Increment
        $service->incrementCache($company->id);

        // Cache should now be 2
        $this->assertEquals(2, $service->getUserCount($company->id));
    }

    /** @test */
    public function decrement_cache_updates_count()
    {
        $owner = User::factory()->create();
        $company = Company::factory()->create(['owner_id' => $owner->id]);

        $user2 = User::factory()->create();
        DB::table('user_company')->insert([
            ['user_id' => $owner->id, 'company_id' => $company->id],
            ['user_id' => $user2->id, 'company_id' => $company->id],
        ]);

        $service = new UserCountService();

        // Prime the cache
        $count = $service->getUserCount($company->id);
        $this->assertEquals(2, $count);

        // Decrement
        $service->decrementCache($company->id);

        // Cache should now be 1
        $this->assertEquals(1, $service->getUserCount($company->id));
    }

    /** @test */
    public function clear_cache_forces_fresh_query()
    {
        $owner = User::factory()->create();
        $company = Company::factory()->create(['owner_id' => $owner->id]);

        DB::table('user_company')->insert([
            'user_id' => $owner->id,
            'company_id' => $company->id,
        ]);

        $service = new UserCountService();

        // Prime cache
        $service->getUserCount($company->id);

        // Add user directly to DB (bypassing cache)
        $user2 = User::factory()->create();
        DB::table('user_company')->insert([
            'user_id' => $user2->id,
            'company_id' => $company->id,
        ]);

        // Still cached as 1
        $this->assertEquals(1, $service->getUserCount($company->id));

        // Clear cache
        $service->clearCache($company->id);

        // Now should be 2
        $this->assertEquals(2, $service->getUserCount($company->id));
    }

    // ─── Usage API Endpoint Test ─────────────────────────────────

    /** @test */
    public function usage_endpoint_returns_stats()
    {
        $owner = User::factory()->create(['role' => 'super admin']);
        $company = Company::factory()->create(['owner_id' => $owner->id]);
        CompanySubscription::factory()->active()->create([
            'company_id' => $company->id,
            'plan' => 'standard',
        ]);

        DB::table('user_company')->insert([
            'user_id' => $owner->id,
            'company_id' => $company->id,
        ]);

        $response = $this->actingAs($owner)
            ->withHeader('company', (string) $company->id)
            ->getJson('/api/v1/users/usage');

        $response->assertOk()
            ->assertJsonStructure([
                'usage' => [
                    'current_count',
                    'limit',
                    'remaining',
                    'is_unlimited',
                    'has_reached_limit',
                    'usage_percentage',
                ],
            ]);

        $this->assertEquals(1, $response->json('usage.current_count'));
        $this->assertEquals(3, $response->json('usage.limit'));
    }
}
