@component('mail::layout')
    {{-- Header --}}
    @slot('header')
        @component('mail::header', ['url' => ''])
            Facturino Support
        @endcomponent
    @endslot

    {{-- Body --}}
    <h2>New Support Contact Submission</h2>

    <p>A new support inquiry has been submitted. Here are the details:</p>

    @component('mail::panel')
    **Reference Number:** {{ $contact->reference_number }}<br>
    **Date:** {{ $contact->created_at->format('d M Y, H:i') }}<br>
    **Priority:** {{ $contact->priority_name }}<br>
    **Category:** {{ $contact->category_name }}
    @endcomponent

    <h3>Contact Information</h3>
    <p>
        <strong>Name:</strong> {{ $contact->name }}<br>
        <strong>Email:</strong> {{ $contact->email }}<br>
        @if($contact->company_name)
            <strong>Company:</strong> {{ $contact->company_name }}<br>
        @endif
        @if($contact->user_id)
            <strong>User ID:</strong> {{ $contact->user_id }}<br>
        @endif
    </p>

    <h3>Subject</h3>
    <p>{{ $contact->subject }}</p>

    <h3>Message</h3>
    <p style="white-space: pre-wrap;">{{ $contact->message }}</p>

    @if($contact->attachments && count($contact->attachments) > 0)
        <h3>Attachments</h3>
        <ul>
            @foreach($contact->attachments as $attachment)
                <li>{{ $attachment['name'] }} ({{ $attachment['size'] }})</li>
            @endforeach
        </ul>
    @endif

    {{-- Button --}}
    @if(isset($viewUrl))
        @component('mail::button', ['url' => $viewUrl, 'color' => 'primary'])
            View in Admin Panel
        @endcomponent
    @endif

    {{-- Footer --}}
    @slot('footer')
        @component('mail::footer')
            &copy; {{ date('Y') }} Facturino. All rights reserved.
        @endcomponent
    @endslot
@endcomponent
{{-- CLAUDE-CHECKPOINT --}}
