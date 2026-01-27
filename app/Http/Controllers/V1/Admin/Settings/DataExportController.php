<?php

namespace App\Http\Controllers\V1\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Jobs\ExportUserDataJob;
use App\Models\UserDataExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DataExportController extends Controller
{
    /**
     * Create a new user data export job (GDPR compliance)
     */
    public function store(Request $request)
    {
        $user = $request->user();

        // Check if there's already a pending or processing export
        $existingExport = UserDataExport::whereUser($user->id)
            ->whereIn('status', ['pending', 'processing'])
            ->first();

        if ($existingExport) {
            // Auto-recover stuck exports (stuck for more than 15 minutes)
            if ($existingExport->resetIfStuck(15)) {
                // Export was stuck, continue to create a new one
            } else {
                return response()->json([
                    'message' => 'You already have a data export in progress',
                    'export' => $existingExport,
                ], 422);
            }
        }

        // Create export job
        $export = UserDataExport::create([
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        // For sync queue driver, run the job synchronously with error handling
        // This ensures the export completes before the HTTP response
        if (config('queue.default') === 'sync') {
            try {
                Log::info("Starting synchronous data export for user {$user->id}", [
                    'export_id' => $export->id,
                ]);

                // Run the job synchronously
                ExportUserDataJob::dispatchSync($export);

                // Refresh to get updated status
                $export->refresh();

                Log::info("Completed synchronous data export for user {$user->id}", [
                    'export_id' => $export->id,
                    'status' => $export->status,
                ]);

                return response()->json([
                    'export' => $export,
                    'message' => $export->status === 'completed'
                        ? 'Your data export is ready for download.'
                        : 'Your data export request has been processed.',
                ], 201);
            } catch (\Exception $e) {
                // Mark export as failed
                $export->markAsFailed('Export failed: '.$e->getMessage());

                Log::error("Data export failed for user {$user->id}", [
                    'export_id' => $export->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return response()->json([
                    'export' => $export->fresh(),
                    'message' => 'Data export failed. Please try again.',
                ], 500);
            }
        }

        // For async queue drivers (database, redis), dispatch to queue
        ExportUserDataJob::dispatch($export);

        return response()->json([
            'export' => $export,
            'message' => 'Your data export request has been queued. You will be notified when it is ready.',
        ], 201);
    }

    /**
     * List user data exports for the current user
     */
    public function index(Request $request)
    {
        $exports = UserDataExport::whereUser($request->user()->id)
            ->latest()
            ->paginate(10);

        return response()->json($exports);
    }

    /**
     * Get the latest export status
     */
    public function latest(Request $request)
    {
        $export = UserDataExport::whereUser($request->user()->id)
            ->latest()
            ->first();

        if (! $export) {
            return response()->json([
                'export' => null,
            ]);
        }

        // Auto-detect stuck exports when user checks status
        // Mark as failed if stuck for more than 15 minutes
        $export->resetIfStuck(15);

        // Refresh to get updated status
        $export->refresh();

        return response()->json([
            'export' => $export,
        ]);
    }

    /**
     * Download exported file
     */
    public function download(Request $request, UserDataExport $export)
    {
        // Check ownership
        if ($export->user_id !== $request->user()->id) {
            abort(403, 'You do not have permission to download this export');
        }

        // Check status
        if ($export->status !== 'completed') {
            abort(400, 'Export is not ready for download');
        }

        // Check if expired
        if ($export->expires_at && $export->expires_at->isPast()) {
            abort(410, 'Export has expired');
        }

        // Check if file exists
        if (! Storage::exists($export->file_path)) {
            abort(404, 'Export file not found');
        }

        $filename = 'my-data-export-'.now()->format('Y-m-d').'.zip';

        return Storage::download($export->file_path, $filename);
    }

    /**
     * Delete export job
     */
    public function destroy(Request $request, UserDataExport $export)
    {
        // Check ownership
        if ($export->user_id !== $request->user()->id) {
            abort(403, 'You do not have permission to delete this export');
        }

        // Delete file if exists
        $export->deleteFile();

        // Delete export job record
        $export->delete();

        return response()->json([
            'message' => 'Export deleted successfully',
        ]);
    }
}
// CLAUDE-CHECKPOINT
