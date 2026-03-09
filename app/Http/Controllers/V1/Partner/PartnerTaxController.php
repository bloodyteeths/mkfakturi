<?php

namespace App\Http\Controllers\V1\Partner;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Partner;
use App\Models\TaxReportPeriod;
use App\Models\TaxReturn;
use App\Services\VatXmlService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Partner Tax Controller
 *
 * Provides partner access to VAT returns (DDV-04) and Corporate Income Tax (DB)
 * for client companies. Wraps existing admin services with partner access checks.
 */
class PartnerTaxController extends Controller
{
    protected VatXmlService $vatService;

    public function __construct(VatXmlService $vatService)
    {
        $this->vatService = $vatService;
    }

    // ──────────────────────────────────────────────
    // VAT Return (DDV-04) Methods
    // ──────────────────────────────────────────────

    /**
     * Preview VAT data for specified period.
     */
    public function vatPreview(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $validated = $request->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'period_type' => 'required|string|in:MONTHLY,QUARTERLY',
        ]);

        $companyModel = Company::findOrFail($company);

        if (empty($companyModel->vat_number)) {
            return response()->json([
                'error' => 'VAT number required',
                'message' => 'Company VAT number must be set before generating VAT returns.',
                'action' => 'set_vat_number',
            ], 422);
        }

        try {
            $periodStart = Carbon::parse($validated['period_start']);
            $periodEnd = Carbon::parse($validated['period_end']);

            $this->validatePeriodLength($periodStart, $periodEnd, $validated['period_type']);

            $this->vatService->initForPeriod($companyModel, $periodStart, $periodEnd, $validated['period_type']);
            $vatData = $this->vatService->calculateVatForPeriod();
            $inputVatData = $this->vatService->calculateInputVatForPeriod();

            $totalOutputVat = $vatData['standard']['vat_amount'] + $vatData['reduced']['vat_amount'];
            $totalInputVat = $inputVatData['standard']['vat_amount'] + $inputVatData['reduced']['vat_amount'];

            return response()->json([
                'data' => [
                    'company' => [
                        'id' => $companyModel->id,
                        'name' => $companyModel->name,
                        'vat_number' => $companyModel->vat_number,
                    ],
                    'period' => [
                        'start' => $periodStart->format('Y-m-d'),
                        'end' => $periodEnd->format('Y-m-d'),
                        'type' => $validated['period_type'],
                    ],
                    'standard' => $vatData['standard'],
                    'reduced' => $vatData['reduced'],
                    'zero' => $vatData['zero'],
                    'exempt' => $vatData['exempt'],
                    'total_output_vat' => $totalOutputVat,
                    'total_input_vat' => $totalInputVat,
                    'net_vat_due' => $totalOutputVat - $totalInputVat,
                    'total_transactions' => array_sum(array_column($vatData, 'transaction_count')),
                ],
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to preview VAT data',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate and download VAT return XML.
     */
    public function vatGenerate(Request $request, int $company): Response|JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $validated = $request->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'period_type' => 'required|string|in:MONTHLY,QUARTERLY',
        ]);

        $companyModel = Company::findOrFail($company);

        if (empty($companyModel->vat_number)) {
            return response()->json([
                'error' => 'VAT number required',
                'message' => 'Company VAT number must be set before generating VAT returns.',
            ], 422);
        }

        try {
            $periodStart = Carbon::parse($validated['period_start']);
            $periodEnd = Carbon::parse($validated['period_end']);

            $this->validatePeriodLength($periodStart, $periodEnd, $validated['period_type']);

            $xml = $this->vatService->generateVatReturn(
                $companyModel,
                $periodStart,
                $periodEnd,
                $validated['period_type']
            );

            $companyName = preg_replace('/[^a-zA-Z0-9]/', '_', $companyModel->name);
            $filename = sprintf(
                'DDV04_%s_%s_%s.xml',
                $companyName,
                $periodStart->format('Y-m-d'),
                $periodEnd->format('Y-m-d')
            );

            return response($xml, 200, [
                'Content-Type' => 'application/xml',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to generate VAT return',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * File a VAT return.
     */
    public function vatFile(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $validated = $request->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'period_type' => 'required|string|in:MONTHLY,QUARTERLY',
            'xml_content' => 'nullable|string',
            'generate_xml' => 'nullable|boolean',
            'receipt_number' => 'nullable|string',
        ]);

        $companyModel = Company::findOrFail($company);

        try {
            $periodStart = Carbon::parse($validated['period_start']);
            $periodEnd = Carbon::parse($validated['period_end']);

            $this->validatePeriodLength($periodStart, $periodEnd, $validated['period_type']);

            // Generate XML server-side if requested or not provided
            $xmlContent = $validated['xml_content'] ?? null;
            if (empty($xmlContent) || ($validated['generate_xml'] ?? false)) {
                $xmlContent = $this->vatService->generateVatReturn(
                    $companyModel,
                    $periodStart,
                    $periodEnd,
                    $validated['period_type']
                );
            }

            return DB::transaction(function () use ($validated, $companyModel, $periodStart, $periodEnd, $xmlContent) {
                $period = TaxReportPeriod::firstOrCreate(
                    [
                        'company_id' => $companyModel->id,
                        'period_type' => strtolower($validated['period_type']),
                        'year' => $periodStart->year,
                        'month' => strtolower($validated['period_type']) === TaxReportPeriod::PERIOD_MONTHLY ? $periodStart->month : null,
                        'quarter' => strtolower($validated['period_type']) === TaxReportPeriod::PERIOD_QUARTERLY ? $periodStart->quarter : null,
                    ],
                    [
                        'start_date' => $periodStart,
                        'end_date' => $periodEnd,
                        'due_date' => $periodEnd->copy()->addDays(25),
                        'status' => TaxReportPeriod::STATUS_OPEN,
                    ]
                );

                $taxReturn = TaxReturn::create([
                    'company_id' => $companyModel->id,
                    'period_id' => $period->id,
                    'return_type' => TaxReturn::TYPE_VAT,
                    'status' => TaxReturn::STATUS_DRAFT,
                    'return_data' => [
                        'xml_content' => $xmlContent,
                        'period_start' => $periodStart->toDateString(),
                        'period_end' => $periodEnd->toDateString(),
                        'period_type' => $validated['period_type'],
                    ],
                ]);

                $taxReturn->file(
                    Auth::id(),
                    $validated['receipt_number'] ?? null
                );

                if ($period->status === TaxReportPeriod::STATUS_OPEN) {
                    $period->status = TaxReportPeriod::STATUS_CLOSED;
                    $period->locked_at = now();
                    $period->locked_by = Auth::id();
                    $period->save();
                }

                return response()->json([
                    'message' => 'Tax return filed successfully',
                    'data' => [
                        'tax_return_id' => $taxReturn->id,
                        'period_id' => $period->id,
                        'receipt_number' => $taxReturn->receipt_number,
                        'submitted_at' => $taxReturn->submitted_at,
                    ],
                ], 201);
            });
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to file tax return',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List VAT report periods for a company.
     */
    public function vatPeriods(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $validated = $request->validate([
            'status' => 'nullable|string|in:OPEN,CLOSED,FILED,AMENDED',
            'period_type' => 'nullable|string|in:MONTHLY,QUARTERLY,ANNUAL',
            'year' => 'nullable|integer',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        try {
            $query = TaxReportPeriod::where('company_id', $company)
                ->withCount(['taxReturns as filed_returns_count' => function ($query) {
                    $query->whereIn('status', [TaxReturn::STATUS_FILED, TaxReturn::STATUS_ACCEPTED]);
                }]);

            if (!empty($validated['status'])) {
                $query->where('status', $validated['status']);
            }
            if (!empty($validated['period_type'])) {
                $query->where('period_type', $validated['period_type']);
            }
            if (!empty($validated['year'])) {
                $query->where('year', $validated['year']);
            }

            $periods = $query->orderByPeriod('desc')
                ->paginateData($validated['limit'] ?? 15);

            return response()->json(['data' => $periods]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch tax periods',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get tax returns for a specific period.
     */
    public function vatReturns(Request $request, int $company, int $periodId): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $period = TaxReportPeriod::where('company_id', $company)->findOrFail($periodId);

        try {
            $returns = TaxReturn::forPeriod($periodId)
                ->where('return_type', TaxReturn::TYPE_VAT)
                ->with(['submittedBy:id,name,email'])
                ->orderBySubmission('desc')
                ->get()
                ->map(function ($return) {
                    return [
                        'id' => $return->id,
                        'status' => $return->status,
                        'status_label' => $return->status_label,
                        'receipt_number' => $return->receipt_number,
                        'submitted_at' => $return->submitted_at,
                        'submitted_by' => $return->submittedBy ? [
                            'id' => $return->submittedBy->id,
                            'name' => $return->submittedBy->name,
                        ] : null,
                        'accepted_at' => $return->accepted_at,
                        'rejected_at' => $return->rejected_at,
                        'rejection_reason' => $return->rejection_reason,
                        'is_amendment' => $return->amendment_of !== null,
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
                'error' => 'Failed to fetch tax returns',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download XML for a specific VAT return.
     */
    public function vatDownloadXml(Request $request, int $company, int $id): Response|JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $taxReturn = TaxReturn::where('company_id', $company)->findOrFail($id);

        $xmlContent = $taxReturn->return_data['xml_content'] ?? null;
        if (!$xmlContent) {
            return response()->json(['error' => 'XML content not found for this tax return'], 404);
        }

        $period = $taxReturn->period;
        $companyName = preg_replace('/[^a-zA-Z0-9]/', '_', $taxReturn->company->name);
        $filename = sprintf(
            'DDV04_%s_%s_%s.xml',
            $companyName,
            $period->start_date->format('Y-m-d'),
            $period->end_date->format('Y-m-d')
        );

        if ($taxReturn->amendment_of) {
            $filename = str_replace('.xml', '_AMENDMENT.xml', $filename);
        }

        return response($xmlContent, 200, [
            'Content-Type' => 'application/xml',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    /**
     * Get VAT compliance status for a company.
     */
    public function vatStatus(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $companyModel = Company::findOrFail($company);

        try {
            $now = now();
            $currentMonth = $now->copy()->startOfMonth();
            $complianceAlerts = [];

            if (empty($companyModel->vat_number)) {
                $complianceAlerts[] = [
                    'type' => 'warning',
                    'severity' => 'high',
                    'message' => 'VAT number not set for company.',
                    'action_required' => true,
                ];
            }

            $lastReturn = TaxReturn::where('company_id', $company)
                ->where('return_type', TaxReturn::TYPE_VAT)
                ->whereIn('status', [TaxReturn::STATUS_FILED, TaxReturn::STATUS_ACCEPTED])
                ->orderBy('submitted_at', 'desc')
                ->first();

            $nextDeadline = $currentMonth->copy()->addMonth()->day(15)->endOfDay();

            $currentStatus = 'unknown';
            if ($lastReturn) {
                $daysSinceLastFiling = $now->diffInDays($lastReturn->submitted_at);
                $currentStatus = $daysSinceLastFiling <= 45 ? 'compliant' : 'overdue';
            } else {
                $currentStatus = 'no_returns';
            }

            return response()->json([
                'current_status' => $currentStatus,
                'last_generation' => $lastReturn ? $lastReturn->submitted_at->toIso8601String() : null,
                'compliance_alerts' => $complianceAlerts,
                'next_deadline' => $nextDeadline->toIso8601String(),
                'vat_number' => $companyModel->vat_number,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch VAT status',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // ──────────────────────────────────────────────
    // Corporate Income Tax (CIT / DB) Methods
    // ──────────────────────────────────────────────

    /**
     * Preview CIT calculation for a fiscal year.
     */
    public function citPreview(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'adjustments' => 'nullable|array',
            'adjustments.*.description' => 'required|string',
            'adjustments.*.amount' => 'required|numeric|min:0',
            'loss_carryforward' => 'nullable|numeric|min:0',
        ]);

        $companyModel = Company::findOrFail($company);

        try {
            $citService = app(\App\Services\CorporateIncomeTaxService::class);
            $preview = $citService->preview(
                $companyModel,
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
     * Generate CIT XML (DB-VP form).
     */
    public function citGenerate(Request $request, int $company): Response|JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'adjustments' => 'nullable|array',
            'adjustments.*.description' => 'required|string',
            'adjustments.*.amount' => 'required|numeric|min:0',
            'loss_carryforward' => 'nullable|numeric|min:0',
        ]);

        $companyModel = Company::findOrFail($company);

        try {
            $citService = app(\App\Services\CorporateIncomeTaxService::class);
            $citData = $citService->calculate(
                $companyModel,
                $validated['year'],
                $validated['adjustments'] ?? [],
                $validated['loss_carryforward'] ?? 0
            );

            $citXmlService = app(\App\Services\CitXmlService::class);
            $xml = $citXmlService->generate($companyModel, $validated['year'], $citData);

            $companyName = preg_replace('/[^a-zA-Z0-9]/', '_', $companyModel->name);
            $filename = sprintf('DB_VP_%s_%d.xml', $companyName, $validated['year']);

            return response($xml, 200, [
                'Content-Type' => 'application/xml',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
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
    public function citFile(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'return_data' => 'required|array',
            'xml_content' => 'required|string',
            'receipt_number' => 'nullable|string',
        ]);

        $companyModel = Company::findOrFail($company);

        try {
            $period = TaxReportPeriod::firstOrCreate(
                [
                    'company_id' => $companyModel->id,
                    'period_type' => TaxReportPeriod::PERIOD_ANNUAL,
                    'year' => $validated['year'],
                ],
                [
                    'start_date' => Carbon::create($validated['year'], 1, 1),
                    'end_date' => Carbon::create($validated['year'], 12, 31),
                    'due_date' => Carbon::create($validated['year'] + 1, 3, 15),
                    'status' => TaxReportPeriod::STATUS_OPEN,
                ]
            );

            $returnData = array_merge($validated['return_data'], [
                'xml_content' => $validated['xml_content'],
            ]);

            $taxReturn = TaxReturn::create([
                'company_id' => $companyModel->id,
                'period_id' => $period->id,
                'return_type' => TaxReturn::TYPE_CORPORATE,
                'status' => TaxReturn::STATUS_DRAFT,
                'return_data' => $returnData,
            ]);

            $taxReturn->file(
                Auth::id(),
                $validated['receipt_number'] ?? null
            );

            return response()->json([
                'message' => 'CIT return filed successfully',
                'data' => [
                    'tax_return_id' => $taxReturn->id,
                    'period_id' => $period->id,
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
     * Download XML for a specific CIT return.
     */
    public function citDownloadXml(Request $request, int $company, int $id): Response|JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $taxReturn = TaxReturn::where('company_id', $company)
            ->where('return_type', TaxReturn::TYPE_CORPORATE)
            ->findOrFail($id);

        $xmlContent = $taxReturn->return_data['xml_content'] ?? null;
        if (!$xmlContent) {
            return response()->json(['error' => 'XML content not found'], 404);
        }

        $companyName = preg_replace('/[^a-zA-Z0-9]/', '_', $taxReturn->company->name);
        $year = $taxReturn->return_data['year'] ?? $taxReturn->period->year;
        $filename = sprintf('DB_VP_%s_%d.xml', $companyName, $year);

        return response($xmlContent, 200, [
            'Content-Type' => 'application/xml',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    // ──────────────────────────────────────────────
    // Internal helpers
    // ──────────────────────────────────────────────

    /**
     * Validate period length based on type.
     */
    protected function validatePeriodLength(Carbon $start, Carbon $end, string $type): void
    {
        $type = strtoupper($type);
        $diffInDays = $start->diffInDays($end);

        if ($type === 'MONTHLY' && ($diffInDays < 27 || $diffInDays > 31)) {
            throw ValidationException::withMessages([
                'period_end' => 'Monthly period must be between 28-31 days',
            ]);
        } elseif ($type === 'QUARTERLY' && ($diffInDays < 88 || $diffInDays > 93)) {
            throw ValidationException::withMessages([
                'period_end' => 'Quarterly period must be between 89-92 days',
            ]);
        }
    }

    /**
     * Calculate VAT summary using VatXmlService public API.
     */
    protected function calculateVatSummary(Company $company, Carbon $periodStart, Carbon $periodEnd): array
    {
        $this->vatService->initForPeriod($company, $periodStart, $periodEnd);

        return $this->vatService->calculateVatForPeriod();
    }

    /**
     * Get partner from authenticated request.
     */
    protected function getPartnerFromRequest(Request $request): ?Partner
    {
        $user = $request->user();
        if (!$user) {
            return null;
        }

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
     */
    protected function hasCompanyAccess(Partner $partner, int $companyId): bool
    {
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
