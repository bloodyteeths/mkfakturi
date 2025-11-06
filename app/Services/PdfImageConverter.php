<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * PDF to Image Converter Service
 *
 * Converts PDF documents to images for AI vision analysis.
 * Supports multiple backends: Imagick, external APIs, etc.
 */
class PdfImageConverter
{
    private string $backend;
    private int $dpi;
    private string $format;

    /**
     * Create a new PDF converter instance
     */
    public function __construct()
    {
        $this->backend = config('ai.pdf_converter_backend', 'imagick');
        $this->dpi = config('ai.pdf_converter_dpi', 150);
        $this->format = config('ai.pdf_converter_format', 'png');
    }

    /**
     * Convert PDF to array of base64 encoded images
     *
     * @param string $pdfPath Path to PDF file (can be storage path or full path)
     * @param array<string, mixed> $options Additional options
     * @return array<int, array{data: string, media_type: string, page: int}> Array of images
     * @throws \Exception If conversion fails or PDF analysis feature is disabled
     */
    public function convertToImages(string $pdfPath, array $options = []): array
    {
        // Guard: Check if PDF analysis is enabled
        if (!$this->isPdfAnalysisAllowed()) {
            Log::warning('[PdfImageConverter] PDF conversion blocked', [
                'path' => $pdfPath,
                'reason' => 'PDF analysis feature is disabled',
            ]);

            throw new \Exception(
                'Конверзијата на PDF документи не е овозможена. ' .
                'Оваа функционалност бара активирање на функцијата за анализа на PDF документи.'
            );
        }

        $dpi = $options['dpi'] ?? $this->dpi;
        $format = $options['format'] ?? $this->format;

        Log::info('[PdfImageConverter] Starting PDF conversion', [
            'path' => $pdfPath,
            'backend' => $this->backend,
            'dpi' => $dpi,
            'format' => $format,
        ]);

        // Resolve the file path
        $fullPath = $this->resolvePath($pdfPath);

        if (!file_exists($fullPath)) {
            throw new \Exception("PDF file not found: {$pdfPath}");
        }

        // Route to appropriate backend
        switch ($this->backend) {
            case 'imagick':
                return $this->convertWithImagick($fullPath, $dpi, $format);

            case 'external_api':
                return $this->convertWithExternalApi($fullPath, $dpi, $format);

            default:
                throw new \Exception("Unsupported PDF converter backend: {$this->backend}");
        }
    }

    /**
     * Convert PDF using Imagick extension
     *
     * @param string $fullPath Full path to PDF
     * @param int $dpi DPI for rendering
     * @param string $format Output format
     * @return array<int, array{data: string, media_type: string, page: int}>
     * @throws \Exception If Imagick is not available or conversion fails
     */
    private function convertWithImagick(string $fullPath, int $dpi, string $format): array
    {
        if (!extension_loaded('imagick')) {
            throw new \Exception(
                'Imagick extension is not installed. ' .
                'Please install it with: pecl install imagick, or configure an external PDF converter.'
            );
        }

        try {
            $imagick = new \Imagick();
            $imagick->setResolution($dpi, $dpi);
            $imagick->readImage($fullPath);

            $images = [];
            $pageCount = $imagick->getNumberImages();

            Log::info('[PdfImageConverter] Converting with Imagick', [
                'pages' => $pageCount,
                'dpi' => $dpi,
            ]);

            for ($page = 0; $page < $pageCount; $page++) {
                $imagick->setIteratorIndex($page);
                $imagick->setImageFormat($format);
                $imagick->setImageCompressionQuality(85);

                $imageData = base64_encode($imagick->getImageBlob());
                $mediaType = $this->getMediaType($format);

                $images[] = [
                    'data' => $imageData,
                    'media_type' => $mediaType,
                    'page' => $page + 1,
                ];
            }

            $imagick->clear();
            $imagick->destroy();

            Log::info('[PdfImageConverter] Conversion successful', [
                'pages_converted' => count($images),
            ]);

            return $images;

        } catch (\Exception $e) {
            Log::error('[PdfImageConverter] Imagick conversion failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new \Exception('PDF conversion failed: ' . $e->getMessage());
        }
    }

    /**
     * Convert PDF using external API service
     *
     * @param string $fullPath Full path to PDF
     * @param int $dpi DPI for rendering
     * @param string $format Output format
     * @return array<int, array{data: string, media_type: string, page: int}>
     * @throws \Exception If API call fails
     */
    private function convertWithExternalApi(string $fullPath, int $dpi, string $format): array
    {
        // TODO: Implement external API conversion (e.g., Gotenberg, CloudConvert, etc.)
        // For now, throw a helpful error
        throw new \Exception(
            'External API PDF conversion is not yet implemented. ' .
            'Please install Imagick extension or configure a different backend.'
        );
    }

    /**
     * Resolve file path from storage or full path
     *
     * @param string $path Path to resolve
     * @return string Full filesystem path
     */
    private function resolvePath(string $path): string
    {
        // If it's already a full path and exists, return it
        if (file_exists($path)) {
            return $path;
        }

        // Try to resolve from storage
        $storagePath = Storage::path($path);
        if (file_exists($storagePath)) {
            return $storagePath;
        }

        // Try public storage
        $publicPath = storage_path('app/public/' . $path);
        if (file_exists($publicPath)) {
            return $publicPath;
        }

        // Return original path (will fail later with clear error)
        return $path;
    }

    /**
     * Get MIME type for image format
     *
     * @param string $format Image format
     * @return string MIME type
     */
    private function getMediaType(string $format): string
    {
        $types = [
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
        ];

        return $types[strtolower($format)] ?? 'image/png';
    }

    /**
     * Check if PDF conversion is available
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        switch ($this->backend) {
            case 'imagick':
                return extension_loaded('imagick');

            case 'external_api':
                // TODO: Check if external API is configured
                return false;

            default:
                return false;
        }
    }

    /**
     * Get the current backend name
     *
     * @return string
     */
    public function getBackend(): string
    {
        return $this->backend;
    }

    /**
     * Get backend status and capabilities
     *
     * @return array<string, mixed>
     */
    public function getStatus(): array
    {
        return [
            'backend' => $this->backend,
            'available' => $this->isAvailable(),
            'pdf_analysis_allowed' => $this->isPdfAnalysisAllowed(),
            'imagick_loaded' => extension_loaded('imagick'),
            'imagick_version' => extension_loaded('imagick') ? \Imagick::getVersion()['versionString'] : null,
            'dpi' => $this->dpi,
            'format' => $this->format,
        ];
    }

    /**
     * Check if PDF analysis feature is allowed
     *
     * @return bool
     */
    private function isPdfAnalysisAllowed(): bool
    {
        // Check all PDF-related features - conversion is allowed if ANY of them is enabled
        $pdfAnalysis = config('ai.features.pdf_analysis', false);
        $receiptScanning = config('ai.features.receipt_scanning', false);
        $invoiceExtraction = config('ai.features.invoice_extraction', false);

        $allowed = $pdfAnalysis || $receiptScanning || $invoiceExtraction;

        Log::debug('[PdfImageConverter] PDF analysis permission checked', [
            'pdf_analysis' => $pdfAnalysis,
            'receipt_scanning' => $receiptScanning,
            'invoice_extraction' => $invoiceExtraction,
            'allowed' => $allowed,
        ]);

        return $allowed;
    }

    /**
     * Check if a specific feature requiring PDF conversion is enabled
     *
     * @param string $featureName Feature to check (pdf_analysis, receipt_scanning, invoice_extraction)
     * @return bool
     */
    public function isFeatureEnabled(string $featureName): bool
    {
        return (bool) config("ai.features.{$featureName}", false);
    }
}

// CLAUDE-CHECKPOINT
