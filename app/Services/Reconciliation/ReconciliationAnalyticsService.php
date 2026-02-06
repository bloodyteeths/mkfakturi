<?php

namespace App\Services\Reconciliation;

use App\Models\BankTransaction;
use App\Models\Reconciliation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * ReconciliationAnalyticsService
 *
 * Provides comprehensive analytics for bank reconciliation KPIs.
 * All queries are explicitly company-scoped using forCompany() to
 * ensure tenant isolation (P0-13).
 *
 * @see P0-10 Phase 0 Analytics Dashboard
 */
class ReconciliationAnalyticsService
{
    /**
     * Get comprehensive analytics for a company in a given period.
     *
     * @param  int  $companyId  The company to scope analytics to
     * @param  string|null  $fromDate  Start of period (Y-m-d), defaults to first day of current month
     * @param  string|null  $toDate  End of period (Y-m-d), defaults to today
     * @return array<string, mixed>
     */
    public function getAnalytics(int $companyId, ?string $fromDate = null, ?string $toDate = null): array
    {
        $from = $fromDate ? Carbon::parse($fromDate)->startOfDay() : Carbon::now()->startOfMonth();
        $to = $toDate ? Carbon::parse($toDate)->endOfDay() : Carbon::now()->endOfDay();

        $transactions = $this->getTransactionStats($companyId, $from, $to);
        $reconciliations = $this->getReconciliationStats($companyId, $from, $to);
        $amounts = $this->getAmountStats($companyId, $from, $to);
        $avgTimeToReconcile = $this->getAvgTimeToReconcile($companyId, $from, $to);
        $parseAccuracy = $this->getParseAccuracy($companyId, $from, $to);
        $matchByMethod = $this->getMatchByMethod($companyId, $from, $to);
        $dailyTrend = $this->getDailyTrend($companyId, $from, $to);

        $totalTransactions = $transactions['total'];
        $autoMatched = $reconciliations['auto_matched'];
        $manualMatched = $reconciliations['manual_matched'];
        $pending = $totalTransactions - ($autoMatched + $manualMatched);

        return [
            'period' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'total_transactions' => $totalTransactions,
            'auto_matched' => $autoMatched,
            'manual_matched' => $manualMatched,
            'auto_match_rate' => $totalTransactions > 0
                ? round($autoMatched / $totalTransactions, 4)
                : 0.0,
            'pending' => max(0, $pending),
            'avg_confidence' => $reconciliations['avg_confidence'],
            'total_amount_matched' => $amounts['matched'],
            'total_amount_pending' => $amounts['pending'],
            'avg_time_to_reconcile_seconds' => $avgTimeToReconcile,
            'parse_accuracy' => $parseAccuracy,
            'match_by_method' => $matchByMethod,
            'daily_trend' => $dailyTrend,
        ];
    }

    /**
     * Get transaction count statistics for the period.
     *
     * @param  int  $companyId
     * @param  Carbon  $from
     * @param  Carbon  $to
     * @return array{total: int}
     */
    protected function getTransactionStats(int $companyId, Carbon $from, Carbon $to): array
    {
        $total = BankTransaction::forCompany($companyId)
            ->whereBetween('transaction_date', [$from, $to])
            ->where('is_duplicate', false)
            ->count();

        return ['total' => $total];
    }

    /**
     * Get reconciliation breakdown statistics.
     *
     * Auto-matched: reconciliation with match_type='auto' and confidence >= 85
     * Manual-matched: reconciliation with match_type='manual'
     *
     * @param  int  $companyId
     * @param  Carbon  $from
     * @param  Carbon  $to
     * @return array{auto_matched: int, manual_matched: int, avg_confidence: float}
     */
    protected function getReconciliationStats(int $companyId, Carbon $from, Carbon $to): array
    {
        $baseQuery = Reconciliation::forCompany($companyId)
            ->whereHas('bankTransaction', function ($q) use ($from, $to) {
                $q->whereBetween('transaction_date', [$from, $to]);
            })
            ->where('status', Reconciliation::STATUS_MATCHED);

        $autoMatched = (clone $baseQuery)
            ->where('match_type', Reconciliation::MATCH_TYPE_AUTO)
            ->where('confidence', '>=', 85)
            ->count();

        $manualMatched = (clone $baseQuery)
            ->where('match_type', Reconciliation::MATCH_TYPE_MANUAL)
            ->count();

        // Also count auto matches with lower confidence that were confirmed
        // plus rule-based matches
        $ruleMatched = (clone $baseQuery)
            ->where('match_type', Reconciliation::MATCH_TYPE_RULE)
            ->count();

        // For transactions matched via BankTransaction directly (without Reconciliation record)
        $directAutoMatched = BankTransaction::forCompany($companyId)
            ->whereBetween('transaction_date', [$from, $to])
            ->where('is_duplicate', false)
            ->whereNotNull('matched_invoice_id')
            ->where('match_confidence', '>=', 85)
            ->whereDoesntHave('reconciliations')
            ->count();

        $directManualMatched = BankTransaction::forCompany($companyId)
            ->whereBetween('transaction_date', [$from, $to])
            ->where('is_duplicate', false)
            ->whereNotNull('matched_invoice_id')
            ->where(function ($q) {
                $q->where('match_confidence', '<', 85)
                    ->orWhereNull('match_confidence');
            })
            ->whereDoesntHave('reconciliations')
            ->count();

        $totalAutoMatched = $autoMatched + $directAutoMatched;
        $totalManualMatched = $manualMatched + $ruleMatched + $directManualMatched;

        // Average confidence across all matched items
        $avgConfidenceFromReconciliations = Reconciliation::forCompany($companyId)
            ->whereHas('bankTransaction', function ($q) use ($from, $to) {
                $q->whereBetween('transaction_date', [$from, $to]);
            })
            ->where('status', Reconciliation::STATUS_MATCHED)
            ->whereNotNull('confidence')
            ->avg('confidence');

        $avgConfidenceFromTransactions = BankTransaction::forCompany($companyId)
            ->whereBetween('transaction_date', [$from, $to])
            ->where('is_duplicate', false)
            ->whereNotNull('matched_invoice_id')
            ->whereNotNull('match_confidence')
            ->avg('match_confidence');

        // Combine averages (prefer reconciliation table, fall back to transaction table)
        $avgConfidence = $avgConfidenceFromReconciliations ?? $avgConfidenceFromTransactions ?? 0.0;

        return [
            'auto_matched' => $totalAutoMatched,
            'manual_matched' => $totalManualMatched,
            'avg_confidence' => round((float) $avgConfidence, 2),
        ];
    }

    /**
     * Get matched and pending amount totals.
     *
     * @param  int  $companyId
     * @param  Carbon  $from
     * @param  Carbon  $to
     * @return array{matched: float, pending: float}
     */
    protected function getAmountStats(int $companyId, Carbon $from, Carbon $to): array
    {
        $matchedAmount = BankTransaction::forCompany($companyId)
            ->whereBetween('transaction_date', [$from, $to])
            ->where('is_duplicate', false)
            ->whereNotNull('matched_invoice_id')
            ->sum('amount');

        $pendingAmount = BankTransaction::forCompany($companyId)
            ->whereBetween('transaction_date', [$from, $to])
            ->where('is_duplicate', false)
            ->whereNull('matched_invoice_id')
            ->sum('amount');

        return [
            'matched' => round((float) $matchedAmount, 2),
            'pending' => round((float) $pendingAmount, 2),
        ];
    }

    /**
     * Calculate average time from transaction import to reconciliation match.
     *
     * @param  int  $companyId
     * @param  Carbon  $from
     * @param  Carbon  $to
     * @return int Average seconds, 0 if no data
     */
    protected function getAvgTimeToReconcile(int $companyId, Carbon $from, Carbon $to): int
    {
        // From Reconciliation records
        $avgFromReconciliations = Reconciliation::forCompany($companyId)
            ->whereHas('bankTransaction', function ($q) use ($from, $to) {
                $q->whereBetween('transaction_date', [$from, $to]);
            })
            ->where('status', Reconciliation::STATUS_MATCHED)
            ->whereNotNull('matched_at')
            ->join('bank_transactions', 'reconciliations.bank_transaction_id', '=', 'bank_transactions.id')
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, bank_transactions.created_at, reconciliations.matched_at)) as avg_seconds')
            ->value('avg_seconds');

        if ($avgFromReconciliations) {
            return (int) round($avgFromReconciliations);
        }

        // Fallback: from BankTransaction matched_at vs created_at
        $avgFromTransactions = BankTransaction::forCompany($companyId)
            ->whereBetween('transaction_date', [$from, $to])
            ->where('is_duplicate', false)
            ->whereNotNull('matched_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, matched_at)) as avg_seconds')
            ->value('avg_seconds');

        return (int) round($avgFromTransactions ?? 0);
    }

    /**
     * Get parse accuracy per bank source.
     *
     * Parse accuracy is the ratio of successfully processed transactions
     * (not failed) to total transactions, grouped by source/bank.
     *
     * @param  int  $companyId
     * @param  Carbon  $from
     * @param  Carbon  $to
     * @return array<string, float>
     */
    protected function getParseAccuracy(int $companyId, Carbon $from, Carbon $to): array
    {
        $results = BankTransaction::forCompany($companyId)
            ->whereBetween('transaction_date', [$from, $to])
            ->where('is_duplicate', false)
            ->join('bank_accounts', 'bank_transactions.bank_account_id', '=', 'bank_accounts.id')
            ->select(
                'bank_accounts.bank_name',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN bank_transactions.processing_status != \'failed\' THEN 1 ELSE 0 END) as successful')
            )
            ->groupBy('bank_accounts.bank_name')
            ->get();

        $accuracy = [];
        foreach ($results as $row) {
            $bankKey = strtolower(str_replace(' ', '_', $row->bank_name ?? 'unknown'));
            $accuracy[$bankKey] = $row->total > 0
                ? round($row->successful / $row->total, 2)
                : 0.0;
        }

        return $accuracy;
    }

    /**
     * Get breakdown of match methods used.
     *
     * Categories: amount, reference, customer, rule
     * Derived from reconciliation match_details or match_type fields.
     *
     * @param  int  $companyId
     * @param  Carbon  $from
     * @param  Carbon  $to
     * @return array{amount: int, reference: int, customer: int, rule: int}
     */
    protected function getMatchByMethod(int $companyId, Carbon $from, Carbon $to): array
    {
        // From Reconciliation table match_details JSON or match_type
        $reconciliations = Reconciliation::forCompany($companyId)
            ->whereHas('bankTransaction', function ($q) use ($from, $to) {
                $q->whereBetween('transaction_date', [$from, $to]);
            })
            ->where('status', Reconciliation::STATUS_MATCHED)
            ->select('match_type', 'match_details')
            ->get();

        $methods = [
            'amount' => 0,
            'reference' => 0,
            'customer' => 0,
            'rule' => 0,
        ];

        foreach ($reconciliations as $rec) {
            $details = $rec->match_details ?? [];

            // Check match_details for specific method
            if (isset($details['method'])) {
                $method = strtolower($details['method']);
                if (isset($methods[$method])) {
                    $methods[$method]++;
                    continue;
                }
            }

            // Fall back to match_type
            if ($rec->match_type === Reconciliation::MATCH_TYPE_RULE) {
                $methods['rule']++;
            } elseif ($rec->match_type === Reconciliation::MATCH_TYPE_AUTO) {
                // Auto matches typically use amount + reference
                $methods['amount']++;
            } elseif ($rec->match_type === Reconciliation::MATCH_TYPE_MANUAL) {
                $methods['customer']++;
            }
        }

        // Also count direct matches from BankTransaction (without Reconciliation record)
        $directMatched = BankTransaction::forCompany($companyId)
            ->whereBetween('transaction_date', [$from, $to])
            ->where('is_duplicate', false)
            ->whereNotNull('matched_invoice_id')
            ->whereDoesntHave('reconciliations')
            ->count();

        // Direct matches are typically amount-based
        $methods['amount'] += $directMatched;

        return $methods;
    }

    /**
     * Get daily trend of matched vs unmatched transactions.
     *
     * Returns up to 30 days of data within the period.
     *
     * @param  int  $companyId
     * @param  Carbon  $from
     * @param  Carbon  $to
     * @return array<int, array{date: string, matched: int, unmatched: int}>
     */
    protected function getDailyTrend(int $companyId, Carbon $from, Carbon $to): array
    {
        // Limit to last 30 days within the range
        $trendFrom = $to->copy()->subDays(29)->max($from);

        $results = BankTransaction::forCompany($companyId)
            ->whereBetween('transaction_date', [$trendFrom, $to])
            ->where('is_duplicate', false)
            ->select(
                DB::raw('DATE(transaction_date) as date'),
                DB::raw('SUM(CASE WHEN matched_invoice_id IS NOT NULL THEN 1 ELSE 0 END) as matched'),
                DB::raw('SUM(CASE WHEN matched_invoice_id IS NULL THEN 1 ELSE 0 END) as unmatched')
            )
            ->groupBy(DB::raw('DATE(transaction_date)'))
            ->orderBy('date')
            ->get();

        // Fill in missing dates with zeros
        $trend = [];
        $current = $trendFrom->copy();
        $resultsByDate = $results->keyBy('date');

        while ($current->lte($to)) {
            $dateStr = $current->toDateString();
            $row = $resultsByDate->get($dateStr);

            $trend[] = [
                'date' => $dateStr,
                'matched' => $row ? (int) $row->matched : 0,
                'unmatched' => $row ? (int) $row->unmatched : 0,
            ];

            $current->addDay();
        }

        return $trend;
    }
}

// CLAUDE-CHECKPOINT
