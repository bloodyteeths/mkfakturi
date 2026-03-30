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
 * Implements Macedonian labor law (Закон за работни односи, Art. 112-113, 137, 146-149):
 *
 * - Annual leave: 100% pay (no deduction) — Art. 137
 * - Sick leave: 70% pay (30% deduction, employer-funded first 30 days) — Art. 112
 * - Sick leave (work injury): 100% pay from day 1 — Art. 113
 * - Maternity leave: 100% pay — 270 days
 * - Parental leave (father): 100% pay — 7 days
 * - Marriage leave: 100% pay — 3 days (Art. 146)
 * - Bereavement leave: 100% pay — 5 days (Art. 146)
 * - Blood donation: 100% pay — 2 days (Art. 146)
 * - Study/exam leave: 100% pay — 7 days (Art. 146)
 * - Moving house: 100% pay — 2 days (Art. 146)
 * - Natural disaster: 100% pay — 3 days (Art. 146)
 * - Unpaid leave: 0% pay — up to 90 days (Art. 149)
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
     * Counts weekdays only (Monday-Friday), excluding weekends and MK public holidays.
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
            if ($current->isWeekday() && !$this->isPublicHoliday($current)) {
                $businessDays++;
            }
            $current->addDay();
        }

        return $businessDays;
    }

    /**
     * Check if a given date is a Macedonian public holiday.
     *
     * @param Carbon $date The date to check
     * @return bool
     */
    public function isPublicHoliday(Carbon $date): bool
    {
        $holidays = $this->getPublicHolidays($date->year);

        return in_array($date->format('m-d'), $holidays);
    }

    /**
     * Get Macedonian public holidays for a given year.
     *
     * Returns month-day pairs (MM-DD) for fixed holidays per
     * Закон за празниците на Република Македонија.
     * Easter/Ramazan Bajram are variable — not included (TODO: add Orthodox Easter calc).
     *
     * @param int $year
     * @return array<string> Array of 'MM-DD' strings
     */
    public function getPublicHolidays(int $year): array
    {
        $holidays = config('mk.payroll.public_holidays', []);

        if (empty($holidays)) {
            // Fallback hardcoded list
            $holidays = [
                '01-01', // Нова Година
                '01-07', // Божик (Orthodox Christmas)
                '04-28', // Orthodox Good Friday (approximate — varies)
                '05-01', // Ден на трудот
                '05-24', // Св. Кирил и Методиј
                '08-02', // Ден на Републиката (Илинден)
                '09-08', // Ден на независноста
                '10-11', // Ден на народното востание
                '10-23', // Ден на македонската револуционерна борба
                '12-08', // Св. Климент Охридски
            ];
        }

        return $holidays;
    }
}

