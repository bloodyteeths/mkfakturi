<?php

namespace Modules\Mk\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\InterestNoteMail;
use App\Models\CompanySetting;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Modules\Mk\Models\InterestCalculation;
use Modules\Mk\Services\InterestCalculationService;

class InterestController extends Controller
{
    protected InterestCalculationService $service;

    public function __construct(InterestCalculationService $service)
    {
        $this->service = $service;
    }

    /**
     * List interest calculations with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $query = InterestCalculation::forCompany($companyId)
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
     * Trigger batch calculation for all overdue invoices.
     */
    public function calculate(Request $request): JsonResponse
    {
        $companyId = (int) $request->header('company');
        $asOfDate = $request->input('as_of_date');

        try {
            $calculations = $this->service->batchCalculate($companyId, $asOfDate);
            $saved = $this->service->saveCalculations($companyId, $calculations);

            return response()->json([
                'success' => true,
                'data' => $calculations,
                'saved_count' => count($saved),
                'annual_rate' => $this->service->getAnnualRate($companyId),
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
    public function show(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $calculation = InterestCalculation::forCompany($companyId)
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
     * Generate interest note PDF for selected calculations.
     */
    public function generateNote(Request $request)
    {
        $companyId = (int) $request->header('company');

        $request->validate([
            'customer_id' => 'required|integer',
            'calculation_ids' => 'required|array|min:1',
            'calculation_ids.*' => 'integer',
        ]);

        try {
            $data = $this->service->getInterestNoteData(
                $companyId,
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
    public function sendNote(Request $request): JsonResponse
    {
        $companyId = (int) $request->header('company');

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
                $companyId,
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
    public function waive(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $calculation = InterestCalculation::forCompany($companyId)
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
     * Get summary statistics.
     */
    public function summary(Request $request): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $summary = $this->service->getSummary($companyId);

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * Get the current interest rate for this company.
     */
    public function getRate(Request $request): JsonResponse
    {
        $companyId = (int) $request->header('company');
        $customRate = CompanySetting::getSetting('interest_annual_rate', $companyId);

        return response()->json([
            'success' => true,
            'data' => [
                'annual_rate' => $this->service->getAnnualRate($companyId),
                'is_custom' => $customRate !== null && $customRate !== '',
                'default_rate' => 13.25,
            ],
        ]);
    }

    /**
     * Update the interest rate for this company.
     */
    public function updateRate(Request $request): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $request->validate([
            'annual_rate' => 'required|numeric|min:0|max:100',
        ]);

        CompanySetting::setSettings([
            'interest_annual_rate' => $request->input('annual_rate'),
        ], $companyId);

        return response()->json([
            'success' => true,
            'data' => ['annual_rate' => (float) $request->input('annual_rate')],
            'message' => 'Interest rate updated.',
        ]);
    }

    /**
     * Reset the interest rate to statutory default.
     */
    public function resetRate(Request $request): JsonResponse
    {
        $companyId = (int) $request->header('company');

        CompanySetting::where('company_id', $companyId)
            ->where('option', 'interest_annual_rate')
            ->delete();

        // Clear the specific cache key (works for all drivers including file)
        \Illuminate\Support\Facades\Cache::forget("company:{$companyId}:setting:interest_annual_rate");

        return response()->json([
            'success' => true,
            'data' => ['annual_rate' => 13.25],
            'message' => 'Rate reset to statutory default.',
        ]);
    }
}

// CLAUDE-CHECKPOINT
