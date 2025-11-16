<?php

namespace App\Notifications;

use Coderflex\LaravelTicket\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Ticket $ticket;

    /**
     * Create a new notification instance.
     */
    public function __construct(Ticket $ticket)
    {
        $this->ticket = $ticket;
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
        $priorityLabel = ucfirst($this->ticket->priority);

        return (new MailMessage)
            ->subject("Support Ticket Created: {$this->ticket->title}")
            ->markdown('emails.tickets.created', [
                'ticket' => $this->ticket,
                'ticketUrl' => $ticketUrl,
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
            'ticket_priority' => $this->ticket->priority,
            'ticket_status' => $this->ticket->status,
        ];
    }
}

// CLAUDE-CHECKPOINT
