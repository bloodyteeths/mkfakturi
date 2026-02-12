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
}
// CLAUDE-CHECKPOINT
