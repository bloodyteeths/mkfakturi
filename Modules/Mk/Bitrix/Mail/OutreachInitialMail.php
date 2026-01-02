<?php

namespace Modules\Mk\Bitrix\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Initial outreach email for Bitrix24 campaign.
 * First touch - permission-based with clear CTA.
 */
class OutreachInitialMail extends Mailable implements ShouldQueue
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
            ->subject(__('outreach.initial.subject', ['company' => $this->companyName]))
            ->markdown('emails.outreach.initial', [
                'companyName' => $this->companyName,
                'demoUrl' => $this->demoUrl,
                'unsubscribeUrl' => $this->unsubscribeUrl,
            ]);
    }
}
// CLAUDE-CHECKPOINT
