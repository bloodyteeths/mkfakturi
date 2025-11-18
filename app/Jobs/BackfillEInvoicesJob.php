<?php

namespace App\Jobs;

use App\Models\EInvoice;
use App\Models\EInvoiceSubmission;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Backfill E-Invoices Job
 *
 * Migrates existing invoices to the e-invoice system.
 * Creates EInvoice records with status based on invoice age and status.
 *
 * Logic:
 * - Only processes invoices with status SENT/VIEWED/COMPLETED that don't have e-invoices yet
 * - Old invoices (>30 days + COMPLETED) → marked as 'ACCEPTED' (assume filed)
 * - Recent invoices → marked as 'DRAFT'
 * - Creates initial EInvoiceSubmission if status is 'ACCEPTED'
 * - Processes in chunks of 100 for memory efficiency
 * - Idempotent: skips if e-invoice already exists
 *
 * Queue: default
 */
class BackfillEInvoicesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The company ID to backfill (null = all companies)
     */
    protected ?int $companyId;

    /**
     * Number of days to consider as "old" for auto-accepting
     */
    protected int $oldInvoiceDays;

    /**
     * Dry run mode (log only, don't create records)
     */
    protected bool $dryRun;

    /**
     * Job timeout in seconds
     *
     * @var int
     */
    public $timeout = 3600; // 1 hour

    /**
     * Create a new job instance.
     *
     * @param  int|null  $companyId  Company to backfill (null = all companies)
     * @param  int  $oldInvoiceDays  Days to consider invoice as old (default: 30)
     * @param  bool  $dryRun  If true, only log what would be done
     */
    public function __construct(?int $companyId = null, int $oldInvoiceDays = 30, bool $dryRun = false)
    {
        $this->companyId = $companyId;
        $this->oldInvoiceDays = $oldInvoiceDays;
        $this->dryRun = $dryRun;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $mode = $this->dryRun ? '[DRY RUN]' : '';

        Log::info("$mode Starting e-invoice backfill", [
            'company_id' => $this->companyId,
            'old_invoice_days' => $this->oldInvoiceDays,
            'dry_run' => $this->dryRun,
        ]);

        $totalProcessed = 0;
        $totalCreated = 0;
        $totalSkipped = 0;
        $errors = [];

        // Build base query for invoices that need e-invoices
        $query = Invoice::whereIn('status', [
            Invoice::STATUS_SENT,
            Invoice::STATUS_VIEWED,
            Invoice::STATUS_COMPLETED,
        ])
            ->whereDoesntHave('eInvoice')
            ->with(['company:id,name', 'customer:id,name']);

        // Filter by company if specified
        if ($this->companyId !== null) {
            $query->where('company_id', $this->companyId);
        }

        // Get total count for progress logging
        $totalInvoices = $query->count();

        Log::info("$mode Found invoices to process", [
            'total' => $totalInvoices,
            'company_id' => $this->companyId,
        ]);

        // Process in chunks of 100 for memory efficiency
        $query->orderBy('id')
            ->chunk(100, function ($invoices) use (&$totalProcessed, &$totalCreated, &$totalSkipped, &$errors, $mode, $totalInvoices) {
                foreach ($invoices as $invoice) {
                    try {
                        $result = $this->processInvoice($invoice);

                        $totalProcessed++;

                        if ($result['created']) {
                            $totalCreated++;
                        } elseif ($result['skipped']) {
                            $totalSkipped++;
                        }

                        // Log progress every 50 invoices
                        if ($totalProcessed % 50 === 0) {
                            Log::info("$mode Backfill progress", [
                                'processed' => $totalProcessed,
                                'total' => $totalInvoices,
                                'created' => $totalCreated,
                                'skipped' => $totalSkipped,
                                'percentage' => round(($totalProcessed / $totalInvoices) * 100, 2),
                            ]);
                        }
                    } catch (\Exception $e) {
                        $errors[] = [
                            'invoice_id' => $invoice->id,
                            'invoice_number' => $invoice->invoice_number,
                            'error' => $e->getMessage(),
                        ];

                        Log::error("$mode Failed to backfill e-invoice", [
                            'invoice_id' => $invoice->id,
                            'invoice_number' => $invoice->invoice_number,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }
                }
            });

        Log::info("$mode E-invoice backfill completed", [
            'total_processed' => $totalProcessed,
            'total_created' => $totalCreated,
            'total_skipped' => $totalSkipped,
            'errors' => count($errors),
            'error_details' => $errors,
        ]);
    }

    /**
     * Process a single invoice and create e-invoice record
     *
     * @param  Invoice  $invoice  Invoice to process
     * @return array Result with 'created' and 'skipped' flags
     */
    protected function processInvoice(Invoice $invoice): array
    {
        // Double-check if e-invoice already exists (idempotent)
        $existingEInvoice = EInvoice::where('invoice_id', $invoice->id)->first();

        if ($existingEInvoice) {
            Log::debug('E-invoice already exists, skipping', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'e_invoice_id' => $existingEInvoice->id,
            ]);

            return ['created' => false, 'skipped' => true];
        }

        // Determine status based on invoice age and status
        $status = $this->determineEInvoiceStatus($invoice);

        $eInvoiceData = [
            'invoice_id' => $invoice->id,
            'company_id' => $invoice->company_id,
            'status' => $status,
            'ubl_xml' => null,
            'ubl_xml_signed' => null,
            'ubl_file_path' => null,
            'signed_file_path' => null,
            'certificate_id' => null,
            'signed_at' => null,
            'submitted_at' => null,
            'accepted_at' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
        ];

        // If marking as accepted, set timestamps
        if ($status === EInvoice::STATUS_ACCEPTED) {
            // Use invoice date as accepted date (approximation)
            $eInvoiceData['accepted_at'] = $invoice->invoice_date;
            $eInvoiceData['submitted_at'] = $invoice->invoice_date;
            $eInvoiceData['signed_at'] = $invoice->invoice_date;
        }

        if ($this->dryRun) {
            Log::info('[DRY RUN] Would create e-invoice', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'invoice_date' => $invoice->invoice_date->toDateString(),
                'invoice_status' => $invoice->status,
                'e_invoice_status' => $status,
                'company_id' => $invoice->company_id,
                'company_name' => $invoice->company->name ?? 'Unknown',
                'customer_name' => $invoice->customer->name ?? 'Unknown',
            ]);

            return ['created' => false, 'skipped' => false];
        }

        // Create e-invoice in a transaction
        DB::transaction(function () use ($eInvoiceData, $invoice, $status) {
            $eInvoice = EInvoice::create($eInvoiceData);

            // If accepted, create initial submission record
            if ($status === EInvoice::STATUS_ACCEPTED) {
                $this->createInitialSubmission($eInvoice, $invoice);
            }

            Log::info('Created e-invoice from backfill', [
                'e_invoice_id' => $eInvoice->id,
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'status' => $status,
                'company_id' => $invoice->company_id,
            ]);
        });

        return ['created' => true, 'skipped' => false];
    }

    /**
     * Determine e-invoice status based on invoice properties
     *
     * @param  Invoice  $invoice  Invoice to evaluate
     * @return string E-invoice status
     */
    protected function determineEInvoiceStatus(Invoice $invoice): string
    {
        // Check if invoice is old and completed
        $isOld = $invoice->invoice_date->lt(Carbon::now()->subDays($this->oldInvoiceDays));
        $isCompleted = $invoice->status === Invoice::STATUS_COMPLETED;

        // Old + completed invoices → assume they were filed and accepted
        if ($isOld && $isCompleted) {
            return EInvoice::STATUS_ACCEPTED;
        }

        // Everything else → draft (needs to be processed)
        return EInvoice::STATUS_DRAFT;
    }

    /**
     * Create initial submission record for accepted e-invoices
     *
     * @param  EInvoice  $eInvoice  E-invoice that was accepted
     * @param  Invoice  $invoice  Original invoice
     */
    protected function createInitialSubmission(EInvoice $eInvoice, Invoice $invoice): void
    {
        EInvoiceSubmission::create([
            'e_invoice_id' => $eInvoice->id,
            'company_id' => $invoice->company_id,
            'submitted_by' => null, // Unknown who submitted historically
            'submitted_at' => $invoice->invoice_date,
            'portal_url' => null,
            'receipt_number' => 'BACKFILL-'.$invoice->invoice_number, // Placeholder receipt
            'status' => EInvoiceSubmission::STATUS_ACCEPTED,
            'response_data' => [
                'backfill' => true,
                'original_invoice_date' => $invoice->invoice_date->toDateString(),
                'note' => 'Auto-created during backfill migration',
            ],
            'retry_count' => 0,
            'next_retry_at' => null,
            'error_message' => null,
        ]);

        Log::debug('Created initial submission for backfilled e-invoice', [
            'e_invoice_id' => $eInvoice->id,
            'invoice_id' => $invoice->id,
        ]);
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('E-invoice backfill job failed', [
            'company_id' => $this->companyId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}

// CLAUDE-CHECKPOINT
