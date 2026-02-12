<?php

namespace Tests\Unit;

use Modules\Mk\Payroll\Services\MacedonianPayrollTaxService;
use Tests\TestCase;

/**
 * P7-05: Contribution Base Cap Tests
 *
 * Verifies that social contributions are clamped to min/max base limits:
 * - Min: MKD 31,577 (50% of national average MKD 63,154) = 3,157,700 cents
 * - Max: MKD 1,010,464 (16x national average) = 101,046,400 cents
 */
class ContributionBaseCapTest extends TestCase
{
    private MacedonianPayrollTaxService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MacedonianPayrollTaxService();
    }

    /** @test */
    public function it_clamps_low_salary_to_minimum_base()
    {
        // Gross: 20,000 MKD (2,000,000 cents) — below minimum 31,577
        $result = $this->service->calculateFromGross(2000000);

        // Contributions should be calculated on MIN base (3,157,700 cents), not actual gross
        // Pension employee: 3,157,700 * 0.09 = 284,193
        $minBase = 3157700;
        $expectedPension = (int) round($minBase * 0.09);
        $this->assertEquals($expectedPension, $result->pensionEmployee);

        // But gross and net are based on actual salary
        $this->assertEquals(2000000, $result->grossSalary);
    }

    /** @test */
    public function it_clamps_high_salary_to_maximum_base()
    {
        // Gross: 1,200,000 MKD (120,000,000 cents) — above maximum 1,010,464
        $result = $this->service->calculateFromGross(120000000);

        // Contributions should be calculated on MAX base (101,046,400 cents)
        $maxBase = 101046400;
        $expectedPension = (int) round($maxBase * 0.09);
        $this->assertEquals($expectedPension, $result->pensionEmployee);

        // Employer pension also capped
        $expectedEmployerPension = (int) round($maxBase * 0.09);
        $this->assertEquals($expectedEmployerPension, $result->pensionEmployer);
    }

    /** @test */
    public function it_does_not_clamp_normal_salary()
    {
        // Gross: 60,000 MKD (6,000,000 cents) — within range
        $result = $this->service->calculateFromGross(6000000);

        // Contributions should be calculated on actual gross
        $expectedPension = (int) round(6000000 * 0.09);
        $this->assertEquals($expectedPension, $result->pensionEmployee);
    }

    /** @test */
    public function clamp_method_returns_correct_values()
    {
        // Below min
        $this->assertEquals(3157700, $this->service->clampToContributionBase(1000000));

        // Above max
        $this->assertEquals(101046400, $this->service->clampToContributionBase(200000000));

        // Within range
        $this->assertEquals(6000000, $this->service->clampToContributionBase(6000000));

        // Exactly at min
        $this->assertEquals(3157700, $this->service->clampToContributionBase(3157700));

        // Exactly at max
        $this->assertEquals(101046400, $this->service->clampToContributionBase(101046400));
    }

    /** @test */
    public function income_tax_is_on_actual_gross_not_clamped_base()
    {
        // High salary: 1,200,000 MKD
        $result = $this->service->calculateFromGross(120000000);

        // Taxable base = gross - employee contributions (contributions on capped base)
        // Income tax = 10% of taxable base (taxable base uses actual gross minus capped contributions)
        $this->assertGreaterThan(0, $result->incomeTax);

        // Tax should be based on actual gross minus capped contributions
        $maxBase = 101046400;
        $cappedContributions = (int) round($maxBase * 0.09)   // pension
            + (int) round($maxBase * 0.0375) // health
            + (int) round($maxBase * 0.012)  // unemployment
            + (int) round($maxBase * 0.005); // additional
        $expectedTaxableBase = 120000000 - $cappedContributions;
        $expectedTax = (int) round($expectedTaxableBase * 0.10);
        $this->assertEquals($expectedTax, $result->incomeTax);
    }
}
// CLAUDE-CHECKPOINT
