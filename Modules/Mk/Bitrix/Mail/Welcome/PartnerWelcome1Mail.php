<?php

namespace Modules\Mk\Bitrix\Mail\Welcome;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

// CLAUDE-CHECKPOINT
class PartnerWelcome1Mail extends Mailable implements ShouldQueue
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
        return $this->from('partners@facturino.mk', 'Мериса Т. — Facturino')
            ->subject(__('welcome.partner_1.subject'))
            ->withSymfonyMessage(fn ($message) => $message->getHeaders()->addTextHeader('X-PM-Message-Stream', 'broadcast'))
            ->markdown('emails.welcome.partner_1', [
                'name' => $this->name,
                'appUrl' => $this->appUrl,
            ]);
    }
}
