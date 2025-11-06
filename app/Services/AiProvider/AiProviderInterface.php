<?php

namespace App\Services\AiProvider;

/**
 * Interface for AI provider implementations
 *
 * This interface defines the contract that all AI providers must implement
 * to be used with the AI Insights system.
 */
interface AiProviderInterface
{
    /**
     * Generate a response from a single prompt
     *
     * @param string $prompt The prompt to send to the AI
     * @param array<string, mixed> $options Additional options (max_tokens, temperature, etc.)
     * @return string The AI's response
     * @throws \Exception If the API call fails
     */
    public function generate(string $prompt, array $options = []): string;

    /**
     * Generate a response from a conversation with multiple messages
     *
     * @param array<int, array{role: string, content: string}> $messages Array of messages with role and content
     * @return string The AI's response
     * @throws \Exception If the API call fails
     */
    public function chat(array $messages): string;

    /**
     * Get the provider name
     *
     * @return string The provider name (claude, openai, gemini)
     */
    public function getProviderName(): string;

    /**
     * Get the model being used
     *
     * @return string The model identifier
     */
    public function getModel(): string;

    /**
     * Analyze an image with optional text prompt
     *
     * @param string $imageData Base64 encoded image data
     * @param string $mediaType MIME type (image/png, image/jpeg, image/webp, image/gif)
     * @param string $prompt Text prompt/question about the image
     * @param array<string, mixed> $options Additional options
     * @return string The AI's response
     * @throws \Exception If the API call fails or provider doesn't support vision
     */
    public function analyzeImage(string $imageData, string $mediaType, string $prompt, array $options = []): string;

    /**
     * Analyze a document (PDF converted to images) with optional text prompt
     *
     * @param array<int, array{data: string, media_type: string}> $images Array of image pages
     * @param string $prompt Text prompt/question about the document
     * @param array<string, mixed> $options Additional options
     * @return string The AI's response
     * @throws \Exception If the API call fails or provider doesn't support vision
     */
    public function analyzeDocument(array $images, string $prompt, array $options = []): string;
}

// CLAUDE-CHECKPOINT
