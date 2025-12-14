<?php

namespace App\Services\AiProvider;

use Illuminate\Support\Facades\Log;

/**
 * Null AI Provider
 *
 * Provides deterministic fallback responses when no real AI provider
 * is configured. Ensures API handlers degrade gracefully instead of
 * throwing exceptions in production.
 */
class NullAiProvider implements AiProviderInterface
{
    private string $sourceProvider;

    private ?string $reason;

    public function __construct(string $sourceProvider = 'unknown', ?string $reason = null)
    {
        $this->sourceProvider = $sourceProvider;
        $this->reason = $reason;

        Log::warning('Falling back to Null AI provider', [
            'requested_provider' => $sourceProvider,
            'reason' => $reason,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function generate(string $prompt, array $options = []): string
    {
        $fallback = [[
            'type' => 'info',
            'title' => 'AI анализата е моментално недостапна',
            'description' => 'Конфигурирајте ги AI акредитивите или MCP серверот за да активирате финансиски препораки.',
            'action' => 'Проверете ги поставките за AI провајдер и MCP токен во административниот панел.',
            'priority' => 1,
        ]];

        Log::notice('Returning fallback AI insight payload', [
            'provider' => $this->getProviderName(),
            'reason' => $this->reason,
        ]);

        return json_encode($fallback, JSON_UNESCAPED_UNICODE);
    }

    /**
     * {@inheritdoc}
     */
    public function chat(array $messages): string
    {
        Log::notice('Returning fallback AI chat response', [
            'provider' => $this->getProviderName(),
            'reason' => $this->reason,
        ]);

        return 'AI асистентот е привремено недостапен. Ве молиме конфигурирајте го AI провајдерот пред повторно да пробате.';
    }

    /**
     * {@inheritdoc}
     */
    public function getProviderName(): string
    {
        return 'disabled';
    }

    /**
     * {@inheritdoc}
     */
    public function getModel(): string
    {
        return sprintf('unconfigured-%s', $this->sourceProvider);
    }

    /**
     * {@inheritdoc}
     */
    public function analyzeImage(string $imageData, string $mediaType, string $prompt, array $options = []): string
    {
        Log::notice('Returning fallback AI image analysis response', [
            'provider' => $this->getProviderName(),
            'reason' => $this->reason,
        ]);

        return 'Анализата на слики е моментално недостапна. Ве молиме конфигурирајте го AI провајдерот пред повторно да пробате.';
    }

    /**
     * {@inheritdoc}
     */
    public function analyzeDocument(array $images, string $prompt, array $options = []): string
    {
        Log::notice('Returning fallback AI document analysis response', [
            'provider' => $this->getProviderName(),
            'reason' => $this->reason,
            'page_count' => count($images),
        ]);

        return 'Анализата на документи е моментално недостапна. Ве молиме конфигурирајте го AI провајдерот пред повторно да пробате.';
    }

    /**
     * {@inheritdoc}
     */
    public function generateStream(string $prompt, callable $onChunk, array $options = []): string
    {
        $response = 'AI асистентот е привремено недостапен. Ве молиме конфигурирајте го AI провајдерот.';
        $onChunk($response);
        return $response;
    }
}

// CLAUDE-CHECKPOINT
