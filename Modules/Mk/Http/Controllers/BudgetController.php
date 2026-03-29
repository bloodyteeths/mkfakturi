<?php

namespace Modules\Mk\Http\Controllers;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Mk\Models\Budget;
use Modules\Mk\Services\AiBudgetService;
use Modules\Mk\Services\BudgetService;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
            'search' => $request->query('search'),
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
            ->with(['lines.ifrsAccount:id,code,name,type', 'costCenter:id,name,code,color', 'createdBy', 'approvedBy'])
            ->find($id);

        if (! $budget) {
            return response()->json(['error' => 'Budget not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $budget->id,
                'company_id' => $budget->company_id,
                'number' => $budget->number,
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
                    'ifrs_account' => $line->ifrsAccount ? [
                        'id' => $line->ifrsAccount->id,
                        'code' => $line->ifrsAccount->code,
                        'name' => $line->ifrsAccount->name,
                        'type' => $line->ifrsAccount->type,
                    ] : null,
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
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
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
     * Generate a smart budget from real company data (invoices, bills, expenses).
     */
    public function smartBudget(Request $request): JsonResponse
    {
        $companyId = (int) $request->header('company');

        if (! $companyId) {
            return response()->json(['error' => 'Company header required'], 400);
        }

        $validator = Validator::make($request->all(), [
            'year' => 'required|integer|min:2020|max:2099',
            'growth_pct' => 'nullable|numeric|min:-100|max:1000',
            'locale' => 'nullable|string|in:mk,en,sq,tr',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $data = $this->service->generateSmartBudget(
            $companyId,
            (string) $request->input('year'),
            (float) $request->input('growth_pct', 0),
            $request->input('locale', 'mk')
        );

        return response()->json([
            'success' => true,
            'data' => $data,
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
            'cost_center_id' => 'nullable|integer|exists:cost_centers,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $data = $this->service->prefillFromActuals(
            $companyId,
            (string) $request->input('year'),
            (float) $request->input('growth_pct', 0),
            $request->input('cost_center_id') ? (int) $request->input('cost_center_id') : null
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

        try {
            $comparison = $this->service->getBudgetVsActual($budget);
            $summary = $this->service->getVarianceSummary($budget);
        } catch (\Exception $e) {
            \Log::warning('Budget vs-actual failed', ['budget_id' => $id, 'error' => $e->getMessage()]);
            $comparison = [];
            $summary = ['total_budgeted' => 0, 'total_actual' => 0, 'total_variance' => 0, 'by_account_type' => []];
        }

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
    /**
     * Clone a budget (deep copy with lines).
     */
    public function clone(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');

        if (! $companyId) {
            return response()->json(['error' => 'Company header required'], 400);
        }

        $budget = Budget::forCompany($companyId)->with('lines')->find($id);

        if (! $budget) {
            return response()->json(['error' => 'Budget not found'], 404);
        }

        $newBudget = $this->service->clone($budget);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $newBudget->id,
                'name' => $newBudget->name,
                'status' => $newBudget->status,
            ],
            'message' => 'Budget cloned successfully.',
        ], 201);
    }

    /**
     * Archive an approved or locked budget.
     */
    public function archive(Request $request, int $id): JsonResponse
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
            $budget = $this->service->archive($budget);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $budget->id,
                    'status' => $budget->status,
                ],
                'message' => 'Budget archived successfully.',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Export budget as CSV download.
     */
    public function exportCsv(Request $request, int $id): StreamedResponse
    {
        $companyId = (int) $request->header('company');

        if (! $companyId) {
            abort(400, 'Company header required');
        }

        $budget = Budget::forCompany($companyId)->with('lines')->find($id);

        if (! $budget) {
            abort(404, 'Budget not found');
        }

        $filename = 'budget-' . $budget->id . '-' . now()->format('Y-m-d') . '.csv';

        return new StreamedResponse(function () use ($budget) {
            $handle = fopen('php://output', 'w');

            // Metadata rows
            fputcsv($handle, ['Budget Name', $budget->name]);
            fputcsv($handle, ['Period', $budget->start_date?->toDateString() . ' — ' . $budget->end_date?->toDateString()]);
            fputcsv($handle, ['Status', $budget->status]);
            fputcsv($handle, ['Scenario', $budget->scenario ?? 'expected']);
            fputcsv($handle, []); // blank row

            // Header row
            fputcsv($handle, ['Account Type', 'Period Start', 'Period End', 'Budgeted Amount', 'Notes']);

            // Data rows
            foreach ($budget->lines as $line) {
                fputcsv($handle, [
                    $line->account_type,
                    $line->period_start?->toDateString(),
                    $line->period_end?->toDateString(),
                    (float) $line->amount,
                    $line->notes ?? '',
                ]);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * AI-powered budget suggestions (deducts from AI usage quota).
     */
    public function aiSuggest(Request $request): JsonResponse
    {
        $companyId = (int) $request->header('company');

        if (! $companyId) {
            return response()->json(['error' => 'Company header required'], 400);
        }

        $validator = Validator::make($request->all(), [
            'year' => 'required|integer|min:2020|max:2099',
            'locale' => 'nullable|string|in:mk,en,sq,tr',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $company = \App\Models\Company::find($companyId);
        if (! $company) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        // Check usage limit before calling AI
        $usageService = app(\App\Services\UsageLimitService::class);
        if (! $usageService->canUse($company, 'ai_queries_per_month')) {
            $usage = $usageService->getUsage($company, 'ai_queries_per_month');

            return response()->json([
                'error' => 'AI usage limit exceeded',
                'usage' => $usage,
                'upgrade_url' => '/admin/pricing',
            ], 402);
        }

        $aiService = app(AiBudgetService::class);
        $result = $aiService->suggestBudget(
            $company,
            (string) $request->input('year'),
            $request->input('locale', 'mk')
        );

        if ($result === null) {
            return response()->json([
                'error' => 'AI analysis unavailable. Please try again later.',
            ], 503);
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Export budget as PDF.
     */
    public function exportPdf(Request $request, int $id)
    {
        $companyId = (int) $request->header('company');

        if (! $companyId) {
            return response()->json(['error' => 'Company header required'], 400);
        }

        $budget = Budget::forCompany($companyId)
            ->with(['lines', 'costCenter:id,name,code,color', 'createdBy', 'approvedBy', 'company.address'])
            ->find($id);

        if (! $budget) {
            return response()->json(['error' => 'Budget not found'], 404);
        }

        $company = $budget->company;

        // Generate sequential budget number
        $budgetNumber = str_pad($budget->id, 5, '0', STR_PAD_LEFT);
        $preparedDate = now()->format('d.m.Y');

        // Macedonian number formatter
        $formatNumber = function ($value) {
            return number_format((float) ($value ?? 0), 2, ',', '.');
        };

        // Account type labels
        $accountTypeLabels = $this->service->getAccountTypeLabels();

        // Classify revenue vs expense types
        $revenueTypes = ['OPERATING_REVENUE', 'NON_OPERATING_REVENUE'];
        $expenseTypes = ['OPERATING_EXPENSE', 'NON_OPERATING_EXPENSE', 'DIRECT_EXPENSE', 'OVERHEAD_EXPENSE'];

        $revenueLines = $budget->lines->filter(fn ($l) => in_array(strtoupper($l->account_type), $revenueTypes));
        $expenseLines = $budget->lines->filter(fn ($l) => in_array(strtoupper($l->account_type), $expenseTypes));
        $otherLines = $budget->lines->filter(fn ($l) =>
            ! in_array(strtoupper($l->account_type), array_merge($revenueTypes, $expenseTypes))
        );

        $totalRevenue = $revenueLines->sum('amount');
        $totalExpenses = $expenseLines->sum('amount');
        $projectedProfitLoss = $totalRevenue - $totalExpenses;

        $periodTypeLabels = [
            'monthly' => 'Месечно',
            'quarterly' => 'Квартално',
            'yearly' => 'Годишно',
        ];

        $scenarioLabels = [
            'expected' => 'Очекувано',
            'optimistic' => 'Оптимистично',
            'pessimistic' => 'Песимистично',
        ];

        $report_period = $budget->start_date?->format('d.m.Y') . ' - ' . $budget->end_date?->format('d.m.Y');

        // Include comparison data for approved/locked budgets
        $comparison = null;
        if (in_array($budget->status, ['approved', 'locked'])) {
            try {
                $comparison = $this->service->getBudgetVsActual($budget);
            } catch (\Exception $e) {
                $comparison = null;
            }
        }

        $pdf = Pdf::loadView('app.pdf.reports.budget', compact(
            'budget',
            'company',
            'comparison',
            'budgetNumber',
            'preparedDate',
            'formatNumber',
            'accountTypeLabels',
            'revenueLines',
            'expenseLines',
            'otherLines',
            'totalRevenue',
            'totalExpenses',
            'projectedProfitLoss',
            'periodTypeLabels',
            'scenarioLabels',
            'report_period'
        ));

        $pdf->setPaper('A4', 'portrait');
        $filename = "budzet-{$budgetNumber}.pdf";

        return $pdf->download($filename);
    }

    /**
     * Export budget vs actual comparison as PDF.
     */
    public function exportComparisonPdf(Request $request, int $id)
    {
        $companyId = (int) $request->header('company');

        if (! $companyId) {
            return response()->json(['error' => 'Company header required'], 400);
        }

        $budget = Budget::forCompany($companyId)
            ->with(['lines', 'costCenter:id,name,code,color', 'createdBy', 'approvedBy', 'company.address'])
            ->find($id);

        if (! $budget) {
            return response()->json(['error' => 'Budget not found'], 404);
        }

        $company = $budget->company;

        // Get comparison data from BudgetService
        $comparisonRows = $this->service->getBudgetVsActual($budget);
        $summary = $this->service->getVarianceSummary($budget);

        // Macedonian number formatter
        $formatNumber = function ($value) {
            return number_format((float) ($value ?? 0), 2, ',', '.');
        };

        $report_period = $budget->start_date?->format('d.m.Y') . ' - ' . $budget->end_date?->format('d.m.Y');

        $pdf = Pdf::loadView('app.pdf.reports.budget-vs-actual', compact(
            'budget',
            'company',
            'comparisonRows',
            'summary',
            'formatNumber',
            'report_period'
        ));

        $pdf->setPaper('A4', 'portrait');
        $budgetNumber = str_pad($budget->id, 5, '0', STR_PAD_LEFT);
        $filename = "budzet-vs-realizacija-{$budgetNumber}.pdf";

        return $pdf->download($filename);
    }
}

// CLAUDE-CHECKPOINT
