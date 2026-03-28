<?php

namespace Modules\Mk\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\Mk\Services\CashJournalService;

class CashJournalController extends Controller
{
    protected CashJournalService $service;

    public function __construct(CashJournalService $service)
    {
        $this->service = $service;
    }

    /**
     * Get cash journal data as JSON.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'from_date' => 'required|date',
                'to_date' => 'required|date|after_or_equal:from_date',
            ]);
        } catch (ValidationException $e) {
            throw $e;
        }

        $companyId = (int) $request->header('company');
        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');

        try {
            $data = $this->service->generate($companyId, $fromDate, $toDate);

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate cash journal: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download cash journal as PDF.
     */
    public function pdf(Request $request)
    {
        try {
            $request->validate([
                'from_date' => 'required|date',
                'to_date' => 'required|date|after_or_equal:from_date',
            ]);
        } catch (ValidationException $e) {
            throw $e;
        }

        $companyId = (int) $request->header('company');
        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');

        try {
            $pdfContent = $this->service->generatePdf($companyId, $fromDate, $toDate);

            $filename = 'blagajnicki-izvestaj-' . $fromDate . '-' . $toDate . '.pdf';

            return response($pdfContent, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate PDF: ' . $e->getMessage(),
            ], 500);
        }
    }
}
// CLAUDE-CHECKPOINT
