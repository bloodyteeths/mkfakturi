<?php

namespace App\Http\Controllers\V1\Admin\Report;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Currency;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use PDF;

class CustomerSalesReportController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  string  $hash
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request, $hash)
    {
        $company = Company::where('unique_hash', $hash)->first();

        if (! $company) {
            abort(404, 'Company not found');
        }

        $this->authorize('view report', $company);

        $locale = CompanySetting::getSetting('language', $company->id) ?: 'mk';
        App::setLocale($locale);

        $fromDate = $request->from_date ?: now()->startOfMonth()->format('Y-m-d');
        $toDate = $request->to_date ?: now()->endOfMonth()->format('Y-m-d');

        $start = Carbon::createFromFormat('Y-m-d', $fromDate);
        $end = Carbon::createFromFormat('Y-m-d', $toDate);

        $customers = Customer::with(['invoices' => function ($query) use ($start, $end) {
            $query->whereBetween(
                'invoice_date',
                [$start->format('Y-m-d'), $end->format('Y-m-d')]
            );
        }])
            ->where('company_id', $company->id)
            ->applyInvoiceFilters($request->only(['from_date', 'to_date']))
            ->get();

        $totalAmount = 0;
        foreach ($customers as $customer) {
            $customerTotalAmount = 0;
            foreach ($customer->invoices as $invoice) {
                $customerTotalAmount += $invoice->base_total;
            }
            $customer->totalAmount = $customerTotalAmount;
            $totalAmount += $customerTotalAmount;
        }

        $dateFormat = CompanySetting::getSetting('carbon_date_format', $company->id) ?: 'd/m/Y';
        $from_date = Carbon::createFromFormat('Y-m-d', $fromDate)->translatedFormat($dateFormat);
        $to_date = Carbon::createFromFormat('Y-m-d', $toDate)->translatedFormat($dateFormat);

        $currencyId = CompanySetting::getSetting('currency', $company->id);
        $currency = $currencyId ? Currency::find($currencyId) : null;
        if (! $currency) {
            $currency = Currency::where('code', 'MKD')->first() ?: Currency::first();
        }

        view()->share([
            'customers' => $customers,
            'totalAmount' => $totalAmount,
            'company' => $company,
            'from_date' => $from_date,
            'to_date' => $to_date,
            'currency' => $currency,
        ]);

        $pdf = PDF::loadView('app.pdf.reports.sales-customers');

        if ($request->has('preview')) {
            return view('app.pdf.reports.sales-customers');
        }

        if ($request->has('download')) {
            return $pdf->download();
        }

        return $pdf->stream();
    }
}

// CLAUDE-CHECKPOINT
