<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class FixedAsset extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'acquisition_date' => 'date',
        'disposal_date' => 'date',
        'acquisition_cost' => 'decimal:2',
        'residual_value' => 'decimal:2',
        'disposal_amount' => 'decimal:2',
    ];

    // Categories matching Macedonian chart of accounts
    const CATEGORY_REAL_ESTATE = 'real_estate';
    const CATEGORY_BUILDINGS = 'buildings';
    const CATEGORY_EQUIPMENT = 'equipment';
    const CATEGORY_VEHICLES = 'vehicles';
    const CATEGORY_COMPUTERS_SOFTWARE = 'computers_software';
    const CATEGORY_OTHER = 'other';

    const STATUS_ACTIVE = 'active';
    const STATUS_DISPOSED = 'disposed';
    const STATUS_FULLY_DEPRECIATED = 'fully_depreciated';

    const METHOD_STRAIGHT_LINE = 'straight_line';
    const METHOD_DECLINING_BALANCE = 'declining_balance';

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function depreciationAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'depreciation_account_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Monthly depreciation amount (straight-line)
     */
    public function getMonthlyDepreciationAttribute(): float
    {
        if ($this->useful_life_months <= 0) {
            return 0;
        }

        return round(($this->acquisition_cost - $this->residual_value) / $this->useful_life_months, 2);
    }

    /**
     * Annual depreciation amount (straight-line)
     */
    public function getAnnualDepreciationAttribute(): float
    {
        return round($this->monthly_depreciation * 12, 2);
    }

    /**
     * Accumulated depreciation as of a given date
     */
    public function getAccumulatedDepreciation(?Carbon $asOfDate = null): float
    {
        $asOfDate = $asOfDate ?? Carbon::now();

        if ($asOfDate->lt($this->acquisition_date)) {
            return 0;
        }

        $endDate = $this->disposal_date && $this->disposal_date->lt($asOfDate) ? $this->disposal_date : $asOfDate;
        $months = $this->acquisition_date->diffInMonths($endDate);
        $maxMonths = $this->useful_life_months;

        if ($months > $maxMonths) {
            $months = $maxMonths;
        }

        if ($this->depreciation_method === self::METHOD_DECLINING_BALANCE) {
            return $this->calculateDecliningBalanceDepreciation($months);
        }

        // Straight-line
        return round($this->monthly_depreciation * $months, 2);
    }

    /**
     * Net book value as of a given date
     */
    public function getNetBookValue(?Carbon $asOfDate = null): float
    {
        $accumulated = $this->getAccumulatedDepreciation($asOfDate);

        return round($this->acquisition_cost - $accumulated, 2);
    }

    /**
     * Depreciation rate per year (percentage)
     */
    public function getDepreciationRateAttribute(): float
    {
        if ($this->useful_life_months <= 0) {
            return 0;
        }

        return round(12 / $this->useful_life_months * 100, 2);
    }

    /**
     * Calculate declining balance depreciation
     */
    protected function calculateDecliningBalanceDepreciation(int $months): float
    {
        if ($this->useful_life_months <= 0 || $this->acquisition_cost <= 0) {
            return 0;
        }

        $years = $this->useful_life_months / 12;
        $rate = 1 - pow(max($this->residual_value, 0.01) / $this->acquisition_cost, 1 / $years);
        $bookValue = $this->acquisition_cost;
        $totalDepreciation = 0;
        $fullYears = intdiv($months, 12);
        $remainingMonths = $months % 12;

        for ($y = 0; $y < $fullYears; $y++) {
            $yearDepreciation = round($bookValue * $rate, 2);
            $totalDepreciation += $yearDepreciation;
            $bookValue -= $yearDepreciation;
        }

        if ($remainingMonths > 0) {
            $partialDepreciation = round($bookValue * $rate * $remainingMonths / 12, 2);
            $totalDepreciation += $partialDepreciation;
        }

        // Never depreciate below residual value
        $maxDepreciation = $this->acquisition_cost - $this->residual_value;

        return round(min($totalDepreciation, $maxDepreciation), 2);
    }

    /**
     * Generate depreciation schedule
     */
    public function getDepreciationSchedule(): array
    {
        $schedule = [];
        $startDate = $this->acquisition_date->copy()->startOfMonth();
        $bookValue = (float) $this->acquisition_cost;
        $totalDepreciated = 0;
        $years = (int) ceil($this->useful_life_months / 12);

        if ($this->depreciation_method === self::METHOD_DECLINING_BALANCE) {
            $rate = 1 - pow(max($this->residual_value, 0.01) / $this->acquisition_cost, 1 / ($this->useful_life_months / 12));
        }

        for ($y = 0; $y < $years; $y++) {
            $yearStart = $startDate->copy()->addYears($y);
            $yearEnd = $yearStart->copy()->endOfYear();

            // For the first year, calculate partial months
            $monthsInYear = 12;
            if ($y === 0) {
                $monthsInYear = 12 - $this->acquisition_date->month + 1;
            }
            // For the last year
            $remainingMonths = $this->useful_life_months - ($y * 12 - (12 - $monthsInYear));
            if ($y > 0) {
                $remainingMonths = $this->useful_life_months - ((12 - ($this->acquisition_date->month - 1)) + ($y - 1) * 12);
            }
            if ($remainingMonths <= 0) {
                break;
            }
            $monthsInYear = min($monthsInYear, $remainingMonths);

            if ($this->depreciation_method === self::METHOD_STRAIGHT_LINE) {
                $yearDepreciation = round($this->monthly_depreciation * $monthsInYear, 2);
            } else {
                $yearDepreciation = round($bookValue * $rate * $monthsInYear / 12, 2);
            }

            // Don't depreciate below residual value
            $maxRemaining = $this->acquisition_cost - $this->residual_value - $totalDepreciated;
            $yearDepreciation = min($yearDepreciation, max($maxRemaining, 0));

            $totalDepreciated += $yearDepreciation;
            $bookValue -= $yearDepreciation;

            $schedule[] = [
                'year' => $yearStart->year,
                'months' => $monthsInYear,
                'opening_value' => round($bookValue + $yearDepreciation, 2),
                'depreciation' => round($yearDepreciation, 2),
                'accumulated' => round($totalDepreciated, 2),
                'closing_value' => round($bookValue, 2),
            ];

            if ($bookValue <= $this->residual_value) {
                break;
            }
        }

        return $schedule;
    }

    /**
     * Scope: active assets for a company
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope: by company
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}

// CLAUDE-CHECKPOINT
