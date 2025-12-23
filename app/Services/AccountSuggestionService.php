<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountMapping;
use App\Models\AccountSuggestionFeedback;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
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
     * Now includes calibrated confidence based on historical accuracy!
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
                'calibrated_confidence' => 0.0,
                'reason' => 'no_company',
                'sample_size' => 0,
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
                'calibrated_confidence' => 0.0,
                'reason' => 'no_match',
                'sample_size' => 0,
                'alternatives' => [],
            ];
        }

        $topSuggestion = array_shift($suggestions);

        // Apply confidence calibration based on historical accuracy
        $staticConfidence = $topSuggestion['confidence'];
        $historicalAccuracy = $this->getHistoricalAccuracy($companyId, $entityType, $topSuggestion['reason']);

        // Blend static confidence (60%) with historical accuracy (40%)
        $calibratedConfidence = ($staticConfidence * 0.6) + ($historicalAccuracy['rate'] * 0.4);

        $topSuggestion['calibrated_confidence'] = round($calibratedConfidence, 3);
        $topSuggestion['sample_size'] = $historicalAccuracy['sample_size'];
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
     *
     * Uses official 3-digit Macedonian chart of accounts (Regulation 174/2011):
     * - 231 = Обврски за ДДВ (VAT payable)
     * - 131 = Побарувања за ДДВ (VAT receivable)
     * - 102 = Жиро-сметка (Bank)
     * - 100 = Готовина (Cash)
     */
    protected function detectSpecialAccountType(string $text, int $companyId, string $entityType): ?array
    {
        $specialPatterns = [
            // VAT/Tax payable - Class 2 Liabilities
            ['patterns' => ['ддв', 'vat', 'данок', 'tax', 'ddv'], 'code' => '231', 'confidence' => 0.92],
            // Bank - Class 1 Cash and Receivables
            ['patterns' => ['банка', 'bank', 'жиро', 'трансакциска', 'комерцијална', 'стопанска', 'халк'], 'code' => '102', 'confidence' => 0.90],
            // Cash - Class 1 Cash and Receivables
            ['patterns' => ['каса', 'cash', 'готовина', 'готово'], 'code' => '100', 'confidence' => 0.90],
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
                            'reason' => 'special',
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
     * Uses official 3-digit Macedonian chart of accounts (Regulation 174/2011):
     * Class 7 - Revenue Coverage (ПОКРИВАЊЕ НА РАСХОДИ И ПРИХОДИ):
     * - 720 = Приходи од продажба на производи во земјата (Products)
     * - 721 = Приходи од продажба на услуги во земјата (Services)
     * - 722 = Приходи од продажба на стока во земјата (Goods/Trade)
     */
    protected function classifyItemType(string $text): ?array
    {
        // CONSULTING indicators (4030) - professional advisory services
        $consultingIndicators = [
            'консултации', 'консалтинг', 'consulting', 'советување',
            'анализа', 'истражување', 'ревизија', 'audit',
            'стратегија', 'планирање', 'проект',
        ];

        // SERVICE indicators (4020) - actions, work, intangible
        $serviceIndicators = [
            // Macedonian service words
            'услуга', 'услуги', 'сервис', 'поправка', 'одржување', 'чистење',
            'обука', 'тренинг', 'едукација',
            'дизајн', 'развој', 'програмирање', 'изработка', 'монтажа',
            'транспорт', 'достава', 'превоз', 'шпедиција',
            'закуп', 'наем', 'изнајмување', 'рента',
            'осигурување', 'застапување', 'посредување',
            'реклама', 'маркетинг', 'промоција',
            'правни', 'адвокат', 'нотар', 'сметководство',
            'поддршка', 'support', 'maintenance', 'hosting',
            // English service words
            'service', 'training', 'design', 'development',
            'cleaning', 'repair', 'delivery', 'shipping',
            'rental', 'lease', 'subscription', 'license',
        ];

        // GOODS/TRADE indicators (4010) - retail, resale items
        $goodsIndicators = [
            // Common trade goods
            'стока', 'стоки', 'трговија', 'малопродажба', 'велепродажба',
            'резервни делови', 'accessories', 'додатоци',
            // Retail categories
            'облека', 'обувки', 'чевли', 'патики', 'чизми', 'сандали',
            'храна', 'пијалок', 'пијалоци', 'прехрана',
            'козметика', 'парфем', 'шминка',
            'мебел', 'намештај', 'столици', 'маси', 'кревет',
            'играчки', 'книги', 'списанија',
            'алат', 'tools',
            // Brand indicators (usually goods)
            'nike', 'adidas', 'puma', 'samsung', 'apple', 'iphone', 'dell', 'hp', 'lenovo',
            'lg', 'sony', 'philips', 'bosch', 'ikea', 'zara', 'h&m',
        ];

        // PRODUCT indicators (4040) - manufactured items
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

        // Check for service suffix patterns (Macedonian gerunds end in -ње, -ење)
        $hasServiceSuffix = preg_match('/(ње|ење|ање|ирање)\b/u', $text);

        // Score each category
        $consultingScore = 0;
        $serviceScore = 0;
        $goodsScore = 0;
        $productScore = 0;

        foreach ($consultingIndicators as $indicator) {
            if (str_contains($text, $indicator)) {
                $consultingScore += 3; // Higher weight for consulting
            }
        }

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
        $maxScore = max($consultingScore, $serviceScore, $goodsScore, $productScore);

        if ($maxScore === 0) {
            return null; // No classification possible
        }

        // Calculate confidence based on score difference
        $totalScore = $consultingScore + $serviceScore + $goodsScore + $productScore;
        $confidence = min(0.95, 0.55 + ($maxScore / $totalScore) * 0.40);

        // Return appropriate account code based on winner
        // Uses official 3-digit Macedonian codes (Class 7 - Revenue)
        if ($consultingScore === $maxScore) {
            return ['code' => '721', 'confidence' => $confidence, 'type' => 'consulting']; // Services (includes consulting)
        } elseif ($serviceScore === $maxScore) {
            return ['code' => '721', 'confidence' => $confidence, 'type' => 'service']; // Services
        } elseif ($goodsScore === $maxScore) {
            return ['code' => '722', 'confidence' => $confidence, 'type' => 'goods']; // Goods/Trade
        } else {
            return ['code' => '720', 'confidence' => $confidence, 'type' => 'product']; // Products
        }
    }

    /**
     * Smart classification of expense type.
     *
     * Uses official 3-digit Macedonian chart of accounts (Regulation 174/2011):
     * Class 4 - Costs and Expenses (ТРОШОЦИ И РАСХОДИ):
     * - 400 = Трошоци за суровини и материјали
     * - 404 = Трошоци за канцелариски материјал
     * - 410 = Трошоци за транспортни услуги
     * - 412 = Трошоци за закупнини
     * - 413 = Трошоци за реклама и пропаганда
     * - 415 = Трошоци за комунални услуги
     * - 416 = Трошоци за сметководствени и консултантски услуги
     * - 417 = Трошоци за осигурување
     * - 418 = Трошоци за телефон, поштарина, интернет
     * - 420 = Плати на вработени
     * - 421 = Придонеси на товар на работодавач
     * - 430 = Амортизација на нематеријални средства
     * - 431 = Амортизација на материјални средства
     * - 440 = Трошоци за репрезентација
     * - 441 = Трошоци за службени патувања
     * - 445 = Трошоци за провизии и надоместоци
     * - 470 = Камати
     * Class 7 - Cost of goods sold:
     * - 702 = Вредност на продадена стока
     */
    protected function classifyExpenseType(string $text): ?array
    {
        $expenseCategories = [
            // Cost of goods sold (Class 7 - 702)
            ['patterns' => ['стока', 'набавка', 'залиха', 'inventory', 'goods'], 'code' => '702', 'confidence' => 0.85],
            // Materials (400)
            ['patterns' => ['материјал', 'суровина', 'material', 'raw'], 'code' => '400', 'confidence' => 0.85],
            // Office supplies (404)
            ['patterns' => ['канцелариски', 'office', 'хартија', 'тонер', 'печатач', 'потрепштини'], 'code' => '404', 'confidence' => 0.85],
            // Rent (412)
            ['patterns' => ['наем', 'кирија', 'rent', 'закуп', 'простор'], 'code' => '412', 'confidence' => 0.90],
            // Utilities (415)
            ['patterns' => ['струја', 'вода', 'греење', 'комунал', 'евн', 'електричн', 'utility', 'electricity', 'heating'], 'code' => '415', 'confidence' => 0.90],
            // Telecom (418)
            ['patterns' => ['телефон', 'интернет', 'telecom', 'мобилен', 'фиксен', 'a1', 'makedonski telekom', 'one', 't-mobile'], 'code' => '418', 'confidence' => 0.90],
            // Transport (410)
            ['patterns' => ['транспорт', 'гориво', 'бензин', 'дизел', 'такси', 'превоз', 'fuel', 'transport', 'паркинг', 'makpetrol', 'okta'], 'code' => '410', 'confidence' => 0.85],
            // Marketing (413)
            ['patterns' => ['маркетинг', 'реклама', 'промоција', 'marketing', 'advertising', 'facebook', 'google ads'], 'code' => '413', 'confidence' => 0.85],
            // Accounting services (416)
            ['patterns' => ['сметководство', 'сметководствен', 'accounting', 'bookkeeping'], 'code' => '416', 'confidence' => 0.90],
            // Legal & consulting (416)
            ['patterns' => ['адвокат', 'правни', 'консултант', 'consulting', 'нотар', 'legal'], 'code' => '416', 'confidence' => 0.85],
            // Bank fees (445)
            ['patterns' => ['провизија', 'банкарск', 'bank fee', 'bank charge', 'комерцијална', 'стопанска', 'халк'], 'code' => '445', 'confidence' => 0.90],
            // Salaries (420)
            ['patterns' => ['плата', 'salary', 'wage', 'бруто', 'нето', 'хонорар'], 'code' => '420', 'confidence' => 0.90],
            // Contributions (421)
            ['patterns' => ['придонес', 'пио', 'здравствен', 'contribution', 'пензиско'], 'code' => '421', 'confidence' => 0.90],
            // Travel (441)
            ['patterns' => ['дневница', 'патни', 'командировка', 'travel', 'патување'], 'code' => '441', 'confidence' => 0.85],
            // Depreciation (430)
            ['patterns' => ['амортизација', 'depreciation', 'отпис'], 'code' => '430', 'confidence' => 0.90],
            // Interest (470)
            ['patterns' => ['камата', 'interest', 'каматни'], 'code' => '470', 'confidence' => 0.90],
            // Insurance (417)
            ['patterns' => ['осигурување', 'insurance', 'полиса', 'premium', 'триглав', 'еуролинк', 'croatia', 'сава'], 'code' => '417', 'confidence' => 0.90],
            // Representation (440)
            ['patterns' => ['репрезентација', 'угостителство', 'ресторан', 'кафе', 'entertainment'], 'code' => '440', 'confidence' => 0.85],
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
     * Uses official 3-digit Macedonian chart of accounts (Regulation 174/2011).
     *
     * @param string $categoryName
     * @param int $companyId
     * @return array|null
     */
    protected function suggestByCategory(string $categoryName, int $companyId): ?array
    {
        $categoryLower = strtolower($categoryName);

        // Category to official 3-digit Macedonian account code mapping (Class 4 - Costs)
        $categoryMap = [
            'office' => '404',         // Трошоци за канцелариски материјал
            'канцелариски' => '404',
            'rent' => '412',           // Трошоци за закупнини
            'кирија' => '412',
            'наем' => '412',
            'закуп' => '412',
            'utilities' => '415',      // Трошоци за комунални услуги
            'комунални' => '415',
            'струја' => '415',
            'salaries' => '420',       // Плати на вработени
            'плата' => '420',
            'плати' => '420',
            'transport' => '410',      // Трошоци за транспортни услуги
            'транспорт' => '410',
            'гориво' => '410',
            'превоз' => '410',
            'telecom' => '418',        // Трошоци за телефон, поштарина, интернет
            'телефон' => '418',
            'интернет' => '418',
            'insurance' => '417',      // Трошоци за осигурување
            'осигурување' => '417',
            'материјал' => '400',      // Трошоци за суровини и материјали
            'material' => '400',
            'услуги' => '419',         // Други трошоци за услуги
            'service' => '419',
            'консалтинг' => '416',     // Трошоци за сметководствени и консултантски услуги
            'consulting' => '416',
            'банкарски' => '445',      // Трошоци за провизии и надоместоци
            'bank' => '445',
            'провизија' => '445',
            'амортизација' => '430',   // Амортизација на нематеријални средства
            'depreciation' => '430',
            'маркетинг' => '413',      // Трошоци за реклама и пропаганда
            'реклама' => '413',
            'сметководство' => '416',  // Трошоци за сметководствени и консултантски услуги
            'accounting' => '416',
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
                        'confidence' => 0.75,
                        'reason' => 'category',
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
     * Seeded accounts:
     * - 1610 = Побарувања од купувачи - домашни (Customer receivables)
     * - 2210 = Обврски - домашни добавувачи (Supplier payables)
     * - 5000 = Набавна вредност на продадена стока (General expense)
     * Uses official 3-digit Macedonian chart of accounts (Regulation 174/2011):
     * - 231 = Обврски за ДДВ (VAT payable)
     * - 720 = Приходи од продажба на производи во земјата (Revenue)
     * - 220 = Обврски кон добавувачи во земјата (Payables)
     * - 400 = Трошоци за суровини и материјали (Expenses)
     *
     * @param string $entityType
     * @param int $companyId
     * @return array|null
     */
    protected function suggestDefault(string $entityType, int $companyId): ?array
    {
        // Default account codes by entity type (using official 3-digit Macedonian codes)
        $defaults = [
            AccountMapping::ENTITY_CUSTOMER => '720', // Приходи од продажба на производи во земјата
            'customer' => '720',
            AccountMapping::ENTITY_SUPPLIER => '220', // Обврски кон добавувачи во земјата
            'supplier' => '220',
            AccountMapping::ENTITY_EXPENSE_CATEGORY => '400', // Трошоци за суровини и материјали
            'expense_category' => '400',
            'tax' => '231', // Обврски за ДДВ
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
            // Fallback to alternative codes (still 3-digit Macedonian)
            $fallbacks = [
                AccountMapping::ENTITY_CUSTOMER => '721', // Приходи од продажба на услуги во земјата
                'customer' => '721',
                AccountMapping::ENTITY_SUPPLIER => '220', // Обврски кон добавувачи во земјата
                'supplier' => '220',
                AccountMapping::ENTITY_EXPENSE_CATEGORY => '419', // Други трошоци за услуги
                'expense_category' => '419',
                'tax' => '231', // Обврски за ДДВ
            ];

            $account = Account::where('company_id', $companyId)
                ->where('code', $fallbacks[$entityType] ?? '400')
                ->where('is_active', true)
                ->first();
        }

        if (!$account) {
            // Last resort: any account of the appropriate type
            $typeMap = [
                AccountMapping::ENTITY_CUSTOMER => Account::TYPE_REVENUE,
                'customer' => Account::TYPE_REVENUE,
                AccountMapping::ENTITY_SUPPLIER => Account::TYPE_LIABILITY,
                'supplier' => Account::TYPE_LIABILITY,
                AccountMapping::ENTITY_EXPENSE_CATEGORY => Account::TYPE_EXPENSE,
                'expense_category' => Account::TYPE_EXPENSE,
                'tax' => Account::TYPE_LIABILITY,
            ];

            $account = Account::where('company_id', $companyId)
                ->where('type', $typeMap[$entityType] ?? Account::TYPE_EXPENSE)
                ->where('is_active', true)
                ->first();
        }

        if ($account) {
            return [
                'account_id' => $account->id,
                'account_code' => $account->code,
                'account_name' => $account->name,
                'confidence' => 0.35,
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

    /**
     * Record feedback for a suggestion to enable confidence calibration.
     *
     * This method should be called whenever a user accepts or modifies
     * an account suggestion to build historical accuracy data.
     *
     * @param int $companyId Company ID
     * @param string $entityType Entity type (customer, supplier, expense_category)
     * @param string $reason Suggestion reason (learned, pattern, category, default, special)
     * @param int $suggestedAccountId Account that was suggested by the AI
     * @param int|null $acceptedAccountId Account that user actually selected
     * @param float $originalConfidence Original static confidence score (0.000 to 1.000)
     * @return void
     */
    public function recordFeedback(
        int $companyId,
        string $entityType,
        string $reason,
        int $suggestedAccountId,
        ?int $acceptedAccountId,
        float $originalConfidence
    ): void {
        try {
            $wasAccepted = $suggestedAccountId === $acceptedAccountId;
            $wasModified = !$wasAccepted && $acceptedAccountId !== null;

            AccountSuggestionFeedback::create([
                'company_id' => $companyId,
                'entity_type' => $entityType,
                'suggestion_reason' => $reason,
                'suggested_account_id' => $suggestedAccountId,
                'accepted_account_id' => $acceptedAccountId,
                'original_confidence' => $originalConfidence,
                'was_accepted' => $wasAccepted,
                'was_modified' => $wasModified,
            ]);

            // Clear cache for this company+entity+reason combination
            $cacheKey = $this->getAccuracyCacheKey($companyId, $entityType, $reason);
            Cache::forget($cacheKey);

            Log::info('Account suggestion feedback recorded', [
                'company_id' => $companyId,
                'entity_type' => $entityType,
                'reason' => $reason,
                'was_accepted' => $wasAccepted,
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to record account suggestion feedback', [
                'company_id' => $companyId,
                'entity_type' => $entityType,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get historical accuracy rate for a specific suggestion type.
     *
     * Returns acceptance rate from last 100 suggestions with caching.
     * Falls back to 0.5 if insufficient data (< 10 samples).
     *
     * @param int $companyId Company ID
     * @param string $entityType Entity type (customer, supplier, expense_category)
     * @param string $reason Suggestion reason (learned, pattern, category, default, special)
     * @return array ['rate' => float, 'sample_size' => int]
     */
    public function getHistoricalAccuracy(
        int $companyId,
        string $entityType,
        string $reason
    ): array {
        $cacheKey = $this->getAccuracyCacheKey($companyId, $entityType, $reason);

        return Cache::remember($cacheKey, 3600, function () use ($companyId, $entityType, $reason) {
            $accuracy = AccountSuggestionFeedback::calculateAccuracyRate(
                $companyId,
                $entityType,
                $reason,
                100 // Last 100 suggestions
            );

            // Fall back to neutral 0.5 if not enough data
            if ($accuracy['sample_size'] < 10) {
                return [
                    'rate' => 0.5,
                    'sample_size' => $accuracy['sample_size'],
                ];
            }

            return $accuracy;
        });
    }

    /**
     * Learn from user feedback when they confirm or change an account selection.
     *
     * This method combines the existing mapping learning with the new feedback tracking.
     * Call this when a user makes a final account selection (accepts or modifies a suggestion).
     *
     * @param int $companyId Company ID
     * @param string $entityType Entity type
     * @param int|null $entityId Entity ID (if available)
     * @param int $suggestedAccountId Account that was suggested
     * @param int $selectedAccountId Account that user selected
     * @param float $originalConfidence Original confidence score
     * @param string $suggestionReason Reason for the suggestion
     * @return void
     */
    public function learnFromFeedback(
        int $companyId,
        string $entityType,
        ?int $entityId,
        int $suggestedAccountId,
        int $selectedAccountId,
        float $originalConfidence,
        string $suggestionReason
    ): void {
        // Record feedback for calibration
        $this->recordFeedback(
            $companyId,
            $entityType,
            $suggestionReason,
            $suggestedAccountId,
            $selectedAccountId,
            $originalConfidence
        );

        // Learn the mapping if entity ID is available
        if ($entityId !== null) {
            $this->learnMapping($entityType, $entityId, $selectedAccountId, $companyId);
        }
    }

    /**
     * Generate cache key for accuracy data.
     *
     * @param int $companyId
     * @param string $entityType
     * @param string $reason
     * @return string
     */
    protected function getAccuracyCacheKey(int $companyId, string $entityType, string $reason): string
    {
        return "account_suggestion_accuracy:{$companyId}:{$entityType}:{$reason}";
    }
}
// CLAUDE-CHECKPOINT
