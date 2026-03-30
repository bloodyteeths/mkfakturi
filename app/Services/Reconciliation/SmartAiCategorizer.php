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
            Log::info('[SmartAiCategorizer] Usage limit reached', [
                'transaction_id' => $tx->id,
                'company_id' => $tx->company_id,
            ]);

            return null;
        }

        try {
            $provider = app(GeminiProvider::class);
        } catch (\Exception $e) {
            Log::warning('[SmartAiCategorizer] Provider init failed', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        $isDebit = $tx->transaction_type === 'debit' || ($tx->transaction_type === null && $tx->amount < 0);
        $langName = self::LOCALE_NAMES[$locale] ?? self::LOCALE_NAMES['mk'];

        $categoryList = $categories->map(fn ($c) => "{$c->id}: {$c->name}")->implode("\n");

        // Use explicit field based on corrected direction (accessor uses amount sign which can be wrong)
        $counterparty = $isDebit ? ($tx->creditor_name ?? '') : ($tx->debtor_name ?? '');
        $counterpartyIban = $isDebit ? ($tx->creditor_iban ?? '') : ($tx->debtor_iban ?? '');

        $txData = json_encode([
            'amount' => (float) $tx->amount,
            'date' => $tx->transaction_date?->format('Y-m-d') ?? '',
            'description' => $tx->description ?? '',
            'remittance_info' => $tx->remittance_info ?? '',
            'counterparty' => $counterparty,
            'iban' => $counterpartyIban,
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
- "плата", "salary", "wages", "paga", "нето плата", "neto" → payroll/salary (link_payroll)
- "придонес", "пио", "ФПИОМ", "fpiom", "pension", "пензиско" → payroll pension contribution (tax_payment sub_type ФПИОМ)
- "здравств", "ФЗОМ", "fzom", "health" → payroll health contribution (tax_payment sub_type ФЗОМ)
- "вработување", "employment", "невработеност" → payroll employment fund (tax_payment sub_type Вработување)
- "персонален данок", "данок од плата", "ПДД" → payroll income tax (tax_payment sub_type Персонален данок)
- "УЈП", "ДДВ", "данок на добивка", "аконтација", "tatim", "TVSH" → tax_payment
- "кредит", "рата", "ануитет", "лизинг", "kredi", "loan repayment", "leasing" → loan_repayment
- "позајмица", "позајмица дадена", "loan given", "дадена позајмица", "huadhëni", "kredi dhënë" → loan_given (outgoing loan)
- "провизија", "камата банка", "komision", "fee", "банкарска провизија" → bank fee (expense)
- "ЕВН", "EVN", "Телеком", "водовод", "electricity", "water", "комунална" → utility (expense)
- "кирија", "закуп", "qira", "rent" → rent (expense)
- "камата", "interest", "лихва", "камата на депозит" → bank interest income (record_income)
- "поврат", "refund", "враќање", "рекламација" → refund (record_income)
- "субвенција", "grant", "дотација", "државна помош" → government grant (record_income)
- "сопственик", "влог", "основач", "капитал", "дивиденда", "ortaku", "themelues" → owner equity
- "интерен трансфер", "меѓу сметки", "transferim", "own account" → internal_transfer
- "осигурување", "полиса", "sigurim", "insurance" → insurance (expense or income depending on direction)
- "закупнина", "наем", "rent received" → rental income (record_income for credits)
- "судска", "gjyqësore", "court", "извршител" → legal/court (expense or income)
- "царина", "customs", "doganë", "gümrük", "увоз" → customs/import duties (tax_payment sub_type Царина)
- "акциза", "excise", "akcizë" → excise tax (tax_payment sub_type Акциза)
- "комунална такса", "фирмарина", "komunale" → communal tax (tax_payment sub_type Комунална такса)
- "депонирање", "готовина", "cash deposit", "депозит каса" → cash_deposit (credits) or cash_withdrawal (debits)
- "подигнување", "cash withdrawal", "ATM", "благајна" → cash_withdrawal (debits)
- "аванс", "advance", "предуплата", "prepayment", "paradhëni", "avans" → advance_received (credits) or advance_paid (debits)

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

            return $this->parseResponse($response, $isDebit);
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
- "create_expense": Regular business expenses (utilities, rent, supplies, fees, subscriptions, office, transport, marketing, repairs, insurance premiums, professional services, software, telecom). Set category_id to best matching expense category.
- "link_payroll": Salary/wage/contribution payments to employees. Set category_id to null.
- "tax_payment": Tax payments to government (УЈП/UJP, ДДВ/VAT, данок на добивка/profit tax, персонален данок/personal income tax, придонеси за ФПИОМ/ФЗОМ pension/health, царина/customs, акциза/excise, комунална такса). Set category_id to null. Include "sub_type" field with specific tax name.
- "loan_repayment": Loan installments, credit repayments, leasing payments to banks or financial institutions. Set category_id to null.
- "loan_given": Loan given to third party, money lent out (позајмица дадена, дадена позајмица, позајмица). NOT a loan repayment. Set category_id to null.
- "owner_withdrawal": Owner/shareholder withdrawing capital, dividend payout to owner, personal withdrawal by owner (повлекување капитал, дивиденда, лично подигнување). Set category_id to null.
- "cash_withdrawal": Cash withdrawal from bank to cash register (подигнување готовина, ATM, благајна). Set category_id to null.
- "advance_paid": Advance payment to supplier before receiving goods/invoice (аванс, предуплата, prepayment). Set category_id to null.
- "internal_transfer": Transfer to company's own account at another bank, or between own accounts. Set category_id to null.
- "mark_reviewed": Only if none of the above fit. Set category_id to null.

Rules (check in this order):
1. Cash withdrawal keywords (подигнување готовина, ATM, благајна, cash withdrawal) → "cash_withdrawal"
2. Salary/payroll keywords (плата, нето, придонес за вработен, paga) → "link_payroll"
3. Tax keywords (УЈП, ДДВ, данок, ФПИОМ, ФЗОМ, tatim, TVSH, аконтација, царина, акциза, комунална такса) → "tax_payment"
4. Loan given keywords (позајмица, позајмица дадена, дадена позајмица, loan given, huadhëni) → "loan_given"
5. Loan repayment keywords (кредит, рата, ануитет, лизинг, kredi, loan repayment, leasing) → "loan_repayment"
6. Owner keywords (сопственик, дивиденда, повлекување, лично подигнување, ortaku, divident) → "owner_withdrawal"
7. Advance payment keywords (аванс, предуплата, авансно плаќање, avans, paradhëni) → "advance_paid"
8. Internal transfer (own account name, same company IBAN) → "internal_transfer"
9. Everything else → "create_expense" with best category
INSTRUCTIONS;
    }

    private function creditActionInstructions(): string
    {
        return <<<'INSTRUCTIONS'
For INCOMING (credit) transactions, choose one action:
- "link_invoice": Customer payment for goods/services — looks like it pays an invoice. Set category_id to null.
- "record_income": Non-invoice income: bank interest, refunds, insurance payouts, government grants/subsidies, court awards, rental income received. Set category_id to null.
- "owner_contribution": Owner/shareholder adding capital, founder's deposit, personal investment by owner (влог на капитал, основачки влог, капитална инвестиција, ortaku, kapital). Set category_id to null.
- "loan_received": Loan disbursement from bank, credit line drawdown, financial institution deposit (кредит примен, дисбурзирање, kredi e marrë). Set category_id to null.
- "cash_deposit": Cash deposit from cash register to bank (депонирање готовина, cash deposit, готовински уплата). Set category_id to null.
- "advance_received": Advance payment from customer before invoice (аванс, предуплата, авансна уплата, paradhëni). Set category_id to null.
- "internal_transfer": Transfer from company's own account at another bank, or between own accounts. Set category_id to null.
- "mark_reviewed": Only if none of the above fit. Set category_id to null.

Rules (check in this order):
1. Cash deposit keywords (депонирање, готовинска уплата, cash deposit, благајна) → "cash_deposit"
2. Owner/capital keywords (сопственик, влог, основач, капитал, ortaku, themelues) → "owner_contribution"
3. Loan keywords (кредит, заем, дисбурзирање, banka, kredi, loan disbursement) + counterparty is bank → "loan_received"
4. Advance payment keywords (аванс, предуплата, авансна уплата, avans, paradhëni) → "advance_received"
5. Interest/refund/grant (камата, поврат, субвенција, дотација, interest, refund, grant) → "record_income"
6. Internal transfer (own account, same company) → "internal_transfer"
7. Customer payment / service / goods → "link_invoice"
8. Everything else → "record_income"
INSTRUCTIONS;
    }

    private function parseResponse(string $response, bool $isDebit = true): ?SmartSuggestion
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
            SmartSuggestion::ACTION_OWNER_CONTRIBUTION,
            SmartSuggestion::ACTION_OWNER_WITHDRAWAL,
            SmartSuggestion::ACTION_LOAN_RECEIVED,
            SmartSuggestion::ACTION_LOAN_REPAYMENT,
            SmartSuggestion::ACTION_TAX_PAYMENT,
            SmartSuggestion::ACTION_INTERNAL_TRANSFER,
            SmartSuggestion::ACTION_CASH_DEPOSIT,
            SmartSuggestion::ACTION_CASH_WITHDRAWAL,
            SmartSuggestion::ACTION_ADVANCE_RECEIVED,
            SmartSuggestion::ACTION_ADVANCE_PAID,
            SmartSuggestion::ACTION_LOAN_GIVEN,
        ];

        if (! $action || ! in_array($action, $validActions)) {
            return null;
        }

        // Guard: AI must not suggest credit actions for debits or vice versa
        $debitOnlyActions = [
            SmartSuggestion::ACTION_CREATE_EXPENSE, SmartSuggestion::ACTION_LINK_BILL,
            SmartSuggestion::ACTION_LINK_PAYROLL, SmartSuggestion::ACTION_OWNER_WITHDRAWAL,
            SmartSuggestion::ACTION_LOAN_REPAYMENT, SmartSuggestion::ACTION_TAX_PAYMENT,
            SmartSuggestion::ACTION_CASH_WITHDRAWAL, SmartSuggestion::ACTION_ADVANCE_PAID,
            SmartSuggestion::ACTION_LOAN_GIVEN,
        ];
        $creditOnlyActions = [
            SmartSuggestion::ACTION_RECORD_INCOME, SmartSuggestion::ACTION_LINK_INVOICE,
            SmartSuggestion::ACTION_OWNER_CONTRIBUTION, SmartSuggestion::ACTION_LOAN_RECEIVED,
            SmartSuggestion::ACTION_CASH_DEPOSIT, SmartSuggestion::ACTION_ADVANCE_RECEIVED,
        ];
        // internal_transfer and mark_reviewed can be either direction

        if ($isDebit && in_array($action, $creditOnlyActions)) {
            $action = SmartSuggestion::ACTION_CREATE_EXPENSE; // Fallback debit action
        } elseif (! $isDebit && in_array($action, $debitOnlyActions)) {
            $action = SmartSuggestion::ACTION_RECORD_INCOME; // Fallback credit action
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
