<?php

namespace Modules\Mk\Services;

use App\Models\Invoice;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Invoice-Transaction Matcher Service
 *
 * Automatically matches bank transactions with invoices based on amount, date, and reference
 * Updates invoice status to PAID when match is found and confirmed
 */
class Matcher
{
    protected $companyId;

    protected $matchingWindow; // days to look for matching transactions

    protected $amountTolerance; // percentage tolerance for amount matching

    public function __construct(int $companyId, int $matchingWindow = 7, float $amountTolerance = 0.01)
    {
        $this->companyId = $companyId;
        $this->matchingWindow = $matchingWindow;
        $this->amountTolerance = $amountTolerance;
    }

    /**
     * Match all unmatched bank transactions with unpaid invoices
     */
    public function matchAllTransactions(): array
    {
        Log::info('Starting transaction matching process', [
            'company_id' => $this->companyId,
            'matching_window' => $this->matchingWindow,
            'amount_tolerance' => $this->amountTolerance,
        ]);

        $unmatchedTransactions = $this->getUnmatchedTransactions();
        $unpaidInvoices = $this->getUnpaidInvoices();

        $matches = [];
        $totalMatched = 0;

        foreach ($unmatchedTransactions as $transaction) {
            $matchedInvoice = $this->findMatchingInvoice($transaction, $unpaidInvoices);

            if ($matchedInvoice) {
                $success = $this->createPaymentRecord($transaction, $matchedInvoice);

                if ($success) {
                    $matches[] = [
                        'transaction_id' => $transaction->id,
                        'invoice_id' => $matchedInvoice->id,
                        'amount' => $transaction->amount,
                        'confidence' => $this->calculateMatchConfidence($transaction, $matchedInvoice),
                    ];

                    $totalMatched++;

                    // Remove matched invoice from pool to avoid duplicate matching
                    $unpaidInvoices = $unpaidInvoices->reject(function ($invoice) use ($matchedInvoice) {
                        return $invoice->id === $matchedInvoice->id;
                    });
                }
            }
        }

        Log::info('Transaction matching completed', [
            'company_id' => $this->companyId,
            'total_matched' => $totalMatched,
            'matches' => $matches,
        ]);

        return $matches;
    }

    /**
     * Match a specific transaction with invoices (creates payment)
     */
    public function matchTransaction($transaction): ?array
    {
        $unpaidInvoices = $this->getUnpaidInvoices();
        $matchedInvoice = $this->findMatchingInvoice($transaction, $unpaidInvoices);

        if ($matchedInvoice) {
            $success = $this->createPaymentRecord($transaction, $matchedInvoice);

            if ($success) {
                return [
                    'transaction_id' => $transaction->id,
                    'invoice_id' => $matchedInvoice->id,
                    'amount' => $transaction->amount,
                    'confidence' => $this->calculateMatchConfidence($transaction, $matchedInvoice),
                    'invoice_number' => $matchedInvoice->invoice_number,
                    'invoice_total' => (float) $matchedInvoice->total,
                ];
            }
        }

        return null;
    }

    /**
     * Suggest a match for a transaction without creating a payment
     * Used for displaying suggested matches in the UI
     */
    public function suggestMatch($transaction): ?array
    {
        $unpaidInvoices = $this->getUnpaidInvoices();
        $matchedInvoice = $this->findMatchingInvoice($transaction, $unpaidInvoices);

        if ($matchedInvoice) {
            return [
                'transaction_id' => $transaction->id,
                'invoice_id' => $matchedInvoice->id,
                'amount' => (float) $transaction->amount,
                'confidence' => $this->calculateMatchConfidence($transaction, $matchedInvoice),
                'invoice_number' => $matchedInvoice->invoice_number,
                'invoice_total' => (float) $matchedInvoice->total,
            ];
        }

        return null;
    }

    /**
     * Get unmatched bank transactions
     */
    protected function getUnmatchedTransactions()
    {
        return DB::table('bank_transactions')
            ->where('company_id', $this->companyId)
            ->whereNull('matched_invoice_id')
            ->where('amount', '>', 0) // Only incoming payments
            ->where('transaction_date', '>=', Carbon::now()->subDays($this->matchingWindow))
            ->orderBy('transaction_date', 'desc')
            ->get();
    }

    /**
     * Get unpaid invoices
     */
    protected function getUnpaidInvoices()
    {
        return Invoice::where('company_id', $this->companyId)
            ->where('status', 'SENT')
            ->where('due_date', '>=', Carbon::now()->subDays($this->matchingWindow * 2))
            ->orderBy('due_date', 'desc')
            ->get();
    }

    /**
     * Find matching invoice for a transaction
     */
    protected function findMatchingInvoice($transaction, $invoices): ?Invoice
    {
        $bestMatch = null;
        $bestScore = 0;

        foreach ($invoices as $invoice) {
            $score = $this->calculateMatchScore($transaction, $invoice);

            if ($score > $bestScore && $score >= 0.7) { // Minimum 70% confidence
                $bestScore = $score;
                $bestMatch = $invoice;
            }
        }

        return $bestMatch;
    }

    /**
     * Calculate match score between transaction and invoice
     *
     * @deprecated Use calculateConfidenceScore instead
     */
    protected function calculateMatchScore($transaction, Invoice $invoice): float
    {
        return $this->calculateConfidenceScore($transaction, $invoice);
    }

    /**
     * Calculate confidence score for matching a bank transaction with an invoice
     * Returns a score from 0.0 to 1.0 based on multiple factors
     *
     * Scoring factors:
     * - Exact amount match: +0.4
     * - Date within ±3 days: +0.2
     * - Fuzzy description match (Levenshtein): +0.2
     * - Reference/invoice number in description: +0.3
     * - Customer IBAN match: +0.1
     *
     * @param  object  $bankTxn  Bank transaction object
     * @param  Invoice  $invoice  Invoice model instance
     * @return float Score from 0.0 to 1.0
     */
    public function calculateConfidenceScore($bankTxn, Invoice $invoice): float
    {
        $score = 0.0;

        // 1. Exact amount match: +0.4
        $amountDiff = abs($bankTxn->amount - $invoice->total);
        if ($amountDiff == 0) {
            $score += 0.4;
        } elseif ($invoice->total > 0 && ($amountDiff / $invoice->total) <= 0.01) {
            // Within 1% tolerance
            $score += 0.35;
        }

        // 2. Date within ±3 days: +0.2
        $transactionDate = Carbon::parse($bankTxn->transaction_date);
        $invoiceDate = Carbon::parse($invoice->invoice_date);
        $dueDate = Carbon::parse($invoice->due_date);

        $daysDiffInvoice = abs($transactionDate->diffInDays($invoiceDate));
        $daysDiffDue = abs($transactionDate->diffInDays($dueDate));
        $minDaysDiff = min($daysDiffInvoice, $daysDiffDue);

        if ($minDaysDiff <= 3) {
            $score += 0.2 - ($minDaysDiff * 0.05); // Gradually decrease score
        } elseif ($minDaysDiff <= 7) {
            $score += 0.1;
        }

        // 3. Reference/invoice number in description: +0.3
        $description = strtolower($bankTxn->description ?? '');
        $remittanceInfo = strtolower($bankTxn->remittance_info ?? '');
        $invoiceNumber = strtolower($invoice->invoice_number);

        if (strpos($description, $invoiceNumber) !== false ||
            strpos($remittanceInfo, $invoiceNumber) !== false) {
            $score += 0.3;
        } else {
            // Check for partial match (last 4+ digits)
            $invoiceDigits = preg_replace('/[^0-9]/', '', $invoiceNumber);
            if (strlen($invoiceDigits) >= 4) {
                $lastFourDigits = substr($invoiceDigits, -4);
                if (strpos($description, $lastFourDigits) !== false ||
                    strpos($remittanceInfo, $lastFourDigits) !== false) {
                    $score += 0.15;
                }
            }
        }

        // 4. Fuzzy description match (Levenshtein): +0.2
        if ($invoice->customer && $invoice->customer->name) {
            $customerName = strtolower($invoice->customer->name);
            $debtorName = strtolower($bankTxn->debtor_name ?? '');
            $creditorName = strtolower($bankTxn->creditor_name ?? '');

            // Use the appropriate counterparty name based on transaction direction
            $counterpartyName = $bankTxn->amount > 0 ? $debtorName : $creditorName;

            if (strlen($customerName) > 0 && strlen($counterpartyName) > 0) {
                // Calculate Levenshtein distance
                $maxLen = max(strlen($customerName), strlen($counterpartyName));
                $distance = levenshtein($customerName, $counterpartyName);
                $similarity = 1 - ($distance / $maxLen);

                if ($similarity >= 0.8) {
                    $score += 0.2;
                } elseif ($similarity >= 0.6) {
                    $score += 0.15;
                } elseif ($similarity >= 0.4) {
                    $score += 0.1;
                }
            }
        }

        // 5. Customer IBAN match: +0.1
        if ($invoice->customer) {
            $customerIban = strtolower($invoice->customer->iban ?? '');
            $debtorIban = strtolower($bankTxn->debtor_iban ?? '');
            $creditorIban = strtolower($bankTxn->creditor_iban ?? '');

            if (strlen($customerIban) > 0) {
                // Remove spaces from IBANs for comparison
                $customerIban = str_replace(' ', '', $customerIban);
                $debtorIban = str_replace(' ', '', $debtorIban);
                $creditorIban = str_replace(' ', '', $creditorIban);

                if ($customerIban === $debtorIban || $customerIban === $creditorIban) {
                    $score += 0.1;
                }
            }
        }

        // Ensure score doesn't exceed 1.0
        return min($score, 1.0);
    }

    // CLAUDE-CHECKPOINT

    /**
     * Calculate amount matching score
     */
    protected function calculateAmountScore(float $transactionAmount, float $invoiceAmount): float
    {
        if ($invoiceAmount == 0) {
            return 0;
        }

        $difference = abs($transactionAmount - $invoiceAmount) / $invoiceAmount;

        if ($difference <= $this->amountTolerance) {
            return 1.0; // Perfect match
        } elseif ($difference <= 0.05) {
            return 0.8; // Very close
        } elseif ($difference <= 0.1) {
            return 0.5; // Moderately close
        }

        return 0; // Too different
    }

    /**
     * Calculate date proximity score
     */
    protected function calculateDateScore(string $transactionDate, string $invoiceDueDate): float
    {
        $transactionCarbon = Carbon::parse($transactionDate);
        $dueDateCarbon = Carbon::parse($invoiceDueDate);

        $daysDifference = abs($transactionCarbon->diffInDays($dueDateCarbon));

        if ($daysDifference <= 1) {
            return 1.0; // Same day or next day
        } elseif ($daysDifference <= 3) {
            return 0.8; // Within 3 days
        } elseif ($daysDifference <= 7) {
            return 0.6; // Within a week
        } elseif ($daysDifference <= 14) {
            return 0.3; // Within two weeks
        }

        return 0.1; // More than two weeks
    }

    /**
     * Calculate reference matching score
     */
    protected function calculateReferenceScore($transaction, Invoice $invoice): float
    {
        $description = strtolower($transaction->description ?? '');
        $remittanceInfo = strtolower($transaction->remittance_info ?? '');
        $invoiceNumber = strtolower($invoice->invoice_number);

        // Check if invoice number appears in transaction description or remittance
        if (strpos($description, $invoiceNumber) !== false ||
            strpos($remittanceInfo, $invoiceNumber) !== false) {
            return 1.0; // Perfect reference match
        }

        // Check for partial matches (last 4 digits, etc.)
        $invoiceDigits = preg_replace('/[^0-9]/', '', $invoiceNumber);
        if (strlen($invoiceDigits) >= 4) {
            $lastFourDigits = substr($invoiceDigits, -4);
            if (strpos($description, $lastFourDigits) !== false ||
                strpos($remittanceInfo, $lastFourDigits) !== false) {
                return 0.7; // Partial reference match
            }
        }

        // Check customer name matching
        if ($invoice->customer) {
            $customerName = strtolower($invoice->customer->name);
            $transactionCreditor = strtolower($transaction->creditor_name ?? '');

            if (strpos($transactionCreditor, $customerName) !== false ||
                strpos($customerName, $transactionCreditor) !== false) {
                return 0.5; // Customer name match
            }
        }

        return 0; // No reference match
    }

    /**
     * Create payment record and mark invoice as paid
     */
    protected function createPaymentRecord($transaction, Invoice $invoice): bool
    {
        try {
            DB::beginTransaction();

            // Create payment record
            $payment = Payment::create([
                'company_id' => $this->companyId,
                'invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'amount' => $transaction->amount,
                'currency_id' => $invoice->currency_id,
                'payment_date' => Carbon::parse($transaction->transaction_date)->format('Y-m-d'),
                'payment_number' => $this->generatePaymentNumber(),
                'payment_method' => 'bank_transfer',
                'notes' => 'Auto-matched from bank transaction: '.$transaction->external_reference,
                'reference' => $transaction->external_reference,
            ]);

            // Update invoice status
            $invoice->update([
                'status' => 'PAID',
                'paid_status' => Payment::STATUS_COMPLETED,
                'payment_date' => $payment->payment_date,
            ]);

            // Mark transaction as matched
            DB::table('bank_transactions')
                ->where('id', $transaction->id)
                ->update([
                    'matched_invoice_id' => $invoice->id,
                    'matched_payment_id' => $payment->id,
                    'matched_at' => now(),
                    'updated_at' => now(),
                ]);

            DB::commit();

            Log::info('Invoice automatically paid via bank transaction match', [
                'invoice_id' => $invoice->id,
                'payment_id' => $payment->id,
                'transaction_id' => $transaction->id,
                'amount' => $transaction->amount,
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create payment from transaction match', [
                'invoice_id' => $invoice->id,
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Calculate match confidence for reporting
     */
    protected function calculateMatchConfidence($transaction, Invoice $invoice): float
    {
        return round($this->calculateMatchScore($transaction, $invoice) * 100, 1);
    }

    /**
     * Generate unique payment number
     */
    protected function generatePaymentNumber(): string
    {
        $prefix = 'PAY-';
        $year = date('Y');
        $month = date('m');

        $lastPayment = Payment::where('company_id', $this->companyId)
            ->where('payment_number', 'like', $prefix.$year.$month.'%')
            ->orderBy('payment_number', 'desc')
            ->first();

        if ($lastPayment) {
            $lastNumber = intval(substr($lastPayment->payment_number, -4));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix.$year.$month.'-'.str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get matching statistics for a company
     */
    public function getMatchingStats(): array
    {
        $totalTransactions = DB::table('bank_transactions')
            ->where('company_id', $this->companyId)
            ->where('amount', '>', 0)
            ->count();

        $matchedTransactions = DB::table('bank_transactions')
            ->where('company_id', $this->companyId)
            ->where('amount', '>', 0)
            ->whereNotNull('matched_invoice_id')
            ->count();

        $matchRate = $totalTransactions > 0 ? ($matchedTransactions / $totalTransactions) * 100 : 0;

        return [
            'total_transactions' => $totalTransactions,
            'matched_transactions' => $matchedTransactions,
            'unmatched_transactions' => $totalTransactions - $matchedTransactions,
            'match_rate' => round($matchRate, 1),
        ];
    }
}
