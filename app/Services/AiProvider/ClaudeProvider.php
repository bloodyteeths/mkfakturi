<?php

namespace App\Services\AiProvider;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Claude AI Provider Implementation
 *
 * Integrates with Anthropic's Claude API for AI-powered insights generation.
 */
class ClaudeProvider implements AiProviderInterface
{
    private string $apiKey;
    private string $model;
    private string $apiUrl;
    private string $apiVersion;
    private int $maxTokens;
    private float $temperature;

    /**
     * Create a new Claude provider instance
     */
    public function __construct()
    {
        $config = config('ai.providers.claude');

        $this->apiKey = $config['api_key'] ?? '';
        $this->model = $config['model'] ?? 'claude-3-5-sonnet-20241022';
        $this->apiUrl = $config['api_url'] ?? 'https://api.anthropic.com/v1/messages';
        $this->apiVersion = $config['api_version'] ?? '2023-06-01';
        $this->maxTokens = $config['max_tokens'] ?? 4096;
        $this->temperature = $config['temperature'] ?? 0.7;

        if (empty($this->apiKey)) {
            throw new \RuntimeException('Claude API key is not configured');
        }
    }

    /**
     * Generate a response from a single prompt
     *
     * @param string $prompt The prompt to send to Claude
     * @param array<string, mixed> $options Additional options
     * @return string The AI's response
     * @throws \Exception If the API call fails
     */
    public function generate(string $prompt, array $options = []): string
    {
        $maxTokens = $options['max_tokens'] ?? $this->maxTokens;
        $temperature = $options['temperature'] ?? $this->temperature;

        $startTime = microtime(true);

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => $this->apiVersion,
                'content-type' => 'application/json',
            ])
            ->timeout(30)
            ->post($this->apiUrl, [
                'model' => $this->model,
                'max_tokens' => $maxTokens,
                'temperature' => $temperature,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
            ]);

            if ($response->failed()) {
                $this->logApiCall('generate', $prompt, null, $response->status(), microtime(true) - $startTime);
                throw new \Exception('Claude API request failed: ' . $response->body());
            }

            $data = $response->json();
            $text = $data['content'][0]['text'] ?? '';

            $this->logApiCall('generate', $prompt, $text, 200, microtime(true) - $startTime, [
                'input_tokens' => $data['usage']['input_tokens'] ?? 0,
                'output_tokens' => $data['usage']['output_tokens'] ?? 0,
            ]);

            return $text;

        } catch (\Exception $e) {
            $this->logApiCall('generate', $prompt, null, 0, microtime(true) - $startTime, ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Generate a response from a conversation with multiple messages
     *
     * @param array<int, array{role: string, content: string}> $messages Array of messages
     * @return string The AI's response
     * @throws \Exception If the API call fails
     */
    public function chat(array $messages): string
    {
        $startTime = microtime(true);

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => $this->apiVersion,
                'content-type' => 'application/json',
            ])
            ->timeout(30)
            ->post($this->apiUrl, [
                'model' => $this->model,
                'max_tokens' => $this->maxTokens,
                'temperature' => $this->temperature,
                'messages' => $messages,
            ]);

            if ($response->failed()) {
                $this->logApiCall('chat', json_encode($messages), null, $response->status(), microtime(true) - $startTime);
                throw new \Exception('Claude API chat request failed: ' . $response->body());
            }

            $data = $response->json();
            $text = $data['content'][0]['text'] ?? '';

            $this->logApiCall('chat', json_encode($messages), $text, 200, microtime(true) - $startTime, [
                'input_tokens' => $data['usage']['input_tokens'] ?? 0,
                'output_tokens' => $data['usage']['output_tokens'] ?? 0,
            ]);

            return $text;

        } catch (\Exception $e) {
            $this->logApiCall('chat', json_encode($messages), null, 0, microtime(true) - $startTime, ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Get the provider name
     *
     * @return string
     */
    public function getProviderName(): string
    {
        return 'claude';
    }

    /**
     * Get the model being used
     *
     * @return string
     */
    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * Log API call for cost tracking and monitoring
     *
     * @param string $method The method called (generate or chat)
     * @param string $input The input prompt or messages
     * @param string|null $output The AI response
     * @param int $statusCode HTTP status code
     * @param float $duration Duration in seconds
     * @param array<string, mixed> $metadata Additional metadata
     */
    private function logApiCall(
        string $method,
        string $input,
        ?string $output,
        int $statusCode,
        float $duration,
        array $metadata = []
    ): void {
        if (! config('ai.log_api_calls', true)) {
            return;
        }

        $logData = [
            'provider' => 'claude',
            'model' => $this->model,
            'method' => $method,
            'status_code' => $statusCode,
            'duration_seconds' => round($duration, 3),
            'input_length' => strlen($input),
            'output_length' => $output ? strlen($output) : 0,
            'timestamp' => now()->toDateTimeString(),
        ];

        if (! empty($metadata)) {
            $logData = array_merge($logData, $metadata);
        }

        Log::channel(config('ai.log_channel', 'stack'))->info('AI API Call', $logData);
    }
}
