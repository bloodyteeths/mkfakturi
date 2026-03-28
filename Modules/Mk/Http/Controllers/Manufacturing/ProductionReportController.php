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
