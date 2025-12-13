<?php

namespace App\Http\Controllers\V1\Admin\ProformaInvoices;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteProformaInvoiceRequest;
use App\Http\Requests\ProformaInvoiceRequest;
use App\Http\Resources\ProformaInvoiceResource;
use App\Jobs\GenerateProformaInvoicePdfJob;
use App\Models\ProformaInvoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Proforma Invoice Controller
 *
 * Handles CRUD operations for proforma invoices
 */
class ProformaInvoicesController extends Controller
{
    /**
     * Relations required to render the proforma invoice resource without N+1 queries.
     *
     * @return array<int, string>
     */
    private function proformaInvoiceResourceRelations(): array
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
            'convertedInvoice',
        ];
    }

    /**
     * Display a listing of proforma invoices.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ProformaInvoice::class);

        $limit = $request->input('limit', 10);

        $proformaInvoices = ProformaInvoice::whereCompany()
            ->applyFilters($request->all())
            ->with($this->proformaInvoiceResourceRelations())
            ->latest()
            ->paginateData($limit);

        return ProformaInvoiceResource::collection($proformaInvoices)
            ->additional(['meta' => [
                'proforma_invoice_total_count' => ProformaInvoice::whereCompany()->count(),
            ]])
            ->response();
    }

    /**
     * Store a newly created proforma invoice.
     */
    public function store(ProformaInvoiceRequest $request): JsonResponse
    {
        $this->authorize('create', ProformaInvoice::class);

        $proformaInvoice = ProformaInvoice::createProformaInvoice($request);
        $proformaInvoice->load($this->proformaInvoiceResourceRelations());

        if ($request->has('proformaInvoiceSend')) {
            $proformaInvoice->markAsSent();
        }

        GenerateProformaInvoicePdfJob::dispatchAfterResponse($proformaInvoice->id);

        return (new ProformaInvoiceResource($proformaInvoice))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified proforma invoice.
     */
    public function show(Request $request, ProformaInvoice $proformaInvoice): JsonResponse
    {
        $this->authorize('view', $proformaInvoice);

        $proformaInvoice->load($this->proformaInvoiceResourceRelations());

        return (new ProformaInvoiceResource($proformaInvoice))
            ->response();
    }

    /**
     * Update the specified proforma invoice.
     */
    public function update(ProformaInvoiceRequest $request, ProformaInvoice $proformaInvoice): JsonResponse
    {
        $this->authorize('update', $proformaInvoice);

        $proformaInvoice = $proformaInvoice->updateProformaInvoice($request);

        GenerateProformaInvoicePdfJob::dispatchAfterResponse($proformaInvoice->id, true);

        $proformaInvoice->load($this->proformaInvoiceResourceRelations());

        return (new ProformaInvoiceResource($proformaInvoice))
            ->response();
    }

    /**
     * Delete the specified proforma invoices.
     */
    public function delete(DeleteProformaInvoiceRequest $request): JsonResponse
    {
        $this->authorize('deleteMultiple', ProformaInvoice::class);

        ProformaInvoice::deleteProformaInvoices($request->ids);

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Send proforma invoice via email.
     */
    public function send(Request $request, ProformaInvoice $proformaInvoice): JsonResponse
    {
        $this->authorize('send', $proformaInvoice);

        $request->validate([
            'from' => 'required|email',
            'to' => 'required|email',
            'subject' => 'required|string',
            'body' => 'required|string',
        ]);

        $proformaInvoice->send($request->all());

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Preview proforma invoice email.
     */
    public function sendPreview(Request $request, ProformaInvoice $proformaInvoice)
    {
        $this->authorize('send', $proformaInvoice);

        $markdown = new \Illuminate\Mail\Markdown(view(), config('mail.markdown'));

        $data = $proformaInvoice->sendProformaInvoiceData($request->all());
        $data['url'] = $proformaInvoice->proformaInvoicePdfUrl ?? '';

        return $markdown->render('emails.send.proforma-invoice', ['data' => $data]);
    }

    /**
     * Mark proforma invoice as viewed.
     */
    public function markAsViewed(ProformaInvoice $proformaInvoice): JsonResponse
    {
        $this->authorize('markAsViewed', $proformaInvoice);

        $proformaInvoice->markAsViewed();

        return response()->json([
            'success' => true,
            'message' => 'Proforma invoice marked as viewed',
        ]);
    }

    /**
     * Mark proforma invoice as expired.
     */
    public function markAsExpired(ProformaInvoice $proformaInvoice): JsonResponse
    {
        $this->authorize('markAsExpired', $proformaInvoice);

        $proformaInvoice->markAsExpired();

        return response()->json([
            'success' => true,
            'message' => 'Proforma invoice marked as expired',
        ]);
    }

    /**
     * Mark proforma invoice as rejected.
     */
    public function markAsRejected(ProformaInvoice $proformaInvoice): JsonResponse
    {
        $this->authorize('markAsRejected', $proformaInvoice);

        $proformaInvoice->markAsRejected();

        return response()->json([
            'success' => true,
            'message' => 'Proforma invoice marked as rejected',
        ]);
    }

    /**
     * Convert proforma invoice to regular invoice.
     */
    public function convertToInvoice(ProformaInvoice $proformaInvoice): JsonResponse
    {
        $this->authorize('convertToInvoice', $proformaInvoice);

        try {
            $invoice = $proformaInvoice->convertToInvoice();

            return response()->json([
                'success' => true,
                'message' => 'Proforma invoice converted to invoice successfully',
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download proforma invoice PDF.
     */
    public function downloadPdf(ProformaInvoice $proformaInvoice)
    {
        $this->authorize('view', $proformaInvoice);

        $pdf = $proformaInvoice->getPDFData();

        return $pdf->download("proforma-{$proformaInvoice->proforma_invoice_number}.pdf");
    }
}

// CLAUDE-CHECKPOINT
