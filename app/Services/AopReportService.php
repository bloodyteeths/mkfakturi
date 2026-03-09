<?php

namespace App\Services;

use App\Domain\Accounting\IfrsAdapter;
use App\Models\Company;
use App\Models\FiscalYear;
use Illuminate\Support\Facades\Log;

/**
 * AOP Report Service
 *
 * Maps IFRS account type balances to UJP AOP (Аналитичка Ознака на Позиција)
 * codes for Образец 36 (Balance Sheet) and Образец 37 (Income Statement).
 *
 * Uses the official AOP configs from config/ujp_forms/ (Образец 36 + 37)
 * to produce UJP-compliant financial reports with AOP-coded positions.
 */
class AopReportService
{
    protected IfrsAdapter $ifrsAdapter;

    public function __construct(IfrsAdapter $ifrsAdapter)
    {
        $this->ifrsAdapter = $ifrsAdapter;
    }

    /**
     * Get Образец 36 (Balance Sheet) with AOP codes.
     *
     * @return array{aktiva: array, pasiva: array, is_balanced: bool}
     */
    public function getBalanceSheetAop(Company $company, int $year): array
    {
        $endDate = "{$year}-12-31";
        $fallback = config('ujp_forms.obrazec_36.ifrs_to_aop_fallback', []);
        $codeToAop = config('ujp_forms.obrazec_36.account_code_to_aop', []);

        // Get IFRS type-level balances (for buildAopRows fallback when no AOP balances)
        $current = $this->ifrsAdapter->getBalanceSheet($company, $endDate);
        $currentBalances = $this->extractBalanceSheetByType($current);

        // Get per-account balances with type info for precise AOP distribution
        $currentAccounts = $this->extractAccountBalances($company, '2020-01-01', $endDate);

        // CRITICAL: Filter out P&L-typed accounts before BS distribution.
        // IFRS account codes can collide with MK chart codes (e.g., IFRS code '600'
        // is OPERATING_REVENUE, but MK code '600' means WIP inventory). Including
        // P&L accounts in the BS distribution corrupts asset/liability totals.
        // All P&L impact is handled exclusively by injectNetIncome() into equity.
        $currentBsAccounts = $this->filterBalanceSheetAccounts($currentAccounts);
        $currentAopBalances = $this->distributeToAopCodes($currentBsAccounts, $codeToAop, $fallback);

        // Inject accumulated P&L into equity AOPs (075/076 prior years, 077/078 current year)
        // Uses UNFILTERED accounts so all P&L accounts contribute to the equity injection.
        $this->injectNetIncome($currentAopBalances, $company, $year, $currentAccounts);

        // Get previous year data
        $previousBalances = $this->getPreviousYearBalanceSheet($company, $year - 1);
        $prevAccounts = $this->extractAccountBalances($company, '2020-01-01', ($year - 1) . '-12-31');
        $prevBsAccounts = $this->filterBalanceSheetAccounts($prevAccounts);
        $prevAopBalances = $this->distributeToAopCodes($prevBsAccounts, $codeToAop, $fallback);
        $this->injectNetIncome($prevAopBalances, $company, $year - 1, $prevAccounts);

        // Build AOP rows for АКТИВА (official 112-row config)
        $aktivaConfig = config('ujp_forms.obrazec_36.aktiva', []);
        $aktiva = $this->buildAopRows($aktivaConfig, $currentBalances, $previousBalances, $fallback, $currentAopBalances, $prevAopBalances);

        // Build AOP rows for ПАСИВА
        $pasivaConfig = config('ujp_forms.obrazec_36.pasiva', []);
        $pasiva = $this->buildAopRows($pasivaConfig, $currentBalances, $previousBalances, $fallback, $currentAopBalances, $prevAopBalances);

        // Check balance (063 = ВКУПНА АКТИВА, 111 = ВКУПНА ПАСИВА)
        $totalAktiva = $this->findAopValue($aktiva, '063');
        $totalPasiva = $this->findAopValue($pasiva, '111');

        return [
            'aktiva' => $aktiva,
            'pasiva' => $pasiva,
            'is_balanced' => abs($totalAktiva - $totalPasiva) < 0.01,
            'total_aktiva' => $totalAktiva,
            'total_pasiva' => $totalPasiva,
        ];
    }

    /**
     * Get Образец 37 (Income Statement) with AOP codes.
     *
     * @return array{prihodi: array, rashodi: array, rezultat: array}
     */
    public function getIncomeStatementAop(Company $company, int $year): array
    {
        $startDate = "{$year}-01-01";
        $endDate = "{$year}-12-31";
        $fallback = config('ujp_forms.obrazec_37.ifrs_to_aop_fallback', []);
        $codeToAop = config('ujp_forms.obrazec_37.account_code_to_aop', []);

        // Get IFRS type-level balances (for buildAopRows fallback when no AOP balances)
        $current = $this->ifrsAdapter->getIncomeStatement($company, $startDate, $endDate);
        $currentBalances = $this->extractIncomeStatementByType($current);

        // Get per-account period balances with type info for precise AOP distribution
        $currentAccounts = $this->extractPeriodAccountBalances($company, $startDate, $endDate);
        $currentAopBalances = $this->distributeToAopCodes($currentAccounts, $codeToAop, $fallback);

        // Get previous year data
        $previousBalances = $this->getPreviousYearIncomeStatement($company, $year - 1);
        $prevAccounts = $this->extractPeriodAccountBalances(
            $company, ($year - 1) . '-01-01', ($year - 1) . '-12-31'
        );
        $prevAopBalances = $this->distributeToAopCodes($prevAccounts, $codeToAop, $fallback);

        // Build AOP rows for Приходи (official config)
        $prihodiConfig = config('ujp_forms.obrazec_37.prihodi', []);
        $prihodi = $this->buildAopRows($prihodiConfig, $currentBalances, $previousBalances, $fallback, $currentAopBalances, $prevAopBalances);

        // Build AOP rows for Расходи
        $rashodiConfig = config('ujp_forms.obrazec_37.rashodi', []);
        $rashodi = $this->buildAopRows($rashodiConfig, $currentBalances, $previousBalances, $fallback, $currentAopBalances, $prevAopBalances);

        // Build result rows (financial income/expenses + calculated profit/loss)
        $rezultatConfig = config('ujp_forms.obrazec_37.rezultat', []);
        $rezultat = $this->buildResultRows(
            $rezultatConfig, $currentBalances, $previousBalances,
            $fallback, $prihodi, $rashodi, $currentAopBalances, $prevAopBalances
        );

        return [
            'prihodi' => $prihodi,
            'rashodi' => $rashodi,
            'rezultat' => $rezultat,
        ];
    }

    /**
     * Extract IFRS account type balances from balance sheet data.
     * Returns: ['RECEIVABLE' => 59, 'EQUITY' => 221, ...]
     */
    public function extractBalanceSheetByType(array $bsData): array
    {
        $balances = [];

        if (isset($bsData['error'])) {
            return $balances;
        }

        // Assets (positive = debit balance)
        foreach ($bsData['balance_sheet']['assets'] ?? [] as $account) {
            $type = $account['code'] ?? '';
            if ($type) {
                $balances[$type] = ($balances[$type] ?? 0) + abs($account['balance'] ?? 0);
            }
        }

        // Liabilities (already abs'd by flattenIfrsAccounts with absBalance=true)
        foreach ($bsData['balance_sheet']['liabilities'] ?? [] as $account) {
            $type = $account['code'] ?? '';
            if ($type) {
                $balances[$type] = ($balances[$type] ?? 0) + abs($account['balance'] ?? 0);
            }
        }

        // Equity (already abs'd)
        foreach ($bsData['balance_sheet']['equity'] ?? [] as $account) {
            $type = $account['code'] ?? '';
            if ($type) {
                $balances[$type] = ($balances[$type] ?? 0) + abs($account['balance'] ?? 0);
            }
        }

        return $balances;
    }

    /**
     * Extract IFRS account type balances from income statement data.
     * All values returned as positive (absolute).
     */
    public function extractIncomeStatementByType(array $isData): array
    {
        $balances = [];

        if (isset($isData['error'])) {
            return $balances;
        }

        // Revenues (already abs'd by flattenIfrsAccounts)
        foreach ($isData['income_statement']['revenues'] ?? [] as $account) {
            $type = $account['code'] ?? '';
            if ($type) {
                $balances[$type] = ($balances[$type] ?? 0) + abs($account['balance'] ?? 0);
            }
        }

        // Expenses — use absolute values for UJP display
        foreach ($isData['income_statement']['expenses'] ?? [] as $account) {
            $type = $account['code'] ?? '';
            if ($type) {
                $balances[$type] = ($balances[$type] ?? 0) + abs($account['balance'] ?? 0);
            }
        }

        return $balances;
    }

    /**
     * Build AOP rows from config, mapping IFRS type balances to AOP positions.
     * Computes subtotals bottom-up.
     *
     * Supports signed sum_of entries: ['+066', '-068'] means add 066, subtract 068.
     * Unsigned entries default to addition for backward compatibility.
     */
    public function buildAopRows(
        array $config, array $currentBalances, array $previousBalances,
        ?array $fallbackOverride = null,
        array $currentAopBalances = [], array $previousAopBalances = []
    ): array {
        $fallback = $fallbackOverride ?? [];
        $hasAopBalances = ! empty($currentAopBalances) || ! empty($previousAopBalances);

        // First pass: fill leaf nodes from per-code AOP balances or IFRS type fallback
        $rows = [];
        foreach ($config as $rowConfig) {
            $aop = $rowConfig['aop'];
            $isTotal = $rowConfig['is_total'] ?? false;

            $current = 0;
            $previous = 0;

            if (! $isTotal && isset($rowConfig['ifrs_types'])) {
                if ($hasAopBalances) {
                    // Use pre-distributed per-account-code balances (more precise).
                    // Missing AOP = 0: all amounts are fully distributed, so type-level
                    // fallback would double-count amounts already mapped to other AOPs.
                    $current = $currentAopBalances[$aop] ?? 0;
                    $previous = $previousAopBalances[$aop] ?? 0;
                } else {
                    // Fallback: IFRS type-level balances (only when no per-account data)
                    foreach ($rowConfig['ifrs_types'] as $ifrsType) {
                        $fallbackAop = $fallback[$ifrsType] ?? null;
                        if ($fallbackAop === $aop) {
                            $current += $currentBalances[$ifrsType] ?? 0;
                            $previous += $previousBalances[$ifrsType] ?? 0;
                        }
                    }
                }
            }

            $rows[] = [
                'aop' => $aop,
                'label' => $rowConfig['label'],
                'current' => round($current, 2),
                'previous' => round($previous, 2),
                'is_total' => $isTotal,
                'sum_of' => $rowConfig['sum_of'] ?? null,
                'indent' => $rowConfig['indent'] ?? 0,
            ];
        }

        // Second pass: compute subtotals via multi-pass iteration.
        // Nested hierarchies (up to 4 levels: grand total → section → subsection → leaf)
        // require multiple passes since parent totals depend on child totals.
        $rowsByAop = [];
        foreach ($rows as &$row) {
            $rowsByAop[$row['aop']] = &$row;
        }
        unset($row);

        $totalRows = array_filter($rows, fn ($r) => $r['is_total'] && $r['sum_of']);
        $maxPasses = 10;

        for ($pass = 0; $pass < $maxPasses; $pass++) {
            $changed = false;
            foreach ($totalRows as $totalRow) {
                $currentSum = 0;
                $previousSum = 0;
                foreach ($totalRow['sum_of'] as $childRef) {
                    $sign = 1;
                    $childAop = (string) $childRef;
                    if (is_string($childRef) && str_starts_with($childRef, '-')) {
                        $sign = -1;
                        $childAop = substr($childRef, 1);
                    } elseif (is_string($childRef) && str_starts_with($childRef, '+')) {
                        $childAop = substr($childRef, 1);
                    }
                    if (isset($rowsByAop[$childAop])) {
                        $currentSum += $sign * $rowsByAop[$childAop]['current'];
                        $previousSum += $sign * $rowsByAop[$childAop]['previous'];
                    }
                }
                $newCurrent = round($currentSum, 2);
                $newPrevious = round($previousSum, 2);

                if ($rowsByAop[$totalRow['aop']]['current'] !== $newCurrent
                    || $rowsByAop[$totalRow['aop']]['previous'] !== $newPrevious) {
                    $rowsByAop[$totalRow['aop']]['current'] = $newCurrent;
                    $rowsByAop[$totalRow['aop']]['previous'] = $newPrevious;
                    $changed = true;
                }
            }
            if (! $changed) {
                break;
            }
        }

        // Clean up internal fields
        return array_map(function ($row) {
            unset($row['sum_of']);

            return $row;
        }, array_values($rows));
    }

    /**
     * Build result rows for Образец 37 (financial income/expenses + calculated profit/loss).
     *
     * Handles both ifrs_types rows (financial income AOP 223, expenses AOP 224)
     * and formula rows (operating_profit, tax, net_profit, etc.).
     */
    protected function buildResultRows(
        array $config, array $currentBalances, array $previousBalances,
        array $fallback, array $prihodi, array $rashodi,
        array $currentAopBalances = [], array $previousAopBalances = []
    ): array {
        // Get totals from prihodi/rashodi sections
        $totalRevenue = $this->findAopValue($prihodi, '201');
        $totalExpenses = $this->findAopValue($rashodi, '207');
        $prevRevenue = $this->findAopPrevValue($prihodi, '201');
        $prevExpenses = $this->findAopPrevValue($rashodi, '207');

        $hasAopBalances = ! empty($currentAopBalances) || ! empty($previousAopBalances);

        // First pass: extract leaf rows with ifrs_types (financial income/expenses)
        $leafBalances = [];
        foreach ($config as $rowConfig) {
            if (isset($rowConfig['ifrs_types']) && ! empty($rowConfig['ifrs_types'])) {
                $aop = $rowConfig['aop'];
                if ($hasAopBalances) {
                    $current = $currentAopBalances[$aop] ?? 0;
                    $previous = $previousAopBalances[$aop] ?? 0;
                } else {
                    $current = 0;
                    $previous = 0;
                    foreach ($rowConfig['ifrs_types'] as $ifrsType) {
                        $fallbackAop = $fallback[$ifrsType] ?? null;
                        if ($fallbackAop === $aop) {
                            $current += $currentBalances[$ifrsType] ?? 0;
                            $previous += $previousBalances[$ifrsType] ?? 0;
                        }
                    }
                }
                $leafBalances[$aop] = ['current' => $current, 'previous' => $previous];
            }
        }

        $financialIncome = $leafBalances['223']['current'] ?? 0;
        $financialExpenses = $leafBalances['224']['current'] ?? 0;
        $prevFinancialIncome = $leafBalances['223']['previous'] ?? 0;
        $prevFinancialExpenses = $leafBalances['224']['previous'] ?? 0;

        // Compute operating result (revenue + financial income - expenses - financial expenses)
        $operatingResult = ($totalRevenue + $financialIncome) - ($totalExpenses + $financialExpenses);
        $prevOperatingResult = ($prevRevenue + $prevFinancialIncome) - ($prevExpenses + $prevFinancialExpenses);

        $profitBeforeTax = $operatingResult;
        $prevProfitBeforeTax = $prevOperatingResult;

        $isProfit = $profitBeforeTax >= 0;
        $prevIsProfit = $prevProfitBeforeTax >= 0;

        $tax = $isProfit ? round($profitBeforeTax * 0.10, 2) : 0;
        $prevTax = $prevIsProfit ? round($prevProfitBeforeTax * 0.10, 2) : 0;

        $netResult = $profitBeforeTax - $tax;
        $prevNetResult = $prevProfitBeforeTax - $prevTax;

        // Build all rows
        $rows = [];
        foreach ($config as $rowConfig) {
            $aop = $rowConfig['aop'];
            $current = 0;
            $previous = 0;

            if (isset($rowConfig['formula'])) {
                switch ($rowConfig['formula']) {
                    case 'operating_profit':
                        $current = $operatingResult >= 0 ? abs($operatingResult) : 0;
                        $previous = $prevOperatingResult >= 0 ? abs($prevOperatingResult) : 0;
                        break;
                    case 'operating_loss':
                        $current = $operatingResult < 0 ? abs($operatingResult) : 0;
                        $previous = $prevOperatingResult < 0 ? abs($prevOperatingResult) : 0;
                        break;
                    case 'profit_before_tax':
                        $current = $profitBeforeTax >= 0 ? abs($profitBeforeTax) : 0;
                        $previous = $prevProfitBeforeTax >= 0 ? abs($prevProfitBeforeTax) : 0;
                        break;
                    case 'loss_before_tax':
                        $current = $profitBeforeTax < 0 ? abs($profitBeforeTax) : 0;
                        $previous = $prevProfitBeforeTax < 0 ? abs($prevProfitBeforeTax) : 0;
                        break;
                    case 'tax':
                        $current = $tax;
                        $previous = $prevTax;
                        break;
                    case 'net_profit':
                        $current = $netResult >= 0 ? abs($netResult) : 0;
                        $previous = $prevNetResult >= 0 ? abs($prevNetResult) : 0;
                        break;
                    case 'net_loss':
                        $current = $netResult < 0 ? abs($netResult) : 0;
                        $previous = $prevNetResult < 0 ? abs($prevNetResult) : 0;
                        break;
                    case 'net_oci':
                        $current = 0;
                        $previous = 0;
                        break;
                    case 'total_comprehensive':
                        $current = $netResult;
                        $previous = $prevNetResult;
                        break;
                    case 'total_revenue':
                        $current = $totalRevenue + $financialIncome;
                        $previous = $prevRevenue + $prevFinancialIncome;
                        break;
                    case 'total_expenses':
                        $current = $totalExpenses + $financialExpenses + $tax;
                        $previous = $prevExpenses + $prevFinancialExpenses + $prevTax;
                        break;
                }
            } elseif (isset($leafBalances[$aop])) {
                $current = $leafBalances[$aop]['current'];
                $previous = $leafBalances[$aop]['previous'];
            }

            $rows[] = [
                'aop' => $aop,
                'label' => $rowConfig['label'],
                'current' => round($current, 2),
                'previous' => round($previous, 2),
                'is_total' => false,
                'is_result' => isset($rowConfig['formula']),
                'indent' => $rowConfig['indent'] ?? 0,
            ];
        }

        return $rows;
    }

    /**
     * Extract per-account records with code, IFRS type, and closing balance.
     * Returns: [['code' => '100', 'type' => 'BANK', 'balance' => 49000.60], ...]
     * Balance = closing_debit - closing_credit (signed: positive=debit, negative=credit).
     * Used for balance sheet — closing balances represent cumulative state.
     */
    public function extractAccountBalances(Company $company, string $fromDate, string $toDate): array
    {
        try {
            $trialBalance = $this->ifrsAdapter->getTrialBalanceSixColumn($company, $fromDate, $toDate);
        } catch (\Exception $e) {
            return [];
        }

        if (isset($trialBalance['error']) || empty($trialBalance['accounts'])) {
            return [];
        }

        $accounts = [];
        foreach ($trialBalance['accounts'] as $account) {
            $accounts[] = [
                'code' => (string) ($account['code'] ?? ''),
                'type' => $account['account_type'] ?? '',
                'balance' => round(($account['closing_debit'] ?? 0) - ($account['closing_credit'] ?? 0), 2),
            ];
        }

        return $accounts;
    }

    /**
     * Extract per-account records with code, IFRS type, and period activity balance.
     * Returns: [['code' => '720', 'type' => 'OPERATING_REVENUE', 'balance' => 5000], ...]
     * Uses the larger of period_debit/period_credit as the absolute balance.
     * Used for income statement — period amounts represent activity during the period.
     */
    public function extractPeriodAccountBalances(Company $company, string $fromDate, string $toDate): array
    {
        try {
            $trialBalance = $this->ifrsAdapter->getTrialBalanceSixColumn($company, $fromDate, $toDate);
        } catch (\Exception $e) {
            return [];
        }

        if (isset($trialBalance['error']) || empty($trialBalance['accounts'])) {
            return [];
        }

        $accounts = [];
        foreach ($trialBalance['accounts'] as $account) {
            $periodDebit = $account['period_debit'] ?? 0;
            $periodCredit = $account['period_credit'] ?? 0;
            $accounts[] = [
                'code' => (string) ($account['code'] ?? ''),
                'type' => $account['account_type'] ?? '',
                'balance' => round(max($periodDebit, $periodCredit), 2),
            ];
        }

        return $accounts;
    }

    /**
     * Distribute per-account balances to AOP codes using code-first, type-fallback strategy.
     *
     * For each account:
     * 1. If account code found in $codeToAopMap → add balance to that AOP
     * 2. Else if IFRS type found in $typeToAopFallback → add balance to that AOP
     * 3. Else → skip (unmapped account)
     *
     * Sign convention: Trial balance stores balance as (closing_debit - closing_credit).
     * Credit-normal accounts (liabilities, equity) have negative balances.
     * We negate credit-normal types so they display as positive on the pasiva side.
     * Asset-side contra-balances (credit in a debit-normal account) remain negative,
     * correctly reducing the asset total via sum_of in buildAopRows.
     */
    public function distributeToAopCodes(
        array $accounts,
        array $codeToAopMap,
        array $typeToAopFallback
    ): array {
        // Credit-normal account types: negate so credits become positive for pasiva display
        $creditNormalTypes = [
            'PAYABLE', 'CURRENT_LIABILITY', 'NON_CURRENT_LIABILITY',
            'CONTROL', 'RECONCILIATION', 'EQUITY',
        ];

        $aopBalances = [];

        foreach ($accounts as $account) {
            $code = $account['code'] ?? '';
            $type = $account['type'] ?? '';
            $balance = $account['balance'] ?? 0;

            if ($balance == 0) {
                continue;
            }

            // Negate credit-normal types so credits display as positive on pasiva side
            if (in_array($type, $creditNormalTypes)) {
                $balance = -$balance;
            }

            if ($code !== '' && isset($codeToAopMap[$code])) {
                $aop = $codeToAopMap[$code];
                $aopBalances[$aop] = ($aopBalances[$aop] ?? 0) + $balance;
            } elseif ($type !== '' && isset($typeToAopFallback[$type])) {
                $aop = $typeToAopFallback[$type];
                $aopBalances[$aop] = ($aopBalances[$aop] ?? 0) + $balance;
            }
        }

        // Round values (preserve sign — contra-balances are naturally negative)
        foreach ($aopBalances as $aop => $val) {
            $aopBalances[$aop] = round($val, 2);
        }

        return $aopBalances;
    }

    /**
     * Filter accounts to only include balance sheet types (exclude P&L types).
     *
     * IFRS account codes can collide with MK chart codes (e.g., IFRS '600' = revenue,
     * MK '600' = WIP inventory). P&L accounts must not enter the balance sheet
     * distribution — they are handled by injectNetIncome() into equity.
     */
    protected function filterBalanceSheetAccounts(array $accounts): array
    {
        $plTypes = [
            'OPERATING_REVENUE', 'NON_OPERATING_REVENUE',
            'OPERATING_EXPENSE', 'DIRECT_EXPENSE', 'OVERHEAD_EXPENSE', 'OTHER_EXPENSE',
        ];

        return array_values(array_filter($accounts, function ($account) use ($plTypes) {
            return ! in_array($account['type'] ?? '', $plTypes);
        }));
    }

    /**
     * Get previous year balance sheet data.
     */
    public function getPreviousYearBalanceSheet(Company $company, int $prevYear): array
    {
        if ($prevYear < 2020) {
            return [];
        }

        // Try cached pre-closing data first
        $fiscalYear = FiscalYear::where('company_id', $company->id)
            ->where('year', $prevYear)
            ->first();

        if ($fiscalYear && $fiscalYear->notes) {
            $notes = json_decode($fiscalYear->notes, true);
            if (isset($notes['pre_closing_summary']['balance_sheet'])) {
                return $this->extractBalanceSheetByType([
                    'balance_sheet' => $notes['pre_closing_summary']['balance_sheet'],
                ]);
            }
        }

        // Fallback: compute live
        try {
            $bs = $this->ifrsAdapter->getBalanceSheet($company, "{$prevYear}-12-31");

            return $this->extractBalanceSheetByType($bs);
        } catch (\Exception $e) {
            Log::warning("Could not get previous year balance sheet for year {$prevYear}", [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Get previous year income statement data.
     */
    public function getPreviousYearIncomeStatement(Company $company, int $prevYear): array
    {
        if ($prevYear < 2020) {
            return [];
        }

        // Try cached pre-closing data first
        $fiscalYear = FiscalYear::where('company_id', $company->id)
            ->where('year', $prevYear)
            ->first();

        if ($fiscalYear && $fiscalYear->notes) {
            $notes = json_decode($fiscalYear->notes, true);
            if (isset($notes['pre_closing_summary']['income_statement'])) {
                return $this->extractIncomeStatementByType([
                    'income_statement' => $notes['pre_closing_summary']['income_statement'],
                ]);
            }
        }

        // Fallback: compute live
        try {
            $is = $this->ifrsAdapter->getIncomeStatement(
                $company,
                "{$prevYear}-01-01",
                "{$prevYear}-12-31"
            );

            return $this->extractIncomeStatementByType($is);
        } catch (\Exception $e) {
            Log::warning("Could not get previous year income statement for year {$prevYear}", [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Inject accumulated P&L into equity AOP codes per Macedonian standards.
     *
     * AOP 075/076: Prior years' accumulated profit/loss (акумулирана добивка / пренесена загуба)
     * AOP 077/078: Current year profit/loss (добивка / загуба за деловната година)
     *
     * Calculates total unbooked P&L from trial balance accounts, then splits
     * into current year (from income statement) and prior years (remainder).
     */
    protected function injectNetIncome(array &$aopBalances, Company $company, int $year, array $extractedAccounts = []): void
    {
        // P&L account types — these were filtered from the BS distribution by
        // filterBalanceSheetAccounts(), so ALL P&L accounts must be included here
        // for the equity injection to balance correctly.
        $plTypes = [
            'OPERATING_REVENUE', 'NON_OPERATING_REVENUE',
            'OPERATING_EXPENSE', 'DIRECT_EXPENSE', 'OVERHEAD_EXPENSE', 'OTHER_EXPENSE',
        ];

        // Calculate total accumulated unbooked P&L from trial balance.
        // Revenue accounts have negative balance (credits > debits), expenses positive.
        // Net income = -(sum of all P&L balances): positive = profit, negative = loss.
        $totalPnL = 0;
        if (! empty($extractedAccounts)) {
            foreach ($extractedAccounts as $account) {
                if (in_array($account['type'] ?? '', $plTypes)) {
                    $totalPnL -= $account['balance'] ?? 0;
                }
            }
        }

        // Try to get current year P&L from income statement
        $currentYearPnL = 0;
        try {
            $is = $this->ifrsAdapter->getIncomeStatement(
                $company, "{$year}-01-01", "{$year}-12-31"
            );
            if (! isset($is['error'])) {
                $totalRevenue = $is['income_statement']['totals']['revenue'] ?? 0;
                $totalExpenses = $is['income_statement']['totals']['expenses'] ?? 0;
                $currentYearPnL = $totalRevenue - $totalExpenses;
            }
        } catch (\Exception $e) {
            // If income statement fails, treat all P&L as prior years
        }

        // Prior years P&L = total accumulated - current year
        $priorYearsPnL = $totalPnL - $currentYearPnL;

        // Inject prior years P&L into AOP 075 (accumulated profit) or 076 (carried-forward loss)
        if (abs($priorYearsPnL) >= 0.01) {
            if ($priorYearsPnL >= 0) {
                $aopBalances['075'] = ($aopBalances['075'] ?? 0) + abs($priorYearsPnL);
            } else {
                $aopBalances['076'] = ($aopBalances['076'] ?? 0) + abs($priorYearsPnL);
            }
        }

        // Inject current year P&L into AOP 077 (profit) or 078 (loss)
        if (abs($currentYearPnL) >= 0.01) {
            if ($currentYearPnL >= 0) {
                $aopBalances['077'] = ($aopBalances['077'] ?? 0) + abs($currentYearPnL);
            } else {
                $aopBalances['078'] = ($aopBalances['078'] ?? 0) + abs($currentYearPnL);
            }
        }
    }

    /**
     * Find the current value for a specific AOP code in a rows array.
     */
    protected function findAopValue(array $rows, string $aop): float
    {
        foreach ($rows as $row) {
            if ($row['aop'] === $aop) {
                return $row['current'];
            }
        }

        return 0;
    }

    /**
     * Find the previous value for a specific AOP code in a rows array.
     */
    protected function findAopPrevValue(array $rows, string $aop): float
    {
        foreach ($rows as $row) {
            if ($row['aop'] === $aop) {
                return $row['previous'];
            }
        }

        return 0;
    }
}

