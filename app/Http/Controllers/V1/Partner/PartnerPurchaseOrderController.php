<?php

namespace App\Http\Controllers\V1\Partner;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Mk\Models\PurchaseOrder;
use Modules\Mk\Services\PurchaseOrderService;

class PartnerPurchaseOrderController extends Controller
{
    protected PurchaseOrderService $service;

    public function __construct(PurchaseOrderService $service)
    {
        $this->service = $service;
    }

    /**
     * List purchase orders for a partner's client company.
     */
    public function index(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $result = $this->service->list($company, $request->query());

        return response()->json([
            'success' => true,
            'data' => $result['data'],
            'meta' => $result['meta'],
        ]);
    }

    /**
     * Show a single purchase order with items.
     */
    public function show(Request $request, int $company, int $id): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $po = PurchaseOrder::forCompany($company)
            ->with([
                'supplier',
                'items.item',
                'createdBy:id,name',
                'currency:id,name,code,symbol',
                'warehouse',
                'costCenter:id,name,code,color',
                'convertedBill',
                'goodsReceipts.items',
            ])
            ->where('id', $id)
            ->first();

        if (!$po) {
            return response()->json(['success' => false, 'message' => 'Purchase order not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $po,
        ]);
    }

    /**
     * Create a new purchase order for a partner's client company.
     */
    public function store(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $request->validate([
            'supplier_id' => 'nullable|integer',
            'po_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date',
            'currency_id' => 'nullable|integer',
            'warehouse_id' => 'nullable|integer',
            'cost_center_id' => 'nullable|integer|exists:cost_centers,id',
            'notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'nullable|integer',
            'items.*.name' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.price' => 'required|integer|min:0',
            'items.*.tax' => 'nullable|integer|min:0',
        ]);

        try {
            $po = $this->service->create($company, $request->all(), $request->user()?->id);

            return response()->json([
                'success' => true,
                'data' => $po,
                'message' => 'Purchase order created successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Three-way match check.
     */
    public function threeWayMatch(Request $request, int $company, int $id): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $po = PurchaseOrder::forCompany($company)->where('id', $id)->first();

        if (!$po) {
            return response()->json(['success' => false, 'message' => 'Purchase order not found'], 404);
        }

        $result = $this->service->threeWayMatch($po);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Update a draft purchase order.
     */
    public function update(Request $request, int $company, int $id): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $po = PurchaseOrder::forCompany($company)->where('id', $id)->first();
        if (!$po) {
            return response()->json(['success' => false, 'message' => 'Purchase order not found'], 404);
        }

        $request->validate([
            'supplier_id' => 'nullable|integer',
            'po_date' => 'nullable|date',
            'expected_delivery_date' => 'nullable|date',
            'currency_id' => 'nullable|integer',
            'warehouse_id' => 'nullable|integer',
            'cost_center_id' => 'nullable|integer|exists:cost_centers,id',
            'notes' => 'nullable|string|max:2000',
            'items' => 'nullable|array|min:1',
            'items.*.item_id' => 'nullable|integer',
            'items.*.name' => 'required_with:items|string|max:255',
            'items.*.quantity' => 'required_with:items|numeric|min:0.0001',
            'items.*.price' => 'required_with:items|integer|min:0',
            'items.*.tax' => 'nullable|integer|min:0',
        ]);

        try {
            $po = $this->service->update($po, $request->all());

            return response()->json([
                'success' => true,
                'data' => $po,
                'message' => 'Purchase order updated successfully',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Mark purchase order as sent (and email supplier).
     */
    public function send(Request $request, int $company, int $id): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $po = PurchaseOrder::forCompany($company)->where('id', $id)->first();
        if (!$po) {
            return response()->json(['success' => false, 'message' => 'Purchase order not found'], 404);
        }

        try {
            $result = $this->service->send($po);

            return response()->json([
                'success' => true,
                'data' => $result['po'],
                'email_sent_to' => $result['email_sent_to'],
                'message' => $result['email_sent_to']
                    ? 'Purchase order sent to ' . $result['email_sent_to']
                    : 'Purchase order marked as sent',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Receive goods for a purchase order.
     */
    public function receiveGoods(Request $request, int $company, int $id): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $po = PurchaseOrder::forCompany($company)->where('id', $id)->first();
        if (!$po) {
            return response()->json(['success' => false, 'message' => 'Purchase order not found'], 404);
        }

        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.purchase_order_item_id' => 'required|integer',
            'items.*.quantity_received' => 'required|numeric|min:0',
            'items.*.quantity_accepted' => 'nullable|numeric|min:0',
            'items.*.quantity_rejected' => 'nullable|numeric|min:0',
        ]);

        try {
            $receipt = $this->service->receiveGoods($po, $request->input('items'), $request->user()?->id);
            $po = $po->fresh(['items', 'supplier', 'goodsReceipts']);

            return response()->json([
                'success' => true,
                'data' => [
                    'receipt' => $receipt,
                    'purchase_order' => $po,
                ],
                'message' => 'Goods received successfully',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Convert purchase order to bill.
     */
    public function convertToBill(Request $request, int $company, int $id): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $po = PurchaseOrder::forCompany($company)->where('id', $id)->first();
        if (!$po) {
            return response()->json(['success' => false, 'message' => 'Purchase order not found'], 404);
        }

        try {
            $bill = $this->service->convertToBill($po);

            return response()->json([
                'success' => true,
                'data' => [
                    'bill' => $bill,
                    'purchase_order' => $po->fresh(),
                ],
                'message' => 'Bill created from purchase order',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Cancel a purchase order.
     */
    public function cancel(Request $request, int $company, int $id): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $po = PurchaseOrder::forCompany($company)->where('id', $id)->first();
        if (!$po) {
            return response()->json(['success' => false, 'message' => 'Purchase order not found'], 404);
        }

        try {
            $po = $this->service->cancel($po);

            return response()->json([
                'success' => true,
                'data' => $po,
                'message' => 'Purchase order cancelled',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Delete a purchase order (draft only).
     */
    public function destroy(Request $request, int $company, int $id): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $po = PurchaseOrder::forCompany($company)->where('id', $id)->first();
        if (!$po) {
            return response()->json(['success' => false, 'message' => 'Purchase order not found'], 404);
        }

        try {
            $this->service->deletePo($po);

            return response()->json([
                'success' => true,
                'message' => 'Purchase order deleted',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Generate PDF for a purchase order.
     */
    public function pdf(Request $request, int $company, int $id): Response
    {
        $partner = $this->getPartnerFromRequest($request);
        if (!$partner) {
            abort(404, 'Partner not found');
        }
        if (!$this->hasCompanyAccess($partner, $company)) {
            abort(403, 'No access to this company');
        }

        $po = PurchaseOrder::forCompany($company)
            ->with([
                'supplier.billingAddress',
                'items.item',
                'createdBy:id,name',
                'currency:id,name,code,symbol',
                'warehouse',
                'costCenter:id,name,code,color',
                'convertedBill',
                'goodsReceipts.items',
                'company.address',
            ])
            ->where('id', $id)
            ->first();

        if (!$po) {
            abort(404, 'Purchase order not found');
        }

        $companyModel = $po->company;

        $pdf = Pdf::loadView('app.pdf.reports.purchase-order', ['po' => $po, 'company' => $companyModel]);
        $pdf->setPaper('A4', 'portrait');

        $filename = "nabavka-{$po->po_number}.pdf";

        return $pdf->download($filename);
    }

    // ---- Partner access helpers (same pattern as PartnerCompensationController) ----

    /**
     * Get partner from authenticated request.
     */
    protected function getPartnerFromRequest(Request $request): ?Partner
    {
        $user = $request->user();

        if (!$user) {
            return null;
        }

        // Super admin gets a fake partner to pass validation
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

    /**
     * Check if partner has access to a company.
     */
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
