<?php

namespace App\Services\Reconciliation;

use App\Models\BankTransaction;
use App\Models\Company;
use App\Services\AiProvider\GeminiProvider;
use App\Services\UsageLimitService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Enhanced AI categorizer for smart reconciliation.
 *
 * Unlike the original AiTransactionCategorizer (10 hardcoded categories),
 * this uses the company's actual expense categories and suggests concrete actions.
 */
class SmartAiCategorizer
{
    private const LOCALE_NAMES = [
        'mk' => 'Macedonian (македонски)',
        'sq' => 'Albanian (shqip)',
        'tr' => 'Turkish (Türkçe)',
        'en' => 'English',
    ];

    /**
     * Generate a smart suggestion using AI.
     *
     * @param  BankTransaction  $tx  The transaction to categorize
     * @param  Collection  $categories  Company's expense categories (id, name)
     * @param  string  $locale  User's locale
     */
    public function suggest(BankTransaction $tx, Collection $categories, string $locale = 'mk'): ?SmartSuggestion
    {
        $company = Company::find($tx->company_id);
        if ($company && ! $this->checkUsageLimit($company)) {
            return null;
        }

        try {
            $provider = app(GeminiProvider::class);
        } catch (\Exception $e) {
            return null;
        }

        $isDebit = $tx->amount < 0;
        $langName = self::LOCALE_NAMES[$locale] ?? self::LOCALE_NAMES['mk'];

        $categoryList = $categories->map(fn ($c) => "{$c->id}: {$c->name}")->implode("\n");

        $txData = json_encode([
            'amount' => (float) $tx->amount,
            'date' => $tx->transaction_date?->format('Y-m-d') ?? '',
            'description' => $tx->description ?? '',
            'remittance_info' => $tx->remittance_info ?? '',
            'counterparty' => $tx->counterparty_name ?? '',
            'iban' => $tx->counterparty_iban ?? '',
            'direction' => $isDebit ? 'outgoing (debit)' : 'incoming (credit)',
        ], JSON_UNESCAPED_UNICODE);

        $actionInstructions = $isDebit
            ? $this->debitActionInstructions()
            : $this->creditActionInstructions();

        $prompt = <<<PROMPT
You are an expert accountant. Analyze this bank transaction and suggest how to categorize it.
You MUST return the "reason" field in {$langName}.

Transaction:
{$txData}

{$actionInstructions}

Available expense categories for this company:
{$categoryList}

Keyword hints:
- "плата", "salary", "wages", "придонес", "paga" → payroll/salary
- "даноци", "ДДВ", "УЈП", "данок", "tatim", "TVSH" → tax payment
- "провизија", "камата банка", "komision", "fee" → bank fee
- "кредит", "рата", "kredi", "loan" → loan payment
- "ЕВН", "EVN", "Телеком", "водовод", "electricity", "water" → utility
- "кирија", "закуп", "qira", "rent" → rent
- "камата", "interest", "лихва" → bank interest income
- "поврат", "refund", "враќање" → refund
- "субвенција", "grant", "дотација" → government grant/subsidy

Return ONLY valid JSON (no markdown, no explanation):
{"action": "create_expense", "category_id": 5, "category_name": "Utilities", "confidence": 0.85, "reason": "Description matches utility payment"}
PROMPT;

        try {
            $response = $provider->generate($prompt, [
                'temperature' => 0.1,
                'max_tokens' => 250,
                'thinking_budget' => 0,
            ]);

            if ($company) {
                $this->trackUsage($company);
            }

            return $this->parseResponse($response);
        } catch (\Exception $e) {
            Log::warning('[SmartAiCategorizer] Failed', [
                'transaction_id' => $tx->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function debitActionInstructions(): string
    {
        return <<<'INSTRUCTIONS'
For OUTGOING (debit) transactions, choose one action:
- "create_expense": For regular business expenses (utilities, rent, supplies, fees, subscriptions, etc.). Set category_id to the best matching expense category.
- "link_payroll": For salary/wage payments. Set category_id to null.
- "mark_reviewed": For internal transfers between own accounts or transactions that don't need an expense record. Set category_id to null.

Rules:
- If it looks like a salary/payroll payment → action = "link_payroll"
- If it's a transfer to own account / internal transfer → action = "mark_reviewed"
- For all other outgoing payments → action = "create_expense" with the best category
INSTRUCTIONS;
    }

    private function creditActionInstructions(): string
    {
        return <<<'INSTRUCTIONS'
For INCOMING (credit) transactions that are NOT customer payments, choose one action:
- "record_income": For bank interest, refunds, government grants, insurance payouts, dividends, or other non-invoice income. Set category_id to null.
- "mark_reviewed": For internal transfers from own accounts. Set category_id to null.
- "link_invoice": If it looks like a customer payment. Set category_id to null.

Rules:
- If counterparty is a bank or description mentions interest/камата → action = "record_income"
- If description mentions refund/поврат/враќање → action = "record_income"
- If description mentions grant/субвенција/дотација → action = "record_income"
- If it's a transfer from own account → action = "mark_reviewed"
- If it looks like a customer payment → action = "link_invoice"
INSTRUCTIONS;
    }

    private function parseResponse(string $response): ?SmartSuggestion
    {
        $cleaned = preg_replace('/^```(?:json)?\s*\n?/m', '', $response);
        $cleaned = preg_replace('/\n?```\s*$/m', '', $cleaned);
        $cleaned = trim($cleaned);

        $data = json_decode($cleaned, true);
        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($data)) {
            Log::warning('[SmartAiCategorizer] Parse failed', ['response' => substr($response, 0, 300)]);

            return null;
        }

        $action = $data['action'] ?? null;
        $validActions = [
            SmartSuggestion::ACTION_CREATE_EXPENSE,
            SmartSuggestion::ACTION_RECORD_INCOME,
            SmartSuggestion::ACTION_LINK_PAYROLL,
            SmartSuggestion::ACTION_LINK_INVOICE,
            SmartSuggestion::ACTION_MARK_REVIEWED,
        ];

        if (! $action || ! in_array($action, $validActions)) {
            return null;
        }

        return new SmartSuggestion(
            action: $action,
            confidence: (float) min(0.92, max(0.3, $data['confidence'] ?? 0.5)),
            reason: $data['reason'] ?? '',
            categoryId: isset($data['category_id']) ? (int) $data['category_id'] : null,
            categoryName: $data['category_name'] ?? null,
        );
    }

    private function checkUsageLimit(Company $company): bool
    {
        try {
            return app(UsageLimitService::class)->canUse($company, 'ai_queries_per_month');
        } catch (\Exception $e) {
            return true;
        }
    }

    private function trackUsage(Company $company): void
    {
        try {
            app(UsageLimitService::class)->incrementUsage($company, 'ai_queries_per_month');
        } catch (\Exception $e) {
            // Non-critical
        }
    }
}
// CLAUDE-CHECKPOINT
