<?php

namespace Modules\Mk\Services;

use App\Models\Company;
use App\Models\Expense;
use PDF;

/**
 * Rashoden Nalog (Cash Disbursement Voucher) Service
 *
 * Generates printable Расходен налог PDFs — the standard Macedonian
 * cash disbursement voucher used when paying cash out of the каса.
 * Counterpart to Приходен налог (Cash Receipt).
 */
class RashodenNalogService
{
    /**
     * Generate a Расходен налог PDF for an expense.
     *
     * @param Expense $expense
     * @param Company $company
     * @return \Barryvdh\DomPDF\PDF
     */
    public function generateForExpense(Expense $expense, Company $company)
    {
        $expense->loadMissing(['category', 'currency']);

        $voucher = $this->buildVoucher(
            company: $company,
            recipientName: $expense->supplier ?? $expense->category?->name ?? 'Неодредено',
            recipientAddress: '',
            recipientEdb: '',
            recipientEmbs: '',
            amountCents: (int) $expense->amount,
            purpose: $expense->notes ?? $expense->category?->name ?? 'Расход',
            referenceDocument: $expense->expense_number ?? '',
            debitAccount: $expense->debit_account ?? '',
            creditAccount: $expense->credit_account ?? '1000',
            date: $expense->expense_date
                ? $expense->expense_date->format('d.m.Y')
                : now()->format('d.m.Y'),
            number: $expense->expense_number ?? ('РН-' . $expense->id)
        );

        view()->share(['vouchers' => [$voucher]]);

        return PDF::loadView('app.pdf.reports.rashoden-nalog');
    }

    /**
     * Generate a Расходен налог PDF for a bill payment.
     *
     * @param \App\Models\BillPayment $billPayment
     * @param Company $company
     * @return \Barryvdh\DomPDF\PDF
     */
    public function generateForBillPayment($billPayment, Company $company)
    {
        $billPayment->loadMissing(['bill.supplier', 'bill.currency', 'paymentMode']);

        $bill = $billPayment->bill;
        $supplier = $bill?->supplier;

        $voucher = $this->buildVoucher(
            company: $company,
            recipientName: $supplier?->name ?? 'Неодредено',
            recipientAddress: $supplier?->address_street_1 ?? '',
            recipientEdb: $supplier?->edb ?? '',
            recipientEmbs: $supplier?->embs ?? '',
            amountCents: (int) $billPayment->amount,
            purpose: 'Плаќање по фактура ' . ($bill?->bill_number ?? ''),
            referenceDocument: $bill?->bill_number ?? '',
            debitAccount: '',
            creditAccount: '1000',
            date: $billPayment->payment_date
                ? $billPayment->payment_date->format('d.m.Y')
                : now()->format('d.m.Y'),
            number: 'РН-' . $billPayment->id
        );

        view()->share(['vouchers' => [$voucher]]);

        return PDF::loadView('app.pdf.reports.rashoden-nalog');
    }

    /**
     * Generate a generic Расходен налог PDF from raw data.
     *
     * @param array $data
     * @return \Barryvdh\DomPDF\PDF
     */
    public function generateGeneric(array $data)
    {
        $company = Company::find($data['company_id'] ?? 0);

        $voucher = $this->buildVoucher(
            company: $company,
            recipientName: $data['recipient_name'] ?? '',
            recipientAddress: $data['recipient_address'] ?? '',
            recipientEdb: $data['recipient_edb'] ?? '',
            recipientEmbs: $data['recipient_embs'] ?? '',
            amountCents: (int) ($data['amount'] ?? 0),
            purpose: $data['purpose'] ?? '',
            referenceDocument: $data['reference_document'] ?? '',
            debitAccount: $data['debit_account'] ?? '',
            creditAccount: $data['credit_account'] ?? '1000',
            date: $data['date'] ?? now()->format('d.m.Y'),
            number: $data['number'] ?? ('РН-' . time())
        );

        view()->share(['vouchers' => [$voucher]]);

        return PDF::loadView('app.pdf.reports.rashoden-nalog');
    }

    /**
     * Build a voucher data array.
     */
    protected function buildVoucher(
        ?Company $company,
        string $recipientName,
        string $recipientAddress,
        string $recipientEdb,
        string $recipientEmbs,
        int $amountCents,
        string $purpose,
        string $referenceDocument,
        string $debitAccount,
        string $creditAccount,
        string $date,
        string $number
    ): array {
        $pp30Service = app(Pp30PdfService::class);

        return [
            'company_name' => $company?->name ?? '',
            'company_address' => $company?->address_street_1 ?? '',
            'company_edb' => $company?->edb ?? '',
            'company_embs' => $company?->embs ?? '',
            'company_logo' => '',
            'number' => $number,
            'date' => $date,
            'recipient_name' => $recipientName,
            'recipient_address' => $recipientAddress,
            'recipient_edb' => $recipientEdb,
            'recipient_embs' => $recipientEmbs,
            'purpose' => $purpose,
            'reference_document' => $referenceDocument,
            'debit_account' => $debitAccount,
            'credit_account' => $creditAccount,
            'amount' => $amountCents,
            'amount_formatted' => number_format($amountCents / 100, 2, ',', '.'),
            'amount_words' => $pp30Service->amountToWords($amountCents),
        ];
    }
}

// CLAUDE-CHECKPOINT
