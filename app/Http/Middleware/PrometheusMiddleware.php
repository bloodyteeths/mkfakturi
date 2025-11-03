<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Arquivei\LaravelPrometheusExporter\PrometheusExporter;

/**
 * Prometheus Middleware
 *
 * Automatically collects request metrics for monitoring
 * CLAUDE-CHECKPOINT
 */
class PrometheusMiddleware
{
    protected PrometheusExporter $prometheus;

    public function __construct(PrometheusExporter $prometheus)
    {
        $this->prometheus = $prometheus;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        $this->recordMetrics($request, $response, $startTime);

        return $response;
    }

    /**
     * Record request metrics
     */
    protected function recordMetrics(Request $request, Response $response, float $startTime): void
    {
        try {
            $config = config('prometheus', []);
            
            // Skip ignored routes
            if ($this->shouldIgnoreRoute($request, $config)) {
                return;
            }

            $duration = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
            $method = $request->getMethod();
            $route = $request->route() ? $request->route()->getName() ?? $request->route()->uri() : 'unknown';
            $statusCode = $response->getStatusCode();
            $statusClass = $this->getStatusClass($statusCode);

            // Request duration histogram
            $this->prometheus->registerHistogram(
                'http_request_duration_ms',
                'HTTP request duration in milliseconds',
                ['method', 'route', 'status_class'],
                [1, 5, 10, 25, 50, 100, 250, 500, 1000, 2500, 5000, 10000]
            );
            $this->prometheus->incHistogram(
                'http_request_duration_ms',
                $duration,
                [$method, $route, $statusClass]
            );

            // Request counter
            $this->prometheus->registerCounter(
                'http_requests_total',
                'Total number of HTTP requests',
                ['method', 'route', 'status_class']
            );
            $this->prometheus->incCounter(
                'http_requests_total',
                [$method, $route, $statusClass]
            );

            // Response size histogram (if available)
            if ($response->headers->has('Content-Length')) {
                $responseSize = (int) $response->headers->get('Content-Length');
                
                $this->prometheus->registerHistogram(
                    'http_response_size_bytes',
                    'HTTP response size in bytes',
                    ['method', 'route'],
                    [100, 1000, 10000, 100000, 1000000, 10000000]
                );
                $this->prometheus->incHistogram(
                    'http_response_size_bytes',
                    $responseSize,
                    [$method, $route]
                );
            }

            // Memory usage gauge
            $memoryUsage = memory_get_usage(true);
            $this->prometheus->registerGauge(
                'php_memory_usage_bytes',
                'Current PHP memory usage in bytes'
            );
            $this->prometheus->setGauge(
                'php_memory_usage_bytes',
                $memoryUsage
            );

        } catch (\Exception $e) {
            // Silently fail to avoid disrupting the application
            \Log::debug('Failed to record Prometheus metrics', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Check if route should be ignored
     */
    protected function shouldIgnoreRoute(Request $request, array $config): bool
    {
        $ignoreRoutes = $config['ignore_routes'] ?? [];
        $ignoreMethods = $config['ignore_request_methods'] ?? [];
        
        $method = $request->getMethod();
        if (in_array($method, $ignoreMethods)) {
            return true;
        }

        $route = $request->route();
        if (!$route) {
            return false;
        }

        $routeName = $route->getName();
        $routeUri = $route->uri();

        foreach ($ignoreRoutes as $ignorePattern) {
            if ($routeName && fnmatch($ignorePattern, $routeName)) {
                return true;
            }
            if ($routeUri && fnmatch($ignorePattern, $routeUri)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get status class from status code
     */
    protected function getStatusClass(int $statusCode): string
    {
        if ($statusCode >= 200 && $statusCode < 300) {
            return '2xx';
        } elseif ($statusCode >= 300 && $statusCode < 400) {
            return '3xx';
        } elseif ($statusCode >= 400 && $statusCode < 500) {
            return '4xx';
        } elseif ($statusCode >= 500) {
            return '5xx';
        } else {
            return '1xx';
        }
    }
}