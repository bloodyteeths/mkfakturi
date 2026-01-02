<?php

namespace Modules\Mk\Bitrix\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Day 3 follow-up email for Bitrix24 campaign.
 * Shorter, references first email with success metric.
 */
class OutreachFollowUp1Mail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public string $companyName;
    public string $contactEmail;
    public string $demoUrl;
    public string $unsubscribeUrl;

    /**
     * Create a new message instance.
     *
     * @param string $companyName Lead company name
     * @param string $contactEmail Lead contact email
     * @param string $demoUrl URL to demo/landing page
     * @param string $unsubscribeUrl URL to unsubscribe
     */
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

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from(config('mail.from.address'), 'Facturino')
            ->subject(__('outreach.followup1.subject'))
            ->markdown('emails.outreach.followup_1', [
                'companyName' => $this->companyName,
                'demoUrl' => $this->demoUrl,
                'unsubscribeUrl' => $this->unsubscribeUrl,
            ]);
    }
}
// CLAUDE-CHECKPOINT
