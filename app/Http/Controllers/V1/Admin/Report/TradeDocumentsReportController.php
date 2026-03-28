<?php

namespace App\Http\Controllers\V1\Admin\Report;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Currency;
use App\Models\CreditNote;
use App\Models\Expense;
use App\Models\InventoryDocument;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Mk\Models\Nivelacija;
use PDF;

class TradeDocumentsReportController extends Controller
{
    /**
     * List trade documents for a company (unified view).
     * Aggregates bills (KAP/PLT sources), nivelacii, prenosnici, and trade book entries.
     */
    public function index(Request $request): JsonResponse
    {
        $company = $request->header('company');
        if (! $company) {
            return response()->json(['error' => 'Company header required'], 422);
        }

        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');
        $type = $request->query('type', 'all');

        if (! $fromDate || ! $toDate) {
            return response()->json([
                'error' => 'from_date and to_date are required',
            ], 422);
        }

        $documents = collect();
        $currency = Currency::find(CompanySetting::getSetting('currency', $company));
        $currencySymbol = $currency->symbol ?? 'ден.';

        // Bills — source for КАП and ПЛТ
        if (in_array($type, ['all', 'kap', 'plt'])) {
            $bills = Bill::where('company_id', $company)
                ->whereNotIn('status', ['DRAFT'])
                ->where('bill_date', '>=', $fromDate)
                ->where('bill_date', '<=', $toDate)
                ->without(['creator', 'company'])
                ->with(['supplier'])
                ->orderBy('bill_date')
                ->get();

            foreach ($bills as $bill) {
                $billDate = $bill->bill_date instanceof \DateTimeInterface
                    ? $bill->bill_date->format('Y-m-d')
                    : substr((string) ($bill->bill_date ?? ''), 0, 10);

                $totalFormatted = number_format(($bill->total ?? 0) / 100, 2, ',', '.') . ' ' . $currencySymbol;

                if ($type === 'all' || $type === 'kap') {
                    $documents->push([
                        'date' => $billDate,
                        'doc_type' => 'kap',
                        'type_label' => 'КАП',
                        'doc_number' => $bill->bill_number ?? '',
                        'party' => $bill->supplier?->name ?? '',
                        'amount_formatted' => $totalFormatted,
                        'export_url' => "/api/v1/partner/companies/{$company}/accounting/kap/{$bill->id}/export",
                    ]);
                }

                if ($type === 'all' || $type === 'plt') {
                    $documents->push([
                        'date' => $billDate,
                        'doc_type' => 'plt',
                        'type_label' => 'ПЛТ',
                        'doc_number' => $bill->bill_number ?? '',
                        'party' => $bill->supplier?->name ?? '',
                        'amount_formatted' => $totalFormatted,
                        'export_url' => "/api/v1/partner/companies/{$company}/accounting/plt/{$bill->id}/export",
                    ]);
                }
            }
        }

        // Нивелации
        if (in_array($type, ['all', 'nivelacija'])) {
            $nivelacii = Nivelacija::where('company_id', $company)
                ->where('document_date', '>=', $fromDate)
                ->where('document_date', '<=', $toDate)
                ->orderBy('document_date', 'desc')
                ->get();

            foreach ($nivelacii as $niv) {
                $nivDate = $niv->document_date instanceof \DateTimeInterface
                    ? $niv->document_date->format('Y-m-d')
                    : substr((string) ($niv->document_date ?? ''), 0, 10);

                $diff = $niv->total_difference ?? 0;
                $diffFormatted = ($diff >= 0 ? '+' : '') . number_format($diff / 100, 2, ',', '.') . ' ' . $currencySymbol;

                $documents->push([
                    'date' => $nivDate,
                    'doc_type' => 'nivelacija',
                    'type_label' => 'Нивелација',
                    'doc_number' => $niv->document_number ?? '',
                    'party' => $niv->reason ?? '',
                    'amount_formatted' => $diffFormatted,
                    'export_url' => "/api/v1/partner/companies/{$company}/accounting/nivelacii/{$niv->id}/export",
                ]);
            }
        }

        // Преносници (inventory transfers)
        if (in_array($type, ['all', 'prenosnica'])) {
            $transfers = InventoryDocument::where('company_id', $company)
                ->where('document_type', InventoryDocument::TYPE_TRANSFER)
                ->where('document_date', '>=', $fromDate)
                ->where('document_date', '<=', $toDate)
                ->with(['warehouse', 'destinationWarehouse'])
                ->orderBy('document_date', 'desc')
                ->get();

            foreach ($transfers as $doc) {
                $docDate = $doc->document_date instanceof \DateTimeInterface
                    ? $doc->document_date->format('Y-m-d')
                    : substr((string) ($doc->document_date ?? ''), 0, 10);

                $totalValue = $doc->items ? $doc->items->sum(function ($item) {
                    return ($item->quantity ?? 0) * ($item->unit_cost ?? 0);
                }) : 0;
                $valueFormatted = number_format($totalValue / 100, 2, ',', '.') . ' ' . $currencySymbol;

                $fromTo = ($doc->warehouse?->name ?? '?') . ' → ' . ($doc->destinationWarehouse?->name ?? '?');

                $documents->push([
                    'date' => $docDate,
                    'doc_type' => 'prenosnica',
                    'type_label' => 'Преносница',
                    'doc_number' => $doc->document_number ?? '',
                    'party' => $fromTo,
                    'amount_formatted' => $valueFormatted,
                    'export_url' => "/api/v1/partner/companies/{$company}/accounting/prenosnica/{$doc->id}/export",
                ]);
            }
        }

        // Trade Book (ЕТ) — shown as summary entry when "all" or "trade_book" type
        if ($type === 'trade_book') {
            // For trade book, return the ET entries directly
            $etEntries = $this->buildTradeBookEntries($company, $fromDate, $toDate, $currency);
            return response()->json([
                'success' => true,
                'data' => $etEntries,
            ]);
        }

        // Sort by date descending
        $sorted = $documents->sortByDesc('date')->values();

        return response()->json([
            'success' => true,
            'data' => $sorted,
        ]);
    }

    /**
     * Export Trade Book (ЕТ) as PDF.
     */
    public function tradeBookExport(Request $request): Response
    {
        $company = $request->header('company');
        if (! $company) {
            abort(422, 'Company header required');
        }

        $companyModel = Company::find($company);
        if (! $companyModel) {
            abort(404, 'Company not found');
        }
        $companyModel->load('address');

        $fromDate = $request->query('from_date', now()->startOfMonth()->toDateString());
        $toDate = $request->query('to_date', now()->toDateString());

        $currency = Currency::find(CompanySetting::getSetting('currency', $company));

        $entries = $this->buildTradeBookEntries($company, $fromDate, $toDate, $currency);

        view()->share([
            'company' => $companyModel,
            'entries' => $entries,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'currency' => $currency,
        ]);

        $pdf = PDF::loadView('app.pdf.reports.trade-book');
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download("trgovska_kniga_{$fromDate}_{$toDate}.pdf");
    }

    /**
     * Build trade book (ЕТ) entries from invoices, credit notes, bills, expenses.
     */
    protected function buildTradeBookEntries(int $companyId, string $fromDate, string $toDate, ?Currency $currency): array
    {
        $entries = collect();
        $seq = 0;

        // Invoices (sales)
        $invoices = Invoice::where('company_id', $companyId)
            ->whereNotIn('status', ['DRAFT'])
            ->with('customer')
            ->where('invoice_date', '>=', $fromDate)
            ->where('invoice_date', '<=', $toDate)
            ->orderBy('invoice_date')
            ->get();

        foreach ($invoices as $inv) {
            $seq++;
            $entries->push([
                'seq' => $seq,
                'date' => substr((string) ($inv->invoice_date instanceof \DateTimeInterface ? $inv->invoice_date->format('d.m') : ($inv->invoice_date ?? '')), 0, 5),
                'doc_name' => 'Фактура',
                'doc_number' => $inv->invoice_number ?? '',
                'doc_date' => substr((string) ($inv->invoice_date instanceof \DateTimeInterface ? $inv->invoice_date->format('d.m.Y') : ($inv->invoice_date ?? '')), 0, 10),
                'party' => $inv->customer?->name ?? '',
                'nabavna' => 0,
                'prodazhna' => (int) ($inv->total ?? 0),
                'promet' => (int) ($inv->total ?? 0),
                'doc_type' => 'invoice',
            ]);
        }

        // Credit notes (negative)
        $creditNotes = CreditNote::where('company_id', $companyId)
            ->whereNotIn('status', ['DRAFT'])
            ->with('customer')
            ->where('credit_note_date', '>=', $fromDate)
            ->where('credit_note_date', '<=', $toDate)
            ->orderBy('credit_note_date')
            ->get();

        foreach ($creditNotes as $cn) {
            $seq++;
            $entries->push([
                'seq' => $seq,
                'date' => substr((string) ($cn->credit_note_date instanceof \DateTimeInterface ? $cn->credit_note_date->format('d.m') : ($cn->credit_note_date ?? '')), 0, 5),
                'doc_name' => 'Кредит нота',
                'doc_number' => $cn->credit_note_number ?? '',
                'doc_date' => substr((string) ($cn->credit_note_date instanceof \DateTimeInterface ? $cn->credit_note_date->format('d.m.Y') : ($cn->credit_note_date ?? '')), 0, 10),
                'party' => $cn->customer?->name ?? '',
                'nabavna' => 0,
                'prodazhna' => -abs((int) ($cn->total ?? 0)),
                'promet' => -abs((int) ($cn->total ?? 0)),
                'doc_type' => 'credit_note',
            ]);
        }

        // Bills (purchases)
        $bills = Bill::where('company_id', $companyId)
            ->whereNotIn('status', ['DRAFT'])
            ->without(['creator', 'company'])
            ->with(['supplier'])
            ->where('bill_date', '>=', $fromDate)
            ->where('bill_date', '<=', $toDate)
            ->orderBy('bill_date')
            ->get();

        foreach ($bills as $bill) {
            $seq++;
            $billDate = $bill->bill_date instanceof \DateTimeInterface
                ? $bill->bill_date->format('d.m.Y')
                : substr((string) ($bill->bill_date ?? ''), 0, 10);

            $entries->push([
                'seq' => $seq,
                'date' => substr($billDate, 0, 5),
                'doc_name' => 'Влезна фактура',
                'doc_number' => $bill->bill_number ?? '',
                'doc_date' => $billDate,
                'party' => $bill->supplier?->name ?? '',
                'nabavna' => (int) ($bill->total ?? 0),
                'prodazhna' => 0,
                'promet' => null,
                'doc_type' => 'bill',
            ]);
        }

        // Expenses
        $expenses = Expense::where('company_id', $companyId)
            ->with('category')
            ->where('expense_date', '>=', $fromDate)
            ->where('expense_date', '<=', $toDate)
            ->orderBy('expense_date')
            ->get();

        foreach ($expenses as $exp) {
            $seq++;
            $expDate = $exp->expense_date instanceof \DateTimeInterface
                ? $exp->expense_date->format('d.m.Y')
                : substr((string) ($exp->expense_date ?? ''), 0, 10);

            $entries->push([
                'seq' => $seq,
                'date' => substr($expDate, 0, 5),
                'doc_name' => 'Трошок',
                'doc_number' => $exp->expense_number ?? ($exp->category?->name ?? ''),
                'doc_date' => $expDate,
                'party' => $exp->category?->name ?? '',
                'nabavna' => (int) ($exp->amount ?? 0),
                'prodazhna' => 0,
                'promet' => null,
                'doc_type' => 'expense',
            ]);
        }

        return $entries->sortBy('date')->values()->toArray();
    }
}

// CLAUDE-CHECKPOINT
