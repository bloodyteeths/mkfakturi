<?php

namespace App\Http\Controllers\V1\Partner;

use App\Http\Controllers\Controller;
use App\Mail\InterestNoteMail;
use App\Models\CompanySetting;
use App\Models\Customer;
use App\Models\Partner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Modules\Mk\Models\InterestCalculation;
use Modules\Mk\Services\InterestCalculationService;

class PartnerInterestController extends Controller
{
    protected InterestCalculationService $service;

    public function __construct(InterestCalculationService $service)
    {
        $this->service = $service;
    }

    /**
     * List interest calculations for a partner's client company.
     */
    public function index(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $query = InterestCalculation::forCompany($company)
            ->with(['customer:id,name', 'invoice:id,invoice_number,total,due_amount,due_date'])
            ->orderBy('calculation_date', 'desc');

        if ($status = $request->query('status')) {
            $query->byStatus($status);
        }

        if ($customerId = $request->query('customer_id')) {
            $query->where('customer_id', (int) $customerId);
        }

        if ($dateFrom = $request->query('date_from')) {
            $query->where('calculation_date', '>=', $dateFrom);
        }

        if ($dateTo = $request->query('date_to')) {
            $query->where('calculation_date', '<=', $dateTo);
        }

        $limit = $request->query('limit', 15);
        if ($limit === 'all') {
            return response()->json([
                'success' => true,
                'data' => $query->get(),
            ]);
        }

        $calculations = $query->paginate((int) $limit);

        return response()->json([
            'success' => true,
            'data' => $calculations->items(),
            'meta' => [
                'current_page' => $calculations->currentPage(),
                'last_page' => $calculations->lastPage(),
                'per_page' => $calculations->perPage(),
                'total' => $calculations->total(),
            ],
        ]);
    }

    /**
     * Trigger batch calculation.
     */
    public function calculate(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $asOfDate = $request->input('as_of_date');

        try {
            $calculations = $this->service->batchCalculate($company, $asOfDate);
            $saved = $this->service->saveCalculations($company, $calculations);

            return response()->json([
                'success' => true,
                'data' => $calculations,
                'saved_count' => count($saved),
                'annual_rate' => $this->service->getAnnualRate($company),
                'message' => sprintf('Calculated interest for %d overdue invoices.', count($calculations)),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Show a single interest calculation.
     */
    public function show(Request $request, int $company, int $id): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $calculation = InterestCalculation::forCompany($company)
            ->with(['customer', 'invoice', 'interestInvoice'])
            ->where('id', $id)
            ->first();

        if (! $calculation) {
            return response()->json(['success' => false, 'message' => 'Interest calculation not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $calculation,
        ]);
    }

    /**
     * Generate interest note PDF.
     */
    public function generateNote(Request $request, int $company)
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $request->validate([
            'customer_id' => 'required|integer',
            'calculation_ids' => 'required|array|min:1',
            'calculation_ids.*' => 'integer',
        ]);

        try {
            $data = $this->service->getInterestNoteData(
                $company,
                (int) $request->input('customer_id'),
                $request->input('calculation_ids')
            );

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('app.pdf.reports.interest-note', $data);
            $pdf->setPaper('A4', 'portrait');

            // Mark as invoiced after successful PDF generation
            InterestCalculation::whereIn('id', $data['calculation_ids'])
                ->where('status', 'calculated')
                ->update(['status' => 'invoiced']);

            return $pdf->download("kamatna-nota-{$data['note_number']}.pdf");
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Send interest note PDF via email to the customer.
     */
    public function sendNote(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $request->validate([
            'customer_id' => 'required|integer',
            'calculation_ids' => 'required|array|min:1',
            'calculation_ids.*' => 'integer',
        ]);

        try {
            $customerId = (int) $request->input('customer_id');
            $customer = Customer::find($customerId);

            if (! $customer || ! $customer->email) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer has no email address.',
                ], 422);
            }

            $data = $this->service->getInterestNoteData(
                $company,
                $customerId,
                $request->input('calculation_ids')
            );

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('app.pdf.reports.interest-note', $data);
            $pdf->setPaper('A4', 'portrait');

            Mail::to($customer->email)->send(new InterestNoteMail($data, $pdf));

            // Mark as invoiced after successful send
            InterestCalculation::whereIn('id', $data['calculation_ids'])
                ->where('status', 'calculated')
                ->update(['status' => 'invoiced']);

            return response()->json([
                'success' => true,
                'message' => "Interest note sent to {$customer->email}.",
                'email' => $customer->email,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Waive an interest calculation.
     */
    public function waive(Request $request, int $company, int $id): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $calculation = InterestCalculation::forCompany($company)
            ->where('id', $id)
            ->first();

        if (! $calculation) {
            return response()->json(['success' => false, 'message' => 'Interest calculation not found'], 404);
        }

        try {
            $waived = $this->service->waive($calculation);

            return response()->json([
                'success' => true,
                'data' => $waived,
                'message' => 'Interest calculation waived.',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Summary statistics.
     */
    public function summary(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $summary = $this->service->getSummary($company);

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * Get the current interest rate for a company.
     */
    public function getRate(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $customRate = CompanySetting::getSetting('interest_annual_rate', $company);

        return response()->json([
            'success' => true,
            'data' => [
                'annual_rate' => $this->service->getAnnualRate($company),
                'is_custom' => $customRate !== null && $customRate !== '',
                'default_rate' => 13.25,
            ],
        ]);
    }

    /**
     * Update the interest rate for a company.
     */
    public function updateRate(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $request->validate([
            'annual_rate' => 'required|numeric|min:0|max:100',
        ]);

        CompanySetting::setSettings([
            'interest_annual_rate' => $request->input('annual_rate'),
        ], $company);

        return response()->json([
            'success' => true,
            'data' => ['annual_rate' => (float) $request->input('annual_rate')],
            'message' => 'Interest rate updated.',
        ]);
    }

    /**
     * Reset the interest rate to statutory default.
     */
    public function resetRate(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        CompanySetting::where('company_id', $company)
            ->where('option', 'interest_annual_rate')
            ->delete();

        // Clear the specific cache key (works for all drivers including file)
        Cache::forget("company:{$company}:setting:interest_annual_rate");

        return response()->json([
            'success' => true,
            'data' => ['annual_rate' => 13.25],
            'message' => 'Rate reset to statutory default.',
        ]);
    }

    /**
     * Get customers for a company (for filter dropdown).
     */
    public function customers(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $customers = Customer::where('company_id', $company)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $customers,
        ]);
    }

    // ---- Partner access helpers ----

    protected function getPartnerFromRequest(Request $request): ?Partner
    {
        $user = $request->user();

        if (! $user) {
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
