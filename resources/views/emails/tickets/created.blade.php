@component('mail::message')
# Support Ticket Created

Hello {{ $notifiable->name }},

Thank you for contacting Facturino support. We have received your support ticket and our team will respond as soon as possible.

@component('mail::panel')
**Ticket #{{ $ticket->id }}** ({{ $ticket->uuid }})<br>
**Subject:** {{ $ticket->title }}<br>
**Priority:** {{ ucfirst($ticket->priority) }}<br>
**Status:** {{ ucfirst($ticket->status) }}<br>
**Created:** {{ $ticket->created_at->format('d M Y, H:i') }}
@endcomponent

### Your Message

{{ $ticket->message }}

@if($ticket->categories && count($ticket->categories) > 0)
### Categories

@foreach($ticket->categories as $category)
- {{ $category->name }}
@endforeach
@endif

### What Happens Next?

- Our support team will review your ticket
- You will receive updates via email
- You can view your ticket and add replies using the button below

@if($ticket->priority === 'urgent')
**⚠️ Your ticket has been marked as URGENT and will be prioritized by our team.**
@elseif($ticket->priority === 'high')
**Your ticket has been marked as HIGH priority and will be addressed promptly.**
@endif

@component('mail::button', ['url' => $ticketUrl, 'color' => 'primary'])
View Ticket
@endcomponent

You can also access your ticket at any time from your Facturino dashboard under Support.

Best regards,<br>
The Facturino Support Team
@endcomponent
