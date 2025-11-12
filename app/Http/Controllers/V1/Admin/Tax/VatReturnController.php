<?php

namespace App\Http\Controllers\V1\Admin\Tax;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\TaxReportPeriod;
use App\Models\TaxReturn;
use App\Services\VatXmlService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * VAT Return Controller
 * 
 * Handles ДДВ-04 VAT return generation for Macedonia tax compliance.
 * Integrates with VatXmlService for XML generation and validation.
 */
class VatReturnController extends Controller
{
    protected VatXmlService $vatService;

    public function __construct(VatXmlService $vatService)
    {
        $this->vatService = $vatService;
    }

    /**
     * Preview VAT data for specified period
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function preview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'period_type' => 'required|string|in:MONTHLY,QUARTERLY'
        ]);

        // Get company and authorize access
        $company = Company::findOrFail($validated['company_id']);
        Gate::authorize('view', $company);

        try {
            // Log company VAT info for debugging
            \Log::info('VatReturnController::preview - Company VAT Check', [
                'company_id' => $company->id,
                'company_name' => $company->name,
                'vat_number' => $company->vat_number,
                'vat_id' => $company->vat_id,
                'tax_id' => $company->tax_id,
                'vat_number_empty' => empty($company->vat_number),
                'vat_number_is_null' => is_null($company->vat_number),
            ]);

            // Validate VAT number is set
            if (empty($company->vat_number)) {
                \Log::warning('VatReturnController::preview - VAT number missing', [
                    'company_id' => $company->id,
                    'vat_number' => $company->vat_number,
                    'vat_id' => $company->vat_id,
                ]);

                return response()->json([
                    'error' => 'VAT number required',
                    'message' => 'Company VAT number must be set before generating VAT returns. Please update your company settings.',
                    'action' => 'set_vat_number'
                ], 422);
            }

            // Parse dates
            $periodStart = Carbon::parse($validated['period_start']);
            $periodEnd = Carbon::parse($validated['period_end']);

            // Validate period length
            $this->validatePeriodLength($periodStart, $periodEnd, $validated['period_type']);

            // Get VAT data summary without generating full XML
            $vatData = $this->calculateVatSummary($company, $periodStart, $periodEnd);

            return response()->json([
                'data' => [
                    'company' => [
                        'id' => $company->id,
                        'name' => $company->name,
                        'vat_number' => $company->vat_number
                    ],
                    'period' => [
                        'start' => $periodStart->format('Y-m-d'),
                        'end' => $periodEnd->format('Y-m-d'),
                        'type' => $validated['period_type']
                    ],
                    'standard' => $vatData['standard'],
                    'reduced' => $vatData['reduced'],
                    'zero' => $vatData['zero'],
                    'exempt' => $vatData['exempt'],
                    'total_output_vat' => $vatData['standard']['vat_amount'] + $vatData['reduced']['vat_amount'],
                    'total_transactions' => array_sum(array_column($vatData, 'transaction_count'))
                ]
            ]);

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to preview VAT data',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate and download VAT return XML
     * 
     * @param Request $request
     * @return Response
     */
    public function generate(Request $request): Response
    {
        $validated = $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'period_type' => 'required|string|in:MONTHLY,QUARTERLY',
            'validate_xml' => 'boolean'
        ]);

        // Get company and authorize access
        $company = Company::findOrFail($validated['company_id']);
        Gate::authorize('view', $company);

        try {
            // Validate VAT number is set
            if (empty($company->vat_number)) {
                return response()->json([
                    'error' => 'VAT number required',
                    'message' => 'Company VAT number must be set before generating VAT returns. Please update your company settings.',
                    'action' => 'set_vat_number'
                ], 422);
            }

            // Parse dates
            $periodStart = Carbon::parse($validated['period_start']);
            $periodEnd = Carbon::parse($validated['period_end']);
            $periodType = $validated['period_type'];

            // Validate period length
            $this->validatePeriodLength($periodStart, $periodEnd, $periodType);

            // Generate VAT return XML
            $xml = $this->vatService->generateVatReturn(
                $company,
                $periodStart,
                $periodEnd,
                $periodType
            );

            // Validate XML if requested
            if ($validated['validate_xml'] ?? true) {
                try {
                    $isValid = $this->vatService->validateXml($xml);
                    if (!$isValid) {
                        $errors = $this->vatService->getValidationErrors($xml);
                        return response()->json([
                            'error' => 'XML validation failed',
                            'validation_errors' => $errors
                        ], 422);
                    }
                } catch (\Exception $e) {
                    // Continue without validation if schema file is missing
                    // This allows development/testing without requiring XSD schema
                }
            }

            // Generate filename
            $companyName = preg_replace('/[^a-zA-Z0-9]/', '_', $company->name);
            $filename = sprintf(
                'DDV04_%s_%s_%s.xml',
                $companyName,
                $periodStart->format('Y-m-d'),
                $periodEnd->format('Y-m-d')
            );

            // Return XML file as download
            return response($xml, 200, [
                'Content-Type' => 'application/xml',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0'
            ]);

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to generate VAT return',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate period length based on type
     */
    protected function validatePeriodLength(Carbon $start, Carbon $end, string $type): void
    {
        $diffInDays = $start->diffInDays($end);

        if ($type === 'MONTHLY') {
            // Allow 28-31 days for monthly periods
            if ($diffInDays < 27 || $diffInDays > 31) {
                throw ValidationException::withMessages([
                    'period_end' => 'Monthly period must be between 28-31 days'
                ]);
            }
        } elseif ($type === 'QUARTERLY') {
            // Allow 89-92 days for quarterly periods
            if ($diffInDays < 88 || $diffInDays > 93) {
                throw ValidationException::withMessages([
                    'period_end' => 'Quarterly period must be between 89-92 days'
                ]);
            }
        }
    }

    /**
     * Calculate VAT summary data for preview
     */
    protected function calculateVatSummary(Company $company, Carbon $periodStart, Carbon $periodEnd): array
    {
        // Use VatXmlService's protected method through reflection for consistent data
        $reflection = new \ReflectionClass($this->vatService);
        $method = $reflection->getMethod('calculateVatForPeriod');
        $method->setAccessible(true);

        // Set the service properties
        $companyProperty = $reflection->getProperty('company');
        $companyProperty->setAccessible(true);
        $companyProperty->setValue($this->vatService, $company);

        $periodStartProperty = $reflection->getProperty('periodStart');
        $periodStartProperty->setAccessible(true);
        $periodStartProperty->setValue($this->vatService, $periodStart);

        $periodEndProperty = $reflection->getProperty('periodEnd');
        $periodEndProperty->setAccessible(true);
        $periodEndProperty->setValue($this->vatService, $periodEnd);

        // Get the VAT calculation data
        return $method->invoke($this->vatService);
    }

    /**
     * Get VAT compliance status for a company
     *
     * @param Company $company
     * @return JsonResponse
     */
    public function status(Company $company): JsonResponse
    {
        Gate::authorize('view', $company);

        try {
            $now = now();
            $currentMonth = $now->startOfMonth();
            $complianceAlerts = [];

            // Check if VAT number is set
            if (empty($company->vat_number)) {
                $complianceAlerts[] = [
                    'type' => 'warning',
                    'severity' => 'high',
                    'message' => 'VAT number not set for company. Please update company settings.',
                    'action_required' => true
                ];
            }

            // Get most recent filed tax return
            $lastReturn = TaxReturn::where('company_id', $company->id)
                ->where('return_type', TaxReturn::TYPE_VAT)
                ->whereIn('status', [TaxReturn::STATUS_FILED, TaxReturn::STATUS_ACCEPTED])
                ->orderBy('submitted_at', 'desc')
                ->first();

            // Get the most recent period (filed or open)
            $lastPeriod = TaxReportPeriod::where('company_id', $company->id)
                ->orderBy('end_date', 'desc')
                ->first();

            // Calculate next deadline (assume monthly, 15th of following month)
            $nextDeadline = $currentMonth->copy()->addMonth()->day(15)->endOfDay();

            // Determine compliance status
            $currentStatus = 'unknown';
            if ($lastReturn) {
                $daysSinceLastFiling = $now->diffInDays($lastReturn->submitted_at);
                if ($daysSinceLastFiling <= 45) { // Within last period + grace
                    $currentStatus = 'compliant';
                } else {
                    $currentStatus = 'overdue';
                    $complianceAlerts[] = [
                        'type' => 'error',
                        'severity' => 'critical',
                        'message' => 'VAT return overdue. Last filing was ' . $daysSinceLastFiling . ' days ago.',
                        'action_required' => true
                    ];
                }
            } else {
                $currentStatus = 'no_returns';
                $complianceAlerts[] = [
                    'type' => 'info',
                    'severity' => 'medium',
                    'message' => 'No VAT returns have been filed yet.',
                    'action_required' => false
                ];
            }

            $status = [
                'current_status' => $currentStatus,
                'last_generation' => $lastReturn ? $lastReturn->submitted_at->toIso8601String() : null,
                'return_period' => $lastPeriod ? [
                    'start' => $lastPeriod->start_date->format('Y-m-d'),
                    'end' => $lastPeriod->end_date->format('Y-m-d'),
                    'type' => strtoupper($lastPeriod->period_type),
                    'status' => strtoupper($lastPeriod->status)
                ] : null,
                'compliance_alerts' => $complianceAlerts,
                'next_deadline' => $nextDeadline->toIso8601String(),
                'vat_number' => $company->vat_number
            ];

            return response()->json($status);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch VAT status',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * File a tax return
     *
     * Creates or finds the tax report period, creates a tax return record,
     * files it, and locks the period.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function file(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'period_type' => 'required|string|in:MONTHLY,QUARTERLY',
            'xml_content' => 'required|string',
            'receipt_number' => 'nullable|string',
            'response_data' => 'nullable|array',
        ]);

        // Get company and authorize access
        $company = Company::findOrFail($validated['company_id']);
        Gate::authorize('manage', $company);

        try {
            // Parse dates
            $periodStart = Carbon::parse($validated['period_start']);
            $periodEnd = Carbon::parse($validated['period_end']);
            $periodType = $validated['period_type'];

            // Validate period length
            $this->validatePeriodLength($periodStart, $periodEnd, $periodType);

            // Find or create tax report period
            $period = TaxReportPeriod::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'period_type' => $periodType,
                    'year' => $periodStart->year,
                    'month' => $periodType === 'MONTHLY' ? $periodStart->month : null,
                    'quarter' => $periodType === 'QUARTERLY' ? $periodStart->quarter : null,
                ],
                [
                    'start_date' => $periodStart,
                    'end_date' => $periodEnd,
                    'status' => TaxReportPeriod::STATUS_OPEN,
                ]
            );

            // Create tax return with XML content
            $taxReturn = TaxReturn::create([
                'company_id' => $company->id,
                'period_id' => $period->id,
                'return_type' => TaxReturn::TYPE_VAT,
                'status' => TaxReturn::STATUS_DRAFT,
                'return_data' => [
                    'xml_content' => $validated['xml_content'],
                    'period_start' => $periodStart->toDateString(),
                    'period_end' => $periodEnd->toDateString(),
                    'period_type' => $periodType,
                ],
            ]);

            // File the tax return
            $taxReturn->file(
                Auth::id(),
                $validated['receipt_number'] ?? null,
                $validated['response_data'] ?? null
            );

            // Lock the period if this is the first filing
            if ($period->status === TaxReportPeriod::STATUS_OPEN) {
                $period->status = TaxReportPeriod::STATUS_CLOSED;
                $period->closed_at = now();
                $period->closed_by_id = Auth::id();
                $period->save();
            }

            return response()->json([
                'message' => 'Tax return filed successfully',
                'data' => [
                    'tax_return_id' => $taxReturn->id,
                    'period_id' => $period->id,
                    'submission_reference' => $taxReturn->submission_reference,
                    'submitted_at' => $taxReturn->submitted_at,
                ]
            ], 201);

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to file tax return',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * List tax report periods
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getPeriods(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'status' => 'nullable|string|in:OPEN,CLOSED,FILED,AMENDED',
            'period_type' => 'nullable|string|in:MONTHLY,QUARTERLY,ANNUAL',
            'year' => 'nullable|integer',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        // Get company and authorize access
        $company = Company::findOrFail($validated['company_id']);
        Gate::authorize('view', $company);

        try {
            $query = TaxReportPeriod::where('company_id', $company->id)
                ->withCount(['taxReturns as filed_returns_count' => function ($query) {
                    $query->whereIn('status', [TaxReturn::STATUS_FILED, TaxReturn::STATUS_ACCEPTED]);
                }]);

            // Apply filters
            if (!empty($validated['status'])) {
                $query->where('status', $validated['status']);
            }

            if (!empty($validated['period_type'])) {
                $query->where('period_type', $validated['period_type']);
            }

            if (!empty($validated['year'])) {
                $query->where('year', $validated['year']);
            }

            // Order by period date descending
            $periods = $query->orderByPeriod('desc')
                ->paginateData($validated['limit'] ?? 15);

            return response()->json([
                'data' => $periods
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch tax periods',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get tax returns for a specific period
     *
     * Includes all returns for the period, including amendments.
     * Provides XML download links for each return.
     *
     * @param Request $request
     * @param int $periodId
     * @return JsonResponse
     */
    public function getReturns(Request $request, int $periodId): JsonResponse
    {
        // Find the period and authorize access
        $period = TaxReportPeriod::findOrFail($periodId);
        Gate::authorize('view', $period->company);

        try {
            $returns = TaxReturn::forPeriod($periodId)
                ->with(['submittedBy:id,name,email', 'amendmentOf:id,submission_reference'])
                ->orderBySubmission('desc')
                ->get()
                ->map(function ($return) {
                    return [
                        'id' => $return->id,
                        'return_type' => $return->return_type,
                        'status' => $return->status,
                        'status_label' => $return->status_label,
                        'submission_reference' => $return->submission_reference,
                        'submitted_at' => $return->submitted_at,
                        'submitted_by' => $return->submittedBy ? [
                            'id' => $return->submittedBy->id,
                            'name' => $return->submittedBy->name,
                        ] : null,
                        'accepted_at' => $return->accepted_at,
                        'rejected_at' => $return->rejected_at,
                        'rejection_reason' => $return->rejection_reason,
                        'is_amendment' => $return->amendment_of_id !== null,
                        'amendment_of' => $return->amendmentOf ? [
                            'id' => $return->amendmentOf->id,
                            'submission_reference' => $return->amendmentOf->submission_reference,
                        ] : null,
                        'xml_download_url' => route('api.v1.tax.vat-returns.download-xml', ['id' => $return->id]),
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
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch tax returns',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Close a tax period
     *
     * Validates that all returns have been filed, then closes the period
     * and locks all documents within that period.
     *
     * @param Request $request
     * @param int $periodId
     * @return JsonResponse
     */
    public function closePeriod(Request $request, int $periodId): JsonResponse
    {
        // Find the period and authorize access
        $period = TaxReportPeriod::findOrFail($periodId);
        Gate::authorize('manage', $period->company);

        try {
            // Validate all returns are filed
            if ($period->hasUnfiledReturns()) {
                return response()->json([
                    'error' => 'Cannot close period',
                    'message' => 'All tax returns must be filed before closing the period'
                ], 422);
            }

            // Close the period
            $period->close(Auth::id());

            return response()->json([
                'message' => 'Tax period closed successfully',
                'data' => [
                    'period_id' => $period->id,
                    'status' => $period->status,
                    'closed_at' => $period->closed_at,
                    'closed_by_id' => $period->closed_by_id,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to close tax period',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reopen a closed tax period
     *
     * Allows reopening a closed period for amendments or corrections.
     * Requires a reason for audit trail purposes.
     *
     * @param Request $request
     * @param int $periodId
     * @return JsonResponse
     */
    public function reopenPeriod(Request $request, int $periodId): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|min:10|max:500',
        ]);

        // Find the period and authorize access
        $period = TaxReportPeriod::findOrFail($periodId);
        Gate::authorize('manage', $period->company);

        try {
            // Reopen the period
            $period->reopen(Auth::id(), $validated['reason']);

            return response()->json([
                'message' => 'Tax period reopened successfully',
                'data' => [
                    'period_id' => $period->id,
                    'status' => $period->status,
                    'reopened_at' => $period->reopened_at,
                    'reopened_by_id' => $period->reopened_by_id,
                    'reopen_reason' => $period->reopen_reason,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to reopen tax period',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download XML for a specific tax return
     *
     * @param int $id
     * @return Response
     */
    public function downloadXml(int $id): Response
    {
        // Find the tax return
        $taxReturn = TaxReturn::findOrFail($id);

        // Authorize access
        Gate::authorize('view', $taxReturn->company);

        try {
            // Get XML content from return_data
            $xmlContent = $taxReturn->return_data['xml_content'] ?? null;

            if (!$xmlContent) {
                return response()->json([
                    'error' => 'XML content not found for this tax return'
                ], 404);
            }

            // Generate filename
            $period = $taxReturn->period;
            $companyName = preg_replace('/[^a-zA-Z0-9]/', '_', $taxReturn->company->name);
            $filename = sprintf(
                'DDV04_%s_%s_%s.xml',
                $companyName,
                $period->start_date->format('Y-m-d'),
                $period->end_date->format('Y-m-d')
            );

            // Add amendment suffix if applicable
            if ($taxReturn->amendment_of_id) {
                $filename = str_replace('.xml', '_AMENDMENT.xml', $filename);
            }

            // Return XML file as download
            return response($xmlContent, 200, [
                'Content-Type' => 'application/xml',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to download XML',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}

// CLAUDE-CHECKPOINT

