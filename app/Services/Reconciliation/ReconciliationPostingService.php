<?php

namespace App\Services\Reconciliation;

use App\Events\ReconciliationMatched;
use App\Events\ReconciliationPosted;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Reconciliation;
use App\Services\SerialNumberFormatter;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Vinkla\Hashids\Facades\Hashids;

/**
 * Reconciliation Posting Service
 *
 * Posts matched reconciliations as Payment records with full idempotency.
 * When a bank transaction is matched to an invoice, this service:
 *
 * 1. Validates the transaction is a credit (incoming money)
 * 2. Validates currency compatibility with the invoice
 * 3. Creates a Payment record using DB-level idempotency (unique constraint)
 * 4. Updates the invoice paid status via subtractInvoicePayment()
 * 5. Links the reconciliation to the new payment
 * 6. Dispatches ReconciliationPosted event
 *
 * Idempotency is guaranteed by the unique constraint on
 * (company_id, source_type, source_id) in the payments table.
 * Calling post() twice for the same reconciliation returns
 * PostingResult::alreadyPosted() on the second call.
 *
 * P0-12: Reconciliation Posting Service
 *
 * @version 1.0.0
 */
class ReconciliationPostingService
{
    /**
     * Source type constant for bank transaction payments.
     */
    public const SOURCE_TYPE_BANK_TRANSACTION = 'bank_transaction';

    /**
     * Payment method name used for bank reconciliation payments.
     */
    public const BANK_PAYMENT_METHOD_NAME = 'Bank Transfer';

    /**
     * Post a matched reconciliation to Payment (IDEMPOTENT).
     *
     * Uses DB-level unique constraint for true idempotency,
     * not just in-memory checks which fail on retries.
     *
     * @param  Reconciliation  $recon  The matched reconciliation to post
     * @return PostingResult Success, alreadyPosted, or error
     */
    public function post(Reconciliation $recon): PostingResult
    {
        // Eager load relationships needed for validation and posting
        $recon->loadMissing(['bankTransaction', 'invoice', 'invoice.currency']);

        $tx = $recon->bankTransaction;

        if (! $tx) {
            return PostingResult::error('Reconciliation has no associated bank transaction');
        }

        // VALIDATION: Only credit transactions can create payments
        if ($tx->transaction_type !== 'credit' && $tx->amount <= 0) {
            return PostingResult::error('Cannot create payment from debit transaction');
        }

        // VALIDATION: Reconciliation must have an invoice to post against
        if (! $recon->invoice_id) {
            return PostingResult::error('Reconciliation has no matched invoice');
        }

        $invoice = $recon->invoice;
        if (! $invoice) {
            return PostingResult::error('Matched invoice not found');
        }

        // VALIDATION: Currency compatibility check
        // BankTransaction stores currency as ISO code string (e.g., 'MKD')
        // Invoice has a currency relation with a 'code' field
        $invoiceCurrencyCode = $invoice->currency->code ?? null;
        if ($invoiceCurrencyCode && $tx->currency && $tx->currency !== $invoiceCurrencyCode) {
            return PostingResult::error(
                "Currency mismatch: transaction {$tx->currency} vs invoice {$invoiceCurrencyCode}"
            );
        }

        // VALIDATION: Invoice should not already be fully paid
        if ($invoice->paid_status === Invoice::STATUS_PAID) {
            return PostingResult::error('Invoice is already fully paid');
        }

        // Dispatch matched event before posting
        event(new ReconciliationMatched($recon));

        try {
            return DB::transaction(function () use ($recon, $tx, $invoice) {
                // Lock the reconciliation row to prevent concurrent updates
                $recon = Reconciliation::lockForUpdate()->find($recon->id);

                if (! $recon) {
                    return PostingResult::error('Reconciliation record not found during lock');
                }

                // DB-LEVEL IDEMPOTENCY: firstOrCreate with unique constraint
                // If payment already exists for this source, return it
                $paymentMethodId = $this->getBankPaymentMethodId($recon->company_id);

                $paymentAmount = abs($tx->amount); // ALWAYS positive for payments

                // Convert amount to integer cents as InvoiceShelf stores amounts in cents
                // BankTransaction stores amount as decimal:2, Payment stores as unsigned bigint (cents)
                $paymentAmountCents = (int) round($paymentAmount * 100);

                $payment = Payment::firstOrCreate(
                    [
                        'company_id' => $recon->company_id,
                        'source_type' => self::SOURCE_TYPE_BANK_TRANSACTION,
                        'source_id' => $tx->id,
                    ],
                    [
                        'invoice_id' => $recon->invoice_id,
                        'customer_id' => $invoice->customer_id,
                        'payment_method_id' => $paymentMethodId,
                        'amount' => $paymentAmountCents,
                        'payment_date' => $tx->transaction_date ?? Carbon::now(),
                        'currency_id' => $invoice->currency_id,
                        'exchange_rate' => $invoice->exchange_rate ?? 1,
                        'base_amount' => $paymentAmountCents * ($invoice->exchange_rate ?? 1),
                        'notes' => "Auto-posted from bank reconciliation #{$recon->id}",
                        'payment_number' => 'PENDING', // Will be set below
                    ]
                );

                // If payment already existed, this is a replay - return early
                if (! $payment->wasRecentlyCreated) {
                    Log::info('ReconciliationPostingService: idempotent replay detected', [
                        'reconciliation_id' => $recon->id,
                        'payment_id' => $payment->id,
                        'bank_transaction_id' => $tx->id,
                    ]);

                    return PostingResult::alreadyPosted($payment);
                }

                // Generate proper payment number and unique hash
                $serial = (new SerialNumberFormatter)
                    ->setModel($payment)
                    ->setCompany($recon->company_id)
                    ->setCustomer($invoice->customer_id)
                    ->setNextNumbers();

                $payment->payment_number = $serial->getNextNumber();
                $payment->sequence_number = $serial->nextSequenceNumber;
                $payment->customer_sequence_number = $serial->nextCustomerSequenceNumber;
                $payment->unique_hash = Hashids::connection(Payment::class)->encode($payment->id);
                $payment->save();

                // Update invoice paid status using InvoiceShelf's built-in method
                $invoice->subtractInvoicePayment($paymentAmountCents);

                // Link reconciliation to payment and mark as matched
                $recon->update([
                    'payment_id' => $payment->id,
                    'status' => Reconciliation::STATUS_MATCHED,
                    'matched_at' => now(),
                ]);

                // Mark bank transaction as processed
                $tx->markAsMatched($invoice->id, $payment->id, $recon->confidence);

                // Dispatch posted event
                event(new ReconciliationPosted($recon, $payment));

                Log::info('ReconciliationPostingService: payment posted successfully', [
                    'reconciliation_id' => $recon->id,
                    'payment_id' => $payment->id,
                    'payment_number' => $payment->payment_number,
                    'invoice_id' => $invoice->id,
                    'amount_cents' => $paymentAmountCents,
                    'bank_transaction_id' => $tx->id,
                ]);

                return PostingResult::success($payment);
            });
        } catch (\Illuminate\Database\QueryException $e) {
            // Handle unique constraint violation (concurrent duplicate post)
            if (str_contains($e->getMessage(), 'payments_idempotency_unique') ||
                str_contains($e->getMessage(), 'Duplicate entry')) {

                $existingPayment = Payment::where([
                    'company_id' => $recon->company_id,
                    'source_type' => self::SOURCE_TYPE_BANK_TRANSACTION,
                    'source_id' => $tx->id,
                ])->first();

                if ($existingPayment) {
                    Log::info('ReconciliationPostingService: concurrent duplicate caught by DB constraint', [
                        'reconciliation_id' => $recon->id,
                        'payment_id' => $existingPayment->id,
                    ]);

                    return PostingResult::alreadyPosted($existingPayment);
                }
            }

            Log::error('ReconciliationPostingService: database error during posting', [
                'reconciliation_id' => $recon->id,
                'error' => $e->getMessage(),
            ]);

            return PostingResult::error('Database error: ' . $e->getMessage());
        } catch (\Exception $e) {
            Log::error('ReconciliationPostingService: unexpected error during posting', [
                'reconciliation_id' => $recon->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return PostingResult::error('Posting failed: ' . $e->getMessage());
        }
    }

    /**
     * Get or create the bank transfer payment method for a company.
     *
     * @param  int  $companyId  The company ID
     * @return int The payment method ID
     */
    protected function getBankPaymentMethodId(int $companyId): int
    {
        $method = PaymentMethod::firstOrCreate(
            [
                'company_id' => $companyId,
                'name' => self::BANK_PAYMENT_METHOD_NAME,
            ],
            [
                'company_id' => $companyId,
                'name' => self::BANK_PAYMENT_METHOD_NAME,
            ]
        );

        return $method->id;
    }
}

// CLAUDE-CHECKPOINT
