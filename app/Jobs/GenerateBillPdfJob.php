<?php

namespace App\Jobs;

use App\Models\Bill;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateBillPdfJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $timeout = 300;  // 5 minutes

    public $tries = 3;

    public $backoff = [30, 60, 120];

    public int $billId;

    public bool $regenerate;

    public function __construct(int $billId, bool $regenerate = false)
    {
        $this->billId = $billId;
        $this->regenerate = $regenerate;
    }

    public function handle(): void
    {
        $bill = Bill::find($this->billId);

        if (! $bill) {
            return;
        }

        $bill->getPDFData();
    }

    public function failed(\Throwable $exception): void
    {
        \Log::error('PDF generation failed', [
            'job' => static::class,
            'id' => $this->billId ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}
// CLAUDE-CHECKPOINT
