<?php

namespace App\Console\Commands;

use App\Services\WacAuditService;
use Illuminate\Console\Command;

class FreezeStockMovementsCommand extends Command
{
    protected $signature = 'stock:freeze-movements
                            {--company= : Company ID (optional, all companies if omitted)}
                            {--dry-run : Show what would be frozen without making changes}';

    protected $description = 'Freeze eligible stock movements to enforce WAC chain immutability';

    public function handle(WacAuditService $auditService): int
    {
        $companyId = $this->option('company');

        if ($this->option('dry-run')) {
            $query = \App\Models\StockMovement::whereNull('frozen_at')
                ->where('created_at', '<', now()->subHours(24));

            if ($companyId) {
                $query->where('company_id', $companyId);
            }

            $count = $query->count();
            $this->info("[DRY RUN] Would freeze {$count} movement(s).");

            return self::SUCCESS;
        }

        if ($companyId) {
            $frozen = $auditService->freezeMovements((int) $companyId);
            $this->info("Frozen {$frozen} movement(s) for company #{$companyId}.");
        } else {
            $companies = \App\Models\Company::pluck('id');
            $totalFrozen = 0;

            foreach ($companies as $id) {
                $frozen = $auditService->freezeMovements($id);
                $totalFrozen += $frozen;
            }

            $this->info("Frozen {$totalFrozen} movement(s) across {$companies->count()} companies.");
        }

        return self::SUCCESS;
    }
}
