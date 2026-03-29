<?php

namespace Tests\Unit;

use Modules\Mk\Payroll\Services\MacedonianPayrollTaxService;
use Tests\TestCase;

/**
 * Macedonian Payroll Tax Service Tests
 *
 * Tests the MK tax calculation service to ensure it correctly implements
 * Macedonian payroll tax law (2024+ rates).
 *
 * In MK, ALL contributions are deducted from gross salary:
 * - Pension (ПИО): 18.8% of gross
 * - Health (Здравство): 7.5% of gross
 * - Unemployment: 1.2% of gross
 * - Additional (professional): 0.5% of gross
 * - Total contributions: 28% of gross
 * - Income Tax: 10% flat on (Gross - contributions - personal deduction)
 * - Employer cost = Gross (no add-on)
 *
 * Verified against UJP МПИН declaration (Образец 20848268, 07.03.2026):
 * Gross 34,235 → Net 23,223 MKD
 */
class MacedonianPayrollTaxServiceTest extends TestCase
{
    private MacedonianPayrollTaxService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MacedonianPayrollTaxService();
    }

    /** @test — verified against UJP МПИН declaration */
    public function it_matches_ujp_declaration_for_34235_gross()
    {
        // From UJP Декларација за прием (Образец 20848268, 07.03.2026)
        // ФАКТУРИНО ДООЕЛ, period 02/2026, gross 34,235 MKD
        $grossCents = 3423500;
        $result = $this->service->calculateFromGross($grossCents);

        // ПИО: 18.8% of 34,235 = 6,436 (rounded to whole denar per UJP)
        $this->assertEquals(643600, $result->pensionEmployee, 'Pension should be 18.8% of gross (UJP: 6,436)');
        // Здравство: 7.5% of 34,235 = 2,568 (rounded to whole denar)
        $this->assertEquals(256800, $result->healthEmployee, 'Health should be 7.5% of gross (UJP: 2,568)');
        // Професионален: 0.5% = 171 (rounded to whole denar)
        $this->assertEquals(17100, $result->additionalContribution, 'Professional should be 0.5% (UJP: 171)');
        // Невработеност: 1.2% = 411 (rounded to whole denar)
        $this->assertEquals(41100, $result->unemployment, 'Unemployment should be 1.2% (UJP: 411)');

        // Total contributions: 9,586 (28%)
        $totalContribs = $result->getTotalEmployeeContributions();
        $this->assertEquals(958600, $totalContribs, 'Total contributions 9,586 MKD');

        // Taxable: 34,235 - 9,586 - 10,390 = 14,259
        // PIT: 1,426 (10% of 14,259 rounded to whole denar)
        // Net: 34,235 - 9,586 - 1,426 = 23,223 (matches UJP declaration exactly)
        $this->assertEquals(2322300, $result->netSalary, 'Net should be 23,223 MKD (UJP verified)');

        // Employer cost = gross (no add-on in MK)
        $this->assertEquals($grossCents, $result->totalEmployerCost, 'Employer cost = gross in MK');

        // No separate employer contributions
        $this->assertEquals(0, $result->pensionEmployer, 'No employer pension add-on in MK');
        $this->assertEquals(0, $result->healthEmployer, 'No employer health add-on in MK');
    }

    /** @test */
    public function it_calculates_correct_pension_at_full_rate()
    {
        // 18.8% of 100,000 MKD = 18,800 MKD
        $grossCents = 10000000;
        $result = $this->service->calculateFromGross($grossCents);

        $this->assertEquals(1880000, $result->pensionEmployee, 'Pension should be 18.8% of gross');
    }

    /** @test */
    public function it_calculates_correct_health_at_full_rate()
    {
        // 7.5% of 100,000 MKD = 7,500 MKD
        $grossCents = 10000000;
        $result = $this->service->calculateFromGross($grossCents);

        $this->assertEquals(750000, $result->healthEmployee, 'Health should be 7.5% of gross');
    }

    /** @test */
    public function it_calculates_correct_unemployment_contribution()
    {
        // 1.2% of 100,000 MKD = 1,200 MKD
        $grossCents = 10000000;
        $result = $this->service->calculateFromGross($grossCents);

        $this->assertEquals(120000, $result->unemployment, 'Unemployment should be 1.2% of gross');
    }

    /** @test */
    public function it_calculates_correct_additional_contribution()
    {
        // 0.5% of 100,000 MKD = 500 MKD
        $grossCents = 10000000;
        $result = $this->service->calculateFromGross($grossCents);

        $this->assertEquals(50000, $result->additionalContribution, 'Additional should be 0.5% of gross');
    }

    /** @test */
    public function it_calculates_correct_total_contributions_28_percent()
    {
        // Total: 18.8% + 7.5% + 1.2% + 0.5% = 28%
        // 28% of 100,000 MKD = 28,000 MKD
        $grossCents = 10000000;
        $result = $this->service->calculateFromGross($grossCents);

        $totalContributions = $result->getTotalEmployeeContributions();
        $this->assertEquals(2800000, $totalContributions, 'Total contributions should be 28% of gross');
    }

    /** @test */
    public function it_calculates_correct_taxable_base()
    {
        // Taxable = max(0, Gross - 28% contributions - personal deduction)
        // max(0, 10,000,000 - 2,800,000 - 1,039,000) = 6,161,000
        $grossCents = 10000000;
        $result = $this->service->calculateFromGross($grossCents);

        $this->assertEquals(6161000, $result->taxableBase, 'Taxable base = gross - 28% - 10,390');
    }

    /** @test */
    public function it_calculates_correct_income_tax()
    {
        // 10% of 6,161,000 = 616,100
        $grossCents = 10000000;
        $result = $this->service->calculateFromGross($grossCents);

        $this->assertEquals(616100, $result->incomeTax, 'Income tax should be 10% of taxable base');
    }

    /** @test */
    public function it_calculates_correct_net_salary()
    {
        // Net = Gross - contributions - income tax
        // 10,000,000 - 2,800,000 - 616,100 = 6,583,900
        $grossCents = 10000000;
        $result = $this->service->calculateFromGross($grossCents);

        $this->assertEquals(6583900, $result->netSalary, 'Net salary should be 65,839 MKD');
    }

    /** @test */
    public function it_sets_employer_cost_equal_to_gross()
    {
        // In MK, employer cost = gross (no add-on)
        $grossCents = 10000000;
        $result = $this->service->calculateFromGross($grossCents);

        $this->assertEquals($grossCents, $result->totalEmployerCost, 'Employer cost = gross in MK');
    }

    /** @test */
    public function it_has_zero_employer_contributions()
    {
        $grossCents = 10000000;
        $result = $this->service->calculateFromGross($grossCents);

        $this->assertEquals(0, $result->pensionEmployer, 'No employer pension in MK model');
        $this->assertEquals(0, $result->healthEmployer, 'No employer health in MK model');
        $this->assertEquals(0, $result->getTotalEmployerContributions(), 'No employer contributions');
    }

    /** @test */
    public function it_matches_full_example_calculation()
    {
        // Gross: 100,000 MKD
        // Contributions: 28,000 (28%)
        // Taxable: 100,000 - 28,000 - 10,390 = 61,610
        // PIT: 6,161
        // Net: 100,000 - 28,000 - 6,161 = 65,839
        // Employer cost: 100,000

        $grossCents = 10000000;
        $result = $this->service->calculateFromGross($grossCents);

        $this->assertEquals(10000000, $result->grossSalary);
        $this->assertEquals(2800000, $result->getTotalEmployeeContributions());
        $this->assertEquals(6161000, $result->taxableBase);
        $this->assertEquals(616100, $result->incomeTax);
        $this->assertEquals(6583900, $result->netSalary);
        $this->assertEquals(10000000, $result->totalEmployerCost);
    }

    /** @test */
    public function it_calculates_correct_total_deductions()
    {
        // Total deductions = contributions + income tax
        // 2,800,000 + 616,100 = 3,416,100
        $grossCents = 10000000;
        $result = $this->service->calculateFromGross($grossCents);

        $this->assertEquals(3416100, $result->totalEmployeeDeductions);
    }

    /** @test */
    public function it_clamps_low_salary_to_minimum_contribution_base()
    {
        // 20,000 MKD → contribution base clamped to 31,577 MKD
        $grossCents = 2000000;
        $result = $this->service->calculateFromGross($grossCents);

        // Contributions on clamped base (3,157,700) with whole-denar rounding:
        // 18.8% = 593,600, 7.5% = 236,800, 1.2% = 37,900, 0.5% = 15,800
        // Total = 884,100
        $totalContribs = $result->getTotalEmployeeContributions();
        $this->assertEquals(884100, $totalContribs, 'Contributions on clamped base');

        // Taxable: max(0, 2,000,000 - 884,100 - 1,039,000) = 76,900
        $this->assertEquals(76900, $result->taxableBase);

        // PIT: 10% of 76,900 = 7,690 → rounded to 7,700
        $this->assertEquals(7700, $result->incomeTax);

        // Net: 2,000,000 - 884,100 - 7,700 = 1,108,200
        $this->assertEquals(1108200, $result->netSalary);
    }

    /** @test */
    public function it_calculates_correctly_for_high_salary()
    {
        // 200,000 MKD — within max contribution base
        $grossCents = 20000000;
        $result = $this->service->calculateFromGross($grossCents);

        // 28% of 200,000 = 56,000
        $this->assertEquals(5600000, $result->getTotalEmployeeContributions());

        // Taxable: 200,000 - 56,000 - 10,390 = 133,610
        $this->assertEquals(13361000, $result->taxableBase);

        // PIT: 13,361
        $this->assertEquals(1336100, $result->incomeTax);

        // Net: 200,000 - 56,000 - 13,361 = 130,639
        $this->assertEquals(13063900, $result->netSalary);

        // Employer cost = gross
        $this->assertEquals(20000000, $result->totalEmployerCost);
    }

    /** @test */
    public function it_ensures_net_is_always_less_than_gross()
    {
        $testSalaries = [3000000, 5000000, 10000000, 20000000];

        foreach ($testSalaries as $grossCents) {
            $result = $this->service->calculateFromGross($grossCents);
            $this->assertLessThan(
                $result->grossSalary,
                $result->netSalary,
                "Net should be less than gross for {$grossCents} cents"
            );
        }
    }

    /** @test */
    public function it_ensures_employer_cost_equals_gross()
    {
        $testSalaries = [3000000, 5000000, 10000000, 20000000];

        foreach ($testSalaries as $grossCents) {
            $result = $this->service->calculateFromGross($grossCents);
            $this->assertEquals(
                $result->grossSalary,
                $result->totalEmployerCost,
                "Employer cost should equal gross in MK model for {$grossCents} cents"
            );
        }
    }

    /** @test */
    public function it_provides_net_salary_shortcut_method()
    {
        $grossCents = 10000000;
        $netSalary = $this->service->calculateNetSalary($grossCents);

        $this->assertEquals(6583900, $netSalary, 'Net salary shortcut should return 65,839 MKD');
    }

    /** @test */
    public function it_provides_employer_cost_shortcut_method()
    {
        $grossCents = 10000000;
        $employerCost = $this->service->getEmployerCost($grossCents);

        $this->assertEquals(10000000, $employerCost, 'Employer cost = gross in MK');
    }

    /** @test */
    public function it_provides_tax_breakdown_array()
    {
        $grossCents = 10000000;
        $breakdown = $this->service->getTaxBreakdown($grossCents);

        $this->assertIsArray($breakdown);
        $this->assertArrayHasKey('grossSalary', $breakdown);
        $this->assertArrayHasKey('netSalary', $breakdown);
        $this->assertArrayHasKey('incomeTax', $breakdown);
        $this->assertEquals(10000000, $breakdown['grossSalary']);
        $this->assertEquals(6583900, $breakdown['netSalary']);
    }

    /** @test */
    public function it_validates_valid_embg()
    {
        $this->assertTrue($this->service->validateEmbg('0101990450006'));
    }

    /** @test */
    public function it_rejects_embg_with_invalid_length()
    {
        $this->assertFalse($this->service->validateEmbg('123456789012'));
        $this->assertFalse($this->service->validateEmbg('12345678901234'));
    }

    /** @test */
    public function it_rejects_embg_with_invalid_check_digit()
    {
        $this->assertFalse($this->service->validateEmbg('0101990450007'));
    }

    /** @test */
    public function it_handles_rounding_correctly_for_odd_amounts()
    {
        $grossCents = 5555555;
        $result = $this->service->calculateFromGross($grossCents);

        $this->assertIsInt($result->grossSalary);
        $this->assertIsInt($result->netSalary);
        $this->assertIsInt($result->incomeTax);
        $this->assertGreaterThan(0, $result->netSalary);
        $this->assertLessThan($result->grossSalary, $result->netSalary);
    }
}

// CLAUDE-CHECKPOINT
