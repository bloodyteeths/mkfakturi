<?php

namespace App\Notifications;

use App\Models\Payout;
use App\Models\Partner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PayoutCalculated extends Notification implements ShouldQueue
{
    use Queueable;

    protected Payout $payout;
    protected Partner $partner;

    /**
     * Create a new notification instance.
     */
    public function __construct(Payout $payout, Partner $partner)
    {
        $this->payout = $payout;
        $this->partner = $partner;
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
        $monthRef = $this->payout->details['month_ref'] ?? 'N/A';
        $eventCount = $this->payout->details['event_count'] ?? 0;

        return (new MailMessage)
            ->subject('Affiliate Payout Scheduled - Facturino Partner')
            ->greeting('Hello ' . $this->partner->name . ',')
            ->line('Your affiliate payout has been calculated and scheduled.')
            ->line('**Payout Details:**')
            ->line("Month: {$monthRef}")
            ->line("Amount: €" . number_format($this->payout->amount, 2))
            ->line("Commission events: {$eventCount}")
            ->line("Payment method: Bank Transfer")
            ->line("Expected payment date: " . $this->payout->payout_date->format('F d, Y'))
            ->line('**Bank Account:**')
            ->line("IBAN: {$this->partner->bank_account}")
            ->line('The funds will be transferred to your registered bank account within 3-5 business days.')
            ->action('View Payout Details', url('/partner/payouts/' . $this->payout->id))
            ->line('If you have any questions about your payout, please contact our support team.')
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
            'payout_id' => $this->payout->id,
            'partner_id' => $this->partner->id,
            'amount' => $this->payout->amount,
            'payout_date' => $this->payout->payout_date->toIso8601String(),
        ];
    }
}

// CLAUDE-CHECKPOINT
