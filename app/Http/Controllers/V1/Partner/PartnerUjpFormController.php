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
     * Generate and download PDF for a client company.
     */
    public function generatePdf(Request $request, $company, string $formCode): JsonResponse
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

            // Generate PDF using output() directly instead of going through
            // Response->getContent() which can return false for binary data
            $pdfResponse = $service->toPdf($companyModel, $data, $validated['year']);
            $content = $pdfResponse->getContent();

            // Fallback: if getContent() returned false/empty, use output buffering
            if (empty($content)) {
                ob_start();
                $pdfResponse->sendContent();
                $content = ob_get_clean();
            }

            // Last resort: regenerate directly via DomPDF output()
            if (empty($content)) {
                $content = $this->regeneratePdfDirect($service, $companyModel, $data, $validated['year'], $formCode);
            }

            $filename = $formCode . '_' . $validated['year'] . '.pdf';

            return response()->json([
                'pdf' => base64_encode((string) $content),
                'filename' => $filename,
                'size' => strlen((string) $content),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('UJP PDF generation failed', [
                'form' => $formCode,
                'company' => $company,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

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

    /**
     * Regenerate PDF directly using DomPDF output() as fallback.
     */
    protected function regeneratePdfDirect(TaxFormService $service, Company $company, array $data, int $year, string $formCode): string
    {
        // Map form code to blade view name
        $viewMap = [
            'ddv-04' => 'ddv-04',
            'db' => 'db',
            'obrazec-36' => 'obrazec-36',
            'obrazec-37' => 'obrazec-37',
        ];

        $viewName = 'app.pdf.reports.ujp.' . ($viewMap[$formCode] ?? $formCode);
        $config = config('ujp_forms.' . str_replace('-', '_', $formCode)) ?? [];

        // Build view data based on form code
        $viewData = [
            'company' => $company,
            'year' => $year,
            'formCode' => $service->formCode(),
            'formTitle' => $service->formTitle(),
            'formSubtitle' => '',
            'sluzhbenVesnik' => $config['sluzhben_vesnik'] ?? '',
            'periodStart' => sprintf('01.01.%d', $year),
            'periodEnd' => sprintf('31.12.%d', $year),
        ];

        // Add form-specific data
        if ($formCode === 'obrazec-36') {
            $viewData['aktiva'] = $data['aktiva'] ?? [];
            $viewData['pasiva'] = $data['pasiva'] ?? [];
            $viewData['totalAktiva'] = $data['total_aktiva'] ?? 0;
            $viewData['totalPasiva'] = $data['total_pasiva'] ?? 0;
            $viewData['isBalanced'] = $data['is_balanced'] ?? false;
        } elseif ($formCode === 'obrazec-37') {
            $viewData['prihodi'] = $data['prihodi'] ?? [];
            $viewData['rashodi'] = $data['rashodi'] ?? [];
            $viewData['rezultat'] = $data['rezultat'] ?? [];
        } elseif ($formCode === 'db') {
            $viewData['aop'] = $data['aop'] ?? [];
            $viewData['config'] = $config;
        } elseif ($formCode === 'ddv-04') {
            $viewData['data'] = $data;
            $viewData['fields'] = $data['fields'] ?? [];
            $viewData['overrides'] = $data['overrides'] ?? [];
            $viewData['periodStart'] = $data['period_start'] ?? '';
            $viewData['periodEnd'] = $data['period_end'] ?? '';
            $viewData['currency'] = $company->currency ?? null;
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($viewName, $viewData);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->output();
    }
}

// CLAUDE-CHECKPOINT
