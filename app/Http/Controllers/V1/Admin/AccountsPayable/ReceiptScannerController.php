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

            // Read file contents once (avoids S3 round-trip for non-PDF images)
            $rawContents = file_get_contents($file->getPathname());

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
                    $images = $pdfConverter->convertToImages($storedPath, ['maxPages' => 1]);

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

            // Call the /parse endpoint (Gemini Vision AI) to extract structured invoice data
            try {
                \Log::info('ReceiptScannerController::scan - Calling parse endpoint', [
                    'company_id' => $companyId,
                    'ocr_file_path' => $ocrFilePath,
                    'ocr_file_name' => $ocrFileName,
                    'was_pdf' => $isPdf,
                ]);

                // For non-PDF: pass raw file contents directly (skip S3 read-back)
                // For PDF: converted image is already in S3, rawContents won't match
                $parseContents = $isPdf ? null : $rawContents;

                $parseResult = $parserClient->parseReceipt(
                    $companyId,
                    $ocrFilePath,
                    $ocrFileName,
                    $parseContents
                );

                \Log::info('ReceiptScannerController::scan - Parse completed', [
                    'company_id' => $companyId,
                    'extraction_method' => $parseResult['extraction_method'] ?? 'unknown',
                    'supplier' => $parseResult['supplier']['name'] ?? null,
                ]);

                // Generate image URL: prefer S3 temporary URL (avoids proxying large files through PHP)
                try {
                    $imageUrl = Storage::disk($disk)->temporaryUrl($ocrFilePath, now()->addHours(2));
                } catch (\RuntimeException $e) {
                    // Fallback for local disk or drivers that don't support temporary URLs
                    $imageUrl = url('api/v1/receipts/image/'.$ocrFilePath);
                }

                // Map structured Gemini response to bill form fields
                $parsedData = [
                    'vendor_name' => $parseResult['supplier']['name'] ?? null,
                    'tax_id' => $parseResult['supplier']['tax_id'] ?? null,
                    'bill_number' => $parseResult['invoice']['number'] ?? null,
                    'bill_date' => $parseResult['invoice']['date'] ?? null,
                    'due_date' => $parseResult['invoice']['due_date'] ?? null,
                    'total' => isset($parseResult['totals']['total'])
                        ? $parseResult['totals']['total'] / 100
                        : null,
                    'subtotal' => isset($parseResult['totals']['subtotal'])
                        ? $parseResult['totals']['subtotal'] / 100
                        : null,
                    'tax' => isset($parseResult['totals']['tax'])
                        ? $parseResult['totals']['tax'] / 100
                        : null,
                    'currency' => $parseResult['invoice']['currency'] ?? null,
                    'line_items' => $parseResult['line_items'] ?? [],
                ];

                $responsePayload = [
                    'image_url' => $imageUrl,
                    'stored_path' => $storedPath,
                    'ocr_file_path' => $ocrFilePath,
                    'data' => $parsedData,
                    'extraction_method' => $parseResult['extraction_method'] ?? 'unknown',
                ];

                \Log::info('ReceiptScannerController::scan - Returning response', [
                    'image_url' => $imageUrl,
                    'extraction_method' => $parseResult['extraction_method'] ?? 'unknown',
                    'vendor' => $parsedData['vendor_name'],
                    'total' => $parsedData['total'],
                ]);

                return response()->json($responsePayload, 200);

            } catch (\Throwable $parseException) {
                \Log::error('ReceiptScannerController::scan - Parse failed', [
                    'user_id' => auth()->id(),
                    'company_id' => $companyId,
                    'error' => $parseException->getMessage(),
                    'exception' => get_class($parseException),
                ]);

                return response()->json([
                    'message' => 'parse_failed',
                    'error' => $parseException->getMessage(),
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
    public function getImage(string $path): \Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\JsonResponse
    {
        try {
            $disk = config('filesystems.default', 'local');

            if (! str_starts_with($path, 'scanned-receipts/')) {
                return response()->json(['error' => 'Invalid path'], 403);
            }

            if (! Storage::disk($disk)->exists($path)) {
                return response()->json(['error' => 'File not found'], 404);
            }

            $mimeType = Storage::disk($disk)->mimeType($path) ?: 'application/octet-stream';
            $size = Storage::disk($disk)->size($path);

            // Stream the file instead of loading into memory (handles large files on S3/R2)
            return response()->stream(function () use ($disk, $path) {
                $stream = Storage::disk($disk)->readStream($path);
                fpassthru($stream);
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }, 200, [
                'Content-Type' => $mimeType,
                'Content-Length' => $size,
                'Cache-Control' => 'public, max-age=3600',
            ]);
        } catch (\Throwable $e) {
            \Log::error('ReceiptScannerController::getImage - Failed to serve image', [
                'path' => $path,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Failed to load image: '.$e->getMessage()], 500);
        }
    } // CLAUDE-CHECKPOINT

    /**
     * Extract structured invoice data from OCR text for form pre-fill.
     */
    private function parseOcrText(string $text): ?array
    {
        if (empty(trim($text))) {
            return null;
        }

        $lines = array_values(array_filter(array_map('trim', explode("\n", $text))));

        // Supplier name is typically the first non-empty line
        $vendorName = $lines[0] ?? null;

        // Date detection (YYYY-MM-DD or DD.MM.YYYY or DD/MM/YYYY)
        $date = null;
        if (preg_match('/(\d{4}-\d{2}-\d{2})/', $text, $m)) {
            $date = $m[1];
        } elseif (preg_match('/(\d{2}\.\d{2}\.\d{4})/', $text, $m)) {
            $date = $m[1];
        } elseif (preg_match('/(\d{2}\/\d{2}\/\d{4})/', $text, $m)) {
            $date = $m[1];
        }

        // Invoice number detection
        $invoiceNumber = null;
        $invoicePatterns = [
            '/(?:фактура|invoice|faktura|fatura)\s*(?:бр\.?|no\.?|nr\.?|#)?\s*[:\-]?\s*(\S+)/iu',
            '/(?:бр\.?|no\.?|nr\.?)\s*[:\-]?\s*(\d[\d\-\/]+)/iu',
        ];
        foreach ($invoicePatterns as $pattern) {
            if (preg_match($pattern, $text, $m)) {
                $invoiceNumber = trim($m[1]);
                break;
            }
        }

        // Total amount - look for lines with keywords
        $keywords = ['total', 'вкупно', 'vkupno', 'износ за плаќање', 'вкупен износ', 'iznos', 'toplam', 'totali'];
        $total = null;
        foreach ($lines as $line) {
            $lower = mb_strtolower($line);
            foreach ($keywords as $kw) {
                if (mb_strpos($lower, $kw) !== false) {
                    if (preg_match('/(\d[\d.,]*\d)/', $line, $m)) {
                        $val = (float) str_replace(',', '.', $m[1]);
                        if ($val > 0 && $val < 1000000) {
                            $total = $val;
                        }
                    }
                    break;
                }
            }
        }

        // If no keyword match, use largest reasonable number
        if ($total === null) {
            $allNumbers = [];
            preg_match_all('/(\d+[.,]\d{2})/', $text, $matches);
            foreach ($matches[1] as $raw) {
                $val = (float) str_replace(',', '.', $raw);
                if ($val > 0 && $val < 1000000) {
                    $allNumbers[] = $val;
                }
            }
            if (! empty($allNumbers)) {
                $total = max($allNumbers);
            }
        }

        return [
            'vendor_name' => $vendorName,
            'bill_number' => $invoiceNumber,
            'bill_date' => $date,
            'due_date' => null,
            'total' => $total,
            'tax' => null,
        ];
    } // CLAUDE-CHECKPOINT
}
