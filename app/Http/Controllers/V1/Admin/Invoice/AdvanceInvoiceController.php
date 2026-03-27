<?php

namespace App\Http\Controllers\V1\Admin\Invoice;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\AdvanceInvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API endpoints for advance invoice operations.
 *
 * Advance invoices (Аванс Фактура) are created through the normal
 * InvoicesController with type='advance'. This controller handles
 * the settlement flow: linking advances to final invoices.
 */
class AdvanceInvoiceController extends Controller
{
    protected AdvanceInvoiceService $service;

    public function __construct(AdvanceInvoiceService $service)
    {
        $this->service = $service;
    }

    /**
     * Get unsettled advance invoices for a customer.
     * Used when creating a final invoice to select which advances to deduct.
     *
     * GET /api/v1/invoices/unsettled-advances?customer_id=X
     */
    public function unsettledAdvances(Request $request): JsonResponse
    {
        $request->validate([
            'customer_id' => 'required|integer|exists:customers,id',
        ]);

        $companyId = (int) $request->header('company');
        $customerId = (int) $request->input('customer_id');

        $advances = $this->service->getUnsettledAdvances($customerId, $companyId);

        return response()->json([
            'data' => $advances->map(fn (Invoice $adv) => [
                'id' => $adv->id,
                'invoice_number' => $adv->invoice_number,
                'invoice_date' => $adv->invoice_date,
                'total' => $adv->total,
                'sub_total' => $adv->sub_total,
                'tax' => $adv->tax,
                'due_amount' => $adv->due_amount,
                'paid_status' => $adv->paid_status,
                'customer' => [
                    'id' => $adv->customer->id ?? null,
                    'name' => $adv->customer->name ?? null,
                ],
            ]),
        ]);
    }

    /**
     * Settle advance invoices against a final invoice.
     *
     * POST /api/v1/invoices/{invoice}/settle-advances
     * Body: { "advance_invoice_ids": [1, 2, 3] }
     */
    public function settle(Request $request, Invoice $invoice): JsonResponse
    {
        $this->authorize('update', $invoice);

        $request->validate([
            'advance_invoice_ids' => 'required|array|min:1',
            'advance_invoice_ids.*' => 'integer|exists:invoices,id',
        ]);

        try {
            $result = $this->service->settleAdvances(
                $invoice,
                $request->input('advance_invoice_ids')
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $result->id,
                    'type' => $result->type,
                    'total' => $result->total,
                    'due_amount' => $result->due_amount,
                    'total_advances_amount' => $result->getTotalAdvancesAmount(),
                    'remaining_after_advances' => $result->getRemainingAfterAdvances(),
                ],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Preview settlement calculation without persisting.
     *
     * POST /api/v1/invoices/{invoice}/preview-settlement
     * Body: { "advance_invoice_ids": [1, 2, 3] }
     */
    public function previewSettlement(Request $request, Invoice $invoice): JsonResponse
    {
        $this->authorize('view', $invoice);

        $request->validate([
            'advance_invoice_ids' => 'required|array|min:1',
            'advance_invoice_ids.*' => 'integer|exists:invoices,id',
        ]);

        $preview = $this->service->previewSettlement(
            $invoice,
            $request->input('advance_invoice_ids')
        );

        return response()->json(['data' => $preview]);
    }
}

// CLAUDE-CHECKPOINT
