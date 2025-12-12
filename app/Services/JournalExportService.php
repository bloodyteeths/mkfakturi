<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountMapping;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use League\Csv\Writer;

/**
 * Service for exporting journal entries to external accounting systems.
 * Supports CSV formats for Pantheon and Zonel accounting software.
 */
class JournalExportService
{
    /**
     * Export formats supported
     */
    public const FORMAT_CSV = 'csv';

    public const FORMAT_PANTHEON = 'pantheon';

    public const FORMAT_ZONEL = 'zonel';

    /**
     * Transaction types for journal entries
     */
    public const TYPE_INVOICE = 'invoice';

    public const TYPE_PAYMENT = 'payment';

    public const TYPE_EXPENSE = 'expense';

    protected int $companyId;

    protected Carbon $fromDate;

    protected Carbon $toDate;

    public function __construct(int $companyId, string $from, string $to)
    {
        $this->companyId = $companyId;
        $this->fromDate = Carbon::parse($from)->startOfDay();
        $this->toDate = Carbon::parse($to)->endOfDay();
    }

    /**
     * Get all journal entries for the date range.
     */
    public function getJournalEntries(): Collection
    {
        $entries = collect();

        // Get invoice journal entries
        $invoices = Invoice::where('company_id', $this->companyId)
            ->whereBetween('invoice_date', [$this->fromDate, $this->toDate])
            ->whereNotIn('status', [Invoice::STATUS_DRAFT])
            ->with(['customer', 'items', 'taxes'])
            ->get();

        foreach ($invoices as $invoice) {
            $entries = $entries->merge($this->invoiceToJournalEntries($invoice));
        }

        // Get payment journal entries
        $payments = Payment::where('company_id', $this->companyId)
            ->whereBetween('payment_date', [$this->fromDate, $this->toDate])
            ->with(['customer', 'invoice'])
            ->get();

        foreach ($payments as $payment) {
            $entries = $entries->merge($this->paymentToJournalEntries($payment));
        }

        // Get expense journal entries
        $expenses = Expense::where('company_id', $this->companyId)
            ->whereBetween('expense_date', [$this->fromDate, $this->toDate])
            ->with(['category', 'supplier'])
            ->get();

        foreach ($expenses as $expense) {
            $entries = $entries->merge($this->expenseToJournalEntries($expense));
        }

        return $entries->sortBy('date');
    }

    /**
     * Convert invoice to journal entries (double-entry).
     */
    protected function invoiceToJournalEntries(Invoice $invoice): array
    {
        $entries = [];
        $date = \Carbon\Carbon::parse($invoice->invoice_date)->format('Y-m-d');
        $reference = $invoice->invoice_number;
        $description = "Invoice {$reference} - {$invoice->customer->name}";

        // Debit: Accounts Receivable
        $arAccount = $this->getAccountCode('accounts_receivable', self::TYPE_INVOICE);
        $entries[] = [
            'date' => $date,
            'reference' => $reference,
            'type' => self::TYPE_INVOICE,
            'account_code' => $arAccount,
            'account_name' => 'Accounts Receivable',
            'description' => $description,
            'debit' => $invoice->total / 100,
            'credit' => 0,
            'customer_name' => $invoice->customer->name ?? '',
            'currency' => $invoice->currency->code ?? 'MKD',
        ];

        // Credit: Revenue (subtotal)
        $revenueAccount = $this->getAccountCode('revenue', self::TYPE_INVOICE);
        $subtotal = $invoice->sub_total / 100;
        $entries[] = [
            'date' => $date,
            'reference' => $reference,
            'type' => self::TYPE_INVOICE,
            'account_code' => $revenueAccount,
            'account_name' => 'Revenue',
            'description' => $description,
            'debit' => 0,
            'credit' => $subtotal,
            'customer_name' => $invoice->customer->name ?? '',
            'currency' => $invoice->currency->code ?? 'MKD',
        ];

        // Credit: Tax Payable (if applicable)
        if ($invoice->tax > 0) {
            $taxAccount = $this->getAccountCode('tax_payable', self::TYPE_INVOICE);
            $entries[] = [
                'date' => $date,
                'reference' => $reference,
                'type' => self::TYPE_INVOICE,
                'account_code' => $taxAccount,
                'account_name' => 'Tax Payable',
                'description' => $description.' - VAT',
                'debit' => 0,
                'credit' => $invoice->tax / 100,
                'customer_name' => $invoice->customer->name ?? '',
                'currency' => $invoice->currency->code ?? 'MKD',
            ];
        }

        return $entries;
    }

    /**
     * Convert payment to journal entries.
     */
    protected function paymentToJournalEntries(Payment $payment): array
    {
        $entries = [];
        $date = \Carbon\Carbon::parse($payment->payment_date)->format('Y-m-d');
        $reference = $payment->payment_number;
        $description = "Payment {$reference} - {$payment->customer->name}";

        // Debit: Cash/Bank
        $cashAccount = $this->getAccountCode('cash', self::TYPE_PAYMENT);
        $entries[] = [
            'date' => $date,
            'reference' => $reference,
            'type' => self::TYPE_PAYMENT,
            'account_code' => $cashAccount,
            'account_name' => 'Cash/Bank',
            'description' => $description,
            'debit' => $payment->amount / 100,
            'credit' => 0,
            'customer_name' => $payment->customer->name ?? '',
            'currency' => $payment->currency->code ?? 'MKD',
        ];

        // Credit: Accounts Receivable
        $arAccount = $this->getAccountCode('accounts_receivable', self::TYPE_PAYMENT);
        $entries[] = [
            'date' => $date,
            'reference' => $reference,
            'type' => self::TYPE_PAYMENT,
            'account_code' => $arAccount,
            'account_name' => 'Accounts Receivable',
            'description' => $description,
            'debit' => 0,
            'credit' => $payment->amount / 100,
            'customer_name' => $payment->customer->name ?? '',
            'currency' => $payment->currency->code ?? 'MKD',
        ];

        return $entries;
    }

    /**
     * Convert expense to journal entries.
     */
    protected function expenseToJournalEntries(Expense $expense): array
    {
        $entries = [];
        $date = \Carbon\Carbon::parse($expense->expense_date)->format('Y-m-d');
        $reference = $expense->invoice_number ?? 'EXP-'.$expense->id;
        $categoryName = $expense->category->name ?? 'Expense';
        $description = "{$categoryName} - {$reference}";

        // Debit: Expense account (based on category)
        $expenseAccount = $this->getAccountCode('expense', self::TYPE_EXPENSE, $expense->expense_category_id);
        $entries[] = [
            'date' => $date,
            'reference' => $reference,
            'type' => self::TYPE_EXPENSE,
            'account_code' => $expenseAccount,
            'account_name' => $categoryName,
            'description' => $description,
            'debit' => $expense->amount / 100,
            'credit' => 0,
            'customer_name' => $expense->supplier->name ?? '',
            'currency' => $expense->currency->code ?? 'MKD',
        ];

        // Credit: Cash/Accounts Payable
        $payableAccount = $this->getAccountCode('accounts_payable', self::TYPE_EXPENSE);
        $entries[] = [
            'date' => $date,
            'reference' => $reference,
            'type' => self::TYPE_EXPENSE,
            'account_code' => $payableAccount,
            'account_name' => 'Accounts Payable',
            'description' => $description,
            'debit' => 0,
            'credit' => $expense->amount / 100,
            'customer_name' => $expense->supplier->name ?? '',
            'currency' => $expense->currency->code ?? 'MKD',
        ];

        return $entries;
    }

    /**
     * Get account code from mappings or use default.
     */
    protected function getAccountCode(string $mapping, string $type, ?int $categoryId = null): string
    {
        // Try to find a mapping first based on entity_type and transaction_type
        // The mapping parameter maps to different entity types:
        // 'accounts_receivable', 'revenue', 'tax_payable' -> 'default' entity with transaction type
        // 'expense' with categoryId -> 'expense_category' entity

        $accountMapping = null;

        if ($mapping === 'expense' && $categoryId) {
            // For expenses with category, use ENTITY_EXPENSE_CATEGORY
            $accountMapping = AccountMapping::findForEntity(
                $this->companyId,
                AccountMapping::ENTITY_EXPENSE_CATEGORY,
                $categoryId,
                AccountMapping::TRANSACTION_EXPENSE
            );
        } else {
            // For other mappings, use default entity with appropriate transaction type
            $transactionType = match($type) {
                self::TYPE_INVOICE => AccountMapping::TRANSACTION_INVOICE,
                self::TYPE_PAYMENT => AccountMapping::TRANSACTION_PAYMENT,
                self::TYPE_EXPENSE => AccountMapping::TRANSACTION_EXPENSE,
                default => null,
            };

            $accountMapping = AccountMapping::findForEntity(
                $this->companyId,
                AccountMapping::ENTITY_DEFAULT,
                null,
                $transactionType
            );
        }

        // Use debit account for AR/expense, credit account for revenue/AP/tax
        if ($accountMapping) {
            if (in_array($mapping, ['accounts_receivable', 'expense', 'cash'])) {
                // These typically appear as debits
                if ($accountMapping->debitAccount) {
                    return $accountMapping->debitAccount->code;
                }
            } else {
                // These typically appear as credits (revenue, payables, tax)
                if ($accountMapping->creditAccount) {
                    return $accountMapping->creditAccount->code;
                }
            }
        }

        // Return default codes based on standard Macedonian chart of accounts
        return $this->getDefaultAccountCode($mapping);
    }

    /**
     * Get default account codes based on standard Macedonian chart of accounts.
     */
    protected function getDefaultAccountCode(string $mapping): string
    {
        $defaults = [
            'accounts_receivable' => '220100', // Побарувања од купувачи
            'revenue' => '660100', // Приходи од продажба
            'tax_payable' => '270100', // ДДВ обврска
            'cash' => '240100', // Жиро сметка
            'accounts_payable' => '220200', // Обврски кон добавувачи
            'expense' => '540100', // Општи трошоци
        ];

        return $defaults[$mapping] ?? '999999';
    }

    /**
     * Export to generic CSV format.
     */
    public function toCSV(): string
    {
        $entries = $this->getJournalEntries();

        $csv = Writer::createFromString('');
        $csv->insertOne([
            'Date',
            'Reference',
            'Type',
            'Account Code',
            'Account Name',
            'Description',
            'Debit',
            'Credit',
            'Customer/Supplier',
            'Currency',
        ]);

        foreach ($entries as $entry) {
            $csv->insertOne([
                $entry['date'],
                $entry['reference'],
                $entry['type'],
                $entry['account_code'],
                $entry['account_name'],
                $entry['description'],
                number_format($entry['debit'], 2, '.', ''),
                number_format($entry['credit'], 2, '.', ''),
                $entry['customer_name'],
                $entry['currency'],
            ]);
        }

        return $csv->toString();
    }

    /**
     * Export to Pantheon CSV format.
     * Pantheon uses specific column format for import.
     */
    public function toPantheonCSV(): string
    {
        $entries = $this->getJournalEntries();

        $csv = Writer::createFromString('');
        // Pantheon format: Datum;Dokument;Konto;Partner;Opis;Dolg;Potr;Valuta
        $csv->setDelimiter(';');
        $csv->insertOne([
            'Datum',
            'Dokument',
            'Konto',
            'Partner',
            'Opis',
            'Dolg',
            'Potr',
            'Valuta',
        ]);

        foreach ($entries as $entry) {
            $csv->insertOne([
                Carbon::parse($entry['date'])->format('d.m.Y'), // Macedonian date format
                $entry['reference'],
                $entry['account_code'],
                $entry['customer_name'],
                mb_substr($entry['description'], 0, 50), // Pantheon has character limit
                number_format($entry['debit'], 2, ',', ''),
                number_format($entry['credit'], 2, ',', ''),
                $entry['currency'],
            ]);
        }

        return $csv->toString();
    }

    /**
     * Export to Zonel CSV format.
     * Zonel uses specific column format for import.
     */
    public function toZonelCSV(): string
    {
        $entries = $this->getJournalEntries();

        $csv = Writer::createFromString('');
        // Zonel format uses pipe delimiter
        $csv->setDelimiter('|');
        $csv->insertOne([
            'DATUM',
            'BROJ_DOK',
            'SIFRA_KONTO',
            'NAZIV_KONTO',
            'OPIS',
            'DOLZUVA',
            'POBARUVA',
            'SIFRA_PARTNER',
            'NAZIV_PARTNER',
        ]);

        $docNumber = 1;
        $currentRef = null;

        foreach ($entries as $entry) {
            // Zonel groups entries by document number
            if ($currentRef !== $entry['reference']) {
                $currentRef = $entry['reference'];
                $docNumber++;
            }

            $csv->insertOne([
                Carbon::parse($entry['date'])->format('dmY'), // Zonel date format
                str_pad($docNumber, 6, '0', STR_PAD_LEFT),
                $entry['account_code'],
                mb_substr($entry['account_name'], 0, 30),
                mb_substr($entry['description'], 0, 100),
                number_format($entry['debit'], 2, '.', ''),
                number_format($entry['credit'], 2, '.', ''),
                '', // Partner code - would need to be mapped
                mb_substr($entry['customer_name'], 0, 50),
            ]);
        }

        return $csv->toString();
    }

    /**
     * Get summary statistics for the export period.
     */
    public function getSummary(): array
    {
        $entries = $this->getJournalEntries();

        $totalDebit = $entries->sum('debit');
        $totalCredit = $entries->sum('credit');

        return [
            'from_date' => $this->fromDate->format('Y-m-d'),
            'to_date' => $this->toDate->format('Y-m-d'),
            'entry_count' => $entries->count(),
            'invoice_count' => $entries->where('type', self::TYPE_INVOICE)->unique('reference')->count(),
            'payment_count' => $entries->where('type', self::TYPE_PAYMENT)->unique('reference')->count(),
            'expense_count' => $entries->where('type', self::TYPE_EXPENSE)->unique('reference')->count(),
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'is_balanced' => abs($totalDebit - $totalCredit) < 0.01,
        ];
    }
}
// CLAUDE-CHECKPOINT
