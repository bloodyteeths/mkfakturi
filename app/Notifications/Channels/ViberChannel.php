<?php

namespace App\Notifications\Channels;

use App\Services\ViberNotificationService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Custom Laravel notification channel for Viber Business messages.
 *
 * Usage in notification class:
 *   public function via($notifiable) { return ['mail', ViberChannel::class]; }
 *   public function toViber($notifiable) { return ['text' => '...', 'button_text' => '...', 'button_url' => '...']; }
 */
class ViberChannel
{
    public function __construct(
        protected ViberNotificationService $viber
    ) {}

    /**
     * Send the given notification via Viber.
     *
     * @param  mixed  $notifiable  Must have routeNotificationForViber() method
     * @param  Notification  $notification  Must have toViber() method
     */
    public function send(mixed $notifiable, Notification $notification): void
    {
        if (! $this->viber->isEnabled()) {
            return;
        }

        $viberId = $notifiable->routeNotificationFor('viber');

        if (empty($viberId)) {
            return;
        }

        $data = $notification->toViber($notifiable);

        if (empty($data['text'])) {
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
// CLAUDE-CHECKPOINT
