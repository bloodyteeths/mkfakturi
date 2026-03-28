<?php

namespace Modules\Mk\Payroll\Services;

use Modules\Mk\Payroll\DTOs\PayrollCalculationResult;

/**
 * Macedonian Payroll Tax Service
 *
 * Calculates payroll taxes and contributions per Закон за придонеси
 * од задолжително социјално осигурување and Закон за данокот на
 * личен доход.
 *
 * Rates (2025):
 * - Pension (PIO): 18% total (9% employee + 9% employer)
 * - Health (ZO): 7.5% total (3.75% employee + 3.75% employer)
 * - Unemployment: 1.2% (employee only)
 * - Additional: 0.5% (employee only, professional diseases)
 * - Personal deduction: MKD 10,270/month (лично ослободување)
 * - Income Tax: 10% on (gross − contributions − personal deduction)
 *
 * Contribution base: clamped to 50%–16× national average salary.
 * All monetary values are in cents (integer).
 */
class MacedonianPayrollTaxService
{
    // Income tax rate
    private const INCOME_TAX_RATE = 0.10; // 10%

    // Employee contribution rates
    private const PENSION_EMPLOYEE_RATE = 0.09; // 9%
    private const HEALTH_EMPLOYEE_RATE = 0.0375; // 3.75%
    private const UNEMPLOYMENT_RATE = 0.012; // 1.2%
    private const ADDITIONAL_CONTRIBUTION_RATE = 0.005; // 0.5%

    // Employer contribution rates
    private const PENSION_EMPLOYER_RATE = 0.09; // 9%
    private const HEALTH_EMPLOYER_RATE = 0.0375; // 3.75%

    // Contribution base limits (P7-05)
    private const DEFAULT_MIN_CONTRIBUTION_BASE = 3157700;  // MKD 31,577 in cents
    private const DEFAULT_MAX_CONTRIBUTION_BASE = 101046400; // MKD 1,010,464 in cents

    // Personal deduction (лично ослободување) — Закон за данокот на личен доход
    private const DEFAULT_PERSONAL_DEDUCTION = 1027000; // MKD 10,270 in cents

    /**
     * Calculate payroll from gross salary.
     *
     * Formula per MK law:
     * 1. Contributions on clamped base (50%–16× avg salary)
     * 2. Taxable base = gross − employee contributions − personal deduction
     * 3. Income tax = 10% × taxable base
     * 4. Net = gross − contributions − income tax
     *
     * @param int $grossCents Gross salary in cents
     * @return PayrollCalculationResult
     */
    public function calculateFromGross(int $grossCents): PayrollCalculationResult
    {
        // P7-05: Clamp gross to contribution base limits
        $contributionBase = $this->clampToContributionBase($grossCents);

        // Step 1: Employee contributions (on clamped base)
        $pensionEmployee = $this->calculatePensionEmployee($contributionBase);
        $healthEmployee = $this->calculateHealthEmployee($contributionBase);
        $unemployment = $this->calculateUnemployment($contributionBase);
        $additionalContribution = $this->calculateAdditionalContribution($contributionBase);

        $totalEmployeeContributions = $pensionEmployee
            + $healthEmployee
            + $unemployment
            + $additionalContribution;

        // Step 2: Personal deduction (лично ослободување)
        $personalDeduction = $this->getPersonalDeduction();

        // Step 3: Taxable base = gross − contributions − personal deduction
        $taxableBase = max(0, $grossCents - $totalEmployeeContributions - $personalDeduction);

        // Step 4: Income tax (10% of taxable base)
        $incomeTax = $this->calculateIncomeTax($taxableBase);

        // Step 5: Net salary = gross − contributions − income tax
        $netSalary = $grossCents - $totalEmployeeContributions - $incomeTax;

        // Step 6: Employer contributions (on clamped base)
        $pensionEmployer = $this->calculatePensionEmployer($contributionBase);
        $healthEmployer = $this->calculateHealthEmployer($contributionBase);

        // Totals
        $totalEmployeeDeductions = $totalEmployeeContributions + $incomeTax;
        $totalEmployerCost = $grossCents + $pensionEmployer + $healthEmployer;

        return new PayrollCalculationResult(
            grossSalary: $grossCents,
            netSalary: $netSalary,
            taxableBase: $taxableBase,
            personalDeduction: $personalDeduction,
            pensionEmployee: $pensionEmployee,
            pensionEmployer: $pensionEmployer,
            healthEmployee: $healthEmployee,
            healthEmployer: $healthEmployer,
            unemployment: $unemployment,
            additionalContribution: $additionalContribution,
            incomeTax: $incomeTax,
            totalEmployeeDeductions: $totalEmployeeDeductions,
            totalEmployerCost: $totalEmployerCost
        );
    }

    /**
     * Calculate net salary only (simplified version)
     *
     * @param int $grossCents Gross salary in cents
     * @return int Net salary in cents
     */
    public function calculateNetSalary(int $grossCents): int
    {
        $result = $this->calculateFromGross($grossCents);

        return $result->netSalary;
    }

    /**
     * Calculate total employer cost (gross + employer contributions)
     *
     * @param int $grossCents Gross salary in cents
     * @return int Total employer cost in cents
     */
    public function getEmployerCost(int $grossCents): int
    {
        $result = $this->calculateFromGross($grossCents);

        return $result->totalEmployerCost;
    }

    /**
     * Get tax breakdown as array
     *
     * @param int $grossCents Gross salary in cents
     * @return array
     */
    public function getTaxBreakdown(int $grossCents): array
    {
        $result = $this->calculateFromGross($grossCents);

        return $result->toArray();
    }

    /**
     * Validate Macedonian EMBG (Единствен Матичен Број на Граѓанинот)
     *
     * EMBG is a 13-digit unique personal identification number:
     * - Positions 1-2: Day of birth (01-31)
     * - Positions 3-4: Month of birth (01-12)
     * - Positions 5-7: Year of birth (last 3 digits, e.g., 990 for 1990)
     * - Positions 8-9: Region code
     * - Positions 10-12: Sequential number
     * - Position 13: Check digit
     *
     * @param string $embg 13-digit EMBG number
     * @return bool
     */
    public function validateEmbg(string $embg): bool
    {
        // Must be exactly 13 digits
        if (! preg_match('/^\d{13}$/', $embg)) {
            return false;
        }

        // Extract components
        $day = (int) substr($embg, 0, 2);
        $month = (int) substr($embg, 2, 2);
        $year = (int) substr($embg, 4, 3);
        $region = (int) substr($embg, 7, 2);

        // Validate day (01-31)
        if ($day < 1 || $day > 31) {
            return false;
        }

        // Validate month (01-12)
        if ($month < 1 || $month > 12) {
            return false;
        }

        // Validate region code (01-99, excluding some reserved ranges)
        if ($region < 1 || $region > 99) {
            return false;
        }

        // Validate check digit using Luhn-like algorithm
        $checksum = 0;
        $weights = [7, 6, 5, 4, 3, 2, 7, 6, 5, 4, 3, 2];

        for ($i = 0; $i < 12; $i++) {
            $checksum += (int) $embg[$i] * $weights[$i];
        }

        $checkDigit = (11 - ($checksum % 11)) % 11;
        $providedCheckDigit = (int) $embg[12];

        return $checkDigit === $providedCheckDigit;
    }

    /**
     * P7-05: Clamp gross salary to contribution base limits.
     *
     * Social contributions are calculated on a base that is clamped between
     * the minimum (50% of national average) and maximum (16x national average).
     * Income tax is still calculated on actual gross — only contributions are clamped.
     *
     * @param int $grossCents Actual gross salary in cents
     * @return int Clamped contribution base in cents
     */
    public function clampToContributionBase(int $grossCents): int
    {
        $minBase = (int) config('mk.payroll.min_contribution_base', self::DEFAULT_MIN_CONTRIBUTION_BASE);
        $maxBase = (int) config('mk.payroll.max_contribution_base', self::DEFAULT_MAX_CONTRIBUTION_BASE);

        return max($minBase, min($maxBase, $grossCents));
    }

    /**
     * Calculate employee pension contribution (9% of gross)
     */
    private function calculatePensionEmployee(int $grossCents): int
    {
        return (int) round($grossCents * self::PENSION_EMPLOYEE_RATE);
    }

    /**
     * Calculate employer pension contribution (9% of gross)
     */
    private function calculatePensionEmployer(int $grossCents): int
    {
        return (int) round($grossCents * self::PENSION_EMPLOYER_RATE);
    }

    /**
     * Calculate employee health contribution (3.75% of gross)
     */
    private function calculateHealthEmployee(int $grossCents): int
    {
        return (int) round($grossCents * self::HEALTH_EMPLOYEE_RATE);
    }

    /**
     * Calculate employer health contribution (3.75% of gross)
     */
    private function calculateHealthEmployer(int $grossCents): int
    {
        return (int) round($grossCents * self::HEALTH_EMPLOYER_RATE);
    }

    /**
     * Calculate unemployment contribution (1.2% of gross, employee only)
     */
    private function calculateUnemployment(int $grossCents): int
    {
        return (int) round($grossCents * self::UNEMPLOYMENT_RATE);
    }

    /**
     * Calculate additional contribution (0.5% of gross, employee only)
     */
    private function calculateAdditionalContribution(int $grossCents): int
    {
        return (int) round($grossCents * self::ADDITIONAL_CONTRIBUTION_RATE);
    }

    /**
     * Calculate income tax (10% of taxable base)
     */
    private function calculateIncomeTax(int $taxableBaseCents): int
    {
        return (int) round($taxableBaseCents * self::INCOME_TAX_RATE);
    }

    /**
     * Get personal deduction (лично ослободување) amount.
     *
     * Per Закон за данокот на личен доход, the personal deduction
     * is subtracted from gross − contributions before calculating income tax.
     *
     * @return int Personal deduction in cents
     */
    public function getPersonalDeduction(): int
    {
        return (int) config('mk.payroll.personal_deduction', self::DEFAULT_PERSONAL_DEDUCTION);
    }
}

// CLAUDE-CHECKPOINT
