<?php

namespace App\Http\Middleware;

use App\Services\PerformanceMonitorService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PerformanceMonitoringMiddleware
{
    protected PerformanceMonitorService $performanceMonitor;
    protected string $requestId;

    public function __construct(PerformanceMonitorService $performanceMonitor)
    {
        $this->performanceMonitor = $performanceMonitor;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip monitoring for certain routes to reduce overhead
        if ($this->shouldSkipMonitoring($request)) {
            return $next($request);
        }

        $this->requestId = uniqid('req_');
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        
        // Start monitoring
        $this->performanceMonitor->startTimer($this->requestId);
        
        // Process the request
        $response = $next($request);
        
        // End monitoring and collect metrics
        $metrics = $this->performanceMonitor->endTimer($this->requestId);
        
        // Add request context to metrics
        $fullMetrics = array_merge($metrics, [
            'method' => $request->method(),
            'url' => $request->url(),
            'route' => optional($request->route())->getName(),
            'user_id' => auth()->id(),
            'company_id' => $request->header('company'),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'response_status' => $response->getStatusCode(),
        ]);
        
        // Add performance headers for debugging
        if (config('app.debug') || $request->header('X-Debug-Performance')) {
            $response->headers->set('X-Execution-Time', $metrics['execution_time_ms'] . 'ms');
            $response->headers->set('X-Memory-Usage', $metrics['memory_used_mb'] . 'MB');
            $response->headers->set('X-Request-ID', $this->requestId);
        }
        
        // Log slow requests
        if ($metrics['execution_time_ms'] > 300) {
            Log::warning('Slow request detected', $fullMetrics);
        }
        
        // Store metrics asynchronously to avoid blocking the response
        $this->storeMetricsAsync($fullMetrics);
        
        return $response;
    }

    /**
     * Determine if monitoring should be skipped for this request
     */
    protected function shouldSkipMonitoring(Request $request): bool
    {
        // Skip monitoring for certain routes to reduce overhead
        $skipPatterns = [
            '/horizon/*',
            '/telescope/*',
            '/_debugbar/*',
            '/health',
            '/metrics',
            '/favicon.ico',
        ];
        
        foreach ($skipPatterns as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }
        
        // Skip monitoring for static assets
        $staticExtensions = ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'ico', 'woff', 'woff2', 'ttf'];
        $extension = strtolower(pathinfo($request->path(), PATHINFO_EXTENSION));
        
        return in_array($extension, $staticExtensions);
    }

    /**
     * Store metrics asynchronously (in production, this could dispatch a job)
     */
    protected function storeMetricsAsync(array $metrics): void
    {
        // For now, we'll store directly, but in production this should be queued
        try {
            $cacheKey = 'performance_metrics:' . date('Y-m-d-H');
            $existingMetrics = cache()->get($cacheKey, []);
            $existingMetrics[] = array_merge($metrics, ['timestamp' => now()->toISOString()]);
            
            // Keep only last 100 requests per hour to prevent memory issues
            if (count($existingMetrics) > 100) {
                $existingMetrics = array_slice($existingMetrics, -100);
            }
            
            cache()->put($cacheKey, $existingMetrics, 3600); // Store for 1 hour
        } catch (\Exception $e) {
            // Don't let monitoring failures break the application
            Log::error('Failed to store performance metrics', [
                'error' => $e->getMessage(),
                'request_id' => $this->requestId,
            ]);
        }
    }

    /**
     * Handle the request termination phase
     */
    public function terminate(Request $request, Response $response): void
    {
        // Perform any cleanup or additional logging after the response is sent
        // This runs after the response is sent to the client
        
        // Clean up old metrics periodically (every 100th request)
        if (rand(1, 100) === 1) {
            try {
                $this->performanceMonitor->clearOldMetrics();
            } catch (\Exception $e) {
                Log::error('Failed to clear old performance metrics', [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
