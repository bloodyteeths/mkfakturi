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
}
