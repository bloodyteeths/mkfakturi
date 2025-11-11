<?php

namespace App\Http\Controllers\V1\Admin\EInvoice;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\EInvoice;
use App\Models\EInvoiceSubmission;
use App\Models\Invoice;
use App\Services\CertificateExtractionService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Mk\Services\MkUblMapper;
use Modules\Mk\Services\MkXmlSigner;

/**
 * E-Invoice Controller
 *
 * Manages electronic invoice generation, signing, and submission
 * to the Macedonian tax authority (e-UJP portal).
 *
 * Handles the complete e-invoicing workflow:
 * - Generate UBL XML from invoices
 * - Sign XML with QES certificates
 * - Submit to tax authority
 * - Track submission status
 * - Retry failed submissions
 */
class EInvoiceController extends Controller
{
    /**
     * Portal URL for health checks
     */
    protected const PORTAL_URL = 'https://e-ujp.ujp.gov.mk';

    /**
     * Display a listing of e-invoices with filters.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', EInvoice::class);

        $limit = $request->input('limit', 10);

        $query = EInvoice::whereCompany()
            ->with([
                'invoice.customer',
                'invoice.currency',
                'certificate:id,name,fingerprint',
                'submissions' => function ($query) {
                    $query->latest()->limit(5);
                },
            ]);

        // Apply filters
        if ($request->has('status')) {
            $query->whereStatus($request->input('status'));
        }

        if ($request->has('invoice_id')) {
            $query->whereInvoice($request->input('invoice_id'));
        }

        if ($request->has('date_from')) {
            $query->whereHas('invoice', function ($q) use ($request) {
                $q->where('invoice_date', '>=', $request->input('date_from'));
            });
        }

        if ($request->has('date_to')) {
            $query->whereHas('invoice', function ($q) use ($request) {
                $q->where('invoice_date', '<=', $request->input('date_to'));
            });
        }

        $eInvoices = $query->latest()->paginateData($limit);

        return response()->json($eInvoices);
    }

    /**
     * Display the specified e-invoice with submissions.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        Log::info('[EInvoiceController::show] Request received', [
            'e_invoice_id' => $id,
            'user_id' => auth()->id(),
            'user_email' => auth()->user()->email,
            'company_id' => auth()->user()->company_id ?? 'N/A',
            'is_owner' => auth()->user()->isOwner(),
        ]);

        try {
            $this->authorize('viewAny', EInvoice::class);
            Log::info('[EInvoiceController::show] Authorization PASSED');
        } catch (\Exception $e) {
            Log::error('[EInvoiceController::show] Authorization FAILED', [
                'error' => $e->getMessage(),
                'user_abilities' => auth()->user()->getAbilities()->pluck('name')->toArray(),
            ]);
            throw $e;
        }

        $eInvoice = EInvoice::whereCompany()
            ->with([
                'invoice.customer',
                'invoice.currency',
                'invoice.items.taxes',
                'certificate',
                'submissions.submittedBy',
            ])
            ->findOrFail($id);

        return response()->json([
            'data' => $eInvoice,
        ]);
    }

    /**
     * Generate UBL XML for an invoice (preview mode, doesn't save).
     *
     * @param  int  $invoiceId
     * @return JsonResponse
     */
    public function generate(int $invoiceId): JsonResponse
    {
        Log::info('[EInvoiceController::generate] Request received', [
            'invoice_id' => $invoiceId,
            'user_id' => auth()->id(),
            'user_email' => auth()->user()->email,
            'company_id' => auth()->user()->company_id ?? 'N/A',
            'is_owner' => auth()->user()->isOwner(),
            'request_headers' => request()->headers->all(),
        ]);

        try {
            $this->authorize('create', EInvoice::class);
            Log::info('[EInvoiceController::generate] Authorization PASSED');
        } catch (\Exception $e) {
            Log::error('[EInvoiceController::generate] Authorization FAILED', [
                'error' => $e->getMessage(),
                'user_abilities' => auth()->user()->getAbilities()->pluck('name')->toArray(),
            ]);
            throw $e;
        }

        try {
            $invoice = Invoice::whereCompany()
                ->with([
                    'customer',
                    'customer.addresses',
                    'company',
                    'company.address',
                    'company.bankAccounts',
                    'items.taxes.taxType',
                    'taxes.taxType',
                    'currency',
                ])
                ->findOrFail($invoiceId);

            // Check if invoice is already sent/finalized
            if ($invoice->status !== 'SENT') {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice must be in SENT status to generate e-invoice.',
                ], 422);
            }

            // Generate UBL XML
            $ublMapper = new MkUblMapper();
            $ublXml = $ublMapper->mapInvoiceToUbl($invoice);

            // Get or create e-invoice record
            $eInvoice = EInvoice::whereInvoice($invoiceId)->first();

            if (!$eInvoice) {
                $eInvoice = EInvoice::create([
                    'invoice_id' => $invoice->id,
                    'company_id' => $invoice->company_id,
                    'ubl_xml' => $ublXml,
                    'status' => EInvoice::STATUS_DRAFT,
                ]);
            } else {
                // Update draft UBL XML
                $eInvoice->update([
                    'ubl_xml' => $ublXml,
                ]);
            }

            // Refresh and load relationships
            $eInvoice->load([
                'invoice.customer',
                'invoice.currency',
                'invoice.items.taxes',
            ]);

            return response()->json([
                'success' => true,
                'data' => $eInvoice,
                'message' => 'E-invoice generated successfully',
            ]);
        } catch (Exception $e) {
            Log::error('E-invoice generation failed', [
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate e-invoice: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sign an e-invoice with the company's active certificate.
     *
     * @param  int  $id
     * @param  Request  $request
     * @return JsonResponse
     */
    public function sign(int $id, Request $request): JsonResponse
    {
        $this->authorize('create', EInvoice::class);

        try {
            $eInvoice = EInvoice::whereCompany()->findOrFail($id);

            // Validate e-invoice has UBL XML
            if (empty($eInvoice->ubl_xml)) {
                return response()->json([
                    'success' => false,
                    'message' => 'E-invoice has no UBL XML. Please generate first.',
                ], 422);
            }

            // Get active certificate for company
            $certificate = Certificate::getActiveCertificate($eInvoice->company_id);

            if (!$certificate) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active certificate found for this company. Please upload a valid certificate.',
                ], 422);
            }

            // Validate certificate is not expired
            if (!$certificate->isValid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Certificate is expired or invalid. Please upload a new certificate.',
                ], 422);
            }

            // Get passphrase from request
            $passphrase = $request->input('passphrase');

            // Create extraction service instance for cleanup tracking
            $extractionService = app(CertificateExtractionService::class);

            try {
                // Extract certificate paths (decrypt if needed)
                $privateKeyPath = $extractionService->getTempPrivateKeyPath($certificate, $passphrase);
                $certificatePath = $extractionService->getTempCertificatePath($certificate, $passphrase);

                // Sign the XML
                $signer = new MkXmlSigner(
                    $privateKeyPath,
                    $certificatePath,
                    $passphrase
                );

                $signedXml = $signer->signXml($eInvoice->ubl_xml);

                // Get certificate info for metadata
                $certInfo = $signer->getCertificateInfo();

                // Update e-invoice with signed XML
                $eInvoice->sign(
                    $certificate,
                    $signedXml,
                    $certInfo['subject'] ?? null,
                    $certInfo['issuer'] ?? null
                );

                // Update certificate last used timestamp
                $certificate->markAsUsed();

                return response()->json([
                    'success' => true,
                    'message' => 'E-invoice signed successfully.',
                    'data' => [
                        'e_invoice' => $eInvoice->fresh(),
                        'certificate_info' => $certInfo,
                    ],
                ]);
            } finally {
                // Always cleanup temporary files
                $extractionService->cleanup();
            }
        } catch (Exception $e) {
            Log::error('E-invoice signing failed', [
                'e_invoice_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Check for specific error types
            $message = 'Failed to sign e-invoice: ';

            if (str_contains($e->getMessage(), 'Private key file not found')) {
                $message .= 'Certificate private key not found.';
            } elseif (str_contains($e->getMessage(), 'Certificate file not found')) {
                $message .= 'Certificate file not found.';
            } elseif (str_contains($e->getMessage(), 'Invalid XML')) {
                $message .= 'Invalid UBL XML format.';
            } else {
                $message .= $e->getMessage();
            }

            return response()->json([
                'success' => false,
                'message' => $message,
            ], 500);
        }
    }

    /**
     * Queue e-invoice for submission to tax authority.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function submit(int $id): JsonResponse
    {
        $this->authorize('update', EInvoice::class);

        try {
            $eInvoice = EInvoice::whereCompany()->findOrFail($id);

            // Validate e-invoice is signed
            if (!$eInvoice->isSigned()) {
                return response()->json([
                    'success' => false,
                    'message' => 'E-invoice must be signed before submission.',
                ], 422);
            }

            // Check if already submitted and accepted
            if ($eInvoice->isAccepted()) {
                return response()->json([
                    'success' => false,
                    'message' => 'E-invoice has already been accepted by the tax authority.',
                ], 422);
            }

            DB::beginTransaction();

            try {
                // Update e-invoice status
                $eInvoice->submit();

                // Create submission record
                $submission = EInvoiceSubmission::create([
                    'e_invoice_id' => $eInvoice->id,
                    'company_id' => $eInvoice->company_id,
                    'submitted_by' => auth()->id(),
                    'submitted_at' => now(),
                    'status' => EInvoiceSubmission::STATUS_PENDING,
                    'portal_url' => self::PORTAL_URL,
                    'retry_count' => 0,
                ]);

                DB::commit();

                // Dispatch job to submit to tax authority
                dispatch(new \App\Jobs\SubmitEInvoiceJob($submission->id));

                return response()->json([
                    'success' => true,
                    'message' => 'E-invoice queued for submission.',
                    'data' => [
                        'e_invoice' => $eInvoice->fresh(),
                        'submission' => $submission,
                    ],
                ]);
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            Log::error('E-invoice submission failed', [
                'e_invoice_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit e-invoice: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Simulate/validate XML without submitting to portal.
     * Useful for testing XML validity before actual submission.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function simulate(int $id): JsonResponse
    {
        $this->authorize('create', EInvoice::class);

        try {
            $eInvoice = EInvoice::whereCompany()->findOrFail($id);

            // Validate e-invoice has signed XML
            if (empty($eInvoice->ubl_xml_signed)) {
                return response()->json([
                    'success' => false,
                    'message' => 'E-invoice must be signed before simulation.',
                ], 422);
            }

            // Validate XML structure
            $doc = new \DOMDocument();
            $doc->preserveWhiteSpace = false;
            $doc->formatOutput = true;

            if (!$doc->loadXML($eInvoice->ubl_xml_signed)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid XML structure.',
                ], 422);
            }

            // Verify signature
            $signer = new MkXmlSigner();
            $isValidSignature = $signer->verifySignature($eInvoice->ubl_xml_signed);

            // Validate UBL schema (basic checks)
            $validationErrors = $this->validateUblStructure($doc);

            return response()->json([
                'success' => true,
                'data' => [
                    'xml_valid' => true,
                    'signature_valid' => $isValidSignature,
                    'validation_errors' => $validationErrors,
                    'xml_size_bytes' => strlen($eInvoice->ubl_xml_signed),
                    'ready_for_submission' => $isValidSignature && empty($validationErrors),
                ],
            ]);
        } catch (Exception $e) {
            Log::error('E-invoice simulation failed', [
                'e_invoice_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Simulation failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download signed XML file.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function downloadXml(int $id)
    {
        $this->authorize('viewAny', EInvoice::class);

        $eInvoice = EInvoice::whereCompany()->findOrFail($id);

        if (empty($eInvoice->ubl_xml_signed)) {
            return response()->json([
                'success' => false,
                'message' => 'E-invoice has not been signed yet.',
            ], 422);
        }

        $invoice = $eInvoice->invoice;
        $filename = 'e-invoice-'.$invoice->invoice_number.'-signed.xml';

        return response($eInvoice->ubl_xml_signed, 200)
            ->header('Content-Type', 'application/xml')
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
    }

    /**
     * Retry a failed submission.
     *
     * @param  int  $submissionId
     * @return JsonResponse
     */
    public function resubmit(int $submissionId): JsonResponse
    {
        $this->authorize('update', EInvoice::class);

        try {
            $submission = EInvoiceSubmission::whereCompany()
                ->with('eInvoice')
                ->findOrFail($submissionId);

            // Check if can retry
            if (!$submission->canRetry()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Maximum retry attempts exceeded or submission is not in retryable state.',
                ], 422);
            }

            // Update retry info
            $submission->retry();

            // Dispatch job again
            dispatch(new \App\Jobs\SubmitEInvoiceJob($submission->id));

            return response()->json([
                'success' => true,
                'message' => 'E-invoice resubmission queued.',
                'data' => [
                    'submission' => $submission->fresh(),
                    'retry_count' => $submission->retry_count,
                    'next_retry_at' => $submission->next_retry_at,
                ],
            ]);
        } catch (Exception $e) {
            Log::error('E-invoice resubmission failed', [
                'submission_id' => $submissionId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to resubmit: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check portal health status.
     * Pings the e-UJP portal to verify availability.
     *
     * @return JsonResponse
     */
    public function checkPortalStatus(): JsonResponse
    {
        $this->authorize('viewAny', EInvoice::class);

        try {
            $startTime = microtime(true);

            $response = Http::timeout(10)->get(self::PORTAL_URL);

            $endTime = microtime(true);
            $responseTime = round(($endTime - $startTime) * 1000, 2); // ms

            $isOnline = $response->successful();

            return response()->json([
                'success' => true,
                'data' => [
                    'portal_url' => self::PORTAL_URL,
                    'is_online' => $isOnline,
                    'status_code' => $response->status(),
                    'response_time_ms' => $responseTime,
                    'checked_at' => now()->toIso8601String(),
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'data' => [
                    'portal_url' => self::PORTAL_URL,
                    'is_online' => false,
                    'error' => $e->getMessage(),
                    'checked_at' => now()->toIso8601String(),
                ],
            ]);
        }
    }

    /**
     * Get submission queue (pending and failed submissions).
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function getSubmissionQueue(Request $request): JsonResponse
    {
        $this->authorize('viewAny', EInvoice::class);

        $limit = $request->input('limit', 20);

        $submissions = EInvoiceSubmission::whereCompany()
            ->with([
                'eInvoice.invoice',
                'submittedBy',
            ])
            ->whereIn('status', [
                EInvoiceSubmission::STATUS_PENDING,
                EInvoiceSubmission::STATUS_ERROR,
            ])
            ->latest()
            ->paginateData($limit);

        // Get statistics
        $stats = [
            'total_pending' => EInvoiceSubmission::whereCompany()
                ->whereStatus(EInvoiceSubmission::STATUS_PENDING)
                ->count(),
            'total_error' => EInvoiceSubmission::whereCompany()
                ->whereStatus(EInvoiceSubmission::STATUS_ERROR)
                ->count(),
            'ready_for_retry' => EInvoiceSubmission::whereCompany()
                ->where('status', EInvoiceSubmission::STATUS_ERROR)
                ->where('retry_count', '<', EInvoiceSubmission::MAX_RETRIES)
                ->where('next_retry_at', '<=', now())
                ->count(),
        ];

        return response()->json([
            'data' => $submissions,
            'stats' => $stats,
        ]);
    }

    /**
     * Extract private key from certificate.
     * Handles decryption and temporary file creation using CertificateExtractionService.
     *
     * @param  Certificate  $certificate
     * @param  string|null  $passphrase Optional PFX passphrase
     * @return string Path to private key file
     * @throws Exception
     */
    protected function extractPrivateKey(Certificate $certificate, ?string $passphrase = null): string
    {
        $extractionService = app(CertificateExtractionService::class);
        return $extractionService->getTempPrivateKeyPath($certificate, $passphrase);
    }

    /**
     * Extract certificate from Certificate model.
     * Handles decryption and temporary file creation using CertificateExtractionService.
     *
     * @param  Certificate  $certificate
     * @param  string|null  $passphrase Optional PFX passphrase
     * @return string Path to certificate file
     * @throws Exception
     */
    protected function extractCertificate(Certificate $certificate, ?string $passphrase = null): string
    {
        $extractionService = app(CertificateExtractionService::class);
        return $extractionService->getTempCertificatePath($certificate, $passphrase);
    }

    /**
     * Format XML for preview display.
     *
     * @param  string  $xml
     * @return array Formatted preview data
     */
    protected function formatXmlPreview(string $xml): array
    {
        $doc = new \DOMDocument();
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = true;
        $doc->loadXML($xml);

        // Extract key fields for preview
        $preview = [
            'invoice_number' => $this->getXmlValue($doc, 'ID'),
            'issue_date' => $this->getXmlValue($doc, 'IssueDate'),
            'supplier_name' => $this->getXmlValue($doc, 'AccountingSupplierParty/Party/PartyName/Name'),
            'customer_name' => $this->getXmlValue($doc, 'AccountingCustomerParty/Party/PartyName/Name'),
            'total_amount' => $this->getXmlValue($doc, 'LegalMonetaryTotal/PayableAmount'),
        ];

        return $preview;
    }

    /**
     * Get XML element value by tag name.
     *
     * @param  \DOMDocument  $doc
     * @param  string  $tagPath
     * @return string|null
     */
    protected function getXmlValue(\DOMDocument $doc, string $tagPath): ?string
    {
        $parts = explode('/', $tagPath);
        $elements = $doc->getElementsByTagName(end($parts));

        if ($elements->length > 0) {
            return $elements->item(0)->nodeValue;
        }

        return null;
    }

    /**
     * Validate UBL XML structure.
     *
     * @param  \DOMDocument  $doc
     * @return array Validation errors
     */
    protected function validateUblStructure(\DOMDocument $doc): array
    {
        $errors = [];

        // Required elements check
        $requiredElements = [
            'ID',
            'IssueDate',
            'AccountingSupplierParty',
            'AccountingCustomerParty',
            'LegalMonetaryTotal',
        ];

        foreach ($requiredElements as $element) {
            $elements = $doc->getElementsByTagName($element);
            if ($elements->length === 0) {
                $errors[] = "Missing required element: {$element}";
            }
        }

        return $errors;
    }
}

// CLAUDE-CHECKPOINT
