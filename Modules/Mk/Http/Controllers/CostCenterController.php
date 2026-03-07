<?php

namespace Modules\Mk\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Mk\Models\CostCenter;
use Modules\Mk\Models\CostCenterRule;
use Modules\Mk\Services\CostCenterLedgerService;
use Modules\Mk\Services\CostCenterService;

class CostCenterController extends Controller
{
    protected CostCenterService $service;

    protected CostCenterLedgerService $ledgerService;

    public function __construct(CostCenterService $service, CostCenterLedgerService $ledgerService)
    {
        $this->service = $service;
        $this->ledgerService = $ledgerService;
    }

    /**
     * List cost centers for the current company.
     * Query param: ?tree=1 for tree structure.
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = (int) $request->header('company');

        if (! $companyId) {
            return response()->json(['error' => 'Company header required'], 400);
        }

        $tree = (bool) $request->query('tree', false);
        $data = $this->service->list($companyId, $tree);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Show a single cost center with children.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');

        if (! $companyId) {
            return response()->json(['error' => 'Company header required'], 400);
        }

        $cc = CostCenter::forCompany($companyId)->with('children')->find($id);

        if (! $cc) {
            return response()->json(['error' => 'Cost center not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $cc->id,
                'company_id' => $cc->company_id,
                'parent_id' => $cc->parent_id,
                'name' => $cc->name,
                'code' => $cc->code,
                'color' => $cc->color,
                'description' => $cc->description,
                'is_active' => $cc->is_active,
                'sort_order' => $cc->sort_order,
                'full_path' => $cc->fullPath(),
                'children' => $cc->children->map(fn ($child) => [
                    'id' => $child->id,
                    'name' => $child->name,
                    'code' => $child->code,
                    'color' => $child->color,
                    'is_active' => $child->is_active,
                ]),
                'created_at' => $cc->created_at?->toIso8601String(),
                'updated_at' => $cc->updated_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Create a new cost center.
     */
    public function store(Request $request): JsonResponse
    {
        $companyId = (int) $request->header('company');

        if (! $companyId) {
            return response()->json(['error' => 'Company header required'], 400);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150',
            'code' => 'nullable|string|max:20',
            'color' => 'nullable|string|max:7',
            'parent_id' => 'nullable|integer|exists:cost_centers,id',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        // Check unique code within company
        $code = $request->input('code');
        if ($code) {
            $existing = CostCenter::forCompany($companyId)
                ->where('code', $code)
                ->exists();
            if ($existing) {
                return response()->json(['error' => 'A cost center with this code already exists.'], 422);
            }
        }

        try {
            $cc = $this->service->create($companyId, $validator->validated());

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $cc->id,
                    'name' => $cc->name,
                    'code' => $cc->code,
                    'color' => $cc->color,
                    'parent_id' => $cc->parent_id,
                    'description' => $cc->description,
                    'is_active' => $cc->is_active,
                    'sort_order' => $cc->sort_order,
                    'full_path' => $cc->fullPath(),
                ],
                'message' => 'Cost center created successfully.',
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Update an existing cost center.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');

        if (! $companyId) {
            return response()->json(['error' => 'Company header required'], 400);
        }

        $cc = CostCenter::forCompany($companyId)->find($id);

        if (! $cc) {
            return response()->json(['error' => 'Cost center not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:150',
            'code' => 'nullable|string|max:20',
            'color' => 'nullable|string|max:7',
            'parent_id' => 'nullable|integer',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        // Check unique code within company (excluding self)
        $code = $request->input('code');
        if ($code) {
            $existing = CostCenter::forCompany($companyId)
                ->where('code', $code)
                ->where('id', '<>', $id)
                ->exists();
            if ($existing) {
                return response()->json(['error' => 'A cost center with this code already exists.'], 422);
            }
        }

        try {
            $cc = $this->service->update($cc, $validator->validated());

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $cc->id,
                    'name' => $cc->name,
                    'code' => $cc->code,
                    'color' => $cc->color,
                    'parent_id' => $cc->parent_id,
                    'description' => $cc->description,
                    'is_active' => $cc->is_active,
                    'sort_order' => $cc->sort_order,
                    'full_path' => $cc->fullPath(),
                ],
                'message' => 'Cost center updated successfully.',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Soft-delete a cost center.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');

        if (! $companyId) {
            return response()->json(['error' => 'Company header required'], 400);
        }

        $cc = CostCenter::forCompany($companyId)->find($id);

        if (! $cc) {
            return response()->json(['error' => 'Cost center not found'], 404);
        }

        try {
            $this->service->delete($cc);

            return response()->json([
                'success' => true,
                'message' => 'Cost center deleted successfully.',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Reorder cost centers (update sort_order and parent_id in bulk).
     */
    public function reorder(Request $request): JsonResponse
    {
        $companyId = (int) $request->header('company');

        if (! $companyId) {
            return response()->json(['error' => 'Company header required'], 400);
        }

        $validator = Validator::make($request->all(), [
            'orders' => 'required|array',
            'orders.*.id' => 'required|integer|exists:cost_centers,id',
            'orders.*.sort_order' => 'required|integer|min:0',
            'orders.*.parent_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $this->service->reorder($companyId, $request->input('orders'));

        return response()->json([
            'success' => true,
            'message' => 'Cost centers reordered successfully.',
        ]);
    }

    // ---- Rules ----

    /**
     * List assignment rules for the current company.
     */
    public function rules(Request $request): JsonResponse
    {
        $companyId = (int) $request->header('company');

        if (! $companyId) {
            return response()->json(['error' => 'Company header required'], 400);
        }

        $rules = CostCenterRule::where('company_id', $companyId)
            ->with('costCenter:id,name,code,color')
            ->orderBy('priority', 'desc')
            ->orderBy('id')
            ->get()
            ->map(fn (CostCenterRule $rule) => [
                'id' => $rule->id,
                'cost_center_id' => $rule->cost_center_id,
                'cost_center' => $rule->costCenter ? [
                    'id' => $rule->costCenter->id,
                    'name' => $rule->costCenter->name,
                    'code' => $rule->costCenter->code,
                    'color' => $rule->costCenter->color,
                ] : null,
                'match_type' => $rule->match_type,
                'match_value' => $rule->match_value,
                'priority' => $rule->priority,
                'is_active' => $rule->is_active,
            ]);

        return response()->json([
            'success' => true,
            'data' => $rules,
        ]);
    }

    /**
     * Create a new assignment rule.
     */
    public function storeRule(Request $request): JsonResponse
    {
        $companyId = (int) $request->header('company');

        if (! $companyId) {
            return response()->json(['error' => 'Company header required'], 400);
        }

        $validator = Validator::make($request->all(), [
            'cost_center_id' => 'required|integer|exists:cost_centers,id',
            'match_type' => 'required|in:vendor,account,description,item',
            'match_value' => 'required|string|max:255',
            'priority' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        // Verify cost center belongs to same company
        $cc = CostCenter::forCompany($companyId)->find($request->input('cost_center_id'));
        if (! $cc) {
            return response()->json(['error' => 'Cost center not found for this company.'], 422);
        }

        $rule = CostCenterRule::create(array_merge($validator->validated(), [
            'company_id' => $companyId,
        ]));

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $rule->id,
                'cost_center_id' => $rule->cost_center_id,
                'match_type' => $rule->match_type,
                'match_value' => $rule->match_value,
                'priority' => $rule->priority,
                'is_active' => $rule->is_active,
            ],
            'message' => 'Assignment rule created successfully.',
        ], 201);
    }

    /**
     * Update an existing assignment rule.
     */
    public function updateRule(Request $request, int $ruleId): JsonResponse
    {
        $companyId = (int) $request->header('company');

        if (! $companyId) {
            return response()->json(['error' => 'Company header required'], 400);
        }

        $rule = CostCenterRule::where('company_id', $companyId)->find($ruleId);

        if (! $rule) {
            return response()->json(['error' => 'Rule not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'cost_center_id' => 'sometimes|required|integer|exists:cost_centers,id',
            'match_type' => 'sometimes|required|in:vendor,account,description,item',
            'match_value' => 'sometimes|required|string|max:255',
            'priority' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        // Verify cost center belongs to same company if changing
        if ($request->has('cost_center_id')) {
            $cc = CostCenter::forCompany($companyId)->find($request->input('cost_center_id'));
            if (! $cc) {
                return response()->json(['error' => 'Cost center not found for this company.'], 422);
            }
        }

        $rule->update($validator->validated());

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $rule->id,
                'cost_center_id' => $rule->cost_center_id,
                'match_type' => $rule->match_type,
                'match_value' => $rule->match_value,
                'priority' => $rule->priority,
                'is_active' => $rule->is_active,
            ],
            'message' => 'Assignment rule updated successfully.',
        ]);
    }

    /**
     * Delete an assignment rule.
     */
    public function deleteRule(Request $request, int $ruleId): JsonResponse
    {
        $companyId = (int) $request->header('company');

        if (! $companyId) {
            return response()->json(['error' => 'Company header required'], 400);
        }

        $rule = CostCenterRule::where('company_id', $companyId)->find($ruleId);

        if (! $rule) {
            return response()->json(['error' => 'Rule not found'], 404);
        }

        $rule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Assignment rule deleted successfully.',
        ]);
    }

    /**
     * Suggest a cost center for a given document context.
     */
    public function suggest(Request $request): JsonResponse
    {
        $companyId = (int) $request->header('company');

        if (! $companyId) {
            return response()->json(['error' => 'Company header required'], 400);
        }

        $context = [
            'vendor_id' => $request->input('vendor_id'),
            'account_code' => $request->input('account_code'),
            'description' => $request->input('description'),
            'item_ids' => $request->input('item_ids', []),
        ];

        $suggestion = $this->service->suggestForDocument($companyId, $context);

        return response()->json([
            'success' => true,
            'data' => $suggestion,
        ]);
    }

    /**
     * Get cost center financial summary for a date range.
     */
    public function summary(Request $request): JsonResponse
    {
        $companyId = (int) $request->header('company');

        if (! $companyId) {
            return response()->json(['error' => 'Company header required'], 400);
        }

        $fromDate = $request->query('from_date', now()->startOfYear()->toDateString());
        $toDate = $request->query('to_date', now()->toDateString());

        $summary = $this->ledgerService->getCostCenterSummary($companyId, $fromDate, $toDate);

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * Get filtered trial balance for a specific cost center.
     */
    public function trialBalance(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');

        if (! $companyId) {
            return response()->json(['error' => 'Company header required'], 400);
        }

        // Verify cost center belongs to this company
        $cc = CostCenter::forCompany($companyId)->find($id);
        if (! $cc) {
            return response()->json(['error' => 'Cost center not found'], 404);
        }

        $fromDate = $request->query('from_date', now()->startOfYear()->toDateString());
        $toDate = $request->query('to_date', now()->toDateString());

        $trialBalance = $this->ledgerService->getFilteredTrialBalance($companyId, $fromDate, $toDate, $id);

        return response()->json([
            'success' => true,
            'cost_center' => [
                'id' => $cc->id,
                'name' => $cc->name,
                'code' => $cc->code,
                'color' => $cc->color,
            ],
            'trial_balance' => $trialBalance,
        ]);
    }

    /**
     * Get P&L for a specific cost center.
     */
    public function profitLoss(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');

        if (! $companyId) {
            return response()->json(['error' => 'Company header required'], 400);
        }

        $cc = CostCenter::forCompany($companyId)->find($id);
        if (! $cc) {
            return response()->json(['error' => 'Cost center not found'], 404);
        }

        $fromDate = $request->query('from_date', now()->startOfYear()->toDateString());
        $toDate = $request->query('to_date', now()->toDateString());

        $pl = $this->ledgerService->getCostCenterProfitLoss($companyId, $fromDate, $toDate, $id);

        return response()->json([
            'success' => true,
            'cost_center' => [
                'id' => $cc->id,
                'name' => $cc->name,
                'code' => $cc->code,
                'color' => $cc->color,
            ],
            'profit_loss' => $pl,
        ]);
    }
}

// CLAUDE-CHECKPOINT
