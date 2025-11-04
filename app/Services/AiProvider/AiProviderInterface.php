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
}
