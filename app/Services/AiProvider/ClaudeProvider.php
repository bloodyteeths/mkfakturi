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
     * @param  string  $prompt  The prompt to send to Claude
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
                    throw new \Exception('Claude API request failed: '.$response->body());
                }

                $data = $response->json();
                $text = $data['content'][0]['text'] ?? '';

                $this->logApiCall('generate', $prompt, $text, 200, microtime(true) - $startTime, [
                    'input_tokens' => $data['usage']['input_tokens'] ?? 0,
                    'output_tokens' => $data['usage']['output_tokens'] ?? 0,
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
                    throw new \Exception('Claude API chat request failed: '.$response->body());
                }

                $data = $response->json();
                $text = $data['content'][0]['text'] ?? '';

                $this->logApiCall('chat', json_encode($messages), $text, 200, microtime(true) - $startTime, [
                    'input_tokens' => $data['usage']['input_tokens'] ?? 0,
                    'output_tokens' => $data['usage']['output_tokens'] ?? 0,
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
     * Analyze an image with optional text prompt
     *
     * @param  string  $imageData  Base64 encoded image data
     * @param  string  $mediaType  MIME type (image/png, image/jpeg, image/webp, image/gif)
     * @param  string  $prompt  Text prompt/question about the image
     * @param  array<string, mixed>  $options  Additional options
     * @return string The AI's response
     *
     * @throws \Exception If the API call fails
     */
    public function analyzeImage(string $imageData, string $mediaType, string $prompt, array $options = []): string
    {
        $maxTokens = $options['max_tokens'] ?? $this->maxTokens;
        $temperature = $options['temperature'] ?? $this->temperature;

        $startTime = microtime(true);

        try {
            $text = $this->callWithRetry(function () use ($imageData, $mediaType, $prompt, $maxTokens, $temperature, $startTime) {
                // Build multimodal content array
                $content = [
                    [
                        'type' => 'image',
                        'source' => [
                            'type' => 'base64',
                            'media_type' => $mediaType,
                            'data' => $imageData,
                        ],
                    ],
                    [
                        'type' => 'text',
                        'text' => $prompt,
                    ],
                ];

                $response = Http::withHeaders([
                    'x-api-key' => $this->apiKey,
                    'anthropic-version' => $this->apiVersion,
                    'content-type' => 'application/json',
                ])
                    ->timeout(60) // Longer timeout for image analysis
                    ->post($this->apiUrl, [
                        'model' => $this->model,
                        'max_tokens' => $maxTokens,
                        'temperature' => $temperature,
                        'messages' => [
                            [
                                'role' => 'user',
                                'content' => $content,
                            ],
                        ],
                    ]);

                if ($response->failed()) {
                    $this->logApiCall('analyzeImage', $prompt, null, $response->status(), microtime(true) - $startTime);
                    throw new \Exception('Claude API image analysis request failed: '.$response->body());
                }

                $data = $response->json();
                $text = $data['content'][0]['text'] ?? '';

                $this->logApiCall('analyzeImage', $prompt, $text, 200, microtime(true) - $startTime, [
                    'input_tokens' => $data['usage']['input_tokens'] ?? 0,
                    'output_tokens' => $data['usage']['output_tokens'] ?? 0,
                    'media_type' => $mediaType,
                    'image_size_bytes' => strlen($imageData),
                ]);

                return $text;
            });

            return $text;

        } catch (\Exception $e) {
            $this->logApiCall('analyzeImage', $prompt, null, 0, microtime(true) - $startTime, ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Analyze a document (PDF converted to images) with optional text prompt
     *
     * @param  array<int, array{data: string, media_type: string}>  $images  Array of image pages
     * @param  string  $prompt  Text prompt/question about the document
     * @param  array<string, mixed>  $options  Additional options
     * @return string The AI's response
     *
     * @throws \Exception If the API call fails
     */
    public function analyzeDocument(array $images, string $prompt, array $options = []): string
    {
        $maxTokens = $options['max_tokens'] ?? $this->maxTokens;
        $temperature = $options['temperature'] ?? $this->temperature;

        $startTime = microtime(true);

        try {
            $text = $this->callWithRetry(function () use ($images, $prompt, $maxTokens, $temperature, $startTime) {
                // Build multimodal content array with multiple images
                $content = [];

                foreach ($images as $index => $image) {
                    $content[] = [
                        'type' => 'image',
                        'source' => [
                            'type' => 'base64',
                            'media_type' => $image['media_type'],
                            'data' => $image['data'],
                        ],
                    ];
                }

                // Add text prompt at the end
                $content[] = [
                    'type' => 'text',
                    'text' => $prompt,
                ];

                $response = Http::withHeaders([
                    'x-api-key' => $this->apiKey,
                    'anthropic-version' => $this->apiVersion,
                    'content-type' => 'application/json',
                ])
                    ->timeout(120) // Even longer timeout for multi-page documents
                    ->post($this->apiUrl, [
                        'model' => $this->model,
                        'max_tokens' => $maxTokens,
                        'temperature' => $temperature,
                        'messages' => [
                            [
                                'role' => 'user',
                                'content' => $content,
                            ],
                        ],
                    ]);

                if ($response->failed()) {
                    $this->logApiCall('analyzeDocument', $prompt, null, $response->status(), microtime(true) - $startTime);
                    throw new \Exception('Claude API document analysis request failed: '.$response->body());
                }

                $data = $response->json();
                $text = $data['content'][0]['text'] ?? '';

                $this->logApiCall('analyzeDocument', $prompt, $text, 200, microtime(true) - $startTime, [
                    'input_tokens' => $data['usage']['input_tokens'] ?? 0,
                    'output_tokens' => $data['usage']['output_tokens'] ?? 0,
                    'page_count' => count($images),
                ]);

                return $text;
            });

            return $text;

        } catch (\Exception $e) {
            $this->logApiCall('analyzeDocument', $prompt, null, 0, microtime(true) - $startTime, ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Generate a response with prompt caching enabled
     *
     * This method enables Anthropic's prompt caching feature which can reduce
     * token costs by up to 90% for repeated system prompts. The system prompt
     * is cached and reused across requests.
     *
     * @param  string  $systemPrompt  The system/context prompt to cache
     * @param  string  $userMessage  The user's message
     * @param  array<string, mixed>  $options  Additional options
     * @return string The AI's response
     *
     * @throws \Exception If the API call fails
     */
    public function generateWithCache(string $systemPrompt, string $userMessage, array $options = []): string
    {
        $maxTokens = $options['max_tokens'] ?? $this->maxTokens;
        $temperature = $options['temperature'] ?? $this->temperature;
        $model = $options['model'] ?? $this->model;

        $startTime = microtime(true);

        try {
            $text = $this->callWithRetry(function () use ($systemPrompt, $userMessage, $model, $maxTokens, $temperature, $startTime) {
                // Build system prompt with cache_control for static content
                $systemContent = [
                    [
                        'type' => 'text',
                        'text' => $systemPrompt,
                        'cache_control' => ['type' => 'ephemeral'], // Cache for 5 minutes
                    ],
                ];

                $response = Http::withHeaders([
                    'x-api-key' => $this->apiKey,
                    'anthropic-version' => $this->apiVersion,
                    'anthropic-beta' => 'prompt-caching-2024-07-31',
                    'content-type' => 'application/json',
                ])
                    ->timeout(60)
                    ->post($this->apiUrl, [
                        'model' => $model,
                        'max_tokens' => $maxTokens,
                        'temperature' => $temperature,
                        'system' => $systemContent,
                        'messages' => [
                            [
                                'role' => 'user',
                                'content' => $userMessage,
                            ],
                        ],
                    ]);

                if ($response->failed()) {
                    $this->logApiCall('generateWithCache', $userMessage, null, $response->status(), microtime(true) - $startTime, [
                        'cache_enabled' => true,
                    ]);
                    throw new \Exception('Claude API request failed: '.$response->body());
                }

                $data = $response->json();
                $text = $data['content'][0]['text'] ?? '';

                // Log with cache metrics
                $this->logApiCall('generateWithCache', $userMessage, $text, 200, microtime(true) - $startTime, [
                    'input_tokens' => $data['usage']['input_tokens'] ?? 0,
                    'output_tokens' => $data['usage']['output_tokens'] ?? 0,
                    'cache_creation_input_tokens' => $data['usage']['cache_creation_input_tokens'] ?? 0,
                    'cache_read_input_tokens' => $data['usage']['cache_read_input_tokens'] ?? 0,
                    'cache_enabled' => true,
                    'system_prompt_length' => strlen($systemPrompt),
                ]);

                return $text;
            });

            return $text;

        } catch (\Exception $e) {
            $this->logApiCall('generateWithCache', $userMessage, null, 0, microtime(true) - $startTime, [
                'error' => $e->getMessage(),
                'cache_enabled' => true,
            ]);
            throw $e;
        }
    }

    /**
     * Generate a chat response with system prompt and caching
     *
     * @param  string  $systemPrompt  The system/context prompt to cache
     * @param  array<int, array{role: string, content: string}>  $messages  Array of messages
     * @param  array<string, mixed>  $options  Additional options
     * @return string The AI's response
     *
     * @throws \Exception If the API call fails
     */
    public function chatWithCache(string $systemPrompt, array $messages, array $options = []): string
    {
        $maxTokens = $options['max_tokens'] ?? $this->maxTokens;
        $temperature = $options['temperature'] ?? $this->temperature;
        $model = $options['model'] ?? $this->model;

        $startTime = microtime(true);

        try {
            $text = $this->callWithRetry(function () use ($systemPrompt, $messages, $model, $maxTokens, $temperature, $startTime) {
                // Build system prompt with cache_control
                $systemContent = [
                    [
                        'type' => 'text',
                        'text' => $systemPrompt,
                        'cache_control' => ['type' => 'ephemeral'],
                    ],
                ];

                $response = Http::withHeaders([
                    'x-api-key' => $this->apiKey,
                    'anthropic-version' => $this->apiVersion,
                    'anthropic-beta' => 'prompt-caching-2024-07-31',
                    'content-type' => 'application/json',
                ])
                    ->timeout(60)
                    ->post($this->apiUrl, [
                        'model' => $model,
                        'max_tokens' => $maxTokens,
                        'temperature' => $temperature,
                        'system' => $systemContent,
                        'messages' => $messages,
                    ]);

                if ($response->failed()) {
                    $this->logApiCall('chatWithCache', json_encode($messages), null, $response->status(), microtime(true) - $startTime, [
                        'cache_enabled' => true,
                    ]);
                    throw new \Exception('Claude API chat request failed: '.$response->body());
                }

                $data = $response->json();
                $text = $data['content'][0]['text'] ?? '';

                $this->logApiCall('chatWithCache', json_encode($messages), $text, 200, microtime(true) - $startTime, [
                    'input_tokens' => $data['usage']['input_tokens'] ?? 0,
                    'output_tokens' => $data['usage']['output_tokens'] ?? 0,
                    'cache_creation_input_tokens' => $data['usage']['cache_creation_input_tokens'] ?? 0,
                    'cache_read_input_tokens' => $data['usage']['cache_read_input_tokens'] ?? 0,
                    'cache_enabled' => true,
                    'system_prompt_length' => strlen($systemPrompt),
                ]);

                return $text;
            });

            return $text;

        } catch (\Exception $e) {
            $this->logApiCall('chatWithCache', json_encode($messages), null, 0, microtime(true) - $startTime, [
                'error' => $e->getMessage(),
                'cache_enabled' => true,
            ]);
            throw $e;
        }
    }

    /**
     * Generate a response with a specific model override
     *
     * @param  string  $prompt  The prompt to send to Claude
     * @param  string  $model  The model to use (e.g., claude-3-haiku-20240307)
     * @param  array<string, mixed>  $options  Additional options
     * @return string The AI's response
     *
     * @throws \Exception If the API call fails
     */
    public function generateWithModel(string $prompt, string $model, array $options = []): string
    {
        $maxTokens = $options['max_tokens'] ?? $this->maxTokens;
        $temperature = $options['temperature'] ?? $this->temperature;

        $startTime = microtime(true);

        try {
            $text = $this->callWithRetry(function () use ($prompt, $model, $maxTokens, $temperature, $startTime) {
                $response = Http::withHeaders([
                    'x-api-key' => $this->apiKey,
                    'anthropic-version' => $this->apiVersion,
                    'content-type' => 'application/json',
                ])
                    ->timeout(30)
                    ->post($this->apiUrl, [
                        'model' => $model, // Use provided model instead of default
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
                    $this->logApiCall('generateWithModel', $prompt, null, $response->status(), microtime(true) - $startTime, [
                        'model_override' => $model,
                    ]);
                    throw new \Exception('Claude API request failed: '.$response->body());
                }

                $data = $response->json();
                $text = $data['content'][0]['text'] ?? '';

                $this->logApiCall('generateWithModel', $prompt, $text, 200, microtime(true) - $startTime, [
                    'input_tokens' => $data['usage']['input_tokens'] ?? 0,
                    'output_tokens' => $data['usage']['output_tokens'] ?? 0,
                    'model_override' => $model,
                ]);

                return $text;
            });

            return $text;

        } catch (\Exception $e) {
            $this->logApiCall('generateWithModel', $prompt, null, 0, microtime(true) - $startTime, [
                'error' => $e->getMessage(),
                'model_override' => $model,
            ]);
            throw $e;
        }
    }

    /**
     * Generate a streaming response from a single prompt
     *
     * @param  string  $prompt  The prompt to send to Claude
     * @param  callable  $onChunk  Callback function called for each chunk of text
     * @param  array<string, mixed>  $options  Additional options (max_tokens, temperature, etc.)
     * @return string The complete AI response (accumulated from all chunks)
     *
     * @throws \Exception If the API call fails
     */
    public function generateStream(string $prompt, callable $onChunk, array $options = []): string
    {
        $maxTokens = $options['max_tokens'] ?? $this->maxTokens;
        $temperature = $options['temperature'] ?? $this->temperature;
        $model = $options['model'] ?? $this->model;

        $startTime = microtime(true);
        $fullResponse = '';

        try {
            // Use cURL for streaming as Laravel HTTP client doesn't handle SSE well
            $ch = curl_init($this->apiUrl);

            $payload = json_encode([
                'model' => $model,
                'max_tokens' => $maxTokens,
                'temperature' => $temperature,
                'stream' => true,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
            ]);

            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => [
                    'x-api-key: '.$this->apiKey,
                    'anthropic-version: '.$this->apiVersion,
                    'content-type: application/json',
                    'Accept: text/event-stream',
                ],
                CURLOPT_TIMEOUT => 120,
                CURLOPT_RETURNTRANSFER => false,
                CURLOPT_WRITEFUNCTION => function ($ch, $data) use (&$fullResponse, $onChunk) {
                    // Parse SSE data
                    $lines = explode("\n", $data);
                    foreach ($lines as $line) {
                        $line = trim($line);
                        if (empty($line) || $line === 'event: message_start' || $line === 'event: content_block_start' || $line === 'event: content_block_delta' || $line === 'event: content_block_stop' || $line === 'event: message_delta' || $line === 'event: message_stop' || $line === 'event: ping') {
                            continue;
                        }

                        if (strpos($line, 'data: ') === 0) {
                            $jsonData = substr($line, 6);
                            if ($jsonData === '[DONE]') {
                                continue;
                            }

                            $eventData = json_decode($jsonData, true);
                            if ($eventData === null) {
                                continue;
                            }

                            // Extract text from content_block_delta events
                            if (isset($eventData['type']) && $eventData['type'] === 'content_block_delta') {
                                $text = $eventData['delta']['text'] ?? '';
                                if (!empty($text)) {
                                    $fullResponse .= $text;
                                    $onChunk($text);
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
                $this->logApiCall('generateStream', $prompt, null, $httpCode, microtime(true) - $startTime, [
                    'error' => $error ?: 'HTTP error',
                ]);
                throw new \Exception('Claude streaming API failed: '.$error.' (HTTP '.$httpCode.')');
            }

            $this->logApiCall('generateStream', $prompt, $fullResponse, 200, microtime(true) - $startTime, [
                'response_length' => strlen($fullResponse),
                'streamed' => true,
            ]);

            return $fullResponse;

        } catch (\Exception $e) {
            $this->logApiCall('generateStream', $prompt, null, 0, microtime(true) - $startTime, [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get the provider name
     */
    public function getProviderName(): string
    {
        return 'claude';
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

                // Extract status code if it's an HTTP exception
                $statusCode = 0;
                if (method_exists($e, 'getResponse') && $e->getResponse()) {
                    $statusCode = $e->getResponse()->getStatusCode();
                } elseif (strpos($e->getMessage(), 'failed:') !== false) {
                    // Try to extract status code from exception message
                    if (preg_match('/status code (\d+)/i', $e->getMessage(), $matches)) {
                        $statusCode = (int) $matches[1];
                    }
                }

                // Check if error is retryable
                if (! $this->isRetryableError($e, $statusCode)) {
                    // Non-retryable error, throw immediately
                    throw $e;
                }

                // If this was the last attempt, throw the exception
                if ($attempt >= $maxAttempts) {
                    Log::channel(config('ai.log_channel', 'stack'))->warning(
                        'AI API call failed after max retry attempts',
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

                // Log retry attempt
                Log::channel(config('ai.log_channel', 'stack'))->info(
                    'Retrying AI API call after failure',
                    [
                        'attempt' => $attempt,
                        'max_attempts' => $maxAttempts,
                        'delay_ms' => $delay,
                        'error' => $e->getMessage(),
                        'status_code' => $statusCode,
                    ]
                );

                // Wait before retrying (convert ms to microseconds)
                usleep($delay * 1000);
            }
        }

        // This should never be reached, but for type safety
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
        $retryableCodes = config('ai.retry.retryable_status_codes', [429, 500, 502, 503, 529]);

        // Check if status code is retryable
        if ($statusCode > 0 && in_array($statusCode, $retryableCodes)) {
            return true;
        }

        // Check for timeout errors
        $message = strtolower($e->getMessage());
        if (
            strpos($message, 'timeout') !== false ||
            strpos($message, 'timed out') !== false ||
            strpos($message, 'operation timed out') !== false
        ) {
            return true;
        }

        // Check for connection errors
        if (
            strpos($message, 'connection refused') !== false ||
            strpos($message, 'connection reset') !== false ||
            strpos($message, 'could not connect') !== false ||
            strpos($message, 'connection failed') !== false ||
            strpos($message, 'network error') !== false
        ) {
            return true;
        }

        // Do not retry client errors (4xx except 429)
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

        // Calculate base delay: initialDelay * (multiplier ^ (attempt - 1))
        $baseDelay = $initialDelay * pow($multiplier, $attempt - 1);

        // Add jitter: random value between 0 and baseDelay/2
        $jitter = random_int(0, (int) ($baseDelay / 2));

        return (int) ($baseDelay + $jitter);
    }
}

// CLAUDE-CHECKPOINT
