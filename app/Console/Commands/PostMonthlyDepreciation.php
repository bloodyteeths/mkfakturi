<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\FixedAsset;
use App\Services\DepreciationGLService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class PostMonthlyDepreciation extends Command
{
    protected $signature = 'depreciation:post-monthly
                            {--month= : Month to post (YYYY-MM), defaults to previous month}
                            {--company= : Process only a specific company ID}';

    protected $description = 'Post monthly depreciation GL entries for all companies with active fixed assets';

    public function handle(DepreciationGLService $service): int
    {
        $month = $this->option('month')
            ? Carbon::parse($this->option('month') . '-01')->startOfMonth()
            : Carbon::now()->subMonth()->startOfMonth();

        $this->info("Posting depreciation for month: {$month->format('Y-m')}");

        $query = Company::whereHas('fixedAssets', function ($q) use ($month) {
            $q->active()->where('acquisition_date', '<=', $month->copy()->endOfMonth());
        });

        if ($this->option('company')) {
            $query->where('id', $this->option('company'));
        }

        $companies = $query->get();

        if ($companies->isEmpty()) {
            $this->info('No companies with active fixed assets found.');

            return self::SUCCESS;
        }

        $totalPosted = 0;
        $totalSkipped = 0;
        $totalErrors = 0;

        foreach ($companies as $company) {
            if (! $this->isIfrsEnabled($company->id)) {
                $this->line("  Company #{$company->id} ({$company->name}): IFRS disabled, skipping");

                continue;
            }

            $results = $service->postMonthlyDepreciation($company, $month);
            $totalPosted += $results['posted'];
            $totalSkipped += $results['skipped'];
            $totalErrors += $results['errors'];

            $this->line("  Company #{$company->id} ({$company->name}): {$results['posted']} posted, {$results['skipped']} skipped, {$results['errors']} errors");
        }

        $this->info("Done. Total: {$totalPosted} posted, {$totalSkipped} skipped, {$totalErrors} errors");

        return $totalErrors > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function isIfrsEnabled(int $companyId): bool
    {
        $globalEnabled = config('ifrs.enabled', false) ||
            env('FEATURE_ACCOUNTING_BACKBONE', false) ||
            (function_exists('feature') && feature('accounting-backbone'));

        if (! $globalEnabled) {
            return false;
        }

        $companySetting = CompanySetting::getSetting('ifrs_enabled', $companyId);

        return $companySetting === 'YES' || $companySetting === true || $companySetting === '1';
    }
}
