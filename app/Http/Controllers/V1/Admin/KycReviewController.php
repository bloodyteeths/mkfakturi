<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\KycDocument;
use App\Models\Partner;
use App\Notifications\KycStatusChanged;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class KycReviewController extends Controller
{
    /**
     * List all pending KYC documents
     */
    public function listPending(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 20);

        $documents = KycDocument::with(['partner', 'partner.user'])
            ->pending()
            ->orderBy('created_at', 'asc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'documents' => $documents->map(function ($doc) {
                return [
                    'id' => $doc->id,
                    'partner_id' => $doc->partner_id,
                    'partner_name' => $doc->partner->name,
                    'partner_email' => $doc->partner->email,
                    'document_type' => $doc->document_type,
                    'original_filename' => $doc->original_filename,
                    'status' => $doc->status,
                    'uploaded_at' => $doc->created_at->toIso8601String(),
                    'file_size' => $doc->metadata['file_size'] ?? null,
                    'mime_type' => $doc->metadata['mime_type'] ?? null,
                ];
            }),
            'pagination' => [
                'current_page' => $documents->currentPage(),
                'total_pages' => $documents->lastPage(),
                'total' => $documents->total(),
                'per_page' => $documents->perPage(),
            ],
        ]);
    }

    /**
     * List all KYC documents with filter options
     */
    public function listAll(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 20);
        $status = $request->get('status'); // 'pending', 'approved', 'rejected', or null for all
        $partnerId = $request->get('partner_id');

        $query = KycDocument::with(['partner', 'partner.user', 'verifiedBy']);

        if ($status) {
            $query->where('status', $status);
        }

        if ($partnerId) {
            $query->where('partner_id', $partnerId);
        }

        $documents = $query->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'documents' => $documents->map(function ($doc) {
                return [
                    'id' => $doc->id,
                    'partner_id' => $doc->partner_id,
                    'partner_name' => $doc->partner->name,
                    'partner_email' => $doc->partner->email,
                    'document_type' => $doc->document_type,
                    'original_filename' => $doc->original_filename,
                    'status' => $doc->status,
                    'uploaded_at' => $doc->created_at->toIso8601String(),
                    'verified_at' => $doc->verified_at?->toIso8601String(),
                    'verified_by_name' => $doc->verifiedBy?->name,
                    'rejection_reason' => $doc->rejection_reason,
                ];
            }),
            'pagination' => [
                'current_page' => $documents->currentPage(),
                'total_pages' => $documents->lastPage(),
                'total' => $documents->total(),
                'per_page' => $documents->perPage(),
            ],
        ]);
    }

    /**
     * Approve KYC document
     */
    public function approve(Request $request, int $documentId): JsonResponse
    {
        $adminUser = $request->user();
        $document = KycDocument::with('partner')->findOrFail($documentId);

        if ($document->status === 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Document already approved',
            ], 400);
        }

        DB::beginTransaction();

        try {
            // Approve the document
            $document->approve($adminUser->id);

            // Check if all required documents are approved
            $allApproved = KycDocument::allRequiredDocumentsApproved($document->partner_id);

            if ($allApproved) {
                // Update partner KYC status to verified
                $partner = $document->partner;
                $partner->kyc_status = 'verified';
                $partner->save();

                // Send email notification
                if ($partner->user) {
                    $partner->user->notify(new KycStatusChanged($partner, 'verified'));
                }

                Log::info('Partner KYC verified', [
                    'partner_id' => $partner->id,
                    'admin_user_id' => $adminUser->id,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Document approved successfully',
                'partner_kyc_verified' => $allApproved,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to approve KYC document', [
                'document_id' => $documentId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to approve document',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Reject KYC document
     */
    public function reject(Request $request, int $documentId): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|min:10|max:500',
        ]);

        $adminUser = $request->user();
        $document = KycDocument::with('partner')->findOrFail($documentId);

        if ($document->status === 'rejected') {
            return response()->json([
                'success' => false,
                'message' => 'Document already rejected',
            ], 400);
        }

        DB::beginTransaction();

        try {
            // Reject the document
            $document->reject($adminUser->id, $request->reason);

            // Update partner KYC status to rejected
            $partner = $document->partner;
            $partner->kyc_status = 'rejected';
            $partner->save();

            // Send email notification
            if ($partner->user) {
                $partner->user->notify(new KycStatusChanged($partner, 'rejected', $request->reason));
            }

            DB::commit();

            Log::info('KYC document rejected', [
                'document_id' => $documentId,
                'partner_id' => $partner->id,
                'admin_user_id' => $adminUser->id,
                'reason' => $request->reason,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Document rejected successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to reject KYC document', [
                'document_id' => $documentId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reject document',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Download KYC document (admin only)
     *
     * @return \Illuminate\Http\Response|JsonResponse
     */
    public function downloadDocument(Request $request, int $documentId)
    {
        $document = KycDocument::findOrFail($documentId);

        if (! Storage::exists($document->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'Document file not found',
            ], 404);
        }

        Log::info('KYC document downloaded by admin', [
            'document_id' => $documentId,
            'admin_user_id' => $request->user()->id,
            'partner_id' => $document->partner_id,
        ]);

        return Storage::download($document->file_path, $document->original_filename);
    }

    /**
     * Get partner KYC overview
     */
    public function getPartnerKycOverview(Request $request, int $partnerId): JsonResponse
    {
        $partner = Partner::with(['kycDocuments', 'user'])->findOrFail($partnerId);

        $documents = $partner->kycDocuments->map(function ($doc) {
            return [
                'id' => $doc->id,
                'document_type' => $doc->document_type,
                'original_filename' => $doc->original_filename,
                'status' => $doc->status,
                'uploaded_at' => $doc->created_at->toIso8601String(),
                'verified_at' => $doc->verified_at?->toIso8601String(),
                'rejection_reason' => $doc->rejection_reason,
            ];
        });

        $allRequiredApproved = KycDocument::allRequiredDocumentsApproved($partnerId);

        return response()->json([
            'success' => true,
            'partner' => [
                'id' => $partner->id,
                'name' => $partner->name,
                'email' => $partner->email,
                'kyc_status' => $partner->kyc_status,
            ],
            'documents' => $documents,
            'all_required_approved' => $allRequiredApproved,
            'required_documents' => ['id_card', 'proof_of_address'],
        ]);
    }
}

// CLAUDE-CHECKPOINT
