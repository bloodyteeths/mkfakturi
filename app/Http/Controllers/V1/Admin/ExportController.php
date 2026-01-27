<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessExportJob;
use App\Models\Company;
use App\Models\ExportJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExportController extends Controller
{
    /**
     * Create a new export job
     */
    public function store(Request $request, Company $company)
    {
        $this->authorize('view', $company);

        $validated = $request->validate([
            'type' => 'required|in:invoices,bills,customers,suppliers,transactions,expenses,payments,items,estimates,proforma_invoices,recurring_invoices',
            'format' => 'required|in:csv,xlsx,pdf',
            'params' => 'nullable|array',
            'params.start_date' => 'nullable|date',
            'params.end_date' => 'nullable|date|after_or_equal:params.start_date',
            'params.status' => 'nullable|string',
        ]);

        $exportJob = ExportJob::create([
            'company_id' => $company->id,
            'user_id' => $request->user()->id,
            'type' => $validated['type'],
            'format' => $validated['format'],
            'params' => $validated['params'] ?? null,
            'status' => 'pending',
        ]);

        // Run job synchronously since queue workers are disabled on Railway
        // This executes immediately instead of being queued
        ProcessExportJob::dispatchSync($exportJob);

        return response()->json([
            'export_job' => $exportJob,
            'message' => 'Export job created successfully',
        ], 201);
    }

    /**
     * List export jobs for the user
     */
    public function index(Request $request, Company $company)
    {
        $this->authorize('view', $company);

        $exports = ExportJob::whereCompany($company->id)
            ->whereUser($request->user()->id)
            ->latest()
            ->paginate(25);

        return response()->json($exports);
    }

    /**
     * Download exported file
     */
    public function download(Request $request, Company $company, ExportJob $exportJob)
    {
        $this->authorize('view', $company);

        // Check ownership
        if ($exportJob->user_id !== $request->user()->id) {
            abort(403, 'You do not have permission to download this export');
        }

        // Check company match
        if ($exportJob->company_id !== $company->id) {
            abort(403, 'Export does not belong to this company');
        }

        // Check status
        if ($exportJob->status !== 'completed') {
            abort(400, 'Export is not ready for download');
        }

        // Check if expired
        if ($exportJob->expires_at && $exportJob->expires_at->isPast()) {
            abort(410, 'Export has expired');
        }

        // Check if file exists
        if (! Storage::exists($exportJob->file_path)) {
            abort(404, 'Export file not found');
        }

        return Storage::download($exportJob->file_path, basename($exportJob->file_path));
    }

    /**
     * Delete export job
     */
    public function destroy(Request $request, Company $company, ExportJob $exportJob)
    {
        $this->authorize('view', $company);

        // Check ownership
        if ($exportJob->user_id !== $request->user()->id) {
            abort(403, 'You do not have permission to delete this export');
        }

        // Delete file if exists
        $exportJob->deleteFile();

        // Delete export job record
        $exportJob->delete();

        return response()->json([
            'message' => 'Export deleted successfully',
        ]);
    }
}
// CLAUDE-CHECKPOINT: Added 'items' type support to export validation
