<?php

namespace App\Http\Controllers\V1\Admin\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\YearEndClosingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Year-End Closing Controller
 *
 * Provides API endpoints for the guided year-end closing wizard.
 * Steps: Preflight → Review → Adjust → Close → Reports → Finalize
 */
class YearEndClosingController extends Controller
{
    protected YearEndClosingService $service;

    public function __construct(YearEndClosingService $service)
    {
        $this->service = $service;
    }

    /**
     * GET /api/v1/year-end/{year}/preflight
     *
     * Step 1: Run pre-flight checks.
     */
    public function preflight(Request $request, int $year): JsonResponse
    {
        $company = Company::find($request->header('company'));
        if (! $company) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        $result = $this->service->getPreflightChecks($company, $year);

        return response()->json($result);
    }

    /**
     * GET /api/v1/year-end/{year}/summary
     *
     * Step 2: Get financial statement summaries for review.
     */
    public function summary(Request $request, int $year): JsonResponse
    {
        $company = Company::find($request->header('company'));
        if (! $company) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        $result = $this->service->getFinancialSummary($company, $year);

        return response()->json($result);
    }

    /**
     * POST /api/v1/year-end/{year}/closing
     *
     * Step 4: Generate closing entries (preview or commit).
     */
    public function closing(Request $request, int $year): JsonResponse
    {
        $company = Company::find($request->header('company'));
        if (! $company) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        $mode = $request->input('mode', 'preview');

        if ($mode === 'preview') {
            $result = $this->service->previewClosingEntries($company, $year);

            return response()->json($result);
        }

        // Commit mode
        try {
            $result = $this->service->generateClosingEntries($company, $year);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * GET /api/v1/year-end/{year}/reports/{type}
     *
     * Step 5: Download reports.
     * Types: balance-sheet, income-statement, trial-balance, tax-summary, bundle
     */
    public function reports(Request $request, int $year, string $type): JsonResponse
    {
        $company = Company::find($request->header('company'));
        if (! $company) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        $startDate = "{$year}-01-01";
        $endDate = "{$year}-12-31";

        $summary = $this->service->getFinancialSummary($company, $year);

        return match ($type) {
            'tax-summary' => response()->json([
                'year' => $year,
                'company' => $company->name,
                'tax_id' => $company->tax_id ?? '',
                'revenue' => $summary['summary']['total_revenue'],
                'expenses' => $summary['summary']['total_expenses'],
                'profit_before_tax' => $summary['summary']['net_profit_before_tax'],
                'income_tax_rate' => $summary['summary']['income_tax_rate'],
                'income_tax' => $summary['summary']['income_tax'],
                'net_profit' => $summary['summary']['net_profit_after_tax'],
                'form' => 'ДБ-ВП',
                'portal' => 'etax.ujp.gov.mk',
            ]),
            default => response()->json([
                'year' => $year,
                'type' => $type,
                'data' => $summary,
            ]),
        };
    }

    /**
     * POST /api/v1/year-end/{year}/finalize
     *
     * Step 6: Lock the fiscal year.
     */
    public function finalize(Request $request, int $year): JsonResponse
    {
        $company = Company::find($request->header('company'));
        if (! $company) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        try {
            $result = $this->service->finalize($company, $year, auth()->id());

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * POST /api/v1/year-end/{year}/undo
     *
     * Undo year-end closing (within 24h).
     */
    public function undo(Request $request, int $year): JsonResponse
    {
        $company = Company::find($request->header('company'));
        if (! $company) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        try {
            $result = $this->service->undo($company, $year, auth()->id());

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
// CLAUDE-CHECKPOINT
