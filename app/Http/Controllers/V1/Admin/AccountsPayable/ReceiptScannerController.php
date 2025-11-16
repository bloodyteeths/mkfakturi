<?php

namespace App\Http\Controllers\V1\Admin\AccountsPayable;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReceiptScanRequest;
use App\Models\Bill;
use App\Services\InvoiceParsing\InvoiceParserClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class ReceiptScannerController extends Controller
{
    public function scan(
        ReceiptScanRequest $request,
        InvoiceParserClient $parserClient,
    ): JsonResponse
    {
        try {
            \Log::info('ReceiptScannerController::scan - Starting', [
                'user_id' => auth()->id(),
                'company_id' => $request->header('company'),
                'has_file' => $request->hasFile('receipt'),
                'file_size' => $request->hasFile('receipt') ? $request->file('receipt')->getSize() : null,
                'file_mime' => $request->hasFile('receipt') ? $request->file('receipt')->getMimeType() : null,
            ]);

            $companyId = (int) $request->header('company');

            $file = $request->file('receipt');

            if (! $file) {
                \Log::error('ReceiptScannerController::scan - No file uploaded');

                return response()->json(['message' => 'No file uploaded'], 400);
            }

            \Log::info('ReceiptScannerController::scan - File details', [
                'original_name' => $file->getClientOriginalName(),
                'size_bytes' => $file->getSize(),
                'size_kb' => round($file->getSize() / 1024, 2),
                'size_mb' => round($file->getSize() / 1024 / 1024, 2),
                'mime_type' => $file->getMimeType(),
            ]);

            // Authorize based on Bill creation
            $this->authorize('create', Bill::class);

            $disk = config('filesystems.default', 'local');
            $storedPath = $file->store('scanned-receipts/'.$companyId, ['disk' => $disk]);

            // Call the OCR endpoint to extract text from the image
            try {
                \Log::info('ReceiptScannerController::scan - Calling OCR endpoint', [
                    'company_id' => $companyId,
                    'stored_path' => $storedPath,
                ]);

                $ocrResult = $parserClient->ocr(
                    $companyId,
                    $storedPath,
                    $file->getClientOriginalName()
                );

                \Log::info('ReceiptScannerController::scan - OCR completed', [
                    'company_id' => $companyId,
                    'text_length' => strlen($ocrResult['text'] ?? ''),
                ]);

                // Generate the image URL
                $imageUrl = Storage::disk($disk)->url($storedPath);

                return response()->json([
                    'image_url' => $imageUrl,
                    'stored_path' => $storedPath,
                    'ocr_text' => $ocrResult['text'] ?? '',
                ], 200);

            } catch (\Throwable $ocrException) {
                \Log::error('ReceiptScannerController::scan - OCR failed', [
                    'user_id' => auth()->id(),
                    'company_id' => $companyId,
                    'error' => $ocrException->getMessage(),
                    'exception' => get_class($ocrException),
                ]);

                return response()->json([
                    'message' => 'ocr_failed',
                    'error' => $ocrException->getMessage(),
                ], 422);
            }
        } catch (\Throwable $e) {
            \Log::error('ReceiptScannerController::scan - Unhandled exception', [
                'user_id' => auth()->id(),
                'company_id' => $request->header('company'),
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);

            // Mirror into PHP error log so it appears in container logs
            error_log(sprintf(
                '[ReceiptScanner] Unhandled exception for company %s: %s (%s:%d)',
                $request->header('company'),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            ));

            return response()->json([
                'message' => 'receipt_scan_failed',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    } // CLAUDE-CHECKPOINT
}
