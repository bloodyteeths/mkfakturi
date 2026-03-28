<?php

namespace Modules\Mk\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\Expense;
use Illuminate\Http\Request;
use Modules\Mk\Services\RashodenNalogService;

/**
 * Rashoden Nalog Controller
 *
 * Generates Расходен налог (Cash Disbursement Voucher) PDFs
 * for expenses and bill payments.
 */
class RashodenNalogController extends Controller
{
    /**
     * Verify the authenticated user has access to the company.
     */
    protected function getCompany(Request $request): Company
    {
        $companyId = (int) $request->header('company');
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated');
        }

        // Super admins can access any company
        if ($user->role === 'super admin') {
            return Company::findOrFail($companyId);
        }

        // Verify user belongs to this company
        $hasAccess = CompanyUser::where('company_id', $companyId)
            ->where('user_id', $user->id)
            ->exists();

        if (! $hasAccess) {
            $isOwner = Company::where('id', $companyId)
                ->where('owner_id', $user->id)
                ->exists();

            if (! $isOwner) {
                abort(403, 'Access denied to this company');
            }
        }

        return Company::findOrFail($companyId);
    }

    /**
     * Generate Расходен налог PDF for an expense.
     *
     * GET /api/v1/expenses/{expense}/rashoden-nalog
     */
    public function forExpense(Request $request, Expense $expense)
    {
        $company = $this->getCompany($request);

        // Verify expense belongs to this company
        if ((int) $expense->company_id !== (int) $company->id) {
            abort(403, 'Expense does not belong to this company');
        }

        try {
            $service = app(RashodenNalogService::class);
            $pdf = $service->generateForExpense($expense, $company);

            return $pdf->download("Rashoden_Nalog_{$expense->expense_number}.pdf");
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Generate Расходен налог PDF for a bill payment.
     *
     * GET /api/v1/bill-payments/{billPayment}/rashoden-nalog
     */
    public function forBillPayment(Request $request, int $billPayment)
    {
        $company = $this->getCompany($request);

        // BillPayment model — resolve manually
        $payment = \App\Models\BillPayment::findOrFail($billPayment);

        // Verify bill payment belongs to this company through the bill
        $payment->loadMissing('bill');
        if ($payment->bill && (int) $payment->bill->company_id !== (int) $company->id) {
            abort(403, 'Bill payment does not belong to this company');
        }

        try {
            $service = app(RashodenNalogService::class);
            $pdf = $service->generateForBillPayment($payment, $company);

            return $pdf->download("Rashoden_Nalog_BP_{$payment->id}.pdf");
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}

// CLAUDE-CHECKPOINT
