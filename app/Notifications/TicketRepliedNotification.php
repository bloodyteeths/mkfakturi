<?php

namespace App\Notifications;

use Coderflex\LaravelTicket\Models\Message;
use Coderflex\LaravelTicket\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketRepliedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Ticket $ticket;
    protected Message $message;
    protected bool $isAgentReply;

    /**
     * Create a new notification instance.
     */
    public function __construct(Ticket $ticket, Message $message, bool $isAgentReply = false)
    {
        $this->ticket = $ticket;
        $this->message = $message;
        $this->isAgentReply = $isAgentReply;
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
            ->subject("New Reply on Support Ticket: {$this->ticket->title}")
            ->markdown('emails.tickets.replied', [
                'ticket' => $this->ticket,
                'message' => $this->message,
                'ticketUrl' => $ticketUrl,
                'isAgentReply' => $this->isAgentReply,
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
            'message_id' => $this->message->id,
            'message_preview' => substr($this->message->message, 0, 100),
            'is_agent_reply' => $this->isAgentReply,
            'replied_by' => $this->message->user->name ?? 'Support',
        ];
    }
}

// CLAUDE-CHECKPOINT
