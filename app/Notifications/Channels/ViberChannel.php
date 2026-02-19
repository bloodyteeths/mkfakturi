<?php

namespace App\Notifications\Channels;

use App\Models\CompanySetting;
use App\Services\ViberNotificationService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Custom Laravel notification channel for Viber Business messages.
 *
 * Three-layer check:
 *  1. Platform enabled (super-admin global setting)
 *  2. Tenant opted in (company_settings.viber_opt_in = 'YES')
 *  3. Customer has viber_phone set
 *
 * Usage in notification class:
 *   public function via($notifiable) { return ['mail', ViberChannel::class]; }
 *   public function toViber($notifiable) { return ['text' => '...', 'event_type' => 'invoice_sent', ...]; }
 */
class ViberChannel
{
    public function __construct(
        protected ViberNotificationService $viber
    ) {}

    /**
     * Send the given notification via Viber.
     *
     * @param  mixed  $notifiable  Customer model (has routeNotificationForViber + company_id)
     * @param  Notification  $notification  Must have toViber() method
     */
    public function send(mixed $notifiable, Notification $notification): void
    {
        // 1. Platform-level check
        if (! $this->viber->isEnabled()) {
            return;
        }

        // 2. Tenant opt-in check
        $companyId = $notifiable->company_id ?? request()->header('company');
        if ($companyId) {
            $optIn = CompanySetting::where('company_id', $companyId)
                ->where('option', 'viber_opt_in')
                ->value('value');

            if ($optIn !== 'YES') {
                return;
            }
        }

        // 3. Customer viber phone check
        $viberId = $notifiable->routeNotificationFor('viber');

        if (empty($viberId)) {
            return;
        }

        $data = $notification->toViber($notifiable);

        if (empty($data['text'])) {
            return;
        }

        // 4. Event-type check (super-admin can disable specific events globally)
        $eventType = $data['event_type'] ?? null;
        if ($eventType && ! $this->viber->isEventAllowed($eventType)) {
            return;
        }

        if (! empty($data['button_text']) && ! empty($data['button_url'])) {
            $result = $this->viber->sendRichMessage(
                $viberId,
                $data['text'],
                $data['button_text'],
                $data['button_url']
            );
        } else {
            $result = $this->viber->sendMessage(
                $viberId,
                $data['text'],
                $data['tracking_data'] ?? null
            );
        }

        if (! $result['success']) {
            Log::warning('ViberChannel: Failed to send notification', [
                'notifiable_type' => get_class($notifiable),
                'notifiable_id' => $notifiable->getKey(),
                'error' => $result['error'],
            ]);
        }
    }
}
