<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CompanyReferralMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public string $inviteeEmail;
    public string $inviterCompanyName;
    public string $signupLink;

    /**
     * Create a new message instance.
     */
    public function __construct(string $inviteeEmail, string $inviterCompanyName, string $signupLink)
    {
        $this->inviteeEmail = $inviteeEmail;
        $this->inviterCompanyName = $inviterCompanyName;
        $this->signupLink = $signupLink;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // Use centralized Facturino email with company name
        $fromName = $this->inviterCompanyName . ' преку Facturino';

        return $this->from(config('mail.from.address'), $fromName)
            ->subject(__('company_referral.subject', ['company' => $this->inviterCompanyName]))
            ->markdown('emails.company-referral', [
                'inviterCompanyName' => $this->inviterCompanyName,
                'signupLink' => $this->signupLink,
            ]);
    }
}
// CLAUDE-CHECKPOINT
