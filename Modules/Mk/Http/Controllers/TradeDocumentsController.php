<?php

namespace Modules\Mk\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Currency;
use App\Models\InventoryDocument;
use App\Models\Partner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Modules\Mk\Models\ImportCalculation;
use Modules\Mk\Models\Nivelacija;
use Modules\Mk\Services\TradeCalculationService;
use PDF;

class TradeDocumentsController extends Controller
{
    public function __construct(
        protected TradeCalculationService $calcService,
    ) {}

    // ==========================================
    // КАП — Калкулација на големопродажна цена
    // ==========================================

    /**
     * КАП calculation data for a bill (JSON).
     */
    public function kapData(Request $request, int $company, int $billId): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner || ! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $bill = Bill::where('company_id', $company)
            ->where('id', $billId)
            ->without(['creator', 'company'])
            ->with(['supplier', 'items.item.unit', 'items.taxes.taxType'])
            ->first();

        if (! $bill) {
            return response()->json(['error' => 'Bill not found'], 404);
        }

        $markupOverrides = $request->input('markup_overrides', []);
        $dependentCosts = $request->input('dependent_costs', []);

        $result = $this->calcService->calculateKap($bill, $markupOverrides, $dependentCosts);

        $marginCheck = $this->calcService->validateMarginCaps($result['items'], 'wholesale');

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $result['items'],
                'totals' => $result['totals'],
                'bill_number' => $bill->bill_number,
                'supplier_name' => $bill->supplier?->name ?? '',
                'bill_date' => $bill->bill_date instanceof \DateTimeInterface
                    ? $bill->bill_date->format('Y-m-d')
                    : ($bill->bill_date ?? ''),
            ],
            'margin_check' => $marginCheck,
        ]);
    }

    /**
     * КАП PDF export for a bill.
     */
    public function kapExport(Request $request, int $company, int $billId): Response
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner || ! $this->hasCompanyAccess($partner, $company)) {
            abort(403, 'Access denied');
        }

        $companyModel = Company::find($company);
        if (! $companyModel) {
            abort(404, 'Company not found');
        }
        $companyModel->load('address');

        $bill = Bill::where('company_id', $company)
            ->where('id', $billId)
            ->without(['creator', 'company'])
            ->with(['supplier', 'items.item.unit', 'items.taxes.taxType'])
            ->first();

        if (! $bill) {
            abort(404, 'Bill not found');
        }

        $markupOverrides = $request->input('markup_overrides', []);
        $dependentCosts = $request->input('dependent_costs', []);

        $result = $this->calcService->calculateKap($bill, $markupOverrides, $dependentCosts);

        $currency = Currency::find(CompanySetting::getSetting('currency', $company));

        $billDate = $bill->bill_date instanceof \DateTimeInterface
            ? $bill->bill_date->format('Y-m-d')
            : ($bill->bill_date ?? '');

        view()->share([
            'company' => $companyModel,
            'bill' => $bill,
            'bill_date' => substr((string) $billDate, 0, 10),
            'supplier_name' => $bill->supplier?->name ?? '',
            'supplier_address' => $bill->supplier?->address_street_1 ?? '',
            'items' => $result['items'],
            'totals' => $result['totals'],
            'dependent_costs' => $dependentCosts,
            'currency' => $currency,
        ]);

        $pdf = PDF::loadView('app.pdf.reports.trade-kap');
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download("kap_{$bill->bill_number}_{$billDate}.pdf");
    }

    // ==========================================
    // ПЛТ — Приемен лист во трговијата на мало
    // ==========================================

    /**
     * ПЛТ calculation data for a bill (JSON).
     */
    public function pltData(Request $request, int $company, int $billId): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner || ! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $bill = Bill::where('company_id', $company)
            ->where('id', $billId)
            ->without(['creator', 'company'])
            ->with(['supplier', 'items.item.unit', 'items.taxes.taxType'])
            ->first();

        if (! $bill) {
            return response()->json(['error' => 'Bill not found'], 404);
        }

        $markupOverrides = $request->input('markup_overrides', []);
        $result = $this->calcService->calculatePlt($bill, $markupOverrides);

        $marginCheck = $this->calcService->validateMarginCaps($result['items'], 'retail');

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $result['items'],
                'totals' => $result['totals'],
                'bill_number' => $bill->bill_number,
                'supplier_name' => $bill->supplier?->name ?? '',
                'bill_date' => $bill->bill_date instanceof \DateTimeInterface
                    ? $bill->bill_date->format('Y-m-d')
                    : ($bill->bill_date ?? ''),
            ],
            'margin_check' => $marginCheck,
        ]);
    }

    /**
     * ПЛТ PDF export for a bill.
     */
    public function pltExport(Request $request, int $company, int $billId): Response
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner || ! $this->hasCompanyAccess($partner, $company)) {
            abort(403, 'Access denied');
        }

        $companyModel = Company::find($company);
        if (! $companyModel) {
            abort(404, 'Company not found');
        }
        $companyModel->load('address');

        $bill = Bill::where('company_id', $company)
            ->where('id', $billId)
            ->without(['creator', 'company'])
            ->with(['supplier', 'items.item.unit', 'items.taxes.taxType'])
            ->first();

        if (! $bill) {
            abort(404, 'Bill not found');
        }

        $markupOverrides = $request->input('markup_overrides', []);
        $result = $this->calcService->calculatePlt($bill, $markupOverrides);

        $currency = Currency::find(CompanySetting::getSetting('currency', $company));

        $billDate = $bill->bill_date instanceof \DateTimeInterface
            ? $bill->bill_date->format('Y-m-d')
            : ($bill->bill_date ?? '');

        view()->share([
            'company' => $companyModel,
            'bill' => $bill,
            'bill_date' => substr((string) $billDate, 0, 10),
            'supplier_name' => $bill->supplier?->name ?? '',
            'supplier_address' => $bill->supplier?->address_street_1 ?? '',
            'items' => $result['items'],
            'totals' => $result['totals'],
            'currency' => $currency,
        ]);

        $pdf = PDF::loadView('app.pdf.reports.trade-plt');
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download("plt_{$bill->bill_number}_{$billDate}.pdf");
    }
    // CLAUDE-CHECKPOINT

    // ==========================================
    // Нивелација CRUD
    // ==========================================

    /**
     * List нивелации for a company.
     */
    public function nivelaciiIndex(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner || ! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $query = Nivelacija::whereCompany($company)
            ->with(['sourceBill' => fn ($q) => $q->without(['supplier', 'currency', 'company']), 'warehouse:id,name', 'creator'])
            ->orderBy('document_date', 'desc');

        if ($status = $request->query('status')) {
            $query->whereStatus($status);
        }

        if ($fromDate = $request->query('from_date')) {
            $query->where('document_date', '>=', $fromDate);
        }

        if ($toDate = $request->query('to_date')) {
            $query->where('document_date', '<=', $toDate);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('document_number', 'LIKE', '%' . $search . '%')
                    ->orWhere('reason', 'LIKE', '%' . $search . '%');
            });
        }

        $limit = $request->query('limit', 15);
        if ($limit === 'all') {
            return response()->json([
                'success' => true,
                'data' => $query->get(),
            ]);
        }

        $nivelacii = $query->paginate((int) $limit);

        return response()->json([
            'success' => true,
            'data' => $nivelacii->items(),
            'meta' => [
                'current_page' => $nivelacii->currentPage(),
                'last_page' => $nivelacii->lastPage(),
                'per_page' => $nivelacii->perPage(),
                'total' => $nivelacii->total(),
            ],
        ]);
    }

    /**
     * Create a draft нивелација.
     */
    public function nivelaciiStore(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner || ! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        try {
            $validated = $request->validate([
                'type' => 'required|in:price_change,discount,supplier_change',
                'document_date' => 'required|date',
                'reason' => 'required|string|max:500',
                'source_bill_id' => 'nullable|integer',
                'warehouse_id' => 'nullable|integer',
                'items' => 'required|array|min:1',
                'items.*.item_id' => 'required|integer',
                'items.*.quantity_on_hand' => 'required|numeric|min:0',
                'items.*.old_retail_price' => 'required|integer|min:0',
                'items.*.new_retail_price' => 'required|integer|min:0',
                'items.*.old_wholesale_price' => 'nullable|integer|min:0',
                'items.*.new_wholesale_price' => 'nullable|integer|min:0',
                'items.*.old_markup_percent' => 'nullable|numeric|min:0',
                'items.*.new_markup_percent' => 'nullable|numeric|min:0',
                'items.*.notes' => 'nullable|string|max:255',
            ]);
        } catch (ValidationException $e) {
            throw $e;
        }

        $nivelacijaItems = [];
        $totalDifference = 0;

        foreach ($validated['items'] as $itemData) {
            $priceDiff = ($itemData['new_retail_price'] ?? 0) - ($itemData['old_retail_price'] ?? 0);
            $lineTotalDiff = (int) round($priceDiff * ($itemData['quantity_on_hand'] ?? 0));
            $totalDifference += $lineTotalDiff;

            $nivelacijaItems[] = array_merge($itemData, [
                'warehouse_id' => $itemData['warehouse_id'] ?? $validated['warehouse_id'] ?? null,
                'price_difference' => $priceDiff,
                'total_difference' => $lineTotalDiff,
            ]);
        }

        // Validate all item_ids belong to this company
        $itemIds = array_column($validated['items'], 'item_id');
        $existingCount = \App\Models\Item::where('company_id', $company)
            ->whereIn('id', $itemIds)
            ->count();
        if ($existingCount !== count($itemIds)) {
            return response()->json(['error' => 'Некои артикли не се пронајдени во оваа компанија.'], 422);
        }

        try {
            $nivelacija = Nivelacija::create([
                'company_id' => $company,
                'document_date' => $validated['document_date'],
                'type' => $validated['type'],
                'status' => Nivelacija::STATUS_DRAFT,
                'reason' => $validated['reason'],
                'source_bill_id' => $validated['source_bill_id'] ?? null,
                'warehouse_id' => $validated['warehouse_id'] ?? null,
                'total_difference' => $totalDifference,
                'created_by' => auth()->id(),
            ]);

            foreach ($nivelacijaItems as $itemData) {
                $nivelacija->items()->create($itemData);
            }

            return response()->json([
                'success' => true,
                'data' => $nivelacija->load('items.item'),
            ], 201);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Nivelacija create error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Failed to create nivelacija: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show a single нивелација with items.
     */
    public function nivelaciiShow(Request $request, int $company, int $id): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner || ! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $nivelacija = Nivelacija::whereCompany($company)
            ->with(['items.item.unit', 'sourceBill' => fn ($q) => $q->without(['supplier', 'currency', 'company']), 'warehouse:id,name', 'approver', 'creator'])
            ->where('id', $id)
            ->first();

        if (! $nivelacija) {
            return response()->json(['error' => 'Nivelacija not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $nivelacija,
        ]);
    }

    /**
     * Update a draft нивелација.
     */
    public function nivelaciiUpdate(Request $request, int $company, int $id): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner || ! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $nivelacija = Nivelacija::whereCompany($company)->where('id', $id)->first();
        if (! $nivelacija) {
            return response()->json(['error' => 'Nivelacija not found'], 404);
        }

        if (! $nivelacija->isDraft()) {
            return response()->json(['error' => 'Само нацрт нивелации може да се менуваат.'], 422);
        }

        try {
            $validated = $request->validate([
                'reason' => 'sometimes|string|max:500',
                'document_date' => 'sometimes|date',
                'warehouse_id' => 'nullable|integer',
                'items' => 'sometimes|array|min:1',
                'items.*.item_id' => 'required_with:items|integer',
                'items.*.quantity_on_hand' => 'required_with:items|numeric|min:0',
                'items.*.old_retail_price' => 'required_with:items|integer|min:0',
                'items.*.new_retail_price' => 'required_with:items|integer|min:0',
                'items.*.old_wholesale_price' => 'nullable|integer|min:0',
                'items.*.new_wholesale_price' => 'nullable|integer|min:0',
                'items.*.old_markup_percent' => 'nullable|numeric|min:0',
                'items.*.new_markup_percent' => 'nullable|numeric|min:0',
                'items.*.notes' => 'nullable|string|max:255',
            ]);
        } catch (ValidationException $e) {
            throw $e;
        }

        $nivelacija->update(array_filter([
            'reason' => $validated['reason'] ?? null,
            'document_date' => $validated['document_date'] ?? null,
            'warehouse_id' => array_key_exists('warehouse_id', $validated) ? $validated['warehouse_id'] : null,
        ], fn ($v) => $v !== null));

        if (isset($validated['items'])) {
            $nivelacija->items()->delete();
            $totalDifference = 0;

            foreach ($validated['items'] as $itemData) {
                $priceDiff = ($itemData['new_retail_price'] ?? 0) - ($itemData['old_retail_price'] ?? 0);
                $lineTotalDiff = (int) round($priceDiff * ($itemData['quantity_on_hand'] ?? 0));
                $totalDifference += $lineTotalDiff;

                $nivelacija->items()->create(array_merge($itemData, [
                    'warehouse_id' => $itemData['warehouse_id'] ?? $validated['warehouse_id'] ?? $nivelacija->warehouse_id,
                    'price_difference' => $priceDiff,
                    'total_difference' => $lineTotalDiff,
                ]));
            }

            $nivelacija->update(['total_difference' => $totalDifference]);
        }

        return response()->json([
            'success' => true,
            'data' => $nivelacija->fresh()->load('items.item'),
        ]);
    }

    /**
     * Approve a draft нивелација — updates item prices.
     */
    public function nivelaciiApprove(Request $request, int $company, int $id): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner || ! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $nivelacija = Nivelacija::whereCompany($company)->where('id', $id)->first();
        if (! $nivelacija) {
            return response()->json(['error' => 'Nivelacija not found'], 404);
        }

        try {
            $this->calcService->approveNivelacija($nivelacija);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'data' => $nivelacija->fresh()->load('items.item'),
        ]);
    }

    /**
     * Void an approved нивелација — reverts item prices.
     */
    public function nivelaciiVoid(Request $request, int $company, int $id): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner || ! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $nivelacija = Nivelacija::whereCompany($company)->where('id', $id)->first();
        if (! $nivelacija) {
            return response()->json(['error' => 'Nivelacija not found'], 404);
        }

        try {
            $this->calcService->voidNivelacija($nivelacija);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'data' => $nivelacija->fresh()->load('items.item'),
        ]);
    }

    /**
     * Export нивелација as PDF.
     */
    public function nivelaciiExport(Request $request, int $company, int $id): Response
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner || ! $this->hasCompanyAccess($partner, $company)) {
            abort(403, 'Access denied');
        }

        $companyModel = Company::find($company);
        if (! $companyModel) {
            abort(404, 'Company not found');
        }
        $companyModel->load('address');

        $nivelacija = Nivelacija::whereCompany($company)
            ->with(['items.item.unit', 'sourceBill' => fn ($q) => $q->without(['supplier', 'currency', 'company']), 'warehouse:id,name', 'approver'])
            ->where('id', $id)
            ->first();

        if (! $nivelacija) {
            abort(404, 'Nivelacija not found');
        }

        $currency = Currency::find(CompanySetting::getSetting('currency', $company));

        view()->share([
            'company' => $companyModel,
            'nivelacija' => $nivelacija,
            'currency' => $currency,
        ]);

        $pdf = PDF::loadView('app.pdf.reports.trade-nivelacija');
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download("nivelacija_{$nivelacija->document_number}_{$nivelacija->document_date?->format('Y-m-d')}.pdf");
    }

    /**
     * Check for items with pending price changes needing нивелација.
     */
    public function nivelaciiPendingCheck(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner || ! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $billId = $request->query('bill_id');
        $priceType = $request->query('price_type', 'retail');

        if (! $billId) {
            return response()->json(['error' => 'bill_id is required'], 422);
        }

        $bill = Bill::where('company_id', $company)
            ->where('id', $billId)
            ->without(['creator', 'company'])
            ->with(['items.item.unit', 'items.taxes.taxType'])
            ->first();

        if (! $bill) {
            return response()->json(['error' => 'Bill not found'], 404);
        }

        $markupOverrides = $request->input('markup_overrides', []);

        $calculated = $priceType === 'retail'
            ? $this->calcService->calculatePlt($bill, $markupOverrides)
            : $this->calcService->calculateKap($bill, $markupOverrides);

        $pending = [];
        foreach ($calculated['items'] as $item) {
            $itemId = $item['item_id'] ?? null;
            if (! $itemId) {
                continue;
            }

            $dbItem = \App\Models\Item::find($itemId);
            if (! $dbItem) {
                continue;
            }

            $field = $priceType === 'retail' ? 'retail_price' : 'wholesale_price';
            $currentPrice = $dbItem->{$field} ?? 0;
            $newPrice = $item['unit_price_prodazhna'] ?? 0;

            if ($currentPrice !== $newPrice && $newPrice > 0) {
                $pending[] = [
                    'item_id' => $itemId,
                    'name' => $dbItem->name,
                    'current_price' => $currentPrice,
                    'new_price' => $newPrice,
                    'difference' => $newPrice - $currentPrice,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => $pending,
            'count' => count($pending),
        ]);
    }

    // ==========================================
    // Преносница PDF
    // ==========================================

    /**
     * Export Преносница PDF for an InventoryDocument of type transfer.
     */
    public function prenosnicaExport(Request $request, int $company, int $documentId): Response
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner || ! $this->hasCompanyAccess($partner, $company)) {
            abort(403, 'Access denied');
        }

        $companyModel = Company::find($company);
        if (! $companyModel) {
            abort(404, 'Company not found');
        }
        $companyModel->load('address');

        $doc = InventoryDocument::where('company_id', $company)
            ->where('id', $documentId)
            ->where('document_type', InventoryDocument::TYPE_TRANSFER)
            ->with(['warehouse', 'destinationWarehouse', 'items.item.unit'])
            ->first();

        if (! $doc) {
            abort(404, 'Transfer document not found');
        }

        $currency = Currency::find(CompanySetting::getSetting('currency', $company));

        $docData = [
            'document_number' => $doc->document_number,
            'document_date' => $doc->document_date?->format('d.m.Y') ?? '',
            'from_warehouse' => $doc->warehouse?->name ?? '',
            'to_warehouse' => $doc->destinationWarehouse?->name ?? '',
            'reason' => $doc->notes ?? null,
            'items' => $doc->items->map(function ($item) {
                return [
                    'sku' => $item->item?->sku ?? '',
                    'item_name' => $item->item?->name ?? '',
                    'unit' => $item->item?->unit?->name ?? '',
                    'quantity' => $item->quantity ?? 0,
                    'unit_cost' => $item->unit_cost ?? 0,
                    'total_cost' => $item->total_cost ?? (($item->quantity ?? 0) * ($item->unit_cost ?? 0)),
                    'notes' => $item->notes ?? '',
                ];
            })->toArray(),
        ];

        view()->share([
            'company' => $companyModel,
            'document' => $docData,
            'currency' => $currency,
        ]);

        $pdf = PDF::loadView('app.pdf.reports.trade-prenosnica');
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download("prenosnica_{$doc->document_number}_{$doc->document_date?->format('Y-m-d')}.pdf");
    }

    // ==========================================
    // Apply Prices (from ПЛТ/КАП calculation)
    // ==========================================

    /**
     * Apply calculated prices to items and optionally create auto-нивелација.
     */
    public function applyPrices(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner || ! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        try {
            $validated = $request->validate([
                'bill_id' => 'required|integer',
                'price_type' => 'required|in:retail,wholesale',
                'markup_overrides' => 'nullable|array',
                'dependent_costs' => 'nullable|array',
                'create_nivelacija' => 'nullable|boolean',
            ]);
        } catch (ValidationException $e) {
            throw $e;
        }

        $bill = Bill::where('company_id', $company)
            ->where('id', $validated['bill_id'])
            ->without(['creator', 'company'])
            ->with(['items.item.unit', 'items.taxes.taxType'])
            ->first();

        if (! $bill) {
            return response()->json(['error' => 'Bill not found'], 404);
        }

        $markupOverrides = $validated['markup_overrides'] ?? [];
        $dependentCosts = $validated['dependent_costs'] ?? [];
        $priceType = $validated['price_type'];

        $calculated = $priceType === 'retail'
            ? $this->calcService->calculatePlt($bill, $markupOverrides)
            : $this->calcService->calculateKap($bill, $markupOverrides, $dependentCosts);

        $result = $this->calcService->applyPricesToItems($calculated['items'], $priceType);

        $nivelacija = null;
        if (($validated['create_nivelacija'] ?? true) && ! empty($result['changed'])) {
            $nivelacija = $this->calcService->createAutoNivelacija(
                $company,
                $result['changed'],
                "Автоматска нивелација од фактура {$bill->bill_number}",
                $bill->id,
            );
        }

        return response()->json([
            'success' => true,
            'data' => [
                'changed' => $result['changed'],
                'unchanged' => $result['unchanged'],
                'nivelacija' => $nivelacija,
            ],
        ]);
    }

    // ==========================================
    // Влезна калкулација — Import Cost Calculation
    // ==========================================

    public function importCalcIndex(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner || ! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $query = ImportCalculation::whereCompany($company)
            ->with(['supplierBill' => fn ($q) => $q->without(['creator', 'company'])->with('supplier:id,name'), 'warehouse:id,name', 'creator:id,name'])
            ->orderBy('document_date', 'desc')
            ->orderBy('id', 'desc');

        if ($request->filled('status')) {
            $query->whereStatus($request->input('status'));
        }
        if ($request->filled('from_date') || $request->filled('to_date')) {
            $query->whereDateRange($request->input('from_date'), $request->input('to_date'));
        }
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('document_number', 'like', "%{$search}%")
                    ->orWhere('supplier_name', 'like', "%{$search}%")
                    ->orWhere('supplier_invoice_number', 'like', "%{$search}%");
            });
        }

        $limit = $request->input('limit', 15);
        $data = $limit === 'all' ? $query->get() : $query->paginate((int) $limit);

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function importCalcStore(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner || ! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        try {
            $validated = $request->validate([
                'document_date' => 'required|date',
                'supplier_bill_id' => 'nullable|integer',
                'supplier_name' => 'nullable|string|max:255',
                'supplier_invoice_number' => 'nullable|string|max:100',
                'currency_code' => 'nullable|string|max:3',
                'exchange_rate' => 'required|numeric|min:0.000001',
                'warehouse_id' => 'required|integer',
                'transport_amount' => 'nullable|integer|min:0',
                'forwarding_amount' => 'nullable|integer|min:0',
                'other_costs_amount' => 'nullable|integer|min:0',
                'vat_rate' => 'nullable|numeric|min:0|max:100',
                'notes' => 'nullable|string',
                'items' => 'required|array|min:1',
                'items.*.item_id' => 'nullable|integer',
                'items.*.tariff_heading' => 'required|string|max:20',
                'items.*.description' => 'required|string|max:500',
                'items.*.quantity' => 'required|numeric|min:0.0001',
                'items.*.unit' => 'nullable|string|max:20',
                'items.*.unit_price_fcy' => 'required|integer|min:0',
                'items.*.customs_duty_rate' => 'required|numeric|min:0|max:100',
                'items.*.notes' => 'nullable|string|max:255',
            ]);
        } catch (ValidationException $e) {
            throw $e;
        }

        try {
            $calc = ImportCalculation::create([
                'company_id' => $company,
                'document_date' => $validated['document_date'],
                'status' => ImportCalculation::STATUS_DRAFT,
                'supplier_bill_id' => $validated['supplier_bill_id'] ?? null,
                'supplier_name' => $validated['supplier_name'] ?? null,
                'supplier_invoice_number' => $validated['supplier_invoice_number'] ?? null,
                'currency_code' => $validated['currency_code'] ?? 'EUR',
                'exchange_rate' => $validated['exchange_rate'],
                'warehouse_id' => $validated['warehouse_id'],
                'transport_amount' => $validated['transport_amount'] ?? 0,
                'forwarding_amount' => $validated['forwarding_amount'] ?? 0,
                'other_costs_amount' => $validated['other_costs_amount'] ?? 0,
                'vat_rate' => $validated['vat_rate'] ?? 18.00,
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['items'] as $itemData) {
                $calc->items()->create([
                    'item_id' => $itemData['item_id'] ?? null,
                    'tariff_heading' => $itemData['tariff_heading'],
                    'description' => $itemData['description'],
                    'quantity' => $itemData['quantity'],
                    'unit' => $itemData['unit'] ?? 'ком.',
                    'unit_price_fcy' => $itemData['unit_price_fcy'],
                    'customs_duty_rate' => $itemData['customs_duty_rate'],
                    'notes' => $itemData['notes'] ?? null,
                ]);
            }

            $result = $this->calcService->calculateImportCosts($calc);

            foreach ($result['items'] as $computedItem) {
                $calc->items()->where('id', $computedItem['id'])->update([
                    'invoice_value_fcy' => $computedItem['invoice_value_fcy'],
                    'invoice_value_mkd' => $computedItem['invoice_value_mkd'],
                    'transport_allocated' => $computedItem['transport_allocated'],
                    'customs_base' => $computedItem['customs_base'],
                    'customs_duty_amount' => $computedItem['customs_duty_amount'],
                    'forwarding_allocated' => $computedItem['forwarding_allocated'],
                    'other_costs_allocated' => $computedItem['other_costs_allocated'],
                    'landed_cost_before_vat' => $computedItem['landed_cost_before_vat'],
                    'import_vat_amount' => $computedItem['import_vat_amount'],
                    'total_landed_cost' => $computedItem['total_landed_cost'],
                    'unit_landed_cost' => $computedItem['unit_landed_cost'],
                ]);
            }

            $calc->update([
                'total_invoice_value_mkd' => $result['totals']['total_invoice_mkd'],
                'customs_duty_total' => $result['totals']['customs_duty'],
                'import_vat_total' => $result['totals']['import_vat'],
                'total_landed_cost' => $result['totals']['total_landed_cost'],
            ]);

            return response()->json([
                'success' => true,
                'data' => $calc->fresh()->load('items.item'),
            ], 201);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Import calculation create error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Failed to create import calculation: ' . $e->getMessage()], 500);
        }
    }

    public function importCalcFromBill(Request $request, int $company, int $billId): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner || ! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $bill = Bill::where('company_id', $company)
            ->where('id', $billId)
            ->without(['creator', 'company'])
            ->with(['supplier:id,name', 'items.item.unit'])
            ->first();

        if (! $bill) {
            return response()->json(['error' => 'Bill not found'], 404);
        }

        $items = $bill->items->map(fn ($bi) => [
            'item_id' => $bi->item_id,
            'description' => $bi->item?->name ?? $bi->name ?? '',
            'quantity' => $bi->quantity ?? 1,
            'unit' => $bi->item?->unit?->name ?? 'ком.',
            'unit_price_fcy' => $bi->price ?? 0,
            'tariff_heading' => '',
            'customs_duty_rate' => 0,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'supplier_name' => $bill->supplier?->name ?? '',
                'supplier_invoice_number' => $bill->bill_number ?? '',
                'exchange_rate' => $bill->exchange_rate ?? 1,
                'items' => $items,
            ],
        ]);
    }

    public function importCalcShow(Request $request, int $company, int $id): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner || ! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $calc = ImportCalculation::whereCompany($company)
            ->with([
                'items.item.unit',
                'supplierBill' => fn ($q) => $q->without(['creator', 'company'])->with('supplier:id,name'),
                'warehouse:id,name',
                'approver:id,name',
                'creator:id,name',
            ])
            ->where('id', $id)
            ->first();

        if (! $calc) {
            return response()->json(['error' => 'Import calculation not found'], 404);
        }

        $tariffSummary = [];
        foreach ($calc->items->groupBy('tariff_heading') as $heading => $items) {
            $tariffSummary[] = [
                'tariff_heading' => $heading,
                'items_count' => $items->count(),
                'customs_base_total' => $items->sum('customs_base'),
                'duty_rate' => (float) $items->first()->customs_duty_rate,
                'duty_amount' => $items->sum('customs_duty_amount'),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $calc,
            'tariff_summary' => $tariffSummary,
        ]);
    }

    public function importCalcUpdate(Request $request, int $company, int $id): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner || ! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $calc = ImportCalculation::whereCompany($company)->where('id', $id)->first();
        if (! $calc) {
            return response()->json(['error' => 'Import calculation not found'], 404);
        }
        if (! $calc->isDraft()) {
            return response()->json(['error' => 'Само нацрт калкулации може да се менуваат.'], 422);
        }

        try {
            $validated = $request->validate([
                'document_date' => 'sometimes|date',
                'supplier_bill_id' => 'nullable|integer',
                'supplier_name' => 'nullable|string|max:255',
                'supplier_invoice_number' => 'nullable|string|max:100',
                'currency_code' => 'nullable|string|max:3',
                'exchange_rate' => 'sometimes|numeric|min:0.000001',
                'warehouse_id' => 'sometimes|integer',
                'transport_amount' => 'nullable|integer|min:0',
                'forwarding_amount' => 'nullable|integer|min:0',
                'other_costs_amount' => 'nullable|integer|min:0',
                'vat_rate' => 'nullable|numeric|min:0|max:100',
                'notes' => 'nullable|string',
                'items' => 'sometimes|array|min:1',
                'items.*.item_id' => 'nullable|integer',
                'items.*.tariff_heading' => 'required_with:items|string|max:20',
                'items.*.description' => 'required_with:items|string|max:500',
                'items.*.quantity' => 'required_with:items|numeric|min:0.0001',
                'items.*.unit' => 'nullable|string|max:20',
                'items.*.unit_price_fcy' => 'required_with:items|integer|min:0',
                'items.*.customs_duty_rate' => 'required_with:items|numeric|min:0|max:100',
                'items.*.notes' => 'nullable|string|max:255',
            ]);
        } catch (ValidationException $e) {
            throw $e;
        }

        $updateData = [];
        foreach (['document_date', 'currency_code', 'exchange_rate', 'warehouse_id', 'vat_rate'] as $field) {
            if (isset($validated[$field])) {
                $updateData[$field] = $validated[$field];
            }
        }
        foreach (['supplier_bill_id', 'supplier_name', 'supplier_invoice_number', 'notes'] as $field) {
            if (array_key_exists($field, $validated)) {
                $updateData[$field] = $validated[$field];
            }
        }
        foreach (['transport_amount', 'forwarding_amount', 'other_costs_amount'] as $field) {
            if (array_key_exists($field, $validated)) {
                $updateData[$field] = $validated[$field] ?? 0;
            }
        }

        if (! empty($updateData)) {
            $calc->update($updateData);
        }

        if (isset($validated['items'])) {
            $calc->items()->delete();
            foreach ($validated['items'] as $itemData) {
                $calc->items()->create([
                    'item_id' => $itemData['item_id'] ?? null,
                    'tariff_heading' => $itemData['tariff_heading'],
                    'description' => $itemData['description'],
                    'quantity' => $itemData['quantity'],
                    'unit' => $itemData['unit'] ?? 'ком.',
                    'unit_price_fcy' => $itemData['unit_price_fcy'],
                    'customs_duty_rate' => $itemData['customs_duty_rate'],
                    'notes' => $itemData['notes'] ?? null,
                ]);
            }

            $calc = $calc->fresh();
            $result = $this->calcService->calculateImportCosts($calc);
            foreach ($result['items'] as $computedItem) {
                $calc->items()->where('id', $computedItem['id'])->update([
                    'invoice_value_fcy' => $computedItem['invoice_value_fcy'],
                    'invoice_value_mkd' => $computedItem['invoice_value_mkd'],
                    'transport_allocated' => $computedItem['transport_allocated'],
                    'customs_base' => $computedItem['customs_base'],
                    'customs_duty_amount' => $computedItem['customs_duty_amount'],
                    'forwarding_allocated' => $computedItem['forwarding_allocated'],
                    'other_costs_allocated' => $computedItem['other_costs_allocated'],
                    'landed_cost_before_vat' => $computedItem['landed_cost_before_vat'],
                    'import_vat_amount' => $computedItem['import_vat_amount'],
                    'total_landed_cost' => $computedItem['total_landed_cost'],
                    'unit_landed_cost' => $computedItem['unit_landed_cost'],
                ]);
            }
            $calc->update([
                'total_invoice_value_mkd' => $result['totals']['total_invoice_mkd'],
                'customs_duty_total' => $result['totals']['customs_duty'],
                'import_vat_total' => $result['totals']['import_vat'],
                'total_landed_cost' => $result['totals']['total_landed_cost'],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $calc->fresh()->load('items.item'),
        ]);
    }

    public function importCalcApprove(Request $request, int $company, int $id): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner || ! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $calc = ImportCalculation::whereCompany($company)->where('id', $id)->first();
        if (! $calc) {
            return response()->json(['error' => 'Import calculation not found'], 404);
        }

        try {
            $this->calcService->approveImportCalculation($calc);

            return response()->json([
                'success' => true,
                'message' => 'Влезната калкулација е одобрена. Залихата е евидентирана.',
                'data' => $calc->fresh()->load('items.item'),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function importCalcVoid(Request $request, int $company, int $id): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner || ! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $calc = ImportCalculation::whereCompany($company)->where('id', $id)->first();
        if (! $calc) {
            return response()->json(['error' => 'Import calculation not found'], 404);
        }

        try {
            $this->calcService->voidImportCalculation($calc);

            return response()->json([
                'success' => true,
                'message' => 'Влезната калкулација е поништена. Залихата е сторнирана.',
                'data' => $calc->fresh()->load('items.item'),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function importCalcExport(Request $request, int $company, int $id): Response
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner || ! $this->hasCompanyAccess($partner, $company)) {
            abort(403);
        }

        $calc = ImportCalculation::whereCompany($company)
            ->with(['items.item.unit', 'supplierBill.supplier', 'warehouse', 'creator'])
            ->where('id', $id)
            ->first();

        if (! $calc) {
            abort(404);
        }

        $companyModel = Company::find($company);
        $currency = Currency::where('code', 'MKD')->first();

        $tariffSummary = [];
        foreach ($calc->items->groupBy('tariff_heading') as $heading => $items) {
            $tariffSummary[] = [
                'tariff_heading' => $heading,
                'items_count' => $items->count(),
                'customs_base_total' => $items->sum('customs_base'),
                'duty_rate' => (float) $items->first()->customs_duty_rate,
                'duty_amount' => $items->sum('customs_duty_amount'),
            ];
        }

        $pdf = PDF::loadView('app.pdf.reports.trade-import-calculation', [
            'company' => $companyModel,
            'calc' => $calc,
            'currency' => $currency,
            'tariff_summary' => $tariffSummary,
        ]);

        $pdf->setPaper('a4', 'landscape');

        $filename = "vlezna_kalkulacija_{$calc->document_number}_{$calc->document_date->format('Ymd')}.pdf";

        return $pdf->stream($filename);
    }

    // ==========================================
    // Partner Access Helpers
    // ==========================================

    public function listBillsForCompany(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner || ! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $query = Bill::where('company_id', $company)
            ->without(['supplier', 'currency', 'company'])
            ->with('supplier:id,name')
            ->orderBy('bill_date', 'desc');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('bill_number', 'like', "%{$search}%")
                    ->orWhereHas('supplier', fn ($sq) => $sq->where('name', 'like', "%{$search}%"));
            });
        }

        $limit = $request->input('limit', 50);
        $data = $limit === 'all' ? $query->get() : $query->paginate((int) $limit);

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function listSuppliersForCompany(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner || ! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $suppliers = \App\Models\Supplier::where('company_id', $company)
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();

        return response()->json(['success' => true, 'data' => $suppliers]);
    }

    protected function getPartnerFromRequest(Request $request): ?Partner
    {
        $user = $request->user();

        if (! $user) {
            return null;
        }

        if ($user->role === 'super admin') {
            $fakePartner = new Partner();
            $fakePartner->id = 0;
            $fakePartner->user_id = $user->id;
            $fakePartner->name = 'Super Admin';
            $fakePartner->email = $user->email;
            $fakePartner->is_super_admin = true;

            return $fakePartner;
        }

        return Partner::where('user_id', $user->id)->first();
    }

    protected function hasCompanyAccess(Partner $partner, int $companyId): bool
    {
        if ($partner->is_super_admin ?? false) {
            return true;
        }

        return $partner->companies()
            ->where('companies.id', $companyId)
            ->where('partner_company_links.is_active', true)
            ->exists();
    }
}

// CLAUDE-CHECKPOINT
