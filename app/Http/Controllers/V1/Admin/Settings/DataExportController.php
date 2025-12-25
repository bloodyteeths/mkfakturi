<?php

namespace App\Http\Controllers\V1\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Jobs\ExportUserDataJob;
use App\Models\UserDataExport;
use Illuminate\Http\Request;
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
            return response()->json([
                'message' => 'You already have a data export in progress',
                'export' => $existingExport,
            ], 422);
        }

        // Create export job
        $export = UserDataExport::create([
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        // Dispatch job for processing
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
