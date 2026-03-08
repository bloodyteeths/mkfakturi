<?php

namespace App\Http\Controllers\V1\Admin\Tax;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\Tax\TaxFormService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/**
 * UJP Tax Form Controller
 *
 * Unified controller for all UJP tax forms (ДДВ-04, ДБ, Образец 36/37, etc.).
 * Resolves the appropriate TaxFormService by {formCode} URL parameter.
 */
class UjpFormController extends Controller
{
    /**
     * List available UJP forms with status per company.
     */
    public function list(Request $request): JsonResponse
    {
        $companyId = $request->header('company');
        $company = $companyId ? Company::find($companyId) : null;

        if ($company) {
            Gate::authorize('view', $company);
        }

        $forms = [];
        foreach (TaxFormService::registry() as $code => $serviceClass) {
            $service = app($serviceClass);
            $formInfo = [
                'code' => $code,
                'form_code' => $service->formCode(),
                'title' => $service->formTitle(),
                'period_type' => $service->periodType(),
                'return_type' => $service->returnType(),
            ];
            $forms[] = $formInfo;
        }

        return response()->json(['data' => $forms]);
    }

    /**
     * Preview form data (collect + validate).
     */
    public function preview(Request $request, string $formCode): JsonResponse
    {
        $service = $this->resolveService($formCode);
        if (!$service) {
            return response()->json(['error' => 'Unknown form code: ' . $formCode], 404);
        }

        $validated = $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'nullable|integer|min:1|max:12',
            'quarter' => 'nullable|integer|min:1|max:4',
            'overrides' => 'nullable|array',
        ]);

        $company = Company::findOrFail($validated['company_id']);
        Gate::authorize('view', $company);

        try {
            $preview = $service->preview(
                $company,
                $validated['year'],
                $validated['month'] ?? null,
                $validated['quarter'] ?? null,
                $validated['overrides'] ?? []
            );

            return response()->json(['data' => $preview]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to preview form',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate and download XML.
     */
    public function generateXml(Request $request, string $formCode): Response|JsonResponse
    {
        $service = $this->resolveService($formCode);
        if (!$service) {
            return response()->json(['error' => 'Unknown form code: ' . $formCode], 404);
        }

        $validated = $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'nullable|integer|min:1|max:12',
            'quarter' => 'nullable|integer|min:1|max:4',
            'overrides' => 'nullable|array',
        ]);

        $company = Company::findOrFail($validated['company_id']);
        Gate::authorize('view', $company);

        try {
            $data = $service->collect(
                $company,
                $validated['year'],
                $validated['month'] ?? null,
                $validated['quarter'] ?? null,
                $validated['overrides'] ?? []
            );

            $xml = $service->toXml($company, $data);

            $companyName = preg_replace('/[^a-zA-Z0-9]/', '_', $company->name);
            $filename = sprintf(
                '%s_%s_%d.xml',
                str_replace(['/', ' '], '_', $service->formCode()),
                $companyName,
                $validated['year']
            );

            return response($xml, 200, [
                'Content-Type' => 'application/xml',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to generate XML',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate and download PDF.
     */
    public function generatePdf(Request $request, string $formCode): Response|JsonResponse
    {
        $service = $this->resolveService($formCode);
        if (!$service) {
            return response()->json(['error' => 'Unknown form code: ' . $formCode], 404);
        }

        $validated = $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'nullable|integer|min:1|max:12',
            'quarter' => 'nullable|integer|min:1|max:4',
            'overrides' => 'nullable|array',
        ]);

        $company = Company::findOrFail($validated['company_id']);
        Gate::authorize('view', $company);

        try {
            $prevReporting = error_reporting(error_reporting() & ~E_DEPRECATED);

            $data = $service->collect(
                $company,
                $validated['year'],
                $validated['month'] ?? null,
                $validated['quarter'] ?? null,
                $validated['overrides'] ?? []
            );

            $pdfResponse = $service->toPdf($company, $data, $validated['year']);
            $pdfContent = $pdfResponse->getContent();

            error_reporting($prevReporting);

            if (ob_get_length() > 0) {
                ob_clean();
            }

            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Length' => strlen($pdfContent),
                'Content-Disposition' => 'inline; filename="' . $formCode . '_' . $validated['year'] . '.pdf"',
            ]);
        } catch (\Exception $e) {
            if (isset($prevReporting)) {
                error_reporting($prevReporting);
            }

            return response()->json([
                'error' => 'Failed to generate PDF',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * File a tax return (save TaxReturn record).
     */
    public function file(Request $request, string $formCode): JsonResponse
    {
        $service = $this->resolveService($formCode);
        if (!$service) {
            return response()->json(['error' => 'Unknown form code: ' . $formCode], 404);
        }

        $validated = $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'nullable|integer|min:1|max:12',
            'quarter' => 'nullable|integer|min:1|max:4',
            'overrides' => 'nullable|array',
            'receipt_number' => 'nullable|string',
        ]);

        $company = Company::findOrFail($validated['company_id']);
        Gate::authorize('manage', $company);

        try {
            $data = $service->collect(
                $company,
                $validated['year'],
                $validated['month'] ?? null,
                $validated['quarter'] ?? null,
                $validated['overrides'] ?? []
            );

            // Validate before filing
            $validation = $service->validate($data);
            if (!empty($validation['errors'])) {
                return response()->json([
                    'error' => 'Validation failed',
                    'validation' => $validation,
                ], 422);
            }

            $taxReturn = $service->file(
                $company,
                $data,
                $validated['year'],
                $validated['month'] ?? null,
                $validated['quarter'] ?? null,
                $validated['receipt_number'] ?? null
            );

            return response()->json([
                'message' => 'Tax return filed successfully',
                'data' => [
                    'tax_return_id' => $taxReturn->id,
                    'period_id' => $taxReturn->period_id,
                    'form_code' => $service->formCode(),
                    'submitted_at' => $taxReturn->submitted_at,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to file tax return',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Resolve TaxFormService by URL code.
     */
    protected function resolveService(string $formCode): ?TaxFormService
    {
        return TaxFormService::resolve($formCode);
    }
}

// CLAUDE-CHECKPOINT
