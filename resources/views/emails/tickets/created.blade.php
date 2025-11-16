@component('mail::layout')
    {{-- Header --}}
    @slot('header')
        @component('mail::header', ['url' => ''])
            Facturino Support
        @endcomponent
    @endslot

    {{-- Body --}}
    <h2>Support Ticket Created</h2>

    <p>Hello {{ $notifiable->name }},</p>

    <p>Thank you for contacting Facturino support. We have received your support ticket and our team will respond as soon as possible.</p>

    @component('mail::panel')
    **Ticket ID:** {{ $ticket->uuid }}<br>
    **Subject:** {{ $ticket->title }}<br>
    **Priority:** {{ ucfirst($ticket->priority) }}<br>
    **Status:** {{ ucfirst($ticket->status) }}<br>
    **Created:** {{ $ticket->created_at->format('d M Y, H:i') }}
    @endcomponent

    <h3>Your Message</h3>
    <p style="white-space: pre-wrap;">{{ $ticket->message }}</p>

    @if($ticket->categories && count($ticket->categories) > 0)
        <h3>Categories</h3>
        <p>
            @foreach($ticket->categories as $category)
                <span style="display: inline-block; padding: 4px 12px; margin: 2px; background-color: #f3f4f6; border-radius: 4px;">{{ $category->name }}</span>
            @endforeach
        </p>
    @endif

    <h3>What Happens Next?</h3>
    <ul>
        <li>Our support team will review your ticket</li>
        <li>You will receive updates via email</li>
        <li>You can view your ticket and add replies using the button below</li>
    </ul>

    @if($ticket->priority === 'urgent')
        <p style="color: #dc2626; font-weight: bold;">⚠️ Your ticket has been marked as URGENT and will be prioritized by our team.</p>
    @elseif($ticket->priority === 'high')
        <p style="color: #ea580c; font-weight: bold;">Your ticket has been marked as HIGH priority and will be addressed promptly.</p>
    @endif

    {{-- Button --}}
    @component('mail::button', ['url' => $ticketUrl, 'color' => 'primary'])
        View Ticket
    @endcomponent

    <p style="font-size: 12px; color: #6b7280;">
        You can also access your ticket at any time from your Facturino dashboard under Support.
    </p>

    {{-- Footer --}}
    @slot('footer')
        @component('mail::footer')
            &copy; {{ date('Y') }} Facturino. All rights reserved.
        @endcomponent
    @endslot
@endcomponent
{{-- CLAUDE-CHECKPOINT --}}
