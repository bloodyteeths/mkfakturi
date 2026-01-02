<?php

namespace Modules\Mk\Bitrix\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Partner invitation email (transactional).
 * Sent when a new partner is approved for the program.
 */
class PartnerInviteMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public string $partnerName;
    public string $partnerEmail;
    public string $activationUrl;

    /**
     * Create a new message instance.
     *
     * @param string $partnerName Partner's name
     * @param string $partnerEmail Partner's email
     * @param string $activationUrl URL to set password/complete profile
     */
    public function __construct(
        string $partnerName,
        string $partnerEmail,
        string $activationUrl
    ) {
        $this->partnerName = $partnerName;
        $this->partnerEmail = $partnerEmail;
        $this->activationUrl = $activationUrl;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from(config('mail.from.address'), 'Facturino')
            ->subject(__('outreach.partner_invite.subject'))
            ->markdown('emails.outreach.partner-invite', [
                'partnerName' => $this->partnerName,
                'activationUrl' => $this->activationUrl,
            ]);
    }
}
// CLAUDE-CHECKPOINT
