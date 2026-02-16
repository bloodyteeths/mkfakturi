<?php

namespace Modules\Mk\Bitrix\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Day 0 — Initial outreach to companies.
 * Focus: e-faktura compliance, time savings, Macedonian-specific features.
 */
class CompanyOutreachInitialMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public string $companyName;
    public string $contactEmail;
    public string $demoUrl;
    public string $unsubscribeUrl;

    public function __construct(
        string $companyName,
        string $contactEmail,
        string $demoUrl,
        string $unsubscribeUrl
    ) {
        $this->companyName = $companyName;
        $this->contactEmail = $contactEmail;
        $this->demoUrl = $demoUrl;
        $this->unsubscribeUrl = $unsubscribeUrl;
    }

    public function build()
    {
        return $this->from(config('mail.from.address'), 'Facturino')
            ->subject(__('outreach.company_initial.subject'))
            ->markdown('emails.outreach.company_initial', [
                'companyName' => $this->companyName,
                'demoUrl' => $this->demoUrl,
                'unsubscribeUrl' => $this->unsubscribeUrl,
            ]);
    }
}
// CLAUDE-CHECKPOINT
