@component('mail::message')
# Support Ticket Status Updated

Hello {{ $notifiable->name }},

The status of your support ticket has been updated.

@component('mail::panel')
**Ticket #{{ $ticket->id }}**<br>
**Subject:** {{ $ticket->title }}<br>
**Previous Status:** ~~{{ ucfirst($oldStatus) }}~~<br>
**New Status:** **{{ ucfirst($newStatus) }}**<br>
**Priority:** {{ ucfirst($ticket->priority) }}<br>
**Updated:** {{ $ticket->updated_at->format('d M Y, H:i') }}
@endcomponent

@if($newStatus === 'in_progress')
### Your Ticket is Being Handled

Our support team is now actively working on your ticket. You can expect a response soon.
@elseif($newStatus === 'resolved')
### Your Ticket Has Been Resolved

Our support team has marked your ticket as resolved. If you believe the issue is not fully resolved, you can reopen the ticket by replying to it.
@elseif($newStatus === 'open')
### Your Ticket is Open

Your ticket has been reopened and is awaiting assignment to a support agent.
@endif

@if($ticket->assignedToUser)
**Assigned to:** {{ $ticket->assignedToUser->name }}
@endif

@component('mail::button', ['url' => $ticketUrl, 'color' => 'primary'])
View Ticket
@endcomponent

You will receive email notifications for any updates to your ticket.

Best regards,<br>
The Facturino Support Team
@endcomponent
