<?php

namespace Modules\Mk\Models;

use App\Models\Bill;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Payment Batch Item Model
 *
 * Represents a single payment instruction within a batch.
 * Each item corresponds to one creditor transfer (typically one bill).
 *
 * @property int $id
 * @property int $payment_batch_id
 * @property int|null $bill_id
 * @property string $creditor_name
 * @property string|null $creditor_iban
 * @property string|null $creditor_bic
 * @property string|null $creditor_bank_name
 * @property int $amount Amount in cents
 * @property string $currency_code
 * @property string|null $purpose_code
 * @property string|null $payment_reference
 * @property string|null $description
 * @property string $status pending|exported|confirmed|failed
 * @property \Carbon\Carbon|null $reconciled_at
 * @property int|null $bank_transaction_id
 */
class PaymentBatchItem extends Model
{
    protected $table = 'payment_batch_items';

    protected $fillable = [
        'payment_batch_id',
        'bill_id',
        'creditor_name',
        'creditor_iban',
        'creditor_bic',
        'creditor_bank_name',
        'amount',
        'currency_code',
        'purpose_code',
        'payment_reference',
        'description',
        'status',
        'reconciled_at',
        'bank_transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'reconciled_at' => 'datetime',
        ];
    }

    // Status constants
    public const STATUS_PENDING = 'pending';

    public const STATUS_EXPORTED = 'exported';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_FAILED = 'failed';

    // ----- Relationships -----

    public function paymentBatch(): BelongsTo
    {
        return $this->belongsTo(PaymentBatch::class, 'payment_batch_id');
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    // ----- Accessors -----

    /**
     * Get formatted amount (amount / 100).
     */
    public function getFormattedAmountAttribute(): float
    {
        return $this->amount / 100;
    }
}

// CLAUDE-CHECKPOINT
