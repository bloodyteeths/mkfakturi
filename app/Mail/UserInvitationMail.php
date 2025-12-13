<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserInvitationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public User $user;
    public string $token;
    public string $companyName;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, string $token, string $companyName)
    {
        $this->user = $user;
        $this->token = $token;
        $this->companyName = $companyName;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $setPasswordUrl = url('/reset-password/' . $this->token . '?email=' . urlencode($this->user->email));

        // Use centralized Facturino email
        $fromName = $this->companyName . ' преку Facturino';

        return $this->from(config('mail.from.address'), $fromName)
            ->subject(__('user_invitation.subject', ['company' => $this->companyName]))
            ->markdown('emails.user-invitation', [
                'user' => $this->user,
                'companyName' => $this->companyName,
                'setPasswordUrl' => $setPasswordUrl,
            ]);
    }
}
// CLAUDE-CHECKPOINT
