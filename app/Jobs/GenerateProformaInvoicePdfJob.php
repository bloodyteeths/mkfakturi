<?php

namespace App\Jobs;

use App\Models\ProformaInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Generate Proforma Invoice PDF Job
 *
 * Background job to generate PDF for proforma invoices
 */
class GenerateProformaInvoicePdfJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public $timeout = 300;  // 5 minutes

    public $tries = 3;

    public $backoff = [30, 60, 120];

    /**
     * The proforma invoice ID
     */
    public int $proformaInvoiceId;

    /**
     * Whether to delete existing file
     */
    public bool $deleteExistingFile;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(int $proformaInvoiceId, bool $deleteExistingFile = false)
    {
        $this->proformaInvoiceId = $proformaInvoiceId;
        $this->deleteExistingFile = $deleteExistingFile;
    }

    /**
     * Execute the job.
     */
    public function handle(): int
    {
        $proformaInvoice = ProformaInvoice::find($this->proformaInvoiceId);

        if (! $proformaInvoice) {
            return 0;
        }

        $proformaInvoice->generatePDF('proforma_invoice', $proformaInvoice->proforma_invoice_number, $this->deleteExistingFile);

        return 0;
    }

    public function failed(\Throwable $exception): void
    {
        \Log::error('PDF generation failed', [
            'job' => static::class,
            'id' => $this->proformaInvoiceId ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}
// CLAUDE-CHECKPOINT

