@component('mail::message')
# Support Ticket Closed

Hello {{ $notifiable->name }},

@if($wasResolved)
We're glad to inform you that your support ticket has been resolved and closed.
@else
Your support ticket has been closed.
@endif

@component('mail::panel')
**Ticket #{{ $ticket->id }}**<br>
**Subject:** {{ $ticket->title }}<br>
**Status:** Closed<br>
**Resolution:** {{ $wasResolved ? 'Resolved' : 'Closed without resolution' }}<br>
**Closed on:** {{ $ticket->updated_at->format('d M Y, H:i') }}
@endcomponent

@if($wasResolved)
### Your Issue Has Been Resolved

Our support team has successfully resolved your issue. We hope you're satisfied with the resolution.

### Need Further Assistance?

If you have any additional questions or if the issue persists, you can:

- Reply to this ticket to reopen it
- Create a new support ticket
- Contact our support team directly
@else
### Ticket Closed

This ticket has been closed. If you need further assistance with this issue, you can reopen the ticket by clicking the button below and adding a new reply.
@endif

@component('mail::button', ['url' => $ticketUrl, 'color' => 'primary'])
View Ticket
@endcomponent

@if($wasResolved)
### We Value Your Feedback

Your feedback helps us improve our service. If you have a moment, we'd appreciate your thoughts on how we handled your ticket.
@endif

Thank you for using Facturino. We're here to help whenever you need us.

Best regards,<br>
The Facturino Support Team
@endcomponent
