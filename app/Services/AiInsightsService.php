<?php

namespace App\Services;

use App\Models\Company;
use App\Services\AiProvider\AiProviderInterface;
use App\Services\AiProvider\ClaudeProvider;
use App\Services\AiProvider\GeminiProvider;
use App\Services\AiProvider\NullAiProvider;
use App\Services\AiProvider\OpenAiProvider;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * AI Insights Service
 *
 * Main orchestrator for generating AI-powered financial insights.
 * Fetches data via MCP, builds prompts, calls AI providers, and caches results.
 */
class AiInsightsService
{
    private AiProviderInterface $aiProvider;

    private McpDataProvider $dataProvider;

    private int $cacheTtl;

    /**
     * Create a new AI Insights service instance
     *
     * @param  McpDataProvider  $dataProvider  Direct data provider for financial data
     */
    public function __construct(McpDataProvider $dataProvider)
    {
        $this->dataProvider = $dataProvider;
        $this->cacheTtl = config('ai.cache_ttl', 21600); // 6 hours
        $this->aiProvider = $this->resolveAiProvider();
    }

    /**
     * Analyze company financials and generate insights
     *
     * @param  Company  $company  The company to analyze
     * @return array<string, mixed> Array with insights, timestamp, and expiry
     *
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
            Log::info('[AiInsightsService] Starting financial analysis', [
                'company_id' => $company->id,
                'company_name' => $company->name,
            ]);

            // 1. Fetch financial data from MCP
            $trialBalance = $this->fetchTrialBalance($company);
            $companyStats = $this->fetchCompanyStats($company);

            Log::info('[AiInsightsService] Data fetched from provider', [
                'company_id' => $company->id,
                'trial_balance' => $trialBalance,
                'company_stats' => $companyStats,
            ]);

            // 2. Build AI analysis prompt
            $prompt = $this->buildAnalysisPrompt($company, $trialBalance, $companyStats);

            Log::info('[AiInsightsService] Prompt built', [
                'company_id' => $company->id,
                'prompt_length' => strlen($prompt),
                'prompt_preview' => substr($prompt, 0, 200).'...',
            ]);

            // 3. Send to AI provider
            $response = $this->aiProvider->generate($prompt);

            Log::info('[AiInsightsService] AI provider response received', [
                'company_id' => $company->id,
                'response_length' => strlen($response),
                'response_preview' => substr($response, 0, 200).'...',
            ]);

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
     * @param  Company  $company  The company to analyze
     * @return array<string, mixed> Array of detected risks
     *
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
     * @param  Company  $company  The company context
     * @param  string  $question  The user's question
     * @param  array<int, array{role: string, content: string, timestamp: string}>  $conversationHistory  Previous conversation messages
     * @return string The AI's answer
     *
     * @throws \Exception If chat fails
     */
    public function answerQuestion(Company $company, string $question, array $conversationHistory = []): string
    {
        try {
            Log::info('[AiInsightsService] Chat question received', [
                'company_id' => $company->id,
                'company_name' => $company->name,
                'question' => $question,
                'history_messages_count' => count($conversationHistory),
            ]);

            // Detect query context and determine required data
            // Use fast classification if enabled, otherwise use regex patterns
            if (config('ai.use_fast_classification', true)) {
                $classificationResult = $this->classifyQueryIntentFast($question);
                $contextTypes = $classificationResult['contexts'];

                Log::info('[AiInsightsService] Query context detected (fast)', [
                    'company_id' => $company->id,
                    'question' => $question,
                    'detected_contexts' => $contextTypes,
                    'method' => 'haiku_classification',
                ]);
            } else {
                $contextTypes = $this->detectQueryContext($question);

                Log::info('[AiInsightsService] Query context detected (regex)', [
                    'company_id' => $company->id,
                    'question' => $question,
                    'detected_contexts' => $contextTypes,
                    'method' => 'regex_patterns',
                ]);
            }

            // Fetch contextual data based on detected context types
            $contextualData = $this->fetchContextualData($company, $contextTypes);

            Log::info('[AiInsightsService] Contextual data fetched', [
                'company_id' => $company->id,
                'context_types' => $contextTypes,
                'data_keys' => array_keys($contextualData),
            ]);

            // Build contextualized prompt with smart data inclusion and conversation history
            $prompt = $this->buildChatPrompt($company, $contextualData, $question, $conversationHistory);

            Log::info('[AiInsightsService] Chat prompt built', [
                'company_id' => $company->id,
                'prompt_length' => strlen($prompt),
                'full_prompt' => $prompt,
            ]);

            // Get AI response
            $response = $this->aiProvider->generate($prompt, [
                'max_tokens' => 2048,
            ]);

            Log::info('[AiInsightsService] Chat response received', [
                'company_id' => $company->id,
                'question_length' => strlen($question),
                'response_length' => strlen($response),
                'response' => $response,
            ]);

            // Safety check for empty response
            if (empty(trim($response))) {
                Log::warning('[AiInsightsService] AI returned empty response', [
                    'company_id' => $company->id,
                    'question' => $question,
                    'provider' => $this->aiProvider->getProviderName(),
                ]);
                throw new \Exception('AI provider returned an empty response');
            }

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
     * Classify query intent using fast Haiku model
     *
     * Uses Claude Haiku for 10x cheaper intent classification.
     *
     * @param  string  $question  The user's question
     * @return array{contexts: array<string>} Array with detected context types
     *
     * @throws \Exception If classification fails
     */
    private function classifyQueryIntentFast(string $question): array
    {
        try {
            Log::info('[AiInsightsService] Fast classification started', [
                'question' => $question,
            ]);

            $prompt = <<<'PROMPT'
Classify this financial question into one or more categories. Return ONLY the category names as a comma-separated list.

Categories:
- invoices: Questions about specific invoices, invoice lists, overdue/unpaid invoices
- customers: Questions about customers who owe money, customer details, outstanding balances
- trends: Questions about revenue trends, cash flow patterns, growth over time
- payment_timing: Questions about late payments, payment speed, average payment time
- top_customers: Questions about best customers, customer rankings, biggest clients
- basic: General questions about company stats

Question: {$question}

Response (comma-separated categories only):
PROMPT;

            $classificationModel = config('ai.model_routing.classification', 'claude-3-haiku-20240307');

            // Use generateWithModel to override model
            if ($this->aiProvider instanceof \App\Services\AiProvider\ClaudeProvider) {
                $response = $this->aiProvider->generateWithModel($prompt, $classificationModel, [
                    'max_tokens' => 100,
                    'temperature' => 0.3,
                ]);
            } else {
                // Fallback for other providers
                $response = $this->aiProvider->generate($prompt, [
                    'max_tokens' => 100,
                    'temperature' => 0.3,
                ]);
            }

            // Parse the response - expect comma-separated list
            $response = trim($response);
            $contexts = array_map('trim', explode(',', $response));
            $contexts = array_filter($contexts); // Remove empty values

            // Validate against known categories
            $validCategories = ['invoices', 'customers', 'trends', 'payment_timing', 'top_customers', 'basic'];
            $contexts = array_intersect($contexts, $validCategories);

            // Default to 'basic' if no valid contexts found
            if (empty($contexts)) {
                $contexts = ['basic'];
            }

            Log::info('[AiInsightsService] Fast classification completed', [
                'question' => $question,
                'classified_contexts' => $contexts,
                'model_used' => $classificationModel,
            ]);

            return ['contexts' => array_values($contexts)];

        } catch (\Exception $e) {
            Log::error('[AiInsightsService] Fast classification failed', [
                'question' => $question,
                'error' => $e->getMessage(),
            ]);

            // Fallback to basic context on error
            return ['contexts' => ['basic']];
        }
    }

    /**
     * Detect query context based on question patterns
     *
     * Analyzes the user's question to determine what type of data they need.
     * Supports both English and Macedonian queries.
     *
     * @param  string  $question  The user's question
     * @return array<string> Array of context types detected
     */
    private function detectQueryContext(string $question): array
    {
        $contexts = [];
        $questionLower = mb_strtolower($question, 'UTF-8');

        Log::info('[AiInsightsService] Detecting query context', [
            'question' => $question,
            'question_lower' => $questionLower,
        ]);

        // Invoice queries - looking for specific invoices
        $invoicePatterns = [
            // English patterns
            '/\b(show|list|get|find|search|view)\s+(invoices?|bills?)\b/iu',
            '/\b(last|recent|latest|this)\s+(month|week|year|quarter)\'?s?\s+(invoices?|bills?)\b/iu',
            '/\binvoices?\s+(from|in|for|during)\b/iu',
            '/\bhow\s+many\s+invoices?\b/iu',
            '/\boverdue\s+invoices?\b/iu',
            '/\bunpaid\s+invoices?\b/iu',
            '/\bpending\s+invoices?\b/iu',
            // Macedonian patterns
            '/\b(прикажи|покажи|листа|најди|пребарај)\s+(фактур[аие]|сметк[аие])\b/iu',
            '/\b(минат[аие]|овој|оваа|последн[аие])\s+(месец|недела|година|квартал)\s+(фактур[аие]|сметк[аие])\b/iu',
            '/\bфактур[аие]\s+(од|во|за|во текот на)\b/iu',
            '/\bколку\s+фактур[аие]\b/iu',
            '/\bзадоцнет[аие]\s+фактур[аие]\b/iu',
            '/\bнеплатен[аие]\s+фактур[аие]\b/iu',
        ];

        foreach ($invoicePatterns as $pattern) {
            if (preg_match($pattern, $questionLower)) {
                $contexts[] = 'invoices';
                Log::info('[AiInsightsService] Invoice context detected', ['pattern' => $pattern]);
                break;
            }
        }

        // Customer queries - who owes money, customer details
        $customerPatterns = [
            // English patterns
            '/\b(who|which)\s+(owes?|owing|customer|client)\b/iu',
            '/\b(outstanding|unpaid)\s+(customer|client)s?\b/iu',
            '/\b(customer|client)s?\s+(with|having)\s+(debt|balance|outstanding)\b/iu',
            '/\b(top|best|biggest)\s+(customer|client)s?\b/iu',
            '/\blist\s+(customers?|clients?)\b/iu',
            // Macedonian patterns
            '/\b(кој|која|кои)\s+(должи|клиент|купувач)\b/iu',
            '/\b(неплатен[аие]|должи)\s+(клиент[аие]|купувач[аие])\b/iu',
            '/\b(клиент[аие]|купувач[аие])\s+(со|кои имаат)\s+(долг|салдо|неплатено)\b/iu',
            '/\b(топ|најдобр[аие]|најголем[аие])\s+(клиент[аие]|купувач[аие])\b/iu',
            '/\bлиста\s+(клиент[аие]|купувач[аие])\b/iu',
        ];

        foreach ($customerPatterns as $pattern) {
            if (preg_match($pattern, $questionLower)) {
                $contexts[] = 'customers';
                Log::info('[AiInsightsService] Customer context detected', ['pattern' => $pattern]);
                break;
            }
        }

        // Trend queries - cash flow, revenue trends
        $trendPatterns = [
            // English patterns
            '/\b(cash\s*flow|revenue|sales|income)\s+(trend|pattern|over\s+time|history)\b/iu',
            '/\b(monthly|quarterly|yearly|annual)\s+(revenue|sales|income|trend)\b/iu',
            '/\bhow\s+(is|are|was|were)\s+(we|sales|revenue)\s+(doing|performing|trending)\b/iu',
            '/\b(growth|decline|increase|decrease)\s+(in|of)\s+(revenue|sales)\b/iu',
            '/\bcompare\s+(revenue|sales|income)\b/iu',
            // Macedonian patterns
            '/\b(паричен\s*тек|приход|продажба|заработка)\s+(тренд|образец|со текот на време|историја)\b/iu',
            '/\b(месечн[аие]|квартални|годишни)\s+(приход|продажба|заработка|тренд)\b/iu',
            '/\bкако\s+(се|беше|бевме|продажб[аие]|приход[аие])\s+(чувствува|изведува|трендира)\b/iu',
            '/\b(раст|пад|зголемување|намалување)\s+(во|на)\s+(приход|продажба)\b/iu',
            '/\bспореди\s+(приход|продажба|заработка)\b/iu',
        ];

        foreach ($trendPatterns as $pattern) {
            if (preg_match($pattern, $questionLower)) {
                $contexts[] = 'trends';
                Log::info('[AiInsightsService] Trend context detected', ['pattern' => $pattern]);
                break;
            }
        }

        // Payment queries - late payments, payment behavior
        $paymentPatterns = [
            // English patterns
            '/\b(late|overdue|delayed)\s+(payment|paying)\b/iu',
            '/\b(payment|paying)\s+(behavior|pattern|timing|speed)\b/iu',
            '/\bhow\s+(fast|slow|quick|long)\s+(do|are)\s+(customer|client)s?\s+pay\b/iu',
            '/\baverage\s+(payment|pay)\s+(time|period|days)\b/iu',
            '/\b(on\s*time|timely)\s+payment\b/iu',
            // Macedonian patterns
            '/\b(доцнет[аие]|задоцнет[аие])\s+(плаќањ[аие]|наплат[аие])\b/iu',
            '/\b(плаќањ[аие]|наплат[аие])\s+(однесување|образец|навремност|брзина)\b/iu',
            '/\bколку\s+(брзо|бавно|долго)\s+(клиент[аие]|купувач[аие])\s+плаќаат\b/iu',
            '/\bпросечно\s+(плаќањ[аие]|наплат[аие])\s+(време|период|денови)\b/iu',
            '/\b(навремен[аие]|навреме)\s+плаќањ[аие]\b/iu',
        ];

        foreach ($paymentPatterns as $pattern) {
            if (preg_match($pattern, $questionLower)) {
                $contexts[] = 'payment_timing';
                Log::info('[AiInsightsService] Payment timing context detected', ['pattern' => $pattern]);
                break;
            }
        }

        // Customer ranking - top customers, best clients
        $rankingPatterns = [
            // English patterns
            '/\b(top|best|biggest|largest|highest)\s+\d*\s*(customer|client)s?\b/iu',
            '/\b(customer|client)s?\s+(ranking|by\s+revenue|by\s+sales)\b/iu',
            '/\bwho\s+(are|is)\s+(my|our|the)\s+(top|best|biggest)\b/iu',
            '/\brank\s+(customer|client)s?\b/iu',
            // Macedonian patterns
            '/\b(топ|најдобр[аие]|најголем[аие]|највисок[аие])\s+\d*\s*(клиент[аие]|купувач[аие])\b/iu',
            '/\b(клиент[аие]|купувач[аие])\s+(рангирање|по\s+приход|по\s+продажба)\b/iu',
            '/\bкои\s+се\s+(мои|наши|наш[аие])\s+(топ|најдобр[аие]|најголем[аие])\b/iu',
            '/\bрангирај\s+(клиент[аие]|купувач[аие])\b/iu',
        ];

        foreach ($rankingPatterns as $pattern) {
            if (preg_match($pattern, $questionLower)) {
                $contexts[] = 'top_customers';
                Log::info('[AiInsightsService] Top customers context detected', ['pattern' => $pattern]);
                break;
            }
        }

        // If no specific context detected, use basic stats
        if (empty($contexts)) {
            $contexts[] = 'basic';
            Log::info('[AiInsightsService] No specific context detected, using basic');
        }

        Log::info('[AiInsightsService] Final detected contexts', [
            'contexts' => $contexts,
        ]);

        return array_unique($contexts);
    }

    /**
     * Fetch contextual data based on detected query context types
     *
     * @param  array<string>  $contextTypes  Detected context types
     * @return array<string, mixed> Contextual data for prompt building
     */
    private function fetchContextualData(Company $company, array $contextTypes): array
    {
        $data = [];

        try {
            // Always fetch basic company stats
            $data['company_stats'] = $this->fetchCompanyStats($company);

            Log::info('[AiInsightsService] Fetching data for contexts', [
                'company_id' => $company->id,
                'contexts' => $contextTypes,
            ]);

            // Fetch data based on detected contexts
            foreach ($contextTypes as $context) {
                switch ($context) {
                    case 'invoices':
                        // Fetch recent invoices with various statuses
                        $data['recent_invoices'] = $this->dataProvider->searchInvoices($company, [
                            'from_date' => now()->subMonths(3)->format('Y-m-d'),
                        ]);

                        // Also get overdue invoices specifically
                        $data['overdue_invoices'] = $this->dataProvider->searchInvoices($company, [
                            'status' => 'SENT',
                        ]);

                        Log::info('[AiInsightsService] Invoice data fetched', [
                            'recent_count' => count($data['recent_invoices'] ?? []),
                            'overdue_count' => count($data['overdue_invoices'] ?? []),
                        ]);
                        break;

                    case 'customers':
                        // Fetch invoices with customer details for outstanding analysis
                        $data['customer_invoices'] = $this->dataProvider->searchInvoices($company, [
                            'status' => 'SENT',
                        ]);

                        // Also get top customers for context
                        $data['top_customers'] = $this->dataProvider->getTopCustomers($company, 10);

                        Log::info('[AiInsightsService] Customer data fetched', [
                            'customer_invoices_count' => count($data['customer_invoices'] ?? []),
                            'top_customers_count' => count($data['top_customers'] ?? []),
                        ]);
                        break;

                    case 'trends':
                        // Fetch monthly trends
                        $data['monthly_trends'] = $this->dataProvider->getMonthlyTrends($company, 12);

                        // Also get customer growth for comprehensive trend analysis
                        $data['customer_growth'] = $this->dataProvider->getCustomerGrowth($company, 12);

                        Log::info('[AiInsightsService] Trend data fetched', [
                            'monthly_trends_count' => count($data['monthly_trends'] ?? []),
                            'customer_growth_count' => count($data['customer_growth'] ?? []),
                        ]);
                        break;

                    case 'payment_timing':
                        // Fetch payment timing analysis
                        $data['payment_timing'] = $this->dataProvider->getPaymentTimingAnalysis($company);

                        // Also get recent invoices to show specific examples
                        $data['recent_invoices'] = $this->dataProvider->searchInvoices($company, [
                            'from_date' => now()->subMonths(6)->format('Y-m-d'),
                        ]);

                        Log::info('[AiInsightsService] Payment timing data fetched', [
                            'payment_timing' => $data['payment_timing'],
                            'recent_invoices_count' => count($data['recent_invoices'] ?? []),
                        ]);
                        break;

                    case 'top_customers':
                        // Fetch top customers by revenue
                        $data['top_customers'] = $this->dataProvider->getTopCustomers($company, 10);

                        Log::info('[AiInsightsService] Top customers data fetched', [
                            'top_customers_count' => count($data['top_customers'] ?? []),
                        ]);
                        break;

                    case 'basic':
                        // Basic stats already fetched above
                        Log::info('[AiInsightsService] Using basic stats only');
                        break;

                    default:
                        Log::warning('[AiInsightsService] Unknown context type', [
                            'context' => $context,
                        ]);
                        break;
                }
            }

            Log::info('[AiInsightsService] All contextual data fetched successfully', [
                'company_id' => $company->id,
                'data_keys' => array_keys($data),
            ]);

            return $data;

        } catch (\Exception $e) {
            Log::error('[AiInsightsService] Failed to fetch contextual data', [
                'company_id' => $company->id,
                'contexts' => $contextTypes,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Return at least basic stats on error
            return [
                'company_stats' => $data['company_stats'] ?? $this->fetchCompanyStats($company),
            ];
        }
    }

    /**
     * Fetch trial balance from MCP
     *
     * @return array<string, mixed>
     */
    private function fetchTrialBalance(Company $company): array
    {
        return $this->dataProvider->getTrialBalance($company);
    }

    /**
     * Fetch company statistics from MCP
     *
     * @return array<string, mixed>
     */
    private function fetchCompanyStats(Company $company): array
    {
        return $this->dataProvider->getCompanyStats($company);
    }

    /**
     * Build analysis prompt in Macedonian
     *
     * @param  array<string, mixed>  $trialBalance
     * @param  array<string, mixed>  $stats
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
        $invoicesCount = $stats['invoices_count'] ?? 0;
        $pendingInvoices = $stats['pending_invoices'] ?? 0;
        $overdueInvoices = $stats['overdue_invoices'] ?? 0;
        $draftInvoices = $stats['draft_invoices'] ?? 0;

        // Payment reconciliation data
        $paymentsReceived = number_format($stats['payments_received'] ?? 0, 2);
        $paymentVariance = $stats['payment_variance'] ?? 0;
        $varianceFormatted = number_format(abs($paymentVariance), 2);
        $varianceStatus = $paymentVariance == 0
            ? '✓ Совпаѓа'
            : ($paymentVariance > 0 ? "⚠️ Разлика: +{$varianceFormatted} {$currency}" : "⚠️ Разлика: -{$varianceFormatted} {$currency}");

        // Profit calculation
        $profit = ($stats['revenue'] ?? 0) - ($stats['expenses'] ?? 0);
        $profitFormatted = number_format($profit, 2);
        $profitMargin = $revenue > 0 ? number_format(($profit / ($stats['revenue'] ?? 1)) * 100, 1) : '0.0';

        // Fetch trend data for comprehensive analysis
        $monthlyTrends = $this->dataProvider->getMonthlyTrends($company, 6); // Last 6 months
        $paymentTiming = $this->dataProvider->getPaymentTimingAnalysis($company);
        $topCustomers = $this->dataProvider->getTopCustomers($company, 5); // Top 5

        // Format trend data for prompt
        $trendsText = $this->formatMonthlyTrends($monthlyTrends, $currency);
        $paymentTimingText = $this->formatPaymentTiming($paymentTiming);
        $topCustomersText = $this->formatTopCustomers($topCustomers, $currency);

        return <<<PROMPT
Ти си македонски финансиски советник кој анализира финансиското здравје на компанијата.

Компанија: {$companyName}

Пробна биланса (година до денес):
- Вкупно дебити: {$debits} {$currency}
- Вкупно кредити: {$credits} {$currency}
- Биланс: {$balance} {$currency}

Статистика на компанијата:
- Вкупно фактури: {$invoicesCount} (Во изработка: {$draftInvoices}, Чекај наплата: {$pendingInvoices}, Задоцнети: {$overdueInvoices})
- Вкупен приход: {$revenue} {$currency}
- Вкупни трошоци: {$expenses} {$currency}
- Нето профит: {$profitFormatted} {$currency} (маржа: {$profitMargin}%)
- Наплатени плаќања: {$paymentsReceived} {$currency} {$varianceStatus}
- Неплатени фактури (износ): {$outstanding} {$currency}
- Број на клиенти: {$customers}

Трендови (последни 6 месеци):
{$trendsText}

Навреме на наплата:
{$paymentTimingText}

Топ клиенти:
{$topCustomersText}

Обезбеди 3-5 конкретни и корисни совети во македонски јазик, фокусирајќи се на:
1. Трендови на паричен тек и ликвидност
2. Профитна маржа и однос помеѓу приходи и трошоци
3. Проблеми со наплата и рекон�илијација на плаќања
4. Можности за даночна оптимизација
5. Деловни ризици или предупредувања
6. Ако има разлика помеѓу приходи и плаќања, задолжително коментирај и објасни

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
     * @param  array<string, mixed>  $anomalies
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
     * @param  array<string, mixed>  $contextualData  Data with 'company_stats' and optional contextual data
     * @param  array<int, array{role: string, content: string, timestamp: string}>  $conversationHistory  Previous conversation messages
     */
    private function buildChatPrompt(Company $company, array $contextualData, string $question, array $conversationHistory = []): string
    {
        $companyName = $company->name;
        $currency = $company->currency ?? 'MKD';

        // Detect if this is a complex analytical query
        $isComplexQuery = $this->detectComplexAnalyticalQuery($question);

        // Fetch comprehensive stats for complex queries
        if ($isComplexQuery) {
            try {
                $comprehensiveStats = $this->dataProvider->getComprehensiveStats($company);
                $contextualData['comprehensive_stats'] = $comprehensiveStats;
            } catch (\Exception $e) {
                Log::warning('[AiInsightsService] Failed to fetch comprehensive stats', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Extract company stats - handle both flat and nested comprehensive_stats structure
        $comprehensiveStats = $contextualData['comprehensive_stats'] ?? null;

        // If we have comprehensive stats (nested structure), extract the company stats from it
        if ($comprehensiveStats !== null && isset($comprehensiveStats['company'])) {
            $stats = $comprehensiveStats['company'];

            // Extract additional counts from nested structures
            $suppliersCount = $comprehensiveStats['suppliers']['suppliers_count'] ?? 0;
            $itemsCount = $comprehensiveStats['inventory']['total_items'] ?? 0;
            $estimatesCount = $comprehensiveStats['estimates']['estimates_count'] ?? 0;
            $billsCount = $comprehensiveStats['bills']['bills_count'] ?? 0;
            $projectsCount = $comprehensiveStats['projects']['projects_count'] ?? 0;
        } else {
            // Fallback to flat company_stats
            $stats = $contextualData['company_stats'] ?? [];
            $suppliersCount = $stats['suppliers'] ?? 0;
            $itemsCount = $stats['items'] ?? 0;
            $estimatesCount = $stats['estimates_count'] ?? 0;
            $billsCount = $stats['bills_count'] ?? 0;
            $projectsCount = $stats['projects_count'] ?? 0;
        }

        $revenue = number_format($stats['revenue'] ?? 0, 2);
        $expenses = number_format($stats['expenses'] ?? 0, 2);
        $outstanding = number_format($stats['outstanding'] ?? 0, 2);
        $customers = $stats['customers'] ?? 0;
        $invoicesCount = $stats['invoices_count'] ?? 0;
        $pendingInvoices = $stats['pending_invoices'] ?? 0;
        $overdueInvoices = $stats['overdue_invoices'] ?? 0;
        $draftInvoices = $stats['draft_invoices'] ?? 0;

        // Payment and profit data
        $paymentsReceived = number_format($stats['payments_received'] ?? 0, 2);
        $paymentVariance = $stats['payment_variance'] ?? 0;
        $profit = ($stats['revenue'] ?? 0) - ($stats['expenses'] ?? 0);
        $profitFormatted = number_format($profit, 2);
        $profitMargin = $revenue > 0 ? number_format(($profit / ($stats['revenue'] ?? 1)) * 100, 1) : '0.0';

        // Use the counts we extracted above
        $suppliers = $suppliersCount;
        $items = $itemsCount;
        $estimates = $estimatesCount;
        $bills = $billsCount;
        $projects = $projectsCount;
        $avgInvoiceValue = $stats['avg_invoice_value'] ?? 0;
        $avgInvoiceValueFormatted = number_format($avgInvoiceValue, 2);

        // Detect if user is asking "how to" questions
        $isHowToQuery = $this->detectHowToQuery($question);

        // Build base prompt with enhanced system instructions
        $prompt = <<<BASETEXT
Ти си македонски финансиски советник кој работи со Facturino - систем за фактурирање и финансиско управување.

Твоја улога:
- Знаеш ГИ СЈ работи во Facturino апликацијата
- Помагаш на корисниците со финансиски прашања, анализа и совети
- Одговараш на македонски јазик
- Даваш конкретни, акционабилни совети
- Може да им помогнеш да ја користат апликацијата

Контекст на компанијата:
- Име: {$companyName}
- Вкупно фактури: {$invoicesCount}
- Фактури во изработка: {$draftInvoices}
- Чекај на наплата: {$pendingInvoices}
- Задоцнети фактури: {$overdueInvoices}
- Приходи: {$revenue} {$currency}
- Трошоци: {$expenses} {$currency}
- Профит: {$profitFormatted} {$currency} (маржа: {$profitMargin}%)
- Наплатени плаќања: {$paymentsReceived} {$currency}
- Неплатени фактури (износ): {$outstanding} {$currency}
- Просечна вредност на фактура: {$avgInvoiceValueFormatted} {$currency}
- Број на клиенти: {$customers}
- Број на добавувачи: {$suppliers}
- Број на артикли: {$items}
- Понуди: {$estimates}
- Сметки од добавувачи: {$bills}
- Проекти: {$projects}

BASETEXT;

        // Add contextual data sections based on what's available
        if (! empty($contextualData['recent_invoices'])) {
            $invoicesText = $this->formatInvoicesForPrompt($contextualData['recent_invoices'], $currency);
            $prompt .= "\nПоследни фактури (последни 3 месеци):\n{$invoicesText}\n";
        }

        if (! empty($contextualData['overdue_invoices'])) {
            $overdueText = $this->formatInvoicesForPrompt($contextualData['overdue_invoices'], $currency, true);
            $prompt .= "\nЗадоцнети фактури:\n{$overdueText}\n";
        }

        if (! empty($contextualData['customer_invoices'])) {
            $customerInvoicesText = $this->formatCustomerInvoicesForPrompt($contextualData['customer_invoices'], $currency);
            $prompt .= "\nФактури по клиент (неплатени):\n{$customerInvoicesText}\n";
        }

        if (! empty($contextualData['monthly_trends'])) {
            $trendsText = $this->formatMonthlyTrends($contextualData['monthly_trends'], $currency);
            $prompt .= "\nМесечни трендови:\n{$trendsText}\n";
        }

        if (! empty($contextualData['customer_growth'])) {
            $growthText = $this->formatCustomerGrowth($contextualData['customer_growth']);
            $prompt .= "\nРаст на клиенти:\n{$growthText}\n";
        }

        if (! empty($contextualData['payment_timing'])) {
            $paymentTimingText = $this->formatPaymentTiming($contextualData['payment_timing']);
            $prompt .= "\nНавремност на наплата:\n{$paymentTimingText}\n";
        }

        if (! empty($contextualData['top_customers'])) {
            $topCustomersText = $this->formatTopCustomers($contextualData['top_customers'], $currency);
            $prompt .= "\nТоп клиенти:\n{$topCustomersText}\n";
        }

        // Add app documentation for "how to" queries
        if ($isHowToQuery) {
            $appDocs = $this->getAppDocumentation();
            $prompt .= "\n{$appDocs}\n";
        }

        // Detect conversation references and extract entities
        $conversationReferences = [];
        $entities = [];
        $conversationSummary = '';

        if (! empty($conversationHistory)) {
            // Detect if user is referring to previous conversation
            $conversationReferences = $this->detectConversationReferences($question);

            // Extract entities from conversation history
            $entities = $this->extractEntitiesFromConversation($conversationHistory);

            // Create summary for long conversations
            $conversationSummary = $this->summarizeConversationContext($conversationHistory);
        }

        // Add conversation history if available (last 10 messages)
        if (! empty($conversationHistory)) {
            // If conversation is long, show summary first
            if (! empty($conversationSummary)) {
                $prompt .= "\n{$conversationSummary}\n";
            }

            $prompt .= "\nПретходна конверзација:\n";

            // Limit to last 10 messages to avoid token overflow
            $recentHistory = array_slice($conversationHistory, -10);

            foreach ($recentHistory as $message) {
                $role = $message['role'] === 'user' ? 'Корисник' : 'Асистент';
                $content = $message['content'];
                $prompt .= "{$role}: {$content}\n";
            }

            $prompt .= "\n";

            // Add entity summary if entities were found
            if (! empty(array_filter($entities))) {
                $prompt .= "Клучни ентитети споменати во конверзацијата:\n";

                if (! empty($entities['invoice_numbers'])) {
                    $prompt .= '- Фактури: '.implode(', ', $entities['invoice_numbers'])."\n";
                }
                if (! empty($entities['customer_names'])) {
                    $prompt .= '- Клиенти: '.implode(', ', $entities['customer_names'])."\n";
                }
                if (! empty($entities['amounts'])) {
                    $amountTexts = array_map(fn ($a) => $a['full'], array_slice($entities['amounts'], 0, 5));
                    $prompt .= '- Износи: '.implode(', ', $amountTexts)."\n";
                }
                if (! empty($entities['dates'])) {
                    $prompt .= '- Датуми: '.implode(', ', array_slice($entities['dates'], 0, 5))."\n";
                }
                if (! empty($entities['item_names'])) {
                    $prompt .= '- Артикли: '.implode(', ', $entities['item_names'])."\n";
                }

                $prompt .= "\n";
            }

            // Add explicit context instruction if references detected
            if (! empty($conversationReferences)) {
                $prompt .= "ВАЖНО: Корисникот се повикува на претходен контекст во конверзацијата.\n";
                $prompt .= "Детектирани референци: ".implode(', ', $conversationReferences)."\n";

                if (! empty($entities['invoice_numbers']) || ! empty($entities['customer_names']) || ! empty($entities['amounts'])) {
                    $entityList = [];
                    if (! empty($entities['invoice_numbers'])) {
                        $entityList[] = 'фактури ('.implode(', ', array_slice($entities['invoice_numbers'], 0, 3)).')';
                    }
                    if (! empty($entities['customer_names'])) {
                        $entityList[] = 'клиенти ('.implode(', ', array_slice($entities['customer_names'], 0, 3)).')';
                    }
                    if (! empty($entities['amounts'])) {
                        $amounts = array_slice($entities['amounts'], 0, 3);
                        $amountTexts = array_map(fn ($a) => $a['full'], $amounts);
                        $entityList[] = 'износи ('.implode(', ', $amountTexts).')';
                    }

                    $prompt .= 'Претходно дискутиравме за: '.implode(', ', $entityList).".\n";
                }

                $prompt .= "Кога корисникот каже 'тоа', 'ова', 'истото', 'претходното', се однесува на горенаведените теми и ентитети.\n\n";
            }
        }

        // Add question and enhanced instructions
        $prompt .= <<<INSTRUCTIONS

Прашање од корисникот:
{$question}

INSTRUCTIONS;

        // Add special instructions and detailed data for complex analytical queries
        if ($isComplexQuery) {
            // Fetch item sales analysis for profit optimization questions
            try {
                $itemSales = $this->dataProvider->getItemSalesAnalysis($company, 3);
                if (!empty($itemSales['items'])) {
                    $prompt .= "\n**ДЕТАЛНА АНАЛИЗА НА ПРОДАЖБА ПО АРТИКЛИ (последни 3 месеци):**\n";
                    $prompt .= "Тековен профит: " . number_format($itemSales['totals']['profit'] ?? 0, 2) . " {$currency}\n";
                    $prompt .= "Тековен приход: " . number_format($itemSales['totals']['revenue'] ?? 0, 2) . " {$currency}\n";
                    $prompt .= "Тековни трошоци: " . number_format($itemSales['totals']['expenses'] ?? 0, 2) . " {$currency}\n";
                    $prompt .= "Профитна маржа: " . ($itemSales['analysis']['profit_margin_percent'] ?? 0) . "%\n";
                    $prompt .= "Просечен месечен приход: " . number_format($itemSales['analysis']['avg_monthly_revenue'] ?? 0, 2) . " {$currency}\n";
                    $prompt .= "Просечен месечен профит: " . number_format($itemSales['analysis']['avg_monthly_profit'] ?? 0, 2) . " {$currency}\n\n";

                    $prompt .= "Топ артикли по приход:\n";
                    foreach (array_slice($itemSales['items'], 0, 15) as $item) {
                        $prompt .= "- {$item['name']}: {$item['total_revenue']} {$currency} ({$item['total_quantity']} продадени, просечна цена {$item['avg_price']} {$currency}, {$item['revenue_contribution_percent']}% од вкупен приход)\n";
                    }
                    $prompt .= "\n";
                }
            } catch (\Exception $e) {
                Log::warning('[AiInsightsService] Failed to fetch item sales for complex query', ['error' => $e->getMessage()]);
            }

            // Add customer dependency analysis for risk-related questions
            try {
                $customerDependency = $comprehensiveStats['customer_dependency'] ?? $this->dataProvider->getCustomerDependencyAnalysis($company);
                if (!empty($customerDependency['customers'])) {
                    $prompt .= "\n**АНАЛИЗА НА ЗАВИСНОСТ ОД КЛИЕНТИ:**\n";
                    $prompt .= "Ризик скор: " . ($customerDependency['risk_score'] ?? 0) . "/100\n";
                    $prompt .= "Ниво на ризик: " . ($customerDependency['risk_level'] ?? 'unknown') . "\n";
                    $prompt .= "Топ 1 клиент: " . ($customerDependency['concentration']['top_1_percent'] ?? 0) . "% од приходот\n";
                    $prompt .= "Топ 3 клиенти: " . ($customerDependency['concentration']['top_3_percent'] ?? 0) . "% од приходот\n";
                    $prompt .= "Топ 5 клиенти: " . ($customerDependency['concentration']['top_5_percent'] ?? 0) . "% од приходот\n\n";

                    if (!empty($customerDependency['customers'])) {
                        $prompt .= "Детали по клиент:\n";
                        foreach (array_slice($customerDependency['customers'], 0, 10) as $customer) {
                            $customerName = is_array($customer['customer_name'] ?? null) ? json_encode($customer['customer_name']) : ($customer['customer_name'] ?? 'Unknown');
                            $prompt .= "- {$customerName}: " . number_format($customer['total_revenue'] ?? 0, 2) . " {$currency} (" . ($customer['revenue_percent'] ?? 0) . "% од вкупно)\n";
                        }
                        $prompt .= "\n";
                    }
                }
            } catch (\Exception $e) {
                Log::warning('[AiInsightsService] Failed to fetch customer dependency for complex query', ['error' => $e->getMessage()]);
            }

            // Add financial projections for forecasting questions
            try {
                $projections = $comprehensiveStats['projections'] ?? $this->dataProvider->getFinancialProjections($company, 6);
                if (!empty($projections['forecast'])) {
                    $prompt .= "\n**ФИНАНСИСКИ ПРОЕКЦИИ (6 месеци напред):**\n";
                    $prompt .= "Месечна стапка на раст: " . number_format($projections['growth_rates']['monthly_growth_percent'] ?? 0, 1) . "%\n";
                    $prompt .= "Годишна стапка на раст: " . number_format($projections['growth_rates']['annual_growth_percent'] ?? 0, 1) . "%\n";
                    $prompt .= "Просечен месечен приход: " . number_format($projections['averages']['avg_monthly_revenue'] ?? 0, 2) . " {$currency}\n\n";

                    $prompt .= "Проекција по месец:\n";
                    foreach ($projections['forecast'] as $month) {
                        $prompt .= "- {$month['month']}: " . number_format($month['projected_revenue'] ?? 0, 2) . " {$currency}\n";
                    }

                    if (!empty($projections['break_even'])) {
                        $prompt .= "\nБрејк-ивен анализа:\n";
                        $prompt .= "- Месечни фиксни трошоци: " . number_format($projections['break_even']['monthly_fixed_costs'] ?? 0, 2) . " {$currency}\n";
                        $prompt .= "- Потребен приход за брејк-ивен: " . number_format($projections['break_even']['required_revenue'] ?? 0, 2) . " {$currency}\n";
                    }
                    $prompt .= "\n";
                }
            } catch (\Exception $e) {
                Log::warning('[AiInsightsService] Failed to fetch projections for complex query', ['error' => $e->getMessage()]);
            }

            $prompt .= <<<COMPLEX_INSTRUCTIONS

**ИНСТРУКЦИИ ЗА КОМПЛЕКСНИ ПРАШАЊА:**

Кога корисникот бара оптимизација на профит (пр. "колку % да ги зголемам цените за да имам 5 милиони профит"):
1. **ТЕКОВНА СОСТОЈБА**: Покажи тековен приход, трошоци, профит
2. **ЦЕЛ**: Дефинирај ја целта (пр. 5,000,000 MKD профит)
3. **РАЗЛИКА**: Пресметај колку недостасува (цел - тековен профит)
4. **КАЛКУЛАЦИЈА**:
   - Ако профитната маржа е X%, тогаш за Y профит, потребен приход = Y / (X/100)
   - Процент на зголемување = ((потребен приход - тековен приход) / тековен приход) * 100
5. **ПРЕПОРАКА ПО АРТИКЛ**: Кои артикли да се зголемат и зошто:
   - Артикли со најголем придонес - мало зголемување има големо влијание
   - Артикли со ниска цена - може да поднесат поголемо зголемување
6. **КОНКРЕТЕН ПЛАН**: Табела со артикл, тековна цена, нова цена, % зголемување

Пример формат за одговор:
```
📊 ТЕКОВНА СОСТОЈБА:
- Приход: 3,000,000 MKD
- Трошоци: 2,500,000 MKD
- Профит: 500,000 MKD (маржа 16.7%)

🎯 ЦЕЛ: 5,000,000 MKD нето профит

📈 КАЛКУЛАЦИЈА:
- Недостасува: 4,500,000 MKD профит
- Со 16.7% маржа, потребен приход: 30,000,000 MKD
- Потребно зголемување на приходот: 900%

💡 ПРЕПОРАКА:
За да го достигнете ова реално:
- Опција А: Зголеми цени за 15% + намали трошоци за 10%
- Опција Б: Фокусирај се на артикли со највисока маржа

📋 ПЛАН ПО АРТИКЛИ:
| Артикл | Тековна цена | Нова цена | % |
|--------|--------------|-----------|---|
| Артикл 1 | 1000 MKD | 1150 MKD | +15% |
```

За сценарио анализа (пр. "што ако ги изгубам топ 3 клиенти"):
1. Идентификувај ги клиентите и нивниот придонес
2. Пресметај загуба на приход
3. Пресметај влијание на профит
4. Дај план за компензација

За прашања за трендови и предикции:
1. Анализирај историски трендови
2. Пресметај стапка на раст
3. Проектирај во иднина (линеарно)
4. Кажи колку доверлива е проекцијата

COMPLEX_INSTRUCTIONS;
        }

        $prompt .= <<<FINAL_INSTRUCTIONS

Обезбеди јасен, конкретен и корисен одговор на македонски јазик. Користи ги податоците од компанијата за да го персонализираш одговорот. Ако прашањето бара конкретни бројки, користи ги достапните податоци. ВАЖНО: Ако има креирани фактури (invoicesCount > 0), секогаш спомени го тој број во одговорот. Користи ги детаљните податоци погоре за да дадеш прецизен и релевантен одговор.

Ако корисникот се повикува на претходната конверзација (на пример, "што рековте порано", "за тоа што го споменавме", "продолжи", "дај ми повеќе детали"), користи го контекстот од претходната конверзација за да дадеш релевантен одговор.
FINAL_INSTRUCTIONS;

        return $prompt;
    }

    /**
     * Clear conversation history for a specific conversation
     *
     * @param  Company  $company  The company
     * @param  string  $conversationId  The conversation ID to clear
     * @return void
     */
    public function clearConversation(Company $company, string $conversationId): void
    {
        $cacheKey = "ai_chat:{$company->id}:{$conversationId}";
        Cache::forget($cacheKey);

        Log::info('AI conversation cleared', [
            'company_id' => $company->id,
            'conversation_id' => $conversationId,
        ]);
    }

    /**
     * Parse AI response into structured insights array
     *
     * @param  string  $response  The AI's JSON response
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
            usort($validated, fn ($a, $b) => $b['priority'] <=> $a['priority']);

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
     */
    private function resolveAiProvider(): AiProviderInterface
    {
        $provider = strtolower((string) config('ai.default_provider', 'claude'));

        try {
            return match ($provider) {
                'claude' => new ClaudeProvider,
                'openai' => new OpenAiProvider,
                'gemini' => new GeminiProvider,
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
     */
    public function clearCache(Company $company): void
    {
        Cache::forget("insights:{$company->id}");
        Log::info('AI insights cache cleared', ['company_id' => $company->id]);
    }

    /**
     * Format monthly trends for AI prompt
     *
     * @param  array<int, array{month: string, revenue: float, expenses: float, profit: float, invoice_count: int}>  $trends
     */
    private function formatMonthlyTrends(array $trends, string $currency): string
    {
        if (empty($trends)) {
            return 'Нема доволно податоци за трендови';
        }

        $lines = [];
        foreach ($trends as $trend) {
            $month = $trend['month'];
            $revenue = number_format($trend['revenue'], 2);
            $expenses = number_format($trend['expenses'], 2);
            $profit = number_format($trend['profit'], 2);
            $invoices = $trend['invoice_count'];

            $lines[] = "  {$month}: Приход {$revenue} {$currency}, Трошоци {$expenses} {$currency}, Профит {$profit} {$currency} ({$invoices} фактури)";
        }

        return implode("\n", $lines);
    }

    /**
     * Format payment timing analysis for AI prompt
     *
     * @param  array{avg_days_to_payment: float, on_time_percentage: float, late_percentage: float}  $timing
     */
    private function formatPaymentTiming(array $timing): string
    {
        $avgDays = $timing['avg_days_to_payment'] ?? 0;
        $onTime = $timing['on_time_percentage'] ?? 0;
        $late = $timing['late_percentage'] ?? 0;

        return <<<TEXT
- Просечно време до наплата: {$avgDays} денови
- Навремени наплати: {$onTime}%
- Доцнети наплати: {$late}%
TEXT;
    }

    /**
     * Format top customers for AI prompt
     *
     * @param  array<int, array{customer_name: string, revenue: float, invoice_count: int}>  $customers
     */
    private function formatTopCustomers(array $customers, string $currency): string
    {
        if (empty($customers)) {
            return 'Нема доволно податоци за клиенти';
        }

        $lines = [];
        foreach ($customers as $index => $customer) {
            $rank = $index + 1;
            $name = $customer['customer_name'];
            $revenue = number_format($customer['revenue'], 2);
            $invoices = $customer['invoice_count'];

            $lines[] = "  {$rank}. {$name}: {$revenue} {$currency} ({$invoices} фактури)";
        }

        return implode("\n", $lines);
    }

    /**
     * Format invoices list for AI prompt
     *
     * @param  array<int, array>  $invoices  Array of invoice data
     * @param  bool  $overdueOnly  Whether to filter only overdue invoices
     */
    private function formatInvoicesForPrompt(array $invoices, string $currency, bool $overdueOnly = false): string
    {
        if (empty($invoices)) {
            return 'Нема фактури';
        }

        $lines = [];
        $count = 0;
        $maxDisplay = 10; // Limit to avoid excessive token usage

        foreach ($invoices as $invoice) {
            // Filter overdue if requested
            if ($overdueOnly) {
                $dueDate = isset($invoice['due_date']) ? \Carbon\Carbon::parse($invoice['due_date']) : null;
                if (! $dueDate || $dueDate->isFuture()) {
                    continue;
                }
            }

            if ($count >= $maxDisplay) {
                $remaining = count($invoices) - $count;
                $lines[] = "  ... и уште {$remaining} фактури";
                break;
            }

            $invoiceNumber = $invoice['invoice_number'] ?? 'N/A';
            $customerName = $invoice['customer_name'] ?? 'Unknown';
            $total = number_format($invoice['total'] ?? 0, 2);
            $dueAmount = number_format($invoice['due_amount'] ?? 0, 2);
            $status = $invoice['paid_status'] ?? $invoice['status'] ?? 'N/A';
            $invoiceDate = $invoice['invoice_date'] ?? 'N/A';
            $dueDate = $invoice['due_date'] ?? 'N/A';

            $lines[] = "  - #{$invoiceNumber}: {$customerName}, {$total} {$currency} (должи: {$dueAmount} {$currency}), статус: {$status}, датум: {$invoiceDate}, рок: {$dueDate}";
            $count++;
        }

        return implode("\n", $lines);
    }

    /**
     * Format customer invoices grouped by customer for AI prompt
     *
     * @param  array<int, array>  $invoices  Array of invoice data
     */
    private function formatCustomerInvoicesForPrompt(array $invoices, string $currency): string
    {
        if (empty($invoices)) {
            return 'Нема неплатени фактури';
        }

        // Group invoices by customer
        $byCustomer = [];
        foreach ($invoices as $invoice) {
            $customerName = $invoice['customer_name'] ?? 'Unknown';
            if (! isset($byCustomer[$customerName])) {
                $byCustomer[$customerName] = [
                    'count' => 0,
                    'total_due' => 0,
                    'invoices' => [],
                ];
            }
            $byCustomer[$customerName]['count']++;
            $byCustomer[$customerName]['total_due'] += $invoice['due_amount'] ?? 0;
            $byCustomer[$customerName]['invoices'][] = $invoice;
        }

        // Sort by total due (descending)
        uasort($byCustomer, fn ($a, $b) => $b['total_due'] <=> $a['total_due']);

        $lines = [];
        $maxCustomers = 10;
        $count = 0;

        foreach ($byCustomer as $customerName => $data) {
            if ($count >= $maxCustomers) {
                $remaining = count($byCustomer) - $count;
                $lines[] = "  ... и уште {$remaining} клиенти";
                break;
            }

            $totalDue = number_format($data['total_due'], 2);
            $invoiceCount = $data['count'];
            $lines[] = "  - {$customerName}: {$totalDue} {$currency} ({$invoiceCount} фактури)";

            $count++;
        }

        return implode("\n", $lines);
    }

    /**
     * Format customer growth for AI prompt
     *
     * @param  array<int, array{month: string, new_customers: int, total_customers: int}>  $growth
     */
    private function formatCustomerGrowth(array $growth): string
    {
        if (empty($growth)) {
            return 'Нема доволно податоци за раст на клиенти';
        }

        $lines = [];
        // Show only last 6 months to keep prompt concise
        $recent = array_slice($growth, -6);

        foreach ($recent as $monthData) {
            $month = $monthData['month'];
            $newCustomers = $monthData['new_customers'];
            $totalCustomers = $monthData['total_customers'];

            $lines[] = "  {$month}: +{$newCustomers} нови (вкупно: {$totalCustomers})";
        }

        return implode("\n", $lines);
    }

    /**
     * Check if a specific AI feature is enabled
     *
     * @param  string  $featureName  Feature name from config('ai.features')
     */
    private function checkFeatureFlag(string $featureName): bool
    {
        $enabled = config("ai.features.{$featureName}", false);

        Log::debug('[AiInsightsService] Feature flag checked', [
            'feature' => $featureName,
            'enabled' => $enabled,
        ]);

        return (bool) $enabled;
    }

    /**
     * Check if PDF analysis feature is enabled
     */
    public function isPdfAnalysisEnabled(): bool
    {
        return $this->checkFeatureFlag('pdf_analysis');
    }

    /**
     * Check if receipt scanning feature is enabled
     */
    public function isReceiptScanningEnabled(): bool
    {
        return $this->checkFeatureFlag('receipt_scanning');
    }

    /**
     * Check if invoice extraction feature is enabled
     */
    public function isInvoiceExtractionEnabled(): bool
    {
        return $this->checkFeatureFlag('invoice_extraction');
    }

    /**
     * Get list of enabled AI features
     *
     * @return array<string, bool> Array of feature names and their enabled status
     */
    public function getEnabledFeatures(): array
    {
        $features = config('ai.features', []);
        $enabled = [];

        foreach ($features as $feature => $status) {
            $enabled[$feature] = (bool) $status;
        }

        Log::debug('[AiInsightsService] Enabled features retrieved', [
            'features' => $enabled,
        ]);

        return $enabled;
    }

    /**
     * Ensure a feature is enabled, throw exception if not
     *
     * @param  string  $featureName  Feature name from config
     * @param  string  $macedonianName  Macedonian name for error message
     *
     * @throws \Exception If feature is disabled
     */
    private function requireFeature(string $featureName, string $macedonianName): void
    {
        if (! $this->checkFeatureFlag($featureName)) {
            Log::warning('[AiInsightsService] Feature access blocked', [
                'feature' => $featureName,
                'reason' => 'Feature flag disabled',
            ]);

            throw new \Exception(
                "Функцијата \"{$macedonianName}\" не е овозможена. ".
                'Ве молиме контактирајте го администраторот за да ја активира.'
            );
        }
    }

    /**
     * Analyze PDF document using AI vision
     *
     * @param  string  $pdfPath  Path to PDF file
     * @param  string  $analysisType  Type of analysis (receipt, invoice, general)
     * @param  array<string, mixed>  $options  Additional options
     * @return array<string, mixed> Analysis results
     *
     * @throws \Exception If PDF analysis is disabled or fails
     */
    public function analyzePdf(string $pdfPath, string $analysisType = 'general', array $options = []): array
    {
        // Guard: Check if PDF analysis is enabled
        $this->requireFeature('pdf_analysis', 'анализа на PDF документи');

        try {
            Log::info('[AiInsightsService] Starting PDF analysis', [
                'path' => $pdfPath,
                'type' => $analysisType,
            ]);

            // Convert PDF to images
            $converter = new PdfImageConverter;
            $images = $converter->convertToImages($pdfPath, $options);

            // Build vision analysis prompt based on type
            $prompt = $this->buildPdfAnalysisPrompt($analysisType);

            // Send to AI provider with vision capabilities
            $response = $this->aiProvider->generateWithVision($prompt, $images);

            Log::info('[AiInsightsService] PDF analysis completed', [
                'path' => $pdfPath,
                'type' => $analysisType,
                'pages_analyzed' => count($images),
            ]);

            return [
                'analysis' => $response,
                'pages' => count($images),
                'type' => $analysisType,
                'timestamp' => Carbon::now()->toDateTimeString(),
            ];

        } catch (\Exception $e) {
            Log::error('[AiInsightsService] PDF analysis failed', [
                'path' => $pdfPath,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Scan receipt image and extract data
     *
     * @param  string  $imagePath  Path to receipt image
     * @param  array<string, mixed>  $options  Additional options
     * @return array<string, mixed> Extracted receipt data
     *
     * @throws \Exception If receipt scanning is disabled or fails
     */
    public function scanReceipt(string $imagePath, array $options = []): array
    {
        // Guard: Check if receipt scanning is enabled
        $this->requireFeature('receipt_scanning', 'скенирање на сметки');

        try {
            Log::info('[AiInsightsService] Starting receipt scan', [
                'path' => $imagePath,
            ]);

            // Read image and encode
            $imageData = base64_encode(file_get_contents($imagePath));
            $mediaType = $this->detectImageMediaType($imagePath);

            $images = [[
                'data' => $imageData,
                'media_type' => $mediaType,
                'page' => 1,
            ]];

            // Build receipt scanning prompt
            $prompt = $this->buildReceiptScanningPrompt();

            // Send to AI provider
            $response = $this->aiProvider->generateWithVision($prompt, $images);

            // Parse structured data
            $receiptData = $this->parseReceiptData($response);

            Log::info('[AiInsightsService] Receipt scan completed', [
                'path' => $imagePath,
                'items_found' => count($receiptData['items'] ?? []),
            ]);

            return $receiptData;

        } catch (\Exception $e) {
            Log::error('[AiInsightsService] Receipt scanning failed', [
                'path' => $imagePath,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Extract invoice data from document
     *
     * @param  string  $documentPath  Path to invoice document (PDF or image)
     * @param  array<string, mixed>  $options  Additional options
     * @return array<string, mixed> Extracted invoice data
     *
     * @throws \Exception If invoice extraction is disabled or fails
     */
    public function extractInvoiceData(string $documentPath, array $options = []): array
    {
        // Guard: Check if invoice extraction is enabled
        $this->requireFeature('invoice_extraction', 'извлекување на податоци од фактури');

        try {
            Log::info('[AiInsightsService] Starting invoice extraction', [
                'path' => $documentPath,
            ]);

            // Determine if PDF or image
            $isPdf = strtolower(pathinfo($documentPath, PATHINFO_EXTENSION)) === 'pdf';

            if ($isPdf) {
                // Convert PDF to images
                $converter = new PdfImageConverter;
                $images = $converter->convertToImages($documentPath, $options);
            } else {
                // Direct image processing
                $imageData = base64_encode(file_get_contents($documentPath));
                $mediaType = $this->detectImageMediaType($documentPath);

                $images = [[
                    'data' => $imageData,
                    'media_type' => $mediaType,
                    'page' => 1,
                ]];
            }

            // Build invoice extraction prompt
            $prompt = $this->buildInvoiceExtractionPrompt();

            // Send to AI provider
            $response = $this->aiProvider->generateWithVision($prompt, $images);

            // Parse structured invoice data
            $invoiceData = $this->parseInvoiceData($response);

            Log::info('[AiInsightsService] Invoice extraction completed', [
                'path' => $documentPath,
                'items_found' => count($invoiceData['items'] ?? []),
            ]);

            return $invoiceData;

        } catch (\Exception $e) {
            Log::error('[AiInsightsService] Invoice extraction failed', [
                'path' => $documentPath,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Build PDF analysis prompt based on analysis type
     *
     * @param  string  $type  Analysis type
     */
    private function buildPdfAnalysisPrompt(string $type): string
    {
        return match ($type) {
            'receipt' => 'Анализирај ја оваа сметка и извлечи ги сите релевантни податоци (продавач, ставки, износи, данок, вкупно).',
            'invoice' => 'Анализирај ја оваа фактура и извлечи ги сите релевантни податоци (издавач, примач, ставки, износи, датуми).',
            default => 'Анализирај го овој документ и извлечи ги клучните финансиски информации.',
        };
    }

    /**
     * Build receipt scanning prompt in Macedonian
     */
    private function buildReceiptScanningPrompt(): string
    {
        return <<<'PROMPT'
Анализирај ја оваа сметка и извлечи ги следните податоци во JSON формат:
{
  "merchant_name": "Име на продавачот",
  "merchant_address": "Адреса",
  "merchant_tax_id": "Даночен број",
  "date": "Датум (YYYY-MM-DD формат)",
  "time": "Време",
  "items": [
    {
      "name": "Опис на артикл",
      "quantity": 1.0,
      "price": 100.00,
      "total": 100.00
    }
  ],
  "subtotal": 100.00,
  "tax": 18.00,
  "total": 118.00,
  "payment_method": "Готовина/Картичка",
  "receipt_number": "Број на сметка"
}

Врати само валиден JSON без дополнителен текст.
PROMPT;
    }

    /**
     * Build invoice extraction prompt in Macedonian
     */
    private function buildInvoiceExtractionPrompt(): string
    {
        return <<<'PROMPT'
Анализирај ја оваа фактура и извлечи ги следните податоци во JSON формат:
{
  "invoice_number": "Број на фактура",
  "invoice_date": "Датум (YYYY-MM-DD формат)",
  "due_date": "Рок за плаќање (YYYY-MM-DD формат)",
  "seller": {
    "name": "Име на издавач",
    "address": "Адреса",
    "tax_id": "Даночен број",
    "vat_number": "ДДВ број"
  },
  "buyer": {
    "name": "Име на примач",
    "address": "Адреса",
    "tax_id": "Даночен број"
  },
  "items": [
    {
      "description": "Опис",
      "quantity": 1.0,
      "unit": "парче/кг/м",
      "unit_price": 100.00,
      "total": 100.00,
      "tax_rate": 18.0
    }
  ],
  "subtotal": 100.00,
  "tax": 18.00,
  "total": 118.00,
  "currency": "MKD",
  "payment_terms": "Услови за плаќање",
  "notes": "Забелешки"
}

Врати само валиден JSON без дополнителен текст.
PROMPT;
    }

    /**
     * Parse receipt data from AI response
     *
     * @param  string  $response  AI response
     * @return array<string, mixed>
     */
    private function parseReceiptData(string $response): array
    {
        try {
            // Extract JSON from response
            $jsonMatch = [];
            if (preg_match('/\{[\s\S]*\}/', $response, $jsonMatch)) {
                $response = $jsonMatch[0];
            }

            return json_decode($response, true, 512, JSON_THROW_ON_ERROR);

        } catch (\JsonException $e) {
            Log::error('[AiInsightsService] Failed to parse receipt data', [
                'error' => $e->getMessage(),
                'response' => $response,
            ]);

            throw new \Exception('Не успеа парсирањето на податоците од сметката.');
        }
    }

    /**
     * Parse invoice data from AI response
     *
     * @param  string  $response  AI response
     * @return array<string, mixed>
     */
    private function parseInvoiceData(string $response): array
    {
        try {
            // Extract JSON from response
            $jsonMatch = [];
            if (preg_match('/\{[\s\S]*\}/', $response, $jsonMatch)) {
                $response = $jsonMatch[0];
            }

            return json_decode($response, true, 512, JSON_THROW_ON_ERROR);

        } catch (\JsonException $e) {
            Log::error('[AiInsightsService] Failed to parse invoice data', [
                'error' => $e->getMessage(),
                'response' => $response,
            ]);

            throw new \Exception('Не успеа парсирањето на податоците од фактурата.');
        }
    }

    /**
     * Detect image media type from file path
     *
     * @param  string  $path  File path
     * @return string Media type
     */
    private function detectImageMediaType(string $path): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        $types = [
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
        ];

        return $types[$extension] ?? 'image/png';
    }

    /**
     * Detect if query is asking for complex analytical calculations
     *
     * @param  string  $question  The user's question
     * @return bool True if complex analytical query detected
     */
    private function detectComplexAnalyticalQuery(string $question): bool
    {
        $questionLower = mb_strtolower($question, 'UTF-8');

        $complexPatterns = [
            // Profit optimization patterns
            '/колку\s+(проценти|%|пати)\s+(да|треба)/iu',
            '/колку\s+%/iu',
            '/колку\s+процент/iu',
            '/да\s+(имам|постигнам|добијам)\s+\d+/iu',
            '/за\s+да\s+(имам|достигнам|постигнам)\s+\d+/iu',
            '/\d+\s*(милион|мил\.?|m|к|k)/iu',
            '/нето\s+профит/iu',
            '/бруто\s+профит/iu',

            // Price/revenue change patterns
            '/да\s+ги\s+(зголемам|намалам|промен[аие]м)\s+(цените|цена|трошоците)/iu',
            '/(зголеми|намали|промени)\s+(цена|цени|трошоци)/iu',
            '/кој\s+артикл/iu',
            '/кои\s+артикли/iu',
            '/кој\s+производ/iu',

            // Analysis patterns
            '/што\s+треба\s+да\s+направам\s+за/iu',
            '/анализа\s+(на|за)/iu',
            '/анализирај/iu',
            '/пресметај/iu',
            '/калкулација/iu',
            '/оптимизација/iu',
            '/оптимизирај/iu',

            // Scenario analysis
            '/што\s+ако/iu',
            '/ако\s+(ги\s+)?изгубам/iu',
            '/ако\s+.*\s+колку/iu',
            '/ако\s+трошоците\s+(растат|се зголемат)/iu',
            '/симулација/iu',
            '/сценарио/iu',

            // Prediction patterns
            '/кога\s+ќе\s+(достигнам|имам|постигнам)/iu',
            '/предвиди/iu',
            '/прогноза/iu',
            '/проекција/iu',
            '/тренд/iu',
            '/врз\s+основа\s+на/iu',

            // Risk and dependency analysis
            '/колку\s+зависам/iu',
            '/ризик/iu',
            '/најслаба\s+точка/iu',
            '/најголем\s+проблем/iu',
            '/break-?even/iu',
            '/точка\s+на\s+рентабилност/iu',

            // Comparison and ranking
            '/најдобар\s+месец/iu',
            '/најлош\s+месец/iu',
            '/споредба/iu',
            '/споредуваj/iu',
            '/рангирај/iu',
            '/топ\s+\d+/iu',

            // Revenue targets
            '/колку\s+(фактури|приход|профит)\s+ми\s+треба/iu',
            '/потребен\s+приход/iu',
            '/целен\s+приход/iu',

            // English patterns
            '/how\s+many\s+percent/iu',
            '/what\s+(percentage|percent)/iu',
            '/how\s+to\s+(achieve|reach|get)\s+\d+/iu',
            '/analysis\s+of/iu',
            '/calculate/iu',
            '/if\s+.*\s+how\s+much/iu',
            '/(increase|decrease|change)\s+(price|cost|revenue)/iu',
            '/which\s+item/iu',
            '/what\s+if\s+i\s+(lose|increase|decrease)/iu',
            '/break-?even/iu',
            '/forecast/iu',
            '/projection/iu',
            '/scenario/iu',
            '/optimize/iu',
        ];

        foreach ($complexPatterns as $pattern) {
            if (preg_match($pattern, $questionLower)) {
                Log::info('[AiInsightsService] Complex analytical query detected', [
                    'pattern' => $pattern,
                    'question' => $question,
                ]);

                return true;
            }
        }

        return false;
    }

    /**
     * Detect if query is asking "how to" questions about the app
     *
     * @param  string  $question  The user's question
     * @return bool True if "how to" query detected
     */
    private function detectHowToQuery(string $question): bool
    {
        $questionLower = mb_strtolower($question, 'UTF-8');

        $howToPatterns = [
            // Macedonian patterns
            '/како\s+(да|можам|се)\s+(креирам|направам|додадам|пратам|внесам)/iu',
            '/каде\s+(можам|е|се наоѓа)/iu',
            '/кои\s+се\s+чекорите/iu',
            '/објасни\s+како/iu',
            '/упатство\s+за/iu',
            '/помош\s+за/iu',
            // English patterns
            '/how\s+(do|can|to)\s+(i|create|add|send|make)/iu',
            '/where\s+(can|is|do)/iu',
            '/steps\s+to/iu',
            '/explain\s+how/iu',
            '/guide\s+(for|to)/iu',
            '/help\s+with/iu',
        ];

        foreach ($howToPatterns as $pattern) {
            if (preg_match($pattern, $questionLower)) {
                Log::info('[AiInsightsService] How-to query detected', [
                    'pattern' => $pattern,
                    'question' => $question,
                ]);

                return true;
            }
        }

        return false;
    }

    /**
     * Get app documentation about Facturino features
     *
     * @return string Documentation text in Macedonian
     */
    private function getAppDocumentation(): string
    {
        return <<<'DOCUMENTATION'
Упатство за Facturino апликација:

Како да креирате фактура:
1. Одете на "Фактури" → "Нова фактура"
2. Изберете клиент (или креирајте нов)
3. Додајте артикли со количина и цена
4. Проверете датум и рок за плаќање
5. Кликнете "Зачувај и испрати" или само "Зачувај"

Како да креирате профактура:
1. Одете на "Профактури" → "Нова профактура"
2. Процесот е ист како за фактура
3. Профактурата може подоцна да се конвертира во фактура

Како да управувате со клиенти:
1. Одете на "Клиенти"
2. Кликнете "Нов клиент" за да додадете
3. Внесете: име, адреса, даночен број, email
4. Можете да видите историја на фактури по клиент

Како да управувате со добавувачи:
1. Одете на "Добавувачи"
2. Кликнете "Нов добавувач"
3. Внесете детали на добавувачот
4. Користете за евидентирање на сметки

Како да евидентирате трошоци:
1. Одете на "Трошоци"
2. Кликнете "Нов трошок"
3. Внесете опис, износ, категорија
4. Прикачете документ ако имате

Како да управувате со артикли:
1. Одете на "Артикли"
2. Кликнете "Нов артикл"
3. Внесете: име, опис, цена, единица мерка
4. Овие артикли се користат при креирање фактури

Како да креирате понуда:
1. Одете на "Понуди" → "Нова понуда"
2. Изберете клиент и додајте артикли
3. Понудата може да се конвертира во фактура

Како да поставите повторувачки фактури:
1. Одете на "Повторувачки фактури"
2. Креирајте нова повторувачка фактура
3. Изберете фреквенција (месечно, квартално, итн.)
4. Системот автоматски ќе ги креира фактурите

Како да евидентирате сметки од добавувачи:
1. Одете на "Сметки"
2. Кликнете "Нова сметка"
3. Изберете добавувач и внесете детали
4. Следете кога треба да платите

Како да пратите е-фактура:
1. Креирајте фактура
2. Во детали на фактура, кликнете "Прати е-фактура"
3. Системот ќе ја испрати преку официјалниот систем

Како да управувате со проекти:
1. Одете на "Проекти"
2. Креирајте нов проект
3. Поврзете фактури и трошоци со проектот
4. Следете профитабилност по проект

Како да видите извештаи:
1. Одете на "Извештаи"
2. Изберете тип на извештај:
   - Извештај за приходи (Sales Report)
   - Извештај за трошоци (Expenses Report)
   - Извештај по клиент (Customer Report)
   - Профит и загуба (Profit & Loss)
   - Даночен извештај (Tax Report)

DOCUMENTATION;
    }

    /**
     * Extract entities from conversation history
     *
     * Detects and extracts mentioned entities like customer names, invoice numbers,
     * amounts, dates, and item names from the conversation.
     *
     * @param  array<int, array{role: string, content: string}>  $conversationHistory  Previous conversation messages
     * @return array<string, array> Array of entities grouped by type
     */
    private function extractEntitiesFromConversation(array $conversationHistory): array
    {
        $entities = [
            'invoice_numbers' => [],
            'amounts' => [],
            'dates' => [],
            'customer_names' => [],
            'item_names' => [],
        ];

        foreach ($conversationHistory as $message) {
            $content = $message['content'] ?? '';

            // Extract invoice numbers (Macedonian and English formats)
            // Matches: ф-123, фак-456, FA-789, inv-101, invoice-202
            if (preg_match_all('/\b(ф[ак]?-?\d+|fa-?\d+|inv-?\d+|invoice-?\d+)\b/iu', $content, $matches)) {
                foreach ($matches[0] as $invoiceNum) {
                    $entities['invoice_numbers'][] = $invoiceNum;
                }
            }

            // Extract amounts with currency
            // Matches: 5000 MKD, 1,234.56 денари, 100 EUR, 2.500 денари
            if (preg_match_all('/\b(\d{1,3}(?:[,\.]\d{3})*(?:[,\.]\d{1,2})?)\s*(MKD|денари|денар|€|EUR|евра)\b/iu', $content, $matches)) {
                for ($i = 0; $i < count($matches[0]); $i++) {
                    $entities['amounts'][] = [
                        'value' => $matches[1][$i],
                        'currency' => $matches[2][$i],
                        'full' => $matches[0][$i],
                    ];
                }
            }

            // Extract dates
            // Matches: 15.12.2025, 15/12/25, 15.12, 15/12
            if (preg_match_all('/\b(\d{1,2}[\/.]\d{1,2}(?:[\/.]\d{2,4})?)\b/', $content, $matches)) {
                foreach ($matches[0] as $date) {
                    $entities['dates'][] = $date;
                }
            }

            // Extract customer names (heuristic: capitalized words after customer/client keywords)
            $customerPatterns = [
                '/\b(?:клиент|купувач|customer|client)\s+([А-ЏŠŽČĆ][а-џšžčć]+(?:\s+[А-ЏŠŽČĆ][а-џšžčć]+)*)/u',
                '/\b([А-ЏŠŽČĆ][а-џšžčć]+(?:\s+[А-ЏŠŽČĆ][а-џšžčć]+)*)\s+(?:должи|плаќа|има|owes|pays|has)/u',
            ];
            foreach ($customerPatterns as $pattern) {
                if (preg_match_all($pattern, $content, $matches)) {
                    foreach ($matches[1] as $name) {
                        $entities['customer_names'][] = trim($name);
                    }
                }
            }

            // Extract item names (heuristic: items mentioned in context of products/items)
            $itemPatterns = [
                '/\b(?:артикл|производ|продукт|item|product)\s+"([^"]+)"/iu',
                '/\b(?:артикл|производ|продукт|item|product)\s+([А-ЏŠŽČĆA-Z][а-џšžčća-z]+(?:\s+[А-ЏŠŽČĆA-Z][а-џšžčća-z]+)*)/u',
            ];
            foreach ($itemPatterns as $pattern) {
                if (preg_match_all($pattern, $content, $matches)) {
                    foreach ($matches[1] as $itemName) {
                        $entities['item_names'][] = trim($itemName);
                    }
                }
            }
        }

        // Deduplicate entities
        foreach ($entities as $key => $values) {
            if ($key === 'amounts') {
                // For amounts, deduplicate by full text
                $unique = [];
                $seen = [];
                foreach ($values as $amount) {
                    $fullText = $amount['full'];
                    if (! in_array($fullText, $seen)) {
                        $unique[] = $amount;
                        $seen[] = $fullText;
                    }
                }
                $entities[$key] = $unique;
            } else {
                $entities[$key] = array_values(array_unique($values));
            }
        }

        Log::info('[AiInsightsService] Entities extracted from conversation', [
            'invoice_numbers_count' => count($entities['invoice_numbers']),
            'amounts_count' => count($entities['amounts']),
            'dates_count' => count($entities['dates']),
            'customer_names_count' => count($entities['customer_names']),
            'item_names_count' => count($entities['item_names']),
        ]);

        return $entities;
    }

    /**
     * Detect conversation references in user's question
     *
     * Identifies when the user is referring to previous conversation context
     * using pronouns, continuation words, or follow-up questions.
     *
     * @param  string  $question  The user's question
     * @return array<string> Array of detected reference types
     */
    private function detectConversationReferences(string $question): array
    {
        $references = [];
        $questionLower = mb_strtolower($question, 'UTF-8');

        // Demonstrative pronouns - "that", "this", "those", "these"
        $demonstrativePatterns = [
            '/\b(тоа|ова|овие|тие|истото|истиот|истата|оној|онаа|оние)\b/u',
            '/\b(that|this|those|these|it|them|the same)\b/iu',
        ];
        foreach ($demonstrativePatterns as $pattern) {
            if (preg_match($pattern, $questionLower)) {
                $references[] = 'demonstrative';
                break;
            }
        }

        // Previous context references - "earlier", "previously mentioned", "before"
        $previousContextPatterns = [
            '/\b(претходн[оаие]|порано|што споменавме|погоре|претходно споменато|порано споменато)\b/u',
            '/\b(previous|earlier|mentioned before|above|previously mentioned)\b/iu',
        ];
        foreach ($previousContextPatterns as $pattern) {
            if (preg_match($pattern, $questionLower)) {
                $references[] = 'previous_context';
                break;
            }
        }

        // Continuation markers - "continue", "more details", "explain more"
        $continuationPatterns = [
            '/\b(продолжи|повеќе детали|објасни подетално|кажи повеќе|покажи повеќе|дополнително|уште)\b/u',
            '/\b(continue|more details|explain more|tell me more|show more|additional|also)\b/iu',
        ];
        foreach ($continuationPatterns as $pattern) {
            if (preg_match($pattern, $questionLower)) {
                $references[] = 'continuation';
                break;
            }
        }

        // Follow-up questions - "why", "how so", "from where"
        $followUpPatterns = [
            '/^(зошто|како така|од каде|од кој|како|защо)\b/u',
            '/^(why|how so|from where|which one|how)\b/iu',
            '/\b(зошто (тоа|ова)|како така|објасни (го|ја))\b/u',
        ];
        foreach ($followUpPatterns as $pattern) {
            if (preg_match($pattern, $questionLower)) {
                $references[] = 'follow_up';
                break;
            }
        }

        // Same entity references - "the same customer", "that client"
        $sameEntityPatterns = [
            '/\b(истиот клиент|истата фактура|истиот артикл|тој клиент|таа фактура)\b/u',
            '/\b(the same customer|that client|that invoice|the same item)\b/iu',
        ];
        foreach ($sameEntityPatterns as $pattern) {
            if (preg_match($pattern, $questionLower)) {
                $references[] = 'same_entity';
                break;
            }
        }

        // Clarification requests - "what did you mean", "which one"
        $clarificationPatterns = [
            '/\b(што мислеше|што рече|кое|која|кој од|што значи)\b/u',
            '/\b(what did you mean|which one|what do you mean|clarify)\b/iu',
        ];
        foreach ($clarificationPatterns as $pattern) {
            if (preg_match($pattern, $questionLower)) {
                $references[] = 'clarification';
                break;
            }
        }

        Log::info('[AiInsightsService] Conversation references detected', [
            'question' => $question,
            'references' => $references,
        ]);

        return array_unique($references);
    }

    /**
     * Summarize conversation context for long conversations
     *
     * Creates a concise summary of key points, numbers, and decisions
     * when the conversation exceeds 6 messages to prevent context loss.
     *
     * @param  array<int, array{role: string, content: string}>  $conversationHistory  Previous conversation messages
     * @return string Summary of conversation context
     */
    private function summarizeConversationContext(array $conversationHistory): string
    {
        if (count($conversationHistory) <= 6) {
            return '';
        }

        // Extract entities from entire conversation
        $entities = $this->extractEntitiesFromConversation($conversationHistory);

        // Build summary text
        $summary = "Краток преглед на дискусијата:\n";

        // Main topics (extract from user questions)
        $userMessages = array_filter($conversationHistory, fn ($msg) => ($msg['role'] ?? '') === 'user');
        if (! empty($userMessages)) {
            $firstUserMsg = reset($userMessages);
            $summary .= "- Почетна тема: ".mb_substr($firstUserMsg['content'] ?? '', 0, 100)."...\n";
        }

        // Key numbers mentioned
        if (! empty($entities['amounts'])) {
            $summary .= "- Споменати износи: ";
            $amounts = array_slice($entities['amounts'], 0, 3);
            $amountTexts = array_map(fn ($a) => $a['full'], $amounts);
            $summary .= implode(', ', $amountTexts);
            if (count($entities['amounts']) > 3) {
                $summary .= ' и други';
            }
            $summary .= "\n";
        }

        // Invoice numbers mentioned
        if (! empty($entities['invoice_numbers'])) {
            $summary .= '- Фактури: '.implode(', ', array_slice($entities['invoice_numbers'], 0, 3));
            if (count($entities['invoice_numbers']) > 3) {
                $summary .= ' и други';
            }
            $summary .= "\n";
        }

        // Customer names mentioned
        if (! empty($entities['customer_names'])) {
            $summary .= '- Клиенти: '.implode(', ', array_slice($entities['customer_names'], 0, 3));
            if (count($entities['customer_names']) > 3) {
                $summary .= ' и други';
            }
            $summary .= "\n";
        }

        // Dates mentioned
        if (! empty($entities['dates'])) {
            $summary .= '- Датуми: '.implode(', ', array_slice($entities['dates'], 0, 3));
            if (count($entities['dates']) > 3) {
                $summary .= ' и други';
            }
            $summary .= "\n";
        }

        Log::info('[AiInsightsService] Conversation summary created', [
            'messages_count' => count($conversationHistory),
            'summary_length' => strlen($summary),
        ]);

        return $summary;
    }
}

// CLAUDE-CHECKPOINT
