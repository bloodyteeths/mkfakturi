@component('mail::message')
# Reply to Your Support Request

Hi {{ $contact->name }},

Our team has responded to your support request **{{ $contact->reference_number }}**.

### Your Original Message

> {{ $contact->message }}

### Our Response

{{ $contact->admin_reply }}

---

If you need further assistance, please reply to this email or submit a new support request.

Best regards,<br>
The Facturino Support Team
@endcomponent
