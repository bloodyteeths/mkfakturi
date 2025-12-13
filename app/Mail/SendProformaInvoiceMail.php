<?php

namespace App\Mail;

use App\Models\EmailLog;
use App\Models\ProformaInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Vinkla\Hashids\Facades\Hashids;

class SendProformaInvoiceMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public $data = [];

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $log = EmailLog::create([
            'from' => config('mail.from.address'),
            'to' => $this->data['to'],
            'subject' => $this->data['subject'],
            'body' => $this->data['body'],
            'mailable_type' => ProformaInvoice::class,
            'mailable_id' => $this->data['proforma_invoice']['id'],
        ]);

        $log->token = Hashids::connection(EmailLog::class)->encode($log->id);
        $log->save();

        $this->data['url'] = route('proforma-invoice', ['email_log' => $log->token]);

        // Use centralized Facturino email with company name
        $companyName = $this->data['company']['name'] ?? config('mail.from.name');
        $fromName = $companyName . ' преку Facturino';
        $replyTo = $this->data['from'] ?? config('mail.from.address');

        $mailContent = $this->from(config('mail.from.address'), $fromName)
            ->replyTo($replyTo, $companyName)
            ->subject($this->data['subject'])
            ->markdown('emails.send.proforma-invoice', ['data' => $this->data]);

        if ($this->data['attach']['data']) {
            $mailContent->attachData(
                $this->data['attach']['data']->output(),
                $this->data['proforma_invoice']['proforma_invoice_number'].'.pdf'
            );
        }

        return $mailContent;
    }
}
// CLAUDE-CHECKPOINT
