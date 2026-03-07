<?php

namespace Modules\Mk\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use IFRS\Models\Account;
use IFRS\Models\Entity;

class CostCenterLedgerService
{
    /**
     * Tag all ledger entries for a transaction with a cost center ID.
     *
     * This is the workaround for the eloquent-ifrs Ledger::post() method
     * not propagating custom columns. Call this AFTER $transaction->post().
     */
    public function tagLedgerEntries(int $transactionId, int $costCenterId): int
    {
        if (! Schema::hasColumn('ifrs_ledgers', 'cost_center_id')) {
            Log::warning('ifrs_ledgers.cost_center_id column does not exist, skipping tagging');

            return 0;
        }

        $affected = DB::table('ifrs_ledgers')
            ->where('transaction_id', $transactionId)
            ->whereNull('deleted_at')
            ->update(['cost_center_id' => $costCenterId]);

        Log::info('Tagged ledger entries with cost center', [
            'transaction_id' => $transactionId,
            'cost_center_id' => $costCenterId,
            'rows_affected' => $affected,
        ]);

        return $affected;
    }

    /**
     * Get a trial balance filtered by cost center.
     *
     * Mirrors IfrsAdapter::getTrialBalanceSixColumn() but with cost_center_id filter.
     *
     * @return array
     */
    public function getFilteredTrialBalance(int $companyId, string $fromDate, string $toDate, ?int $costCenterId): array
    {
        // Resolve entity_id from company
        $entityId = $this->getEntityId($companyId);
        if (! $entityId) {
            return ['accounts' => [], 'totals' => $this->emptyTotals()];
        }

        $ccFilter = $costCenterId !== null;

        $rows = DB::select("
            SELECT
                a.id,
                a.code,
                a.name,
                a.account_type,
                COALESCE(SUM(CASE WHEN l.posting_date < ? AND l.entry_type = 'D' THEN l.amount / l.rate ELSE 0 END), 0) as pre_debit,
                COALESCE(SUM(CASE WHEN l.posting_date < ? AND l.entry_type = 'C' THEN l.amount / l.rate ELSE 0 END), 0) as pre_credit,
                COALESCE(SUM(CASE WHEN l.posting_date >= ? AND l.posting_date <= ? AND l.entry_type = 'D' THEN l.amount / l.rate ELSE 0 END), 0) as period_debit,
                COALESCE(SUM(CASE WHEN l.posting_date >= ? AND l.posting_date <= ? AND l.entry_type = 'C' THEN l.amount / l.rate ELSE 0 END), 0) as period_credit
            FROM ifrs_accounts a
            LEFT JOIN ifrs_ledgers l
                ON a.id = l.post_account
                AND l.entity_id = a.entity_id
                AND l.deleted_at IS NULL
                " . ($ccFilter ? 'AND l.cost_center_id = ?' : '') . "
            WHERE a.entity_id = ? AND a.deleted_at IS NULL
            GROUP BY a.id, a.code, a.name, a.account_type
            HAVING (pre_debit <> 0 OR pre_credit <> 0 OR period_debit <> 0 OR period_credit <> 0)
            ORDER BY a.code
        ", array_filter([
            $fromDate, $fromDate,
            $fromDate, $toDate,
            $fromDate, $toDate,
            $ccFilter ? $costCenterId : null,
            $entityId,
        ], fn ($v) => $v !== null));

        $accounts = [];
        $totals = $this->emptyTotals();

        foreach ($rows as $row) {
            $openingBalance = round($row->pre_debit - $row->pre_credit, 2);
            $closingBalance = round($openingBalance + $row->period_debit - $row->period_credit, 2);

            $openingDebit = $openingBalance > 0 ? $openingBalance : 0;
            $openingCredit = $openingBalance < 0 ? abs($openingBalance) : 0;
            $closingDebit = $closingBalance > 0 ? $closingBalance : 0;
            $closingCredit = $closingBalance < 0 ? abs($closingBalance) : 0;

            $periodDebit = round($row->period_debit, 2);
            $periodCredit = round($row->period_credit, 2);

            $accounts[] = [
                'id' => $row->id,
                'code' => $row->code,
                'name' => $row->name,
                'account_type' => $row->account_type,
                'opening_debit' => $openingDebit,
                'opening_credit' => $openingCredit,
                'period_debit' => $periodDebit,
                'period_credit' => $periodCredit,
                'closing_debit' => $closingDebit,
                'closing_credit' => $closingCredit,
            ];

            $totals['opening_debit'] += $openingDebit;
            $totals['opening_credit'] += $openingCredit;
            $totals['period_debit'] += $periodDebit;
            $totals['period_credit'] += $periodCredit;
            $totals['closing_debit'] += $closingDebit;
            $totals['closing_credit'] += $closingCredit;
        }

        // Round totals
        foreach ($totals as $k => $v) {
            $totals[$k] = round($v, 2);
        }

        return [
            'accounts' => $accounts,
            'totals' => $totals,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'cost_center_id' => $costCenterId,
            'is_balanced' => abs($totals['closing_debit'] - $totals['closing_credit']) < 0.01,
        ];
    }

    /**
     * Get general ledger for a specific account, filtered by cost center.
     *
     * @return array
     */
    public function getFilteredGeneralLedger(
        int $companyId,
        string $fromDate,
        string $toDate,
        int $accountId,
        ?int $costCenterId
    ): array {
        $entityId = $this->getEntityId($companyId);
        if (! $entityId) {
            return ['error' => 'IFRS Entity not available'];
        }

        $account = Account::where('entity_id', $entityId)->find($accountId);
        if (! $account) {
            return ['error' => 'Account not found in IFRS ledger'];
        }

        $ccFilter = $costCenterId !== null;

        // Opening balance
        $openingQuery = DB::table('ifrs_ledgers')
            ->where('entity_id', $entityId)
            ->where('post_account', $accountId)
            ->where('posting_date', '<', $fromDate)
            ->whereNull('deleted_at');

        if ($ccFilter) {
            $openingQuery->where('cost_center_id', $costCenterId);
        }

        $openingBalance = $openingQuery->selectRaw("
            SUM(CASE WHEN entry_type = 'D' THEN amount ELSE 0 END) -
            SUM(CASE WHEN entry_type = 'C' THEN amount ELSE 0 END) as balance
        ")->value('balance') ?? 0;

        // Period entries
        $entriesQuery = DB::table('ifrs_ledgers as l')
            ->join('ifrs_transactions as t', 'l.transaction_id', '=', 't.id')
            ->where('l.entity_id', $entityId)
            ->where('l.post_account', $accountId)
            ->whereBetween('l.posting_date', [$fromDate, $toDate])
            ->whereNull('l.deleted_at');

        if ($ccFilter) {
            $entriesQuery->where('l.cost_center_id', $costCenterId);
        }

        $entries = $entriesQuery->select([
            'l.posting_date as date',
            'l.transaction_id',
            't.transaction_type as document_type',
            't.transaction_no as document_number',
            't.narration',
            'l.entry_type',
            'l.amount',
        ])
            ->orderBy('l.posting_date')
            ->orderBy('l.id')
            ->get();

        $ledgerEntries = [];
        $runningBalance = $openingBalance;

        foreach ($entries as $entry) {
            $debit = $entry->entry_type === 'D' ? $entry->amount : 0;
            $credit = $entry->entry_type === 'C' ? $entry->amount : 0;
            $runningBalance += ($debit - $credit);

            $ledgerEntries[] = [
                'date' => $entry->date,
                'transaction_id' => $entry->transaction_id,
                'document_type' => $entry->document_type,
                'reference' => $entry->document_number ?? '',
                'description' => $entry->narration ?? '',
                'debit' => $debit,
                'credit' => $credit,
                'running_balance' => $runningBalance,
            ];
        }

        return [
            'account' => [
                'id' => $account->id,
                'name' => $account->name,
                'code' => $account->code ?? '',
            ],
            'opening_balance' => $openingBalance,
            'entries' => $ledgerEntries,
            'closing_balance' => $runningBalance,
            'cost_center_id' => $costCenterId,
        ];
    }

    /**
     * Get a summary of debits/credits per cost center for a date range.
     * Used on the cost center dashboard/summary view.
     *
     * @return array
     */
    public function getCostCenterSummary(int $companyId, string $fromDate, string $toDate): array
    {
        $entityId = $this->getEntityId($companyId);
        if (! $entityId) {
            return ['centers' => [], 'grand_total' => $this->emptySummaryTotals()];
        }

        $rows = DB::select("
            SELECT
                l.cost_center_id,
                cc.name as cost_center_name,
                cc.code as cost_center_code,
                cc.color as cost_center_color,
                COALESCE(SUM(CASE WHEN l.entry_type = 'D' THEN l.amount / l.rate ELSE 0 END), 0) as total_debit,
                COALESCE(SUM(CASE WHEN l.entry_type = 'C' THEN l.amount / l.rate ELSE 0 END), 0) as total_credit
            FROM ifrs_ledgers l
            LEFT JOIN cost_centers cc ON cc.id = l.cost_center_id
            WHERE l.entity_id = ?
              AND l.posting_date >= ?
              AND l.posting_date <= ?
              AND l.deleted_at IS NULL
              AND l.cost_center_id IS NOT NULL
            GROUP BY l.cost_center_id, cc.name, cc.code, cc.color
            ORDER BY total_debit DESC
        ", [$entityId, $fromDate, $toDate]);

        $centers = [];
        $grandDebit = 0;
        $grandCredit = 0;

        foreach ($rows as $row) {
            $debit = round($row->total_debit, 2);
            $credit = round($row->total_credit, 2);
            $net = round($debit - $credit, 2);

            $centers[] = [
                'cost_center_id' => $row->cost_center_id,
                'name' => $row->cost_center_name ?? 'Unknown',
                'code' => $row->cost_center_code,
                'color' => $row->cost_center_color ?? '#6366f1',
                'total_debit' => $debit,
                'total_credit' => $credit,
                'net' => $net,
            ];

            $grandDebit += $debit;
            $grandCredit += $credit;
        }

        // Calculate percentage of total for each center
        $grandTotal = $grandDebit + $grandCredit;
        foreach ($centers as &$center) {
            $centerTotal = $center['total_debit'] + $center['total_credit'];
            $center['percentage'] = $grandTotal > 0
                ? round(($centerTotal / $grandTotal) * 100, 1)
                : 0;
        }
        unset($center);

        // Also get "Unassigned" totals (NULL cost_center_id)
        $unassigned = DB::selectOne("
            SELECT
                COALESCE(SUM(CASE WHEN entry_type = 'D' THEN amount / rate ELSE 0 END), 0) as total_debit,
                COALESCE(SUM(CASE WHEN entry_type = 'C' THEN amount / rate ELSE 0 END), 0) as total_credit
            FROM ifrs_ledgers
            WHERE entity_id = ?
              AND posting_date >= ?
              AND posting_date <= ?
              AND deleted_at IS NULL
              AND cost_center_id IS NULL
        ", [$entityId, $fromDate, $toDate]);

        return [
            'centers' => $centers,
            'unassigned' => [
                'total_debit' => round($unassigned->total_debit ?? 0, 2),
                'total_credit' => round($unassigned->total_credit ?? 0, 2),
                'net' => round(($unassigned->total_debit ?? 0) - ($unassigned->total_credit ?? 0), 2),
            ],
            'grand_total' => [
                'total_debit' => round($grandDebit, 2),
                'total_credit' => round($grandCredit, 2),
                'net' => round($grandDebit - $grandCredit, 2),
            ],
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ];
    }

    /**
     * Get Profit & Loss for a specific cost center.
     *
     * Groups revenue and expense accounts, filtered by cost_center_id.
     *
     * @return array
     */
    public function getCostCenterProfitLoss(int $companyId, string $fromDate, string $toDate, int $costCenterId): array
    {
        $entityId = $this->getEntityId($companyId);
        if (! $entityId) {
            return ['error' => 'IFRS Entity not available'];
        }

        // Revenue account types (credits are positive revenue)
        $revenueTypes = ['OPERATING_REVENUE', 'NON_OPERATING_REVENUE'];
        // Expense account types (debits are positive expenses)
        $expenseTypes = ['OPERATING_EXPENSE', 'DIRECT_EXPENSE', 'OVERHEAD_EXPENSE', 'OTHER_EXPENSE', 'NON_OPERATING_EXPENSE'];

        $rows = DB::select("
            SELECT
                a.id,
                a.code,
                a.name,
                a.account_type,
                COALESCE(SUM(CASE WHEN l.entry_type = 'D' THEN l.amount / l.rate ELSE 0 END), 0) as total_debit,
                COALESCE(SUM(CASE WHEN l.entry_type = 'C' THEN l.amount / l.rate ELSE 0 END), 0) as total_credit
            FROM ifrs_accounts a
            INNER JOIN ifrs_ledgers l
                ON a.id = l.post_account
                AND l.entity_id = a.entity_id
                AND l.deleted_at IS NULL
                AND l.cost_center_id = ?
                AND l.posting_date >= ?
                AND l.posting_date <= ?
            WHERE a.entity_id = ? AND a.deleted_at IS NULL
            GROUP BY a.id, a.code, a.name, a.account_type
            HAVING (total_debit <> 0 OR total_credit <> 0)
            ORDER BY a.code
        ", [$costCenterId, $fromDate, $toDate, $entityId]);

        $revenues = [];
        $expenses = [];
        $totalRevenue = 0;
        $totalExpenses = 0;

        foreach ($rows as $row) {
            $debit = round($row->total_debit, 2);
            $credit = round($row->total_credit, 2);

            if (in_array($row->account_type, $revenueTypes)) {
                // Revenue: credit - debit (credits increase revenue)
                $balance = round($credit - $debit, 2);
                $revenues[] = [
                    'code' => $row->code,
                    'name' => $row->name,
                    'account_type' => $row->account_type,
                    'balance' => $balance,
                ];
                $totalRevenue += $balance;
            } elseif (in_array($row->account_type, $expenseTypes)) {
                // Expenses: debit - credit (debits increase expenses)
                $balance = round($debit - $credit, 2);
                $expenses[] = [
                    'code' => $row->code,
                    'name' => $row->name,
                    'account_type' => $row->account_type,
                    'balance' => $balance,
                ];
                $totalExpenses += $balance;
            }
        }

        $netProfit = round($totalRevenue - $totalExpenses, 2);

        return [
            'revenues' => $revenues,
            'expenses' => $expenses,
            'total_revenue' => round($totalRevenue, 2),
            'total_expenses' => round($totalExpenses, 2),
            'net_profit' => $netProfit,
            'cost_center_id' => $costCenterId,
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ];
    }

    /**
     * Bulk-tag existing ledger entries by assigning cost center to all
     * ledger rows belonging to a specific document's IFRS transaction.
     */
    public function bulkTagByTransaction(array $transactionCostCenterMap): int
    {
        if (! Schema::hasColumn('ifrs_ledgers', 'cost_center_id')) {
            return 0;
        }

        $totalAffected = 0;

        DB::transaction(function () use ($transactionCostCenterMap, &$totalAffected) {
            foreach ($transactionCostCenterMap as $transactionId => $costCenterId) {
                $affected = DB::table('ifrs_ledgers')
                    ->where('transaction_id', $transactionId)
                    ->whereNull('deleted_at')
                    ->update(['cost_center_id' => $costCenterId]);

                $totalAffected += $affected;
            }
        });

        Log::info('Bulk-tagged ledger entries with cost centers', [
            'transactions_count' => count($transactionCostCenterMap),
            'rows_affected' => $totalAffected,
        ]);

        return $totalAffected;
    }

    /**
     * Get entity_id for a company from ifrs_entities.
     */
    protected function getEntityId(int $companyId): ?int
    {
        $entityId = DB::table('companies')
            ->where('id', $companyId)
            ->value('ifrs_entity_id');

        if (! $entityId) {
            // Fallback: entity_id might match company_id
            $exists = DB::table('ifrs_entities')->where('id', $companyId)->exists();

            return $exists ? $companyId : null;
        }

        return (int) $entityId;
    }

    /**
     * Return empty 6-column trial balance totals structure.
     */
    protected function emptyTotals(): array
    {
        return [
            'opening_debit' => 0,
            'opening_credit' => 0,
            'period_debit' => 0,
            'period_credit' => 0,
            'closing_debit' => 0,
            'closing_credit' => 0,
        ];
    }

    /**
     * Return empty summary totals.
     */
    protected function emptySummaryTotals(): array
    {
        return [
            'total_debit' => 0,
            'total_credit' => 0,
            'net' => 0,
        ];
    }
}

// CLAUDE-CHECKPOINT
