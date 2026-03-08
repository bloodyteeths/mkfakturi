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

        // Get IFRS type-level balances (fallback data)
        $current = $this->ifrsAdapter->getBalanceSheet($company, $endDate);
        $currentBalances = $this->extractBalanceSheetByType($current);

        // Get per-account-code balances for precise AOP distribution
        $currentCodeBalances = $this->extractBalancesByAccountCode($company, '2020-01-01', $endDate);
        $currentAopBalances = $this->distributeToAopCodes($currentCodeBalances, $currentBalances, $codeToAop, $fallback);

        // Get previous year data (type-level only — code-level requires live query)
        $previousBalances = $this->getPreviousYearBalanceSheet($company, $year - 1);
        $prevCodeBalances = $this->extractBalancesByAccountCode($company, '2020-01-01', "{$year}-01-01");
        $prevAopBalances = $this->distributeToAopCodes($prevCodeBalances, $previousBalances, $codeToAop, $fallback);

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

        // Get IFRS type-level balances (fallback data)
        $current = $this->ifrsAdapter->getIncomeStatement($company, $startDate, $endDate);
        $currentBalances = $this->extractIncomeStatementByType($current);

        // Get per-account-code period balances for precise AOP distribution
        $currentCodeBalances = $this->extractPeriodBalancesByAccountCode($company, $startDate, $endDate);
        $currentAopBalances = $this->distributeToAopCodes($currentCodeBalances, $currentBalances, $codeToAop, $fallback);

        // Get previous year data
        $previousBalances = $this->getPreviousYearIncomeStatement($company, $year - 1);
        $prevCodeBalances = $this->extractPeriodBalancesByAccountCode(
            $company, ($year - 1) . '-01-01', ($year - 1) . '-12-31'
        );
        $prevAopBalances = $this->distributeToAopCodes($prevCodeBalances, $previousBalances, $codeToAop, $fallback);

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
                if ($hasAopBalances && isset($currentAopBalances[$aop])) {
                    // Use pre-distributed per-account-code balances (more precise)
                    $current = $currentAopBalances[$aop] ?? 0;
                    $previous = $previousAopBalances[$aop] ?? 0;
                } else {
                    // Fallback: IFRS type-level balances
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
                if ($hasAopBalances && isset($currentAopBalances[$aop])) {
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
     * Extract per-account-code closing balances from trial balance.
     * Returns signed values: positive for debit balances, negative for credit balances.
     * Used for balance sheet — closing balances represent cumulative state.
     */
    public function extractBalancesByAccountCode(Company $company, string $fromDate, string $toDate): array
    {
        try {
            $trialBalance = $this->ifrsAdapter->getTrialBalanceSixColumn($company, $fromDate, $toDate);
        } catch (\Exception $e) {
            return [];
        }

        if (isset($trialBalance['error']) || empty($trialBalance['accounts'])) {
            return [];
        }

        $codeBalances = [];
        foreach ($trialBalance['accounts'] as $account) {
            $code = (string) $account['code'];
            $balance = round(($account['closing_debit'] ?? 0) - ($account['closing_credit'] ?? 0), 2);
            $codeBalances[$code] = ($codeBalances[$code] ?? 0) + $balance;
        }

        return $codeBalances;
    }

    /**
     * Extract per-account-code period balances from trial balance.
     * Returns absolute period activity amounts (debit or credit side, whichever is larger).
     * Used for income statement — period amounts represent activity during the period.
     */
    public function extractPeriodBalancesByAccountCode(Company $company, string $fromDate, string $toDate): array
    {
        try {
            $trialBalance = $this->ifrsAdapter->getTrialBalanceSixColumn($company, $fromDate, $toDate);
        } catch (\Exception $e) {
            return [];
        }

        if (isset($trialBalance['error']) || empty($trialBalance['accounts'])) {
            return [];
        }

        $codeBalances = [];
        foreach ($trialBalance['accounts'] as $account) {
            $code = (string) $account['code'];
            // For IS accounts, use the larger side as the period amount
            $periodDebit = $account['period_debit'] ?? 0;
            $periodCredit = $account['period_credit'] ?? 0;
            $balance = round(max($periodDebit, $periodCredit), 2);
            $codeBalances[$code] = ($codeBalances[$code] ?? 0) + $balance;
        }

        return $codeBalances;
    }

    /**
     * Distribute per-account-code balances to AOP codes.
     *
     * 1. For each account code with a mapping, add its balance to the target AOP.
     * 2. Track which IFRS types were distributed via code mapping.
     * 3. Remaining type-level balances go through the ifrs_to_aop_fallback.
     *
     * Returns: ['047' => 3000, '049' => 1200, ...] keyed by AOP code.
     */
    public function distributeToAopCodes(
        array $codeBalances,
        array $typeBalances,
        array $codeToAopMap,
        array $typeToAopFallback
    ): array {
        $aopBalances = [];
        $distributedByType = []; // Track how much was distributed per IFRS type

        if (empty($codeBalances)) {
            // No per-code data — fall through to type-level only
            foreach ($typeToAopFallback as $ifrsType => $aop) {
                if (isset($typeBalances[$ifrsType]) && $typeBalances[$ifrsType] != 0) {
                    $aopBalances[$aop] = ($aopBalances[$aop] ?? 0) + abs($typeBalances[$ifrsType]);
                }
            }

            return $aopBalances;
        }

        // Step 1: Distribute per-code balances to AOP codes
        $distributedCodes = [];
        foreach ($codeBalances as $code => $balance) {
            if (isset($codeToAopMap[$code])) {
                $aop = $codeToAopMap[$code];
                // Use absolute value — the AOP form shows positive amounts
                // Contra-accounts (009, 019, etc.) have negative balances which correctly subtract
                $aopBalances[$aop] = ($aopBalances[$aop] ?? 0) + $balance;
                $distributedCodes[$code] = true;
            }
        }

        // Make all AOP balances positive (the form shows absolute values)
        foreach ($aopBalances as $aop => $val) {
            $aopBalances[$aop] = abs(round($val, 2));
        }

        return $aopBalances;
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

