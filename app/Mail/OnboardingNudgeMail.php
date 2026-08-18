<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Onboarding setup nudge — a warm, personal note from Tamara to companies that
 * signed up but haven't finished setting up, framed around the e-Faktura
 * mandate (2026-10-01). Sent on the Postmark `broadcast` stream (the default
 * `outbound` stream silently drops, and `outreach` is for cold campaigns).
 *
 * $variant selects the tailored middle paragraph:
 *   - 'first_invoice'    : signed up, no invoice yet → nudge to first invoice
 *   - 'complete_profile' : issued invoices but missing VAT/logo/bank → finish profile
 */
class OnboardingNudgeMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public string $companyName;
    public string $variant;
    public string $appUrl;

    public function __construct(string $companyName, string $variant = 'first_invoice')
    {
        $this->companyName = $companyName;
        $this->variant = in_array($variant, ['first_invoice', 'complete_profile'], true)
            ? $variant
            : 'first_invoice';
        $this->appUrl = config('app.url');
    }

    public function build()
    {
        $fromAddress = env('MAIL_PARTNER_FROM_ADDRESS', 'partneri@facturino.mk');

        return $this->from($fromAddress, 'Тамара — Facturino')
            ->replyTo($fromAddress, 'Тамара — Facturino')
            ->subject('Пред е-Фактура да стане задолжителна — да го завршиме поставувањето заедно')
            ->withSymfonyMessage(fn ($message) => $message->getHeaders()->addTextHeader('X-PM-Message-Stream', 'broadcast'))
            ->markdown('emails.onboarding.setup-nudge', [
                'companyName' => $this->companyName,
                'variant' => $this->variant,
                'appUrl' => $this->appUrl,
            ]);
    }
}
