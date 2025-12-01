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
}
// CLAUDE-CHECKPOINT
