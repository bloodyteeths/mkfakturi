<?php

namespace App\Notifications;

use Coderflex\LaravelTicket\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketClosedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Ticket $ticket;

    protected bool $wasResolved;

    /**
     * Create a new notification instance.
     */
    public function __construct(Ticket $ticket, bool $wasResolved = true)
    {
        $this->ticket = $ticket;
        $this->wasResolved = $wasResolved;
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
        $ticketUrl = url("/admin/support/{$this->ticket->uuid}");

        return (new MailMessage)
            ->subject("Support Ticket Closed: {$this->ticket->title}")
            ->markdown('emails.tickets.closed', [
                'ticket' => $this->ticket,
                'ticketUrl' => $ticketUrl,
                'wasResolved' => $this->wasResolved,
                'notifiable' => $notifiable,
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'ticket_uuid' => $this->ticket->uuid,
            'ticket_title' => $this->ticket->title,
            'was_resolved' => $this->wasResolved,
            'closed_at' => $this->ticket->updated_at,
        ];
    }
}

// CLAUDE-CHECKPOINT
