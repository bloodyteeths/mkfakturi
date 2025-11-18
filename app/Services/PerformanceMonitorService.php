<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PerformanceMonitorService
{
    protected float $startTime;

    protected array $metrics = [];

    public function __construct()
    {
        $this->startTime = microtime(true);
    }

    /**
     * Start monitoring a specific operation
     */
    public function startTimer(string $operation): void
    {
        $this->metrics[$operation] = [
            'start_time' => microtime(true),
            'memory_start' => memory_get_usage(true),
        ];
    }

    /**
     * End monitoring and record metrics
     */
    public function endTimer(string $operation): array
    {
        if (! isset($this->metrics[$operation])) {
            return [];
        }

        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);

        $executionTime = ($endTime - $this->metrics[$operation]['start_time']) * 1000; // in milliseconds
        $memoryUsed = $endMemory - $this->metrics[$operation]['memory_start'];

        $result = [
            'operation' => $operation,
            'execution_time_ms' => round($executionTime, 2),
            'memory_used_bytes' => $memoryUsed,
            'memory_used_mb' => round($memoryUsed / 1024 / 1024, 2),
        ];

        // Log slow operations
        if ($executionTime > 300) { // Operations slower than 300ms
            Log::warning('Slow operation detected', $result);
        }

        unset($this->metrics[$operation]);

        return $result;
    }

    /**
     * Monitor database query execution
     */
    public function monitorQuery(callable $callback, string $description = 'database_query'): mixed
    {
        $this->startTimer($description);

        // Enable query logging temporarily
        DB::enableQueryLog();

        $result = $callback();

        $queries = DB::getQueryLog();
        $queryMetrics = $this->endTimer($description);

        // Analyze queries for potential issues
        $this->analyzeQueries($queries, $description);

        DB::disableQueryLog();

        return $result;
    }

    /**
     * Monitor API request performance
     */
    public function monitorRequest(Request $request): void
    {
        $requestId = uniqid('req_');
        $this->startTimer($requestId);

        // Store request info for later analysis
        $this->metrics[$requestId]['request_info'] = [
            'method' => $request->method(),
            'url' => $request->url(),
            'user_id' => auth()->id(),
            'company_id' => $request->header('company'),
            'ip' => $request->ip(),
        ];
    }

    /**
     * End request monitoring
     */
    public function endRequestMonitoring(string $requestId): void
    {
        if (! isset($this->metrics[$requestId])) {
            return;
        }

        $metrics = $this->endTimer($requestId);
        $requestInfo = $this->metrics[$requestId]['request_info'] ?? [];

        $fullMetrics = array_merge($metrics, $requestInfo);

        // Store metrics for analysis
        $this->storeMetrics($fullMetrics);

        // Add response headers for debugging
        if (config('app.debug')) {
            response()->header('X-Execution-Time', $metrics['execution_time_ms'].'ms');
            response()->header('X-Memory-Usage', $metrics['memory_used_mb'].'MB');
        }
    }

    /**
     * Analyze database queries for performance issues
     */
    protected function analyzeQueries(array $queries, string $context): void
    {
        foreach ($queries as $query) {
            $executionTime = $query['time'];

            // Log slow queries
            if ($executionTime > 100) { // Queries slower than 100ms
                Log::warning('Slow query detected', [
                    'context' => $context,
                    'execution_time_ms' => $executionTime,
                    'sql' => $query['query'],
                    'bindings' => $query['bindings'],
                ]);
            }

            // Detect potential N+1 queries
            if (str_contains(strtoupper($query['query']), 'SELECT') &&
                ! str_contains(strtoupper($query['query']), 'LIMIT')) {
                $this->detectPotentialN1Query($query, $context);
            }
        }
    }

    /**
     * Detect potential N+1 query patterns
     */
    protected function detectPotentialN1Query(array $query, string $context): void
    {
        static $queryPatterns = [];

        $pattern = preg_replace('/\d+/', '?', $query['query']);

        if (! isset($queryPatterns[$pattern])) {
            $queryPatterns[$pattern] = 0;
        }

        $queryPatterns[$pattern]++;

        // If we see the same pattern more than 10 times, it might be N+1
        if ($queryPatterns[$pattern] > 10) {
            Log::warning('Potential N+1 query detected', [
                'context' => $context,
                'pattern' => $pattern,
                'count' => $queryPatterns[$pattern],
                'original_query' => $query['query'],
            ]);

            // Reset counter to avoid spam
            $queryPatterns[$pattern] = 0;
        }
    }

    /**
     * Store performance metrics
     */
    protected function storeMetrics(array $metrics): void
    {
        // Store in cache for recent metrics
        $cacheKey = 'performance_metrics:'.date('Y-m-d-H');
        $existingMetrics = Cache::get($cacheKey, []);
        $existingMetrics[] = array_merge($metrics, ['timestamp' => now()->toISOString()]);

        // Keep only last 100 requests per hour
        if (count($existingMetrics) > 100) {
            $existingMetrics = array_slice($existingMetrics, -100);
        }

        Cache::put($cacheKey, $existingMetrics, 3600); // Store for 1 hour
    }

    /**
     * Get performance metrics for analysis
     */
    public function getMetrics(string $period = 'hour'): array
    {
        switch ($period) {
            case 'hour':
                $cacheKey = 'performance_metrics:'.date('Y-m-d-H');
                break;
            case 'day':
                $metrics = [];
                for ($i = 0; $i < 24; $i++) {
                    $hour = date('Y-m-d-H', strtotime("-$i hours"));
                    $hourMetrics = Cache::get("performance_metrics:$hour", []);
                    $metrics = array_merge($metrics, $hourMetrics);
                }

                return $this->aggregateMetrics($metrics);
            default:
                $cacheKey = 'performance_metrics:'.date('Y-m-d-H');
        }

        return Cache::get($cacheKey, []);
    }

    /**
     * Aggregate metrics for analysis
     */
    protected function aggregateMetrics(array $metrics): array
    {
        if (empty($metrics)) {
            return [];
        }

        $executionTimes = array_column($metrics, 'execution_time_ms');
        $memoryUsages = array_column($metrics, 'memory_used_mb');

        return [
            'total_requests' => count($metrics),
            'avg_execution_time_ms' => round(array_sum($executionTimes) / count($executionTimes), 2),
            'max_execution_time_ms' => max($executionTimes),
            'min_execution_time_ms' => min($executionTimes),
            'avg_memory_usage_mb' => round(array_sum($memoryUsages) / count($memoryUsages), 2),
            'max_memory_usage_mb' => max($memoryUsages),
            'slow_requests' => count(array_filter($executionTimes, fn ($time) => $time > 300)),
            'endpoints' => $this->getEndpointStats($metrics),
        ];
    }

    /**
     * Get statistics per endpoint
     */
    protected function getEndpointStats(array $metrics): array
    {
        $endpointStats = [];

        foreach ($metrics as $metric) {
            $url = $metric['url'] ?? 'unknown';

            if (! isset($endpointStats[$url])) {
                $endpointStats[$url] = [
                    'count' => 0,
                    'total_time' => 0,
                    'max_time' => 0,
                ];
            }

            $endpointStats[$url]['count']++;
            $endpointStats[$url]['total_time'] += $metric['execution_time_ms'];
            $endpointStats[$url]['max_time'] = max($endpointStats[$url]['max_time'], $metric['execution_time_ms']);
        }

        // Calculate averages
        foreach ($endpointStats as $url => &$stats) {
            $stats['avg_time'] = round($stats['total_time'] / $stats['count'], 2);
            unset($stats['total_time']); // Remove to clean up response
        }

        // Sort by average time (slowest first)
        uasort($endpointStats, fn ($a, $b) => $b['avg_time'] <=> $a['avg_time']);

        return $endpointStats;
    }

    /**
     * Get current system performance
     */
    public function getSystemPerformance(): array
    {
        $loadAverage = function_exists('sys_getloadavg') ? sys_getloadavg() : [0, 0, 0];

        return [
            'memory_usage' => [
                'current_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                'peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
                'limit_mb' => ini_get('memory_limit'),
            ],
            'load_average' => [
                '1min' => $loadAverage[0] ?? 0,
                '5min' => $loadAverage[1] ?? 0,
                '15min' => $loadAverage[2] ?? 0,
            ],
            'opcache' => function_exists('opcache_get_status') ? opcache_get_status() : null,
            'cache_stats' => $this->getCachePerformance(),
        ];
    }

    /**
     * Get cache performance metrics
     */
    protected function getCachePerformance(): array
    {
        try {
            if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
                $redis = Cache::getStore()->connection();
                $info = $redis->info();

                return [
                    'type' => 'redis',
                    'hits' => $info['keyspace_hits'] ?? 0,
                    'misses' => $info['keyspace_misses'] ?? 0,
                    'hit_rate' => $this->calculateCacheHitRate($info),
                    'memory_usage' => $info['used_memory_human'] ?? 'unknown',
                ];
            }

            return ['type' => 'other', 'stats' => 'not_available'];
        } catch (\Exception $e) {
            return ['type' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Calculate cache hit rate
     */
    protected function calculateCacheHitRate(array $info): float
    {
        $hits = $info['keyspace_hits'] ?? 0;
        $misses = $info['keyspace_misses'] ?? 0;
        $total = $hits + $misses;

        return $total > 0 ? round(($hits / $total) * 100, 2) : 0.0;
    }

    /**
     * Clear old performance metrics
     */
    public function clearOldMetrics(): void
    {
        try {
            if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
                $redis = Cache::getStore()->connection();
                $pattern = 'mkaccounting_cache:performance_metrics:*';

                // Use scan instead of keys for better performance and compatibility
                $cursor = '0';
                do {
                    $result = $redis->scan($cursor, ['match' => $pattern, 'count' => 100]);
                    if ($result === false) {
                        break;
                    }

                    [$cursor, $keys] = $result;

                    foreach ($keys as $key) {
                        // Keep only metrics from last 24 hours
                        $timestamp = str_replace('mkaccounting_cache:performance_metrics:', '', $key);
                        if (strtotime($timestamp) < strtotime('-24 hours')) {
                            $redis->del($key);
                        }
                    }
                } while ($cursor !== '0');
            }
        } catch (\Exception $e) {
            // Silently fail - performance monitoring shouldn't break the app
            \Log::debug('Failed to clear old performance metrics: '.$e->getMessage());
        }
    }
}
