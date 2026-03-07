<?php

namespace App\Mail;

use App\Models\Customer;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\Mk\Models\ReminderTemplate;

class PaymentReminder extends Mailable
{
    use Queueable;
    use SerializesModels;

    public Invoice $invoice;

    public Customer $customer;

    public ReminderTemplate $template;

    public string $level;

    public string $locale;

    /**
     * Create a new message instance.
     */
    public function __construct(
        Invoice $invoice,
        Customer $customer,
        ReminderTemplate $template,
        string $level,
        string $locale = 'mk'
    ) {
        $this->invoice = $invoice;
        $this->customer = $customer;
        $this->template = $template;
        $this->level = $level;
        $this->locale = $locale;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $company = $this->invoice->company;
        $companyName = $company->name ?? config('mail.from.name');
        $fromName = $companyName . ' преку Facturino';

        // Resolve subject and body for locale
        $subject = $this->resolveTemplate($this->template->getSubjectForLocale($this->locale));
        $body = $this->resolveTemplate($this->template->getBodyForLocale($this->locale));

        // Calculate days overdue
        $dueDate = $this->invoice->due_date instanceof \DateTimeInterface
            ? Carbon::parse($this->invoice->due_date)
            : Carbon::parse((string) $this->invoice->due_date);

        return $this->from(config('mail.from.address'), $fromName)
            ->subject($subject)
            ->view('emails.payment-reminder', [
                'invoice' => $this->invoice,
                'customer' => $this->customer,
                'company' => $company,
                'body' => $body,
                'level' => $this->level,
                'daysOverdue' => $dueDate->diffInDays(Carbon::today()),
            ])
            ->withSymfonyMessage(function ($message) {
                $message->getHeaders()->addTextHeader('X-PM-Message-Stream', 'broadcast');
            });
    }

    /**
     * Replace template placeholders with actual values.
     */
    protected function resolveTemplate(string $text): string
    {
        $dueDate = $this->invoice->due_date instanceof \DateTimeInterface
            ? Carbon::parse($this->invoice->due_date)
            : Carbon::parse((string) $this->invoice->due_date);

        $daysOverdue = $dueDate->diffInDays(Carbon::today());

        $replacements = [
            '{INVOICE_NUMBER}' => $this->invoice->invoice_number ?? '',
            '{AMOUNT_DUE}' => number_format(($this->invoice->due_amount ?? 0) / 100, 2, '.', ','),
            '{DUE_DATE}' => $dueDate->format('d.m.Y'),
            '{DAYS_OVERDUE}' => (string) $daysOverdue,
            '{CUSTOMER_NAME}' => $this->customer->name ?? '',
            '{COMPANY_NAME}' => $this->invoice->company->name ?? '',
            '{TOTAL}' => number_format(($this->invoice->total ?? 0) / 100, 2, '.', ','),
        ];

        return strtr($text, $replacements);
    }
}

// CLAUDE-CHECKPOINT
