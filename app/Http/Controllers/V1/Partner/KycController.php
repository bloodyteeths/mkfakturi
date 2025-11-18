<?php

namespace App\Http\Controllers\V1\Partner;

use App\Http\Controllers\Controller;
use App\Models\KycDocument;
use App\Models\Partner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class KycController extends Controller
{
    /**
     * Submit KYC documents
     */
    public function submitKyc(Request $request): JsonResponse
    {
        // Get authenticated partner
        $user = $request->user();
        $partner = Partner::where('user_id', $user->id)->firstOrFail();

        // Validate request
        $validator = Validator::make($request->all(), [
            'documents' => 'required|array|min:2',
            'documents.*.type' => 'required|in:id_card,passport,proof_of_address,bank_statement,tax_certificate,other',
            'documents.*.file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check required documents
        $types = collect($request->documents)->pluck('type')->toArray();
        $requiredTypes = ['id_card', 'proof_of_address'];

        foreach ($requiredTypes as $requiredType) {
            if (! in_array($requiredType, $types)) {
                return response()->json([
                    'success' => false,
                    'message' => "Missing required document: {$requiredType}",
                ], 422);
            }
        }

        $uploadedDocuments = [];

        try {
            foreach ($request->documents as $docData) {
                $file = $docData['file'];
                $type = $docData['type'];

                // Generate secure filename
                $filename = Str::random(40).'.'.$file->getClientOriginalExtension();
                $path = 'kyc/'.$partner->id.'/'.$filename;

                // Store file (encrypted storage)
                Storage::putFileAs('kyc/'.$partner->id, $file, $filename);

                // Create KYC document record
                $document = KycDocument::create([
                    'partner_id' => $partner->id,
                    'document_type' => $type,
                    'original_filename' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'status' => 'pending',
                    'metadata' => [
                        'file_size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                        'uploaded_at' => now()->toIso8601String(),
                    ],
                ]);

                $uploadedDocuments[] = $document;
            }

            return response()->json([
                'success' => true,
                'message' => 'KYC documents submitted successfully. Our team will review them within 24-48 hours.',
                'documents' => $uploadedDocuments,
            ], 201);

        } catch (\Exception $e) {
            // Clean up uploaded files if error occurs
            foreach ($uploadedDocuments as $doc) {
                if (Storage::exists($doc->file_path)) {
                    Storage::delete($doc->file_path);
                }
                $doc->delete();
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload KYC documents',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get KYC status for authenticated partner
     */
    public function getStatus(Request $request): JsonResponse
    {
        $user = $request->user();
        $partner = Partner::where('user_id', $user->id)->firstOrFail();

        $documents = KycDocument::where('partner_id', $partner->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $allApproved = KycDocument::allRequiredDocumentsApproved($partner->id);

        return response()->json([
            'success' => true,
            'kyc_status' => $partner->kyc_status,
            'all_required_approved' => $allApproved,
            'documents' => $documents->map(function ($doc) {
                return [
                    'id' => $doc->id,
                    'document_type' => $doc->document_type,
                    'original_filename' => $doc->original_filename,
                    'status' => $doc->status,
                    'uploaded_at' => $doc->created_at->toIso8601String(),
                    'verified_at' => $doc->verified_at?->toIso8601String(),
                    'rejection_reason' => $doc->rejection_reason,
                ];
            }),
        ]);
    }

    /**
     * Download KYC document (partner can download their own documents)
     *
     * @return \Illuminate\Http\Response|JsonResponse
     */
    public function downloadDocument(Request $request, int $documentId)
    {
        $user = $request->user();
        $partner = Partner::where('user_id', $user->id)->firstOrFail();

        $document = KycDocument::where('id', $documentId)
            ->where('partner_id', $partner->id)
            ->firstOrFail();

        if (! Storage::exists($document->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'Document file not found',
            ], 404);
        }

        return Storage::download($document->file_path, $document->original_filename);
    }

    /**
     * Delete KYC document (only if status is pending or rejected)
     */
    public function deleteDocument(Request $request, int $documentId): JsonResponse
    {
        $user = $request->user();
        $partner = Partner::where('user_id', $user->id)->firstOrFail();

        $document = KycDocument::where('id', $documentId)
            ->where('partner_id', $partner->id)
            ->firstOrFail();

        // Can only delete pending or rejected documents
        if ($document->status === 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete approved documents',
            ], 403);
        }

        $document->delete(); // Soft delete, file deletion handled in model

        return response()->json([
            'success' => true,
            'message' => 'Document deleted successfully',
        ]);
    }
}

// CLAUDE-CHECKPOINT
