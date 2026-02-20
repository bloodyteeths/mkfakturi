<?php

namespace Modules\Mk\Bitrix\Mail\Welcome;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

// CLAUDE-CHECKPOINT
class CompanyWelcome3Mail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public string $name;
    public string $ctaUrl;

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->ctaUrl = config('app.url') . '/admin/settings/e-faktura';
    }

    public function build()
    {
        return $this->from(config('mail.from.address'), 'Мериса Т. — Facturino')
            ->subject(__('welcome.company_3.subject'))
            ->markdown('emails.welcome.company_3', [
                'name' => $this->name,
                'ctaUrl' => $this->ctaUrl,
            ]);
    }
}
