<?php

namespace App\Services\Reconciliation;

use App\Events\ReconciliationMatched;
use App\Events\ReconciliationPosted;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Reconciliation;
use App\Models\ReconciliationSplit;
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
     * Fee tolerance percentage for split payment validation.
     * Allows up to 2% difference between transaction amount and sum of splits.
     */
    public const SPLIT_FEE_TOLERANCE = 0.02;

    /**
     * Post a split payment: one bank transaction allocated across multiple invoices.
     *
     * Each split entry gets its own Payment record. The reconciliation is marked
     * as STATUS_SPLIT. All operations are wrapped in a DB transaction for atomicity.
     *
     * P0-14: Partial Payments + Multi-Invoice Settlement.
     *
     * @param  Reconciliation  $recon  The reconciliation record
     * @param  array  $splits  Array of ['invoice_id' => int, 'amount' => float]
     * @return array<PostingResult> One PostingResult per split
     */
    public function postSplit(Reconciliation $recon, array $splits): array
    {
        $recon->loadMissing(['bankTransaction']);

        $tx = $recon->bankTransaction;

        if (! $tx) {
            return [PostingResult::error('Reconciliation has no associated bank transaction')];
        }

        if ($tx->transaction_type !== 'credit' && $tx->amount <= 0) {
            return [PostingResult::error('Cannot create payment from debit transaction')];
        }

        if (empty($splits)) {
            return [PostingResult::error('No splits provided')];
        }

        // Validate: sum of splits must be within fee tolerance of transaction amount
        $transactionAmount = abs($tx->amount);
        $splitSum = array_sum(array_column($splits, 'amount'));

        if ($splitSum <= 0) {
            return [PostingResult::error('Split amounts must be positive')];
        }

        $tolerance = $transactionAmount * self::SPLIT_FEE_TOLERANCE;
        if (abs($transactionAmount - $splitSum) > $tolerance) {
            return [PostingResult::error(
                sprintf(
                    'Split total (%.2f) does not match transaction amount (%.2f) within %.0f%% tolerance',
                    $splitSum,
                    $transactionAmount,
                    self::SPLIT_FEE_TOLERANCE * 100
                )
            )];
        }

        // Check for existing splits (idempotency)
        $existingSplits = ReconciliationSplit::where('reconciliation_id', $recon->id)->count();
        if ($existingSplits > 0) {
            Log::info('ReconciliationPostingService::postSplit: idempotent replay detected', [
                'reconciliation_id' => $recon->id,
                'existing_split_count' => $existingSplits,
            ]);

            $existingPayments = ReconciliationSplit::where('reconciliation_id', $recon->id)
                ->whereNotNull('payment_id')
                ->with('payment')
                ->get();

            return $existingPayments->map(function ($split) {
                return PostingResult::alreadyPosted($split->payment);
            })->all();
        }

        try {
            return DB::transaction(function () use ($recon, $tx, $splits) {
                $recon = Reconciliation::lockForUpdate()->find($recon->id);

                if (! $recon) {
                    return [PostingResult::error('Reconciliation record not found during lock')];
                }

                $paymentMethodId = $this->getBankPaymentMethodId($recon->company_id);
                $results = [];

                foreach ($splits as $splitData) {
                    $invoiceId = $splitData['invoice_id'];
                    $allocatedAmount = (float) $splitData['amount'];

                    $invoice = Invoice::where('company_id', $recon->company_id)
                        ->where('id', $invoiceId)
                        ->first();

                    if (! $invoice) {
                        $results[] = PostingResult::error("Invoice #{$invoiceId} not found for company");
                        continue;
                    }

                    if ($invoice->paid_status === Invoice::STATUS_PAID) {
                        $results[] = PostingResult::error("Invoice #{$invoiceId} is already fully paid");
                        continue;
                    }

                    // Currency compatibility
                    $invoiceCurrencyCode = $invoice->currency->code ?? null;
                    if ($invoiceCurrencyCode && $tx->currency && $tx->currency !== $invoiceCurrencyCode) {
                        $results[] = PostingResult::error(
                            "Currency mismatch for invoice #{$invoiceId}: transaction {$tx->currency} vs invoice {$invoiceCurrencyCode}"
                        );
                        continue;
                    }

                    $paymentAmountCents = (int) round($allocatedAmount * 100);

                    // Create payment with unique source_id combining tx.id and invoice.id
                    // to allow multiple payments from the same transaction
                    $splitSourceId = "{$tx->id}_split_{$invoiceId}";

                    $payment = Payment::firstOrCreate(
                        [
                            'company_id' => $recon->company_id,
                            'source_type' => self::SOURCE_TYPE_BANK_TRANSACTION,
                            'source_id' => $splitSourceId,
                        ],
                        [
                            'invoice_id' => $invoiceId,
                            'customer_id' => $invoice->customer_id,
                            'payment_method_id' => $paymentMethodId,
                            'amount' => $paymentAmountCents,
                            'payment_date' => $tx->transaction_date ?? Carbon::now(),
                            'currency_id' => $invoice->currency_id,
                            'exchange_rate' => $invoice->exchange_rate ?? 1,
                            'base_amount' => $paymentAmountCents * ($invoice->exchange_rate ?? 1),
                            'notes' => "Split payment from bank reconciliation #{$recon->id}",
                            'payment_number' => 'PENDING',
                        ]
                    );

                    if (! $payment->wasRecentlyCreated) {
                        $results[] = PostingResult::alreadyPosted($payment);
                        continue;
                    }

                    // Generate proper payment number
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

                    // Update invoice paid status
                    $invoice->subtractInvoicePayment($paymentAmountCents);

                    // Create split record
                    ReconciliationSplit::create([
                        'reconciliation_id' => $recon->id,
                        'invoice_id' => $invoiceId,
                        'allocated_amount' => $allocatedAmount,
                        'payment_id' => $payment->id,
                    ]);

                    $results[] = PostingResult::success($payment);
                }

                // Mark reconciliation as split
                $recon->update([
                    'status' => Reconciliation::STATUS_SPLIT,
                    'matched_at' => now(),
                ]);

                // Mark bank transaction as processed
                $firstInvoiceId = $splits[0]['invoice_id'] ?? null;
                $firstPayment = $results[0]->payment ?? null;

                if ($firstInvoiceId && $firstPayment) {
                    $tx->markAsMatched($firstInvoiceId, $firstPayment->id, $recon->confidence);
                }

                Log::info('ReconciliationPostingService::postSplit: split payments posted', [
                    'reconciliation_id' => $recon->id,
                    'split_count' => count($results),
                    'bank_transaction_id' => $tx->id,
                ]);

                return $results;
            });
        } catch (\Exception $e) {
            Log::error('ReconciliationPostingService::postSplit: error during split posting', [
                'reconciliation_id' => $recon->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [PostingResult::error('Split posting failed: ' . $e->getMessage())];
        }
    }

    /**
     * Post a partial payment (installment): transaction covers part of an invoice.
     *
     * Same as post() but does NOT error if the transaction amount is less than
     * the invoice total. The invoice will remain in a partially-paid state.
     *
     * P0-14: Partial Payments + Multi-Invoice Settlement.
     *
     * @param  Reconciliation  $recon  The reconciliation with a matched invoice
     * @return PostingResult
     */
    public function postPartial(Reconciliation $recon): PostingResult
    {
        $recon->loadMissing(['bankTransaction', 'invoice', 'invoice.currency']);

        $tx = $recon->bankTransaction;

        if (! $tx) {
            return PostingResult::error('Reconciliation has no associated bank transaction');
        }

        if ($tx->transaction_type !== 'credit' && $tx->amount <= 0) {
            return PostingResult::error('Cannot create payment from debit transaction');
        }

        if (! $recon->invoice_id) {
            return PostingResult::error('Reconciliation has no matched invoice');
        }

        $invoice = $recon->invoice;
        if (! $invoice) {
            return PostingResult::error('Matched invoice not found');
        }

        // Currency compatibility
        $invoiceCurrencyCode = $invoice->currency->code ?? null;
        if ($invoiceCurrencyCode && $tx->currency && $tx->currency !== $invoiceCurrencyCode) {
            return PostingResult::error(
                "Currency mismatch: transaction {$tx->currency} vs invoice {$invoiceCurrencyCode}"
            );
        }

        // NOTE: Unlike post(), we do NOT check if invoice is already fully paid
        // because partial payments can be applied to invoices that already have some payments
        if ($invoice->paid_status === Invoice::STATUS_PAID) {
            return PostingResult::error('Invoice is already fully paid');
        }

        event(new ReconciliationMatched($recon));

        try {
            return DB::transaction(function () use ($recon, $tx, $invoice) {
                $recon = Reconciliation::lockForUpdate()->find($recon->id);

                if (! $recon) {
                    return PostingResult::error('Reconciliation record not found during lock');
                }

                $paymentMethodId = $this->getBankPaymentMethodId($recon->company_id);
                $paymentAmount = abs($tx->amount);
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
                        'notes' => "Partial payment from bank reconciliation #{$recon->id}",
                        'payment_number' => 'PENDING',
                    ]
                );

                if (! $payment->wasRecentlyCreated) {
                    Log::info('ReconciliationPostingService::postPartial: idempotent replay detected', [
                        'reconciliation_id' => $recon->id,
                        'payment_id' => $payment->id,
                    ]);

                    return PostingResult::alreadyPosted($payment);
                }

                // Generate payment number
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

                // Update invoice paid status (partial payment)
                $invoice->subtractInvoicePayment($paymentAmountCents);

                // Mark reconciliation as partial
                $recon->update([
                    'payment_id' => $payment->id,
                    'status' => Reconciliation::STATUS_PARTIAL,
                    'matched_at' => now(),
                ]);

                // Mark bank transaction as processed
                $tx->markAsMatched($invoice->id, $payment->id, $recon->confidence);

                event(new ReconciliationPosted($recon, $payment));

                Log::info('ReconciliationPostingService::postPartial: partial payment posted', [
                    'reconciliation_id' => $recon->id,
                    'payment_id' => $payment->id,
                    'payment_amount_cents' => $paymentAmountCents,
                    'invoice_id' => $invoice->id,
                    'bank_transaction_id' => $tx->id,
                ]);

                return PostingResult::success($payment);
            });
        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), 'payments_idempotency_unique') ||
                str_contains($e->getMessage(), 'Duplicate entry')) {

                $existingPayment = Payment::where([
                    'company_id' => $recon->company_id,
                    'source_type' => self::SOURCE_TYPE_BANK_TRANSACTION,
                    'source_id' => $tx->id,
                ])->first();

                if ($existingPayment) {
                    return PostingResult::alreadyPosted($existingPayment);
                }
            }

            Log::error('ReconciliationPostingService::postPartial: database error', [
                'reconciliation_id' => $recon->id,
                'error' => $e->getMessage(),
            ]);

            return PostingResult::error('Database error: ' . $e->getMessage());
        } catch (\Exception $e) {
            Log::error('ReconciliationPostingService::postPartial: unexpected error', [
                'reconciliation_id' => $recon->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return PostingResult::error('Partial posting failed: ' . $e->getMessage());
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
