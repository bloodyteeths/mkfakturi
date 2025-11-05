<?php

namespace App\Services;

use App\Models\Company;
use App\Services\AiProvider\AiProviderInterface;
use App\Services\AiProvider\ClaudeProvider;
use App\Services\AiProvider\GeminiProvider;
use App\Services\AiProvider\OpenAiProvider;
use App\Services\AiProvider\NullAiProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * AI Insights Service
 *
 * Main orchestrator for generating AI-powered financial insights.
 * Fetches data via MCP, builds prompts, calls AI providers, and caches results.
 */
class AiInsightsService
{
    private AiProviderInterface $aiProvider;
    private McpClient $mcpClient;
    private int $cacheTtl;

    /**
     * Create a new AI Insights service instance
     *
     * @param McpClient $mcpClient The MCP client for fetching financial data
     */
    public function __construct(McpClient $mcpClient)
    {
        $this->mcpClient = $mcpClient;
        $this->cacheTtl = config('ai.cache_ttl', 21600); // 6 hours
        $this->aiProvider = $this->resolveAiProvider();
    }

    /**
     * Analyze company financials and generate insights
     *
     * @param Company $company The company to analyze
     * @return array<string, mixed> Array with insights, timestamp, and expiry
     * @throws \Exception If analysis fails
     */
    public function analyzeFinancials(Company $company): array
    {
        $cacheKey = "insights:{$company->id}";

        // Check cache first
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            // 1. Fetch financial data from MCP
            $trialBalance = $this->fetchTrialBalance($company);
            $companyStats = $this->fetchCompanyStats($company);

            // 2. Build AI analysis prompt
            $prompt = $this->buildAnalysisPrompt($company, $trialBalance, $companyStats);

            // 3. Send to AI provider
            $response = $this->aiProvider->generate($prompt);

            // 4. Parse AI response into structured insights
            $insights = $this->parseInsights($response);

            // 5. Build result structure
            $result = [
                'items' => $insights,
                'timestamp' => Carbon::now()->toDateTimeString(),
                'expires_at' => Carbon::now()->addSeconds($this->cacheTtl)->toDateTimeString(),
                'provider' => $this->aiProvider->getProviderName(),
                'model' => $this->aiProvider->getModel(),
            ];

            // 6. Cache for configured TTL
            Cache::put($cacheKey, $result, $this->cacheTtl);

            Log::info('AI insights generated', [
                'company_id' => $company->id,
                'insights_count' => count($insights),
                'provider' => $this->aiProvider->getProviderName(),
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('AI insights generation failed', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Detect financial risks and anomalies
     *
     * @param Company $company The company to analyze
     * @return array<string, mixed> Array of detected risks
     * @throws \Exception If risk detection fails
     */
    public function detectRisks(Company $company): array
    {
        try {
            // Use MCP anomaly scan tool
            $anomalies = $this->mcpClient->call('anomaly_scan', [
                'company_id' => $company->id,
            ]);

            // Build risk analysis prompt
            $prompt = $this->buildRiskAnalysisPrompt($company, $anomalies);

            // Get AI analysis of anomalies
            $response = $this->aiProvider->generate($prompt);

            // Parse risk insights
            $risks = $this->parseInsights($response);

            return [
                'risks' => $risks,
                'anomalies_detected' => count($anomalies['items'] ?? []),
                'timestamp' => Carbon::now()->toDateTimeString(),
            ];

        } catch (\Exception $e) {
            Log::error('Risk detection failed', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Answer a financial question using AI with company context
     *
     * @param Company $company The company context
     * @param string $question The user's question
     * @return string The AI's answer
     * @throws \Exception If chat fails
     */
    public function answerQuestion(Company $company, string $question): string
    {
        try {
            // Fetch relevant financial context
            $companyStats = $this->fetchCompanyStats($company);

            // Build contextualized prompt
            $prompt = $this->buildChatPrompt($company, $companyStats, $question);

            // Get AI response
            $response = $this->aiProvider->generate($prompt, [
                'max_tokens' => 2048,
            ]);

            Log::info('AI chat question answered', [
                'company_id' => $company->id,
                'question_length' => strlen($question),
                'response_length' => strlen($response),
            ]);

            return $response;

        } catch (\Exception $e) {
            Log::error('AI chat failed', [
                'company_id' => $company->id,
                'question' => $question,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Fetch trial balance from MCP
     *
     * @param Company $company
     * @return array<string, mixed>
     */
    private function fetchTrialBalance(Company $company): array
    {
        try {
            return $this->mcpClient->call('get_trial_balance', [
                'company_id' => $company->id,
            ]);
        } catch (McpException $e) {
            Log::warning('Failed to fetch trial balance, using empty data', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'debits' => 0,
                'credits' => 0,
                'balance' => 0,
            ];
        }
    }

    /**
     * Fetch company statistics from MCP
     *
     * @param Company $company
     * @return array<string, mixed>
     */
    private function fetchCompanyStats(Company $company): array
    {
        try {
            return $this->mcpClient->call('get_company_stats', [
                'company_id' => $company->id,
            ]);
        } catch (McpException $e) {
            Log::warning('Failed to fetch company stats, using empty data', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'revenue' => 0,
                'expenses' => 0,
                'outstanding' => 0,
                'customers' => 0,
            ];
        }
    }

    /**
     * Build analysis prompt in Macedonian
     *
     * @param Company $company
     * @param array<string, mixed> $trialBalance
     * @param array<string, mixed> $stats
     * @return string
     */
    private function buildAnalysisPrompt(Company $company, array $trialBalance, array $stats): string
    {
        $companyName = $company->name;
        $currency = $company->currency ?? 'MKD';

        $debits = number_format($trialBalance['debits'] ?? 0, 2);
        $credits = number_format($trialBalance['credits'] ?? 0, 2);
        $balance = number_format($trialBalance['balance'] ?? 0, 2);

        $revenue = number_format($stats['revenue'] ?? 0, 2);
        $expenses = number_format($stats['expenses'] ?? 0, 2);
        $outstanding = number_format($stats['outstanding'] ?? 0, 2);
        $customers = $stats['customers'] ?? 0;

        return <<<PROMPT
Ти си македонски финансиски советник кој анализира финансиското здравје на компанијата.

Компанија: {$companyName}

Пробна биланса (година до денес):
- Вкупно дебити: {$debits} {$currency}
- Вкупно кредити: {$credits} {$currency}
- Биланс: {$balance} {$currency}

Статистика на компанијата:
- Вкупен приход: {$revenue} {$currency}
- Вкупни трошоци: {$expenses} {$currency}
- Неплатени фактури: {$outstanding} {$currency}
- Број на клиенти: {$customers}

Обезбеди 3-5 конкретни и корисни совети во македонски јазик, фокусирајќи се на:
1. Трендови на паричен тек
2. Однос помеѓу приходи и трошоци
3. Проблеми со наплата
4. Можности за даночна оптимизација
5. Деловни ризици или предупредувања

Форматирај го секој совет како JSON објект во следниот формат:
{
  "type": "warning|success|info",
  "title": "Кратка насловна реченица",
  "description": "Детално објаснување на ситуацијата",
  "action": "Препорачана акција што треба да ја преземе корисникот",
  "priority": [број од 1 до 5, каде 5 е највисок приоритет]
}

Врати низа од JSON објекти, без дополнителен текст. Само валиден JSON низа.
PROMPT;
    }

    /**
     * Build risk analysis prompt in Macedonian
     *
     * @param Company $company
     * @param array<string, mixed> $anomalies
     * @return string
     */
    private function buildRiskAnalysisPrompt(Company $company, array $anomalies): string
    {
        $companyName = $company->name;
        $anomaliesJson = json_encode($anomalies, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
Ти си македонски финансиски советник кој анализира финансиски ризици и аномалии.

Компанија: {$companyName}

Детектирани аномалии:
{$anomaliesJson}

Анализирај ги аномалиите и обезбеди 3-5 конкретни предупредувања за ризици на македонски јазик.

Форматирај го секое предупредување како JSON објект:
{
  "type": "warning",
  "title": "Кратка насловна реченица за ризикот",
  "description": "Детално објаснување на ризикот и потенцијалното влијание",
  "action": "Конкретни чекори за митигација на ризикот",
  "priority": [број од 1 до 5, каде 5 е критичен ризик]
}

Врати низа од JSON објекти, без дополнителен текст. Само валиден JSON низа.
PROMPT;
    }

    /**
     * Build chat prompt in Macedonian with company context
     *
     * @param Company $company
     * @param array<string, mixed> $stats
     * @param string $question
     * @return string
     */
    private function buildChatPrompt(Company $company, array $stats, string $question): string
    {
        $companyName = $company->name;
        $currency = $company->currency ?? 'MKD';

        $revenue = number_format($stats['revenue'] ?? 0, 2);
        $expenses = number_format($stats['expenses'] ?? 0, 2);
        $outstanding = number_format($stats['outstanding'] ?? 0, 2);
        $customers = $stats['customers'] ?? 0;

        return <<<PROMPT
Ти си македонски финансиски советник кој помага на корисниците со нивните финансиски прашања.

Контекст на компанијата:
- Име: {$companyName}
- Приходи: {$revenue} {$currency}
- Трошоци: {$expenses} {$currency}
- Неплатени фактури: {$outstanding} {$currency}
- Број на клиенти: {$customers}

Прашање од корисникот:
{$question}

Обезбеди јасен, конкретен и корисен одговор на македонски јазик. Користи ги податоците од компанијата за да го персонализираш одговорот. Ако прашањето бара конкретни бројки, користи ги достапните податоци.
PROMPT;
    }

    /**
     * Parse AI response into structured insights array
     *
     * @param string $response The AI's JSON response
     * @return array<int, array<string, mixed>> Array of insight objects
     */
    private function parseInsights(string $response): array
    {
        try {
            // Try to extract JSON from response (AI might add extra text)
            $jsonMatch = [];
            if (preg_match('/\[[\s\S]*\]/', $response, $jsonMatch)) {
                $response = $jsonMatch[0];
            }

            $insights = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

            if (! is_array($insights)) {
                throw new \RuntimeException('AI response is not a valid array');
            }

            // Validate and sanitize each insight
            $validated = [];
            foreach ($insights as $insight) {
                if (! is_array($insight)) {
                    continue;
                }

                $validated[] = [
                    'type' => $insight['type'] ?? 'info',
                    'title' => $insight['title'] ?? 'Совет',
                    'description' => $insight['description'] ?? '',
                    'action' => $insight['action'] ?? '',
                    'priority' => min(5, max(1, (int) ($insight['priority'] ?? 3))),
                ];
            }

            // Sort by priority (highest first)
            usort($validated, fn($a, $b) => $b['priority'] <=> $a['priority']);

            // Limit to max insights
            $maxInsights = config('ai.insights.max_insights', 5);
            return array_slice($validated, 0, $maxInsights);

        } catch (\JsonException $e) {
            Log::error('Failed to parse AI insights response', [
                'error' => $e->getMessage(),
                'response' => $response,
            ]);

            // Return fallback insight
            return [[
                'type' => 'info',
                'title' => 'Анализата е достапна',
                'description' => 'Финансиските податоци се анализирани успешно.',
                'action' => 'Проверете ги вашите финансиски извештаи за повеќе детали.',
                'priority' => 1,
            ]];
        }
    }

    /**
     * Resolve the AI provider instance based on configuration
     *
     * @return AiProviderInterface
     */
    private function resolveAiProvider(): AiProviderInterface
    {
        $provider = strtolower((string) config('ai.default_provider', 'claude'));

        try {
            return match($provider) {
                'claude' => new ClaudeProvider(),
                'openai' => new OpenAiProvider(),
                'gemini' => new GeminiProvider(),
                default => throw new \RuntimeException("Unsupported AI provider: {$provider}"),
            };
        } catch (\Throwable $e) {
            Log::warning('AI provider unavailable, using fallback', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            return new NullAiProvider($provider, $e->getMessage());
        }
    }

    /**
     * Clear cached insights for a company
     *
     * @param Company $company
     * @return void
     */
    public function clearCache(Company $company): void
    {
        Cache::forget("insights:{$company->id}");
        Log::info('AI insights cache cleared', ['company_id' => $company->id]);
    }
}
