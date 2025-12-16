<?php

namespace App\Services\AiProvider;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * OpenAI Provider Implementation
 *
 * Integrates with OpenAI's GPT API for AI-powered insights generation.
 */
class OpenAiProvider implements AiProviderInterface
{
    private string $apiKey;

    private string $model;

    private string $apiUrl;

    private int $maxTokens;

    private float $temperature;

    /**
     * Create a new OpenAI provider instance
     */
    public function __construct()
    {
        $config = config('ai.providers.openai');

        $this->apiKey = $config['api_key'] ?? '';
        $this->model = $config['model'] ?? 'gpt-4-turbo';
        $this->apiUrl = $config['api_url'] ?? 'https://api.openai.com/v1/chat/completions';
        $this->maxTokens = $config['max_tokens'] ?? 4096;
        $this->temperature = $config['temperature'] ?? 0.7;

        if (empty($this->apiKey)) {
            throw new \RuntimeException('OpenAI API key is not configured');
        }
    }

    /**
     * Generate a response from a single prompt
     *
     * @param  string  $prompt  The prompt to send to OpenAI
     * @param  array<string, mixed>  $options  Additional options
     * @return string The AI's response
     *
     * @throws \Exception If the API call fails
     */
    public function generate(string $prompt, array $options = []): string
    {
        $maxTokens = $options['max_tokens'] ?? $this->maxTokens;
        $temperature = $options['temperature'] ?? $this->temperature;

        $startTime = microtime(true);

        try {
            $text = $this->callWithRetry(function () use ($prompt, $maxTokens, $temperature, $startTime) {
                $response = Http::withToken($this->apiKey)
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
                    throw new \Exception('OpenAI API request failed: '.$response->body());
                }

                $data = $response->json();
                $text = $data['choices'][0]['message']['content'] ?? '';

                $this->logApiCall('generate', $prompt, $text, 200, microtime(true) - $startTime, [
                    'prompt_tokens' => $data['usage']['prompt_tokens'] ?? 0,
                    'completion_tokens' => $data['usage']['completion_tokens'] ?? 0,
                    'total_tokens' => $data['usage']['total_tokens'] ?? 0,
                ]);

                return $text;
            });

            return $text;

        } catch (\Exception $e) {
            $this->logApiCall('generate', $prompt, null, 0, microtime(true) - $startTime, ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Generate a response from a conversation with multiple messages
     *
     * @param  array<int, array{role: string, content: string}>  $messages  Array of messages
     * @return string The AI's response
     *
     * @throws \Exception If the API call fails
     */
    public function chat(array $messages): string
    {
        $startTime = microtime(true);

        try {
            $text = $this->callWithRetry(function () use ($messages, $startTime) {
                $response = Http::withToken($this->apiKey)
                    ->timeout(30)
                    ->post($this->apiUrl, [
                        'model' => $this->model,
                        'max_tokens' => $this->maxTokens,
                        'temperature' => $this->temperature,
                        'messages' => $messages,
                    ]);

                if ($response->failed()) {
                    $this->logApiCall('chat', json_encode($messages), null, $response->status(), microtime(true) - $startTime);
                    throw new \Exception('OpenAI API chat request failed: '.$response->body());
                }

                $data = $response->json();
                $text = $data['choices'][0]['message']['content'] ?? '';

                $this->logApiCall('chat', json_encode($messages), $text, 200, microtime(true) - $startTime, [
                    'prompt_tokens' => $data['usage']['prompt_tokens'] ?? 0,
                    'completion_tokens' => $data['usage']['completion_tokens'] ?? 0,
                    'total_tokens' => $data['usage']['total_tokens'] ?? 0,
                ]);

                return $text;
            });

            return $text;

        } catch (\Exception $e) {
            $this->logApiCall('chat', json_encode($messages), null, 0, microtime(true) - $startTime, ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Get the provider name
     */
    public function getProviderName(): string
    {
        return 'openai';
    }

    /**
     * Get the model being used
     */
    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * Log API call for cost tracking and monitoring
     *
     * @param  string  $method  The method called (generate or chat)
     * @param  string  $input  The input prompt or messages
     * @param  string|null  $output  The AI response
     * @param  int  $statusCode  HTTP status code
     * @param  float  $duration  Duration in seconds
     * @param  array<string, mixed>  $metadata  Additional metadata
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
            'provider' => 'openai',
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

    /**
     * Analyze an image with optional text prompt
     *
     * @param  string  $imageData  Base64 encoded image data
     * @param  string  $mediaType  MIME type
     * @param  string  $prompt  Text prompt/question about the image
     * @param  array<string, mixed>  $options  Additional options
     * @return string The AI's response
     *
     * @throws \Exception Vision support not implemented for OpenAI yet
     */
    public function analyzeImage(string $imageData, string $mediaType, string $prompt, array $options = []): string
    {
        // TODO: Implement OpenAI vision API support (GPT-4 Vision)
        throw new \Exception('Vision analysis is not yet implemented for OpenAI provider. Please use Claude provider for document analysis.');
    }

    /**
     * Analyze a document (PDF converted to images) with optional text prompt
     *
     * @param  array<int, array{data: string, media_type: string}>  $images  Array of image pages
     * @param  string  $prompt  Text prompt/question about the document
     * @param  array<string, mixed>  $options  Additional options
     * @return string The AI's response
     *
     * @throws \Exception Vision support not implemented for OpenAI yet
     */
    public function analyzeDocument(array $images, string $prompt, array $options = []): string
    {
        // TODO: Implement OpenAI vision API support (GPT-4 Vision)
        throw new \Exception('Document analysis is not yet implemented for OpenAI provider. Please use Claude provider for document analysis.');
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
        // OpenAI streaming not implemented - fall back to regular generate
        $response = $this->generate($prompt, $options);
        $onChunk($response);
        return $response;
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
                        'OpenAI API call failed after max retry attempts',
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
                    'Retrying OpenAI API call after failure',
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
            strpos($message, 'rate limit') !== false
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

// CLAUDE-CHECKPOINT
