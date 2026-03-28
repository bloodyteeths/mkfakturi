<?php

namespace Modules\Mk\Services;

use App\Models\Bill;
use App\Models\BillPayment;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Supplier;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Carbon\Carbon;

class OpenItemsStatementService
{
    /**
     * Generate IOS for a specific customer.
     * Invoices = debit (задолжување), Payments = credit (одобрување)
     */
    public function generateForCustomer(int $companyId, int $customerId, string $asOfDate): array
    {
        $customer = Customer::find($customerId);
        if (! $customer) {
            return $this->emptyResult('customer', $asOfDate);
        }

        $entries = [];

        // Get all invoices for this customer up to as_of_date
        $invoices = Invoice::where('company_id', $companyId)
            ->where('customer_id', $customerId)
            ->where('invoice_date', '<=', $asOfDate)
            ->whereNotIn('status', ['DRAFT'])
            ->without(['customer', 'currency'])
            ->select(['id', 'invoice_date', 'invoice_number', 'total', 'due_amount', 'status', 'paid_status'])
            ->orderBy('invoice_date')
            ->get();

        foreach ($invoices as $invoice) {
            $entries[] = [
                'date' => $invoice->invoice_date,
                'date_formatted' => Carbon::parse($invoice->invoice_date)->format('d.m.Y'),
                'document' => 'Фактура ' . $invoice->invoice_number,
                'debit' => (int) $invoice->total,
                'credit' => 0,
                'source_type' => 'invoice',
                'source_id' => $invoice->id,
            ];
        }

        // Get all payments for this customer up to as_of_date
        $payments = Payment::where('company_id', $companyId)
            ->where('customer_id', $customerId)
            ->where('payment_date', '<=', $asOfDate)
            ->without(['customer', 'paymentMethod', 'currency', 'company'])
            ->select(['id', 'payment_date', 'payment_number', 'amount', 'invoice_id'])
            ->orderBy('payment_date')
            ->get();

        foreach ($payments as $payment) {
            $entries[] = [
                'date' => $payment->payment_date,
                'date_formatted' => Carbon::parse($payment->payment_date)->format('d.m.Y'),
                'document' => 'Уплата ' . ($payment->payment_number ?: 'ПН-' . $payment->id),
                'debit' => 0,
                'credit' => (int) $payment->amount,
                'source_type' => 'payment',
                'source_id' => $payment->id,
            ];
        }

        return $this->buildResult($entries, $customer, 'customer', $asOfDate);
    }

    /**
     * Generate IOS for a specific supplier.
     * Bills = credit (we owe them), BillPayments = debit (we paid)
     * Note: For supplier IOS, the perspective is reversed:
     * - Bill = задолжување (they invoiced us)
     * - BillPayment = одобрување (we paid them)
     */
    public function generateForSupplier(int $companyId, int $supplierId, string $asOfDate): array
    {
        $supplier = Supplier::find($supplierId);
        if (! $supplier) {
            return $this->emptyResult('supplier', $asOfDate);
        }

        $entries = [];

        // Get all bills from this supplier up to as_of_date
        $bills = Bill::where('company_id', $companyId)
            ->where('supplier_id', $supplierId)
            ->where('bill_date', '<=', $asOfDate)
            ->whereNotIn('status', ['DRAFT'])
            ->without(['supplier', 'currency', 'company'])
            ->select(['id', 'bill_date', 'bill_number', 'total', 'due_amount', 'status', 'paid_status'])
            ->orderBy('bill_date')
            ->get();

        foreach ($bills as $bill) {
            $entries[] = [
                'date' => $bill->bill_date,
                'date_formatted' => Carbon::parse($bill->bill_date)->format('d.m.Y'),
                'document' => 'Фактура ' . ($bill->bill_number ?: 'Б-' . $bill->id),
                'debit' => (int) $bill->total,
                'credit' => 0,
                'source_type' => 'bill',
                'source_id' => $bill->id,
            ];
        }

        // Get all bill payments to this supplier up to as_of_date
        $billPayments = BillPayment::where('company_id', $companyId)
            ->whereHas('bill', function ($q) use ($supplierId) {
                $q->where('supplier_id', $supplierId);
            })
            ->where('payment_date', '<=', $asOfDate)
            ->select(['id', 'payment_date', 'payment_number', 'amount', 'bill_id'])
            ->with(['bill:id,bill_number'])
            ->orderBy('payment_date')
            ->get();

        foreach ($billPayments as $bp) {
            $entries[] = [
                'date' => $bp->payment_date,
                'date_formatted' => Carbon::parse($bp->payment_date)->format('d.m.Y'),
                'document' => 'Плаќање ' . ($bp->payment_number ?: 'БП-' . $bp->id),
                'debit' => 0,
                'credit' => (int) $bp->amount,
                'source_type' => 'bill_payment',
                'source_id' => $bp->id,
            ];
        }

        return $this->buildResult($entries, $supplier, 'supplier', $asOfDate);
    }

    /**
     * Generate PDF for customer or supplier IOS.
     */
    public function generatePdf(string $type, int $companyId, int $partnerId, string $asOfDate): string
    {
        if ($type === 'customer') {
            $data = $this->generateForCustomer($companyId, $partnerId, $asOfDate);
        } else {
            $data = $this->generateForSupplier($companyId, $partnerId, $asOfDate);
        }

        $company = Company::with('address')->find($companyId);
        $currency = Currency::find(CompanySetting::getSetting('currency', $companyId));

        $pdf = PDF::loadView('app.pdf.reports.ios', [
            'company' => $company,
            'currency' => $currency,
            'data' => $data,
            'type' => $type,
            'report_period' => Carbon::parse($asOfDate)->format('d.m.Y'),
        ]);

        return $pdf->output();
    }

    /**
     * Build the result array from entries.
     */
    private function buildResult(array $entries, $partner, string $type, string $asOfDate): array
    {
        // Sort chronologically
        usort($entries, function ($a, $b) {
            $dateCompare = strcmp($a['date'], $b['date']);
            if ($dateCompare !== 0) {
                return $dateCompare;
            }
            // Debits before credits on same date
            return $b['debit'] <=> $a['debit'];
        });

        // Calculate running balance
        $runningBalance = 0;
        $numbered = [];
        foreach ($entries as $i => $entry) {
            $runningBalance += $entry['debit'] - $entry['credit'];
            $entry['balance'] = $runningBalance;
            $entry['number'] = $i + 1;
            $numbered[] = $entry;
        }

        $totalDebit = array_sum(array_column($entries, 'debit'));
        $totalCredit = array_sum(array_column($entries, 'credit'));
        $netBalance = $totalDebit - $totalCredit;

        return [
            'partner' => [
                'id' => $partner->id,
                'name' => $partner->name,
                'vat_number' => $partner->vat_number ?? null,
                'tax_id' => $partner->tax_id ?? null,
                'email' => $partner->email ?? null,
                'address' => $this->getPartnerAddress($partner),
            ],
            'type' => $type,
            'as_of_date' => $asOfDate,
            'as_of_date_formatted' => Carbon::parse($asOfDate)->format('d.m.Y'),
            'entries' => $numbered,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'net_balance' => $netBalance,
            'balance_in_our_favor' => $netBalance > 0,
            'balance_formatted_label' => $netBalance >= 0
                ? 'Салдо во наша корист'
                : 'Салдо во Ваша корист',
            'balance_abs' => abs($netBalance),
        ];
    }

    /**
     * Get partner address string.
     */
    private function getPartnerAddress($partner): string
    {
        $parts = [];

        if (method_exists($partner, 'billingAddress') && $partner->billingAddress) {
            $addr = $partner->billingAddress;
            if ($addr->address_street_1) {
                $parts[] = $addr->address_street_1;
            }
            if ($addr->city) {
                $parts[] = $addr->city;
            }
            if ($addr->zip) {
                $parts[] = $addr->zip;
            }
        } elseif (isset($partner->address_street_1)) {
            if ($partner->address_street_1) {
                $parts[] = $partner->address_street_1;
            }
            if ($partner->city) {
                $parts[] = $partner->city;
            }
            if ($partner->zip) {
                $parts[] = $partner->zip;
            }
        }

        return implode(', ', $parts);
    }

    /**
     * Return empty result structure.
     */
    private function emptyResult(string $type, string $asOfDate): array
    {
        return [
            'partner' => null,
            'type' => $type,
            'as_of_date' => $asOfDate,
            'as_of_date_formatted' => Carbon::parse($asOfDate)->format('d.m.Y'),
            'entries' => [],
            'total_debit' => 0,
            'total_credit' => 0,
            'net_balance' => 0,
            'balance_in_our_favor' => true,
            'balance_formatted_label' => '',
            'balance_abs' => 0,
        ];
    }
}
// CLAUDE-CHECKPOINT
