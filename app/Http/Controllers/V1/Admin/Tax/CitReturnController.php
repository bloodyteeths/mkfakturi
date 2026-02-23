<?php

namespace App\Http\Controllers\V1\Admin\Tax;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\TaxReportPeriod;
use App\Models\TaxReturn;
use App\Services\CitXmlService;
use App\Services\CorporateIncomeTaxService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * Corporate Income Tax Return Controller (ДБ-ВП / DB-VP)
 *
 * Admin-facing controller for CIT annual return generation and filing.
 * Handles preview, XML generation, filing, and period management.
 */
class CitReturnController extends Controller
{
    protected CorporateIncomeTaxService $citService;

    protected CitXmlService $citXmlService;

    public function __construct(CorporateIncomeTaxService $citService, CitXmlService $citXmlService)
    {
        $this->citService = $citService;
        $this->citXmlService = $citXmlService;
    }

    /**
     * Preview CIT calculation for a fiscal year.
     */
    public function preview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'year' => 'required|integer|min:2020|max:2100',
            'adjustments' => 'nullable|array',
            'adjustments.*.category' => 'nullable|string',
            'adjustments.*.description' => 'required|string',
            'adjustments.*.amount' => 'required|numeric|min:0',
            'loss_carryforward' => 'nullable|numeric|min:0',
        ]);

        $companyId = $validated['company_id'] ?? $request->header('company');
        $company = Company::findOrFail($companyId);
        Gate::authorize('view', $company);

        try {
            $preview = $this->citService->preview(
                $company,
                $validated['year'],
                $validated['adjustments'] ?? [],
                $validated['loss_carryforward'] ?? 0
            );

            return response()->json(['data' => $preview]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to preview CIT calculation',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate and download CIT return XML.
     */
    public function generate(Request $request): Response|JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'year' => 'required|integer|min:2020|max:2100',
            'adjustments' => 'nullable|array',
            'adjustments.*.category' => 'nullable|string',
            'adjustments.*.description' => 'required|string',
            'adjustments.*.amount' => 'required|numeric|min:0',
            'loss_carryforward' => 'nullable|numeric|min:0',
        ]);

        $companyId = $validated['company_id'] ?? $request->header('company');
        $company = Company::findOrFail($companyId);
        Gate::authorize('view', $company);

        try {
            $citData = $this->citService->calculate(
                $company,
                $validated['year'],
                $validated['adjustments'] ?? [],
                $validated['loss_carryforward'] ?? 0
            );

            $xml = $this->citXmlService->generate($company, $validated['year'], $citData);

            $companyName = preg_replace('/[^a-zA-Z0-9]/', '_', $company->name);
            $filename = sprintf('DB_VP_%s_%d.xml', $companyName, $validated['year']);

            return response($xml, 200, [
                'Content-Type' => 'application/xml',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to generate CIT return',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * File a CIT return.
     */
    public function file(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'year' => 'required|integer|min:2020|max:2100',
            'return_data' => 'required|array',
            'xml_content' => 'required|string',
            'receipt_number' => 'nullable|string',
        ]);

        $companyId = $validated['company_id'] ?? $request->header('company');
        $company = Company::findOrFail($companyId);
        Gate::authorize('view', $company);

        try {
            $year = $validated['year'];

            $period = TaxReportPeriod::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'period_type' => TaxReportPeriod::PERIOD_ANNUAL,
                    'year' => $year,
                ],
                [
                    'start_date' => Carbon::create($year, 1, 1),
                    'end_date' => Carbon::create($year, 12, 31),
                    'status' => TaxReportPeriod::STATUS_OPEN,
                ]
            );

            $returnData = array_merge($validated['return_data'], [
                'xml_content' => $validated['xml_content'],
            ]);

            $taxReturn = TaxReturn::create([
                'company_id' => $company->id,
                'period_id' => $period->id,
                'return_type' => TaxReturn::TYPE_CORPORATE,
                'status' => TaxReturn::STATUS_DRAFT,
                'return_data' => $returnData,
            ]);

            $taxReturn->file(
                Auth::id(),
                $validated['receipt_number'] ?? null
            );

            if ($period->status === TaxReportPeriod::STATUS_OPEN) {
                $period->status = TaxReportPeriod::STATUS_CLOSED;
                $period->closed_at = now();
                $period->closed_by_id = Auth::id();
                $period->save();
            }

            return response()->json([
                'message' => 'CIT return filed successfully',
                'data' => [
                    'tax_return_id' => $taxReturn->id,
                    'period_id' => $period->id,
                    'submission_reference' => $taxReturn->submission_reference,
                    'submitted_at' => $taxReturn->submitted_at,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to file CIT return',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List annual tax periods for CIT.
     */
    public function getPeriods(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'nullable|integer|exists:companies,id',
            'status' => 'nullable|string|in:OPEN,CLOSED,FILED,AMENDED',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $companyId = $validated['company_id'] ?? $request->header('company');
        $company = Company::findOrFail($companyId);
        Gate::authorize('view', $company);

        try {
            $query = TaxReportPeriod::where('company_id', $company->id)
                ->where('period_type', TaxReportPeriod::PERIOD_ANNUAL)
                ->withCount(['taxReturns as filed_returns_count' => function ($query) {
                    $query->where('return_type', TaxReturn::TYPE_CORPORATE)
                        ->whereIn('status', [TaxReturn::STATUS_FILED, TaxReturn::STATUS_ACCEPTED]);
                }]);

            if (!empty($validated['status'])) {
                $query->where('status', $validated['status']);
            }

            $periods = $query->orderByPeriod('desc')
                ->paginateData($validated['limit'] ?? 15);

            return response()->json(['data' => $periods]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch CIT periods',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get CIT returns for a specific period.
     */
    public function getReturns(Request $request, int $periodId): JsonResponse
    {
        $period = TaxReportPeriod::findOrFail($periodId);
        Gate::authorize('view', $period->company);

        try {
            $returns = TaxReturn::forPeriod($periodId)
                ->where('return_type', TaxReturn::TYPE_CORPORATE)
                ->with(['submittedBy:id,name,email'])
                ->orderBySubmission('desc')
                ->get()
                ->map(function ($return) {
                    return [
                        'id' => $return->id,
                        'status' => $return->status,
                        'status_label' => $return->status_label,
                        'submission_reference' => $return->submission_reference,
                        'submitted_at' => $return->submitted_at,
                        'submitted_by' => $return->submittedBy ? [
                            'id' => $return->submittedBy->id,
                            'name' => $return->submittedBy->name,
                        ] : null,
                        'return_data' => $return->return_data ? collect($return->return_data)->except('xml_content')->toArray() : null,
                        'accepted_at' => $return->accepted_at,
                        'rejected_at' => $return->rejected_at,
                        'rejection_reason' => $return->rejection_reason,
                        'is_amendment' => $return->amendment_of_id !== null,
                        'created_at' => $return->created_at,
                    ];
                });

            return response()->json([
                'data' => [
                    'period' => [
                        'id' => $period->id,
                        'period_name' => $period->period_name,
                        'start_date' => $period->start_date,
                        'end_date' => $period->end_date,
                        'status' => $period->status,
                    ],
                    'returns' => $returns,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch CIT returns',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download XML for a specific CIT return.
     */
    public function downloadXml(int $id): Response|JsonResponse
    {
        $taxReturn = TaxReturn::findOrFail($id);
        Gate::authorize('view', $taxReturn->company);

        $xmlContent = $taxReturn->return_data['xml_content'] ?? null;
        if (!$xmlContent) {
            return response()->json(['error' => 'XML content not found for this CIT return'], 404);
        }

        $period = $taxReturn->period;
        $companyName = preg_replace('/[^a-zA-Z0-9]/', '_', $taxReturn->company->name);
        $filename = sprintf('DB_VP_%s_%d.xml', $companyName, $period->year);

        if ($taxReturn->amendment_of_id) {
            $filename = str_replace('.xml', '_AMENDMENT.xml', $filename);
        }

        return response($xmlContent, 200, [
            'Content-Type' => 'application/xml',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}

// CLAUDE-CHECKPOINT
