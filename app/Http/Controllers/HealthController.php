<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Health Check Controller for Docker health checks
 * 
 * Provides endpoints for monitoring application health
 */
class HealthController extends Controller
{
    /**
     * Basic health check endpoint
     */
    public function health(): JsonResponse
    {
        try {
            $checks = [
                'status' => 'ok',
                'timestamp' => now()->toISOString(),
                'version' => config('app.version', '1.0.0'),
                'environment' => config('app.env'),
                'checks' => []
            ];

            // Check database connection
            try {
                DB::connection()->getPdo();
                $checks['checks']['database'] = 'ok';
            } catch (\Exception $e) {
                $checks['checks']['database'] = 'error';
                $checks['status'] = 'error';
            }

            // Check cache connection
            try {
                Cache::put('health_check', 'ok', 60);
                $cacheValue = Cache::get('health_check');
                $checks['checks']['cache'] = $cacheValue === 'ok' ? 'ok' : 'error';
                Cache::forget('health_check');
            } catch (\Exception $e) {
                $checks['checks']['cache'] = 'error';
                $checks['status'] = 'error';
            }

            // Check storage
            try {
                $testFile = 'health_check_' . time() . '.txt';
                \Storage::put($testFile, 'health check');
                if (\Storage::exists($testFile)) {
                    \Storage::delete($testFile);
                    $checks['checks']['storage'] = 'ok';
                } else {
                    $checks['checks']['storage'] = 'error';
                    $checks['status'] = 'error';
                }
            } catch (\Exception $e) {
                $checks['checks']['storage'] = 'error';
                $checks['status'] = 'error';
            }

            $httpStatus = $checks['status'] === 'ok' ? 200 : 503;
            
            return response()->json($checks, $httpStatus);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Health check failed',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ], 503);
        }
    }

    /**
     * Readiness check - indicates if app is ready to serve traffic
     */
    public function ready(): JsonResponse
    {
        try {
            // Check if migrations are up to date
            $migrationStatus = \Artisan::call('migrate:status');
            
            return response()->json([
                'status' => 'ready',
                'timestamp' => now()->toISOString(),
                'migrations' => $migrationStatus === 0 ? 'ok' : 'pending'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'not ready',
                'message' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ], 503);
        }
    }
}