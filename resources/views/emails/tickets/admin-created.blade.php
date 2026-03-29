@component('mail::message')
# New Support Ticket

A new support ticket has been submitted.

@component('mail::panel')
**Ticket #{{ $ticket->id }}**<br>
**Subject:** {{ $ticket->title }}<br>
**Priority:** {{ ucfirst($ticket->priority) }}<br>
**From:** {{ $ticket->user->name ?? 'Unknown' }} ({{ $ticket->user->email ?? '-' }})<br>
**Company:** {{ $ticket->company->name ?? 'N/A' }}<br>
**Created:** {{ $ticket->created_at->format('d M Y, H:i') }}
@endcomponent

### Message

{{ $ticket->message }}

@component('mail::button', ['url' => $ticketUrl, 'color' => 'primary'])
View Ticket
@endcomponent

Best regards,<br>
Facturino Support System
@endcomponent
