@component('mail::message')
# New Support Contact Submission

A new support inquiry has been submitted. Here are the details:

@component('mail::panel')
**Reference Number:** {{ $contact->reference_number }}<br>
**Date:** {{ $contact->created_at->format('d M Y, H:i') }}<br>
**Priority:** {{ $contact->priority_name }}<br>
**Category:** {{ $contact->category_name }}
@endcomponent

### Contact Information

**Name:** {{ $contact->name }}<br>
**Email:** {{ $contact->email }}<br>
@if($contact->company_name)
**Company:** {{ $contact->company_name }}<br>
@endif
@if($contact->user_id)
**User ID:** {{ $contact->user_id }}
@endif

### Subject

{{ $contact->subject }}

### Message

{{ $contact->message }}

@if($contact->attachments && count($contact->attachments) > 0)
### Attachments

@foreach($contact->attachments as $attachment)
- {{ $attachment['name'] }} ({{ $attachment['size'] }})
@endforeach
@endif

@if(isset($viewUrl))
@component('mail::button', ['url' => $viewUrl, 'color' => 'primary'])
View in Admin Panel
@endcomponent
@endif

Thanks,<br>
Facturino Support System
@endcomponent
