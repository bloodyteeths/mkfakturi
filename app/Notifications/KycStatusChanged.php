<?php

namespace App\Notifications;

use App\Models\Partner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class KycStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    protected Partner $partner;
    protected string $status;
    protected ?string $rejectionReason;

    /**
     * Create a new notification instance.
     */
    public function __construct(Partner $partner, string $status, ?string $rejectionReason = null)
    {
        $this->partner = $partner;
        $this->status = $status;
        $this->rejectionReason = $rejectionReason;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        if ($this->status === 'verified') {
            return $this->buildVerifiedEmail($notifiable);
        } elseif ($this->status === 'rejected') {
            return $this->buildRejectedEmail($notifiable);
        }

        // Fallback for pending or unknown status
        return $this->buildPendingEmail($notifiable);
    }

    /**
     * Build email for verified KYC
     */
    protected function buildVerifiedEmail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('KYC Verification Approved - Facturino Partner')
            ->greeting('Congratulations, ' . $this->partner->name . '!')
            ->line('Your KYC verification has been approved.')
            ->line('You are now eligible to receive payouts from your affiliate commissions.')
            ->line('**Next Steps:**')
            ->line('1. Continue referring companies to Facturino')
            ->line('2. Track your earnings in the Partner Dashboard')
            ->line('3. Receive monthly payouts (minimum €100)')
            ->action('View Partner Dashboard', url('/partner/dashboard'))
            ->line('If you have any questions, please contact our support team.')
            ->salutation('Best regards, The Facturino Team');
    }

    /**
     * Build email for rejected KYC
     */
    protected function buildRejectedEmail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('KYC Verification Requires Attention - Facturino Partner')
            ->greeting('Hello ' . $this->partner->name . ',')
            ->line('Unfortunately, we were unable to approve your KYC verification at this time.');

        if ($this->rejectionReason) {
            $message->line('**Reason for rejection:**')
                ->line($this->rejectionReason);
        }

        return $message
            ->line('**What to do next:**')
            ->line('1. Review the rejection reason above')
            ->line('2. Prepare corrected documents')
            ->line('3. Re-submit your KYC documents')
            ->action('Re-submit KYC Documents', url('/partner/kyc'))
            ->line('If you have any questions, please contact our support team.')
            ->salutation('Best regards, The Facturino Team');
    }

    /**
     * Build email for pending KYC
     */
    protected function buildPendingEmail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('KYC Documents Received - Facturino Partner')
            ->greeting('Hello ' . $this->partner->name . ',')
            ->line('We have received your KYC documents.')
            ->line('Our team will review them within 24-48 business hours.')
            ->line('You will receive another email once your verification is complete.')
            ->action('View KYC Status', url('/partner/kyc'))
            ->line('Thank you for your patience.')
            ->salutation('Best regards, The Facturino Team');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'partner_id' => $this->partner->id,
            'partner_name' => $this->partner->name,
            'kyc_status' => $this->status,
            'rejection_reason' => $this->rejectionReason,
        ];
    }
}

// CLAUDE-CHECKPOINT
