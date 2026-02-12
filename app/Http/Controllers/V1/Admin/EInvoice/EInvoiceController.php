<?php

namespace App\Http\Controllers\V1\Admin\EInvoice;

use App\Http\Controllers\Controller;
use App\Jobs\PollEInvoiceInboxJob;
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

        // Return e-invoice with submissions
        return response()->json([
            'data' => $eInvoice,
            'submissions' => $eInvoice->submissions->toArray(),
        ]);
    }

    /**
     * Display e-invoice by invoice ID with submissions.
     */
    public function showByInvoice(int $invoiceId): JsonResponse
    {
        Log::info('[EInvoiceController::showByInvoice] Request received', [
            'invoice_id' => $invoiceId,
            'user_id' => auth()->id(),
            'user_email' => auth()->user()->email,
            'company_id' => auth()->user()->company_id ?? 'N/A',
            'is_owner' => auth()->user()->isOwner(),
        ]);

        try {
            $this->authorize('viewAny', EInvoice::class);
            Log::info('[EInvoiceController::showByInvoice] Authorization PASSED');
        } catch (\Exception $e) {
            Log::error('[EInvoiceController::showByInvoice] Authorization FAILED', [
                'error' => $e->getMessage(),
                'user_abilities' => auth()->user()->getAbilities()->pluck('name')->toArray(),
            ]);
            throw $e;
        }

        $eInvoice = EInvoice::whereCompany()
            ->whereInvoice($invoiceId)
            ->with([
                'invoice.customer',
                'invoice.currency',
                'invoice.items.taxes',
                'certificate',
                'submissions.submittedBy',
            ])
            ->first();

        if (! $eInvoice) {
            return response()->json([
                'data' => null,
                'submissions' => [],
            ]);
        }

        return response()->json([
            'data' => $eInvoice,
            'submissions' => $eInvoice->submissions->toArray(),
        ]);
    }
    // CLAUDE-CHECKPOINT

    /**
     * Generate UBL XML for an invoice (preview mode, doesn't save).
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

            // Check usage limit for e-faktura
            $usageService = app(\App\Services\UsageLimitService::class);
            $company = $invoice->company;
            if ($company && ! $usageService->canUse($company, 'efaktura_per_month')) {
                return response()->json($usageService->buildLimitExceededResponse($company, 'efaktura_per_month'), 402);
            }

            // Check if invoice is already sent/finalized
            if ($invoice->status !== 'SENT') {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice must be in SENT status to generate e-invoice.',
                ], 422);
            }

            // Generate UBL XML
            $ublMapper = new MkUblMapper;
            $ublXml = $ublMapper->mapInvoiceToUbl($invoice);

            // Get or create e-invoice record
            $eInvoice = EInvoice::whereInvoice($invoiceId)->first();

            if (! $eInvoice) {
                $eInvoice = EInvoice::create([
                    'invoice_id' => $invoice->id,
                    'company_id' => $invoice->company_id,
                    'ubl_xml' => $ublXml,
                    'status' => EInvoice::STATUS_DRAFT,
                ]);

                // Increment usage after first e-invoice creation (not re-generation)
                $usageService->incrementUsage($company, 'efaktura_per_month');
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
                'certificate',
                'submissions.submittedBy',
            ]);

            return response()->json([
                'success' => true,
                'data' => $eInvoice,
                'submissions' => $eInvoice->submissions->toArray(),
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

            if (! $certificate) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active certificate found for this company. Please upload a valid certificate.',
                ], 422);
            }

            // Validate certificate is not expired
            if (! $certificate->isValid()) {
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

                // Reload e-invoice with relationships
                $eInvoice = $eInvoice->fresh([
                    'invoice.customer',
                    'invoice.currency',
                    'invoice.items.taxes',
                    'certificate',
                    'submissions.submittedBy',
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'E-invoice signed successfully.',
                    'data' => $eInvoice,
                    'submissions' => $eInvoice->submissions->toArray(),
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
     */
    public function submit(int $id): JsonResponse
    {
        try {
            $eInvoice = EInvoice::whereCompany()->findOrFail($id);

            // Authorize after loading the model
            $this->authorize('update', $eInvoice);

            // Validate e-invoice is signed
            if (! $eInvoice->isSigned()) {
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
                dispatch(new \App\Jobs\SubmitEInvoiceJob($eInvoice->id))->onQueue('einvoice');

                // Reload e-invoice with relationships
                $eInvoice = $eInvoice->fresh([
                    'invoice.customer',
                    'invoice.currency',
                    'invoice.items.taxes',
                    'certificate',
                    'submissions.submittedBy',
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'E-invoice queued for submission.',
                    'data' => $eInvoice,
                    'submissions' => $eInvoice->submissions->toArray(),
                    'submission' => $submission,
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
            $doc = new \DOMDocument;
            $doc->preserveWhiteSpace = false;
            $doc->formatOutput = true;

            if (! $doc->loadXML($eInvoice->ubl_xml_signed)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid XML structure.',
                ], 422);
            }

            // Verify signature
            $signer = new MkXmlSigner;
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
     */
    public function resubmit(int $submissionId): JsonResponse
    {
        $this->authorize('update', EInvoice::class);

        try {
            $submission = EInvoiceSubmission::whereCompany()
                ->with('eInvoice')
                ->findOrFail($submissionId);

            // Check if can retry
            if (! $submission->canRetry()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Maximum retry attempts exceeded or submission is not in retryable state.',
                ], 422);
            }

            // Update retry info
            $submission->retry();

            // Dispatch job again
            dispatch(new \App\Jobs\SubmitEInvoiceJob($eInvoice->id));

            // Reload e-invoice with relationships
            $eInvoice = $submission->eInvoice->fresh([
                'invoice.customer',
                'invoice.currency',
                'invoice.items.taxes',
                'certificate',
                'submissions.submittedBy',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'E-invoice resubmission queued.',
                'data' => $eInvoice,
                'submissions' => $eInvoice->submissions->toArray(),
                'submission' => $submission->fresh(),
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

    // ------------------------------------------------------------------
    // P7-02: Incoming e-invoice endpoints
    // ------------------------------------------------------------------

    /**
     * List incoming e-invoices for the company.
     *
     * GET /api/v1/e-invoices/incoming
     *
     * Filters: status, date_from, date_to, sender (name or VAT ID)
     * Returns paginated inbound e-invoices.
     */
    public function listIncoming(Request $request): JsonResponse
    {
        $this->authorize('viewAny', EInvoice::class);

        $limit = $request->input('limit', 10);

        $query = EInvoice::whereCompany()
            ->inbound()
            ->with([
                'company:id,name',
                'reviewedBy:id,name,email',
            ]);

        // Apply status filter
        if ($request->has('status')) {
            $query->whereStatus($request->input('status'));
        }

        // Apply date range filters on received_at
        if ($request->has('date_from')) {
            $query->where('received_at', '>=', $request->input('date_from'));
        }

        if ($request->has('date_to')) {
            $query->where('received_at', '<=', $request->input('date_to'));
        }

        // Apply sender filter (searches name and VAT ID)
        if ($request->has('sender')) {
            $sender = $request->input('sender');
            $query->where(function ($q) use ($sender) {
                $q->where('sender_name', 'LIKE', "%{$sender}%")
                    ->orWhere('sender_vat_id', 'LIKE', "%{$sender}%");
            });
        }

        $eInvoices = $query->latest('received_at')->paginateData($limit);

        return response()->json($eInvoices);
    }

    /**
     * Show a single incoming e-invoice with UBL preview.
     *
     * GET /api/v1/e-invoices/incoming/{id}
     *
     * Returns the inbound e-invoice with its UBL XML preview data.
     */
    public function showIncoming(int $id): JsonResponse
    {
        $this->authorize('viewAny', EInvoice::class);

        $eInvoice = EInvoice::whereCompany()
            ->inbound()
            ->with([
                'company:id,name',
                'submissions',
                'reviewedBy:id,name,email',
            ])
            ->findOrFail($id);

        // Parse UBL XML for preview if available
        $ublPreview = null;
        if (! empty($eInvoice->ubl_xml)) {
            try {
                $ublPreview = $this->formatXmlPreview($eInvoice->ubl_xml);
            } catch (Exception $e) {
                Log::warning('Failed to parse UBL XML preview for incoming e-invoice', [
                    'e_invoice_id' => $id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'data' => $eInvoice,
            'ubl_preview' => $ublPreview,
            'submissions' => $eInvoice->submissions->toArray(),
        ]);
    }

    /**
     * Accept an incoming e-invoice.
     *
     * POST /api/v1/e-invoices/incoming/{id}/accept
     *
     * Validates that the e-invoice is in RECEIVED or UNDER_REVIEW status,
     * then marks it as ACCEPTED_INCOMING.
     */
    public function acceptIncoming(int $id): JsonResponse
    {
        $this->authorize('create', EInvoice::class);

        try {
            $eInvoice = EInvoice::whereCompany()
                ->inbound()
                ->findOrFail($id);

            // Validate status allows acceptance
            if (! in_array($eInvoice->status, [EInvoice::STATUS_RECEIVED, EInvoice::STATUS_UNDER_REVIEW])) {
                return response()->json([
                    'success' => false,
                    'message' => 'E-invoice cannot be accepted in its current status: '.$eInvoice->status,
                ], 422);
            }

            $eInvoice->reviewed_by = auth()->id();
            $eInvoice->acceptIncoming();

            // TODO: P7-03 — Optionally create a supplier bill/expense from UBL data
            // This would parse the UBL XML and create an AccountsPayable\Bill record
            // linked back to this inbound e-invoice.

            Log::info('Incoming e-invoice accepted', [
                'e_invoice_id' => $eInvoice->id,
                'reviewed_by' => auth()->id(),
                'sender_vat_id' => $eInvoice->sender_vat_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Incoming e-invoice accepted successfully.',
                'data' => $eInvoice->fresh(['company:id,name', 'reviewedBy:id,name,email']),
            ]);
        } catch (Exception $e) {
            Log::error('Failed to accept incoming e-invoice', [
                'e_invoice_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to accept e-invoice: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reject an incoming e-invoice with a reason.
     *
     * POST /api/v1/e-invoices/incoming/{id}/reject
     *
     * Requires a rejection_reason in the request body.
     */
    public function rejectIncoming(Request $request, int $id): JsonResponse
    {
        $this->authorize('create', EInvoice::class);

        $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        try {
            $eInvoice = EInvoice::whereCompany()
                ->inbound()
                ->findOrFail($id);

            // Validate status allows rejection
            if (! in_array($eInvoice->status, [EInvoice::STATUS_RECEIVED, EInvoice::STATUS_UNDER_REVIEW])) {
                return response()->json([
                    'success' => false,
                    'message' => 'E-invoice cannot be rejected in its current status: '.$eInvoice->status,
                ], 422);
            }

            $eInvoice->reviewed_by = auth()->id();
            $eInvoice->rejectIncoming($request->input('rejection_reason'));

            Log::info('Incoming e-invoice rejected', [
                'e_invoice_id' => $eInvoice->id,
                'reviewed_by' => auth()->id(),
                'rejection_reason' => $request->input('rejection_reason'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Incoming e-invoice rejected.',
                'data' => $eInvoice->fresh(['company:id,name', 'reviewedBy:id,name,email']),
            ]);
        } catch (Exception $e) {
            Log::error('Failed to reject incoming e-invoice', [
                'e_invoice_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reject e-invoice: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Trigger a manual poll of the UJP portal inbox for new invoices.
     *
     * POST /api/v1/e-invoices/incoming/poll
     *
     * Dispatches the PollEInvoiceInboxJob asynchronously.
     * Returns 202 Accepted immediately.
     */
    public function pollPortalInbox(): JsonResponse
    {
        $this->authorize('create', EInvoice::class);

        $companyId = auth()->user()->company_id ?? auth()->user()->company->id;

        dispatch(new PollEInvoiceInboxJob($companyId, auth()->id()))
            ->onQueue('einvoice');

        Log::info('Portal inbox poll dispatched', [
            'company_id' => $companyId,
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Portal inbox poll has been queued. New invoices will appear shortly.',
        ], 202);
    }

    /**
     * Check portal health status.
     * Pings the e-UJP portal to verify availability.
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
     * @param  string|null  $passphrase  Optional PFX passphrase
     * @return string Path to private key file
     *
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
     * @param  string|null  $passphrase  Optional PFX passphrase
     * @return string Path to certificate file
     *
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
     * @return array Formatted preview data
     */
    protected function formatXmlPreview(string $xml): array
    {
        $doc = new \DOMDocument;
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
// CLAUDE-CHECKPOINT
