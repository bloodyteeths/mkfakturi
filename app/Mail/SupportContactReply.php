<?php

namespace App\Mail;

use App\Models\SupportContact;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class SupportContactReply extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public SupportContact $contact
    ) {}

    public function headers(): Headers
    {
        return new Headers(
            text: [
                'X-PM-Message-Stream' => 'broadcast',
            ],
        );
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Re: '.$this->contact->subject.' - '.$this->contact->reference_number,
            replyTo: [config('support.email', 'partners@facturino.mk')],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.support.contact-reply',
            with: [
                'contact' => $this->contact,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
