<?php

namespace App\Mail;

use App\Models\SupportContact;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SupportContactConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public SupportContact $contact,
        public int $responseTime = 48,
        public ?string $supportUrl = null
    ) {
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Thank you for contacting Facturino Support - ' . $this->contact->reference_number,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.support.contact-confirmation',
            with: [
                'contact' => $this->contact,
                'responseTime' => $this->responseTime,
                'supportUrl' => $this->supportUrl,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
// CLAUDE-CHECKPOINT
