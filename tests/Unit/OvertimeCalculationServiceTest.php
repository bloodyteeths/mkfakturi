<?php

namespace Tests\Unit;

use Modules\Mk\Payroll\Services\OvertimeCalculationService;
use Tests\TestCase;

/**
 * P7-04: Overtime Calculation Service Tests
 */
class OvertimeCalculationServiceTest extends TestCase
{
    private OvertimeCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new OvertimeCalculationService();
    }

    /** @test */
    public function it_calculates_regular_overtime_at_135_percent()
    {
        // Gross: 60,000 MKD (6,000,000 cents), 22 working days, 10 overtime hours at 135%
        $result = $this->service->calculate(6000000, 10, 1.35, 22);

        // Hourly rate = 6,000,000 / (22 * 8) = 34,091 cents
        // Premium = 10 * 34,091 * 0.35 = 119,318 cents (~1,193 MKD)
        $this->assertGreaterThan(0, $result['overtime_amount']);
        $this->assertEquals(10.0, $result['overtime_hours']);
        $this->assertEquals(1.35, $result['multiplier']);

        // Verify exact value: hourly_rate = round(6000000 / 176) = 34091
        // premium = round(10 * 34091 * 0.35) = round(119318.5) = 119319 (rounds up)
        $expectedHourlyRate = (int) round(6000000 / (22 * 8));
        $expectedPremium = (int) round(10 * $expectedHourlyRate * 0.35);
        $this->assertEqualsWithDelta($expectedPremium, $result['overtime_amount'], 1);
    }

    /** @test */
    public function it_calculates_holiday_overtime_at_150_percent()
    {
        $result = $this->service->calculate(6000000, 10, 1.50, 22);

        // Premium = 10 * hourly_rate * 0.50
        $expectedHourlyRate = (int) round(6000000 / (22 * 8));
        $expectedPremium = (int) round(10 * $expectedHourlyRate * 0.50);
        $this->assertEquals($expectedPremium, $result['overtime_amount']);
        $this->assertEquals(1.50, $result['multiplier']);
    }

    /** @test */
    public function it_returns_zero_for_no_overtime_hours()
    {
        $result = $this->service->calculate(6000000, 0, 1.35, 22);

        $this->assertEquals(0, $result['overtime_amount']);
        $this->assertEquals(0.0, $result['overtime_hours']);
    }

    /** @test */
    public function it_returns_zero_for_zero_gross()
    {
        $result = $this->service->calculate(0, 10, 1.35, 22);

        $this->assertEquals(0, $result['overtime_amount']);
    }

    /** @test */
    public function it_clamps_multiplier_to_valid_range()
    {
        // Multiplier above 2.0 should be clamped to 2.0
        $result = $this->service->calculate(6000000, 10, 3.0, 22);
        $this->assertEquals(2.0, $result['multiplier']);

        // Multiplier below 1.0 should be clamped to 1.0
        $result = $this->service->calculate(6000000, 10, 0.5, 22);
        $this->assertEquals(1.0, $result['multiplier']);
    }

    /** @test */
    public function it_gets_correct_multiplier_for_type()
    {
        $this->assertEquals(1.35, $this->service->getMultiplier('regular'));
        $this->assertEquals(1.50, $this->service->getMultiplier('holiday'));
        $this->assertEquals(1.50, $this->service->getMultiplier('night'));
    }
}
// CLAUDE-CHECKPOINT
