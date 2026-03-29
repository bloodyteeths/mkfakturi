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
     * Get account type labels map.
     */
    public function getAccountTypeLabels(): array
    {
        return $this->accountTypeLabels;
    }

    /**
     * List budgets for a company with optional filters.
     *
     * @return array
     */
    public function list(int $companyId, array $filters = []): array
    {
        $query = Budget::forCompany($companyId)
            ->with(['costCenter:id,name,code,color', 'createdBy', 'approvedBy'])
            ->withCount('lines')
            ->withSum('lines', 'amount');

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

        if (! empty($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        $budgets = $query->orderBy('created_at', 'desc')->limit(200)->get();

        return $budgets->map(fn (Budget $b) => $this->formatBudget($b))->toArray();
    }

    /**
     * Create a new budget with lines.
     */
    public function create(int $companyId, array $data): Budget
    {
        return DB::transaction(function () use ($companyId, $data) {
            // Auto-generate sequential budget number: BUD-YYYY-NNN
            $year = date('Y', strtotime($data['start_date']));
            $lastNumber = Budget::where('company_id', $companyId)
                ->where('number', 'like', "BUD-{$year}-%")
                ->orderByDesc('number')
                ->value('number');
            $seq = $lastNumber ? (int) substr($lastNumber, -3) + 1 : 1;
            $number = sprintf('BUD-%s-%03d', $year, $seq);

            $budget = Budget::create([
                'company_id' => $companyId,
                'number' => $number,
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
     * Clone a budget with all its lines.
     */
    public function clone(Budget $budget): Budget
    {
        return DB::transaction(function () use ($budget) {
            $budget->loadMissing('lines');

            $newBudget = $budget->replicate(['id', 'approved_by', 'approved_at', 'created_at', 'updated_at']);
            $newBudget->name = $budget->name . ' (копија)';
            $newBudget->status = 'draft';
            $newBudget->created_by = auth()->id();
            $newBudget->save();

            foreach ($budget->lines as $line) {
                $newLine = $line->replicate(['id', 'budget_id', 'created_at', 'updated_at']);
                $newLine->budget_id = $newBudget->id;
                $newLine->save();
            }

            Log::info('Budget cloned', [
                'source_budget_id' => $budget->id,
                'new_budget_id' => $newBudget->id,
                'company_id' => $budget->company_id,
            ]);

            return $newBudget->load('lines');
        });
    }

    /**
     * Archive an approved or locked budget.
     */
    public function archive(Budget $budget): Budget
    {
        if (! in_array($budget->status, ['approved', 'locked'])) {
            throw new \InvalidArgumentException('Only approved or locked budgets can be archived.');
        }

        $budget->update(['status' => 'archived']);

        Log::info('Budget archived', [
            'budget_id' => $budget->id,
            'company_id' => $budget->company_id,
        ]);

        return $budget->fresh();
    }

    /**
     * Category labels for smart budget (localized).
     */
    protected array $smartCategoryLabels = [
        'invoice_revenue' => [
            'mk' => 'Приходи од фактури',
            'en' => 'Invoice Revenue',
            'sq' => 'Te ardhura nga faturat',
            'tr' => 'Fatura Gelirleri',
        ],
        'recurring_revenue' => [
            'mk' => 'Повторувачки приходи',
            'en' => 'Recurring Revenue',
            'sq' => 'Te ardhura te perseritura',
            'tr' => 'Tekrarlayan Gelir',
        ],
        'bill_expenses' => [
            'mk' => 'Трошоци од сметки',
            'en' => 'Bill Expenses',
            'sq' => 'Shpenzime nga faturat',
            'tr' => 'Fatura Giderleri',
        ],
    ];

    /**
     * Map expense category names (keywords) to IFRS account types.
     * Case-insensitive matching via str_contains.
     */
    protected array $expenseCategoryMapping = [
        // Rent / Office
        'наем' => 'OVERHEAD_EXPENSE',
        'кирија' => 'OVERHEAD_EXPENSE',
        'rent' => 'OVERHEAD_EXPENSE',
        'qira' => 'OVERHEAD_EXPENSE',
        'kira' => 'OVERHEAD_EXPENSE',
        'канцелари' => 'OVERHEAD_EXPENSE',
        'office' => 'OVERHEAD_EXPENSE',
        'ofis' => 'OVERHEAD_EXPENSE',
        'zyre' => 'OVERHEAD_EXPENSE',
        // Utilities
        'комунал' => 'OVERHEAD_EXPENSE',
        'utilit' => 'OVERHEAD_EXPENSE',
        'komunal' => 'OVERHEAD_EXPENSE',
        'струја' => 'OVERHEAD_EXPENSE',
        'вода' => 'OVERHEAD_EXPENSE',
        // Salaries
        'плата' => 'OPERATING_EXPENSE',
        'плати' => 'OPERATING_EXPENSE',
        'salary' => 'OPERATING_EXPENSE',
        'paga' => 'OPERATING_EXPENSE',
        'maas' => 'OPERATING_EXPENSE',
        // Materials / Direct
        'материјал' => 'DIRECT_EXPENSE',
        'material' => 'DIRECT_EXPENSE',
        'malzeme' => 'DIRECT_EXPENSE',
        'набавк' => 'DIRECT_EXPENSE',
        'purchase' => 'DIRECT_EXPENSE',
        'blerje' => 'DIRECT_EXPENSE',
    ];

    /**
     * Generate a smart budget from real company data (invoices, bills, expenses).
     *
     * Unlike prefillFromActuals() which uses IFRS ledgers, this method queries
     * actual transaction data that company users create: invoices, bills, expenses.
     *
     * @return array Simplified budget proposal with categories
     */
    public function generateSmartBudget(int $companyId, string $year, ?float $growthPct = 0, string $locale = 'mk'): array
    {
        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            $monthExpr = "CAST(strftime('%%m', %s) AS INTEGER)";
            $yearFilter = "strftime('%%Y', %s) = ?";
        } else {
            $monthExpr = "MONTH(%s)";
            $yearFilter = "YEAR(%s) = ?";
        }

        $multiplier = 1 + (($growthPct ?? 0) / 100);
        $nextYear = (int) $year + 1;
        $categories = [];

        // 1. Revenue from invoices
        $invoiceMonthExpr = sprintf($monthExpr, 'invoice_date');
        $invoiceYearFilter = sprintf($yearFilter, 'invoice_date');

        $invoiceRows = DB::table('invoices')
            ->where('company_id', $companyId)
            ->where('status', '!=', 'DRAFT')
            ->whereRaw($invoiceYearFilter, [(string) $year])
            ->select(
                DB::raw("{$invoiceMonthExpr} as month"),
                DB::raw('SUM(COALESCE(base_total, total)) as total_cents')
            )
            ->groupBy(DB::raw($invoiceMonthExpr))
            ->orderBy(DB::raw($invoiceMonthExpr))
            ->get();

        if ($invoiceRows->isNotEmpty()) {
            $monthly = [];
            $total = 0;
            foreach ($invoiceRows as $row) {
                $amount = round(($row->total_cents ?? 0) / 100, 2);
                $adjusted = round($amount * $multiplier, 2);
                $monthly[(int) $row->month] = $adjusted;
                $total += $adjusted;
            }
            $categories[] = [
                'key' => 'invoice_revenue',
                'label' => $this->smartCategoryLabels['invoice_revenue'][$locale] ?? $this->smartCategoryLabels['invoice_revenue']['en'],
                'account_type' => 'OPERATING_REVENUE',
                'monthly' => $monthly,
                'total' => round($total, 2),
                'original_total' => round($total / $multiplier, 2),
            ];
        }

        // 2. Recurring revenue projection
        $recurringTotal = $this->projectRecurringRevenue($companyId, $nextYear, $locale);
        if ($recurringTotal) {
            $categories[] = $recurringTotal;
        }

        // 3. Expenses from bills
        $billMonthExpr = sprintf($monthExpr, 'bill_date');
        $billYearFilter = sprintf($yearFilter, 'bill_date');

        $billRows = DB::table('bills')
            ->where('company_id', $companyId)
            ->where('status', '!=', 'DRAFT')
            ->whereNull('deleted_at')
            ->whereRaw($billYearFilter, [(string) $year])
            ->select(
                DB::raw("{$billMonthExpr} as month"),
                DB::raw('SUM(COALESCE(base_total, total)) as total_cents')
            )
            ->groupBy(DB::raw($billMonthExpr))
            ->orderBy(DB::raw($billMonthExpr))
            ->get();

        if ($billRows->isNotEmpty()) {
            $monthly = [];
            $total = 0;
            foreach ($billRows as $row) {
                $amount = round(($row->total_cents ?? 0) / 100, 2);
                $adjusted = round($amount * $multiplier, 2);
                $monthly[(int) $row->month] = $adjusted;
                $total += $adjusted;
            }
            $categories[] = [
                'key' => 'bill_expenses',
                'label' => $this->smartCategoryLabels['bill_expenses'][$locale] ?? $this->smartCategoryLabels['bill_expenses']['en'],
                'account_type' => 'OPERATING_EXPENSE',
                'monthly' => $monthly,
                'total' => round($total, 2),
                'original_total' => round($total / $multiplier, 2),
            ];
        }

        // 4. Expenses by category
        $expMonthExpr = sprintf($monthExpr, 'e.expense_date');
        $expYearFilter = sprintf($yearFilter, 'e.expense_date');

        $catExpenseRows = DB::table('expenses as e')
            ->join('expense_categories as ec', 'e.expense_category_id', '=', 'ec.id')
            ->where('e.company_id', $companyId)
            ->whereRaw($expYearFilter, [(string) $year])
            ->select(
                'ec.id as category_id',
                'ec.name as category_name',
                DB::raw("{$expMonthExpr} as month"),
                DB::raw('SUM(COALESCE(e.base_amount, e.amount)) as total_cents')
            )
            ->groupBy('ec.id', 'ec.name', DB::raw($expMonthExpr))
            ->orderBy('ec.name')
            ->orderBy(DB::raw($expMonthExpr))
            ->get();

        if ($catExpenseRows->isNotEmpty()) {
            // Group by category
            $byCategory = $catExpenseRows->groupBy('category_id');

            foreach ($byCategory as $catId => $rows) {
                $categoryName = $rows->first()->category_name;
                $accountType = $this->mapExpenseCategoryToIfrs($categoryName);

                $monthly = [];
                $total = 0;
                foreach ($rows as $row) {
                    $amount = round(($row->total_cents ?? 0) / 100, 2);
                    $adjusted = round($amount * $multiplier, 2);
                    $monthly[(int) $row->month] = $adjusted;
                    $total += $adjusted;
                }

                $categories[] = [
                    'key' => 'expense_cat_' . $catId,
                    'label' => $categoryName,
                    'account_type' => $accountType,
                    'monthly' => $monthly,
                    'total' => round($total, 2),
                    'original_total' => round($total / $multiplier, 2),
                ];
            }
        }

        // Calculate summary
        $totalRevenue = collect($categories)
            ->filter(fn ($c) => str_contains($c['account_type'], 'REVENUE'))
            ->sum('total');
        $totalExpenses = collect($categories)
            ->filter(fn ($c) => ! str_contains($c['account_type'], 'REVENUE'))
            ->sum('total');

        return [
            'source_year' => $year,
            'target_year' => (string) $nextYear,
            'growth_pct' => $growthPct ?? 0,
            'has_data' => ! empty($categories),
            'summary' => [
                'total_revenue' => round($totalRevenue, 2),
                'total_expenses' => round($totalExpenses, 2),
                'projected_profit' => round($totalRevenue - $totalExpenses, 2),
            ],
            'categories' => $categories,
        ];
    }

    /**
     * Project recurring revenue for a target year.
     */
    protected function projectRecurringRevenue(int $companyId, int $targetYear, string $locale): ?array
    {
        $recurring = DB::table('recurring_invoices')
            ->where('company_id', $companyId)
            ->where('status', 'ACTIVE')
            ->get();

        if ($recurring->isEmpty()) {
            return null;
        }

        $monthly = [];
        $total = 0;

        foreach ($recurring as $ri) {
            $amount = round(($ri->total ?? 0) / 100, 2);
            // Simple projection: assume monthly frequency for each active recurring invoice
            // Distribute across all 12 months
            for ($m = 1; $m <= 12; $m++) {
                $monthly[$m] = ($monthly[$m] ?? 0) + $amount;
                $total += $amount;
            }
        }

        if ($total <= 0) {
            return null;
        }

        return [
            'key' => 'recurring_revenue',
            'label' => $this->smartCategoryLabels['recurring_revenue'][$locale] ?? $this->smartCategoryLabels['recurring_revenue']['en'],
            'account_type' => 'OPERATING_REVENUE',
            'monthly' => $monthly,
            'total' => round($total, 2),
            'original_total' => round($total, 2),
        ];
    }

    /**
     * Map an expense category name to an IFRS account type via keyword matching.
     */
    protected function mapExpenseCategoryToIfrs(string $categoryName): string
    {
        $lower = mb_strtolower($categoryName);
        foreach ($this->expenseCategoryMapping as $keyword => $accountType) {
            if (str_contains($lower, $keyword)) {
                return $accountType;
            }
        }

        return 'OPERATING_EXPENSE'; // Default fallback
    }

    /**
     * Pre-fill budget lines from actual IFRS ledger data for a given year.
     *
     * Queries ifrs_ledgers joined with ifrs_accounts to get actual amounts
     * per account_type per month, with optional growth percentage applied.
     *
     * @return array Array of budget line structures
     */
    public function prefillFromActuals(int $companyId, string $year, ?float $growthPct = 0, ?int $costCenterId = null): array
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

        $costCenterFilter = '';
        $params = [$entityId, (string) $year];
        if ($costCenterId) {
            $costCenterFilter = 'AND l.cost_center_id = ?';
            $params[] = $costCenterId;
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
              {$costCenterFilter}
              AND l.deleted_at IS NULL
              AND a.deleted_at IS NULL
            GROUP BY a.account_type, {$monthExpr}
            ORDER BY a.account_type, {$monthExpr}
        ", $params);

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
        $grouped = $budget->lines->groupBy(function (BudgetLine $line) use ($budget) {
            $start = $line->period_start ?? $budget->start_date;
            $end = $line->period_end ?? $budget->end_date;

            return $line->account_type . '|' . ($start ? $start->toDateString() : '') . '|' . ($end ? $end->toDateString() : '');
        });

        foreach ($grouped as $key => $lines) {
            [$accountType, $periodStart, $periodEnd] = explode('|', $key);

            // Skip lines with missing period dates
            if (empty($periodStart) || empty($periodEnd)) {
                continue;
            }

            $budgeted = $lines->sum('amount');

            // Query actual from ledger, filtering by cost center if budget has one
            $costCenterFilter = '';
            $queryParams = [$entityId, $accountType, $periodStart, $periodEnd];
            if ($budget->cost_center_id) {
                $costCenterFilter = 'AND l.cost_center_id = ?';
                $queryParams[] = $budget->cost_center_id;
            }

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
                  {$costCenterFilter}
                  AND l.deleted_at IS NULL
                  AND a.deleted_at IS NULL
            ", $queryParams);

            $row = $actuals[0] ?? null;
            $isRevenue = str_contains($accountType, 'REVENUE');
            $actual = 0;

            if ($row) {
                $actual = $isRevenue
                    ? round(($row->total_credit ?? 0) - ($row->total_debit ?? 0), 2)
                    : round(($row->total_debit ?? 0) - ($row->total_credit ?? 0), 2);
            }

            // Fallback: if IFRS has no data, try real company data (invoices/bills/expenses)
            if ($actual == 0) {
                $actual = $this->getActualFromCompanyData(
                    $budget->company_id,
                    $accountType,
                    $periodStart,
                    $periodEnd
                );
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
     * Get actual amounts from real company data (invoices/bills/expenses)
     * as fallback when IFRS ledger has no data.
     */
    protected function getActualFromCompanyData(int $companyId, string $accountType, string $periodStart, string $periodEnd): float
    {
        $isRevenue = str_contains($accountType, 'REVENUE');

        if ($isRevenue) {
            // Revenue: sum invoices (non-draft) in the period
            $invoiceTotal = DB::table('invoices')
                ->where('company_id', $companyId)
                ->where('status', '<>', 'DRAFT')
                ->where('invoice_date', '>=', $periodStart)
                ->where('invoice_date', '<=', $periodEnd)
                ->whereNull('deleted_at')
                ->sum('base_total');

            return round($invoiceTotal / 100, 2);
        }

        // Expenses: sum bills + expenses in the period
        $billTotal = DB::table('bills')
            ->where('company_id', $companyId)
            ->where('status', '<>', 'DRAFT')
            ->where('bill_date', '>=', $periodStart)
            ->where('bill_date', '<=', $periodEnd)
            ->whereNull('deleted_at')
            ->sum('base_total');

        $expenseTotal = DB::table('expenses')
            ->where('company_id', $companyId)
            ->where('expense_date', '>=', $periodStart)
            ->where('expense_date', '<=', $periodEnd)
            ->whereNull('deleted_at')
            ->sum('base_amount');

        return round(($billTotal + $expenseTotal) / 100, 2);
    }

    /**
     * Format a budget for API response.
     */
    protected function formatBudget(Budget $budget): array
    {
        return [
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
            'lines_count' => $budget->lines_count ?? $budget->lines()->count(),
            'total_amount' => (float) ($budget->lines_sum_amount ?? $budget->lines()->sum('amount')),
            'created_at' => $budget->created_at?->toIso8601String(),
            'updated_at' => $budget->updated_at?->toIso8601String(),
        ];
    }
}

// CLAUDE-CHECKPOINT
