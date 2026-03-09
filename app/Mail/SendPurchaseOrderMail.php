<?php

namespace App\Mail;

use App\Models\EmailLog;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\Mk\Models\PurchaseOrder;

class SendPurchaseOrderMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function build()
    {
        $log = EmailLog::create([
            'from' => config('mail.from.address'),
            'to' => $this->data['to'],
            'subject' => $this->data['subject'],
            'body' => $this->data['body'],
            'mailable_type' => PurchaseOrder::class,
            'mailable_id' => $this->data['purchase_order']['id'],
        ]);

        $companyName = $this->data['company']['name'] ?? config('mail.from.name');
        $fromName = $companyName . ' преку Facturino';
        $replyTo = $this->data['from'] ?? config('mail.from.address');

        $mail = $this->from(config('mail.from.address'), $fromName)
            ->replyTo($replyTo, $companyName)
            ->subject($this->data['subject'])
            ->markdown('emails.send.purchase-order', ['data' => $this->data])
            ->withSymfonyMessage(function ($message) {
                $message->getHeaders()->addTextHeader('X-PM-Message-Stream', 'broadcast');
            });

        // Attach the PDF if provided
        if (!empty($this->data['pdf_content']) && !empty($this->data['pdf_filename'])) {
            $mail->attachData(
                $this->data['pdf_content'],
                $this->data['pdf_filename'],
                ['mime' => 'application/pdf']
            );
        }

        return $mail;
    }
}

// CLAUDE-CHECKPOINT
