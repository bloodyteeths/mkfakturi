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
     * Generate and return raw PDF binary for a client company.
     *
     * Returns raw application/pdf binary (NOT base64 JSON) to avoid
     * Railway proxy truncation of large (~198KB) JSON responses.
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

        $year = $validated['year'];
        $companyModel = Company::findOrFail($company);

        // Capture stray output (DomPDF warnings) that would corrupt the response
        ob_start();

        try {
            $data = $service->collect(
                $companyModel,
                $year,
                $validated['month'] ?? null,
                $validated['quarter'] ?? null,
                $validated['overrides'] ?? []
            );

            $viewName = 'app.pdf.reports.ujp.' . $formCode;
            $viewData = $this->buildPdfViewData($service, $companyModel, $data, $formCode, $year);
            $html = view($viewName, $viewData)->render();

            if (empty($html)) {
                throw new \RuntimeException('Blade template rendered to empty HTML');
            }

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
            $pdf->setPaper('A4', 'portrait');
            $content = $pdf->output();

            if (empty($content)) {
                throw new \RuntimeException('DomPDF produced empty output');
            }

            if (substr($content, 0, 5) !== '%PDF-') {
                throw new \RuntimeException('Generated content is not valid PDF');
            }

            // Discard stray output
            ob_get_clean();

            $filename = $formCode . '_' . $year . '.pdf';

            return response($content, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Length' => strlen($content),
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
            ]);
        } catch (\Throwable $e) {
            if (ob_get_level()) {
                ob_get_clean();
            }

            \Illuminate\Support\Facades\Log::error('UJP PDF generation failed', [
                'form' => $formCode,
                'company' => $company,
                'error' => $e->getMessage(),
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
     * Build view data array for a given form code (used by generatePdf).
     */
    protected function buildPdfViewData(TaxFormService $service, Company $company, array $data, string $formCode, int $year): array
    {
        $config = config('ujp_forms.' . str_replace('-', '_', $formCode)) ?? [];

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

        return $viewData;
    }
}

// CLAUDE-CHECKPOINT
