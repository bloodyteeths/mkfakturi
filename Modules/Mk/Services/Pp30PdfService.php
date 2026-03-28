<?php

namespace Modules\Mk\Services;

use App\Models\BankAccount;
use App\Models\Bill;
use App\Models\Company;
use Modules\Mk\Models\PaymentBatch;
use PDF;

/**
 * PP30 PDF Service
 *
 * Generates printable PP30 payment slip PDFs for:
 * - Single bills (direct from bill view)
 * - Payment batches (multi-page, one slip per item)
 */
class Pp30PdfService
{
    protected AmountToWordsService $amountToWordsService;

    public function __construct(?AmountToWordsService $amountToWordsService = null)
    {
        $this->amountToWordsService = $amountToWordsService ?? new AmountToWordsService;
    }

    /**
     * Macedonian bank code mapping (IBAN positions 5-7).
     */
    private const BANK_CODES = [
        '210' => 'Комерцијална Банка',
        '250' => 'НЛБ Банка',
        '270' => 'Халк Банка',
        '300' => 'Стопанска Банка',
        '380' => 'УНИ Банка',
        '500' => 'Шпаркасе Банка',
        '530' => 'ПроКредит Банка',
        '290' => 'Силк Роуд Банка',
        '320' => 'ТТК Банка',
    ];

    /**
     * Generate PP30 PDF for a single bill.
     */
    public function generateForBill(Bill $bill, Company $company, ?BankAccount $bankAccount = null)
    {
        $bill->loadMissing(['supplier', 'currency']);

        if (! $bankAccount) {
            $bankAccount = $this->getDefaultBankAccount($company);
        }

        $paidAmount = $bill->payments()->sum('amount');
        $dueAmount = (int) ($bill->total - $paidAmount);
        if ($dueAmount <= 0) {
            $dueAmount = (int) $bill->total;
        }

        $slip = $this->buildSlip(
            $company->name ?? '',
            $bankAccount?->iban ?? $bankAccount?->account_number ?? '',
            $bankAccount?->bank_name ?? '',
            $bill->supplier?->name ?? 'Unknown',
            $bill->supplier?->iban ?? '',
            $bill->supplier?->bank_name ?? '',
            $dueAmount,
            $bill->currency?->code ?? 'MKD',
            '',
            '',
            $bill->bill_number ? "Плаќање по фактура {$bill->bill_number}" : 'Плаќање по фактура',
            now()->format('d.m.Y'),
            $bill->bill_number ?? ''
        );

        view()->share(['slips' => [$slip]]);

        return PDF::loadView('app.pdf.reports.pp30');
    }

    /**
     * Generate PP30 PDF for an entire payment batch (one slip per item).
     */
    public function generateForBatch(PaymentBatch $batch)
    {
        $batch->load(['items', 'company', 'bankAccount']);

        $company = $batch->company;
        $bankAccount = $batch->bankAccount ?? $this->getDefaultBankAccount($company);

        $slips = [];

        foreach ($batch->items as $item) {
            $slips[] = $this->buildSlip(
                $company->name ?? '',
                $bankAccount?->iban ?? $bankAccount?->account_number ?? '',
                $bankAccount?->bank_name ?? '',
                $item->creditor_name,
                $item->creditor_iban ?? '',
                $item->creditor_bank_name ?? '',
                (int) $item->amount,
                $item->currency_code ?? 'MKD',
                $item->purpose_code ?? '',
                $item->payment_reference ?? '',
                $item->description ?? 'Плаќање',
                $batch->batch_date ? $batch->batch_date->format('d.m.Y') : now()->format('d.m.Y'),
                $item->bill?->bill_number ?? ''
            );
        }

        if (empty($slips)) {
            throw new \Exception('No items in payment batch.');
        }

        view()->share(['slips' => $slips]);

        return PDF::loadView('app.pdf.reports.pp30');
    }

    /**
     * Build a single slip data array.
     */
    protected function buildSlip(
        string $debtorName,
        string $debtorIban,
        string $debtorBank,
        string $creditorName,
        string $creditorIban,
        string $creditorBank,
        int $amountCents,
        string $currencyCode,
        string $purposeCode,
        string $paymentReference,
        string $description,
        string $date,
        string $billNumber
    ): array {
        // Auto-detect bank name from IBAN if not provided
        if (empty($debtorBank) && ! empty($debtorIban)) {
            $debtorBank = $this->getBankNameFromIban($debtorIban);
        }
        if (empty($creditorBank) && ! empty($creditorIban)) {
            $creditorBank = $this->getBankNameFromIban($creditorIban);
        }

        return [
            'debtor_name' => $debtorName,
            'debtor_iban' => $this->formatIban($debtorIban),
            'debtor_bank' => $debtorBank,
            'creditor_name' => $creditorName,
            'creditor_iban' => $this->formatIban($creditorIban),
            'creditor_bank' => $creditorBank,
            'amount' => $amountCents,
            'amount_formatted' => number_format($amountCents / 100, 2, ',', '.'),
            'amount_words' => $this->amountToWords($amountCents),
            'currency_code' => $currencyCode,
            'purpose_code' => $purposeCode,
            'payment_reference' => $paymentReference,
            'description' => $description,
            'date' => $date,
            'bill_number' => $billNumber,
        ];
    }

    /**
     * Convert amount in cents to Macedonian words.
     *
     * Delegates to shared AmountToWordsService.
     * e.g. 1500000 → "петнаесет илјади денари и 00 дени"
     */
    public function amountToWords(int $amountCents): string
    {
        return $this->amountToWordsService->convert($amountCents, 'MKD');
    }

    /**
     * Format IBAN with spaces every 4 characters.
     * MK07210001234567890 → MK07 2100 0123 4567 890
     */
    public function formatIban(string $iban): string
    {
        $iban = strtoupper(str_replace(' ', '', $iban));

        if (empty($iban)) {
            return '';
        }

        return trim(chunk_split($iban, 4, ' '));
    }

    /**
     * Extract bank name from Macedonian IBAN based on bank code (positions 5-7).
     */
    public function getBankNameFromIban(string $iban): string
    {
        $iban = strtoupper(str_replace(' ', '', $iban));

        if (! str_starts_with($iban, 'MK') || strlen($iban) < 7) {
            return '';
        }

        $bankCode = substr($iban, 4, 3);

        return self::BANK_CODES[$bankCode] ?? '';
    }

    /**
     * Generate PP30 from a bank transaction (pre-fill from transaction data).
     */
    public function generateFromTransaction(\App\Models\BankTransaction $transaction, Company $company, ?BankAccount $bankAccount = null)
    {
        if (! $bankAccount) {
            $bankAccount = $this->getDefaultBankAccount($company);
        }

        $slip = $this->buildSlip(
            $company->name ?? '',
            $bankAccount?->iban ?? $bankAccount?->account_number ?? '',
            $bankAccount?->bank_name ?? '',
            $transaction->creditor_name ?? $transaction->debtor_name ?? '',
            $transaction->creditor_iban ?? $transaction->debtor_iban ?? '',
            '',
            (int) round(abs((float) $transaction->amount) * 100),
            $transaction->currency ?? 'MKD',
            '',
            $transaction->transaction_reference ?? '',
            $transaction->description ?? 'Плаќање',
            $transaction->transaction_date
                ? \Carbon\Carbon::parse($transaction->transaction_date)->format('d.m.Y')
                : now()->format('d.m.Y'),
            ''
        );

        view()->share(['slips' => [$slip]]);

        return PDF::loadView('app.pdf.reports.pp30');
    }

    /**
     * Generate PP10 collection order PDF.
     */
    public function generatePp10(array $slips)
    {
        if (empty($slips)) {
            throw new \Exception('No slips provided for PP10.');
        }

        view()->share(['slips' => $slips]);

        return PDF::loadView('app.pdf.reports.pp10');
    }

    /**
     * Get the company's primary/default bank account.
     */
    protected function getDefaultBankAccount(Company $company): ?BankAccount
    {
        return BankAccount::where('company_id', $company->id)
            ->orderBy('id')
            ->first();
    }
}

// CLAUDE-CHECKPOINT
