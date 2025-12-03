<?php

namespace App\Http\Controllers\V1\Admin\ProformaInvoices;

use App\Http\Controllers\Controller;
use App\Models\ProformaInvoice;
use App\Space\PdfTemplateUtils;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProformaInvoiceTemplatesController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @return JsonResponse
     *
     * @throws AuthorizationException
     */
    public function __invoke(Request $request)
    {
        $this->authorize('viewAny', ProformaInvoice::class);

        $proformaInvoiceTemplates = PdfTemplateUtils::getFormattedTemplates('proforma_invoice');

        return response()->json([
            'proformaInvoiceTemplates' => $proformaInvoiceTemplates,
        ]);
    }
}
