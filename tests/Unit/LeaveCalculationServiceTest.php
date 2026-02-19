<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\Currency;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\PayrollEmployee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Mk\Payroll\Services\LeaveCalculationService;
use Tests\TestCase;

/**
 * Leave Calculation Service Unit Test
 *
 * Tests the leave deduction calculation logic including:
 * - Sick leave at 70% pay (30% deduction)
 * - Annual leave at 100% pay (no deduction)
 * - Unpaid leave at 0% pay (full deduction)
 * - Business day calculation (excluding weekends)
 * - Remaining balance calculation
 */
class LeaveCalculationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected LeaveCalculationService $service;

    protected Company $company;

    protected User $user;

    protected Currency $currency;

    protected PayrollEmployee $employee;

    protected LeaveType $annualType;

    protected LeaveType $sickType;

    protected LeaveType $unpaidType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new LeaveCalculationService();

        // Create test currency (MKD)
        $this->currency = Currency::create([
            'name' => 'Macedonian Denar',
            'code' => 'MKD',
            'symbol' => 'den',
            'precision' => 2,
            'thousand_separator' => ',',
            'decimal_separator' => '.',
            'swap_currency_symbol' => 0,
        ]);

        // Create test user and company
        $this->user = User::factory()->create();
        $this->company = Company::factory()->create([
            'name' => 'Test Company',
            'currency_id' => $this->currency->id,
        ]);

        // Create test employee with 100,000 MKD gross (in cents)
        $this->employee = PayrollEmployee::create([
            'company_id' => $this->company->id,
            'employee_number' => 'EMP001',
            'first_name' => 'Test',
            'last_name' => 'Employee',
            'email' => 'test@test.com',
            'embg' => '0101990450006',
            'bank_account_iban' => 'MK07250120000058984',
            'bank_name' => 'Test Bank',
            'employment_date' => now()->subYear(),
            'employment_type' => 'full_time',
            'base_salary_amount' => 10000000, // 100,000 MKD in cents
            'currency_id' => $this->currency->id,
            'is_active' => true,
            'creator_id' => $this->user->id,
        ]);

        // Create leave types
        $this->annualType = LeaveType::create([
            'company_id' => $this->company->id,
            'name' => 'Annual Leave',
            'name_mk' => 'Годишен одмор',
            'code' => LeaveType::CODE_ANNUAL,
            'max_days_per_year' => 20,
            'pay_percentage' => 100.00,
            'is_active' => true,
        ]);

        $this->sickType = LeaveType::create([
            'company_id' => $this->company->id,
            'name' => 'Sick Leave',
            'name_mk' => 'Боледување',
            'code' => LeaveType::CODE_SICK,
            'max_days_per_year' => 30,
            'pay_percentage' => 70.00,
            'is_active' => true,
        ]);

        $this->unpaidType = LeaveType::create([
            'company_id' => $this->company->id,
            'name' => 'Unpaid Leave',
            'name_mk' => 'Неплатено отсуство',
            'code' => LeaveType::CODE_UNPAID,
            'max_days_per_year' => 30,
            'pay_percentage' => 0.00,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function test_sick_leave_deduction_at_70_percent()
    {
        // Period: a full month with 22 working days
        $periodStart = Carbon::create(2026, 3, 1);
        $periodEnd = Carbon::create(2026, 3, 31);

        // Create approved sick leave for 5 business days (Mon-Fri)
        LeaveRequest::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->sickType->id,
            'start_date' => Carbon::create(2026, 3, 9), // Monday
            'end_date' => Carbon::create(2026, 3, 13),  // Friday
            'business_days' => 5,
            'status' => LeaveRequest::STATUS_APPROVED,
        ]);

        $result = $this->service->calculateLeaveDeduction(
            $this->employee,
            $periodStart,
            $periodEnd
        );

        // 22 working days in March 2026
        // Daily rate = 10,000,000 / 22 = 454,545 (rounded)
        // Sick leave at 70% pay = 30% deduction
        // Deduction = 454,545 * 5 * 0.30 = 681,818 (approx)
        $this->assertEquals(5, $result['leave_days_taken']);
        $this->assertGreaterThan(0, $result['leave_deduction_amount']);

        // Verify the deduction is approximately 30% of 5 daily rates
        $workingDays = $this->service->calculateBusinessDays($periodStart, $periodEnd);
        $dailyRate = (int) round(10000000 / $workingDays);
        $expectedDeduction = (int) round($dailyRate * 5 * 0.30);
        $this->assertEquals($expectedDeduction, $result['leave_deduction_amount']);

        // Verify details
        $this->assertCount(1, $result['details']);
        $this->assertEquals(LeaveType::CODE_SICK, $result['details'][0]['leave_type_code']);
        $this->assertEquals(70.00, $result['details'][0]['pay_percentage']);
    }

    /** @test */
    public function test_annual_leave_no_deduction()
    {
        $periodStart = Carbon::create(2026, 3, 1);
        $periodEnd = Carbon::create(2026, 3, 31);

        // Create approved annual leave (100% pay = no deduction)
        LeaveRequest::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->annualType->id,
            'start_date' => Carbon::create(2026, 3, 9),
            'end_date' => Carbon::create(2026, 3, 13),
            'business_days' => 5,
            'status' => LeaveRequest::STATUS_APPROVED,
        ]);

        $result = $this->service->calculateLeaveDeduction(
            $this->employee,
            $periodStart,
            $periodEnd
        );

        // Annual leave at 100% pay = 0% deduction
        $this->assertEquals(5, $result['leave_days_taken']);
        $this->assertEquals(0, $result['leave_deduction_amount']);
    }

    /** @test */
    public function test_unpaid_leave_full_deduction()
    {
        $periodStart = Carbon::create(2026, 3, 1);
        $periodEnd = Carbon::create(2026, 3, 31);

        // Create approved unpaid leave (0% pay = full deduction)
        LeaveRequest::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->unpaidType->id,
            'start_date' => Carbon::create(2026, 3, 9),
            'end_date' => Carbon::create(2026, 3, 13),
            'business_days' => 5,
            'status' => LeaveRequest::STATUS_APPROVED,
        ]);

        $result = $this->service->calculateLeaveDeduction(
            $this->employee,
            $periodStart,
            $periodEnd
        );

        // Unpaid leave at 0% pay = 100% deduction
        $this->assertEquals(5, $result['leave_days_taken']);
        $this->assertGreaterThan(0, $result['leave_deduction_amount']);

        // Verify the deduction is 100% of 5 daily rates
        $workingDays = $this->service->calculateBusinessDays($periodStart, $periodEnd);
        $dailyRate = (int) round(10000000 / $workingDays);
        $expectedDeduction = (int) round($dailyRate * 5 * 1.00);
        $this->assertEquals($expectedDeduction, $result['leave_deduction_amount']);
    }

    /** @test */
    public function test_business_days_excludes_weekends()
    {
        // Monday to Friday = 5 business days
        $start = Carbon::create(2026, 3, 9); // Monday
        $end = Carbon::create(2026, 3, 13);  // Friday
        $this->assertEquals(5, $this->service->calculateBusinessDays($start, $end));

        // Monday to Sunday (next week) = 5 weekdays
        $start = Carbon::create(2026, 3, 9);  // Monday
        $end = Carbon::create(2026, 3, 15);   // Sunday
        $this->assertEquals(5, $this->service->calculateBusinessDays($start, $end));

        // Saturday to Sunday = 0 business days
        $start = Carbon::create(2026, 3, 14); // Saturday
        $end = Carbon::create(2026, 3, 15);   // Sunday
        $this->assertEquals(0, $this->service->calculateBusinessDays($start, $end));

        // Single Monday = 1 business day
        $start = Carbon::create(2026, 3, 9); // Monday
        $end = Carbon::create(2026, 3, 9);   // Monday
        $this->assertEquals(1, $this->service->calculateBusinessDays($start, $end));

        // Two full weeks (Mon-Fri + Mon-Fri) = 10 business days
        $start = Carbon::create(2026, 3, 9);  // Monday
        $end = Carbon::create(2026, 3, 20);   // Friday
        $this->assertEquals(10, $this->service->calculateBusinessDays($start, $end));

        // End before start = 0 business days
        $start = Carbon::create(2026, 3, 13); // Friday
        $end = Carbon::create(2026, 3, 9);    // Monday (before start)
        $this->assertEquals(0, $this->service->calculateBusinessDays($start, $end));
    }

    /** @test */
    public function test_remaining_balance_calculation()
    {
        // Employee has 20 days annual leave per year
        // Create approved leave for 7 days
        LeaveRequest::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->annualType->id,
            'start_date' => Carbon::create(2026, 2, 9),
            'end_date' => Carbon::create(2026, 2, 13),
            'business_days' => 5,
            'status' => LeaveRequest::STATUS_APPROVED,
        ]);

        LeaveRequest::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->annualType->id,
            'start_date' => Carbon::create(2026, 6, 1),
            'end_date' => Carbon::create(2026, 6, 2),
            'business_days' => 2,
            'status' => LeaveRequest::STATUS_APPROVED,
        ]);

        $remaining = $this->service->getRemainingBalance(
            $this->employee,
            $this->annualType,
            2026
        );

        // 20 max - 7 used = 13 remaining
        $this->assertEquals(13, $remaining);
    }

    /** @test */
    public function test_no_deduction_when_no_approved_leaves()
    {
        $periodStart = Carbon::create(2026, 3, 1);
        $periodEnd = Carbon::create(2026, 3, 31);

        $result = $this->service->calculateLeaveDeduction(
            $this->employee,
            $periodStart,
            $periodEnd
        );

        $this->assertEquals(0, $result['leave_days_taken']);
        $this->assertEquals(0, $result['leave_deduction_amount']);
        $this->assertEmpty($result['details']);
    }

    /** @test */
    public function test_pending_leaves_not_included_in_deductions()
    {
        $periodStart = Carbon::create(2026, 3, 1);
        $periodEnd = Carbon::create(2026, 3, 31);

        // Create a pending (not approved) leave request
        LeaveRequest::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->sickType->id,
            'start_date' => Carbon::create(2026, 3, 9),
            'end_date' => Carbon::create(2026, 3, 13),
            'business_days' => 5,
            'status' => LeaveRequest::STATUS_PENDING,
        ]);

        $result = $this->service->calculateLeaveDeduction(
            $this->employee,
            $periodStart,
            $periodEnd
        );

        // Pending leaves should not generate deductions
        $this->assertEquals(0, $result['leave_days_taken']);
        $this->assertEquals(0, $result['leave_deduction_amount']);
    }
}

