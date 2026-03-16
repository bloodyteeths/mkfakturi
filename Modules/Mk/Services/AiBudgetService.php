<?php

namespace Modules\Mk\Services;

use App\Models\Company;
use App\Services\AiProvider\GeminiProvider;
use App\Services\UsageLimitService;
use Illuminate\Support\Facades\Log;

/**
 * AI Budget Service
 *
 * Uses Gemini to analyze company financial data and suggest budget adjustments.
 * Each call deducts from the company's ai_queries_per_month usage quota.
 */
class AiBudgetService
{
    private ?GeminiProvider $provider = null;

    public function __construct(
        private BudgetService $budgetService
    ) {}

    /**
     * Generate AI-powered budget suggestions based on company financial data.
     *
     * @return array|null Null if usage limit exceeded or AI unavailable
     */
    public function suggestBudget(Company $company, string $year, string $locale = 'mk'): ?array
    {
        // Check AI usage limits
        if (! $this->checkUsageLimit($company)) {
            return null;
        }

        try {
            $provider = $this->getProvider();
            if (! $provider) {
                return null;
            }

            // Get smart budget data (pure SQL, no AI cost)
            $data = $this->budgetService->generateSmartBudget(
                $company->id,
                $year,
                0, // no growth applied - AI will suggest its own
                $locale
            );

            if (! $data['has_data']) {
                return [
                    'has_data' => false,
                    'message' => 'No financial data available for analysis.',
                ];
            }

            $prompt = $this->buildPrompt($data, $locale);

            $response = $provider->generate($prompt, [
                'temperature' => 0.1,
                'max_tokens' => 1024,
            ]);

            // Track usage AFTER successful call
            $this->trackUsage($company);

            $insights = $this->parseResponse($response);

            // Get current usage for frontend display
            $usage = $this->getUsage($company);

            return [
                'has_data' => true,
                'insights' => $insights,
                'usage' => $usage,
            ];
        } catch (\Exception $e) {
            Log::warning('[AiBudgetService] Budget suggestion failed', [
                'company_id' => $company->id,
                'year' => $year,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Build the Gemini prompt for budget analysis.
     */
    protected function buildPrompt(array $data, string $locale): string
    {
        $localeInstruction = match ($locale) {
            'mk' => 'Одговори целосно на македонски јазик (кирилица).',
            'sq' => 'Pergjigju plotesisht ne shqip.',
            'tr' => 'Tamamen Turkce olarak yanit verin.',
            default => 'Respond entirely in English.',
        };

        $categoriesJson = json_encode($data['categories'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
You are a financial advisor for a small Macedonian business.
{$localeInstruction}

Analyze this company's financial data from {$data['source_year']} and suggest a budget for {$data['target_year']}.

Financial Summary:
- Total Revenue: {$data['summary']['total_revenue']} MKD
- Total Expenses: {$data['summary']['total_expenses']} MKD
- Net Profit: {$data['summary']['projected_profit']} MKD

Detailed Categories:
{$categoriesJson}

Provide your response as valid JSON with this exact structure:
{
    "trend": "growing|stable|declining",
    "trend_description": "One sentence describing the overall financial trend",
    "suggested_growth_pct": 5.0,
    "adjustments": [
        {
            "category_key": "invoice_revenue",
            "suggested_total": 600000,
            "reason": "Brief reason for this adjustment"
        }
    ],
    "risks": ["Risk 1", "Risk 2"],
    "opportunities": ["Opportunity 1", "Opportunity 2"]
}

Rules:
1. suggested_growth_pct should be a realistic percentage between -20 and 30
2. adjustments array should only include categories where you suggest a different amount
3. Maximum 3 risks and 3 opportunities, each under 80 characters
4. trend_description should be under 120 characters
5. Return ONLY valid JSON, no markdown, no explanation
PROMPT;
    }

    /**
     * Parse the AI response, stripping markdown code fences.
     */
    protected function parseResponse(string $response): ?array
    {
        $cleaned = preg_replace('/^```(?:json)?\s*\n?/m', '', $response);
        $cleaned = preg_replace('/\n?```\s*$/m', '', $cleaned);
        $cleaned = trim($cleaned);

        $data = json_decode($cleaned, true);

        if (! is_array($data) || ! isset($data['trend'])) {
            Log::warning('[AiBudgetService] Failed to parse AI response', [
                'raw' => substr($response, 0, 500),
            ]);

            return null;
        }

        // Validate and sanitize
        $data['trend'] = in_array($data['trend'], ['growing', 'stable', 'declining'])
            ? $data['trend']
            : 'stable';
        $data['suggested_growth_pct'] = max(-20, min(30, (float) ($data['suggested_growth_pct'] ?? 0)));
        $data['risks'] = array_slice($data['risks'] ?? [], 0, 3);
        $data['opportunities'] = array_slice($data['opportunities'] ?? [], 0, 3);
        $data['adjustments'] = array_slice($data['adjustments'] ?? [], 0, 10);

        return $data;
    }

    protected function checkUsageLimit(Company $company): bool
    {
        try {
            return app(UsageLimitService::class)->canUse($company, 'ai_queries_per_month');
        } catch (\Exception $e) {
            return true;
        }
    }

    protected function trackUsage(Company $company): void
    {
        try {
            app(UsageLimitService::class)->incrementUsage($company, 'ai_queries_per_month');
        } catch (\Exception $e) {
            // Non-critical
        }
    }

    protected function getUsage(Company $company): array
    {
        try {
            return app(UsageLimitService::class)->getUsage($company, 'ai_queries_per_month');
        } catch (\Exception $e) {
            return ['used' => 0, 'limit' => null, 'remaining' => null];
        }
    }

    protected function getProvider(): ?GeminiProvider
    {
        if ($this->provider !== null) {
            return $this->provider;
        }

        try {
            $this->provider = app(GeminiProvider::class);

            return $this->provider;
        } catch (\Exception $e) {
            Log::warning('[AiBudgetService] Gemini provider unavailable', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}

// CLAUDE-CHECKPOINT
