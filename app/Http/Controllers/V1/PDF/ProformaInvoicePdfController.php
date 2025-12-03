<?php

namespace App\Http\Controllers\V1\PDF;

use App\Http\Controllers\Controller;
use App\Models\ProformaInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProformaInvoicePdfController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request, ProformaInvoice $proformaInvoice)
    {
        Log::info('ProformaInvoicePdfController: Starting PDF generation', [
            'proforma_invoice_id' => $proformaInvoice->id,
            'unique_hash' => $proformaInvoice->unique_hash,
            'proforma_invoice_number' => $proformaInvoice->proforma_invoice_number,
            'company_id' => $proformaInvoice->company_id,
        ]);

        try {
            if ($request->has('preview')) {
                Log::info('ProformaInvoicePdfController: Generating preview');
                return $proformaInvoice->getPDFData();
            }

            Log::info('ProformaInvoicePdfController: Generating PDF stream');
            return $proformaInvoice->getGeneratedPDFOrStream('proforma_invoice');
        } catch (\Exception $e) {
            Log::error('ProformaInvoicePdfController: PDF generation failed', [
                'proforma_invoice_id' => $proformaInvoice->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}

// CLAUDE-CHECKPOINT
