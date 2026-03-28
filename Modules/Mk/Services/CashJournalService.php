<?php

namespace Modules\Mk\Services;

use App\Models\BillPayment;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Currency;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\PaymentMethod;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CashJournalService
{
    /**
     * Cash account codes per Правилник 174/2011.
     * 100 = Парични средства на трансакциски сметки (bank - excluded)
     * 101 = Парични средства во благајна - денари
     * 102 = Парични средства во благајна
     * 104 = Парични средства во благајна во странска валута
     */
    private const CASH_ACCOUNT_CODES = ['101', '102', '104'];

    /**
     * Generate cash journal data for a date range.
     */
    public function generate(int $companyId, string $fromDate, string $toDate): array
    {
        $cashMethodIds = $this->getCashPaymentMethodIds($companyId);

        if (empty($cashMethodIds)) {
            // Fallback: use payment mode CASH
            $cashMethodIds = $this->getCashPaymentMethodIdsByMode($companyId);
        }

        $openingBalance = $this->getOpeningBalance($companyId, $fromDate, $cashMethodIds);

        $entries = [];

        // Income entries: Payments (AR) with cash payment methods
        $payments = Payment::where('company_id', $companyId)
            ->whereBetween('payment_date', [$fromDate, $toDate])
            ->whereIn('payment_method_id', $cashMethodIds)
            ->without(['customer', 'paymentMethod', 'currency', 'company'])
            ->select(['id', 'payment_date', 'payment_number', 'amount', 'notes', 'payment_method_id', 'customer_id', 'invoice_id'])
            ->with(['customer:id,name', 'invoice:id,invoice_number'])
            ->orderBy('payment_date')
            ->orderBy('id')
            ->get();

        foreach ($payments as $payment) {
            $customerName = $payment->customer->name ?? '';
            $invoiceNum = $payment->invoice->invoice_number ?? '';
            $description = 'Наплата';
            if ($customerName) {
                $description .= ' - ' . $customerName;
            }

            $entries[] = [
                'date' => Carbon::parse($payment->payment_date)->format('d.m.Y'),
                'date_sort' => $payment->payment_date,
                'description' => $description,
                'document_ref' => $payment->payment_number ?: ($invoiceNum ? 'ПН-' . $invoiceNum : ''),
                'income' => (int) $payment->amount,
                'expense' => 0,
                'type' => 'income',
            ];
        }

        // Expense entries: Expenses with cash payment methods
        $expenses = Expense::where('company_id', $companyId)
            ->whereBetween('expense_date', [$fromDate, $toDate])
            ->whereIn('payment_method_id', $cashMethodIds)
            ->select(['id', 'expense_date', 'amount', 'notes', 'expense_category_id', 'invoice_number', 'payment_method_id'])
            ->with(['category:id,name'])
            ->orderBy('expense_date')
            ->orderBy('id')
            ->get();

        foreach ($expenses as $expense) {
            $categoryName = $expense->category->name ?? '';
            $description = $categoryName ?: ($expense->notes ?: 'Расход');

            $entries[] = [
                'date' => Carbon::parse($expense->expense_date)->format('d.m.Y'),
                'date_sort' => $expense->expense_date,
                'description' => $description,
                'document_ref' => $expense->invoice_number ?: ('РН-' . $expense->id),
                'income' => 0,
                'expense' => (int) $expense->amount,
                'type' => 'expense',
            ];
        }

        // Expense entries: Bill Payments with cash payment methods
        $billPayments = BillPayment::where('company_id', $companyId)
            ->whereBetween('payment_date', [$fromDate, $toDate])
            ->whereIn('payment_method_id', $cashMethodIds)
            ->select(['id', 'payment_date', 'amount', 'payment_number', 'bill_id', 'payment_method_id'])
            ->with(['bill:id,bill_number,supplier_id', 'bill.supplier:id,name'])
            ->orderBy('payment_date')
            ->orderBy('id')
            ->get();

        foreach ($billPayments as $bp) {
            $supplierName = $bp->bill->supplier->name ?? '';
            $billNumber = $bp->bill->bill_number ?? '';
            $description = 'Плаќање';
            if ($supplierName) {
                $description .= ' - ' . $supplierName;
            }

            $entries[] = [
                'date' => Carbon::parse($bp->payment_date)->format('d.m.Y'),
                'date_sort' => $bp->payment_date,
                'description' => $description,
                'document_ref' => $bp->payment_number ?: ($billNumber ? 'БП-' . $billNumber : ''),
                'income' => 0,
                'expense' => (int) $bp->amount,
                'type' => 'expense',
            ];
        }

        // Sort by date, then income first
        usort($entries, function ($a, $b) {
            $dateCompare = strcmp($a['date_sort'], $b['date_sort']);
            if ($dateCompare !== 0) {
                return $dateCompare;
            }
            // Income before expense on same day
            return strcmp($a['type'], $b['type']);
        });

        // Number entries and remove sort key
        $numbered = [];
        foreach ($entries as $i => $entry) {
            unset($entry['date_sort'], $entry['type']);
            $entry['number'] = $i + 1;
            $numbered[] = $entry;
        }

        $totalIncome = array_sum(array_column($entries, 'income'));
        $totalExpense = array_sum(array_column($entries, 'expense'));
        $closingBalance = $openingBalance + $totalIncome - $totalExpense;

        // Generate report number
        $reportNumber = $this->generateReportNumber($companyId, $fromDate);

        return [
            'opening_balance' => $openingBalance,
            'entries' => $numbered,
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'closing_balance' => $closingBalance,
            'report_number' => $reportNumber,
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ];
    }

    /**
     * Generate PDF string for cash journal.
     */
    public function generatePdf(int $companyId, string $fromDate, string $toDate): string
    {
        $data = $this->generate($companyId, $fromDate, $toDate);
        $company = Company::with('address')->find($companyId);
        $currency = Currency::find(CompanySetting::getSetting('currency', $companyId));

        $pdf = PDF::loadView('app.pdf.reports.blagajnicki-izvestaj', [
            'company' => $company,
            'currency' => $currency,
            'data' => $data,
            'report_period' => Carbon::parse($fromDate)->format('d.m.Y') . ' - ' . Carbon::parse($toDate)->format('d.m.Y'),
        ]);

        return $pdf->output();
    }

    /**
     * Get opening balance: net cash position before the from_date.
     */
    private function getOpeningBalance(int $companyId, string $beforeDate, array $cashMethodIds): int
    {
        if (empty($cashMethodIds)) {
            return 0;
        }

        // Total cash income before period
        $totalIncome = (int) Payment::where('company_id', $companyId)
            ->where('payment_date', '<', $beforeDate)
            ->whereIn('payment_method_id', $cashMethodIds)
            ->sum('amount');

        // Total cash expenses before period
        $totalExpenses = (int) Expense::where('company_id', $companyId)
            ->where('expense_date', '<', $beforeDate)
            ->whereIn('payment_method_id', $cashMethodIds)
            ->sum('amount');

        // Total bill payments (cash) before period
        $totalBillPayments = (int) BillPayment::where('company_id', $companyId)
            ->where('payment_date', '<', $beforeDate)
            ->whereIn('payment_method_id', $cashMethodIds)
            ->sum('amount');

        return $totalIncome - $totalExpenses - $totalBillPayments;
    }

    /**
     * Get payment method IDs that have cash account codes.
     */
    private function getCashPaymentMethodIds(int $companyId): array
    {
        return PaymentMethod::where('company_id', $companyId)
            ->whereIn('account_code', self::CASH_ACCOUNT_CODES)
            ->pluck('id')
            ->toArray();
    }

    /**
     * Fallback: get payment methods by CASH mode.
     */
    private function getCashPaymentMethodIdsByMode(int $companyId): array
    {
        return PaymentMethod::where('company_id', $companyId)
            ->where(function ($q) {
                $q->where('name', 'LIKE', '%cash%')
                    ->orWhere('name', 'LIKE', '%Cash%')
                    ->orWhere('name', 'LIKE', '%благајна%')
                    ->orWhere('name', 'LIKE', '%Благајна%')
                    ->orWhere('name', 'LIKE', '%готовина%')
                    ->orWhere('name', 'LIKE', '%Готовина%');
            })
            ->pluck('id')
            ->toArray();
    }

    /**
     * Generate report number based on period.
     */
    private function generateReportNumber(int $companyId, string $fromDate): string
    {
        $date = Carbon::parse($fromDate);

        return 'БИ-' . $date->format('Y') . '-' . str_pad($date->month, 2, '0', STR_PAD_LEFT);
    }
}
// CLAUDE-CHECKPOINT
