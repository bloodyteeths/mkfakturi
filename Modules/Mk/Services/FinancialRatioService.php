<?php

namespace Modules\Mk\Services;

use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Mk\Models\FinancialRatioCache;

class FinancialRatioService
{
    /** @var array<int, int|null> Cache of company_id => entity_id lookups */
    private array $entityIdCache = [];

    /**
     * Get the IFRS entity_id for a company. Cached per request.
     */
    private function getEntityId(int $companyId): ?int
    {
        if (!array_key_exists($companyId, $this->entityIdCache)) {
            $this->entityIdCache[$companyId] = Company::where('id', $companyId)->value('ifrs_entity_id');
        }

        return $this->entityIdCache[$companyId];
    }

    /**
     * Check if a company has IFRS accounting initialized.
     */
    public function isInitialized(int $companyId): bool
    {
        return $this->getEntityId($companyId) !== null;
    }

    /**
     * Compute all ratios for a given date.
     *
     * @return array All ratio groups combined
     */
    public function computeAllRatios(int $companyId, string $date): array
    {
        $liquidity = $this->getLiquidityRatios($companyId, $date);
        $profitability = $this->getProfitabilityRatios($companyId, $date);
        $solvency = $this->getSolvencyRatios($companyId, $date);
        $activity = $this->getActivityRatios($companyId, $date);
        $altmanZ = $this->getAltmanZScore($companyId, $date);
        $rawAmounts = $this->getRawAmounts($companyId, $date);
        $ebitda = $this->getEbitda($companyId, $date);
        $workingCapital = $this->getWorkingCapital($companyId, $date);

        return [
            'date' => $date,
            'liquidity' => $liquidity,
            'profitability' => $profitability,
            'solvency' => $solvency,
            'activity' => $activity,
            'altman_z' => $altmanZ,
            'raw' => [
                'revenue' => $rawAmounts['revenue'],
                'cash' => $rawAmounts['cash'],
                'ebitda' => $ebitda['ebitda'],
                'ebitda_margin' => $ebitda['ebitda_margin'],
                'working_capital' => $workingCapital['working_capital'],
            ],
        ];
    }

    /**
     * Compute ratios for current period and prior year for comparison.
     */
    public function computeComparativeRatios(int $companyId, string $currentDate): array
    {
        $current = $this->computeAllRatios($companyId, $currentDate);

        // Prior period: same period last year
        $priorDate = Carbon::parse($currentDate)->subYear()->endOfMonth()->toDateString();
        $prior = $this->computeAllRatios($companyId, $priorDate);

        return [
            'current' => $current,
            'prior' => $prior,
            'prior_date' => $priorDate,
        ];
    }

    /**
     * Get raw monetary amounts for dashboard cards.
     */
    public function getRawAmounts(int $companyId, string $date): array
    {
        $revenue = $this->sumByAccountTypes($companyId, $date, ['OPERATING_REVENUE'], true);
        $cash = $this->sumByAccountTypes($companyId, $date, ['BANK']);

        return [
            'revenue' => round($revenue, 2),
            'cash' => round($cash, 2),
        ];
    }

    /**
     * Get EBITDA (Earnings Before Interest, Taxes, Depreciation & Amortization).
     *
     * Note: We use EBIT as the proxy for EBITDA because IFRS account types
     * do not reliably separate depreciation/amortization from other operating
     * expenses. For MK SMEs this is an acceptable approximation.
     *
     * @return array{ebitda: float, ebitda_margin: float}
     */
    public function getEbitda(int $companyId, string $date): array
    {
        $revenue = $this->sumByAccountTypes($companyId, $date, ['OPERATING_REVENUE'], true);
        $operatingExpense = $this->sumByAccountTypes($companyId, $date, ['OPERATING_EXPENSE']);

        // EBITDA ≈ EBIT (D&A not separately identifiable from IFRS account types)
        $ebitda = $revenue - $operatingExpense;

        $ebitdaMargin = $revenue != 0
            ? round($ebitda / $revenue, 4)
            : 0;

        return [
            'ebitda' => round($ebitda, 2),
            'ebitda_margin' => $ebitdaMargin,
        ];
    }

    /**
     * Get Working Capital metrics.
     *
     * Working Capital = Current Assets - Current Liabilities
     * Working Capital Ratio = Current Assets / Current Liabilities (same as current_ratio)
     *
     * @return array{working_capital: float, working_capital_ratio: float}
     */
    public function getWorkingCapital(int $companyId, string $date): array
    {
        $currentAssets = $this->sumByAccountTypes($companyId, $date, [
            'BANK', 'RECEIVABLE', 'INVENTORY', 'CURRENT_ASSET',
        ]);

        $currentLiabilities = $this->sumByAccountTypes($companyId, $date, [
            'PAYABLE', 'CURRENT_LIABILITY',
        ]);

        $workingCapital = $currentAssets - abs($currentLiabilities);

        $workingCapitalRatio = $currentLiabilities != 0
            ? round($currentAssets / abs($currentLiabilities), 4)
            : 0;

        return [
            'working_capital' => round($workingCapital, 2),
            'working_capital_ratio' => $workingCapitalRatio,
        ];
    }

    /**
     * Get liquidity ratios: current_ratio, quick_ratio, cash_ratio.
     */
    public function getLiquidityRatios(int $companyId, string $date): array
    {
        $currentAssets = $this->sumByAccountTypes($companyId, $date, [
            'BANK', 'RECEIVABLE', 'INVENTORY', 'CURRENT_ASSET',
        ]);

        $currentLiabilities = $this->sumByAccountTypes($companyId, $date, [
            'PAYABLE', 'CURRENT_LIABILITY',
        ]);

        $inventory = $this->sumByAccountTypes($companyId, $date, ['INVENTORY']);
        $cash = $this->sumByAccountTypes($companyId, $date, ['BANK']);

        $currentRatio = $currentLiabilities != 0
            ? round($currentAssets / abs($currentLiabilities), 4)
            : 0;

        $quickRatio = $currentLiabilities != 0
            ? round(($currentAssets - $inventory) / abs($currentLiabilities), 4)
            : 0;

        $cashRatio = $currentLiabilities != 0
            ? round($cash / abs($currentLiabilities), 4)
            : 0;

        return [
            'current_ratio' => $currentRatio,
            'quick_ratio' => $quickRatio,
            'cash_ratio' => $cashRatio,
        ];
    }

    /**
     * Get profitability ratios: gross_margin, net_margin, roe, roa.
     */
    public function getProfitabilityRatios(int $companyId, string $date): array
    {
        $revenue = $this->sumByAccountTypes($companyId, $date, [
            'OPERATING_REVENUE',
        ], true);

        $cogs = $this->sumByAccountTypes($companyId, $date, [
            'OPERATING_EXPENSE',
        ]);

        $nonOpRevenue = $this->sumByAccountTypes($companyId, $date, [
            'NON_OPERATING_REVENUE',
        ], true);

        $nonOpExpense = $this->sumByAccountTypes($companyId, $date, [
            'NON_OPERATING_EXPENSE',
        ]);

        $netIncome = $revenue + $nonOpRevenue - $cogs - $nonOpExpense;

        $equity = $this->sumByAccountTypes($companyId, $date, ['EQUITY'], true);

        $totalAssets = $this->sumByAccountTypes($companyId, $date, [
            'BANK', 'RECEIVABLE', 'INVENTORY', 'CURRENT_ASSET',
            'NON_CURRENT_ASSET',
        ]);

        $grossMargin = $revenue != 0
            ? round(($revenue - $cogs) / $revenue, 4)
            : 0;

        $netMargin = $revenue != 0
            ? round($netIncome / $revenue, 4)
            : 0;

        $roe = $equity != 0
            ? round($netIncome / abs($equity), 4)
            : 0;

        $roa = $totalAssets != 0
            ? round($netIncome / $totalAssets, 4)
            : 0;

        return [
            'gross_margin' => $grossMargin,
            'net_margin' => $netMargin,
            'roe' => $roe,
            'roa' => $roa,
        ];
    }

    /**
     * Get solvency ratios: debt_to_equity, interest_coverage.
     */
    public function getSolvencyRatios(int $companyId, string $date): array
    {
        $totalLiabilities = $this->sumByAccountTypes($companyId, $date, [
            'PAYABLE', 'CURRENT_LIABILITY', 'NON_CURRENT_LIABILITY',
        ], true);

        $equity = $this->sumByAccountTypes($companyId, $date, ['EQUITY'], true);

        $revenue = $this->sumByAccountTypes($companyId, $date, [
            'OPERATING_REVENUE',
        ], true);

        $operatingExpense = $this->sumByAccountTypes($companyId, $date, [
            'OPERATING_EXPENSE',
        ]);

        $ebit = $revenue - $operatingExpense;

        // Interest expense approximation: MK SMEs rarely have a dedicated interest
        // expense account type in IFRS. We use the full NON_OPERATING_EXPENSE as a
        // proxy, which may include foreign exchange losses, fines, etc. This overstates
        // interest expense and therefore understates the interest coverage ratio —
        // a conservative approximation acceptable for SME financial health monitoring.
        $interestExpense = $this->sumByAccountTypes($companyId, $date, [
            'NON_OPERATING_EXPENSE',
        ]);

        $debtToEquity = $equity != 0
            ? round(abs($totalLiabilities) / abs($equity), 4)
            : 0;

        $interestCoverage = $interestExpense != 0
            ? round($ebit / $interestExpense, 4)
            : 0;

        return [
            'debt_to_equity' => $debtToEquity,
            'interest_coverage' => $interestCoverage,
        ];
    }

    /**
     * Get activity ratios: receivable_days, payable_days, inventory_turnover.
     *
     * Uses proper average balances (beginning + ending / 2) and actual days
     * in the period rather than a hardcoded 365.
     */
    public function getActivityRatios(int $companyId, string $date): array
    {
        $revenue = $this->sumByAccountTypes($companyId, $date, [
            'OPERATING_REVENUE',
        ], true);

        $cogs = $this->sumByAccountTypes($companyId, $date, [
            'OPERATING_EXPENSE',
        ]);

        // Use average balances: (beginning of year + end of period) / 2
        $avgReceivables = $this->getAverageBalance($companyId, $date, ['RECEIVABLE']);
        $avgPayables = $this->getAverageBalance($companyId, $date, ['PAYABLE'], true);
        $avgInventory = $this->getAverageBalance($companyId, $date, ['INVENTORY']);

        // Use actual days in the period (Jan 1 to $date) instead of hardcoded 365
        $startOfYear = Carbon::parse($date)->startOfYear();
        $endDate = Carbon::parse($date);
        $daysInPeriod = $startOfYear->diffInDays($endDate) + 1;

        $dailyRevenue = $daysInPeriod > 0 ? $revenue / $daysInPeriod : 0;
        $dailyCogs = $daysInPeriod > 0 ? $cogs / $daysInPeriod : 0;

        $receivableDays = $dailyRevenue != 0
            ? round($avgReceivables / $dailyRevenue, 4)
            : 0;

        $payableDays = $dailyCogs != 0
            ? round(abs($avgPayables) / $dailyCogs, 4)
            : 0;

        $inventoryTurnover = $avgInventory != 0
            ? round($cogs / $avgInventory, 4)
            : 0;

        return [
            'receivable_days' => $receivableDays,
            'payable_days' => $payableDays,
            'inventory_turnover' => $inventoryTurnover,
        ];
    }

    /**
     * Compute the average balance for given account types over the fiscal period.
     *
     * Average = (balance at start of fiscal year + balance at end of period) / 2
     * The fiscal year for MK companies starts on Jan 1.
     *
     * @param  array  $accountTypes  IFRS account type codes
     * @param  bool  $creditPositive  Whether credit balances are positive (for liabilities/revenue)
     */
    protected function getAverageBalance(
        int $companyId,
        string $date,
        array $accountTypes,
        bool $creditPositive = false
    ): float {
        $startOfYear = Carbon::parse($date)->startOfYear()->subDay()->toDateString();

        // Balance at end of previous year (= beginning of current fiscal year)
        $beginningBalance = $this->sumByAccountTypes($companyId, $startOfYear, $accountTypes, $creditPositive);

        // Balance at end of current period
        $endingBalance = $this->sumByAccountTypes($companyId, $date, $accountTypes, $creditPositive);

        return ($beginningBalance + $endingBalance) / 2;
    }

    /**
     * Get Altman Z-Score.
     *
     * Z = 1.2*A + 1.4*B + 3.3*C + 0.6*D + 1.0*E
     * A = working_capital / total_assets
     * B = retained_earnings / total_assets
     * C = EBIT / total_assets
     * D = equity_market / total_liabilities (use book equity as proxy)
     * E = revenue / total_assets
     */
    public function getAltmanZScore(int $companyId, string $date): array
    {
        $currentAssets = $this->sumByAccountTypes($companyId, $date, [
            'BANK', 'RECEIVABLE', 'INVENTORY', 'CURRENT_ASSET',
        ]);

        $currentLiabilities = $this->sumByAccountTypes($companyId, $date, [
            'PAYABLE', 'CURRENT_LIABILITY',
        ], true);

        $totalAssets = $this->sumByAccountTypes($companyId, $date, [
            'BANK', 'RECEIVABLE', 'INVENTORY', 'CURRENT_ASSET',
            'NON_CURRENT_ASSET',
        ]);

        $totalLiabilities = $this->sumByAccountTypes($companyId, $date, [
            'PAYABLE', 'CURRENT_LIABILITY', 'NON_CURRENT_LIABILITY',
        ], true);

        $equity = $this->sumByAccountTypes($companyId, $date, ['EQUITY'], true);

        $revenue = $this->sumByAccountTypes($companyId, $date, [
            'OPERATING_REVENUE',
        ], true);

        $operatingExpense = $this->sumByAccountTypes($companyId, $date, [
            'OPERATING_EXPENSE',
        ]);

        $workingCapital = $currentAssets - abs($currentLiabilities);
        $retainedEarnings = $equity; // Proxy: use total equity
        $ebit = $revenue - $operatingExpense;

        $a = $totalAssets != 0 ? $workingCapital / $totalAssets : 0;
        $b = $totalAssets != 0 ? $retainedEarnings / $totalAssets : 0;
        $c = $totalAssets != 0 ? $ebit / $totalAssets : 0;
        $d = abs($totalLiabilities) != 0 ? abs($equity) / abs($totalLiabilities) : 0;
        $e = $totalAssets != 0 ? $revenue / $totalAssets : 0;

        $zScore = round(1.2 * $a + 1.4 * $b + 3.3 * $c + 0.6 * $d + 1.0 * $e, 4);

        // Determine zone
        if ($zScore > 2.99) {
            $zone = 'safe';
        } elseif ($zScore >= 1.81) {
            $zone = 'caution';
        } else {
            $zone = 'danger';
        }

        return [
            'z_score' => $zScore,
            'zone' => $zone,
            'components' => [
                'A' => round($a, 4),
                'B' => round($b, 4),
                'C' => round($c, 4),
                'D' => round($d, 4),
                'E' => round($e, 4),
            ],
        ];
    }

    /**
     * Get monthly trend data for a specific ratio type.
     *
     * @param  string  $ratioType  One of: current_ratio, quick_ratio, cash_ratio, gross_margin, net_margin, roe, roa, debt_to_equity, interest_coverage, receivable_days, payable_days, inventory_turnover, altman_z
     * @param  int  $months  Number of months to look back
     * @return array Array of monthly ratio values
     */
    public function getTrends(int $companyId, string $ratioType, int $months = 12): array
    {
        // Build cache lookup keyed by YYYY-MM
        $cached = FinancialRatioCache::forCompany($companyId)
            ->ofType($ratioType)
            ->where('period_date', '>=', Carbon::now()->subMonths($months)->startOfMonth()->toDateString())
            ->orderBy('period_date', 'asc')
            ->get()
            ->keyBy(fn ($row) => Carbon::parse($row->period_date)->format('Y-m'));

        // Compute for each month, using cache when available
        $trends = [];
        $now = Carbon::now();

        for ($i = $months - 1; $i >= 0; $i--) {
            $monthEnd = $now->copy()->subMonths($i)->endOfMonth();
            $monthKey = $monthEnd->format('Y-m');

            if ($cached->has($monthKey)) {
                $row = $cached->get($monthKey);
                $trends[] = [
                    'date' => $row->period_date->toDateString(),
                    'value' => (float) $row->ratio_value,
                    'metadata' => $row->metadata,
                ];
            } else {
                $value = $this->getSingleRatioValue($companyId, $monthEnd->toDateString(), $ratioType);
                $trends[] = [
                    'date' => $monthEnd->toDateString(),
                    'value' => $value,
                    'metadata' => null,
                ];
            }
        }

        return $trends;
    }

    /**
     * Cache all ratios for a given date.
     */
    public function cacheRatios(int $companyId, string $date): void
    {
        $allRatios = $this->computeAllRatios($companyId, $date);
        $now = Carbon::now();

        $periodDate = Carbon::parse($date)->endOfMonth()->toDateString();

        // Flatten ratio groups
        $flatRatios = [];

        foreach (['liquidity', 'profitability', 'solvency', 'activity'] as $group) {
            if (isset($allRatios[$group]) && is_array($allRatios[$group])) {
                foreach ($allRatios[$group] as $key => $value) {
                    $flatRatios[$key] = $value;
                }
            }
        }

        // Add Altman Z-Score
        if (isset($allRatios['altman_z']['z_score'])) {
            $flatRatios['altman_z'] = $allRatios['altman_z']['z_score'];
        }

        foreach ($flatRatios as $ratioType => $ratioValue) {
            FinancialRatioCache::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'period_date' => $periodDate,
                    'ratio_type' => $ratioType,
                ],
                [
                    'ratio_value' => $ratioValue,
                    'metadata' => null,
                    'calculated_at' => $now,
                ]
            );
        }

        Log::info('BI ratios cached', [
            'company_id' => $companyId,
            'period_date' => $periodDate,
            'ratio_count' => count($flatRatios),
        ]);
    }

    /**
     * Get the last time ratios were cached for a company.
     */
    public function getLastCachedAt(int $companyId): ?string
    {
        $latest = FinancialRatioCache::forCompany($companyId)
            ->orderByDesc('calculated_at')
            ->value('calculated_at');

        return $latest ? Carbon::parse($latest)->toIso8601String() : null;
    }

    /**
     * Get a single ratio value by type.
     */
    protected function getSingleRatioValue(int $companyId, string $date, string $ratioType): float
    {
        // Map ratio type to its group method
        $liquidityRatios = ['current_ratio', 'quick_ratio', 'cash_ratio'];
        $profitabilityRatios = ['gross_margin', 'net_margin', 'roe', 'roa'];
        $solvencyRatios = ['debt_to_equity', 'interest_coverage'];
        $activityRatios = ['receivable_days', 'payable_days', 'inventory_turnover'];

        if (in_array($ratioType, $liquidityRatios)) {
            $ratios = $this->getLiquidityRatios($companyId, $date);

            return (float) ($ratios[$ratioType] ?? 0);
        }

        if (in_array($ratioType, $profitabilityRatios)) {
            $ratios = $this->getProfitabilityRatios($companyId, $date);

            return (float) ($ratios[$ratioType] ?? 0);
        }

        if (in_array($ratioType, $solvencyRatios)) {
            $ratios = $this->getSolvencyRatios($companyId, $date);

            return (float) ($ratios[$ratioType] ?? 0);
        }

        if (in_array($ratioType, $activityRatios)) {
            $ratios = $this->getActivityRatios($companyId, $date);

            return (float) ($ratios[$ratioType] ?? 0);
        }

        if ($ratioType === 'altman_z') {
            $result = $this->getAltmanZScore($companyId, $date);

            return (float) ($result['z_score'] ?? 0);
        }

        return 0;
    }

    /**
     * Sum IFRS ledger amounts by account types.
     *
     * For asset/expense account types: debit amounts are positive.
     * For liability/equity/revenue account types when $creditPositive = true:
     *   credit amounts are returned as positive.
     *
     * @param  array  $accountTypes  Array of IFRS account_type values
     * @param  bool  $creditPositive  If true, returns credit balance (revenue/liability/equity)
     */
    protected function sumByAccountTypes(int $companyId, string $date, array $accountTypes, bool $creditPositive = false): float
    {
        $entityId = $this->getEntityId($companyId);

        if ($entityId === null) {
            return 0;
        }

        $query = DB::table('ifrs_ledgers as l')
            ->join('ifrs_accounts as a', function ($join) {
                $join->on('l.post_account', '=', 'a.id')
                    ->on('l.entity_id', '=', 'a.entity_id');
            })
            ->where('a.entity_id', $entityId)
            ->whereIn('a.account_type', $accountTypes)
            ->where('l.posting_date', '<=', $date)
            ->whereNull('l.deleted_at')
            ->whereNull('a.deleted_at');

        if ($creditPositive) {
            // For revenue/liability/equity: credit is positive
            $amount = $query->sum(DB::raw("
                CASE WHEN l.entry_type = 'C' THEN l.amount / l.rate
                     WHEN l.entry_type = 'D' THEN -(l.amount / l.rate)
                     ELSE 0 END
            "));
        } else {
            // For asset/expense: debit is positive
            $amount = $query->sum(DB::raw("
                CASE WHEN l.entry_type = 'D' THEN l.amount / l.rate
                     WHEN l.entry_type = 'C' THEN -(l.amount / l.rate)
                     ELSE 0 END
            "));
        }

        return (float) $amount;
    }
}

// CLAUDE-CHECKPOINT
