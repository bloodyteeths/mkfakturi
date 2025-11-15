<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessInboundBillEmail implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $companyId;

    public string $from;

    public ?string $subject;

    /**
     * @var array<int, array<string, string>>
     */
    public array $attachments;

    /**
     * @param array<int, array<string, string>> $attachments
     */
    public function __construct(int $companyId, string $from, ?string $subject, array $attachments)
    {
        $this->companyId = $companyId;
        $this->from = $from;
        $this->subject = $subject;
        $this->attachments = $attachments;
    }

    public function handle(): void
    {
        foreach ($this->attachments as $attachment) {
            ParseInvoicePdfJob::dispatch(
                $this->companyId,
                $attachment['path'],
                $attachment['original_name'],
                $this->from,
                $this->subject
            );
        }

        Log::info('ProcessInboundBillEmail dispatched ParseInvoicePdfJob for attachments', [
            'company_id' => $this->companyId,
            'attachments' => $this->attachments,
        ]);
    }
}

