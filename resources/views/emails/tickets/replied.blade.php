@component('mail::message')
# New Reply on Your Support Ticket

Hello {{ $notifiable->name }},

@if($isAgentReply)
Our support team has replied to your ticket. Here's what they said:
@else
A new reply has been added to your support ticket.
@endif

@component('mail::panel')
**Ticket #{{ $ticket->id }}**<br>
**Subject:** {{ $ticket->title }}<br>
**Status:** {{ ucfirst($ticket->status) }}<br>
**Priority:** {{ ucfirst($ticket->priority) }}<br>
**Replied by:** {{ $message->user->name ?? 'Support Team' }}<br>
**Replied at:** {{ $message->created_at->format('d M Y, H:i') }}
@endcomponent

### Message

> {{ $message->message }}

@if($isAgentReply)
You can view the full conversation and reply to this message by clicking the button below.
@else
You can view the full conversation by clicking the button below.
@endif

@component('mail::button', ['url' => $ticketUrl, 'color' => 'primary'])
View Full Conversation
@endcomponent

@if($ticket->status === 'resolved' || $ticket->status === 'closed')
**Note:** This ticket is currently marked as {{ $ticket->status }}. Replying to it will reopen the ticket.
@endif

You can reply directly from your Facturino dashboard or by viewing the ticket using the button above.

Best regards,<br>
The Facturino Support Team
@endcomponent
