<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\FiscalDevice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Mk\Services\FiscalFraudDetectionService;

class FiscalFraudCheck extends Command
{
    protected $signature = 'fiscal:fraud-check {--company= : Specific company ID} {--date= : Date to check (default: yesterday)}';

    protected $description = 'Run daily fiscal fraud detection checks across all companies with fiscal devices';

    public function handle(): int
    {
        $service = app(FiscalFraudDetectionService::class);

        $date = $this->option('date')
            ? \Carbon\Carbon::parse($this->option('date'))
            : \Carbon\Carbon::yesterday();

        if ($this->option('company')) {
            $companyIds = [(int) $this->option('company')];
        } else {
            $companyIds = FiscalDevice::active()
                ->distinct()
                ->pluck('company_id')
                ->toArray();
        }

        $this->info("Running fiscal fraud checks for {$date->toDateString()} across " . count($companyIds) . ' companies');

        $totalAlerts = 0;

        foreach ($companyIds as $companyId) {
            $alerts = $service->runDailyChecks($companyId, $date);
            $totalAlerts += count($alerts);

            if (count($alerts) > 0) {
                $this->warn("Company {$companyId}: " . count($alerts) . ' alert(s) generated');
            }
        }

        $this->info("Done. {$totalAlerts} total alert(s) generated.");

        if ($totalAlerts > 0) {
            Log::warning('Fiscal fraud daily check completed', [
                'date' => $date->toDateString(),
                'companies_checked' => count($companyIds),
                'total_alerts' => $totalAlerts,
            ]);
        }

        return Command::SUCCESS;
    }
}

// CLAUDE-CHECKPOINT
