<?php

namespace App\Http\Controllers\V1\Partner;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Services\BulkReportingService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * P8-03: Bulk reporting across partner's managed clients.
 *
 * Provides multi-company comparison, consolidated balance sheets,
 * and bulk export for partners managing 50+ clients.
 */
class BulkReportController extends Controller
{
    public function __construct(
        private BulkReportingService $reportingService
    ) {}

    /**
     * Multi-company report: separate report per company.
     *
     * POST /api/v1/partner/reports/multi-company
     *
     * @return JsonResponse
     */
    public function multiCompany(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_ids' => 'required|array|min:1',
            'company_ids.*' => 'required|integer|exists:companies,id',
            'from_date' => 'required|date|date_format:Y-m-d',
            'to_date' => 'required|date|date_format:Y-m-d|after_or_equal:from_date',
            'report_type' => 'required|string|in:trial_balance,profit_loss,balance_sheet',
        ]);

        $partner = $this->getPartnerFromRequest($request);

        if (! $partner) {
            return response()->json([
                'success' => false,
                'message' => 'Partner not found',
            ], 404);
        }

        try {
            $result = $this->reportingService->multiCompanyReport(
                $partner,
                $validated['company_ids'],
                $validated['report_type'],
                Carbon::parse($validated['from_date']),
                Carbon::parse($validated['to_date'])
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate multi-company report.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Consolidated report: aggregated across companies.
     *
     * POST /api/v1/partner/reports/consolidated
     *
     * @return JsonResponse
     */
    public function consolidated(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_ids' => 'required|array|min:1',
            'company_ids.*' => 'required|integer|exists:companies,id',
            'from_date' => 'required|date|date_format:Y-m-d',
            'to_date' => 'required|date|date_format:Y-m-d|after_or_equal:from_date',
        ]);

        $partner = $this->getPartnerFromRequest($request);

        if (! $partner) {
            return response()->json([
                'success' => false,
                'message' => 'Partner not found',
            ], 404);
        }

        try {
            $result = $this->reportingService->consolidatedReport(
                $partner,
                $validated['company_ids'],
                Carbon::parse($validated['from_date']),
                Carbon::parse($validated['to_date'])
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate consolidated report.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Export report as CSV download or JSON.
     *
     * POST /api/v1/partner/reports/export
     *
     * @return JsonResponse|StreamedResponse
     */
    public function export(Request $request): JsonResponse|StreamedResponse
    {
        $validated = $request->validate([
            'company_ids' => 'required|array|min:1',
            'company_ids.*' => 'required|integer|exists:companies,id',
            'from_date' => 'required|date|date_format:Y-m-d',
            'to_date' => 'required|date|date_format:Y-m-d|after_or_equal:from_date',
            'report_type' => 'required|string|in:trial_balance,profit_loss,balance_sheet',
            'format' => 'required|string|in:csv,json',
        ]);

        $partner = $this->getPartnerFromRequest($request);

        if (! $partner) {
            return response()->json([
                'success' => false,
                'message' => 'Partner not found',
            ], 404);
        }

        try {
            $result = $this->reportingService->exportReport(
                $partner,
                $validated['company_ids'],
                $validated['report_type'],
                Carbon::parse($validated['from_date']),
                Carbon::parse($validated['to_date']),
                $validated['format']
            );

            // CSV returns a StreamedResponse directly
            if ($result instanceof StreamedResponse) {
                return $result;
            }

            // JSON format
            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export report.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get partner from authenticated request.
     * Mirrors the pattern used in PartnerAccountingReportsController.
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
}

// CLAUDE-CHECKPOINT
