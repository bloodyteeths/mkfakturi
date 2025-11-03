<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Health Check Controller for Docker and Monitoring
 *
 * Provides comprehensive health check endpoints for all system components
 * CLAUDE-CHECKPOINT
 */
class HealthController extends Controller
{
    /**
     * Comprehensive health check endpoint
     */
    public function health(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'queues' => $this->checkQueues(),
            'signer' => $this->checkSigner(),
            'bank_sync' => $this->checkBankSync(),
            'storage' => $this->checkStorage(),
        ];

        $healthy = !in_array(false, $checks, true);

        return response()->json([
            'status' => $healthy ? 'healthy' : 'degraded',
            'timestamp' => now()->toIso8601String(),
            'version' => config('app.version', '1.0.0'),
            'environment' => config('app.env'),
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }

    /**
     * Check database connectivity
     */
    private function checkDatabase(): bool
    {
        try {
            DB::connection()->getPdo();
            DB::table('users')->limit(1)->count();
            return true;
        } catch (\Exception $e) {
            \Log::error('Health check: Database failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Check Redis connectivity
     */
    private function checkRedis(): bool
    {
        try {
            if (config('cache.default') !== 'redis') {
                return true;
            }
            Cache::store('redis')->put('health_check', 'ok', 10);
            $value = Cache::store('redis')->get('health_check');
            return $value === 'ok';
        } catch (\Exception $e) {
            \Log::error('Health check: Redis failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Check queue health
     */
    private function checkQueues(): bool
    {
        try {
            $pendingJobs = DB::table('jobs')->count();
            $recentFailedJobs = DB::table('failed_jobs')
                ->where('failed_at', '>=', Carbon::now()->subHour())
                ->count();

            if ($pendingJobs > 10000) {
                \Log::warning('Health check: Queue backlog too large', ['pending_jobs' => $pendingJobs]);
                return false;
            }

            if ($recentFailedJobs > 100) {
                \Log::warning('Health check: Too many recent failed jobs', ['failed_jobs_last_hour' => $recentFailedJobs]);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            \Log::error('Health check: Queue check failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Check signer certificate health
     */
    private function checkSigner(): bool
    {
        try {
            $certPath = config('mk.xml_signing.certificate_path');

            if (!$certPath || !file_exists($certPath)) {
                \Log::warning('Health check: Certificate file not found', ['path' => $certPath]);
                return false;
            }

            $certContent = file_get_contents($certPath);
            $cert = openssl_x509_read($certContent);

            if (!$cert) {
                \Log::error('Health check: Invalid certificate');
                return false;
            }

            $certInfo = openssl_x509_parse($cert);
            $expiryTimestamp = $certInfo['validTo_time_t'] ?? 0;
            $daysUntilExpiry = ($expiryTimestamp - time()) / 86400;

            if ($daysUntilExpiry <= 7) {
                \Log::warning('Health check: Certificate expiring soon', ['days_until_expiry' => round($daysUntilExpiry, 2)]);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            \Log::error('Health check: Certificate check failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Check bank sync health
     */
    private function checkBankSync(): bool
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable('bank_transactions')) {
                return true;
            }

            if (DB::getSchemaBuilder()->hasTable('telescope_entries')) {
                $syncErrors = DB::table('telescope_entries')
                    ->where('type', 'log')
                    ->where('content->level', 'error')
                    ->where('content->message', 'like', '%bank%sync%')
                    ->where('created_at', '>=', Carbon::now()->subDay())
                    ->count();

                if ($syncErrors > 10) {
                    \Log::warning('Health check: Too many bank sync errors', ['sync_errors_24h' => $syncErrors]);
                    return false;
                }
            }

            return true;
        } catch (\Exception $e) {
            \Log::error('Health check: Bank sync check failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Check storage health
     */
    private function checkStorage(): bool
    {
        try {
            $testFile = 'health_check_' . time() . '.txt';
            \Storage::put($testFile, 'health check');
            if (\Storage::exists($testFile)) {
                \Storage::delete($testFile);
                return true;
            }
            return false;
        } catch (\Exception $e) {
            \Log::error('Health check: Storage failed', ['error' => $e->getMessage()]);
            return false;
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