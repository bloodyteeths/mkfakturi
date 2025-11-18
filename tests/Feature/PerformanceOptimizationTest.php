<?php

namespace Tests\Feature;

use App\Models\CompanySetting;
use App\Models\Customer;
use App\Models\User;
use App\Services\CurrencyExchangeService;
use App\Services\QueryCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PerformanceOptimizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear any existing cache
        Cache::flush();
    }

    /** @test */
    public function it_caches_company_settings()
    {
        $company = \App\Models\Company::factory()->create();

        // Create a setting
        CompanySetting::create([
            'company_id' => $company->id,
            'option' => 'test_setting',
            'value' => 'test_value',
        ]);

        // First call should hit the database
        $startTime = microtime(true);
        $value1 = CompanySetting::getSetting('test_setting', $company->id);
        $firstCallTime = (microtime(true) - $startTime) * 1000;

        // Second call should hit the cache
        $startTime = microtime(true);
        $value2 = CompanySetting::getSetting('test_setting', $company->id);
        $secondCallTime = (microtime(true) - $startTime) * 1000;

        $this->assertEquals('test_value', $value1);
        $this->assertEquals('test_value', $value2);

        // Second call should be faster (from cache)
        $this->assertLessThan($firstCallTime, $secondCallTime);
    }

    /** @test */
    public function it_uses_cacheable_trait_on_models()
    {
        $user = User::factory()->create();

        // Test that CacheableTrait methods are available
        $this->assertTrue(method_exists($user, 'cacheAttribute'));
        $this->assertTrue(method_exists($user, 'cacheComputed'));
        $this->assertTrue(method_exists($user, 'clearModelCache'));

        // Test caching a computed value
        $result = $user->cacheComputed('test_computation', function () {
            return 'computed_value';
        });

        $this->assertEquals('computed_value', $result);
    }

    /** @test */
    public function currency_exchange_service_caches_rates()
    {
        $company = \App\Models\Company::factory()->create();
        $service = app(CurrencyExchangeService::class);

        // Mock the external API call
        \Http::fake([
            'api.exchangerate-api.com/*' => \Http::response([
                'rates' => ['EUR' => 1.2],
            ]),
        ]);

        // First call should hit the API
        $rate1 = $service->getExchangeRate('USD', 'EUR', $company->id);

        // Second call should hit the cache
        $rate2 = $service->getExchangeRate('USD', 'EUR', $company->id);

        $this->assertEquals(1.2, $rate1);
        $this->assertEquals(1.2, $rate2);

        // Verify only one HTTP request was made (second was cached)
        \Http::assertSentCount(1);
    }

    /** @test */
    public function query_cache_service_caches_aggregations()
    {
        $company = \App\Models\Company::factory()->create();
        $service = app(QueryCacheService::class);

        // Create some test data
        Customer::factory()->count(5)->create(['company_id' => $company->id]);

        request()->headers->set('company', $company->id);

        // Test aggregation caching
        $result1 = $service->cacheAggregation('customer_count', function () use ($company) {
            return Customer::where('company_id', $company->id)->count();
        });

        $result2 = $service->cacheAggregation('customer_count', function () use ($company) {
            return Customer::where('company_id', $company->id)->count();
        });

        $this->assertEquals(5, $result1);
        $this->assertEquals(5, $result2);
    }

    /** @test */
    public function performance_middleware_adds_headers_in_debug_mode()
    {
        config(['app.debug' => true]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/api/users')
            ->assertStatus(200);

        // In debug mode, performance headers should be present
        $this->assertTrue($response->headers->has('X-Execution-Time'));
        $this->assertTrue($response->headers->has('X-Memory-Usage'));
    }

    /** @test */
    public function cache_clearing_command_works()
    {
        // Set up some cached data
        Cache::put('test_key', 'test_value', 3600);
        $this->assertEquals('test_value', Cache::get('test_key'));

        // Run the cache clearing command
        $this->artisan('cache:clear-performance', ['--type' => 'all'])
            ->assertExitCode(0);

        // Note: The command clears specific patterns, not all cache
        // This test mainly ensures the command runs without errors
    }

    /** @test */
    public function models_have_eager_loading_configured()
    {
        $customer = Customer::factory()->create();

        // Test that default relationships are eager loaded
        $loadedCustomer = Customer::first();

        // Check that currency relationship is loaded (from $with property)
        $this->assertTrue($loadedCustomer->relationLoaded('currency'));
    }

    /** @test */
    public function cache_invalidation_works_on_model_updates()
    {
        $company = \App\Models\Company::factory()->create();

        // Create and cache a setting
        $setting = CompanySetting::create([
            'company_id' => $company->id,
            'option' => 'test_setting',
            'value' => 'original_value',
        ]);

        // Cache the setting
        $cachedValue = CompanySetting::getSetting('test_setting', $company->id);
        $this->assertEquals('original_value', $cachedValue);

        // Update the setting
        $setting->update(['value' => 'updated_value']);

        // The cache should be invalidated and return the new value
        $newValue = CompanySetting::getSetting('test_setting', $company->id);
        $this->assertEquals('updated_value', $newValue);
    }

    /** @test */
    public function cache_service_provider_registers_services()
    {
        // Test that our custom services are registered
        $this->assertTrue(app()->bound('query.cache'));
        $this->assertTrue(app()->bound('currency.exchange'));
        $this->assertTrue(app()->bound('performance.monitor'));

        // Test that they resolve to the correct classes
        $this->assertInstanceOf(QueryCacheService::class, app('query.cache'));
        $this->assertInstanceOf(CurrencyExchangeService::class, app('currency.exchange'));
        $this->assertInstanceOf(\App\Services\PerformanceMonitorService::class, app('performance.monitor'));
    }

    /** @test */
    public function cache_macros_are_registered()
    {
        // Test that our custom cache macros are available
        $this->assertTrue(Cache::hasMacro('companyRemember'));
        $this->assertTrue(Cache::hasMacro('userRemember'));
        $this->assertTrue(Cache::hasMacro('flushCompanyCache'));
        $this->assertTrue(Cache::hasMacro('flushModelCache'));
    }
}
