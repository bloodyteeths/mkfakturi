<?php

namespace App\Http\Controllers\V1\PDF;

use App\Http\Controllers\Controller;
use App\Models\ProformaInvoice;
use Illuminate\Http\Request;

class ProformaInvoicePdfController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request, ProformaInvoice $proformaInvoice)
    {
        if ($request->has('preview')) {
            return $proformaInvoice->getPDFData();
        }

        return $proformaInvoice->getGeneratedPDFOrStream('proforma_invoice');
    }
}

// CLAUDE-CHECKPOINT
