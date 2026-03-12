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
     * @param  array<int, array<string, string>>  $attachments
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
        Log::info('ProcessInboundBillEmail: starting', [
            'company_id' => $this->companyId,
            'from' => $this->from,
            'subject' => $this->subject,
            'attachment_count' => count($this->attachments),
        ]);

        foreach ($this->attachments as $attachment) {
            Log::info('ProcessInboundBillEmail: dispatching ParseInvoicePdfJob', [
                'company_id' => $this->companyId,
                'path' => $attachment['path'],
                'name' => $attachment['original_name'],
            ]);

            ParseInvoicePdfJob::dispatch(
                $this->companyId,
                $attachment['path'],
                $attachment['original_name'],
                $this->from,
                $this->subject,
                $attachment['content_type'] ?? 'application/pdf'
            );
        }
    }
}
