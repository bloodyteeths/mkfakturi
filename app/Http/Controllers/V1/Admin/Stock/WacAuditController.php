<?php

namespace App\Http\Controllers\V1\Admin\Stock;

use App\Http\Controllers\Controller;
use App\Models\StockMovement;
use App\Models\WacAuditRun;
use App\Models\WacCorrectionProposal;
use App\Services\WacAiAnalyzerService;
use App\Services\WacAuditService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WacAuditController extends Controller
{
    public function __construct(
        protected WacAuditService $auditService,
        protected WacAiAnalyzerService $aiAnalyzer
    ) {}

    /**
     * Resolve company ID from either the URL param (partner routes)
     * or the request header (user routes).
     */
    protected function resolveCompanyId(Request $request): int
    {
        return (int) ($request->route('company') ?: $request->header('company'));
    }

    /**
     * List audit runs for the company.
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = $this->resolveCompanyId($request);
        $limit = (int) $request->query('limit', 15);

        $runs = WacAuditRun::where('company_id', $companyId)
            ->with(['item:id,name,sku', 'warehouse:id,name', 'triggeredBy:id,name'])
            ->orderBy('created_at', 'desc')
            ->paginate($limit);

        return response()->json([
            'data' => $runs->map(fn ($run) => $this->formatRun($run)),
            'meta' => [
                'current_page' => $runs->currentPage(),
                'last_page' => $runs->lastPage(),
                'per_page' => $runs->perPage(),
                'total' => $runs->total(),
            ],
        ]);
    }

    /**
     * Trigger a new WAC audit run.
     */
    public function run(Request $request): JsonResponse
    {
        $companyId = $this->resolveCompanyId($request);
        $itemId = $request->input('item_id');
        $warehouseId = $request->input('warehouse_id');

        try {
            $auditRun = $this->auditService->verifyChain(
                (int) $companyId,
                $itemId ? (int) $itemId : null,
                $warehouseId ? (int) $warehouseId : null,
                auth()->id()
            );

            return response()->json([
                'success' => true,
                'message' => $auditRun->hasDiscrepancies()
                    ? "Audit complete: {$auditRun->discrepancies_found} discrepancies found."
                    : 'Audit complete: all WAC chains are consistent.',
                'data' => $this->formatRun($auditRun),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Audit failed.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get audit run detail with discrepancies.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $companyId = $this->resolveCompanyId($request);

        $auditRun = WacAuditRun::where('company_id', $companyId)
            ->with([
                'item:id,name,sku',
                'warehouse:id,name',
                'triggeredBy:id,name',
                'discrepancies' => fn ($q) => $q->with(['movement', 'item:id,name,sku', 'warehouse:id,name'])
                    ->orderBy('chain_position', 'asc'),
                'proposals',
            ])
            ->findOrFail($id);

        return response()->json([
            'data' => [
                ...$this->formatRun($auditRun),
                'discrepancies' => $auditRun->discrepancies->map(fn ($d) => [
                    'id' => $d->id,
                    'movement_id' => $d->movement_id,
                    'item_id' => $d->item_id,
                    'item_name' => $d->item?->name,
                    'warehouse_id' => $d->warehouse_id,
                    'warehouse_name' => $d->warehouse?->name,
                    'chain_position' => $d->chain_position,
                    'stored_balance_quantity' => (float) $d->stored_balance_quantity,
                    'stored_balance_value' => $d->stored_balance_value,
                    'expected_balance_quantity' => (float) $d->expected_balance_quantity,
                    'expected_balance_value' => $d->expected_balance_value,
                    'quantity_drift' => (float) $d->quantity_drift,
                    'value_drift' => $d->value_drift,
                    'error_category' => $d->error_category,
                    'ai_explanation' => $d->ai_explanation,
                    'is_root_cause' => $d->is_root_cause,
                    'movement' => $d->movement ? [
                        'id' => $d->movement->id,
                        'source_type' => $d->movement->source_type,
                        'source_type_label' => $d->movement->source_type_label,
                        'quantity' => $d->movement->quantity,
                        'unit_cost' => $d->movement->unit_cost,
                        'movement_date' => $d->movement->movement_date?->format('Y-m-d'),
                        'notes' => $d->movement->notes,
                    ] : null,
                ]),
                'proposals' => $auditRun->proposals->map(fn ($p) => $this->formatProposal($p)),
            ],
        ]);
    }

    /**
     * Trigger AI analysis of discrepancies.
     */
    public function analyze(Request $request, int $id): JsonResponse
    {
        $companyId = $this->resolveCompanyId($request);

        $auditRun = WacAuditRun::where('company_id', $companyId)->findOrFail($id);

        if (! $auditRun->hasDiscrepancies()) {
            return response()->json([
                'message' => 'No discrepancies to analyze.',
            ], 422);
        }

        try {
            $results = $this->aiAnalyzer->analyzeDiscrepancies($auditRun);

            return response()->json([
                'success' => true,
                'message' => 'AI analysis complete.',
                'data' => $results,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'AI analysis failed.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * View correction proposal for an audit run.
     */
    public function proposal(Request $request, int $id): JsonResponse
    {
        $companyId = $this->resolveCompanyId($request);

        $auditRun = WacAuditRun::where('company_id', $companyId)->findOrFail($id);

        $proposal = WacCorrectionProposal::where('audit_run_id', $auditRun->id)
            ->orderBy('created_at', 'desc')
            ->first();

        if (! $proposal) {
            return response()->json(['data' => null]);
        }

        return response()->json([
            'data' => $this->formatProposal($proposal),
        ]);
    }

    /**
     * Generate a correction proposal for an audit run.
     */
    public function generateProposal(Request $request, int $id): JsonResponse
    {
        $companyId = $this->resolveCompanyId($request);

        $auditRun = WacAuditRun::where('company_id', $companyId)->findOrFail($id);

        if (! $auditRun->hasDiscrepancies()) {
            return response()->json([
                'message' => 'No discrepancies to correct.',
            ], 422);
        }

        $proposal = $this->auditService->generateCorrectionProposal($auditRun, auth()->id());

        if (! $proposal) {
            return response()->json([
                'message' => 'All drifts are negligible. No correction needed.',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Correction proposal generated.',
            'data' => $this->formatProposal($proposal),
        ], 201);
    }

    /**
     * Approve and apply a correction proposal.
     */
    public function approveProposal(Request $request, int $proposalId): JsonResponse
    {
        $companyId = $this->resolveCompanyId($request);

        $proposal = WacCorrectionProposal::where('company_id', $companyId)->findOrFail($proposalId);

        if (! $proposal->isUsable()) {
            return response()->json([
                'error' => 'Proposal is no longer usable.',
                'status' => $proposal->status,
            ], 422);
        }

        try {
            $movements = $this->auditService->applyCorrectionProposal($proposal, auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Correction applied. ' . count($movements) . ' movement(s) created.',
                'data' => [
                    'proposal' => $this->formatProposal($proposal->fresh()),
                    'created_movements' => count($movements),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to apply correction.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reject a correction proposal.
     */
    public function rejectProposal(Request $request, int $proposalId): JsonResponse
    {
        $companyId = $this->resolveCompanyId($request);

        $proposal = WacCorrectionProposal::where('company_id', $companyId)->findOrFail($proposalId);

        if ($proposal->status !== WacCorrectionProposal::STATUS_PENDING) {
            return response()->json([
                'error' => 'Only pending proposals can be rejected.',
                'status' => $proposal->status,
            ], 422);
        }

        $proposal->update([
            'status' => WacCorrectionProposal::STATUS_REJECTED,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_notes' => $request->input('notes'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Correction proposal rejected.',
            'data' => $this->formatProposal($proposal->fresh()),
        ]);
    }

    /**
     * Seed test discrepancy data for E2E testing.
     * Creates stock movements with a deliberate WAC chain error.
     * Super-admin only.
     */
    public function seedTestDiscrepancy(Request $request): JsonResponse
    {
        $companyId = (int) $this->resolveCompanyId($request);
        $userId = auth()->id();

        // Get the first trackable item + warehouse for this company
        $existing = StockMovement::where('company_id', $companyId)
            ->select('item_id', 'warehouse_id')
            ->groupBy('item_id', 'warehouse_id')
            ->first();

        if (! $existing) {
            return response()->json(['error' => 'No stock movements exist to seed from.'], 422);
        }

        $itemId = $existing->item_id;
        $warehouseId = $existing->warehouse_id;

        // Create 3 new test movements with correct chain, then corrupt #2
        $now = Carbon::now();
        $baseDate = $now->copy()->subDays(3);

        // Movement 1: Stock IN 10 @ 100 MKD (10000 cents) → balance: 10 qty, 1000000 value
        $m1 = StockMovement::create([
            'company_id' => $companyId,
            'warehouse_id' => $warehouseId,
            'item_id' => $itemId,
            'source_type' => StockMovement::SOURCE_ADJUSTMENT,
            'quantity' => 10,
            'unit_cost' => 10000, // 100 MKD in cents
            'total_cost' => 100000,
            'movement_date' => $baseDate,
            'notes' => 'WAC test seed: initial stock IN',
            'balance_quantity' => 10,
            'balance_value' => 100000, // Correct: 10 × 10000
            'created_by' => $userId,
        ]);

        // Movement 2: Stock IN 5 @ 120 MKD (12000 cents) → balance: 15 qty, 160000 value
        // WAC = 160000/15 = 10667
        $m2 = StockMovement::create([
            'company_id' => $companyId,
            'warehouse_id' => $warehouseId,
            'item_id' => $itemId,
            'source_type' => StockMovement::SOURCE_ADJUSTMENT,
            'quantity' => 5,
            'unit_cost' => 12000, // 120 MKD in cents
            'total_cost' => 60000,
            'movement_date' => $baseDate->copy()->addDay(),
            'notes' => 'WAC test seed: second stock IN (WILL BE CORRUPTED)',
            'balance_quantity' => 15,
            'balance_value' => 160000, // Correct: 100000 + 60000
            'created_by' => $userId,
        ]);

        // Movement 3: Stock OUT -3 @ WAC → balance: 12 qty, expected ~128000 value
        // WAC at this point = 160000/15 = 10667, OUT cost = 3 × 10667 = 32001
        $wac = (int) round(160000 / 15);
        $outCost = 3 * $wac;
        $m3 = StockMovement::create([
            'company_id' => $companyId,
            'warehouse_id' => $warehouseId,
            'item_id' => $itemId,
            'source_type' => StockMovement::SOURCE_ADJUSTMENT,
            'quantity' => -3,
            'unit_cost' => $wac,
            'total_cost' => $outCost,
            'movement_date' => $baseDate->copy()->addDays(2),
            'notes' => 'WAC test seed: stock OUT (cascade from corruption)',
            'balance_quantity' => 12,
            'balance_value' => 160000 - $outCost, // Correct based on correct chain
            'created_by' => $userId,
        ]);

        // Now CORRUPT movement #2's balance_value via raw DB (bypass boot guard)
        // Change 160000 → 200000 (wrong by 40000 cents = 400 MKD)
        DB::table('stock_movements')
            ->where('id', $m2->id)
            ->update(['balance_value' => 200000, 'frozen_at' => null]);

        // Also corrupt movement #3's balance to cascade from the wrong #2
        // With corrupted #2: WAC = 200000/15 = 13333, OUT = 3 × 13333 = 39999
        $corruptedWac = (int) round(200000 / 15);
        $corruptedOutCost = 3 * $corruptedWac;
        DB::table('stock_movements')
            ->where('id', $m3->id)
            ->update(['balance_value' => 200000 - $corruptedOutCost, 'frozen_at' => null]);

        return response()->json([
            'success' => true,
            'message' => 'Seeded 3 test movements with WAC chain error on movement #2.',
            'data' => [
                'item_id' => $itemId,
                'warehouse_id' => $warehouseId,
                'movements' => [$m1->id, $m2->id, $m3->id],
                'corrupted_movement' => $m2->id,
                'correct_balance_value' => 160000,
                'corrupted_balance_value' => 200000,
                'drift' => 40000,
            ],
        ], 201);
    }

    protected function formatRun(WacAuditRun $run): array
    {
        return [
            'id' => $run->id,
            'company_id' => $run->company_id,
            'item_id' => $run->item_id,
            'item_name' => $run->item?->name,
            'warehouse_id' => $run->warehouse_id,
            'warehouse_name' => $run->warehouse?->name,
            'status' => $run->status,
            'total_movements_checked' => $run->total_movements_checked,
            'discrepancies_found' => $run->discrepancies_found,
            'has_discrepancies' => $run->hasDiscrepancies(),
            'summary' => $run->summary,
            'ai_analysis' => $run->ai_analysis,
            'triggered_by' => $run->triggeredBy?->name,
            'started_at' => $run->started_at?->format('Y-m-d H:i:s'),
            'completed_at' => $run->completed_at?->format('Y-m-d H:i:s'),
            'created_at' => $run->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    protected function formatProposal(WacCorrectionProposal $proposal): array
    {
        return [
            'id' => $proposal->id,
            'audit_run_id' => $proposal->audit_run_id,
            'status' => $proposal->status,
            'description' => $proposal->description,
            'correction_entries' => $proposal->correction_entries,
            'ai_reasoning' => $proposal->ai_reasoning,
            'net_quantity_adjustment' => (float) $proposal->net_quantity_adjustment,
            'net_value_adjustment' => $proposal->net_value_adjustment,
            'proposed_by' => $proposal->proposedBy?->name,
            'reviewed_by' => $proposal->reviewedBy?->name,
            'reviewed_at' => $proposal->reviewed_at?->format('Y-m-d H:i:s'),
            'review_notes' => $proposal->review_notes,
            'applied_at' => $proposal->applied_at?->format('Y-m-d H:i:s'),
            'expires_at' => $proposal->expires_at?->format('Y-m-d H:i:s'),
            'is_usable' => $proposal->isUsable(),
            'created_at' => $proposal->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
