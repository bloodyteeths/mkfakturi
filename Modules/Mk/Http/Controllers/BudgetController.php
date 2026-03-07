<?php

namespace Modules\Mk\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Mk\Models\Budget;
use Modules\Mk\Services\BudgetService;

class BudgetController extends Controller
{
    protected BudgetService $service;

    public function __construct(BudgetService $service)
    {
        $this->service = $service;
    }

    /**
     * List budgets for the current company.
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = (int) $request->header('company');

        if (! $companyId) {
            return response()->json(['error' => 'Company header required'], 400);
        }

        $filters = [
            'status' => $request->query('status'),
            'year' => $request->query('year'),
            'cost_center_id' => $request->query('cost_center_id'),
            'scenario' => $request->query('scenario'),
        ];

        $data = $this->service->list($companyId, array_filter($filters));

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Show a single budget with its lines.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');

        if (! $companyId) {
            return response()->json(['error' => 'Company header required'], 400);
        }

        $budget = Budget::forCompany($companyId)
            ->with(['lines', 'costCenter:id,name,code,color', 'createdBy:id,name', 'approvedBy:id,name'])
            ->find($id);

        if (! $budget) {
            return response()->json(['error' => 'Budget not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $budget->id,
                'company_id' => $budget->company_id,
                'name' => $budget->name,
                'period_type' => $budget->period_type,
                'start_date' => $budget->start_date?->toDateString(),
                'end_date' => $budget->end_date?->toDateString(),
                'status' => $budget->status,
                'cost_center_id' => $budget->cost_center_id,
                'cost_center' => $budget->costCenter ? [
                    'id' => $budget->costCenter->id,
                    'name' => $budget->costCenter->name,
                    'code' => $budget->costCenter->code,
                    'color' => $budget->costCenter->color,
                ] : null,
                'scenario' => $budget->scenario,
                'created_by' => $budget->created_by,
                'created_by_user' => $budget->createdBy ? [
                    'id' => $budget->createdBy->id,
                    'name' => $budget->createdBy->name,
                ] : null,
                'approved_by' => $budget->approved_by,
                'approved_by_user' => $budget->approvedBy ? [
                    'id' => $budget->approvedBy->id,
                    'name' => $budget->approvedBy->name,
                ] : null,
                'approved_at' => $budget->approved_at?->toIso8601String(),
                'lines' => $budget->lines->map(fn ($line) => [
                    'id' => $line->id,
                    'account_type' => $line->account_type,
                    'ifrs_account_id' => $line->ifrs_account_id,
                    'cost_center_id' => $line->cost_center_id,
                    'period_start' => $line->period_start?->toDateString(),
                    'period_end' => $line->period_end?->toDateString(),
                    'amount' => (float) $line->amount,
                    'formatted_amount' => $line->formatted_amount,
                    'notes' => $line->notes,
                ]),
                'created_at' => $budget->created_at?->toIso8601String(),
                'updated_at' => $budget->updated_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Create a new budget.
     */
    public function store(Request $request): JsonResponse
    {
        $companyId = (int) $request->header('company');

        if (! $companyId) {
            return response()->json(['error' => 'Company header required'], 400);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150',
            'period_type' => 'nullable|in:monthly,quarterly,yearly',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'scenario' => 'nullable|in:expected,optimistic,pessimistic',
            'cost_center_id' => 'nullable|integer|exists:cost_centers,id',
            'lines' => 'nullable|array',
            'lines.*.account_type' => 'required_with:lines|string|max:50',
            'lines.*.ifrs_account_id' => 'nullable|integer',
            'lines.*.cost_center_id' => 'nullable|integer',
            'lines.*.period_start' => 'required_with:lines|date',
            'lines.*.period_end' => 'required_with:lines|date',
            'lines.*.amount' => 'nullable|numeric',
            'lines.*.notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {
            $data = $validator->validated();
            $data['created_by'] = $request->user()?->id;

            $budget = $this->service->create($companyId, $data);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $budget->id,
                    'name' => $budget->name,
                    'status' => $budget->status,
                ],
                'message' => 'Budget created successfully.',
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Update a draft budget.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');

        if (! $companyId) {
            return response()->json(['error' => 'Company header required'], 400);
        }

        $budget = Budget::forCompany($companyId)->find($id);

        if (! $budget) {
            return response()->json(['error' => 'Budget not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:150',
            'period_type' => 'nullable|in:monthly,quarterly,yearly',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date',
            'scenario' => 'nullable|in:expected,optimistic,pessimistic',
            'cost_center_id' => 'nullable|integer',
            'lines' => 'nullable|array',
            'lines.*.account_type' => 'required_with:lines|string|max:50',
            'lines.*.ifrs_account_id' => 'nullable|integer',
            'lines.*.cost_center_id' => 'nullable|integer',
            'lines.*.period_start' => 'required_with:lines|date',
            'lines.*.period_end' => 'required_with:lines|date',
            'lines.*.amount' => 'nullable|numeric',
            'lines.*.notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {
            $budget = $this->service->update($budget, $validator->validated());

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $budget->id,
                    'name' => $budget->name,
                    'status' => $budget->status,
                ],
                'message' => 'Budget updated successfully.',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Approve a draft budget.
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');

        if (! $companyId) {
            return response()->json(['error' => 'Company header required'], 400);
        }

        $budget = Budget::forCompany($companyId)->find($id);

        if (! $budget) {
            return response()->json(['error' => 'Budget not found'], 404);
        }

        try {
            $budget = $this->service->approve($budget, $request->user()?->id ?? 0);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $budget->id,
                    'status' => $budget->status,
                    'approved_at' => $budget->approved_at?->toIso8601String(),
                ],
                'message' => 'Budget approved successfully.',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Lock an approved budget.
     */
    public function lock(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');

        if (! $companyId) {
            return response()->json(['error' => 'Company header required'], 400);
        }

        $budget = Budget::forCompany($companyId)->find($id);

        if (! $budget) {
            return response()->json(['error' => 'Budget not found'], 404);
        }

        try {
            $budget = $this->service->lock($budget);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $budget->id,
                    'status' => $budget->status,
                ],
                'message' => 'Budget locked successfully.',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Soft-delete a budget.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');

        if (! $companyId) {
            return response()->json(['error' => 'Company header required'], 400);
        }

        $budget = Budget::forCompany($companyId)->find($id);

        if (! $budget) {
            return response()->json(['error' => 'Budget not found'], 404);
        }

        if ($budget->status === 'locked') {
            return response()->json(['error' => 'Locked budgets cannot be deleted.'], 422);
        }

        $budget->delete();

        return response()->json([
            'success' => true,
            'message' => 'Budget deleted successfully.',
        ]);
    }

    /**
     * Pre-fill budget lines from actuals for a given year.
     */
    public function prefillFromActuals(Request $request): JsonResponse
    {
        $companyId = (int) $request->header('company');

        if (! $companyId) {
            return response()->json(['error' => 'Company header required'], 400);
        }

        $validator = Validator::make($request->all(), [
            'year' => 'required|integer|min:2020|max:2099',
            'growth_pct' => 'nullable|numeric|min:-100|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $data = $this->service->prefillFromActuals(
            $companyId,
            (string) $request->input('year'),
            (float) $request->input('growth_pct', 0)
        );

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get budget vs actual comparison.
     */
    public function budgetVsActual(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');

        if (! $companyId) {
            return response()->json(['error' => 'Company header required'], 400);
        }

        $budget = Budget::forCompany($companyId)->with('lines')->find($id);

        if (! $budget) {
            return response()->json(['error' => 'Budget not found'], 404);
        }

        $comparison = $this->service->getBudgetVsActual($budget);
        $summary = $this->service->getVarianceSummary($budget);

        return response()->json([
            'success' => true,
            'data' => [
                'budget' => [
                    'id' => $budget->id,
                    'name' => $budget->name,
                    'period_type' => $budget->period_type,
                    'start_date' => $budget->start_date?->toDateString(),
                    'end_date' => $budget->end_date?->toDateString(),
                    'scenario' => $budget->scenario,
                    'status' => $budget->status,
                ],
                'comparison' => $comparison,
                'summary' => $summary,
            ],
        ]);
    }
}

// CLAUDE-CHECKPOINT
