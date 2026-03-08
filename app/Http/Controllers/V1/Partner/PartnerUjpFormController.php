<?php

namespace App\Http\Controllers\V1\Partner;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Partner;
use App\Services\Tax\TaxFormService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Partner UJP Tax Form Controller
 *
 * Provides partner access to all UJP tax forms for client companies.
 * Wraps the TaxFormService with partner access checks.
 */
class PartnerUjpFormController extends Controller
{
    /**
     * List available UJP forms.
     */
    public function list(Request $request): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }

        $forms = [];
        foreach (TaxFormService::registry() as $code => $serviceClass) {
            $service = app($serviceClass);
            $forms[] = [
                'code' => $code,
                'form_code' => $service->formCode(),
                'title' => $service->formTitle(),
                'period_type' => $service->periodType(),
                'return_type' => $service->returnType(),
            ];
        }

        return response()->json(['data' => $forms]);
    }

    /**
     * Preview form data for a client company.
     */
    public function preview(Request $request, $company, string $formCode): JsonResponse
    {
        $company = (int) $company;
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $service = TaxFormService::resolve($formCode);
        if (!$service) {
            return response()->json(['error' => 'Unknown form code: ' . $formCode], 404);
        }

        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'nullable|integer|min:1|max:12',
            'quarter' => 'nullable|integer|min:1|max:4',
            'overrides' => 'nullable|array',
        ]);

        $companyModel = Company::findOrFail($company);

        try {
            $preview = $service->preview(
                $companyModel,
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
     * Generate and download XML for a client company.
     */
    public function generateXml(Request $request, $company, string $formCode): Response|JsonResponse
    {
        $company = (int) $company;
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $service = TaxFormService::resolve($formCode);
        if (!$service) {
            return response()->json(['error' => 'Unknown form code: ' . $formCode], 404);
        }

        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'nullable|integer|min:1|max:12',
            'quarter' => 'nullable|integer|min:1|max:4',
            'overrides' => 'nullable|array',
        ]);

        $companyModel = Company::findOrFail($company);

        try {
            $data = $service->collect(
                $companyModel,
                $validated['year'],
                $validated['month'] ?? null,
                $validated['quarter'] ?? null,
                $validated['overrides'] ?? []
            );

            $xml = $service->toXml($companyModel, $data);

            $companyName = preg_replace('/[^a-zA-Z0-9]/', '_', $companyModel->name);
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
     * Generate and stream PDF for a client company.
     *
     * Uses $service->toPdf() — same approach as the admin UjpFormController.
     */
    public function generatePdf(Request $request, $company, string $formCode): Response|JsonResponse
    {
        $company = (int) $company;
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $service = TaxFormService::resolve($formCode);
        if (!$service) {
            return response()->json(['error' => 'Unknown form code: ' . $formCode], 404);
        }

        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'nullable|integer|min:1|max:12',
            'quarter' => 'nullable|integer|min:1|max:4',
            'overrides' => 'nullable|array',
        ]);

        $companyModel = Company::findOrFail($company);

        try {
            // Suppress deprecation warnings from IFRS package during PDF generation
            // to prevent them from corrupting the binary PDF response.
            // Production already excludes E_DEPRECATED via php.ini error_reporting.
            $prevReporting = error_reporting(error_reporting() & ~E_DEPRECATED);

            $data = $service->collect(
                $companyModel,
                $validated['year'],
                $validated['month'] ?? null,
                $validated['quarter'] ?? null,
                $validated['overrides'] ?? []
            );

            $pdfResponse = $service->toPdf($companyModel, $data, $validated['year']);
            $pdfContent = $pdfResponse->getContent();

            error_reporting($prevReporting);

            // Clean any stray output in the buffer (deprecation warnings from autoloading)
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
     * File a tax return for a client company.
     */
    public function file(Request $request, $company, string $formCode): JsonResponse
    {
        $company = (int) $company;
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $service = TaxFormService::resolve($formCode);
        if (!$service) {
            return response()->json(['error' => 'Unknown form code: ' . $formCode], 404);
        }

        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'nullable|integer|min:1|max:12',
            'quarter' => 'nullable|integer|min:1|max:4',
            'overrides' => 'nullable|array',
            'receipt_number' => 'nullable|string',
        ]);

        $companyModel = Company::findOrFail($company);

        try {
            $data = $service->collect(
                $companyModel,
                $validated['year'],
                $validated['month'] ?? null,
                $validated['quarter'] ?? null,
                $validated['overrides'] ?? []
            );

            $validation = $service->validate($data);
            if (!empty($validation['errors'])) {
                return response()->json([
                    'error' => 'Validation failed',
                    'validation' => $validation,
                ], 422);
            }

            $taxReturn = $service->file(
                $companyModel,
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
