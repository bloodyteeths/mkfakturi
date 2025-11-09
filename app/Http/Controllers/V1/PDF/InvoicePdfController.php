<?php

namespace App\Http\Controllers\V1\PDF;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;

class InvoicePdfController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request, Invoice $invoice)
    {
        \Log::info('InvoicePdfController invoked', [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'unique_hash' => $invoice->unique_hash,
            'company_id' => $invoice->company_id,
            'has_preview' => $request->has('preview'),
        ]);

        if ($request->has('preview')) {
            return $invoice->getPDFData();
        }

        return $invoice->getGeneratedPDFOrStream('invoice');
    }
}
