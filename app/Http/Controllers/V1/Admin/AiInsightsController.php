<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateAiInsights;
use App\Models\Company;
use App\Services\AiInsightsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * AI Insights Controller
 *
 * Handles API endpoints for AI-powered financial insights and chat.
 */
class AiInsightsController extends Controller
{
    /**
     * Create a new controller instance
     *
     * @param AiInsightsService $aiService
     */
    public function __construct(
        private AiInsightsService $aiService
    ) {}

    /**
     * Get cached AI insights or return empty state
     *
     * GET /api/v1/ai/insights
     *
     * @param Request $request
     * @return JsonResponse
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
     * Generate AI insights (async via job queue)
     *
     * POST /api/v1/ai/insights/generate
     *
     * @param Request $request
     * @return JsonResponse
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

        try {
            // Dispatch async job for generation
            GenerateAiInsights::dispatch($company);

            Log::info('AI insights generation queued', [
                'company_id' => $company->id,
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'AI insights generation started',
                'status' => 'processing',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to queue AI insights generation', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to start insights generation',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Refresh insights synchronously (immediate response)
     *
     * POST /api/v1/ai/insights/refresh
     *
     * @param Request $request
     * @return JsonResponse
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

        try {
            // Clear cache first
            $this->aiService->clearCache($company);

            // Generate new insights synchronously
            $insights = $this->aiService->analyzeFinancials($company);

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
     *
     * @param Request $request
     * @return JsonResponse
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
            'message' => 'required|string|max:' . config('ai.chat.max_message_length', 1000),
        ]);

        try {
            $response = $this->aiService->answerQuestion(
                $company,
                $validated['message']
            );

            return response()->json([
                'response' => $response,
                'timestamp' => now()->toDateTimeString(),
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
     * Get AI settings and configuration
     *
     * GET /api/v1/ai/settings
     *
     * @param Request $request
     * @return JsonResponse
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
     *
     * @param Request $request
     * @return JsonResponse
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
     *
     * @param Request $request
     * @return JsonResponse
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

        try {
            $risks = $this->aiService->detectRisks($company);

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
