@component('mail::layout')
    {{-- Header --}}
    @slot('header')
        @component('mail::header', ['url' => ''])
            Facturino Support
        @endcomponent
    @endslot

    {{-- Body --}}
    <h2>New Reply on Your Support Ticket</h2>

    <p>Hello {{ $notifiable->name }},</p>

    @if($isAgentReply)
        <p>Our support team has replied to your ticket. Here's what they said:</p>
    @else
        <p>A new reply has been added to your support ticket.</p>
    @endif

    @component('mail::panel')
    **Ticket ID:** {{ $ticket->uuid }}<br>
    **Subject:** {{ $ticket->title }}<br>
    **Status:** {{ ucfirst($ticket->status) }}<br>
    **Priority:** {{ ucfirst($ticket->priority) }}<br>
    **Replied by:** {{ $message->user->name ?? 'Support Team' }}<br>
    **Replied at:** {{ $message->created_at->format('d M Y, H:i') }}
    @endcomponent

    <h3>Message</h3>
    <div style="background-color: #f9fafb; border-left: 4px solid #3b82f6; padding: 16px; margin: 16px 0;">
        <p style="white-space: pre-wrap; margin: 0;">{{ $message->message }}</p>
    </div>

    @if($isAgentReply)
        <h3>What to Do Next?</h3>
        <p>You can view the full conversation and reply to this message by clicking the button below.</p>
    @else
        <p>You can view the full conversation by clicking the button below.</p>
    @endif

    {{-- Button --}}
    @component('mail::button', ['url' => $ticketUrl, 'color' => 'primary'])
        View Full Conversation
    @endcomponent

    @if($ticket->status === 'resolved' || $ticket->status === 'closed')
        <p style="font-size: 12px; color: #dc2626; margin-top: 16px;">
            <strong>Note:</strong> This ticket is currently marked as {{ $ticket->status }}. Replying to it will reopen the ticket.
        </p>
    @endif

    <p style="font-size: 12px; color: #6b7280;">
        You can reply directly from your Facturino dashboard or by viewing the ticket using the button above.
    </p>

    {{-- Footer --}}
    @slot('footer')
        @component('mail::footer')
            &copy; {{ date('Y') }} Facturino. All rights reserved.
        @endcomponent
    @endslot
@endcomponent
{{-- CLAUDE-CHECKPOINT --}}
