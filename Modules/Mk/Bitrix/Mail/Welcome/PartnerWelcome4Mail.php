<?php

namespace Modules\Mk\Bitrix\Mail\Welcome;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

// CLAUDE-CHECKPOINT
class PartnerWelcome4Mail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public string $name;
    public string $ctaUrl;

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->ctaUrl = config('app.url') . '/admin/partner/commissions';
    }

    public function build()
    {
        return $this->from('partners@facturino.mk', 'Мериса Т. — Facturino')
            ->subject(__('welcome.partner_4.subject'))
            ->markdown('emails.welcome.partner_4', [
                'name' => $this->name,
                'ctaUrl' => $this->ctaUrl,
            ]);
    }
}
