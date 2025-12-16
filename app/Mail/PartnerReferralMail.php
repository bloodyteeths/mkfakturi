<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PartnerReferralMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public string $inviteeEmail;
    public string $inviterPartnerName;
    public string $signupLink;

    /**
     * Create a new message instance.
     */
    public function __construct(string $inviteeEmail, string $inviterPartnerName, string $signupLink)
    {
        $this->inviteeEmail = $inviteeEmail;
        $this->inviterPartnerName = $inviterPartnerName;
        $this->signupLink = $signupLink;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // Use centralized Facturino email
        $fromName = 'Facturino';

        return $this->from(config('mail.from.address'), $fromName)
            ->subject(__('partner_referral.subject', ['partner' => $this->inviterPartnerName]))
            ->markdown('emails.partner-referral', [
                'inviterPartnerName' => $this->inviterPartnerName,
                'signupLink' => $this->signupLink,
            ]);
    }
}
// CLAUDE-CHECKPOINT
