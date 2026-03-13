<?php

namespace App\Http\Controllers\V1\Partner;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessClientDocumentJob;
use App\Models\ClientDocument;
use App\Models\Partner;
use App\Services\DocumentConfirmationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use ZipArchive;

class PartnerClientDocumentController extends Controller
{
    /**
     * List all documents for a managed company.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request, int $companyId): JsonResponse
    {
        $partner = $this->getPartnerOrFail($request);

        if (! $this->partnerManagesCompany($partner, $companyId)) {
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

        $documents = $query->paginate($request->input('per_page', 15));

        // Count pending documents for notification badge
        $pendingCount = ClientDocument::forCompany($companyId)->pending()->count();

        return response()->json([
            'success' => true,
            'data' => $documents->getCollection()->map(fn ($doc) => $this->formatDocument($doc)),
            'pending_count' => $pendingCount,
            'current_page' => $documents->currentPage(),
            'last_page' => $documents->lastPage(),
            'per_page' => $documents->perPage(),
            'total' => $documents->total(),
            'from' => $documents->firstItem(),
            'to' => $documents->lastItem(),
        ]);
    }

    /**
     * Show a single document for a managed company.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, int $companyId, int $id): JsonResponse
    {
        $partner = $this->getPartnerOrFail($request);

        if (! $this->partnerManagesCompany($partner, $companyId)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this company.',
            ], 403);
        }

        $document = ClientDocument::with(['uploader:id,name,email', 'reviewer:id,name,email'])
            ->where('company_id', $companyId)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->formatDocument($document),
        ]);
    }

    /**
     * Mark a document as reviewed.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function markReviewed(Request $request, int $companyId, int $id): JsonResponse
    {
        $partner = $this->getPartnerOrFail($request);

        if (! $this->partnerManagesCompany($partner, $companyId)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this company.',
            ], 403);
        }

        $document = ClientDocument::where('company_id', $companyId)->findOrFail($id);

        if (! $document->isPending()) {
            return response()->json([
                'success' => false,
                'message' => 'Only pending documents can be reviewed.',
            ], 422);
        }

        $notes = $request->input('notes');
        $document->markReviewed($request->user()->id, $notes);

        return response()->json([
            'success' => true,
            'message' => 'Document marked as reviewed.',
            'data' => $this->formatDocument($document->fresh(['uploader', 'reviewer'])),
        ]);
    }

    /**
     * Reject a document with a reason.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function reject(Request $request, int $companyId, int $id): JsonResponse
    {
        $partner = $this->getPartnerOrFail($request);

        if (! $this->partnerManagesCompany($partner, $companyId)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this company.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:1000',
        ], [
            'reason.required' => 'A rejection reason is required.',
            'reason.max' => 'Rejection reason must not exceed 1000 characters.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $document = ClientDocument::where('company_id', $companyId)->findOrFail($id);

        if (! $document->isPending()) {
            return response()->json([
                'success' => false,
                'message' => 'Only pending documents can be rejected.',
            ], 422);
        }

        $document->reject($request->user()->id, $request->input('reason'));

        return response()->json([
            'success' => true,
            'message' => 'Document has been rejected.',
            'data' => $this->formatDocument($document->fresh(['uploader', 'reviewer'])),
        ]);
    }

    /**
     * Generate a signed temporary download URL for a document.
     *
     * @return \Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function download(Request $request, int $companyId, int $id)
    {
        $partner = $this->getPartnerOrFail($request);

        if (! $this->partnerManagesCompany($partner, $companyId)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this company.',
            ], 403);
        }

        $document = ClientDocument::where('company_id', $companyId)->findOrFail($id);

        if (! Storage::exists($document->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'Document file not found.',
            ], 404);
        }

        return Storage::download($document->file_path, $document->original_filename);
    }

    /**
     * Get the AI processing status of a document (for polling).
     */
    public function processingStatus(Request $request, int $companyId, int $id): JsonResponse
    {
        $partner = $this->getPartnerOrFail($request);
        if (! $this->partnerManagesCompany($partner, $companyId)) {
            return response()->json(['success' => false, 'message' => 'Access denied.'], 403);
        }

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
     * Confirm AI extraction and create an entity from extracted data (partner).
     */
    public function confirm(Request $request, int $companyId, int $id): JsonResponse
    {
        $partner = $this->getPartnerOrFail($request);
        if (! $this->partnerManagesCompany($partner, $companyId)) {
            return response()->json(['success' => false, 'message' => 'Access denied.'], 403);
        }

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
                'reviewer_id' => $request->user()->id,
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
            Log::error('Partner document confirm failed', [
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
            'invoice' => 'bill',
            'receipt' => 'expense',
            'bank_statement' => 'bank_transactions',
            'product_list' => 'items',
            'tax_form' => 'tax_form',
            'contract' => 'contract',
            default => 'bill',
        };
    }

    /**
     * Re-run AI processing on a document (partner).
     */
    public function reprocess(Request $request, int $companyId, int $id): JsonResponse
    {
        $partner = $this->getPartnerOrFail($request);
        if (! $this->partnerManagesCompany($partner, $companyId)) {
            return response()->json(['success' => false, 'message' => 'Access denied.'], 403);
        }

        $doc = ClientDocument::where('company_id', $companyId)->findOrFail($id);

        if ($doc->processing_status === ClientDocument::PROCESSING_CONFIRMED) {
            return response()->json([
                'success' => false,
                'message' => 'Confirmed documents cannot be reprocessed.',
            ], 422);
        }

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
     * Bulk download all pending documents for a company as a ZIP file.
     *
     * @return \Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function bulkDownload(Request $request, int $companyId)
    {
        $partner = $this->getPartnerOrFail($request);

        if (! $this->partnerManagesCompany($partner, $companyId)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this company.',
            ], 403);
        }

        $documents = ClientDocument::forCompany($companyId)
            ->pending()
            ->get();

        if ($documents->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No pending documents to download.',
            ], 404);
        }

        // Create ZIP file
        $zipFileName = "client-documents-{$companyId}-".now()->format('Y-m-d-His').'.zip';
        $zipPath = storage_path("app/temp/{$zipFileName}");

        // Ensure temp directory exists
        if (! file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create ZIP archive.',
            ], 500);
        }

        $addedFiles = 0;

        foreach ($documents as $document) {
            if (Storage::exists($document->file_path)) {
                $fileContent = Storage::get($document->file_path);
                $entryName = $document->category.'/'.$document->original_filename;

                // Ensure unique filename within ZIP
                $counter = 1;
                $baseName = pathinfo($document->original_filename, PATHINFO_FILENAME);
                $extension = pathinfo($document->original_filename, PATHINFO_EXTENSION);

                while ($zip->locateName($entryName) !== false) {
                    $entryName = $document->category.'/'.$baseName."_{$counter}.{$extension}";
                    $counter++;
                }

                $zip->addFromString($entryName, $fileContent);
                $addedFiles++;
            }
        }

        $zip->close();

        if ($addedFiles === 0) {
            @unlink($zipPath);

            return response()->json([
                'success' => false,
                'message' => 'No document files found on disk.',
            ], 404);
        }

        return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
    }

    /**
     * Get the authenticated partner or fail.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    private function getPartnerOrFail(Request $request): Partner
    {
        $user = $request->user();

        // Super admin bypass
        if ($user->role === 'super admin') {
            // Return a dummy partner for super admins
            $partner = new Partner();
            $partner->id = 0;
            $partner->is_active = true;

            return $partner;
        }

        return Partner::where('user_id', $user->id)->firstOrFail();
    }

    /**
     * Check if partner manages the given company.
     */
    private function partnerManagesCompany(Partner $partner, int $companyId): bool
    {
        // Super admin bypass (id=0 is our dummy)
        if ($partner->id === 0) {
            return true;
        }

        return $partner->activeCompanies()
            ->where('companies.id', $companyId)
            ->exists();
    }

    /**
     * Format a document for API response.
     */
    private function formatDocument(ClientDocument $document): array
    {
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

