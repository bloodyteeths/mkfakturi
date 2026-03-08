<?php

namespace App\Http\Controllers\V1\Admin\Report;

use App\Domain\Accounting\IfrsAdapter;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use PDF;

class EquityChangesReportController extends Controller
{
    public function __invoke(Request $request, $hash)
    {
        $company = Company::where('unique_hash', $hash)->first();

        if (! $company) {
            abort(404, 'Company not found');
        }

        $this->authorize('view report', $company);

        if (! $this->isFeatureEnabled()) {
            return response()->view('app.pdf.reports.feature-disabled', [
                'message' => 'Accounting Backbone feature is disabled.',
            ]);
        }

        $locale = CompanySetting::getSetting('language', $company->id) ?: 'mk';
        App::setLocale($locale);

        $company->load('address');

        $adapter = app(IfrsAdapter::class);
        $year = (int) $request->query('year', now()->year);

        $current = $adapter->getEquityChanges($company, $year);
        $previous = $adapter->getEquityChanges($company, $year - 1);

        $currency = $this->getSafeCurrency($company->id);

        view()->share([
            'company' => $company,
            'year' => $year,
            'current' => $current,
            'previous' => $previous,
            'currency' => $currency,
        ]);

        $pdf = PDF::loadView('app.pdf.reports.equity-changes');

        if ($request->has('preview')) {
            return view('app.pdf.reports.equity-changes');
        }

        if ($request->has('download')) {
            return $pdf->download("equity_changes_{$year}.pdf");
        }

        return $pdf->stream();
    }

    private function getSafeCurrency($companyId): Currency
    {
        $currencyId = CompanySetting::getSetting('currency', $companyId);
        $currency = $currencyId ? Currency::find($currencyId) : null;
        if (! $currency) {
            $currency = Currency::where('code', 'MKD')->first() ?: Currency::first();
        }

        return $currency;
    }

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
