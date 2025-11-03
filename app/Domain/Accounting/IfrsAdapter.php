<?php

namespace App\Domain\Accounting;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\Payment;
use Carbon\Carbon;
use IFRS\Models\Account;
use IFRS\Models\Currency;
use IFRS\Models\LineItem;
use IFRS\Models\ReportingPeriod;
use IFRS\Models\Transaction;
use IFRS\Reports\BalanceSheet;
use IFRS\Reports\IncomeStatement;
use IFRS\Reports\TrialBalance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * IFRS Adapter for Double-Entry Accounting
 *
 * This adapter provides a clean interface between Fakturino's invoice/payment
 * system and the eloquent-ifrs double-entry accounting package.
 *
 * Double-entry posting rules:
 * - Invoice: DR Accounts Receivable | CR Revenue
 * - Payment: DR Cash/Bank | CR Accounts Receivable
 * - Payment Fee: DR Fee Expense | CR Cash/Bank
 *
 * @package App\Domain\Accounting
 */
class IfrsAdapter
{
    /**
     * Account code constants (Macedonian COA)
     */
    public const ACCOUNT_CASH = 300; // Bank/Cash account
    public const ACCOUNT_ACCOUNTS_RECEIVABLE = 500; // A/R account
    public const ACCOUNT_REVENUE = 4000; // Operating revenue
    public const ACCOUNT_FEE_EXPENSE = 5100; // Fee expense

    /**
     * Post an invoice to the general ledger
     *
     * Creates a journal entry:
     * DR Accounts Receivable (Asset)
     * CR Revenue (Revenue)
     *
     * @param Invoice $invoice The invoice to post
     * @return void
     * @throws \Exception If posting fails
     */
    public function postInvoice(Invoice $invoice): void
    {
        try {
            DB::beginTransaction();

            // Get or create the entity for this company
            $entity = $this->getOrCreateEntity($invoice->company);

            // Get or create currency
            $currency = $this->getOrCreateCurrency($invoice->currency->code ?? 'MKD');

            // Get reporting period (current year)
            $reportingPeriod = $this->getOrCreateReportingPeriod($entity, $invoice->invoice_date);

            // Get accounts
            $arAccount = $this->getOrCreateAccount($entity, self::ACCOUNT_ACCOUNTS_RECEIVABLE, 'Accounts Receivable', Account::RECEIVABLE);
            $revenueAccount = $this->getOrCreateAccount($entity, self::ACCOUNT_REVENUE, 'Sales Revenue', Account::OPERATING_REVENUE);

            // Create transaction
            $transaction = new Transaction();
            $transaction->account_id = $arAccount->id;
            $transaction->transaction_date = Carbon::parse($invoice->invoice_date);
            $transaction->narration = "Invoice {$invoice->invoice_number}";
            $transaction->transaction_type = Transaction::IN; // Client Invoice
            $transaction->entity_id = $entity->id;
            $transaction->currency_id = $currency->id;
            $transaction->credited = false;
            $transaction->save();

            // Create line item for Accounts Receivable (Debit)
            $lineItem1 = new LineItem();
            $lineItem1->account_id = $arAccount->id;
            $lineItem1->transaction_id = $transaction->id;
            $lineItem1->amount = $invoice->total / 100; // Convert from cents
            $lineItem1->entity_id = $entity->id;
            $lineItem1->description = "Invoice {$invoice->invoice_number}";
            $lineItem1->entry_type = 'debit';
            $lineItem1->save();

            // Create line item for Revenue (Credit)
            $lineItem2 = new LineItem();
            $lineItem2->account_id = $revenueAccount->id;
            $lineItem2->transaction_id = $transaction->id;
            $lineItem2->amount = $invoice->total / 100; // Convert from cents
            $lineItem2->entity_id = $entity->id;
            $lineItem2->description = "Invoice {$invoice->invoice_number}";
            $lineItem2->entry_type = 'credit';
            $lineItem2->save();

            // Post the transaction
            $transaction->post();

            DB::commit();

            Log::info('Invoice posted to ledger', [
                'invoice_id' => $invoice->id,
                'transaction_id' => $transaction->id,
                'amount' => $invoice->total / 100,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to post invoice to ledger', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Post a payment to the general ledger
     *
     * Creates a journal entry:
     * DR Cash/Bank (Asset)
     * CR Accounts Receivable (Asset)
     *
     * @param Payment $payment The payment to post
     * @return void
     * @throws \Exception If posting fails
     */
    public function postPayment(Payment $payment): void
    {
        try {
            DB::beginTransaction();

            // Get or create the entity for this company
            $entity = $this->getOrCreateEntity($payment->company);

            // Get or create currency
            $currency = $this->getOrCreateCurrency($payment->currency->code ?? 'MKD');

            // Get reporting period
            $reportingPeriod = $this->getOrCreateReportingPeriod($entity, $payment->payment_date);

            // Get accounts
            $cashAccount = $this->getOrCreateAccount($entity, self::ACCOUNT_CASH, 'Cash/Bank', Account::BANK);
            $arAccount = $this->getOrCreateAccount($entity, self::ACCOUNT_ACCOUNTS_RECEIVABLE, 'Accounts Receivable', Account::RECEIVABLE);

            // Create transaction
            $transaction = new Transaction();
            $transaction->account_id = $cashAccount->id;
            $transaction->transaction_date = Carbon::parse($payment->payment_date);
            $transaction->narration = "Payment {$payment->payment_number}";
            $transaction->transaction_type = Transaction::RC; // Client Receipt
            $transaction->entity_id = $entity->id;
            $transaction->currency_id = $currency->id;
            $transaction->credited = false;
            $transaction->save();

            // Create line item for Cash (Debit)
            $lineItem1 = new LineItem();
            $lineItem1->account_id = $cashAccount->id;
            $lineItem1->transaction_id = $transaction->id;
            $lineItem1->amount = $payment->amount / 100; // Convert from cents
            $lineItem1->entity_id = $entity->id;
            $lineItem1->description = "Payment {$payment->payment_number}";
            $lineItem1->entry_type = 'debit';
            $lineItem1->save();

            // Create line item for A/R (Credit)
            $lineItem2 = new LineItem();
            $lineItem2->account_id = $arAccount->id;
            $lineItem2->transaction_id = $transaction->id;
            $lineItem2->amount = $payment->amount / 100; // Convert from cents
            $lineItem2->entity_id = $entity->id;
            $lineItem2->description = "Payment {$payment->payment_number}";
            $lineItem2->entry_type = 'credit';
            $lineItem2->save();

            // Post the transaction
            $transaction->post();

            DB::commit();

            Log::info('Payment posted to ledger', [
                'payment_id' => $payment->id,
                'transaction_id' => $transaction->id,
                'amount' => $payment->amount / 100,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to post payment to ledger', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Post a payment fee to the general ledger
     *
     * Creates a journal entry:
     * DR Fee Expense (Expense)
     * CR Cash/Bank (Asset)
     *
     * @param Payment $payment The payment with fee
     * @param float $fee The fee amount
     * @return void
     * @throws \Exception If posting fails
     */
    public function postFee(Payment $payment, float $fee): void
    {
        try {
            DB::beginTransaction();

            // Get or create the entity for this company
            $entity = $this->getOrCreateEntity($payment->company);

            // Get or create currency
            $currency = $this->getOrCreateCurrency($payment->currency->code ?? 'MKD');

            // Get reporting period
            $reportingPeriod = $this->getOrCreateReportingPeriod($entity, $payment->payment_date);

            // Get accounts
            $cashAccount = $this->getOrCreateAccount($entity, self::ACCOUNT_CASH, 'Cash/Bank', Account::BANK);
            $feeExpenseAccount = $this->getOrCreateAccount($entity, self::ACCOUNT_FEE_EXPENSE, 'Payment Processing Fees', Account::OPERATING_EXPENSE);

            // Create transaction
            $transaction = new Transaction();
            $transaction->account_id = $feeExpenseAccount->id;
            $transaction->transaction_date = Carbon::parse($payment->payment_date);
            $transaction->narration = "Payment Fee for {$payment->payment_number}";
            $transaction->transaction_type = Transaction::JN; // Journal Entry
            $transaction->entity_id = $entity->id;
            $transaction->currency_id = $currency->id;
            $transaction->credited = false;
            $transaction->save();

            // Create line item for Fee Expense (Debit)
            $lineItem1 = new LineItem();
            $lineItem1->account_id = $feeExpenseAccount->id;
            $lineItem1->transaction_id = $transaction->id;
            $lineItem1->amount = $fee;
            $lineItem1->entity_id = $entity->id;
            $lineItem1->description = "Payment processing fee";
            $lineItem1->entry_type = 'debit';
            $lineItem1->save();

            // Create line item for Cash (Credit)
            $lineItem2 = new LineItem();
            $lineItem2->account_id = $cashAccount->id;
            $lineItem2->transaction_id = $transaction->id;
            $lineItem2->amount = $fee;
            $lineItem2->entity_id = $entity->id;
            $lineItem2->description = "Payment processing fee";
            $lineItem2->entry_type = 'credit';
            $lineItem2->save();

            // Post the transaction
            $transaction->post();

            DB::commit();

            Log::info('Payment fee posted to ledger', [
                'payment_id' => $payment->id,
                'transaction_id' => $transaction->id,
                'fee' => $fee,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to post payment fee to ledger', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get trial balance for a company
     *
     * @param Company $company
     * @param string $asOfDate Date in Y-m-d format
     * @return array
     */
    public function getTrialBalance(Company $company, string $asOfDate): array
    {
        $entity = $this->getOrCreateEntity($company);
        $date = Carbon::parse($asOfDate);

        $trialBalance = new TrialBalance($entity, $date);

        return $trialBalance->attributes();
    }

    /**
     * Get balance sheet for a company
     *
     * @param Company $company
     * @param string $asOfDate Date in Y-m-d format
     * @return array
     */
    public function getBalanceSheet(Company $company, string $asOfDate): array
    {
        $entity = $this->getOrCreateEntity($company);
        $date = Carbon::parse($asOfDate);

        $balanceSheet = new BalanceSheet($entity, $date);

        return $balanceSheet->attributes();
    }

    /**
     * Get income statement for a company
     *
     * @param Company $company
     * @param string $startDate Date in Y-m-d format
     * @param string $endDate Date in Y-m-d format
     * @return array
     */
    public function getIncomeStatement(Company $company, string $startDate, string $endDate): array
    {
        $entity = $this->getOrCreateEntity($company);
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $incomeStatement = new IncomeStatement($entity, $start, $end);

        return $incomeStatement->attributes();
    }

    /**
     * Get or create IFRS entity for a company
     *
     * @param Company $company
     * @return \IFRS\Models\Entity
     */
    private function getOrCreateEntity(Company $company): \IFRS\Models\Entity
    {
        $entity = \IFRS\Models\Entity::where('name', $company->name)->first();

        if (!$entity) {
            $currency = $this->getOrCreateCurrency($company->currency->code ?? 'MKD');

            $entity = new \IFRS\Models\Entity();
            $entity->name = $company->name;
            $entity->currency_id = $currency->id;
            $entity->save();
        }

        return $entity;
    }

    /**
     * Get or create IFRS currency
     *
     * @param string $code Currency code (e.g., 'MKD', 'EUR')
     * @return Currency
     */
    private function getOrCreateCurrency(string $code): Currency
    {
        $currency = Currency::where('currency_code', $code)->first();

        if (!$currency) {
            $currency = new Currency();
            $currency->name = $code;
            $currency->currency_code = $code;
            $currency->save();
        }

        return $currency;
    }

    /**
     * Get or create reporting period for the given date
     *
     * @param \IFRS\Models\Entity $entity
     * @param string $date
     * @return ReportingPeriod
     */
    private function getOrCreateReportingPeriod(\IFRS\Models\Entity $entity, string $date): ReportingPeriod
    {
        $carbonDate = Carbon::parse($date);
        $year = $carbonDate->year;

        $period = ReportingPeriod::where('entity_id', $entity->id)
            ->where('year', $year)
            ->first();

        if (!$period) {
            $period = new ReportingPeriod();
            $period->period_count = 1;
            $period->year = $year;
            $period->entity_id = $entity->id;
            $period->status = ReportingPeriod::OPEN;
            $period->save();
        }

        return $period;
    }

    /**
     * Get or create an account
     *
     * @param \IFRS\Models\Entity $entity
     * @param int $code Account code
     * @param string $name Account name
     * @param string $type Account type
     * @return Account
     */
    private function getOrCreateAccount(\IFRS\Models\Entity $entity, int $code, string $name, string $type): Account
    {
        $account = Account::where('entity_id', $entity->id)
            ->where('account_type', $type)
            ->where('code', $code)
            ->first();

        if (!$account) {
            $currency = Currency::where('entity_id', $entity->id)->first();
            if (!$currency) {
                $currency = $this->getOrCreateCurrency('MKD');
            }

            $account = new Account();
            $account->name = $name;
            $account->account_type = $type;
            $account->code = $code;
            $account->entity_id = $entity->id;
            $account->currency_id = $currency->id;
            $account->save();
        }

        return $account;
    }
}

// CLAUDE-CHECKPOINT
