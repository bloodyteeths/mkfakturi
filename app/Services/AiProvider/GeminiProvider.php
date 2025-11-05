<?php

namespace App\Services\AiProvider;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Gemini AI Provider Implementation
 *
 * Integrates with Google's Generative Language API (Gemini) for AI insights.
 */
class GeminiProvider implements AiProviderInterface
{
    private string $apiKey;
    private string $model;
    private string $apiUrl;
    private int $maxTokens;
    private float $temperature;

    public function __construct()
    {
        $config = config('ai.providers.gemini');

        $this->apiKey = $config['api_key'] ?? '';
        $this->model = $config['model'] ?? 'gemini-pro';
        $this->apiUrl = rtrim($config['api_url'] ?? 'https://generativelanguage.googleapis.com/v1beta/models', '/');
        $this->maxTokens = $config['max_tokens'] ?? 2048;
        $this->temperature = $config['temperature'] ?? 0.7;

        if (empty($this->apiKey)) {
            throw new \RuntimeException('Gemini API key is not configured');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function generate(string $prompt, array $options = []): string
    {
        $maxTokens = $options['max_tokens'] ?? $this->maxTokens;
        $temperature = $options['temperature'] ?? $this->temperature;

        return $this->sendRequest([
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
        ], $maxTokens, $temperature, 'generate');
    }

    /**
     * {@inheritdoc}
     */
    public function chat(array $messages): string
    {
        $contents = [];
        foreach ($messages as $message) {
            $role = $message['role'] === 'assistant' ? 'model' : 'user';
            $contents[] = [
                'role' => $role,
                'parts' => [
                    ['text' => $message['content']],
                ],
            ];
        }

        return $this->sendRequest([
            'contents' => $contents,
        ], $this->maxTokens, $this->temperature, 'chat');
    }

    /**
     * Send request to Gemini API and return the text response.
     *
     * @param array<string, mixed> $payload
     * @param int $maxTokens
     * @param float $temperature
     * @param string $method
     * @return string
     *
     * @throws \Exception
     */
    private function sendRequest(
        array $payload,
        int $maxTokens,
        float $temperature,
        string $method
    ): string {
        $requestPayload = array_merge($payload, [
            'generationConfig' => [
                'temperature' => $temperature,
                'maxOutputTokens' => $maxTokens,
            ],
        ]);

        $url = sprintf(
            '%s/%s:generateContent?key=%s',
            $this->apiUrl,
            $this->model,
            $this->apiKey
        );

        $startTime = microtime(true);

        try {
            $response = Http::timeout(30)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, $requestPayload);

            $duration = microtime(true) - $startTime;

            if ($response->failed()) {
                $this->logApiCall($method, $requestPayload, null, $response->status(), $duration);
                throw new \Exception('Gemini API request failed: ' . $response->body());
            }

            $data = $response->json();
            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

            $this->logApiCall($method, $requestPayload, $text, 200, $duration, [
                'prompt_feedback' => $data['promptFeedback'] ?? null,
            ]);

            return $text;
        } catch (\Exception $e) {
            $this->logApiCall($method, $requestPayload, null, 0, microtime(true) - $startTime, [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function getProviderName(): string
    {
        return 'gemini';
    }

    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * @param array<string, mixed> $input
     * @param string|null $output
     * @param array<string, mixed> $metadata
     */
    private function logApiCall(
        string $method,
        array $input,
        ?string $output,
        int $statusCode,
        float $duration,
        array $metadata = []
    ): void {
        if (! config('ai.log_api_calls', true)) {
            return;
        }

        $payloadString = json_encode($input);

        $logData = [
            'provider' => 'gemini',
            'model' => $this->model,
            'method' => $method,
            'status_code' => $statusCode,
            'duration_seconds' => round($duration, 3),
            'input_length' => strlen($payloadString ?: ''),
            'output_length' => $output ? strlen($output) : 0,
            'timestamp' => now()->toDateTimeString(),
        ];

        if (! empty($metadata)) {
            $logData = array_merge($logData, $metadata);
        }

        Log::channel(config('ai.log_channel', 'stack'))->info('AI API Call', $logData);
    }
}
