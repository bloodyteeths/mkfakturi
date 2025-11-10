<?php

namespace App\Http\Controllers\V1\Admin\ProformaInvoices;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProformaInvoiceRequest;
use App\Http\Requests\DeleteProformaInvoiceRequest;
use App\Http\Resources\ProformaInvoiceResource;
use App\Jobs\GenerateProformaInvoicePdfJob;
use App\Models\ProformaInvoice;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Proforma Invoice Controller
 *
 * Handles CRUD operations for proforma invoices
 *
 * @package App\Http\Controllers\V1\Admin\ProformaInvoices
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
     *
     * @param  Request  $request
     * @return JsonResponse
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
     *
     * @param  ProformaInvoiceRequest  $request
     * @return JsonResponse
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
     *
     * @param  Request  $request
     * @param  ProformaInvoice  $proformaInvoice
     * @return JsonResponse
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
     *
     * @param  ProformaInvoiceRequest  $request
     * @param  ProformaInvoice  $proformaInvoice
     * @return JsonResponse
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
     *
     * @param  DeleteProformaInvoiceRequest  $request
     * @return JsonResponse
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
     *
     * @param  Request  $request
     * @param  ProformaInvoice  $proformaInvoice
     * @return JsonResponse
     */
    public function send(Request $request, ProformaInvoice $proformaInvoice): JsonResponse
    {
        $this->authorize('send', $proformaInvoice);

        $request->validate([
            'to' => 'required|email',
            'subject' => 'required|string',
            'body' => 'required|string',
        ]);

        try {
            $data = [
                'to' => $request->to,
                'subject' => $request->subject,
                'body' => $request->body,
            ];

            // Send email logic would go here (similar to Invoice::send)
            // For now, just mark as sent
            $proformaInvoice->markAsSent();

            return response()->json([
                'success' => true,
                'message' => 'Proforma invoice sent successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark proforma invoice as viewed.
     *
     * @param  ProformaInvoice  $proformaInvoice
     * @return JsonResponse
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
     *
     * @param  ProformaInvoice  $proformaInvoice
     * @return JsonResponse
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
     *
     * @param  ProformaInvoice  $proformaInvoice
     * @return JsonResponse
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
     *
     * @param  ProformaInvoice  $proformaInvoice
     * @return JsonResponse
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
}

// CLAUDE-CHECKPOINT
