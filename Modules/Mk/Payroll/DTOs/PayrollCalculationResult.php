<?php

namespace Modules\Mk\Payroll\DTOs;

/**
 * Payroll Calculation Result DTO
 *
 * Contains all calculated values for a payroll calculation.
 * All monetary values are in cents (integer).
 */
class PayrollCalculationResult
{
    /**
     * @param int $grossSalary Gross salary in cents
     * @param int $netSalary Net salary (take-home pay) in cents
     * @param int $taxableBase Taxable base for income tax in cents
     * @param int $pensionEmployee Employee pension contribution (9%) in cents
     * @param int $pensionEmployer Employer pension contribution (9%) in cents
     * @param int $healthEmployee Employee health contribution (3.75%) in cents
     * @param int $healthEmployer Employer health contribution (3.75%) in cents
     * @param int $unemployment Unemployment contribution (1.2%) in cents
     * @param int $additionalContribution Additional contribution for professional diseases (0.5%) in cents
     * @param int $incomeTax Income tax (10% flat) in cents
     * @param int $totalEmployeeDeductions Total employee deductions in cents
     * @param int $totalEmployerCost Total employer cost (gross + employer contributions) in cents
     */
    public function __construct(
        public int $grossSalary,
        public int $netSalary,
        public int $taxableBase,
        public int $pensionEmployee,
        public int $pensionEmployer,
        public int $healthEmployee,
        public int $healthEmployer,
        public int $unemployment,
        public int $additionalContribution,
        public int $incomeTax,
        public int $totalEmployeeDeductions,
        public int $totalEmployerCost
    ) {
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            grossSalary: $data['grossSalary'] ?? 0,
            netSalary: $data['netSalary'] ?? 0,
            taxableBase: $data['taxableBase'] ?? 0,
            pensionEmployee: $data['pensionEmployee'] ?? 0,
            pensionEmployer: $data['pensionEmployer'] ?? 0,
            healthEmployee: $data['healthEmployee'] ?? 0,
            healthEmployer: $data['healthEmployer'] ?? 0,
            unemployment: $data['unemployment'] ?? 0,
            additionalContribution: $data['additionalContribution'] ?? 0,
            incomeTax: $data['incomeTax'] ?? 0,
            totalEmployeeDeductions: $data['totalEmployeeDeductions'] ?? 0,
            totalEmployerCost: $data['totalEmployerCost'] ?? 0
        );
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'grossSalary' => $this->grossSalary,
            'netSalary' => $this->netSalary,
            'taxableBase' => $this->taxableBase,
            'pensionEmployee' => $this->pensionEmployee,
            'pensionEmployer' => $this->pensionEmployer,
            'healthEmployee' => $this->healthEmployee,
            'healthEmployer' => $this->healthEmployer,
            'unemployment' => $this->unemployment,
            'additionalContribution' => $this->additionalContribution,
            'incomeTax' => $this->incomeTax,
            'totalEmployeeDeductions' => $this->totalEmployeeDeductions,
            'totalEmployerCost' => $this->totalEmployerCost,
        ];
    }

    /**
     * Get total employee contributions (pension + health + unemployment + additional)
     */
    public function getTotalEmployeeContributions(): int
    {
        return $this->pensionEmployee
            + $this->healthEmployee
            + $this->unemployment
            + $this->additionalContribution;
    }

    /**
     * Get total employer contributions (pension + health)
     */
    public function getTotalEmployerContributions(): int
    {
        return $this->pensionEmployer + $this->healthEmployer;
    }
}

// LLM-CHECKPOINT
