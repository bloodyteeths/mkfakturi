<?php

namespace App\Http\Controllers\V1\Admin\Accounting;

use App\Http\Controllers\Controller;
use App\Services\JournalExportService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Controller for journal entry exports to external accounting systems.
 */
class JournalExportController extends Controller
{
    /**
     * Get journal export summary and preview.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
        ]);

        $companyId = $request->header('company');

        $service = new JournalExportService(
            $companyId,
            $request->input('from'),
            $request->input('to')
        );

        return response()->json([
            'summary' => $service->getSummary(),
            'entries' => $service->getJournalEntries()->values(),
        ]);
    }

    /**
     * Export journals to CSV/Pantheon/Zonel format.
     */
    public function export(Request $request): Response
    {
        $request->validate([
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
            'format' => 'sometimes|in:csv,pantheon,zonel',
        ]);

        $companyId = $request->header('company');
        $format = $request->input('format', 'csv');

        $service = new JournalExportService(
            $companyId,
            $request->input('from'),
            $request->input('to')
        );

        // Generate CSV based on format
        $csv = match ($format) {
            JournalExportService::FORMAT_PANTHEON => $service->toPantheonCSV(),
            JournalExportService::FORMAT_ZONEL => $service->toZonelCSV(),
            default => $service->toCSV(),
        };

        // Generate filename
        $from = Carbon::parse($request->input('from'))->format('Ymd');
        $to = Carbon::parse($request->input('to'))->format('Ymd');
        $filename = "journals_{$format}_{$from}_{$to}.csv";

        return response($csv, 200)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"")
            ->header('Content-Length', strlen($csv));
    }

    /**
     * Get available export formats.
     */
    public function formats(): JsonResponse
    {
        return response()->json([
            'formats' => [
                [
                    'value' => 'csv',
                    'label' => 'Generic CSV',
                    'description' => 'Standard CSV format compatible with most systems',
                ],
                [
                    'value' => 'pantheon',
                    'label' => 'Pantheon',
                    'description' => 'Format for Pantheon accounting software',
                ],
                [
                    'value' => 'zonel',
                    'label' => 'Zonel',
                    'description' => 'Format for Zonel accounting software',
                ],
            ],
        ]);
    }
}
// CLAUDE-CHECKPOINT
