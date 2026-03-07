<?php

namespace App\Http\Controllers\V1\Admin\Report;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Currency;
use App\Services\AopReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use PDF;

class IncomeStatementReportController extends Controller
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

        // Check if accounting backbone feature is enabled
        if (! $this->isFeatureEnabled()) {
            return response()->view('app.pdf.reports.feature-disabled', [
                'message' => 'Accounting Backbone feature is disabled. Please enable FEATURE_ACCOUNTING_BACKBONE to access IFRS reports.',
            ]);
        }

        $locale = CompanySetting::getSetting('language', $company->id) ?: 'mk';
        App::setLocale($locale);

        $fromDate = $request->has('from_date')
            ? $request->from_date
            : now()->startOfYear()->toDateString();
        $toDate = $request->has('to_date')
            ? $request->to_date
            : now()->toDateString();

        $year = (int) Carbon::parse($toDate)->format('Y');

        // Use AopReportService to get Образец 37 data
        $aopService = app(AopReportService::class);
        $aopData = $aopService->getIncomeStatementAop($company, $year);

        // Format dates
        $dateFormat = CompanySetting::getSetting('carbon_date_format', $company->id) ?: 'd/m/Y';
        $formatted_from_date = Carbon::createFromFormat('Y-m-d', $fromDate)
            ->translatedFormat($dateFormat);
        $formatted_to_date = Carbon::createFromFormat('Y-m-d', $toDate)
            ->translatedFormat($dateFormat);

        $currencyId = CompanySetting::getSetting('currency', $company->id);
        $currency = $currencyId ? Currency::find($currencyId) : null;
        if (! $currency) {
            $currency = Currency::where('code', 'MKD')->first() ?: Currency::first();
        }

        view()->share([
            'company' => $company,
            'aopData' => $aopData,
            'from_date' => $formatted_from_date,
            'to_date' => $formatted_to_date,
            'currency' => $currency,
        ]);

        $pdf = PDF::loadView('app.pdf.reports.income-statement');

        if ($request->has('preview')) {
            return view('app.pdf.reports.income-statement');
        }

        if ($request->has('download')) {
            return $pdf->download();
        }

        return $pdf->stream();
    }

    /**
     * Check if accounting backbone feature is enabled
     */
    private function isFeatureEnabled(): bool
    {
        if (function_exists('feature')) {
            return feature('accounting-backbone');
        }

        return config('ifrs.enabled', false) ||
               env('FEATURE_ACCOUNTING_BACKBONE', false);
    }
}

// CLAUDE-CHECKPOINT
