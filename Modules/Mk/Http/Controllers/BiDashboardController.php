<?php

namespace Modules\Mk\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Mk\Services\FinancialRatioService;

class BiDashboardController extends Controller
{
    protected FinancialRatioService $service;

    public function __construct(FinancialRatioService $service)
    {
        $this->service = $service;
    }

    /**
     * Get financial ratios for a given date.
     */
    public function ratios(Request $request): JsonResponse
    {
        $companyId = (int) $request->header('company');

        if (! $companyId) {
            return response()->json(['error' => 'Company header required'], 400);
        }

        if (! $this->service->isInitialized($companyId)) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'accounting_not_initialized',
            ]);
        }

        $date = $request->query('date', Carbon::now()->endOfMonth()->toDateString());

        try {
            $ratios = $this->service->computeAllRatios($companyId, $date);

            return response()->json([
                'success' => true,
                'data' => $ratios,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get ratio trends over time.
     */
    public function trends(Request $request): JsonResponse
    {
        $companyId = (int) $request->header('company');

        if (! $companyId) {
            return response()->json(['error' => 'Company header required'], 400);
        }

        if (! $this->service->isInitialized($companyId)) {
            return response()->json([
                'success' => true,
                'data' => ['ratio_type' => $request->query('ratio_type', 'current_ratio'), 'months' => 12, 'trends' => []],
                'message' => 'accounting_not_initialized',
            ]);
        }

        $ratioType = $request->query('ratio_type', 'current_ratio');
        $months = (int) $request->query('months', 12);

        if ($months < 1 || $months > 60) {
            $months = 12;
        }

        try {
            $trends = $this->service->getTrends($companyId, $ratioType, $months);

            return response()->json([
                'success' => true,
                'data' => [
                    'ratio_type' => $ratioType,
                    'months' => $months,
                    'trends' => $trends,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get full summary with all ratio groups and health indicators.
     */
    public function summary(Request $request): JsonResponse
    {
        $companyId = (int) $request->header('company');

        if (! $companyId) {
            return response()->json(['error' => 'Company header required'], 400);
        }

        if (! $this->service->isInitialized($companyId)) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'accounting_not_initialized',
            ]);
        }

        $date = $request->query('date', Carbon::now()->endOfMonth()->toDateString());

        try {
            $allData = $this->service->computeAllRatios($companyId, $date);
            $raw = $allData['raw'] ?? [];
            unset($allData['raw']);

            $healthIndicators = $this->buildHealthIndicators($allData);

            return response()->json([
                'success' => true,
                'data' => [
                    'date' => $date,
                    'ratios' => $allData,
                    'health' => $healthIndicators,
                    'raw' => $raw,
                    'last_calculated_at' => $this->service->getLastCachedAt($companyId),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get comparative ratios: current period vs same period last year.
     */
    public function comparative(Request $request): JsonResponse
    {
        $companyId = (int) $request->header('company');

        if (! $companyId) {
            return response()->json(['error' => 'Company header required'], 400);
        }

        if (! $this->service->isInitialized($companyId)) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'accounting_not_initialized',
            ]);
        }

        $date = $request->query('date', Carbon::now()->endOfMonth()->toDateString());

        try {
            $data = $this->service->computeComparativeRatios($companyId, $date);

            $currentRaw = $data['current']['raw'] ?? [];
            unset($data['current']['raw']);
            $priorRaw = $data['prior']['raw'] ?? [];
            unset($data['prior']['raw']);

            return response()->json([
                'success' => true,
                'data' => [
                    'current_date' => $date,
                    'prior_date' => $data['prior_date'],
                    'current' => [
                        'ratios' => $data['current'],
                        'health' => $this->buildHealthIndicators($data['current']),
                        'raw' => $currentRaw,
                    ],
                    'prior' => [
                        'ratios' => $data['prior'],
                        'health' => $this->buildHealthIndicators($data['prior']),
                        'raw' => $priorRaw,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Export BI dashboard as PDF.
     */
    public function exportPdf(Request $request): \Illuminate\Http\Response
    {
        $companyId = (int) $request->header('company');

        if (! $companyId) {
            abort(400, 'Company header required');
        }

        if (! $this->service->isInitialized($companyId)) {
            abort(404, 'Accounting not initialized');
        }

        $date = $request->query('date', Carbon::now()->endOfMonth()->toDateString());
        $type = $request->query('type', 'summary');

        $company = \App\Models\Company::find($companyId);
        $allData = $this->service->computeAllRatios($companyId, $date);
        $raw = $allData['raw'] ?? [];
        unset($allData['raw']);
        $health = $this->buildHealthIndicators($allData);

        // Comparative: prior year same period
        $priorDate = Carbon::parse($date)->subYear()->endOfMonth()->toDateString();
        $priorData = $this->service->computeAllRatios($companyId, $priorDate);
        $priorRaw = $priorData['raw'] ?? [];
        unset($priorData['raw']);

        $viewData = [
            'company' => $company,
            'date' => $date,
            'prior_date' => $priorDate,
            'ratios' => $allData,
            'health' => $health,
            'raw' => $raw,
            'prior_ratios' => $priorData,
            'prior_raw' => $priorRaw,
            'type' => $type,
        ];

        $view = 'app.pdf.reports.bi-dashboard';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($view, $viewData);
        $pdf->setPaper('a4', 'portrait');

        $filename = "bi-dashboard-{$type}-" . Carbon::parse($date)->format('Y-m') . ".pdf";

        return $pdf->download($filename);
    }

    /**
     * Force re-compute and re-cache ratios.
     */
    public function refresh(Request $request): JsonResponse
    {
        $companyId = (int) $request->header('company');

        if (! $companyId) {
            return response()->json(['error' => 'Company header required'], 400);
        }

        if (! $this->service->isInitialized($companyId)) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'accounting_not_initialized',
            ]);
        }

        $date = $request->query('date', Carbon::now()->endOfMonth()->toDateString());

        try {
            $this->service->cacheRatios($companyId, $date);
            $allData = $this->service->computeAllRatios($companyId, $date);
            $raw = $allData['raw'] ?? [];
            unset($allData['raw']);
            $healthIndicators = $this->buildHealthIndicators($allData);

            return response()->json([
                'success' => true,
                'data' => [
                    'date' => $date,
                    'ratios' => $allData,
                    'health' => $healthIndicators,
                    'raw' => $raw,
                ],
                'message' => 'Ratios refreshed successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Build health indicators from computed ratios.
     */
    protected function buildHealthIndicators(array $ratios): array
    {
        $indicators = [];

        // Liquidity health
        $currentRatio = $ratios['liquidity']['current_ratio'] ?? 0;
        if ($currentRatio >= 1.5) {
            $indicators['liquidity'] = 'safe';
        } elseif ($currentRatio >= 1.0) {
            $indicators['liquidity'] = 'caution';
        } else {
            $indicators['liquidity'] = 'danger';
        }

        // Profitability health
        $netMargin = $ratios['profitability']['net_margin'] ?? 0;
        if ($netMargin >= 0.1) {
            $indicators['profitability'] = 'safe';
        } elseif ($netMargin >= 0) {
            $indicators['profitability'] = 'caution';
        } else {
            $indicators['profitability'] = 'danger';
        }

        // Solvency health
        $debtToEquity = $ratios['solvency']['debt_to_equity'] ?? 0;
        if ($debtToEquity <= 1.0) {
            $indicators['solvency'] = 'safe';
        } elseif ($debtToEquity <= 2.0) {
            $indicators['solvency'] = 'caution';
        } else {
            $indicators['solvency'] = 'danger';
        }

        // Overall (Altman Z-Score)
        $indicators['overall'] = $ratios['altman_z']['zone'] ?? 'danger';

        return $indicators;
    }
}

// CLAUDE-CHECKPOINT
