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

        $data = $service->collect($company, $year, $month);

        if ($request->has('download')) {
            $pdf = $service->toPdf($company, $data, $year);
            $pdf->headers->set('Content-Disposition', 'attachment; filename="' . $formCode . '_' . $year . '.pdf"');

            return $pdf;
        }

        return $service->toPdf($company, $data, $year);
    }
}

// CLAUDE-CHECKPOINT
