<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * Bank Transaction Model
 * 
 * Represents imported bank transactions from PSD2 APIs, CSV imports, or manual entry
 * Supports automatic invoice matching and payment processing
 */
class BankTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_account_id',
        'company_id',
        'external_reference',
        'transaction_reference',
        'transaction_id',
        'amount',
        'currency',
        'transaction_type',
        'booking_status',
        'transaction_date',
        'booking_date',
        'value_date',
        'description',
        'remittance_info',
        'payment_reference',
        'end_to_end_id',
        'debtor_name',
        'debtor_iban',
        'debtor_account',
        'creditor_name',
        'creditor_iban',
        'creditor_account',
        'debtor_bic',
        'creditor_bic',
        'matched_invoice_id',
        'matched_payment_id',
        'matched_at',
        'match_confidence',
        'processing_status',
        'processing_notes',
        'processed_at',
        'source',
        'raw_data',
        'is_duplicate',
        'duplicate_of',
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
        'booking_date' => 'datetime',
        'value_date' => 'datetime',
        'matched_at' => 'datetime',
        'processed_at' => 'datetime',
        'amount' => 'decimal:2',
        'match_confidence' => 'decimal:2',
        'raw_data' => 'array',
        'is_duplicate' => 'boolean',
    ];

    protected $dates = [
        'transaction_date',
        'booking_date',
        'value_date',
        'matched_at',
        'processed_at',
    ];

    // Processing status constants
    const STATUS_UNPROCESSED = 'unprocessed';
    const STATUS_PROCESSED = 'processed';
    const STATUS_FAILED = 'failed';
    const STATUS_IGNORED = 'ignored';

    // Booking status constants
    const BOOKING_BOOKED = 'booked';
    const BOOKING_PENDING = 'pending';
    const BOOKING_INFO = 'info';

    // Transaction type constants
    const TYPE_CREDIT = 'credit';
    const TYPE_DEBIT = 'debit';
    const TYPE_TRANSFER = 'transfer';

    // Source constants
    const SOURCE_PSD2 = 'psd2';
    const SOURCE_CSV_IMPORT = 'csv_import';
    const SOURCE_MANUAL = 'manual';

    /**
     * Get the bank account that owns the transaction
     */
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    /**
     * Get the company that owns the transaction
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the matched invoice if any
     */
    public function matchedInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'matched_invoice_id');
    }

    /**
     * Get the matched payment if any
     */
    public function matchedPayment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'matched_payment_id');
    }

    /**
     * Get the original transaction if this is a duplicate
     */
    public function originalTransaction(): BelongsTo
    {
        return $this->belongsTo(BankTransaction::class, 'duplicate_of');
    }

    /**
     * Scope: Get unmatched transactions
     */
    public function scopeUnmatched($query)
    {
        return $query->whereNull('matched_invoice_id');
    }

    /**
     * Scope: Get matched transactions
     */
    public function scopeMatched($query)
    {
        return $query->whereNotNull('matched_invoice_id');
    }

    /**
     * Scope: Get credit transactions (incoming money)
     */
    public function scopeCredits($query)
    {
        return $query->where('amount', '>', 0);
    }

    /**
     * Scope: Get debit transactions (outgoing money)
     */
    public function scopeDebits($query)
    {
        return $query->where('amount', '<', 0);
    }

    /**
     * Scope: Get transactions for a specific company
     */
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope: Get transactions in date range
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    /**
     * Scope: Get recent transactions
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('transaction_date', '>=', Carbon::now()->subDays($days));
    }

    /**
     * Scope: By processing status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('processing_status', $status);
    }

    /**
     * Check if transaction is a credit (incoming money)
     */
    public function isCredit(): bool
    {
        return $this->amount > 0;
    }

    /**
     * Check if transaction is a debit (outgoing money)
     */
    public function isDebit(): bool
    {
        return $this->amount < 0;
    }

    /**
     * Check if transaction is matched with an invoice
     */
    public function isMatched(): bool
    {
        return !is_null($this->matched_invoice_id);
    }

    /**
     * Check if transaction is processed
     */
    public function isProcessed(): bool
    {
        return $this->processing_status === self::STATUS_PROCESSED;
    }

    /**
     * Check if transaction is a duplicate
     */
    public function isDuplicate(): bool
    {
        return $this->is_duplicate;
    }

    /**
     * Get formatted amount with currency
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2) . ' ' . $this->currency;
    }

    /**
     * Get absolute amount (always positive)
     */
    public function getAbsoluteAmountAttribute(): float
    {
        return abs($this->amount);
    }

    /**
     * Get transaction direction (credit/debit)
     */
    public function getDirectionAttribute(): string
    {
        return $this->amount > 0 ? 'credit' : 'debit';
    }

    /**
     * Get counterparty name (debtor or creditor depending on direction)
     */
    public function getCounterpartyNameAttribute(): ?string
    {
        return $this->isCredit() ? $this->debtor_name : $this->creditor_name;
    }

    /**
     * Get counterparty IBAN (debtor or creditor depending on direction)
     */
    public function getCounterpartyIbanAttribute(): ?string
    {
        return $this->isCredit() ? $this->debtor_iban : $this->creditor_iban;
    }

    /**
     * Mark transaction as processed
     */
    public function markAsProcessed(string $notes = null): void
    {
        $this->update([
            'processing_status' => self::STATUS_PROCESSED,
            'processing_notes' => $notes,
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark transaction as failed
     */
    public function markAsFailed(string $notes = null): void
    {
        $this->update([
            'processing_status' => self::STATUS_FAILED,
            'processing_notes' => $notes,
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark transaction as matched with invoice and payment
     */
    public function markAsMatched(int $invoiceId, int $paymentId, float $confidence = null): void
    {
        $this->update([
            'matched_invoice_id' => $invoiceId,
            'matched_payment_id' => $paymentId,
            'matched_at' => now(),
            'match_confidence' => $confidence,
            'processing_status' => self::STATUS_PROCESSED,
            'processed_at' => now(),
        ]);
    }

    /**
     * Clear matching information
     */
    public function clearMatch(): void
    {
        $this->update([
            'matched_invoice_id' => null,
            'matched_payment_id' => null,
            'matched_at' => null,
            'match_confidence' => null,
        ]);
    }
}