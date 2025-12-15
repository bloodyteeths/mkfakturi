<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\AiInsightsService;
use App\Services\UsageLimitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * AI Insights Controller
 *
 * Handles API endpoints for AI-powered financial insights and chat.
 */
class AiInsightsController extends Controller
{
    /**
     * Create a new controller instance
     */
    public function __construct(
        private AiInsightsService $aiService
    ) {}

    /**
     * Get cached AI insights or return empty state
     *
     * GET /api/v1/ai/insights
     */
    public function index(Request $request): JsonResponse
    {
        $company = Company::find($request->header('company'));

        if (! $company) {
            return response()->json([
                'error' => 'Company not found',
            ], 404);
        }

        $this->authorize('view dashboard', $company);

        // Try to get cached insights
        $insights = Cache::get("insights:{$company->id}");

        if ($insights === null) {
            return response()->json([
                'insights' => [],
                'generated_at' => null,
                'next_refresh' => null,
                'cached' => false,
            ]);
        }

        return response()->json([
            'insights' => $insights['items'] ?? [],
            'generated_at' => $insights['timestamp'] ?? null,
            'next_refresh' => $insights['expires_at'] ?? null,
            'provider' => $insights['provider'] ?? null,
            'model' => $insights['model'] ?? null,
            'cached' => true,
        ]);
    }

    /**
     * Generate AI insights (synchronous execution)
     *
     * POST /api/v1/ai/insights/generate
     */
    public function generate(Request $request): JsonResponse
    {
        $company = Company::find($request->header('company'));

        if (! $company) {
            return response()->json([
                'error' => 'Company not found',
            ], 404);
        }

        $this->authorize('view dashboard', $company);

        // Check usage limit for AI queries
        $usageService = app(UsageLimitService::class);
        if (! $usageService->canUse($company, 'ai_queries_per_month')) {
            return response()->json(
                $usageService->buildLimitExceededResponse($company, 'ai_queries_per_month'),
                403
            );
        }

        try {
            // Execute synchronously instead of queuing to avoid queue processing issues
            // In production, this ensures immediate execution regardless of queue configuration
            Log::info('AI insights generation started (synchronous)', [
                'company_id' => $company->id,
                'user_id' => $request->user()->id,
            ]);

            // Generate insights immediately
            $insights = $this->aiService->analyzeFinancials($company);

            Log::info('AI insights generated successfully', [
                'company_id' => $company->id,
                'insights_count' => count($insights['items'] ?? []),
                'provider' => $insights['provider'] ?? null,
                'model' => $insights['model'] ?? null,
            ]);

            // Increment usage after successful AI call
            $usageService->incrementUsage($company, 'ai_queries_per_month');

            return response()->json([
                'message' => 'AI insights generation completed',
                'status' => 'completed',
                'insights_count' => count($insights['items'] ?? []),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate AI insights', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Failed to generate insights',
                'message' => $e->getMessage(),
            ], 500);
        }
        // CLAUDE-CHECKPOINT
    }

    /**
     * Refresh insights synchronously (immediate response)
     *
     * POST /api/v1/ai/insights/refresh
     */
    public function refresh(Request $request): JsonResponse
    {
        $company = Company::find($request->header('company'));

        if (! $company) {
            return response()->json([
                'error' => 'Company not found',
            ], 404);
        }

        $this->authorize('view dashboard', $company);

        // Check usage limit for AI queries
        $usageService = app(UsageLimitService::class);
        if (! $usageService->canUse($company, 'ai_queries_per_month')) {
            return response()->json(
                $usageService->buildLimitExceededResponse($company, 'ai_queries_per_month'),
                403
            );
        }

        try {
            // Clear cache first
            $this->aiService->clearCache($company);

            // Generate new insights synchronously
            $insights = $this->aiService->analyzeFinancials($company);

            // Increment usage after successful AI call
            $usageService->incrementUsage($company, 'ai_queries_per_month');

            return response()->json([
                'insights' => $insights['items'] ?? [],
                'generated_at' => $insights['timestamp'] ?? null,
                'next_refresh' => $insights['expires_at'] ?? null,
                'provider' => $insights['provider'] ?? null,
                'model' => $insights['model'] ?? null,
            ]);

        } catch (\Exception $e) {
            Log::error('AI insights refresh failed', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to refresh insights',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Interactive AI chat for financial questions
     *
     * POST /api/v1/ai/insights/chat
     */
    public function chat(Request $request): JsonResponse
    {
        $company = Company::find($request->header('company'));

        if (! $company) {
            return response()->json([
                'error' => 'Company not found',
            ], 404);
        }

        $this->authorize('view dashboard', $company);

        $validated = $request->validate([
            'message' => 'required|string|max:'.config('ai.chat.max_message_length', 1000),
            'conversation_id' => 'nullable|string|uuid',
        ]);

        // Check usage limit for AI queries
        $usageService = app(UsageLimitService::class);
        if (! $usageService->canUse($company, 'ai_queries_per_month')) {
            return response()->json(
                $usageService->buildLimitExceededResponse($company, 'ai_queries_per_month'),
                403
            );
        }

        try {
            // Generate conversation ID if not provided
            $conversationId = $validated['conversation_id'] ?? \Illuminate\Support\Str::uuid()->toString();
            $cacheKey = "ai_chat:{$company->id}:{$conversationId}";
            $cacheTtl = 3600; // 1 hour

            // Retrieve conversation history from cache
            $conversation = Cache::get($cacheKey, [
                'messages' => [],
                'created_at' => now()->toDateTimeString(),
                'last_activity' => now()->toDateTimeString(),
            ]);

            // Extract conversation history (last 10 messages max)
            $conversationHistory = array_slice($conversation['messages'], -10);

            // Get AI response with conversation context
            $response = $this->aiService->answerQuestion(
                $company,
                $validated['message'],
                $conversationHistory
            );

            // Increment usage after successful AI call
            $usageService->incrementUsage($company, 'ai_queries_per_month');

            // Update conversation history
            $conversation['messages'][] = [
                'role' => 'user',
                'content' => $validated['message'],
                'timestamp' => now()->toDateTimeString(),
            ];

            $conversation['messages'][] = [
                'role' => 'assistant',
                'content' => $response,
                'timestamp' => now()->toDateTimeString(),
            ];

            $conversation['last_activity'] = now()->toDateTimeString();

            // Store updated conversation in cache
            Cache::put($cacheKey, $conversation, $cacheTtl);

            return response()->json([
                'response' => $response,
                'conversation_id' => $conversationId,
                'timestamp' => now()->toDateTimeString(),
                'message_count' => count($conversation['messages']),
            ]);

        } catch (\Exception $e) {
            Log::error('AI chat failed', [
                'company_id' => $company->id,
                'message' => $validated['message'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to process chat message',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear conversation history
     *
     * DELETE /api/v1/ai/insights/chat/{conversationId}
     */
    public function clearConversation(Request $request, string $conversationId): JsonResponse
    {
        $company = Company::find($request->header('company'));

        if (! $company) {
            return response()->json([
                'error' => 'Company not found',
            ], 404);
        }

        $this->authorize('view dashboard', $company);

        try {
            $this->aiService->clearConversation($company, $conversationId);

            return response()->json([
                'message' => 'Conversation cleared successfully',
                'conversation_id' => $conversationId,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to clear conversation', [
                'company_id' => $company->id,
                'conversation_id' => $conversationId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to clear conversation',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Streaming AI chat for real-time responses
     *
     * POST /api/v1/ai/insights/chat-stream
     *
     * NOTE: Add this route to routes/api.php:
     * Route::post('ai/insights/chat-stream', [AiInsightsController::class, 'chatStream']);
     */
    public function chatStream(Request $request): StreamedResponse
    {
        $company = Company::find($request->header('company'));

        if (! $company) {
            return response()->stream(function () {
                echo "data: ".json_encode(['error' => 'Company not found'])."\n\n";
                flush();
            }, 404);
        }

        $this->authorize('view dashboard', $company);

        $validated = $request->validate([
            'message' => 'required|string|max:'.config('ai.chat.max_message_length', 1000),
        ]);

        // Check usage limit for AI queries
        $usageService = app(UsageLimitService::class);
        if (! $usageService->canUse($company, 'ai_queries_per_month')) {
            $limitResponse = $usageService->buildLimitExceededResponse($company, 'ai_queries_per_month');

            return response()->stream(function () use ($limitResponse) {
                echo "data: ".json_encode($limitResponse)."\n\n";
                flush();
            }, 403);
        }

        // Get AI provider from container
        $aiProvider = $this->resolveAiProvider();

        return response()->stream(function () use ($company, $validated, $usageService, $aiProvider) {
            // Set headers for SSE
            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            header('Connection: keep-alive');
            header('X-Accel-Buffering: no'); // Disable nginx buffering

            try {
                // Build context-aware prompt with company financial data
                $prompt = $this->buildChatPrompt($company, $validated['message']);

                // Stream the response
                $aiProvider->generateStream(
                    $prompt,
                    function ($chunk) {
                        // Send each chunk as SSE
                        echo "data: ".json_encode(['chunk' => $chunk])."\n\n";
                        if (ob_get_level() > 0) {
                            ob_flush();
                        }
                        flush();
                    }
                );

                // Increment usage after successful AI call
                $usageService->incrementUsage($company, 'ai_queries_per_month');

                // Send completion event
                echo "data: ".json_encode(['done' => true, 'timestamp' => now()->toDateTimeString()])."\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();

            } catch (\Exception $e) {
                Log::error('AI chat stream failed', [
                    'company_id' => $company->id,
                    'message' => $validated['message'],
                    'error' => $e->getMessage(),
                ]);

                echo "data: ".json_encode(['error' => 'Failed to process chat message', 'message' => $e->getMessage()])."\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Build a context-aware chat prompt with company financial data
     *
     * @param  Company  $company
     * @param  string  $message
     * @return string
     */
    private function buildChatPrompt(Company $company, string $message): string
    {
        // Build basic company context
        $context = "You are a financial assistant for {$company->name}.\n\n";
        $context .= "Company Information:\n";
        $context .= "- Name: {$company->name}\n";
        $context .= "- Currency: {$company->currency}\n\n";

        // Add the user's question
        $context .= "User Question: {$message}\n\n";
        $context .= "Please provide a helpful response in Macedonian language.";

        return $context;
    }

    /**
     * Resolve the AI provider from configuration
     *
     * @return \App\Services\AiProvider\AiProviderInterface
     */
    private function resolveAiProvider(): \App\Services\AiProvider\AiProviderInterface
    {
        $provider = config('ai.default_provider', 'claude');

        return match ($provider) {
            'claude' => new \App\Services\AiProvider\ClaudeProvider(),
            'openai' => new \App\Services\AiProvider\OpenAiProvider(),
            'gemini' => new \App\Services\AiProvider\GeminiProvider(),
            default => new \App\Services\AiProvider\NullAiProvider($provider, 'Unknown provider'),
        };
    }

    /**
     * Get AI settings and configuration
     *
     * GET /api/v1/ai/settings
     */
    public function getSettings(Request $request): JsonResponse
    {
        $company = Company::find($request->header('company'));

        if (! $company) {
            return response()->json([
                'error' => 'Company not found',
            ], 404);
        }

        $this->authorize('view dashboard', $company);

        return response()->json([
            'ai_enabled' => $company->ai_insights_enabled ?? true,
            'provider' => config('ai.default_provider'),
            'cache_ttl_hours' => config('ai.cache_ttl') / 3600,
            'language' => config('ai.insights.language'),
            'max_insights' => config('ai.insights.max_insights'),
        ]);
    }

    /**
     * Update AI settings for the company
     *
     * POST /api/v1/ai/settings
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $company = Company::find($request->header('company'));

        if (! $company) {
            return response()->json([
                'error' => 'Company not found',
            ], 404);
        }

        $this->authorize('manage-company', $company);

        $validated = $request->validate([
            'ai_enabled' => 'required|boolean',
        ]);

        try {
            $company->ai_insights_enabled = $validated['ai_enabled'];
            $company->save();

            Log::info('AI settings updated', [
                'company_id' => $company->id,
                'ai_enabled' => $validated['ai_enabled'],
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'AI settings updated successfully',
                'ai_enabled' => $company->ai_insights_enabled,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update AI settings', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to update settings',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get risk analysis for the company
     *
     * GET /api/v1/ai/risks
     */
    public function risks(Request $request): JsonResponse
    {
        $company = Company::find($request->header('company'));

        if (! $company) {
            return response()->json([
                'error' => 'Company not found',
            ], 404);
        }

        $this->authorize('view dashboard', $company);

        // Check usage limit for AI queries
        $usageService = app(UsageLimitService::class);
        if (! $usageService->canUse($company, 'ai_queries_per_month')) {
            return response()->json(
                $usageService->buildLimitExceededResponse($company, 'ai_queries_per_month'),
                403
            );
        }

        try {
            $risks = $this->aiService->detectRisks($company);

            // Increment usage after successful AI call
            $usageService->incrementUsage($company, 'ai_queries_per_month');

            return response()->json($risks);

        } catch (\Exception $e) {
            Log::error('Risk detection failed', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to detect risks',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}

// CLAUDE-CHECKPOINT
