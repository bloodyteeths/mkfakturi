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
}

// CLAUDE-CHECKPOINT
