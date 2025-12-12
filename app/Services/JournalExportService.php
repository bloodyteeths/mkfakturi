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

        // Debit: Accounts Receivable (check for learned customer mapping)
        $arAccountData = $this->getAccountCodeWithStatus('accounts_receivable', self::TYPE_INVOICE, 'customer', $invoice->user_id);
        $entries[] = [
            'date' => $date,
            'reference' => $reference,
            'type' => self::TYPE_INVOICE,
            'account_code' => $arAccountData['code'],
            'account_name' => 'Accounts Receivable',
            'description' => $description,
            'debit' => $invoice->total / 100,
            'credit' => 0,
            'customer_name' => $invoice->customer->name ?? '',
            'currency' => $invoice->currency->code ?? 'MKD',
            'entity' => [
                'type' => 'customer',
                'id' => $invoice->user_id,
                'name' => $invoice->customer->name ?? '',
            ],
            'mapping_status' => $arAccountData['status'],
        ];

        // Credit: Revenue (subtotal)
        $revenueAccountData = $this->getAccountCodeWithStatus('revenue', self::TYPE_INVOICE);
        $subtotal = $invoice->sub_total / 100;
        $entries[] = [
            'date' => $date,
            'reference' => $reference,
            'type' => self::TYPE_INVOICE,
            'account_code' => $revenueAccountData['code'],
            'account_name' => 'Revenue',
            'description' => $description,
            'debit' => 0,
            'credit' => $subtotal,
            'customer_name' => $invoice->customer->name ?? '',
            'currency' => $invoice->currency->code ?? 'MKD',
            'entity' => [
                'type' => 'customer',
                'id' => $invoice->user_id,
                'name' => $invoice->customer->name ?? '',
            ],
            'mapping_status' => $revenueAccountData['status'],
        ];

        // Credit: Tax Payable (if applicable)
        if ($invoice->tax > 0) {
            $taxAccountData = $this->getAccountCodeWithStatus('tax_payable', self::TYPE_INVOICE);
            $entries[] = [
                'date' => $date,
                'reference' => $reference,
                'type' => self::TYPE_INVOICE,
                'account_code' => $taxAccountData['code'],
                'account_name' => 'Tax Payable',
                'description' => $description.' - VAT',
                'debit' => 0,
                'credit' => $invoice->tax / 100,
                'customer_name' => $invoice->customer->name ?? '',
                'currency' => $invoice->currency->code ?? 'MKD',
                'mapping_status' => $taxAccountData['status'],
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
        $cashAccountData = $this->getAccountCodeWithStatus('cash', self::TYPE_PAYMENT);
        $entries[] = [
            'date' => $date,
            'reference' => $reference,
            'type' => self::TYPE_PAYMENT,
            'account_code' => $cashAccountData['code'],
            'account_name' => 'Cash/Bank',
            'description' => $description,
            'debit' => $payment->amount / 100,
            'credit' => 0,
            'customer_name' => $payment->customer->name ?? '',
            'currency' => $payment->currency->code ?? 'MKD',
            'entity' => [
                'type' => 'customer',
                'id' => $payment->customer_id,
                'name' => $payment->customer->name ?? '',
            ],
            'mapping_status' => $cashAccountData['status'],
        ];

        // Credit: Accounts Receivable (check for learned customer mapping)
        $arAccountData = $this->getAccountCodeWithStatus('accounts_receivable', self::TYPE_PAYMENT, 'customer', $payment->customer_id);
        $entries[] = [
            'date' => $date,
            'reference' => $reference,
            'type' => self::TYPE_PAYMENT,
            'account_code' => $arAccountData['code'],
            'account_name' => 'Accounts Receivable',
            'description' => $description,
            'debit' => 0,
            'credit' => $payment->amount / 100,
            'customer_name' => $payment->customer->name ?? '',
            'currency' => $payment->currency->code ?? 'MKD',
            'entity' => [
                'type' => 'customer',
                'id' => $payment->customer_id,
                'name' => $payment->customer->name ?? '',
            ],
            'mapping_status' => $arAccountData['status'],
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

        // Debit: Expense account (check for learned category mapping)
        $expenseAccountData = $this->getAccountCodeWithStatus('expense', self::TYPE_EXPENSE, 'expense_category', $expense->expense_category_id);
        $entries[] = [
            'date' => $date,
            'reference' => $reference,
            'type' => self::TYPE_EXPENSE,
            'account_code' => $expenseAccountData['code'],
            'account_name' => $categoryName,
            'description' => $description,
            'debit' => $expense->amount / 100,
            'credit' => 0,
            'customer_name' => $expense->supplier->name ?? '',
            'currency' => $expense->currency->code ?? 'MKD',
            'entity' => [
                'type' => 'expense_category',
                'id' => $expense->expense_category_id,
                'name' => $categoryName,
            ],
            'mapping_status' => $expenseAccountData['status'],
        ];

        // Credit: Cash/Accounts Payable (check for learned supplier mapping if supplier exists)
        $payableAccountData = $expense->supplier_id
            ? $this->getAccountCodeWithStatus('accounts_payable', self::TYPE_EXPENSE, 'supplier', $expense->supplier_id)
            : $this->getAccountCodeWithStatus('accounts_payable', self::TYPE_EXPENSE);

        $entries[] = [
            'date' => $date,
            'reference' => $reference,
            'type' => self::TYPE_EXPENSE,
            'account_code' => $payableAccountData['code'],
            'account_name' => 'Accounts Payable',
            'description' => $description,
            'debit' => 0,
            'credit' => $expense->amount / 100,
            'customer_name' => $expense->supplier->name ?? '',
            'currency' => $expense->currency->code ?? 'MKD',
            'entity' => $expense->supplier_id ? [
                'type' => 'supplier',
                'id' => $expense->supplier_id,
                'name' => $expense->supplier->name ?? '',
            ] : null,
            'mapping_status' => $payableAccountData['status'],
        ];

        return $entries;
    }

    /**
     * Get account code with mapping status information.
     *
     * Returns account code and status showing if it's learned, default, etc.
     *
     * @param string $mapping Account mapping type (accounts_receivable, revenue, etc.)
     * @param string $type Transaction type (invoice, payment, expense)
     * @param string|null $entityType Entity type for learned mappings (customer, supplier, expense_category)
     * @param int|null $entityId Entity ID for learned mappings
     * @return array ['code' => string, 'status' => array]
     */
    protected function getAccountCodeWithStatus(string $mapping, string $type, ?string $entityType = null, ?int $entityId = null): array
    {
        $hasLearnedMapping = false;
        $confidence = 0.0;
        $isDefault = true;
        $accountCode = null;

        // First check if there's a learned mapping for this specific entity
        if ($entityType && $entityId) {
            $mappingEntityType = match($entityType) {
                'customer' => AccountMapping::ENTITY_CUSTOMER,
                'supplier' => AccountMapping::ENTITY_SUPPLIER,
                'expense_category' => AccountMapping::ENTITY_EXPENSE_CATEGORY,
                default => null,
            };

            if ($mappingEntityType) {
                $accountMapping = AccountMapping::where('company_id', $this->companyId)
                    ->where('entity_type', $mappingEntityType)
                    ->where('entity_id', $entityId)
                    ->first();

                if ($accountMapping && $accountMapping->debitAccount) {
                    $accountCode = $accountMapping->debitAccount->code;
                    $hasLearnedMapping = true;
                    $isDefault = false;
                    $confidence = $accountMapping->meta['confidence'] ?? 1.0;
                }
            }
        }

        // Fall back to existing logic if no learned mapping found
        if (!$accountCode) {
            $accountCode = $this->getAccountCode($mapping, $type, $entityType === 'expense_category' ? $entityId : null);
        }

        return [
            'code' => $accountCode,
            'status' => [
                'has_learned_mapping' => $hasLearnedMapping,
                'confidence' => $confidence,
                'is_default' => $isDefault,
            ],
        ];
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
     * Export to Pantheon XML format.
     * Pantheon uses specific XML schema for journal entry import.
     */
    public function toPantheonXML(): string
    {
        $entries = $this->getJournalEntries();

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><Dnevnik></Dnevnik>');

        // Add metadata
        $xml->addChild('DatumOd', $this->fromDate->format('Y-m-d'));
        $xml->addChild('DatumDo', $this->toDate->format('Y-m-d'));
        $xml->addChild('Firma', $this->companyId);

        // Group entries by document reference
        $groupedEntries = collect($entries)->groupBy('reference');

        $stavki = $xml->addChild('Stavki');

        foreach ($groupedEntries as $reference => $docEntries) {
            $dokument = $stavki->addChild('Dokument');
            $firstEntry = $docEntries->first();

            $dokument->addChild('Datum', Carbon::parse($firstEntry['date'])->format('Y-m-d'));
            $dokument->addChild('BrojDokument', htmlspecialchars($reference));
            $dokument->addChild('Opis', htmlspecialchars(mb_substr($firstEntry['description'], 0, 100)));

            $knizenja = $dokument->addChild('Knizenja');

            foreach ($docEntries as $entry) {
                $knizenje = $knizenja->addChild('Knizenje');
                $knizenje->addChild('Konto', $entry['account_code']);
                $knizenje->addChild('Partner', htmlspecialchars($entry['customer_name'] ?? ''));
                $knizenje->addChild('Opis', htmlspecialchars(mb_substr($entry['description'], 0, 50)));
                $knizenje->addChild('Dolzuva', number_format($entry['debit'], 2, '.', ''));
                $knizenje->addChild('Pobaruva', number_format($entry['credit'], 2, '.', ''));
                $knizenje->addChild('Valuta', $entry['currency']);
            }
        }

        // Format XML with proper indentation
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());

        return $dom->saveXML();
    }

    /**
     * Export to Pantheon CSV format (legacy/alternative).
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
