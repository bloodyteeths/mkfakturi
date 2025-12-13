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
     *
     * Uses actual seeded account codes:
     * - 2710 = ДДВ за уплата (VAT payable)
     * - 1630 = ДДВ за поврат (VAT receivable)
     * - 1020 = Жиро сметка (Bank)
     * - 1010 = Каса (Cash)
     */
    protected function detectSpecialAccountType(string $text, int $companyId, string $entityType): ?array
    {
        $specialPatterns = [
            // VAT/Tax payable
            ['patterns' => ['ддв', 'vat', 'данок', 'tax', 'ddv'], 'code' => '2710', 'confidence' => 0.92],
            // Bank
            ['patterns' => ['банка', 'bank', 'жиро', 'трансакциска', 'комерцијална', 'стопанска', 'халк'], 'code' => '1020', 'confidence' => 0.90],
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
     * Uses ACTUAL seeded Macedonian chart of accounts:
     * - 4040 = Приходи од производи (Products)
     * - 4020 = Приходи од услуги (Services)
     * - 4010 = Приходи од продажба на стока (Goods/Trade)
     * - 4030 = Приходи од консалтинг (Consulting - special service)
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
        if ($consultingScore === $maxScore) {
            return ['code' => '4030', 'confidence' => $confidence, 'type' => 'consulting'];
        } elseif ($serviceScore === $maxScore) {
            return ['code' => '4020', 'confidence' => $confidence, 'type' => 'service'];
        } elseif ($goodsScore === $maxScore) {
            return ['code' => '4010', 'confidence' => $confidence, 'type' => 'goods'];
        } else {
            return ['code' => '4040', 'confidence' => $confidence, 'type' => 'product'];
        }
    }

    /**
     * Smart classification of expense type.
     *
     * Uses ACTUAL seeded Macedonian chart of accounts (5xxx):
     * - 5010 = Набавка на стока
     * - 5020 = Набавка на материјали
     * - 5210 = Канцелариски материјал
     * - 5410 = Кирија
     * - 5420 = Комунални услуги
     * - 5430 = Телефон и интернет
     * - 5440 = Транспорт и гориво
     * - 5450 = Маркетинг и реклама
     * - 5460 = Сметководствени услуги
     * - 5470 = Правни и консултантски услуги
     * - 5480 = Банкарски провизии
     * - 5610 = Бруто плати
     * - 5800 = Амортизација
     * - 5910 = Камати
     * - 5940 = Застрахување
     */
    protected function classifyExpenseType(string $text): ?array
    {
        $expenseCategories = [
            // Cost of goods (5010)
            ['patterns' => ['стока', 'набавка', 'залиха', 'inventory', 'goods'], 'code' => '5010', 'confidence' => 0.85],
            // Materials (5020)
            ['patterns' => ['материјал', 'суровина', 'material', 'raw'], 'code' => '5020', 'confidence' => 0.85],
            // Office supplies (5210)
            ['patterns' => ['канцелариски', 'office', 'хартија', 'тонер', 'печатач', 'потрепштини'], 'code' => '5210', 'confidence' => 0.85],
            // Rent (5410)
            ['patterns' => ['наем', 'кирија', 'rent', 'закуп', 'простор'], 'code' => '5410', 'confidence' => 0.90],
            // Utilities (5420)
            ['patterns' => ['струја', 'вода', 'греење', 'комунал', 'евн', 'електричн', 'utility', 'electricity', 'heating'], 'code' => '5420', 'confidence' => 0.90],
            // Telecom (5430)
            ['patterns' => ['телефон', 'интернет', 'telecom', 'мобилен', 'фиксен', 'a1', 'makedonski telekom', 'one', 't-mobile'], 'code' => '5430', 'confidence' => 0.90],
            // Transport (5440)
            ['patterns' => ['транспорт', 'гориво', 'бензин', 'дизел', 'такси', 'превоз', 'fuel', 'transport', 'паркинг', 'makpetrol', 'okta'], 'code' => '5440', 'confidence' => 0.85],
            // Marketing (5450)
            ['patterns' => ['маркетинг', 'реклама', 'промоција', 'marketing', 'advertising', 'facebook', 'google ads'], 'code' => '5450', 'confidence' => 0.85],
            // Accounting services (5460)
            ['patterns' => ['сметководство', 'сметководствен', 'accounting', 'bookkeeping'], 'code' => '5460', 'confidence' => 0.90],
            // Legal & consulting (5470)
            ['patterns' => ['адвокат', 'правни', 'консултант', 'consulting', 'нотар', 'legal'], 'code' => '5470', 'confidence' => 0.85],
            // Bank fees (5480)
            ['patterns' => ['провизија', 'банкарск', 'bank fee', 'bank charge', 'комерцијална', 'стопанска', 'халк'], 'code' => '5480', 'confidence' => 0.90],
            // Salaries (5610)
            ['patterns' => ['плата', 'salary', 'wage', 'бруто', 'нето', 'хонорар'], 'code' => '5610', 'confidence' => 0.90],
            // Contributions (5620)
            ['patterns' => ['придонес', 'пио', 'здравствен', 'contribution', 'пензиско'], 'code' => '5620', 'confidence' => 0.90],
            // Travel (5630)
            ['patterns' => ['дневница', 'патни', 'командировка', 'travel', 'патување'], 'code' => '5630', 'confidence' => 0.85],
            // Depreciation (5800)
            ['patterns' => ['амортизација', 'depreciation', 'отпис'], 'code' => '5800', 'confidence' => 0.90],
            // Interest (5910)
            ['patterns' => ['камата', 'interest', 'каматни'], 'code' => '5910', 'confidence' => 0.90],
            // Insurance (5940)
            ['patterns' => ['осигурување', 'insurance', 'полиса', 'premium', 'триглав', 'еуролинк', 'croatia', 'сава'], 'code' => '5940', 'confidence' => 0.90],
            // Representation (5950)
            ['patterns' => ['репрезентација', 'угостителство', 'ресторан', 'кафе', 'entertainment'], 'code' => '5950', 'confidence' => 0.85],
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
     * Uses actual Macedonian Chart of Accounts codes from the seeder (5xxx).
     *
     * @param string $categoryName
     * @param int $companyId
     * @return array|null
     */
    protected function suggestByCategory(string $categoryName, int $companyId): ?array
    {
        $categoryLower = strtolower($categoryName);

        // Category to ACTUAL seeded account code mapping (5xxx series)
        $categoryMap = [
            'office' => '5210',        // Канцелариски материјал
            'канцелариски' => '5210',
            'rent' => '5410',          // Кирија
            'кирија' => '5410',
            'наем' => '5410',
            'закуп' => '5410',
            'utilities' => '5420',     // Комунални услуги
            'комунални' => '5420',
            'струја' => '5420',
            'salaries' => '5610',      // Бруто плати
            'плата' => '5610',
            'плати' => '5610',
            'transport' => '5440',     // Транспорт и гориво
            'транспорт' => '5440',
            'гориво' => '5440',
            'превоз' => '5440',
            'telecom' => '5430',       // Телефон и интернет
            'телефон' => '5430',
            'интернет' => '5430',
            'insurance' => '5940',     // Застрахување
            'осигурување' => '5940',
            'материјал' => '5020',     // Набавка на материјали
            'material' => '5020',
            'услуги' => '5400',        // Трошоци за услуги
            'service' => '5400',
            'консалтинг' => '5470',    // Правни и консултантски услуги
            'consulting' => '5470',
            'банкарски' => '5480',     // Банкарски провизии
            'bank' => '5480',
            'провизија' => '5480',
            'амортизација' => '5800',  // Амортизација
            'depreciation' => '5800',
            'маркетинг' => '5450',     // Маркетинг и реклама
            'реклама' => '5450',
            'сметководство' => '5460', // Сметководствени услуги
            'accounting' => '5460',
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
     * - 2710 = ДДВ за уплата (VAT payable)
     * - 4000 = Приходи од продажба (Revenue)
     *
     * @param string $entityType
     * @param int $companyId
     * @return array|null
     */
    protected function suggestDefault(string $entityType, int $companyId): ?array
    {
        // Default account codes by entity type (using ACTUAL seeded codes)
        $defaults = [
            AccountMapping::ENTITY_CUSTOMER => '4000', // Приходи од продажба (Revenue for customer invoices)
            'customer' => '4000',
            AccountMapping::ENTITY_SUPPLIER => '2210', // Обврски - домашни добавувачи
            'supplier' => '2210',
            AccountMapping::ENTITY_EXPENSE_CATEGORY => '5000', // Набавна вредност на продадена стока
            'expense_category' => '5000',
            'tax' => '2710', // ДДВ за уплата
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
                AccountMapping::ENTITY_CUSTOMER => '4000', // Приходи од продажба
                'customer' => '4000',
                AccountMapping::ENTITY_SUPPLIER => '2200', // Обврски кон добавувачи
                'supplier' => '2200',
                AccountMapping::ENTITY_EXPENSE_CATEGORY => '5400', // Трошоци за услуги
                'expense_category' => '5400',
                'tax' => '2700', // ДДВ обврски
            ];

            $account = Account::where('company_id', $companyId)
                ->where('code', $fallbacks[$entityType] ?? '5000')
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
}
// CLAUDE-CHECKPOINT
