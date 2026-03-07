<?php

namespace Modules\Mk\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Mk\Models\CustomReportTemplate;

class CustomReportService
{
    /**
     * List saved templates for a company.
     */
    public function list(int $companyId, array $filters = []): array
    {
        $query = CustomReportTemplate::forCompany($companyId)
            ->with('createdBy:id,name');

        if (! empty($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        $templates = $query->orderBy('updated_at', 'desc')->get();

        return $templates->map(fn (CustomReportTemplate $t) => $this->formatTemplate($t))->toArray();
    }

    /**
     * Create a new template.
     */
    public function create(int $companyId, array $data): CustomReportTemplate
    {
        $template = CustomReportTemplate::create([
            'company_id' => $companyId,
            'name' => $data['name'],
            'account_filter' => $data['account_filter'],
            'columns' => $data['columns'],
            'period_type' => $data['period_type'] ?? null,
            'group_by' => $data['group_by'] ?? null,
            'comparison' => $data['comparison'] ?? null,
            'schedule_cron' => $data['schedule_cron'] ?? null,
            'schedule_emails' => $data['schedule_emails'] ?? null,
            'created_by' => $data['created_by'] ?? null,
        ]);

        Log::info('Custom report template created', [
            'template_id' => $template->id,
            'company_id' => $companyId,
            'name' => $template->name,
        ]);

        return $template;
    }

    /**
     * Update an existing template.
     */
    public function update(int $id, array $data): CustomReportTemplate
    {
        $template = CustomReportTemplate::findOrFail($id);

        $template->update(array_filter([
            'name' => $data['name'] ?? null,
            'account_filter' => $data['account_filter'] ?? null,
            'columns' => $data['columns'] ?? null,
            'period_type' => array_key_exists('period_type', $data) ? $data['period_type'] : null,
            'group_by' => array_key_exists('group_by', $data) ? $data['group_by'] : null,
            'comparison' => array_key_exists('comparison', $data) ? $data['comparison'] : null,
            'schedule_cron' => array_key_exists('schedule_cron', $data) ? $data['schedule_cron'] : null,
            'schedule_emails' => array_key_exists('schedule_emails', $data) ? $data['schedule_emails'] : null,
        ], fn ($v) => $v !== null));

        Log::info('Custom report template updated', [
            'template_id' => $template->id,
            'company_id' => $template->company_id,
        ]);

        return $template->fresh();
    }

    /**
     * Delete a template.
     */
    public function delete(int $id): void
    {
        $template = CustomReportTemplate::findOrFail($id);

        Log::info('Custom report template deleted', [
            'template_id' => $template->id,
            'company_id' => $template->company_id,
            'name' => $template->name,
        ]);

        $template->delete();
    }

    /**
     * Preview a report from ad-hoc config (not saved yet).
     *
     * @param  int  $companyId
     * @param  array  $config  { account_filter, columns, period_type, group_by, comparison, date_from?, date_to? }
     * @return array
     */
    public function preview(int $companyId, array $config): array
    {
        return $this->runReport($companyId, $config);
    }

    /**
     * Execute a saved template.
     */
    public function execute(int $templateId, array $overrides = []): array
    {
        $template = CustomReportTemplate::findOrFail($templateId);

        $config = [
            'account_filter' => $template->account_filter,
            'columns' => $template->columns,
            'period_type' => $template->period_type,
            'group_by' => $template->group_by,
            'comparison' => $template->comparison,
            'date_from' => $overrides['date_from'] ?? null,
            'date_to' => $overrides['date_to'] ?? null,
        ];

        return [
            'template' => $this->formatTemplate($template),
            'report' => $this->runReport($template->company_id, $config),
        ];
    }

    /**
     * Generate CSV/Excel export for a template.
     */
    public function exportExcel(int $templateId): array
    {
        $result = $this->execute($templateId);

        return $result;
    }

    /**
     * Generate PDF export for a template.
     */
    public function exportPdf(int $templateId): array
    {
        $result = $this->execute($templateId);

        return $result;
    }

    // ---- Core Report Engine ----

    /**
     * Run the report query against ifrs_accounts and ifrs_ledgers.
     *
     * @param  int  $companyId
     * @param  array  $config
     * @return array
     */
    protected function runReport(int $companyId, array $config): array
    {
        $entityId = $companyId; // entity_id = company_id in IFRS tables
        $accountFilter = $config['account_filter'] ?? [];
        $columns = $config['columns'] ?? ['code', 'name', 'opening', 'debit', 'credit', 'closing'];
        $periodType = $config['period_type'] ?? 'year';
        $groupBy = $config['group_by'] ?? null;
        $comparison = $config['comparison'] ?? null;

        // Determine date range
        $dates = $this->resolveDateRange($periodType, $config);
        $dateFrom = $dates['from'];
        $dateTo = $dates['to'];

        // Fetch matching accounts
        $accounts = $this->getFilteredAccounts($entityId, $accountFilter);

        if (empty($accounts)) {
            return [
                'columns' => $columns,
                'rows' => [],
                'totals' => [],
                'period' => ['from' => $dateFrom, 'to' => $dateTo],
                'comparison_rows' => [],
            ];
        }

        $accountIds = array_column($accounts, 'id');

        // Build rows based on grouping
        $rows = [];
        if ($groupBy === 'month') {
            $rows = $this->buildGroupedRows($entityId, $accountIds, $accounts, $dateFrom, $dateTo, 'month', $columns);
        } elseif ($groupBy === 'quarter') {
            $rows = $this->buildGroupedRows($entityId, $accountIds, $accounts, $dateFrom, $dateTo, 'quarter', $columns);
        } elseif ($groupBy === 'cost_center') {
            $rows = $this->buildCostCenterRows($entityId, $accountIds, $accounts, $dateFrom, $dateTo, $columns);
        } else {
            $rows = $this->buildFlatRows($entityId, $accountIds, $accounts, $dateFrom, $dateTo, $columns);
        }

        // Compute totals
        $totals = $this->computeTotals($rows, $columns);

        // Comparison data
        $comparisonRows = [];
        if ($comparison === 'previous_year') {
            $prevFrom = Carbon::parse($dateFrom)->subYear()->toDateString();
            $prevTo = Carbon::parse($dateTo)->subYear()->toDateString();

            if ($groupBy === 'month') {
                $comparisonRows = $this->buildGroupedRows($entityId, $accountIds, $accounts, $prevFrom, $prevTo, 'month', $columns);
            } elseif ($groupBy === 'quarter') {
                $comparisonRows = $this->buildGroupedRows($entityId, $accountIds, $accounts, $prevFrom, $prevTo, 'quarter', $columns);
            } else {
                $comparisonRows = $this->buildFlatRows($entityId, $accountIds, $accounts, $prevFrom, $prevTo, $columns);
            }
        } elseif ($comparison === 'budget') {
            $comparisonRows = $this->buildBudgetComparison($companyId, $accountIds, $accounts, $dateFrom, $dateTo);
        }

        return [
            'columns' => $columns,
            'rows' => $rows,
            'totals' => $totals,
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'comparison_rows' => $comparisonRows,
        ];
    }

    /**
     * Resolve date range from period type.
     */
    protected function resolveDateRange(string $periodType, array $config): array
    {
        $now = Carbon::now();

        switch ($periodType) {
            case 'month':
                return [
                    'from' => $config['date_from'] ?? $now->copy()->startOfMonth()->toDateString(),
                    'to' => $config['date_to'] ?? $now->copy()->endOfMonth()->toDateString(),
                ];
            case 'quarter':
                return [
                    'from' => $config['date_from'] ?? $now->copy()->startOfQuarter()->toDateString(),
                    'to' => $config['date_to'] ?? $now->copy()->endOfQuarter()->toDateString(),
                ];
            case 'year':
                return [
                    'from' => $config['date_from'] ?? $now->copy()->startOfYear()->toDateString(),
                    'to' => $config['date_to'] ?? $now->copy()->endOfYear()->toDateString(),
                ];
            case 'custom':
                return [
                    'from' => $config['date_from'] ?? $now->copy()->startOfYear()->toDateString(),
                    'to' => $config['date_to'] ?? $now->copy()->endOfYear()->toDateString(),
                ];
            default:
                return [
                    'from' => $config['date_from'] ?? $now->copy()->startOfYear()->toDateString(),
                    'to' => $config['date_to'] ?? $now->copy()->endOfYear()->toDateString(),
                ];
        }
    }

    /**
     * Get accounts matching the filter.
     *
     * Filter types:
     *  - range: { type: "range", from: "1000", to: "1999" }
     *  - category: { type: "category", categories: ["CURRENT_ASSET", "NON_CURRENT_ASSET"] }
     *  - specific: { type: "specific", codes: ["1000", "1200", "2200"] }
     */
    protected function getFilteredAccounts(int $entityId, array $filter): array
    {
        $query = DB::table('ifrs_accounts')
            ->where('entity_id', $entityId)
            ->whereNull('deleted_at')
            ->select('id', 'code', 'name', 'account_type');

        $filterType = $filter['type'] ?? 'all';

        switch ($filterType) {
            case 'range':
                $from = $filter['from'] ?? '0';
                $to = $filter['to'] ?? '9999';
                $query->whereBetween('code', [$from, $to]);
                break;

            case 'category':
                $categories = $filter['categories'] ?? [];
                if (! empty($categories)) {
                    $query->whereIn('account_type', $categories);
                }
                break;

            case 'specific':
                $codes = $filter['codes'] ?? [];
                if (! empty($codes)) {
                    $query->whereIn('code', $codes);
                }
                break;

            case 'all':
            default:
                // No additional filter
                break;
        }

        $results = $query->orderBy('code')->get();

        return $results->map(fn ($row) => (array) $row)->toArray();
    }

    /**
     * Build flat (ungrouped) rows - one row per account.
     */
    protected function buildFlatRows(int $entityId, array $accountIds, array $accounts, string $dateFrom, string $dateTo, array $columns): array
    {
        if (empty($accountIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($accountIds), '?'));

        // Opening balances (all ledger entries before dateFrom)
        $openingData = [];
        if (in_array('opening', $columns)) {
            $openingRows = DB::select("
                SELECT
                    l.post_account,
                    SUM(CASE WHEN l.entry_type = 'D' THEN l.amount / l.rate ELSE 0 END) as total_debit,
                    SUM(CASE WHEN l.entry_type = 'C' THEN l.amount / l.rate ELSE 0 END) as total_credit
                FROM ifrs_ledgers l
                WHERE l.entity_id = ?
                  AND l.post_account IN ({$placeholders})
                  AND l.posting_date < ?
                  AND l.deleted_at IS NULL
                GROUP BY l.post_account
            ", array_merge([$entityId], $accountIds, [$dateFrom]));

            foreach ($openingRows as $row) {
                $openingData[$row->post_account] = [
                    'debit' => round((float) $row->total_debit, 2),
                    'credit' => round((float) $row->total_credit, 2),
                ];
            }
        }

        // Period movements
        $movementData = [];
        $movementRows = DB::select("
            SELECT
                l.post_account,
                SUM(CASE WHEN l.entry_type = 'D' THEN l.amount / l.rate ELSE 0 END) as total_debit,
                SUM(CASE WHEN l.entry_type = 'C' THEN l.amount / l.rate ELSE 0 END) as total_credit
            FROM ifrs_ledgers l
            WHERE l.entity_id = ?
              AND l.post_account IN ({$placeholders})
              AND l.posting_date >= ?
              AND l.posting_date <= ?
              AND l.deleted_at IS NULL
            GROUP BY l.post_account
        ", array_merge([$entityId], $accountIds, [$dateFrom, $dateTo]));

        foreach ($movementRows as $row) {
            $movementData[$row->post_account] = [
                'debit' => round((float) $row->total_debit, 2),
                'credit' => round((float) $row->total_credit, 2),
            ];
        }

        // Build result rows
        $rows = [];
        $accountMap = [];
        foreach ($accounts as $acc) {
            $accountMap[$acc['id']] = $acc;
        }

        foreach ($accountIds as $accId) {
            $acc = $accountMap[$accId] ?? null;
            if (! $acc) {
                continue;
            }

            $opening = $openingData[$accId] ?? ['debit' => 0, 'credit' => 0];
            $openingBalance = round($opening['debit'] - $opening['credit'], 2);

            $movement = $movementData[$accId] ?? ['debit' => 0, 'credit' => 0];
            $debit = $movement['debit'];
            $credit = $movement['credit'];
            $closing = round($openingBalance + $debit - $credit, 2);

            $row = [
                'account_id' => $accId,
            ];

            if (in_array('code', $columns)) {
                $row['code'] = $acc['code'] ?? '';
            }
            if (in_array('name', $columns)) {
                $row['name'] = $acc['name'] ?? '';
            }
            if (in_array('opening', $columns)) {
                $row['opening'] = $openingBalance;
            }
            if (in_array('debit', $columns)) {
                $row['debit'] = $debit;
            }
            if (in_array('credit', $columns)) {
                $row['credit'] = $credit;
            }
            if (in_array('closing', $columns)) {
                $row['closing'] = $closing;
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Build grouped rows (by month or quarter).
     */
    protected function buildGroupedRows(int $entityId, array $accountIds, array $accounts, string $dateFrom, string $dateTo, string $groupBy, array $columns): array
    {
        if (empty($accountIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($accountIds), '?'));

        $driver = DB::getDriverName();
        if ($groupBy === 'month') {
            $groupExpr = $driver === 'sqlite'
                ? "strftime('%Y-%m', l.posting_date)"
                : "DATE_FORMAT(l.posting_date, '%Y-%m')";
        } else {
            $groupExpr = $driver === 'sqlite'
                ? "(strftime('%Y', l.posting_date) || '-Q' || ((CAST(strftime('%m', l.posting_date) AS INTEGER) + 2) / 3))"
                : "CONCAT(YEAR(l.posting_date), '-Q', QUARTER(l.posting_date))";
        }

        $movementRows = DB::select("
            SELECT
                l.post_account,
                {$groupExpr} as period_group,
                SUM(CASE WHEN l.entry_type = 'D' THEN l.amount / l.rate ELSE 0 END) as total_debit,
                SUM(CASE WHEN l.entry_type = 'C' THEN l.amount / l.rate ELSE 0 END) as total_credit
            FROM ifrs_ledgers l
            WHERE l.entity_id = ?
              AND l.post_account IN ({$placeholders})
              AND l.posting_date >= ?
              AND l.posting_date <= ?
              AND l.deleted_at IS NULL
            GROUP BY l.post_account, period_group
            ORDER BY period_group, l.post_account
        ", array_merge([$entityId], $accountIds, [$dateFrom, $dateTo]));

        $accountMap = [];
        foreach ($accounts as $acc) {
            $accountMap[$acc['id']] = $acc;
        }

        $rows = [];
        foreach ($movementRows as $row) {
            $acc = $accountMap[$row->post_account] ?? null;
            if (! $acc) {
                continue;
            }

            $debit = round((float) $row->total_debit, 2);
            $credit = round((float) $row->total_credit, 2);

            $resultRow = [
                'account_id' => $row->post_account,
                'period_group' => $row->period_group,
            ];

            if (in_array('code', $columns)) {
                $resultRow['code'] = $acc['code'] ?? '';
            }
            if (in_array('name', $columns)) {
                $resultRow['name'] = $acc['name'] ?? '';
            }
            if (in_array('debit', $columns)) {
                $resultRow['debit'] = $debit;
            }
            if (in_array('credit', $columns)) {
                $resultRow['credit'] = $credit;
            }
            if (in_array('closing', $columns)) {
                $resultRow['closing'] = round($debit - $credit, 2);
            }

            $rows[] = $resultRow;
        }

        return $rows;
    }

    /**
     * Build rows grouped by cost center.
     */
    protected function buildCostCenterRows(int $entityId, array $accountIds, array $accounts, string $dateFrom, string $dateTo, array $columns): array
    {
        if (empty($accountIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($accountIds), '?'));

        // ifrs_ledgers may have a cost_center_id column via line items
        // We join through ifrs_line_items to get cost center info
        $movementRows = DB::select("
            SELECT
                l.post_account,
                COALESCE(li.cost_center_id, 0) as cost_center_id,
                SUM(CASE WHEN l.entry_type = 'D' THEN l.amount / l.rate ELSE 0 END) as total_debit,
                SUM(CASE WHEN l.entry_type = 'C' THEN l.amount / l.rate ELSE 0 END) as total_credit
            FROM ifrs_ledgers l
            LEFT JOIN ifrs_line_items li ON li.id = l.line_item_id
            WHERE l.entity_id = ?
              AND l.post_account IN ({$placeholders})
              AND l.posting_date >= ?
              AND l.posting_date <= ?
              AND l.deleted_at IS NULL
            GROUP BY l.post_account, cost_center_id
            ORDER BY cost_center_id, l.post_account
        ", array_merge([$entityId], $accountIds, [$dateFrom, $dateTo]));

        // Load cost center names
        $costCenterIds = array_unique(array_column($movementRows, 'cost_center_id'));
        $costCenterNames = [];
        if (! empty($costCenterIds)) {
            $ccRows = DB::table('cost_centers')
                ->whereIn('id', $costCenterIds)
                ->select('id', 'name', 'code')
                ->get();
            foreach ($ccRows as $cc) {
                $costCenterNames[$cc->id] = $cc->name . ($cc->code ? " ({$cc->code})" : '');
            }
        }

        $accountMap = [];
        foreach ($accounts as $acc) {
            $accountMap[$acc['id']] = $acc;
        }

        $rows = [];
        foreach ($movementRows as $row) {
            $acc = $accountMap[$row->post_account] ?? null;
            if (! $acc) {
                continue;
            }

            $debit = round((float) $row->total_debit, 2);
            $credit = round((float) $row->total_credit, 2);
            $ccId = (int) $row->cost_center_id;

            $resultRow = [
                'account_id' => $row->post_account,
                'cost_center_id' => $ccId,
                'cost_center_name' => $costCenterNames[$ccId] ?? ($ccId === 0 ? 'Unassigned' : "CC #{$ccId}"),
            ];

            if (in_array('code', $columns)) {
                $resultRow['code'] = $acc['code'] ?? '';
            }
            if (in_array('name', $columns)) {
                $resultRow['name'] = $acc['name'] ?? '';
            }
            if (in_array('debit', $columns)) {
                $resultRow['debit'] = $debit;
            }
            if (in_array('credit', $columns)) {
                $resultRow['credit'] = $credit;
            }
            if (in_array('closing', $columns)) {
                $resultRow['closing'] = round($debit - $credit, 2);
            }

            $rows[] = $resultRow;
        }

        return $rows;
    }

    /**
     * Build budget comparison data from budget_lines.
     */
    protected function buildBudgetComparison(int $companyId, array $accountIds, array $accounts, string $dateFrom, string $dateTo): array
    {
        // Look for an approved budget that overlaps this period
        $budget = DB::table('budgets')
            ->where('company_id', $companyId)
            ->whereIn('status', ['approved', 'locked'])
            ->where('start_date', '<=', $dateTo)
            ->where('end_date', '>=', $dateFrom)
            ->orderBy('created_at', 'desc')
            ->first();

        if (! $budget) {
            return [];
        }

        $budgetLines = DB::table('budget_lines')
            ->where('budget_id', $budget->id)
            ->where('period_start', '>=', $dateFrom)
            ->where('period_end', '<=', $dateTo)
            ->get();

        $accountMap = [];
        foreach ($accounts as $acc) {
            $accountMap[$acc['id']] = $acc;
        }

        // Group budget amounts by account type
        $budgetByType = [];
        foreach ($budgetLines as $line) {
            $type = $line->account_type;
            if (! isset($budgetByType[$type])) {
                $budgetByType[$type] = 0;
            }
            $budgetByType[$type] += (float) $line->amount;
        }

        $rows = [];
        foreach ($budgetByType as $type => $budgetAmount) {
            $rows[] = [
                'account_type' => $type,
                'budget' => round($budgetAmount, 2),
            ];
        }

        return $rows;
    }

    /**
     * Compute column totals from rows.
     */
    protected function computeTotals(array $rows, array $columns): array
    {
        $totals = [];
        $numericColumns = ['opening', 'debit', 'credit', 'closing', 'budget', 'variance', 'variance_pct'];

        foreach ($numericColumns as $col) {
            if (in_array($col, $columns)) {
                $totals[$col] = 0;
            }
        }

        foreach ($rows as $row) {
            foreach ($totals as $col => &$val) {
                $val += (float) ($row[$col] ?? 0);
            }
            unset($val);
        }

        // Round totals
        foreach ($totals as $col => &$val) {
            $val = round($val, 2);
        }
        unset($val);

        return $totals;
    }

    /**
     * Format a template for API response.
     */
    protected function formatTemplate(CustomReportTemplate $template): array
    {
        return [
            'id' => $template->id,
            'company_id' => $template->company_id,
            'name' => $template->name,
            'account_filter' => $template->account_filter,
            'columns' => $template->columns,
            'period_type' => $template->period_type,
            'group_by' => $template->group_by,
            'comparison' => $template->comparison,
            'schedule_cron' => $template->schedule_cron,
            'schedule_emails' => $template->schedule_emails,
            'created_by' => $template->created_by,
            'created_by_user' => $template->createdBy ? [
                'id' => $template->createdBy->id,
                'name' => $template->createdBy->name,
            ] : null,
            'created_at' => $template->created_at?->toIso8601String(),
            'updated_at' => $template->updated_at?->toIso8601String(),
        ];
    }
}

// CLAUDE-CHECKPOINT
