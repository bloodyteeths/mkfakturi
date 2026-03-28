<?php

namespace App\Http\Controllers\V1\Admin\Stock;

use App\Http\Controllers\Controller;
use App\Models\WacAuditRun;
use App\Models\WacCorrectionProposal;
use App\Services\WacAiAnalyzerService;
use App\Services\WacAuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WacAuditController extends Controller
{
    public function __construct(
        protected WacAuditService $auditService,
        protected WacAiAnalyzerService $aiAnalyzer
    ) {}

    /**
     * List audit runs for the company.
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = $request->header('company');
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
        $companyId = $request->header('company');
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
        $companyId = $request->header('company');

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
        $companyId = $request->header('company');

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
        $companyId = $request->header('company');

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
        $companyId = $request->header('company');

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
        $companyId = $request->header('company');

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
        $companyId = $request->header('company');

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
