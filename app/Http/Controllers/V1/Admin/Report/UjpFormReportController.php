<?php

namespace App\Http\Controllers\V1\Admin\Report;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\Tax\TaxFormService;
use Illuminate\Http\Request;

class UjpFormReportController extends Controller
{
    public function __invoke(Request $request, $hash, string $formCode)
    {
        $company = Company::where('unique_hash', $hash)->first();

        if (! $company) {
            abort(404, 'Company not found');
        }

        $service = TaxFormService::resolve($formCode);
        if (! $service) {
            abort(404, 'Unknown form code: ' . $formCode);
        }

        $year = (int) $request->query('year', date('Y'));
        $month = $request->query('month') ? (int) $request->query('month') : null;

        $prevReporting = error_reporting(error_reporting() & ~E_DEPRECATED);

        $data = $service->collect($company, $year, $month);
        $pdfResponse = $service->toPdf($company, $data, $year);
        $pdfContent = $pdfResponse->getContent();

        error_reporting($prevReporting);

        if (ob_get_length() > 0) {
            ob_clean();
        }

        $disposition = $request->has('download') ? 'attachment' : 'inline';

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Length' => strlen($pdfContent),
            'Content-Disposition' => $disposition . '; filename="' . $formCode . '_' . $year . '.pdf"',
        ]);
    }
}

// CLAUDE-CHECKPOINT
