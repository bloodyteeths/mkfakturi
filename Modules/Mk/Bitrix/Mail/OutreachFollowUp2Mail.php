<?php

namespace Modules\Mk\Bitrix\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Day 7 final follow-up email for Bitrix24 campaign.
 * For leads who opened/clicked - offers extended trial.
 */
class OutreachFollowUp2Mail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public string $companyName;
    public string $contactEmail;
    public string $signupUrl;
    public string $unsubscribeUrl;

    /**
     * Create a new message instance.
     *
     * @param string $companyName Lead company name
     * @param string $contactEmail Lead contact email
     * @param string $signupUrl URL to signup with extended trial
     * @param string $unsubscribeUrl URL to unsubscribe
     */
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

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from(config('mail.from.address'), 'Facturino')
            ->subject(__('outreach.followup2.subject'))
            ->markdown('emails.outreach.followup_2', [
                'companyName' => $this->companyName,
                'signupUrl' => $this->signupUrl,
                'unsubscribeUrl' => $this->unsubscribeUrl,
            ]);
    }
}
// CLAUDE-CHECKPOINT
