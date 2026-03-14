<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateEstimatePdfJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $timeout = 300;  // 5 minutes

    public $tries = 3;

    public $backoff = [30, 60, 120];

    public $estimate;

    public $deleteExistingFile;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($estimate, $deleteExistingFile = false)
    {
        $this->estimate = $estimate;
        $this->deleteExistingFile = $deleteExistingFile;
    }

    /**
     * Execute the job.
     */
    public function handle(): int
    {
        $this->estimate->generatePDF('estimate', $this->estimate->estimate_number, $this->deleteExistingFile);

        return 0;
    }

    public function failed(\Throwable $exception): void
    {
        \Log::error('PDF generation failed', [
            'job' => static::class,
            'id' => $this->estimate->id ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}
// CLAUDE-CHECKPOINT
