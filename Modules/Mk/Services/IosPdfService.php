<?php

namespace Modules\Mk\Services;

use App\Models\BankAccount;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use Carbon\Carbon;
use PDF;

/**
 * IOS (Извод на отворени ставки) PDF Service
 *
 * Generates the standard Macedonian open items statement
 * for reconciliation between trading partners.
 */
class IosPdfService
{
    /**
     * Generate IOS for a customer (receivables perspective).
     */
    public function generateForCustomer(Company $company, Customer $customer, ?string $from = null, ?string $to = null)
    {
        $from = $from ? Carbon::parse($from) : Carbon::now()->startOfYear();
        $to = $to ? Carbon::parse($to) : Carbon::now();

        $invoices = Invoice::where('company_id', $company->id)
            ->where('customer_id', $customer->id)
            ->whereBetween('invoice_date', [$from, $to])
            ->orderBy('invoice_date')
            ->get();

        $payments = Payment::where('company_id', $company->id)
            ->where('customer_id', $customer->id)
            ->whereBetween('payment_date', [$from, $to])
            ->orderBy('payment_date')
            ->get();

        $items = [];
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($invoices as $inv) {
            $amount = (float) $inv->total / 100;
            $items[] = [
                'document_number' => $inv->invoice_number,
                'date' => Carbon::parse($inv->invoice_date)->format('d.m.Y'),
                'due_date' => $inv->due_date ? Carbon::parse($inv->due_date)->format('d.m.Y') : '-',
                'description' => 'Фактура',
                'debit' => $amount,
                'credit' => 0,
                'sort_date' => $inv->invoice_date,
            ];
            $totalDebit += $amount;
        }

        foreach ($payments as $pay) {
            $amount = (float) $pay->amount / 100;
            $items[] = [
                'document_number' => $pay->payment_number ?? '-',
                'date' => Carbon::parse($pay->payment_date)->format('d.m.Y'),
                'due_date' => '-',
                'description' => 'Уплата',
                'debit' => 0,
                'credit' => $amount,
                'sort_date' => $pay->payment_date,
            ];
            $totalCredit += $amount;
        }

        usort($items, fn ($a, $b) => strcmp($a['sort_date'], $b['sort_date']));

        $bankAccount = BankAccount::where('company_id', $company->id)
            ->where('is_active', true)
            ->first();

        $pdf = PDF::loadView('app.pdf.reports.ios', [
            'company' => $company,
            'partner_name' => $customer->name,
            'partner_vat' => $customer->vat_number ?? '',
            'partner_tax_id' => $customer->tax_id ?? '',
            'partner_address' => trim(($customer->billing_address_street_1 ?? '') . ', ' . ($customer->billing_city ?? ''), ', '),
            'bank_account' => $bankAccount?->iban ?? $bankAccount?->account_number ?? '-',
            'items' => $items,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'currency' => 'МКД',
            'date' => now()->format('d.m.Y'),
            'period_from' => $from->format('d.m.Y'),
            'period_to' => $to->format('d.m.Y'),
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf;
    }
}
// CLAUDE-CHECKPOINT
