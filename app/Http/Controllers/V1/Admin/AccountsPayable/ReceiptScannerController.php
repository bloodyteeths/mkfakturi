<?php

namespace App\Http\Controllers\V1\Admin\AccountsPayable;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReceiptScanRequest;
use App\Models\Bill;
use App\Services\InvoiceParsing\InvoiceParserClient;
use App\Services\PdfImageConverter;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class ReceiptScannerController extends Controller
{
    public function scan(
        ReceiptScanRequest $request,
        InvoiceParserClient $parserClient,
        PdfImageConverter $pdfConverter,
    ): JsonResponse {
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

            $mimeType = $file->getMimeType();
            $originalName = $file->getClientOriginalName();
            $isPdf = $mimeType === 'application/pdf' || str_ends_with(strtolower($originalName), '.pdf');

            \Log::info('ReceiptScannerController::scan - File details', [
                'original_name' => $originalName,
                'size_bytes' => $file->getSize(),
                'size_kb' => round($file->getSize() / 1024, 2),
                'size_mb' => round($file->getSize() / 1024 / 1024, 2),
                'mime_type' => $mimeType,
                'is_pdf' => $isPdf,
            ]);

            // Authorize based on Bill creation
            $this->authorize('create', Bill::class);

            $disk = config('filesystems.default', 'local');
            $storedPath = $file->store('scanned-receipts/'.$companyId, ['disk' => $disk]);
            $ocrFilePath = $storedPath;
            $ocrFileName = $originalName;

            // If PDF, convert first page to image for OCR
            if ($isPdf) {
                \Log::info('ReceiptScannerController::scan - PDF detected, converting to image', [
                    'stored_path' => $storedPath,
                ]);

                try {
                    // Check if PDF converter is available
                    if (!$pdfConverter->isAvailable()) {
                        \Log::warning('ReceiptScannerController::scan - PDF converter not available', [
                            'backend' => $pdfConverter->getBackend(),
                            'status' => $pdfConverter->getStatus(),
                        ]);

                        return response()->json([
                            'message' => 'pdf_conversion_unavailable',
                            'error' => 'PDF conversion is not available. Please upload an image (JPEG/PNG) instead.',
                        ], 422);
                    }

                    // Convert PDF to images (first page only for receipts)
                    $images = $pdfConverter->convertToImages($storedPath, ['dpi' => 200]);

                    if (empty($images)) {
                        throw new \Exception('PDF conversion returned no images');
                    }

                    // Take first page and save as PNG
                    $firstPage = $images[0];
                    $imageData = base64_decode($firstPage['data']);
                    $imageName = pathinfo($originalName, PATHINFO_FILENAME) . '_page1.png';
                    $imagePath = 'scanned-receipts/' . $companyId . '/' . $imageName;

                    Storage::disk($disk)->put($imagePath, $imageData);

                    \Log::info('ReceiptScannerController::scan - PDF converted to image', [
                        'original_pdf' => $storedPath,
                        'converted_image' => $imagePath,
                        'image_size' => strlen($imageData),
                    ]);

                    // Use the converted image for OCR
                    $ocrFilePath = $imagePath;
                    $ocrFileName = $imageName;

                } catch (\Exception $pdfException) {
                    \Log::error('ReceiptScannerController::scan - PDF conversion failed', [
                        'error' => $pdfException->getMessage(),
                        'stored_path' => $storedPath,
                    ]);

                    return response()->json([
                        'message' => 'pdf_conversion_failed',
                        'error' => 'Failed to convert PDF to image: ' . $pdfException->getMessage(),
                    ], 422);
                }
            }

            // Call the OCR endpoint to extract text from the image
            try {
                \Log::info('ReceiptScannerController::scan - Calling OCR endpoint', [
                    'company_id' => $companyId,
                    'ocr_file_path' => $ocrFilePath,
                    'ocr_file_name' => $ocrFileName,
                    'was_pdf' => $isPdf,
                ]);

                $ocrResult = $parserClient->ocr(
                    $companyId,
                    $ocrFilePath,
                    $ocrFileName
                );

                \Log::info('ReceiptScannerController::scan - OCR completed', [
                    'company_id' => $companyId,
                    'text_length' => strlen($ocrResult['text'] ?? ''),
                ]);

                // Generate the image URL using our custom route instead of Storage::url()
                // to avoid dependency on storage:link symlink which doesn't exist in Railway
                // For PDFs, use the converted image path so we can display the image preview
                $imageUrl = url('api/v1/receipts/image/'.$ocrFilePath);

                $responsePayload = [
                    'image_url' => $imageUrl,
                    'stored_path' => $storedPath, // Keep original PDF path for reference
                    'ocr_file_path' => $ocrFilePath, // Path used for OCR (converted image for PDFs)
                    'ocr_text' => $ocrResult['text'] ?? '',
                    'hocr' => $ocrResult['hocr'] ?? null, // Include hOCR for selectable text overlay
                    'image_width' => $ocrResult['image_width'] ?? null,
                    'image_height' => $ocrResult['image_height'] ?? null,
                ];

                \Log::info('ReceiptScannerController::scan - Returning response', [
                    'response' => $responsePayload,
                    'image_url_length' => strlen($imageUrl),
                    'ocr_text_length' => strlen($ocrResult['text'] ?? ''),
                    'has_hocr' => isset($ocrResult['hocr']),
                ]);

                return response()->json($responsePayload, 200);

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
    }

    /**
     * Serve a scanned receipt image from storage.
     * This avoids the need for public/storage symlink in Railway.
     */
    public function getImage(string $path): \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\JsonResponse
    {
        try {
            $disk = config('filesystems.default', 'local');

            // Security check: ensure the path starts with scanned-receipts/
            if (! str_starts_with($path, 'scanned-receipts/')) {
                return response()->json(['error' => 'Invalid path'], 403);
            }

            // Check if file exists
            if (! Storage::disk($disk)->exists($path)) {
                return response()->json(['error' => 'File not found'], 404);
            }

            $fullPath = Storage::disk($disk)->path($path);
            $mimeType = Storage::disk($disk)->mimeType($path);

            return response()->file($fullPath, [
                'Content-Type' => $mimeType,
                'Cache-Control' => 'public, max-age=3600',
            ]);
        } catch (\Throwable $e) {
            \Log::error('ReceiptScannerController::getImage - Failed to serve image', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Failed to load image'], 500);
        }
    } // CLAUDE-CHECKPOINT
}
