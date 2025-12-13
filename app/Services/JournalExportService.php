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
 *
 * Uses AI-powered account classification for revenue accounts based on
 * invoice item content (goods, services, products, consulting).
 */
class JournalExportService
{
    protected ?AccountSuggestionService $suggestionService = null;
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
        $this->suggestionService = new AccountSuggestionService();
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
     * Includes detailed item breakdown, tax rates, and customer tax ID for Pantheon.
     */
    protected function invoiceToJournalEntries(Invoice $invoice): array
    {
        $entries = [];
        $date = \Carbon\Carbon::parse($invoice->invoice_date)->format('Y-m-d');
        $reference = $invoice->invoice_number;

        // Collect item descriptions for AI suggestions
        $itemDescriptions = $invoice->items->pluck('name')->filter()->implode(', ');
        $description = "Invoice {$reference} - {$invoice->customer->name}";
        $itemContext = $itemDescriptions ?: $invoice->notes ?? '';

        // Customer details for Pantheon
        $customerTaxId = $invoice->customer->tax_id ?? $invoice->customer->vat_number ?? '';
        $customerCode = $customerTaxId ?: 'C' . str_pad($invoice->customer_id, 6, '0', STR_PAD_LEFT);

        // Collect detailed item information
        $itemsDetails = [];
        $taxBreakdown = [];
        foreach ($invoice->items as $item) {
            $itemsDetails[] = [
                'name' => $item->name,
                'description' => $item->description ?? '',
                'quantity' => $item->quantity,
                'price' => $item->price / 100,
                'total' => $item->total / 100,
                'tax' => $item->tax / 100,
                'unit' => $item->unit_name ?? 'kom',
            ];

            // Group taxes by rate
            if ($item->taxes && $item->taxes->count() > 0) {
                foreach ($item->taxes as $tax) {
                    $rate = $tax->percent ?? 18;
                    if (!isset($taxBreakdown[$rate])) {
                        $taxBreakdown[$rate] = ['rate' => $rate, 'base' => 0, 'amount' => 0];
                    }
                    $taxBreakdown[$rate]['base'] += ($item->total - $item->tax) / 100;
                    $taxBreakdown[$rate]['amount'] += $item->tax / 100;
                }
            } elseif ($item->tax > 0) {
                // Default 18% VAT if no specific tax
                $rate = 18;
                if (!isset($taxBreakdown[$rate])) {
                    $taxBreakdown[$rate] = ['rate' => $rate, 'base' => 0, 'amount' => 0];
                }
                $taxBreakdown[$rate]['base'] += ($item->total - $item->tax) / 100;
                $taxBreakdown[$rate]['amount'] += $item->tax / 100;
            }
        }

        // Debit: Accounts Receivable
        $arAccountData = $this->getAccountCodeWithStatus('accounts_receivable', self::TYPE_INVOICE, 'customer', $invoice->customer_id);
        $entries[] = [
            'date' => $date,
            'reference' => $reference,
            'type' => self::TYPE_INVOICE,
            'doc_type' => 'FI', // Фактура Издадена (Invoice Issued)
            'account_code' => $arAccountData['code'],
            'account_name' => 'Побарувања од купувачи',
            'description' => $description,
            'item_context' => $itemContext,
            'debit' => $invoice->total / 100,
            'credit' => 0,
            'customer_name' => $invoice->customer->name ?? '',
            'customer_code' => $customerCode,
            'customer_tax_id' => $customerTaxId,
            'currency' => $invoice->currency->code ?? 'MKD',
            'entity' => [
                'type' => 'customer',
                'id' => $invoice->customer_id,
                'name' => $invoice->customer->name ?? '',
                'tax_id' => $customerTaxId,
            ],
            'items' => $itemsDetails,
            'tax_breakdown' => array_values($taxBreakdown),
            'mapping_status' => $arAccountData['status'],
        ];

        // Credit: Revenue (subtotal) - Use AI to classify based on invoice items
        $aiContext = trim(($invoice->customer->name ?? '') . ' ' . $itemContext);
        $revenueAccountData = $this->getRevenueAccountWithAI($aiContext, $invoice->customer_id);
        $subtotal = $invoice->sub_total / 100;
        $entries[] = [
            'date' => $date,
            'reference' => $reference,
            'type' => self::TYPE_INVOICE,
            'doc_type' => 'FI',
            'account_code' => $revenueAccountData['code'],
            'account_name' => $revenueAccountData['name'] ?? 'Приходи',
            'description' => $description,
            'item_context' => $itemContext,
            'debit' => 0,
            'credit' => $subtotal,
            'customer_name' => $invoice->customer->name ?? '',
            'customer_code' => $customerCode,
            'customer_tax_id' => $customerTaxId,
            'currency' => $invoice->currency->code ?? 'MKD',
            'entity' => [
                'type' => 'customer',
                'id' => $invoice->customer_id,
                'name' => $invoice->customer->name ?? '',
                'tax_id' => $customerTaxId,
            ],
            'items' => $itemsDetails,
            'tax_breakdown' => array_values($taxBreakdown),
            'mapping_status' => $revenueAccountData['status'],
        ];

        // Credit: Tax Payable - one entry per tax rate
        if (!empty($taxBreakdown)) {
            $taxAccountData = $this->getAccountCodeWithStatus('tax_payable', self::TYPE_INVOICE);
            foreach ($taxBreakdown as $rate => $taxData) {
                if ($taxData['amount'] > 0) {
                    $entries[] = [
                        'date' => $date,
                        'reference' => $reference,
                        'type' => self::TYPE_INVOICE,
                        'doc_type' => 'FI',
                        'account_code' => $taxAccountData['code'],
                        'account_name' => "ДДВ {$rate}%",
                        'description' => "{$description} - ДДВ {$rate}%",
                        'item_context' => "ДДВ {$rate}%",
                        'debit' => 0,
                        'credit' => $taxData['amount'],
                        'customer_name' => $invoice->customer->name ?? '',
                        'customer_code' => $customerCode,
                        'customer_tax_id' => $customerTaxId,
                        'currency' => $invoice->currency->code ?? 'MKD',
                        'entity' => [
                            'type' => 'tax',
                            'id' => null,
                            'name' => "ДДВ {$rate}%",
                            'rate' => $rate,
                        ],
                        'tax_base' => $taxData['base'],
                        'tax_rate' => $rate,
                        'mapping_status' => $taxAccountData['status'],
                    ];
                }
            }
        } elseif ($invoice->tax > 0) {
            // Fallback if no tax breakdown available
            $taxAccountData = $this->getAccountCodeWithStatus('tax_payable', self::TYPE_INVOICE);
            $entries[] = [
                'date' => $date,
                'reference' => $reference,
                'type' => self::TYPE_INVOICE,
                'doc_type' => 'FI',
                'account_code' => $taxAccountData['code'],
                'account_name' => 'ДДВ 18%',
                'description' => $description . ' - ДДВ',
                'item_context' => 'ДДВ VAT',
                'debit' => 0,
                'credit' => $invoice->tax / 100,
                'customer_name' => $invoice->customer->name ?? '',
                'customer_code' => $customerCode,
                'customer_tax_id' => $customerTaxId,
                'currency' => $invoice->currency->code ?? 'MKD',
                'entity' => [
                    'type' => 'tax',
                    'id' => null,
                    'name' => 'ДДВ',
                ],
                'tax_rate' => 18,
                'mapping_status' => $taxAccountData['status'],
            ];
        }

        return $entries;
    }

    /**
     * Convert payment to journal entries.
     * Includes customer tax ID and document type for Pantheon.
     */
    protected function paymentToJournalEntries(Payment $payment): array
    {
        $entries = [];
        $date = \Carbon\Carbon::parse($payment->payment_date)->format('Y-m-d');
        $reference = $payment->payment_number;
        $description = "Payment {$reference} - {$payment->customer->name}";

        // Customer details
        $customerTaxId = $payment->customer->tax_id ?? $payment->customer->vat_number ?? '';
        $customerCode = $customerTaxId ?: 'C' . str_pad($payment->customer_id, 6, '0', STR_PAD_LEFT);

        // Payment method for document type
        $paymentMethod = $payment->paymentMethod->name ?? 'Cash';
        $docType = 'PL'; // Плаќање (Payment)

        // Debit: Cash/Bank
        $cashAccountData = $this->getAccountCodeWithStatus('cash', self::TYPE_PAYMENT);
        $entries[] = [
            'date' => $date,
            'reference' => $reference,
            'type' => self::TYPE_PAYMENT,
            'doc_type' => $docType,
            'account_code' => $cashAccountData['code'],
            'account_name' => 'Парични средства',
            'description' => $description,
            'debit' => $payment->amount / 100,
            'credit' => 0,
            'customer_name' => $payment->customer->name ?? '',
            'customer_code' => $customerCode,
            'customer_tax_id' => $customerTaxId,
            'currency' => $payment->currency->code ?? 'MKD',
            'payment_method' => $paymentMethod,
            'entity' => [
                'type' => 'customer',
                'id' => $payment->customer_id,
                'name' => $payment->customer->name ?? '',
                'tax_id' => $customerTaxId,
            ],
            'mapping_status' => $cashAccountData['status'],
        ];

        // Credit: Accounts Receivable
        $arAccountData = $this->getAccountCodeWithStatus('accounts_receivable', self::TYPE_PAYMENT, 'customer', $payment->customer_id);
        $entries[] = [
            'date' => $date,
            'reference' => $reference,
            'type' => self::TYPE_PAYMENT,
            'doc_type' => $docType,
            'account_code' => $arAccountData['code'],
            'account_name' => 'Побарувања од купувачи',
            'description' => $description,
            'debit' => 0,
            'credit' => $payment->amount / 100,
            'customer_name' => $payment->customer->name ?? '',
            'customer_code' => $customerCode,
            'customer_tax_id' => $customerTaxId,
            'currency' => $payment->currency->code ?? 'MKD',
            'payment_method' => $paymentMethod,
            'entity' => [
                'type' => 'customer',
                'id' => $payment->customer_id,
                'name' => $payment->customer->name ?? '',
                'tax_id' => $customerTaxId,
            ],
            'mapping_status' => $arAccountData['status'],
        ];

        return $entries;
    }

    /**
     * Convert expense to journal entries.
     * Includes supplier tax ID and document type for Pantheon.
     */
    protected function expenseToJournalEntries(Expense $expense): array
    {
        $entries = [];
        $date = \Carbon\Carbon::parse($expense->expense_date)->format('Y-m-d');
        $reference = $expense->invoice_number ?? 'EXP-'.$expense->id;
        $categoryName = $expense->category->name ?? 'Expense';
        $description = "{$categoryName} - {$reference}";
        $docType = 'FP'; // Фактура Примена (Invoice Received / Expense)

        // Supplier details
        $supplierTaxId = '';
        $supplierCode = '';
        $supplierName = '';
        if ($expense->supplier_id && $expense->supplier) {
            $supplierTaxId = $expense->supplier->tax_id ?? $expense->supplier->vat_number ?? '';
            $supplierCode = $supplierTaxId ?: 'S' . str_pad($expense->supplier_id, 6, '0', STR_PAD_LEFT);
            $supplierName = $expense->supplier->name ?? '';
        }

        // Debit: Expense account (check for learned category mapping)
        $expenseAccountData = $this->getAccountCodeWithStatus('expense', self::TYPE_EXPENSE, 'expense_category', $expense->expense_category_id);
        $entries[] = [
            'date' => $date,
            'reference' => $reference,
            'type' => self::TYPE_EXPENSE,
            'doc_type' => $docType,
            'account_code' => $expenseAccountData['code'],
            'account_name' => $categoryName,
            'description' => $description,
            'debit' => $expense->amount / 100,
            'credit' => 0,
            'customer_name' => $supplierName,
            'customer_code' => $supplierCode,
            'customer_tax_id' => $supplierTaxId,
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
            'doc_type' => $docType,
            'account_code' => $payableAccountData['code'],
            'account_name' => 'Обврски кон добавувачи',
            'description' => $description,
            'debit' => 0,
            'credit' => $expense->amount / 100,
            'customer_name' => $supplierName,
            'customer_code' => $supplierCode,
            'customer_tax_id' => $supplierTaxId,
            'currency' => $expense->currency->code ?? 'MKD',
            'entity' => $expense->supplier_id ? [
                'type' => 'supplier',
                'id' => $expense->supplier_id,
                'name' => $supplierName,
                'tax_id' => $supplierTaxId,
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

        // Only use learned entity mappings for appropriate account types:
        // - Customer mappings are for REVENUE accounts (what to credit for sales)
        // - Supplier mappings are for EXPENSE/AP accounts
        // - Expense category mappings are for EXPENSE accounts
        // DO NOT use learned mappings for accounts_receivable, cash, tax_payable - those use standard accounts
        $shouldUseLearned = $entityType && $entityId && !in_array($mapping, ['accounts_receivable', 'cash', 'tax_payable']);

        if ($shouldUseLearned) {
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
            'accounts_receivable' => '1610', // Побарувања од купувачи - домашни
            'revenue' => '4000', // Приходи од продажба
            'tax_payable' => '2710', // ДДВ за уплата
            'cash' => '1020', // Жиро сметка
            'accounts_payable' => '2210', // Обврски - домашни добавувачи
            'expense' => '5000', // Набавна вредност на продадена стока
        ];

        return $defaults[$mapping] ?? '5000';
    }

    /**
     * Get revenue account using AI classification based on invoice item content.
     *
     * First checks for learned mappings, then falls back to AI classification.
     * Returns classified revenue account:
     * - 4010: Приходи од продажба на стока (Goods/Trade)
     * - 4020: Приходи од услуги (Services)
     * - 4030: Приходи од консалтинг (Consulting)
     * - 4040: Приходи од производи (Products)
     *
     * @param string $aiContext Item descriptions and customer context
     * @param int|null $customerId Customer ID for learned mapping lookup
     * @return array ['code' => string, 'name' => string, 'status' => array]
     */
    protected function getRevenueAccountWithAI(string $aiContext, ?int $customerId): array
    {
        // First check for learned mapping for this customer
        if ($customerId) {
            $learnedMapping = AccountMapping::where('company_id', $this->companyId)
                ->where('entity_type', AccountMapping::ENTITY_CUSTOMER)
                ->where('entity_id', $customerId)
                ->first();

            if ($learnedMapping && $learnedMapping->debitAccount) {
                return [
                    'code' => $learnedMapping->debitAccount->code,
                    'name' => $learnedMapping->debitAccount->name,
                    'status' => [
                        'has_learned_mapping' => true,
                        'confidence' => $learnedMapping->meta['confidence'] ?? 1.0,
                        'is_default' => false,
                    ],
                ];
            }
        }

        // Use AI suggestion service to classify based on item content
        if ($this->suggestionService && !empty($aiContext)) {
            $suggestion = $this->suggestionService->suggestWithConfidence(
                'customer',
                $aiContext,
                null,
                $this->companyId
            );

            if ($suggestion && isset($suggestion['account_id'])) {
                $account = Account::find($suggestion['account_id']);
                if ($account) {
                    // Auto-save this AI suggestion as learned mapping (implicit acceptance during export)
                    if ($customerId) {
                        $this->autoSaveMapping(
                            AccountMapping::ENTITY_CUSTOMER,
                            $customerId,
                            $account->id,
                            $suggestion['confidence'] ?? 0.5,
                            $suggestion['reason'] ?? 'ai_classification'
                        );
                    }

                    return [
                        'code' => $account->code,
                        'name' => $account->name,
                        'status' => [
                            'has_learned_mapping' => false,
                            'confidence' => $suggestion['confidence'] ?? 0.5,
                            'is_default' => false,
                            'ai_reason' => $suggestion['reason'] ?? 'pattern',
                            'auto_saved' => (bool) $customerId,
                        ],
                    ];
                }
            }
        }

        // Fallback to default revenue account
        $defaultCode = '4000'; // Приходи од продажба
        $account = Account::where('company_id', $this->companyId)
            ->where('code', $defaultCode)
            ->where('is_active', true)
            ->first();

        // Auto-save default mapping too (so next time it's consistent)
        if ($customerId && $account) {
            $this->autoSaveMapping(
                AccountMapping::ENTITY_CUSTOMER,
                $customerId,
                $account->id,
                0.3,
                'default_fallback'
            );
        }

        return [
            'code' => $account ? $account->code : $defaultCode,
            'name' => $account ? $account->name : 'Revenue',
            'status' => [
                'has_learned_mapping' => false,
                'confidence' => 0.3,
                'is_default' => true,
                'auto_saved' => (bool) ($customerId && $account),
            ],
        ];
    }

    /**
     * Auto-save an AI suggestion as a learned mapping.
     * This happens during export when user implicitly accepts the suggestion.
     *
     * @param string $entityType Entity type (customer, supplier, expense_category)
     * @param int $entityId Entity ID
     * @param int $accountId Account ID to map to
     * @param float $confidence AI confidence score
     * @param string $reason Reason for the suggestion
     */
    protected function autoSaveMapping(
        string $entityType,
        int $entityId,
        int $accountId,
        float $confidence,
        string $reason
    ): void {
        try {
            AccountMapping::updateOrCreate(
                [
                    'company_id' => $this->companyId,
                    'entity_type' => $entityType,
                    'entity_id' => $entityId,
                ],
                [
                    'debit_account_id' => $accountId,
                    'meta' => [
                        'auto_accepted' => true,
                        'confidence' => $confidence,
                        'reason' => $reason,
                        'accepted_at' => now()->toIso8601String(),
                    ],
                ]
            );
        } catch (\Exception $e) {
            // Log but don't fail export if auto-save fails
            \Log::warning('Failed to auto-save account mapping during export', [
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);
        }
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
     *
     * Includes:
     * - Document type codes (FI=Фактура Издадена, FP=Фактура Примена, PL=Плаќање)
     * - Customer/Supplier tax ID (EDB)
     * - Partner codes
     * - Detailed item breakdown
     * - Tax rate information
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

            // Document header
            $dokument->addChild('Datum', Carbon::parse($firstEntry['date'])->format('Y-m-d'));
            $dokument->addChild('TipDokument', $firstEntry['doc_type'] ?? 'XX'); // FI, FP, PL
            $dokument->addChild('BrojDokument', htmlspecialchars($reference));
            $dokument->addChild('Opis', htmlspecialchars(mb_substr($firstEntry['description'], 0, 100)));

            // Partner info (customer/supplier)
            $partner = $dokument->addChild('Partner');
            $partner->addChild('Sifra', htmlspecialchars($firstEntry['customer_code'] ?? ''));
            $partner->addChild('Naziv', htmlspecialchars($firstEntry['customer_name'] ?? ''));
            $partner->addChild('EDB', htmlspecialchars($firstEntry['customer_tax_id'] ?? '')); // Единствен Даночен Број

            // Invoice items (if available on first entry - for invoices)
            if (!empty($firstEntry['items']) && is_array($firstEntry['items'])) {
                $stavkiNode = $dokument->addChild('StavkiFaktura');
                foreach ($firstEntry['items'] as $item) {
                    $stavka = $stavkiNode->addChild('Stavka');
                    $stavka->addChild('Naziv', htmlspecialchars(mb_substr($item['name'], 0, 100)));
                    $stavka->addChild('Opis', htmlspecialchars(mb_substr($item['description'] ?? '', 0, 200)));
                    $stavka->addChild('Kolicina', number_format($item['quantity'], 2, '.', ''));
                    $stavka->addChild('Edinica', htmlspecialchars($item['unit'] ?? 'kom'));
                    $stavka->addChild('Cena', number_format($item['price'], 2, '.', ''));
                    $stavka->addChild('Vkupno', number_format($item['total'], 2, '.', ''));
                    $stavka->addChild('DDV', number_format($item['tax'], 2, '.', ''));
                }
            }

            // Tax breakdown (if available)
            if (!empty($firstEntry['tax_breakdown']) && is_array($firstEntry['tax_breakdown'])) {
                $ddvNode = $dokument->addChild('DDVPregled');
                foreach ($firstEntry['tax_breakdown'] as $taxData) {
                    $ddvStavka = $ddvNode->addChild('DDVStavka');
                    $ddvStavka->addChild('Stapka', $taxData['rate'] ?? 18);
                    $ddvStavka->addChild('Osnova', number_format($taxData['base'] ?? 0, 2, '.', ''));
                    $ddvStavka->addChild('Iznos', number_format($taxData['amount'] ?? 0, 2, '.', ''));
                }
            }

            // Journal entries (double-entry bookkeeping)
            $knizenja = $dokument->addChild('Knizenja');

            foreach ($docEntries as $entry) {
                $knizenje = $knizenja->addChild('Knizenje');
                $knizenje->addChild('Konto', $entry['account_code']);
                $knizenje->addChild('NazivKonto', htmlspecialchars(mb_substr($entry['account_name'] ?? '', 0, 50)));
                $knizenje->addChild('SifraPartner', htmlspecialchars($entry['customer_code'] ?? ''));
                $knizenje->addChild('Partner', htmlspecialchars($entry['customer_name'] ?? ''));
                $knizenje->addChild('EDB', htmlspecialchars($entry['customer_tax_id'] ?? ''));
                $knizenje->addChild('Opis', htmlspecialchars(mb_substr($entry['description'], 0, 100)));
                $knizenje->addChild('Dolzuva', number_format($entry['debit'], 2, '.', ''));
                $knizenje->addChild('Pobaruva', number_format($entry['credit'], 2, '.', ''));
                $knizenje->addChild('Valuta', $entry['currency']);

                // Tax details for tax entries
                if (isset($entry['tax_rate'])) {
                    $knizenje->addChild('DDVStapka', $entry['tax_rate']);
                }
                if (isset($entry['tax_base'])) {
                    $knizenje->addChild('DDVOsnova', number_format($entry['tax_base'], 2, '.', ''));
                }
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
