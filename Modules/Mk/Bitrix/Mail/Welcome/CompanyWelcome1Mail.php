<?php

namespace Modules\Mk\Bitrix\Mail\Welcome;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

// CLAUDE-CHECKPOINT
class CompanyWelcome1Mail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public string $name;
    public string $appUrl;

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->appUrl = config('app.url');
    }

    public function build()
    {
        return $this->from(config('mail.from.address'), 'Мериса Т. — Facturino')
            ->subject(__('welcome.company_1.subject'))
            ->markdown('emails.welcome.company_1', [
                'name' => $this->name,
                'appUrl' => $this->appUrl,
            ]);
    }
}
