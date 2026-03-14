<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiDraft;
use App\Models\Company;
use App\Services\AiNaturalLanguageService;
use App\Services\UsageLimitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * AI Natural Language Assistant Controller
 *
 * POST /api/v1/ai/assistant — process natural language commands
 * GET  /api/v1/ai/drafts/{id} — retrieve a draft for form pre-fill
 */
class AiAssistantController extends Controller
{
    public function __construct(
        private AiNaturalLanguageService $service
    ) {}

    /**
     * Process a natural language accounting command.
     *
     * POST /api/v1/ai/assistant
     */
    public function process(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $company = Company::find($request->header('company'));
        if (! $company) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        $user = $request->user();

        // Check AI usage limits
        $usageService = app(UsageLimitService::class);
        if (! $usageService->canUse($company, 'ai_queries_per_month')) {
            $limitResponse = $usageService->buildLimitExceededResponse($company, 'ai_queries_per_month');

            return response()->json($limitResponse, 402);
        }

        $result = $this->service->process($request->input('message'), $company, $user);

        return response()->json($result);
    }

    /**
     * Retrieve a draft for form pre-fill.
     *
     * GET /api/v1/ai/drafts/{id}
     */
    public function getDraft(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');
        if (! $companyId) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        $draft = $this->service->getDraft($id, $companyId);
        if (! $draft) {
            return response()->json(['error' => 'Draft not found or expired'], 404);
        }

        return response()->json([
            'id' => $draft->id,
            'entity_type' => $draft->entity_type,
            'entity_data' => $draft->entity_data,
            'expires_at' => $draft->expires_at?->toISOString(),
        ]);
    }

    /**
     * Mark a draft as used (called after form submission).
     *
     * POST /api/v1/ai/drafts/{id}/use
     */
    public function useDraft(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $draft = AiDraft::where('id', $id)
            ->where('company_id', $companyId)
            ->usable()
            ->first();

        if (! $draft) {
            return response()->json(['error' => 'Draft not found or already used'], 404);
        }

        $draft->markUsed();

        return response()->json(['status' => 'ok']);
    }
}
// CLAUDE-CHECKPOINT
