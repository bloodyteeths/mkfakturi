<?php

namespace Modules\Mk\Services;

use App\Models\Bill;
use App\Models\Expense;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Modules\Mk\Models\CostCenterRule;

class CostCenterAutoAssigner
{
    /**
     * Auto-assign a cost center to a document based on matching rules.
     *
     * Skips if the document already has a cost_center_id (user explicitly set it).
     * Uses saveQuietly() to avoid re-triggering observer loops.
     * Fails silently — never blocks document creation.
     */
    public function assignIfMatched(Model $document): void
    {
        try {
            // Don't override if user already set a cost center
            if ($document->cost_center_id) {
                return;
            }

            $companyId = (int) $document->company_id;
            if (! $companyId) {
                return;
            }

            $context = $this->buildContext($document);
            if (empty($context)) {
                return;
            }

            $costCenterId = CostCenterRule::matchDocument($companyId, $context);

            if ($costCenterId) {
                $document->cost_center_id = $costCenterId;
                $document->saveQuietly();

                Log::info('CostCenterAutoAssigner: assigned cost center', [
                    'document_type' => class_basename($document),
                    'document_id' => $document->id,
                    'cost_center_id' => $costCenterId,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('CostCenterAutoAssigner: failed to assign', [
                'document_type' => class_basename($document),
                'document_id' => $document->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Build the matching context array from a document.
     */
    protected function buildContext(Model $document): array
    {
        if ($document instanceof Invoice) {
            return [
                'vendor_id' => $document->customer_id,
                'description' => $document->notes,
                'item_ids' => $document->items()->pluck('item_id')->filter()->map(fn ($id) => (int) $id)->toArray(),
                'account_code' => null,
            ];
        }

        if ($document instanceof Bill) {
            return [
                'vendor_id' => $document->supplier_id,
                'description' => $document->notes,
                'item_ids' => $document->items()->pluck('item_id')->filter()->map(fn ($id) => (int) $id)->toArray(),
                'account_code' => null,
            ];
        }

        if ($document instanceof Expense) {
            return [
                'vendor_id' => $document->supplier_id ?: $document->customer_id,
                'description' => $document->notes,
                'item_ids' => [],
                'account_code' => null,
            ];
        }

        return [];
    }
}

// CLAUDE-CHECKPOINT
