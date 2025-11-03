<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Arquivei\LaravelPrometheusExporter\PrometheusExporter;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Customer;
use App\Models\Company;
use Carbon\Carbon;

/**
 * Prometheus Metrics Controller
 *
 * Exposes application metrics in Prometheus format for monitoring
 * Includes business metrics, system health, and performance indicators
 * CLAUDE-CHECKPOINT
 */
class PrometheusController extends Controller
{
    protected PrometheusExporter $prometheus;

    public function __construct(PrometheusExporter $prometheus)
    {
        $this->prometheus = $prometheus;
    }

    /**
     * Export metrics in Prometheus format
     */
    public function metrics(Request $request): Response
    {
        try {
            // Clear previous metrics to avoid duplicates
            $this->prometheus->clear();

            // Collect business metrics
            $this->collectBusinessMetrics();
            
            // Collect system health metrics
            $this->collectSystemHealthMetrics();
            
            // Collect banking integration metrics
            $this->collectBankingMetrics();
            
            // Collect performance metrics
            $this->collectPerformanceMetrics();

            // Export metrics
            $metrics = $this->prometheus->export();

            return response($metrics, 200, [
                'Content-Type' => 'text/plain; version=0.0.4; charset=utf-8'
            ]);

        } catch (\Exception $e) {
            \Log::error('Prometheus metrics collection failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response('# Error collecting metrics', 500, [
                'Content-Type' => 'text/plain'
            ]);
        }
    }

    /**
     * Collect business-related metrics
     */
    protected function collectBusinessMetrics(): void
    {
        // Total invoices by status
        $invoicesByStatus = Invoice::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        foreach ($invoicesByStatus as $stat) {
            $this->prometheus->registerGauge(
                'invoiceshelf_invoices_total',
                'Total number of invoices by status',
                ['status']
            );
            $this->prometheus->setGauge(
                'invoiceshelf_invoices_total',
                $stat->count,
                [$stat->status]
            );
        }

        // Revenue metrics (last 30 days)
        $revenueData = Payment::where('created_at', '>=', Carbon::now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, SUM(amount) as daily_revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $totalRevenue30Days = $revenueData->sum('daily_revenue');
        
        $this->prometheus->registerGauge(
            'invoiceshelf_revenue_30_days_total',
            'Total revenue in last 30 days'
        );
        $this->prometheus->setGauge(
            'invoiceshelf_revenue_30_days_total',
            $totalRevenue30Days
        );

        // Customer metrics
        $totalCustomers = Customer::count();
        $activeCustomers = Customer::whereHas('invoices', function ($query) {
            $query->where('created_at', '>=', Carbon::now()->subDays(90));
        })->count();

        $this->prometheus->registerGauge(
            'invoiceshelf_customers_total',
            'Total number of customers'
        );
        $this->prometheus->setGauge(
            'invoiceshelf_customers_total',
            $totalCustomers
        );

        $this->prometheus->registerGauge(
            'invoiceshelf_customers_active',
            'Number of active customers (with invoices in last 90 days)'
        );
        $this->prometheus->setGauge(
            'invoiceshelf_customers_active',
            $activeCustomers
        );

        // Overdue invoices
        $overdueInvoices = Invoice::where('status', 'SENT')
            ->where('due_date', '<', Carbon::now())
            ->count();

        $this->prometheus->registerGauge(
            'invoiceshelf_invoices_overdue',
            'Number of overdue invoices'
        );
        $this->prometheus->setGauge(
            'invoiceshelf_invoices_overdue',
            $overdueInvoices
        );

        // Companies count
        $totalCompanies = Company::count();
        
        $this->prometheus->registerGauge(
            'invoiceshelf_companies_total',
            'Total number of companies'
        );
        $this->prometheus->setGauge(
            'invoiceshelf_companies_total',
            $totalCompanies
        );
    }

    /**
     * Collect system health metrics
     */
    protected function collectSystemHealthMetrics(): void
    {
        // Database connection check
        try {
            DB::connection()->getPdo();
            $dbHealthy = 1;
        } catch (\Exception $e) {
            $dbHealthy = 0;
        }

        $this->prometheus->registerGauge(
            'invoiceshelf_database_healthy',
            '1 if database is healthy, 0 otherwise'
        );
        $this->prometheus->setGauge(
            'invoiceshelf_database_healthy',
            $dbHealthy
        );

        // Cache check
        try {
            Cache::put('health_check', 'ok', 10);
            $cacheValue = Cache::get('health_check');
            $cacheHealthy = ($cacheValue === 'ok') ? 1 : 0;
        } catch (\Exception $e) {
            $cacheHealthy = 0;
        }

        $this->prometheus->registerGauge(
            'invoiceshelf_cache_healthy',
            '1 if cache is healthy, 0 otherwise'
        );
        $this->prometheus->setGauge(
            'invoiceshelf_cache_healthy',
            $cacheHealthy
        );

        // Disk space (storage folder)
        $storagePath = storage_path();
        $totalSpace = disk_total_space($storagePath);
        $freeSpace = disk_free_space($storagePath);
        $usedSpace = $totalSpace - $freeSpace;
        $diskUsagePercent = ($totalSpace > 0) ? ($usedSpace / $totalSpace) * 100 : 0;

        $this->prometheus->registerGauge(
            'invoiceshelf_disk_usage_percent',
            'Disk usage percentage for storage'
        );
        $this->prometheus->setGauge(
            'invoiceshelf_disk_usage_percent',
            $diskUsagePercent
        );

        // Memory usage
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));
        $memoryUsagePercent = ($memoryLimit > 0) ? ($memoryUsage / $memoryLimit) * 100 : 0;

        $this->prometheus->registerGauge(
            'invoiceshelf_memory_usage_bytes',
            'Current memory usage in bytes'
        );
        $this->prometheus->setGauge(
            'invoiceshelf_memory_usage_bytes',
            $memoryUsage
        );

        $this->prometheus->registerGauge(
            'invoiceshelf_memory_usage_percent',
            'Memory usage percentage'
        );
        $this->prometheus->setGauge(
            'invoiceshelf_memory_usage_percent',
            $memoryUsagePercent
        );

        // Certificate expiry check
        $this->checkCertificateExpiry();
    }

    /**
     * Check certificate expiry and update metrics
     */
    protected function checkCertificateExpiry(): void
    {
        try {
            $certPath = config('mk.xml_signing.certificate_path');

            if (!$certPath || !file_exists($certPath)) {
                // No certificate or file doesn't exist
                $this->prometheus->registerGauge(
                    'fakturino_signer_cert_expiry_days',
                    'Days until signer certificate expires'
                );
                $this->prometheus->setGauge(
                    'fakturino_signer_cert_expiry_days',
                    -1 // Indicate missing certificate
                );
                return;
            }

            $certContent = file_get_contents($certPath);
            $cert = openssl_x509_read($certContent);

            if (!$cert) {
                $this->prometheus->registerGauge(
                    'fakturino_signer_cert_expiry_days',
                    'Days until signer certificate expires'
                );
                $this->prometheus->setGauge(
                    'fakturino_signer_cert_expiry_days',
                    -1 // Indicate invalid certificate
                );
                return;
            }

            $certInfo = openssl_x509_parse($cert);
            $expiryTimestamp = $certInfo['validTo_time_t'] ?? 0;
            $daysUntilExpiry = ($expiryTimestamp - time()) / 86400;

            $this->prometheus->registerGauge(
                'fakturino_signer_cert_expiry_days',
                'Days until signer certificate expires'
            );
            $this->prometheus->setGauge(
                'fakturino_signer_cert_expiry_days',
                round($daysUntilExpiry, 2)
            );

            // Also track certificate health (1 = healthy, 0 = expiring soon/expired)
            $certHealthy = $daysUntilExpiry > 7 ? 1 : 0;

            $this->prometheus->registerGauge(
                'fakturino_signer_cert_healthy',
                '1 if certificate is healthy (more than 7 days until expiry), 0 otherwise'
            );
            $this->prometheus->setGauge(
                'fakturino_signer_cert_healthy',
                $certHealthy
            );

        } catch (\Exception $e) {
            \Log::warning('Failed to check certificate expiry', [
                'error' => $e->getMessage()
            ]);

            // Set to -1 to indicate error
            $this->prometheus->registerGauge(
                'fakturino_signer_cert_expiry_days',
                'Days until signer certificate expires'
            );
            $this->prometheus->setGauge(
                'fakturino_signer_cert_expiry_days',
                -1
            );
        }
    }

    /**
     * Collect banking integration metrics
     */
    protected function collectBankingMetrics(): void
    {
        // Bank transactions (last 24 hours)
        $bankTransactions24h = DB::table('bank_transactions')
            ->where('created_at', '>=', Carbon::now()->subDays(1))
            ->count();

        $this->prometheus->registerGauge(
            'invoiceshelf_bank_transactions_24h',
            'Number of bank transactions synced in last 24 hours'
        );
        $this->prometheus->setGauge(
            'invoiceshelf_bank_transactions_24h',
            $bankTransactions24h
        );

        // Matched vs unmatched transactions
        $matchedTransactions = DB::table('bank_transactions')
            ->whereNotNull('matched_invoice_id')
            ->count();

        $unmatchedTransactions = DB::table('bank_transactions')
            ->whereNull('matched_invoice_id')
            ->count();

        $totalTransactions = $matchedTransactions + $unmatchedTransactions;
        $matchRate = $totalTransactions > 0 ? ($matchedTransactions / $totalTransactions) * 100 : 0;

        $this->prometheus->registerGauge(
            'invoiceshelf_bank_transactions_matched',
            'Number of matched bank transactions'
        );
        $this->prometheus->setGauge(
            'invoiceshelf_bank_transactions_matched',
            $matchedTransactions
        );

        $this->prometheus->registerGauge(
            'invoiceshelf_bank_transactions_unmatched',
            'Number of unmatched bank transactions'
        );
        $this->prometheus->setGauge(
            'invoiceshelf_bank_transactions_unmatched',
            $unmatchedTransactions
        );

        $this->prometheus->registerGauge(
            'invoiceshelf_bank_match_rate_percent',
            'Percentage of bank transactions successfully matched'
        );
        $this->prometheus->setGauge(
            'invoiceshelf_bank_match_rate_percent',
            $matchRate
        );

        // Bank sync errors (from logs - last 24 hours)
        $syncErrors = DB::table('telescope_entries')
            ->where('type', 'log')
            ->where('content->level', 'error')
            ->where('content->message', 'like', '%sync%')
            ->where('created_at', '>=', Carbon::now()->subDays(1))
            ->count();

        $this->prometheus->registerGauge(
            'invoiceshelf_bank_sync_errors_24h',
            'Number of bank sync errors in last 24 hours'
        );
        $this->prometheus->setGauge(
            'invoiceshelf_bank_sync_errors_24h',
            $syncErrors
        );
    }

    /**
     * Collect performance metrics
     */
    protected function collectPerformanceMetrics(): void
    {
        // Average response time (from Telescope, last hour)
        $avgResponseTime = 0;
        try {
            $responseTimeData = DB::table('telescope_entries')
                ->where('type', 'request')
                ->where('created_at', '>=', Carbon::now()->subHour())
                ->selectRaw('AVG(JSON_EXTRACT(content, "$.duration")) as avg_duration')
                ->first();
            
            $avgResponseTime = $responseTimeData->avg_duration ?? 0;
        } catch (\Exception $e) {
            // Telescope might not be installed yet
        }

        $this->prometheus->registerGauge(
            'invoiceshelf_avg_response_time_ms',
            'Average response time in milliseconds (last hour)'
        );
        $this->prometheus->setGauge(
            'invoiceshelf_avg_response_time_ms',
            $avgResponseTime
        );

        // Queue jobs pending
        $pendingJobs = DB::table('jobs')->count();

        $this->prometheus->registerGauge(
            'invoiceshelf_queue_jobs_pending',
            'Number of pending queue jobs'
        );
        $this->prometheus->setGauge(
            'invoiceshelf_queue_jobs_pending',
            $pendingJobs
        );

        // Failed jobs
        $failedJobs = DB::table('failed_jobs')->count();

        $this->prometheus->registerGauge(
            'invoiceshelf_queue_jobs_failed',
            'Number of failed queue jobs'
        );
        $this->prometheus->setGauge(
            'invoiceshelf_queue_jobs_failed',
            $failedJobs
        );

        // Application uptime (using app start time from cache)
        $appStartTime = Cache::remember('app_start_time', 86400, function () {
            return time();
        });
        $uptime = time() - $appStartTime;

        $this->prometheus->registerGauge(
            'invoiceshelf_uptime_seconds',
            'Application uptime in seconds'
        );
        $this->prometheus->setGauge(
            'invoiceshelf_uptime_seconds',
            $uptime
        );
    }

    /**
     * Parse memory limit string to bytes
     */
    protected function parseMemoryLimit(string $memoryLimit): int
    {
        if ($memoryLimit === '-1') {
            return 0; // Unlimited
        }

        $unit = strtolower(substr($memoryLimit, -1));
        $value = (int) substr($memoryLimit, 0, -1);

        switch ($unit) {
            case 'g':
                return $value * 1024 * 1024 * 1024;
            case 'm':
                return $value * 1024 * 1024;
            case 'k':
                return $value * 1024;
            default:
                return (int) $memoryLimit;
        }
    }

    /**
     * Health check endpoint for load balancers
     */
    public function health(): Response
    {
        $health = [
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'checks' => []
        ];

        // Database check
        try {
            DB::connection()->getPdo();
            $health['checks']['database'] = 'healthy';
        } catch (\Exception $e) {
            $health['checks']['database'] = 'unhealthy';
            $health['status'] = 'unhealthy';
        }

        // Cache check
        try {
            Cache::put('health_check', 'ok', 10);
            $cacheValue = Cache::get('health_check');
            $health['checks']['cache'] = ($cacheValue === 'ok') ? 'healthy' : 'unhealthy';
        } catch (\Exception $e) {
            $health['checks']['cache'] = 'unhealthy';
            $health['status'] = 'unhealthy';
        }

        $statusCode = ($health['status'] === 'healthy') ? 200 : 503;

        return response()->json($health, $statusCode);
    }
}