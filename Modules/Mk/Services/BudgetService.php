<?php

namespace Modules\Mk\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Mk\Models\Budget;
use Modules\Mk\Models\BudgetLine;

class BudgetService
{
    /**
     * IFRS account type labels for Macedonian locale.
     */
    protected array $accountTypeLabels = [
        'OPERATING_REVENUE' => 'Оперативни приходи',
        'OPERATING_EXPENSE' => 'Оперативни расходи',
        'NON_OPERATING_REVENUE' => 'Неоперативни приходи',
        'NON_OPERATING_EXPENSE' => 'Неоперативни расходи',
        'DIRECT_EXPENSE' => 'Директни трошоци',
        'OVERHEAD_EXPENSE' => 'Општи трошоци',
        'CURRENT_ASSET' => 'Тековни средства',
        'NON_CURRENT_ASSET' => 'Нетековни средства',
        'CURRENT_LIABILITY' => 'Тековни обврски',
        'NON_CURRENT_LIABILITY' => 'Нетековни обврски',
        'EQUITY' => 'Капитал',
        'CONTRA_ASSET' => 'Контра средства',
        'CONTRA_LIABILITY' => 'Контра обврски',
        'CONTRA_EQUITY' => 'Контра капитал',
        'RECEIVABLE' => 'Побарувања',
        'PAYABLE' => 'Обврски',
        'BANK' => 'Банка',
        'INVENTORY' => 'Залихи',
        'RECONCILIATION' => 'Усогласување',
    ];

    /**
     * List budgets for a company with optional filters.
     *
     * @return array
     */
    public function list(int $companyId, array $filters = []): array
    {
        $query = Budget::forCompany($companyId)
            ->with(['costCenter:id,name,code,color', 'createdBy:id,name', 'approvedBy:id,name'])
            ->withCount('lines');

        if (! empty($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        if (! empty($filters['year'])) {
            $year = (int) $filters['year'];
            $query->whereYear('start_date', '<=', $year)
                ->whereYear('end_date', '>=', $year);
        }

        if (! empty($filters['cost_center_id'])) {
            $query->byCostCenter((int) $filters['cost_center_id']);
        }

        if (! empty($filters['scenario'])) {
            $query->where('scenario', $filters['scenario']);
        }

        $budgets = $query->orderBy('created_at', 'desc')->get();

        return $budgets->map(fn (Budget $b) => $this->formatBudget($b))->toArray();
    }

    /**
     * Create a new budget with lines.
     */
    public function create(int $companyId, array $data): Budget
    {
        return DB::transaction(function () use ($companyId, $data) {
            $budget = Budget::create([
                'company_id' => $companyId,
                'name' => $data['name'],
                'period_type' => $data['period_type'] ?? 'monthly',
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'status' => 'draft',
                'cost_center_id' => $data['cost_center_id'] ?? null,
                'scenario' => $data['scenario'] ?? 'expected',
                'created_by' => $data['created_by'] ?? null,
            ]);

            if (! empty($data['lines']) && is_array($data['lines'])) {
                foreach ($data['lines'] as $line) {
                    BudgetLine::create([
                        'budget_id' => $budget->id,
                        'account_type' => $line['account_type'],
                        'ifrs_account_id' => $line['ifrs_account_id'] ?? null,
                        'cost_center_id' => $line['cost_center_id'] ?? null,
                        'period_start' => $line['period_start'],
                        'period_end' => $line['period_end'],
                        'amount' => $line['amount'] ?? 0,
                        'notes' => $line['notes'] ?? null,
                    ]);
                }
            }

            Log::info('Budget created', [
                'budget_id' => $budget->id,
                'company_id' => $companyId,
                'name' => $budget->name,
                'lines_count' => count($data['lines'] ?? []),
            ]);

            return $budget->load('lines');
        });
    }

    /**
     * Update a draft budget and its lines.
     */
    public function update(Budget $budget, array $data): Budget
    {
        if ($budget->status !== 'draft') {
            throw new \InvalidArgumentException('Only draft budgets can be edited.');
        }

        return DB::transaction(function () use ($budget, $data) {
            $budget->update(array_filter([
                'name' => $data['name'] ?? null,
                'period_type' => $data['period_type'] ?? null,
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'cost_center_id' => array_key_exists('cost_center_id', $data) ? $data['cost_center_id'] : null,
                'scenario' => $data['scenario'] ?? null,
            ], fn ($v) => $v !== null));

            if (isset($data['lines']) && is_array($data['lines'])) {
                // Replace all lines
                $budget->lines()->delete();

                foreach ($data['lines'] as $line) {
                    BudgetLine::create([
                        'budget_id' => $budget->id,
                        'account_type' => $line['account_type'],
                        'ifrs_account_id' => $line['ifrs_account_id'] ?? null,
                        'cost_center_id' => $line['cost_center_id'] ?? null,
                        'period_start' => $line['period_start'],
                        'period_end' => $line['period_end'],
                        'amount' => $line['amount'] ?? 0,
                        'notes' => $line['notes'] ?? null,
                    ]);
                }
            }

            Log::info('Budget updated', [
                'budget_id' => $budget->id,
                'company_id' => $budget->company_id,
            ]);

            return $budget->fresh()->load('lines');
        });
    }

    /**
     * Approve a draft budget.
     */
    public function approve(Budget $budget, int $userId): Budget
    {
        if ($budget->status !== 'draft') {
            throw new \InvalidArgumentException('Only draft budgets can be approved.');
        }

        $budget->update([
            'status' => 'approved',
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);

        Log::info('Budget approved', [
            'budget_id' => $budget->id,
            'approved_by' => $userId,
        ]);

        return $budget->fresh();
    }

    /**
     * Lock an approved budget (prevent further changes).
     */
    public function lock(Budget $budget): Budget
    {
        if ($budget->status !== 'approved') {
            throw new \InvalidArgumentException('Only approved budgets can be locked.');
        }

        $budget->update(['status' => 'locked']);

        Log::info('Budget locked', [
            'budget_id' => $budget->id,
        ]);

        return $budget->fresh();
    }

    /**
     * Pre-fill budget lines from actual IFRS ledger data for a given year.
     *
     * Queries ifrs_ledgers joined with ifrs_accounts to get actual amounts
     * per account_type per month, with optional growth percentage applied.
     *
     * @return array Array of budget line structures
     */
    public function prefillFromActuals(int $companyId, string $year, ?float $growthPct = 0): array
    {
        $entityId = $companyId; // entity_id = company_id in ifrs_ledgers

        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            $monthExpr = "CAST(strftime('%m', l.posting_date) AS INTEGER)";
            $yearFilter = "strftime('%Y', l.posting_date) = ?";
        } else {
            $monthExpr = "MONTH(l.posting_date)";
            $yearFilter = "YEAR(l.posting_date) = ?";
        }

        $rows = DB::select("
            SELECT
                a.account_type,
                {$monthExpr} as month,
                SUM(
                    CASE WHEN l.entry_type = 'D' THEN l.amount / l.rate ELSE 0 END
                ) as total_debit,
                SUM(
                    CASE WHEN l.entry_type = 'C' THEN l.amount / l.rate ELSE 0 END
                ) as total_credit
            FROM ifrs_ledgers l
            JOIN ifrs_accounts a ON a.id = l.post_account AND a.entity_id = l.entity_id
            WHERE l.entity_id = ?
              AND {$yearFilter}
              AND l.deleted_at IS NULL
              AND a.deleted_at IS NULL
            GROUP BY a.account_type, {$monthExpr}
            ORDER BY a.account_type, {$monthExpr}
        ", [$entityId, (string) $year]);

        $multiplier = 1 + (($growthPct ?? 0) / 100);
        $lines = [];
        $nextYear = (int) $year + 1;

        foreach ($rows as $row) {
            // For revenue accounts, use credit - debit (net credit = revenue)
            // For expense/asset accounts, use debit - credit (net debit = expense)
            $isRevenue = str_contains($row->account_type, 'REVENUE');
            $netAmount = $isRevenue
                ? round($row->total_credit - $row->total_debit, 2)
                : round($row->total_debit - $row->total_credit, 2);

            $adjustedAmount = round($netAmount * $multiplier, 2);

            $month = (int) $row->month;
            $periodStart = Carbon::create($nextYear, $month, 1)->toDateString();
            $periodEnd = Carbon::create($nextYear, $month, 1)->endOfMonth()->toDateString();

            $lines[] = [
                'account_type' => $row->account_type,
                'account_type_label' => $this->accountTypeLabels[$row->account_type] ?? $row->account_type,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'month' => $month,
                'actual_amount' => $netAmount,
                'amount' => $adjustedAmount,
            ];
        }

        return [
            'source_year' => $year,
            'target_year' => (string) $nextYear,
            'growth_pct' => $growthPct ?? 0,
            'lines' => $lines,
        ];
    }

    /**
     * Get budget vs actual comparison for a budget.
     *
     * For each budget line, queries actual amounts from ifrs_ledgers
     * for the same period and account_type.
     *
     * @return array Array of comparison rows
     */
    public function getBudgetVsActual(Budget $budget): array
    {
        $budget->loadMissing('lines');
        $entityId = $budget->company_id;

        $result = [];

        // Group budget lines by account_type + period for efficient querying
        $grouped = $budget->lines->groupBy(function (BudgetLine $line) {
            return $line->account_type . '|' . $line->period_start->toDateString() . '|' . $line->period_end->toDateString();
        });

        foreach ($grouped as $key => $lines) {
            [$accountType, $periodStart, $periodEnd] = explode('|', $key);

            $budgeted = $lines->sum('amount');

            // Query actual from ledger
            $actuals = DB::select("
                SELECT
                    SUM(
                        CASE WHEN l.entry_type = 'D' THEN l.amount / l.rate ELSE 0 END
                    ) as total_debit,
                    SUM(
                        CASE WHEN l.entry_type = 'C' THEN l.amount / l.rate ELSE 0 END
                    ) as total_credit
                FROM ifrs_ledgers l
                JOIN ifrs_accounts a ON a.id = l.post_account AND a.entity_id = l.entity_id
                WHERE l.entity_id = ?
                  AND a.account_type = ?
                  AND l.posting_date >= ?
                  AND l.posting_date <= ?
                  AND l.deleted_at IS NULL
                  AND a.deleted_at IS NULL
            ", [$entityId, $accountType, $periodStart, $periodEnd]);

            $row = $actuals[0] ?? null;
            $isRevenue = str_contains($accountType, 'REVENUE');
            $actual = 0;

            if ($row) {
                $actual = $isRevenue
                    ? round(($row->total_credit ?? 0) - ($row->total_debit ?? 0), 2)
                    : round(($row->total_debit ?? 0) - ($row->total_credit ?? 0), 2);
            }

            $variance = round($actual - $budgeted, 2);
            $variancePct = $budgeted != 0
                ? round(($variance / abs($budgeted)) * 100, 2)
                : ($actual != 0 ? 100 : 0);

            $result[] = [
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'account_type' => $accountType,
                'account_type_label' => $this->accountTypeLabels[$accountType] ?? $accountType,
                'budgeted' => round($budgeted, 2),
                'actual' => $actual,
                'variance' => $variance,
                'variance_pct' => $variancePct,
            ];
        }

        // Sort by period then account_type
        usort($result, function ($a, $b) {
            $periodCmp = strcmp($a['period_start'], $b['period_start']);
            if ($periodCmp !== 0) {
                return $periodCmp;
            }

            return strcmp($a['account_type'], $b['account_type']);
        });

        return $result;
    }

    /**
     * Get aggregated variance summary for a budget.
     *
     * @return array Summary with totals and top over/under budget categories
     */
    public function getVarianceSummary(Budget $budget): array
    {
        $comparison = $this->getBudgetVsActual($budget);

        $totalBudgeted = 0;
        $totalActual = 0;
        $byAccountType = [];

        foreach ($comparison as $row) {
            $totalBudgeted += $row['budgeted'];
            $totalActual += $row['actual'];

            $type = $row['account_type'];
            if (! isset($byAccountType[$type])) {
                $byAccountType[$type] = [
                    'account_type' => $type,
                    'account_type_label' => $row['account_type_label'],
                    'budgeted' => 0,
                    'actual' => 0,
                    'variance' => 0,
                ];
            }
            $byAccountType[$type]['budgeted'] += $row['budgeted'];
            $byAccountType[$type]['actual'] += $row['actual'];
            $byAccountType[$type]['variance'] += $row['variance'];
        }

        // Calculate variance_pct for each account type
        foreach ($byAccountType as &$item) {
            $item['budgeted'] = round($item['budgeted'], 2);
            $item['actual'] = round($item['actual'], 2);
            $item['variance'] = round($item['variance'], 2);
            $item['variance_pct'] = $item['budgeted'] != 0
                ? round(($item['variance'] / abs($item['budgeted'])) * 100, 2)
                : ($item['actual'] != 0 ? 100 : 0);
        }
        unset($item);

        $byAccountType = array_values($byAccountType);

        // Sort to find top over-budget (positive variance for expenses, negative for revenue)
        $overBudget = collect($byAccountType)
            ->filter(fn ($item) => $item['variance'] > 0)
            ->sortByDesc('variance')
            ->take(5)
            ->values()
            ->toArray();

        $underBudget = collect($byAccountType)
            ->filter(fn ($item) => $item['variance'] < 0)
            ->sortBy('variance')
            ->take(5)
            ->values()
            ->toArray();

        $totalVariance = round($totalActual - $totalBudgeted, 2);

        return [
            'total_budgeted' => round($totalBudgeted, 2),
            'total_actual' => round($totalActual, 2),
            'total_variance' => $totalVariance,
            'total_variance_pct' => $totalBudgeted != 0
                ? round(($totalVariance / abs($totalBudgeted)) * 100, 2)
                : 0,
            'by_account_type' => $byAccountType,
            'top_over_budget' => $overBudget,
            'top_under_budget' => $underBudget,
        ];
    }

    /**
     * Format a budget for API response.
     */
    protected function formatBudget(Budget $budget): array
    {
        return [
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
            'lines_count' => $budget->lines_count ?? $budget->lines()->count(),
            'created_at' => $budget->created_at?->toIso8601String(),
            'updated_at' => $budget->updated_at?->toIso8601String(),
        ];
    }
}

// CLAUDE-CHECKPOINT
