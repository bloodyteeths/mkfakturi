<?php

namespace App\Services\Reconciliation;

use App\Models\BankTransaction;
use App\Models\Company;
use App\Services\AiProvider\GeminiProvider;
use App\Services\UsageLimitService;
use Illuminate\Support\Facades\Log;

/**
 * AI Transaction Categorizer
 *
 * Layer 4 of the 4-layer reconciliation pipeline.
 * When no invoice match is found (even with AI enhancement),
 * classifies unmatched transactions into accounting categories.
 *
 * Categories are shown as badges in the UI. User can accept
 * the category (creates draft journal entry) or re-categorize.
 */
class AiTransactionCategorizer
{
    private const LOCALE_NAMES = [
        'mk' => 'Macedonian (македонски)',
        'sq' => 'Albanian (shqip)',
        'tr' => 'Turkish (Türkçe)',
        'en' => 'English',
    ];

    /**
     * Valid transaction categories.
     */
    public const CATEGORIES = [
        'salary',
        'tax_payment',
        'bank_fee',
        'loan_payment',
        'internal_transfer',
        'supplier_payment',
        'utility',
        'rent',
        'subscription',
        'other',
    ];

    private ?GeminiProvider $provider = null;

    /**
     * Categorize a single unmatched bank transaction.
     *
     * @param  BankTransaction  $transaction  The transaction to categorize
     * @return array|null  ['category' => string, 'confidence' => float, 'reason' => string]
     */
    public function categorize(BankTransaction $transaction, string $locale = 'mk'): ?array
    {
        // Check AI usage limits
        $company = Company::find($transaction->company_id);
        if ($company && ! $this->checkUsageLimit($company)) {
            return null;
        }

        try {
            $provider = $this->getProvider();
            if (! $provider) {
                return null;
            }

            $prompt = $this->buildCategorizationPrompt($transaction, $locale);

            $response = $provider->generate($prompt, [
                'temperature' => 0.1,
                'max_tokens' => 200,
            ]);

            if ($company) {
                $this->trackUsage($company);
            }

            return $this->parseResponse($response);
        } catch (\Exception $e) {
            Log::warning('[AiCategorizer] Categorization failed', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Categorize multiple transactions in batch.
     *
     * @param  \Illuminate\Support\Collection  $transactions
     * @return array  [transaction_id => category_result]
     */
    public function categorizeBatch(\Illuminate\Support\Collection $transactions, string $locale = 'mk'): array
    {
        $results = [];

        foreach ($transactions as $transaction) {
            // Skip if already categorized
            if ($transaction->ai_category) {
                continue;
            }

            $result = $this->categorize($transaction, $locale);
            if ($result) {
                $results[$transaction->id] = $result;

                // Update transaction with category
                $transaction->update([
                    'ai_category' => $result['category'],
                    'ai_match_reason' => $result['reason'],
                ]);
            }
        }

        return $results;
    }

    /**
     * Build the Gemini prompt for transaction categorization.
     */
    protected function buildCategorizationPrompt(BankTransaction $transaction, string $locale = 'mk'): string
    {
        $langName = self::LOCALE_NAMES[$locale] ?? self::LOCALE_NAMES['mk'];
        $counterparty = $transaction->counterparty_name ?? '';
        $iban = $transaction->counterparty_iban ?? '';

        $txData = json_encode([
            'amount' => (float) $transaction->amount,
            'date' => $transaction->transaction_date?->format('Y-m-d') ?? '',
            'description' => $transaction->description ?? '',
            'remittance_info' => $transaction->remittance_info ?? '',
            'counterparty' => $counterparty,
            'iban' => $iban,
            'direction' => $transaction->amount > 0 ? 'incoming' : 'outgoing',
        ], JSON_UNESCAPED_UNICODE);

        $categories = implode(', ', self::CATEGORIES);

        return <<<PROMPT
Classify this bank transaction into one category.
You MUST return the reason field in {$langName}.

Transaction:
{$txData}

Categories: {$categories}

Rules:
- salary: payroll, wages, "плата", "придонес", "paga"
- tax_payment: government tax, "даноци", "ДДВ", "УЈП", "данок", "tatim", "TVSH"
- bank_fee: bank charges, "провизија", "камата банка", "komision"
- loan_payment: loan installment, "кредит", "рата", "kredi"
- internal_transfer: transfer between own accounts, same company name
- supplier_payment: payment to supplier for goods/services
- utility: electricity, water, phone, internet, "ЕВН", "Телеком", "EVN"
- rent: office/space rent, "кирија", "закуп", "qira"
- subscription: recurring software/service subscription
- other: none of the above

Return ONLY valid JSON:
{"category": "salary", "confidence": 0.9, "reason": "Description contains payroll keywords"}
PROMPT;
    }

    /**
     * Parse the AI response.
     */
    protected function parseResponse(string $response): ?array
    {
        $cleaned = preg_replace('/^```(?:json)?\s*\n?/m', '', $response);
        $cleaned = preg_replace('/\n?```\s*$/m', '', $cleaned);
        $cleaned = trim($cleaned);

        $data = json_decode($cleaned, true);
        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($data)) {
            Log::warning('[AiCategorizer] Failed to parse response', [
                'response' => substr($response, 0, 300),
            ]);

            return null;
        }

        $category = $data['category'] ?? null;
        if (! $category || ! in_array($category, self::CATEGORIES)) {
            return null;
        }

        return [
            'category' => $category,
            'confidence' => (float) ($data['confidence'] ?? 0),
            'reason' => $data['reason'] ?? '',
        ];
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

    protected function getProvider(): ?GeminiProvider
    {
        if ($this->provider !== null) {
            return $this->provider;
        }

        try {
            $this->provider = app(GeminiProvider::class);

            return $this->provider;
        } catch (\Exception $e) {
            return null;
        }
    }
}
// CLAUDE-CHECKPOINT
