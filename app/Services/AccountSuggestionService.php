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
     * Suggest account based on pattern matching (keywords in name/description).
     *
     * Uses actual Macedonian Chart of Accounts codes from the seeder.
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

        // Pattern keywords mapped to ACTUAL seeded account codes
        // Seeded codes: 1010 (cash), 1020 (bank), 2200-2202 (receivables),
        // 4200-4202 (payables), 4700 (VAT), 6xxx (revenue), 7xxx (expenses)
        $patterns = [
            // Bank and cash patterns
            'банка' => '1020',
            'bank' => '1020',
            'каса' => '1010',
            'cash' => '1010',
            'готовина' => '1010',
            'жиро' => '1020',

            // VAT patterns
            'ддв' => '4700',
            'vat' => '4700',
            'данок' => '4700',
            'tax' => '4700',

            // Salary patterns
            'плата' => '7030',
            'salary' => '7030',
            'wage' => '7030',
            'надоместок' => '7030',

            // Utilities
            'комунални' => '7090',
            'струја' => '7090',
            'electricity' => '7090',
            'вода' => '7090',
            'water' => '7090',
            'греење' => '7090',

            // Rent
            'кирија' => '7080',
            'rent' => '7080',
            'закуп' => '7080',
            'наем' => '7080',

            // Office supplies
            'канцелариски' => '7050',
            'office' => '7050',

            // Services (revenue for customers, expense for suppliers)
            'услуги' => ($entityType === AccountMapping::ENTITY_CUSTOMER ? '6020' : '7020'),
            'service' => ($entityType === AccountMapping::ENTITY_CUSTOMER ? '6020' : '7020'),
            'консалтинг' => ($entityType === AccountMapping::ENTITY_CUSTOMER ? '6020' : '7020'),
            'consulting' => ($entityType === AccountMapping::ENTITY_CUSTOMER ? '6020' : '7020'),

            // Sales/Products
            'продажба' => '6010',
            'sales' => '6010',
            'производ' => '6010',
            'product' => '6010',
            'стоки' => '6030',
            'goods' => '6030',

            // Revenue
            'приходи' => '6000',
            'revenue' => '6000',

            // Materials
            'материјал' => '7010',
            'material' => '7010',

            // Transport
            'транспорт' => '7070',
            'transport' => '7070',
            'гориво' => '7070',
            'fuel' => '7070',

            // Telecom
            'телефон' => '7060',
            'интернет' => '7060',
            'telecom' => '7060',

            // Insurance
            'осигурување' => '7100',
            'insurance' => '7100',

            // Bank fees/financial
            'провизија' => '7800',
            'fee' => '7800',
            'камата' => ($entityType === AccountMapping::ENTITY_CUSTOMER ? '6800' : '7800'),
            'interest' => ($entityType === AccountMapping::ENTITY_CUSTOMER ? '6800' : '7800'),
        ];

        foreach ($patterns as $keyword => $accountCode) {
            if (str_contains($searchText, $keyword)) {
                $account = Account::where('company_id', $companyId)
                    ->where('code', $accountCode)
                    ->where('is_active', true)
                    ->first();

                if ($account) {
                    return [
                        'account_id' => $account->id,
                        'account_code' => $account->code,
                        'account_name' => $account->name,
                        'confidence' => 0.85,
                        'reason' => 'pattern',
                        'alternatives' => [],
                    ];
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
