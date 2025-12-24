<?php

namespace Tests\Unit;

use Modules\Mk\Payroll\Services\MacedonianPayrollTaxService;
use Tests\TestCase;

/**
 * Macedonian Payroll Tax Service Tests
 *
 * Tests the MK tax calculation service to ensure it correctly implements
 * Macedonian payroll tax law (2024 rates).
 *
 * Tax rates verified:
 * - Employee: Pension 9%, Health 3.75%, Unemployment 1.2%, Additional 0.5% = 14.45% total
 * - Employer: Pension 9%, Health 3.75% = 12.75% total
 * - Income Tax: 10% flat on (Gross - Employee contributions)
 */
class MacedonianPayrollTaxServiceTest extends TestCase
{
    private MacedonianPayrollTaxService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MacedonianPayrollTaxService();
    }

    /** @test */
    public function it_calculates_correct_employee_pension_contribution()
    {
        // 9% of 100,000 MKD = 9,000 MKD
        $grossCents = 10000000; // 100,000 MKD in cents
        $result = $this->service->calculateFromGross($grossCents);

        $this->assertEquals(900000, $result->pensionEmployee, 'Employee pension should be 9% of gross');
    }

    /** @test */
    public function it_calculates_correct_employee_health_contribution()
    {
        // 3.75% of 100,000 MKD = 3,750 MKD
        $grossCents = 10000000; // 100,000 MKD in cents
        $result = $this->service->calculateFromGross($grossCents);

        $this->assertEquals(375000, $result->healthEmployee, 'Employee health should be 3.75% of gross');
    }

    /** @test */
    public function it_calculates_correct_unemployment_contribution()
    {
        // 1.2% of 100,000 MKD = 1,200 MKD
        $grossCents = 10000000; // 100,000 MKD in cents
        $result = $this->service->calculateFromGross($grossCents);

        $this->assertEquals(120000, $result->unemployment, 'Unemployment should be 1.2% of gross');
    }

    /** @test */
    public function it_calculates_correct_additional_contribution()
    {
        // 0.5% of 100,000 MKD = 500 MKD
        $grossCents = 10000000; // 100,000 MKD in cents
        $result = $this->service->calculateFromGross($grossCents);

        $this->assertEquals(50000, $result->additionalContribution, 'Additional contribution should be 0.5% of gross');
    }

    /** @test */
    public function it_calculates_correct_total_employee_contributions()
    {
        // Total employee: 9% + 3.75% + 1.2% + 0.5% = 14.45%
        // 14.45% of 100,000 MKD = 14,450 MKD
        $grossCents = 10000000; // 100,000 MKD in cents
        $result = $this->service->calculateFromGross($grossCents);

        $totalEmployeeContributions = $result->getTotalEmployeeContributions();
        $this->assertEquals(1445000, $totalEmployeeContributions, 'Total employee contributions should be 14.45% of gross');
    }

    /** @test */
    public function it_calculates_correct_taxable_base()
    {
        // Taxable base = Gross - Employee contributions
        // 100,000 - 14,450 = 85,550 MKD
        $grossCents = 10000000; // 100,000 MKD in cents
        $result = $this->service->calculateFromGross($grossCents);

        $this->assertEquals(8555000, $result->taxableBase, 'Taxable base should be gross minus employee contributions');
    }

    /** @test */
    public function it_calculates_correct_income_tax()
    {
        // Income tax = 10% of taxable base
        // 10% of 85,550 = 8,555 MKD
        $grossCents = 10000000; // 100,000 MKD in cents
        $result = $this->service->calculateFromGross($grossCents);

        $this->assertEquals(855500, $result->incomeTax, 'Income tax should be 10% of taxable base');
    }

    /** @test */
    public function it_calculates_correct_net_salary()
    {
        // Net = Taxable base - Income tax
        // 85,550 - 8,555 = 76,995 MKD
        $grossCents = 10000000; // 100,000 MKD in cents
        $result = $this->service->calculateFromGross($grossCents);

        $this->assertEquals(7699500, $result->netSalary, 'Net salary should be 76,995 MKD');
    }

    /** @test */
    public function it_calculates_correct_employer_pension_contribution()
    {
        // 9% of 100,000 MKD = 9,000 MKD
        $grossCents = 10000000; // 100,000 MKD in cents
        $result = $this->service->calculateFromGross($grossCents);

        $this->assertEquals(900000, $result->pensionEmployer, 'Employer pension should be 9% of gross');
    }

    /** @test */
    public function it_calculates_correct_employer_health_contribution()
    {
        // 3.75% of 100,000 MKD = 3,750 MKD
        $grossCents = 10000000; // 100,000 MKD in cents
        $result = $this->service->calculateFromGross($grossCents);

        $this->assertEquals(375000, $result->healthEmployer, 'Employer health should be 3.75% of gross');
    }

    /** @test */
    public function it_calculates_correct_total_employer_contributions()
    {
        // Total employer: 9% + 3.75% = 12.75%
        // 12.75% of 100,000 MKD = 12,750 MKD
        $grossCents = 10000000; // 100,000 MKD in cents
        $result = $this->service->calculateFromGross($grossCents);

        $totalEmployerContributions = $result->getTotalEmployerContributions();
        $this->assertEquals(1275000, $totalEmployerContributions, 'Total employer contributions should be 12.75% of gross');
    }

    /** @test */
    public function it_calculates_correct_total_employer_cost()
    {
        // Total cost = Gross + Employer contributions
        // 100,000 + 12,750 = 112,750 MKD
        $grossCents = 10000000; // 100,000 MKD in cents
        $result = $this->service->calculateFromGross($grossCents);

        $this->assertEquals(11275000, $result->totalEmployerCost, 'Total employer cost should be 112,750 MKD');
    }

    /** @test */
    public function it_matches_roadmap_example_calculation()
    {
        // From ROADMAP-PAYROLL.md:
        // Gross: 100,000
        // Employee deductions: 14,450
        // Taxable base: 85,550
        // Income tax: 8,555
        // Net: 76,995
        // Employer cost: 112,750

        $grossCents = 10000000; // 100,000 MKD in cents
        $result = $this->service->calculateFromGross($grossCents);

        $this->assertEquals(10000000, $result->grossSalary, 'Gross should be 100,000 MKD');
        $this->assertEquals(1445000, $result->getTotalEmployeeContributions(), 'Employee deductions should be 14,450 MKD');
        $this->assertEquals(8555000, $result->taxableBase, 'Taxable base should be 85,550 MKD');
        $this->assertEquals(855500, $result->incomeTax, 'Income tax should be 8,555 MKD');
        $this->assertEquals(7699500, $result->netSalary, 'Net should be 76,995 MKD');
        $this->assertEquals(11275000, $result->totalEmployerCost, 'Employer cost should be 112,750 MKD');
    }

    /** @test */
    public function it_calculates_correct_total_employee_deductions()
    {
        // Total deductions = Employee contributions + Income tax
        // 14,450 + 8,555 = 23,005 MKD
        $grossCents = 10000000; // 100,000 MKD in cents
        $result = $this->service->calculateFromGross($grossCents);

        $this->assertEquals(2300500, $result->totalEmployeeDeductions, 'Total employee deductions should be 23,005 MKD');
    }

    /** @test */
    public function it_calculates_correctly_for_minimum_wage()
    {
        // Minimum wage in Macedonia ~20,000 MKD
        $grossCents = 2000000; // 20,000 MKD in cents
        $result = $this->service->calculateFromGross($grossCents);

        // Employee contributions: 14.45% of 20,000 = 2,890 MKD
        $this->assertEquals(289000, $result->getTotalEmployeeContributions());

        // Taxable base: 20,000 - 2,890 = 17,110 MKD
        $this->assertEquals(1711000, $result->taxableBase);

        // Income tax: 10% of 17,110 = 1,711 MKD
        $this->assertEquals(171100, $result->incomeTax);

        // Net: 17,110 - 1,711 = 15,399 MKD
        $this->assertEquals(1539900, $result->netSalary);

        // Employer cost: 20,000 + 2,550 = 22,550 MKD
        $this->assertEquals(2255000, $result->totalEmployerCost);
    }

    /** @test */
    public function it_calculates_correctly_for_high_salary()
    {
        // High salary: 200,000 MKD
        $grossCents = 20000000; // 200,000 MKD in cents
        $result = $this->service->calculateFromGross($grossCents);

        // Employee contributions: 14.45% of 200,000 = 28,900 MKD
        $this->assertEquals(2890000, $result->getTotalEmployeeContributions());

        // Taxable base: 200,000 - 28,900 = 171,100 MKD
        $this->assertEquals(17110000, $result->taxableBase);

        // Income tax: 10% of 171,100 = 17,110 MKD
        $this->assertEquals(1711000, $result->incomeTax);

        // Net: 171,100 - 17,110 = 153,990 MKD
        $this->assertEquals(15399000, $result->netSalary);

        // Employer cost: 200,000 + 25,500 = 225,500 MKD
        $this->assertEquals(22550000, $result->totalEmployerCost);
    }

    /** @test */
    public function it_returns_zero_for_zero_salary()
    {
        $result = $this->service->calculateFromGross(0);

        $this->assertEquals(0, $result->grossSalary);
        $this->assertEquals(0, $result->netSalary);
        $this->assertEquals(0, $result->taxableBase);
        $this->assertEquals(0, $result->incomeTax);
        $this->assertEquals(0, $result->getTotalEmployeeContributions());
        $this->assertEquals(0, $result->getTotalEmployerContributions());
        $this->assertEquals(0, $result->totalEmployerCost);
    }

    /** @test */
    public function it_provides_net_salary_shortcut_method()
    {
        $grossCents = 10000000; // 100,000 MKD in cents
        $netSalary = $this->service->calculateNetSalary($grossCents);

        $this->assertEquals(7699500, $netSalary, 'Net salary shortcut should return 76,995 MKD');
    }

    /** @test */
    public function it_provides_employer_cost_shortcut_method()
    {
        $grossCents = 10000000; // 100,000 MKD in cents
        $employerCost = $this->service->getEmployerCost($grossCents);

        $this->assertEquals(11275000, $employerCost, 'Employer cost shortcut should return 112,750 MKD');
    }

    /** @test */
    public function it_provides_tax_breakdown_array()
    {
        $grossCents = 10000000; // 100,000 MKD in cents
        $breakdown = $this->service->getTaxBreakdown($grossCents);

        $this->assertIsArray($breakdown);
        $this->assertArrayHasKey('grossSalary', $breakdown);
        $this->assertArrayHasKey('netSalary', $breakdown);
        $this->assertArrayHasKey('incomeTax', $breakdown);
        $this->assertEquals(10000000, $breakdown['grossSalary']);
        $this->assertEquals(7699500, $breakdown['netSalary']);
    }

    /** @test */
    public function it_validates_valid_embg()
    {
        // Valid EMBG: 13 digits with correct check digit
        // Example: 0101990450006 (born 01.01.1990, region 45, check digit 6)
        $validEmbg = '0101990450006';

        $isValid = $this->service->validateEmbg($validEmbg);

        $this->assertTrue($isValid, 'Valid EMBG should pass validation');
    }

    /** @test */
    public function it_rejects_embg_with_invalid_length()
    {
        $shortEmbg = '123456789012'; // 12 digits
        $longEmbg = '12345678901234'; // 14 digits

        $this->assertFalse($this->service->validateEmbg($shortEmbg), 'EMBG with 12 digits should be rejected');
        $this->assertFalse($this->service->validateEmbg($longEmbg), 'EMBG with 14 digits should be rejected');
    }

    /** @test */
    public function it_rejects_embg_with_non_numeric_characters()
    {
        $alphaEmbg = '010199045000A';
        $specialEmbg = '0101990450-06';

        $this->assertFalse($this->service->validateEmbg($alphaEmbg), 'EMBG with letters should be rejected');
        $this->assertFalse($this->service->validateEmbg($specialEmbg), 'EMBG with special chars should be rejected');
    }

    /** @test */
    public function it_rejects_embg_with_invalid_day()
    {
        $invalidDay = '0001990450006'; // Day 00
        $invalidDay2 = '3201990450006'; // Day 32

        $this->assertFalse($this->service->validateEmbg($invalidDay), 'EMBG with day 00 should be rejected');
        $this->assertFalse($this->service->validateEmbg($invalidDay2), 'EMBG with day 32 should be rejected');
    }

    /** @test */
    public function it_rejects_embg_with_invalid_month()
    {
        $invalidMonth = '0100990450006'; // Month 00
        $invalidMonth2 = '0113990450006'; // Month 13

        $this->assertFalse($this->service->validateEmbg($invalidMonth), 'EMBG with month 00 should be rejected');
        $this->assertFalse($this->service->validateEmbg($invalidMonth2), 'EMBG with month 13 should be rejected');
    }

    /** @test */
    public function it_rejects_embg_with_invalid_region()
    {
        $invalidRegion = '0101990000006'; // Region 00

        $this->assertFalse($this->service->validateEmbg($invalidRegion), 'EMBG with region 00 should be rejected');
    }

    /** @test */
    public function it_rejects_embg_with_invalid_check_digit()
    {
        // Valid structure but wrong check digit
        $invalidCheckDigit = '0101990450007'; // Should be 6, not 7

        $this->assertFalse($this->service->validateEmbg($invalidCheckDigit), 'EMBG with invalid check digit should be rejected');
    }

    /** @test */
    public function it_validates_multiple_valid_embgs()
    {
        $validEmbgs = [
            '0101990450006', // Example 1
            '1512985410003', // Example 2
            '2706995420009', // Example 3
        ];

        foreach ($validEmbgs as $embg) {
            $this->assertTrue(
                $this->service->validateEmbg($embg),
                "EMBG {$embg} should be valid"
            );
        }
    }

    /** @test */
    public function it_handles_rounding_correctly_for_odd_amounts()
    {
        // Test with amount that produces decimal results
        $grossCents = 5555555; // 55,555.55 MKD
        $result = $this->service->calculateFromGross($grossCents);

        // Verify all values are integers (no decimals)
        $this->assertIsInt($result->grossSalary);
        $this->assertIsInt($result->netSalary);
        $this->assertIsInt($result->incomeTax);
        $this->assertIsInt($result->pensionEmployee);
        $this->assertIsInt($result->healthEmployee);

        // Verify the calculation still produces reasonable results
        $this->assertGreaterThan(0, $result->netSalary);
        $this->assertLessThan($result->grossSalary, $result->netSalary);
    }

    /** @test */
    public function it_ensures_net_is_always_less_than_gross()
    {
        $testSalaries = [1000000, 5000000, 10000000, 20000000]; // Various amounts

        foreach ($testSalaries as $grossCents) {
            $result = $this->service->calculateFromGross($grossCents);
            $this->assertLessThan(
                $result->grossSalary,
                $result->netSalary,
                "Net salary should always be less than gross for {$grossCents} cents"
            );
        }
    }

    /** @test */
    public function it_ensures_employer_cost_is_always_greater_than_gross()
    {
        $testSalaries = [1000000, 5000000, 10000000, 20000000]; // Various amounts

        foreach ($testSalaries as $grossCents) {
            $result = $this->service->calculateFromGross($grossCents);
            $this->assertGreaterThan(
                $result->grossSalary,
                $result->totalEmployerCost,
                "Employer cost should always be greater than gross for {$grossCents} cents"
            );
        }
    }
}

// LLM-CHECKPOINT
