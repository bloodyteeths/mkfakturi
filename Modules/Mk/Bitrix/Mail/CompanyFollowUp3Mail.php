<?php

namespace Modules\Mk\Bitrix\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Day 14 — Company follow-up: e-faktura deadline urgency.
 */
class CompanyFollowUp3Mail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public string $companyName;
    public string $contactEmail;
    public string $signupUrl;
    public string $unsubscribeUrl;

    public function __construct(
        string $companyName,
        string $contactEmail,
        string $signupUrl,
        string $unsubscribeUrl
    ) {
        $this->companyName = $companyName;
        $this->contactEmail = $contactEmail;
        $this->signupUrl = $signupUrl;
        $this->unsubscribeUrl = $unsubscribeUrl;
    }

    public function build()
    {
        return $this->from(config('mail.from.address'), 'Facturino')
            ->subject(__('outreach.company_followup3.subject'))
            ->view('emails.outreach.company_followup_3', [
                'companyName' => $this->companyName,
                'signupUrl' => $this->signupUrl,
                'unsubscribeUrl' => $this->unsubscribeUrl,
            ]);
    }
}
