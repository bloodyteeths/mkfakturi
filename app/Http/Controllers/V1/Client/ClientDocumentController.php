<?php

namespace App\Http\Controllers\V1\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClientDocumentUploadRequest;
use App\Jobs\ProcessClientDocumentJob;
use App\Models\ClientDocument;
use App\Services\DocumentConfirmationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ClientDocumentController extends Controller
{
    /**
     * Upload a new client document.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function upload(ClientDocumentUploadRequest $request): JsonResponse
    {
        $user = $request->user();
        $companyId = (int) $request->header('company');

        // Verify user belongs to this company
        if (! $user->isOwner() && ! $user->hasCompany($companyId)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this company.',
            ], 403);
        }

        // Check usage limit
        $usageService = app(\App\Services\UsageLimitService::class);
        $company = \App\Models\Company::find($companyId);
        if ($company && ! $usageService->canUse($company, 'client_documents_per_month')) {
            return response()->json($usageService->buildLimitExceededResponse($company, 'client_documents_per_month'), 402);
        }

        $file = $request->file('file');

        // Generate secure filename
        $extension = $file->getClientOriginalExtension();
        $filename = Str::random(40).'.'.$extension;
        $yearMonth = now()->format('Y-m');
        $directory = "client-documents/{$companyId}/{$yearMonth}";
        $path = "{$directory}/{$filename}";

        try {
            // Store file — use explicit disk to avoid FileDisk contamination
            $disk = config('filesystems.media_disk');

            Log::info('ClientDocument upload', [
                'disk' => $disk,
                'path' => $path,
                'company_id' => $companyId,
                'file_size' => $file->getSize(),
            ]);

            $stored = Storage::disk($disk)->putFileAs($directory, $file, $filename);

            if (! $stored) {
                Log::error('ClientDocument: putFileAs returned false (silent failure)', [
                    'disk' => $disk,
                    'path' => $path,
                    'driver' => config("filesystems.disks.{$disk}.driver"),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to store file. Please try again.',
                ], 500);
            }

            // Auto-assign partner_id from company's active partner link
            $partnerId = $this->getActivePartnerId($companyId);

            // Create document record
            $document = ClientDocument::create([
                'company_id' => $companyId,
                'uploaded_by' => $user->id,
                'partner_id' => $partnerId,
                'category' => $request->input('category'),
                'original_filename' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'status' => ClientDocument::STATUS_PENDING,
                'notes' => $request->input('notes'),
                'metadata' => [
                    'uploaded_at' => now()->toIso8601String(),
                    'ip_address' => $request->ip(),
                ],
            ]);

            // Increment usage after successful creation
            $usageService->incrementUsage($company, 'client_documents_per_month');

            // Dispatch AI processing job
            ProcessClientDocumentJob::dispatch($document->id);

            return response()->json([
                'success' => true,
                'message' => 'Document uploaded successfully. AI processing started.',
                'data' => $this->formatDocument($document),
            ], 201);
        } catch (\Exception $e) {
            // Clean up file if it was stored
            if (Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload document.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * List all documents for the authenticated user's company.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $companyId = (int) $request->header('company');

        if (! $user->isOwner() && ! $user->hasCompany($companyId)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this company.',
            ], 403);
        }

        $query = ClientDocument::forCompany($companyId)
            ->with(['uploader:id,name,email', 'reviewer:id,name,email'])
            ->orderBy('created_at', 'desc');

        // Filter by category
        if ($request->has('category') && $request->input('category')) {
            $query->where('category', $request->input('category'));
        }

        // Filter by status
        if ($request->has('status') && $request->input('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by processing status
        if ($request->has('processing_status') && $request->input('processing_status')) {
            $query->where('processing_status', $request->input('processing_status'));
        }

        $documents = $query->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $documents->getCollection()->map(fn ($doc) => $this->formatDocument($doc)),
            'current_page' => $documents->currentPage(),
            'last_page' => $documents->lastPage(),
            'per_page' => $documents->perPage(),
            'total' => $documents->total(),
            'from' => $documents->firstItem(),
            'to' => $documents->lastItem(),
        ]);
    }

    /**
     * Show a single document.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $companyId = (int) $request->header('company');

        $document = ClientDocument::with(['uploader:id,name,email', 'reviewer:id,name,email'])
            ->where('company_id', $companyId)
            ->findOrFail($id);

        if (! $user->isOwner() && ! $user->hasCompany($document->company_id)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this document.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatDocument($document),
        ]);
    }

    /**
     * Delete a document (soft delete). Only allowed if status is pending_review.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $companyId = (int) $request->header('company');

        $document = ClientDocument::where('company_id', $companyId)->findOrFail($id);

        if (! $user->isOwner() && ! $user->hasCompany($document->company_id)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this document.',
            ], 403);
        }

        // Delete the physical file
        $disk = config('filesystems.media_disk');
        if ($document->file_path && Storage::disk($disk)->exists($document->file_path)) {
            Storage::disk($disk)->delete($document->file_path);
        }

        $document->delete(); // Soft delete

        return response()->json([
            'success' => true,
            'message' => 'Document deleted successfully.',
        ]);
    }

    /**
     * Download a document file.
     */
    public function download(Request $request, int $id)
    {
        $user = $request->user();
        // Accept company from header (axios) or query param (iframe/direct browser link)
        $companyId = (int) ($request->header('company') ?: $request->query('company', 0));

        $document = ClientDocument::where('company_id', $companyId)->findOrFail($id);

        if (! $user->isOwner() && ! $user->hasCompany($document->company_id)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this document.',
            ], 403);
        }

        $disk = config('filesystems.media_disk');
        if (! $document->file_path || ! Storage::disk($disk)->exists($document->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'File not found.',
            ], 404);
        }

        return Storage::disk($disk)->download(
            $document->file_path,
            $document->original_filename,
            ['Content-Type' => $document->mime_type]
        );
    }

    /**
     * Preview a document file inline (for iframe embedding).
     */
    public function preview(Request $request, int $id)
    {
        $user = $request->user();
        $companyId = (int) ($request->header('company') ?: $request->query('company', 0));

        $document = ClientDocument::where('company_id', $companyId)->findOrFail($id);

        if (! $user->isOwner() && ! $user->hasCompany($document->company_id)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this document.',
            ], 403);
        }

        $disk = config('filesystems.media_disk');
        if (! $document->file_path || ! Storage::disk($disk)->exists($document->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'File not found.',
            ], 404);
        }

        $stream = Storage::disk($disk)->readStream($document->file_path);

        return response()->stream(function () use ($stream) {
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $document->mime_type,
            'Content-Disposition' => 'inline; filename="'.addcslashes($document->original_filename, '"').'"',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
    // CLAUDE-CHECKPOINT

    /**
     * Get the AI processing status of a document (for polling).
     */
    public function processingStatus(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');
        $document = ClientDocument::where('company_id', $companyId)->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $document->id,
                'processing_status' => $document->processing_status,
                'ai_classification' => $document->ai_classification,
                'extraction_method' => $document->extraction_method,
                'error_message' => $document->error_message,
                'has_extracted_data' => ! empty($document->extracted_data),
                'linked_bill_id' => $document->linked_bill_id,
                'linked_expense_id' => $document->linked_expense_id,
                'linked_invoice_id' => $document->linked_invoice_id,
            ],
        ]);
    }

    /**
     * Confirm AI extraction and create an entity from the extracted data.
     */
    public function confirm(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $companyId = (int) $request->header('company');

        $doc = ClientDocument::where('company_id', $companyId)->findOrFail($id);

        if ($doc->processing_status !== ClientDocument::PROCESSING_EXTRACTED) {
            return response()->json([
                'success' => false,
                'message' => 'Document must be in "extracted" state to confirm.',
            ], 422);
        }

        $extractedData = $request->input('extracted_data', $doc->extracted_data);
        $entityType = $request->input('entity_type', $this->inferEntityType($doc));

        $service = app(DocumentConfirmationService::class);

        try {
            $result = match ($entityType) {
                'bill' => $service->confirmAsBill($doc, $extractedData, $companyId),
                'expense' => $service->confirmAsExpense($doc, $extractedData, $companyId),
                'invoice' => $service->confirmAsInvoice($doc, $extractedData, $companyId),
                'bank_transactions' => $service->confirmAsBankTransactions($doc, $extractedData, $companyId),
                'items' => $service->confirmAsItems($doc, $extractedData, $companyId),
                'tax_form', 'contract' => $service->confirmAsDocument($doc, $extractedData),
                default => throw ValidationException::withMessages(['entity_type' => 'Unsupported entity type.']),
            };

            $doc->update([
                'processing_status' => ClientDocument::PROCESSING_CONFIRMED,
                'status' => ClientDocument::STATUS_REVIEWED,
                'reviewer_id' => $user->id,
                'reviewed_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Document confirmed successfully.',
                'data' => array_merge(
                    ['entity_type' => $entityType],
                    $result,
                    ['document' => $this->formatDocument($doc->fresh())]
                ),
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Document confirm failed', [
                'document_id' => $doc->id,
                'entity_type' => $entityType,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to confirm document: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Infer the entity type from the AI classification.
     */
    private function inferEntityType(ClientDocument $doc): string
    {
        return match ($doc->ai_classification['type'] ?? 'other') {
            'invoice' => 'invoice',
            'receipt' => 'expense',
            'bank_statement' => 'bank_transactions',
            'product_list' => 'items',
            'tax_form' => 'tax_form',
            'contract' => 'contract',
            default => 'bill',
        };
    }

    /**
     * Re-run AI processing on a document.
     */
    public function reprocess(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $doc = ClientDocument::where('company_id', $companyId)->findOrFail($id);

        // Only allow reprocessing of failed or extracted (not confirmed) documents
        if ($doc->processing_status === ClientDocument::PROCESSING_CONFIRMED) {
            return response()->json([
                'success' => false,
                'message' => 'Confirmed documents cannot be reprocessed.',
            ], 422);
        }

        // Clear previous AI data
        $doc->update([
            'processing_status' => ClientDocument::PROCESSING_PENDING,
            'ai_classification' => null,
            'extracted_data' => null,
            'extraction_method' => null,
            'error_message' => null,
        ]);

        ProcessClientDocumentJob::dispatch($doc->id);

        return response()->json([
            'success' => true,
            'message' => 'Document reprocessing started.',
            'data' => $this->formatDocument($doc->fresh()),
        ]);
    }

    /**
     * Get the active partner ID for a company.
     */
    private function getActivePartnerId(int $companyId): ?int
    {
        $link = \DB::table('partner_company_links')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->first();

        return $link ? (int) $link->partner_id : null;
    }

    /**
     * Format a document for API response.
     */
    private function formatDocument(ClientDocument $document): array
    {
        // Check if file exists on current storage disk
        $fileAvailable = false;
        if ($document->file_path) {
            try {
                $disk = config('filesystems.media_disk');
                $fileAvailable = Storage::disk($disk)->exists($document->file_path);
            } catch (\Throwable $e) {
                $fileAvailable = false;
            }
        }

        return [
            'id' => $document->id,
            'company_id' => $document->company_id,
            'uploaded_by' => $document->uploaded_by,
            'partner_id' => $document->partner_id,
            'category' => $document->category,
            'original_filename' => $document->original_filename,
            'file_size' => $document->file_size,
            'mime_type' => $document->mime_type,
            'status' => $document->status,
            'processing_status' => $document->processing_status,
            'ai_classification' => $document->ai_classification,
            'extracted_data' => $document->extracted_data,
            'linked_bill_id' => $document->linked_bill_id,
            'linked_expense_id' => $document->linked_expense_id,
            'linked_invoice_id' => $document->linked_invoice_id,
            'extraction_method' => $document->extraction_method,
            'error_message' => $document->error_message,
            'reviewer_id' => $document->reviewer_id,
            'reviewed_at' => $document->reviewed_at?->toIso8601String(),
            'notes' => $document->notes,
            'rejection_reason' => $document->rejection_reason,
            'metadata' => $document->metadata,
            'file_available' => $fileAvailable,
            'created_at' => $document->created_at?->toIso8601String(),
            'updated_at' => $document->updated_at?->toIso8601String(),
            'uploader' => $document->relationLoaded('uploader') && $document->uploader ? [
                'id' => $document->uploader->id,
                'name' => $document->uploader->name,
                'email' => $document->uploader->email,
            ] : null,
            'reviewer' => $document->relationLoaded('reviewer') && $document->reviewer ? [
                'id' => $document->reviewer->id,
                'name' => $document->reviewer->name,
                'email' => $document->reviewer->email,
            ] : null,
        ];
    }
} // CLAUDE-CHECKPOINT

