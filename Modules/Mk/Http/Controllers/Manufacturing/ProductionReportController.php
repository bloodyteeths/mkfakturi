<?php

namespace Modules\Mk\Http\Controllers\Manufacturing;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Item;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Modules\Mk\Models\Manufacturing\Bom;
use Modules\Mk\Models\Manufacturing\ProductionOrder;
use Modules\Mk\Services\ManufacturingReportService;
use PDF;

class ProductionReportController extends Controller
{
    public function __construct(
        protected ManufacturingReportService $reportService,
    ) {}

    /**
     * Dashboard summary — KPIs, pipeline, recent orders, material alerts.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $companyId = (int) $request->header('company');
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth()->toDateString();
        $endOfMonth = $now->copy()->endOfMonth()->toDateString();

        // Status counts (pipeline)
        $statusCounts = ProductionOrder::where('company_id', $companyId)
            ->selectRaw("status, COUNT(*) as count")
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // This month completed orders
        $completedThisMonth = ProductionOrder::where('company_id', $companyId)
            ->where('status', ProductionOrder::STATUS_COMPLETED)
            ->whereBetween('completed_at', [$startOfMonth, $endOfMonth])
            ->with('outputItem:id,name')
            ->get();

        $totalProductionCostMonth = $completedThisMonth->sum('total_production_cost');
        $totalWastageCostMonth = $completedThisMonth->sum('total_wastage_cost');
        $totalQuantityMonth = $completedThisMonth->sum('actual_quantity');

        // Active (in_progress) cost accumulation
        $activeOrders = ProductionOrder::where('company_id', $companyId)
            ->where('status', ProductionOrder::STATUS_IN_PROGRESS)
            ->with('outputItem:id,name')
            ->get();

        $activeProductionCost = $activeOrders->sum('total_production_cost');

        // Recent orders (last 8, any status)
        $recentOrders = ProductionOrder::where('company_id', $companyId)
            ->with(['outputItem:id,name', 'bom:id,name,code'])
            ->orderByDesc('updated_at')
            ->limit(8)
            ->get()
            ->map(fn ($o) => [
                'id' => $o->id,
                'order_number' => $o->order_number,
                'item_name' => $o->outputItem?->name,
                'bom_code' => $o->bom?->code,
                'status' => $o->status,
                'planned_quantity' => $o->planned_quantity,
                'actual_quantity' => $o->actual_quantity,
                'total_production_cost' => $o->total_production_cost,
                'order_date' => $o->order_date?->format('Y-m-d'),
                'expected_completion_date' => $o->expected_completion_date?->format('Y-m-d'),
            ]);

        // Overdue orders (in_progress past expected_completion_date)
        $overdueCount = ProductionOrder::where('company_id', $companyId)
            ->where('status', ProductionOrder::STATUS_IN_PROGRESS)
            ->whereNotNull('expected_completion_date')
            ->where('expected_completion_date', '<', $now->toDateString())
            ->count();

        // BOM count + active BOM count
        $bomTotal = Bom::where('company_id', $companyId)->count();
        $bomActive = Bom::where('company_id', $companyId)->where('is_active', true)->count();

        // Top products by production volume (this month)
        $topProducts = $completedThisMonth->groupBy('output_item_id')
            ->map(fn ($group) => [
                'item_name' => $group->first()->outputItem?->name ?? '-',
                'quantity' => $group->sum('actual_quantity'),
                'cost' => $group->sum('total_production_cost'),
                'orders' => $group->count(),
            ])
            ->sortByDesc('quantity')
            ->values()
            ->take(5)
            ->toArray();

        // Wastage rate
        $wastagePercent = $totalProductionCostMonth > 0
            ? round(($totalWastageCostMonth / $totalProductionCostMonth) * 100, 1)
            : 0;

        // Average cost per unit this month
        $avgCostPerUnit = $totalQuantityMonth > 0
            ? (int) round($totalProductionCostMonth / (float) $totalQuantityMonth)
            : 0;

        // ---- Monthly trend chart (last 6 months) ----
        $chartMonths = [];
        $chartLabels = [];
        $chartProductionCost = [];
        $chartWastageCost = [];
        $chartQuantity = [];
        $chartOrderCount = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i);
            $mStart = $month->copy()->startOfMonth()->toDateString();
            $mEnd = $month->copy()->endOfMonth()->toDateString();
            $chartLabels[] = $month->translatedFormat('M');

            $monthOrders = ProductionOrder::where('company_id', $companyId)
                ->where('status', ProductionOrder::STATUS_COMPLETED)
                ->whereBetween('completed_at', [$mStart, $mEnd])
                ->get();

            $chartProductionCost[] = $monthOrders->sum('total_production_cost');
            $chartWastageCost[] = $monthOrders->sum('total_wastage_cost');
            $chartQuantity[] = (float) $monthOrders->sum('actual_quantity');
            $chartOrderCount[] = $monthOrders->count();
        }

        // ---- Material availability per active BOM ----
        $stockService = app(\App\Services\StockService::class);
        $activeBoms = Bom::where('company_id', $companyId)
            ->where('is_active', true)
            ->with(['outputItem:id,name', 'lines.item:id,name,minimum_quantity,track_quantity,quantity'])
            ->limit(10)
            ->get();

        $materialAvailability = $activeBoms->map(function ($bom) use ($companyId, $stockService) {
            $status = 'green'; // all good
            $shortages = [];

            foreach ($bom->lines as $line) {
                if (! $line->item) {
                    continue;
                }
                $stock = $stockService->getItemStock($companyId, $line->item->id);
                $currentQty = $stock['current_quantity'] ?? 0;
                $neededQty = (float) $line->quantity;
                $minQty = $line->item->minimum_quantity ?? 0;

                // Red: not enough stock for one production run
                if ($currentQty < $neededQty) {
                    $status = 'red';
                    $shortages[] = [
                        'item_id' => $line->item->id,
                        'item_name' => $line->item->name,
                        'needed' => $neededQty,
                        'available' => $currentQty,
                        'deficit' => round($neededQty - $currentQty, 2),
                        'below_minimum' => $minQty > 0 && $currentQty <= $minQty,
                    ];
                }
                // Yellow: enough for production but below item's minimum_quantity threshold
                elseif ($minQty > 0 && $currentQty <= $minQty && $status !== 'red') {
                    $status = 'yellow';
                    $shortages[] = [
                        'item_id' => $line->item->id,
                        'item_name' => $line->item->name,
                        'needed' => $neededQty,
                        'available' => $currentQty,
                        'deficit' => 0,
                        'below_minimum' => true,
                        'minimum_quantity' => $minQty,
                    ];
                }
                // Yellow: enough but tight buffer (< 1.5× needed)
                elseif ($currentQty < $neededQty * 1.5 && $status !== 'red') {
                    $status = 'yellow';
                }
            }

            return [
                'bom_id' => $bom->id,
                'bom_name' => $bom->name,
                'bom_code' => $bom->code,
                'output_item' => $bom->outputItem?->name,
                'status' => $status,
                'material_count' => $bom->lines->count(),
                'shortages' => $shortages,
            ];
        })->toArray();

        // ---- OEE per work center (this month) ----
        $workCenters = \Modules\Mk\Models\Manufacturing\WorkCenter::where('company_id', $companyId)
            ->active()->orderBy('sort_order')->get();
        $oeeData = $workCenters->map(function ($wc) use ($startOfMonth, $endOfMonth) {
            $oee = $wc->calculateOee($startOfMonth, $endOfMonth);

            return [
                'id' => $wc->id,
                'name' => $wc->name,
                'code' => $wc->code,
                'oee' => $oee['oee'],
                'availability' => $oee['availability'],
                'performance' => $oee['performance'],
                'quality' => $oee['quality'],
                'target_oee' => $wc->getTargetOee(),
                'order_count' => $oee['order_count'],
            ];
        })->toArray();

        $totalOeeOrders = array_sum(array_column($oeeData, 'order_count'));
        $overallOee = $totalOeeOrders > 0
            ? round(array_sum(array_map(fn ($wc) => $wc['oee'] * $wc['order_count'], $oeeData)) / $totalOeeOrders, 1)
            : 0;

        // ---- Active orders timeline (for mini-Gantt) ----
        $timelineOrders = ProductionOrder::where('company_id', $companyId)
            ->whereIn('status', [ProductionOrder::STATUS_DRAFT, ProductionOrder::STATUS_IN_PROGRESS])
            ->with('outputItem:id,name')
            ->orderBy('order_date')
            ->limit(12)
            ->get()
            ->map(fn ($o) => [
                'id' => $o->id,
                'order_number' => $o->order_number,
                'item_name' => $o->outputItem?->name,
                'status' => $o->status,
                'start' => $o->order_date?->format('Y-m-d'),
                'end' => $o->expected_completion_date?->format('Y-m-d')
                    ?? $o->order_date?->copy()->addDays(7)->format('Y-m-d'),
                'planned_quantity' => $o->planned_quantity,
                'is_overdue' => $o->expected_completion_date && $o->expected_completion_date->lt($now),
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'kpis' => [
                    'total_production_cost_month' => $totalProductionCostMonth,
                    'active_orders' => $statusCounts[ProductionOrder::STATUS_IN_PROGRESS] ?? 0,
                    'completed_this_month' => $completedThisMonth->count(),
                    'wastage_percent' => $wastagePercent,
                    'active_production_cost' => $activeProductionCost,
                    'avg_cost_per_unit' => $avgCostPerUnit,
                    'overdue_count' => $overdueCount,
                ],
                'pipeline' => [
                    'draft' => $statusCounts[ProductionOrder::STATUS_DRAFT] ?? 0,
                    'in_progress' => $statusCounts[ProductionOrder::STATUS_IN_PROGRESS] ?? 0,
                    'completed' => $statusCounts[ProductionOrder::STATUS_COMPLETED] ?? 0,
                    'cancelled' => $statusCounts[ProductionOrder::STATUS_CANCELLED] ?? 0,
                ],
                'boms' => [
                    'total' => $bomTotal,
                    'active' => $bomActive,
                ],
                'recent_orders' => $recentOrders,
                'top_products' => $topProducts,
                'chart' => [
                    'labels' => $chartLabels,
                    'production_cost' => $chartProductionCost,
                    'wastage_cost' => $chartWastageCost,
                    'quantity' => $chartQuantity,
                    'order_count' => $chartOrderCount,
                ],
                'material_availability' => $materialAvailability,
                'oee' => [
                    'overall' => $overallOee,
                    'work_centers' => $oeeData,
                ],
                'timeline' => $timelineOrders,
                'period' => [
                    'month' => $now->format('Y-m'),
                    'label' => $now->translatedFormat('F Y'),
                ],
            ],
        ]);
    }

    /**
     * Smart reorder — create a draft PO from material shortages.
     *
     * Accepts an array of items with quantities. Groups by preferred supplier
     * and creates one draft PO per supplier.
     */
    public function smartReorder(Request $request): JsonResponse
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|integer',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.supplier_id' => 'nullable|integer',
            'warehouse_id' => 'nullable|integer',
            'notes' => 'nullable|string',
        ]);

        $companyId = (int) $request->header('company');
        $userId = $request->user()?->id;
        $poService = app(\Modules\Mk\Services\PurchaseOrderService::class);

        // Group items by supplier
        $bySupplier = [];
        foreach ($request->items as $entry) {
            $item = Item::where('company_id', $companyId)->find($entry['item_id']);
            if (! $item) {
                continue;
            }

            $supplierId = $entry['supplier_id']
                ?? $item->preferred_supplier_id
                ?? null;

            if (! $supplierId) {
                continue; // Skip items with no supplier
            }

            $qty = $item->reorder_quantity && $item->reorder_quantity > $entry['quantity']
                ? $item->reorder_quantity
                : (float) $entry['quantity'];

            $bySupplier[$supplierId][] = [
                'item_id' => $item->id,
                'name' => $item->name,
                'quantity' => $qty,
                'price' => $item->cost ?? 0, // WAC or last cost
                'tax' => 0,
            ];
        }

        if (empty($bySupplier)) {
            return response()->json([
                'success' => false,
                'message' => 'No items with assigned suppliers. Set preferred suppliers on items first.',
            ], 422);
        }

        $createdPos = [];
        foreach ($bySupplier as $supplierId => $items) {
            try {
                $leadTime = max(...array_map(
                    fn ($i) => Item::find($i['item_id'])?->lead_time_days ?? 7,
                    $items
                ));

                $po = $poService->create($companyId, [
                    'supplier_id' => $supplierId,
                    'po_date' => now()->toDateString(),
                    'expected_delivery_date' => now()->addDays($leadTime)->toDateString(),
                    'warehouse_id' => $request->warehouse_id,
                    'notes' => $request->notes ?? 'Auto-generated from manufacturing material shortage',
                    'items' => $items,
                ], $userId);

                $createdPos[] = [
                    'id' => $po->id,
                    'po_number' => $po->po_number,
                    'supplier' => $po->supplier?->name,
                    'total' => $po->total,
                    'item_count' => count($items),
                ];
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Smart reorder failed for supplier', [
                    'supplier_id' => $supplierId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'purchase_orders' => $createdPos,
                'count' => count($createdPos),
            ],
            'message' => count($createdPos) . ' purchase order(s) created as draft.',
        ]);
    }

    /**
     * Net material requirements — calculate what's needed across all pending orders.
     */
    public function netRequirements(Request $request): JsonResponse
    {
        $companyId = (int) $request->header('company');
        $stockService = app(\App\Services\StockService::class);

        // Get all pending (draft + in_progress) orders
        $pendingOrders = ProductionOrder::where('company_id', $companyId)
            ->whereIn('status', [ProductionOrder::STATUS_DRAFT, ProductionOrder::STATUS_IN_PROGRESS])
            ->with(['bom.lines.item:id,name,minimum_quantity,preferred_supplier_id,reorder_quantity'])
            ->get();

        // Aggregate material needs across all orders
        $materialNeeds = [];
        foreach ($pendingOrders as $order) {
            if (! $order->bom) {
                continue;
            }
            $multiplier = (float) $order->planned_quantity / max(1, (float) $order->bom->output_quantity);

            foreach ($order->bom->lines as $line) {
                if (! $line->item) {
                    continue;
                }
                $itemId = $line->item->id;
                $needed = (float) $line->quantity * $multiplier;

                if (! isset($materialNeeds[$itemId])) {
                    $materialNeeds[$itemId] = [
                        'item_id' => $itemId,
                        'item_name' => $line->item->name,
                        'total_needed' => 0,
                        'order_count' => 0,
                        'minimum_quantity' => $line->item->minimum_quantity,
                        'preferred_supplier_id' => $line->item->preferred_supplier_id,
                        'reorder_quantity' => $line->item->reorder_quantity,
                    ];
                }
                $materialNeeds[$itemId]['total_needed'] += $needed;
                $materialNeeds[$itemId]['order_count']++;
            }
        }

        // Check stock and calculate net requirements
        $results = [];
        foreach ($materialNeeds as $itemId => $need) {
            $stock = $stockService->getItemStock($companyId, $itemId);
            $currentQty = $stock['current_quantity'] ?? 0;
            $netRequired = max(0, $need['total_needed'] - $currentQty);
            $belowMinimum = $need['minimum_quantity'] && $currentQty < $need['minimum_quantity'];

            $results[] = array_merge($need, [
                'current_stock' => $currentQty,
                'net_required' => round($netRequired, 2),
                'below_minimum' => $belowMinimum,
                'status' => $netRequired > 0 ? 'shortage' : ($belowMinimum ? 'low' : 'ok'),
            ]);
        }

        // Sort: shortages first, then low, then ok
        usort($results, fn ($a, $b) => ['shortage' => 0, 'low' => 1, 'ok' => 2][$a['status']] <=> ['shortage' => 0, 'low' => 1, 'ok' => 2][$b['status']]);

        return response()->json([
            'success' => true,
            'data' => [
                'requirements' => $results,
                'pending_orders' => $pendingOrders->count(),
            ],
        ]);
    }

    /**
     * Cost analysis report — period-based production cost breakdown.
     */
    public function costAnalysis(Request $request): JsonResponse
    {
        $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date',
        ]);

        $companyId = (int) $request->header('company');

        $data = $this->reportService->getCostAnalysis(
            $companyId,
            $request->query('from'),
            $request->query('to')
        );

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * Variance report — normative vs actual.
     */
    public function varianceReport(Request $request): JsonResponse
    {
        $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date',
        ]);

        $companyId = (int) $request->header('company');

        $data = $this->reportService->getVarianceReport(
            $companyId,
            $request->query('from'),
            $request->query('to')
        );

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * Wastage (утрасок) report.
     */
    public function wastageReport(Request $request): JsonResponse
    {
        $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date',
        ]);

        $companyId = (int) $request->header('company');

        $data = $this->reportService->getWastageReport(
            $companyId,
            $request->query('from'),
            $request->query('to')
        );

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * PDF: Работен налог (Production Order document).
     */
    public function orderPdf(Request $request, int $id)
    {
        $companyId = (int) $request->header('company');
        $order = ProductionOrder::where('company_id', $companyId)->findOrFail($id);

        $data = $this->reportService->getOrderPdfData($order);
        $this->setLocale($companyId);

        view()->share($data);

        $pdf = PDF::loadView('app.pdf.reports.manufacturing.production-order');

        if ($request->has('preview')) {
            return view('app.pdf.reports.manufacturing.production-order');
        }

        return $pdf->download("raboten_nalog_{$order->order_number}.pdf");
    }

    /**
     * PDF: Калкулација (Costing Calculation).
     */
    public function costingPdf(Request $request, int $id)
    {
        $companyId = (int) $request->header('company');
        $order = ProductionOrder::where('company_id', $companyId)->findOrFail($id);

        $data = $this->reportService->getOrderPdfData($order);
        $this->setLocale($companyId);

        view()->share($data);

        $pdf = PDF::loadView('app.pdf.reports.manufacturing.kalkulacija');

        if ($request->has('preview')) {
            return view('app.pdf.reports.manufacturing.kalkulacija');
        }

        return $pdf->download("kalkulacija_{$order->order_number}.pdf");
    }

    /**
     * PDF: Сопроизводствен налог (Co-production Order).
     */
    public function coProductionPdf(Request $request, int $id)
    {
        $companyId = (int) $request->header('company');
        $order = ProductionOrder::where('company_id', $companyId)->findOrFail($id);

        $data = $this->reportService->getOrderPdfData($order);
        $this->setLocale($companyId);

        if ($data['order']->coProductionOutputs->count() < 2) {
            return response()->json([
                'success' => false,
                'message' => 'This order does not have co-production outputs.',
            ], 422);
        }

        view()->share($data);

        $pdf = PDF::loadView('app.pdf.reports.manufacturing.co-production');

        if ($request->has('preview')) {
            return view('app.pdf.reports.manufacturing.co-production');
        }

        return $pdf->download("soproizvod_{$order->order_number}.pdf");
    }

    /**
     * PDF: Норматив (BOM printout).
     */
    public function normativPdf(Request $request, int $bomId)
    {
        $companyId = (int) $request->header('company');
        $bom = Bom::where('company_id', $companyId)
            ->with([
                'outputItem:id,name,unit_id',
                'outputItem.unit:id,name',
                'outputUnit:id,name',
                'lines.item:id,name,unit_id',
                'lines.item.unit:id,name',
                'lines.unit:id,name',
                'createdBy:id,name',
                'approvedBy:id,name',
                'company',
            ])
            ->findOrFail($bomId);

        $this->setLocale($companyId);

        $data = [
            'bom' => $bom,
            'company' => $bom->company,
        ];

        view()->share($data);

        $pdf = PDF::loadView('app.pdf.reports.manufacturing.normativ');

        if ($request->has('preview')) {
            return view('app.pdf.reports.manufacturing.normativ');
        }

        return $pdf->download("normativ_{$bom->code}.pdf");
    }

    /**
     * PDF: Приемница (Receipt note — finished goods received from production).
     */
    public function priemnicaPdf(Request $request, int $id)
    {
        $companyId = (int) $request->header('company');
        $order = ProductionOrder::where('company_id', $companyId)->findOrFail($id);

        $data = $this->reportService->getOrderPdfData($order);
        $this->setLocale($companyId);

        // Build items list: finished goods received from this production order
        $items = [];

        // Main output
        $items[] = [
            'code' => $order->outputItem?->id,
            'name' => $order->outputItem?->name ?? 'Готов производ',
            'unit' => $order->outputItem?->unit?->name ?? '',
            'quantity' => (float) $order->actual_quantity,
            'unit_price' => (int) $order->cost_per_unit,
            'note' => '',
        ];

        // Co-production outputs
        foreach ($order->coProductionOutputs as $output) {
            $items[] = [
                'code' => $output->item?->id,
                'name' => $output->item?->name ?? 'Сопроизвод',
                'unit' => $output->item?->unit?->name ?? '',
                'quantity' => (float) $output->quantity,
                'unit_price' => (int) $output->cost_per_unit,
                'note' => $output->is_primary ? 'Главен' : 'Споредeн',
            ];
        }

        view()->share(array_merge($data, [
            'document_number' => 'ПР-' . $order->order_number,
            'date' => $order->completed_at?->format('d.m.Y') ?? $order->order_date?->format('d.m.Y'),
            'warehouse_name' => $order->outputWarehouse?->name ?? '—',
            'items' => $items,
            'notes' => $order->notes,
        ]));

        $pdf = PDF::loadView('app.pdf.reports.manufacturing.priemnica');

        if ($request->has('preview')) {
            return view('app.pdf.reports.manufacturing.priemnica');
        }

        return $pdf->download("priemnica_{$order->order_number}.pdf");
    }

    /**
     * PDF: Издатница (Issue note — raw materials issued to production).
     */
    public function izdatnicaPdf(Request $request, int $id)
    {
        $companyId = (int) $request->header('company');
        $order = ProductionOrder::where('company_id', $companyId)->findOrFail($id);

        $data = $this->reportService->getOrderPdfData($order);
        $this->setLocale($companyId);

        // Build items list from materials consumed
        $items = [];
        foreach ($order->materials as $material) {
            $items[] = [
                'code' => $material->item?->id,
                'name' => $material->item?->name ?? 'Материјал',
                'unit' => $material->item?->unit?->name ?? '',
                'quantity' => (float) $material->actual_quantity,
                'unit_price' => (int) $material->actual_unit_cost,
                'note' => $material->notes ?? '',
            ];
        }

        view()->share(array_merge($data, [
            'document_number' => 'ИЗ-' . $order->order_number,
            'date' => $order->order_date?->format('d.m.Y'),
            'from_warehouse' => $order->materials->first()?->warehouse?->name ?? '—',
            'to_destination' => 'Производство',
            'requestor' => $order->createdBy?->name,
            'items' => $items,
            'notes' => $order->notes,
        ]));

        $pdf = PDF::loadView('app.pdf.reports.manufacturing.izdatnica');

        if ($request->has('preview')) {
            return view('app.pdf.reports.manufacturing.izdatnica');
        }

        return $pdf->download("izdatnica_{$order->order_number}.pdf");
    }

    /**
     * PDF: Требовница (Material requisition — planned materials needed).
     */
    public function trebovnicaPdf(Request $request, int $id)
    {
        $companyId = (int) $request->header('company');
        $order = ProductionOrder::where('company_id', $companyId)->findOrFail($id);

        $data = $this->reportService->getOrderPdfData($order);
        $this->setLocale($companyId);

        // Build items list: planned vs actual materials
        $items = [];
        foreach ($order->materials as $material) {
            $items[] = [
                'code' => $material->item?->id,
                'name' => $material->item?->name ?? 'Материјал',
                'unit' => $material->item?->unit?->name ?? '',
                'requested_qty' => (float) $material->planned_quantity,
                'approved_qty' => (float) $material->planned_quantity,
                'issued_qty' => (float) $material->actual_quantity,
                'note' => $material->notes ?? '',
            ];
        }

        view()->share(array_merge($data, [
            'document_number' => 'ТР-' . $order->order_number,
            'date' => $order->order_date?->format('d.m.Y'),
            'requestor' => $order->createdBy?->name,
            'department' => 'Производство',
            'items' => $items,
            'notes' => $order->notes,
        ]));

        $pdf = PDF::loadView('app.pdf.reports.manufacturing.trebovnica');

        if ($request->has('preview')) {
            return view('app.pdf.reports.manufacturing.trebovnica');
        }

        return $pdf->download("trebovnica_{$order->order_number}.pdf");
    }

    /**
     * PDF: Лагерска картица (Warehouse Kardex card for an item).
     */
    public function lagerskaKarticaPdf(Request $request, int $itemId)
    {
        $companyId = (int) $request->header('company');
        $item = Item::where('company_id', $companyId)->findOrFail($itemId);

        $this->setLocale($companyId);

        $warehouseId = $request->query('warehouse_id');
        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');

        $warehouse = null;
        if ($warehouseId) {
            $warehouse = Warehouse::where('company_id', $companyId)->find($warehouseId);
        }

        // Build movement query
        $query = StockMovement::where('company_id', $companyId)
            ->where('item_id', $item->id)
            ->orderBy('movement_date')
            ->orderBy('id');

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        // Opening balance: last movement before from_date
        $openingBalance = ['quantity' => 0, 'wac' => 0, 'value' => 0];
        if ($fromDate) {
            $lastBefore = (clone $query)->where('movement_date', '<', $fromDate)
                ->orderBy('movement_date', 'desc')
                ->orderBy('id', 'desc')
                ->first();

            if ($lastBefore) {
                $openingBalance = [
                    'quantity' => (float) $lastBefore->balance_quantity,
                    'wac' => $lastBefore->balance_quantity > 0
                        ? (int) round($lastBefore->balance_value / $lastBefore->balance_quantity)
                        : 0,
                    'value' => (int) $lastBefore->balance_value,
                ];
            }

            $query->where('movement_date', '>=', $fromDate);
        }

        if ($toDate) {
            $query->where('movement_date', '<=', $toDate);
        }

        $movements = $query->get()->map(function ($mv) {
            $sourceLabels = [
                'initial' => 'Почетно салдо',
                'bill_item' => 'Фактура-наб.',
                'invoice_item' => 'Фактура-прод.',
                'adjustment' => 'Корекција',
                'transfer_in' => 'Трансфер влез',
                'transfer_out' => 'Трансфер излез',
                'inventory_document' => 'Документ',
                'goods_receipt' => 'Примка',
                'production_consume' => 'Производство',
                'production_output' => 'Готов произв.',
                'production_byproduct' => 'Сопроизвод',
                'production_wastage' => 'Утрасок',
            ];

            return [
                'date' => $mv->movement_date?->format('d.m.Y'),
                'document' => $mv->source_id ? "#{$mv->source_id}" : '',
                'description' => $sourceLabels[$mv->source_type] ?? $mv->source_type,
                'quantity' => (float) $mv->quantity,
                'unit_price' => $mv->unit_cost ?? 0,
                'value' => $mv->total_cost ?? 0,
                'balance_qty' => (float) $mv->balance_quantity,
                'balance_wac' => $mv->balance_quantity > 0
                    ? (int) round($mv->balance_value / $mv->balance_quantity)
                    : 0,
                'balance_value' => (int) $mv->balance_value,
            ];
        })->toArray();

        // Closing balance from last movement
        $closingBalance = ! empty($movements)
            ? [
                'quantity' => end($movements)['balance_qty'],
                'wac' => end($movements)['balance_wac'],
                'value' => end($movements)['balance_value'],
            ]
            : $openingBalance;

        $company = Company::find($companyId);

        view()->share([
            'company' => $company,
            'item_name' => $item->name,
            'item_code' => $item->sku ?? $item->id,
            'unit_name' => $item->unit_name ?? '',
            'warehouse_name' => $warehouse?->name,
            'period_from' => $fromDate,
            'period_to' => $toDate,
            'opening_balance' => $openingBalance,
            'movements' => $movements,
            'closing_balance' => $closingBalance,
        ]);

        $pdf = PDF::loadView('app.pdf.reports.manufacturing.lagerska-kartica')
            ->setPaper('a4', 'landscape');

        if ($request->has('preview')) {
            return view('app.pdf.reports.manufacturing.lagerska-kartica');
        }

        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $item->name);

        return $pdf->download("lagerska_kartica_{$safeName}.pdf");
    }

    /**
     * Set locale from company settings.
     */
    private function setLocale(int $companyId): void
    {
        $locale = CompanySetting::getSetting('language', $companyId) ?: 'mk';
        App::setLocale($locale);
    }
}

// CLAUDE-CHECKPOINT
