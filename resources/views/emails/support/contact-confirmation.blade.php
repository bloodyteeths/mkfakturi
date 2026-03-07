@component('mail::message')
# Thank You for Contacting Facturino Support

Hi {{ $contact->name }},

We've received your support inquiry and our team will get back to you as soon as possible.

@component('mail::panel')
**Reference Number:** {{ $contact->reference_number }}<br>
**Subject:** {{ $contact->subject }}<br>
**Priority:** {{ $contact->priority_name }}<br>
**Expected Response Time:** {{ $responseTime }} hours
@endcomponent

### Your Message

{{ $contact->message }}

@if($contact->attachments && count($contact->attachments) > 0)
### Attachments Received

@foreach($contact->attachments as $attachment)
- {{ $attachment['name'] }}
@endforeach
@endif

Please use the reference number **{{ $contact->reference_number }}** in any future correspondence about this inquiry.

If you need to add more information, you can reply to this email or submit a new inquiry through our support form.

@if(isset($supportUrl))
@component('mail::button', ['url' => $supportUrl, 'color' => 'primary'])
Contact Support Again
@endcomponent
@endif

Best regards,<br>
The Facturino Support Team
@endcomponent
