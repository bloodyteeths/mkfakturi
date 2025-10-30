<?php

namespace App\Services;

use App\Providers\CacheServiceProvider;
use Illuminate\Cache\CacheManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QueryCacheService
{
    protected CacheManager $cache;

    public function __construct(CacheManager $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Cache expensive aggregation queries
     */
    public function cacheAggregation(string $key, callable $callback, int $ttl = null): mixed
    {
        $ttl = $ttl ?: CacheServiceProvider::CACHE_TTLS['AGGREGATION'];
        $companyId = request()->header('company', 'default');
        $cacheKey = "aggregation:{$companyId}:{$key}";
        
        return $this->cache->remember($cacheKey, $ttl, $callback);
    }

    /**
     * Cache dashboard statistics
     */
    public function cacheDashboardStats(string $key, callable $callback, int $ttl = null): mixed
    {
        $ttl = $ttl ?: CacheServiceProvider::CACHE_TTLS['DASHBOARD'];
        $companyId = request()->header('company', 'default');
        $userId = auth()->id() ?? 'guest';
        $cacheKey = "dashboard:{$companyId}:{$userId}:{$key}";
        
        return $this->cache->remember($cacheKey, $ttl, $callback);
    }

    /**
     * Cache search results
     */
    public function cacheSearchResults(string $query, string $type, callable $callback, int $ttl = null): mixed
    {
        $ttl = $ttl ?: CacheServiceProvider::CACHE_TTLS['SEARCH_RESULTS'];
        $companyId = request()->header('company', 'default');
        $cacheKey = "search:{$companyId}:{$type}:" . md5($query);
        
        return $this->cache->remember($cacheKey, $ttl, $callback);
    }

    /**
     * Cache frequently accessed lists with pagination
     */
    public function cacheList(string $model, array $filters, int $page, int $perPage, callable $callback, int $ttl = null): mixed
    {
        $ttl = $ttl ?: CacheServiceProvider::CACHE_TTLS['QUERY_RESULT'];
        $companyId = request()->header('company', 'default');
        $filterHash = md5(serialize($filters));
        $cacheKey = "list:{$companyId}:{$model}:{$filterHash}:{$page}:{$perPage}";
        
        return $this->cache->remember($cacheKey, $ttl, $callback);
    }

    /**
     * Optimize and cache complex queries with joins
     */
    public function optimizeComplexQuery(Builder $query, string $cacheKey = null, int $ttl = null): Collection
    {
        $ttl = $ttl ?: CacheServiceProvider::CACHE_TTLS['QUERY_RESULT'];
        $companyId = request()->header('company', 'default');
        
        if (!$cacheKey) {
            $sql = $query->toSql();
            $bindings = $query->getBindings();
            $cacheKey = "complex_query:" . md5($sql . serialize($bindings));
        }
        
        $fullCacheKey = "query:{$companyId}:{$cacheKey}";
        
        return $this->cache->remember($fullCacheKey, $ttl, function () use ($query) {
            // Log slow queries for monitoring
            $startTime = microtime(true);
            $result = $query->get();
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            if ($executionTime > 100) { // Log queries slower than 100ms
                Log::info('Slow query detected', [
                    'execution_time_ms' => round($executionTime, 2),
                    'sql' => $query->toSql(),
                    'bindings' => $query->getBindings(),
                ]);
            }
            
            return $result;
        });
    }

    /**
     * Cache common company statistics
     */
    public function getCompanyStats(int $companyId): array
    {
        return $this->cache->remember(
            "company_stats:{$companyId}",
            CacheServiceProvider::CACHE_TTLS['DASHBOARD'],
            function () use ($companyId) {
                return [
                    'total_customers' => DB::table('customers')->where('company_id', $companyId)->count(),
                    'total_invoices' => DB::table('invoices')->where('company_id', $companyId)->count(),
                    'total_payments' => DB::table('payments')->where('company_id', $companyId)->count(),
                    'total_items' => DB::table('items')->where('company_id', $companyId)->count(),
                    'pending_invoices' => DB::table('invoices')
                        ->where('company_id', $companyId)
                        ->whereIn('status', ['DRAFT', 'SENT', 'VIEWED'])
                        ->count(),
                    'overdue_invoices' => DB::table('invoices')
                        ->where('company_id', $companyId)
                        ->where('due_date', '<', now())
                        ->whereNotIn('status', ['PAID', 'COMPLETED'])
                        ->count(),
                ];
            }
        );
    }

    /**
     * Cache user-specific data
     */
    public function cacheUserData(string $key, callable $callback, int $ttl = null): mixed
    {
        $ttl = $ttl ?: CacheServiceProvider::CACHE_TTLS['USER_SETTINGS'];
        $userId = auth()->id() ?? 'guest';
        $companyId = request()->header('company', 'default');
        $cacheKey = "user_data:{$userId}:{$companyId}:{$key}";
        
        return $this->cache->remember($cacheKey, $ttl, $callback);
    }

    /**
     * Batch load related models to prevent N+1 queries
     */
    public function batchLoadRelations(Collection $models, array $relations): Collection
    {
        foreach ($relations as $relation) {
            $models->load($relation);
        }
        
        return $models;
    }

    /**
     * Clear query cache for a specific pattern
     */
    public function clearQueryCache(string $pattern, int $companyId = null): void
    {
        $companyId = $companyId ?: request()->header('company');
        
        if ($this->cache->getStore() instanceof \Illuminate\Cache\RedisStore) {
            $redis = $this->cache->getStore()->connection();
            $searchPattern = "mkaccounting_cache:*:{$companyId}:{$pattern}*";
            $keys = $redis->keys($searchPattern);
            
            if (!empty($keys)) {
                $redis->del($keys);
                Log::info('Query cache cleared', [
                    'pattern' => $pattern,
                    'company_id' => $companyId,
                    'keys_cleared' => count($keys),
                ]);
            }
        }
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        if ($this->cache->getStore() instanceof \Illuminate\Cache\RedisStore) {
            $redis = $this->cache->getStore()->connection();
            $info = $redis->info();
            
            return [
                'redis_version' => $info['redis_version'] ?? 'unknown',
                'used_memory_human' => $info['used_memory_human'] ?? 'unknown',
                'connected_clients' => $info['connected_clients'] ?? 0,
                'total_commands_processed' => $info['total_commands_processed'] ?? 0,
                'keyspace_hits' => $info['keyspace_hits'] ?? 0,
                'keyspace_misses' => $info['keyspace_misses'] ?? 0,
                'hit_rate' => $this->calculateHitRate($info),
            ];
        }
        
        return ['message' => 'Cache statistics only available for Redis'];
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

    /**
     * Warm up commonly used cache entries
     */
    public function warmUpCommonQueries(int $companyId): void
    {
        Log::info('Starting cache warm-up', ['company_id' => $companyId]);
        
        // Warm up company stats
        $this->getCompanyStats($companyId);
        
        // Warm up recent customers
        $this->cache->remember(
            "recent_customers:{$companyId}",
            CacheServiceProvider::CACHE_TTLS['MEDIUM'],
            function () use ($companyId) {
                return DB::table('customers')
                    ->where('company_id', $companyId)
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get();
            }
        );
        
        // Warm up recent invoices
        $this->cache->remember(
            "recent_invoices:{$companyId}",
            CacheServiceProvider::CACHE_TTLS['MEDIUM'],
            function () use ($companyId) {
                return DB::table('invoices')
                    ->where('company_id', $companyId)
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get();
            }
        );
        
        Log::info('Cache warm-up completed', ['company_id' => $companyId]);
    }
}