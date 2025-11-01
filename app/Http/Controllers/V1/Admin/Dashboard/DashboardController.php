<?php

namespace App\Http\Controllers\V1\Admin\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Estimate;
use App\Models\Invoice;
use App\Services\DashboardMetricsService;
use Illuminate\Http\Request;
use Silber\Bouncer\BouncerFacade;

class DashboardController extends Controller
{
    public function __construct(private DashboardMetricsService $metrics)
    {
    }

    /**
     * Handle the incoming request.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request)
    {
        $company = Company::find($request->header('company'));

        $this->authorize('view dashboard', $company);

        $series = $this->metrics->getAnnualSeries((int) $company->id, $request->boolean('previous_year'));
        $counts = $this->metrics->getCounts((int) $company->id);
        $recent = $this->metrics->getRecentEntities((int) $company->id);

        return response()->json([
            'total_amount_due' => $counts['total_amount_due'],
            'total_customer_count' => $counts['total_customer_count'],
            'total_invoice_count' => $counts['total_invoice_count'],
            'total_estimate_count' => $counts['total_estimate_count'],

            'recent_due_invoices' => BouncerFacade::can('view-invoice', Invoice::class) ? $recent['invoices'] : [],
            'recent_estimates' => BouncerFacade::can('view-estimate', Estimate::class) ? $recent['estimates'] : [],

            'chart_data' => [
                'months' => $series['months'],
                'invoice_totals' => $series['invoice_totals'],
                'expense_totals' => $series['expense_totals'],
                'receipt_totals' => $series['receipt_totals'],
                'net_income_totals' => $series['net_income_totals'],
            ],

            'total_sales' => $series['total_sales'],
            'total_receipts' => $series['total_receipts'],
            'total_expenses' => $series['total_expenses'],
            'total_net_income' => $series['total_receipts'] - $series['total_expenses'],
        ]);
    }
}
