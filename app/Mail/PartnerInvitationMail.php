<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PartnerInvitationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public string $inviteeEmail;
    public string $companyName;
    public string $inviteLink;
    public array $permissions;

    /**
     * Create a new message instance.
     */
    public function __construct(string $inviteeEmail, string $companyName, string $inviteLink, array $permissions)
    {
        $this->inviteeEmail = $inviteeEmail;
        $this->companyName = $companyName;
        $this->inviteLink = $inviteLink;
        $this->permissions = $permissions;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // Use centralized Facturino email
        $fromName = $this->companyName . ' преку Facturino';

        return $this->from(config('mail.from.address'), $fromName)
            ->subject(__('partner_invitation.subject', ['company' => $this->companyName]))
            ->markdown('emails.partner-invitation', [
                'companyName' => $this->companyName,
                'inviteLink' => $this->inviteLink,
                'permissions' => $this->permissions,
            ]);
    }
}
// CLAUDE-CHECKPOINT
