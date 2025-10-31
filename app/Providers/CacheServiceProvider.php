<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class CacheServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register cache-related services
        $this->app->singleton('query.cache', function ($app) {
            return new \App\Services\QueryCacheService($app['cache']);
        });

        $this->app->singleton('currency.exchange', function ($app) {
            return new \App\Services\CurrencyExchangeService();
        });

        $this->app->singleton('performance.monitor', function ($app) {
            return new \App\Services\PerformanceMonitorService();
        });

        // Register aliases for easier access
        $this->app->alias('query.cache', \App\Services\QueryCacheService::class);
        $this->app->alias('currency.exchange', \App\Services\CurrencyExchangeService::class);
        $this->app->alias('performance.monitor', \App\Services\PerformanceMonitorService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->configureCacheSettings();
        $this->configureRateLimiting();
        $this->registerCacheMacros();
    }

    /**
     * Configure cache settings and strategies
     */
    protected function configureCacheSettings(): void
    {
        // Set default cache tags for better organization
        Cache::macro('rememberForever', function ($key, $callback) {
            return $this->store()->rememberForever($key, $callback);
        });

        // Company-scoped cache helper
        Cache::macro('companyRemember', function ($key, $ttl, $callback) {
            $companyId = request()->header('company', 'default');
            $cacheKey = "company:{$companyId}:{$key}";
            
            return Cache::remember($cacheKey, $ttl, $callback);
        });

        // User-scoped cache helper
        Cache::macro('userRemember', function ($key, $ttl, $callback) {
            $userId = auth()->id() ?? 'guest';
            $companyId = request()->header('company', 'default');
            $cacheKey = "user:{$userId}:company:{$companyId}:{$key}";
            
            return Cache::remember($cacheKey, $ttl, $callback);
        });

        // Model cache helper with automatic invalidation
        Cache::macro('modelRemember', function ($model, $key, $ttl, $callback) {
            $modelKey = get_class($model) . ':' . $model->getKey();
            $cacheKey = "model:{$modelKey}:{$key}";
            
            return Cache::remember($cacheKey, $ttl, $callback);
        });

        // API response cache helper
        Cache::macro('apiRemember', function ($request, $ttl, $callback) {
            $cacheKey = $this->generateApiCacheKey($request);
            
            return Cache::remember($cacheKey, $ttl, $callback);
        });
    }

    /**
     * Configure rate limiting for better performance
     */
    protected function configureRateLimiting(): void
    {
        // API rate limiting
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(300)->by($request->user()?->id ?: $request->ip());
        });

        // Migration API rate limiting (more strict for heavy operations)
        RateLimiter::for('migration', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        // Search API rate limiting
        RateLimiter::for('search', function (Request $request) {
            return Limit::perMinute(100)->by($request->user()?->id ?: $request->ip());
        });
    }

    /**
     * Register useful cache macros
     */
    protected function registerCacheMacros(): void
    {
        // Flush company-specific cache
        Cache::macro('flushCompanyCache', function ($companyId = null) {
            $companyId = $companyId ?? request()->header('company');
            $pattern = "company:{$companyId}:*";
            
            // For Redis cache store
            if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
                $redis = Cache::getStore()->connection();
                $keys = $redis->keys($pattern);
                if (!empty($keys)) {
                    $redis->del($keys);
                }
            }
            // For Array cache store (testing) - just do nothing as cache is cleared automatically
            elseif (Cache::getStore() instanceof \Illuminate\Cache\ArrayStore) {
                // ArrayStore doesn't need explicit flushing in tests as it's already isolated per test
                return;
            }
        });

        // Flush model-specific cache
        Cache::macro('flushModelCache', function ($model) {
            $modelKey = get_class($model) . ':' . $model->getKey();
            $pattern = "model:{$modelKey}:*";
            
            // For Redis cache store
            if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
                $redis = Cache::getStore()->connection();
                $keys = $redis->keys($pattern);
                if (!empty($keys)) {
                    $redis->del($keys);
                }
            }
            // For Array cache store (testing) - just do nothing as cache is cleared automatically
            elseif (Cache::getStore() instanceof \Illuminate\Cache\ArrayStore) {
                // ArrayStore doesn't need explicit flushing in tests as it's already isolated per test
                return;
            }
        });

        // Cache statistics
        Cache::macro('getStats', function () {
            if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
                $redis = Cache::getStore()->connection();
                return $redis->info('stats');
            }
            
            return ['message' => 'Statistics only available for Redis cache'];
        });
    }

    /**
     * Generate API cache key based on request
     */
    protected function generateApiCacheKey(Request $request): string
    {
        $uri = $request->getRequestUri();
        $method = $request->getMethod();
        $company = $request->header('company', 'default');
        $user = auth()->id() ?? 'guest';
        $params = $request->query();
        
        // Sort parameters for consistent cache keys
        ksort($params);
        $paramString = http_build_query($params);
        
        return "api:{$method}:{$company}:{$user}:" . md5($uri . $paramString);
    }

    /**
     * Cache configuration constants
     */
    public const CACHE_TTLS = [
        // Short-term cache (5 minutes)
        'SHORT' => 300,
        
        // Medium-term cache (1 hour)
        'MEDIUM' => 3600,
        
        // Long-term cache (24 hours)
        'LONG' => 86400,
        
        // Very long-term cache (1 week)
        'VERY_LONG' => 604800,
        
        // Settings cache (until manually invalidated)
        'SETTINGS' => 'forever',
        
        // Company settings (1 day)
        'COMPANY_SETTINGS' => 86400,
        
        // User settings (1 hour)
        'USER_SETTINGS' => 3600,
        
        // API responses (15 minutes)
        'API_RESPONSE' => 900,
        
        // Search results (30 minutes)
        'SEARCH_RESULTS' => 1800,
        
        // Field mapping rules (1 day)
        'FIELD_MAPPING' => 86400,
        
        // Migration transformation cache (1 hour)
        'MIGRATION_TRANSFORM' => 3600,
        
        // Query result cache (15 minutes)
        'QUERY_RESULT' => 900,
        
        // Aggregation cache (5 minutes)
        'AGGREGATION' => 300,
        
        // Dashboard cache (10 minutes)
        'DASHBOARD' => 600,
    ];

    /**
     * Cache tags for organized cache management
     */
    public const CACHE_TAGS = [
        'company' => 'company',
        'user' => 'user',
        'customer' => 'customer',
        'invoice' => 'invoice',
        'item' => 'item',
        'payment' => 'payment',
        'expense' => 'expense',
        'settings' => 'settings',
        'migration' => 'migration',
        'api' => 'api',
        'search' => 'search',
        'dashboard' => 'dashboard',
        'report' => 'report',
        'query' => 'query',
        'aggregation' => 'aggregation',
    ];
}
