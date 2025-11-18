<?php

namespace Modules\Mk\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Picqer\Barcode\BarcodeGeneratorSVG;
use Picqer\Barcode\Types\TypeCode128;
use Picqer\Barcode\Types\TypeEan13;
use Picqer\Barcode\Types\TypeUpcA;

/**
 * Barcode Generation Service
 *
 * Provides barcode generation capabilities for invoices, products, and items
 * Supports CODE128, EAN13, and UPCA formats
 *
 * @example
 * $barcodeService = app(BarcodeService::class);
 * $svg = $barcodeService->generate('1234567890', 'code128', 'svg');
 * $png = $barcodeService->generate('1234567890123', 'ean13', 'png');
 */
class BarcodeService
{
    /**
     * Supported barcode types
     */
    const TYPE_CODE128 = 'code128';

    const TYPE_EAN13 = 'ean13';

    const TYPE_UPCA = 'upca';

    /**
     * Supported output formats
     */
    const FORMAT_SVG = 'svg';

    const FORMAT_PNG = 'png';

    /**
     * Default barcode width factor
     */
    const DEFAULT_WIDTH_FACTOR = 2;

    /**
     * Default barcode height in pixels (for PNG)
     */
    const DEFAULT_HEIGHT = 50;

    /**
     * Generate barcode
     *
     * Generates a barcode in the specified format and type.
     * Returns the barcode as a string (SVG or base64-encoded PNG).
     *
     * @param  string  $code  The code to encode (must be valid for the barcode type)
     * @param  string  $type  Barcode type: code128, ean13, upca (default: code128)
     * @param  string  $format  Output format: svg or png (default: svg)
     * @param  int|null  $widthFactor  Width factor for barcode bars (default: 2)
     * @param  int|null  $height  Height in pixels for PNG format (default: 50)
     * @return string The generated barcode (SVG string or base64-encoded PNG)
     *
     * @throws Exception If barcode generation fails or code is invalid
     *
     * @example
     * // Generate CODE128 barcode as SVG
     * $svg = $service->generate('INVOICE-12345', 'code128', 'svg');
     *
     * // Generate EAN13 barcode as PNG
     * $png = $service->generate('1234567890123', 'ean13', 'png');
     *
     * // Use in HTML
     * echo '<img src="data:image/svg+xml;base64,' . base64_encode($svg) . '" />';
     */
    public function generate(
        string $code,
        string $type = self::TYPE_CODE128,
        string $format = self::FORMAT_SVG,
        ?int $widthFactor = null,
        ?int $height = null
    ): string {
        try {
            // Validate inputs
            $this->validateType($type);
            $this->validateFormat($format);
            $this->validateCode($code, $type);

            // Set defaults
            $widthFactor = $widthFactor ?? self::DEFAULT_WIDTH_FACTOR;
            $height = $height ?? self::DEFAULT_HEIGHT;

            // Get barcode type instance
            $barcodeType = $this->getBarcodeType($type);

            // Generate barcode based on format
            if ($format === self::FORMAT_SVG) {
                $generator = new BarcodeGeneratorSVG;
                $barcode = $generator->getBarcode($code, $barcodeType, $widthFactor, $height);
            } else {
                $generator = new BarcodeGeneratorPNG;
                $barcode = $generator->getBarcode($code, $barcodeType, $widthFactor, $height);
                // Return base64-encoded PNG
                $barcode = base64_encode($barcode);
            }

            Log::info('Barcode generated successfully', [
                'code' => $code,
                'type' => $type,
                'format' => $format,
                'code_length' => strlen($code),
            ]);

            return $barcode;

        } catch (Exception $e) {
            Log::error('Barcode generation failed', [
                'code' => $code,
                'type' => $type,
                'format' => $format,
                'error' => $e->getMessage(),
            ]);

            throw new Exception('Failed to generate barcode: '.$e->getMessage());
        }
    }

    /**
     * Validate barcode code based on type
     *
     * Validates the input code according to the barcode type requirements.
     *
     * @param  string  $code  The code to validate
     * @param  string  $type  The barcode type
     * @return bool True if valid
     *
     * @throws Exception If code is invalid for the specified type
     *
     * @example
     * $valid = $service->validate('1234567890123', 'ean13'); // true
     * $valid = $service->validate('123', 'ean13'); // throws Exception
     */
    public function validate(string $code, string $type): bool
    {
        try {
            $this->validateType($type);
            $this->validateCode($code, $type);

            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Generate invoice barcode
     *
     * Convenience method to generate a barcode for invoice numbers.
     * Uses CODE128 format by default as it supports alphanumeric codes.
     *
     * @param  string  $invoiceNumber  The invoice number
     * @param  string  $format  Output format: svg or png (default: svg)
     * @return string The generated barcode
     *
     * @example
     * $barcode = $service->generateInvoiceBarcode('INV-2025-001');
     */
    public function generateInvoiceBarcode(string $invoiceNumber, string $format = self::FORMAT_SVG): string
    {
        return $this->generate($invoiceNumber, self::TYPE_CODE128, $format);
    }

    /**
     * Generate product barcode
     *
     * Convenience method to generate a barcode for product codes.
     * Automatically selects appropriate type based on code format.
     *
     * @param  string  $productCode  The product code
     * @param  string  $format  Output format: svg or png (default: svg)
     * @return string The generated barcode
     *
     * @example
     * $barcode = $service->generateProductBarcode('1234567890123'); // Uses EAN13
     * $barcode = $service->generateProductBarcode('PROD-ABC-123'); // Uses CODE128
     */
    public function generateProductBarcode(string $productCode, string $format = self::FORMAT_SVG): string
    {
        $type = $this->detectBarcodeType($productCode);

        return $this->generate($productCode, $type, $format);
    }

    /**
     * Get barcode as data URI
     *
     * Returns the barcode as a complete data URI that can be used directly in HTML img src.
     *
     * @param  string  $code  The code to encode
     * @param  string  $type  Barcode type (default: code128)
     * @param  string  $format  Output format: svg or png (default: svg)
     * @return string Data URI (e.g., "data:image/svg+xml;base64,...")
     *
     * @example
     * $dataUri = $service->getDataUri('12345', 'code128', 'svg');
     * echo '<img src="' . $dataUri . '" alt="Barcode" />';
     */
    public function getDataUri(
        string $code,
        string $type = self::TYPE_CODE128,
        string $format = self::FORMAT_SVG
    ): string {
        $barcode = $this->generate($code, $type, $format);

        if ($format === self::FORMAT_SVG) {
            return 'data:image/svg+xml;base64,'.base64_encode($barcode);
        } else {
            // Barcode is already base64-encoded
            return 'data:image/png;base64,'.$barcode;
        }
    }

    /**
     * Validate barcode type
     *
     * @param  string  $type  The barcode type to validate
     *
     * @throws Exception If type is not supported
     */
    protected function validateType(string $type): void
    {
        $validTypes = [self::TYPE_CODE128, self::TYPE_EAN13, self::TYPE_UPCA];

        if (! in_array($type, $validTypes)) {
            throw new Exception(
                "Invalid barcode type '$type'. Supported types: ".implode(', ', $validTypes)
            );
        }
    }

    /**
     * Validate output format
     *
     * @param  string  $format  The output format to validate
     *
     * @throws Exception If format is not supported
     */
    protected function validateFormat(string $format): void
    {
        $validFormats = [self::FORMAT_SVG, self::FORMAT_PNG];

        if (! in_array($format, $validFormats)) {
            throw new Exception(
                "Invalid output format '$format'. Supported formats: ".implode(', ', $validFormats)
            );
        }
    }

    /**
     * Validate code based on barcode type requirements
     *
     * @param  string  $code  The code to validate
     * @param  string  $type  The barcode type
     *
     * @throws Exception If code doesn't meet type requirements
     */
    protected function validateCode(string $code, string $type): void
    {
        if (empty($code)) {
            throw new Exception('Barcode code cannot be empty');
        }

        switch ($type) {
            case self::TYPE_EAN13:
                // EAN13 requires exactly 13 digits
                if (! preg_match('/^\d{13}$/', $code)) {
                    throw new Exception('EAN13 barcode requires exactly 13 digits');
                }
                // Validate check digit
                if (! $this->validateEan13CheckDigit($code)) {
                    throw new Exception('Invalid EAN13 check digit');
                }
                break;

            case self::TYPE_UPCA:
                // UPC-A requires exactly 12 digits
                if (! preg_match('/^\d{12}$/', $code)) {
                    throw new Exception('UPC-A barcode requires exactly 12 digits');
                }
                // Validate check digit
                if (! $this->validateUpcaCheckDigit($code)) {
                    throw new Exception('Invalid UPC-A check digit');
                }
                break;

            case self::TYPE_CODE128:
                // CODE128 supports alphanumeric and special characters
                // Most characters are allowed, just check it's not too long
                if (strlen($code) > 80) {
                    throw new Exception('CODE128 barcode code too long (max 80 characters)');
                }
                break;
        }
    }

    /**
     * Get barcode type instance
     *
     * @param  string  $type  The barcode type
     * @return object The barcode type instance
     */
    protected function getBarcodeType(string $type): object
    {
        return match ($type) {
            self::TYPE_CODE128 => new TypeCode128,
            self::TYPE_EAN13 => new TypeEan13,
            self::TYPE_UPCA => new TypeUpcA,
            default => throw new Exception("Unsupported barcode type: $type"),
        };
    }

    /**
     * Validate EAN13 check digit
     *
     * @param  string  $code  The EAN13 code
     * @return bool True if check digit is valid
     */
    protected function validateEan13CheckDigit(string $code): bool
    {
        if (strlen($code) !== 13) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $code[$i] * (($i % 2 === 0) ? 1 : 3);
        }

        $checkDigit = (10 - ($sum % 10)) % 10;

        return (int) $code[12] === $checkDigit;
    }

    /**
     * Validate UPC-A check digit
     *
     * @param  string  $code  The UPC-A code
     * @return bool True if check digit is valid
     */
    protected function validateUpcaCheckDigit(string $code): bool
    {
        if (strlen($code) !== 12) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 11; $i++) {
            $sum += (int) $code[$i] * (($i % 2 === 0) ? 3 : 1);
        }

        $checkDigit = (10 - ($sum % 10)) % 10;

        return (int) $code[11] === $checkDigit;
    }

    /**
     * Auto-detect barcode type based on code format
     *
     * @param  string  $code  The code to analyze
     * @return string The detected barcode type
     */
    protected function detectBarcodeType(string $code): string
    {
        // Check if it's a valid EAN13 (13 digits)
        if (preg_match('/^\d{13}$/', $code) && $this->validateEan13CheckDigit($code)) {
            return self::TYPE_EAN13;
        }

        // Check if it's a valid UPC-A (12 digits)
        if (preg_match('/^\d{12}$/', $code) && $this->validateUpcaCheckDigit($code)) {
            return self::TYPE_UPCA;
        }

        // Default to CODE128 for everything else (most flexible)
        return self::TYPE_CODE128;
    }

    /**
     * Get list of supported barcode types
     *
     * @return array List of supported types with descriptions
     */
    public function getSupportedTypes(): array
    {
        return [
            self::TYPE_CODE128 => [
                'name' => 'CODE128',
                'description' => 'Alphanumeric barcode, most flexible',
                'pattern' => 'Up to 80 alphanumeric characters',
                'use_case' => 'Invoice numbers, product codes, general purpose',
            ],
            self::TYPE_EAN13 => [
                'name' => 'EAN-13',
                'description' => 'European Article Number (13 digits)',
                'pattern' => 'Exactly 13 digits with check digit',
                'use_case' => 'Retail products, international trade',
            ],
            self::TYPE_UPCA => [
                'name' => 'UPC-A',
                'description' => 'Universal Product Code (12 digits)',
                'pattern' => 'Exactly 12 digits with check digit',
                'use_case' => 'North American retail products',
            ],
        ];
    }
}

// CLAUDE-CHECKPOINT
