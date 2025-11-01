<?php

namespace App\Jobs;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class GeneratePaymentPdfJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(
        public int $paymentId,
        public bool $deleteExistingFile = false
    ) {
    }

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function handle(): int
    {
        $payment = Payment::find($this->paymentId);

        if (! $payment) {
            return 0;
        }

        $payment->generatePDF('payment', $payment->payment_number, $this->deleteExistingFile);

        return 0;
    }
}
