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

        $this->authorize('view report', $company);

        // Check if accounting backbone feature is enabled
        if (! $this->isFeatureEnabled()) {
            return response()->view('app.pdf.reports.feature-disabled', [
                'message' => 'Accounting Backbone feature is disabled. Please enable FEATURE_ACCOUNTING_BACKBONE to access IFRS reports.',
            ]);
        }

        $locale = CompanySetting::getSetting('language', $company->id);
        App::setLocale($locale);

        // Get income statement data via IfrsAdapter
        $adapter = new IfrsAdapter;
        $fromDate = $request->has('from_date')
            ? $request->from_date
            : now()->startOfMonth()->toDateString();
        $toDate = $request->has('to_date')
            ? $request->to_date
            : now()->toDateString();

        $incomeStatement = $adapter->getIncomeStatement($company, $fromDate, $toDate);

        // Handle errors from adapter
        if (isset($incomeStatement['error'])) {
            return response()->view('app.pdf.reports.feature-disabled', [
                'message' => $incomeStatement['error'],
            ]);
        }

        // Format dates
        $dateFormat = CompanySetting::getSetting('carbon_date_format', $company->id);
        $formatted_from_date = Carbon::createFromFormat('Y-m-d', $fromDate)
            ->translatedFormat($dateFormat);
        $formatted_to_date = Carbon::createFromFormat('Y-m-d', $toDate)
            ->translatedFormat($dateFormat);

        $currency = Currency::findOrFail(
            CompanySetting::getSetting('currency', $company->id)
        );

        // Get color settings
        $colors = [
            'primary_text_color',
            'heading_text_color',
            'section_heading_text_color',
            'border_color',
            'body_text_color',
            'footer_text_color',
            'footer_total_color',
            'footer_bg_color',
            'date_text_color',
        ];
        $colorSettings = CompanySetting::whereIn('option', $colors)
            ->whereCompany($company->id)
            ->get();

        view()->share([
            'company' => $company,
            'incomeStatement' => $incomeStatement,
            'from_date' => $formatted_from_date,
            'to_date' => $formatted_to_date,
            'colorSettings' => $colorSettings,
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
            return feature('accounting_backbone');
        }

        return config('ifrs.enabled', false) ||
               env('FEATURE_ACCOUNTING_BACKBONE', false);
    }
}

// CLAUDE-CHECKPOINT
