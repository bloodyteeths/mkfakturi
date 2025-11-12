<?php

namespace App\Jobs;

use App\Models\Certificate;
use App\Models\EInvoice;
use App\Models\EInvoiceSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Modules\Mk\Services\MkUblMapper;
use Modules\Mk\Services\MkXmlSigner;
use Exception;
use Throwable;

/**
 * SubmitEInvoiceJob
 *
 * Queued job for submitting e-invoices to the Macedonian tax authority.
 * Handles UBL XML generation, digital signing, and portal submission with retry logic.
 *
 * Job workflow:
 * 1. Load EInvoice model
 * 2. Check if already submitted (skip if accepted/rejected)
 * 3. Generate UBL XML using MkUblMapper
 * 4. Sign XML using MkXmlSigner with company's active certificate
 * 5. Create EInvoiceSubmission record with idempotency key
 * 6. Call efaktura upload tool to submit to portal
 * 7. Parse response for receipt number
 * 8. Update submission status (accepted/rejected/error)
 * 9. Update e-invoice status
 * 10. Log all steps
 *
 * Retry logic:
 * - Max tries: 3
 * - Backoff: [60, 300, 900] seconds (1 min, 5 min, 15 min)
 * - Timeout: 120 seconds per attempt
 * - Queue: 'einvoice'
 *
 * @property int $eInvoiceId E-invoice ID to submit
 * @property int|null $userId User ID who initiated submission (optional)
 * @property array $options Additional submission options
 */
class SubmitEInvoiceJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Maximum number of retry attempts
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Backoff delays in seconds between retries
     * [1 minute, 5 minutes, 15 minutes]
     *
     * @var array
     */
    public $backoff = [60, 300, 900];

    /**
     * Job execution timeout in seconds
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * Queue name for e-invoice submissions
     *
     * @var string
     */
    /**
     * E-invoice ID to submit
     *
     * @var int
     */
    protected int $eInvoiceId;

    /**
     * User ID who initiated submission (optional)
     *
     * @var int|null
     */
    protected ?int $userId;

    /**
     * Additional submission options
     *
     * @var array
     */
    protected array $options;

    /**
     * Current submission record
     *
     * @var EInvoiceSubmission|null
     */
    protected ?EInvoiceSubmission $submission = null;

    /**
     * Create a new job instance.
     *
     * @param int $eInvoiceId E-invoice ID to submit
     * @param int|null $userId User ID who initiated submission
     * @param array $options Additional submission options
     * @return void
     */
    public function __construct(int $eInvoiceId, ?int $userId = null, array $options = [])
    {
        $this->eInvoiceId = $eInvoiceId;
        $this->userId = $userId;
        $this->options = $options;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws Exception
     */
    public function handle(): void
    {
        Log::info('SubmitEInvoiceJob: Starting e-invoice submission', [
            'e_invoice_id' => $this->eInvoiceId,
            'user_id' => $this->userId,
            'attempt' => $this->attempts(),
        ]);

        try {
            // Step 1: Load EInvoice model
            $eInvoice = $this->loadEInvoice();

            // Step 2: Check if already submitted (skip if accepted/rejected)
            if ($this->shouldSkipSubmission($eInvoice)) {
                Log::info('SubmitEInvoiceJob: Skipping submission - already processed', [
                    'e_invoice_id' => $eInvoice->id,
                    'status' => $eInvoice->status,
                ]);
                return;
            }

            // Step 3: Generate UBL XML
            $ublXml = $this->generateUblXml($eInvoice);

            // Step 4: Sign XML with company's active certificate
            $signedXml = $this->signXml($eInvoice, $ublXml);

            // Step 5: Create EInvoiceSubmission record with idempotency key
            $this->submission = $this->createSubmission($eInvoice);

            // Step 6: Submit to tax authority portal
            $uploadResult = $this->submitToPortal($eInvoice, $signedXml);

            // Step 7: Parse response for receipt number
            $receiptNumber = $this->extractReceiptNumber($uploadResult);

            // Step 8: Update submission status
            $this->updateSubmissionStatus($uploadResult, $receiptNumber);

            // Step 9: Update e-invoice status
            $this->updateEInvoiceStatus($eInvoice, $uploadResult);

            // Step 10: Log success
            Log::info('SubmitEInvoiceJob: E-invoice submitted successfully', [
                'e_invoice_id' => $eInvoice->id,
                'submission_id' => $this->submission->id,
                'receipt_number' => $receiptNumber,
                'status' => $uploadResult['status'] ?? 'unknown',
            ]);

        } catch (Throwable $exception) {
            $this->handleException($exception);
        }
    }

    /**
     * Handle job failure.
     *
     * @param Throwable $exception
     * @return void
     */
    public function failed(Throwable $exception): void
    {
        Log::error('SubmitEInvoiceJob: Job failed after all retry attempts', [
            'e_invoice_id' => $this->eInvoiceId,
            'attempts' => $this->attempts(),
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Mark submission as error if it exists
        if ($this->submission) {
            $this->submission->markAsError(
                'Job failed after maximum retry attempts: ' . $exception->getMessage(),
                ['exception' => get_class($exception)],
                false // Don't schedule retry (already exhausted)
            );

            // Also mark e-invoice as failed
            $eInvoice = EInvoice::find($this->eInvoiceId);
            if ($eInvoice) {
                $eInvoice->markAsFailed('Submission failed after ' . $this->tries . ' attempts');
            }
        }
    }

    /**
     * Load the e-invoice model.
     *
     * @return EInvoice
     * @throws Exception
     */
    protected function loadEInvoice(): EInvoice
    {
        Log::info('SubmitEInvoiceJob: Loading e-invoice', [
            'e_invoice_id' => $this->eInvoiceId,
        ]);

        $eInvoice = EInvoice::with(['invoice', 'company', 'certificate'])->find($this->eInvoiceId);

        if (!$eInvoice) {
            throw new Exception("E-invoice not found: {$this->eInvoiceId}");
        }

        if (!$eInvoice->invoice) {
            throw new Exception("Invoice not found for e-invoice: {$this->eInvoiceId}");
        }

        if (!$eInvoice->company) {
            throw new Exception("Company not found for e-invoice: {$this->eInvoiceId}");
        }

        return $eInvoice;
    }

    /**
     * Check if submission should be skipped.
     *
     * @param EInvoice $eInvoice
     * @return bool
     */
    protected function shouldSkipSubmission(EInvoice $eInvoice): bool
    {
        // Skip if already accepted or rejected (final states)
        return in_array($eInvoice->status, [
            EInvoice::STATUS_ACCEPTED,
            EInvoice::STATUS_REJECTED,
        ]);
    }

    /**
     * Generate UBL XML from invoice.
     *
     * @param EInvoice $eInvoice
     * @return string UBL XML content
     * @throws Exception
     */
    protected function generateUblXml(EInvoice $eInvoice): string
    {
        Log::info('SubmitEInvoiceJob: Generating UBL XML', [
            'e_invoice_id' => $eInvoice->id,
            'invoice_id' => $eInvoice->invoice_id,
        ]);

        try {
            $mapper = new MkUblMapper();
            $ublXml = $mapper->mapInvoiceToUbl($eInvoice->invoice);

            // Store UBL XML in e-invoice record
            $eInvoice->ubl_xml = $ublXml;
            $eInvoice->save();

            Log::info('SubmitEInvoiceJob: UBL XML generated successfully', [
                'e_invoice_id' => $eInvoice->id,
                'xml_size' => strlen($ublXml),
            ]);

            return $ublXml;

        } catch (Exception $e) {
            Log::error('SubmitEInvoiceJob: Failed to generate UBL XML', [
                'e_invoice_id' => $eInvoice->id,
                'error' => $e->getMessage(),
            ]);
            throw new Exception('Failed to generate UBL XML: ' . $e->getMessage());
        }
    }

    /**
     * Sign XML with company's active certificate.
     *
     * @param EInvoice $eInvoice
     * @param string $ublXml
     * @return string Signed XML content
     * @throws Exception
     */
    protected function signXml(EInvoice $eInvoice, string $ublXml): string
    {
        Log::info('SubmitEInvoiceJob: Signing UBL XML', [
            'e_invoice_id' => $eInvoice->id,
            'company_id' => $eInvoice->company_id,
        ]);

        try {
            // Get company's active certificate
            $certificate = $this->getActiveCertificate($eInvoice);

            // Initialize XML signer
            $signer = new MkXmlSigner(
                $certificate->certificate_path,
                $certificate->certificate_path,
                $this->options['certificate_password'] ?? null
            );

            // Sign the XML
            $signedXml = $signer->signXml($ublXml);

            // Store signed XML in e-invoice record
            $eInvoice->ubl_xml_signed = $signedXml;
            $eInvoice->certificate_id = $certificate->id;
            $eInvoice->status = EInvoice::STATUS_SIGNED;
            $eInvoice->signed_at = now();
            $eInvoice->save();

            // Update certificate last_used_at
            $certificate->last_used_at = now();
            $certificate->save();

            Log::info('SubmitEInvoiceJob: UBL XML signed successfully', [
                'e_invoice_id' => $eInvoice->id,
                'certificate_id' => $certificate->id,
                'signed_xml_size' => strlen($signedXml),
            ]);

            return $signedXml;

        } catch (Exception $e) {
            Log::error('SubmitEInvoiceJob: Failed to sign UBL XML', [
                'e_invoice_id' => $eInvoice->id,
                'error' => $e->getMessage(),
            ]);
            throw new Exception('Failed to sign UBL XML: ' . $e->getMessage());
        }
    }

    /**
     * Get company's active certificate.
     *
     * @param EInvoice $eInvoice
     * @return Certificate
     * @throws Exception
     */
    protected function getActiveCertificate(EInvoice $eInvoice): Certificate
    {
        // Try to use the certificate already associated with the e-invoice
        if ($eInvoice->certificate_id) {
            $certificate = Certificate::find($eInvoice->certificate_id);
            if ($certificate && $certificate->is_active && !$certificate->is_expired) {
                return $certificate;
            }
        }

        // Otherwise, get the company's active certificate
        $certificate = Certificate::where('company_id', $eInvoice->company_id)
            ->where('is_active', true)
            ->where('valid_to', '>', now())
            ->orderBy('valid_to', 'desc')
            ->first();

        if (!$certificate) {
            throw new Exception("No active certificate found for company: {$eInvoice->company_id}");
        }

        return $certificate;
    }

    /**
     * Create submission record with idempotency key.
     *
     * @param EInvoice $eInvoice
     * @return EInvoiceSubmission
     */
    protected function createSubmission(EInvoice $eInvoice): EInvoiceSubmission
    {
        Log::info('SubmitEInvoiceJob: Creating submission record', [
            'e_invoice_id' => $eInvoice->id,
            'attempt' => $this->attempts(),
        ]);

        // Create idempotency key based on e-invoice ID and attempt number
        $idempotencyKey = sprintf(
            'einvoice-%d-attempt-%d-%s',
            $eInvoice->id,
            $this->attempts(),
            now()->format('YmdHis')
        );

        $submission = EInvoiceSubmission::create([
            'e_invoice_id' => $eInvoice->id,
            'company_id' => $eInvoice->company_id,
            'submitted_by' => $this->userId,
            'submitted_at' => now(),
            'status' => EInvoiceSubmission::STATUS_PENDING,
            'retry_count' => $this->attempts() - 1,
            'response_data' => [
                'idempotency_key' => $idempotencyKey,
                'job_uuid' => $this->job->uuid() ?? null,
            ],
        ]);

        Log::info('SubmitEInvoiceJob: Submission record created', [
            'submission_id' => $submission->id,
            'idempotency_key' => $idempotencyKey,
        ]);

        return $submission;
    }

    /**
     * Submit to tax authority portal using efaktura_upload.php tool.
     *
     * @param EInvoice $eInvoice
     * @param string $signedXml
     * @return array Upload result
     * @throws Exception
     */
    protected function submitToPortal(EInvoice $eInvoice, string $signedXml): array
    {
        Log::info('SubmitEInvoiceJob: Submitting to tax authority portal', [
            'e_invoice_id' => $eInvoice->id,
            'submission_id' => $this->submission->id,
        ]);

        try {
            // Save signed XML to temporary file
            $tempXmlPath = $this->saveToTempFile($signedXml, 'einvoice', '.xml');

            // Path to efaktura_upload.php tool
            $uploadToolPath = base_path('tools/efaktura_upload.php');

            if (!file_exists($uploadToolPath)) {
                throw new Exception("E-faktura upload tool not found: {$uploadToolPath}");
            }

            // Execute upload tool via Process facade
            $result = Process::timeout($this->timeout)
                ->run([
                    'php',
                    $uploadToolPath,
                    '--xml=' . $tempXmlPath,
                    '--mode=' . config('mk.efaktura.mode', 'portal'),
                ]);

            // Clean up temporary file
            @unlink($tempXmlPath);

            // Check if process was successful
            if (!$result->successful()) {
                throw new Exception(
                    'Upload tool execution failed: ' . $result->errorOutput()
                );
            }

            // Parse output from upload tool
            $output = $result->output();
            $uploadResult = $this->parseUploadToolOutput($output);

            Log::info('SubmitEInvoiceJob: Portal submission completed', [
                'e_invoice_id' => $eInvoice->id,
                'upload_result' => $uploadResult,
            ]);

            return $uploadResult;

        } catch (Exception $e) {
            Log::error('SubmitEInvoiceJob: Portal submission failed', [
                'e_invoice_id' => $eInvoice->id,
                'error' => $e->getMessage(),
            ]);
            throw new Exception('Portal submission failed: ' . $e->getMessage());
        }
    }

    /**
     * Save content to temporary file.
     *
     * @param string $content
     * @param string $prefix
     * @param string $extension
     * @return string File path
     */
    protected function saveToTempFile(string $content, string $prefix = 'temp', string $extension = '.tmp'): string
    {
        $tempDir = sys_get_temp_dir();
        $tempFile = tempnam($tempDir, $prefix) . $extension;

        file_put_contents($tempFile, $content);

        return $tempFile;
    }

    /**
     * Parse output from efaktura_upload.php tool.
     *
     * @param string $output
     * @return array Parsed result
     */
    protected function parseUploadToolOutput(string $output): array
    {
        $result = [
            'success' => false,
            'status' => 'unknown',
            'upload_id' => null,
            'receipt_number' => null,
            'raw_output' => $output,
        ];

        // Look for success indicators
        if (preg_match('/Success:\s*(Yes|true)/i', $output)) {
            $result['success'] = true;
        }

        // Extract status
        if (preg_match('/Status:\s*(\w+)/i', $output, $matches)) {
            $result['status'] = strtolower(trim($matches[1]));
        }

        // Extract upload ID
        if (preg_match('/Upload ID:\s*([^\s\n]+)/i', $output, $matches)) {
            $result['upload_id'] = trim($matches[1]);
        }

        // Extract receipt number
        if (preg_match('/Receipt:\s*([^\s\n]+)/i', $output, $matches)) {
            $result['receipt_number'] = trim($matches[1]);
        }

        return $result;
    }

    /**
     * Extract receipt number from upload result.
     *
     * @param array $uploadResult
     * @return string|null
     */
    protected function extractReceiptNumber(array $uploadResult): ?string
    {
        return $uploadResult['receipt_number'] ?? null;
    }

    /**
     * Update submission status based on upload result.
     *
     * @param array $uploadResult
     * @param string|null $receiptNumber
     * @return void
     */
    protected function updateSubmissionStatus(array $uploadResult, ?string $receiptNumber): void
    {
        Log::info('SubmitEInvoiceJob: Updating submission status', [
            'submission_id' => $this->submission->id,
            'status' => $uploadResult['status'] ?? 'unknown',
        ]);

        $status = $uploadResult['status'] ?? 'unknown';

        if ($uploadResult['success'] && in_array($status, ['accepted', 'success'])) {
            // Mark as accepted
            $this->submission->markAsAccepted($receiptNumber, $uploadResult);
        } elseif (in_array($status, ['rejected', 'failed'])) {
            // Mark as rejected
            $this->submission->markAsRejected(
                $uploadResult['error_message'] ?? 'Submission rejected by tax authority',
                $uploadResult
            );
        } else {
            // Mark as error and schedule retry
            $this->submission->markAsError(
                'Unexpected upload status: ' . $status,
                $uploadResult,
                true // Schedule retry
            );
        }
    }

    /**
     * Update e-invoice status based on upload result.
     *
     * @param EInvoice $eInvoice
     * @param array $uploadResult
     * @return void
     */
    protected function updateEInvoiceStatus(EInvoice $eInvoice, array $uploadResult): void
    {
        Log::info('SubmitEInvoiceJob: Updating e-invoice status', [
            'e_invoice_id' => $eInvoice->id,
            'status' => $uploadResult['status'] ?? 'unknown',
        ]);

        $status = $uploadResult['status'] ?? 'unknown';

        if ($uploadResult['success'] && in_array($status, ['accepted', 'success'])) {
            // Mark as accepted
            $eInvoice->markAsAccepted();
            $eInvoice->submitted_at = now();
            $eInvoice->save();
        } elseif (in_array($status, ['rejected', 'failed'])) {
            // Mark as rejected
            $eInvoice->markAsRejected(
                $uploadResult['error_message'] ?? 'Submission rejected by tax authority'
            );
        } else {
            // Mark as submitted (pending confirmation)
            $eInvoice->status = EInvoice::STATUS_SUBMITTED;
            $eInvoice->submitted_at = now();
            $eInvoice->save();
        }
    }

    /**
     * Handle exception during job execution.
     *
     * @param Throwable $exception
     * @return void
     * @throws Exception
     */
    protected function handleException(Throwable $exception): void
    {
        Log::error('SubmitEInvoiceJob: Exception during job execution', [
            'e_invoice_id' => $this->eInvoiceId,
            'submission_id' => $this->submission?->id,
            'attempt' => $this->attempts(),
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Mark submission as error if it exists
        if ($this->submission) {
            $this->submission->markAsError(
                $exception->getMessage(),
                [
                    'exception' => get_class($exception),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                ],
                true // Schedule retry
            );
        }

        // Re-throw exception to trigger Laravel's retry mechanism
        throw $exception;
    }
}

// CLAUDE-CHECKPOINT
