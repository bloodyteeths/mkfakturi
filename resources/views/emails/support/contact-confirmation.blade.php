@component('mail::layout')
    {{-- Header --}}
    @slot('header')
        @component('mail::header', ['url' => ''])
            Facturino Support
        @endcomponent
    @endslot

    {{-- Body --}}
    <h2>Thank You for Contacting Facturino Support</h2>

    <p>Hi {{ $contact->name }},</p>

    <p>We've received your support inquiry and our team will get back to you as soon as possible.</p>

    @component('mail::panel')
    **Reference Number:** {{ $contact->reference_number }}<br>
    **Subject:** {{ $contact->subject }}<br>
    **Priority:** {{ $contact->priority_name }}<br>
    **Expected Response Time:** {{ $responseTime }} hours
    @endcomponent

    <h3>Your Message</h3>
    <p style="white-space: pre-wrap;">{{ $contact->message }}</p>

    @if($contact->attachments && count($contact->attachments) > 0)
        <h3>Attachments Received</h3>
        <ul>
            @foreach($contact->attachments as $attachment)
                <li>{{ $attachment['name'] }}</li>
            @endforeach
        </ul>
    @endif

    <p>Please use the reference number <strong>{{ $contact->reference_number }}</strong> in any future correspondence about this inquiry.</p>

    <p>If you need to add more information, you can reply to this email or submit a new inquiry through our support form.</p>

    {{-- Button --}}
    @if(isset($supportUrl))
        @component('mail::button', ['url' => $supportUrl, 'color' => 'primary'])
            Contact Support Again
        @endcomponent
    @endif

    <p>
        Best regards,<br>
        The Facturino Support Team
    </p>

    {{-- Footer --}}
    @slot('footer')
        @component('mail::footer')
            &copy; {{ date('Y') }} Facturino. All rights reserved.<br>
            Need help? Visit <a href="https://facturino.mk">facturino.mk</a>
        @endcomponent
    @endslot
@endcomponent
{{-- CLAUDE-CHECKPOINT --}}
