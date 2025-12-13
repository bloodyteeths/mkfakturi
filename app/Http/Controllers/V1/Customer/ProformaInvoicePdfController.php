<?php

namespace App\Http\Controllers\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProformaInvoiceResource;
use App\Models\CompanySetting;
use App\Models\EmailLog;
use App\Models\ProformaInvoice;
use Illuminate\Http\Request;

class ProformaInvoicePdfController extends Controller
{
    public function getPdf(EmailLog $emailLog, Request $request)
    {
        $proformaInvoice = ProformaInvoice::find($emailLog->mailable_id);

        if (! $emailLog->isExpired()) {
            if ($proformaInvoice && ($proformaInvoice->status == ProformaInvoice::STATUS_SENT || $proformaInvoice->status == ProformaInvoice::STATUS_DRAFT)) {
                $proformaInvoice->status = ProformaInvoice::STATUS_VIEWED;
                $proformaInvoice->save();
            }

            return $proformaInvoice->getGeneratedPDFOrStream('proforma_invoice');
        }

        abort(403, 'Link Expired.');
    }

    public function getProformaInvoice(EmailLog $emailLog)
    {
        $proformaInvoice = ProformaInvoice::find($emailLog->mailable_id);

        return new ProformaInvoiceResource($proformaInvoice);
    }
}
// CLAUDE-CHECKPOINT
