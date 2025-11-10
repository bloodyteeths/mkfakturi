<?php

namespace App\Jobs;

use App\Models\CreditNote;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Generate Credit Note PDF Job
 *
 * Background job to generate PDF for credit notes.
 * Mirrors GenerateInvoicePdfJob pattern.
 *
 * @package App\Jobs
 */
class GenerateCreditNotePdfJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(
        public int $creditNoteId,
        public bool $deleteExistingFile = false
    ) {
    }

    /**
     * Execute the job.
     *
     * @return int
     */
    public function handle(): int
    {
        $creditNote = CreditNote::find($this->creditNoteId);

        if (! $creditNote) {
            return 0;
        }

        $creditNote->generatePDF('credit_note', $creditNote->credit_note_number, $this->deleteExistingFile);

        return 0;
    }
}

// CLAUDE-CHECKPOINT
