<?php

namespace App\Console\Commands;

use App\Models\WacAuditRun;
use App\Services\WacAuditService;
use Illuminate\Console\Command;

class VerifyWacChainCommand extends Command
{
    protected $signature = 'stock:verify-wac
                            {--company= : Company ID (required)}
                            {--item= : Item ID (optional, all items if omitted)}
                            {--warehouse= : Warehouse ID (optional, all warehouses if omitted)}
                            {--fix-preview : Show proposed corrections without applying}
                            {--freeze : Freeze all eligible movements after verification}';

    protected $description = 'Verify WAC chain integrity and detect discrepancies';

    public function handle(WacAuditService $auditService): int
    {
        $companyId = $this->option('company');

        if (! $companyId) {
            $this->error('--company is required.');

            return self::FAILURE;
        }

        $itemId = $this->option('item') ? (int) $this->option('item') : null;
        $warehouseId = $this->option('warehouse') ? (int) $this->option('warehouse') : null;

        $scope = "Company #{$companyId}";
        if ($itemId) {
            $scope .= ", Item #{$itemId}";
        }
        if ($warehouseId) {
            $scope .= ", Warehouse #{$warehouseId}";
        }

        $this->info("WAC Chain Verification for {$scope}");
        $this->info(str_repeat('=', 50));
        $this->newLine();

        try {
            $auditRun = $auditService->verifyChain(
                (int) $companyId,
                $itemId,
                $warehouseId
            );

            $this->displayResults($auditRun);

            if ($this->option('fix-preview') && $auditRun->hasDiscrepancies()) {
                $this->newLine();
                $this->info('Generating correction proposal (preview only)...');
                $proposal = $auditService->generateCorrectionProposal($auditRun);

                if ($proposal) {
                    $this->newLine();
                    $this->warn('Proposed corrections:');
                    foreach ($proposal->correction_entries as $entry) {
                        $this->line(sprintf(
                            '  Item #%d, WH #%d: qty_adj=%.4f, value_adj=%s MKD',
                            $entry['item_id'],
                            $entry['warehouse_id'],
                            $entry['quantity_adjustment'] ?? 0,
                            number_format(($entry['value_adjustment'] ?? 0) / 100, 2)
                        ));
                    }
                    $this->line(sprintf(
                        '  Net: qty=%.4f, value=%s MKD',
                        $proposal->net_quantity_adjustment,
                        number_format($proposal->net_value_adjustment / 100, 2)
                    ));
                    $this->warn('Use the web UI to approve these corrections.');
                } else {
                    $this->info('No corrections needed (all drifts are negligible).');
                }
            }

            if ($this->option('freeze')) {
                $this->newLine();
                $frozen = $auditService->freezeMovements((int) $companyId);
                $this->info("Frozen {$frozen} movement(s).");
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Verification failed: ' . $e->getMessage());

            return self::FAILURE;
        }
    }

    protected function displayResults(WacAuditRun $auditRun): void
    {
        $this->info("Audit Run #{$auditRun->id} — Status: {$auditRun->status}");
        $this->line("Movements checked: {$auditRun->total_movements_checked}");
        $this->line("Discrepancies found: {$auditRun->discrepancies_found}");

        if (! $auditRun->hasDiscrepancies()) {
            $this->newLine();
            $this->info('All WAC chains are consistent. No discrepancies found.');

            return;
        }

        $this->newLine();
        $this->warn("Found {$auditRun->discrepancies_found} discrepancies:");

        $summary = $auditRun->summary ?? [];
        $chainDetails = $summary['chain_details'] ?? [];

        foreach ($chainDetails as $chain) {
            $this->newLine();
            $this->line(sprintf(
                'Item #%d, Warehouse #%d: %d discrepancies, root cause at movement #%s',
                $chain['item_id'],
                $chain['warehouse_id'],
                $chain['discrepancies_found'],
                $chain['root_cause_movement_id'] ?? 'unknown'
            ));
            $this->line(sprintf(
                '  Total value drift: %s MKD',
                number_format(($chain['total_value_drift'] ?? 0) / 100, 2)
            ));
        }

        // Show detailed discrepancy table for small result sets
        $discrepancies = $auditRun->discrepancies()->with('movement')->get();

        if ($discrepancies->count() <= 20) {
            $this->newLine();
            $this->table(
                ['#', 'Mvmt ID', 'Pos', 'Stored Qty', 'Expected Qty', 'Stored Val', 'Expected Val', 'Root?'],
                $discrepancies->map(fn ($d) => [
                    $d->id,
                    $d->movement_id,
                    $d->chain_position,
                    number_format($d->stored_balance_quantity, 4),
                    number_format($d->expected_balance_quantity, 4),
                    number_format($d->stored_balance_value / 100, 2),
                    number_format($d->expected_balance_value / 100, 2),
                    $d->is_root_cause ? 'YES' : '',
                ])->toArray()
            );
        }
    }
}
