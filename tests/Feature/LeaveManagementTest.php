<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Currency;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\PayrollEmployee;
use App\Models\PayrollRun;
use App\Models\SalaryStructure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Leave Management Feature Test
 *
 * Tests the leave management API endpoints including:
 * - Creating leave requests
 * - Overlap detection
 * - Approval/rejection workflow
 * - Balance calculations
 * - Payroll deduction integration
 */
class LeaveManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected User $user;

    protected Currency $currency;

    protected PayrollEmployee $employee;

    protected LeaveType $annualLeaveType;

    protected LeaveType $sickLeaveType;

    protected LeaveType $unpaidLeaveType;

    protected function setUp(): void
    {
        parent::setUp();

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

        // Associate user with company
        $this->user->companies()->attach($this->company->id, [
            'role' => 'owner',
        ]);

        // Create test employee
        $this->employee = PayrollEmployee::create([
            'company_id' => $this->company->id,
            'employee_number' => 'EMP001',
            'first_name' => 'Marko',
            'last_name' => 'Markovski',
            'email' => 'marko@test.com',
            'embg' => '0101990450006',
            'bank_account_iban' => 'MK07250120000058984',
            'bank_name' => 'Stopanska Banka',
            'employment_date' => now()->subYear(),
            'employment_type' => 'full_time',
            'department' => 'Engineering',
            'position' => 'Software Developer',
            'base_salary_amount' => 10000000, // 100,000 MKD in cents
            'currency_id' => $this->currency->id,
            'is_active' => true,
            'creator_id' => $this->user->id,
        ]);

        // Create salary structure
        SalaryStructure::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'effective_from' => now()->startOfMonth(),
            'effective_to' => null,
            'gross_salary' => 10000000,
            'transport_allowance' => 0,
            'meal_allowance' => 0,
            'other_allowances' => [],
            'is_current' => true,
        ]);

        // Create leave types
        $this->annualLeaveType = LeaveType::create([
            'company_id' => $this->company->id,
            'name' => 'Annual Leave',
            'name_mk' => 'Годишен одмор',
            'code' => LeaveType::CODE_ANNUAL,
            'max_days_per_year' => 20,
            'pay_percentage' => 100.00,
            'is_active' => true,
        ]);

        $this->sickLeaveType = LeaveType::create([
            'company_id' => $this->company->id,
            'name' => 'Sick Leave',
            'name_mk' => 'Боледување',
            'code' => LeaveType::CODE_SICK,
            'max_days_per_year' => 30,
            'pay_percentage' => 70.00,
            'is_active' => true,
        ]);

        $this->unpaidLeaveType = LeaveType::create([
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
    public function test_can_create_leave_request()
    {
        $startDate = now()->addWeek()->startOfWeek()->toDateString(); // Next Monday
        $endDate = now()->addWeek()->startOfWeek()->addDays(4)->toDateString(); // Next Friday

        $response = $this->actingAs($this->user)
            ->withHeaders(['company' => $this->company->id])
            ->postJson('/api/v1/admin/leave-requests', [
                'employee_id' => $this->employee->id,
                'leave_type_id' => $this->annualLeaveType->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'reason' => 'Family vacation',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'company_id',
                    'employee_id',
                    'leave_type_id',
                    'start_date',
                    'end_date',
                    'business_days',
                    'status',
                    'reason',
                ],
                'message',
            ]);

        $this->assertDatabaseHas('leave_requests', [
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->annualLeaveType->id,
            'status' => LeaveRequest::STATUS_PENDING,
            'reason' => 'Family vacation',
        ]);

        $data = $response->json('data');
        $this->assertEquals(5, $data['business_days']); // Monday-Friday = 5 days
        $this->assertEquals(LeaveRequest::STATUS_PENDING, $data['status']);
    }

    /** @test */
    public function test_cannot_create_overlapping_leave()
    {
        $startDate = now()->addWeek()->startOfWeek()->toDateString();
        $endDate = now()->addWeek()->startOfWeek()->addDays(4)->toDateString();

        // Create first leave request
        LeaveRequest::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->annualLeaveType->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'business_days' => 5,
            'status' => LeaveRequest::STATUS_APPROVED,
        ]);

        // Try to create overlapping leave
        $response = $this->actingAs($this->user)
            ->withHeaders(['company' => $this->company->id])
            ->postJson('/api/v1/admin/leave-requests', [
                'employee_id' => $this->employee->id,
                'leave_type_id' => $this->sickLeaveType->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function test_can_approve_leave_request()
    {
        $startDate = now()->addWeek()->startOfWeek()->toDateString();
        $endDate = now()->addWeek()->startOfWeek()->addDays(4)->toDateString();

        $leaveRequest = LeaveRequest::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->annualLeaveType->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'business_days' => 5,
            'status' => LeaveRequest::STATUS_PENDING,
        ]);

        $response = $this->actingAs($this->user)
            ->withHeaders(['company' => $this->company->id])
            ->postJson("/api/v1/admin/leave-requests/{$leaveRequest->id}/approve");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Leave request approved successfully.',
            ]);

        $leaveRequest->refresh();
        $this->assertEquals(LeaveRequest::STATUS_APPROVED, $leaveRequest->status);
        $this->assertEquals($this->user->id, $leaveRequest->approved_by);
        $this->assertNotNull($leaveRequest->approved_at);
    }

    /** @test */
    public function test_can_reject_leave_request_with_reason()
    {
        $startDate = now()->addWeek()->startOfWeek()->toDateString();
        $endDate = now()->addWeek()->startOfWeek()->addDays(4)->toDateString();

        $leaveRequest = LeaveRequest::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->annualLeaveType->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'business_days' => 5,
            'status' => LeaveRequest::STATUS_PENDING,
        ]);

        $response = $this->actingAs($this->user)
            ->withHeaders(['company' => $this->company->id])
            ->postJson("/api/v1/admin/leave-requests/{$leaveRequest->id}/reject", [
                'rejection_reason' => 'Critical project deadline this week',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Leave request rejected successfully.',
            ]);

        $leaveRequest->refresh();
        $this->assertEquals(LeaveRequest::STATUS_REJECTED, $leaveRequest->status);
        $this->assertEquals('Critical project deadline this week', $leaveRequest->rejection_reason);
        $this->assertEquals($this->user->id, $leaveRequest->approved_by);
    }

    /** @test */
    public function test_balance_calculation_returns_remaining_days()
    {
        // Create an approved 5-day annual leave
        LeaveRequest::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->annualLeaveType->id,
            'start_date' => now()->startOfYear()->addDays(7)->startOfWeek()->toDateString(),
            'end_date' => now()->startOfYear()->addDays(7)->startOfWeek()->addDays(4)->toDateString(),
            'business_days' => 5,
            'status' => LeaveRequest::STATUS_APPROVED,
        ]);

        $response = $this->actingAs($this->user)
            ->withHeaders(['company' => $this->company->id])
            ->getJson("/api/v1/admin/leave-requests/balance/{$this->employee->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'leave_type_id',
                        'leave_type_code',
                        'leave_type_name',
                        'max_days_per_year',
                        'used_days',
                        'remaining_days',
                        'pay_percentage',
                    ],
                ],
                'meta',
            ]);

        // Find annual leave balance in response
        $annualBalance = collect($response->json('data'))
            ->firstWhere('leave_type_code', LeaveType::CODE_ANNUAL);

        $this->assertEquals(20, $annualBalance['max_days_per_year']);
        $this->assertEquals(5, $annualBalance['used_days']);
        $this->assertEquals(15, $annualBalance['remaining_days']);
    }

    /** @test */
    public function test_payroll_run_deducts_sick_leave_at_70_percent()
    {
        // Create approved sick leave for 5 business days in the current month
        $periodStart = now()->startOfMonth();
        $periodEnd = now()->endOfMonth();

        // Find a weekday range in this month
        $leaveStart = $periodStart->copy()->addDays(7);
        while (!$leaveStart->isWeekday()) {
            $leaveStart->addDay();
        }
        $leaveEnd = $leaveStart->copy();
        $daysAdded = 0;
        while ($daysAdded < 4) {
            $leaveEnd->addDay();
            if ($leaveEnd->isWeekday()) {
                $daysAdded++;
            }
        }

        LeaveRequest::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->sickLeaveType->id,
            'start_date' => $leaveStart->toDateString(),
            'end_date' => $leaveEnd->toDateString(),
            'business_days' => 5,
            'status' => LeaveRequest::STATUS_APPROVED,
        ]);

        // Verify the leave calculation service produces a deduction
        $leaveService = app(\Modules\Mk\Payroll\Services\LeaveCalculationService::class);
        $result = $leaveService->calculateLeaveDeduction(
            $this->employee,
            $periodStart,
            $periodEnd
        );

        // Sick leave at 70% pay means 30% deduction
        $this->assertGreaterThan(0, $result['leave_days_taken']);
        $this->assertGreaterThan(0, $result['leave_deduction_amount']);
        $this->assertEquals(5, $result['leave_days_taken']);

        // Verify deduction details
        $this->assertNotEmpty($result['details']);
        $detail = $result['details'][0];
        $this->assertEquals(LeaveType::CODE_SICK, $detail['leave_type_code']);
        $this->assertEquals(70.00, $detail['pay_percentage']);
        $this->assertGreaterThan(0, $detail['deduction_amount']);
    }
}

