<?php

namespace Modules\Mk\Services;

use App\Models\CompanySetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Mk\Models\Manufacturing\Bom;
use Modules\Mk\Models\Manufacturing\ProductionOrder;

/**
 * Report data aggregation for manufacturing module.
 * All monetary values in cents (MKD × 100).
 */
class ManufacturingReportService
{
    /**
     * Cost analysis report — period-based production cost breakdown.
     */
    public function getCostAnalysis(int $companyId, ?string $from = null, ?string $to = null): array
    {
        $query = ProductionOrder::where('company_id', $companyId)
            ->where('status', ProductionOrder::STATUS_COMPLETED);

        if ($from) {
            $query->where('order_date', '>=', $from);
        }
        if ($to) {
            $query->where('order_date', '<=', $to);
        }

        $orders = $query->with(['outputItem:id,name,unit_id', 'outputItem.unit:id,name', 'bom', 'bom.lines.item'])->get();

        // Group by output item for per-product breakdown
        $byProduct = $orders->groupBy('output_item_id')->map(function ($group) {
            $first = $group->first();

            return [
                'item_id' => $first->output_item_id,
                'item_name' => $first->outputItem?->name,
                'unit_name' => $first->outputItem?->unit?->name,
                'order_count' => $group->count(),
                'total_quantity' => $group->sum('actual_quantity'),
                'total_material_cost' => $group->sum('total_material_cost'),
                'total_labor_cost' => $group->sum('total_labor_cost'),
                'total_overhead_cost' => $group->sum('total_overhead_cost'),
                'total_wastage_cost' => $group->sum('total_wastage_cost'),
                'total_production_cost' => $group->sum('total_production_cost'),
                'avg_cost_per_unit' => $group->sum('actual_quantity') > 0
                    ? (int) round($group->sum('total_production_cost') / $group->sum('actual_quantity'))
                    : 0,
            ];
        })->values()->toArray();

        return [
            'period' => ['from' => $from, 'to' => $to],
            'summary' => [
                'total_orders' => $orders->count(),
                'total_quantity' => $orders->sum('actual_quantity'),
                'total_material_cost' => $orders->sum('total_material_cost'),
                'total_labor_cost' => $orders->sum('total_labor_cost'),
                'total_overhead_cost' => $orders->sum('total_overhead_cost'),
                'total_wastage_cost' => $orders->sum('total_wastage_cost'),
                'total_production_cost' => $orders->sum('total_production_cost'),
            ],
            'by_product' => $byProduct,
        ];
    }

    /**
     * Variance report — normative vs actual costs.
     */
    public function getVarianceReport(int $companyId, ?string $from = null, ?string $to = null): array
    {
        $query = ProductionOrder::where('company_id', $companyId)
            ->where('status', ProductionOrder::STATUS_COMPLETED);

        if ($from) {
            $query->where('order_date', '>=', $from);
        }
        if ($to) {
            $query->where('order_date', '<=', $to);
        }

        $orders = $query->with([
            'outputItem:id,name',
            'bom', 'bom.lines.item',
            'materials.item:id,name',
        ])->get();

        $orderDetails = $orders->map(function ($order) {
            $bom = $order->bom;
            $normativeCostData = $bom ? $bom->calculateNormativeCost() : ['total_cost' => 0];
            $normativeCostPerUnit = is_array($normativeCostData) ? ($normativeCostData['total_cost'] ?? 0) : (int) $normativeCostData;
            $normativeTotal = $order->actual_quantity > 0
                ? (int) round($normativeCostPerUnit * (float) $order->actual_quantity)
                : 0;

            return [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'order_date' => $order->order_date?->format('Y-m-d'),
                'item_name' => $order->outputItem?->name,
                'bom_name' => $bom?->name,
                'planned_quantity' => $order->planned_quantity,
                'actual_quantity' => $order->actual_quantity,
                'quantity_variance' => (float) $order->actual_quantity - (float) $order->planned_quantity,
                'normative_cost' => $normativeCostPerUnit,
                'normative_total' => $normativeTotal,
                'actual_total' => $order->total_production_cost,
                'material_variance' => $order->material_variance,
                'labor_variance' => $order->labor_variance,
                'total_variance' => $order->total_variance,
                'variance_percent' => $normativeTotal > 0
                    ? round(($order->total_variance / $normativeTotal) * 100, 2)
                    : 0,
            ];
        })->toArray();

        return [
            'period' => ['from' => $from, 'to' => $to],
            'summary' => [
                'total_orders' => count($orderDetails),
                'total_favorable' => collect($orderDetails)->where('total_variance', '<', 0)->count(),
                'total_unfavorable' => collect($orderDetails)->where('total_variance', '>', 0)->count(),
                'net_variance' => $orders->sum('total_variance'),
                'net_material_variance' => $orders->sum('material_variance'),
                'net_labor_variance' => $orders->sum('labor_variance'),
            ],
            'orders' => $orderDetails,
        ];
    }

    /**
     * Wastage report — утрасок tracking and analysis.
     */
    public function getWastageReport(int $companyId, ?string $from = null, ?string $to = null): array
    {
        $query = ProductionOrder::where('company_id', $companyId)
            ->where('status', ProductionOrder::STATUS_COMPLETED);

        if ($from) {
            $query->where('order_date', '>=', $from);
        }
        if ($to) {
            $query->where('order_date', '<=', $to);
        }

        $orders = $query->with([
            'outputItem:id,name',
            'bom:id,name,code,expected_wastage_percent',
            'materials.item:id,name,unit_id',
            'materials.item.unit:id,name',
        ])->get();

        $materialWastage = [];

        foreach ($orders as $order) {
            foreach ($order->materials as $material) {
                $key = $material->item_id;
                if (! isset($materialWastage[$key])) {
                    $materialWastage[$key] = [
                        'item_id' => $material->item_id,
                        'item_name' => $material->item?->name,
                        'unit_name' => $material->item?->unit?->name,
                        'total_planned' => 0,
                        'total_actual' => 0,
                        'total_wastage_qty' => 0,
                        'total_wastage_cost' => 0,
                        'order_count' => 0,
                    ];
                }

                $materialWastage[$key]['total_planned'] += (float) $material->planned_quantity;
                $materialWastage[$key]['total_actual'] += (float) $material->actual_quantity;
                $materialWastage[$key]['total_wastage_qty'] += (float) $material->wastage_quantity;
                $materialWastage[$key]['total_wastage_cost'] += $material->wastage_cost ?? 0;
                $materialWastage[$key]['order_count']++;
            }
        }

        // Calculate wastage percentages
        $materialWastage = collect($materialWastage)->map(function ($item) {
            $item['wastage_percent'] = $item['total_actual'] > 0
                ? round(($item['total_wastage_qty'] / $item['total_actual']) * 100, 2)
                : 0;

            return $item;
        })->sortByDesc('total_wastage_cost')->values()->toArray();

        return [
            'period' => ['from' => $from, 'to' => $to],
            'summary' => [
                'total_orders' => $orders->count(),
                'total_wastage_cost' => $orders->sum('total_wastage_cost'),
                'total_production_cost' => $orders->sum('total_production_cost'),
                'wastage_percent_of_total' => $orders->sum('total_production_cost') > 0
                    ? round(($orders->sum('total_wastage_cost') / $orders->sum('total_production_cost')) * 100, 2)
                    : 0,
            ],
            'by_material' => $materialWastage,
        ];
    }

    /**
     * Get data for a single production order PDF (Работен налог).
     */
    public function getOrderPdfData(ProductionOrder $order): array
    {
        $order->load([
            'outputItem:id,name,unit_id',
            'outputItem.unit:id,name',
            'bom', 'bom.lines.item',
            'currency:id,name,code,symbol',
            'outputWarehouse:id,name',
            'materials.item:id,name,unit_id',
            'materials.item.unit:id,name',
            'materials.warehouse:id,name',
            'laborEntries',
            'overheadEntries',
            'coProductionOutputs.item:id,name,unit_id',
            'coProductionOutputs.item.unit:id,name',
            'createdBy:id,name',
            'approvedBy:id,name',
            'company',
        ]);

        return [
            'order' => $order,
            'company' => $order->company,
            'has_co_production' => $order->coProductionOutputs->count() > 1,
        ];
    }
}

// CLAUDE-CHECKPOINT
