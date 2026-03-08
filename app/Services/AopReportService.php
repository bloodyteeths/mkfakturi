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
 * Uses the static mapping in config/ujp_aop.php to produce UJP-compliant
 * financial reports with AOP-coded positions.
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

        // Get raw IFRS data for current year
        $current = $this->ifrsAdapter->getBalanceSheet($company, $endDate);
        $currentBalances = $this->extractBalanceSheetByType($current);

        // Get previous year data
        $previousBalances = $this->getPreviousYearBalanceSheet($company, $year - 1);

        // Build AOP rows for АКТИВА
        $aktivaConfig = config('ujp_aop.obrazec_36.aktiva', []);
        $aktiva = $this->buildAopRows($aktivaConfig, $currentBalances, $previousBalances);

        // Build AOP rows for ПАСИВА
        $pasivaConfig = config('ujp_aop.obrazec_36.pasiva', []);
        $pasiva = $this->buildAopRows($pasivaConfig, $currentBalances, $previousBalances);

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

        // Get raw IFRS data for current year
        $current = $this->ifrsAdapter->getIncomeStatement($company, $startDate, $endDate);
        $currentBalances = $this->extractIncomeStatementByType($current);

        // Get previous year data
        $previousBalances = $this->getPreviousYearIncomeStatement($company, $year - 1);

        // Build AOP rows for Приходи
        $prihodiConfig = config('ujp_aop.obrazec_37.prihodi', []);
        $prihodi = $this->buildAopRows($prihodiConfig, $currentBalances, $previousBalances);

        // Build AOP rows for Расходи
        $rashodiConfig = config('ujp_aop.obrazec_37.rashodi', []);
        $rashodi = $this->buildAopRows($rashodiConfig, $currentBalances, $previousBalances);

        // Compute result rows
        $totalRevenue = $this->findAopValue($prihodi, '246');
        $totalExpenses = $this->findAopValue($rashodi, '293');
        $prevTotalRevenue = $this->findAopPrevValue($prihodi, '246');
        $prevTotalExpenses = $this->findAopPrevValue($rashodi, '293');

        $rezultat = $this->buildResultRows(
            $totalRevenue, $totalExpenses,
            $prevTotalRevenue, $prevTotalExpenses
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
    public function buildAopRows(array $config, array $currentBalances, array $previousBalances, ?array $fallbackOverride = null): array
    {
        $fallback = $fallbackOverride ?? config('ujp_aop.ifrs_to_aop_fallback', []);

        // First pass: fill leaf nodes from IFRS balances
        $rows = [];
        foreach ($config as $rowConfig) {
            $aop = $rowConfig['aop'];
            $isTotal = $rowConfig['is_total'] ?? false;

            $current = 0;
            $previous = 0;

            if (! $isTotal && isset($rowConfig['ifrs_types'])) {
                foreach ($rowConfig['ifrs_types'] as $ifrsType) {
                    // Check if this IFRS type's fallback AOP matches this row
                    $fallbackAop = $fallback[$ifrsType] ?? null;

                    // If this row is the fallback for this type, add the balance
                    // This handles the case where multiple AOP rows share the same ifrs_types
                    // but only the fallback row gets the balance (since we can't sub-split)
                    if ($fallbackAop === $aop) {
                        $current += $currentBalances[$ifrsType] ?? 0;
                        $previous += $previousBalances[$ifrsType] ?? 0;
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
     * Build result rows for Образец 37 (profit/loss, tax, net).
     */
    protected function buildResultRows(
        float $totalRevenue, float $totalExpenses,
        float $prevRevenue, float $prevExpenses
    ): array {
        $operatingResult = $totalRevenue - $totalExpenses;
        $prevOperatingResult = $prevRevenue - $prevExpenses;

        $isProfit = $operatingResult >= 0;
        $prevIsProfit = $prevOperatingResult >= 0;

        $incomeTax = $isProfit ? $operatingResult * 0.10 : 0;
        $prevIncomeTax = $prevIsProfit ? $prevOperatingResult * 0.10 : 0;

        $netResult = $operatingResult - $incomeTax;
        $prevNetResult = $prevOperatingResult - $prevIncomeTax;

        $config = config('ujp_aop.obrazec_37.rezultat', []);
        $rows = [];

        foreach ($config as $rowConfig) {
            $aop = $rowConfig['aop'];
            $current = 0;
            $previous = 0;
            $show = true;

            switch ($rowConfig['formula'] ?? '') {
                case 'profit':
                    $current = $isProfit ? abs($operatingResult) : 0;
                    $previous = $prevIsProfit ? abs($prevOperatingResult) : 0;
                    break;
                case 'loss':
                    $current = ! $isProfit ? abs($operatingResult) : 0;
                    $previous = ! $prevIsProfit ? abs($prevOperatingResult) : 0;
                    break;
                case 'profit_before_tax':
                    $current = $isProfit ? abs($operatingResult) : 0;
                    $previous = $prevIsProfit ? abs($prevOperatingResult) : 0;
                    break;
                case 'loss_before_tax':
                    $current = ! $isProfit ? abs($operatingResult) : 0;
                    $previous = ! $prevIsProfit ? abs($prevOperatingResult) : 0;
                    break;
                case 'tax':
                    $current = $incomeTax;
                    $previous = $prevIncomeTax;
                    break;
                case 'net_profit':
                    $current = $netResult >= 0 ? abs($netResult) : 0;
                    $previous = $prevNetResult >= 0 ? abs($prevNetResult) : 0;
                    break;
                case 'net_loss':
                    $current = $netResult < 0 ? abs($netResult) : 0;
                    $previous = $prevNetResult < 0 ? abs($prevNetResult) : 0;
                    break;
            }

            $rows[] = [
                'aop' => $aop,
                'label' => $rowConfig['label'],
                'current' => round($current, 2),
                'previous' => round($previous, 2),
                'is_total' => false,
                'is_result' => true,
                'indent' => $rowConfig['indent'] ?? 0,
            ];
        }

        return $rows;
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

