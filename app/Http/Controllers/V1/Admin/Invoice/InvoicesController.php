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
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Invoice::class);

        $limit = $request->input('limit', 10);

        $invoices = Invoice::whereCompany()
            ->applyFilters($request->all())
            ->with($this->invoiceResourceRelations())
            ->latest()
            ->paginateData($limit);

        return InvoiceResource::collection($invoices)
            ->additional(['meta' => [
                'invoice_total_count' => Invoice::whereCompany()->count(),
            ]]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Requests\InvoicesRequest $request)
    {
        $this->authorize('create', Invoice::class);

        $invoice = Invoice::createInvoice($request);
        $invoice->load($this->invoiceResourceRelations());

        if ($request->has('invoiceSend')) {
            $invoice->send($request->subject, $request->body);
        }

        GenerateInvoicePdfJob::dispatch($invoice);

        return new InvoiceResource($invoice);
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\JsonResponse
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
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Requests\InvoicesRequest $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        $invoice = $invoice->updateInvoice($request);

        if (is_string($invoice)) {
            return respondJson($invoice, $invoice);
        }

        GenerateInvoicePdfJob::dispatch($invoice, true);

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
}
