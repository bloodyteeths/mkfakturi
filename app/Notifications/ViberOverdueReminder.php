<?php

namespace App\Notifications;

use App\Notifications\Channels\ViberChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Notification sent via Viber when an invoice is overdue.
 */
class ViberOverdueReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected string $invoiceNumber,
        protected string $amount,
        protected string $currency,
        protected int $daysOverdue,
        protected string $viewUrl
    ) {}

    public function via(object $notifiable): array
    {
        return [ViberChannel::class];
    }

    /**
     * @return array{text: string, button_text: string, button_url: string, event_type: string}
     */
    public function toViber(object $notifiable): array
    {
        return [
            'text' => "Потсетник: Фактура {$this->invoiceNumber} ({$this->amount} {$this->currency}) е доспеана пред {$this->daysOverdue} дена. Ве молиме извршете уплата.",
            'button_text' => 'Преглед на фактура',
            'button_url' => $this->viewUrl,
            'tracking_data' => "overdue_reminder:{$this->invoiceNumber}",
            'event_type' => 'overdue_reminder',
        ];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'invoice_number' => $this->invoiceNumber,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'days_overdue' => $this->daysOverdue,
            'type' => 'overdue_reminder',
        ];
    }
}
