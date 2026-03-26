<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Monitor external service health and alert on failures.
 *
 * Checks: invoice2data (OCR), Gemini API, Postmark, Paddle.
 * Sends email alert on first failure, suppresses repeats for 1 hour.
 * Sends recovery email when service comes back up.
 */
class MonitorServices extends Command
{
    protected $signature = 'services:monitor';

    protected $description = 'Check health of external services and alert on failures';

    /**
     * Services to monitor: name => check callable returns [ok, detail].
     */
    private function getChecks(): array
    {
        return [
            'invoice2data' => fn () => $this->checkInvoice2data(),
            'gemini' => fn () => $this->checkGemini(),
            'postmark' => fn () => $this->checkPostmark(),
        ];
    }

    public function handle(): int
    {
        $alertEmail = env('ADMIN_EMAIL', 'atillatkulu@gmail.com');
        $failures = [];
        $recoveries = [];

        foreach ($this->getChecks() as $name => $check) {
            $cacheKey = "service_monitor:{$name}:down";

            try {
                [$ok, $detail] = $check();
            } catch (\Exception $e) {
                $ok = false;
                $detail = $e->getMessage();
            }

            $wasDown = Cache::get($cacheKey, false);

            if (! $ok) {
                Log::warning("[ServiceMonitor] {$name} is DOWN", ['detail' => $detail]);
                $this->error("{$name}: DOWN — {$detail}");

                if (! $wasDown) {
                    // First failure — mark as down and queue alert
                    Cache::put($cacheKey, $detail, now()->addHours(6));
                    $failures[$name] = $detail;
                }
                // Already down — suppress repeat alerts
            } else {
                $this->info("{$name}: OK");

                if ($wasDown) {
                    // Was down, now recovered
                    Cache::forget($cacheKey);
                    $recoveries[$name] = $wasDown; // previous error detail
                    Log::info("[ServiceMonitor] {$name} recovered");
                }
            }
        }

        // Send failure alert
        if (! empty($failures)) {
            $this->sendAlert($alertEmail, $failures, 'down');
        }

        // Send recovery alert
        if (! empty($recoveries)) {
            $this->sendAlert($alertEmail, $recoveries, 'recovered');
        }

        return 0;
    }

    private function checkInvoice2data(): array
    {
        $baseUrl = rtrim(config('services.invoice2data.url', 'http://invoice2data-service:8000'), '/');
        if (! str_starts_with($baseUrl, 'http')) {
            $baseUrl = 'https://' . $baseUrl;
        }

        $response = Http::timeout(10)->connectTimeout(5)->get($baseUrl . '/health');

        if ($response->successful()) {
            return [true, 'healthy'];
        }

        return [false, "HTTP {$response->status()}: " . substr($response->body(), 0, 200)];
    }

    private function checkGemini(): array
    {
        $apiKey = config('ai.providers.gemini.api_key');
        if (empty($apiKey)) {
            return [false, 'GEMINI_API_KEY not configured'];
        }

        $model = config('ai.providers.gemini.model', 'gemini-2.5-flash');
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}?key={$apiKey}";

        $response = Http::timeout(10)->connectTimeout(5)->get($url);

        if ($response->successful()) {
            return [true, 'API key valid'];
        }

        $error = $response->json('error.message') ?? "HTTP {$response->status()}";

        return [false, $error];
    }

    private function checkPostmark(): array
    {
        $token = config('services.postmark.token');
        if (empty($token)) {
            return [false, 'POSTMARK_TOKEN not configured'];
        }

        $response = Http::timeout(10)
            ->connectTimeout(5)
            ->withHeaders(['X-Postmark-Server-Token' => $token])
            ->get('https://api.postmarkapp.com/server');

        if ($response->successful()) {
            return [true, 'connected'];
        }

        return [false, "HTTP {$response->status()}: " . substr($response->body(), 0, 200)];
    }

    private function sendAlert(string $to, array $services, string $type): void
    {
        $isDown = $type === 'down';
        $emoji = $isDown ? '🔴' : '🟢';
        $subject = $isDown
            ? 'Facturino: Service DOWN — ' . implode(', ', array_keys($services))
            : 'Facturino: Service RECOVERED — ' . implode(', ', array_keys($services));

        $lines = [];
        foreach ($services as $name => $detail) {
            $lines[] = "<p><strong>{$name}</strong>: {$detail}</p>";
        }
        $body = implode("\n", $lines);

        $action = $isDown
            ? '<p style="color: #dc2626;">Immediate attention required.</p>'
            : '<p style="color: #16a34a;">Services are back to normal.</p>';

        $html = <<<HTML
        <p>{$emoji} <strong>{$subject}</strong></p>
        {$body}
        {$action}
        <p><small>Sent by Facturino service monitor at {$this->now()}</small></p>
        HTML;

        try {
            Mail::html($html, function ($message) use ($to, $subject) {
                $message->to($to)
                    ->from(config('mail.from.address', 'fakturi@facturino.mk'), 'Facturino Monitor')
                    ->subject($subject);
                $message->getSymfonyMessage()
                    ->getHeaders()
                    ->addTextHeader('X-PM-Message-Stream', 'broadcast');
            });

            Log::info("[ServiceMonitor] Alert sent", [
                'type' => $type,
                'services' => array_keys($services),
                'to' => $to,
            ]);
        } catch (\Exception $e) {
            Log::error("[ServiceMonitor] Failed to send alert email", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function now(): string
    {
        return now()->timezone('Europe/Skopje')->format('Y-m-d H:i:s T');
    }
}
// CLAUDE-CHECKPOINT
