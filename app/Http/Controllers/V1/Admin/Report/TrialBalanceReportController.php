<?php

namespace App\Http\Controllers\V1\Admin\Report;

use App\Domain\Accounting\IfrsAdapter;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Currency;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use PDF;

class TrialBalanceReportController extends Controller
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

        // Get trial balance data via IfrsAdapter
        $adapter = app(IfrsAdapter::class);
        $asOfDate = $request->has('as_of_date')
            ? $request->as_of_date
            : now()->toDateString();

        $trialBalance = $adapter->getTrialBalanceSixColumn($company, '2020-01-01', $asOfDate);

        // Handle errors from adapter
        if (isset($trialBalance['error'])) {
            return response()->view('app.pdf.reports.feature-disabled', [
                'message' => $trialBalance['error'],
            ]);
        }

        // Format date
        $dateFormat = CompanySetting::getSetting('carbon_date_format', $company->id) ?: 'd/m/Y';
        $formatted_date = Carbon::createFromFormat('Y-m-d', $asOfDate)
            ->translatedFormat($dateFormat);

        $currencyId = CompanySetting::getSetting('currency', $company->id);
        $currency = $currencyId ? Currency::find($currencyId) : null;
        if (! $currency) {
            $currency = Currency::where('code', 'MKD')->first() ?: Currency::first();
        }

        view()->share([
            'company' => $company,
            'trialBalance' => $trialBalance,
            'as_of_date' => $formatted_date,
            'from_date' => $formatted_date,
            'to_date' => $formatted_date,
            'currency' => $currency,
        ]);

        $pdf = PDF::loadView('app.pdf.reports.trial-balance');

        if ($request->has('preview')) {
            return view('app.pdf.reports.trial-balance');
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
