<?php

namespace Modules\Mk\Services;

use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * QR Code Generation Service
 *
 * Provides QR code generation for payments, invoices, and items
 * Supports various QR code sizes and error correction levels
 *
 * @example
 * $qrService = app(QrCodeService::class);
 * $qrCode = $qrService->generatePaymentQr([
 *     'amount' => '100.00',
 *     'currency' => 'MKD',
 *     'recipient' => 'Company Name'
 * ]);
 */
class QrCodeService
{
    /**
     * Error correction levels
     * L = 7% error correction
     * M = 15% error correction
     * Q = 25% error correction
     * H = 30% error correction
     */
    const ERROR_CORRECTION_LOW = 'L';

    const ERROR_CORRECTION_MEDIUM = 'M';

    const ERROR_CORRECTION_QUARTILE = 'Q';

    const ERROR_CORRECTION_HIGH = 'H';

    /**
     * QR code sizes (in pixels)
     */
    const SIZE_SMALL = 150;

    const SIZE_MEDIUM = 250;

    const SIZE_LARGE = 400;

    /**
     * Default QR code settings
     */
    const DEFAULT_SIZE = self::SIZE_MEDIUM;

    const DEFAULT_ERROR_CORRECTION = self::ERROR_CORRECTION_MEDIUM;

    const DEFAULT_MARGIN = 2;

    /**
     * Generate payment QR code
     *
     * Creates a QR code for payment information following Macedonian payment standards.
     * Supports CASYS and standard bank transfer formats.
     *
     * @param  array  $paymentData  Payment information array
     *                              - amount: string Payment amount
     *                              - currency: string Currency code (default: MKD)
     *                              - recipient: string Recipient name
     *                              - iban: string|null Recipient IBAN
     *                              - reference: string|null Payment reference
     *                              - purpose: string|null Payment purpose/description
     *                              - model: string|null Payment model (for Macedonian payments)
     * @param  string  $format  Output format: svg, png, eps (default: svg)
     * @param  int|null  $size  QR code size in pixels (default: 250)
     * @param  string|null  $errorCorrection  Error correction level (default: M)
     * @return string The generated QR code
     *
     * @throws Exception If QR code generation fails
     *
     * @example
     * $qr = $service->generatePaymentQr([
     *     'amount' => '1500.00',
     *     'currency' => 'MKD',
     *     'recipient' => 'Invoice Company',
     *     'iban' => 'MK07200002785123453',
     *     'reference' => 'INV-2025-001',
     *     'purpose' => 'Payment for invoice INV-2025-001'
     * ]);
     */
    public function generatePaymentQr(
        array $paymentData,
        string $format = 'svg',
        ?int $size = null,
        ?string $errorCorrection = null
    ): string {
        try {
            // Validate payment data
            $this->validatePaymentData($paymentData);

            // Build payment string (format depends on standard)
            $paymentString = $this->buildPaymentString($paymentData);

            // Generate QR code
            $qrCode = $this->generate(
                $paymentString,
                $format,
                $size ?? self::DEFAULT_SIZE,
                $errorCorrection ?? self::ERROR_CORRECTION_HIGH
            );

            Log::info('Payment QR code generated', [
                'amount' => $paymentData['amount'] ?? null,
                'currency' => $paymentData['currency'] ?? 'MKD',
                'recipient' => $paymentData['recipient'] ?? null,
                'format' => $format,
            ]);

            return $qrCode;

        } catch (Exception $e) {
            Log::error('Payment QR code generation failed', [
                'error' => $e->getMessage(),
                'payment_data' => $paymentData,
            ]);

            throw new Exception('Failed to generate payment QR code: '.$e->getMessage());
        }
    }

    /**
     * Generate item/product QR code
     *
     * Creates a QR code for item/product information.
     * Can be used for inventory management, product tracking, etc.
     *
     * @param  array  $itemData  Item information array
     *                           - sku: string Product SKU/code
     *                           - name: string Product name
     *                           - price: string|null Product price
     *                           - url: string|null Product URL
     *                           - description: string|null Product description
     * @param  string  $format  Output format: svg, png, eps (default: svg)
     * @param  int|null  $size  QR code size in pixels (default: 250)
     * @param  string|null  $errorCorrection  Error correction level (default: M)
     * @return string The generated QR code
     *
     * @throws Exception If QR code generation fails
     *
     * @example
     * $qr = $service->generateItemQr([
     *     'sku' => 'PROD-12345',
     *     'name' => 'Premium Widget',
     *     'price' => '299.99',
     *     'url' => 'https://example.com/products/12345'
     * ]);
     */
    public function generateItemQr(
        array $itemData,
        string $format = 'svg',
        ?int $size = null,
        ?string $errorCorrection = null
    ): string {
        try {
            // Validate item data
            $this->validateItemData($itemData);

            // Build item string
            $itemString = $this->buildItemString($itemData);

            // Generate QR code
            $qrCode = $this->generate(
                $itemString,
                $format,
                $size ?? self::DEFAULT_SIZE,
                $errorCorrection ?? self::DEFAULT_ERROR_CORRECTION
            );

            Log::info('Item QR code generated', [
                'sku' => $itemData['sku'] ?? null,
                'name' => $itemData['name'] ?? null,
                'format' => $format,
            ]);

            return $qrCode;

        } catch (Exception $e) {
            Log::error('Item QR code generation failed', [
                'error' => $e->getMessage(),
                'item_data' => $itemData,
            ]);

            throw new Exception('Failed to generate item QR code: '.$e->getMessage());
        }
    }

    /**
     * Generate invoice QR code
     *
     * Creates a QR code for invoice information.
     * Can link to invoice URL or contain invoice summary data.
     *
     * @param  array  $invoiceData  Invoice information array
     *                              - invoice_number: string Invoice number
     *                              - amount: string Invoice total amount
     *                              - currency: string Currency code
     *                              - date: string Invoice date
     *                              - url: string|null Invoice view URL
     * @param  string  $format  Output format: svg, png, eps (default: svg)
     * @param  int|null  $size  QR code size in pixels (default: 250)
     * @return string The generated QR code
     *
     * @example
     * $qr = $service->generateInvoiceQr([
     *     'invoice_number' => 'INV-2025-001',
     *     'amount' => '1500.00',
     *     'currency' => 'MKD',
     *     'date' => '2025-01-15',
     *     'url' => 'https://example.com/invoices/view/123'
     * ]);
     */
    public function generateInvoiceQr(
        array $invoiceData,
        string $format = 'svg',
        ?int $size = null
    ): string {
        try {
            // If URL is provided, use it directly for easy access
            if (! empty($invoiceData['url'])) {
                $data = $invoiceData['url'];
            } else {
                // Build structured invoice data
                $data = $this->buildInvoiceString($invoiceData);
            }

            $qrCode = $this->generate(
                $data,
                $format,
                $size ?? self::DEFAULT_SIZE,
                self::ERROR_CORRECTION_MEDIUM
            );

            Log::info('Invoice QR code generated', [
                'invoice_number' => $invoiceData['invoice_number'] ?? null,
                'format' => $format,
            ]);

            return $qrCode;

        } catch (Exception $e) {
            Log::error('Invoice QR code generation failed', [
                'error' => $e->getMessage(),
                'invoice_data' => $invoiceData,
            ]);

            throw new Exception('Failed to generate invoice QR code: '.$e->getMessage());
        }
    }

    /**
     * Generate generic QR code
     *
     * Creates a QR code from any string data.
     *
     * @param  string  $data  The data to encode
     * @param  string  $format  Output format: svg, png, eps (default: svg)
     * @param  int  $size  QR code size in pixels (default: 250)
     * @param  string  $errorCorrection  Error correction level (default: M)
     * @param  int|null  $margin  Margin size (default: 2)
     * @return string The generated QR code
     *
     * @throws Exception If QR code generation fails
     *
     * @example
     * $qr = $service->generate('https://example.com', 'svg', 250, 'M');
     */
    public function generate(
        string $data,
        string $format = 'svg',
        int $size = self::DEFAULT_SIZE,
        string $errorCorrection = self::DEFAULT_ERROR_CORRECTION,
        ?int $margin = null
    ): string {
        try {
            // Validate inputs
            $this->validateFormat($format);
            $this->validateErrorCorrection($errorCorrection);

            if (empty($data)) {
                throw new Exception('QR code data cannot be empty');
            }

            $margin = $margin ?? self::DEFAULT_MARGIN;

            // Map error correction levels to bacon-qr-code v3
            $ecLevel = match ($errorCorrection) {
                'L' => \BaconQrCode\Common\ErrorCorrectionLevel::L(),
                'M' => \BaconQrCode\Common\ErrorCorrectionLevel::M(),
                'Q' => \BaconQrCode\Common\ErrorCorrectionLevel::Q(),
                'H' => \BaconQrCode\Common\ErrorCorrectionLevel::H(),
                default => \BaconQrCode\Common\ErrorCorrectionLevel::M(),
            };

            // Generate QR code using bacon-qr-code v3
            if ($format === 'svg') {
                $renderer = new ImageRenderer(
                    new RendererStyle($size, $margin, null, null, $ecLevel),
                    new SvgImageBackEnd
                );
            } else { // png (ImagickImageBackEnd requires Imagick extension)
                $renderer = new ImageRenderer(
                    new RendererStyle($size, $margin, null, null, $ecLevel),
                    new ImagickImageBackEnd
                );
            }

            $writer = new Writer($renderer);
            $qr = $writer->writeString($data);

            Log::debug('QR code generated', [
                'format' => $format,
                'size' => $size,
                'error_correction' => $errorCorrection,
                'data_length' => strlen($data),
            ]);

            return $qr;

        } catch (Exception $e) {
            Log::error('QR code generation failed', [
                'error' => $e->getMessage(),
                'format' => $format,
                'size' => $size,
            ]);

            throw new Exception('Failed to generate QR code: '.$e->getMessage());
        }
    }

    /**
     * Get QR code as data URI
     *
     * Returns the QR code as a complete data URI that can be used directly in HTML img src.
     *
     * @param  string  $data  The data to encode
     * @param  string  $format  Output format: svg, png (default: svg)
     * @param  int|null  $size  QR code size in pixels
     * @return string Data URI (e.g., "data:image/svg+xml;base64,...")
     *
     * @example
     * $dataUri = $service->getDataUri('https://example.com', 'svg');
     * echo '<img src="' . $dataUri . '" alt="QR Code" />';
     */
    public function getDataUri(
        string $data,
        string $format = 'svg',
        ?int $size = null
    ): string {
        $qrCode = $this->generate($data, $format, $size ?? self::DEFAULT_SIZE);

        if ($format === 'svg') {
            return 'data:image/svg+xml;base64,'.base64_encode($qrCode);
        } else {
            // For PNG, the QR code is already binary data
            return 'data:image/png;base64,'.base64_encode($qrCode);
        }
    }

    /**
     * Validate payment data
     *
     * @param  array  $paymentData  Payment data to validate
     *
     * @throws Exception If required fields are missing
     */
    protected function validatePaymentData(array $paymentData): void
    {
        $requiredFields = ['amount', 'recipient'];

        foreach ($requiredFields as $field) {
            if (empty($paymentData[$field])) {
                throw new Exception("Payment data missing required field: $field");
            }
        }

        // Validate amount is numeric
        if (! is_numeric($paymentData['amount'])) {
            throw new Exception('Payment amount must be numeric');
        }
    }

    /**
     * Validate item data
     *
     * @param  array  $itemData  Item data to validate
     *
     * @throws Exception If required fields are missing
     */
    protected function validateItemData(array $itemData): void
    {
        $requiredFields = ['sku', 'name'];

        foreach ($requiredFields as $field) {
            if (empty($itemData[$field])) {
                throw new Exception("Item data missing required field: $field");
            }
        }
    }

    /**
     * Validate output format
     *
     * @param  string  $format  Format to validate
     *
     * @throws Exception If format is not supported
     */
    protected function validateFormat(string $format): void
    {
        $validFormats = ['svg', 'png', 'eps'];

        if (! in_array($format, $validFormats)) {
            throw new Exception(
                "Invalid QR code format '$format'. Supported formats: ".implode(', ', $validFormats)
            );
        }
    }

    /**
     * Validate error correction level
     *
     * @param  string  $errorCorrection  Error correction level to validate
     *
     * @throws Exception If error correction level is not valid
     */
    protected function validateErrorCorrection(string $errorCorrection): void
    {
        $validLevels = [
            self::ERROR_CORRECTION_LOW,
            self::ERROR_CORRECTION_MEDIUM,
            self::ERROR_CORRECTION_QUARTILE,
            self::ERROR_CORRECTION_HIGH,
        ];

        if (! in_array($errorCorrection, $validLevels)) {
            throw new Exception(
                "Invalid error correction level '$errorCorrection'. Valid levels: ".
                implode(', ', $validLevels)
            );
        }
    }

    /**
     * Build payment string for QR code
     *
     * Formats payment data according to Macedonian payment standards.
     *
     * @param  array  $paymentData  Payment data
     * @return string Formatted payment string
     */
    protected function buildPaymentString(array $paymentData): string
    {
        // For Macedonian payments, we can use a structured format
        // This could be adapted to match specific payment gateway requirements

        $lines = [];

        // Add recipient
        $lines[] = 'RECIPIENT: '.$paymentData['recipient'];

        // Add IBAN if provided
        if (! empty($paymentData['iban'])) {
            $lines[] = 'IBAN: '.$paymentData['iban'];
        }

        // Add amount with currency
        $currency = $paymentData['currency'] ?? 'MKD';
        $lines[] = 'AMOUNT: '.$paymentData['amount'].' '.$currency;

        // Add reference if provided
        if (! empty($paymentData['reference'])) {
            $lines[] = 'REFERENCE: '.$paymentData['reference'];
        }

        // Add purpose/description if provided
        if (! empty($paymentData['purpose'])) {
            $lines[] = 'PURPOSE: '.$paymentData['purpose'];
        }

        // Add payment model if provided (Macedonian specific)
        if (! empty($paymentData['model'])) {
            $lines[] = 'MODEL: '.$paymentData['model'];
        }

        return implode("\n", $lines);
    }

    /**
     * Build item string for QR code
     *
     * @param  array  $itemData  Item data
     * @return string Formatted item string
     */
    protected function buildItemString(array $itemData): string
    {
        // If URL is provided, use it for direct linking
        if (! empty($itemData['url'])) {
            return $itemData['url'];
        }

        // Otherwise, create structured data
        $lines = [];

        $lines[] = 'SKU: '.$itemData['sku'];
        $lines[] = 'NAME: '.$itemData['name'];

        if (! empty($itemData['price'])) {
            $lines[] = 'PRICE: '.$itemData['price'];
        }

        if (! empty($itemData['description'])) {
            $lines[] = 'DESC: '.$itemData['description'];
        }

        return implode("\n", $lines);
    }

    /**
     * Build invoice string for QR code
     *
     * @param  array  $invoiceData  Invoice data
     * @return string Formatted invoice string
     */
    protected function buildInvoiceString(array $invoiceData): string
    {
        $lines = [];

        $lines[] = 'INVOICE: '.($invoiceData['invoice_number'] ?? 'N/A');

        if (! empty($invoiceData['amount']) && ! empty($invoiceData['currency'])) {
            $lines[] = 'AMOUNT: '.$invoiceData['amount'].' '.$invoiceData['currency'];
        }

        if (! empty($invoiceData['date'])) {
            $lines[] = 'DATE: '.$invoiceData['date'];
        }

        return implode("\n", $lines);
    }

    /**
     * Get list of supported error correction levels
     *
     * @return array List of error correction levels with descriptions
     */
    public function getErrorCorrectionLevels(): array
    {
        return [
            self::ERROR_CORRECTION_LOW => [
                'level' => 'L',
                'recovery' => '7%',
                'description' => 'Low - suitable for clean environments',
            ],
            self::ERROR_CORRECTION_MEDIUM => [
                'level' => 'M',
                'recovery' => '15%',
                'description' => 'Medium - balanced option (default)',
            ],
            self::ERROR_CORRECTION_QUARTILE => [
                'level' => 'Q',
                'recovery' => '25%',
                'description' => 'Quartile - good for moderate damage tolerance',
            ],
            self::ERROR_CORRECTION_HIGH => [
                'level' => 'H',
                'recovery' => '30%',
                'description' => 'High - best for harsh environments (recommended for payments)',
            ],
        ];
    }

    /**
     * Get recommended QR code sizes
     *
     * @return array List of recommended sizes with use cases
     */
    public function getRecommendedSizes(): array
    {
        return [
            self::SIZE_SMALL => [
                'size' => 150,
                'use_case' => 'Email signatures, small printed materials',
            ],
            self::SIZE_MEDIUM => [
                'size' => 250,
                'use_case' => 'Invoices, receipts, standard documents (default)',
            ],
            self::SIZE_LARGE => [
                'size' => 400,
                'use_case' => 'Posters, banners, large displays',
            ],
        ];
    }
}

// CLAUDE-CHECKPOINT
