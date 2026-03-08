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
     *
     * Uses step-by-step generation: render blade → HTML string → DomPDF → output.
     * Each step is logged independently for diagnostics.
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

        $year = $validated['year'];
        $companyModel = Company::findOrFail($company);
        $debug = ['form' => $formCode, 'company_id' => $company, 'year' => $year];

        try {
            // Step 1: Collect form data
            $data = $service->collect(
                $companyModel,
                $year,
                $validated['month'] ?? null,
                $validated['quarter'] ?? null,
                $validated['overrides'] ?? []
            );
            $debug['step1_collect'] = 'ok';
            $debug['data_keys'] = array_keys($data);

            // Step 2: Build view data
            $viewName = 'app.pdf.reports.ujp.' . $formCode;
            $viewData = $this->buildPdfViewData($service, $companyModel, $data, $formCode, $year);
            $debug['step2_viewdata'] = 'ok';
            $debug['view_name'] = $viewName;
            $debug['view_exists'] = view()->exists($viewName);

            // Step 3: Render blade template to HTML string
            $html = view($viewName, $viewData)->render();
            $debug['step3_html_length'] = strlen($html);
            $debug['step3_html_preview'] = substr(strip_tags($html), 0, 300);

            if (empty($html)) {
                throw new \RuntimeException('Blade template rendered to empty HTML');
            }

            // Step 4: Generate PDF from HTML string (bypasses loadView entirely)
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
            $pdf->setPaper('A4', 'portrait');
            $content = $pdf->output();
            $debug['step4_pdf_length'] = strlen($content ?: '');

            if (empty($content)) {
                // Fallback: try via loadView + output directly
                \Illuminate\Support\Facades\Log::warning('UJP PDF: loadHTML produced empty, trying loadView', $debug);
                $pdf2 = \Barryvdh\DomPDF\Facade\Pdf::loadView($viewName, $viewData);
                $pdf2->setPaper('A4', 'portrait');
                $content = $pdf2->output();
                $debug['step4_fallback_pdf_length'] = strlen($content ?: '');
            }

            if (empty($content)) {
                throw new \RuntimeException(
                    "DomPDF produced empty output (html_length={$debug['step3_html_length']})"
                );
            }

            // Step 5: Validate PDF magic bytes
            $magic = substr($content, 0, 5);
            $debug['step5_magic'] = $magic;

            if ($magic !== '%PDF-') {
                throw new \RuntimeException('Generated content is not valid PDF (magic: ' . bin2hex($magic) . ')');
            }

            $filename = $formCode . '_' . $year . '.pdf';

            \Illuminate\Support\Facades\Log::info('UJP PDF generated successfully', [
                'form' => $formCode,
                'company' => $company,
                'html_length' => $debug['step3_html_length'],
                'pdf_length' => strlen($content),
            ]);

            return response()->json([
                'pdf' => base64_encode($content),
                'filename' => $filename,
                'size' => strlen($content),
                'debug' => $debug,
            ]);
        } catch (\Throwable $e) {
            $debug['error'] = $e->getMessage();
            $debug['error_location'] = basename($e->getFile()) . ':' . $e->getLine();

            \Illuminate\Support\Facades\Log::error('UJP PDF generation failed', [
                'form' => $formCode,
                'company' => $company,
                'debug' => $debug,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Failed to generate PDF',
                'message' => $e->getMessage(),
                'debug' => $debug,
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
