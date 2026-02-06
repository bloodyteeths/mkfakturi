<?php

namespace App\Http\Controllers\V1\Admin\Banking;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\Reconciliation\ReconciliationAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * ReconciliationAnalyticsController
 *
 * Provides the analytics endpoint for the Phase 0 reconciliation dashboard.
 * Returns KPIs including auto-match rate, average confidence, daily trends,
 * per-bank parse accuracy, and match method breakdown.
 *
 * @see P0-10 Phase 0 Analytics Dashboard
 */
class ReconciliationAnalyticsController extends Controller
{
    /**
     * Get reconciliation analytics for the current company.
     *
     * GET /api/v1/banking/reconciliation/analytics?from=2026-01-01&to=2026-02-28
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'from' => 'nullable|date|date_format:Y-m-d',
            'to' => 'nullable|date|date_format:Y-m-d|after_or_equal:from',
        ]);

        $company = $this->getCompany();

        $service = new ReconciliationAnalyticsService();
        $analytics = $service->getAnalytics(
            $company->id,
            $request->get('from'),
            $request->get('to')
        );

        return response()->json([
            'success' => true,
            'data' => $analytics,
        ]);
    }

    /**
     * Get the current company from the authenticated user.
     *
     * P0-13: Resolves company from request header and validates user access.
     * Falls back to user's first company if no header is provided.
     *
     * @return Company
     */
    protected function getCompany(): Company
    {
        $user = Auth::user();
        $companyIdHeader = request()->header('company');

        if ($companyIdHeader) {
            $companyId = (int) $companyIdHeader;

            if ($user->hasCompany($companyId)) {
                $company = $user->companies()->where('companies.id', $companyId)->first();
                if ($company) {
                    return $company;
                }
            }
        }

        return $user->companies()->firstOrFail();
    }
}

// CLAUDE-CHECKPOINT
