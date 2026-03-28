<?php

namespace Modules\Mk\Services;

use Carbon\Carbon;
use Modules\Mk\Models\Manufacturing\ProductionOrder;
use Modules\Mk\Models\Manufacturing\WorkCenter;

/**
 * Forward-scheduling service for production orders.
 *
 * Takes unscheduled draft orders and fits them into work center
 * time slots based on capacity_hours_per_day and dependencies.
 */
class AutoScheduleService
{
    /**
     * Auto-schedule all draft orders for a company.
     *
     * @return array Scheduled orders with their new dates
     */
    public function schedule(int $companyId, ?int $workCenterId = null): array
    {
        // Get all draft orders that need scheduling
        $query = ProductionOrder::where('company_id', $companyId)
            ->where('status', ProductionOrder::STATUS_DRAFT)
            ->with(['bom:id,estimated_hours,output_quantity', 'dependsOn:production_orders.id,status,expected_completion_date']);

        if ($workCenterId) {
            $query->where('work_center_id', $workCenterId);
        }

        $draftOrders = $query->orderBy('created_at')->get();

        if ($draftOrders->isEmpty()) {
            return [];
        }

        // Get work centers
        $workCenters = WorkCenter::where('company_id', $companyId)
            ->active()
            ->orderBy('sort_order')
            ->get();

        if ($workCenters->isEmpty()) {
            // No work centers — schedule sequentially from today
            return $this->scheduleWithoutWorkCenters($draftOrders);
        }

        // Build capacity calendar per work center
        $calendars = [];
        foreach ($workCenters as $wc) {
            $calendars[$wc->id] = [
                'work_center' => $wc,
                'hours_per_day' => (float) $wc->capacity_hours_per_day ?: 8,
                'used_hours' => $this->getUsedHours($companyId, $wc->id),
            ];
        }

        // Sort orders by dependency topology (orders with no deps first)
        $sorted = $this->topologicalSort($draftOrders);

        $scheduled = [];
        foreach ($sorted as $order) {
            // Determine earliest start from dependencies
            $earliest = Carbon::today();
            if (\Illuminate\Support\Facades\Schema::hasTable('production_order_dependencies')) {
                foreach ($order->dependsOn as $dep) {
                    if ($dep->expected_completion_date && $dep->expected_completion_date->gt($earliest)) {
                        $earliest = $dep->expected_completion_date->copy()->addDay();
                    }
                }
            }

            // Estimate hours needed
            $hours = $this->estimateHours($order);

            // Pick work center (use assigned, or least-loaded)
            $wcId = $order->work_center_id;
            if (! $wcId || ! isset($calendars[$wcId])) {
                $wcId = $this->leastLoadedCenter($calendars);
            }

            if (! $wcId) {
                continue;
            }

            $cal = &$calendars[$wcId];

            // Find first available slot
            $slot = $this->findSlot($earliest, $hours, $cal);

            $order->update([
                'order_date' => $slot['start']->format('Y-m-d'),
                'expected_completion_date' => $slot['end']->format('Y-m-d'),
                'work_center_id' => $wcId,
            ]);

            // Mark hours as used
            $current = $slot['start']->copy();
            $remaining = $hours;
            while ($remaining > 0 && $current->lte($slot['end'])) {
                if ($current->isWeekday()) {
                    $dateKey = $current->format('Y-m-d');
                    $available = $cal['hours_per_day'] - ($cal['used_hours'][$dateKey] ?? 0);
                    $use = min($remaining, $available);
                    $cal['used_hours'][$dateKey] = ($cal['used_hours'][$dateKey] ?? 0) + $use;
                    $remaining -= $use;
                }
                $current->addDay();
            }

            $scheduled[] = [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'work_center_id' => $wcId,
                'order_date' => $slot['start']->format('Y-m-d'),
                'expected_completion_date' => $slot['end']->format('Y-m-d'),
                'estimated_hours' => $hours,
            ];
        }

        return $scheduled;
    }

    /**
     * Schedule orders sequentially when no work centers exist.
     */
    private function scheduleWithoutWorkCenters($orders): array
    {
        $scheduled = [];
        $nextDate = Carbon::today();

        foreach ($orders as $order) {
            // Skip weekends
            while ($nextDate->isWeekend()) {
                $nextDate->addDay();
            }

            $hours = $this->estimateHours($order);
            $days = max(1, (int) ceil($hours / 8));
            $endDate = $nextDate->copy();

            // Add working days
            $added = 0;
            while ($added < $days - 1) {
                $endDate->addDay();
                if ($endDate->isWeekday()) {
                    $added++;
                }
            }

            $order->update([
                'order_date' => $nextDate->format('Y-m-d'),
                'expected_completion_date' => $endDate->format('Y-m-d'),
            ]);

            $scheduled[] = [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'order_date' => $nextDate->format('Y-m-d'),
                'expected_completion_date' => $endDate->format('Y-m-d'),
                'estimated_hours' => $hours,
            ];

            $nextDate = $endDate->copy()->addDay();
        }

        return $scheduled;
    }

    /**
     * Get used hours per day for a work center from existing scheduled orders.
     */
    private function getUsedHours(int $companyId, int $workCenterId): array
    {
        $orders = ProductionOrder::where('company_id', $companyId)
            ->where('work_center_id', $workCenterId)
            ->whereIn('status', [ProductionOrder::STATUS_DRAFT, ProductionOrder::STATUS_IN_PROGRESS])
            ->whereNotNull('order_date')
            ->whereNotNull('expected_completion_date')
            ->with('bom:id,estimated_hours,output_quantity')
            ->get();

        $used = [];
        foreach ($orders as $order) {
            $hours = $this->estimateHours($order);
            $start = Carbon::parse($order->order_date);
            $end = Carbon::parse($order->expected_completion_date);
            $days = max(1, $start->diffInWeekdays($end) + 1);
            $hoursPerDay = $hours / $days;

            $current = $start->copy();
            while ($current->lte($end)) {
                if ($current->isWeekday()) {
                    $key = $current->format('Y-m-d');
                    $used[$key] = ($used[$key] ?? 0) + $hoursPerDay;
                }
                $current->addDay();
            }
        }

        return $used;
    }

    /**
     * Estimate hours needed for a production order.
     */
    private function estimateHours(ProductionOrder $order): float
    {
        if ($order->bom && $order->bom->estimated_hours) {
            $outputQty = (float) $order->bom->output_quantity ?: 1;
            $batches = (float) $order->planned_quantity / $outputQty;

            return max(1, round($batches * (float) $order->bom->estimated_hours, 1));
        }

        return 8; // Default: 1 day
    }

    /**
     * Find first available slot for given hours starting from earliest date.
     */
    private function findSlot(Carbon $earliest, float $hours, array &$cal): array
    {
        $hoursPerDay = $cal['hours_per_day'];
        $start = $earliest->copy();

        // Skip to first weekday
        while ($start->isWeekend()) {
            $start->addDay();
        }

        $remaining = $hours;
        $end = $start->copy();
        $slotStart = null;

        $current = $start->copy();
        $maxLookahead = 365;

        while ($remaining > 0 && $maxLookahead-- > 0) {
            if ($current->isWeekday()) {
                $dateKey = $current->format('Y-m-d');
                $usedToday = $cal['used_hours'][$dateKey] ?? 0;
                $available = max(0, $hoursPerDay - $usedToday);

                if ($available > 0) {
                    if (! $slotStart) {
                        $slotStart = $current->copy();
                    }
                    $remaining -= $available;
                    $end = $current->copy();
                }
            }
            $current->addDay();
        }

        return [
            'start' => $slotStart ?? $start,
            'end' => $end,
        ];
    }

    /**
     * Find least-loaded work center.
     */
    private function leastLoadedCenter(array $calendars): ?int
    {
        $minLoad = PHP_FLOAT_MAX;
        $bestId = null;

        foreach ($calendars as $wcId => $cal) {
            $totalUsed = array_sum($cal['used_hours']);
            if ($totalUsed < $minLoad) {
                $minLoad = $totalUsed;
                $bestId = $wcId;
            }
        }

        return $bestId;
    }

    /**
     * Topological sort: orders with no dependencies first.
     */
    private function topologicalSort($orders): array
    {
        $sorted = [];
        $visited = [];
        $orderMap = $orders->keyBy('id');

        foreach ($orders as $order) {
            $this->topoVisit($order, $orderMap, $visited, $sorted);
        }

        return $sorted;
    }

    private function topoVisit($order, $orderMap, &$visited, &$sorted): void
    {
        if (isset($visited[$order->id])) {
            return;
        }
        $visited[$order->id] = true;

        if (\Illuminate\Support\Facades\Schema::hasTable('production_order_dependencies')) {
            foreach ($order->dependsOn as $dep) {
                if (isset($orderMap[$dep->id])) {
                    $this->topoVisit($orderMap[$dep->id], $orderMap, $visited, $sorted);
                }
            }
        }

        $sorted[] = $order;
    }
}

// CLAUDE-CHECKPOINT
