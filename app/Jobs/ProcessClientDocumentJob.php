<?php

namespace App\Jobs;

use App\Models\ClientDocument;
use App\Models\Company;
use App\Notifications\DocumentProcessedNotification;
use App\Services\InvoiceParsing\Invoice2DataServiceException;
use App\Services\InvoiceParsing\InvoiceParserClient;
use App\Services\InvoiceParsing\ParsedInvoiceMapper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessClientDocumentJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $documentId;

    /**
     * Number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Seconds to wait before retrying after a failure.
     *
     * @var array<int,int>
     */
    public array $backoff = [30, 60, 300];

    public function __construct(int $documentId)
    {
        $this->documentId = $documentId;
    }

    public function handle(InvoiceParserClient $client, ParsedInvoiceMapper $mapper): void
    {
        $doc = ClientDocument::find($this->documentId);

        if (! $doc) {
            Log::warning('ProcessClientDocumentJob: document not found', [
                'document_id' => $this->documentId,
            ]);

            return;
        }

        // Skip if already processed or confirmed
        if (in_array($doc->processing_status, [
            ClientDocument::PROCESSING_EXTRACTED,
            ClientDocument::PROCESSING_CONFIRMED,
        ])) {
            Log::info('ProcessClientDocumentJob: document already processed, skipping', [
                'document_id' => $doc->id,
                'status' => $doc->processing_status,
            ]);

            return;
        }

        Log::info('ProcessClientDocumentJob: starting', [
            'document_id' => $doc->id,
            'company_id' => $doc->company_id,
            'file' => $doc->original_filename,
            'attempt' => $this->attempts(),
        ]);

        try {
            $this->classifyDocument($doc, $client);
            $this->extractData($doc, $client, $mapper);
            $this->notifyOwner($doc);
        } catch (Invoice2DataServiceException $e) {
            $hasRetries = $this->attempts() < $this->tries;

            Log::warning('ProcessClientDocumentJob: service unavailable', [
                'document_id' => $doc->id,
                'attempt' => $this->attempts(),
                'will_retry' => $hasRetries,
                'error' => $e->getMessage(),
            ]);

            $doc->update([
                'processing_status' => $hasRetries
                    ? ClientDocument::PROCESSING_RETRYING
                    : ClientDocument::PROCESSING_FAILED,
                'error_message' => $e->getMessage(),
            ]);

            if ($hasRetries) {
                $this->release($this->backoff[$this->attempts() - 1] ?? 300);
            } else {
                $this->notifyOwner($doc);
            }

            return;
        } catch (RequestException $e) {
            $status = $e->response?->status();

            if ($status && $status >= 400 && $status < 500) {
                // Client error — don't retry
                Log::warning('ProcessClientDocumentJob: client error, marking failed', [
                    'document_id' => $doc->id,
                    'status' => $status,
                    'error' => $e->getMessage(),
                ]);

                $doc->update([
                    'processing_status' => ClientDocument::PROCESSING_FAILED,
                    'error_message' => "AI processing failed (HTTP {$status})",
                ]);

                $this->notifyOwner($doc);

                return;
            }

            // Server error — retry if attempts remain
            $hasRetries = $this->attempts() < $this->tries;

            $doc->update([
                'processing_status' => $hasRetries
                    ? ClientDocument::PROCESSING_RETRYING
                    : ClientDocument::PROCESSING_FAILED,
                'error_message' => $e->getMessage(),
            ]);

            if ($hasRetries) {
                $this->release($this->backoff[$this->attempts() - 1] ?? 300);
            } else {
                $this->notifyOwner($doc);
            }

            return;
        } catch (\Throwable $e) {
            Log::error('ProcessClientDocumentJob: unexpected error', [
                'document_id' => $doc->id,
                'error' => $e->getMessage(),
                'exception_class' => get_class($e),
            ]);

            $doc->update([
                'processing_status' => ClientDocument::PROCESSING_FAILED,
                'error_message' => 'Unexpected error: '.$e->getMessage(),
            ]);

            $this->notifyOwner($doc);
        }
    }

    /**
     * Step 1: Classify the document type using AI vision.
     */
    protected function classifyDocument(ClientDocument $doc, InvoiceParserClient $client): void
    {
        $doc->update(['processing_status' => ClientDocument::PROCESSING_CLASSIFYING]);

        $classification = $client->classify(
            $doc->company_id,
            $doc->file_path,
            $doc->original_filename
        );

        $doc->update([
            'ai_classification' => $classification,
            'category' => $classification['type'],
        ]);

        Log::info('ProcessClientDocumentJob: classified', [
            'document_id' => $doc->id,
            'type' => $classification['type'],
            'confidence' => $classification['confidence'],
        ]);
    }

    /**
     * Step 2: Extract structured data based on document type.
     */
    protected function extractData(ClientDocument $doc, InvoiceParserClient $client, ParsedInvoiceMapper $mapper): void
    {
        $doc->update(['processing_status' => ClientDocument::PROCESSING_EXTRACTING]);

        $type = $doc->ai_classification['type'] ?? 'other';

        if (in_array($type, ['invoice', 'receipt'])) {
            $this->extractInvoiceData($doc, $client, $mapper);
        } elseif ($type === 'bank_statement') {
            $this->extractBankStatementData($doc, $client);
        } elseif ($type === 'product_list') {
            $this->extractProductListData($doc, $client);
        } elseif ($type === 'tax_form') {
            $this->extractTaxFormData($doc, $client);
        } else {
            // contract, other — store classification summary only
            $doc->update([
                'extracted_data' => [
                    'summary' => $doc->ai_classification['summary'] ?? '',
                    'type' => $type,
                ],
                'extraction_method' => 'classification_only',
                'processing_status' => ClientDocument::PROCESSING_EXTRACTED,
            ]);
        }
    }

    /**
     * Extract invoice/receipt data using the parsing pipeline.
     */
    protected function extractInvoiceData(ClientDocument $doc, InvoiceParserClient $client, ParsedInvoiceMapper $mapper): void
    {
        $parsed = $client->parse(
            $doc->company_id,
            $doc->file_path,
            $doc->original_filename,
            '',
            null
        );

        $components = $mapper->mapToBillComponents($doc->company_id, $parsed);

        $doc->update([
            'extracted_data' => $components,
            'extraction_method' => 'gemini_vision',
            'processing_status' => ClientDocument::PROCESSING_EXTRACTED,
        ]);

        Log::info('ProcessClientDocumentJob: invoice extracted', [
            'document_id' => $doc->id,
            'supplier' => $components['supplier']['name'] ?? null,
            'total' => $components['bill']['total'] ?? 0,
            'items_count' => count($components['items'] ?? []),
        ]);
    }

    /**
     * Extract bank statement data using the bank statement parser.
     */
    protected function extractBankStatementData(ClientDocument $doc, InvoiceParserClient $client): void
    {
        try {
            $result = $client->parseBankStatement(
                $doc->company_id,
                $doc->file_path,
                $doc->original_filename
            );

            $transactions = $result['transactions'] ?? [];

            // Convert amounts from floats to cents for consistent display
            $transactions = array_map(function ($txn) {
                $txn['debit'] = isset($txn['debit']) ? (int) round(floatval($txn['debit']) * 100) : 0;
                $txn['credit'] = isset($txn['credit']) ? (int) round(floatval($txn['credit']) * 100) : 0;

                return $txn;
            }, $transactions);

            $doc->update([
                'extracted_data' => [
                    'transactions' => $transactions,
                    'bank_name' => $result['bank_name'] ?? '',
                    'bank_code' => $result['bank_code'] ?? '',
                    'account_number' => $result['account_number'] ?? '',
                    'statement_date' => $result['statement_date'] ?? '',
                    'transaction_count' => $result['transaction_count'] ?? count($transactions),
                    'confidence' => $result['confidence'] ?? null,
                    'raw_text' => $result['raw_text'] ?? '',
                    'summary' => $doc->ai_classification['summary'] ?? '',
                    'type' => 'bank_statement',
                ],
                'extraction_method' => $result['extraction_method'] ?? 'gemini_bank_statement',
                'processing_status' => ClientDocument::PROCESSING_EXTRACTED,
            ]);

            Log::info('ProcessClientDocumentJob: bank statement extracted', [
                'document_id' => $doc->id,
                'bank_name' => $result['bank_name'] ?? null,
                'transaction_count' => count($transactions),
            ]);
        } catch (\Throwable $e) {
            // Fallback: try basic OCR
            Log::warning('ProcessClientDocumentJob: bank statement parser failed, trying OCR fallback', [
                'document_id' => $doc->id,
                'error' => $e->getMessage(),
            ]);

            try {
                $ocrResult = $client->ocr(
                    $doc->company_id,
                    $doc->file_path,
                    $doc->original_filename
                );

                $doc->update([
                    'extracted_data' => [
                        'raw_text' => $ocrResult['text'] ?? '',
                        'summary' => $doc->ai_classification['summary'] ?? '',
                        'type' => 'bank_statement',
                    ],
                    'extraction_method' => 'bank_ocr',
                    'processing_status' => ClientDocument::PROCESSING_EXTRACTED,
                ]);
            } catch (\Throwable $e2) {
                $doc->update([
                    'extracted_data' => [
                        'summary' => $doc->ai_classification['summary'] ?? '',
                        'type' => 'bank_statement',
                    ],
                    'extraction_method' => 'classification_only',
                    'processing_status' => ClientDocument::PROCESSING_EXTRACTED,
                ]);
            }
        }
    }

    /**
     * Extract product list data using the product list parser.
     */
    protected function extractProductListData(ClientDocument $doc, InvoiceParserClient $client): void
    {
        try {
            $result = $client->parseProductList(
                $doc->company_id,
                $doc->file_path,
                $doc->original_filename
            );

            $doc->update([
                'extracted_data' => [
                    'products' => $result['products'] ?? [],
                    'currency' => $result['currency'] ?? 'MKD',
                    'source_company' => $result['source_company'] ?? null,
                    'type' => 'product_list',
                ],
                'extraction_method' => 'gemini_vision',
                'processing_status' => ClientDocument::PROCESSING_EXTRACTED,
            ]);

            Log::info('ProcessClientDocumentJob: product list extracted', [
                'document_id' => $doc->id,
                'products_count' => count($result['products'] ?? []),
            ]);
        } catch (\Throwable $e) {
            Log::warning('ProcessClientDocumentJob: product list extraction failed, using summary', [
                'document_id' => $doc->id,
                'error' => $e->getMessage(),
            ]);

            $doc->update([
                'extracted_data' => [
                    'summary' => $doc->ai_classification['summary'] ?? '',
                    'type' => 'product_list',
                ],
                'extraction_method' => 'classification_only',
                'processing_status' => ClientDocument::PROCESSING_EXTRACTED,
            ]);
        }
    }

    /**
     * Extract tax form data using the tax form parser.
     */
    protected function extractTaxFormData(ClientDocument $doc, InvoiceParserClient $client): void
    {
        try {
            $result = $client->parseTaxForm(
                $doc->company_id,
                $doc->file_path,
                $doc->original_filename
            );

            $doc->update([
                'extracted_data' => [
                    'form_type' => $result['form_type'] ?? 'other',
                    'declarant' => $result['declarant'] ?? [],
                    'period' => $result['period'] ?? [],
                    'fields' => $result['fields'] ?? [],
                    'totals' => $result['totals'] ?? [],
                    'type' => 'tax_form',
                ],
                'extraction_method' => 'gemini_vision',
                'processing_status' => ClientDocument::PROCESSING_EXTRACTED,
            ]);

            Log::info('ProcessClientDocumentJob: tax form extracted', [
                'document_id' => $doc->id,
                'form_type' => $result['form_type'] ?? 'unknown',
                'fields_count' => count($result['fields'] ?? []),
            ]);
        } catch (\Throwable $e) {
            Log::warning('ProcessClientDocumentJob: tax form extraction failed, using summary', [
                'document_id' => $doc->id,
                'error' => $e->getMessage(),
            ]);

            $doc->update([
                'extracted_data' => [
                    'summary' => $doc->ai_classification['summary'] ?? '',
                    'type' => 'tax_form',
                ],
                'extraction_method' => 'classification_only',
                'processing_status' => ClientDocument::PROCESSING_EXTRACTED,
            ]);
        }
    }

    /**
     * Notify the company owner about processing completion.
     */
    protected function notifyOwner(ClientDocument $doc): void
    {
        try {
            $company = Company::find($doc->company_id);
            if ($company?->owner) {
                $company->owner->notify(new DocumentProcessedNotification($doc));
            }
        } catch (\Throwable $e) {
            Log::warning('ProcessClientDocumentJob: failed to send notification', [
                'document_id' => $doc->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
    /**
     * Handle a job failure after all retries exhausted.
     */
    public function failed(?\Throwable $exception): void
    {
        $doc = ClientDocument::find($this->documentId);

        if ($doc && ! in_array($doc->processing_status, [
            ClientDocument::PROCESSING_EXTRACTED,
            ClientDocument::PROCESSING_CONFIRMED,
        ])) {
            $doc->update([
                'processing_status' => ClientDocument::PROCESSING_FAILED,
                'error_message' => 'Processing failed after all retries: '.($exception?->getMessage() ?? 'unknown'),
            ]);

            $this->notifyOwner($doc);
        }
    }
} // CLAUDE-CHECKPOINT
