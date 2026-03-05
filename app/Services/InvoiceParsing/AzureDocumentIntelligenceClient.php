<?php

namespace App\Services\InvoiceParsing;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Azure Document Intelligence (formerly Form Recognizer) client.
 *
 * Uses the prebuilt-invoice model for structured invoice parsing and
 * the prebuilt-read model for plain-text OCR extraction.
 *
 * @see https://learn.microsoft.com/en-us/azure/ai-services/document-intelligence/
 */
class AzureDocumentIntelligenceClient implements InvoiceParserClient
{
    /** Maximum number of polling attempts before giving up. */
    private const MAX_POLL_RETRIES = 10;

    /** Maximum delay in seconds between poll attempts. */
    private const MAX_POLL_DELAY_SECONDS = 30;

    /**
     * Parse a PDF invoice using Azure Document Intelligence prebuilt-invoice model.
     *
     * Submits the document for asynchronous analysis, polls for results, then
     * normalizes the Azure response into the internal format expected by
     * {@see ParsedInvoiceMapper::mapToBillComponents()}.
     *
     * @param  int          $companyId    The company owning this invoice
     * @param  string       $filePath     Relative path on the default storage disk
     * @param  string       $originalName Original filename for logging
     * @param  string       $from         Sender email/identifier
     * @param  string|null  $subject      Email subject (unused by Azure but kept for interface compliance)
     * @return array<string,mixed> Normalized invoice data with supplier, invoice, totals, and line_items keys
     *
     * @throws Invoice2DataServiceException When the Azure service is unreachable or returns an error
     */
    public function parse(int $companyId, string $filePath, string $originalName, string $from, ?string $subject): array
    {
        $fileBytes = $this->readFileBytes($filePath);
        $endpoint = $this->getEndpoint();
        $apiVersion = $this->getApiVersion();
        $timeout = $this->getTimeout();

        $analyzeUrl = rtrim($endpoint, '/')
            . '/documentintelligence/documentModels/prebuilt-invoice:analyze'
            . '?api-version=' . $apiVersion;

        try {
            // Step 1: Submit the document for analysis
            $submitResponse = Http::timeout($timeout)
                ->connectTimeout(10)
                ->withHeaders([
                    'Ocp-Apim-Subscription-Key' => $this->getKey(),
                    'Content-Type' => 'application/octet-stream',
                ])
                ->withBody($fileBytes, 'application/octet-stream')
                ->post($analyzeUrl);

            $submitResponse->throw();

            // Step 2: Get the Operation-Location for polling
            $operationLocation = $submitResponse->header('Operation-Location');

            if (! $operationLocation) {
                Log::warning('Azure Document Intelligence: missing Operation-Location header', [
                    'company_id' => $companyId,
                    'file' => $originalName,
                    'status' => $submitResponse->status(),
                ]);

                throw new Invoice2DataServiceException(
                    'Azure Document Intelligence did not return an operation URL.',
                    502
                );
            }

            // Step 3: Poll for results with exponential backoff
            $result = $this->pollForResult($operationLocation, $companyId, $originalName);

            // Step 4: Normalize and return
            return $this->normalizeParseResult($result);
        } catch (ConnectionException $e) {
            Log::warning('Azure Document Intelligence unreachable during parse', [
                'url' => $analyzeUrl,
                'company_id' => $companyId,
                'file' => $originalName,
                'error' => $e->getMessage(),
            ]);

            throw new Invoice2DataServiceException(
                'Invoice parsing service is currently unavailable. Please try again later.',
                503,
                $e
            );
        } catch (RequestException $e) {
            Log::warning('Azure Document Intelligence request failed during parse', [
                'url' => $analyzeUrl,
                'company_id' => $companyId,
                'file' => $originalName,
                'status' => $e->response->status(),
                'body' => $e->response->body(),
                'error' => $e->getMessage(),
            ]);

            throw new Invoice2DataServiceException(
                'Invoice parsing service returned an error. Please try again later.',
                $e->response->status(),
                $e
            );
        }
    }

    /**
     * Extract text from a document using Azure Document Intelligence prebuilt-read model.
     *
     * Returns the full extracted text content suitable for display or further processing.
     *
     * @param  int     $companyId    The company owning this document
     * @param  string  $filePath     Relative path on the default storage disk
     * @param  string  $originalName Original filename for logging
     * @return array<string,mixed> Array with keys: success (bool), text (string), format (string)
     *
     * @throws Invoice2DataServiceException When the Azure service is unreachable or returns an error
     */
    public function ocr(int $companyId, string $filePath, string $originalName): array
    {
        $fileBytes = $this->readFileBytes($filePath);
        $endpoint = $this->getEndpoint();
        $apiVersion = $this->getApiVersion();
        $timeout = $this->getTimeout();

        $analyzeUrl = rtrim($endpoint, '/')
            . '/documentintelligence/documentModels/prebuilt-read:analyze'
            . '?api-version=' . $apiVersion;

        try {
            // Step 1: Submit the document for analysis
            $submitResponse = Http::timeout($timeout)
                ->connectTimeout(10)
                ->withHeaders([
                    'Ocp-Apim-Subscription-Key' => $this->getKey(),
                    'Content-Type' => 'application/octet-stream',
                ])
                ->withBody($fileBytes, 'application/octet-stream')
                ->post($analyzeUrl);

            $submitResponse->throw();

            // Step 2: Get the Operation-Location for polling
            $operationLocation = $submitResponse->header('Operation-Location');

            if (! $operationLocation) {
                Log::warning('Azure Document Intelligence: missing Operation-Location header for OCR', [
                    'company_id' => $companyId,
                    'file' => $originalName,
                    'status' => $submitResponse->status(),
                ]);

                throw new Invoice2DataServiceException(
                    'Azure Document Intelligence did not return an operation URL.',
                    502
                );
            }

            // Step 3: Poll for results
            $result = $this->pollForResult($operationLocation, $companyId, $originalName);

            // Step 4: Extract text content from all pages
            $text = $this->extractTextFromReadResult($result);

            return [
                'success' => true,
                'text' => $text,
                'format' => 'text',
            ];
        } catch (ConnectionException $e) {
            Log::warning('Azure Document Intelligence unreachable during OCR', [
                'url' => $analyzeUrl,
                'company_id' => $companyId,
                'file' => $originalName,
                'error' => $e->getMessage(),
            ]);

            throw new Invoice2DataServiceException(
                'Invoice OCR service is currently unavailable. Please try again later.',
                503,
                $e
            );
        } catch (RequestException $e) {
            Log::warning('Azure Document Intelligence request failed during OCR', [
                'url' => $analyzeUrl,
                'company_id' => $companyId,
                'file' => $originalName,
                'status' => $e->response->status(),
                'body' => $e->response->body(),
                'error' => $e->getMessage(),
            ]);

            throw new Invoice2DataServiceException(
                'Invoice OCR service returned an error. Please try again later.',
                $e->response->status(),
                $e
            );
        }
    }

    /**
     * Poll the Azure operation URL until the analysis completes or fails.
     *
     * Uses exponential backoff starting at 1 second, doubling each attempt up to
     * {@see MAX_POLL_DELAY_SECONDS}, for a maximum of {@see MAX_POLL_RETRIES} attempts.
     *
     * @param  string  $operationUrl The Operation-Location URL returned by Azure
     * @param  int     $companyId    Company ID for log context
     * @param  string  $originalName Filename for log context
     * @return array<string,mixed> The analyzeResult portion of the Azure response
     *
     * @throws Invoice2DataServiceException When polling times out or the operation fails
     * @throws ConnectionException          When the polling request cannot connect
     * @throws RequestException             When the polling request returns an HTTP error
     */
    private function pollForResult(string $operationUrl, int $companyId, string $originalName): array
    {
        $delay = 1; // Start at 1 second
        $timeout = $this->getTimeout();

        for ($attempt = 1; $attempt <= self::MAX_POLL_RETRIES; $attempt++) {
            sleep($delay);

            $pollResponse = Http::timeout($timeout)
                ->connectTimeout(10)
                ->withHeaders([
                    'Ocp-Apim-Subscription-Key' => $this->getKey(),
                ])
                ->get($operationUrl);

            $pollResponse->throw();

            $body = $pollResponse->json();
            $status = $body['status'] ?? 'unknown';

            if ($status === 'succeeded') {
                Log::info('Azure Document Intelligence analysis succeeded', [
                    'company_id' => $companyId,
                    'file' => $originalName,
                    'attempts' => $attempt,
                ]);

                return $body['analyzeResult'] ?? [];
            }

            if ($status === 'failed') {
                $error = $body['error'] ?? [];
                Log::warning('Azure Document Intelligence analysis failed', [
                    'company_id' => $companyId,
                    'file' => $originalName,
                    'error_code' => $error['code'] ?? 'unknown',
                    'error_message' => $error['message'] ?? 'No error message provided',
                ]);

                throw new Invoice2DataServiceException(
                    'Document analysis failed: ' . ($error['message'] ?? 'Unknown error'),
                    422
                );
            }

            // Status is "running" or "notStarted" — wait and retry
            $delay = min($delay * 2, self::MAX_POLL_DELAY_SECONDS);
        }

        Log::warning('Azure Document Intelligence analysis timed out after polling', [
            'company_id' => $companyId,
            'file' => $originalName,
            'max_retries' => self::MAX_POLL_RETRIES,
        ]);

        throw new Invoice2DataServiceException(
            'Document analysis timed out after ' . self::MAX_POLL_RETRIES . ' polling attempts.',
            504
        );
    }

    /**
     * Normalize Azure prebuilt-invoice result into the internal format.
     *
     * Maps Azure Document Intelligence field names to the structure expected by
     * {@see ParsedInvoiceMapper::mapToBillComponents()}:
     *
     * - VendorName       -> supplier.name
     * - VendorTaxId      -> supplier.tax_id
     * - VendorAddress    -> supplier.address
     * - InvoiceId        -> invoice.number
     * - InvoiceDate      -> invoice.date
     * - DueDate          -> invoice.due_date
     * - InvoiceTotal     -> totals.total (converted to cents)
     * - SubTotal         -> totals.subtotal (converted to cents)
     * - TotalTax         -> totals.tax (converted to cents)
     * - Items[]          -> line_items[] with description, quantity, unit_price, total
     * - CurrencyCode     -> invoice.currency
     *
     * @param  array<string,mixed>  $analyzeResult The Azure analyzeResult object
     * @return array<string,mixed> Normalized data matching internal invoice format
     */
    private function normalizeParseResult(array $analyzeResult): array
    {
        // Azure returns documents as an array; take the first invoice found
        $documents = $analyzeResult['documents'] ?? [];
        $fields = ! empty($documents) ? ($documents[0]['fields'] ?? []) : [];

        $supplier = [
            'name' => $this->getFieldValue($fields, 'VendorName'),
            'tax_id' => $this->getFieldValue($fields, 'VendorTaxId'),
            'address' => $this->getFieldContent($fields, 'VendorAddress'),
            'email' => null,
        ];

        $currencyCode = $this->getFieldValue($fields, 'CurrencyCode');
        $invoiceTotal = $this->getFieldCurrencyAmount($fields, 'InvoiceTotal');
        $subTotal = $this->getFieldCurrencyAmount($fields, 'SubTotal');
        $totalTax = $this->getFieldCurrencyAmount($fields, 'TotalTax');
        $amountDue = $this->getFieldCurrencyAmount($fields, 'AmountDue');

        // Convert to cents (integer) — the mapper expects amounts in cents
        $totalCents = $this->toCents($invoiceTotal ?? $amountDue);
        $subTotalCents = $this->toCents($subTotal);
        $taxCents = $this->toCents($totalTax);

        // If subtotal is missing, derive it from total - tax
        if ($subTotalCents === 0 && $totalCents > 0) {
            $subTotalCents = $totalCents - $taxCents;
        }

        $invoice = [
            'number' => $this->getFieldValue($fields, 'InvoiceId'),
            'date' => $this->getFieldDate($fields, 'InvoiceDate'),
            'due_date' => $this->getFieldDate($fields, 'DueDate'),
            'currency' => $currencyCode,
        ];

        $totals = [
            'total' => $totalCents,
            'subtotal' => $subTotalCents,
            'tax' => $taxCents,
        ];

        $lineItems = $this->normalizeLineItems($fields);

        return [
            'supplier' => $supplier,
            'invoice' => $invoice,
            'totals' => $totals,
            'line_items' => $lineItems,
        ];
    }

    /**
     * Normalize Azure invoice line items into the internal format.
     *
     * Each Azure line item may contain: Description, Quantity, UnitPrice, Amount,
     * ProductCode, Unit, Tax, and Date fields.
     *
     * @param  array<string,mixed>  $fields The top-level invoice fields from Azure
     * @return array<int,array<string,mixed>> Normalized line items
     */
    private function normalizeLineItems(array $fields): array
    {
        $items = $fields['Items'] ?? null;

        if (! $items || ! isset($items['valueArray'])) {
            return [];
        }

        $lineItems = [];

        foreach ($items['valueArray'] as $item) {
            $itemFields = $item['valueObject'] ?? [];

            $description = $this->getFieldValue($itemFields, 'Description')
                ?? $this->getFieldValue($itemFields, 'ProductCode')
                ?? 'Item';

            $quantity = $this->getFieldNumberValue($itemFields, 'Quantity') ?? 1.0;
            $unitPrice = $this->getFieldCurrencyAmount($itemFields, 'UnitPrice');
            $amount = $this->getFieldCurrencyAmount($itemFields, 'Amount');
            $tax = $this->getFieldCurrencyAmount($itemFields, 'Tax');

            $unitPriceCents = $this->toCents($unitPrice);
            $totalCents = $this->toCents($amount);
            $taxCents = $this->toCents($tax);

            // If unit price is missing but total and quantity are available, derive it
            if ($unitPriceCents === 0 && $totalCents > 0 && $quantity > 0) {
                $unitPriceCents = (int) round($totalCents / $quantity);
            }

            // If total is missing but unit price and quantity are available, derive it
            if ($totalCents === 0 && $unitPriceCents > 0) {
                $totalCents = (int) round($unitPriceCents * $quantity);
            }

            $lineItems[] = [
                'description' => $description,
                'name' => $description,
                'quantity' => $quantity,
                'unit_price' => $unitPriceCents,
                'total' => $totalCents,
                'tax' => $taxCents,
            ];
        }

        return $lineItems;
    }

    /**
     * Extract plain text from a prebuilt-read analysis result.
     *
     * Concatenates the content from all pages into a single string.
     *
     * @param  array<string,mixed>  $analyzeResult The Azure analyzeResult object
     * @return string The extracted text content
     */
    private function extractTextFromReadResult(array $analyzeResult): string
    {
        // The top-level 'content' field contains all extracted text
        if (! empty($analyzeResult['content'])) {
            return $analyzeResult['content'];
        }

        // Fallback: concatenate text from individual pages
        $pages = $analyzeResult['pages'] ?? [];
        $textParts = [];

        foreach ($pages as $page) {
            $lines = $page['lines'] ?? [];
            foreach ($lines as $line) {
                $textParts[] = $line['content'] ?? '';
            }
        }

        return implode("\n", $textParts);
    }

    /**
     * Read raw file bytes from the default storage disk.
     *
     * @param  string  $filePath Relative path on the storage disk
     * @return string Raw file content
     *
     * @throws Invoice2DataServiceException If the file cannot be read
     */
    private function readFileBytes(string $filePath): string
    {
        $disk = config('filesystems.default', 'local');
        $absolutePath = Storage::disk($disk)->path($filePath);

        $bytes = file_get_contents($absolutePath);

        if ($bytes === false) {
            throw new Invoice2DataServiceException(
                "Unable to read file for document analysis: {$filePath}",
                400
            );
        }

        return $bytes;
    }

    /**
     * Get a simple string value from an Azure field.
     *
     * @param  array<string,mixed>  $fields    Azure fields object
     * @param  string               $fieldName The Azure field name
     * @return string|null The field value or null if not present
     */
    private function getFieldValue(array $fields, string $fieldName): ?string
    {
        $field = $fields[$fieldName] ?? null;

        if (! $field) {
            return null;
        }

        // Prefer valueString, fall back to content
        return $field['valueString'] ?? $field['content'] ?? null;
    }

    /**
     * Get the content representation of an Azure field (useful for addresses).
     *
     * @param  array<string,mixed>  $fields    Azure fields object
     * @param  string               $fieldName The Azure field name
     * @return string|null The field content or null if not present
     */
    private function getFieldContent(array $fields, string $fieldName): ?string
    {
        $field = $fields[$fieldName] ?? null;

        if (! $field) {
            return null;
        }

        // For address fields, prefer content which has the full formatted address
        // Fall back to valueAddress if available
        if (isset($field['content'])) {
            return $field['content'];
        }

        if (isset($field['valueAddress'])) {
            $addr = $field['valueAddress'];
            $parts = array_filter([
                $addr['streetAddress'] ?? null,
                $addr['city'] ?? null,
                $addr['state'] ?? null,
                $addr['postalCode'] ?? null,
                $addr['countryRegion'] ?? null,
            ]);

            return implode(', ', $parts) ?: null;
        }

        return $field['valueString'] ?? null;
    }

    /**
     * Get a date value from an Azure field in Y-m-d format.
     *
     * @param  array<string,mixed>  $fields    Azure fields object
     * @param  string               $fieldName The Azure field name
     * @return string|null Date string in Y-m-d format or null
     */
    private function getFieldDate(array $fields, string $fieldName): ?string
    {
        $field = $fields[$fieldName] ?? null;

        if (! $field) {
            return null;
        }

        // Azure returns dates as valueDate in YYYY-MM-DD format
        return $field['valueDate'] ?? $field['content'] ?? null;
    }

    /**
     * Get a currency amount from an Azure field.
     *
     * Azure currency fields have a valueCurrency object with amount and currencyCode.
     * Falls back to valueNumber for plain numeric fields.
     *
     * @param  array<string,mixed>  $fields    Azure fields object
     * @param  string               $fieldName The Azure field name
     * @return float|null The amount as a float (in the original currency unit, e.g. 12.50) or null
     */
    private function getFieldCurrencyAmount(array $fields, string $fieldName): ?float
    {
        $field = $fields[$fieldName] ?? null;

        if (! $field) {
            return null;
        }

        // Prefer valueCurrency.amount for explicit currency fields
        if (isset($field['valueCurrency']['amount'])) {
            return (float) $field['valueCurrency']['amount'];
        }

        // Fall back to valueNumber for plain numeric fields
        if (isset($field['valueNumber'])) {
            return (float) $field['valueNumber'];
        }

        // Last resort: try to parse the content string
        if (isset($field['content'])) {
            $cleaned = preg_replace('/[^\d.,\-]/', '', $field['content']);

            if ($cleaned !== '' && $cleaned !== null) {
                // Handle European format (1.234,56) vs US format (1,234.56)
                if (preg_match('/\d+\.\d{3},\d{2}$/', $cleaned)) {
                    $cleaned = str_replace('.', '', $cleaned);
                    $cleaned = str_replace(',', '.', $cleaned);
                } else {
                    $cleaned = str_replace(',', '', $cleaned);
                }

                return (float) $cleaned;
            }
        }

        return null;
    }

    /**
     * Get a plain number value from an Azure field.
     *
     * @param  array<string,mixed>  $fields    Azure fields object
     * @param  string               $fieldName The Azure field name
     * @return float|null The numeric value or null
     */
    private function getFieldNumberValue(array $fields, string $fieldName): ?float
    {
        $field = $fields[$fieldName] ?? null;

        if (! $field) {
            return null;
        }

        if (isset($field['valueNumber'])) {
            return (float) $field['valueNumber'];
        }

        if (isset($field['content']) && is_numeric($field['content'])) {
            return (float) $field['content'];
        }

        return null;
    }

    /**
     * Convert a float amount (e.g. 12.50) to integer cents (e.g. 1250).
     *
     * @param  float|null  $amount The amount in major currency units
     * @return int The amount in cents (minor currency units)
     */
    private function toCents(?float $amount): int
    {
        if ($amount === null) {
            return 0;
        }

        return (int) round($amount * 100);
    }

    /**
     * Get the Azure Document Intelligence endpoint from config.
     *
     * @return string The configured endpoint URL
     *
     * @throws Invoice2DataServiceException If endpoint is not configured
     */
    private function getEndpoint(): string
    {
        $endpoint = config('services.azure_document_intelligence.endpoint');

        if (! $endpoint) {
            throw new Invoice2DataServiceException(
                'Azure Document Intelligence endpoint is not configured. '
                . 'Set AZURE_DOCUMENT_INTELLIGENCE_ENDPOINT in your .env file.',
                500
            );
        }

        return $endpoint;
    }

    /**
     * Get the Azure Document Intelligence API key from config.
     *
     * @return string The configured API key
     *
     * @throws Invoice2DataServiceException If key is not configured
     */
    private function getKey(): string
    {
        $key = config('services.azure_document_intelligence.key');

        if (! $key) {
            throw new Invoice2DataServiceException(
                'Azure Document Intelligence API key is not configured. '
                . 'Set AZURE_DOCUMENT_INTELLIGENCE_KEY in your .env file.',
                500
            );
        }

        return $key;
    }

    /**
     * Get the Azure Document Intelligence API version from config.
     *
     * @return string The API version string (e.g. '2024-11-30')
     */
    private function getApiVersion(): string
    {
        return config('services.azure_document_intelligence.api_version', '2024-11-30');
    }

    /**
     * Get the request timeout in seconds from config.
     *
     * @return int Timeout in seconds
     */
    private function getTimeout(): int
    {
        return (int) config('services.azure_document_intelligence.timeout', 120);
    }
    public function parseReceipt(int $companyId, string $filePath, string $originalName): array
    {
        return $this->parse($companyId, $filePath, $originalName, '', null);
    }
} // CLAUDE-CHECKPOINT
