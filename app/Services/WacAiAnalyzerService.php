<?php

namespace App\Services;

use App\Models\WacAuditRun;
use App\Services\AiProvider\GeminiProvider;
use Illuminate\Support\Facades\Log;

class WacAiAnalyzerService
{
    protected GeminiProvider $gemini;

    public function __construct(GeminiProvider $gemini)
    {
        $this->gemini = $gemini;
    }

    /**
     * Analyze discrepancies in an audit run using Gemini AI.
     * Categorizes errors and provides root cause explanation.
     */
    public function analyzeDiscrepancies(WacAuditRun $auditRun): array
    {
        $discrepancies = $auditRun->discrepancies()
            ->with(['movement', 'item:id,name,sku', 'warehouse:id,name'])
            ->orderBy('chain_position', 'asc')
            ->get();

        if ($discrepancies->isEmpty()) {
            return ['status' => 'no_discrepancies'];
        }

        // Group by item+warehouse chain for analysis
        $chains = $discrepancies->groupBy(fn ($d) => $d->item_id . ':' . $d->warehouse_id);
        $allResults = [];

        foreach ($chains as $key => $chainDiscrepancies) {
            $firstDisc = $chainDiscrepancies->first();

            // Get full movement chain for context
            $movements = \App\Models\StockMovement::where('company_id', $auditRun->company_id)
                ->where('item_id', $firstDisc->item_id)
                ->where('warehouse_id', $firstDisc->warehouse_id)
                ->orderBy('movement_date', 'asc')
                ->orderBy('id', 'asc')
                ->limit(100)
                ->get(['id', 'source_type', 'quantity', 'unit_cost', 'total_cost', 'movement_date', 'balance_quantity', 'balance_value', 'notes']);

            $prompt = $this->buildPrompt(
                $firstDisc->item?->name ?? "Item #{$firstDisc->item_id}",
                $firstDisc->warehouse?->name ?? "Warehouse #{$firstDisc->warehouse_id}",
                $movements,
                $chainDiscrepancies
            );

            try {
                $response = $this->gemini->generate($prompt, [
                    'temperature' => 0.1,
                    'max_tokens' => 2048,
                    'thinking_budget' => 0,
                ]);

                $parsed = $this->parseResponse($response);

                if ($parsed) {
                    // Update discrepancies with AI analysis
                    $this->applyAnalysisToDiscrepancies($chainDiscrepancies, $parsed);
                    $allResults[$key] = $parsed;
                } else {
                    $allResults[$key] = ['error' => 'Failed to parse AI response'];
                }
            } catch (\Exception $e) {
                Log::warning('WAC AI analysis failed for chain', [
                    'audit_run_id' => $auditRun->id,
                    'item_id' => $firstDisc->item_id,
                    'error' => $e->getMessage(),
                ]);
                $allResults[$key] = ['error' => $e->getMessage()];
            }
        }

        // Save overall analysis to the audit run
        $auditRun->update(['ai_analysis' => $allResults]);

        return $allResults;
    }

    /**
     * Build the Gemini prompt for WAC chain analysis.
     */
    protected function buildPrompt(string $itemName, string $warehouseName, $movements, $discrepancies): string
    {
        $movementData = $movements->map(fn ($m) => [
            'id' => $m->id,
            'type' => $m->source_type,
            'date' => $m->movement_date->format('Y-m-d'),
            'qty' => (float) $m->quantity,
            'unit_cost' => $m->unit_cost,
            'total_cost' => $m->total_cost,
            'balance_qty' => (float) $m->balance_quantity,
            'balance_value' => $m->balance_value,
        ])->toArray();

        $discrepancyData = $discrepancies->map(fn ($d) => [
            'movement_id' => $d->movement_id,
            'position' => $d->chain_position,
            'stored_qty' => (float) $d->stored_balance_quantity,
            'expected_qty' => (float) $d->expected_balance_quantity,
            'stored_value' => $d->stored_balance_value,
            'expected_value' => $d->expected_balance_value,
            'qty_drift' => (float) $d->quantity_drift,
            'value_drift' => $d->value_drift,
            'is_root_cause' => $d->is_root_cause,
        ])->toArray();

        $movementJson = json_encode($movementData, JSON_PRETTY_PRINT);
        $discrepancyJson = json_encode($discrepancyData, JSON_PRETTY_PRINT);

        return <<<PROMPT
You are an inventory accounting auditor analyzing a WAC (Weighted Average Cost) chain for errors.

Item: "{$itemName}" in warehouse "{$warehouseName}"

WAC formula: Stock IN adds value (qty × unit_cost). Stock OUT removes value (qty × WAC where WAC = balance_value / balance_quantity).

Movement chain (chronological):
{$movementJson}

Discrepancies found (stored vs expected values):
{$discrepancyJson}

Analyze the root cause of the discrepancies. Classify the error and explain what happened.

Return ONLY valid JSON (no markdown, no explanation outside JSON):
{
  "root_cause": {
    "movement_id": <id of the movement that caused the error>,
    "category": "<one of: wrong_unit_cost, wrong_quantity, wrong_date_order, missing_movement, duplicate_movement, rounding_error>",
    "explanation": "<brief explanation of what went wrong>"
  },
  "cascade_impact": {
    "affected_movements": <number of movements affected by the error>,
    "total_value_drift_cents": <total value drift in cents>
  },
  "confidence": <0-100 integer>
}
PROMPT;
    }

    /**
     * Parse the AI response JSON.
     */
    protected function parseResponse(string $response): ?array
    {
        // Clean response: remove markdown code fences if present
        $cleaned = trim($response);
        $cleaned = preg_replace('/^```(?:json)?\s*/i', '', $cleaned);
        $cleaned = preg_replace('/\s*```$/', '', $cleaned);
        $cleaned = trim($cleaned);

        $parsed = json_decode($cleaned, true);

        if (! $parsed || ! isset($parsed['root_cause'])) {
            Log::warning('WAC AI response parse failed', [
                'response_preview' => mb_substr($response, 0, 500),
            ]);

            return null;
        }

        // Validate expected structure
        $validCategories = [
            'wrong_unit_cost',
            'wrong_quantity',
            'wrong_date_order',
            'missing_movement',
            'duplicate_movement',
            'rounding_error',
        ];

        $category = $parsed['root_cause']['category'] ?? null;
        if ($category && ! in_array($category, $validCategories)) {
            $parsed['root_cause']['category'] = 'wrong_unit_cost'; // safe default
        }

        return $parsed;
    }

    /**
     * Apply AI analysis results to discrepancy records.
     */
    protected function applyAnalysisToDiscrepancies($discrepancies, array $analysis): void
    {
        $rootCauseId = $analysis['root_cause']['movement_id'] ?? null;
        $category = $analysis['root_cause']['category'] ?? null;
        $explanation = $analysis['root_cause']['explanation'] ?? null;

        foreach ($discrepancies as $discrepancy) {
            $updates = [];

            if ($discrepancy->is_root_cause || $discrepancy->movement_id == $rootCauseId) {
                $updates['error_category'] = $category;
                $updates['ai_explanation'] = $explanation;
            } else {
                $updates['error_category'] = 'cascade';
                $updates['ai_explanation'] = 'Cascading error from root cause at movement #' . ($rootCauseId ?? 'unknown');
            }

            $discrepancy->update($updates);
        }
    }
}
