<?php

namespace App\Services;

use App\Models\StockMovement;
use App\Models\WacAuditDiscrepancy;
use App\Models\WacAuditRun;
use App\Models\WacCorrectionProposal;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WacAuditService
{
    protected StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Verify WAC chain integrity for a company.
     * Walks all movements chronologically and recalculates expected balances.
     */
    public function verifyChain(int $companyId, ?int $itemId = null, ?int $warehouseId = null, ?int $triggeredBy = null): WacAuditRun
    {
        $auditRun = WacAuditRun::create([
            'company_id' => $companyId,
            'item_id' => $itemId,
            'warehouse_id' => $warehouseId,
            'status' => WacAuditRun::STATUS_RUNNING,
            'triggered_by' => $triggeredBy,
            'started_at' => Carbon::now(),
        ]);

        try {
            $totalChecked = 0;
            $totalDiscrepancies = 0;
            $chainSummaries = [];

            // Get distinct (item_id, warehouse_id) combinations to check
            $query = StockMovement::where('company_id', $companyId)
                ->select('item_id', 'warehouse_id')
                ->groupBy('item_id', 'warehouse_id');

            if ($itemId) {
                $query->where('item_id', $itemId);
            }
            if ($warehouseId) {
                $query->where('warehouse_id', $warehouseId);
            }

            $chains = $query->get();

            foreach ($chains as $chain) {
                $result = $this->verifyOneChain(
                    $auditRun,
                    $companyId,
                    $chain->item_id,
                    $chain->warehouse_id
                );

                $totalChecked += $result['movements_checked'];
                $totalDiscrepancies += $result['discrepancies_found'];

                if ($result['discrepancies_found'] > 0) {
                    $chainSummaries[] = [
                        'item_id' => $chain->item_id,
                        'warehouse_id' => $chain->warehouse_id,
                        'movements_checked' => $result['movements_checked'],
                        'discrepancies_found' => $result['discrepancies_found'],
                        'root_cause_movement_id' => $result['root_cause_movement_id'],
                        'total_value_drift' => $result['total_value_drift'],
                    ];
                }
            }

            $auditRun->update([
                'status' => WacAuditRun::STATUS_COMPLETED,
                'total_movements_checked' => $totalChecked,
                'discrepancies_found' => $totalDiscrepancies,
                'summary' => [
                    'chains_checked' => $chains->count(),
                    'chains_with_errors' => count($chainSummaries),
                    'chain_details' => $chainSummaries,
                ],
                'completed_at' => Carbon::now(),
            ]);

            return $auditRun->fresh();
        } catch (\Exception $e) {
            Log::error('WAC audit failed', [
                'audit_run_id' => $auditRun->id,
                'error' => $e->getMessage(),
            ]);

            $auditRun->update([
                'status' => WacAuditRun::STATUS_FAILED,
                'summary' => ['error' => $e->getMessage()],
                'completed_at' => Carbon::now(),
            ]);

            throw $e;
        }
    }

    /**
     * Verify a single (item, warehouse) chain.
     */
    protected function verifyOneChain(WacAuditRun $auditRun, int $companyId, int $itemId, int $warehouseId): array
    {
        $movements = StockMovement::where('company_id', $companyId)
            ->where('item_id', $itemId)
            ->where('warehouse_id', $warehouseId)
            ->orderBy('movement_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $movementsChecked = 0;
        $discrepanciesFound = 0;
        $rootCauseMovementId = null;
        $totalValueDrift = 0;
        $isFirstDiscrepancy = true;

        // Running expected balance
        $expectedQty = 0.0;
        $expectedValue = 0;

        foreach ($movements as $position => $movement) {
            $movementsChecked++;

            // Recalculate expected balance using same WAC formula as StockService::recordMovement()
            $qty = (float) $movement->quantity;

            if ($qty > 0) {
                // Stock IN: add value at unit_cost
                $unitCost = $movement->unit_cost ?? 0;
                $totalCost = (int) ($qty * $unitCost);
                $expectedValue = $expectedValue + $totalCost;
            } else {
                // Stock OUT: remove value at WAC
                $wac = $expectedQty > 0 ? (int) round($expectedValue / $expectedQty) : 0;
                $totalCost = (int) (abs($qty) * $wac);
                $expectedValue = max(0, $expectedValue - $totalCost);
            }

            $expectedQty = $expectedQty + $qty;

            // Compare with stored values (tolerance: 1 cent for value, 0.0001 for quantity)
            $storedQty = (float) $movement->balance_quantity;
            $storedValue = (int) $movement->balance_value;

            $qtyDrift = round($storedQty - $expectedQty, 4);
            $valueDrift = $storedValue - $expectedValue;

            $hasQtyDrift = abs($qtyDrift) > 0.0001;
            $hasValueDrift = abs($valueDrift) > 1;

            if ($hasQtyDrift || $hasValueDrift) {
                $discrepanciesFound++;
                $totalValueDrift += $valueDrift;

                $isRootCause = $isFirstDiscrepancy;
                if ($isFirstDiscrepancy) {
                    $rootCauseMovementId = $movement->id;
                    $isFirstDiscrepancy = false;
                }

                WacAuditDiscrepancy::create([
                    'audit_run_id' => $auditRun->id,
                    'movement_id' => $movement->id,
                    'item_id' => $itemId,
                    'warehouse_id' => $warehouseId,
                    'chain_position' => $position + 1,
                    'stored_balance_quantity' => $storedQty,
                    'stored_balance_value' => $storedValue,
                    'expected_balance_quantity' => $expectedQty,
                    'expected_balance_value' => $expectedValue,
                    'quantity_drift' => $qtyDrift,
                    'value_drift' => $valueDrift,
                    'is_root_cause' => $isRootCause,
                ]);
            }
        }

        return [
            'movements_checked' => $movementsChecked,
            'discrepancies_found' => $discrepanciesFound,
            'root_cause_movement_id' => $rootCauseMovementId,
            'total_value_drift' => $totalValueDrift,
        ];
    }

    /**
     * Freeze all eligible movements for a company.
     * Freezes movements older than 24h or with a subsequent movement in the same chain.
     */
    public function freezeMovements(int $companyId): int
    {
        $cutoff = Carbon::now()->subHours(24);

        // Freeze movements older than 24h
        $frozenByAge = StockMovement::where('company_id', $companyId)
            ->whereNull('frozen_at')
            ->where('created_at', '<', $cutoff)
            ->update(['frozen_at' => Carbon::now()]);

        return $frozenByAge;
    }

    /**
     * Generate a correction proposal for an audit run's discrepancies.
     * Creates forward-only correction entries — never edits historical records.
     */
    public function generateCorrectionProposal(WacAuditRun $auditRun, ?int $proposedBy = null): ?WacCorrectionProposal
    {
        if (! $auditRun->isCompleted() || ! $auditRun->hasDiscrepancies()) {
            return null;
        }

        // Check for existing pending proposal
        $existing = WacCorrectionProposal::where('audit_run_id', $auditRun->id)
            ->usable()
            ->first();

        if ($existing) {
            return $existing;
        }

        // Group discrepancies by (item_id, warehouse_id) and find the LAST discrepancy in each chain
        // The correction only needs to fix the FINAL drift — not each intermediate step
        $discrepancies = $auditRun->discrepancies()
            ->orderBy('chain_position', 'desc')
            ->get()
            ->groupBy(fn ($d) => $d->item_id . ':' . $d->warehouse_id);

        $correctionEntries = [];
        $netQtyAdjustment = 0.0;
        $netValueAdjustment = 0;
        $descriptions = [];

        foreach ($discrepancies as $key => $chainDiscrepancies) {
            // Last discrepancy in the chain shows the final cumulative drift
            $lastDiscrepancy = $chainDiscrepancies->first(); // Already sorted DESC
            $rootCause = $chainDiscrepancies->firstWhere('is_root_cause', true);

            $qtyDrift = (float) $lastDiscrepancy->quantity_drift;
            $valueDrift = (int) $lastDiscrepancy->value_drift;

            // Skip if drift is negligible
            if (abs($qtyDrift) <= 0.0001 && abs($valueDrift) <= 1) {
                continue;
            }

            // Generate correction entry
            // Quantity corrections: create a stock adjustment to fix quantity
            // Value corrections: create a value-only adjustment
            $entry = [
                'item_id' => $lastDiscrepancy->item_id,
                'warehouse_id' => $lastDiscrepancy->warehouse_id,
                'quantity_adjustment' => -$qtyDrift, // Negate the drift to correct it
                'value_adjustment' => -$valueDrift,
                'source_type' => StockMovement::SOURCE_WAC_CORRECTION,
                'meta' => [
                    'corrects_audit_run_id' => $auditRun->id,
                    'root_cause_movement_id' => $rootCause?->movement_id,
                    'discrepancy_count' => $chainDiscrepancies->count(),
                    'final_drift' => [
                        'quantity' => $qtyDrift,
                        'value' => $valueDrift,
                    ],
                ],
            ];

            $correctionEntries[] = $entry;
            $netQtyAdjustment += -$qtyDrift;
            $netValueAdjustment += -$valueDrift;

            $descriptions[] = "Item #{$lastDiscrepancy->item_id} WH #{$lastDiscrepancy->warehouse_id}: "
                . "qty drift={$qtyDrift}, value drift=" . number_format($valueDrift / 100, 2) . ' MKD';
        }

        if (empty($correctionEntries)) {
            return null;
        }

        return WacCorrectionProposal::create([
            'company_id' => $auditRun->company_id,
            'audit_run_id' => $auditRun->id,
            'status' => WacCorrectionProposal::STATUS_PENDING,
            'description' => 'WAC chain correction: ' . implode('; ', $descriptions),
            'correction_entries' => $correctionEntries,
            'net_quantity_adjustment' => $netQtyAdjustment,
            'net_value_adjustment' => $netValueAdjustment,
            'proposed_by' => $proposedBy,
            'expires_at' => Carbon::now()->addDays(7),
        ]);
    }

    /**
     * Apply an approved correction proposal.
     * Creates correction movements via StockService.
     *
     * @return StockMovement[] Created correction movements
     */
    public function applyCorrectionProposal(WacCorrectionProposal $proposal, int $userId): array
    {
        if (! $proposal->isUsable()) {
            throw new \RuntimeException('Correction proposal is no longer usable (status: ' . $proposal->status . ').');
        }

        $createdMovements = [];

        DB::beginTransaction();
        try {
            foreach ($proposal->correction_entries as $entry) {
                $qtyAdj = (float) ($entry['quantity_adjustment'] ?? 0);
                $valueAdj = (int) ($entry['value_adjustment'] ?? 0);

                // Get current stock to calculate the corrected balance
                $currentStock = $this->stockService->getItemStock(
                    $proposal->company_id,
                    $entry['item_id'],
                    $entry['warehouse_id']
                );

                // For value-only corrections (qty drift is zero), we create a zero-quantity movement
                // that adjusts the balance_value directly
                if (abs($qtyAdj) <= 0.0001 && abs($valueAdj) > 1) {
                    // Value-only correction — create a special adjustment
                    $movement = StockMovement::create([
                        'company_id' => $proposal->company_id,
                        'warehouse_id' => $entry['warehouse_id'],
                        'item_id' => $entry['item_id'],
                        'source_type' => StockMovement::SOURCE_WAC_CORRECTION,
                        'source_id' => null,
                        'quantity' => 0,
                        'unit_cost' => null,
                        'total_cost' => abs($valueAdj),
                        'movement_date' => Carbon::now(),
                        'notes' => 'WAC chain value correction (audit run #' . $proposal->audit_run_id . ')',
                        'balance_quantity' => $currentStock['quantity'],
                        'balance_value' => $currentStock['total_value'] + $valueAdj,
                        'meta' => $entry['meta'] ?? [],
                        'created_by' => $userId,
                    ]);

                    $createdMovements[] = $movement;
                } elseif (abs($qtyAdj) > 0.0001) {
                    // Quantity + value correction — use StockService for proper WAC chain
                    if ($qtyAdj > 0) {
                        // Positive adjustment: need a unit cost
                        $unitCost = $currentStock['weighted_average_cost'] > 0
                            ? $currentStock['weighted_average_cost']
                            : 1; // Minimum 1 cent to avoid zero cost

                        $movement = $this->stockService->recordAdjustment(
                            $proposal->company_id,
                            $entry['warehouse_id'],
                            $entry['item_id'],
                            $qtyAdj,
                            $unitCost,
                            'WAC chain correction (audit run #' . $proposal->audit_run_id . ')',
                            $entry['meta'] ?? [],
                            $userId,
                            true // skip negative check
                        );
                    } else {
                        $movement = $this->stockService->recordAdjustment(
                            $proposal->company_id,
                            $entry['warehouse_id'],
                            $entry['item_id'],
                            $qtyAdj,
                            null,
                            'WAC chain correction (audit run #' . $proposal->audit_run_id . ')',
                            $entry['meta'] ?? [],
                            $userId,
                            true // skip negative check
                        );
                    }

                    $createdMovements[] = $movement;
                }
            }

            $proposal->update([
                'status' => WacCorrectionProposal::STATUS_APPLIED,
                'reviewed_by' => $userId,
                'reviewed_at' => Carbon::now(),
                'applied_at' => Carbon::now(),
            ]);

            DB::commit();

            Log::info('WAC correction proposal applied', [
                'proposal_id' => $proposal->id,
                'audit_run_id' => $proposal->audit_run_id,
                'movements_created' => count($createdMovements),
                'applied_by' => $userId,
            ]);

            return $createdMovements;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to apply WAC correction', [
                'proposal_id' => $proposal->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
