<?php

namespace Modules\Mk\Payroll\Services;

use Modules\Mk\Payroll\DTOs\PayrollCalculationResult;

/**
 * Macedonian Payroll Tax Service
 *
 * Calculates payroll taxes and contributions according to Macedonian tax law (2024 rates).
 *
 * Tax and contribution rates:
 * - Income Tax: 10% flat (on taxable base = gross - employee contributions)
 * - Pension (PIO): 18% total (9% employee + 9% employer)
 * - Health (ZO): 7.5% total (3.75% employee + 3.75% employer)
 * - Unemployment: 1.2% (employee only)
 * - Additional contribution: 0.5% (employee only, for professional diseases)
 *
 * All monetary values are in cents (integer).
 */
class MacedonianPayrollTaxService
{
    // 2024 Macedonian Tax Rates
    private const INCOME_TAX_RATE = 0.10; // 10% flat rate

    // Employee contribution rates
    private const PENSION_EMPLOYEE_RATE = 0.09; // 9%
    private const HEALTH_EMPLOYEE_RATE = 0.0375; // 3.75%
    private const UNEMPLOYMENT_RATE = 0.012; // 1.2%
    private const ADDITIONAL_CONTRIBUTION_RATE = 0.005; // 0.5%

    // Employer contribution rates
    private const PENSION_EMPLOYER_RATE = 0.09; // 9%
    private const HEALTH_EMPLOYER_RATE = 0.0375; // 3.75%

    /**
     * Calculate payroll from gross salary
     *
     * Steps:
     * 1. Calculate employee contributions (pension, health, unemployment, additional)
     * 2. Calculate taxable base (gross - employee contributions)
     * 3. Calculate income tax (10% of taxable base)
     * 4. Calculate net salary (taxable base - income tax)
     * 5. Calculate employer contributions (pension, health)
     * 6. Calculate total employer cost (gross + employer contributions)
     *
     * @param int $grossCents Gross salary in cents
     * @return PayrollCalculationResult
     */
    public function calculateFromGross(int $grossCents): PayrollCalculationResult
    {
        // Step 1: Calculate employee contributions
        $pensionEmployee = $this->calculatePensionEmployee($grossCents);
        $healthEmployee = $this->calculateHealthEmployee($grossCents);
        $unemployment = $this->calculateUnemployment($grossCents);
        $additionalContribution = $this->calculateAdditionalContribution($grossCents);

        // Step 2: Calculate taxable base (gross - employee contributions)
        $totalEmployeeContributions = $pensionEmployee
            + $healthEmployee
            + $unemployment
            + $additionalContribution;
        $taxableBase = $grossCents - $totalEmployeeContributions;

        // Step 3: Calculate income tax (10% of taxable base)
        $incomeTax = $this->calculateIncomeTax($taxableBase);

        // Step 4: Calculate net salary (taxable base - income tax)
        $netSalary = $taxableBase - $incomeTax;

        // Step 5: Calculate employer contributions
        $pensionEmployer = $this->calculatePensionEmployer($grossCents);
        $healthEmployer = $this->calculateHealthEmployer($grossCents);

        // Step 6: Calculate totals
        $totalEmployeeDeductions = $totalEmployeeContributions + $incomeTax;
        $totalEmployerCost = $grossCents + $pensionEmployer + $healthEmployer;

        return new PayrollCalculationResult(
            grossSalary: $grossCents,
            netSalary: $netSalary,
            taxableBase: $taxableBase,
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
}

// LLM-CHECKPOINT
