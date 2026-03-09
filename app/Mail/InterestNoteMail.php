<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InterestNoteMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public array $noteData;

    protected $pdf;

    public function __construct(array $noteData, $pdf)
    {
        $this->noteData = $noteData;
        $this->pdf = $pdf;
    }

    public function build()
    {
        $company = $this->noteData['company'];
        $companyName = $company->name ?? config('mail.from.name');
        $fromName = $companyName . ' преку Facturino';

        return $this->from(config('mail.from.address'), $fromName)
            ->subject("Каматна нота - {$companyName}")
            ->view('emails.interest-note', [
                'company' => $company,
                'customer' => $this->noteData['customer'],
                'totalInterest' => $this->noteData['total_interest'],
                'grandTotal' => $this->noteData['grand_total'],
                'currencySymbol' => $this->noteData['currency_symbol'],
                'noteNumber' => $this->noteData['note_number'],
                'calculationCount' => count($this->noteData['calculation_ids']),
            ])
            ->attachData(
                $this->pdf->output(),
                "kamatna-nota-{$this->noteData['note_number']}.pdf",
                ['mime' => 'application/pdf']
            )
            ->withSymfonyMessage(function ($message) {
                $message->getHeaders()->addTextHeader('X-PM-Message-Stream', 'broadcast');
            });
    }
}
