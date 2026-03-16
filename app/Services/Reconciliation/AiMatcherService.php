<?php

namespace App\Services\Reconciliation;

use App\Models\BankTransaction;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\ReconciliationFeedback;
use App\Services\AiProvider\GeminiProvider;
use App\Services\UsageLimitService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * AI-Enhanced Bank Transaction Matcher
 *
 * Layer 3 of the 4-layer reconciliation pipeline.
 * Uses Gemini to parse transaction descriptions, resolve name variations
 * (Cyrillic/Latin), detect split payments, and extract invoice references
 * that deterministic matching cannot handle.
 *
 * AI boosts deterministic scores — never replaces them.
 * All matches are SUGGESTIONS requiring human approval.
 */
class AiMatcherService
{
    private const LOCALE_NAMES = [
        'mk' => 'Macedonian (македонски)',
        'sq' => 'Albanian (shqip)',
        'tr' => 'Turkish (Türkçe)',
        'en' => 'English',
    ];

    private ?GeminiProvider $provider = null;

    /**
     * Enhance deterministic matching results with AI analysis.
     *
     * Called when deterministic confidence < 90 (not auto-matchable).
     * Returns AI-boosted matches or null if AI can't help.
     *
     * @param  object  $transaction  Bank transaction (stdClass or Model)
     * @param  Collection  $invoices  Pool of unpaid invoices
     * @param  int  $companyId  Company ID for feedback lookup
     * @param  float  $deterministicScore  Best deterministic score (0-1 scale)
     * @return array|null  AI match result or null
     */
    public function enhance(object $transaction, Collection $invoices, int $companyId, float $deterministicScore = 0.0, string $locale = 'mk'): ?array
    {
        if ($invoices->isEmpty()) {
            return null;
        }

        // Check AI usage limits
        $company = Company::find($companyId);
        if ($company && ! $this->checkUsageLimit($company)) {
            Log::debug('[AiMatcher] AI usage limit reached', ['company_id' => $companyId]);

            return null;
        }

        try {
            $provider = $this->getProvider();
            if (! $provider) {
                return null;
            }

            // Build prompt with transaction data, invoice pool, and feedback hints
            $prompt = $this->buildMatchingPrompt($transaction, $invoices, $companyId, $locale);

            $response = $provider->generate($prompt, [
                'temperature' => 0.1, // Low temperature for structured output
                'max_tokens' => 500,
            ]);

            // Track AI usage
            if ($company) {
                $this->trackUsage($company);
            }

            $parsed = $this->parseAiResponse($response);
            if (! $parsed) {
                return null;
            }

            // Merge AI confidence with deterministic score
            return $this->mergeResults($parsed, $deterministicScore, $invoices);
        } catch (\Exception $e) {
            Log::warning('[AiMatcher] AI enhancement failed', [
                'transaction_id' => $transaction->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Build the Gemini prompt for transaction matching.
     */
    protected function buildMatchingPrompt(object $transaction, Collection $invoices, int $companyId, string $locale = 'mk'): string
    {
        $langName = self::LOCALE_NAMES[$locale] ?? self::LOCALE_NAMES['mk'];
        $counterpartyName = $transaction->debtor_name ?? $transaction->creditor_name ?? '';
        $counterpartyIban = $transaction->debtor_iban ?? $transaction->creditor_iban ?? '';

        // Build invoice list (limit to 20 most relevant)
        $invoiceList = $invoices->take(20)->map(function ($invoice) {
            $customerName = $invoice->customer->name ?? 'Unknown';

            return [
                'id' => $invoice->id,
                'number' => $invoice->invoice_number,
                'customer' => $customerName,
                'amount' => (float) $invoice->total,
                'due' => $invoice->due_date ? (is_string($invoice->due_date) ? $invoice->due_date : $invoice->due_date->format('Y-m-d')) : '',
            ];
        })->values()->toArray();

        // Get company-specific matching hints from feedback history
        $hints = $this->getMatchingHints($companyId);

        $transactionData = json_encode([
            'amount' => (float) $transaction->amount,
            'date' => $transaction->transaction_date instanceof \Carbon\Carbon
                ? $transaction->transaction_date->format('Y-m-d')
                : (string) $transaction->transaction_date,
            'description' => $transaction->description ?? '',
            'remittance_info' => $transaction->remittance_info ?? '',
            'counterparty' => $counterpartyName,
            'iban' => $counterpartyIban,
        ], JSON_UNESCAPED_UNICODE);

        $invoiceData = json_encode($invoiceList, JSON_UNESCAPED_UNICODE);

        $prompt = <<<PROMPT
You are a bank reconciliation assistant. Analyze a bank transaction and match it to invoice(s).
You MUST respond in {$langName}. All text fields (reason) MUST be in this language.

Transaction:
{$transactionData}

Open invoices:
{$invoiceData}

PROMPT;

        if (! empty($hints)) {
            $hintsJson = json_encode($hints, JSON_UNESCAPED_UNICODE);
            $prompt .= <<<PROMPT

Known matching hints from previous corrections:
{$hintsJson}

PROMPT;
        }

        $prompt .= <<<PROMPT

Instructions:
1. Match the transaction to one or more invoices based on: name similarity (handle Cyrillic/Latin variations like "ДООЕЛ МАРКОВ" = "Марков ДООЕЛ"), invoice references in description/remittance, amount matching (exact or split across multiple invoices), IBAN matching.
2. If the transaction description mentions multiple invoice numbers, detect the split.
3. For each match, provide a confidence score (0.0-1.0) and a brief reason in {$langName}.
4. If no reasonable match exists, return empty matches array.

Return ONLY valid JSON (no markdown, no explanation):
{
  "matches": [
    {"invoice_id": 123, "amount": 15000, "confidence": 0.95, "reason": "Name matches + invoice reference in description"}
  ],
  "is_split": false
}
PROMPT;

        return $prompt;
    }

    /**
     * Get matching hints from feedback history.
     *
     * When users correct AI matches, we store the corrections.
     * These become hints in future prompts for the same company.
     */
    protected function getMatchingHints(int $companyId): array
    {
        try {
            // Get recent "wrong" feedback with correct invoice info
            $feedback = ReconciliationFeedback::query()
                ->whereHas('reconciliation', function ($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                })
                ->where('feedback', ReconciliationFeedback::FEEDBACK_WRONG)
                ->whereNotNull('correct_invoice_id')
                ->with(['reconciliation.bankTransaction', 'correctInvoice.customer'])
                ->latest()
                ->limit(10)
                ->get();

            $hints = [];
            foreach ($feedback as $fb) {
                $txn = $fb->reconciliation->bankTransaction ?? null;
                $correctInvoice = $fb->correctInvoice ?? null;
                if (! $txn || ! $correctInvoice) {
                    continue;
                }

                $counterparty = $txn->debtor_name ?? $txn->creditor_name ?? '';
                $customerName = $correctInvoice->customer->name ?? '';

                if ($counterparty && $customerName) {
                    $hints[] = [
                        'counterparty' => $counterparty,
                        'maps_to_customer' => $customerName,
                        'customer_id' => $correctInvoice->customer_id,
                    ];
                }
            }

            // Deduplicate hints by counterparty name
            return collect($hints)->unique('counterparty')->values()->toArray();
        } catch (\Exception $e) {
            Log::debug('[AiMatcher] Failed to load matching hints', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Parse AI response JSON.
     */
    protected function parseAiResponse(string $response): ?array
    {
        // Strip markdown code fences if present
        $cleaned = preg_replace('/^```(?:json)?\s*\n?/m', '', $response);
        $cleaned = preg_replace('/\n?```\s*$/m', '', $cleaned);
        $cleaned = trim($cleaned);

        $data = json_decode($cleaned, true);
        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($data)) {
            Log::warning('[AiMatcher] Failed to parse AI response', [
                'response' => substr($response, 0, 500),
                'json_error' => json_last_error_msg(),
            ]);

            return null;
        }

        if (! isset($data['matches']) || ! is_array($data['matches'])) {
            return null;
        }

        return $data;
    }

    /**
     * Merge AI results with deterministic scores.
     *
     * Formula: combined = min(100, deterministicScore + (aiScore * 40))
     * AI boosts the deterministic score, never replaces it.
     */
    protected function mergeResults(array $aiResult, float $deterministicScore, Collection $invoices): ?array
    {
        $matches = $aiResult['matches'] ?? [];
        $isSplit = $aiResult['is_split'] ?? false;

        if (empty($matches)) {
            return null;
        }

        $mergedMatches = [];
        foreach ($matches as $match) {
            $invoiceId = $match['invoice_id'] ?? null;
            $aiConfidence = (float) ($match['confidence'] ?? 0);
            $reason = $match['reason'] ?? '';
            $matchAmount = (float) ($match['amount'] ?? 0);

            if (! $invoiceId || $aiConfidence < 0.3) {
                continue;
            }

            // Verify invoice exists in pool
            $invoice = $invoices->firstWhere('id', $invoiceId);
            if (! $invoice) {
                continue;
            }

            // Merge: deterministic score (0-100) + AI boost (aiScore * 40)
            $deterministicPercent = $deterministicScore * 100;
            $combined = min(100, $deterministicPercent + ($aiConfidence * 40));

            $mergedMatches[] = [
                'invoice_id' => $invoiceId,
                'invoice_number' => $invoice->invoice_number,
                'invoice_total' => (float) $invoice->total,
                'amount' => $matchAmount,
                'confidence' => round($combined, 1),
                'ai_confidence' => round($aiConfidence * 100, 1),
                'deterministic_confidence' => round($deterministicPercent, 1),
                'reason' => $reason,
                'match_type' => 'ai',
            ];
        }

        if (empty($mergedMatches)) {
            return null;
        }

        // Sort by confidence descending
        usort($mergedMatches, fn ($a, $b) => $b['confidence'] <=> $a['confidence']);

        return [
            'matches' => $mergedMatches,
            'is_split' => $isSplit,
            'best_match' => $mergedMatches[0],
        ];
    }

    /**
     * Check if company has remaining AI usage.
     */
    protected function checkUsageLimit(Company $company): bool
    {
        try {
            $usageService = app(UsageLimitService::class);

            return $usageService->canUse($company, 'ai_queries_per_month');
        } catch (\Exception $e) {
            // If usage service fails, allow the request
            return true;
        }
    }

    /**
     * Track AI query usage.
     */
    protected function trackUsage(Company $company): void
    {
        try {
            $usageService = app(UsageLimitService::class);
            $usageService->incrementUsage($company, 'ai_queries_per_month');
        } catch (\Exception $e) {
            // Non-critical: log and continue
            Log::debug('[AiMatcher] Failed to track usage', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get or create the Gemini provider instance.
     */
    protected function getProvider(): ?GeminiProvider
    {
        if ($this->provider !== null) {
            return $this->provider;
        }

        try {
            $this->provider = app(GeminiProvider::class);

            return $this->provider;
        } catch (\Exception $e) {
            Log::debug('[AiMatcher] Gemini provider not available', ['error' => $e->getMessage()]);

            return null;
        }
    }
}
// CLAUDE-CHECKPOINT
