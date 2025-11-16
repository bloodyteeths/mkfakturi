<?php

namespace App\Http\Controllers\V1\Admin\AccountsPayable;

use App\Http\Controllers\Controller;
use App\Http\Resources\SupplierResource;
use App\Models\Bill;
use App\Models\BillPayment;
use App\Models\CompanySetting;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SupplierStatsController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request, Supplier $supplier)
    {
        $this->authorize('view', $supplier);

        $i = 0;
        $months = [];
        $billTotals = [];
        $expenseTotals = [];
        $paymentTotals = [];
        $netProfits = [];
        $monthCounter = 0;
        $fiscalYear = CompanySetting::getSetting('fiscal_year', $request->header('company'));
        $startDate = Carbon::now();
        $start = Carbon::now();
        $end = Carbon::now();
        $terms = explode('-', $fiscalYear);
        $companyStartMonth = intval($terms[0]);

        if ($companyStartMonth <= $start->month) {
            $startDate->month($companyStartMonth)->startOfMonth();
            $start->month($companyStartMonth)->startOfMonth();
            $end->month($companyStartMonth)->endOfMonth();
        } else {
            $startDate->subYear()->month($companyStartMonth)->startOfMonth();
            $start->subYear()->month($companyStartMonth)->startOfMonth();
            $end->subYear()->month($companyStartMonth)->endOfMonth();
        }

        if ($request->has('previous_year')) {
            $startDate->subYear()->startOfMonth();
            $start->subYear()->startOfMonth();
            $end->subYear()->endOfMonth();
        }

        while ($monthCounter < 12) {
            array_push(
                $billTotals,
                Bill::whereBetween(
                    'bill_date',
                    [$start->format('Y-m-d'), $end->format('Y-m-d')]
                )
                    ->whereCompany()
                    ->where('supplier_id', $supplier->id)
                    ->sum('total') ?? 0
            );
            array_push(
                $expenseTotals,
                0 // Expenses don't have supplier_id, so we set to 0
            );
            array_push(
                $paymentTotals,
                BillPayment::whereBetween(
                    'payment_date',
                    [$start->format('Y-m-d'), $end->format('Y-m-d')]
                )
                    ->whereCompany()
                    ->whereHas('bill', function ($query) use ($supplier) {
                        $query->where('supplier_id', $supplier->id);
                    })
                    ->sum('amount') ?? 0
            );
            array_push(
                $netProfits,
                ($billTotals[$i] - $paymentTotals[$i]) // Net profit = bills - payments (amount owed)
            );
            $i++;
            array_push($months, $start->translatedFormat('M'));
            $monthCounter++;
            $end->startOfMonth();
            $start->addMonth()->startOfMonth();
            $end->addMonth()->endOfMonth();
        }

        $start->subMonth()->endOfMonth();

        $billsTotal = Bill::whereBetween(
            'bill_date',
            [$startDate->format('Y-m-d'), $start->format('Y-m-d')]
        )
            ->whereCompany()
            ->where('supplier_id', $supplier->id)
            ->sum('total');

        $paymentsTotal = BillPayment::whereBetween(
            'payment_date',
            [$startDate->format('Y-m-d'), $start->format('Y-m-d')]
        )
            ->whereCompany()
            ->whereHas('bill', function ($query) use ($supplier) {
                $query->where('supplier_id', $supplier->id);
            })
            ->sum('amount');

        $totalExpenses = 0; // Expenses don't have supplier_id

        $netProfit = (int) $billsTotal - (int) $paymentsTotal; // Amount still owed to supplier

        $chartData = [
            'months' => $months,
            'billTotals' => $billTotals,
            'expenseTotals' => $expenseTotals,
            'paymentTotals' => $paymentTotals,
            'netProfit' => $netProfit,
            'netProfits' => $netProfits,
            'billsTotal' => $billsTotal,
            'paymentsTotal' => $paymentsTotal,
            'totalExpenses' => $totalExpenses,
        ];

        $supplier = Supplier::with(['currency'])->find($supplier->id);

        return (new SupplierResource($supplier))
            ->additional(['meta' => [
                'chartData' => $chartData,
            ]]);
    }
}
// CLAUDE-CHECKPOINT
