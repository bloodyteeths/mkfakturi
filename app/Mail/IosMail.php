<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class IosMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public Company $company;

    public Customer $customer;

    protected string $pdfContent;

    /**
     * Create a new message instance.
     */
    public function __construct(Company $company, Customer $customer, string $pdfContent)
    {
        $this->company = $company;
        $this->customer = $customer;
        $this->pdfContent = $pdfContent;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $companyName = $this->company->name ?? config('mail.from.name');
        $fromName = $companyName . ' преку Facturino';

        return $this->from(config('mail.from.address'), $fromName)
            ->subject('Извод на отворени ставки - ' . $companyName)
            ->html(
                '<p>Почитувани,</p>' .
                '<p>Ве молиме потврдете го следниов извод на отворени ставки.</p>' .
                '<p>Доколку имате прашања, контактирајте нè.</p>' .
                '<p>Со почит,<br>' . e($companyName) . '</p>'
            )
            ->attachData($this->pdfContent, 'ИОС-' . e($this->customer->name) . '.pdf', [
                'mime' => 'application/pdf',
            ])
            ->withSymfonyMessage(function ($message) {
                $message->getHeaders()->addTextHeader('X-PM-Message-Stream', 'broadcast');
            });
    }
}

// CLAUDE-CHECKPOINT
