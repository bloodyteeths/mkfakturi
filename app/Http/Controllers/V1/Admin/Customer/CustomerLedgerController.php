<?php

namespace App\Http\Controllers\V1\Admin\Customer;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\BillPayment;
use App\Models\CompanySetting;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PDF;

class CustomerLedgerController extends Controller
{
    /**
     * Unified ledger card for a customer (and its linked supplier).
     */
    public function forCustomer(Request $request, Customer $customer)
    {
        $supplier = $customer->linkedSupplier;

        return $this->buildLedgerResponse($request, $customer, $supplier);
    }

    /**
     * Unified ledger card for a supplier (and its linked customer).
     */
    public function forSupplier(Request $request, Supplier $supplier)
    {
        $customer = $supplier->linkedCustomer;

        return $this->buildLedgerResponse($request, $customer, $supplier);
    }

    /**
     * Download customer ledger card as PDF.
     */
    public function customerPdf(Request $request, Customer $customer)
    {
        $supplier = $customer->linkedSupplier;

        return $this->generatePdf($request, $customer, $supplier, 'customer');
    }

    /**
     * Download supplier ledger card as PDF.
     */
    public function supplierPdf(Request $request, Supplier $supplier)
    {
        $customer = $supplier->linkedCustomer;

        return $this->generatePdf($request, $customer, $supplier, 'supplier');
    }

    private function buildLedgerResponse(Request $request, ?Customer $customer, ?Supplier $supplier)
    {
        $result = $this->buildLedger($request, $customer, $supplier);

        return response()->json([
            'data' => $result['ledger']->values(),
            'meta' => $result['meta'],
        ]);
    }

    private function buildLedger(Request $request, ?Customer $customer, ?Supplier $supplier): array
    {
        $fromDate = $request->query('from_date')
            ? Carbon::parse($request->query('from_date'))
            : Carbon::now()->startOfYear();
        $toDate = $request->query('to_date')
            ? Carbon::parse($request->query('to_date'))
            : Carbon::now()->endOfYear();

        $entries = collect();
        $ifrsTransactionIds = [];

        // AR side: Invoices and Payments from customer
        if ($customer) {
            $invoices = Invoice::where('customer_id', $customer->id)
                ->whereBetween('invoice_date', [$fromDate, $toDate])
                ->get();

            foreach ($invoices as $invoice) {
                if ($invoice->ifrs_transaction_id) {
                    $ifrsTransactionIds[] = $invoice->ifrs_transaction_id;
                }
                $entries->push([
                    'date' => $invoice->invoice_date,
                    'type' => 'invoice',
                    'reference' => $invoice->invoice_number,
                    'description' => 'Invoice #' . $invoice->invoice_number,
                    'debit' => $invoice->total,
                    'credit' => 0,
                    'side' => 'AR',
                    'id' => $invoice->id,
                    'ifrs_transaction_id' => $invoice->ifrs_transaction_id,
                ]);
            }

            $payments = Payment::where('customer_id', $customer->id)
                ->whereBetween('payment_date', [$fromDate, $toDate])
                ->get();

            foreach ($payments as $payment) {
                if ($payment->ifrs_transaction_id) {
                    $ifrsTransactionIds[] = $payment->ifrs_transaction_id;
                }
                $entries->push([
                    'date' => $payment->payment_date,
                    'type' => 'payment',
                    'reference' => $payment->payment_number ?? 'PAY-' . $payment->id,
                    'description' => 'Payment received',
                    'debit' => 0,
                    'credit' => $payment->amount,
                    'side' => 'AR',
                    'id' => $payment->id,
                    'ifrs_transaction_id' => $payment->ifrs_transaction_id,
                ]);
            }
        }

        // AP side: Bills and BillPayments from supplier
        if ($supplier) {
            $bills = Bill::where('supplier_id', $supplier->id)
                ->whereBetween('bill_date', [$fromDate, $toDate])
                ->get();

            foreach ($bills as $bill) {
                if ($bill->ifrs_transaction_id) {
                    $ifrsTransactionIds[] = $bill->ifrs_transaction_id;
                }
                $entries->push([
                    'date' => $bill->bill_date,
                    'type' => 'bill',
                    'reference' => $bill->bill_number,
                    'description' => 'Bill #' . $bill->bill_number,
                    'debit' => 0,
                    'credit' => $bill->total,
                    'side' => 'AP',
                    'id' => $bill->id,
                    'ifrs_transaction_id' => $bill->ifrs_transaction_id,
                ]);
            }

            $billPayments = BillPayment::whereHas('bill', function ($q) use ($supplier) {
                $q->where('supplier_id', $supplier->id);
            })
                ->whereBetween('payment_date', [$fromDate, $toDate])
                ->with('bill:id,bill_number')
                ->get();

            foreach ($billPayments as $bp) {
                if ($bp->ifrs_transaction_id) {
                    $ifrsTransactionIds[] = $bp->ifrs_transaction_id;
                }
                $entries->push([
                    'date' => $bp->payment_date,
                    'type' => 'bill_payment',
                    'reference' => $bp->bill ? $bp->bill->bill_number : 'BP-' . $bp->id,
                    'description' => 'Payment to supplier',
                    'debit' => $bp->amount,
                    'credit' => 0,
                    'side' => 'AP',
                    'id' => $bp->id,
                    'ifrs_transaction_id' => $bp->ifrs_transaction_id,
                ]);
            }
        }

        // IFRS journal entries: pull ledger records by counterparty_name
        // This surfaces imported journal data that has no matching invoice/bill record
        $this->addIfrsJournalEntries($entries, $ifrsTransactionIds, $customer, $supplier, $fromDate, $toDate);

        // Batch-load GL accounts for all IFRS transactions
        $glAccountMap = $this->batchLoadGlAccounts($ifrsTransactionIds);

        // Enrich entries with GL account info (skip journal entries — already enriched)
        $entries = $entries->map(function ($entry) use ($glAccountMap) {
            if ($entry['type'] === 'journal') {
                unset($entry['ifrs_transaction_id']);
                return $entry;
            }

            $txId = $entry['ifrs_transaction_id'] ?? null;
            $accounts = $txId ? ($glAccountMap[$txId] ?? []) : [];

            // Pick the primary account based on document type
            $account = $this->pickPrimaryAccount($accounts, $entry['type']);

            $entry['account_code'] = $account['code'] ?? null;
            $entry['account_name'] = $account['name'] ?? null;
            unset($entry['ifrs_transaction_id']); // Don't expose internal ID

            return $entry;
        });

        // Sort by date, then by type (invoices/bills before payments on same date)
        $entries = $entries->sortBy([
            ['date', 'asc'],
            ['type', 'asc'],
        ])->values();

        // Compute running balance
        $runningBalance = 0;
        $totalDebit = 0;
        $totalCredit = 0;

        $ledger = $entries->map(function ($entry) use (&$runningBalance, &$totalDebit, &$totalCredit) {
            $totalDebit += $entry['debit'];
            $totalCredit += $entry['credit'];
            $runningBalance += $entry['debit'] - $entry['credit'];

            return array_merge($entry, [
                'balance' => $runningBalance,
            ]);
        });

        return [
            'ledger' => $ledger,
            'meta' => [
                'from_date' => $fromDate->toDateString(),
                'to_date' => $toDate->toDateString(),
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'closing_balance' => $runningBalance,
                'customer_id' => $customer?->id,
                'supplier_id' => $supplier?->id,
            ],
        ];
    }

    /**
     * Add IFRS journal entries for a customer/supplier by counterparty_name.
     * Only adds entries whose transaction_id is NOT already in the ledger (avoids duplicates).
     */
    private function addIfrsJournalEntries(
        \Illuminate\Support\Collection &$entries,
        array &$ifrsTransactionIds,
        ?Customer $customer,
        ?Supplier $supplier,
        Carbon $fromDate,
        Carbon $toDate
    ): void {
        if (!Schema::hasColumn('ifrs_line_items', 'counterparty_name')) {
            return;
        }

        // Determine entity_id and counterparty name to search
        $entity = $customer ? $customer->company : ($supplier ? $supplier->company : null);
        if (!$entity || !$entity->ifrs_entity_id) {
            return;
        }

        $entityId = $entity->ifrs_entity_id;
        $counterpartyName = $customer ? $customer->name : ($supplier ? $supplier->name : null);
        if (!$counterpartyName) {
            return;
        }

        // Query IFRS ledger entries where counterparty_name matches
        $journalEntries = DB::table('ifrs_ledgers as l')
            ->join('ifrs_transactions as t', 'l.transaction_id', '=', 't.id')
            ->leftJoin('ifrs_line_items as li', 'l.line_item_id', '=', 'li.id')
            ->leftJoin('ifrs_accounts as a', 'l.post_account', '=', 'a.id')
            ->where('l.entity_id', $entityId)
            ->where('li.counterparty_name', $counterpartyName)
            ->whereBetween('l.posting_date', [$fromDate->toDateString(), $toDate->toDateString()])
            ->whereNull('l.deleted_at')
            ->select([
                'l.posting_date as date',
                'l.entry_type',
                'l.amount',
                'l.transaction_id',
                't.transaction_no as reference',
                'li.narration as line_narration',
                't.narration',
                'a.code as account_code',
                'a.name as account_name',
                'a.account_type',
            ])
            ->orderBy('l.posting_date')
            ->orderBy('l.id')
            ->get();

        // Skip entries whose transaction_id already exists in the ledger (from invoices/bills)
        $existingTxIds = array_flip($ifrsTransactionIds);

        foreach ($journalEntries as $entry) {
            if (isset($existingTxIds[$entry->transaction_id])) {
                continue;
            }

            // IFRS stores amounts in full units; invoices/bills store in cents (subunit)
            // Multiply by 100 to match the format used by formatAmount() in the frontend
            $amount = (float) $entry->amount * 100;
            $debit = $entry->entry_type === 'D' ? $amount : 0;
            $credit = $entry->entry_type === 'C' ? $amount : 0;

            // Determine side from account type
            $side = in_array($entry->account_type, ['RECEIVABLE']) ? 'AR' : (
                in_array($entry->account_type, ['PAYABLE']) ? 'AP' : 'GL'
            );

            $description = $entry->line_narration ?? $entry->narration ?? '';

            $entries->push([
                'date' => $entry->date,
                'type' => 'journal',
                'reference' => $entry->reference ?? '',
                'description' => $description,
                'debit' => $debit,
                'credit' => $credit,
                'side' => $side,
                'id' => $entry->transaction_id,
                'ifrs_transaction_id' => $entry->transaction_id,
                'account_code' => $entry->account_code,
                'account_name' => $entry->account_name,
            ]);

            // Track this transaction so GL enrichment doesn't duplicate
            $ifrsTransactionIds[] = $entry->transaction_id;
        }
    }

    /**
     * Batch-load GL accounts for multiple IFRS transactions.
     * Returns map: [transaction_id => [['code' => X, 'name' => Y, 'account_type' => Z], ...]]
     */
    private function batchLoadGlAccounts(array $transactionIds): array
    {
        if (empty($transactionIds)) {
            return [];
        }

        $rows = DB::table('ifrs_line_items')
            ->join('ifrs_accounts', 'ifrs_line_items.account_id', '=', 'ifrs_accounts.id')
            ->whereIn('ifrs_line_items.transaction_id', array_unique($transactionIds))
            ->whereNull('ifrs_line_items.deleted_at')
            ->select(
                'ifrs_line_items.transaction_id',
                'ifrs_accounts.code',
                'ifrs_accounts.name',
                'ifrs_accounts.account_type'
            )
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $map[$row->transaction_id][] = [
                'code' => $row->code,
                'name' => $row->name,
                'account_type' => $row->account_type,
            ];
        }

        return $map;
    }

    /**
     * Pick the primary GL account for a ledger entry.
     * For invoices: revenue account (not AR). For payments: cash/bank (not AR).
     * For bills: expense account (not AP). For bill_payments: cash/bank (not AP).
     */
    private function pickPrimaryAccount(array $accounts, string $type): array
    {
        if (empty($accounts)) {
            return [];
        }

        // Account types to exclude (control accounts — implicit from document type)
        $excludeTypes = match ($type) {
            'invoice' => ['RECEIVABLE'],
            'payment' => ['RECEIVABLE'],
            'bill' => ['PAYABLE'],
            'bill_payment' => ['PAYABLE'],
            default => [],
        };

        foreach ($accounts as $account) {
            if (!in_array($account['account_type'], $excludeTypes)) {
                return $account;
            }
        }

        // Fallback: return first account if all are excluded
        return $accounts[0];
    }

    /**
     * Generate PDF for a customer or supplier ledger card.
     */
    private function generatePdf(Request $request, ?Customer $customer, ?Supplier $supplier, string $perspective)
    {
        // Determine company from the entity
        $entity = $perspective === 'customer' ? $customer : $supplier;
        if (!$entity) {
            abort(404);
        }

        $company = $entity->company;
        $this->authorize('view report', $company);

        $locale = CompanySetting::getSetting('language', $company->id) ?: 'mk';
        App::setLocale($locale);

        $result = $this->buildLedger($request, $customer, $supplier);

        $dateFormat = CompanySetting::getSetting('carbon_date_format', $company->id) ?: 'd/m/Y';
        $fromFormatted = Carbon::parse($result['meta']['from_date'])->translatedFormat($dateFormat);
        $toFormatted = Carbon::parse($result['meta']['to_date'])->translatedFormat($dateFormat);

        $currencyId = CompanySetting::getSetting('currency', $company->id);
        $currency = $currencyId ? Currency::find($currencyId) : null;
        if (!$currency) {
            $currency = Currency::where('code', 'MKD')->first() ?: Currency::first();
        }

        // Primary entity info for the report header
        $entityName = $perspective === 'customer'
            ? ($customer->name ?? '')
            : ($supplier->name ?? '');
        $entityTaxId = $perspective === 'customer'
            ? ($customer->tax_id ?? '')
            : ($supplier->tax_id ?? '');

        // Load nested JSON translations (trans() can't navigate nested JSON)
        $langFile = lang_path($locale . '.json');
        $t = file_exists($langFile) ? json_decode(file_get_contents($langFile), true) : [];

        $reportTitle = $perspective === 'customer'
            ? (data_get($t, 'customers.customer_ledger_report') ?: 'Картица на купувач')
            : (data_get($t, 'customers.supplier_ledger_report') ?: 'Картица на добавувач');

        $labels = [
            'tax_id' => data_get($t, 'customers.tax_id') ?: 'ЕМБС',
            'period' => data_get($t, 'general.period') ?: 'Период',
            'total' => data_get($t, 'general.total') ?: 'Вкупно',
            'debit' => data_get($t, 'customers.debit') ?: 'Должи',
            'credit' => data_get($t, 'customers.credit') ?: 'Побарува',
            'closing_balance' => data_get($t, 'customers.closing_balance') ?: 'Крајно салдо',
            'receivable' => data_get($t, 'customers.receivable') ?: 'Побарување',
            'payable' => data_get($t, 'customers.payable') ?: 'Обврска',
            'date' => data_get($t, 'general.date') ?: 'Датум',
            'type' => data_get($t, 'customers.document_type') ?: 'Тип на документ',
            'reference' => data_get($t, 'customers.reference') ?: 'Референца',
            'account' => data_get($t, 'customers.account_code') ?: 'Конто',
            'balance' => data_get($t, 'customers.closing_balance') ?: 'Крајно салдо',
            'prepared_by' => data_get($t, 'customers.prepared_by') ?: 'Составил',
            'approved_by' => data_get($t, 'customers.approved_by') ?: 'Одобрил',
            'types' => [
                'invoice' => data_get($t, 'invoices.invoice') ?: 'Фактура',
                'payment' => data_get($t, 'payments.payment') ?: 'Уплата',
                'bill' => data_get($t, 'bills.bill') ?: 'Сметка',
                'bill_payment' => data_get($t, 'bills.payment') ?: 'Исплата',
                'journal' => data_get($t, 'customers.journal_entry') ?: 'Книжење',
            ],
        ];

        view()->share([
            'company' => $company,
            'ledger' => $result['ledger']->values(),
            'meta' => $result['meta'],
            'from_date' => $fromFormatted,
            'to_date' => $toFormatted,
            'report_period' => $fromFormatted . ' — ' . $toFormatted,
            'report_title' => $reportTitle,
            'currency' => $currency,
            'entity_name' => $entityName,
            'entity_tax_id' => $entityTaxId,
            'perspective' => $perspective,
            'labels' => $labels,
        ]);

        $pdf = PDF::loadView('app.pdf.reports.customer-ledger-card');
        $pdf->setPaper('A4', 'landscape');

        $filename = ($perspective === 'customer' ? 'customer' : 'supplier')
            . '-ledger-' . str_replace(' ', '_', $entityName)
            . '-' . $result['meta']['from_date']
            . '.pdf';

        if ($request->has('download')) {
            return $pdf->download($filename);
        }

        return $pdf->stream($filename);
    }
}

// CLAUDE-CHECKPOINT
