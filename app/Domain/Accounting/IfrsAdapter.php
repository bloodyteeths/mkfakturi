<?php

namespace App\Domain\Accounting;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Company;
use IFRS\Models\Account;
use IFRS\Models\Transaction;
use IFRS\Models\LineItem;
use IFRS\Models\ReportingPeriod;
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

            // Get or create accounts
            $arAccount = $this->getAccountsReceivableAccount($invoice->company_id);
            $revenueAccount = $this->getRevenueAccount($invoice->company_id);

            // Create IFRS Transaction (Client Invoice)
            $transaction = Transaction::create([
                'account_id' => $arAccount->id,
                'transaction_date' => $invoice->invoice_date ?? Carbon::now(),
                'narration' => "Invoice #{$invoice->invoice_number} - {$invoice->customer->name}",
                'transaction_type' => Transaction::CS, // Client Invoice
                'currency_id' => $this->getCurrencyId($invoice->company_id),
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
                $taxPayableAccount = $this->getTaxPayableAccount($invoice->company_id);
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

            // Get accounts
            $cashAccount = $this->getCashAccount($payment->company_id);
            $arAccount = $this->getAccountsReceivableAccount($payment->company_id);

            // Create IFRS Transaction (Client Receipt)
            $transaction = Transaction::create([
                'account_id' => $cashAccount->id,
                'transaction_date' => $payment->payment_date ?? Carbon::now(),
                'narration' => "Payment #{$payment->payment_number} - {$payment->customer->name}",
                'transaction_type' => Transaction::CR, // Client Receipt
                'currency_id' => $this->getCurrencyId($payment->company_id),
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

            // Get accounts
            $feeExpenseAccount = $this->getFeeExpenseAccount($payment->company_id);
            $cashAccount = $this->getCashAccount($payment->company_id);

            // Create IFRS Transaction (Journal Entry for Fee)
            $transaction = Transaction::create([
                'account_id' => $feeExpenseAccount->id,
                'transaction_date' => $payment->payment_date ?? Carbon::now(),
                'narration' => "Payment processing fee for #{$payment->payment_number}",
                'transaction_type' => Transaction::JN, // Journal Entry
                'currency_id' => $this->getCurrencyId($payment->company_id),
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

            // TrialBalance expects year string and entity (passing null for now - multi-tenancy needs proper Entity setup)
            $trialBalance = new TrialBalance((string)$date->year, null);

            return [
                'date' => $date->toDateString(),
                'year' => $date->year,
                'accounts' => $trialBalance->accounts,
                'balances' => $trialBalance->balances,
                'total_debits' => $trialBalance->totalDebits(),
                'total_credits' => $trialBalance->totalCredits(),
                'is_balanced' => $trialBalance->totalDebits() === $trialBalance->totalCredits(),
                'note' => 'Multi-company support requires IFRS Entity setup - currently showing all companies',
            ];
        } catch (\Exception $e) {
            Log::error("Failed to generate trial balance", [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return ['error' => $e->getMessage()];
        }
    }

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

            // BalanceSheet expects endDate and entity (passing null for entity - multi-tenancy needs proper setup)
            $balanceSheet = new BalanceSheet($date->toDateString(), null);

            return [
                'date' => $date->toDateString(),
                'assets' => $balanceSheet->assets,
                'liabilities' => $balanceSheet->liabilities,
                'equity' => $balanceSheet->equity,
                'total_assets' => $balanceSheet->totalAssets(),
                'total_liabilities' => $balanceSheet->totalLiabilities(),
                'total_equity' => $balanceSheet->totalEquity(),
                'note' => 'Multi-company support requires IFRS Entity setup - currently showing all companies',
            ];
        } catch (\Exception $e) {
            Log::error("Failed to generate balance sheet", [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
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

            // IncomeStatement expects startDate, endDate, and entity (passing null for entity - multi-tenancy needs proper setup)
            $incomeStatement = new IncomeStatement($start->toDateString(), $end->toDateString(), null);

            return [
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'revenues' => $incomeStatement->revenues,
                'expenses' => $incomeStatement->expenses,
                'total_revenue' => $incomeStatement->totalRevenue(),
                'total_expenses' => $incomeStatement->totalExpenses(),
                'net_income' => $incomeStatement->netIncome(),
                'note' => 'Multi-company support requires IFRS Entity setup - currently showing all companies',
            ];
        } catch (\Exception $e) {
            Log::error("Failed to generate income statement", [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
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
     * Get or create Accounts Receivable account
     *
     * @param int $companyId
     * @return Account
     */
    protected function getAccountsReceivableAccount(int $companyId): Account
    {
        return Account::firstOrCreate(
            [
                'account_type' => Account::RECEIVABLE,
                'category_id' => null,
                'name' => 'Accounts Receivable',
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
     * @return Account
     */
    protected function getRevenueAccount(int $companyId): Account
    {
        return Account::firstOrCreate(
            [
                'account_type' => Account::OPERATING_REVENUE,
                'category_id' => null,
                'name' => 'Sales Revenue',
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
     * @return Account
     */
    protected function getCashAccount(int $companyId): Account
    {
        return Account::firstOrCreate(
            [
                'account_type' => Account::BANK,
                'category_id' => null,
                'name' => 'Cash and Bank',
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
     * @return Account
     */
    protected function getTaxPayableAccount(int $companyId): Account
    {
        return Account::firstOrCreate(
            [
                'account_type' => Account::CONTROL,
                'category_id' => null,
                'name' => 'Tax Payable',
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
     * @return Account
     */
    protected function getFeeExpenseAccount(int $companyId): Account
    {
        return Account::firstOrCreate(
            [
                'account_type' => Account::OPERATING_EXPENSE,
                'category_id' => null,
                'name' => 'Payment Processing Fees',
            ],
            [
                'code' => '5100',
                'currency_id' => $this->getCurrencyId($companyId),
            ]
        );
    }

    /**
     * Get currency ID for company (defaults to first currency)
     *
     * @param int $companyId
     * @return int
     */
    protected function getCurrencyId(int $companyId): int
    {
        // Get the company's currency or default to first available
        $company = Company::find($companyId);

        if ($company && $company->currency_id) {
            return $company->currency_id;
        }

        // Return first IFRS currency or create default MKD
        $currency = \IFRS\Models\Currency::first();

        if (!$currency) {
            $currency = \IFRS\Models\Currency::create([
                'name' => 'Macedonian Denar',
                'currency_code' => 'MKD',
            ]);
        }

        return $currency->id;
    }
}

// CLAUDE-CHECKPOINT
