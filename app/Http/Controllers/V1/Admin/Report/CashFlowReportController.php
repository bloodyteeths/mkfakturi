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

class CashFlowReportController extends Controller
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
        $startDate = $request->query('start_date', now()->startOfYear()->toDateString());
        $endDate = $request->query('end_date', now()->toDateString());

        // Current period
        $current = $adapter->getCashFlowStatement($company, $startDate, $endDate);

        // Previous period (same length, one year back)
        $prevStart = date('Y-m-d', strtotime($startDate . ' -1 year'));
        $prevEnd = date('Y-m-d', strtotime($endDate . ' -1 year'));
        $previous = $adapter->getCashFlowStatement($company, $prevStart, $prevEnd);

        // Build AOP rows from config (same pattern as partner controller)
        $aopConfig = config('ujp_aop.obrazec_38');
        $aopRows = [];

        foreach (['operating', 'investing', 'financing', 'summary'] as $section) {
            foreach ($aopConfig[$section] as $row) {
                $currentVal = 0;
                $previousVal = 0;

                if (! empty($row['data_key'])) {
                    $keys = explode('.', $row['data_key']);
                    $currentVal = $current[$keys[0]][$keys[1]] ?? 0;
                    $previousVal = $previous[$keys[0]][$keys[1]] ?? 0;
                }

                $aopRows[] = [
                    'aop' => $row['aop'],
                    'label' => $row['label'],
                    'indent' => $row['indent'],
                    'is_total' => $row['is_total'] ?? false,
                    'is_summary' => $section === 'summary',
                    'data_key' => $row['data_key'] ?? null,
                    'current' => round($currentVal, 2),
                    'previous' => round($previousVal, 2),
                ];
            }
        }

        $currency = $this->getSafeCurrency($company->id);

        view()->share([
            'company' => $company,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'aopRows' => $aopRows,
            'currency' => $currency,
        ]);

        $pdf = PDF::loadView('app.pdf.reports.cash-flow');

        if ($request->has('preview')) {
            return view('app.pdf.reports.cash-flow');
        }

        if ($request->has('download')) {
            return $pdf->download("cash_flow_{$startDate}_{$endDate}.pdf");
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
