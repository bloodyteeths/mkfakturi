<?php

namespace App\Http\Controllers\V1\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClientDocumentUploadRequest;
use App\Models\ClientDocument;
use App\Models\Partner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
            // Store file
            Storage::putFileAs($directory, $file, $filename);

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

            return response()->json([
                'success' => true,
                'message' => 'Document uploaded successfully.',
                'data' => $this->formatDocument($document),
            ], 201);
        } catch (\Exception $e) {
            // Clean up file if it was stored
            if (Storage::exists($path)) {
                Storage::delete($path);
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

        if (! $document->isPending()) {
            return response()->json([
                'success' => false,
                'message' => 'Only pending documents can be deleted.',
            ], 403);
        }

        // Delete the physical file
        if ($document->file_path && Storage::exists($document->file_path)) {
            Storage::delete($document->file_path);
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
        $companyId = (int) $request->header('company');

        $document = ClientDocument::where('company_id', $companyId)->findOrFail($id);

        if (! $user->isOwner() && ! $user->hasCompany($document->company_id)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this document.',
            ], 403);
        }

        if (! $document->file_path || ! Storage::exists($document->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'File not found.',
            ], 404);
        }

        return Storage::download(
            $document->file_path,
            $document->original_filename,
            ['Content-Type' => $document->mime_type]
        );
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
}

