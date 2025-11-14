<?php

namespace App\Domain\Accounting;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Company;
use App\Models\CompanySetting;
use IFRS\Models\Account;
use IFRS\Models\Transaction;
use IFRS\Models\LineItem;
use IFRS\Models\ReportingPeriod;
use IFRS\Models\Entity;
use IFRS\Reports\TrialBalance;
use IFRS\Reports\BalanceSheet;
use IFRS\Reports\IncomeStatement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * IFRS Adapter for Eloquent-IFRS Integration
 *
 * Provides double-entry accounting integration via eloquent-ifrs package.
 * Posts Invoice and Payment transactions to the general ledger when
 * FEATURE_ACCOUNTING_BACKBONE flag is enabled.
 *
 * @package App\Domain\Accounting
 */
class IfrsAdapter
{
    /**
     * Post an Invoice to the general ledger
     * Creates: DR Accounts Receivable, CR Revenue
     *
     * @param Invoice $invoice
     * @return void
     * @throws \Exception
     */
    public function postInvoice(Invoice $invoice): void
    {
        // Skip if feature is disabled
        if (!$this->isEnabled()) {
            return;
        }

        try {
            DB::beginTransaction();

            // Get or create IFRS entity for company
            $entity = $this->getOrCreateEntityForCompany($invoice->company);
            if (!$entity) {
                throw new \Exception("Failed to get or create IFRS Entity for company");
            }

            // Get or create accounts
            $arAccount = $this->getAccountsReceivableAccount($invoice->company_id, $entity->id);
            $revenueAccount = $this->getRevenueAccount($invoice->company_id, $entity->id);

            // Create IFRS Transaction (Client Invoice)
            $transaction = Transaction::create([
                'account_id' => $arAccount->id,
                'transaction_date' => $invoice->invoice_date ?? Carbon::now(),
                'narration' => "Invoice #{$invoice->invoice_number} - {$invoice->customer->name}",
                'transaction_type' => Transaction::CS, // Client Invoice
                'currency_id' => $this->getCurrencyId($invoice->company_id),
                'entity_id' => $entity->id,
            ]);

            // Line Item: Debit Accounts Receivable
            LineItem::create([
                'transaction_id' => $transaction->id,
                'account_id' => $arAccount->id,
                'amount' => $invoice->total / 100, // Convert cents to dollars
                'quantity' => 1,
                'entry_type' => LineItem::DEBIT,
            ]);

            // Line Item: Credit Revenue
            LineItem::create([
                'transaction_id' => $transaction->id,
                'account_id' => $revenueAccount->id,
                'amount' => $invoice->sub_total / 100, // Convert cents to dollars
                'quantity' => 1,
                'entry_type' => LineItem::CREDIT,
            ]);

            // If there's tax, create a line for tax payable
            if ($invoice->tax > 0) {
                $taxPayableAccount = $this->getTaxPayableAccount($invoice->company_id, $entity->id);
                LineItem::create([
                    'transaction_id' => $transaction->id,
                    'account_id' => $taxPayableAccount->id,
                    'amount' => $invoice->tax / 100,
                    'quantity' => 1,
                    'entry_type' => LineItem::CREDIT,
                ]);
            }

            // Post the transaction to the ledger
            $transaction->post();

            // Store the IFRS transaction ID on the invoice for reference
            $invoice->update(['ifrs_transaction_id' => $transaction->id]);

            DB::commit();

            Log::info("Invoice posted to ledger", [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'ifrs_transaction_id' => $transaction->id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to post invoice to ledger", [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Post a Payment to the general ledger
     * Creates: DR Cash, CR Accounts Receivable
     *
     * @param Payment $payment
     * @return void
     * @throws \Exception
     */
    public function postPayment(Payment $payment): void
    {
        // Skip if feature is disabled
        if (!$this->isEnabled()) {
            return;
        }

        try {
            DB::beginTransaction();

            // Get or create IFRS entity for company
            $entity = $this->getOrCreateEntityForCompany($payment->company);
            if (!$entity) {
                throw new \Exception("Failed to get or create IFRS Entity for company");
            }

            // Get accounts
            $cashAccount = $this->getCashAccount($payment->company_id, $entity->id);
            $arAccount = $this->getAccountsReceivableAccount($payment->company_id, $entity->id);

            // Create IFRS Transaction (Client Receipt)
            $transaction = Transaction::create([
                'account_id' => $cashAccount->id,
                'transaction_date' => $payment->payment_date ?? Carbon::now(),
                'narration' => "Payment #{$payment->payment_number} - {$payment->customer->name}",
                'transaction_type' => Transaction::CR, // Client Receipt
                'currency_id' => $this->getCurrencyId($payment->company_id),
                'entity_id' => $entity->id,
            ]);

            // Line Item: Debit Cash
            LineItem::create([
                'transaction_id' => $transaction->id,
                'account_id' => $cashAccount->id,
                'amount' => $payment->amount / 100, // Convert cents to dollars
                'quantity' => 1,
                'entry_type' => LineItem::DEBIT,
            ]);

            // Line Item: Credit Accounts Receivable
            LineItem::create([
                'transaction_id' => $transaction->id,
                'account_id' => $arAccount->id,
                'amount' => $payment->amount / 100,
                'quantity' => 1,
                'entry_type' => LineItem::CREDIT,
            ]);

            // Post the transaction to the ledger
            $transaction->post();

            // Store the IFRS transaction ID on the payment for reference
            $payment->update(['ifrs_transaction_id' => $transaction->id]);

            DB::commit();

            Log::info("Payment posted to ledger", [
                'payment_id' => $payment->id,
                'payment_number' => $payment->payment_number,
                'ifrs_transaction_id' => $transaction->id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to post payment to ledger", [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Post a payment processing fee to the general ledger
     * Creates: DR Fee Expense, CR Cash
     *
     * @param Payment $payment
     * @param float $fee Amount in cents
     * @return void
     * @throws \Exception
     */
    public function postFee(Payment $payment, float $fee): void
    {
        // Skip if feature is disabled
        if (!$this->isEnabled()) {
            return;
        }

        try {
            DB::beginTransaction();

            // Get or create IFRS entity for company
            $entity = $this->getOrCreateEntityForCompany($payment->company);
            if (!$entity) {
                throw new \Exception("Failed to get or create IFRS Entity for company");
            }

            // Get accounts
            $feeExpenseAccount = $this->getFeeExpenseAccount($payment->company_id, $entity->id);
            $cashAccount = $this->getCashAccount($payment->company_id, $entity->id);

            // Create IFRS Transaction (Journal Entry for Fee)
            $transaction = Transaction::create([
                'account_id' => $feeExpenseAccount->id,
                'transaction_date' => $payment->payment_date ?? Carbon::now(),
                'narration' => "Payment processing fee for #{$payment->payment_number}",
                'transaction_type' => Transaction::JN, // Journal Entry
                'currency_id' => $this->getCurrencyId($payment->company_id),
                'entity_id' => $entity->id,
            ]);

            // Line Item: Debit Fee Expense
            LineItem::create([
                'transaction_id' => $transaction->id,
                'account_id' => $feeExpenseAccount->id,
                'amount' => $fee / 100, // Convert cents to dollars
                'quantity' => 1,
                'entry_type' => LineItem::DEBIT,
            ]);

            // Line Item: Credit Cash
            LineItem::create([
                'transaction_id' => $transaction->id,
                'account_id' => $cashAccount->id,
                'amount' => $fee / 100,
                'quantity' => 1,
                'entry_type' => LineItem::CREDIT,
            ]);

            // Post the transaction to the ledger
            $transaction->post();

            DB::commit();

            Log::info("Payment fee posted to ledger", [
                'payment_id' => $payment->id,
                'fee' => $fee,
                'ifrs_transaction_id' => $transaction->id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to post payment fee to ledger", [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Post a Credit Note to the general ledger
     * Reverses the original invoice transaction:
     * - CR 1200 Accounts Receivable (reduce)
     * - DR 4000 Sales Revenue (reduce)
     * - DR 2100 Tax Payable (reduce)
     *
     * @param \App\Models\CreditNote $creditNote
     * @return void
     * @throws \Exception
     */
    public function postCreditNote($creditNote): void
    {
        // Skip if feature is disabled
        if (!$this->isEnabled()) {
            return;
        }

        // Idempotency check: don't re-post if already posted
        if ($creditNote->ifrs_transaction_id) {
            Log::info("Credit note already posted to ledger, skipping", [
                'credit_note_id' => $creditNote->id,
                'ifrs_transaction_id' => $creditNote->ifrs_transaction_id,
            ]);
            return;
        }

        try {
            DB::beginTransaction();

            // Get or create IFRS entity for company
            $entity = $this->getOrCreateEntityForCompany($creditNote->company);
            if (!$entity) {
                throw new \Exception("Failed to get or create IFRS Entity for company");
            }

            // Get or create accounts
            $arAccount = $this->getAccountsReceivableAccount($creditNote->company_id, $entity->id);
            $revenueAccount = $this->getRevenueAccount($creditNote->company_id, $entity->id);

            // Build narration with reference to original invoice
            $narration = "Credit Note #{$creditNote->credit_note_number} - {$creditNote->customer->name}";
            if ($creditNote->invoice) {
                $narration .= " (reverses Invoice #{$creditNote->invoice->invoice_number})";
            }
            if ($creditNote->invoice && $creditNote->invoice->ifrs_transaction_id) {
                $narration .= " [Ref Txn: {$creditNote->invoice->ifrs_transaction_id}]";
            }

            // Create IFRS Transaction (Credit Note as Journal Entry)
            $transaction = Transaction::create([
                'account_id' => $arAccount->id,
                'transaction_date' => $creditNote->credit_note_date ?? Carbon::now(),
                'narration' => $narration,
                'transaction_type' => Transaction::CN, // Credit Note
                'currency_id' => $this->getCurrencyId($creditNote->company_id),
                'entity_id' => $entity->id,
            ]);

            // Line Item: Credit Accounts Receivable (reduce asset)
            LineItem::create([
                'transaction_id' => $transaction->id,
                'account_id' => $arAccount->id,
                'amount' => $creditNote->total / 100, // Convert cents to dollars
                'quantity' => 1,
                'entry_type' => LineItem::CREDIT,
            ]);

            // Line Item: Debit Revenue (reduce revenue)
            LineItem::create([
                'transaction_id' => $transaction->id,
                'account_id' => $revenueAccount->id,
                'amount' => $creditNote->sub_total / 100, // Convert cents to dollars
                'quantity' => 1,
                'entry_type' => LineItem::DEBIT,
            ]);

            // If there's tax, create a line to reduce tax payable
            if ($creditNote->tax > 0) {
                $taxPayableAccount = $this->getTaxPayableAccount($creditNote->company_id, $entity->id);
                LineItem::create([
                    'transaction_id' => $transaction->id,
                    'account_id' => $taxPayableAccount->id,
                    'amount' => $creditNote->tax / 100,
                    'quantity' => 1,
                    'entry_type' => LineItem::DEBIT, // Debit to reduce liability
                ]);
            }

            // Post the transaction to the ledger
            $transaction->post();

            // Store the IFRS transaction ID on the credit note for reference
            $creditNote->update(['ifrs_transaction_id' => $transaction->id]);

            DB::commit();

            Log::info("Credit note posted to ledger", [
                'credit_note_id' => $creditNote->id,
                'credit_note_number' => $creditNote->credit_note_number,
                'ifrs_transaction_id' => $transaction->id,
                'original_invoice_id' => $creditNote->invoice_id,
                'original_invoice_txn_id' => $creditNote->invoice?->ifrs_transaction_id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to post credit note to ledger", [
                'credit_note_id' => $creditNote->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get Trial Balance for a company as of a specific date
     *
     * @param Company $company
     * @param string|null $asOfDate Date in Y-m-d format
     * @return array
     */
    public function getTrialBalance(Company $company, ?string $asOfDate = null): array
    {
        if (!$this->isEnabled()) {
            return ['error' => 'Accounting backbone feature is disabled'];
        }

        try {
            $date = $asOfDate ? Carbon::parse($asOfDate) : Carbon::now();

            // Get or create IFRS entity for company
            $entity = $this->getOrCreateEntityForCompany($company);
            if (!$entity) {
                return [
                    'error' => 'IFRS Entity not available',
                    'message' => 'Failed to get or create IFRS Entity for this company.',
                    'status' => 'entity_error',
                ];
            }

            // Check if any accounts exist - if not, accounting system not initialized
            $accountCount = Account::where('entity_id', $entity->id)->count();
            if ($accountCount === 0) {
                return [
                    'error' => 'Accounting system not initialized',
                    'message' => 'No chart of accounts found. The accounting backbone feature is enabled but no accounting data exists yet. Create invoices or payments to generate accounting transactions.',
                    'status' => 'not_initialized',
                ];
            }

            // TrialBalance expects year string and entity
            $trialBalance = new TrialBalance((string)$date->year, $entity);
            $sections = $trialBalance->getSections();

            // TrialBalance accumulates debits/credits in $balances property, not via methods
            $totalDebits = $trialBalance->balances['debit'] ?? 0;
            $totalCredits = $trialBalance->balances['credit'] ?? 0;

            return [
                'date' => $date->toDateString(),
                'year' => $date->year,
                'sections' => $sections,
                'accounts' => $sections['accounts'] ?? [],
                'balances' => $sections['results'] ?? [],
                'total_debits' => $totalDebits,
                'total_credits' => $totalCredits,
                'is_balanced' => $totalDebits === $totalCredits,
            ];
        } catch (\Exception $e) {
            Log::error("Failed to generate trial balance", [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Return friendly error for common issues
            if (str_contains($e->getMessage(), 'Attempt to read property')) {
                return [
                    'error' => 'Accounting system not properly configured',
                    'message' => 'The IFRS accounting system requires proper Entity and ReportingPeriod setup. This feature is under development.',
                    'status' => 'configuration_error',
                ];
            }

            return ['error' => $e->getMessage()];
        }
    }

    // CLAUDE-CHECKPOINT: Fixed TrialBalance to use $balances property instead of non-existent methods

    /**
     * Get Balance Sheet for a company as of a specific date
     *
     * @param Company $company
     * @param string|null $asOfDate Date in Y-m-d format
     * @return array
     */
    public function getBalanceSheet(Company $company, ?string $asOfDate = null): array
    {
        if (!$this->isEnabled()) {
            return ['error' => 'Accounting backbone feature is disabled'];
        }

        try {
            $date = $asOfDate ? Carbon::parse($asOfDate) : Carbon::now();

            // Get or create IFRS entity for company
            $entity = $this->getOrCreateEntityForCompany($company);
            if (!$entity) {
                return [
                    'error' => 'IFRS Entity not available',
                    'message' => 'Failed to get or create IFRS Entity for this company.',
                    'status' => 'entity_error',
                ];
            }

            // Check if any accounts exist - if not, accounting system not initialized
            $accountCount = Account::where('entity_id', $entity->id)->count();
            if ($accountCount === 0) {
                return [
                    'error' => 'Accounting system not initialized',
                    'message' => 'No chart of accounts found. The accounting backbone feature is enabled but no accounting data exists yet. Create invoices or payments to generate accounting transactions.',
                    'status' => 'not_initialized',
                ];
            }

            // BalanceSheet expects endDate and entity
            $balanceSheet = new BalanceSheet($date->toDateString(), $entity);
            $sections = $balanceSheet->getSections();

            return [
                'date' => $date->toDateString(),
                'sections' => $sections,
                'assets' => $sections['accounts']['ASSETS'] ?? [],
                'liabilities' => $sections['accounts']['LIABILITIES'] ?? [],
                'equity' => $sections['accounts']['EQUITY'] ?? [],
                'totals' => $sections['totals'] ?? [],
                'results' => $sections['results'] ?? [],
            ];
        } catch (\Exception $e) {
            Log::error("Failed to generate balance sheet", [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Return friendly error for common issues
            if (str_contains($e->getMessage(), 'Attempt to read property')) {
                return [
                    'error' => 'Accounting system not properly configured',
                    'message' => 'The IFRS accounting system requires proper Entity and ReportingPeriod setup. This feature is under development.',
                    'status' => 'configuration_error',
                ];
            }

            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get Income Statement for a company for a date range
     *
     * @param Company $company
     * @param string $startDate Date in Y-m-d format
     * @param string $endDate Date in Y-m-d format
     * @return array
     */
    public function getIncomeStatement(Company $company, string $startDate, string $endDate): array
    {
        if (!$this->isEnabled()) {
            return ['error' => 'Accounting backbone feature is disabled'];
        }

        try {
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);

            // Get or create IFRS entity for company
            $entity = $this->getOrCreateEntityForCompany($company);
            if (!$entity) {
                return [
                    'error' => 'IFRS Entity not available',
                    'message' => 'Failed to get or create IFRS Entity for this company.',
                    'status' => 'entity_error',
                ];
            }

            // Check if any accounts exist - if not, accounting system not initialized
            $accountCount = Account::where('entity_id', $entity->id)->count();
            if ($accountCount === 0) {
                return [
                    'error' => 'Accounting system not initialized',
                    'message' => 'No chart of accounts found. The accounting backbone feature is enabled but no accounting data exists yet. Create invoices or payments to generate accounting transactions.',
                    'status' => 'not_initialized',
                ];
            }

            // IncomeStatement expects startDate, endDate, and entity
            $incomeStatement = new IncomeStatement($start->toDateString(), $end->toDateString(), $entity);
            $sections = $incomeStatement->getSections();

            return [
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'sections' => $sections,
                'revenues' => array_merge(
                    $sections['accounts']['OPERATING_REVENUES'] ?? [],
                    $sections['accounts']['NON_OPERATING_REVENUES'] ?? []
                ),
                'expenses' => array_merge(
                    $sections['accounts']['OPERATING_EXPENSES'] ?? [],
                    $sections['accounts']['NON_OPERATING_EXPENSES'] ?? []
                ),
                'results' => $sections['results'] ?? [],
                'totals' => $sections['totals'] ?? [],
            ];
        } catch (\Exception $e) {
            Log::error("Failed to generate income statement", [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Return friendly error for common issues
            if (str_contains($e->getMessage(), 'Attempt to read property')) {
                return [
                    'error' => 'Accounting system not properly configured',
                    'message' => 'The IFRS accounting system requires proper Entity and ReportingPeriod setup. This feature is under development.',
                    'status' => 'configuration_error',
                ];
            }

            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Check if accounting backbone feature is enabled
     *
     * @return bool
     */
    protected function isEnabled(): bool
    {
        return config('ifrs.enabled', false) ||
               (function_exists('feature') && feature('accounting_backbone'));
    }

    /**
     * Get or create IFRS Entity for a company
     *
     * @param Company $company
     * @return Entity|null
     */
    protected function getOrCreateEntityForCompany(Company $company): ?Entity
    {
        // If company already has an IFRS entity, return it
        if ($company->ifrs_entity_id) {
            $entity = Entity::find($company->ifrs_entity_id);
            if ($entity) {
                return $entity;
            }
        }

        // Create new IFRS Entity for this company
        try {
            $entity = Entity::create([
                'name' => $company->name,
                'currency_id' => $this->getCurrencyId($company->id),
                'year_start' => 1, // January
                'multi_currency' => false,
            ]);

            // Link entity to company
            $company->update(['ifrs_entity_id' => $entity->id]);

            Log::info("Created IFRS Entity for company", [
                'company_id' => $company->id,
                'entity_id' => $entity->id,
            ]);

            return $entity;
        } catch (\Exception $e) {
            Log::error("Failed to create IFRS Entity for company", [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get or create Accounts Receivable account
     *
     * @param int $companyId
     * @param int $entityId
     * @return Account
     */
    protected function getAccountsReceivableAccount(int $companyId, int $entityId): Account
    {
        return Account::firstOrCreate(
            [
                'account_type' => Account::RECEIVABLE,
                'category_id' => null,
                'name' => 'Accounts Receivable',
                'entity_id' => $entityId,
            ],
            [
                'code' => '1200',
                'currency_id' => $this->getCurrencyId($companyId),
            ]
        );
    }

    /**
     * Get or create Revenue account
     *
     * @param int $companyId
     * @param int $entityId
     * @return Account
     */
    protected function getRevenueAccount(int $companyId, int $entityId): Account
    {
        return Account::firstOrCreate(
            [
                'account_type' => Account::OPERATING_REVENUE,
                'category_id' => null,
                'name' => 'Sales Revenue',
                'entity_id' => $entityId,
            ],
            [
                'code' => '4000',
                'currency_id' => $this->getCurrencyId($companyId),
            ]
        );
    }

    /**
     * Get or create Cash account
     *
     * @param int $companyId
     * @param int $entityId
     * @return Account
     */
    protected function getCashAccount(int $companyId, int $entityId): Account
    {
        return Account::firstOrCreate(
            [
                'account_type' => Account::BANK,
                'category_id' => null,
                'name' => 'Cash and Bank',
                'entity_id' => $entityId,
            ],
            [
                'code' => '1000',
                'currency_id' => $this->getCurrencyId($companyId),
            ]
        );
    }

    /**
     * Get or create Tax Payable account
     *
     * @param int $companyId
     * @param int $entityId
     * @return Account
     */
    protected function getTaxPayableAccount(int $companyId, int $entityId): Account
    {
        return Account::firstOrCreate(
            [
                'account_type' => Account::CONTROL,
                'category_id' => null,
                'name' => 'Tax Payable',
                'entity_id' => $entityId,
            ],
            [
                'code' => '2100',
                'currency_id' => $this->getCurrencyId($companyId),
            ]
        );
    }

    /**
     * Get or create Fee Expense account
     *
     * @param int $companyId
     * @param int $entityId
     * @return Account
     */
    protected function getFeeExpenseAccount(int $companyId, int $entityId): Account
    {
        return Account::firstOrCreate(
            [
                'account_type' => Account::OPERATING_EXPENSE,
                'category_id' => null,
                'name' => 'Payment Processing Fees',
                'entity_id' => $entityId,
            ],
            [
                'code' => '5100',
                'currency_id' => $this->getCurrencyId($companyId),
            ]
        );
    }

    /**
     * Post an Expense to the general ledger
     * Creates: DR Expense Account (5XXX based on category), CR Cash/Bank
     *
     * @param \App\Models\Expense $expense
     * @return void
     * @throws \Exception
     */
    public function postExpense($expense): void
    {
        // Skip if feature is disabled
        if (!$this->isEnabled()) {
            return;
        }

        // Idempotency check: don't re-post if already posted
        if ($expense->ifrs_transaction_id) {
            Log::info("Expense already posted to ledger, skipping", [
                'expense_id' => $expense->id,
                'ifrs_transaction_id' => $expense->ifrs_transaction_id,
            ]);
            return;
        }

        try {
            DB::beginTransaction();

            // Get or create IFRS entity for company
            $entity = $this->getOrCreateEntityForCompany($expense->company);
            if (!$entity) {
                throw new \Exception("Failed to get or create IFRS Entity for company");
            }

            // EntityGuard check
            \App\Domain\Guards\EntityGuard::ensureEntityExists($expense->company);

            // Get or create accounts
            $expenseAccount = $this->getExpenseAccount($expense->company_id, $entity->id, $expense->category);
            $cashAccount = $this->getCashAccount($expense->company_id, $entity->id);

            // Build narration
            $categoryName = $expense->category ? $expense->category->name : 'General Expense';
            $narration = "Expense: {$categoryName}";
            if ($expense->notes) {
                $narration .= " - " . substr($expense->notes, 0, 100);
            }

            // Create IFRS Transaction (Cash Purchase)
            $transaction = Transaction::create([
                'account_id' => $expenseAccount->id,
                'transaction_date' => $expense->expense_date ?? Carbon::now(),
                'narration' => $narration,
                'transaction_type' => Transaction::CP, // Cash Purchase
                'currency_id' => $this->getCurrencyId($expense->company_id),
                'entity_id' => $entity->id,
            ]);

            // Line Item: Debit Expense Account
            LineItem::create([
                'transaction_id' => $transaction->id,
                'account_id' => $expenseAccount->id,
                'amount' => $expense->amount / 100, // Convert cents to dollars
                'quantity' => 1,
                'entry_type' => LineItem::DEBIT,
            ]);

            // Line Item: Credit Cash/Bank
            LineItem::create([
                'transaction_id' => $transaction->id,
                'account_id' => $cashAccount->id,
                'amount' => $expense->amount / 100,
                'quantity' => 1,
                'entry_type' => LineItem::CREDIT,
            ]);

            // Post the transaction to the ledger
            $transaction->post();

            // Store the IFRS transaction ID on the expense for reference
            $expense->ifrs_transaction_id = $transaction->id;
            $expense->saveQuietly();

            DB::commit();

            Log::info("Expense posted to ledger", [
                'expense_id' => $expense->id,
                'category' => $categoryName,
                'ifrs_transaction_id' => $transaction->id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to post expense to ledger", [
                'expense_id' => $expense->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get currency ID for company (defaults to first currency)
     *
     * @param int $companyId
     * @return int
     */
    protected function getCurrencyId(int $companyId): int
    {
        // Get the company's currency from settings
        $appCurrencyId = CompanySetting::getSetting('currency', $companyId);

        if ($appCurrencyId) {
            // Get the app currency
            $appCurrency = \App\Models\Currency::find($appCurrencyId);

            if ($appCurrency) {
                // Find or create corresponding IFRS currency
                $ifrsCurrency = \IFRS\Models\Currency::where('currency_code', $appCurrency->code)->first();

                if (!$ifrsCurrency) {
                    $ifrsCurrency = \IFRS\Models\Currency::create([
                        'name' => $appCurrency->name,
                        'currency_code' => $appCurrency->code,
                    ]);
                }

                return $ifrsCurrency->id;
            }
        }

        // Fallback: Return first IFRS currency or create default MKD
        $currency = \IFRS\Models\Currency::first();

        if (!$currency) {
            $currency = \IFRS\Models\Currency::create([
                'name' => 'Macedonian Denar',
                'currency_code' => 'MKD',
            ]);
        }

        return $currency->id;
    }

    /**
     * Get or create Expense account based on category
     *
     * @param int $companyId
     * @param int $entityId
     * @param \App\Models\ExpenseCategory|null $category
     * @return Account
     */
    protected function getExpenseAccount(int $companyId, int $entityId, $category = null): Account
    {
        // Use category name if available, otherwise default
        $categoryName = $category ? $category->name : 'General Expenses';

        // Map to appropriate expense account code based on category
        // Default to 5000 range (Operating Expenses)
        $accountCode = $this->mapCategoryToAccountCode($categoryName);

        return Account::firstOrCreate(
            [
                'account_type' => Account::OPERATING_EXPENSE,
                'category_id' => null,
                'name' => $categoryName,
                'entity_id' => $entityId,
            ],
            [
                'code' => $accountCode,
                'currency_id' => $this->getCurrencyId($companyId),
            ]
        );
    }

    /**
     * Map expense category to appropriate chart of accounts code
     *
     * @param string $categoryName
     * @return string
     */
    protected function mapCategoryToAccountCode(string $categoryName): string
    {
        // Basic mapping - can be extended based on business needs
        $categoryLower = strtolower($categoryName);

        if (str_contains($categoryLower, 'salary') || str_contains($categoryLower, 'wage')) {
            return '5200'; // Salaries and Wages
        }

        if (str_contains($categoryLower, 'rent')) {
            return '5300'; // Rent Expense
        }

        if (str_contains($categoryLower, 'utility') || str_contains($categoryLower, 'utilities')) {
            return '5400'; // Utilities
        }

        if (str_contains($categoryLower, 'marketing') || str_contains($categoryLower, 'advertising')) {
            return '5500'; // Marketing and Advertising
        }

        if (str_contains($categoryLower, 'travel')) {
            return '5600'; // Travel Expenses
        }

        if (str_contains($categoryLower, 'office') || str_contains($categoryLower, 'supplies')) {
            return '5700'; // Office Supplies
        }

        if (str_contains($categoryLower, 'insurance')) {
            return '5800'; // Insurance
        }

        if (str_contains($categoryLower, 'legal') || str_contains($categoryLower, 'professional')) {
            return '5900'; // Professional Fees
        }

        // Default general expense
        return '5000';
    }

    /**
     * Post a Bill to the general ledger
     * Creates: DR Expense Account (5XXX based on bill items), CR Accounts Payable, DR VAT Receivable
     *
     * @param \App\Models\Bill $bill
     * @return void
     * @throws \Exception
     */
    public function postBill($bill): void
    {
        // Skip if feature is disabled
        if (!$this->isEnabled()) {
            return;
        }

        // Idempotency check: don't re-post if already posted
        if ($bill->ifrs_transaction_id) {
            Log::info("Bill already posted to ledger, skipping", [
                'bill_id' => $bill->id,
                'ifrs_transaction_id' => $bill->ifrs_transaction_id,
            ]);
            return;
        }

        try {
            DB::beginTransaction();

            // Get or create IFRS entity for company
            $entity = $this->getOrCreateEntityForCompany($bill->company);
            if (!$entity) {
                throw new \Exception("Failed to get or create IFRS Entity for company");
            }

            // Get or create accounts
            $apAccount = $this->getAccountsPayableAccount($bill->company_id, $entity->id);
            $expenseAccount = $this->getExpenseAccount($bill->company_id, $entity->id);

            // Build narration
            $narration = "Bill #{$bill->bill_number} - {$bill->supplier->name}";

            // Create IFRS Transaction (Supplier Bill)
            $transaction = Transaction::create([
                'account_id' => $expenseAccount->id,
                'transaction_date' => $bill->bill_date ?? Carbon::now(),
                'narration' => $narration,
                'transaction_type' => Transaction::BL, // Supplier Bill
                'currency_id' => $this->getCurrencyId($bill->company_id),
                'entity_id' => $entity->id,
            ]);

            // Line Item: Debit Expense Account
            LineItem::create([
                'transaction_id' => $transaction->id,
                'account_id' => $expenseAccount->id,
                'amount' => $bill->sub_total / 100, // Convert cents to dollars
                'quantity' => 1,
                'entry_type' => LineItem::DEBIT,
            ]);

            // If there's input VAT, debit VAT Receivable (input VAT is an asset)
            if ($bill->tax > 0) {
                $vatReceivableAccount = $this->getVatReceivableAccount($bill->company_id, $entity->id);
                LineItem::create([
                    'transaction_id' => $transaction->id,
                    'account_id' => $vatReceivableAccount->id,
                    'amount' => $bill->tax / 100,
                    'quantity' => 1,
                    'entry_type' => LineItem::DEBIT,
                ]);
            }

            // Line Item: Credit Accounts Payable
            LineItem::create([
                'transaction_id' => $transaction->id,
                'account_id' => $apAccount->id,
                'amount' => $bill->total / 100, // Total includes tax
                'quantity' => 1,
                'entry_type' => LineItem::CREDIT,
            ]);

            // Post the transaction to the ledger
            $transaction->post();

            // Store the IFRS transaction ID on the bill for reference
            $bill->update(['ifrs_transaction_id' => $transaction->id, 'posted_to_ifrs' => true]);

            DB::commit();

            Log::info("Bill posted to ledger", [
                'bill_id' => $bill->id,
                'bill_number' => $bill->bill_number,
                'ifrs_transaction_id' => $transaction->id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to post bill to ledger", [
                'bill_id' => $bill->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Post a BillPayment to the general ledger
     * Creates: DR Accounts Payable, CR Cash and Bank
     *
     * @param \App\Models\BillPayment $billPayment
     * @return void
     * @throws \Exception
     */
    public function postBillPayment($billPayment): void
    {
        // Skip if feature is disabled
        if (!$this->isEnabled()) {
            return;
        }

        // Idempotency check: don't re-post if already posted
        if ($billPayment->ifrs_transaction_id) {
            Log::info("Bill payment already posted to ledger, skipping", [
                'bill_payment_id' => $billPayment->id,
                'ifrs_transaction_id' => $billPayment->ifrs_transaction_id,
            ]);
            return;
        }

        try {
            DB::beginTransaction();

            // Get or create IFRS entity for company
            $entity = $this->getOrCreateEntityForCompany($billPayment->company);
            if (!$entity) {
                throw new \Exception("Failed to get or create IFRS Entity for company");
            }

            // Get accounts
            $cashAccount = $this->getCashAccount($billPayment->company_id, $entity->id);
            $apAccount = $this->getAccountsPayableAccount($billPayment->company_id, $entity->id);

            // Build narration
            $supplierName = $billPayment->bill && $billPayment->bill->supplier
                ? $billPayment->bill->supplier->name
                : 'Supplier';
            $narration = "Bill Payment #{$billPayment->payment_number} - {$supplierName}";

            // Create IFRS Transaction (Supplier Payment)
            $transaction = Transaction::create([
                'account_id' => $apAccount->id,
                'transaction_date' => $billPayment->payment_date ?? Carbon::now(),
                'narration' => $narration,
                'transaction_type' => Transaction::PY, // Supplier Payment
                'currency_id' => $this->getCurrencyId($billPayment->company_id),
                'entity_id' => $entity->id,
            ]);

            // Line Item: Debit Accounts Payable (reduce liability)
            LineItem::create([
                'transaction_id' => $transaction->id,
                'account_id' => $apAccount->id,
                'amount' => $billPayment->amount / 100, // Convert cents to dollars
                'quantity' => 1,
                'entry_type' => LineItem::DEBIT,
            ]);

            // Line Item: Credit Cash and Bank (reduce asset)
            LineItem::create([
                'transaction_id' => $transaction->id,
                'account_id' => $cashAccount->id,
                'amount' => $billPayment->amount / 100,
                'quantity' => 1,
                'entry_type' => LineItem::CREDIT,
            ]);

            // Post the transaction to the ledger
            $transaction->post();

            // Store the IFRS transaction ID on the bill payment for reference
            $billPayment->update(['ifrs_transaction_id' => $transaction->id, 'posted_to_ifrs' => true]);

            DB::commit();

            Log::info("Bill payment posted to ledger", [
                'bill_payment_id' => $billPayment->id,
                'payment_number' => $billPayment->payment_number,
                'ifrs_transaction_id' => $transaction->id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to post bill payment to ledger", [
                'bill_payment_id' => $billPayment->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get or create Accounts Payable account
     *
     * @param int $companyId
     * @param int $entityId
     * @return Account
     */
    protected function getAccountsPayableAccount(int $companyId, int $entityId): Account
    {
        return Account::firstOrCreate(
            [
                'account_type' => Account::PAYABLE,
                'category_id' => null,
                'name' => 'Accounts Payable',
                'entity_id' => $entityId,
            ],
            [
                'code' => '2000',
                'currency_id' => $this->getCurrencyId($companyId),
            ]
        );
    }

    /**
     * Get or create VAT Receivable account (for input VAT on purchases)
     *
     * @param int $companyId
     * @param int $entityId
     * @return Account
     */
    protected function getVatReceivableAccount(int $companyId, int $entityId): Account
    {
        return Account::firstOrCreate(
            [
                'account_type' => Account::CURRENT_ASSET,
                'category_id' => null,
                'name' => 'VAT Receivable',
                'entity_id' => $entityId,
            ],
            [
                'code' => '1100',
                'currency_id' => $this->getCurrencyId($companyId),
            ]
        );
    }
}

// CLAUDE-CHECKPOINT: Added postBill() and postBillPayment() methods for accounts payable accounting
