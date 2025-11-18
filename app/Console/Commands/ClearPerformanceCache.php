<?php

namespace App\Console\Commands;

use App\Services\CurrencyExchangeService;
use App\Services\PerformanceMonitorService;
use App\Services\QueryCacheService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ClearPerformanceCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:clear-performance 
                            {--type=all : Type of cache to clear (all, query, performance, exchange, company)}
                            {--company= : Specific company ID to clear cache for}
                            {--pattern= : Specific cache pattern to clear}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear performance-related cache entries';

    protected PerformanceMonitorService $performanceMonitor;

    protected QueryCacheService $queryCacheService;

    protected CurrencyExchangeService $currencyExchangeService;

    public function __construct(
        PerformanceMonitorService $performanceMonitor,
        QueryCacheService $queryCacheService,
        CurrencyExchangeService $currencyExchangeService
    ) {
        parent::__construct();
        $this->performanceMonitor = $performanceMonitor;
        $this->queryCacheService = $queryCacheService;
        $this->currencyExchangeService = $currencyExchangeService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type');
        $companyId = $this->option('company');
        $pattern = $this->option('pattern');

        $this->info('Clearing performance cache...');

        switch ($type) {
            case 'query':
                $this->clearQueryCache($companyId, $pattern);
                break;
            case 'performance':
                $this->clearPerformanceMetrics();
                break;
            case 'exchange':
                $this->clearExchangeRateCache($companyId);
                break;
            case 'company':
                $this->clearCompanyCache($companyId);
                break;
            case 'all':
            default:
                $this->clearAllPerformanceCache($companyId);
                break;
        }

        $this->info('Performance cache cleared successfully!');

        return 0;
    }

    /**
     * Clear query cache
     */
    protected function clearQueryCache(?string $companyId, ?string $pattern): void
    {
        if ($pattern) {
            $this->queryCacheService->clearQueryCache($pattern, $companyId);
            $this->line("Cleared query cache for pattern: {$pattern}");
        } else {
            $this->queryCacheService->clearQueryCache('*', $companyId);
            $this->line('Cleared all query cache');
        }
    }

    /**
     * Clear performance metrics
     */
    protected function clearPerformanceMetrics(): void
    {
        $this->performanceMonitor->clearOldMetrics();

        // Also clear current hour metrics
        $currentHourKey = 'performance_metrics:'.date('Y-m-d-H');
        Cache::forget($currentHourKey);

        $this->line('Cleared performance metrics');
    }

    /**
     * Clear exchange rate cache
     */
    protected function clearExchangeRateCache(?string $companyId): void
    {
        if ($companyId) {
            $this->currencyExchangeService->clearAllExchangeRateCache((int) $companyId);
            $this->line("Cleared exchange rate cache for company: {$companyId}");
        } else {
            // Clear for all companies (this is more aggressive)
            if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
                $redis = Cache::getStore()->connection();
                $pattern = 'mkaccounting_cache:*:exchange_rate:*';
                $keys = $redis->keys($pattern);
                if (! empty($keys)) {
                    $redis->del($keys);
                    $this->line('Cleared exchange rate cache for all companies ('.count($keys).' keys)');
                } else {
                    $this->line('No exchange rate cache found to clear');
                }
            }
        }
    }

    /**
     * Clear company-specific cache
     */
    protected function clearCompanyCache(?string $companyId): void
    {
        if (! $companyId) {
            $this->error('Company ID is required for company cache clearing');

            return;
        }

        Cache::flushCompanyCache($companyId);
        $this->line("Cleared company cache for company: {$companyId}");
    }

    /**
     * Clear all performance-related cache
     */
    protected function clearAllPerformanceCache(?string $companyId): void
    {
        $this->clearQueryCache($companyId, null);
        $this->clearPerformanceMetrics();

        if ($companyId) {
            $this->clearExchangeRateCache($companyId);
            $this->clearCompanyCache($companyId);
        } else {
            $this->clearExchangeRateCache(null);
            $this->line('Cleared global performance cache');
        }

        // Clear additional performance-related cache patterns
        $this->clearAdditionalCachePatterns();
    }

    /**
     * Clear additional cache patterns that affect performance
     */
    protected function clearAdditionalCachePatterns(): void
    {
        if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
            $redis = Cache::getStore()->connection();

            $patterns = [
                'mkaccounting_cache:*:dashboard:*',
                'mkaccounting_cache:*:aggregation:*',
                'mkaccounting_cache:*:search:*',
                'mkaccounting_cache:*:list:*',
                'mkaccounting_cache:*:company_stats:*',
            ];

            $totalKeysCleared = 0;

            foreach ($patterns as $pattern) {
                $keys = $redis->keys($pattern);
                if (! empty($keys)) {
                    $redis->del($keys);
                    $totalKeysCleared += count($keys);
                }
            }

            if ($totalKeysCleared > 0) {
                $this->line("Cleared {$totalKeysCleared} additional cache keys");
            }
        }
    }

    /**
     * Show cache statistics before clearing
     */
    protected function showCacheStats(): void
    {
        if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
            $redis = Cache::getStore()->connection();
            $info = $redis->info();

            $this->table(
                ['Metric', 'Value'],
                [
                    ['Memory Used', $info['used_memory_human'] ?? 'unknown'],
                    ['Total Keys', $redis->dbsize()],
                    ['Keyspace Hits', $info['keyspace_hits'] ?? 0],
                    ['Keyspace Misses', $info['keyspace_misses'] ?? 0],
                    ['Hit Rate', $this->calculateHitRate($info).'%'],
                ]
            );
        }
    }

    /**
     * Calculate cache hit rate
     */
    protected function calculateHitRate(array $info): float
    {
        $hits = $info['keyspace_hits'] ?? 0;
        $misses = $info['keyspace_misses'] ?? 0;
        $total = $hits + $misses;

        return $total > 0 ? round(($hits / $total) * 100, 2) : 0.0;
    }
}
