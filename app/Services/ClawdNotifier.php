<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ClawdNotifier — pushes real-time events to the Clawd AI assistant.
 *
 * POSTs JSON to the configured webhook URL with X-Monitor-Token auth.
 * Wrapped in try/catch so it NEVER breaks calling code.
 */
class ClawdNotifier
{
    public static function push(string $type, array $data = []): void
    {
        $url = config('services.clawd.webhook_url');
        $token = config('services.clawd.monitor_token');

        if (! $url) {
            return;
        }

        try {
            Http::withHeaders(['X-Monitor-Token' => $token ?? ''])
                ->timeout(5)
                ->post($url, array_merge([
                    'app' => 'facturino',
                    'type' => $type,
                    'at' => now()->toIso8601String(),
                ], $data));
        } catch (\Throwable $e) {
            Log::debug('ClawdNotifier: push failed', [
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
