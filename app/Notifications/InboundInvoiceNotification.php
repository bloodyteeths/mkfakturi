<?php

namespace App\Notifications;

use App\Models\Bill;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InboundInvoiceNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Bill $bill;

    public function __construct(Bill $bill)
    {
        $this->bill = $bill;
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $billUrl = url("/admin/bills/{$this->bill->id}/view");
        $supplierName = $this->bill->supplier?->name ?? __('bills.unknown_supplier', [], 'en');
        $billNumber = $this->bill->bill_number ?? '-';

        return (new MailMessage)
            ->subject(__('bills.inbound_notification_subject', ['number' => $billNumber]))
            ->greeting(__('bills.inbound_notification_greeting'))
            ->line(__('bills.inbound_notification_line1', [
                'supplier' => $supplierName,
                'number' => $billNumber,
            ]))
            ->line(__('bills.inbound_notification_line2'))
            ->action(__('bills.inbound_notification_action'), $billUrl)
            ->withSymfonyMessage(function ($message) {
                $message->getHeaders()->addTextHeader('X-PM-Message-Stream', 'broadcast');
            });
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'bill_id' => $this->bill->id,
            'bill_number' => $this->bill->bill_number,
            'supplier' => $this->bill->supplier?->name,
        ];
    }
}
// CLAUDE-CHECKPOINT
