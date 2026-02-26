<?php

namespace Modules\Mk\Bitrix\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Day 14 follow-up email — free trial CTA with pricing.
 * Targets leads who haven't responded to the first 3 emails.
 * Focus: concrete offer (14-day free trial, no credit card).
 */
class OutreachFollowUp3Mail extends Mailable implements ShouldQueue
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
     * @param string $signupUrl URL to signup with trial
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
        return $this->from('partners@facturino.mk', 'Facturino')
            ->subject(__('outreach.followup3.subject'))
            ->view('emails.outreach.followup_3', [
                'companyName' => $this->companyName,
                'signupUrl' => $this->signupUrl,
                'unsubscribeUrl' => $this->unsubscribeUrl,
            ]);
    }
}
