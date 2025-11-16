@component('mail::layout')
    {{-- Header --}}
    @slot('header')
        @component('mail::header', ['url' => ''])
            Facturino Support
        @endcomponent
    @endslot

    {{-- Body --}}
    <h2>Support Ticket Closed</h2>

    <p>Hello {{ $notifiable->name }},</p>

    @if($wasResolved)
        <p>We're glad to inform you that your support ticket has been resolved and closed.</p>
    @else
        <p>Your support ticket has been closed.</p>
    @endif

    @component('mail::panel')
    **Ticket ID:** {{ $ticket->uuid }}<br>
    **Subject:** {{ $ticket->title }}<br>
    **Status:** Closed<br>
    **Resolution:** {{ $wasResolved ? 'Resolved' : 'Closed without resolution' }}<br>
    **Closed on:** {{ $ticket->updated_at->format('d M Y, H:i') }}
    @endcomponent

    @if($wasResolved)
        <h3>Your Issue Has Been Resolved</h3>
        <p>Our support team has successfully resolved your issue. We hope you're satisfied with the resolution.</p>

        <h3>Need Further Assistance?</h3>
        <p>If you have any additional questions or if the issue persists, you can:</p>
        <ul>
            <li>Reply to this ticket to reopen it</li>
            <li>Create a new support ticket</li>
            <li>Contact our support team directly</li>
        </ul>
    @else
        <h3>Ticket Closed</h3>
        <p>This ticket has been closed. If you need further assistance with this issue, you can reopen the ticket by clicking the button below and adding a new reply.</p>
    @endif

    {{-- Button --}}
    @component('mail::button', ['url' => $ticketUrl, 'color' => 'primary'])
        View Ticket
    @endcomponent

    @if($wasResolved)
        <h3>We Value Your Feedback</h3>
        <p>Your feedback helps us improve our service. If you have a moment, we'd appreciate your thoughts on how we handled your ticket.</p>
    @endif

    <p style="font-size: 12px; color: #6b7280;">
        Thank you for using Facturino. We're here to help whenever you need us.
    </p>

    {{-- Footer --}}
    @slot('footer')
        @component('mail::footer')
            &copy; {{ date('Y') }} Facturino. All rights reserved.
        @endcomponent
    @endslot
@endcomponent
{{-- CLAUDE-CHECKPOINT --}}
