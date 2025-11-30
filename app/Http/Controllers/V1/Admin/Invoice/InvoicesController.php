<?php

namespace App\Http\Controllers\V1\Admin\Invoice;

use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\Http\Requests\DeleteInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Jobs\GenerateInvoicePdfJob;
use App\Models\Invoice;
use Illuminate\Http\Request;

class InvoicesController extends Controller
{
    /**
     * Relations required to render the invoice resource without N+1 queries.
     *
     * @return array<int, string>
     */
    private function invoiceResourceRelations(): array
    {
        return [
            'customer.currency',
            'customer.company',
            'customer.billingAddress',
            'customer.shippingAddress',
            'customer.fields.customField',
            'customer.fields.company',
            'currency',
            'company',
            'creator',
            'items.item',
            'items.taxes.taxType',
            'items.taxes.currency',
            'items.fields.customField',
            'items.fields.company',
            'taxes.taxType',
            'taxes.currency',
            'fields.customField',
            'fields.company',
        ];
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Invoice::class);

        $limit = $request->has('limit') ? $request->limit : 10;

        $invoices = Invoice::with($this->invoiceResourceRelations())
            ->with('payments')
            ->applyFilters($request->only([
                'search',
                'customer_id',
                'status',
                'date_range',
                'orderByField',
                'orderBy'
            ]))
            ->whereCompany()
            ->paginateData($limit);

        return InvoiceResource::collection($invoices)
            ->additional([
                'meta' => [
                    'invoice_total_count' => Invoice::whereCompany()->count(),
                ]
            ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \App\Http\Resources\InvoiceResource
     */
    public function store(Requests\InvoicesRequest $request)
    {
        $this->authorize('create', Invoice::class);

        // Debug: Log project_id from request
        if ($request->has('project_id')) {
            \Log::info('InvoicesController::store project_id', [
                'project_id' => $request->project_id,
            ]);
        }

        $invoice = Invoice::createInvoice($request);
        $invoice->load($this->invoiceResourceRelations());

        if ($request->has('invoiceSend')) {
            $invoice->send([
                'subject' => $request->subject,
                'body' => $request->body,
                'to' => $invoice->customer->email // Assuming customer email is needed
            ]);
        }

        GenerateInvoicePdfJob::dispatchAfterResponse($invoice->id);

        return new InvoiceResource($invoice);
    }

    /**
     * Display the specified resource.
     *
     * @return \App\Http\Resources\InvoiceResource
     */
    public function show(Request $request, Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $invoice->load($this->invoiceResourceRelations());

        return new InvoiceResource($invoice);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \App\Http\Resources\InvoiceResource|\Illuminate\Http\JsonResponse
     */
    public function update(Requests\InvoicesRequest $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        $invoice = $invoice->updateInvoice($request);

        if (is_string($invoice)) {
            return respondJson($invoice, $invoice);
        }

        GenerateInvoicePdfJob::dispatchAfterResponse($invoice->id, true);

        $invoice->load($this->invoiceResourceRelations());

        return new InvoiceResource($invoice);
    }

    /**
     * delete the specified resources in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(DeleteInvoiceRequest $request)
    {
        $this->authorize('delete multiple invoices');

        Invoice::deleteInvoices($request->ids);

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Initiate CPAY payment for an invoice
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function initiateCpayPayment(Invoice $invoice, Request $request)
    {
        $this->authorize('view', $invoice);

        // Check if invoice is already paid
        if ($invoice->status === Invoice::STATUS_PAID) {
            return response()->json([
                'error' => 'Invoice is already paid',
            ], 400);
        }

        // Check if advanced payments feature is enabled
        if (!config('mk.features.advanced_payments', false)) {
            return response()->json([
                'error' => 'Advanced payments feature is not enabled',
            ], 403);
        }

        try {
            $cpayDriver = app(\Modules\Mk\Services\CpayDriver::class);
            $checkoutData = $cpayDriver->createCheckout($invoice);

            return response()->json([
                'checkout_url' => $checkoutData['checkout_url'],
                'transaction_id' => $checkoutData['params']['order_id'] ?? null,
            ]);
        } catch (\Exception $e) {
            \Log::error('CPAY checkout creation failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
// CLAUDE-CHECKPOINT
