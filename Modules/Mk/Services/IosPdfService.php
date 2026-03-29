<?php

namespace Modules\Mk\Services;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
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

        $interestRate = 10; // Legal default interest rate in MK (%)

        $invoices = Invoice::where('company_id', $company->id)
            ->where('customer_id', $customer->id)
            ->whereBetween('invoice_date', [$from, $to])
            ->orderBy('invoice_date')
            ->get();

        $items = [];
        $subtotalDue = 0;
        $subtotalInterest = 0;

        foreach ($invoices as $inv) {
            $total = (int) $inv->total;
            $paidAmount = (int) ($inv->paid_status === 'PAID' ? $total : ($inv->payments()->sum('amount') ?? 0));
            $dueAmount = max(0, $total - $paidAmount);

            $daysOverdue = 0;
            $interest = 0;
            if ($inv->due_date && $dueAmount > 0) {
                $dueDate = Carbon::parse($inv->due_date);
                if (Carbon::now()->greaterThan($dueDate)) {
                    $daysOverdue = (int) Carbon::now()->diffInDays($dueDate);
                    $interest = (int) round($dueAmount * ($interestRate / 100) * ($daysOverdue / 365));
                }
            }

            $items[] = [
                'invoice_number' => $inv->invoice_number,
                'invoice_date' => Carbon::parse($inv->invoice_date)->format('d.m.Y'),
                'due_date' => $inv->due_date ? Carbon::parse($inv->due_date)->format('d.m.Y') : '-',
                'total' => $total,
                'due_amount' => $dueAmount,
                'days_overdue' => $daysOverdue,
                'interest' => $interest,
            ];
            $subtotalDue += $dueAmount;
            $subtotalInterest += $interest;
        }

        $logo = $company->logo ?? null;

        $pdf = PDF::loadView('app.pdf.reports.ios', [
            'company' => $company,
            'customer' => $customer,
            'logo' => $logo,
            'today' => now()->format('d.m.Y'),
            'items' => $items,
            'subtotal_due' => $subtotalDue,
            'subtotal_interest' => $subtotalInterest,
            'subtotal_total_with_interest' => $subtotalDue + $subtotalInterest,
            'interest_rate' => $interestRate,
            'currency_symbol' => 'ден.',
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf;
    }
}
// CLAUDE-CHECKPOINT
