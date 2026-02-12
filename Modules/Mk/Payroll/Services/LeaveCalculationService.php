<?php

namespace Modules\Mk\Payroll\Services;

use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\PayrollEmployee;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Leave Calculation Service
 *
 * Calculates leave deductions for payroll runs and tracks leave balances.
 * Implements Macedonian labor law requirements:
 * - Annual leave: 100% pay (no deduction)
 * - Sick leave: 70% pay (30% deduction from gross)
 * - Maternity leave: 100% pay (no deduction)
 * - Unpaid leave: 0% pay (full deduction from gross)
 */
class LeaveCalculationService
{
    /**
     * Calculate leave deductions for an employee in a payroll period.
     *
     * Queries approved leave requests overlapping the period, then calculates
     * the deduction based on daily rate and leave type pay percentage.
     *
     * @param PayrollEmployee $employee The employee to calculate for
     * @param Carbon $periodStart Start of the payroll period
     * @param Carbon $periodEnd End of the payroll period
     * @return array{leave_days_taken: int, leave_deduction_amount: int, details: array}
     */
    public function calculateLeaveDeduction(
        PayrollEmployee $employee,
        Carbon $periodStart,
        Carbon $periodEnd
    ): array {
        $approvedLeaves = LeaveRequest::approved()
            ->forEmployee($employee->id)
            ->forPeriod($periodStart, $periodEnd)
            ->with('leaveType')
            ->get();

        if ($approvedLeaves->isEmpty()) {
            return [
                'leave_days_taken' => 0,
                'leave_deduction_amount' => 0,
                'details' => [],
            ];
        }

        // Calculate working days in the period for daily rate
        $workingDaysInPeriod = $this->calculateBusinessDays($periodStart, $periodEnd);

        if ($workingDaysInPeriod <= 0) {
            return [
                'leave_days_taken' => 0,
                'leave_deduction_amount' => 0,
                'details' => [],
            ];
        }

        // Get employee's gross salary (in cents)
        $grossSalary = $employee->base_salary_amount ?? 0;
        $dailyRate = (int) round($grossSalary / $workingDaysInPeriod);

        $totalLeaveDays = 0;
        $totalDeduction = 0;
        $details = [];

        foreach ($approvedLeaves as $leave) {
            // Calculate overlapping business days within the period
            $overlapStart = $leave->start_date->max($periodStart);
            $overlapEnd = $leave->end_date->min($periodEnd);
            $businessDaysInPeriod = $this->calculateBusinessDays($overlapStart, $overlapEnd);

            if ($businessDaysInPeriod <= 0) {
                continue;
            }

            $payPercentage = $leave->leaveType->pay_percentage ?? 100.00;
            $deductionRate = 1 - ($payPercentage / 100);
            $deductionAmount = (int) round($dailyRate * $businessDaysInPeriod * $deductionRate);

            $totalLeaveDays += $businessDaysInPeriod;
            $totalDeduction += $deductionAmount;

            $details[] = [
                'leave_request_id' => $leave->id,
                'leave_type_code' => $leave->leaveType->code,
                'leave_type_name' => $leave->leaveType->name,
                'days_in_period' => $businessDaysInPeriod,
                'pay_percentage' => $payPercentage,
                'deduction_amount' => $deductionAmount,
            ];
        }

        Log::info('Leave deduction calculated', [
            'employee_id' => $employee->id,
            'period' => "{$periodStart->toDateString()} - {$periodEnd->toDateString()}",
            'leave_days_taken' => $totalLeaveDays,
            'leave_deduction_amount' => $totalDeduction,
        ]);

        return [
            'leave_days_taken' => $totalLeaveDays,
            'leave_deduction_amount' => $totalDeduction,
            'details' => $details,
        ];
    }

    /**
     * Get remaining leave balance for an employee and leave type in a year.
     *
     * @param PayrollEmployee $employee The employee to check
     * @param LeaveType $type The leave type to check
     * @param int $year The year to check
     * @return int Remaining days available
     */
    public function getRemainingBalance(
        PayrollEmployee $employee,
        LeaveType $type,
        int $year
    ): int {
        $yearStart = Carbon::create($year, 1, 1);
        $yearEnd = Carbon::create($year, 12, 31);

        $usedDays = LeaveRequest::approved()
            ->forEmployee($employee->id)
            ->where('leave_type_id', $type->id)
            ->where(function ($query) use ($yearStart, $yearEnd) {
                $query->whereBetween('start_date', [$yearStart, $yearEnd])
                    ->orWhereBetween('end_date', [$yearStart, $yearEnd]);
            })
            ->sum('business_days');

        $remaining = $type->max_days_per_year - (int) $usedDays;

        return max(0, $remaining);
    }

    /**
     * Calculate the number of business days between two dates.
     *
     * Counts weekdays only (Monday-Friday), excluding weekends.
     *
     * @param Carbon $start Start date (inclusive)
     * @param Carbon $end End date (inclusive)
     * @return int Number of business days
     */
    public function calculateBusinessDays(Carbon $start, Carbon $end): int
    {
        if ($start->gt($end)) {
            return 0;
        }

        $businessDays = 0;
        $current = $start->copy();

        while ($current->lte($end)) {
            if ($current->isWeekday()) {
                $businessDays++;
            }
            $current->addDay();
        }

        return $businessDays;
    }
}

// CLAUDE-CHECKPOINT
