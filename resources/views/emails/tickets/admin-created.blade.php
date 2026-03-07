@component('mail::layout')
    {{-- Header --}}
    @slot('header')
        @component('mail::header', ['url' => ''])
            Facturino Support
        @endcomponent
    @endslot

    {{-- Body --}}
    <h2>New Support Ticket</h2>

    <p>A new support ticket has been submitted.</p>

    @component('mail::panel')
    **Ticket #{{ $ticket->id }}**<br>
    **Subject:** {{ $ticket->title }}<br>
    **Priority:** {{ ucfirst($ticket->priority) }}<br>
    **From:** {{ $ticket->user->name ?? 'Unknown' }} ({{ $ticket->user->email ?? '-' }})<br>
    **Company:** {{ $ticket->company->name ?? 'N/A' }}<br>
    **Created:** {{ $ticket->created_at->format('d M Y, H:i') }}
    @endcomponent

    <h3>Message</h3>
    <p style="white-space: pre-wrap;">{{ $ticket->message }}</p>

    @component('mail::button', ['url' => $ticketUrl, 'color' => 'primary'])
        View Ticket
    @endcomponent

    {{-- Footer --}}
    @slot('footer')
        @component('mail::footer')
            &copy; {{ date('Y') }} Facturino. All rights reserved.
        @endcomponent
    @endslot
@endcomponent
{{-- CLAUDE-CHECKPOINT --}}
