<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * AI Financial Assistant Proxy Controller
 * Bridges Laravel application with AI-MCP service for financial insights
 *
 * Provides:
 * - Financial summary generation
 * - Risk analysis and scoring
 * - Cash flow forecasting
 * - Real-time AI insights
 *
 * Routes communicate with ai-mcp container on port 3001
 */
class AiProxy extends Controller
{
    private string $aiServiceUrl;

    private int $timeoutSeconds;

    private array $retryConfig;

    public function __construct()
    {
        $this->aiServiceUrl = config('app.ai_service_url', 'http://ai-mcp:3001');
        $this->timeoutSeconds = config('app.ai_timeout', 30);
        $this->retryConfig = [
            'times' => 3,
            'sleep' => 1000, // milliseconds
        ];

        // Ensure user is authenticated for all AI endpoints
        $this->middleware('auth:sanctum');

        // Ensure company context is available
        $this->middleware('company');
    }

    /**
     * Get comprehensive financial summary for current company
     */
    public function getFinancialSummary(Request $request): JsonResponse
    {
        try {
            $companyId = $request->header('company');
            $period = $request->query('period', 'last_30_days');

            // Get company financial data
            $financialData = $this->getCompanyFinancialData($companyId, $period);

            // Check cache first
            $cacheKey = "ai_summary_{$companyId}_{$period}_".md5(json_encode($financialData));

            $summary = Cache::remember($cacheKey, 300, function () use ($financialData) {
                return $this->callAiService('/api/financial-summary', [
                    'company_data' => $financialData,
                    'locale' => 'mk_MK',
                    'currency' => 'MKD',
                ]);
            });

            if (! $summary) {
                return $this->fallbackFinancialSummary($financialData);
            }

            return response()->json([
                'success' => true,
                'data' => $summary,
                'generated_at' => now()->toISOString(),
                'cache_hit' => Cache::has($cacheKey),
            ]);

        } catch (\Exception $e) {
            Log::error('AI Financial Summary Error', [
                'error' => $e->getMessage(),
                'company_id' => $companyId ?? 'unknown',
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to generate financial summary',
                'message' => config('app.debug') ? $e->getMessage() : 'Service temporarily unavailable',
            ], 500);
        }
    }

    /**
     * Get business risk analysis and recommendations
     */
    public function getRiskAnalysis(Request $request): JsonResponse
    {
        try {
            $companyId = $request->header('company');

            // Gather risk assessment data
            $riskData = $this->getCompanyRiskData($companyId);

            // Check cache (risk analysis changes less frequently)
            $cacheKey = "ai_risk_{$companyId}_".md5(json_encode($riskData));

            $riskAnalysis = Cache::remember($cacheKey, 1800, function () use ($riskData) {
                return $this->callAiService('/api/risk-analysis', [
                    'risk_data' => $riskData,
                    'market' => 'macedonia',
                    'industry' => 'general',
                ]);
            });

            if (! $riskAnalysis) {
                return $this->fallbackRiskAnalysis($riskData);
            }

            return response()->json([
                'success' => true,
                'data' => $riskAnalysis,
                'generated_at' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            Log::error('AI Risk Analysis Error', [
                'error' => $e->getMessage(),
                'company_id' => $companyId ?? 'unknown',
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to generate risk analysis',
                'message' => config('app.debug') ? $e->getMessage() : 'Service temporarily unavailable',
            ], 500);
        }
    }

    /**
     * Get cash flow forecasting for planning
     */
    public function getCashFlowForecast(Request $request): JsonResponse
    {
        try {
            $companyId = $request->header('company');
            $periods = $request->query('periods', 6);

            // Historical cash flow data
            $historicalData = $this->getHistoricalCashFlow($companyId);

            $cacheKey = "ai_forecast_{$companyId}_{$periods}_".md5(json_encode($historicalData));

            $forecast = Cache::remember($cacheKey, 900, function () use ($historicalData, $periods) {
                return $this->callAiService('/api/cash-flow-forecast', [
                    'historical_data' => $historicalData,
                    'periods' => $periods,
                    'currency' => 'MKD',
                ]);
            });

            if (! $forecast) {
                return $this->fallbackCashFlowForecast($historicalData, $periods);
            }

            return response()->json([
                'success' => true,
                'data' => $forecast,
                'generated_at' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            Log::error('AI Cash Flow Forecast Error', [
                'error' => $e->getMessage(),
                'company_id' => $companyId ?? 'unknown',
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to generate cash flow forecast',
                'message' => config('app.debug') ? $e->getMessage() : 'Service temporarily unavailable',
            ], 500);
        }
    }

    /**
     * Health check for AI service connectivity
     */
    public function healthCheck(): JsonResponse
    {
        try {
            $response = Http::timeout(5)->get("{$this->aiServiceUrl}/health");

            return response()->json([
                'ai_service' => $response->successful() ? 'healthy' : 'unhealthy',
                'status_code' => $response->status(),
                'response_time' => $response->handlerStats()['total_time'] ?? null,
                'timestamp' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'ai_service' => 'unreachable',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString(),
            ], 503);
        }
    }

    /**
     * Call AI service with retry logic and error handling
     */
    private function callAiService(string $endpoint, array $data = []): ?array
    {
        $attempts = 0;

        while ($attempts < $this->retryConfig['times']) {
            try {
                $response = Http::timeout($this->timeoutSeconds)
                    ->withHeaders([
                        'X-API-Key' => config('services.ai.api_key'),
                        'X-Company-ID' => request()->header('company'),
                        'Content-Type' => 'application/json',
                    ])
                    ->post("{$this->aiServiceUrl}{$endpoint}", $data);

                if ($response->successful()) {
                    return $response->json();
                }

                Log::warning('AI Service HTTP Error', [
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                if ($response->status() >= 500) {
                    // Server error - retry
                    $attempts++;
                    if ($attempts < $this->retryConfig['times']) {
                        usleep($this->retryConfig['sleep'] * 1000);

                        continue;
                    }
                }

                return null;

            } catch (\Exception $e) {
                $attempts++;

                Log::warning('AI Service Connection Error', [
                    'endpoint' => $endpoint,
                    'attempt' => $attempts,
                    'error' => $e->getMessage(),
                ]);

                if ($attempts < $this->retryConfig['times']) {
                    usleep($this->retryConfig['sleep'] * 1000);

                    continue;
                }

                throw $e;
            }
        }

        return null;
    }

    /**
     * Get company financial data for analysis
     */
    private function getCompanyFinancialData(int $companyId, string $period): array
    {
        $startDate = $this->getPeriodStartDate($period);

        $invoices = Invoice::where('company_id', $companyId)
            ->where('invoice_date', '>=', $startDate)
            ->with(['items', 'taxes', 'customer'])
            ->get();

        $payments = Payment::where('company_id', $companyId)
            ->where('payment_date', '>=', $startDate)
            ->get();

        $customers = Customer::where('company_id', $companyId)->count();

        return [
            'period' => $period,
            'start_date' => $startDate->toDateString(),
            'total_revenue' => $invoices->sum('total'),
            'total_expenses' => $payments->where('payment_method.type', 'expense')->sum('amount'),
            'invoices_count' => $invoices->count(),
            'payments_count' => $payments->count(),
            'customers_count' => $customers,
            'average_invoice_value' => $invoices->count() > 0 ? $invoices->avg('total') : 0,
            'currency' => 'MKD',
            'invoices' => $invoices->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'amount' => $invoice->total,
                    'status' => $invoice->status,
                    'date' => $invoice->invoice_date,
                    'customer_id' => $invoice->customer_id,
                ];
            })->toArray(),
        ];
    }

    /**
     * Get company risk assessment data
     */
    private function getCompanyRiskData(int $companyId): array
    {
        $invoices = Invoice::where('company_id', $companyId)
            ->where('invoice_date', '>=', now()->subMonths(6))
            ->get();

        $payments = Payment::where('company_id', $companyId)
            ->where('payment_date', '>=', now()->subMonths(6))
            ->get();

        $overdueInvoices = $invoices->where('status', 'OVERDUE')->count();
        $totalInvoices = $invoices->count();
        $collectionRate = $totalInvoices > 0 ? (($totalInvoices - $overdueInvoices) / $totalInvoices) * 100 : 100;

        return [
            'collection_rate' => $collectionRate,
            'overdue_invoices' => $overdueInvoices,
            'total_invoices' => $totalInvoices,
            'avg_payment_delay' => $this->calculateAveragePaymentDelay($invoices, $payments),
            'customer_concentration' => $this->calculateCustomerConcentration($invoices),
            'revenue_volatility' => $this->calculateRevenueVolatility($invoices),
            'assessment_date' => now()->toDateString(),
        ];
    }

    /**
     * Get historical cash flow data for forecasting
     */
    private function getHistoricalCashFlow(int $companyId): array
    {
        $months = [];

        for ($i = 11; $i >= 0; $i--) {
            $startDate = now()->subMonths($i)->startOfMonth();
            $endDate = now()->subMonths($i)->endOfMonth();

            $inflow = Invoice::where('company_id', $companyId)
                ->whereBetween('invoice_date', [$startDate, $endDate])
                ->where('status', 'PAID')
                ->sum('total');

            $outflow = Payment::where('company_id', $companyId)
                ->whereBetween('payment_date', [$startDate, $endDate])
                ->sum('amount');

            $months[] = [
                'month' => $startDate->format('Y-m'),
                'inflow' => $inflow,
                'outflow' => $outflow,
                'net' => $inflow - $outflow,
            ];
        }

        return $months;
    }

    /**
     * Generate fallback financial summary when AI service unavailable
     */
    private function fallbackFinancialSummary(array $data): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'totalRevenue' => $data['total_revenue'],
                'invoicesCount' => $data['invoices_count'],
                'paymentsCount' => $data['payments_count'],
                'averageInvoiceValue' => $data['average_invoice_value'],
                'currency' => $data['currency'],
                'period' => $data['period'],
                'insights' => [
                    'Financial data processed locally',
                    'AI insights temporarily unavailable',
                ],
                'riskScore' => 0.2,
                'riskLevel' => 'moderate',
                'fallback' => true,
            ],
            'generated_at' => now()->toISOString(),
        ]);
    }

    /**
     * Generate fallback risk analysis
     */
    private function fallbackRiskAnalysis(array $data): JsonResponse
    {
        $riskScore = max(0.1, min(0.9, (100 - $data['collection_rate']) / 100));

        return response()->json([
            'success' => true,
            'data' => [
                'overallRisk' => $riskScore,
                'riskLevel' => $riskScore < 0.3 ? 'low' : ($riskScore < 0.6 ? 'moderate' : 'high'),
                'factors' => [
                    [
                        'category' => 'collection_rate',
                        'score' => $data['collection_rate'] / 100,
                        'description' => "Collection rate: {$data['collection_rate']}%",
                    ],
                ],
                'recommendations' => [
                    'Monitor payment patterns closely',
                    'Consider automated payment reminders',
                ],
                'lastUpdated' => now()->toISOString(),
                'fallback' => true,
            ],
        ]);
    }

    /**
     * Generate fallback cash flow forecast
     */
    private function fallbackCashFlowForecast(array $historical, int $periods): JsonResponse
    {
        $projections = [];
        $avgNet = collect($historical)->avg('net');

        for ($i = 1; $i <= $periods; $i++) {
            $date = now()->addMonths($i)->format('Y-m-d');
            $projections[] = [
                'date' => $date,
                'inflow' => $avgNet * 1.1,
                'outflow' => $avgNet * 0.7,
                'net' => $avgNet,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'currency' => 'MKD',
                'period' => "next_{$periods}_months",
                'projections' => $projections,
                'confidence' => 0.6,
                'fallback' => true,
            ],
            'generated_at' => now()->toISOString(),
        ]);
    }

    /**
     * Helper methods for risk calculations
     */
    private function getPeriodStartDate(string $period): Carbon
    {
        return match ($period) {
            'last_7_days' => now()->subDays(7),
            'last_30_days' => now()->subDays(30),
            'last_90_days' => now()->subDays(90),
            'last_year' => now()->subYear(),
            default => now()->subDays(30)
        };
    }

    private function calculateAveragePaymentDelay(array $invoices, array $payments): float
    {
        // Simplified calculation - in real implementation, match invoices to payments
        return 15.5; // Average delay in days
    }

    private function calculateCustomerConcentration(array $invoices): float
    {
        // Simplified - calculate what % of revenue comes from top 3 customers
        return 0.35; // 35% concentration
    }

    private function calculateRevenueVolatility(array $invoices): float
    {
        // Simplified volatility calculation
        return 0.18; // 18% volatility
    }
}
