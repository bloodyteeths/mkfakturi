<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountMapping;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Account Suggestion Service
 *
 * Provides AI-powered account suggestions for transactions based on:
 * - Existing account mappings
 * - Historical patterns from confirmed transactions
 * - Default accounts from company settings
 * - Transaction type conventions
 */
class AccountSuggestionService
{
    /**
     * Suggest accounts for an invoice.
     *
     * For invoices: Debit=Receivables, Credit=Revenue
     *
     * @return array ['debit_account_id' => X, 'credit_account_id' => Y, 'confidence' => 0.85]
     */
    public function suggestForInvoice(Invoice $invoice): array
    {
        $companyId = $invoice->company_id;
        $confidence = 0.5; // Base confidence

        // Try to find customer-specific mapping first
        $mapping = AccountMapping::findForEntity(
            $companyId,
            AccountMapping::ENTITY_CUSTOMER,
            $invoice->customer_id,
            AccountMapping::TRANSACTION_INVOICE
        );

        if ($mapping && $mapping->debit_account_id && $mapping->credit_account_id) {
            return [
                'debit_account_id' => $mapping->debit_account_id,
                'credit_account_id' => $mapping->credit_account_id,
                'confidence' => 0.95, // High confidence for explicit mapping
            ];
        }

        // Check for patterns from similar confirmed invoices for this customer
        $similarInvoice = Invoice::where('company_id', $companyId)
            ->where('customer_id', $invoice->customer_id)
            ->whereNotNull('confirmed_debit_account_id')
            ->whereNotNull('confirmed_credit_account_id')
            ->latest('account_confirmed_at')
            ->first();

        if ($similarInvoice) {
            return [
                'debit_account_id' => $similarInvoice->confirmed_debit_account_id,
                'credit_account_id' => $similarInvoice->confirmed_credit_account_id,
                'confidence' => 0.85, // High confidence for historical pattern
            ];
        }

        // Fall back to default mapping for invoices
        $defaultMapping = AccountMapping::findForEntity(
            $companyId,
            AccountMapping::ENTITY_DEFAULT,
            null,
            AccountMapping::TRANSACTION_INVOICE
        );

        if ($defaultMapping && $defaultMapping->debit_account_id && $defaultMapping->credit_account_id) {
            return [
                'debit_account_id' => $defaultMapping->debit_account_id,
                'credit_account_id' => $defaultMapping->credit_account_id,
                'confidence' => 0.70,
            ];
        }

        // Last resort: Find typical asset (receivables) and revenue accounts
        $debitAccount = Account::where('company_id', $companyId)
            ->where('type', Account::TYPE_ASSET)
            ->where('is_active', true)
            ->where('name', 'LIKE', '%receivable%')
            ->first();

        $creditAccount = Account::where('company_id', $companyId)
            ->where('type', Account::TYPE_REVENUE)
            ->where('is_active', true)
            ->first();

        return [
            'debit_account_id' => $debitAccount?->id,
            'credit_account_id' => $creditAccount?->id,
            'confidence' => 0.50, // Low confidence for generic suggestion
        ];
    }

    /**
     * Suggest accounts for an expense.
     *
     * For expenses: Debit=Expense category account, Credit=Payables/Cash
     *
     * @return array ['debit_account_id' => X, 'credit_account_id' => Y, 'confidence' => 0.85]
     */
    public function suggestForExpense(Expense $expense): array
    {
        $companyId = $expense->company_id;

        // Try expense category mapping first
        if ($expense->expense_category_id) {
            $mapping = AccountMapping::findForEntity(
                $companyId,
                AccountMapping::ENTITY_EXPENSE_CATEGORY,
                $expense->expense_category_id,
                AccountMapping::TRANSACTION_EXPENSE
            );

            if ($mapping && $mapping->debit_account_id && $mapping->credit_account_id) {
                return [
                    'debit_account_id' => $mapping->debit_account_id,
                    'credit_account_id' => $mapping->credit_account_id,
                    'confidence' => 0.95,
                ];
            }
        }

        // Check for supplier-specific mapping
        if ($expense->supplier_id) {
            $mapping = AccountMapping::findForEntity(
                $companyId,
                AccountMapping::ENTITY_SUPPLIER,
                $expense->supplier_id,
                AccountMapping::TRANSACTION_EXPENSE
            );

            if ($mapping && $mapping->debit_account_id && $mapping->credit_account_id) {
                return [
                    'debit_account_id' => $mapping->debit_account_id,
                    'credit_account_id' => $mapping->credit_account_id,
                    'confidence' => 0.90,
                ];
            }
        }

        // Check for patterns from similar confirmed expenses
        $query = Expense::where('company_id', $companyId)
            ->whereNotNull('confirmed_debit_account_id')
            ->whereNotNull('confirmed_credit_account_id');

        if ($expense->expense_category_id) {
            $query->where('expense_category_id', $expense->expense_category_id);
        }

        $similarExpense = $query->latest('account_confirmed_at')->first();

        if ($similarExpense) {
            return [
                'debit_account_id' => $similarExpense->confirmed_debit_account_id,
                'credit_account_id' => $similarExpense->confirmed_credit_account_id,
                'confidence' => 0.80,
            ];
        }

        // Check payment method mapping
        if ($expense->payment_method_id) {
            $mapping = AccountMapping::findForEntity(
                $companyId,
                AccountMapping::ENTITY_PAYMENT_METHOD,
                $expense->payment_method_id,
                AccountMapping::TRANSACTION_EXPENSE
            );

            if ($mapping && $mapping->credit_account_id) {
                // Use payment method for credit side (cash/bank)
                $debitAccount = Account::where('company_id', $companyId)
                    ->where('type', Account::TYPE_EXPENSE)
                    ->where('is_active', true)
                    ->first();

                return [
                    'debit_account_id' => $debitAccount?->id,
                    'credit_account_id' => $mapping->credit_account_id,
                    'confidence' => 0.70,
                ];
            }
        }

        // Fall back to default expense mapping
        $defaultMapping = AccountMapping::findForEntity(
            $companyId,
            AccountMapping::ENTITY_DEFAULT,
            null,
            AccountMapping::TRANSACTION_EXPENSE
        );

        if ($defaultMapping && $defaultMapping->debit_account_id && $defaultMapping->credit_account_id) {
            return [
                'debit_account_id' => $defaultMapping->debit_account_id,
                'credit_account_id' => $defaultMapping->credit_account_id,
                'confidence' => 0.65,
            ];
        }

        // Last resort: Generic expense and payables accounts
        $debitAccount = Account::where('company_id', $companyId)
            ->where('type', Account::TYPE_EXPENSE)
            ->where('is_active', true)
            ->first();

        $creditAccount = Account::where('company_id', $companyId)
            ->where('type', Account::TYPE_LIABILITY)
            ->where('is_active', true)
            ->where('name', 'LIKE', '%payable%')
            ->first();

        // If no payables, try cash account
        if (! $creditAccount) {
            $creditAccount = Account::where('company_id', $companyId)
                ->where('type', Account::TYPE_ASSET)
                ->where('is_active', true)
                ->where('name', 'LIKE', '%cash%')
                ->first();
        }

        return [
            'debit_account_id' => $debitAccount?->id,
            'credit_account_id' => $creditAccount?->id,
            'confidence' => 0.50,
        ];
    }

    /**
     * Suggest accounts for a payment.
     *
     * For payments: Debit=Cash/Bank, Credit=Receivables
     *
     * @return array ['debit_account_id' => X, 'credit_account_id' => Y, 'confidence' => 0.85]
     */
    public function suggestForPayment(Payment $payment): array
    {
        $companyId = $payment->company_id;

        // Try payment method mapping first
        if ($payment->payment_method_id) {
            $mapping = AccountMapping::findForEntity(
                $companyId,
                AccountMapping::ENTITY_PAYMENT_METHOD,
                $payment->payment_method_id,
                AccountMapping::TRANSACTION_PAYMENT
            );

            if ($mapping && $mapping->debit_account_id && $mapping->credit_account_id) {
                return [
                    'debit_account_id' => $mapping->debit_account_id,
                    'credit_account_id' => $mapping->credit_account_id,
                    'confidence' => 0.95,
                ];
            }
        }

        // Check for patterns from similar confirmed payments
        $similarPayment = Payment::where('company_id', $companyId)
            ->whereNotNull('confirmed_debit_account_id')
            ->whereNotNull('confirmed_credit_account_id')
            ->latest('account_confirmed_at')
            ->first();

        if ($similarPayment) {
            return [
                'debit_account_id' => $similarPayment->confirmed_debit_account_id,
                'credit_account_id' => $similarPayment->confirmed_credit_account_id,
                'confidence' => 0.85,
            ];
        }

        // Fall back to default payment mapping
        $defaultMapping = AccountMapping::findForEntity(
            $companyId,
            AccountMapping::ENTITY_DEFAULT,
            null,
            AccountMapping::TRANSACTION_PAYMENT
        );

        if ($defaultMapping && $defaultMapping->debit_account_id && $defaultMapping->credit_account_id) {
            return [
                'debit_account_id' => $defaultMapping->debit_account_id,
                'credit_account_id' => $defaultMapping->credit_account_id,
                'confidence' => 0.70,
            ];
        }

        // Last resort: Find typical cash and receivables accounts
        $debitAccount = Account::where('company_id', $companyId)
            ->where('type', Account::TYPE_ASSET)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->where('name', 'LIKE', '%cash%')
                    ->orWhere('name', 'LIKE', '%bank%');
            })
            ->first();

        $creditAccount = Account::where('company_id', $companyId)
            ->where('type', Account::TYPE_ASSET)
            ->where('is_active', true)
            ->where('name', 'LIKE', '%receivable%')
            ->first();

        return [
            'debit_account_id' => $debitAccount?->id,
            'credit_account_id' => $creditAccount?->id,
            'confidence' => 0.50,
        ];
    }

    /**
     * Confirm the account suggestion for a transaction.
     *
     * @param  Model  $model  Invoice, Expense, or Payment
     */
    public function confirmSuggestion(Model $model, int $debitAccountId, int $creditAccountId, User $user): void
    {
        $model->update([
            'confirmed_debit_account_id' => $debitAccountId,
            'confirmed_credit_account_id' => $creditAccountId,
            'account_confirmed_at' => now(),
            'account_confirmed_by' => $user->id,
        ]);

        // Learn from this confirmation
        $this->learnFromConfirmation($model);
    }

    /**
     * Update mappings based on confirmed selections for better future suggestions.
     *
     * @param  Model  $model  Invoice, Expense, or Payment
     */
    public function learnFromConfirmation(Model $model): void
    {
        if (! $model->confirmed_debit_account_id || ! $model->confirmed_credit_account_id) {
            return;
        }

        try {
            if ($model instanceof Invoice && $model->customer_id) {
                // Create or update customer mapping for invoices
                $this->upsertMapping(
                    $model->company_id,
                    AccountMapping::ENTITY_CUSTOMER,
                    $model->customer_id,
                    AccountMapping::TRANSACTION_INVOICE,
                    $model->confirmed_debit_account_id,
                    $model->confirmed_credit_account_id
                );
            } elseif ($model instanceof Expense) {
                // Create or update expense category mapping
                if ($model->expense_category_id) {
                    $this->upsertMapping(
                        $model->company_id,
                        AccountMapping::ENTITY_EXPENSE_CATEGORY,
                        $model->expense_category_id,
                        AccountMapping::TRANSACTION_EXPENSE,
                        $model->confirmed_debit_account_id,
                        $model->confirmed_credit_account_id
                    );
                }

                // Also update supplier mapping if applicable
                if ($model->supplier_id) {
                    $this->upsertMapping(
                        $model->company_id,
                        AccountMapping::ENTITY_SUPPLIER,
                        $model->supplier_id,
                        AccountMapping::TRANSACTION_EXPENSE,
                        $model->confirmed_debit_account_id,
                        $model->confirmed_credit_account_id
                    );
                }
            } elseif ($model instanceof Payment && $model->payment_method_id) {
                // Create or update payment method mapping
                $this->upsertMapping(
                    $model->company_id,
                    AccountMapping::ENTITY_PAYMENT_METHOD,
                    $model->payment_method_id,
                    AccountMapping::TRANSACTION_PAYMENT,
                    $model->confirmed_debit_account_id,
                    $model->confirmed_credit_account_id
                );
            }
        } catch (\Exception $e) {
            Log::warning('Failed to learn from account confirmation', [
                'model' => get_class($model),
                'model_id' => $model->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Helper to upsert account mapping.
     */
    protected function upsertMapping(
        int $companyId,
        string $entityType,
        ?int $entityId,
        string $transactionType,
        int $debitAccountId,
        int $creditAccountId
    ): void {
        AccountMapping::updateOrCreate(
            [
                'company_id' => $companyId,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'transaction_type' => $transactionType,
            ],
            [
                'debit_account_id' => $debitAccountId,
                'credit_account_id' => $creditAccountId,
            ]
        );
    }

    /**
     * Suggest accounts with detailed confidence scoring and alternatives.
     *
     * This method provides AI-powered account suggestions for any entity
     * (customer, supplier, expense_category) with confidence scores,
     * reasoning, and alternative suggestions.
     *
     * @param string $entityType Entity type (customer, supplier, expense_category)
     * @param string $entityName Name of the entity for pattern matching
     * @param string|null $description Optional transaction description
     * @param int|null $companyId Company ID (required)
     * @return array Suggestion with confidence, reason, and alternatives
     */
    public function suggestWithConfidence(
        string $entityType,
        string $entityName,
        ?string $description = null,
        ?int $companyId = null
    ): array {
        if (!$companyId) {
            return [
                'account_id' => null,
                'account_code' => null,
                'account_name' => null,
                'confidence' => 0.0,
                'reason' => 'no_company',
                'alternatives' => [],
            ];
        }

        // Step 1: Check for exact learned mapping (entity_id based)
        // This would require the actual entity_id, but since we only have the name,
        // we'll check by pattern matching on entity name
        $suggestions = [];

        // Step 2: Pattern matching on entity name
        $patternSuggestion = $this->suggestByPattern($entityName, $description, $companyId, $entityType);
        if ($patternSuggestion) {
            $suggestions[] = $patternSuggestion;
        }

        // Step 3: Category-based matching (for expense categories)
        if ($entityType === AccountMapping::ENTITY_EXPENSE_CATEGORY) {
            $categorySuggestion = $this->suggestByCategory($entityName, $companyId);
            if ($categorySuggestion) {
                $suggestions[] = $categorySuggestion;
            }
        }

        // Step 4: Default account for entity type
        $defaultSuggestion = $this->suggestDefault($entityType, $companyId);
        if ($defaultSuggestion) {
            $suggestions[] = $defaultSuggestion;
        }

        // Sort suggestions by confidence (highest first)
        usort($suggestions, function ($a, $b) {
            return $b['confidence'] <=> $a['confidence'];
        });

        // Return top suggestion with alternatives
        if (empty($suggestions)) {
            return [
                'account_id' => null,
                'account_code' => null,
                'account_name' => null,
                'confidence' => 0.0,
                'reason' => 'no_match',
                'alternatives' => [],
            ];
        }

        $topSuggestion = array_shift($suggestions);
        $topSuggestion['alternatives'] = array_slice($suggestions, 0, 3); // Top 3 alternatives

        return $topSuggestion;
    }

    /**
     * Smart AI classifier for invoice items.
     *
     * Determines if item is: PRODUCT (6010), SERVICE (6020), or GOODS (6030)
     * Uses heuristics based on common patterns, not just keywords.
     *
     * @param string $entityName
     * @param string|null $description
     * @param int $companyId
     * @param string $entityType
     * @return array|null
     */
    protected function suggestByPattern(
        string $entityName,
        ?string $description,
        int $companyId,
        string $entityType
    ): ?array {
        $searchText = strtolower($entityName . ' ' . ($description ?? ''));

        // First check for special account types (VAT, bank, etc.)
        $specialAccount = $this->detectSpecialAccountType($searchText, $companyId, $entityType);
        if ($specialAccount) {
            return $specialAccount;
        }

        // For customer invoices, classify items as product/service/goods
        if ($entityType === AccountMapping::ENTITY_CUSTOMER || $entityType === 'customer') {
            $classification = $this->classifyItemType($searchText);
            if ($classification) {
                $account = Account::where('company_id', $companyId)
                    ->where('code', $classification['code'])
                    ->where('is_active', true)
                    ->first();

                if ($account) {
                    return [
                        'account_id' => $account->id,
                        'account_code' => $account->code,
                        'account_name' => $account->name,
                        'confidence' => $classification['confidence'],
                        'reason' => 'learned',
                        'alternatives' => [],
                    ];
                }
            }
        }

        // For expenses, classify by expense type
        if ($entityType === AccountMapping::ENTITY_EXPENSE_CATEGORY || $entityType === AccountMapping::ENTITY_SUPPLIER) {
            $expenseType = $this->classifyExpenseType($searchText);
            if ($expenseType) {
                $account = Account::where('company_id', $companyId)
                    ->where('code', $expenseType['code'])
                    ->where('is_active', true)
                    ->first();

                if ($account) {
                    return [
                        'account_id' => $account->id,
                        'account_code' => $account->code,
                        'account_name' => $account->name,
                        'confidence' => $expenseType['confidence'],
                        'reason' => 'learned',
                        'alternatives' => [],
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Detect special account types (VAT, bank, cash, etc.)
     */
    protected function detectSpecialAccountType(string $text, int $companyId, string $entityType): ?array
    {
        $specialPatterns = [
            // VAT/Tax
            ['patterns' => ['ддв', 'vat', 'данок', 'tax', 'ddv'], 'code' => '4700', 'confidence' => 0.95],
            // Bank
            ['patterns' => ['банка', 'bank', 'жиро', 'трансакциска'], 'code' => '1020', 'confidence' => 0.90],
            // Cash
            ['patterns' => ['каса', 'cash', 'готовина', 'готово'], 'code' => '1010', 'confidence' => 0.90],
        ];

        foreach ($specialPatterns as $special) {
            foreach ($special['patterns'] as $pattern) {
                if (str_contains($text, $pattern)) {
                    $account = Account::where('company_id', $companyId)
                        ->where('code', $special['code'])
                        ->where('is_active', true)
                        ->first();

                    if ($account) {
                        return [
                            'account_id' => $account->id,
                            'account_code' => $account->code,
                            'account_name' => $account->name,
                            'confidence' => $special['confidence'],
                            'reason' => 'learned',
                            'alternatives' => [],
                        ];
                    }
                }
            }
        }

        return null;
    }

    /**
     * Smart classification of item type for revenue accounts.
     *
     * Returns: 6010 (products), 6020 (services), 6030 (goods/trade)
     */
    protected function classifyItemType(string $text): ?array
    {
        // SERVICE indicators (6020) - actions, work, intangible
        $serviceIndicators = [
            // Macedonian service words
            'услуга', 'услуги', 'сервис', 'поправка', 'одржување', 'чистење',
            'консултации', 'консалтинг', 'совет', 'обука', 'тренинг',
            'дизајн', 'развој', 'програмирање', 'изработка', 'монтажа',
            'транспорт', 'достава', 'превоз', 'шпедиција',
            'закуп', 'наем', 'изнајмување', 'рента',
            'осигурување', 'застапување', 'посредување',
            'реклама', 'маркетинг', 'промоција',
            'правни', 'адвокат', 'нотар', 'сметководство', 'ревизија',
            'проект', 'планирање', 'анализа', 'истражување',
            'поддршка', 'support', 'maintenance', 'hosting',
            // English service words
            'service', 'consulting', 'training', 'design', 'development',
            'cleaning', 'repair', 'maintenance', 'delivery', 'shipping',
            'rental', 'lease', 'subscription', 'license', 'support',
            // Service verb endings in Macedonian (-ње, -ење)
        ];

        // Check for service suffix patterns (Macedonian gerunds end in -ње, -ење)
        $hasServiceSuffix = preg_match('/(ње|ење|ање|ирање)\b/u', $text);

        // GOODS/TRADE indicators (6030) - retail, resale items
        $goodsIndicators = [
            // Common trade goods
            'стока', 'стоки', 'трговија', 'малопродажба', 'велепродажба',
            'резервни делови', 'accessories', 'додатоци',
            // Retail categories
            'облека', 'обувки', 'чевли', 'патики', 'чизми', 'сандали',
            'храна', 'пијалок', 'пијалоци', 'храна', 'прехрана',
            'козметика', 'парфем', 'шминка',
            'мебел', 'намештај', 'столици', 'маси', 'кревет',
            'играчки', 'книги', 'списанија',
            'алат', 'tools', 'опрема',
            // Brand indicators (usually goods)
            'nike', 'adidas', 'puma', 'samsung', 'apple', 'iphone', 'dell', 'hp', 'lenovo',
            'lg', 'sony', 'philips', 'bosch', 'ikea', 'zara', 'h&m',
        ];

        // PRODUCT indicators (6010) - manufactured items
        $productIndicators = [
            // Manufacturing/production
            'производ', 'производи', 'production', 'manufactured',
            'машина', 'машини', 'уред', 'апарат', 'опрема',
            // Electronics
            'компјутер', 'лаптоп', 'телефон', 'мобилен', 'таблет', 'монитор',
            'принтер', 'скенер', 'рутер', 'сервер', 'хард диск', 'ssd',
            'камера', 'фотоапарат', 'проектор', 'телевизор', 'tv',
            // Materials/raw goods
            'материјал', 'суровина', 'челик', 'алуминиум', 'пластика',
            'дрво', 'стакло', 'бетон', 'цемент', 'боја',
            // Parts/components
            'дел', 'делови', 'компонент', 'склоп',
            // Software as product
            'софтвер', 'software', 'лиценца', 'апликација',
        ];

        // Score each category
        $serviceScore = 0;
        $goodsScore = 0;
        $productScore = 0;

        foreach ($serviceIndicators as $indicator) {
            if (str_contains($text, $indicator)) {
                $serviceScore += 2;
            }
        }
        if ($hasServiceSuffix) {
            $serviceScore += 3;
        }

        foreach ($goodsIndicators as $indicator) {
            if (str_contains($text, $indicator)) {
                $goodsScore += 2;
            }
        }

        foreach ($productIndicators as $indicator) {
            if (str_contains($text, $indicator)) {
                $productScore += 2;
            }
        }

        // Determine winner
        $maxScore = max($serviceScore, $goodsScore, $productScore);

        if ($maxScore === 0) {
            return null; // No classification possible
        }

        // Calculate confidence based on score difference
        $totalScore = $serviceScore + $goodsScore + $productScore;
        $confidence = min(0.95, 0.6 + ($maxScore / $totalScore) * 0.35);

        if ($serviceScore === $maxScore) {
            return ['code' => '6020', 'confidence' => $confidence, 'type' => 'service'];
        } elseif ($goodsScore === $maxScore) {
            return ['code' => '6030', 'confidence' => $confidence, 'type' => 'goods'];
        } else {
            return ['code' => '6010', 'confidence' => $confidence, 'type' => 'product'];
        }
    }

    /**
     * Smart classification of expense type.
     *
     * Returns appropriate 7xxx expense account code.
     */
    protected function classifyExpenseType(string $text): ?array
    {
        $expenseCategories = [
            // Materials (7010)
            ['patterns' => ['материјал', 'суровина', 'material', 'raw', 'залиха'], 'code' => '7010', 'confidence' => 0.85],
            // Services (7020)
            ['patterns' => ['услуга', 'сервис', 'service', 'консултант', 'consulting', 'поддршка'], 'code' => '7020', 'confidence' => 0.85],
            // Salaries (7030)
            ['patterns' => ['плата', 'salary', 'wage', 'надомест', 'бонус', 'хонорар'], 'code' => '7030', 'confidence' => 0.90],
            // Depreciation (7040)
            ['patterns' => ['амортизација', 'depreciation', 'отпис'], 'code' => '7040', 'confidence' => 0.90],
            // Office supplies (7050)
            ['patterns' => ['канцелариски', 'office', 'хартија', 'тонер', 'печатач'], 'code' => '7050', 'confidence' => 0.85],
            // Telecom (7060)
            ['patterns' => ['телефон', 'интернет', 'telecom', 'мобилен', 'фиксен', 'a1', 'makedonski telekom', 'one'], 'code' => '7060', 'confidence' => 0.90],
            // Transport (7070)
            ['patterns' => ['транспорт', 'гориво', 'бензин', 'дизел', 'такси', 'превоз', 'fuel', 'transport', 'паркинг'], 'code' => '7070', 'confidence' => 0.85],
            // Rent (7080)
            ['patterns' => ['наем', 'кирија', 'rent', 'закуп', 'простор', 'канцеларија'], 'code' => '7080', 'confidence' => 0.90],
            // Utilities (7090)
            ['patterns' => ['струја', 'вода', 'греење', 'комунал', 'евн', 'електричн', 'utility', 'electricity', 'heating'], 'code' => '7090', 'confidence' => 0.90],
            // Insurance (7100)
            ['patterns' => ['осигурување', 'insurance', 'полиса', 'premium', 'триглав', 'еуролинк', 'croatia'], 'code' => '7100', 'confidence' => 0.90],
            // Financial expenses (7800)
            ['patterns' => ['камата', 'провизија', 'interest', 'fee', 'банкарски', 'bank charge'], 'code' => '7800', 'confidence' => 0.85],
        ];

        foreach ($expenseCategories as $category) {
            foreach ($category['patterns'] as $pattern) {
                if (str_contains($text, $pattern)) {
                    return ['code' => $category['code'], 'confidence' => $category['confidence']];
                }
            }
        }

        return null;
    }

    /**
     * Suggest account based on expense category matching.
     *
     * Uses actual Macedonian Chart of Accounts codes from the seeder.
     *
     * @param string $categoryName
     * @param int $companyId
     * @return array|null
     */
    protected function suggestByCategory(string $categoryName, int $companyId): ?array
    {
        $categoryLower = strtolower($categoryName);

        // Category to ACTUAL seeded account code mapping
        $categoryMap = [
            'office' => '7050',        // Канцелариски материјал
            'канцелариски' => '7050',
            'rent' => '7080',          // Наем
            'кирија' => '7080',
            'наем' => '7080',
            'utilities' => '7090',     // Комунални трошоци
            'комунални' => '7090',
            'salaries' => '7030',      // Плати и надоместоци
            'плата' => '7030',
            'transport' => '7070',     // Транспортни трошоци
            'транспорт' => '7070',
            'гориво' => '7070',
            'telecom' => '7060',       // Телекомуникации
            'телефон' => '7060',
            'интернет' => '7060',
            'insurance' => '7100',     // Осигурување
            'осигурување' => '7100',
            'материјал' => '7010',     // Трошоци за материјали
            'material' => '7010',
            'услуги' => '7020',        // Трошоци за услуги
            'service' => '7020',
            'консалтинг' => '7020',
            'consulting' => '7020',
            'банкарски' => '7800',     // Финансиски расходи
            'bank fee' => '7800',
            'амортизација' => '7040',  // Амортизација
            'depreciation' => '7040',
        ];

        foreach ($categoryMap as $keyword => $accountCode) {
            if (str_contains($categoryLower, $keyword)) {
                $account = Account::where('company_id', $companyId)
                    ->where('code', $accountCode)
                    ->where('is_active', true)
                    ->first();

                if ($account) {
                    return [
                        'account_id' => $account->id,
                        'account_code' => $account->code,
                        'account_name' => $account->name,
                        'confidence' => 0.7,
                        'reason' => 'pattern',
                        'alternatives' => [],
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Suggest default account for entity type.
     *
     * Uses actual Macedonian Chart of Accounts codes from the seeder.
     *
     * @param string $entityType
     * @param int $companyId
     * @return array|null
     */
    protected function suggestDefault(string $entityType, int $companyId): ?array
    {
        // Default account codes by entity type (using ACTUAL seeded codes)
        // Seeded: 2201 = Domestic receivables, 4201 = Domestic payables, 7000 = Expenses
        $defaults = [
            AccountMapping::ENTITY_CUSTOMER => '2201', // Побарувања од купувачи - домашни
            AccountMapping::ENTITY_SUPPLIER => '4201', // Обврски кон добавувачи - домашни
            AccountMapping::ENTITY_EXPENSE_CATEGORY => '7000', // Расходи (general expenses)
            'tax' => '4700', // ДДВ обврска (VAT liability)
        ];

        $accountCode = $defaults[$entityType] ?? null;
        if (!$accountCode) {
            return null;
        }

        $account = Account::where('company_id', $companyId)
            ->where('code', $accountCode)
            ->where('is_active', true)
            ->first();

        if (!$account) {
            // Fallback to parent account codes
            $fallbacks = [
                AccountMapping::ENTITY_CUSTOMER => '2200', // Побарувања
                AccountMapping::ENTITY_SUPPLIER => '4200', // Обврски кон добавувачи
                AccountMapping::ENTITY_EXPENSE_CATEGORY => '7000', // Расходи
                'tax' => '4700', // ДДВ обврска
            ];

            $account = Account::where('company_id', $companyId)
                ->where('code', $fallbacks[$entityType] ?? '7000')
                ->where('is_active', true)
                ->first();
        }

        if (!$account) {
            // Last resort: any account of the appropriate type
            $typeMap = [
                AccountMapping::ENTITY_CUSTOMER => 'asset',
                AccountMapping::ENTITY_SUPPLIER => 'liability',
                AccountMapping::ENTITY_EXPENSE_CATEGORY => 'expense',
                'tax' => 'liability',
            ];

            $account = Account::where('company_id', $companyId)
                ->where('type', $typeMap[$entityType] ?? 'expense')
                ->where('is_active', true)
                ->first();
        }

        if ($account) {
            return [
                'account_id' => $account->id,
                'account_code' => $account->code,
                'account_name' => $account->name,
                'confidence' => 0.3,
                'reason' => 'default',
                'alternatives' => [],
            ];
        }

        return null;
    }

    /**
     * Learn a new account mapping from user confirmation.
     *
     * This method stores a mapping between an entity and an account
     * so future suggestions can use this learned information.
     *
     * @param string $entityType Entity type (customer, supplier, expense_category)
     * @param int $entityId ID of the entity
     * @param int $accountId Account ID to map to
     * @param int $companyId Company ID
     * @return void
     */
    public function learnMapping(
        string $entityType,
        int $entityId,
        int $accountId,
        int $companyId
    ): void {
        try {
            // For single-sided mappings, we'll use the account for both debit and credit
            // The caller can specify transaction type if needed
            AccountMapping::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'entity_type' => $entityType,
                    'entity_id' => $entityId,
                    'transaction_type' => null, // Generic mapping
                ],
                [
                    'debit_account_id' => $accountId,
                    'credit_account_id' => null,
                    'meta' => [
                        'learned_at' => now()->toIso8601String(),
                        'method' => 'ai_suggestion',
                    ],
                ]
            );

            Log::info('Account mapping learned', [
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'account_id' => $accountId,
                'company_id' => $companyId,
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to learn account mapping', [
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'account_id' => $accountId,
                'company_id' => $companyId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
// CLAUDE-CHECKPOINT
