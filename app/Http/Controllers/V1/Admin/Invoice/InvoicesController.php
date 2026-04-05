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
     * Minimal relations for list view - only what's displayed in the table.
     * This dramatically reduces query count and memory usage.
     *
     * @return array<int, string>
     */
    private function invoiceListRelations(): array
    {
        return [
            'customer:id,name,email',
            // Currency needs all formatting fields (thousand_separator, decimal_separator, etc.)
            'currency',
            'company:id,name',
        ];
    }

    /**
     * Full relations for single invoice view/edit.
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
            'emailLogs',
            'media',
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

        // Build base query with filters first
        $query = Invoice::query()
            ->applyFilters($request->only([
                'search',
                'customer_id',
                'status',
                'from_date',
                'to_date',
                'orderByField',
                'orderBy',
                'type',
                'is_reverse_charge',
            ]))
            ->whereCompany();

        // Get total count from filtered query (not all invoices)
        $totalCount = (clone $query)->count();

        // Load minimal relations for list view performance
        $invoices = $query
            ->with($this->invoiceListRelations())
            ->paginateData($limit);

        return \App\Http\Resources\InvoiceListResource::collection($invoices)
            ->additional([
                'meta' => [
                    'invoice_total_count' => $totalCount,
                ],
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

        $companyId = (int) $request->header('company');

        if (! $request->allowsDuplicate()) {
            $duplicates = Invoice::findPotentialDuplicates($companyId, [
                'customer_id' => $request->input('customer_id'),
                'total' => $request->input('total'),
                'invoice_date' => $request->input('invoice_date'),
            ]);

            if ($duplicates->isNotEmpty()) {
                return response()->json([
                    'is_duplicate_warning' => true,
                    'message' => __('invoices.duplicate_warning'),
                    'duplicates' => $duplicates,
                ], 200);
            }
        }

        $invoice = Invoice::createInvoice($request);
        $invoice->load($this->invoiceResourceRelations());

        if ($request->has('invoiceSend')) {
            $invoice->send([
                'subject' => $request->subject,
                'body' => $request->body,
                'to' => $invoice->customer->email, // Assuming customer email is needed
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
     * Perform a bulk action on multiple invoices.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkAction(Request $request)
    {
        $this->authorize('viewAny', Invoice::class);

        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
            'action' => 'required|in:mark_as_sent,send,clone',
        ]);

        $invoices = Invoice::whereIn('id', $validated['ids'])->whereCompany()->get();
        $processed = 0;

        switch ($validated['action']) {
            case 'mark_as_sent':
                foreach ($invoices as $invoice) {
                    if ($invoice->status === Invoice::STATUS_DRAFT) {
                        $invoice->status = Invoice::STATUS_SENT;
                        $invoice->sent = true;
                        $invoice->save();
                        $processed++;
                    }
                }
                break;
            case 'send':
                foreach ($invoices as $invoice) {
                    if ($invoice->status === Invoice::STATUS_DRAFT) {
                        $invoice->send($request->all());
                        $processed++;
                    }
                }
                break;
            case 'clone':
                $this->authorize('create', Invoice::class);
                foreach ($invoices as $invoice) {
                    app(CloneInvoiceController::class)->__invoke($request, $invoice);
                    $processed++;
                }
                break;
        }

        return response()->json(['success' => true, 'processed' => $processed]);
    }

    /**
     * Upload a source document for an invoice.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadSourceDocument(Invoice $invoice, Request $request)
    {
        $this->authorize('update', $invoice);

        $request->validate([
            'source_document' => 'required|file|max:20480',
        ]);

        $invoice->clearMediaCollection('source_document');
        $invoice->addMediaFromRequest('source_document')
            ->toMediaCollection('source_document');

        return response()->json(['success' => true]);
    }

    /**
     * Initiate CPAY payment for an invoice
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function initiateCpayPayment(Invoice $invoice, Request $request)
    {
        $this->authorize('view', $invoice);

        if ($invoice->status === Invoice::STATUS_PAID) {
            return response()->json([
                'error' => 'Invoice is already paid',
            ], 400);
        }

        $companyId = $invoice->company_id;

        // Check if CASYS is enabled and invoice QR is active for this company
        $casysEnabled = \App\Models\CompanySetting::getSetting('casys_enabled', $companyId) === 'YES';
        $invoiceQr = \App\Models\CompanySetting::getSetting('casys_invoice_qr', $companyId) === 'YES';

        if (! $casysEnabled || ! $invoiceQr) {
            return response()->json([
                'error' => 'Online payments not configured. Enable CASYS in Settings → Online Payments.',
            ], 403);
        }

        try {
            $cpayService = app(\Modules\Mk\Services\CpayMerchantService::class);
            $orderId = 'INV-' . $invoice->id . '-' . time();
            $description = 'Invoice ' . $invoice->invoice_number;

            $checkoutData = $cpayService->createCheckout(
                $companyId,
                $invoice->total,
                $orderId,
                $description
            );

            return response()->json([
                'checkout_url' => $checkoutData['checkout_url'],
                'transaction_id' => $orderId,
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
