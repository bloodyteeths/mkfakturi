<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;
use RuntimeException;

/**
 * Fiscal Receipt Barcode Service
 *
 * IMPORTANT: Macedonian fiscal receipts use DataMatrix barcodes, NOT QR codes.
 *
 * Current limitations:
 * - Standard QR/DataMatrix decoders cannot read Macedonian fiscal DataMatrix codes
 * - The codes contain encrypted payloads that require UJP server validation
 * - See FISCAL_RECEIPT_SCANNING.md for full technical details
 *
 * This service attempts barcode detection but will fail on Macedonian fiscal receipts.
 * The OCR parser (invoice2data-service) is the recommended and working solution.
 */

class FiscalReceiptQrService
{
    /**
     * Decode QR code from an uploaded receipt file and normalize payload.
     *
     * @return array<string,mixed>
     */
    public function decodeAndNormalize(UploadedFile $file): array
    {
        // Ensure we have at least one supported image backend
        if (! extension_loaded('imagick') && ! extension_loaded('gd')) {
            Log::error('FiscalReceiptQrService::decodeAndNormalize - No image backend available', [
                'imagick_loaded' => extension_loaded('imagick'),
                'gd_loaded' => extension_loaded('gd'),
            ]);

            throw new RuntimeException('QR decoding requires Imagick or GD PHP extensions to be enabled on the server.');
        }

        $path = $file->getRealPath();

        if (! $path) {
            throw new RuntimeException('Unable to read uploaded file');
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $imagePath = $path;
        $temporaryImagePath = null;

        // Convert first page of PDF receipts into a PNG for QR scanning
        if ($extension === 'pdf') {
            if (! class_exists(\Imagick::class)) {
                throw new RuntimeException('PDF QR decoding requires Imagick extension');
            }

            $temporaryImagePath = $this->convertPdfToPng($path);
            $imagePath = $temporaryImagePath;
        }

        // CRITICAL: ZXing QR decoder exhausts memory on large images (3MB+).
        // Resize images > 2MB to a reasonable size (max 1920px) before decoding.
        // QR codes remain readable at lower resolutions and this prevents OOM.
        $fileSize = filesize($imagePath);
        $maxSizeBytes = 2 * 1024 * 1024; // 2MB

        if ($fileSize > $maxSizeBytes) {
            Log::info('FiscalReceiptQrService::decodeAndNormalize - Large image detected, resizing before QR decode', [
                'original_size_bytes' => $fileSize,
                'original_size_mb' => round($fileSize / 1024 / 1024, 2),
            ]);

            $resizedPath = $this->resizeImageForQr($imagePath, $extension);
            if ($resizedPath) {
                // Clean up the original temp path if it was a PDF conversion
                if ($temporaryImagePath && $temporaryImagePath === $imagePath && file_exists($temporaryImagePath)) {
                    @unlink($temporaryImagePath);
                }
                $temporaryImagePath = $resizedPath;
                $imagePath = $resizedPath;

                Log::info('FiscalReceiptQrService::decodeAndNormalize - Image resized', [
                    'new_size_bytes' => file_exists($imagePath) ? filesize($imagePath) : null,
                    'new_size_mb' => file_exists($imagePath) ? round(filesize($imagePath) / 1024 / 1024, 2) : null,
                ]);
            }
        }

        $maxRetries = (int) env('FISCAL_QR_MAX_RETRIES', 2);

        // STRATEGY: Try QR decoding in this order for best results:
        // 1. Original uploaded image/file (works for clear QR codes)
        // 2. Enhanced image with grayscale + contrast (for low-quality scans)
        // This ensures we don't break QR-only images with enhancement errors.

        // Attempt 1: Decode original image directly
        Log::info('FiscalReceiptQrService::decodeAndNormalize - Attempting QR decode on original image', [
            'image_path' => $imagePath,
            'file_exists' => file_exists($imagePath),
            'file_size' => file_exists($imagePath) ? filesize($imagePath) : null,
            'extension' => $extension,
        ]);

        $text = $this->decodeImagePath($imagePath);

        if ($text) {
            Log::info('FiscalReceiptQrService::decodeAndNormalize - QR decoded successfully from original image', [
                'text_length' => strlen($text),
                'text_preview' => substr($text, 0, 50),
            ]);
        } else {
            Log::info('FiscalReceiptQrService::decodeAndNormalize - No QR code found in original image');
        }

        // Attempt 2: If decode failed and we have retries enabled, enhance
        // the image (grayscale, higher DPI, contrast) and retry. This
        // significantly increases success rates on Macedonian fiscal receipts
        // with low contrast or narrow QR modules, BUT can fail on some PNGs
        // with color space issues, so we only do this as a fallback.
        if (! $text && $maxRetries > 1) {
            Log::info('FiscalReceiptQrService::decodeAndNormalize - Initial QR decode failed, trying image enhancement', [
                'max_retries' => $maxRetries,
            ]);

            try {
                $enhancedPath = $this->enhanceImageForQr($path, $extension);

                if ($enhancedPath) {
                    Log::info('FiscalReceiptQrService::decodeAndNormalize - Attempting QR decode on enhanced image', [
                        'enhanced_path' => $enhancedPath,
                        'file_exists' => file_exists($enhancedPath),
                        'file_size' => file_exists($enhancedPath) ? filesize($enhancedPath) : null,
                    ]);

                    $text = $this->decodeImagePath($enhancedPath);

                    if ($text) {
                        Log::info('FiscalReceiptQrService::decodeAndNormalize - QR decoded successfully from enhanced image', [
                            'text_length' => strlen($text),
                            'text_preview' => substr($text, 0, 50),
                        ]);
                    } else {
                        Log::info('FiscalReceiptQrService::decodeAndNormalize - No QR code found in enhanced image');
                    }
                }

                if ($enhancedPath && file_exists($enhancedPath)) {
                    @unlink($enhancedPath);
                }
            } catch (\Throwable $enhanceException) {
                // Enhancement can fail on some images (e.g., PNG color space
                // issues). Log the error but don't throw - we'll fall back
                // to the parser microservice instead.
                Log::warning('FiscalReceiptQrService::decodeAndNormalize - Image enhancement failed', [
                    'error' => $enhanceException->getMessage(),
                    'exception' => get_class($enhanceException),
                ]);
            }
        }

        if ($temporaryImagePath && file_exists($temporaryImagePath)) {
            @unlink($temporaryImagePath);
        }

        if (! $text) {
            // Do not leak low-level errors to the user; callers should treat
            // this as a soft failure and may fall back to OCR parsing.
            throw new RuntimeException('QR code not detected in receipt; please upload a clear image or PDF');
        }

        return $this->parsePayload($text);
    }

    protected function decodeImagePath(string $imagePath): ?string
    {
        // NOTE: QR/DataMatrix decoding is currently disabled as standard libraries
        // cannot read Macedonian fiscal DataMatrix codes. This method is kept
        // for potential future implementation when UJP provides official API/SDK.
        //
        // For now, receipts are processed via OCR parser (invoice2data-service).

        return null;
    }

    protected function convertPdfToPng(string $pdfPath): string
    {
        $imagick = new \Imagick();
        $imagick->setResolution(300, 300);
        $imagick->readImage($pdfPath.'[0]');
        $imagick->setImageFormat('png');
        $imagick->setImageColorspace(\Imagick::COLORSPACE_RGB);

        $temporaryImagePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('qr_pdf_', true).'.png';
        $imagick->writeImage($temporaryImagePath);
        $imagick->clear();
        $imagick->destroy();

        return $temporaryImagePath;
    }

    protected function resizeImageForQr(string $imagePath, string $extension): ?string
    {
        // Target max dimension (width or height) to keep QR codes readable
        // while reducing memory usage for ZXing decoder
        $maxDimension = 1920;

        // Prefer Imagick for better quality and memory handling
        if (class_exists(\Imagick::class)) {
            try {
                $imagick = new \Imagick();
                $imagick->readImage($imagePath);

                $width = $imagick->getImageWidth();
                $height = $imagick->getImageHeight();

                // Only resize if image is larger than max dimension
                if ($width > $maxDimension || $height > $maxDimension) {
                    if ($width > $height) {
                        $imagick->scaleImage($maxDimension, 0);
                    } else {
                        $imagick->scaleImage(0, $maxDimension);
                    }
                }

                $imagick->setImageFormat('png');
                $imagick->setImageCompressionQuality(90);

                $resizedPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('qr_resized_', true).'.png';
                $imagick->writeImage($resizedPath);
                $imagick->clear();
                $imagick->destroy();

                return $resizedPath;
            } catch (\Throwable $e) {
                Log::warning('FiscalReceiptQrService::resizeImageForQr - Imagick resize failed', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Fallback to GD if Imagick is not available
        if (extension_loaded('gd')) {
            try {
                $contents = @file_get_contents($imagePath);
                if ($contents === false) {
                    return null;
                }

                $image = @imagecreatefromstring($contents);
                if (! $image) {
                    return null;
                }

                $width = imagesx($image);
                $height = imagesy($image);

                // Only resize if image is larger than max dimension
                if ($width > $maxDimension || $height > $maxDimension) {
                    if ($width > $height) {
                        $newWidth = $maxDimension;
                        $newHeight = (int) ($height * ($maxDimension / $width));
                    } else {
                        $newHeight = $maxDimension;
                        $newWidth = (int) ($width * ($maxDimension / $height));
                    }

                    $resized = imagecreatetruecolor($newWidth, $newHeight);
                    imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                    imagedestroy($image);
                    $image = $resized;
                }

                $resizedPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('qr_resized_', true).'.png';
                imagepng($image, $resizedPath, 9);
                imagedestroy($image);

                return $resizedPath;
            } catch (\Throwable $e) {
                Log::warning('FiscalReceiptQrService::resizeImageForQr - GD resize failed', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return null;
    }

    protected function enhanceImageForQr(string $path, string $extension): ?string
    {
        // Prefer Imagick when available as it provides better control over
        // DPI, grayscale conversion, and contrast enhancement. Fallback to GD
        // when Imagick is not installed.
        if (class_exists(\Imagick::class)) {
            try {
                $imagick = new \Imagick();
                if ($extension === 'pdf') {
                    $imagick->setResolution(300, 300);
                    $imagick->readImage($path.'[0]');
                } else {
                    $imagick->readImage($path);
                }

                $imagick->setImageFormat('png');
                $imagick->setImageColorspace(\Imagick::COLORSPACE_GRAY);
                $imagick->setImageCompressionQuality(100);
                // Increase contrast to make QR modules more distinct
                $imagick->contrastImage(true);

                $enhancedPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('qr_enhanced_', true).'.png';
                $imagick->writeImage($enhancedPath);
                $imagick->clear();
                $imagick->destroy();

                return $enhancedPath;
            } catch (\Throwable $e) {
                Log::warning('FiscalReceiptQrService::enhanceImageForQr - Imagick enhancement failed', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (extension_loaded('gd')) {
            try {
                // Read the image from file contents to avoid path issues
                $contents = @file_get_contents($path);
                if ($contents === false) {
                    return null;
                }

                $image = @imagecreatefromstring($contents);

                if (! $image) {
                    // Try extension-specific loaders as fallback
                    if (in_array($extension, ['jpg', 'jpeg'], true)) {
                        $image = @imagecreatefromjpeg($path);
                    } elseif ($extension === 'png') {
                        $image = @imagecreatefrompng($path);
                    }
                }

                if (! $image) {
                    return null;
                }

                imagefilter($image, IMG_FILTER_GRAYSCALE);
                imagefilter($image, IMG_FILTER_CONTRAST, -20);

                $enhancedPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('qr_enhanced_', true).'.png';
                imagepng($image, $enhancedPath);
                imagedestroy($image);

                return $enhancedPath;
            } catch (\Throwable $e) {
                Log::warning('FiscalReceiptQrService::enhanceImageForQr - GD enhancement failed', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return null;
    }

    /**
     * Parse a Macedonian fiscal QR payload string into a normalized DTO.
     *
     * Expected format (example):
     * MK|TIN=MK1234567;DATETIME=2025-11-15T10:30:00;TOTAL=1000;VAT=180;FID=ABC123;TYPE=CASH
     *
     * @return array<string,mixed>
     */
    public function parsePayload(string $payload): array
    {
        $upper = strtoupper($payload);
        if (! str_starts_with($upper, 'MK|')) {
            throw new RuntimeException('Unsupported fiscal QR format');
        }

        $body = substr($payload, 3);
        $segments = explode(';', $body);

        $data = [];
        foreach ($segments as $segment) {
            if (! str_contains($segment, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $segment, 2);
            $data[strtoupper(trim($key))] = trim($value);
        }

        if (! isset($data['TIN'], $data['TOTAL'], $data['DATETIME'])) {
            throw new RuntimeException('Missing required fiscal QR fields');
        }

        $total = (float) $data['TOTAL'];
        $vat = isset($data['VAT']) ? (float) $data['VAT'] : 0.0;

        $type = strtolower($data['TYPE'] ?? 'CASH');
        $documentType = $type === 'invoice' ? 'invoice' : 'cash';

        try {
            $dateTime = new \Carbon\Carbon($data['DATETIME']);
        } catch (\Throwable $e) {
            throw new RuntimeException('Invalid DATETIME in fiscal QR payload: '.$e->getMessage(), 0, $e);
        }

        return [
            'issuer_tax_id' => $data['TIN'],
            'date_time' => $dateTime,
            'total' => $total,
            'vat_total' => $vat,
            'fiscal_id' => $data['FID'] ?? null,
            'type' => $documentType,
        ];
    }
}
