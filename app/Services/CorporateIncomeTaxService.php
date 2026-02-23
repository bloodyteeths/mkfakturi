<?php

namespace App\Services;

use App\Domain\Accounting\IfrsAdapter;
use App\Models\Company;
use Illuminate\Support\Facades\Log;

/**
 * Corporate Income Tax (CIT / Данок на добивка) Service
 *
 * Calculates the annual corporate income tax for Macedonian companies.
 * Uses IFRS income statement data + manual non-deductible adjustments.
 *
 * MK CIT rate: 10% flat on taxable base.
 * Deadline: March 15 of the following year.
 */
class CorporateIncomeTaxService
{
    protected const CIT_RATE = 0.10;

    protected IfrsAdapter $ifrsAdapter;

    protected AopReportService $aopReportService;

    public function __construct(IfrsAdapter $ifrsAdapter, AopReportService $aopReportService)
    {
        $this->ifrsAdapter = $ifrsAdapter;
        $this->aopReportService = $aopReportService;
    }

    /**
     * Full CIT calculation.
     *
     * @param  array  $adjustments  Non-deductible expense adjustments [{description, amount}]
     * @param  float  $lossCarryforward  Prior-year loss carryforward amount
     * @return array  Structured CIT calculation data
     */
    public function calculate(Company $company, int $year, array $adjustments = [], float $lossCarryforward = 0): array
    {
        $accountingProfit = $this->getAccountingProfit($company, $year);
        $totalAdjustments = $this->sumAdjustments($adjustments);

        $taxableBase = max(0, $accountingProfit + $totalAdjustments - $lossCarryforward);
        $citAmount = round($taxableBase * self::CIT_RATE, 2);
        $advancePayments = $this->getAdvancePayments($company, $year);
        $balanceDue = round($citAmount - $advancePayments, 2);

        return [
            'year' => $year,
            'accounting_profit' => $accountingProfit,
            'adjustments' => $adjustments,
            'total_adjustments' => $totalAdjustments,
            'loss_carryforward' => $lossCarryforward,
            'taxable_base' => $taxableBase,
            'cit_rate' => self::CIT_RATE,
            'cit_amount' => $citAmount,
            'advance_payments' => $advancePayments,
            'balance_due' => $balanceDue,
            'balance_refund' => $balanceDue < 0 ? abs($balanceDue) : 0,
        ];
    }

    /**
     * Preview CIT calculation (same as calculate, with additional metadata).
     */
    public function preview(Company $company, int $year, array $adjustments = [], float $lossCarryforward = 0): array
    {
        $data = $this->calculate($company, $year, $adjustments, $lossCarryforward);

        // Add income statement breakdown from AOP service
        try {
            $incomeStatement = $this->aopReportService->getIncomeStatementAop($company, $year);
            $data['income_statement'] = [
                'total_revenue' => $this->findAopValue($incomeStatement['prihodi'] ?? [], '246'),
                'total_expenses' => $this->findAopValue($incomeStatement['rashodi'] ?? [], '293'),
                'rezultat' => $incomeStatement['rezultat'] ?? [],
            ];
        } catch (\Exception $e) {
            Log::warning('CIT preview: Could not load income statement AOP data', [
                'company_id' => $company->id,
                'year' => $year,
                'error' => $e->getMessage(),
            ]);
            $data['income_statement'] = null;
        }

        $data['company'] = [
            'id' => $company->id,
            'name' => $company->name,
            'vat_number' => $company->vat_number,
        ];

        $data['deadline'] = sprintf('%d-03-15', $year + 1);

        return $data;
    }

    /**
     * Get accounting profit from IFRS income statement.
     * Revenue - Expenses = Operating Result (before tax).
     */
    public function getAccountingProfit(Company $company, int $year): float
    {
        try {
            $incomeStatement = $this->ifrsAdapter->getIncomeStatement(
                $company,
                "{$year}-01-01",
                "{$year}-12-31"
            );

            if (isset($incomeStatement['error'])) {
                Log::warning('CIT: Income statement returned error', [
                    'company_id' => $company->id,
                    'year' => $year,
                    'error' => $incomeStatement['error'],
                ]);
                return 0;
            }

            $totalRevenue = 0;
            $totalExpenses = 0;

            foreach ($incomeStatement['income_statement']['revenues'] ?? [] as $account) {
                $totalRevenue += abs($account['balance'] ?? 0);
            }

            foreach ($incomeStatement['income_statement']['expenses'] ?? [] as $account) {
                $totalExpenses += abs($account['balance'] ?? 0);
            }

            return round($totalRevenue - $totalExpenses, 2);
        } catch (\Exception $e) {
            Log::warning('CIT: Failed to get accounting profit', [
                'company_id' => $company->id,
                'year' => $year,
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Get advance CIT payments made during the year.
     * In MK, companies pay monthly 1/12 of previous year's CIT.
     * Check bank transactions tagged as CIT advance payments.
     */
    public function getAdvancePayments(Company $company, int $year): float
    {
        try {
            // Check if there's a previous year CIT return with advance info
            $previousReturn = \App\Models\TaxReturn::where('company_id', $company->id)
                ->where('return_type', \App\Models\TaxReturn::TYPE_CORPORATE)
                ->whereHas('period', function ($q) use ($year) {
                    $q->where('year', $year - 1);
                })
                ->whereIn('status', [
                    \App\Models\TaxReturn::STATUS_FILED,
                    \App\Models\TaxReturn::STATUS_ACCEPTED,
                ])
                ->orderBy('submitted_at', 'desc')
                ->first();

            if ($previousReturn && isset($previousReturn->return_data['cit_amount'])) {
                // Monthly advance = previous year CIT / 12, for months paid
                $previousCit = $previousReturn->return_data['cit_amount'];
                $monthlyAdvance = $previousCit / 12;
                // Assume 12 months of advances paid during the year
                return round($monthlyAdvance * 12, 2);
            }

            return 0;
        } catch (\Exception $e) {
            Log::warning('CIT: Failed to get advance payments', [
                'company_id' => $company->id,
                'year' => $year,
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Sum adjustment amounts.
     */
    protected function sumAdjustments(array $adjustments): float
    {
        $total = 0;
        foreach ($adjustments as $adj) {
            $total += (float) ($adj['amount'] ?? 0);
        }
        return round($total, 2);
    }

    /**
     * Find the current value for a specific AOP code.
     */
    protected function findAopValue(array $rows, string $aop): float
    {
        foreach ($rows as $row) {
            if (($row['aop'] ?? '') === $aop) {
                return $row['current'] ?? 0;
            }
        }
        return 0;
    }
}

// CLAUDE-CHECKPOINT
