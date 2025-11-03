<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImportJobRequest;
use App\Http\Requests\ImportMappingRequest;
use App\Http\Resources\ImportJobResource;
use App\Models\ImportJob;
use App\Models\ImportLog;
use App\Jobs\Migration\DetectFileTypeJob;
use App\Jobs\Migration\ValidateDataJob;
use App\Jobs\Migration\CommitImportJob;
use App\Jobs\ProcessImportJob;
use App\Services\Migration\ImportPresetService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Laravel\Pennant\Feature;
use League\Csv\Reader;
use League\Csv\Writer;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

class MigrationController extends Controller
{
    /**
     * Display a listing of import jobs for the company.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', ImportJob::class);

        $limit = $request->has('limit') ? $request->limit : 10;

        $imports = ImportJob::with(['creator', 'logs' => function ($query) {
                $query->where('log_type', ImportLog::TYPE_ERROR)->latest()->limit(3);
            }])
            ->whereCompany()
            ->applyFilters($request->all())
            ->paginateData($limit);

        return ImportJobResource::collection($imports)
            ->additional(['meta' => [
                'total_imports' => ImportJob::whereCompany()->count(),
                'active_imports' => ImportJob::whereCompany()->inProgress()->count(),
                'completed_imports' => ImportJob::whereCompany()->completed()->count(),
                'failed_imports' => ImportJob::whereCompany()->failed()->count(),
            ]]);
    }

    /**
     * Create new import job (file upload).
     *
     * @param  \App\Http\Requests\ImportJobRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(ImportJobRequest $request)
    {
        $this->authorize('create', ImportJob::class);

        try {
            DB::beginTransaction();

            // Handle file upload
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $size = $file->getSize();
            $mimeType = $file->getMimeType();

            // Validate file type
            if (!in_array(strtolower($extension), ['csv', 'xlsx', 'xls', 'xml'])) {
                return response()->json([
                    'message' => 'Unsupported file type. Please upload CSV, Excel, or XML files.',
                    'errors' => ['file' => ['Invalid file type']]
                ], 422);
            }

            // Generate unique filename and store file
            $filename = now()->format('Y-m-d_H-i-s') . '_' . uniqid() . '.' . $extension;
            $filePath = $file->storeAs(
                'imports/' . request()->header('company'),
                $filename,
                'private'
            );

            // Create import job record
            $importJob = ImportJob::create([
                'company_id' => request()->header('company'),
                'creator_id' => auth()->id(),
                'type' => $request->type ?? ImportJob::TYPE_COMPLETE,
                'status' => ImportJob::STATUS_PENDING,
                'source_system' => $request->source_system ?? 'unknown',
                'file_path' => $filePath,
                'file_info' => [
                    'original_name' => $originalName,
                    'filename' => $filename,
                    'extension' => $extension,
                    'size' => $size,
                    'mime_type' => $mimeType,
                ],
                'total_records' => 0,
                'processed_records' => 0,
                'successful_records' => 0,
                'failed_records' => 0,
            ]);

            // Log the upload
            ImportLog::create([
                'import_job_id' => $importJob->id,
                'log_type' => ImportLog::TYPE_INFO,
                'message' => 'File uploaded successfully',
                'details' => [
                    'file_name' => $originalName,
                    'file_size' => $size,
                    'file_type' => $extension,
                ],
            ]);

            // Dispatch background job to parse file and detect columns
            DetectFileTypeJob::dispatch($importJob)
                ->onQueue('migration')
                ->delay(now()->addSeconds(2));

            DB::commit();

            Log::info('Import job created', [
                'import_job_id' => $importJob->id,
                'file_name' => $originalName,
                'company_id' => request()->header('company'),
                'user_id' => auth()->id()
            ]);

            return new ImportJobResource($importJob->load('creator'));

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Import job creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'company_id' => request()->header('company'),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'message' => 'Failed to create import job: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified import job.
     *
     * @param  \App\Models\ImportJob  $import
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(ImportJob $import)
    {
        $this->authorize('view', $import);

        $import->load(['creator', 'logs' => function ($query) {
            $query->latest()->limit(20);
        }]);

        return new ImportJobResource($import);
    }

    /**
     * Submit field mappings for the import job.
     *
     * @param  \App\Http\Requests\ImportMappingRequest  $request
     * @param  \App\Models\ImportJob  $import
     * @return \Illuminate\Http\JsonResponse
     */
    public function mapping(ImportMappingRequest $request, ImportJob $import)
    {
        $this->authorize('update', $import);

        try {
            // Validate import job status
            if ($import->status !== ImportJob::STATUS_PENDING && $import->status !== ImportJob::STATUS_MAPPING) {
                return response()->json([
                    'message' => 'Import job is not in a state that allows mapping changes.'
                ], 422);
            }

            // Update mapping configuration
            $import->update([
                'status' => ImportJob::STATUS_MAPPING,
                'mapping_config' => $request->mappings,
                'validation_rules' => $request->validation_rules ?? [],
            ]);

            // Log the mapping
            ImportLog::create([
                'import_job_id' => $import->id,
                'log_type' => ImportLog::TYPE_INFO,
                'message' => 'Field mappings updated',
                'details' => [
                    'mappings_count' => count($request->mappings),
                    'validation_rules_count' => count($request->validation_rules ?? []),
                ],
            ]);

            // Dispatch background job to validate data with new mappings
            ValidateDataJob::dispatch($import)
                ->onQueue('migration')
                ->delay(now()->addSeconds(3));

            Log::info('Import mappings updated', [
                'import_job_id' => $import->id,
                'mappings_count' => count($request->mappings),
                'company_id' => $import->company_id,
            ]);

            return new ImportJobResource($import->fresh(['creator']));

        } catch (\Exception $e) {
            Log::error('Import mapping failed', [
                'import_job_id' => $import->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Failed to update mappings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate mapped data before committing.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ImportJob  $import
     * @return \Illuminate\Http\JsonResponse
     */
    public function validateImport(Request $request, ImportJob $import)
    {
        $this->authorize('update', $import);

        try {
            // Validate import job status
            if (!in_array($import->status, [ImportJob::STATUS_MAPPING, ImportJob::STATUS_VALIDATING])) {
                return response()->json([
                    'message' => 'Import job is not ready for validation.'
                ], 422);
            }

            // Update status to validating
            $import->update(['status' => ImportJob::STATUS_VALIDATING]);

            // Log validation start
            ImportLog::create([
                'import_job_id' => $import->id,
                'log_type' => ImportLog::TYPE_INFO,
                'message' => 'Data validation started',
                'details' => ['triggered_by' => auth()->id()],
            ]);

            // Dispatch background job to validate mapped data
            ValidateDataJob::dispatch($import)
                ->onQueue('migration')
                ->delay(now()->addSeconds(2));

            Log::info('Import validation started', [
                'import_job_id' => $import->id,
                'company_id' => $import->company_id,
            ]);

            return response()->json([
                'message' => 'Validation started. Check progress for updates.',
                'import_job' => new ImportJobResource($import->fresh(['creator'])),
            ]);

        } catch (\Exception $e) {
            Log::error('Import validation failed', [
                'import_job_id' => $import->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Failed to start validation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Commit import to production data.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ImportJob  $import
     * @return \Illuminate\Http\JsonResponse
     */
    public function commit(Request $request, ImportJob $import)
    {
        $this->authorize('update', $import);

        try {
            // Validate import job status
            if ($import->status !== ImportJob::STATUS_VALIDATING) {
                return response()->json([
                    'message' => 'Import job must be validated before committing.'
                ], 422);
            }

            // Check if there are validation errors
            $errorLogs = $import->logs()->where('log_type', ImportLog::TYPE_ERROR)->count();
            if ($errorLogs > 0 && !$request->boolean('force_commit', false)) {
                return response()->json([
                    'message' => 'Import has validation errors. Use force_commit=true to proceed anyway.',
                    'errors_count' => $errorLogs
                ], 422);
            }

            // Update status to committing
            $import->update(['status' => ImportJob::STATUS_COMMITTING]);

            // Log commit start
            ImportLog::create([
                'import_job_id' => $import->id,
                'log_type' => ImportLog::TYPE_INFO,
                'message' => 'Data commit started',
                'details' => [
                    'triggered_by' => auth()->id(),
                    'force_commit' => $request->boolean('force_commit', false),
                ],
            ]);

            // Dispatch background job to commit data to production tables
            CommitImportJob::dispatch($import)
                ->onQueue('migration')
                ->delay(now()->addSeconds(5));

            Log::info('Import commit started', [
                'import_job_id' => $import->id,
                'company_id' => $import->company_id,
                'force_commit' => $request->boolean('force_commit', false),
            ]);

            return response()->json([
                'message' => 'Commit started. This may take several minutes for large imports.',
                'import_job' => new ImportJobResource($import->fresh(['creator'])),
            ]);

        } catch (\Exception $e) {
            Log::error('Import commit failed', [
                'import_job_id' => $import->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Failed to start commit: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel/delete import job.
     *
     * @param  \App\Models\ImportJob  $import
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(ImportJob $import)
    {
        $this->authorize('delete', $import);

        try {
            // Check if import is in progress
            if ($import->isInProgress) {
                return response()->json([
                    'message' => 'Cannot delete import job while it is in progress.'
                ], 422);
            }

            DB::beginTransaction();

            // Delete associated files
            if ($import->file_path && Storage::disk('private')->exists($import->file_path)) {
                Storage::disk('private')->delete($import->file_path);
            }

            // Delete temp data (cascade will handle this, but we'll be explicit)
            $import->tempCustomers()->delete();
            $import->tempInvoices()->delete();
            $import->tempItems()->delete();
            $import->tempPayments()->delete();
            $import->tempExpenses()->delete();
            $import->logs()->delete();

            // Delete the import job
            $importId = $import->id;
            $import->delete();

            DB::commit();

            Log::info('Import job deleted', [
                'import_job_id' => $importId,
                'company_id' => $import->company_id,
                'deleted_by' => auth()->id()
            ]);

            return response()->json([
                'message' => 'Import job deleted successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Import job deletion failed', [
                'import_job_id' => $import->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Failed to delete import job: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get real-time progress for an import job.
     *
     * @param  \App\Models\ImportJob  $import
     * @return \Illuminate\Http\JsonResponse
     */
    public function progress(ImportJob $import)
    {
        $this->authorize('view', $import);

        $recentLogs = $import->logs()
            ->whereIn('log_type', [ImportLog::TYPE_INFO, ImportLog::TYPE_WARNING, ImportLog::TYPE_ERROR])
            ->latest()
            ->limit(5)
            ->get();

        return response()->json([
            'import_job_id' => $import->id,
            'status' => $import->status,
            'progress_percentage' => $import->progressPercentage,
            'total_records' => $import->total_records,
            'processed_records' => $import->processed_records,
            'successful_records' => $import->successful_records,
            'failed_records' => $import->failed_records,
            'duration' => $import->duration,
            'is_in_progress' => $import->isInProgress,
            'can_retry' => $import->canRetry,
            'recent_logs' => $recentLogs->map(function ($log) {
                return [
                    'id' => $log->id,
                    'type' => $log->log_type,
                    'message' => $log->message,
                    'created_at' => $log->created_at->toISOString(),
                    'details' => $log->details,
                ];
            }),
            'last_updated' => now()->toISOString(),
        ]);
    }

    /**
     * Get import logs with filtering.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ImportJob  $import
     * @return \Illuminate\Http\JsonResponse
     */
    public function logs(Request $request, ImportJob $import)
    {
        $this->authorize('view', $import);

        $validator = Validator::make($request->all(), [
            'log_type' => 'sometimes|in:info,warning,error,debug',
            'limit' => 'sometimes|integer|min:1|max:100',
            'page' => 'sometimes|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = $import->logs()->latest();

        // Filter by log type if specified
        if ($request->has('log_type')) {
            $query->where('log_type', $request->log_type);
        }

        $limit = $request->get('limit', 20);
        $logs = $query->paginate($limit);

        return response()->json([
            'data' => $logs->items(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
                'from' => $logs->firstItem(),
                'to' => $logs->lastItem(),
            ],
            'links' => [
                'first' => $logs->url(1),
                'last' => $logs->url($logs->lastPage()),
                'prev' => $logs->previousPageUrl(),
                'next' => $logs->nextPageUrl(),
            ],
        ]);
    }
}