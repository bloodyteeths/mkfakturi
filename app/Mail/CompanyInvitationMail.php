<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CompanyInvitationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public string $inviteeEmail;
    public string $partnerName;
    public string $signupLink;

    /**
     * Create a new message instance.
     */
    public function __construct(string $inviteeEmail, string $partnerName, string $signupLink)
    {
        $this->inviteeEmail = $inviteeEmail;
        $this->partnerName = $partnerName;
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
            ->subject(__('company_invitation.subject', ['partner' => $this->partnerName]))
            ->markdown('emails.company-invitation', [
                'partnerName' => $this->partnerName,
                'signupLink' => $this->signupLink,
            ]);
    }
}
// CLAUDE-CHECKPOINT
