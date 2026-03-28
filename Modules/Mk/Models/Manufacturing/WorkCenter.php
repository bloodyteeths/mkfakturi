<?php

namespace Modules\Mk\Models\Manufacturing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkCenter extends Model
{
    use SoftDeletes;

    protected $table = 'work_centers';

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'description',
        'capacity_hours_per_day',
        'hourly_rate',
        'overhead_rate',
        'target_availability',
        'target_performance',
        'target_quality',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'capacity_hours_per_day' => 'decimal:2',
            'hourly_rate' => 'integer',
            'overhead_rate' => 'integer',
            'target_availability' => 'decimal:2',
            'target_performance' => 'decimal:2',
            'target_quality' => 'decimal:2',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    // ---- Relationships ----

    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function productionOrders(): HasMany
    {
        return $this->hasMany(ProductionOrder::class);
    }

    public function laborEntries(): HasMany
    {
        return $this->hasMany(ProductionOrderLabor::class);
    }

    // ---- Scopes ----

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ---- OEE Calculation ----

    /**
     * Calculate OEE for this work center over a date range.
     *
     * OEE = Availability × Performance × Quality
     * - Availability = actual hours worked / planned capacity hours
     * - Performance = actual output quantity / planned output quantity
     * - Quality = (actual output - wastage) / actual output
     *
     * Returns percentages (0-100).
     */
    public function calculateOee(?string $from = null, ?string $to = null): array
    {
        $ordersQuery = $this->productionOrders()
            ->where('status', ProductionOrder::STATUS_COMPLETED);

        if ($from) {
            $ordersQuery->where('completed_at', '>=', $from);
        }
        if ($to) {
            $ordersQuery->where('completed_at', '<=', $to);
        }

        $orders = $ordersQuery->get();

        if ($orders->isEmpty()) {
            return [
                'availability' => 0,
                'performance' => 0,
                'quality' => 0,
                'oee' => 0,
                'order_count' => 0,
            ];
        }

        // Availability: actual labor hours vs capacity
        $laborQuery = ProductionOrderLabor::whereIn('production_order_id', $orders->pluck('id'));
        if ($from) {
            $laborQuery->where('work_date', '>=', $from);
        }
        if ($to) {
            $laborQuery->where('work_date', '<=', $to);
        }
        $totalLaborHours = (float) $laborQuery->sum('hours');

        // Calculate planned capacity: working days × capacity_hours_per_day
        $dayCount = max(1, $this->getWorkingDays($from, $to));
        $plannedCapacity = $dayCount * (float) $this->capacity_hours_per_day;
        $availability = $plannedCapacity > 0
            ? min(100, round(($totalLaborHours / $plannedCapacity) * 100, 1))
            : 0;

        // Performance: actual quantity vs planned quantity
        $totalPlanned = (float) $orders->sum('planned_quantity');
        $totalActual = (float) $orders->sum('actual_quantity');
        $performance = $totalPlanned > 0
            ? min(100, round(($totalActual / $totalPlanned) * 100, 1))
            : 0;

        // Quality: (production cost - wastage cost) / production cost
        $totalProductionCost = $orders->sum('total_production_cost');
        $totalWastageCost = $orders->sum('total_wastage_cost');
        $quality = $totalProductionCost > 0
            ? round((($totalProductionCost - $totalWastageCost) / $totalProductionCost) * 100, 1)
            : 100;

        $oee = round(($availability / 100) * ($performance / 100) * ($quality / 100) * 100, 1);

        return [
            'availability' => $availability,
            'performance' => $performance,
            'quality' => $quality,
            'oee' => $oee,
            'order_count' => $orders->count(),
            'total_labor_hours' => round($totalLaborHours, 1),
            'planned_capacity' => round($plannedCapacity, 1),
        ];
    }

    /**
     * Estimate working days in range (excludes weekends).
     */
    private function getWorkingDays(?string $from, ?string $to): int
    {
        $start = $from ? \Carbon\Carbon::parse($from) : \Carbon\Carbon::now()->startOfMonth();
        $end = $to ? \Carbon\Carbon::parse($to) : \Carbon\Carbon::now();

        $days = 0;
        $current = $start->copy();

        while ($current->lte($end)) {
            if ($current->isWeekday()) {
                $days++;
            }
            $current->addDay();
        }

        return $days;
    }

    /**
     * Get target OEE (product of targets).
     */
    public function getTargetOee(): float
    {
        return round(
            ((float) $this->target_availability / 100)
            * ((float) $this->target_performance / 100)
            * ((float) $this->target_quality / 100)
            * 100,
            1
        );
    }
}

// CLAUDE-CHECKPOINT
