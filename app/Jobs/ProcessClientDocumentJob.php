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
    public int $tries = 2;

    /**
     * Seconds to wait before retrying after a failure.
     *
     * @var array<int,int>
     */
    public array $backoff = [60, 300];

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
            Log::warning('ProcessClientDocumentJob: service unavailable, will retry', [
                'document_id' => $doc->id,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
            ]);

            $doc->update([
                'processing_status' => ClientDocument::PROCESSING_FAILED,
                'error_message' => $e->getMessage(),
            ]);

            $this->release($this->backoff[$this->attempts() - 1] ?? 300);

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

            // Server error — retry
            $doc->update([
                'processing_status' => ClientDocument::PROCESSING_FAILED,
                'error_message' => $e->getMessage(),
            ]);

            $this->release($this->backoff[$this->attempts() - 1] ?? 300);

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
        } else {
            // contract, tax_form, other — store classification summary only
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
            $result = $client->ocr(
                $doc->company_id,
                $doc->file_path,
                $doc->original_filename
            );

            $doc->update([
                'extracted_data' => [
                    'raw_text' => $result['text'] ?? '',
                    'summary' => $doc->ai_classification['summary'] ?? '',
                    'type' => 'bank_statement',
                ],
                'extraction_method' => 'bank_ocr',
                'processing_status' => ClientDocument::PROCESSING_EXTRACTED,
            ]);
        } catch (\Throwable $e) {
            // Fallback: store classification summary only
            Log::warning('ProcessClientDocumentJob: bank statement OCR failed, using summary', [
                'document_id' => $doc->id,
                'error' => $e->getMessage(),
            ]);

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
} // CLAUDE-CHECKPOINT
