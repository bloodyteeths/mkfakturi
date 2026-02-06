<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ReconciliationSplit Model
 *
 * Tracks the allocation of a single bank transaction across multiple invoices
 * (split payment) or partial payment amounts against a single invoice.
 *
 * Part of P0-14: Partial Payments + Multi-Invoice Settlement.
 *
 * No BelongsToCompany trait needed — company_id is resolved through
 * the parent reconciliation record.
 *
 * @property int $id
 * @property int $reconciliation_id
 * @property int $invoice_id
 * @property float $allocated_amount
 * @property int|null $payment_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ReconciliationSplit extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'reconciliation_id',
        'invoice_id',
        'allocated_amount',
        'payment_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'allocated_amount' => 'decimal:2',
        ];
    }

    /**
     * Get the reconciliation this split belongs to.
     */
    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(Reconciliation::class);
    }

    /**
     * Get the invoice this split is allocated to.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the payment created for this split allocation.
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}

// CLAUDE-CHECKPOINT
