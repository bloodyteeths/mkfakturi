@component('mail::layout')
    {{-- Header --}}
    @slot('header')
        @component('mail::header', ['url' => ''])
            Facturino Support
        @endcomponent
    @endslot

    {{-- Body --}}
    <h2>Support Ticket Status Updated</h2>

    <p>Hello {{ $notifiable->name }},</p>

    <p>The status of your support ticket has been updated.</p>

    @component('mail::panel')
    **Ticket ID:** {{ $ticket->uuid }}<br>
    **Subject:** {{ $ticket->title }}<br>
    **Previous Status:** <span style="text-decoration: line-through;">{{ ucfirst($oldStatus) }}</span><br>
    **New Status:** <strong style="color: #059669;">{{ ucfirst($newStatus) }}</strong><br>
    **Priority:** {{ ucfirst($ticket->priority) }}<br>
    **Updated:** {{ $ticket->updated_at->format('d M Y, H:i') }}
    @endcomponent

    @if($newStatus === 'in_progress')
        <h3>Your Ticket is Being Handled</h3>
        <p>Our support team is now actively working on your ticket. You can expect a response soon.</p>
    @elseif($newStatus === 'resolved')
        <h3>Your Ticket Has Been Resolved</h3>
        <p>Our support team has marked your ticket as resolved. If you believe the issue is not fully resolved, you can reopen the ticket by replying to it.</p>
    @elseif($newStatus === 'open')
        <h3>Your Ticket is Open</h3>
        <p>Your ticket has been reopened and is awaiting assignment to a support agent.</p>
    @endif

    @if($ticket->assignedToUser)
        <p><strong>Assigned to:</strong> {{ $ticket->assignedToUser->name }}</p>
    @endif

    {{-- Button --}}
    @component('mail::button', ['url' => $ticketUrl, 'color' => 'primary'])
        View Ticket
    @endcomponent

    <p style="font-size: 12px; color: #6b7280;">
        You will receive email notifications for any updates to your ticket.
    </p>

    {{-- Footer --}}
    @slot('footer')
        @component('mail::footer')
            &copy; {{ date('Y') }} Facturino. All rights reserved.
        @endcomponent
    @endslot
@endcomponent
{{-- CLAUDE-CHECKPOINT --}}
