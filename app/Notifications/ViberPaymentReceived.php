<?php

namespace App\Notifications;

use App\Notifications\Channels\ViberChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Notification sent via Viber when a payment is received for an invoice.
 */
class ViberPaymentReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected string $invoiceNumber,
        protected string $amount,
        protected string $currency
    ) {}

    public function via(object $notifiable): array
    {
        return [ViberChannel::class];
    }

    /**
     * @return array{text: string, event_type: string}
     */
    public function toViber(object $notifiable): array
    {
        return [
            'text' => "Примена уплата од {$this->amount} {$this->currency} за фактура {$this->invoiceNumber}. Ви благодариме!",
            'tracking_data' => "payment_received:{$this->invoiceNumber}",
            'event_type' => 'payment_received',
        ];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'invoice_number' => $this->invoiceNumber,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'type' => 'payment_received',
        ];
    }
}
// CLAUDE-CHECKPOINT
