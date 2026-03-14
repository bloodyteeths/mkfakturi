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
 */
class GenerateCreditNotePdfJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public $timeout = 300;  // 5 minutes

    public $tries = 3;

    public $backoff = [30, 60, 120];

    public function __construct(
        public int $creditNoteId,
        public bool $deleteExistingFile = false
    ) {}

    /**
     * Execute the job.
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

    public function failed(\Throwable $exception): void
    {
        \Log::error('PDF generation failed', [
            'job' => static::class,
            'id' => $this->creditNoteId ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}
// CLAUDE-CHECKPOINT

