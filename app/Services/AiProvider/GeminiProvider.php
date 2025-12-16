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
        $this->model = $config['model'] ?? 'gemini-1.5-flash';
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
        $model = $options['model'] ?? null;

        return $this->sendRequest([
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
        ], $maxTokens, $temperature, 'generate', $model);
    }

    /**
     * Generate a response with a specific model override
     *
     * @param  string  $prompt  The prompt to send to Gemini
     * @param  string  $model  The model to use (e.g., gemini-1.5-flash, gemini-1.5-pro)
     * @param  array<string, mixed>  $options  Additional options
     * @return string The AI's response
     *
     * @throws \Exception If the API call fails
     */
    public function generateWithModel(string $prompt, string $model, array $options = []): string
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
        ], $maxTokens, $temperature, 'generateWithModel', $model);
    }

    /**
     * {@inheritdoc}
     */
    public function chat(array $messages, array $options = []): string
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

        $model = $options['model'] ?? null;

        return $this->sendRequest([
            'contents' => $contents,
        ], $this->maxTokens, $this->temperature, 'chat', $model);
    }

    /**
     * Send request to Gemini API and return the text response.
     *
     * @param  array<string, mixed>  $payload
     * @param  string|null  $modelOverride  Optional model override
     *
     * @throws \Exception
     */
    private function sendRequest(
        array $payload,
        int $maxTokens,
        float $temperature,
        string $method,
        ?string $modelOverride = null
    ): string {
        $requestPayload = array_merge($payload, [
            'generationConfig' => [
                'temperature' => $temperature,
                'maxOutputTokens' => $maxTokens,
            ],
        ]);

        // Use model override if provided, otherwise use default
        $model = $modelOverride ?? $this->model;

        $url = sprintf(
            '%s/%s:generateContent?key=%s',
            $this->apiUrl,
            $model,
            $this->apiKey
        );

        $startTime = microtime(true);

        try {
            return $this->callWithRetry(function () use ($url, $requestPayload, $method, $startTime) {
                $response = Http::timeout(30)
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->post($url, $requestPayload);

                $duration = microtime(true) - $startTime;

                if ($response->failed()) {
                    $this->logApiCall($method, $requestPayload, null, $response->status(), $duration);
                    throw new \Exception('Gemini API request failed: '.$response->body());
                }

                $data = $response->json();

                // Check for blocked content or empty response
                if (empty($data['candidates'])) {
                    $blockReason = $data['promptFeedback']['blockReason'] ?? 'Unknown';
                    Log::warning('Gemini returned no candidates', [
                        'block_reason' => $blockReason,
                        'prompt_feedback' => $data['promptFeedback'] ?? null,
                    ]);
                    throw new \Exception('Gemini blocked the request: ' . $blockReason);
                }

                // Check if the candidate has content
                $candidate = $data['candidates'][0] ?? null;
                if (!$candidate || empty($candidate['content']['parts'])) {
                    $finishReason = $candidate['finishReason'] ?? 'Unknown';
                    Log::warning('Gemini candidate has no content', [
                        'finish_reason' => $finishReason,
                        'candidate' => $candidate,
                    ]);
                    throw new \Exception('Gemini returned empty response: ' . $finishReason);
                }

                $text = $candidate['content']['parts'][0]['text'] ?? '';

                $this->logApiCall($method, $requestPayload, $text, 200, $duration, [
                    'prompt_feedback' => $data['promptFeedback'] ?? null,
                    'finish_reason' => $candidate['finishReason'] ?? null,
                ]);

                return $text;
            });
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
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $metadata
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

    /**
     * Analyze an image with optional text prompt
     *
     * @param  string  $imageData  Base64 encoded image data
     * @param  string  $mediaType  MIME type
     * @param  string  $prompt  Text prompt/question about the image
     * @param  array<string, mixed>  $options  Additional options
     * @return string The AI's response
     *
     * @throws \Exception Vision support not implemented for Gemini yet
     */
    public function analyzeImage(string $imageData, string $mediaType, string $prompt, array $options = []): string
    {
        // TODO: Implement Gemini vision API support
        throw new \Exception('Vision analysis is not yet implemented for Gemini provider. Please use Claude provider for document analysis.');
    }

    /**
     * Analyze a document (PDF converted to images) with optional text prompt
     *
     * @param  array<int, array{data: string, media_type: string}>  $images  Array of image pages
     * @param  string  $prompt  Text prompt/question about the document
     * @param  array<string, mixed>  $options  Additional options
     * @return string The AI's response
     *
     * @throws \Exception Vision support not implemented for Gemini yet
     */
    public function analyzeDocument(array $images, string $prompt, array $options = []): string
    {
        // TODO: Implement Gemini vision API support
        throw new \Exception('Document analysis is not yet implemented for Gemini provider. Please use Claude provider for document analysis.');
    }

    /**
     * Generate a streaming response from a single prompt
     *
     * @param  string  $prompt  The prompt to send
     * @param  callable  $onChunk  Callback for each chunk
     * @param  array<string, mixed>  $options  Additional options
     * @return string Complete response
     */
    public function generateStream(string $prompt, callable $onChunk, array $options = []): string
    {
        $maxTokens = $options['max_tokens'] ?? $this->maxTokens;
        $temperature = $options['temperature'] ?? $this->temperature;
        $model = $options['model'] ?? $this->model;

        $startTime = microtime(true);
        $fullResponse = '';

        try {
            // Gemini streaming uses streamGenerateContent endpoint
            $url = sprintf(
                '%s/%s:streamGenerateContent?key=%s&alt=sse',
                $this->apiUrl,
                $model,
                $this->apiKey
            );

            $payload = json_encode([
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [['text' => $prompt]],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => $temperature,
                    'maxOutputTokens' => $maxTokens,
                ],
            ]);

            $ch = curl_init($url);

            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                ],
                CURLOPT_TIMEOUT => 120,
                CURLOPT_RETURNTRANSFER => false,
                CURLOPT_WRITEFUNCTION => function ($ch, $data) use (&$fullResponse, $onChunk) {
                    // Parse SSE data from Gemini
                    $lines = explode("\n", $data);
                    foreach ($lines as $line) {
                        $line = trim($line);
                        if (empty($line)) continue;

                        if (strpos($line, 'data: ') === 0) {
                            $jsonData = substr($line, 6);
                            $eventData = json_decode($jsonData, true);

                            if ($eventData === null) continue;

                            // Extract text from candidates
                            $candidates = $eventData['candidates'] ?? [];
                            foreach ($candidates as $candidate) {
                                $parts = $candidate['content']['parts'] ?? [];
                                foreach ($parts as $part) {
                                    $text = $part['text'] ?? '';
                                    if (!empty($text)) {
                                        $fullResponse .= $text;
                                        $onChunk($text);
                                    }
                                }
                            }
                        }
                    }

                    return strlen($data);
                },
            ]);

            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($result === false || $httpCode >= 400) {
                // Fallback to non-streaming if streaming fails
                Log::warning('[GeminiProvider] Streaming failed, falling back to sync', [
                    'http_code' => $httpCode,
                    'error' => $error,
                ]);

                $response = $this->generate($prompt, $options);
                $onChunk($response);
                return $response;
            }

            $this->logApiCall('generateStream', ['prompt' => $prompt], $fullResponse, 200, microtime(true) - $startTime, [
                'response_length' => strlen($fullResponse),
                'streamed' => true,
            ]);

            return $fullResponse;

        } catch (\Exception $e) {
            $this->logApiCall('generateStream', ['prompt' => $prompt], null, 0, microtime(true) - $startTime, [
                'error' => $e->getMessage(),
            ]);

            // Fallback to non-streaming
            $response = $this->generate($prompt, $options);
            $onChunk($response);
            return $response;
        }
    }

    /**
     * Execute an API call with retry logic and exponential backoff
     *
     * @param  callable  $apiCall  The API call to execute
     * @param  int|null  $maxRetries  Maximum number of retry attempts (null = use config)
     * @return mixed The result of the API call
     *
     * @throws \Exception If all retry attempts fail
     */
    private function callWithRetry(callable $apiCall, ?int $maxRetries = null): mixed
    {
        $maxAttempts = $maxRetries ?? config('ai.retry.max_attempts', 3);
        $lastException = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                return $apiCall();
            } catch (\Exception $e) {
                $lastException = $e;

                // Extract status code if available
                $statusCode = 0;
                if (preg_match('/status code (\d+)/i', $e->getMessage(), $matches)) {
                    $statusCode = (int) $matches[1];
                }

                // Check if error is retryable
                if (! $this->isRetryableError($e, $statusCode)) {
                    throw $e;
                }

                // Last attempt - throw exception
                if ($attempt >= $maxAttempts) {
                    Log::channel(config('ai.log_channel', 'stack'))->warning(
                        'Gemini API call failed after max retry attempts',
                        [
                            'attempts' => $attempt,
                            'error' => $e->getMessage(),
                            'status_code' => $statusCode,
                        ]
                    );
                    throw $e;
                }

                // Calculate backoff delay with jitter
                $delay = $this->calculateBackoffDelay($attempt);

                Log::channel(config('ai.log_channel', 'stack'))->info(
                    'Retrying Gemini API call after failure',
                    [
                        'attempt' => $attempt,
                        'max_attempts' => $maxAttempts,
                        'delay_ms' => $delay,
                        'error' => $e->getMessage(),
                    ]
                );

                usleep($delay * 1000);
            }
        }

        throw $lastException ?? new \Exception('API call failed without exception');
    }

    /**
     * Check if an error is retryable
     *
     * @param  \Exception  $e  The exception
     * @param  int  $statusCode  HTTP status code (0 if not available)
     * @return bool True if the error should be retried
     */
    private function isRetryableError(\Exception $e, int $statusCode = 0): bool
    {
        $retryableCodes = [429, 500, 502, 503, 529];

        if ($statusCode > 0 && in_array($statusCode, $retryableCodes)) {
            return true;
        }

        $message = strtolower($e->getMessage());
        if (
            strpos($message, 'timeout') !== false ||
            strpos($message, 'connection') !== false ||
            strpos($message, 'rate limit') !== false ||
            strpos($message, 'resource_exhausted') !== false
        ) {
            return true;
        }

        // Don't retry client errors (4xx except 429)
        if ($statusCode >= 400 && $statusCode < 500 && $statusCode !== 429) {
            return false;
        }

        return false;
    }

    /**
     * Calculate exponential backoff delay with jitter
     *
     * @param  int  $attempt  The current attempt number (1-based)
     * @return int Delay in milliseconds
     */
    private function calculateBackoffDelay(int $attempt): int
    {
        $initialDelay = config('ai.retry.initial_delay_ms', 1000);
        $multiplier = config('ai.retry.multiplier', 2);

        $baseDelay = $initialDelay * pow($multiplier, $attempt - 1);
        $jitter = random_int(0, (int) ($baseDelay / 2));

        return (int) ($baseDelay + $jitter);
    }
}

// CLAUDE-CHECKPOINT
