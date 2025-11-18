<?php

namespace App\Jobs;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class GenerateInvoicePdfJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(
        public int $invoiceId,
        public bool $deleteExistingFile = false
    ) {}

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function handle(): int
    {
        $invoice = Invoice::find($this->invoiceId);

        if (! $invoice) {
            return 0;
        }

        $invoice->generatePDF('invoice', $invoice->invoice_number, $this->deleteExistingFile);

        return 0;
    }
}
