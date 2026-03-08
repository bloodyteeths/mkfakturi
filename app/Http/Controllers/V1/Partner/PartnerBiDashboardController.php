<?php

namespace App\Http\Controllers\V1\Partner;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Mk\Services\FinancialRatioService;

/**
 * Partner BI Dashboard Controller
 *
 * Provides financial ratio dashboards for partner's client companies.
 */
class PartnerBiDashboardController extends Controller
{
    protected FinancialRatioService $service;

    public function __construct(FinancialRatioService $service)
    {
        $this->service = $service;
    }

    /**
     * Get financial ratios for a client company.
     */
    public function ratios(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }

        if (! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        if (! $this->service->isInitialized($company)) {
            return response()->json(['success' => true, 'data' => null, 'message' => 'accounting_not_initialized']);
        }

        $date = $request->query('date', Carbon::now()->endOfMonth()->toDateString());

        try {
            $ratios = $this->service->computeAllRatios($company, $date);

            return response()->json([
                'success' => true,
                'data' => $ratios,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get ratio trends for a client company.
     */
    public function trends(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner || ! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'Access denied'], 403);
        }

        if (! $this->service->isInitialized($company)) {
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
            $trends = $this->service->getTrends($company, $ratioType, $months);

            return response()->json([
                'success' => true,
                'data' => [
                    'ratio_type' => $ratioType,
                    'months' => $months,
                    'trends' => $trends,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get full summary with all ratios and health indicators.
     */
    public function summary(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner || ! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'Access denied'], 403);
        }

        if (! $this->service->isInitialized($company)) {
            return response()->json(['success' => true, 'data' => null, 'message' => 'accounting_not_initialized']);
        }

        $date = $request->query('date', Carbon::now()->endOfMonth()->toDateString());

        try {
            $allData = $this->service->computeAllRatios($company, $date);
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
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
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

    // ---- Access Helpers ----

    /**
     * Get partner from authenticated request.
     * For super admin, returns a "fake" partner object to allow access.
     */
    protected function getPartnerFromRequest(Request $request): ?Partner
    {
        $user = $request->user();

        if (! $user) {
            return null;
        }

        // Super admin gets a fake partner to pass validation
        if ($user->role === 'super admin') {
            $fakePartner = new Partner();
            $fakePartner->id = 0;
            $fakePartner->user_id = $user->id;
            $fakePartner->name = 'Super Admin';
            $fakePartner->email = $user->email;
            $fakePartner->is_super_admin = true;

            return $fakePartner;
        }

        return Partner::where('user_id', $user->id)->first();
    }

    /**
     * Check if partner has access to a company.
     * Super admin has access to all companies.
     */
    protected function hasCompanyAccess(Partner $partner, int $companyId): bool
    {
        // Super admin has access to all companies
        if ($partner->is_super_admin ?? false) {
            return true;
        }

        return $partner->companies()
            ->where('companies.id', $companyId)
            ->where('partner_company_links.is_active', true)
            ->exists();
    }
}

// CLAUDE-CHECKPOINT
