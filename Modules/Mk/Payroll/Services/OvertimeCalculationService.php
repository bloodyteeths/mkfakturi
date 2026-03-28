<?php

namespace Modules\Mk\Payroll\Services;

use Illuminate\Support\Facades\Log;

/**
 * P7-04: Overtime Calculation Service
 *
 * Calculates overtime premium amounts according to Macedonian labor law.
 *
 * Rates (Закон за работни односи):
 * - Regular overtime: 135% of base hourly rate
 * - Holiday/night overtime: 150% of base hourly rate
 *
 * The overtime premium is the additional amount above the normal hourly rate.
 * For 135%: premium = hours * hourly_rate * 0.35
 * For 150%: premium = hours * hourly_rate * 0.50
 *
 * All monetary values are in cents (integer).
 */
class OvertimeCalculationService
{
    /** Standard working hours per day */
    private const HOURS_PER_DAY = 8;

    /** Default working days per month */
    private const DEFAULT_WORKING_DAYS = 22;

    /** Regular overtime multiplier (135%) */
    public const MULTIPLIER_REGULAR = 1.35;

    /** Holiday/night overtime multiplier (150%) */
    public const MULTIPLIER_HOLIDAY = 1.50;

    /**
     * Calculate overtime premium amount.
     *
     * @param int $grossSalaryCents Base gross salary in cents (before overtime)
     * @param float $overtimeHours Number of overtime hours worked
     * @param float $multiplier Overtime rate multiplier (1.35 or 1.50)
     * @param int $workingDays Working days in the period (for hourly rate calc)
     * @return array{overtime_amount: int, hourly_rate: int, overtime_hours: float, multiplier: float}
     */
    public function calculate(
        int $grossSalaryCents,
        float $overtimeHours,
        float $multiplier = self::MULTIPLIER_REGULAR,
        int $workingDays = self::DEFAULT_WORKING_DAYS
    ): array {
        if ($overtimeHours <= 0 || $grossSalaryCents <= 0) {
            return [
                'overtime_amount' => 0,
                'hourly_rate' => 0,
                'overtime_hours' => 0.0,
                'multiplier' => $multiplier,
            ];
        }

        // Clamp multiplier to valid range
        $multiplier = max(1.0, min(2.0, $multiplier));

        // Calculate base hourly rate: gross / (working_days * hours_per_day)
        $totalHours = $workingDays * self::HOURS_PER_DAY;
        $hourlyRate = (int) round($grossSalaryCents / $totalHours);

        // Overtime premium = hours * hourly_rate * (multiplier - 1)
        // e.g., 10 hours at 135% → 10 * rate * 0.35 = premium only
        $premiumFactor = $multiplier - 1.0;
        $overtimeAmount = (int) round($overtimeHours * $hourlyRate * $premiumFactor);

        Log::debug('Overtime calculated', [
            'gross' => $grossSalaryCents,
            'hours' => $overtimeHours,
            'multiplier' => $multiplier,
            'hourly_rate' => $hourlyRate,
            'premium' => $overtimeAmount,
        ]);

        return [
            'overtime_amount' => $overtimeAmount,
            'hourly_rate' => $hourlyRate,
            'overtime_hours' => $overtimeHours,
            'multiplier' => $multiplier,
        ];
    }

    /**
     * Get the configured overtime multiplier from config.
     *
     * @param string $type 'regular' or 'holiday'
     * @return float
     */
    public function getMultiplier(string $type = 'regular'): float
    {
        return match ($type) {
            'holiday', 'night' => config('mk.payroll.overtime_holiday_multiplier', self::MULTIPLIER_HOLIDAY),
            default => config('mk.payroll.overtime_regular_multiplier', self::MULTIPLIER_REGULAR),
        };
    }

    /**
     * Calculate night work premium.
     *
     * Night work hours (22:00-06:00) receive a 35% premium per Art. 105
     * of Закон за работни односи. This is separate from overtime —
     * an employee can work night hours within their regular shift.
     *
     * @param int $grossSalaryCents Base gross salary in cents
     * @param float $nightHours Number of night hours worked (22:00-06:00)
     * @param int $workingDays Working days in the period
     * @return array{night_amount: int, hourly_rate: int, night_hours: float}
     */
    public function calculateNightWork(
        int $grossSalaryCents,
        float $nightHours,
        int $workingDays = self::DEFAULT_WORKING_DAYS
    ): array {
        if ($nightHours <= 0 || $grossSalaryCents <= 0) {
            return [
                'night_amount' => 0,
                'hourly_rate' => 0,
                'night_hours' => 0.0,
            ];
        }

        $nightMultiplier = (float) config('mk.payroll.night_work_multiplier', self::MULTIPLIER_REGULAR);
        $totalHours = $workingDays * self::HOURS_PER_DAY;
        $hourlyRate = (int) round($grossSalaryCents / $totalHours);

        // Night premium = hours * hourly_rate * (multiplier - 1)
        $premiumFactor = $nightMultiplier - 1.0;
        $nightAmount = (int) round($nightHours * $hourlyRate * $premiumFactor);

        Log::debug('Night work calculated', [
            'gross' => $grossSalaryCents,
            'night_hours' => $nightHours,
            'multiplier' => $nightMultiplier,
            'hourly_rate' => $hourlyRate,
            'premium' => $nightAmount,
        ]);

        return [
            'night_amount' => $nightAmount,
            'hourly_rate' => $hourlyRate,
            'night_hours' => $nightHours,
        ];
    }

    /**
     * Calculate seniority bonus (минат труд).
     *
     * Per Македонски колективен договор, employees receive 0.5% of gross
     * salary for each completed year of service. This is mandatory.
     *
     * @param int $grossSalaryCents Base gross salary in cents
     * @param int $yearsOfService Completed years of service
     * @return array{seniority_bonus: int, seniority_years: int, rate: float}
     */
    public function calculateSeniorityBonus(int $grossSalaryCents, int $yearsOfService): array
    {
        if ($yearsOfService <= 0 || $grossSalaryCents <= 0) {
            return [
                'seniority_bonus' => 0,
                'seniority_years' => 0,
                'rate' => 0.0,
            ];
        }

        $ratePerYear = (float) config('mk.payroll.seniority_rate_per_year', 0.005);
        $totalRate = $ratePerYear * $yearsOfService;
        $seniorityBonus = (int) round($grossSalaryCents * $totalRate);

        Log::debug('Seniority bonus calculated', [
            'gross' => $grossSalaryCents,
            'years' => $yearsOfService,
            'rate_per_year' => $ratePerYear,
            'total_rate' => $totalRate,
            'bonus' => $seniorityBonus,
        ]);

        return [
            'seniority_bonus' => $seniorityBonus,
            'seniority_years' => $yearsOfService,
            'rate' => $totalRate,
        ];
    }
}
