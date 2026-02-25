<?php

namespace App\Http\Controllers\V1\Admin\Customer;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\BillPayment;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CustomerLedgerController extends Controller
{
    /**
     * Unified ledger card for a customer (and its linked supplier).
     */
    public function forCustomer(Request $request, Customer $customer)
    {
        $supplier = $customer->linkedSupplier;

        return $this->buildLedger($request, $customer, $supplier);
    }

    /**
     * Unified ledger card for a supplier (and its linked customer).
     */
    public function forSupplier(Request $request, Supplier $supplier)
    {
        $customer = $supplier->linkedCustomer;

        return $this->buildLedger($request, $customer, $supplier);
    }

    private function buildLedger(Request $request, ?Customer $customer, ?Supplier $supplier)
    {
        $fromDate = $request->query('from_date')
            ? Carbon::parse($request->query('from_date'))
            : Carbon::now()->startOfYear();
        $toDate = $request->query('to_date')
            ? Carbon::parse($request->query('to_date'))
            : Carbon::now()->endOfYear();

        $entries = collect();

        // AR side: Invoices and Payments from customer
        if ($customer) {
            $invoices = Invoice::where('customer_id', $customer->id)
                ->whereBetween('invoice_date', [$fromDate, $toDate])
                ->get();

            foreach ($invoices as $invoice) {
                $entries->push([
                    'date' => $invoice->invoice_date,
                    'type' => 'invoice',
                    'reference' => $invoice->invoice_number,
                    'description' => 'Invoice #' . $invoice->invoice_number,
                    'debit' => $invoice->total,
                    'credit' => 0,
                    'side' => 'AR',
                    'id' => $invoice->id,
                ]);
            }

            $payments = Payment::where('customer_id', $customer->id)
                ->whereBetween('payment_date', [$fromDate, $toDate])
                ->get();

            foreach ($payments as $payment) {
                $entries->push([
                    'date' => $payment->payment_date,
                    'type' => 'payment',
                    'reference' => $payment->payment_number ?? 'PAY-' . $payment->id,
                    'description' => 'Payment received',
                    'debit' => 0,
                    'credit' => $payment->amount,
                    'side' => 'AR',
                    'id' => $payment->id,
                ]);
            }
        }

        // AP side: Bills and BillPayments from supplier
        if ($supplier) {
            $bills = Bill::where('supplier_id', $supplier->id)
                ->whereBetween('bill_date', [$fromDate, $toDate])
                ->get();

            foreach ($bills as $bill) {
                $entries->push([
                    'date' => $bill->bill_date,
                    'type' => 'bill',
                    'reference' => $bill->bill_number,
                    'description' => 'Bill #' . $bill->bill_number,
                    'debit' => 0,
                    'credit' => $bill->total,
                    'side' => 'AP',
                    'id' => $bill->id,
                ]);
            }

            $billPayments = BillPayment::whereHas('bill', function ($q) use ($supplier) {
                $q->where('supplier_id', $supplier->id);
            })
                ->whereBetween('payment_date', [$fromDate, $toDate])
                ->with('bill:id,bill_number')
                ->get();

            foreach ($billPayments as $bp) {
                $entries->push([
                    'date' => $bp->payment_date,
                    'type' => 'bill_payment',
                    'reference' => $bp->bill ? $bp->bill->bill_number : 'BP-' . $bp->id,
                    'description' => 'Payment to supplier',
                    'debit' => $bp->amount,
                    'credit' => 0,
                    'side' => 'AP',
                    'id' => $bp->id,
                ]);
            }
        }

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

        return response()->json([
            'data' => $ledger->values(),
            'meta' => [
                'from_date' => $fromDate->toDateString(),
                'to_date' => $toDate->toDateString(),
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'closing_balance' => $runningBalance,
                'customer_id' => $customer?->id,
                'supplier_id' => $supplier?->id,
            ],
        ]);
    }
}
